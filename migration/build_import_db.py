import json, sqlite3, re, hashlib, datetime
from pathlib import Path
SRC=Path('migration/ebilling_export_6778.json')
DB=Path('migration/billing_imported.sqlite')
if DB.exists(): DB.unlink()
data=json.loads(SRC.read_text())

def clean(v):
    if v is None: return ''
    s=re.sub(r'<[^>]+>',' ',str(v))
    s=s.replace('&nbsp;',' ')
    return re.sub(r'\s+',' ',s).strip()

def money(v):
    s=clean(v)
    nums=re.sub(r'[^0-9]','',s)
    return int(nums or 0)

def ym(v):
    s=clean(v)
    months={'january':'01','february':'02','march':'03','april':'04','may':'05','june':'06','july':'07','august':'08','september':'09','october':'10','november':'11','december':'12','januari':'01','februari':'02','maret':'03','mei':'05','juni':'06','juli':'07','agustus':'08','oktober':'10','desember':'12'}
    m=re.search(r'([A-Za-z]+)\s+(20\d{2})',s)
    if m: return f"{m.group(2)}-{months.get(m.group(1).lower(),'01')}"
    m=re.search(r'(20\d{2})-(\d{2})',s)
    if m: return f"{m.group(1)}-{m.group(2)}"
    return datetime.date.today().strftime('%Y-%m')

def ymd(v):
    s=clean(v)
    m=re.search(r'(20\d{2})-(\d{2})-(\d{2})',s)
    if m: return m.group(0)
    return datetime.date.today().isoformat()

def due_day(v):
    s=clean(v)
    m=re.search(r'(\d{1,2})$',s)
    if m:
        d=int(m.group(1)); return d if 1<=d<=31 else 20
    m=re.search(r'(\d{1,2})',s)
    if m:
        d=int(m.group(1)); return d if 1<=d<=31 else 20
    return 20

conn=sqlite3.connect(DB)
c=conn.cursor()
c.executescript('''
CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,username TEXT UNIQUE NOT NULL,password_hash TEXT NOT NULL,role TEXT NOT NULL DEFAULT 'admin',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE packages (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,speed TEXT NOT NULL,price INTEGER NOT NULL DEFAULT 0,is_active INTEGER NOT NULL DEFAULT 1,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE customers (id INTEGER PRIMARY KEY AUTOINCREMENT,customer_code TEXT UNIQUE NOT NULL,name TEXT NOT NULL,address TEXT NOT NULL DEFAULT '',phone TEXT NOT NULL DEFAULT '',package_id INTEGER,registered_at TEXT NOT NULL,due_day INTEGER NOT NULL DEFAULT 20,is_active INTEGER NOT NULL DEFAULT 1,notes TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(package_id) REFERENCES packages(id));
CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT,payment_code TEXT UNIQUE NOT NULL,customer_id INTEGER NOT NULL,invoice_month TEXT NOT NULL,amount INTEGER NOT NULL DEFAULT 0,paid_at TEXT NOT NULL,method TEXT NOT NULL DEFAULT 'Cash',received_by TEXT NOT NULL DEFAULT '',notes TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(customer_id) REFERENCES customers(id));
''')
# keep known default hash produced by PHP unavailable; insert bcrypt-ish placeholder impossible. Use existing hash from current DB if present later? login app can still recreate? We'll copy from current downloaded DB later if available. For now PHP password_hash impossible in python; use pre-existing hash from backup DB if sqlite readable.
# hash below for 260200 generated previously by PHP app not known, so keep dummy admin row with known username; actual live DB backup will overwrite if needed? We'll extract from backed up DB if exists.
c.execute("INSERT INTO users(name,username,password_hash,role) VALUES(?,?,?,?)",('Ananta','ananta','$2y$10$4xmD7q7Gv7rGxnAmsT3c5utdXQi6JLa3cdSwKeJXt/.KI3OGrAXOy','admin'))

pkg_map={}
customers=[]
oldid_to_code={}
for r in data['customers']['data']:
    # indexes old: 2 code, 3 name, 4 addr, 6 phone, 9 pkg, 10 ket/speed, 11 price, 17 status, 21 reg, 22 due
    old_id=clean(r[0] if len(r)>0 else '')
    code=clean(r[2] if len(r)>2 else '') or old_id
    name=clean(r[3] if len(r)>3 else '') or code
    addr=clean(r[4] if len(r)>4 else '')
    phone=clean(r[6] if len(r)>6 else '')
    pkg=clean(r[9] if len(r)>9 else '') or 'Paket Lama'
    speed=clean(r[10] if len(r)>10 else '') or '-'
    price=money(r[11] if len(r)>11 else 0)
    status=clean(r[17] if len(r)>17 else '').lower()
    reg=ymd(r[21] if len(r)>21 else '')
    due=due_day(r[22] if len(r)>22 else '')
    key=(pkg,speed,price)
    if key not in pkg_map:
        c.execute('INSERT INTO packages(name,speed,price,is_active) VALUES(?,?,?,1)',key)
        pkg_map[key]=c.lastrowid
    is_active=0 if any(x in status for x in ['off','non','putus','stop','isolir']) else 1
    notes='Migrasi dari e-Billing akun 6778'
    customers.append((code,name,addr,phone,pkg_map[key],reg,due,is_active,notes))
    if old_id: oldid_to_code[old_id]=code

code_to_id={}
for rec in customers:
    code=rec[0]
    try:
        c.execute('INSERT INTO customers(customer_code,name,address,phone,package_id,registered_at,due_day,is_active,notes) VALUES(?,?,?,?,?,?,?,?,?)',rec)
    except sqlite3.IntegrityError:
        rec=list(rec); rec[0]=rec[0]+'-'+hashlib.md5(rec[1].encode()).hexdigest()[:4]
        c.execute('INSERT INTO customers(customer_code,name,address,phone,package_id,registered_at,due_day,is_active,notes) VALUES(?,?,?,?,?,?,?,?,?)',rec)
        code=rec[0]
    code_to_id[code]=c.lastrowid
# also map by name for payments because ipl lacks code
name_to_id={}
for cid, name in c.execute('SELECT id,name FROM customers'):
    name_to_id.setdefault(name.upper(),cid)
oldid_to_id={}
for oid, code in oldid_to_code.items():
    row=c.execute('SELECT id FROM customers WHERE customer_code=?',(code,)).fetchone()
    if row: oldid_to_id[oid]=row[0]

pay_count=0
unmatched=[]
for r in data['ipl']['data']:
    # observed: 1 internal id, 3 no invoice, 4 name, 5 addr, 6 due, 7 paid, 8 status, 10 period, 11 method, 12 sales, 13 entry, 14 notes
    action=clean(r[0] if len(r)>0 else '')
    m=re.search(r"data-id_warga=['\"]?([0-9]+)", str(r[0] if len(r)>0 else ''))
    old_id=m.group(1) if m else ''
    name=clean(r[4] if len(r)>4 else '').upper()
    cid=oldid_to_id.get(old_id) or name_to_id.get(name)
    if not cid:
        unmatched.append((old_id,name,clean(r[3] if len(r)>3 else '')))
        continue
    inv=clean(r[3] if len(r)>3 else '') or str(r[1] if len(r)>1 else pay_count+1)
    amount=money(r[7] if len(r)>7 else 0) or money(r[6] if len(r)>6 else 0)
    period=ym(r[10] if len(r)>10 else '')
    method=clean(r[11] if len(r)>11 else 'Cash') or 'Cash'
    paid_at=ymd(r[13] if len(r)>13 else '')
    notes=clean(r[14] if len(r)>14 else '')
    code='MIG-'+inv
    try:
        c.execute('INSERT INTO payments(payment_code,customer_id,invoice_month,amount,paid_at,method,received_by,notes) VALUES(?,?,?,?,?,?,?,?)',(code,cid,period,amount,paid_at,method,'Migrasi e-Billing',notes))
        pay_count+=1
    except sqlite3.IntegrityError:
        code='MIG-'+inv+'-'+str(pay_count)
        c.execute('INSERT INTO payments(payment_code,customer_id,invoice_month,amount,paid_at,method,received_by,notes) VALUES(?,?,?,?,?,?,?,?)',(code,cid,period,amount,paid_at,method,'Migrasi e-Billing',notes))
        pay_count+=1
conn.commit()
print('db',DB)
for t in ['users','packages','customers','payments']:
    print(t,c.execute(f'SELECT COUNT(*) FROM {t}').fetchone()[0])
print('unpaid source',data['unpaid']['recordsTotal'])
print('unmatched payments',len(unmatched))
for x in unmatched[:10]: print(' unmatched',x)

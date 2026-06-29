# Blueprint Billing Internet Dentanet

Tanggal: 2026-06-28
Domain target: `https://billing.dentasejahteragroup.my.id/`
Repo: `https://github.com/remek8787/billing-dentasejahteragroup.my.id`

## Tujuan

Membangun sistem billing internet sederhana, cepat dipakai, dan mudah dikembangkan untuk operasional Dentanet/Denta Sejahtera Group.

Fokus versi awal:

- Login admin.
- Dashboard ringkasan pelanggan dan pembayaran.
- Master paket internet.
- Data pelanggan.
- Pembayaran dan history pembayaran.
- Laporan pemasukan harian/bulanan.
- Status pelanggan ON/OFF.

## Prinsip Produk

1. **Mudah untuk operator**: semua aksi penting terlihat jelas di tabel pelanggan.
2. **Tagihan mudah dilacak**: setiap pelanggan punya status lunas/belum lunas, jatuh tempo, dan riwayat pembayaran.
3. **Pemasukan bisa diaudit**: laporan harian/bulanan bisa ditelusuri ke transaksi pembayaran.
4. **Siap berkembang**: struktur dibuat agar nanti bisa ditambah export Excel/PDF, invoice, WhatsApp reminder, dan integrasi MikroTik.

## Role Awal

### Admin

- Login.
- Kelola paket.
- Kelola pelanggan.
- Tambah pembayaran.
- Lihat history.
- Aktif/nonaktifkan pelanggan.
- Lihat laporan.

Role lanjutan yang bisa ditambah:

- Kasir.
- Teknisi.
- Owner/viewer.

## Modul Sistem

### 1. Login

Field:

- Username.
- Password.

Catatan MVP statis/demo:

- Login versi prototype memakai kredensial default dari owner, namun tidak ditampilkan di halaman login. Versi backend wajib memakai password hash dan session/token aman.
- Versi backend wajib memakai password hash dan session/token aman.

### 2. Dashboard Utama

Kartu statistik:

- Pelanggan ON.
- Pelanggan OFF.
- Sudah lunas.
- Belum lunas.
- Pemasukan hari ini.
- Pemasukan bulan ini.

Komponen tambahan:

- Grafik pemasukan bulanan.
- Tabel pelanggan jatuh tempo terdekat.
- Tabel pembayaran terakhir.

### 3. Paket Internet

Field:

- ID paket.
- Nama paket.
- Kecepatan.
- Harga.
- Status aktif/nonaktif.

Aksi:

- Tambah paket.
- Edit paket.
- Nonaktifkan paket.

### 4. Data Pelanggan

Kolom tabel utama sesuai konsep Tuan Besar:

- No.
- Aksi:
  - Edit data pelanggan.
  - Nonaktifkan pelanggan.
  - History pembayaran pelanggan.
  - Tambah/lakukan pembayaran.
- ID Pelanggan.
- Nama Pelanggan.
- Alamat.
- Telepon.
- Nama Langganan/Paket.
- Harga.
- Pembayaran Terakhir.
- Tanggal Registrasi.
- Tanggal Jatuh Tempo.
- Status pelanggan: ON/OFF.
- Status tagihan: Lunas/Belum Lunas.

### 5. Pembayaran

Field transaksi:

- ID pembayaran.
- ID pelanggan.
- Bulan tagihan.
- Nominal.
- Tanggal bayar.
- Metode bayar: cash/transfer/lainnya.
- Admin penerima.
- Catatan.

Aksi:

- Tambah pembayaran dari tabel pelanggan.
- Lihat history pelanggan.
- Cetak/kirim struk pada versi lanjutan.

### 6. Pelanggan Sudah Lunas

Filter otomatis dari status tagihan bulan berjalan.

### 7. Pelanggan Belum Lunas

Filter otomatis dari status tagihan bulan berjalan.

Prioritas operasional:

- Tampilkan pelanggan melewati jatuh tempo.
- Tampilkan tombol bayar cepat.
- Tampilkan tombol reminder WhatsApp pada versi lanjutan.

### 8. Laporan Pemasukan

Kebutuhan:

- Pemasukan hari ini.
- Pemasukan bulan ini.
- Filter bulan sebelumnya.
- Filter rentang tanggal.
- Trace ke data transaksi.

Versi lanjutan:

- Export Excel.
- Export PDF.
- Rekap per paket.
- Rekap per metode bayar.

## Struktur Database Rekomendasi

### users

- id
- name
- username
- password_hash
- role
- created_at
- updated_at

### packages

- id
- package_code
- name
- speed
- price
- is_active
- created_at
- updated_at

### customers

- id
- customer_code
- name
- address
- phone
- package_id
- registered_at
- due_day
- due_date
- is_active
- notes
- created_at
- updated_at

### payments

- id
- payment_code
- customer_id
- invoice_month
- amount
- paid_at
- method
- received_by_user_id
- notes
- created_at
- updated_at

### invoices/tagihan — opsional untuk versi lebih rapi

- id
- customer_id
- invoice_month
- amount
- due_date
- status: unpaid/paid/void
- paid_at
- payment_id
- created_at
- updated_at

## Status dan Logika

### Status pelanggan

- ON: pelanggan aktif.
- OFF: pelanggan nonaktif/isolir.

### Status tagihan

- Lunas: invoice/tagihan bulan berjalan sudah dibayar.
- Belum Lunas: belum ada pembayaran untuk bulan berjalan atau invoice belum paid.

### Pembayaran terakhir

Diambil dari transaksi pembayaran terbaru milik pelanggan.

### Tanggal jatuh tempo

Versi sederhana:

- Setiap pelanggan punya `due_day`.
- Sistem menghitung tanggal jatuh tempo bulan berjalan.

## Rekomendasi Stack

### MVP cepat di shared hosting

- HTML/CSS/JS frontend.
- PHP sederhana + MySQL/MariaDB.
- Cocok untuk cPanel/shared hosting.

### Versi lebih matang

- Laravel + MySQL.
- Auth bawaan Laravel.
- Migration dan model rapi.
- Lebih siap untuk laporan, export, role, API MikroTik, dan WhatsApp reminder.

## Roadmap

### Phase 1 — Prototype UI

- Dashboard statis/interaktif frontend.
- Simulasi data pelanggan.
- Tabel pelanggan + filter lunas/belum lunas/ON/OFF.
- Modal tambah pembayaran dan history.

### Phase 2 — Backend MVP

- Login admin.
- CRUD paket.
- CRUD pelanggan.
- Simpan pembayaran.
- Laporan pemasukan.

### Phase 3 — Operasional

- Export Excel/PDF.
- Invoice/struk pembayaran.
- Reminder WhatsApp manual.
- Backup database.

### Phase 4 — Integrasi jaringan

- Integrasi MikroTik/PPPoE bila diperlukan.
- Auto isolir/aktifkan.
- Sinkron status pelanggan.

## Catatan Deploy

Akses FTP yang diberikan pada 2026-06-28 gagal login dengan pesan:

`530 Login authentication failed`

Jadi deployment hosting menunggu akses FTP benar/aktif.

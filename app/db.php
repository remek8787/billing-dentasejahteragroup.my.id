<?php
const APP_NAME = 'Dentanet Billing';
const DEFAULT_USER = 'ananta';
const DEFAULT_PASS_A = '260';
const DEFAULT_PASS_B = '200';

date_default_timezone_set('Asia/Makassar');

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $pdo = new PDO('sqlite:' . $dir . '/billing.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    init_db($pdo);
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'admin',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS packages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        speed TEXT NOT NULL,
        price INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_code TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        address TEXT NOT NULL DEFAULT '',
        phone TEXT NOT NULL DEFAULT '',
        package_id INTEGER,
        registered_at TEXT NOT NULL,
        due_day INTEGER NOT NULL DEFAULT 20,
        is_active INTEGER NOT NULL DEFAULT 1,
        router_name TEXT NOT NULL DEFAULT '',
        onu_name TEXT NOT NULL DEFAULT '',
        notes TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(package_id) REFERENCES packages(id)
    )");
    try { $pdo->exec("ALTER TABLE customers ADD COLUMN router_name TEXT NOT NULL DEFAULT ''"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE customers ADD COLUMN onu_name TEXT NOT NULL DEFAULT ''"); } catch (Throwable $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        payment_code TEXT UNIQUE NOT NULL,
        customer_id INTEGER NOT NULL,
        invoice_month TEXT NOT NULL,
        amount INTEGER NOT NULL DEFAULT 0,
        paid_at TEXT NOT NULL,
        method TEXT NOT NULL DEFAULT 'Cash',
        received_by TEXT NOT NULL DEFAULT '',
        notes TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(customer_id) REFERENCES customers(id)
    )");

    $exists = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if (!$exists) {
        $stmt = $pdo->prepare("INSERT INTO users(name,username,password_hash,role) VALUES(?,?,?,?)");
        $stmt->execute(['Ananta', DEFAULT_USER, password_hash(DEFAULT_PASS_A . DEFAULT_PASS_B, PASSWORD_DEFAULT), 'admin']);
    }
    $pkgCount = (int)$pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    if (!$pkgCount) {
        $stmt = $pdo->prepare("INSERT INTO packages(name,speed,price,is_active) VALUES(?,?,?,1)");
        foreach ([['Home 10 Mbps','10 Mbps',150000],['Home 15 Mbps','15 Mbps',180000],['Home 20 Mbps','20 Mbps',220000],['UMKM 30 Mbps','30 Mbps',350000]] as $p) $stmt->execute($p);
    }
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rupiah($n): string { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
function redirect($to): never { header('Location: ' . $to); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function csrf_check(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']))) { http_response_code(419); exit('CSRF token tidak valid.'); } }
function is_logged_in(): bool { return !empty($_SESSION['user']); }
function require_login(): void { if (!is_logged_in()) redirect('login.php'); }
function current_month(): string { return date('Y-m'); }
function next_customer_code(PDO $pdo): string { $n=(int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn()+1; return 'DN-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT); }
function payment_code(): string { return 'PAY-' . date('Ymd-His') . '-' . random_int(100,999); }

function app_stats(PDO $pdo): array {
    $month=current_month(); $today=date('Y-m-d');
    $total=(int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $on=(int)$pdo->query("SELECT COUNT(*) FROM customers WHERE is_active=1")->fetchColumn();
    $off=$total-$on;
    $stmt=$pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM payments WHERE invoice_month=?"); $stmt->execute([$month]); $paid=(int)$stmt->fetchColumn();
    $unpaid=max(0,$total-$paid);
    $stmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at=?"); $stmt->execute([$today]); $todayIncome=(int)$stmt->fetchColumn();
    $stmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_month=?"); $stmt->execute([$month]); $monthIncome=(int)$stmt->fetchColumn();
    return compact('total','on','off','paid','unpaid','todayIncome','monthIncome','month');
}

function cell_attr(string $label): string { return ' data-label= . e($label) . '; }

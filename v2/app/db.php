<?php
const APP_NAME = 'Billing Internet v2';
date_default_timezone_set('Asia/Makassar');
function db(): PDO {
    static $pdo=null;
    if($pdo) return $pdo;
    $dir=__DIR__.'/../data'; if(!is_dir($dir)) mkdir($dir,0755,true);
    $pdo=new PDO('sqlite:'.$dir.'/billing-v2.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
    init_db($pdo);
    return $pdo;
}
function init_db(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_code TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        phone TEXT NOT NULL DEFAULT '',
        address TEXT NOT NULL DEFAULT '',
        package_name TEXT NOT NULL DEFAULT '',
        monthly_fee INTEGER NOT NULL DEFAULT 0,
        due_day INTEGER NOT NULL DEFAULT 20,
        router_name TEXT NOT NULL DEFAULT '',
        pppoe_user TEXT NOT NULL DEFAULT '',
        status TEXT NOT NULL DEFAULT 'active',
        notes TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
}
function e($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function rupiah($n): string { return 'Rp '.number_format((int)$n,0,',','.'); }
function next_customer_code(PDO $pdo): string { $n=(int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn()+1; return 'NET-'.str_pad((string)$n,5,'0',STR_PAD_LEFT); }
function redirect($url): never { header('Location: '.$url); exit; }
function money_to_int($v): int { return (int)preg_replace('/[^0-9]/','',(string)$v); }

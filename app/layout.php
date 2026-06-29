<?php
function billing_active_nav(): string {
    $script = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
    $map = ['index.php'=>'dashboard','packages.php'=>'packages','customers.php'=>'customers','payments.php'=>'payments','reports.php'=>'reports'];
    return $map[$script] ?? '';
}
function billing_nav_items(string $active): array {
    $items = [
        ['dashboard','index.php','⌂','Dashboard'],
        ['packages','packages.php','◫','Paket'],
        ['customers','customers.php','◉','Pelanggan'],
        ['payments','payments.php','◆','Pembayaran'],
        ['reports','reports.php','▤','Laporan'],
    ];
    foreach($items as &$it) $it[] = $it[0] === $active;
    return $items;
}
function render_header(string $title='Dashboard'): void { $active=billing_active_nav(); $nav=billing_nav_items($active); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#f8fafc">
  <title><?=e($title)?> - Dentanet Billing</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-body finance-ui">
  <div class="finance-bg"><span></span><span></span><span></span></div>
  <div class="mobile-shade" id="mobileShade" onclick="toggleSidebar(false)"></div>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <img src="assets/dentanet-logo.png" alt="Dentanet">
        <div class="brand-text"><b>Dentanet</b><span>Finance Billing</span></div>
      </div>
      <div class="nav-caption">Menu utama</div>
      <nav>
        <?php foreach($nav as $item): ?>
          <a class="<?=$item[4]?'active':''?>" href="<?=e($item[1])?>" title="<?=e($item[3])?>"><i><?=e($item[2])?></i><span><?=e($item[3])?></span></a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-insight">
        <div class="insight-dot"></div>
        <b>Billing Center</b>
        <p>Kelola pelanggan, tagihan, dan pemasukan dari satu workspace.</p>
      </div>
      <div class="sidebar-foot">
        <div class="operator-card"><span class="avatar-mini">A</span><div class="operator-text">Operator<br><b><?=e($_SESSION['user']['name'] ?? 'Admin')?></b></div></div>
        <a class="logout" href="logout.php">Logout</a>
      </div>
    </aside>
    <main class="main">
      <header class="topbar">
        <div class="top-left">
          <button class="icon-btn" type="button" onclick="toggleSidebar()" aria-label="Hide/show sidebar">☰</button>
          <div><p class="eyebrow">Dentanet Billing System</p><h1><?=e($title)?></h1></div>
        </div>
        <div class="top-actions"><a class="btn primary" href="payments.php?action=new">+ Pembayaran</a><a class="btn soft" href="customers.php?action=new">+ Pelanggan</a></div>
      </header>
<?php } function render_footer(): void { ?>
    </main>
  </div>
<script src="assets/app.js"></script>
<script>
(function(){ if(localStorage.getItem('billingSidebarHidden')==='1') document.body.classList.add('sidebar-hidden'); })();
function toggleSidebar(forceOpen){
  const mobile = window.matchMedia('(max-width: 900px)').matches;
  if(mobile){ const open = forceOpen===undefined ? !document.body.classList.contains('sidebar-open') : forceOpen; document.body.classList.toggle('sidebar-open', open); return; }
  document.body.classList.toggle('sidebar-hidden');
  localStorage.setItem('billingSidebarHidden', document.body.classList.contains('sidebar-hidden')?'1':'0');
}

let touchStartX=0, touchStartY=0;
document.addEventListener('touchstart',e=>{const t=e.touches[0];touchStartX=t.clientX;touchStartY=t.clientY;},{passive:true});
document.addEventListener('touchend',e=>{const t=e.changedTouches[0];const dx=t.clientX-touchStartX;const dy=Math.abs(t.clientY-touchStartY);if(dy>60)return;if(touchStartX<28&&dx>80)toggleSidebar(true);if(document.body.classList.contains('sidebar-open')&&dx<-80)toggleSidebar(false);},{passive:true});
</script>
</body></html><?php } ?>

<?php
// ============================================================
// CUSTOMERS.PHP — Müşteri Listesi
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

// Müşteri listesi, tedarikçi ilişkisi ve proje bilgileri ile
$customers = $conn->query("
    SELECT c.id, c.first_name, c.last_name, c.phn_nmbr, c.email,
           CONCAT(sup.first_name, ' ', sup.last_name) AS supplier_name,
           sup.email AS supplier_email,
           COUNT(pr.id) AS proje_count,
           COALESCE(SUM(pr.sale_price), 0) AS toplam_satis,
           COALESCE(SUM(pr.budget), 0)     AS toplam_butce
    FROM customer_ c
    LEFT JOIN supplier_ sup ON c.splr_id = sup.id
    LEFT JOIN project_  pr  ON pr.customer_id = c.id
    GROUP BY c.id, c.first_name, c.last_name, c.phn_nmbr, c.email, sup.first_name, sup.last_name, sup.email
    ORDER BY c.first_name
")->fetch_all(MYSQLI_ASSOC);

// Her müşterinin projeleri
$projectsByCustomer = [];
$rows = $conn->query("
    SELECT pr.id, pr.name, pr.budget, pr.sale_price, pr.start_date, pr.end_date,
           pr.customer_id,
           CONCAT(sup.first_name,' ',sup.last_name) AS supplier_name
    FROM project_ pr
    LEFT JOIN customer_ cust ON pr.customer_id = cust.id
    LEFT JOIN supplier_ sup ON cust.splr_id = sup.id
    WHERE pr.customer_id IS NOT NULL
    ORDER BY pr.start_date DESC
")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $r) { $projectsByCustomer[$r['customer_id']][] = $r; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Müşteriler</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root{--bg:#0a0e14;--panel:#111820;--border:#1e3a4a;--accent:#00d4ff;--text:#c8d8e8;--muted:#4a6070;--success:#00ff88;--warn:#ffaa00;--error:#ff4060;--purple:#a855f7;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{background:var(--bg);font-family:'Rajdhani',sans-serif;color:var(--text);min-height:100vh;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;}
  nav{background:var(--panel);border-bottom:1px solid var(--border);padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:60px;position:sticky;top:0;z-index:100;}
  .nav-brand{font-size:18px;font-weight:700;letter-spacing:3px;color:#fff;} .nav-brand span{color:var(--accent);}
  .nav-links{display:flex;gap:4px;flex-wrap:wrap;}
  .nav-links a{color:var(--muted);text-decoration:none;font-size:12px;letter-spacing:1px;padding:6px 12px;border:1px solid transparent;transition:all .2s;text-transform:uppercase;}
  .nav-links a:hover,.nav-links a.active{color:var(--accent);border-color:var(--border);}
  .nav-sep{color:var(--border);align-self:center;font-size:16px;padding:0 4px;}
  .nav-user{display:flex;align-items:center;gap:16px;font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);}
  .nav-user a{color:var(--error);text-decoration:none;font-size:11px;letter-spacing:1px;border:1px solid var(--error);padding:4px 10px;transition:all .2s;}
  .nav-user a:hover{background:var(--error);color:var(--bg);}
  main{padding:32px;position:relative;z-index:1;}
  h2{font-size:24px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:28px;} h2 span{color:var(--accent);}
  .filter-bar{background:var(--panel);border:1px solid var(--border);padding:16px 24px;margin-bottom:24px;display:flex;gap:16px;align-items:flex-end;}
  .filter-group{display:flex;flex-direction:column;gap:6px;}
  .filter-group label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);}
  .filter-group input{background:var(--bg);border:1px solid var(--border);color:var(--text);font-family:'Share Tech Mono',monospace;font-size:13px;padding:8px 12px;outline:none;min-width:280px;transition:border-color .2s;}
  .filter-group input:focus{border-color:var(--accent);}

  .customer-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(480px,1fr));gap:20px;}
  .cust-card{background:var(--panel);border:1px solid var(--border);border-top:2px solid var(--success);overflow:hidden;}
  .cust-header{padding:20px 24px;border-bottom:1px solid var(--border);}
  .cust-name{font-size:22px;font-weight:700;margin-bottom:4px;}
  .cust-id{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);margin-bottom:12px;}
  .cust-contacts{display:flex;gap:20px;flex-wrap:wrap;}
  .cust-contact{font-family:'Share Tech Mono',monospace;font-size:11px;}
  .cust-contact .label{color:var(--muted);display:block;font-size:10px;margin-bottom:2px;letter-spacing:1px;text-transform:uppercase;}
  .cust-contact .val{color:var(--text);}

  .cust-stats{display:flex;gap:0;border-bottom:1px solid var(--border);}
  .cust-stat{flex:1;padding:14px 18px;border-right:1px solid var(--border);text-align:center;}
  .cust-stat:last-child{border-right:none;}
  .cs-label{font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-family:'Share Tech Mono',monospace;margin-bottom:5px;}
  .cs-val{font-size:18px;font-weight:700;font-family:'Share Tech Mono',monospace;}

  .cust-supplier{padding:14px 24px;border-bottom:1px solid var(--border);background:rgba(0,212,255,0.02);font-family:'Share Tech Mono',monospace;font-size:11px;display:flex;align-items:center;gap:10px;}
  .sup-label{color:var(--muted);text-transform:uppercase;letter-spacing:1px;}
  .sup-name{color:var(--accent);font-weight:700;}
  .sup-email{color:var(--muted);}
  .arac-tag{border:1px solid var(--accent);color:var(--accent);padding:1px 6px;font-size:10px;background:rgba(0,212,255,0.08);}

  .cust-projects{padding:16px 24px;}
  .cp-title{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:12px;font-family:'Share Tech Mono',monospace;}
  .cp-proj{background:var(--bg);border:1px solid var(--border);padding:12px 16px;margin-bottom:8px;}
  .cp-proj:last-child{margin-bottom:0;}
  .cp-proj-name{font-size:15px;font-weight:700;margin-bottom:6px;}
  .cp-proj-meta{display:flex;gap:16px;flex-wrap:wrap;font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);}
  .cp-proj-meta span{color:var(--text);}
  .cp-profit{font-family:'Share Tech Mono',monospace;font-size:12px;margin-top:8px;padding-top:8px;border-top:1px solid var(--border);}
  .no-proj{font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);padding:8px 0;}
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
<nav>
  <div class="nav-brand">VANGUARD <span>LOGIC</span></div>
  <div class="nav-links">
    <a href="index.php">Personel</a>
    <a href="projects.php">Projeler</a>
    <a href="salary.php">Maaşlar</a>
    <a href="suppliers.php">Tedarikçiler</a>
    <span class="nav-sep">|</span>
    <a href="company.php">Şirket</a>
    <a href="departments.php">Departmanlar</a>
    <a href="customers.php" class="active">Müşteriler</a>
  </div>
  <div class="nav-user">
    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php">ÇIKIŞ</a>
  </div>
</nav>
<main>
  <h2>Müşteri <span>Listesi</span></h2>

  <div class="filter-bar">
    <div class="filter-group">
      <label>Müşteri Ara</label>
      <input type="text" id="custSearch" placeholder="İsim veya e-posta ile ara...">
    </div>
  </div>

  <div class="customer-cards" id="custCards">
    <?php foreach ($customers as $c):
      $projs  = $projectsByCustomer[$c['id']] ?? [];
      $profit = $c['toplam_satis'] - $c['toplam_butce'];
      $searchStr = strtolower($c['first_name'].' '.$c['last_name'].' '.$c['email'].' '.($c['supplier_name']??''));
    ?>
    <div class="cust-card" data-search="<?= htmlspecialchars($searchStr) ?>">
      <div class="cust-header">
        <div class="cust-name"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></div>
        <div class="cust-id">MÜŞTERİ #<?= $c['id'] ?></div>
        <div class="cust-contacts">
          <div class="cust-contact">
            <span class="label">Telefon</span>
            <span class="val"><?= htmlspecialchars($c['phn_nmbr']) ?></span>
          </div>
          <div class="cust-contact">
            <span class="label">E-Posta</span>
            <span class="val"><?= htmlspecialchars($c['email']) ?></span>
          </div>
        </div>
      </div>

      <div class="cust-stats">
        <div class="cust-stat">
          <div class="cs-label">Proje Sayısı</div>
          <div class="cs-val" style="color:var(--accent)"><?= $c['proje_count'] ?></div>
        </div>
        <div class="cust-stat">
          <div class="cs-label">Toplam Satış</div>
          <div class="cs-val" style="color:var(--success);font-size:14px">₺<?= number_format($c['toplam_satis'], 0, ',', '.') ?></div>
        </div>
        <div class="cust-stat">
          <div class="cs-label">Net Kâr</div>
          <div class="cs-val" style="color:<?= $profit >= 0 ? 'var(--success)' : 'var(--error)' ?>;font-size:14px">
            <?= $profit >= 0 ? '+' : '' ?>₺<?= number_format($profit, 0, ',', '.') ?>
          </div>
        </div>
      </div>

      <?php if ($c['supplier_name']): ?>
      <div class="cust-supplier">
        <span class="sup-label">Aracı Tedarikçi:</span>
        <span class="sup-name"><?= htmlspecialchars($c['supplier_name']) ?></span>
        <span class="arac-tag">Aracılık</span>
        <?php if ($c['supplier_email']): ?>
          <span class="sup-email">· <?= htmlspecialchars($c['supplier_email']) ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="cust-projects">
        <div class="cp-title">Projeler</div>
        <?php if (empty($projs)): ?>
          <div class="no-proj">Bu müşteriye ait proje bulunmuyor.</div>
        <?php else: ?>
          <?php foreach ($projs as $pr):
            $prProfit = $pr['sale_price'] - $pr['budget'];
          ?>
          <div class="cp-proj">
            <div class="cp-proj-name"><?= htmlspecialchars($pr['name']) ?></div>
            <div class="cp-proj-meta">
              <span>Bütçe: <span>₺<?= number_format($pr['budget'], 0, ',', '.') ?></span></span>
              <span>Satış: <span>₺<?= number_format($pr['sale_price'], 0, ',', '.') ?></span></span>
              <span>Başlangıç: <span><?= $pr['start_date'] ?></span></span>
              <?php if ($pr['end_date']): ?><span>Bitiş: <span><?= $pr['end_date'] ?></span></span><?php endif; ?>
              <?php if ($pr['supplier_name']): ?><span>Aracı: <span><?= htmlspecialchars($pr['supplier_name']) ?></span></span><?php endif; ?>
            </div>
            <div class="cp-profit" style="color:<?= $prProfit >= 0 ? 'var(--success)' : 'var(--error)' ?>">
              Kâr/Zarar: <?= $prProfit >= 0 ? '+' : '' ?>₺<?= number_format($prProfit, 0, ',', '.') ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>

<script>
document.getElementById('custSearch').addEventListener('input', function() {
  const term = this.value.toLowerCase();
  document.querySelectorAll('.cust-card').forEach(c => {
    c.style.display = c.dataset.search.includes(term) ? '' : 'none';
  });
});
</script>
</body>
</html>

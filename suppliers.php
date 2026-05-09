<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$suppliers = $conn->query("
    SELECT sup.id, sup.first_name, sup.last_name, sup.email, sup.phn_nmbr, sup.address,
           c.name AS company_name,
           COUNT(pr.id) AS project_count
    FROM supplier_ sup
    JOIN company_   c  ON sup.cmpny_id = c.id
    LEFT JOIN customer_ cust ON cust.splr_id = sup.id
    LEFT JOIN project_ pr ON pr.customer_id = cust.id
    GROUP BY sup.id, sup.first_name, sup.last_name, sup.email, sup.phn_nmbr, sup.address, c.name
    ORDER BY sup.first_name
")->fetch_all(MYSQLI_ASSOC);

// KARAkter FONKSİYONU: UPPER ile isim büyüt, SUBSTRING_INDEX ile email domain ayıkla
$charFunc = $conn->query("
    SELECT UPPER(first_name) AS isim_buyuk,
           UPPER(last_name)  AS soyisim_buyuk,
           email,
           SUBSTRING_INDEX(email, '@', -1) AS email_domain
    FROM supplier_
    ORDER BY first_name
")->fetch_all(MYSQLI_ASSOC);

$projectsBySupplier = [];
$rows = $conn->query("
    SELECT pr.id, pr.name, pr.sale_price, pr.start_date,
           CONCAT(cust.first_name,' ',cust.last_name) AS customer_name,
           cust.splr_id
    FROM project_ pr
    JOIN customer_ cust ON pr.customer_id = cust.id
    WHERE cust.splr_id IS NOT NULL
")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $r) { $projectsBySupplier[$r['splr_id']][] = $r; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Tedarikçiler</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root{--bg:#0a0e14;--panel:#111820;--border:#1e3a4a;--accent:#00d4ff;--text:#c8d8e8;--muted:#4a6070;--success:#00ff88;--warn:#ffaa00;--error:#ff4060;--purple:#a855f7;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{background:var(--bg);font-family:'Rajdhani',sans-serif;color:var(--text);min-height:100vh;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0;}
  nav{background:var(--panel);border-bottom:1px solid var(--border);padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:60px;position:sticky;top:0;z-index:100;}
  .nav-brand{font-size:18px;font-weight:700;letter-spacing:3px;color:#fff;} .nav-brand span{color:var(--accent);}
  .nav-links{display:flex;gap:4px;flex-wrap:wrap;} .nav-sep{color:var(--border);align-self:center;font-size:16px;padding:0 4px;}
  .nav-links a{color:var(--muted);text-decoration:none;font-size:13px;letter-spacing:1px;padding:6px 14px;border:1px solid transparent;transition:all .2s;text-transform:uppercase;}
  .nav-links a:hover,.nav-links a.active{color:var(--accent);border-color:var(--border);}
  .nav-user{display:flex;align-items:center;gap:16px;font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);}
  .nav-user a{color:var(--error);text-decoration:none;font-size:11px;letter-spacing:1px;border:1px solid var(--error);padding:4px 10px;transition:all .2s;}
  .nav-user a:hover{background:var(--error);color:var(--bg);}
  main{padding:32px;position:relative;z-index:1;}
  .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;}
  h2{font-size:24px;font-weight:700;letter-spacing:2px;text-transform:uppercase;} h2 span{color:var(--accent);}
  .filter-bar{background:var(--panel);border:1px solid var(--border);padding:18px 24px;margin-bottom:24px;display:flex;gap:20px;align-items:flex-end;flex-wrap:wrap;}
  .filter-group{display:flex;flex-direction:column;gap:6px;}
  .filter-group label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);}
  .filter-group input{background:var(--bg);border:1px solid var(--border);color:var(--text);font-family:'Share Tech Mono',monospace;font-size:13px;padding:8px 12px;outline:none;min-width:260px;transition:border-color .2s;}
  .filter-group input:focus{border-color:var(--accent);}
  .table-wrap{background:var(--panel);border:1px solid var(--border);overflow-x:auto;margin-bottom:36px;}
  table{width:100%;border-collapse:collapse;}
  thead tr{background:rgba(0,212,255,0.05);border-bottom:1px solid var(--border);}
  th{padding:14px 16px;text-align:left;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--accent);white-space:nowrap;}
  td{padding:13px 16px;font-size:14px;border-bottom:1px solid rgba(30,58,74,0.5);vertical-align:middle;}
  tr:last-child td{border-bottom:none;} tr:hover td{background:rgba(0,212,255,0.03);}
  .mono{font-family:'Share Tech Mono',monospace;font-size:12px;} .muted{color:var(--muted);}
  .badge-count{display:inline-block;padding:3px 10px;font-size:11px;font-family:'Share Tech Mono',monospace;border:1px solid var(--purple);color:var(--purple);background:rgba(168,85,247,0.08);}
  .arac-tag{display:inline-block;font-family:'Share Tech Mono',monospace;font-size:10px;letter-spacing:1px;border:1px solid var(--accent);color:var(--accent);padding:2px 8px;background:rgba(0,212,255,0.08);margin-left:8px;vertical-align:middle;}
  .section-title{font-size:14px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:20px;margin-top:8px;}
  .proj-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;}
  .proj-card{background:var(--panel);border:1px solid var(--border);border-top:2px solid var(--accent);padding:20px;transition:border-color .2s;}
  .proj-card:hover{border-color:var(--success);}
  .proj-name{font-size:16px;font-weight:700;margin-bottom:4px;}
  .proj-via{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);margin-top:3px;}
  .proj-via span{color:var(--accent);}
  .proj-info{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);line-height:1.8;margin-top:10px;}
  .proj-info span{color:var(--success);}
  .char-func-box{background:var(--panel);border:1px solid var(--border);border-top:2px solid var(--purple);margin-bottom:36px;overflow-x:auto;}
  .char-func-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
  .char-func-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--purple);font-family:'Share Tech Mono',monospace;}
  .char-func-desc{font-size:12px;color:var(--muted);font-family:'Share Tech Mono',monospace;}
  .domain-tag{display:inline-block;font-family:'Share Tech Mono',monospace;font-size:11px;letter-spacing:1px;border:1px solid var(--purple);color:var(--purple);padding:2px 10px;background:rgba(168,85,247,0.08);}
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
    <a href="suppliers.php" class="active">Tedarikçiler</a>
    <span class="nav-sep">|</span>
    <a href="company.php">Şirket</a>
    <a href="departments.php">Departmanlar</a>
    <a href="customers.php">Müşteriler</a>
  </div>
  <div class="nav-user">
    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php">ÇIKIŞ</a>
  </div>
</nav>
<main>
  <div class="page-header">
    <h2>Tedarikçi <span>Listesi</span></h2>
    <div style="font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted)">Toplam: <?= count($suppliers) ?> tedarikçi</div>
  </div>

  <div class="filter-bar">
    <div class="filter-group">
      <label>Tedarikçi Ara</label>
      <input type="text" id="supSearch" placeholder="İsim, e-posta, adres...">
    </div>
    <div class="filter-group">
      <label>Proje / Müşteri Ara</label>
      <input type="text" id="projSearch" placeholder="Proje adı veya müşteri adı...">
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Ad Soyad</th><th>E-Posta</th><th>Telefon</th><th>Adres</th><th>Şirket</th><th>Aracı Proje Sayısı</th>
        </tr>
      </thead>
      <tbody id="supTableBody">
        <?php foreach ($suppliers as $s): ?>
        <tr class="sup-row" data-search="<?= htmlspecialchars(strtolower($s['first_name'].' '.$s['last_name'].' '.$s['email'].' '.($s['address']??'').' '.$s['company_name'])) ?>">
          <td><span class="mono muted"><?= $s['id'] ?></span></td>
          <td style="font-weight:600"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?><span class="arac-tag">Aracılık</span></td>
          <td><span class="mono"><?= htmlspecialchars($s['email']) ?></span></td>
          <td><span class="mono"><?= htmlspecialchars($s['phn_nmbr']) ?></span></td>
          <td><span class="muted" style="font-size:13px"><?= htmlspecialchars($s['address'] ?? '—') ?></span></td>
          <td><?= htmlspecialchars($s['company_name']) ?></td>
          <td><span class="badge-count"><?= $s['project_count'] ?> proje</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="section-title">Karakter Fonksiyonu — UPPER &amp; SUBSTRING_INDEX</div>
  <div class="char-func-box">
    <div class="char-func-header">
      <span class="char-func-label">Karakter Fonksiyonu</span>
      <span class="char-func-desc">UPPER() ile isimler büyük harfe çevrildi · SUBSTRING_INDEX() ile email domain ayıklandı</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Büyük Harf İsim (UPPER)</th>
          <th>Büyük Harf Soyisim (UPPER)</th>
          <th>E-Posta</th>
          <th>Domain (SUBSTRING_INDEX)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($charFunc as $cf): ?>
        <tr>
          <td style="font-family:'Share Tech Mono',monospace;font-weight:700;color:var(--accent)"><?= htmlspecialchars($cf['isim_buyuk']) ?></td>
          <td style="font-family:'Share Tech Mono',monospace;font-weight:700;color:var(--accent)"><?= htmlspecialchars($cf['soyisim_buyuk']) ?></td>
          <td style="font-family:'Share Tech Mono',monospace;font-size:12px"><?= htmlspecialchars($cf['email']) ?></td>
          <td><span class="domain-tag"><?= htmlspecialchars($cf['email_domain']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="section-title">Tedarikçiye Göre Projeler</div>
  <div class="proj-grid" id="projGrid">
    <?php foreach ($suppliers as $s):
      $projs = $projectsBySupplier[$s['id']] ?? [];
      if (empty($projs)) continue;
      foreach ($projs as $pr): ?>
      <div class="proj-card" data-search="<?= htmlspecialchars(strtolower($pr['name'].' '.($pr['customer_name']??'').' '.$s['first_name'].' '.$s['last_name'])) ?>">
        <div class="proj-name"><?= htmlspecialchars($pr['name']) ?></div>
        <div class="proj-via">Aracı: <span><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></span><span class="arac-tag">Aracılık</span></div>
        <div class="proj-info">
          <?php if ($pr['customer_name']): ?>Müşteri: <span><?= htmlspecialchars($pr['customer_name']) ?></span><br><?php endif; ?>
          Satış: <span>₺<?= number_format($pr['sale_price'],0,',','.') ?></span><br>
          Tarih: <?= $pr['start_date'] ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</main>

<script>
document.getElementById('supSearch').addEventListener('input', function() {
  const t = this.value.toLowerCase();
  document.querySelectorAll('.sup-row').forEach(r => {
    r.style.display = r.dataset.search.includes(t) ? '' : 'none';
  });
});
document.getElementById('projSearch').addEventListener('input', function() {
  const t = this.value.toLowerCase();
  document.querySelectorAll('.proj-card').forEach(c => {
    c.style.display = c.dataset.search.includes(t) ? '' : 'none';
  });
});
</script>
</body>
</html>

<?php
// ============================================================
// SALARY.PHP — Maaş Listesi
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

// Departman bazlı özet (GROUP BY)
$summary = $conn->query("
    SELECT d.name AS department,
           COUNT(DISTINCT p.id) AS personel_sayisi,
           SUM(s.amount)        AS toplam_maas,
           AVG(s.amount)        AS ort_maas,
           MAX(s.amount)        AS max_maas
    FROM departments_ d
    JOIN personnel_ p ON p.dept_id = d.id
    JOIN salary_    s ON s.psnl_id = p.id
    GROUP BY d.name
    ORDER BY toplam_maas DESC
")->fetch_all(MYSQLI_ASSOC);

// Alt sorgu: ortalama üzeri maaş
$aboveAvg = $conn->query("
    SELECT CONCAT(p.f_name,' ',p.l_name) AS personel,
           d.name AS dept,
           s.amount
    FROM personnel_ p
    JOIN salary_      s ON s.psnl_id = p.id
    JOIN departments_ d ON p.dept_id = d.id
    WHERE s.amount > (SELECT AVG(amount) FROM salary_)
    ORDER BY s.amount DESC
")->fetch_all(MYSQLI_ASSOC);

$avgAmount = $conn->query("SELECT AVG(amount) FROM salary_")->fetch_row()[0];

// Personel bazlı TOPLAM kazanç
$personalTotals = $conn->query("
    SELECT CONCAT(p.f_name,' ',p.l_name) AS personel,
           d.name AS dept,
           COUNT(s.id)       AS odeme_sayisi,
           SUM(s.amount)     AS toplam_kazanc,
           MAX(s.amount)     AS son_maas,
           MAX(s.pymnt_date) AS son_odeme
    FROM personnel_ p
    JOIN salary_      s ON s.psnl_id = p.id
    JOIN departments_ d ON p.dept_id = d.id
    GROUP BY p.id, p.f_name, p.l_name, d.name
    ORDER BY toplam_kazanc DESC
")->fetch_all(MYSQLI_ASSOC);

$maxKazanc = !empty($personalTotals) ? $personalTotals[0]['toplam_kazanc'] : 1;

// Tüm maaş ödemeleri
$salaries = $conn->query("
    SELECT s.id, s.amount, s.pymnt_date,
           CONCAT(p.f_name,' ',p.l_name) AS personel,
           d.name AS dept
    FROM salary_ s
    JOIN personnel_  p ON s.psnl_id = p.id
    JOIN departments_ d ON p.dept_id = d.id
    ORDER BY s.pymnt_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Vanguard Logic — Maaşlar</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root{--bg:#0a0e14;--panel:#111820;--border:#1e3a4a;--accent:#00d4ff;--text:#c8d8e8;--muted:#4a6070;--error:#ff4060;--success:#00ff88;--warn:#ffaa00;--purple:#a855f7;}
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
  .nav-user a{color:var(--error);text-decoration:none;font-size:11px;letter-spacing:1px;border:1px solid var(--error);padding:4px 10px;}
  main{padding:32px;position:relative;z-index:1;}
  h2{font-size:24px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;} h2 span{color:var(--accent);}
  .section-title{font-size:14px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:20px;margin-top:32px;}
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:36px;}
  .sum-card{background:var(--panel);border:1px solid var(--border);padding:20px 24px;}
  .sum-dept{font-size:13px;letter-spacing:1px;color:var(--muted);text-transform:uppercase;margin-bottom:12px;}
  .sum-total{font-size:22px;font-weight:700;font-family:'Share Tech Mono',monospace;color:var(--success);}
  .sum-meta{display:flex;gap:20px;margin-top:8px;}
  .sum-item{font-size:11px;font-family:'Share Tech Mono',monospace;color:var(--muted);}
  .sum-item span{color:var(--text);}
  .avg-alert{background:rgba(0,255,136,0.04);border:1px solid rgba(0,255,136,0.3);border-left:3px solid var(--success);padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);}
  .avg-alert strong{color:var(--success);font-size:16px;}
  .above-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:36px;}
  .above-card{background:var(--panel);border:1px solid rgba(0,255,136,0.2);border-left:3px solid var(--success);padding:16px 20px;}
  .above-name{font-size:15px;font-weight:700;margin-bottom:4px;}
  .above-dept{font-size:11px;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;}
  .above-amt{font-family:'Share Tech Mono',monospace;font-size:20px;font-weight:700;color:var(--success);}
  .above-diff{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--warn);margin-top:4px;}

  /* KİŞİSEL TOPLAM KAZANÇ */
  .personal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;margin-bottom:36px;}
  .personal-card{background:var(--panel);border:1px solid var(--border);padding:18px 20px;}
  .personal-card:first-child{border-color:rgba(168,85,247,0.4);}
  .pc-name{font-size:16px;font-weight:700;margin-bottom:2px;}
  .pc-dept{font-size:11px;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;}
  .pc-total{font-family:'Share Tech Mono',monospace;font-size:22px;font-weight:700;color:var(--purple);margin-bottom:8px;}
  .pc-bar-track{height:6px;background:rgba(168,85,247,0.15);margin-bottom:8px;}
  .pc-bar-fill{height:100%;background:linear-gradient(90deg,var(--purple),var(--accent));transition:width .5s ease;}
  .pc-meta{display:flex;gap:16px;flex-wrap:wrap;}
  .pc-meta-item{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);}
  .pc-meta-item span{color:var(--text);}

  .table-wrap{background:var(--panel);border:1px solid var(--border);overflow-x:auto;}
  table{width:100%;border-collapse:collapse;}
  thead tr{background:rgba(0,212,255,0.05);border-bottom:1px solid var(--border);}
  th{padding:14px 16px;text-align:left;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--accent);white-space:nowrap;}
  td{padding:13px 16px;font-size:14px;border-bottom:1px solid rgba(30,58,74,0.5);vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  tr:hover td{background:rgba(0,212,255,0.03);}
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
<nav>
  <div class="nav-brand">VANGUARD <span>LOGIC</span></div>
  <div class="nav-links">
    <a href="index.php">Personel</a>
    <a href="projects.php">Projeler</a>
    <a href="salary.php" class="active">Maaşlar</a>
    <a href="suppliers.php">Tedarikçiler</a>
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
  <h2>Maaş <span>Raporu</span></h2>

  <div class="section-title">Departman Bazlı Özet (GROUP BY Sorgusu)</div>
  <div class="summary-grid">
    <?php foreach ($summary as $s): ?>
    <div class="sum-card">
      <div class="sum-dept"><?= htmlspecialchars($s['department']) ?></div>
      <div class="sum-total">₺<?= number_format($s['toplam_maas'],0,',','.') ?></div>
      <div class="sum-meta">
        <div class="sum-item">Personel: <span><?= $s['personel_sayisi'] ?></span></div>
        <div class="sum-item">Ort: <span>₺<?= number_format($s['ort_maas'],0,',','.') ?></span></div>
        <div class="sum-item">Max: <span>₺<?= number_format($s['max_maas'],0,',','.') ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">Alt Sorgu — Ortalama Üzeri Maaş Alanlar</div>
  <div class="avg-alert">
    <div>Genel Ortalama Maaş: <strong>₺<?= number_format($avgAmount, 0, ',', '.') ?></strong></div>
    <div style="color:var(--accent)"><?= count($aboveAvg) ?> personel ortalamanın üzerinde maaş alıyor</div>
  </div>
  <div class="above-grid">
    <?php foreach ($aboveAvg as $a): $diff = $a['amount'] - $avgAmount; ?>
    <div class="above-card">
      <div class="above-name"><?= htmlspecialchars($a['personel']) ?></div>
      <div class="above-dept"><?= htmlspecialchars($a['dept']) ?></div>
      <div class="above-amt">₺<?= number_format($a['amount'], 0, ',', '.') ?></div>
      <div class="above-diff">+₺<?= number_format($diff, 0, ',', '.') ?> ortalamanın üzerinde</div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">Personel Bazlı Toplam Kazanç</div>
  <div class="personal-grid">
    <?php foreach ($personalTotals as $i => $pt):
      $barPct = $maxKazanc > 0 ? round(($pt['toplam_kazanc'] / $maxKazanc) * 100) : 0;
    ?>
    <div class="personal-card">
      <?php if ($i === 0): ?>
        <div style="font-family:'Share Tech Mono',monospace;font-size:10px;color:var(--purple);letter-spacing:2px;margin-bottom:6px;">★ EN YÜKSEK KAZANÇ</div>
      <?php endif; ?>
      <div class="pc-name"><?= htmlspecialchars($pt['personel']) ?></div>
      <div class="pc-dept"><?= htmlspecialchars($pt['dept']) ?></div>
      <div class="pc-total">₺<?= number_format($pt['toplam_kazanc'], 0, ',', '.') ?></div>
      <div class="pc-bar-track">
        <div class="pc-bar-fill" style="width:<?= $barPct ?>%"></div>
      </div>
      <div class="pc-meta">
        <div class="pc-meta-item">Ödeme Sayısı: <span><?= $pt['odeme_sayisi'] ?></span></div>
        <div class="pc-meta-item">Son Maaş: <span>₺<?= number_format($pt['son_maas'], 0, ',', '.') ?></span></div>
        <div class="pc-meta-item">Son Ödeme: <span><?= $pt['son_odeme'] ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">Maaş Ödemeleri</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Personel</th><th>Departman</th><th>Tutar</th><th>Ödeme Tarihi</th><th>İşlem</th></tr>
      </thead>
      <tbody>
        <?php foreach ($salaries as $s): ?>
        <tr>
          <td style="font-family:'Share Tech Mono',monospace;color:var(--muted)"><?= $s['id'] ?></td>
          <td><?= htmlspecialchars($s['personel']) ?></td>
          <td><?= htmlspecialchars($s['dept']) ?></td>
          <td style="font-family:'Share Tech Mono',monospace;color:var(--success);font-weight:700">₺<?= number_format($s['amount'],0,',','.') ?></td>
          <td style="font-family:'Share Tech Mono',monospace;font-size:12px"><?= $s['pymnt_date'] ?></td>
          <td><a href="salary_edit.php?id=<?= $s['id'] ?>" style="color:var(--accent);text-decoration:none;font-size:12px;border:1px solid var(--accent);padding:4px 8px;">DÜZENLE</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>
</body>
</html>

<?php
// ============================================================
// COMPANY.PHP — Şirket Bilgileri
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$company = $conn->query("SELECT * FROM company_")->fetch_assoc();

// Şirkete bağlı departman sayısı
$deptCount = $conn->query("SELECT COUNT(*) FROM departments_ WHERE cmpny_id = {$company['id']}")->fetch_row()[0];

// Şirkete bağlı personel sayısı
$persCount = $conn->query("
    SELECT COUNT(*) FROM personnel_ p
    JOIN departments_ d ON p.dept_id = d.id
    WHERE d.cmpny_id = {$company['id']}
")->fetch_row()[0];

// Şirkete bağlı proje sayısı
$projCount = $conn->query("
    SELECT COUNT(DISTINCT pr.id) 
    FROM project_ pr
    LEFT JOIN departments_ d ON pr.dept_id = d.id
    LEFT JOIN customer_ c ON pr.customer_id = c.id
    LEFT JOIN supplier_ s ON c.splr_id = s.id
    WHERE d.cmpny_id = {$company['id']} OR s.cmpny_id = {$company['id']}
")->fetch_row()[0];

// Şirkete bağlı tedarikçi sayısı
$supplierCount = $conn->query("SELECT COUNT(*) FROM supplier_ WHERE cmpny_id = {$company['id']}")->fetch_row()[0];

// Toplam proje bütçe vs satış
$financial = $conn->query("
    SELECT SUM(pr.budget) AS total_budget, SUM(pr.sale_price) AS total_sale
    FROM project_ pr
    LEFT JOIN departments_ d ON pr.dept_id = d.id
    LEFT JOIN customer_ c ON pr.customer_id = c.id
    LEFT JOIN supplier_ s ON c.splr_id = s.id
    WHERE d.cmpny_id = {$company['id']} OR s.cmpny_id = {$company['id']}
")->fetch_assoc();

// Departman listesi
$departments = $conn->query("
    SELECT d.id, d.name, d.phn_ext,
           COUNT(p.id) AS personel_count
    FROM departments_ d
    LEFT JOIN personnel_ p ON p.dept_id = d.id
    WHERE d.cmpny_id = {$company['id']}
    GROUP BY d.id, d.name, d.phn_ext
    ORDER BY d.name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Şirket</title>
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
  main{padding:32px;position:relative;z-index:1;max-width:1100px;}
  h2{font-size:24px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:28px;} h2 span{color:var(--accent);}
  .section-title{font-size:13px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;margin-top:32px;}

  /* ŞİRKET KARTI */
  .company-hero{background:var(--panel);border:1px solid var(--border);border-top:3px solid var(--accent);padding:32px;margin-bottom:28px;display:flex;gap:40px;flex-wrap:wrap;align-items:center;}
  .company-icon{width:80px;height:80px;border:2px solid var(--accent);display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--accent);flex-shrink:0;}
  .company-info h3{font-size:28px;font-weight:700;letter-spacing:2px;margin-bottom:6px;}
  .company-id{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);letter-spacing:2px;margin-bottom:16px;}
  .company-contacts{display:flex;gap:24px;flex-wrap:wrap;}
  .contact-item{font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);}
  .contact-item span{color:var(--text);display:block;font-size:14px;margin-top:3px;}

  /* İSTATİSTİKLER */
  .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:28px;}
  .stat-card{background:var(--panel);border:1px solid var(--border);padding:18px 20px;position:relative;overflow:hidden;}
  .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;}
  .s-blue::before{background:var(--accent);}
  .s-green::before{background:var(--success);}
  .s-warn::before{background:var(--warn);}
  .s-purple::before{background:var(--purple);}
  .s-red::before{background:var(--error);}
  .stat-lbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;font-family:'Share Tech Mono',monospace;}
  .stat-num{font-size:26px;font-weight:700;font-family:'Share Tech Mono',monospace;}
  .c-blue{color:var(--accent);} .c-green{color:var(--success);} .c-warn{color:var(--warn);} .c-purple{color:var(--purple);}

  /* FİNANSAL */
  .fin-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;}
  .fin-box{background:var(--panel);border:1px solid var(--border);padding:20px 24px;}
  .fin-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;font-family:'Share Tech Mono',monospace;}
  .fin-val{font-size:22px;font-weight:700;font-family:'Share Tech Mono',monospace;}

  /* DEPARTMAN LİSTESİ */
  .dept-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;}
  .dept-card{background:var(--panel);border:1px solid var(--border);border-left:3px solid var(--accent);padding:18px 20px;}
  .dept-name{font-size:16px;font-weight:700;margin-bottom:6px;}
  .dept-meta{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);line-height:2;}
  .dept-meta span{color:var(--text);}
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
    <a href="company.php" class="active">Şirket</a>
    <a href="departments.php">Departmanlar</a>
    <a href="customers.php">Müşteriler</a>
  </div>
  <div class="nav-user">
    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php">ÇIKIŞ</a>
  </div>
</nav>
<main>
  <h2>Şirket <span>Bilgileri</span></h2>

  <!-- ŞİRKET HERO -->
  <div class="company-hero">
    <div class="company-icon">⬡</div>
    <div class="company-info">
      <h3><?= htmlspecialchars($company['name']) ?></h3>
      <div class="company-id">ID #<?= $company['id'] ?></div>
      <div class="company-contacts">
        <div class="contact-item">
          Telefon
          <span><?= htmlspecialchars($company['phn_nmbr']) ?></span>
        </div>
        <div class="contact-item">
          Faks
          <span><?= htmlspecialchars($company['fax_nmbr']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- PROJENİN TANIMI -->
  <div class="section-title">Projenin Tanımı</div>
  <div class="company-hero" style="border-top-color: var(--purple); flex-direction: column; align-items: flex-start; gap: 16px;">
    <h3 style="color: var(--purple); font-size: 18px;">Vanguard Logic — Kurumsal Personel ve Proje Yönetim Sistemi</h3>
    <p style="font-size: 14px; line-height: 1.6; color: var(--text);">
      Vanguard Logic, orta ve büyük ölçekli teknoloji şirketlerinin personel verimliliğini artırmak, projelerin finansal takibini yapmak ve tedarikçi/müşteri ilişkilerini merkezi bir platformdan yönetmek için tasarlanmış kapsamlı bir ERP modülüdür. 
    </p>
    <p style="font-size: 14px; line-height: 1.6; color: var(--text);">
      Sistem; departman bazlı hiyerarşik personel yapısını (Yönetici, Teknik, Stajyer), projelerin hem iç departmanlara hem de dış müşterilere olan maliyet-satış dengesini ve maaş ödemelerini gerçek zamanlı olarak takip eder. Güçlü veritabanı mimarisi (ERD) ve dinamik arayüzü (AJAX) ile kurumsal süreçlerin dijitalleşmesini hedefler.
    </p>
  </div>

  <!-- İSTATİSTİKLER -->
  <div class="section-title">Genel Özet</div>
  <div class="stats-grid">
    <div class="stat-card s-blue">
      <div class="stat-lbl">Departman</div>
      <div class="stat-num c-blue"><?= $deptCount ?></div>
    </div>
    <div class="stat-card s-green">
      <div class="stat-lbl">Toplam Personel</div>
      <div class="stat-num c-green"><?= $persCount ?></div>
    </div>
    <div class="stat-card s-warn">
      <div class="stat-lbl">Proje</div>
      <div class="stat-num c-warn"><?= $projCount ?></div>
    </div>
    <div class="stat-card s-purple">
      <div class="stat-lbl">Tedarikçi</div>
      <div class="stat-num c-purple"><?= $supplierCount ?></div>
    </div>
  </div>

  <!-- FİNANSAL -->
  <div class="section-title">Finansal Özet (Projeler)</div>
  <div class="fin-row">
    <div class="fin-box">
      <div class="fin-label">Toplam Proje Bütçesi</div>
      <div class="fin-val" style="color:var(--warn)">₺<?= number_format($financial['total_budget'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="fin-box">
      <div class="fin-label">Toplam Satış Değeri</div>
      <div class="fin-val" style="color:var(--success)">₺<?= number_format($financial['total_sale'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="fin-box">
      <div class="fin-label">Net Kâr / Zarar</div>
      <?php $net = ($financial['total_sale'] ?? 0) - ($financial['total_budget'] ?? 0); ?>
      <div class="fin-val" style="color:<?= $net >= 0 ? 'var(--success)' : 'var(--error)' ?>">
        <?= $net >= 0 ? '+' : '' ?>₺<?= number_format($net, 0, ',', '.') ?>
      </div>
    </div>
  </div>

  <!-- DEPARTMANLAR -->
  <div class="section-title">Bünyesindeki Departmanlar</div>
  <div class="dept-grid">
    <?php foreach ($departments as $d): ?>
    <div class="dept-card">
      <div class="dept-name"><?= htmlspecialchars($d['name']) ?></div>
      <div class="dept-meta">
        ID: <span><?= $d['id'] ?></span><br>
        Dahili Numara: <span><?= $d['phn_ext'] ? $d['phn_ext'] : '—' ?></span><br>
        Personel Sayısı: <span><?= $d['personel_count'] ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>

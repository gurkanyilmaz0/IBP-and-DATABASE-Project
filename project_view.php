<?php
// ============================================================
// PROJECT_VIEW.PHP — Projeyi İncele ve Ekip Görüntüle
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: projects.php?msg=error'); exit; }

// Proje detaylarını çek
$proj = $conn->query("
    SELECT
        pr.*,
        COALESCE(c.name, c2.name)  AS company_name,
        d.name  AS dept_name,
        CONCAT(cust.first_name, ' ', cust.last_name) AS customer_name,
        CONCAT(sup.first_name,  ' ', sup.last_name)  AS supplier_name,
        p.dept_id AS manager_dept_id,
        CONCAT(p.f_name, ' ', p.l_name) AS manager_name,
        p.email AS manager_email
    FROM project_ pr
    LEFT JOIN departments_   d    ON pr.dept_id     = d.id
    LEFT JOIN company_       c    ON d.cmpny_id     = c.id
    LEFT JOIN customer_      cust ON pr.customer_id = cust.id
    LEFT JOIN supplier_      sup  ON cust.splr_id   = sup.id
    LEFT JOIN company_       c2   ON sup.cmpny_id   = c2.id
    LEFT JOIN personnel_     p    ON pr.prsnl_id    = p.id
    WHERE pr.id = $id
")->fetch_assoc();

if (!$proj) { header('Location: projects.php?msg=error'); exit; }

// Ekip üyelerini çek (Sorumlu personelin departmanındaki diğer herkes)
$team = [];
if ($proj['manager_dept_id']) {
    $stmt = $conn->prepare("
        SELECT id, f_name, l_name, email,
            CASE
                WHEN mngr_id  IS NOT NULL THEN 'Yönetici'
                WHEN staff_id IS NOT NULL THEN 'Teknik'
                WHEN other_id IS NOT NULL THEN 'Diğer'
                ELSE 'Personel'
            END AS role_type
        FROM personnel_
        WHERE dept_id = ? AND id != ?
        ORDER BY f_name
    ");
    $stmt->bind_param('ii', $proj['manager_dept_id'], $proj['prsnl_id']);
    $stmt->execute();
    $team = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$profit = $proj['sale_price'] - $proj['budget'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Proje İncele</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root { --bg:#0a0e14; --panel:#111820; --border:#1e3a4a; --accent:#00d4ff; --text:#c8d8e8; --muted:#4a6070; --success:#00ff88; --warn:#ffaa00; --error:#ff4060; --purple:#a855f7; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); font-family: 'Rajdhani', sans-serif; color: var(--text); min-height: 100vh; }
  body::before { content: ''; position: fixed; inset: 0; background-image: linear-gradient(rgba(0,212,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(0,212,255,0.02) 1px, transparent 1px); background-size: 40px 40px; pointer-events: none; }
  nav { background: var(--panel); border-bottom: 1px solid var(--border); padding: 0 32px; display: flex; align-items: center; justify-content: space-between; height: 60px; position: sticky; top: 0; z-index: 100; }
  .nav-brand { font-size: 18px; font-weight: 700; letter-spacing: 3px; color: #fff; } .nav-brand span { color: var(--accent); }
  .nav-user { display: flex; align-items: center; gap: 16px; font-family: 'Share Tech Mono', monospace; font-size: 12px; color: var(--muted); }
  .nav-user a { color: var(--error); text-decoration: none; font-size: 11px; letter-spacing: 1px; border: 1px solid var(--error); padding: 4px 10px; transition: all .2s; }
  .nav-user a:hover { background: var(--error); color: var(--bg); }
  main { padding: 32px; position: relative; z-index: 1; max-width: 900px; margin: 0 auto; }
  .breadcrumb { display: flex; align-items: center; gap: 8px; font-family: 'Share Tech Mono', monospace; font-size: 11px; color: var(--muted); margin-bottom: 24px; letter-spacing: 1px; text-transform: uppercase; }
  .breadcrumb a { color: var(--accent); text-decoration: none; } .breadcrumb a:hover { text-decoration: underline; }
  .page-title { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
  h2 { font-size: 28px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; } h2 span { color: var(--accent); }
  .proj-id { font-family: 'Share Tech Mono', monospace; font-size: 14px; color: var(--muted); }
  
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
  .info-box { background: var(--panel); border: 1px solid var(--border); padding: 20px; }
  .info-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); font-family: 'Share Tech Mono', monospace; margin-bottom: 8px; }
  .info-val { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
  .info-val:last-child { margin-bottom: 0; }
  
  .team-section { background: var(--panel); border: 1px solid var(--border); border-top: 2px solid var(--purple); padding: 24px; }
  .team-title { font-size: 18px; font-weight: 700; letter-spacing: 1px; margin-bottom: 20px; color: var(--purple); }
  
  .manager-card { background: rgba(168,85,247,0.05); border: 1px solid rgba(168,85,247,0.2); padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; }
  .mgr-badge { font-family: 'Share Tech Mono', monospace; font-size: 10px; letter-spacing: 2px; background: var(--purple); color: #fff; padding: 4px 8px; }
  .mgr-name { font-size: 18px; font-weight: 700; }
  .mgr-email { font-family: 'Share Tech Mono', monospace; font-size: 12px; color: var(--muted); }
  
  .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
  .member-card { background: var(--bg); border: 1px solid var(--border); padding: 14px; transition: border-color .2s; }
  .member-card:hover { border-color: var(--accent); }
  .mem-role { font-family: 'Share Tech Mono', monospace; font-size: 10px; letter-spacing: 1px; color: var(--accent); margin-bottom: 4px; text-transform: uppercase; }
  .mem-name { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
  .mem-email { font-family: 'Share Tech Mono', monospace; font-size: 11px; color: var(--muted); }
  
  .no-team { font-family: 'Share Tech Mono', monospace; font-size: 12px; color: var(--muted); font-style: italic; }
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
<nav>
  <div class="nav-brand">VANGUARD <span>LOGIC</span></div>
  <div class="nav-user">
    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php">ÇIKIŞ</a>
  </div>
</nav>

<main>
  <div class="breadcrumb">
    <a href="projects.php">Projeler</a> <span>›</span> <span>İncele</span>
  </div>

  <div class="page-title">
    <h2><?= htmlspecialchars($proj['name']) ?></h2>
    <div class="proj-id">PRJ-<?= str_pad($proj['id'], 4, '0', STR_PAD_LEFT) ?></div>
  </div>

  <div class="info-grid">
    <div class="info-box">
      <div class="info-label">Durum & Tarih</div>
      <div class="info-val" style="color:var(--accent)">
        <?= $proj['start_date'] ?> — <?= $proj['end_date'] ?: 'Devam Ediyor' ?>
      </div>
      
      <div class="info-label" style="margin-top:20px;">Finansal Durum</div>
      <div class="info-val" style="font-family:'Share Tech Mono', monospace;">
        Bütçe: <span style="color:var(--warn)">₺<?= number_format($proj['budget'], 0, ',', '.') ?></span><br>
        Satış: <span style="color:var(--success)">₺<?= number_format($proj['sale_price'], 0, ',', '.') ?></span><br>
        Kâr/Zarar: <span style="color:<?= $profit >= 0 ? 'var(--success)' : 'var(--error)' ?>"><?= $profit >= 0 ? '+' : '' ?>₺<?= number_format($profit, 0, ',', '.') ?></span>
      </div>
    </div>

    <div class="info-box">
      <div class="info-label">İlişkiler</div>
      <?php if ($proj['customer_name']): ?>
        <div class="info-val">Müşteri: <span style="color:var(--success)"><?= htmlspecialchars($proj['customer_name']) ?></span></div>
        <?php if ($proj['supplier_name']): ?>
          <div class="info-val">Tedarikçi: <span style="color:var(--muted);font-size:14px;"><?= htmlspecialchars($proj['supplier_name']) ?></span></div>
        <?php endif; ?>
      <?php else: ?>
        <div class="info-val">Departman Projesi: <span style="color:var(--warn)"><?= htmlspecialchars($proj['dept_name']) ?></span></div>
      <?php endif; ?>

      <?php if ($proj['description']): ?>
        <div class="info-label" style="margin-top:20px;">Açıklama</div>
        <div class="info-val" style="font-size:14px; font-weight:400; line-height:1.6; color:var(--text);">
          <?= htmlspecialchars($proj['description']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="team-section">
    <div class="team-title">Proje Ekibi</div>
    
    <div class="manager-card">
      <div class="mgr-badge">SORUMLU YÖNETİCİ</div>
      <div>
        <div class="mgr-name"><?= htmlspecialchars($proj['manager_name'] ?? 'Atanmamış') ?></div>
        <div class="mgr-email"><?= htmlspecialchars($proj['manager_email'] ?? '') ?></div>
      </div>
    </div>

    <div class="info-label" style="margin-bottom:12px;">Görev Alan Diğer Personeller</div>
    <?php if (empty($team)): ?>
      <div class="no-team">Sorumlu personelin departmanında bu projeye atanabilecek başka personel bulunmuyor.</div>
    <?php else: ?>
      <div class="team-grid">
        <?php foreach ($team as $member): ?>
        <div class="member-card">
          <div class="mem-role"><?= htmlspecialchars($member['role_type']) ?></div>
          <div class="mem-name"><?= htmlspecialchars($member['f_name'] . ' ' . $member['l_name']) ?></div>
          <div class="mem-email"><?= htmlspecialchars($member['email']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>

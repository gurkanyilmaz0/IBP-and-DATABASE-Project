<?php
// ============================================================
// DEPARTMENTS.PHP — Departman Listesi
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$departments = $conn->query("
    SELECT d.id, d.name, d.phn_ext,
           c.name AS company_name,
           COUNT(DISTINCT p.id) AS personel_count,
           COUNT(DISTINCT pr.id) AS proje_count,
           COALESCE(SUM(s.amount), 0) AS toplam_maas,
           GROUP_CONCAT(DISTINCT
             CASE
               WHEN p2.mngr_id IS NOT NULL THEN m.mngmnt_lvl
             END
             ORDER BY m.mngmnt_lvl SEPARATOR ', '
           ) AS yoneticiler
    FROM departments_ d
    JOIN company_ c ON d.cmpny_id = c.id
    LEFT JOIN personnel_ p  ON p.dept_id  = d.id
    LEFT JOIN salary_    s  ON s.psnl_id  = p.id
    LEFT JOIN project_   pr ON pr.dept_id = d.id
    LEFT JOIN personnel_ p2 ON p2.dept_id = d.id AND p2.mngr_id IS NOT NULL
    LEFT JOIN manager_   m  ON p2.mngr_id = m.id
    GROUP BY d.id, d.name, d.phn_ext, c.name
    ORDER BY d.name
")->fetch_all(MYSQLI_ASSOC);

// Her departmandaki personel listesi
$personnelByDept = [];
$rows = $conn->query("
    SELECT p.id, p.f_name, p.l_name, p.hire_date,
           p.dept_id,
           CASE
             WHEN p.mngr_id  IS NOT NULL THEN CONCAT('Yönetici — ', m.mngmnt_lvl)
             WHEN p.staff_id IS NOT NULL THEN CONCAT('Teknik — ', ts.tech_skill)
             WHEN p.other_id IS NOT NULL THEN CONCAT('Diğer — ', o.note)
             ELSE 'Belirsiz'
           END AS staff_type
    FROM personnel_ p
    LEFT JOIN manager_         m  ON p.mngr_id  = m.id
    LEFT JOIN technical_staff_ ts ON p.staff_id = ts.id
    LEFT JOIN other_           o  ON p.other_id = o.id
    ORDER BY p.f_name
")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $r) { $personnelByDept[$r['dept_id']][] = $r; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Departmanlar</title>
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
  .section-title{font-size:13px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;margin-top:32px;}

  .dept-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(440px,1fr));gap:20px;}
  .dept-card{background:var(--panel);border:1px solid var(--border);border-top:2px solid var(--accent);overflow:hidden;}
  .dept-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:flex-start;gap:12px;}
  .dept-title{font-size:20px;font-weight:700;letter-spacing:1px;}
  .dept-subtitle{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--muted);margin-top:4px;}
  .dept-badges{display:flex;flex-direction:column;align-items:flex-end;gap:6px;}
  .dept-badge{font-family:'Share Tech Mono',monospace;font-size:10px;letter-spacing:1px;border:1px solid var(--border);color:var(--muted);padding:3px 10px;}
  .dept-stats{display:flex;gap:0;border-bottom:1px solid var(--border);}
  .dept-stat{flex:1;padding:14px 18px;border-right:1px solid var(--border);text-align:center;}
  .dept-stat:last-child{border-right:none;}
  .ds-label{font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-family:'Share Tech Mono',monospace;margin-bottom:5px;}
  .ds-val{font-size:18px;font-weight:700;font-family:'Share Tech Mono',monospace;}
  .dept-personnel{padding:16px 24px;}
  .dp-title{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:12px;font-family:'Share Tech Mono',monospace;}
  .dp-person{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid rgba(30,58,74,0.4);}
  .dp-person:last-child{border-bottom:none;}
  .dp-name{font-size:14px;font-weight:600;}
  .dp-type{font-family:'Share Tech Mono',monospace;font-size:10px;color:var(--muted);padding:2px 8px;border:1px solid var(--border);}
  .dp-date{font-family:'Share Tech Mono',monospace;font-size:10px;color:var(--muted);}
  .dp-empty{font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);padding:8px 0;}
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
    <a href="departments.php" class="active">Departmanlar</a>
    <a href="customers.php">Müşteriler</a>
  </div>
  <div class="nav-user">
    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php">ÇIKIŞ</a>
  </div>
</nav>
<main>
  <h2>Departman <span>Listesi</span></h2>

  <div class="dept-cards">
    <?php foreach ($departments as $d):
      $personnel = $personnelByDept[$d['id']] ?? [];
    ?>
    <div class="dept-card">
      <div class="dept-header">
        <div>
          <div class="dept-title"><?= htmlspecialchars($d['name']) ?></div>
          <div class="dept-subtitle"><?= htmlspecialchars($d['company_name']) ?></div>
        </div>
        <div class="dept-badges">
          <div class="dept-badge">ID: <?= $d['id'] ?></div>
          <?php if ($d['phn_ext']): ?>
            <div class="dept-badge">DAHİLİ: <?= htmlspecialchars($d['phn_ext']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="dept-stats">
        <div class="dept-stat">
          <div class="ds-label">Personel</div>
          <div class="ds-val" style="color:var(--accent)"><?= $d['personel_count'] ?></div>
        </div>
        <div class="dept-stat">
          <div class="ds-label">Proje</div>
          <div class="ds-val" style="color:var(--warn)"><?= $d['proje_count'] ?></div>
        </div>
        <div class="dept-stat">
          <div class="ds-label">Toplam Maaş</div>
          <div class="ds-val" style="color:var(--success);font-size:14px">₺<?= number_format($d['toplam_maas'], 0, ',', '.') ?></div>
        </div>
      </div>

      <div class="dept-personnel">
        <div class="dp-title">Personel</div>
        <?php if (empty($personnel)): ?>
          <div class="dp-empty">Bu departmanda personel yok.</div>
        <?php else: ?>
          <?php foreach ($personnel as $pers): ?>
          <div class="dp-person">
            <div>
              <div class="dp-name"><?= htmlspecialchars($pers['f_name'] . ' ' . $pers['l_name']) ?></div>
              <div class="dp-date"><?= $pers['hire_date'] ?></div>
            </div>
            <span class="dp-type"><?= htmlspecialchars(explode(' — ', $pers['staff_type'])[0]) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>

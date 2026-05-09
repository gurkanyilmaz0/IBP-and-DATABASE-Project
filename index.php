<?php
// ============================================================
// INDEX.PHP — Ana Sayfa (Personel Listesi)
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db.php';

$sort = $_GET['sort'] ?? '';
$orderBy = match($sort) {
    'hire_asc'  => 'p.hire_date ASC',
    'hire_desc' => 'p.hire_date DESC',
    default     => 'd.name, p.f_name',
};

// Personel listesi — self-join ile yönetici adı da getiriliyor
$query = "
    SELECT p.id, p.f_name, p.l_name, p.email, p.hire_date, p.mnthly_hrs,
           d.name AS dept_name,
           TIMESTAMPDIFF(YEAR,  p.hire_date, CURDATE()) AS kidem_yil,
           TIMESTAMPDIFF(MONTH, p.hire_date, CURDATE()) AS kidem_ay,
           CASE
             WHEN p.mngr_id  IS NOT NULL THEN CONCAT('Yönetici (', m.mngmnt_lvl, ')')
             WHEN p.staff_id IS NOT NULL THEN CONCAT('Teknik (', ts.tech_skill, ')')
             WHEN p.other_id IS NOT NULL THEN CONCAT('Diğer (', o.note, ')')
             ELSE 'Belirsiz'
           END AS staff_type,
           CONCAT(mgr.f_name, ' ', mgr.l_name) AS manager_name
    FROM personnel_ p
    JOIN departments_    d   ON p.dept_id  = d.id
    LEFT JOIN manager_   m   ON p.mngr_id  = m.id
    LEFT JOIN technical_staff_ ts ON p.staff_id = ts.id
    LEFT JOIN other_     o   ON p.other_id = o.id
    LEFT JOIN personnel_ mgr ON p.mgr_id   = mgr.id
    ORDER BY $orderBy
";
$result = $conn->query($query);
$personnel = $result->fetch_all(MYSQLI_ASSOC);

$depts = $conn->query("SELECT id, name FROM departments_ ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Panel</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');

  :root {
    --bg:      #0a0e14;
    --panel:   #111820;
    --border:  #1e3a4a;
    --accent:  #00d4ff;
    --accent2: #0066ff;
    --text:    #c8d8e8;
    --muted:   #4a6070;
    --success: #00ff88;
    --warn:    #ffaa00;
    --error:   #ff4060;
    --purple:  #a855f7;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    font-family: 'Rajdhani', sans-serif;
    color: var(--text);
    min-height: 100vh;
  }

  body::before {
    content: '';
    position: fixed; inset: 0;
    background-image:
      linear-gradient(rgba(0,212,255,0.02) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,212,255,0.02) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
  }

  nav {
    background: var(--panel);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
    position: sticky; top: 0; z-index: 100;
  }

  .nav-brand { font-size: 18px; font-weight: 700; letter-spacing: 3px; color: #fff; }
  .nav-brand span { color: var(--accent); }

  .nav-links { display: flex; gap: 4px; flex-wrap: wrap; }

  .nav-links a {
    color: var(--muted);
    text-decoration: none;
    font-size: 12px;
    letter-spacing: 1px;
    padding: 6px 12px;
    border: 1px solid transparent;
    transition: all .2s;
    text-transform: uppercase;
  }

  .nav-links a:hover, .nav-links a.active {
    color: var(--accent);
    border-color: var(--border);
  }

  .nav-sep {
    color: var(--border);
    align-self: center;
    font-size: 16px;
    padding: 0 4px;
  }

  .nav-user {
    display: flex; align-items: center; gap: 16px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    color: var(--muted);
  }

  .nav-user a {
    color: var(--error);
    text-decoration: none;
    font-size: 11px;
    letter-spacing: 1px;
    border: 1px solid var(--error);
    padding: 4px 10px;
    transition: all .2s;
  }

  .nav-user a:hover { background: var(--error); color: var(--bg); }

  main { padding: 32px; position: relative; z-index: 1; }

  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
  }

  h2 { font-size: 24px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
  h2 span { color: var(--accent); }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-family: 'Rajdhani', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    border: 1px solid var(--accent);
    color: var(--accent);
    background: transparent;
    cursor: pointer;
    transition: all .2s;
  }

  .btn:hover { background: var(--accent); color: var(--bg); }

  .filter-bar {
    background: var(--panel);
    border: 1px solid var(--border);
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
  }

  .filter-group { display: flex; flex-direction: column; gap: 6px; }

  .filter-group label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
  }

  .filter-group select,
  .filter-group input {
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'Share Tech Mono', monospace;
    font-size: 13px;
    padding: 8px 12px;
    outline: none;
    min-width: 200px;
    transition: border-color .2s;
  }

  .filter-group select:focus,
  .filter-group input:focus { border-color: var(--accent); }

  #ajaxResult {
    background: rgba(0,212,255,0.05);
    border: 1px solid var(--border);
    border-left: 3px solid var(--accent);
    padding: 12px 16px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    color: var(--accent);
    margin-bottom: 20px;
    display: none;
  }

  .table-wrap {
    background: var(--panel);
    border: 1px solid var(--border);
    overflow-x: auto;
  }

  table { width: 100%; border-collapse: collapse; }

  thead tr {
    background: rgba(0,212,255,0.05);
    border-bottom: 1px solid var(--border);
  }

  th {
    padding: 14px 16px;
    text-align: left;
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--accent);
    white-space: nowrap;
  }

  td {
    padding: 13px 16px;
    font-size: 14px;
    border-bottom: 1px solid rgba(30,58,74,0.5);
    vertical-align: middle;
  }

  tr:last-child td { border-bottom: none; }
  tr:hover td { background: rgba(0,212,255,0.03); }

  .badge {
    display: inline-block;
    padding: 3px 10px;
    font-size: 10px;
    font-family: 'Share Tech Mono', monospace;
    letter-spacing: 1px;
    border: 1px solid;
  }

  .badge-manager  { border-color: var(--warn);    color: var(--warn); }
  .badge-tech     { border-color: var(--accent);  color: var(--accent); }
  .badge-other    { border-color: var(--muted);   color: var(--muted); }

  .mgr-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-family: 'Share Tech Mono', monospace; font-size: 11px;
    color: var(--purple); border: 1px solid rgba(168,85,247,0.4);
    padding: 2px 8px; background: rgba(168,85,247,0.07);
  }

  .mgr-none {
    font-family: 'Share Tech Mono', monospace; font-size: 11px;
    color: var(--muted);
  }

  .actions { display: flex; gap: 8px; }
  .actions a {
    font-size: 11px;
    padding: 4px 10px;
    border: 1px solid;
    text-decoration: none;
    letter-spacing: 1px;
    transition: all .15s;
    font-family: 'Share Tech Mono', monospace;
  }
  .edit-btn  { border-color: var(--accent); color: var(--accent); }
  .edit-btn:hover  { background: var(--accent); color: var(--bg); }
  .del-btn   { border-color: var(--error);  color: var(--error); }
  .del-btn:hover   { background: var(--error);  color: var(--bg); }

  .empty-row td {
    text-align: center;
    color: var(--muted);
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    padding: 40px;
  }

  .alert {
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 14px;
    border: 1px solid;
  }
  .alert-success { border-color: var(--success); color: var(--success); background: rgba(0,255,136,0.05); }
  .alert-error   { border-color: var(--error);   color: var(--error);   background: rgba(255,64,96,0.05); }
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

<nav>
  <div class="nav-brand">VANGUARD <span>LOGIC</span></div>
  <div class="nav-links">
    <a href="index.php" class="active">Personel</a>
    <a href="projects.php">Projeler</a>
    <a href="salary.php">Maaşlar</a>
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
  <div class="page-header">
    <h2>Personel <span>Listesi</span></h2>
    <a href="personnel_form.php" class="btn">+ Yeni Personel</a>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= $_GET['msg'] === 'added' || $_GET['msg'] === 'updated' ? 'success' : 'error' ?>">
      <?php
        $msgs = ['added'=>'Personel başarıyla eklendi.','updated'=>'Personel güncellendi.','deleted'=>'Personel silindi.','error'=>'Bir hata oluştu.'];
        echo htmlspecialchars($msgs[$_GET['msg']] ?? 'İşlem tamamlandı.');
      ?>
    </div>
  <?php endif; ?>

  <div class="filter-bar">
    <div class="filter-group">
      <label>Departmana Göre Filtrele (AJAX)</label>
      <select id="deptFilter">
        <option value="">— Tüm Departmanlar —</option>
        <?php foreach ($depts as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label>Ad / Soyad Ara</label>
      <input type="text" id="searchInput" placeholder="İsim ara...">
    </div>
    <div class="filter-group">
      <label>Kıdeme Göre Sırala (Tarih Fonksiyonu)</label>
      <select id="sortSelect" onchange="location='?sort='+this.value">
        <option value="" <?= $sort==='' ?'selected':'' ?>>— Varsayılan —</option>
        <option value="hire_asc"  <?= $sort==='hire_asc'  ?'selected':'' ?>>↑ En Kıdemli Önce</option>
        <option value="hire_desc" <?= $sort==='hire_desc' ?'selected':'' ?>>↓ En Yeni Personel Önce</option>
      </select>
    </div>
  </div>

  <div id="ajaxResult"></div>

  <div class="table-wrap">
    <table id="personnelTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Ad Soyad</th>
          <th>E-Posta</th>
          <th>Departman</th>
          <th>Tür</th>
          <th>Yöneticisi</th>
          <th>İşe Giriş</th>
          <th>Kıdem</th>
          <th>Aylık Saat</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if (empty($personnel)): ?>
          <tr class="empty-row"><td colspan="10">Kayıt bulunamadı.</td></tr>
        <?php else: ?>
          <?php foreach ($personnel as $p):
            $type = $p['staff_type'];
            $badgeClass = str_contains($type,'Yönetici') ? 'badge-manager' : (str_contains($type,'Teknik') ? 'badge-tech' : 'badge-other');
          ?>
          <tr>
            <td><span style="font-family:'Share Tech Mono',monospace;color:var(--muted)"><?= $p['id'] ?></span></td>
            <td><?= htmlspecialchars($p['f_name'] . ' ' . $p['l_name']) ?></td>
            <td style="font-family:'Share Tech Mono',monospace;font-size:12px"><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['dept_name']) ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(explode(' (',$type)[0]) ?></span></td>
            <td>
              <?php if ($p['manager_name'] && trim($p['manager_name']) !== ' '): ?>
                <span class="mgr-chip">▲ <?= htmlspecialchars($p['manager_name']) ?></span>
              <?php else: ?>
                <span class="mgr-none">—</span>
              <?php endif; ?>
            </td>
            <td style="font-family:'Share Tech Mono',monospace;font-size:12px"><?= $p['hire_date'] ?></td>
            <td style="font-family:'Share Tech Mono',monospace;font-size:12px;white-space:nowrap">
              <?php
                $yil = (int)$p['kidem_yil'];
                $ay  = (int)$p['kidem_ay'] % 12;
                echo $yil . 'y ' . $ay . 'a';
              ?>
            </td>
            <td style="text-align:center"><?= $p['mnthly_hrs'] ?>h</td>
            <td>
              <div class="actions">
                <a href="personnel_form.php?id=<?= $p['id'] ?>" class="edit-btn">DÜZENLE</a>
                <a href="personnel_delete.php?id=<?= $p['id'] ?>"
                   class="del-btn"
                   onclick="return confirm('Bu personeli silmek istediğinize emin misiniz?')">SİL</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script src="assets/js/ajax_personnel.js"></script>
</body>
</html>

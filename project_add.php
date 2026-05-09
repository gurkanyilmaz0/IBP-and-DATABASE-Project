<?php
// ============================================================
// PROJECT_ADD.PHP — Yeni Proje Ekle
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$errors = [];
$data = [
    'name' => '',
    'start_date' => date('Y-m-d'),
    'end_date' => '',
    'budget' => '',
    'sale_price' => '',
    'description' => '',
    'prsnl_id' => 0,
    'customer_id' => null,
    'dept_id' => null
];

// Dropdownlar için verileri çek
$personnelList = $conn->query("SELECT id, f_name, l_name FROM personnel_ ORDER BY f_name")->fetch_all(MYSQLI_ASSOC);
$customers = $conn->query("SELECT id, first_name, last_name FROM customer_ ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);
$departments = $conn->query("SELECT id, name FROM departments_ ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $start_date  = trim($_POST['start_date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '') ?: null;
    $budget      = trim($_POST['budget'] ?? '0');
    $sale_price  = trim($_POST['sale_price'] ?? '0');
    $description = trim($_POST['description'] ?? '') ?: null;
    $prsnl_id    = (int)($_POST['prsnl_id'] ?? 0);
    $customer_id = ($_POST['customer_id'] ?? '') !== '' ? (int)$_POST['customer_id'] : null;
    $dept_id     = ($_POST['dept_id'] ?? '') !== '' ? (int)$_POST['dept_id'] : null;

    // ARC kısıtı kontrolü (customer_id XOR dept_id)
    if ($customer_id && $dept_id) {
        $errors[] = 'Bir proje hem müşteriye hem de departmana ait olamaz. Lütfen birini seçiniz.';
    } elseif (!$customer_id && !$dept_id) {
        $errors[] = 'Lütfen projenin ait olacağı müşteriyi VEYA departmanı seçiniz.';
    }

    if (empty($name))       $errors[] = 'Proje adı boş olamaz.';
    if (empty($start_date)) $errors[] = 'Başlangıç tarihi gereklidir.';
    if ($prsnl_id <= 0)     $errors[] = 'Lütfen sorumlu personel seçiniz.';

    if (empty($errors)) {
        // Yeni ID bul (AUTO_INCREMENT olmadığı varsayımıyla veya max+1)
        $maxRes = $conn->query("SELECT MAX(id) FROM project_");
        $nextId = ($maxRes->fetch_row()[0] ?? 100) + 1;

        $stmt = $conn->prepare("
            INSERT INTO project_ (id, name, start_date, end_date, budget, sale_price, description, prsnl_id, customer_id, dept_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('isssddsiii', $nextId, $name, $start_date, $end_date, $budget, $sale_price, $description, $prsnl_id, $customer_id, $dept_id);
        
        if ($stmt->execute()) {
            header('Location: projects.php?msg=added');
            exit;
        } else {
            $errors[] = 'Veritabanı hatası: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Vanguard Logic — Yeni Proje</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root { --bg:#0a0e14; --panel:#111820; --border:#1e3a4a; --accent:#00d4ff; --text:#c8d8e8; --muted:#4a6070; --success:#00ff88; --error:#ff4060; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); font-family: 'Rajdhani', sans-serif; color: var(--text); min-height: 100vh; }
  nav { background: var(--panel); border-bottom: 1px solid var(--border); padding: 0 32px; display: flex; align-items: center; justify-content: space-between; height: 60px; }
  .nav-brand { font-size: 18px; font-weight: 700; letter-spacing: 3px; color: #fff; } .nav-brand span { color: var(--accent); }
  main { padding: 32px; max-width: 800px; margin: 0 auto; }
  h2 { font-size: 24px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 24px; }
  .form-card { background: var(--panel); border: 1px solid var(--border); border-top: 2px solid var(--accent); padding: 30px; }
  .field { margin-bottom: 20px; }
  label { display: block; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
  input, select, textarea { width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--text); font-family: 'Share Tech Mono', monospace; padding: 12px; outline: none; }
  input:focus, select:focus { border-color: var(--accent); }
  .btn { display: inline-block; padding: 12px 24px; border: 1px solid var(--accent); color: var(--accent); text-decoration: none; font-weight: 700; cursor: pointer; background: transparent; }
  .btn:hover { background: var(--accent); color: var(--bg); }
  .errors { background: rgba(255,64,96,0.1); border: 1px solid var(--error); padding: 15px; margin-bottom: 20px; color: var(--error); font-size: 14px; }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  .arc-box { border: 1px dashed var(--border); padding: 15px; margin-bottom: 20px; background: rgba(0,212,255,0.02); }
</style>
</head>
<body>
<nav><div class="nav-brand">VANGUARD <span>LOGIC</span></div></nav>
<main>
  <h2>Yeni Proje <span>Ekle</span></h2>
  <?php if ($errors): ?>
    <div class="errors"><ul><?php foreach($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <div class="form-card">
    <form method="POST">
      <div class="field"><label>Proje Adı *</label><input type="text" name="name" required></div>
      <div class="field"><label>Açıklama</label><textarea name="description"></textarea></div>
      <div class="field">
        <label>Sorumlu Personel *</label>
        <select name="prsnl_id" required>
          <option value="">-- Personel Seç --</option>
          <?php foreach($personnelList as $p): ?><option value="<?= $p['id'] ?>"><?= $p['f_name'].' '.$p['l_name'] ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="row">
        <div class="field"><label>Başlangıç Tarihi *</label><input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="field"><label>Bitiş Tarihi</label><input type="date" name="end_date"></div>
      </div>
      <div class="row">
        <div class="field"><label>Bütçe (₺)</label><input type="number" name="budget" step="0.01" value="0"></div>
        <div class="field"><label>Satış Fiyatı (₺)</label><input type="number" name="sale_price" step="0.01" value="0"></div>
      </div>
      <div class="arc-box">
        <div class="field">
          <label>Müşteri (Müşteri Projesi ise seçin)</label>
          <select name="customer_id">
            <option value="">-- Müşteri Seç --</option>
            <?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= $c['first_name'].' '.$c['last_name'] ?></option><?php endforeach; ?>
          </select>
        </div>
        <div style="text-align:center; font-size:10px; color:var(--muted); margin: 10px 0;">— VEYA —</div>
        <div class="field">
          <label>Departman (Dahili Proje ise seçin)</label>
          <select name="dept_id">
            <option value="">-- Departman Seç --</option>
            <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= $d['name'] ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn">PROJEYİ KAYDET</button>
        <a href="projects.php" class="btn" style="border-color:var(--muted); color:var(--muted);">İPTAL</a>
      </div>
    </form>
  </div>
</main>
</body>
</html>

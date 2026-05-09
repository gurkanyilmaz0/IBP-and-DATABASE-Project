<?php
// ============================================================
// PROJECT_EDIT.PHP — Proje Düzenle
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$data   = [];

if ($id <= 0) { header('Location: projects.php?msg=error'); exit; }

// Mevcut projeyi çek
$stmt = $conn->prepare("SELECT * FROM project_ WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) { header('Location: projects.php?msg=error'); exit; }

// Personel listesini çek (Dropdown için)
$personnelList = $conn->query("SELECT id, f_name, l_name FROM personnel_ ORDER BY f_name")->fetch_all(MYSQLI_ASSOC);

// FORM GÖNDERME
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $start_date  = trim($_POST['start_date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '') ?: null;
    $budget      = str_replace(['.', ','], ['', '.'], trim($_POST['budget'] ?? '0'));
    $sale_price  = str_replace(['.', ','], ['', '.'], trim($_POST['sale_price'] ?? '0'));
    $description = trim($_POST['description'] ?? '') ?: null;
    $prsnl_id    = (int)($_POST['prsnl_id'] ?? 0);

    // PHP DOĞRULAMA
    if (empty($name))       $errors[] = 'Proje adı boş olamaz.';
    if (empty($start_date)) $errors[] = 'Başlangıç tarihi gereklidir.';
    if (!is_numeric($budget) || (float)$budget < 0)     $errors[] = 'Geçerli bir bütçe giriniz.';
    if (!is_numeric($sale_price) || (float)$sale_price < 0) $errors[] = 'Geçerli bir satış fiyatı giriniz.';
    if ($end_date && $end_date < $start_date) $errors[] = 'Bitiş tarihi başlangıç tarihinden önce olamaz.';
    if ($prsnl_id <= 0) $errors[] = 'Lütfen sorumlu personel seçiniz.';

    if (empty($errors)) {
        $budgetVal    = (float)$budget;
        $salePriceVal = (float)$sale_price;
        $stmt = $conn->prepare("
            UPDATE project_
            SET name=?, start_date=?, end_date=?, budget=?, sale_price=?, description=?, prsnl_id=?
            WHERE id=?
        ");
        $stmt->bind_param('sssddsii', $name, $start_date, $end_date, $budgetVal, $salePriceVal, $description, $prsnl_id, $id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        header('Location: projects.php?msg=updated');
        exit;
    }

    // Hata varsa form verilerini güncelle
    $data['name']        = $name;
    $data['start_date']  = $start_date;
    $data['end_date']    = $end_date;
    $data['budget']      = $budget;
    $data['sale_price']  = $sale_price;
    $data['description'] = $description;
    $data['prsnl_id']    = $prsnl_id;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Proje Düzenle</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');

  :root {
    --bg:      #0a0e14;
    --panel:   #111820;
    --border:  #1e3a4a;
    --accent:  #00d4ff;
    --text:    #c8d8e8;
    --muted:   #4a6070;
    --success: #00ff88;
    --warn:    #ffaa00;
    --error:   #ff4060;
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
  }

  nav {
    background: var(--panel);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    display: flex; align-items: center; justify-content: space-between;
    height: 60px; position: sticky; top: 0; z-index: 100;
  }
  .nav-brand { font-size: 18px; font-weight: 700; letter-spacing: 3px; color: #fff; }
  .nav-brand span { color: var(--accent); }
  .nav-user { display: flex; align-items: center; gap: 16px; font-family: 'Share Tech Mono', monospace; font-size: 12px; color: var(--muted); }
  .nav-user a { color: var(--error); text-decoration: none; font-size: 11px; letter-spacing: 1px; border: 1px solid var(--error); padding: 4px 10px; transition: all .2s; }
  .nav-user a:hover { background: var(--error); color: var(--bg); }

  main { padding: 32px; position: relative; z-index: 1; max-width: 760px; }

  .breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-family: 'Share Tech Mono', monospace; font-size: 11px; color: var(--muted);
    margin-bottom: 24px; letter-spacing: 1px;
  }
  .breadcrumb a { color: var(--accent); text-decoration: none; }
  .breadcrumb a:hover { text-decoration: underline; }
  .breadcrumb span { color: var(--muted); }

  h2 { font-size: 24px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 28px; }
  h2 span { color: var(--accent); }

  .project-badge {
    display: inline-block;
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px; letter-spacing: 2px;
    border: 1px solid var(--border); color: var(--muted);
    padding: 4px 12px; margin-bottom: 20px;
  }

  .form-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-top: 2px solid var(--accent);
    padding: 36px;
  }

  .section-divider {
    font-size: 10px; letter-spacing: 3px; text-transform: uppercase;
    color: var(--muted); margin: 28px 0 20px;
    display: flex; align-items: center; gap: 12px;
  }
  .section-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
  }

  .field { margin-bottom: 22px; }

  label {
    display: block; font-size: 11px; letter-spacing: 2px;
    text-transform: uppercase; color: var(--muted); margin-bottom: 8px;
  }

  input[type="text"],
  input[type="number"],
  input[type="date"],
  textarea {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'Share Tech Mono', monospace;
    font-size: 13px;
    padding: 12px 14px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    resize: none;
  }

  input:focus, textarea:focus, select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0,212,255,0.08);
  }

  input.err, textarea.err, select.err { border-color: var(--error); }

  select {
    width: 100%;
    background: var(--panel);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'Share Tech Mono', monospace;
    font-size: 13px;
    padding: 12px 14px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  textarea { min-height: 90px; line-height: 1.6; font-family: 'Rajdhani', sans-serif; font-size: 14px; }

  .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

  .field-err {
    font-size: 11px; color: var(--error); margin-top: 6px;
    display: none; font-family: 'Share Tech Mono', monospace;
  }
  .field-err.show { display: block; }

  .hint { font-size: 11px; color: var(--muted); margin-top: 5px; font-family: 'Share Tech Mono', monospace; }

  .errors {
    background: rgba(255,64,96,0.08);
    border: 1px solid var(--error);
    padding: 14px 18px; margin-bottom: 24px;
  }
  .errors li { color: var(--error); font-size: 13px; margin-left: 16px; }

  .form-actions { display: flex; gap: 12px; margin-top: 32px; }

  .btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px;
    font-family: 'Rajdhani', sans-serif; font-size: 13px;
    font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    text-decoration: none; border: 1px solid var(--accent);
    color: var(--accent); background: transparent;
    cursor: pointer; transition: all .2s;
  }
  .btn:hover { background: var(--accent); color: var(--bg); }
  .btn-ghost { border-color: var(--muted); color: var(--muted); }
  .btn-ghost:hover { background: var(--muted); color: var(--bg); }

  /* Mevcut değerler önizleme kutusu */
  .current-val {
    background: rgba(0,212,255,0.04);
    border: 1px solid rgba(0,212,255,0.15);
    padding: 8px 12px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px; color: var(--muted);
    margin-bottom: 6px;
  }
  .current-val span { color: var(--accent); }
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
    <a href="projects.php">Projeler</a>
    <span>›</span>
    <span>Düzenle</span>
    <span>›</span>
    <span><?= htmlspecialchars($data['name']) ?></span>
  </div>

  <h2>Proje <span>Düzenle</span></h2>
  <div class="project-badge">PRJ-<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?> · ID: <?= $id ?></div>

  <?php if (!empty($errors)): ?>
    <div class="errors"><ul>
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <div class="form-card">
    <form id="projForm" method="POST" novalidate>

      <!-- Proje Adı -->
      <div class="field">
        <label>Proje Adı *</label>
        <input type="text" name="name" id="name"
               value="<?= htmlspecialchars($data['name']) ?>"
               placeholder="Proje adını giriniz">
        <div class="field-err" id="name_err">Proje adı boş olamaz.</div>
      </div>

      <!-- Açıklama -->
      <div class="field">
        <label>Açıklama</label>
        <textarea name="description" id="description"
                  placeholder="Proje açıklaması (opsiyonel)"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
      </div>

      <div class="section-divider">Sorumlu & Tarihler</div>

      <!-- Sorumlu Personel -->
      <div class="field">
        <label>Sorumlu Personel *</label>
        <select name="prsnl_id" id="prsnl_id">
          <option value="0">-- Personel Seçiniz --</option>
          <?php foreach ($personnelList as $pers): ?>
            <option value="<?= $pers['id'] ?>" <?= (isset($data['prsnl_id']) && $data['prsnl_id'] == $pers['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($pers['f_name'] . ' ' . $pers['l_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="field-err" id="prsnl_err">Lütfen sorumlu personel seçiniz.</div>
      </div>

      <!-- Tarihler -->
      <div class="row-2">
        <div class="field">
          <label>Başlangıç Tarihi *</label>
          <input type="date" name="start_date" id="start_date"
                 value="<?= htmlspecialchars($data['start_date']) ?>">
          <div class="field-err" id="start_err">Başlangıç tarihi gereklidir.</div>
        </div>
        <div class="field">
          <label>Bitiş Tarihi <span style="color:var(--muted);font-size:10px">(opsiyonel)</span></label>
          <input type="date" name="end_date" id="end_date"
                 value="<?= htmlspecialchars($data['end_date'] ?? '') ?>">
          <div class="hint">Boş bırakılırsa "Devam ediyor" olarak görünür.</div>
          <div class="field-err" id="end_err">Bitiş tarihi başlangıçtan önce olamaz.</div>
        </div>
      </div>

      <div class="section-divider">Finansal Bilgiler</div>

      <!-- Fiyatlar -->
      <div class="row-2">
        <div class="field">
          <label>Bütçe (₺) *</label>
          <input type="number" name="budget" id="budget"
                 value="<?= htmlspecialchars($data['budget']) ?>"
                 min="0" step="0.01"
                 placeholder="0.00">
          <div class="field-err" id="budget_err">Geçerli bir bütçe giriniz.</div>
        </div>
        <div class="field">
          <label>Satış Fiyatı (₺) *</label>
          <input type="number" name="sale_price" id="sale_price"
                 value="<?= htmlspecialchars($data['sale_price']) ?>"
                 min="0" step="0.01"
                 placeholder="0.00">
          <div class="field-err" id="price_err">Geçerli bir satış fiyatı giriniz.</div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn">✓ Güncelle</button>
        <a href="projects.php" class="btn btn-ghost">İptal</a>
      </div>
    </form>
  </div>
</main>

<script>
document.getElementById('projForm').addEventListener('submit', function(e) {
  let valid = true;

  function check(id, errId, condition) {
    const el = document.getElementById(id);
    const er = document.getElementById(errId);
    if (!el || !er) return;
    if (condition(el.value)) {
      el.classList.add('err'); er.classList.add('show'); valid = false;
    } else {
      el.classList.remove('err'); er.classList.remove('show');
    }
  }

  check('name',       'name_err',   v => v.trim() === '');
  check('start_date', 'start_err',  v => v.trim() === '');
  check('budget',     'budget_err', v => v === '' || isNaN(v) || Number(v) < 0);
  check('sale_price', 'price_err',  v => v === '' || isNaN(v) || Number(v) < 0);
  check('prsnl_id',   'prsnl_err',  v => Number(v) <= 0);

  // Bitiş tarihi kontrolü
  const start = document.getElementById('start_date').value;
  const end   = document.getElementById('end_date').value;
  if (end && end < start) {
    document.getElementById('end_date').classList.add('err');
    document.getElementById('end_err').classList.add('show');
    valid = false;
  } else {
    document.getElementById('end_date').classList.remove('err');
    document.getElementById('end_err').classList.remove('show');
  }

  if (!valid) e.preventDefault();
});

// Canlı hata temizleme
['name','start_date','end_date','budget','sale_price','prsnl_id'].forEach(function(id) {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', function() {
    this.classList.remove('err');
    const errEl = document.getElementById(id.replace('start_date','start').replace('end_date','end').replace('sale_price','price').replace('prsnl_id','prsnl') + '_err');
    if (errEl) errEl.classList.remove('show');
  });
});
</script>
</body>
</html>

<?php
// ============================================================
// PERSONNEL_FORM.PHP — Personel Ekle / Düzenle
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit   = $id > 0;
$errors   = [];
$data     = ['f_name'=>'','l_name'=>'','email'=>'','password'=>'','mnthly_hrs'=>160,'dept_id'=>'','mngr_id'=>'','staff_id'=>'','other_id'=>''];

// Dropdown verileri
$depts  = $conn->query("SELECT id, name FROM departments_ ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$managers = $conn->query("SELECT id, mngmnt_lvl FROM manager_")->fetch_all(MYSQLI_ASSOC);
$staffs   = $conn->query("SELECT id, tech_skill FROM technical_staff_")->fetch_all(MYSQLI_ASSOC);
$others   = $conn->query("SELECT id, note FROM other_")->fetch_all(MYSQLI_ASSOC);

// Düzenleme modunda mevcut veriyi çek
if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM personnel_ WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $data = array_merge($data, $row);
    $stmt->close();
}

// FORM GÖNDERME
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['f_name']    = trim($_POST['f_name'] ?? '');
    $data['l_name']    = trim($_POST['l_name'] ?? '');
    $data['email']     = trim($_POST['email'] ?? '');
    $data['password']  = trim($_POST['password'] ?? '');
    $data['mnthly_hrs']= (int)($_POST['mnthly_hrs'] ?? 160);
    $data['dept_id']   = (int)($_POST['dept_id'] ?? 0);
    $data['mngr_id']   = !empty($_POST['mngr_id'])  ? (int)$_POST['mngr_id']  : null;
    $data['staff_id']  = !empty($_POST['staff_id'])  ? (int)$_POST['staff_id'] : null;
    $data['other_id']  = !empty($_POST['other_id'])  ? (int)$_POST['other_id'] : null;

    // PHP DOĞRULAMA
    if (empty($data['f_name']))  $errors[] = 'Ad boş olamaz.';
    if (empty($data['l_name']))  $errors[] = 'Soyad boş olamaz.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (!$isEdit && strlen($data['password']) < 6) $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    if (empty($data['dept_id'])) $errors[] = 'Departman seçiniz.';
    if ($data['mnthly_hrs'] < 0 || $data['mnthly_hrs'] > 999) $errors[] = 'Geçerli aylık saat giriniz (0-999).';

    if (empty($errors)) {
        if ($isEdit) {
            // GÜNCELLE
            if (!empty($data['password'])) {
                $hashed   = hash('sha256', $data['password']);
                $mngrId   = $data['mngr_id']  !== null ? (int)$data['mngr_id']  : null;
                $staffId  = $data['staff_id'] !== null ? (int)$data['staff_id'] : null;
                $otherId  = $data['other_id'] !== null ? (int)$data['other_id'] : null;
                $mnthHrs  = (int)$data['mnthly_hrs'];
                $deptId   = (int)$data['dept_id'];
                $stmt = $conn->prepare("UPDATE personnel_ SET f_name=?,l_name=?,email=?,password=?,mnthly_hrs=?,dept_id=?,mngr_id=?,staff_id=?,other_id=? WHERE id=?");
                $stmt->bind_param('ssssiiiiii', $data['f_name'],$data['l_name'],$data['email'],$hashed,$mnthHrs,$deptId,$mngrId,$staffId,$otherId,$id);
            } else {
                $mngrId   = $data['mngr_id']  !== null ? (int)$data['mngr_id']  : null;
                $staffId  = $data['staff_id'] !== null ? (int)$data['staff_id'] : null;
                $otherId  = $data['other_id'] !== null ? (int)$data['other_id'] : null;
                $mnthHrs  = (int)$data['mnthly_hrs'];
                $deptId   = (int)$data['dept_id'];
                $stmt = $conn->prepare("UPDATE personnel_ SET f_name=?,l_name=?,email=?,mnthly_hrs=?,dept_id=?,mngr_id=?,staff_id=?,other_id=? WHERE id=?");
                $stmt->bind_param('sssiiiiii', $data['f_name'],$data['l_name'],$data['email'],$mnthHrs,$deptId,$mngrId,$staffId,$otherId,$id);
            }
            $stmt->execute();
            header('Location: index.php?msg=updated');
        } else {
            // EKLE
            $hashed  = hash('sha256', $data['password']);
            $newId   = (int)$conn->query("SELECT COALESCE(MAX(id),0)+1 FROM personnel_")->fetch_row()[0];
            $mngrId  = $data['mngr_id']  !== null ? (int)$data['mngr_id']  : null;
            $staffId = $data['staff_id'] !== null ? (int)$data['staff_id'] : null;
            $otherId = $data['other_id'] !== null ? (int)$data['other_id'] : null;
            $mnthHrs = (int)$data['mnthly_hrs'];
            $deptId  = (int)$data['dept_id'];
            $stmt    = $conn->prepare("INSERT INTO personnel_(id,f_name,l_name,hire_date,email,password,mnthly_hrs,dept_id,mngr_id,staff_id,other_id) VALUES(?,?,?,CURDATE(),?,?,?,?,?,?,?)");
            $stmt->bind_param('issssiiiii', $newId,$data['f_name'],$data['l_name'],$data['email'],$hashed,$mnthHrs,$deptId,$mngrId,$staffId,$otherId);
            $stmt->execute();
            header('Location: index.php?msg=added');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Vanguard Logic — <?= $isEdit ? 'Personel Düzenle' : 'Yeni Personel' ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root{--bg:#0a0e14;--panel:#111820;--border:#1e3a4a;--accent:#00d4ff;--text:#c8d8e8;--muted:#4a6070;--error:#ff4060;--success:#00ff88;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{background:var(--bg);font-family:'Rajdhani',sans-serif;color:var(--text);min-height:100vh;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;}
  nav{background:var(--panel);border-bottom:1px solid var(--border);padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:60px;}
  .nav-brand{font-size:18px;font-weight:700;letter-spacing:3px;color:#fff;}
  .nav-brand span{color:var(--accent);}
  .nav-user{display:flex;align-items:center;gap:16px;font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--muted);}
  .nav-user a{color:var(--error);text-decoration:none;font-size:11px;letter-spacing:1px;border:1px solid var(--error);padding:4px 10px;}
  main{padding:32px;position:relative;z-index:1;max-width:700px;}
  h2{font-size:24px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:28px;}
  h2 span{color:var(--accent);}
  .form-card{background:var(--panel);border:1px solid var(--border);border-top:2px solid var(--accent);padding:36px;}
  .field{margin-bottom:20px;}
  label{display:block;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
  input,select{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);font-family:'Share Tech Mono',monospace;font-size:13px;padding:11px 14px;outline:none;transition:border-color .2s;}
  input:focus,select:focus{border-color:var(--accent);}
  select option{background:var(--bg);}
  .row-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
  .hint{font-size:11px;color:var(--muted);margin-top:5px;font-family:'Share Tech Mono',monospace;}
  .errors{background:rgba(255,64,96,0.08);border:1px solid var(--error);padding:14px 18px;margin-bottom:24px;}
  .errors li{color:var(--error);font-size:13px;margin-left:16px;}
  .form-actions{display:flex;gap:12px;margin-top:28px;}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;font-family:'Rajdhani',sans-serif;font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;text-decoration:none;border:1px solid var(--accent);color:var(--accent);background:transparent;cursor:pointer;transition:all .2s;}
  .btn:hover{background:var(--accent);color:var(--bg);}
  .btn-ghost{border-color:var(--muted);color:var(--muted);}
  .btn-ghost:hover{background:var(--muted);color:var(--bg);}
  .field-err{font-size:11px;color:var(--error);margin-top:5px;display:none;font-family:'Share Tech Mono',monospace;}
  .field-err.show{display:block;}
  input.err,select.err{border-color:var(--error);}
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
  <h2><?= $isEdit ? 'Personel <span>Düzenle</span>' : 'Yeni <span>Personel</span>' ?></h2>

  <?php if (!empty($errors)): ?>
    <div class="errors"><ul>
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <div class="form-card">
    <form id="pForm" method="POST" novalidate>
      <div class="row-2">
        <div class="field">
          <label>Ad *</label>
          <input type="text" name="f_name" id="f_name" value="<?= htmlspecialchars($data['f_name']) ?>">
          <div class="field-err" id="f_name_err">Ad boş olamaz.</div>
        </div>
        <div class="field">
          <label>Soyad *</label>
          <input type="text" name="l_name" id="l_name" value="<?= htmlspecialchars($data['l_name']) ?>">
          <div class="field-err" id="l_name_err">Soyad boş olamaz.</div>
        </div>
      </div>

      <div class="field">
        <label>E-Posta *</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($data['email']) ?>">
        <div class="field-err" id="email_err">Geçerli bir e-posta giriniz.</div>
      </div>

      <div class="field">
        <label><?= $isEdit ? 'Yeni Şifre (boş bırakılırsa değişmez)' : 'Şifre *' ?></label>
        <input type="password" name="password" id="password">
        <div class="field-err" id="pass_err">Şifre en az 6 karakter olmalıdır.</div>
        <div class="hint">Şifre SHA-256 ile şifrelenerek saklanır.</div>
      </div>

      <div class="row-2">
        <div class="field">
          <label>Departman *</label>
          <select name="dept_id" id="dept_id">
            <option value="">— Seçiniz —</option>
            <?php foreach ($depts as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $data['dept_id'] == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="field-err" id="dept_err">Departman seçiniz.</div>
        </div>
        <div class="field">
          <label>Aylık Saat</label>
          <input type="number" name="mnthly_hrs" id="mnthly_hrs" value="<?= $data['mnthly_hrs'] ?>" min="0" max="999">
          <div class="field-err" id="hrs_err">Aylık saat 0-999 arasında olmalıdır.</div>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label>Yönetici Tipi (opsiyonel)</label>
          <select name="mngr_id">
            <option value="">— Yok —</option>
            <?php foreach ($managers as $m): ?>
              <option value="<?= $m['id'] ?>" <?= $data['mngr_id'] == $m['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['mngmnt_lvl']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Teknik Personel Tipi (opsiyonel)</label>
          <select name="staff_id">
            <option value="">— Yok —</option>
            <?php foreach ($staffs as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $data['staff_id'] == $s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['tech_skill']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field" style="max-width:340px">
        <label>Diğer Tip (opsiyonel)</label>
        <select name="other_id">
          <option value="">— Yok —</option>
          <?php foreach ($others as $o): ?>
            <option value="<?= $o['id'] ?>" <?= $data['other_id'] == $o['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($o['note']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></button>
        <a href="index.php" class="btn btn-ghost">İptal</a>
      </div>
    </form>
  </div>
</main>

<script>
// JS DOĞRULAMA
document.getElementById('pForm').addEventListener('submit', function(e) {
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

  check('f_name',  'f_name_err', v => v.trim() === '');
  check('l_name',  'l_name_err', v => v.trim() === '');
  check('email',   'email_err',  v => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v));
  check('dept_id', 'dept_err',   v => v === '');
  check('mnthly_hrs', 'hrs_err', v => v === '' || isNaN(v) || Number(v) < 0 || Number(v) > 999);

  const pass = document.getElementById('password').value;
  const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
  if (!isEdit && pass.length < 6) {
    document.getElementById('password').classList.add('err');
    document.getElementById('pass_err').classList.add('show');
    valid = false;
  }

  if (!valid) e.preventDefault();
});
</script>
</body>
</html>

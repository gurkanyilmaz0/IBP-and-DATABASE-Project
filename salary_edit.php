<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: salary.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    
    $stmt = $conn->prepare("UPDATE salary_ SET amount = ? WHERE id = ?");
    $stmt->bind_param('di', $amount, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: salary.php');
    exit;
}

$stmt = $conn->prepare("SELECT s.*, p.f_name, p.l_name FROM salary_ s JOIN personnel_ p ON s.psnl_id = p.id WHERE s.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header('Location: salary.php'); exit; }
$salary = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Maaş Düzenle</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');
  :root{--bg:#0a0e14;--panel:#111820;--border:#1e3a4a;--accent:#00d4ff;--text:#c8d8e8;--muted:#4a6070;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{background:var(--bg);font-family:'Rajdhani',sans-serif;color:var(--text);padding:40px;min-height:100vh;display:flex;align-items:center;justify-content:center;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;}
  .form-container{background:var(--panel);border:1px solid var(--border);border-top:3px solid var(--accent);padding:40px;width:100%;max-width:500px;position:relative;z-index:1;box-shadow:0 10px 30px rgba(0,0,0,0.5);}
  h2{font-size:24px;margin-bottom:24px;letter-spacing:1px;text-transform:uppercase;}
  h2 span{color:var(--accent);}
  .field{margin-bottom:20px;}
  label{display:block;font-size:12px;letter-spacing:2px;color:var(--muted);margin-bottom:8px;text-transform:uppercase;}
  input{width:100%;padding:14px;background:var(--bg);border:1px solid var(--border);color:var(--accent);font-size:16px;font-family:'Share Tech Mono',monospace;transition:border-color .2s;}
  input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 2px rgba(0,212,255,0.1);}
  button{width:100%;padding:16px;background:transparent;border:1px solid var(--accent);color:var(--accent);font-weight:700;font-size:16px;letter-spacing:2px;cursor:pointer;font-family:'Rajdhani',sans-serif;text-transform:uppercase;transition:all .2s;margin-top:10px;}
  button:hover{background:var(--accent);color:var(--bg);}
  .back-link{display:block;text-align:center;margin-top:20px;color:var(--muted);text-decoration:none;font-size:14px;font-family:'Share Tech Mono',monospace;transition:color .2s;}
  .back-link:hover{color:var(--accent);}
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
<div class="form-container">
  <h2>Maaş <span>Düzenle</span></h2>
  <div style="font-family:'Share Tech Mono',monospace;font-size:14px;margin-bottom:24px;color:var(--text)">
    Personel: <strong style="color:var(--accent)"><?= htmlspecialchars($salary['f_name'] . ' ' . $salary['l_name']) ?></strong>
  </div>
  <form method="POST">
    <div class="field">
      <label>Maaş Tutarı (₺)</label>
      <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($salary['amount']) ?>" required>
    </div>
    <div class="field">
      <label>Ödeme Tarihi (Sadece Bilgi)</label>
      <input type="text" value="<?= htmlspecialchars($salary['pymnt_date']) ?>" disabled style="color:var(--muted);border-color:transparent;background:rgba(255,255,255,0.02)">
    </div>
    <button type="submit">KAYDET</button>
    <a href="salary.php" class="back-link">İptal ve Geri Dön</a>
  </form>
</div>
</body>
</html>

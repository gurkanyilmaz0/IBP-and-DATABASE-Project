<?php
// ============================================================
// LOGIN.PHP — Kullanıcı Kimlik Doğrulama
// JS doğrulama + PHP doğrulama + SHA256 + MySQL
// ============================================================
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PHP DOĞRULAMA
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre boş bırakılamaz.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi giriniz.';
    } else {
        require_once 'includes/db.php';

        // SHA256 ile şifre hash
        $hashed = hash('sha256', $password);

        $stmt = $conn->prepare(
            "SELECT id, f_name, l_name, email, mngr_id FROM personnel_ WHERE email = ? AND password = ?"
        );
        $stmt->bind_param('ss', $email, $hashed);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['mngr_id'] !== null) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['f_name'] . ' ' . $user['l_name'];
                $_SESSION['user_email']= $user['email'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Sisteme sadece yönetici yetkisine sahip personeller giriş yapabilir.';
            }
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Giriş</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');

  :root {
    --bg:       #0a0e14;
    --panel:    #111820;
    --border:   #1e3a4a;
    --accent:   #00d4ff;
    --accent2:  #0066ff;
    --text:     #c8d8e8;
    --muted:    #4a6070;
    --error:    #ff4060;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    font-family: 'Rajdhani', sans-serif;
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  /* Arka plan grid efekti */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(0,212,255,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,212,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
  }

  .panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-top: 2px solid var(--accent);
    width: 420px;
    padding: 48px 40px;
    position: relative;
    box-shadow: 0 0 60px rgba(0,212,255,0.08), 0 20px 60px rgba(0,0,0,0.5);
  }

  .panel::before {
    content: '';
    position: absolute;
    top: -1px; left: 20px; right: 20px; height: 2px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
  }

  .logo {
    text-align: center;
    margin-bottom: 36px;
  }

  .logo-icon {
    width: 56px; height: 56px;
    border: 2px solid var(--accent);
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--accent);
    margin-bottom: 12px;
    position: relative;
  }

  .logo-icon::after {
    content: '';
    position: absolute;
    inset: 3px;
    border: 1px solid rgba(0,212,255,0.3);
    border-radius: 2px;
  }

  h1 {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #fff;
  }

  .subtitle {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--muted);
    letter-spacing: 2px;
    margin-top: 4px;
  }

  .field { margin-bottom: 20px; }

  label {
    display: block;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 8px;
  }

  input[type="email"],
  input[type="password"] {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'Share Tech Mono', monospace;
    font-size: 14px;
    padding: 12px 16px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
  }

  input.input-error { border-color: var(--error); }

  .field-error {
    font-size: 11px;
    color: var(--error);
    margin-top: 6px;
    display: none;
    font-family: 'Share Tech Mono', monospace;
  }

  .field-error.visible { display: block; }

  .alert-error {
    background: rgba(255,64,96,0.1);
    border: 1px solid var(--error);
    color: var(--error);
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 20px;
    font-family: 'Share Tech Mono', monospace;
  }

  button[type="submit"] {
    width: 100%;
    background: transparent;
    border: 1px solid var(--accent);
    color: var(--accent);
    font-family: 'Rajdhani', sans-serif;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    padding: 14px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: color .2s, background .2s;
    margin-top: 8px;
  }

  button[type="submit"]:hover {
    background: var(--accent);
    color: var(--bg);
  }

  .info-row {
    text-align: center;
    margin-top: 20px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--muted);
  }
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

<div class="panel">
  <div class="logo">
    <div class="logo-icon">⬡</div>
    <h1>Vanguard Logic</h1>
    <div class="subtitle">PERSONEL YÖNETİM SİSTEMİ</div>
  </div>

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form id="loginForm" method="POST" action="login.php" novalidate>
    <div class="field">
      <label for="email">E-Posta Adresi</label>
      <input type="email" id="email" name="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             placeholder="kullanici@vanguard.com">
      <div class="field-error" id="emailErr">Geçerli bir e-posta giriniz.</div>
    </div>

    <div class="field">
      <label for="password">Şifre</label>
      <input type="password" id="password" name="password" placeholder="••••••••">
      <div class="field-error" id="passErr">Şifre en az 6 karakter olmalıdır.</div>
    </div>

    <button type="submit">Sisteme Giriş</button>
  </form>


</div>

<script src="assets/js/login.js"></script>
</body>
</html>

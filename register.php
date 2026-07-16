<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $pass     = $_POST['password']  ?? '';
    $pass2    = $_POST['password2'] ?? '';
    if (!$username||!$email||!$pass) { $error = 'Semua field wajib!'; }
    elseif (strlen($username)<3)  { $error = 'Username min. 3 karakter!'; }
    elseif (strlen($pass)<6)      { $error = 'Password min. 6 karakter!'; }
    elseif ($pass !== $pass2)     { $error = 'Password tidak cocok!'; }
    else {
        $s = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $s->execute([$email,$username]);
        if ($s->fetch()) { $error = 'Email atau username sudah dipakai!'; }
        else {
            $pdo->prepare("INSERT INTO users(username,email,password) VALUES(?,?,?)")
                ->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT)]);
            $success = 'Akun berhasil dibuat!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KaiVC — Daftar</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
    body{background:#03080f;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
    .glow{position:fixed;top:0;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(74,158,255,.12) 0%,transparent 70%);pointer-events:none;}
    .card{width:100%;max-width:400px;background:#060e1c;border:1px solid rgba(74,158,255,.15);border-radius:24px;padding:40px 36px;position:relative;}
    .logo{text-align:center;margin-bottom:28px;}
    .logo-icon{width:64px;height:64px;background:linear-gradient(135deg,#1d4ed8,#4a9eff);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 0 36px rgba(74,158,255,.4);}
    h1{font-size:26px;font-weight:900;letter-spacing:-1px;text-align:center;} h1 span{color:#4a9eff;}
    .sub{color:#3a5470;font-size:13px;text-align:center;margin-top:4px;}
    .field{margin-bottom:12px;}
    label{display:block;font-size:11px;font-weight:700;color:#3a5470;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;}
    input{width:100%;background:#0a1628;border:1.5px solid rgba(74,158,255,.15);border-radius:12px;padding:13px 14px;color:#e2e8f0;font-size:15px;transition:border-color .2s;outline:none;}
    input:focus{border-color:#4a9eff;box-shadow:0 0 0 3px rgba(74,158,255,.1);}
    input::placeholder{color:#3a5470;}
    .btn-main{width:100%;margin-top:8px;background:linear-gradient(135deg,#1d4ed8,#4a9eff);color:#fff;border:none;border-radius:100px;padding:14px;font-size:15px;font-weight:800;cursor:pointer;transition:opacity .15s,transform .1s;box-shadow:0 4px 20px rgba(74,158,255,.3);}
    .btn-main:hover{opacity:.9;} .btn-main:active{transform:scale(.97);}
    .err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;border-radius:12px;padding:12px 14px;font-size:13px;margin-bottom:14px;}
    .ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;border-radius:12px;padding:12px 14px;font-size:13px;margin-bottom:14px;}
    .link-row{text-align:center;color:#3a5470;font-size:13px;margin-top:18px;}
    .link-row a{color:#4a9eff;font-weight:700;text-decoration:none;}
  </style>
</head>
<body>
<div class="glow"></div>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg width="32" height="32" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
    </div>
    <h1><span>Kai</span>VC</h1>
    <p class="sub">Buat akun gratis</p>
  </div>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success) ?> — <a href="login.php" style="color:#4ade80;font-weight:700;">Masuk</a></div><?php endif; ?>
  <form method="POST" id="register-form">
    <div class="field"><label>Username</label><input id="un-in" type="text" name="username" placeholder="username_kamu" required value="<?= htmlspecialchars($_POST['username']??'') ?>"></div>
    <div class="field"><label>Email</label><input id="em-in" type="email" name="email" placeholder="kamu@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>"></div>
    <div class="field"><label>Password</label><input id="pw-in" type="password" name="password" placeholder="Min. 6 karakter" required></div>
    <div class="field"><label>Ulangi Password</label><input id="pw2-in" type="password" name="password2" placeholder="Ulangi password" required></div>
    <button id="reg-btn" class="btn-main" type="submit">Buat Akun</button>
  </form>
  <div class="link-row">Sudah punya akun? <a id="to-login" href="login.php">Masuk</a></div>
</div>
</body>
</html>

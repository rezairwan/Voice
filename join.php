<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$code = trim($_GET['code'] ?? '');
if (!$code) { header('Location: friends.php'); exit; }

// Get server by invite code
$s = $pdo->prepare("SELECT * FROM servers WHERE invite_code=?");
$s->execute([$code]); $server = $s->fetch();

if (!$server) {
    // Invalid code page
    ?><!DOCTYPE html>
    <html lang="id"><head><meta charset="UTF-8"><title>Undangan Tidak Valid</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}body{background:#111214;color:#f2f3f5;display:flex;align-items:center;justify-content:center;height:100vh;}</style>
    </head><body>
    <div style="text-align:center;padding:40px;">
      <div style="font-size:48px;margin-bottom:16px;">🚫</div>
      <div style="font-size:22px;font-weight:800;margin-bottom:8px;">Undangan Tidak Valid</div>
      <div style="color:#949ba4;margin-bottom:24px;">Kode undangan sudah kadaluarsa atau tidak valid.</div>
      <a href="friends.php" style="background:#5865f2;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;">Buka KaiVC</a>
    </div>
    </body></html>
    <?php exit;
}

// If not logged in, save intended server and redirect to login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['pending_invite'] = $code;
    header('Location: login.php?next=join&code='.$code); exit;
}

$userId = $_SESSION['user_id'];
$me = getCurrentUser($pdo);

// Already member?
$s = $pdo->prepare("SELECT id FROM server_members WHERE server_id=? AND user_id=?");
$s->execute([$server['id'],$userId]); $already = $s->fetch();

// Join if not member
if (!$already) {
    try {
        $pdo->prepare("INSERT INTO server_members(server_id,user_id,role) VALUES(?,?,'member')")->execute([$server['id'],$userId]);
    } catch(Exception $e) {}
}

// Get member count
$s = $pdo->prepare("SELECT COUNT(*) FROM server_members WHERE server_id=?");
$s->execute([$server['id']]); $memberCount = $s->fetchColumn();

// Get online count
$s = $pdo->prepare("SELECT COUNT(*) FROM server_members sm JOIN users u ON sm.user_id=u.id WHERE sm.server_id=? AND u.status='online'");
$s->execute([$server['id']]); $onlineCount = $s->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bergabung ke <?= htmlspecialchars($server['name']) ?> — KaiVC</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
body{background:#111214;color:#f2f3f5;display:flex;align-items:center;justify-content:center;height:100vh;overflow:hidden;}
.card{background:#1e1f22;border-radius:20px;padding:0;width:440px;max-width:92vw;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.7);}
.card-banner{height:100px;background:linear-gradient(135deg,#4752c4,#5865f2,#7c3aed);position:relative;}
.srv-icon{width:80px;height:80px;border-radius:24px;background:linear-gradient(135deg,#5865f2,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:#fff;position:absolute;bottom:-24px;left:24px;border:6px solid #1e1f22;}
.card-body{padding:40px 24px 28px;}
.invited-by{font-size:12px;color:#949ba4;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.srv-name{font-size:24px;font-weight:900;margin-bottom:12px;}
.srv-stats{display:flex;gap:16px;margin-bottom:24px;}
.stat{display:flex;align-items:center;gap:6px;font-size:13px;color:#949ba4;font-weight:600;}
.dot{width:8px;height:8px;border-radius:50%;}
.dot-g{background:#23a55a;} .dot-grey{background:#686c72;}
.join-btn{width:100%;padding:14px;background:#5865f2;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:background .15s;margin-bottom:10px;}
.join-btn:hover{background:#4752c4;}
<?php if($already): ?>.join-btn{background:#23a55a;} .join-btn:hover{background:#1a7a43;}<?php endif; ?>
.cancel-link{display:block;text-align:center;color:#949ba4;font-size:13px;font-weight:500;text-decoration:none;padding:6px;}
.cancel-link:hover{color:#f2f3f5;}
</style>
</head>
<body>
<div class="card">
  <div class="card-banner">
    <div class="srv-icon"><?= strtoupper(substr($server['name'],0,2)) ?></div>
  </div>
  <div class="card-body">
    <div class="invited-by">Kamu diundang bergabung ke server</div>
    <div class="srv-name"><?= htmlspecialchars($server['name']) ?></div>
    <div class="srv-stats">
      <div class="stat"><span class="dot dot-g"></span><?= $onlineCount ?> Online</div>
      <div class="stat"><span class="dot dot-grey"></span><?= $memberCount ?> Anggota</div>
    </div>
    <button class="join-btn" onclick="joinNow()">
      <?= $already ? 'Buka Server' : 'Terima Undangan' ?>
    </button>
    <a href="friends.php" class="cancel-link">Tidak, Terima Kasih</a>
  </div>
</div>
<script>
function joinNow(){ window.location.href='server.php?id=<?= $server['id'] ?>'; }
</script>
</body>
</html>

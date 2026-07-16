<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
updateOnlineStatus($pdo, $_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$me = getCurrentUser($pdo);
$friends = getFriends($pdo, $userId);
foreach ($friends as &$f) { $f['unread'] = getUnreadCount($pdo, $userId, $f['id']); }

// Handle actions
if (isset($_GET['action'])) {
    $tid = (int)($_GET['user_id'] ?? 0);
    switch ($_GET['action']) {
        case 'add':    try { $pdo->prepare("INSERT INTO friendships(requester_id,receiver_id)VALUES(?,?)")->execute([$userId,$tid]); } catch(Exception $e){} break;
        case 'accept': $pdo->prepare("UPDATE friendships SET status='accepted' WHERE requester_id=? AND receiver_id=?")->execute([$tid,$userId]); break;
        case 'reject': $pdo->prepare("UPDATE friendships SET status='rejected' WHERE requester_id=? AND receiver_id=?")->execute([$tid,$userId]); break;
        case 'remove': $pdo->prepare("DELETE FROM friendships WHERE (requester_id=? AND receiver_id=?) OR (requester_id=? AND receiver_id=?)")->execute([$userId,$tid,$tid,$userId]); break;
    }
    header('Location: friends.php?tab='.($_GET['tab']??'online')); exit;
}

$tab = $_GET['tab'] ?? 'online';
$addQuery = trim($_GET['q'] ?? '');
$addResult = null; $addMsg = '';

if ($tab === 'add' && $addQuery) {
    $s = $pdo->prepare("SELECT id,username,avatar,status FROM users WHERE (username=? OR email=?) AND id!=? LIMIT 1");
    $s->execute([$addQuery,$addQuery,$userId]);
    $addResult = $s->fetch();
    if ($addResult) { $addResult['fs'] = getFriendshipStatus($pdo,$userId,$addResult['id']); }
    else { $addMsg = "Tidak ada pengguna dengan username/email \"".htmlspecialchars($addQuery)."\""; }
}

// Pending requests
$pendingQ = $pdo->prepare("SELECT u.id,u.username,u.avatar,u.status,f.id AS fid FROM friendships f JOIN users u ON f.requester_id=u.id WHERE f.receiver_id=? AND f.status='pending'");
$pendingQ->execute([$userId]); $pending = $pendingQ->fetchAll();

// Pending outgoing
$outgoingQ = $pdo->prepare("SELECT u.id,u.username,u.avatar,u.status FROM friendships f JOIN users u ON f.receiver_id=u.id WHERE f.requester_id=? AND f.status='pending'");
$outgoingQ->execute([$userId]); $outgoing = $outgoingQ->fetchAll();

$online  = array_filter($friends, fn($f)=>$f['status']==='online');
$allF    = $friends;
$pendingCount = count($pending);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>KaiVC — Teman</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/layout.php'; ?>

<div id="main-wrap">
  <!-- Content Header (tabs) -->
  <div id="content-header">
    <div class="ch-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M7.5 6.5C7.5 8.981 9.519 11 12 11s4.5-2.019 4.5-4.5S14.481 2 12 2 7.5 4.019 7.5 6.5zM20 21h1v-1c0-3.859-3.141-7-7-7h-4c-3.86 0-7 3.141-7 7v1h17z"/></svg>
    </div>
    <span class="ch-name">Teman</span>
    <div class="ch-sep"></div>

    <div class="tabs">
      <a href="?tab=online"  class="tab <?= $tab==='online'?'active':'' ?>">Online</a>
      <a href="?tab=all"     class="tab <?= $tab==='all'?'active':'' ?>">Semua</a>
      <a href="?tab=pending" class="tab <?= $tab==='pending'?'active':'' ?>">
        Tertunda
        <?php if($pendingCount>0): ?>
          <span style="background:var(--badge);color:#fff;border-radius:100px;font-size:10px;font-weight:800;padding:0 6px;margin-left:4px;"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
      <a href="?tab=add" class="tab tab-add">Tambah Teman</a>
    </div>

    <div class="ch-actions">
      <button class="ch-btn" title="Cari">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      </button>
    </div>
  </div>

  <!-- Content body -->
  <div id="content-body">
    <div id="content-scroll">

      <?php if ($tab === 'add'): ?>
      <!-- ADD FRIEND TAB -->
      <div class="add-friend-wrap">
        <div class="af-title">Tambah Teman</div>
        <div class="af-desc">Kamu bisa menambahkan teman dengan username atau email mereka.</div>
        <form method="GET">
          <input type="hidden" name="tab" value="add">
          <div class="af-input-wrap">
            <input name="q" class="af-input" placeholder="Masukkan username atau email" value="<?= htmlspecialchars($addQuery) ?>">
            <button class="af-btn" type="submit">Kirim Permintaan</button>
          </div>
        </form>

        <?php if ($addMsg): ?>
          <div style="margin-top:16px;padding:12px 16px;background:rgba(242,63,67,.1);border:1px solid rgba(242,63,67,.2);border-radius:8px;color:#f87171;font-size:14px;">
            <?= $addMsg ?>
          </div>
        <?php endif; ?>

        <?php if ($addResult): $fs = $addResult['fs']; ?>
        <div style="margin-top:16px;" class="friend-row">
          <div class="av-wrap">
            <?php if(!empty($addResult['avatar'])): ?>
              <img src="<?= htmlspecialchars($addResult['avatar']) ?>" class="av" style="width:40px;height:40px;">
            <?php else: ?>
              <div class="av" style="width:40px;height:40px;font-size:16px;"><?= strtoupper(substr($addResult['username'],0,1)) ?></div>
            <?php endif; ?>
            <span class="av-dot dot-<?= $addResult['status'] ?>"></span>
          </div>
          <div class="fr-info">
            <div class="fr-name"><?= htmlspecialchars($addResult['username']) ?></div>
            <div class="fr-status"><?= $addResult['status'] === 'online' ? 'Online' : 'Offline' ?></div>
          </div>
          <div>
            <?php if (!$fs): ?>
              <a href="?action=add&user_id=<?= $addResult['id'] ?>&tab=add&q=<?= urlencode($addQuery) ?>" class="btn btn-primary">Kirim Permintaan</a>
            <?php elseif ($fs['status']==='accepted'): ?>
              <span style="color:var(--success);font-weight:700;font-size:13px;">✓ Sudah Berteman</span>
            <?php elseif ($fs['status']==='pending' && $fs['requester_id']==$userId): ?>
              <span style="color:var(--text3);font-size:13px;">Menunggu Persetujuan</span>
            <?php elseif ($fs['status']==='pending'): ?>
              <a href="?action=accept&user_id=<?= $addResult['id'] ?>&tab=add&q=<?= urlencode($addQuery) ?>" class="btn btn-primary">Terima</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php elseif ($tab === 'pending'): ?>
      <!-- PENDING TAB -->
      <?php if ($pending): ?>
      <div class="section-divider">Masuk — <?= count($pending) ?></div>
      <?php foreach ($pending as $r): ?>
      <div class="friend-row">
        <div class="av-wrap">
          <?php if(!empty($r['avatar'])): ?><img src="<?= htmlspecialchars($r['avatar']) ?>" class="av" style="width:40px;height:40px;"><?php else: ?><div class="av" style="width:40px;height:40px;font-size:16px;"><?= strtoupper(substr($r['username'],0,1)) ?></div><?php endif; ?>
          <span class="av-dot dot-<?= $r['status'] ?>"></span>
        </div>
        <div class="fr-info">
          <div class="fr-name"><?= htmlspecialchars($r['username']) ?></div>
          <div class="fr-status">Permintaan Teman Masuk</div>
        </div>
        <div class="fr-actions" style="display:flex;">
          <a href="?action=accept&user_id=<?= $r['id'] ?>&tab=pending" class="fr-btn btn-green" title="Terima">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          </a>
          <a href="?action=reject&user_id=<?= $r['id'] ?>&tab=pending" class="fr-btn" title="Tolak" style="color:var(--danger);">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($outgoing): ?>
      <div class="section-divider">Terkirim — <?= count($outgoing) ?></div>
      <?php foreach ($outgoing as $r): ?>
      <div class="friend-row">
        <div class="av-wrap">
          <?php if(!empty($r['avatar'])): ?><img src="<?= htmlspecialchars($r['avatar']) ?>" class="av" style="width:40px;height:40px;"><?php else: ?><div class="av" style="width:40px;height:40px;font-size:16px;"><?= strtoupper(substr($r['username'],0,1)) ?></div><?php endif; ?>
          <span class="av-dot dot-<?= $r['status'] ?>"></span>
        </div>
        <div class="fr-info">
          <div class="fr-name"><?= htmlspecialchars($r['username']) ?></div>
          <div class="fr-status">Permintaan Terkirim</div>
        </div>
        <div class="fr-actions" style="display:flex;">
          <a href="?action=remove&user_id=<?= $r['id'] ?>&tab=pending" class="fr-btn" title="Batalkan">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!$pending && !$outgoing): ?>
      <div class="empty-state">
        <div style="font-size:72px;">🤝</div>
        <div class="es-title">Tidak ada permintaan tertunda</div>
        <div>Belum ada yang ingin berteman denganmu?</div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ONLINE or ALL TAB -->
      <?php $list = ($tab==='online') ? $online : $allF; ?>
      <div class="content-search">
        <input type="text" id="fr-filter" placeholder="Cari" oninput="filterFriends(this.value)">
      </div>
      <div class="section-divider" id="fr-count"><?= $tab==='online'?'Online':'Semua Teman' ?> — <?= count($list) ?></div>
      <div id="fr-list">
        <?php foreach ($list as $f): ?>
        <div class="friend-row" data-name="<?= strtolower(htmlspecialchars($f['username'])) ?>">
          <div class="av-wrap">
            <?php if(!empty($f['avatar'])): ?><img src="<?= htmlspecialchars($f['avatar']) ?>" class="av" style="width:40px;height:40px;"><?php else: ?><div class="av" style="width:40px;height:40px;font-size:16px;"><?= strtoupper(substr($f['username'],0,1)) ?></div><?php endif; ?>
            <span class="av-dot dot-<?= $f['status'] ?>"></span>
          </div>
          <div class="fr-info">
            <div class="fr-name"><?= htmlspecialchars($f['username']) ?></div>
            <div class="fr-status"><?= $f['status']==='online'?'Online':($f['status']==='idle'?'Idle':($f['status']==='dnd'?'Jangan Ganggu':'Offline')) ?></div>
          </div>
          <div class="fr-actions">
            <a href="chat.php?id=<?= $f['id'] ?>" class="fr-btn" title="Kirim Pesan">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
            </a>
            <a href="call.php?id=<?= $f['id'] ?>&type=voice" class="fr-btn" title="Voice Call">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
            </a>
            <button class="fr-btn" onclick="showCtxMenu(event, <?= $f['id'] ?>)" title="Lainnya">
              <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
        <div class="empty-state">
          <div style="font-size:72px;"><?= $tab==='online' ? '🌑' : '👤' ?></div>
          <div class="es-title"><?= $tab==='online' ? 'Tidak ada teman yang online' : 'Belum ada teman' ?></div>
          <div>Tambah teman dengan klik tombol "<a href="?tab=add" style="color:var(--accent);">Tambah Teman</a>"</div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right Panel: Active Now -->
    <div id="right-panel">
      <div class="rp-title">Aktif Sekarang</div>
      <?php
      $activeNow = array_filter($friends, fn($f)=>$f['status']==='online');
      if ($activeNow): foreach($activeNow as $f): ?>
      <div class="rp-item">
        <div class="av-wrap">
          <?php if(!empty($f['avatar'])): ?><img src="<?= htmlspecialchars($f['avatar']) ?>" class="av" style="width:40px;height:40px;"><?php else: ?><div class="av" style="width:40px;height:40px;font-size:14px;"><?= strtoupper(substr($f['username'],0,1)) ?></div><?php endif; ?>
          <span class="av-dot dot-online"></span>
        </div>
        <div class="rp-info">
          <div class="rp-name"><?= htmlspecialchars($f['username']) ?></div>
          <div class="rp-sub">Online</div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div style="color:var(--text3);font-size:14px;">Belum ada teman yang online saat ini.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Context menu -->
<div id="ctx-menu" style="display:none;position:fixed;z-index:999;background:#111c30;border:1px solid rgba(74,158,255,.15);border-radius:8px;padding:6px;min-width:180px;box-shadow:0 8px 32px rgba(0,0,0,.5);">
  <div id="ctx-chat" onclick="ctxAction('chat')" style="padding:8px 12px;cursor:pointer;border-radius:4px;font-size:14px;" onmouseover="this.style.background='rgba(74,158,255,.1)'" onmouseout="this.style.background='none'">💬 Kirim Pesan</div>
  <div id="ctx-call" onclick="ctxAction('call')" style="padding:8px 12px;cursor:pointer;border-radius:4px;font-size:14px;" onmouseover="this.style.background='rgba(74,158,255,.1)'" onmouseout="this.style.background='none'">📞 Voice Call</div>
  <div style="height:1px;background:rgba(74,158,255,.1);margin:4px 0;"></div>
  <div id="ctx-remove" onclick="ctxAction('remove')" style="padding:8px 12px;cursor:pointer;border-radius:4px;font-size:14px;color:#f87171;" onmouseover="this.style.background='rgba(242,63,67,.1)'" onmouseout="this.style.background='none'">Hapus Teman</div>
</div>

<script>
let ctxUserId = 0;
function showCtxMenu(e, uid) {
  e.stopPropagation(); ctxUserId = uid;
  const m = document.getElementById('ctx-menu');
  m.style.display='block'; m.style.left=(e.clientX+4)+'px'; m.style.top=e.clientY+'px';
}
function ctxAction(a) {
  document.getElementById('ctx-menu').style.display='none';
  if (a==='chat')   window.location.href=`chat.php?id=${ctxUserId}`;
  if (a==='call')   window.location.href=`call.php?id=${ctxUserId}&type=voice`;
  if (a==='remove') window.location.href=`?action=remove&user_id=${ctxUserId}&tab=<?= $tab ?>`;
}
document.addEventListener('click', ()=>document.getElementById('ctx-menu').style.display='none');

function filterFriends(q) {
  const rows = document.querySelectorAll('#fr-list .friend-row');
  let vis = 0;
  rows.forEach(r => {
    const match = r.dataset.name.includes(q.toLowerCase());
    r.style.display = match ? '' : 'none';
    if (match) vis++;
  });
  const lbl = document.getElementById('fr-count');
  if (lbl) lbl.textContent = `<?= $tab==='online'?'Online':'Semua Teman' ?> — ${vis}`;
}
</script>
</body>
</html>

<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
requireLogin();
if (isset($_SESSION['user_id'])) updateOnlineStatus($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KaiVC</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --bg-0: #03080f;
  --bg-1: #060e1c;
  --bg-2: #0a1628;
  --bg-3: #0f2040;
  --bg-4: #142850;
  --accent: #4a9eff;
  --accent-dim: rgba(74,158,255,.15);
  --accent-glow: rgba(74,158,255,.3);
  --border: rgba(74,158,255,.12);
  --border-bright: rgba(74,158,255,.25);
  --text: #e2e8f0;
  --text-2: #7a9cc0;
  --text-3: #3a5470;
  --online: #22c55e;
  --away: #f59e0b;
  --busy: #ef4444;
  --offline: #3a5470;
  --danger: #ef4444;
  --success: #22c55e;
}
* { box-sizing:border-box; margin:0; padding:0; font-family:'Inter',sans-serif; }
body { background:var(--bg-0); color:var(--text); display:flex; height:100vh; overflow:hidden; }

/* Sidebar */
#sidebar {
  width:280px; min-width:280px; background:var(--bg-1);
  border-right:1px solid var(--border);
  display:flex; flex-direction:column; height:100vh;
}
#sidebar-header {
  padding:18px 20px 14px;
  border-bottom:1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between;
}
.logo { font-size:20px; font-weight:900; letter-spacing:-1px; }
.logo span { color:var(--accent); }
.search-box {
  margin:12px 14px;
  display:flex; align-items:center; gap:8px;
  background:var(--bg-2); border:1px solid var(--border);
  border-radius:10px; padding:9px 12px;
}
.search-box input { background:none; border:none; color:var(--text); font-size:13px; flex:1; outline:none; }
.search-box input::placeholder { color:var(--text-3); }
.section-label {
  padding:8px 18px 6px;
  font-size:10px; font-weight:700; color:var(--text-3);
  text-transform:uppercase; letter-spacing:1px;
}
.friend-item {
  display:flex; align-items:center; gap:11px;
  padding:9px 14px; cursor:pointer;
  border-radius:10px; margin:2px 8px;
  text-decoration:none; color:var(--text);
  transition:background .15s;
  position:relative;
}
.friend-item:hover, .friend-item.active { background:var(--bg-3); }
.friend-item.active { background:var(--accent-dim); border:1px solid var(--border-bright); }
.avatar-wrap { position:relative; flex-shrink:0; }
.avatar { width:38px; height:38px; border-radius:50%; object-fit:cover; background:var(--bg-3); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; color:var(--accent); border:2px solid var(--border); }
.status-dot { position:absolute; bottom:0; right:0; width:11px; height:11px; border-radius:50%; border:2px solid var(--bg-1); }
.dot-online { background:var(--online); }
.dot-away   { background:var(--away); }
.dot-busy   { background:var(--busy); }
.dot-offline{ background:var(--offline); }
.friend-info { flex:1; min-width:0; }
.friend-name { font-size:14px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.friend-sub  { font-size:11px; color:var(--text-3); }
.unread-badge { background:var(--danger); color:#fff; border-radius:100px; font-size:10px; font-weight:800; padding:2px 7px; min-width:18px; text-align:center; }
#sidebar-footer {
  margin-top:auto; padding:12px 14px;
  border-top:1px solid var(--border);
  background:var(--bg-2);
  display:flex; align-items:center; gap:10px;
}
#sidebar-footer .avatar { width:36px; height:36px; font-size:13px; }
.my-name { font-size:13px; font-weight:700; }
.my-sub  { font-size:11px; color:var(--text-3); }
.icon-btn {
  width:32px; height:32px; border-radius:8px; background:none; border:none;
  color:var(--text-2); cursor:pointer; display:flex; align-items:center; justify-content:center;
  transition:background .15s, color .15s;
}
.icon-btn:hover { background:var(--bg-3); color:var(--accent); }

/* Main */
#main { flex:1; display:flex; flex-direction:column; overflow:hidden; }
#main-header {
  padding:0 20px; height:56px; min-height:56px;
  border-bottom:1px solid var(--border);
  display:flex; align-items:center; gap:12px;
  background:var(--bg-1);
}
#main-content { flex:1; overflow-y:auto; padding:0; }
#main-content::-webkit-scrollbar { width:4px; }
#main-content::-webkit-scrollbar-thumb { background:var(--bg-4); border-radius:4px; }

/* Pending tab */
.pending-card {
  display:flex; align-items:center; gap:12px;
  padding:12px 16px; background:var(--bg-2);
  border:1px solid var(--border); border-radius:14px; margin-bottom:8px;
}
.btn {
  border:none; border-radius:100px; cursor:pointer;
  font-size:13px; font-weight:700; padding:8px 16px;
  transition:opacity .15s, transform .1s;
}
.btn:active { transform:scale(.96); }
.btn-primary { background:var(--accent); color:#000; }
.btn-danger  { background:rgba(239,68,68,.15); color:var(--danger); }
.btn-ghost   { background:var(--bg-3); color:var(--text-2); }
.btn:hover   { opacity:.85; }

/* Search result */
.user-card {
  display:flex; align-items:center; gap:12px;
  padding:12px 16px; background:var(--bg-2);
  border:1px solid var(--border); border-radius:14px; margin-bottom:8px;
}
.empty-state {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:12px; height:100%; color:var(--text-3); text-align:center; padding:40px;
  min-height:300px;
}
.empty-icon { font-size:48px; }
</style>
</head>
<body>
<div id="sidebar">
  <div id="sidebar-header">
    <div class="logo"><span>Kai</span>VC</div>
    <div style="display:flex;gap:4px;">
      <a href="friends.php" class="icon-btn" title="Teman" id="nav-friends">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
      </a>
      <a href="profile.php" class="icon-btn" title="Profil" id="nav-profile">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </a>
    </div>
  </div>

  <!-- Friend search -->
  <div class="search-box">
    <svg width="14" height="14" fill="none" stroke="var(--text-3)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" id="friend-search" placeholder="Cari teman...">
  </div>

  <div class="section-label" id="dm-label">Pesan Langsung</div>

  <div id="dm-list" style="flex:1;overflow-y:auto;padding-bottom:8px;">
    <!-- injected by JS -->
  </div>

  <div id="sidebar-footer">
    <?php $me = getCurrentUser($pdo); ?>
    <div class="avatar-wrap">
      <?php if (!empty($me['avatar'])): ?>
        <img src="<?= htmlspecialchars($me['avatar']) ?>" class="avatar" style="object-fit:cover;">
      <?php else: ?>
        <div class="avatar"><?= strtoupper(substr($me['username'],0,1)) ?></div>
      <?php endif; ?>
      <span class="status-dot dot-online"></span>
    </div>
    <div style="flex:1;min-width:0;">
      <div class="my-name"><?= htmlspecialchars($me['username']) ?></div>
      <div class="my-sub">Online</div>
    </div>
    <a href="logout.php" class="icon-btn" title="Keluar">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
    </a>
  </div>
</div>

<div id="main">
  <div id="main-header">
    <span id="header-icon" style="color:var(--text-3);">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </span>
    <span id="header-name" style="font-weight:700;font-size:15px;">KaiVC</span>
  </div>
  <div id="main-content">
    <div class="empty-state" id="welcome-state">
      <div class="empty-icon">👋</div>
      <div style="font-size:20px;font-weight:800;color:var(--text);">Selamat datang di KaiVC</div>
      <div style="font-size:14px;line-height:1.6;">Pilih teman untuk mulai ngobrol,<br>atau tambah teman baru.</div>
      <a href="friends.php" class="btn btn-primary" style="margin-top:8px;" id="find-friends-btn">+ Tambah Teman</a>
    </div>
  </div>
</div>

<script>
const ME = <?= $_SESSION['user_id'] ?>;

async function loadFriends() {
  const r = await fetch('api/friends.php?action=list');
  const data = await r.json();
  const list = document.getElementById('dm-list');
  const q = document.getElementById('friend-search').value.toLowerCase();
  const filtered = data.filter(f => f.username.toLowerCase().includes(q));

  if (!filtered.length) {
    list.innerHTML = '<div style="padding:16px;color:var(--text-3);font-size:13px;text-align:center;">Belum ada teman</div>';
    return;
  }

  list.innerHTML = filtered.map(f => `
    <a href="chat.php?id=${f.id}" class="friend-item ${location.href.includes('id='+f.id)?'active':''}" id="fi-${f.id}">
      <div class="avatar-wrap">
        ${f.avatar ? `<img src="${f.avatar}" class="avatar" style="object-fit:cover;">` : `<div class="avatar">${f.username[0].toUpperCase()}</div>`}
        <span class="status-dot dot-${f.status}"></span>
      </div>
      <div class="friend-info">
        <div class="friend-name">${f.username}</div>
        <div class="friend-sub">${f.status === 'online' ? '🟢 Online' : f.status === 'busy' ? '🔴 Sibuk' : f.status === 'away' ? '🟡 Away' : '⚫ Offline'}</div>
      </div>
      ${f.unread > 0 ? `<span class="unread-badge">${f.unread}</span>` : ''}
    </a>
  `).join('');
}

document.getElementById('friend-search').addEventListener('input', loadFriends);
loadFriends();
setInterval(loadFriends, 5000);
</script>
</body>
</html>

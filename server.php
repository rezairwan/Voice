<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
updateOnlineStatus($pdo, $_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$me = getCurrentUser($pdo);
$serverId = (int)($_GET['id'] ?? 0);

// Get server
$s = $pdo->prepare("SELECT * FROM servers WHERE id=?"); $s->execute([$serverId]); $server = $s->fetch();
if (!$server) { header('Location: friends.php'); exit; }

// Check membership
$s = $pdo->prepare("SELECT role FROM server_members WHERE server_id=? AND user_id=?"); $s->execute([$serverId,$userId]); $myRole = $s->fetchColumn();
if (!$myRole) { header('Location: friends.php'); exit; }

// Get channels
$s = $pdo->prepare("SELECT * FROM channels WHERE server_id=? ORDER BY position,type DESC,name ASC"); $s->execute([$serverId]); $channels = $s->fetchAll();
$textChannels  = array_filter($channels, fn($c)=>$c['type']==='text');
$voiceChannels = array_filter($channels, fn($c)=>$c['type']==='voice');

// Get members
$s = $pdo->prepare("SELECT u.id,u.username,u.avatar,u.status,sm.role FROM server_members sm JOIN users u ON sm.user_id=u.id WHERE sm.server_id=? ORDER BY sm.role ASC,u.username ASC"); $s->execute([$serverId]); $members = $s->fetchAll();

// Active channel
$channelId = (int)($_GET['ch'] ?? ($textChannels ? array_values($textChannels)[0]['id'] : 0));
$activeChannel = null;
foreach ($channels as $c) { if ($c['id']==$channelId) { $activeChannel=$c; break; } }

// Get my DM friends for layout
$friends = getFriends($pdo, $userId);
foreach ($friends as &$f) { $f['unread'] = getUnreadCount($pdo,$userId,$f['id']); }
// Get my servers
$s = $pdo->prepare("SELECT s.* FROM servers s JOIN server_members sm ON s.id=sm.server_id WHERE sm.user_id=? ORDER BY sm.joined_at ASC"); $s->execute([$userId]); $myServers = $s->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>KaiVC — <?= htmlspecialchars($server['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
/* Server-specific overrides */
#server-sidebar{width:240px;min-width:240px;background:var(--sidebar);display:flex;flex-direction:column;height:100vh;}
#server-header{height:48px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;font-weight:700;font-size:15px;border-bottom:1px solid var(--border);flex-shrink:0;cursor:pointer;}
#server-header:hover{background:var(--hover);}
.ch-section{padding:16px 8px 4px;font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;justify-content:space-between;}
.ch-section button{background:none;border:none;color:var(--text2);cursor:pointer;font-size:18px;padding:0 4px;border-radius:4px;}
.ch-section button:hover{background:var(--hover);color:var(--text);}
.ch-item{display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:4px;margin:1px 4px;cursor:pointer;color:var(--text2);text-decoration:none;font-size:15px;transition:background .1s,color .1s;}
.ch-item:hover{background:var(--hover);color:var(--text);}
.ch-item.active{background:var(--active);color:var(--text);}
.ch-icon{color:var(--text3);flex-shrink:0;}
.voice-users{padding:2px 8px 2px 32px;}
.voice-user{display:flex;align-items:center;gap:8px;padding:3px 4px;font-size:13px;color:var(--text3);}
.voice-user .tiny-av{width:20px;height:20px;border-radius:50%;background:var(--panel);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:var(--accent);}
/* Members panel */
#members-panel{width:240px;min-width:240px;background:var(--main);border-left:1px solid var(--border);overflow-y:auto;padding:16px 8px;}
#members-panel::-webkit-scrollbar{width:4px;}
#members-panel::-webkit-scrollbar-thumb{background:var(--rail);border-radius:4px;}
.mem-section{font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.8px;padding:8px 8px 6px;}
.mem-item{display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:4px;cursor:pointer;transition:background .1s;}
.mem-item:hover{background:var(--hover);}
.mem-name{font-size:14px;font-weight:500;color:var(--text2);}
.mem-role{font-size:10px;font-weight:700;padding:1px 6px;border-radius:100px;}
.role-owner{background:rgba(255,215,0,.15);color:#ffd700;}
.role-admin{background:rgba(74,158,255,.15);color:var(--accent);}
/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;align-items:center;justify-content:center;}
.modal-bg.show{display:flex;}
.modal{background:#1e1f22;border-radius:16px;padding:28px;width:420px;max-width:90vw;}
.modal h2{font-size:20px;font-weight:800;margin-bottom:6px;}
.modal p{color:var(--text3);font-size:14px;margin-bottom:20px;}
.modal input{width:100%;background:var(--rail);border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-size:15px;outline:none;margin-bottom:12px;}
.modal input:focus{border-color:var(--accent);}
.modal-btns{display:flex;gap:10px;justify-content:flex-end;}
.modal-tab{display:flex;gap:0;margin-bottom:20px;background:var(--rail);border-radius:8px;padding:4px;}
.modal-tab button{flex:1;padding:8px;border:none;background:none;color:var(--text2);cursor:pointer;border-radius:6px;font-weight:600;font-size:13px;}
.modal-tab button.active{background:var(--accent2);color:#fff;}
</style>
</head>
<body>

<!-- LEFT RAIL (with servers) -->
<div id="rail">
  <a href="/kai/friends.php" class="rail-logo" title="KaiVC" style="border-radius:50%;">K</a>
  <div class="rail-sep"></div>
  <!-- DM -->
  <a href="/kai/friends.php" class="rail-icon" title="Pesan Langsung">
    <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/><path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/></svg>
  </a>
  <div class="rail-sep"></div>
  <!-- Server icons -->
  <?php foreach($myServers as $srv): ?>
  <a href="/kai/server.php?id=<?= $srv['id'] ?>" class="rail-icon <?= $srv['id']==$serverId?'active':'' ?>" title="<?= htmlspecialchars($srv['name']) ?>" style="border-radius:<?= $srv['id']==$serverId?'16px':'50%' ?>;">
    <?php if(!empty($srv['icon'])): ?>
      <img src="<?= htmlspecialchars($srv['icon']) ?>" style="width:48px;height:48px;border-radius:inherit;object-fit:cover;">
    <?php else: ?>
      <span style="font-size:16px;font-weight:800;"><?= strtoupper(substr($srv['name'],0,2)) ?></span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <!-- Add Server -->
  <button class="rail-icon" onclick="openServerModal()" title="Tambah Server" style="color:var(--online);background:rgba(35,165,90,.1);">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
  </button>
</div>

<!-- SERVER SIDEBAR -->
<div id="server-sidebar">
  <div id="server-header" onclick="toggleSrvMenu()">
    <?= htmlspecialchars($server['name']) ?>
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
  </div>
  <!-- Server Dropdown Menu -->
  <div id="srv-menu" style="display:none;position:absolute;top:48px;left:0;width:238px;background:#111214;border:1px solid rgba(255,255,255,.08);border-radius:8px;z-index:100;padding:6px;box-shadow:0 8px 32px rgba(0,0,0,.5);">
    <button onclick="document.getElementById('invite-modal').classList.add('show');closeSrvMenu()" style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 10px;border:none;background:none;color:#f2f3f5;cursor:pointer;border-radius:4px;font-size:14px;font-weight:500;text-align:left;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
      Undang Orang
    </button>
    <?php if(in_array($myRole,['owner','admin'])): ?>
    <button onclick="document.getElementById('settings-modal').classList.add('show');closeSrvMenu()" style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 10px;border:none;background:none;color:#f2f3f5;cursor:pointer;border-radius:4px;font-size:14px;font-weight:500;text-align:left;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Pengaturan Server
    </button>
    <?php endif; ?>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:4px 0;"></div>
    <button onclick="leaveServer()" style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 10px;border:none;background:none;color:#f23f43;cursor:pointer;border-radius:4px;font-size:14px;font-weight:500;text-align:left;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
      <?= $myRole==='owner'?'Hapus Server':'Keluar dari Server' ?>
    </button>
  </div>

  <div style="flex:1;overflow-y:auto;padding-bottom:8px;">
    <!-- Text channels -->
    <div class="ch-section">
      <span>Saluran Teks</span>
      <?php if(in_array($myRole,['owner','admin'])): ?>
      <button onclick="addChannel('text')" title="Tambah saluran">+</button>
      <?php endif; ?>
    </div>
    <?php foreach($textChannels as $ch): ?>
    <a href="server.php?id=<?= $serverId ?>&ch=<?= $ch['id'] ?>" class="ch-item <?= $ch['id']==$channelId?'active':'' ?>">
      <span class="ch-icon">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
      </span>
      <?= htmlspecialchars($ch['name']) ?>
    </a>
    <?php endforeach; ?>

    <!-- Voice channels -->
    <div class="ch-section" style="margin-top:8px;">
      <span>Saluran Suara</span>
      <?php if(in_array($myRole,['owner','admin'])): ?>
      <button onclick="addChannel('voice')" title="Tambah saluran">+</button>
      <?php endif; ?>
    </div>
    <?php foreach($voiceChannels as $ch): ?>
    <div>
      <a href="voice_channel.php?server=<?= $serverId ?>&ch=<?= $ch['id'] ?>" class="ch-item">
        <span class="ch-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
        </span>
        <?= htmlspecialchars($ch['name']) ?>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- User panel -->
  <div id="user-panel">
    <div class="av-wrap">
      <div class="av" style="width:32px;height:32px;font-size:12px;"><?= strtoupper(substr($me['username'],0,1)) ?></div>
      <span class="av-dot dot-online" style="border-color:var(--rail);"></span>
    </div>
    <div style="flex:1;min-width:0;"><div class="up-name"><?= htmlspecialchars($me['username']) ?></div></div>
    <a href="logout.php" class="up-btn" title="Keluar">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
    </a>
  </div>
</div>

<!-- MAIN CONTENT -->
<div id="main-wrap">
  <?php if($activeChannel && $activeChannel['type']==='text'): ?>
  <div id="content-header">
    <div class="ch-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg></div>
    <span class="ch-name"><?= htmlspecialchars($activeChannel['name']) ?></span>
    <div class="ch-actions">
      <button class="ch-btn" onclick="document.getElementById('members-panel').style.display=document.getElementById('members-panel').style.display==='none'?'block':'none'">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
      </button>
      <?php if(in_array($myRole,['owner','admin'])): ?>
      <button class="ch-btn" onclick="copyInvite()" title="Salin kode undangan">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
      </button>
      <?php endif; ?>
    </div>
  </div>
  <div id="content-body">
    <div style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
      <div id="chat-messages"></div>
      <div id="chat-input-wrap">
        <div id="chat-input-box">
          <textarea id="msg-input" rows="1" placeholder="Pesan ke #<?= htmlspecialchars($activeChannel['name']) ?>"></textarea>
          <button class="input-icon-btn" onclick="sendMsg()">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>
          </button>
        </div>
      </div>
    </div>
    <div id="members-panel">
      <?php
      $owners  = array_filter($members, fn($m)=>$m['role']==='owner');
      $admins  = array_filter($members, fn($m)=>$m['role']==='admin');
      $regular = array_filter($members, fn($m)=>$m['role']==='member');
      foreach([['Pemilik',$owners],['Admin',$admins],['Anggota — '.count($regular),$regular]] as [$label,$list]):
        if(!$list) continue; ?>
      <div class="mem-section"><?= $label ?></div>
      <?php foreach($list as $m): ?>
      <div class="mem-item">
        <div class="av-wrap"><div class="av" style="width:32px;height:32px;font-size:12px;"><?= strtoupper(substr($m['username'],0,1)) ?></div><span class="av-dot dot-<?= $m['status'] ?>"></span></div>
        <div class="mem-name"><?= htmlspecialchars($m['username']) ?></div>
        <?php if($m['role']!=='member'): ?><span class="mem-role role-<?= $m['role'] ?>"><?= $m['role']==='owner'?'Pemilik':'Admin' ?></span><?php endif; ?>
      </div>
      <?php endforeach; endforeach; ?>
    </div>
  </div>

  <?php else: ?>
  <div id="content-header"><span class="ch-name"><?= htmlspecialchars($server['name']) ?></span></div>
  <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--text3);">
    <div style="text-align:center;">
      <div style="font-size:48px;font-weight:900;margin-bottom:8px;"><?= strtoupper(substr($server['name'],0,2)) ?></div>
      <div style="font-size:16px;font-weight:700;color:var(--text2);margin-bottom:4px;"><?= htmlspecialchars($server['name']) ?></div>
      <div>Pilih saluran untuk mulai</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ADD SERVER MODAL -->
<div class="modal-bg" id="srv-modal">
  <div class="modal">
    <h2>Server</h2>
    <p>Buat server baru atau gabung dengan kode undangan</p>
    <div class="modal-tab">
      <button class="active" id="tab-create" onclick="switchTab('create')">Buat Server</button>
      <button id="tab-join" onclick="switchTab('join')">Gabung</button>
    </div>
    <div id="form-create">
      <input id="srv-name" placeholder="Nama Server" maxlength="100">
      <div class="modal-btns">
        <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
        <button class="btn btn-primary" onclick="createServer()">Buat</button>
      </div>
    </div>
    <div id="form-join" style="display:none;">
      <input id="invite-code" placeholder="Kode Undangan">
      <div class="modal-btns">
        <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
        <button class="btn btn-primary" onclick="joinServer()">Gabung</button>
      </div>
    </div>
  </div>
</div>

<!-- INVITE MODAL -->
<div class="modal-bg" id="invite-modal">
  <div class="modal">
    <h2>Undang Orang</h2>
    <p>Bagikan kode ini kepada teman untuk bergabung ke server <strong><?= htmlspecialchars($server['name']) ?></strong></p>
    <div style="display:flex;gap:8px;align-items:center;background:#111214;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
      <code style="flex:1;font-size:16px;font-weight:700;letter-spacing:2px;color:#5865f2;"><?= $server['invite_code'] ?></code>
      <button onclick="copyCode()" style="padding:8px 16px;border:none;border-radius:6px;background:#5865f2;color:#fff;font-weight:700;cursor:pointer;font-size:13px;">Salin</button>
    </div>
    <div id="copy-toast" style="display:none;color:#23a55a;font-size:13px;margin-bottom:10px;">Kode undangan disalin!</div>
    <div style="display:flex;justify-content:flex-end;">
      <button class="btn btn-ghost" onclick="document.getElementById('invite-modal').classList.remove('show')">Tutup</button>
    </div>
  </div>
</div>

<!-- SERVER SETTINGS MODAL -->
<div class="modal-bg" id="settings-modal">
  <div class="modal" style="width:480px;">
    <h2>Pengaturan Server</h2>
    <p>Kelola server <strong><?= htmlspecialchars($server['name']) ?></strong></p>
    <label style="font-size:12px;font-weight:700;color:#949ba4;text-transform:uppercase;letter-spacing:.5px;">Nama Server</label>
    <input id="new-srv-name" value="<?= htmlspecialchars($server['name']) ?>" style="margin-top:6px;margin-bottom:16px;">
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="document.getElementById('settings-modal').classList.remove('show')">Batal</button>
      <button class="btn btn-primary" onclick="saveSettings()">Simpan</button>
    </div>
    <?php if($myRole==='owner'): ?>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06);">
      <div style="font-size:13px;font-weight:700;color:#f23f43;margin-bottom:8px;">Zona Bahaya</div>
      <button class="btn btn-danger" onclick="deleteServer()" style="border:1px solid rgba(242,63,67,.3);">Hapus Server</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ADD CHANNEL MODAL -->
<div class="modal-bg" id="ch-modal">
  <div class="modal">
    <h2 id="ch-modal-title">Tambah Saluran</h2>
    <input id="ch-name" placeholder="nama-saluran">
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="document.getElementById('ch-modal').classList.remove('show')">Batal</button>
      <button class="btn btn-primary" onclick="createChannel()">Buat</button>
    </div>
  </div>
</div>

<script>
const SERVER_ID = <?= $serverId ?>;
const CHANNEL_ID = <?= $channelId ?>;
const INVITE_CODE = '<?= $server['invite_code'] ?>';
let lastMsgId = 0, chType = 'text';

// Chat
const ta = document.getElementById('msg-input');
if(ta){
  ta.addEventListener('input',()=>{ta.style.height='auto';ta.style.height=Math.min(ta.scrollHeight,192)+'px';});
  ta.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMsg();}});
}

async function loadMessages(){
  if(!CHANNEL_ID) return;
  const r = await fetch(`api/channels.php?action=messages&channel_id=${CHANNEL_ID}&after=${lastMsgId}`);
  const d = await r.json();
  if(!d.messages?.length) return;
  const area = document.getElementById('chat-messages');
  d.messages.forEach(m=>{
    lastMsgId = Math.max(lastMsgId, m.id);
    const div = document.createElement('div');
    div.className='msg-group';
    div.innerHTML=`<div class="msg-group-av"><div class="av" style="width:40px;height:40px;font-size:15px;">${m.username[0].toUpperCase()}</div></div><div class="msg-body"><div class="msg-meta"><span class="msg-author">${esc(m.username)}</span><span class="msg-timestamp">${m.time}</span></div><div class="msg-content">${esc(m.content).replace(/\n/g,'<br>')}</div></div>`;
    area.appendChild(div);
  });
  area.scrollTop = area.scrollHeight;
}

async function sendMsg(){
  const content = ta?.value.trim();
  if(!content) return;
  ta.value=''; ta.style.height='auto';
  await fetch('api/channels.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'send',channel_id:CHANNEL_ID,content})});
  loadMessages();
}

function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function toggleSrvMenu(){
  const m=document.getElementById('srv-menu');
  m.style.display=m.style.display==='none'?'block':'none';
}
function closeSrvMenu(){ document.getElementById('srv-menu').style.display='none'; }
document.addEventListener('click',e=>{ if(!document.getElementById('server-header').contains(e.target)&&!document.getElementById('srv-menu').contains(e.target)) closeSrvMenu(); });
function copyCode(){
  navigator.clipboard.writeText('<?= $server['invite_code'] ?>').then(()=>{
    const t=document.getElementById('copy-toast'); t.style.display='block';
    setTimeout(()=>t.style.display='none',2000);
  });
}
async function saveSettings(){
  const name=document.getElementById('new-srv-name').value.trim();
  if(!name) return;
  const r=await fetch('api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'rename',server_id:SERVER_ID,name})});
  window.location.reload();
}
async function leaveServer(){
  if(!confirm('<?= $myRole==='owner'?'Hapus server ini? Semua data akan hilang!':'Keluar dari server ini?' ?>')) return;
  await fetch('api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'leave',server_id:SERVER_ID})});
  window.location.href='friends.php';
}
async function deleteServer(){
  if(!confirm('Hapus server ini? Semua data akan hilang!')) return;
  await fetch('api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'leave',server_id:SERVER_ID})});
  window.location.href='friends.php';
}
function openServerModal(){document.getElementById('invite-modal').classList.add('show');}
function closeModal(){['srv-modal','invite-modal','settings-modal'].forEach(id=>document.getElementById(id)?.classList.remove('show'));}
function switchTab(t){
  document.getElementById('form-create').style.display=t==='create'?'':'none';
  document.getElementById('form-join').style.display=t==='join'?'':'none';
  document.getElementById('tab-create').className=t==='create'?'active':'';
  document.getElementById('tab-join').className=t==='join'?'active':'';
}
async function createServer(){
  const name=document.getElementById('srv-name').value.trim();
  if(!name) return;
  const r=await fetch('api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'create',name})});
  const d=await r.json();
  if(d.server_id) window.location.href=`server.php?id=${d.server_id}`;
}
async function joinServer(){
  const code=document.getElementById('invite-code').value.trim();
  const r=await fetch('api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'join',invite_code:code})});
  const d=await r.json();
  if(d.server_id) window.location.href=`server.php?id=${d.server_id}`;
  else alert(d.error||'Gagal');
}
function addChannel(type){
  chType=type;
  document.getElementById('ch-modal-title').textContent='Tambah Saluran '+(type==='voice'?'Suara':'Teks');
  document.getElementById('ch-modal').classList.add('show');
}
async function createChannel(){
  const name=document.getElementById('ch-name').value.trim();
  if(!name) return;
  const r=await fetch('api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'create_channel',server_id:SERVER_ID,name,type:chType})});
  const d=await r.json();
  if(d.ok) window.location.reload();
}
function copyInvite(){ copyCode(); }
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();document.getElementById('ch-modal').classList.remove('show');closeSrvMenu();}});
['.modal-bg'].forEach(sel=>document.querySelectorAll(sel).forEach(el=>el.addEventListener('click',function(e){if(e.target===this)this.classList.remove('show')})));

if(CHANNEL_ID){loadMessages();setInterval(loadMessages,2000);}
</script>
</body>
</html>

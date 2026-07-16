<?php
// Shared layout - call at top of every page
// Expects: $pdo, $me (current user array), $friends (friend list), $activeUserId (optional)
$activeDmId = $activeUserId ?? 0;
$friends  = (isset($friends)  && is_array($friends))  ? $friends  : [];
$me       = (isset($me)       && is_array($me))       ? $me       : ['username'=>'User','avatar'=>'','status'=>'online'];
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/kai/assets/style.css">

<?php
// Load my servers for rail
$_myServers = [];
if(isset($pdo) && isset($me['id'])){
  $_s = $pdo->prepare("SELECT s.* FROM servers s JOIN server_members sm ON s.id=sm.server_id WHERE sm.user_id=? ORDER BY sm.joined_at ASC");
  $_s->execute([$me['id']]); $_myServers = $_s->fetchAll();
}
$_activeServerId = (int)($_GET['id'] ?? 0);
?>
<!-- LEFT RAIL -->
<div id="rail">
  <a href="/kai/friends.php" class="rail-logo" title="KaiVC">K</a>
  <div class="rail-sep"></div>
  <!-- DM icon -->
  <a href="/kai/friends.php" class="rail-icon <?= (basename($_SERVER['PHP_SELF'])==='friends.php')?'active':'' ?>" title="Pesan Langsung">
    <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/><path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/></svg>
  </a>
  <?php if($_myServers): ?>
  <div class="rail-sep"></div>
  <?php foreach($_myServers as $_srv): ?>
  <a href="/kai/server.php?id=<?= $_srv['id'] ?>" class="rail-icon <?= $_srv['id']==$_activeServerId?'active':'' ?>"
     title="<?= htmlspecialchars($_srv['name']) ?>"
     style="border-radius:<?= $_srv['id']==$_activeServerId?'16px':'50%' ?>;transition:border-radius .2s;">
    <span style="font-size:14px;font-weight:800;"><?= strtoupper(substr($_srv['name'],0,2)) ?></span>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>
  <div class="rail-sep"></div>
  <!-- Add Server -->
  <button onclick="document.getElementById('add-srv-modal').style.display='flex'" class="rail-icon" title="Buat / Gabung Server" style="color:#23a55a;background:rgba(35,165,90,.1);border:none;cursor:pointer;">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
  </button>
</div>

<!-- DM SIDEBAR -->
<div id="dm-sidebar">
  <div id="dm-search">
    <input type="text" id="dm-search-input" placeholder="Cari percakapan">
  </div>

  <div style="flex:1;overflow-y:auto;">
    <div class="dm-section-label">
      <span>Pesan Langsung</span>
      <a href="/kai/friends.php?tab=add" title="Buat DM">+</a>
    </div>

    <div id="dm-list">
      <?php foreach($friends as $f): if(!is_array($f)) continue; ?>
      <a href="/kai/chat.php?id=<?= $f['id'] ?>" class="dm-item <?= ($f['id']??0)==$activeDmId?'active':'' ?>">
        <div class="av-wrap">
          <?php if(!empty($f['avatar'])): ?>
            <img src="<?= htmlspecialchars($f['avatar']) ?>" class="av" style="width:32px;height:32px;object-fit:cover;">
          <?php else: ?>
            <div class="av"><?= strtoupper(substr($f['username']??'?',0,1)) ?></div>
          <?php endif; ?>
          <span class="av-dot dot-<?= $f['status'] ?? 'offline' ?>"></span>
        </div>
        <div class="dm-name"><?= htmlspecialchars($f['username'] ?? '') ?></div>
        <?php if(!empty($f['unread'])): ?>
          <span class="dm-badge"><?= $f['unread'] ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- User Panel -->
  <div id="user-panel">
    <div class="av-wrap" style="position:relative;">
      <?php if(!empty($me['avatar'])): ?>
        <img src="<?= htmlspecialchars($me['avatar']) ?>" class="user-panel-av">
      <?php else: ?>
        <div class="user-panel-av"><?= strtoupper(substr($me['username'],0,1)) ?></div>
      <?php endif; ?>
      <span class="av-dot dot-online" style="border-color:var(--rail);"></span>
    </div>
    <div style="flex:1;min-width:0;">
      <div class="up-name"><?= htmlspecialchars($me['username']) ?></div>
      <div class="up-status">Online</div>
    </div>
    <div class="up-icons">
      <a href="/kai/logout.php" class="up-btn" title="Keluar">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
      </a>
    </div>
  </div>
</div>
<!-- ADD SERVER MODAL -->
<div id="add-srv-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#1e1f22;border-radius:16px;padding:28px 28px 24px;width:440px;max-width:92vw;box-shadow:0 24px 64px rgba(0,0,0,.6);">
    <h2 style="font-size:20px;font-weight:900;margin-bottom:6px;">Server</h2>
    <p style="color:#96989d;font-size:14px;margin-bottom:20px;">Buat server baru atau bergabung dengan kode undangan.</p>
    <!-- Tabs -->
    <div style="display:flex;background:#111214;border-radius:8px;padding:3px;margin-bottom:20px;gap:2px;">
      <button id="srv-tab-create" onclick="srvTab('create')" style="flex:1;padding:8px;border:none;background:var(--accent,#3b82f6);color:#fff;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;">Buat Server</button>
      <button id="srv-tab-join" onclick="srvTab('join')" style="flex:1;padding:8px;border:none;background:none;color:#96989d;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;">Gabung</button>
    </div>
    <!-- Create form -->
    <div id="srv-form-create">
      <input id="srv-name-inp" placeholder="Nama Server" maxlength="100" style="width:100%;background:#111214;border:1.5px solid rgba(59,130,246,.2);border-radius:8px;padding:11px 14px;color:#f1f5f9;font-size:15px;outline:none;margin-bottom:16px;font-family:inherit;">
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="closeSrvModal()" style="padding:9px 20px;border-radius:8px;border:none;background:rgba(255,255,255,.07);color:#f1f5f9;font-weight:600;cursor:pointer;">Batal</button>
        <button onclick="createSrv()" style="padding:9px 22px;border-radius:8px;border:none;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;font-weight:700;cursor:pointer;">Buat</button>
      </div>
    </div>
    <!-- Join form -->
    <div id="srv-form-join" style="display:none;">
      <input id="srv-code-inp" placeholder="Masukkan kode undangan" style="width:100%;background:#111214;border:1.5px solid rgba(59,130,246,.2);border-radius:8px;padding:11px 14px;color:#f1f5f9;font-size:15px;outline:none;margin-bottom:16px;font-family:inherit;">
      <div id="srv-join-err" style="color:#f87171;font-size:13px;margin-bottom:10px;display:none;"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="closeSrvModal()" style="padding:9px 20px;border-radius:8px;border:none;background:rgba(255,255,255,.07);color:#f1f5f9;font-weight:600;cursor:pointer;">Batal</button>
        <button onclick="joinSrv()" style="padding:9px 22px;border-radius:8px;border:none;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;font-weight:700;cursor:pointer;">Gabung</button>
      </div>
    </div>
  </div>
</div>
<script>
function closeSrvModal(){ document.getElementById('add-srv-modal').style.display='none'; }
function srvTab(t){
  document.getElementById('srv-form-create').style.display=t==='create'?'':'none';
  document.getElementById('srv-form-join').style.display=t==='join'?'':'none';
  document.getElementById('srv-tab-create').style.background=t==='create'?'var(--accent,#3b82f6)':'none';
  document.getElementById('srv-tab-create').style.color=t==='create'?'#fff':'#96989d';
  document.getElementById('srv-tab-join').style.background=t==='join'?'var(--accent,#3b82f6)':'none';
  document.getElementById('srv-tab-join').style.color=t==='join'?'#fff':'#96989d';
}
async function createSrv(){
  const name=document.getElementById('srv-name-inp').value.trim();
  if(!name) return;
  const r=await fetch('/kai/api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'create',name})});
  const d=await r.json();
  if(d.server_id) window.location.href='/kai/server.php?id='+d.server_id;
  else alert(d.error||'Gagal membuat server');
}
async function joinSrv(){
  const code=document.getElementById('srv-code-inp').value.trim();
  if(!code) return;
  const r=await fetch('/kai/api/servers.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'join',invite_code:code})});
  const d=await r.json();
  if(d.server_id) window.location.href='/kai/server.php?id='+d.server_id;
  else { const el=document.getElementById('srv-join-err'); el.style.display='block'; el.textContent=d.error||'Kode tidak valid'; }
}
document.getElementById('add-srv-modal').addEventListener('click',function(e){if(e.target===this)closeSrvModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSrvModal();});
</script>

<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
updateOnlineStatus($pdo, $_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$me = getCurrentUser($pdo);
$friendId = (int)($_GET['id'] ?? 0);
if (!$friendId) { header('Location: friends.php'); exit; }
$s = $pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$friendId]); $friend = $s->fetch();
if (!$friend) { header('Location: friends.php'); exit; }
$fs = getFriendshipStatus($pdo, $userId, $friendId);
if (!$fs || $fs['status']!=='accepted') { header('Location: friends.php'); exit; }
$pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0")->execute([$friendId,$userId]);
$friends = getFriends($pdo, $userId);
foreach ($friends as &$f) { $f['unread'] = getUnreadCount($pdo, $userId, $f['id']); }
$activeUserId = $friendId;
// Load initial messages
$msgs = $pdo->prepare("SELECT m.*,u.username,u.avatar FROM messages m JOIN users u ON m.sender_id=u.id WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)) ORDER BY m.created_at ASC LIMIT 100");
$msgs->execute([$userId,$friendId,$friendId,$userId]); $messages = $msgs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>KaiVC — @<?= htmlspecialchars($friend['username']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/layout.php'; ?>

<div id="main-wrap">
  <!-- Chat Header -->
  <div id="content-header">
    <div class="ch-icon">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 7.5h-9m9 4.5h-9m9 4.5H12m3 4.5a3 3 0 006 0V6a3 3 0 00-3-3H6a3 3 0 00-3 3v14.25a3 3 0 003-3h12z"/></svg>
    </div>
    <div class="av-wrap" style="margin-right:2px;">
      <?php if(!empty($friend['avatar'])): ?><img src="<?= htmlspecialchars($friend['avatar']) ?>" class="av" style="width:24px;height:24px;font-size:10px;"><?php else: ?><div class="av" style="width:24px;height:24px;font-size:10px;"><?= strtoupper(substr($friend['username'],0,1)) ?></div><?php endif; ?>
      <span class="av-dot dot-<?= $friend['status'] ?>" style="width:8px;height:8px;border-color:var(--main);"></span>
    </div>
    <span class="ch-name"><?= htmlspecialchars($friend['username']) ?></span>
    <div class="ch-actions">
      <!-- Voice call -->
      <a href="call.php?id=<?= $friendId ?>&type=voice" class="ch-btn" title="Voice Call">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
      </a>
      <!-- Video call -->
      <a href="call.php?id=<?= $friendId ?>&type=video" class="ch-btn" title="Video Call">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
      </a>
      <!-- Search -->
      <button class="ch-btn" title="Cari pesan" onclick="document.getElementById('msg-search').focus()">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      </button>
      <!-- Profile -->
      <button class="ch-btn" title="Profil" id="toggle-profile-btn" onclick="toggleProfile()">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
      </button>
    </div>
  </div>

  <div id="content-body">
    <!-- MESSAGES -->
    <div style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
      <div id="chat-messages">
        <!-- Channel intro -->
        <div style="padding:16px 16px 24px;border-bottom:1px solid var(--border);margin-bottom:16px;">
          <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--accent));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:#fff;margin-bottom:16px;">
            <?= strtoupper(substr($friend['username'],0,1)) ?>
          </div>
          <div style="font-size:22px;font-weight:800;margin-bottom:4px;">@<?= htmlspecialchars($friend['username']) ?></div>
          <div style="color:var(--text3);font-size:14px;">Ini adalah awal percakapanmu dengan <strong><?= htmlspecialchars($friend['username']) ?></strong>.</div>
        </div>

        <?php
        $prevSenderId = null; $prevDate = null;
        foreach ($messages as $i => $m):
          $msgDate = date('Y-m-d', strtotime($m['created_at']));
          $today = date('Y-m-d'); $yesterday = date('Y-m-d', strtotime('-1 day'));
          $isNewDate = ($msgDate !== $prevDate);
          $isGrouped = ($prevSenderId === $m['sender_id'] && !$isNewDate);
          if ($isNewDate):
        ?>
          <div style="display:flex;align-items:center;gap:12px;padding:8px 16px;margin:8px 0;">
            <div style="flex:1;height:1px;background:var(--border);"></div>
            <span style="font-size:11px;font-weight:700;color:var(--text3);white-space:nowrap;">
              <?= $msgDate===$today ? 'Hari Ini' : ($msgDate===$yesterday ? 'Kemarin' : date('d F Y', strtotime($m['created_at']))) ?>
            </span>
            <div style="flex:1;height:1px;background:var(--border);"></div>
          </div>
        <?php endif; if ($isGrouped): ?>
          <div class="msg-continued" data-id="<?= $m['id'] ?>" data-mine="<?= $m['sender_id']==$userId?'1':'0' ?>" style="padding:1px 16px 1px 72px;position:relative;" onmouseenter="showMsgActions(this)" onmouseleave="hideMsgActions(this)">
            <span class="msg-side-time" style="position:absolute;left:18px;top:3px;font-size:10px;color:var(--text3);display:none;"><?= date('H:i', strtotime($m['created_at'])) ?></span>
            <div class="msg-content" id="mc-<?= $m['id'] ?>"><?= nl2br(htmlspecialchars($m['content'])) ?><?php if(!empty($m['is_edited'])): ?> <span class="edited-tag">(diedit)</span><?php endif; ?></div>
            <?php if($m['sender_id']==$userId): ?><div class="msg-actions" style="display:none;"><button onclick="startEdit(<?= $m['id'] ?>)">✏️ Edit</button><button onclick="deleteMsg(<?= $m['id'] ?>)">🗑️ Hapus</button></div><?php endif; ?>
          </div>
        <?php else: ?>
          <div class="msg-group" id="msg-<?= $m['id'] ?>" data-id="<?= $m['id'] ?>" data-mine="<?= $m['sender_id']==$userId?'1':'0' ?>" onmouseenter="showMsgActions(this)" onmouseleave="hideMsgActions(this)">
            <div class="msg-group-av">
              <?php if(!empty($m['avatar'])): ?><img src="<?= htmlspecialchars($m['avatar']) ?>" class="av" style="width:40px;height:40px;"><?php else: ?><div class="av" style="width:40px;height:40px;font-size:15px;"><?= strtoupper(substr($m['username'],0,1)) ?></div><?php endif; ?>
            </div>
            <div class="msg-body">
              <div class="msg-meta">
                <span class="msg-author" style="color:<?= $m['sender_id']==$userId ? 'var(--accent)' : 'var(--text)' ?>"><?= htmlspecialchars($m['username']) ?></span>
                <span class="msg-timestamp"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></span>
              </div>
              <div class="msg-content" id="mc-<?= $m['id'] ?>"><?= nl2br(htmlspecialchars($m['content'])) ?><?php if(!empty($m['is_edited'])): ?> <span class="edited-tag">(diedit)</span><?php endif; ?></div>
              <?php if($m['sender_id']==$userId): ?><div class="msg-actions" style="display:none;"><button onclick="startEdit(<?= $m['id'] ?>)">✏️ Edit</button><button onclick="deleteMsg(<?= $m['id'] ?>)">🗑️ Hapus</button></div><?php endif; ?>
            </div>
          </div>
        <?php endif; $prevSenderId = $m['sender_id']; $prevDate = $msgDate; endforeach; ?>
      </div>

      <!-- Input box -->
      <div id="chat-input-wrap">
        <div id="chat-input-box">
          <button class="input-icon-btn" title="Kirim File" style="font-size:20px;">+</button>
          <textarea id="msg-input" rows="1" placeholder="Pesan ke @<?= htmlspecialchars($friend['username']) ?>"></textarea>
          <button class="input-icon-btn" title="Emoji">😊</button>
          <button class="input-icon-btn" onclick="sendMessage()" title="Kirim" id="send-btn">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Profile Panel (hidden by default) -->
    <div id="profile-panel" style="display:none;width:340px;min-width:340px;background:var(--rail);border-left:1px solid var(--border);overflow-y:auto;flex-shrink:0;">
      <div style="background:linear-gradient(135deg,var(--accent2),var(--accent));height:80px;"></div>
      <div style="padding:0 16px 16px;position:relative;margin-top:-30px;">
        <div style="width:72px;height:72px;border-radius:50%;border:4px solid var(--rail);background:var(--panel);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:var(--accent);overflow:hidden;">
          <?php if(!empty($friend['avatar'])): ?><img src="<?= htmlspecialchars($friend['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: echo strtoupper(substr($friend['username'],0,1)); endif; ?>
        </div>
        <div style="margin-top:12px;background:var(--sidebar);border-radius:8px;padding:16px;border:1px solid var(--border);">
          <div style="font-weight:800;font-size:16px;"><?= htmlspecialchars($friend['username']) ?></div>
          <div style="font-size:12px;color:var(--text3);margin-bottom:12px;"><?= htmlspecialchars($friend['username']) ?></div>
          <div style="border-top:1px solid var(--border);padding-top:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">Status</div>
            <div style="font-size:14px;display:flex;align-items:center;gap:6px;">
              <span class="av-dot dot-<?= $friend['status'] ?>" style="position:static;border:none;width:10px;height:10px;"></span>
              <?= $friend['status']==='online'?'Online':'Offline' ?>
            </div>
          </div>
          <?php if(!empty($friend['bio'])): ?>
          <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">Tentang Saya</div>
            <div style="font-size:14px;line-height:1.5;"><?= htmlspecialchars($friend['bio']) ?></div>
          </div>
          <?php endif; ?>
          <div style="margin-top:12px;display:flex;gap:8px;">
            <a href="call.php?id=<?= $friendId ?>&type=voice" class="btn btn-primary" style="flex:1;text-align:center;text-decoration:none;padding:8px;">📞 Call</a>
            <a href="call.php?id=<?= $friendId ?>&type=video" class="btn" style="flex:1;background:var(--panel);color:var(--text);text-align:center;text-decoration:none;padding:8px;">📹 Video</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Message Modal -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.65);display:none;align-items:center;justify-content:center;">
  <div style="background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:24px;width:480px;max-width:95vw;box-shadow:0 24px 64px rgba(0,0,0,.6);">
    <div style="font-weight:700;font-size:16px;margin-bottom:14px;">✏️ Edit Pesan</div>
    <textarea id="edit-input" rows="4" style="width:100%;background:var(--sidebar);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:10px 12px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box;"></textarea>
    <div style="display:flex;gap:8px;margin-top:14px;justify-content:flex-end;">
      <button onclick="closeEdit()" class="btn" style="background:var(--sidebar);color:var(--text);">Batal</button>
      <button onclick="submitEdit()" class="btn btn-primary" id="edit-save-btn">Simpan</button>
    </div>
  </div>
</div>

<!-- Incoming call toast -->
<div id="call-toast" style="display:none;position:fixed;bottom:24px;right:24px;z-index:999;background:#111c30;border:1.5px solid rgba(35,165,90,.4);border-radius:12px;padding:16px 20px;box-shadow:0 8px 32px rgba(0,0,0,.6);min-width:280px;">
  <div style="font-weight:700;font-size:15px;margin-bottom:2px;">📞 Panggilan Masuk</div>
  <div id="call-toast-from" style="color:var(--text3);font-size:13px;margin-bottom:14px;"></div>
  <div style="display:flex;gap:8px;">
    <button onclick="rejectCall()" class="btn btn-danger" style="flex:1;border:1px solid rgba(242,63,67,.3);">Tolak</button>
    <button onclick="acceptCall()" class="btn btn-green" style="flex:1;">Terima</button>
  </div>
</div>

<script>
const FRIEND_ID = <?= $friendId ?>;
const ME = <?= $userId ?>;
let lastMsgId = <?= !empty($messages) ? max(array_column($messages, 'id')) : 0 ?>;
let incomingCall = null;

// Textarea auto-resize + enter to send
const ta = document.getElementById('msg-input');
ta.addEventListener('input', ()=>{ ta.style.height='auto'; ta.style.height=Math.min(ta.scrollHeight,192)+'px'; });
ta.addEventListener('keydown', e=>{ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage();} });

// Scroll to bottom on load
const msgs = document.getElementById('chat-messages');
msgs.scrollTop = msgs.scrollHeight;

let prevSender = <?= !empty($messages) ? end($messages)['sender_id'] : 'null' ?>;
let prevDate = '<?= !empty($messages) ? date('Y-m-d', strtotime(end($messages)['created_at'])) : '' ?>';

async function loadNewMessages() {
  const r = await fetch(`api/messages.php?with=${FRIEND_ID}&after=${lastMsgId}`);
  const d = await r.json();
  if (!d.messages?.length) return;
  d.messages.forEach(appendMsg);
  msgs.scrollTop = msgs.scrollHeight;
}

function appendMsg(m) {
  lastMsgId = Math.max(lastMsgId, m.id);
  const msgDate = m.created_at.substring(0,10);
  const today = new Date().toISOString().substring(0,10);
  const isGrouped = (prevSender == m.sender_id) && (msgDate == prevDate);

  if (msgDate !== prevDate) {
    const sep = document.createElement('div');
    sep.style.cssText='display:flex;align-items:center;gap:12px;padding:8px 16px;margin:8px 0;';
    sep.innerHTML=`<div style="flex:1;height:1px;background:var(--border);"></div><span style="font-size:11px;font-weight:700;color:var(--text3);">${msgDate===today?'Hari Ini':'Kemarin'}</span><div style="flex:1;height:1px;background:var(--border);"></div>`;
    msgs.appendChild(sep);
  }

  const initials = m.username[0].toUpperCase();
  const isMe = m.sender_id == ME;
  const actBtns = isMe ? `<div class="msg-actions" style="display:none;"><button onclick="startEdit(${m.id})">✏️ Edit</button><button onclick="deleteMsg(${m.id})">🗑️ Hapus</button></div>` : '';

  if (isGrouped) {
    const div = document.createElement('div');
    div.className='msg-continued';
    div.dataset.id = m.id;
    div.dataset.mine = isMe ? '1' : '0';
    div.style.cssText='padding:1px 16px 1px 72px;position:relative;';
    div.addEventListener('mouseenter', ()=>showMsgActions(div));
    div.addEventListener('mouseleave', ()=>hideMsgActions(div));
    div.innerHTML = `<div class="msg-content" id="mc-${m.id}">${esc(m.content).replace(/\n/g,'<br>')}</div>${actBtns}`;
    msgs.appendChild(div);
  } else {
    const div = document.createElement('div');
    div.className='msg-group';
    div.id='msg-'+m.id;
    div.dataset.id = m.id;
    div.dataset.mine = isMe ? '1' : '0';
    div.addEventListener('mouseenter', ()=>showMsgActions(div));
    div.addEventListener('mouseleave', ()=>hideMsgActions(div));
    div.innerHTML=`
      <div class="msg-group-av"><div class="av" style="width:40px;height:40px;font-size:15px;${isMe?'background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff;':''}">${initials}</div></div>
      <div class="msg-body">
        <div class="msg-meta">
          <span class="msg-author" style="color:${isMe?'var(--accent)':'var(--text)'}">${esc(m.username)}</span>
          <span class="msg-timestamp">${m.time||''}</span>
        </div>
        <div class="msg-content" id="mc-${m.id}">${esc(m.content).replace(/\n/g,'<br>')}</div>
        ${actBtns}
      </div>`;
    msgs.appendChild(div);
  }
  prevSender = m.sender_id; prevDate = msgDate;
}

async function sendMessage() {
  const content = ta.value.trim();
  if (!content) return;
  ta.value=''; ta.style.height='auto';
  await fetch('api/send_message.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({receiver_id:FRIEND_ID,content})});
  loadNewMessages();
}

function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── CRUD helpers ──────────────────────────────────────────────
function showMsgActions(el) {
  if (el.dataset.mine !== '1') return;
  const a = el.querySelector('.msg-actions');
  if (a) { a.style.display = 'flex'; }
  const t = el.querySelector('.msg-side-time');
  if (t) { t.style.display = 'block'; }
}
function hideMsgActions(el) {
  const a = el.querySelector('.msg-actions');
  if (a) { a.style.display = 'none'; }
  const t = el.querySelector('.msg-side-time');
  if (t) { t.style.display = 'none'; }
}

let editingId = null;
function startEdit(id) {
  editingId = id;
  const el = document.getElementById('mc-' + id);
  // strip HTML tags to get plain text
  const plain = el.innerText.replace(/ \(diedit\)$/, '').trimEnd();
  document.getElementById('edit-input').value = plain;
  const modal = document.getElementById('edit-modal');
  modal.style.display = 'flex';
  document.getElementById('edit-input').focus();
}
function closeEdit() {
  document.getElementById('edit-modal').style.display = 'none';
  editingId = null;
}
async function submitEdit() {
  const content = document.getElementById('edit-input').value.trim();
  if (!content || !editingId) return;
  document.getElementById('edit-save-btn').disabled = true;
  const r = await fetch('api/edit_message.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:editingId,content})});
  const d = await r.json();
  if (d.ok) {
    const el = document.getElementById('mc-' + editingId);
    if (el) el.innerHTML = esc(content).replace(/\n/g,'<br>') + ' <span class="edited-tag">(diedit)</span>';
    closeEdit();
  } else { alert('Gagal mengedit pesan.'); }
  document.getElementById('edit-save-btn').disabled = false;
}

async function deleteMsg(id) {
  if (!confirm('Hapus pesan ini?')) return;
  const r = await fetch('api/delete_message.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
  const d = await r.json();
  if (d.ok) {
    // Remove the element from DOM
    const el = document.getElementById('msg-' + id) ||
                document.querySelector(`[data-id="${id}"]`);
    if (el) el.remove();
  } else { alert('Gagal menghapus pesan.'); }
}

// Close modal when clicking backdrop
document.getElementById('edit-modal').addEventListener('click', function(e){
  if(e.target === this) closeEdit();
});
// Ctrl+Enter to save edit
document.getElementById('edit-input').addEventListener('keydown', e=>{
  if(e.key==='Enter' && e.ctrlKey){ e.preventDefault(); submitEdit(); }
});

// Profile toggle
function toggleProfile(){
  const p=document.getElementById('profile-panel');
  p.style.display=p.style.display==='none'?'flex':'none';
}

// Incoming call check
async function checkCalls(){
  const r=await fetch('api/call_signal.php?action=check');
  const d=await r.json();
  if(d.call){
    incomingCall=d.call;
    document.getElementById('call-toast-from').textContent=d.call.caller_username+' menelepon...';
    document.getElementById('call-toast').style.display='block';
  } else if(!incomingCall){
    document.getElementById('call-toast').style.display='none';
  }
}
function acceptCall(){ if(!incomingCall)return; window.location.href=`call.php?id=${incomingCall.caller_id}&signal_id=${incomingCall.id}&peer=${incomingCall.caller_peer_id}&type=voice`; }
async function rejectCall(){ if(!incomingCall)return; await fetch(`api/call_signal.php?action=reject&id=${incomingCall.id}`); incomingCall=null; document.getElementById('call-toast').style.display='none'; }

setInterval(loadNewMessages, 2000);
setInterval(checkCalls, 3000);
</script>
</body>
</html>

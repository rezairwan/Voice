<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
updateOnlineStatus($pdo, $_SESSION['user_id']);
$userId    = $_SESSION['user_id'];
$me        = getCurrentUser($pdo);
$serverId  = (int)($_GET['server'] ?? 0);
$channelId = (int)($_GET['ch'] ?? 0);

$s = $pdo->prepare("SELECT * FROM channels WHERE id=? AND server_id=? AND type='voice'");
$s->execute([$channelId,$serverId]); $channel = $s->fetch();
if (!$channel) { header('Location: server.php?id='.$serverId); exit; }

$s = $pdo->prepare("SELECT * FROM servers WHERE id=?"); $s->execute([$serverId]); $server = $s->fetch();
$s = $pdo->prepare("SELECT * FROM channels WHERE server_id=? ORDER BY type DESC,position,name"); $s->execute([$serverId]); $channels = $s->fetchAll();
$textChs  = array_filter($channels, fn($c)=>$c['type']==='text');
$voiceChs = array_filter($channels, fn($c)=>$c['type']==='voice');
$s = $pdo->prepare("SELECT role FROM server_members WHERE server_id=? AND user_id=?"); $s->execute([$serverId,$userId]); $myRole = $s->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>KaiVC — <?= htmlspecialchars($channel['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
<style>
body{overflow:hidden;}
/* Override main for voice */
#voice-main{flex:1;display:flex;flex-direction:column;background:#111214;overflow:hidden;}
#vc-topbar{height:48px;background:#1e1f22;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;padding:0 16px;gap:10px;flex-shrink:0;}
.vc-pill{font-size:11px;font-weight:700;background:rgba(35,165,90,.15);border:1px solid rgba(35,165,90,.3);color:#3ba55c;padding:2px 10px;border-radius:100px;letter-spacing:.4px;}
#vc-grid{flex:1;display:grid;gap:8px;padding:16px;overflow:hidden;align-content:center;justify-content:center;}
/* Tiles */
.vc-tile{border-radius:12px;background:#1e1f22;border:2px solid transparent;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;position:relative;overflow:hidden;transition:border-color .2s;}
.vc-tile.speaking{border-color:#3ba55c;}
.vc-tile video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
.vc-tile-av{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:900;color:#fff;z-index:1;flex-shrink:0;}
.vc-tile-label{position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);border-radius:5px;padding:3px 10px;font-size:12px;font-weight:700;z-index:2;}
.vc-tile-muted{position:absolute;bottom:10px;right:10px;background:rgba(218,55,60,.85);border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;z-index:2;}
/* Controls */
#vc-controls{height:72px;background:#292b2f;border-top:1px solid rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;gap:6px;flex-shrink:0;padding:0 20px;}
.vbtn{height:44px;min-width:44px;border-radius:6px;border:none;background:rgba(255,255,255,.07);color:#dbdee1;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:13px;font-weight:600;padding:0 14px;transition:background .12s,color .12s;}
.vbtn:hover{background:rgba(255,255,255,.13);color:#fff;}
.vbtn.off{background:rgba(218,55,60,.18);color:#da373c;}
.vbtn.leave{background:#da373c;color:#fff;}
.vbtn.leave:hover{background:#a12828;}
.vsep{width:1px;height:32px;background:rgba(255,255,255,.1);margin:0 4px;}
/* Sidebar */
#srv-side{width:240px;min-width:240px;background:#2b2d31;display:flex;flex-direction:column;height:100vh;}
#srv-top{height:48px;display:flex;align-items:center;padding:0 16px;font-weight:700;font-size:15px;border-bottom:1px solid rgba(255,255,255,.06);}
.sch{padding:14px 8px 4px;font-size:11px;font-weight:700;color:#949ba4;text-transform:uppercase;letter-spacing:.8px;}
.sci{display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:4px;margin:1px 4px;cursor:pointer;color:#949ba4;text-decoration:none;font-size:15px;transition:background .1s,color .1s;}
.sci:hover{background:rgba(255,255,255,.06);color:#f2f3f5;}
.sci.act{background:rgba(255,255,255,.1);color:#f2f3f5;}
</style>
</head>
<body>
<!-- Rail -->
<div id="rail">
  <a href="/kai/friends.php" class="rail-logo" title="KaiVC">K</a>
  <div class="rail-sep"></div>
  <a href="/kai/friends.php" class="rail-icon" title="DM"><svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/><path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/></svg></a>
  <div class="rail-sep"></div>
  <a href="/kai/server.php?id=<?= $serverId ?>" class="rail-icon active" title="<?= htmlspecialchars($server['name']) ?>" style="border-radius:16px;font-size:14px;font-weight:800;"><?= strtoupper(substr($server['name'],0,2)) ?></a>
</div>

<!-- Server Sidebar -->
<div id="srv-side">
  <div id="srv-top"><?= htmlspecialchars($server['name']) ?></div>
  <div style="flex:1;overflow-y:auto;padding-bottom:8px;">
    <div class="sch">Saluran Teks</div>
    <?php foreach($textChs as $ch): ?>
    <a href="server.php?id=<?= $serverId ?>&ch=<?= $ch['id'] ?>" class="sci">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
      <?= htmlspecialchars($ch['name']) ?>
    </a>
    <?php endforeach; ?>
    <div class="sch" style="margin-top:8px;">Saluran Suara</div>
    <?php foreach($voiceChs as $ch): ?>
    <a href="voice_channel.php?server=<?= $serverId ?>&ch=<?= $ch['id'] ?>" class="sci <?= $ch['id']==$channelId?'act':'' ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
      <?= htmlspecialchars($ch['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div id="user-panel" style="background:#232428;">
    <div class="av-wrap"><div class="av" style="width:32px;height:32px;font-size:12px;background:#5865f2;color:#fff;"><?= strtoupper(substr($me['username'],0,1)) ?></div><span class="av-dot dot-online" style="border-color:#232428;"></span></div>
    <div style="flex:1;min-width:0;"><div class="up-name"><?= htmlspecialchars($me['username']) ?></div><div class="up-status">In Voice</div></div>
  </div>
</div>

<!-- Voice Main -->
<div id="voice-main">
  <div id="vc-topbar">
    <svg width="16" height="16" fill="none" stroke="#3ba55c" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
    <span style="font-weight:700;font-size:15px;"><?= htmlspecialchars($channel['name']) ?></span>
    <span class="vc-pill" id="vc-status">Menghubungkan</span>
    <div style="margin-left:auto;display:flex;gap:6px;">
      <span style="font-size:12px;color:#686c72;" id="vc-count">0 peserta</span>
    </div>
  </div>

  <div id="vc-grid">
    <div class="vc-tile" id="tile-<?= $userId ?>" style="width:240px;height:180px;">
      <div class="vc-tile-av" style="background:linear-gradient(135deg,#4752c4,#5865f2);"><?= strtoupper(substr($me['username'],0,1)) ?></div>
      <div class="vc-tile-label"><?= htmlspecialchars($me['username']) ?> (Kamu)</div>
    </div>
  </div>

  <div id="vc-controls">
    <button class="vbtn" id="btn-mic" onclick="toggleMic()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/></svg>
      <span>Mikrofon</span>
    </button>
    <button class="vbtn" id="btn-deaf" onclick="toggleDeafen()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
      <span>Headset</span>
    </button>
    <div class="vsep"></div>
    <button class="vbtn" id="btn-cam" onclick="toggleCam()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
      <span>Kamera</span>
    </button>
    <button class="vbtn" id="btn-screen" onclick="toggleScreen()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
      <span>Layar</span>
    </button>
    <div class="vsep"></div>
    <button class="vbtn leave" onclick="leaveVoice()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 3.75L18 6m0 0l2.25 2.25M18 6l2.25-2.25M18 6l-2.25 2.25m-10.5 6c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25"/></svg>
      Keluar
    </button>
  </div>
</div>

<script>
const CH=<?= $channelId ?>, SRV=<?= $serverId ?>, ME_NAME="<?= addslashes($me['username']) ?>", ME_ID=<?= $userId ?>;
const PC={host:'0.peerjs.com',port:443,path:'/',secure:true,config:{iceServers:[{urls:'stun:stun.l.google.com:19302'},{urls:'stun:stun1.l.google.com:19302'},{urls:'stun:stun.cloudflare.com:3478'}]}};
let peer,myPeerId,localStream,camStream,calls={},muted=false,deafened=false,camOn=false,sharing=false;

function updateGrid(){
  const tiles=document.querySelectorAll('#vc-grid .vc-tile');
  const n=tiles.length;
  const grid=document.getElementById('vc-grid');
  const cols=Math.min(n,4), rows=Math.ceil(n/cols);
  grid.style.gridTemplateColumns=`repeat(${cols},minmax(200px,280px))`;
  grid.style.gridTemplateRows=`repeat(${rows},minmax(160px,200px))`;
  document.getElementById('vc-count').textContent=n+' peserta';
}

function addTile(uid,name,stream,isVideo){
  if(document.getElementById('tile-'+uid)) return;
  const tile=document.createElement('div');
  tile.className='vc-tile'; tile.id='tile-'+uid;
  const colors=['#5865f2','#7c3aed','#059669','#b45309','#dc2626','#0891b2'];
  const col=colors[parseInt(uid)%colors.length];
  if(stream&&isVideo){
    const v=document.createElement('video'); v.autoplay=true; v.playsinline=true; v.srcObject=stream;
    tile.appendChild(v);
  } else {
    const av=document.createElement('div'); av.className='vc-tile-av';
    av.style.background=col; av.textContent=name[0].toUpperCase();
    tile.appendChild(av);
  }
  const lbl=document.createElement('div'); lbl.className='vc-tile-label'; lbl.textContent=name;
  tile.appendChild(lbl);
  document.getElementById('vc-grid').appendChild(tile);
  updateGrid();
}

function removeTile(uid){
  document.getElementById('tile-'+uid)?.remove();
  document.getElementById('audio-'+uid)?.remove();
  delete calls[uid]; updateGrid();
}

function addAudio(uid,stream){
  let a=document.getElementById('audio-'+uid);
  if(!a){ a=new Audio(); a.id='audio-'+uid; document.body.appendChild(a); }
  a.srcObject=stream; a.autoplay=true;
}

async function init(){
  try{ localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:false}); }
  catch(e){ localStream=new MediaStream(); }
  peer=new Peer(undefined,PC);
  peer.on('open',async id=>{
    myPeerId=id;
    document.getElementById('vc-status').textContent='Live';
    const r=await fetch('api/channels.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'join_voice',channel_id:CH,peer_id:id})});
    const d=await r.json();
    (d.participants||[]).forEach(p=>callPeer(p.peer_id,p.username,p.user_id));
    peer.on('call',call=>{
      call.answer(localStream);
      call.on('stream',s=>{
        const uid=call.metadata?.uid||call.peer;
        addTile(uid,call.metadata?.name||'Pengguna',s,false);
        addAudio(uid,s);
      });
      call.on('close',()=>removeTile(call.metadata?.uid||call.peer));
      calls[call.peer]=call;
    });
    updateGrid();
  });
  peer.on('error',e=>{ document.getElementById('vc-status').textContent='Error: '+e.type; });
  setInterval(heartbeat,8000);
  setInterval(pollPeers,4000);
}

function callPeer(peerId,name,uid){
  if(calls[peerId]||peerId===myPeerId) return;
  const c=peer.call(peerId,localStream,{metadata:{name:ME_NAME,uid:ME_ID}});
  if(!c) return;
  c.on('stream',s=>{ addTile(uid||peerId,name,s,false); addAudio(uid||peerId,s); });
  c.on('close',()=>removeTile(uid||peerId));
  calls[peerId]=c;
}

async function heartbeat(){
  if(!myPeerId) return;
  await fetch('api/channels.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'join_voice',channel_id:CH,peer_id:myPeerId})});
}

async function pollPeers(){
  if(!myPeerId) return;
  const r=await fetch(`api/channels.php?action=voice_peers&channel_id=${CH}`);
  const list=await r.json();
  list.forEach(p=>{ if(!calls[p.peer_id]) callPeer(p.peer_id,p.username,p.user_id); });
}

function toggleMic(){
  muted=!muted;
  localStream?.getAudioTracks().forEach(t=>t.enabled=!muted);
  document.getElementById('btn-mic').classList.toggle('off',muted);
}
function toggleDeafen(){
  deafened=!deafened;
  document.getElementById('btn-deaf').classList.toggle('off',deafened);
  document.querySelectorAll('audio').forEach(a=>a.muted=deafened);
}
async function toggleCam(){
  if(!camOn){
    try{ camStream=await navigator.mediaDevices.getUserMedia({video:true,audio:false}); }catch(e){return;}
    const vt=document.getElementById('tile-'+ME_ID);
    let v=vt?.querySelector('video');
    if(!v){ v=document.createElement('video'); v.autoplay=true; v.playsinline=true; v.muted=true; vt.appendChild(v); }
    v.srcObject=camStream;
    Object.values(calls).forEach(c=>{ const s=c.peerConnection?.getSenders().find(s=>s.track?.kind==='video'); if(s)s.replaceTrack(camStream.getVideoTracks()[0]); else c.peerConnection?.addTrack(camStream.getVideoTracks()[0],localStream); });
    camOn=true; document.getElementById('btn-cam').classList.add('off');
  } else {
    camStream?.getTracks().forEach(t=>t.stop());
    document.getElementById('tile-'+ME_ID)?.querySelector('video')?.remove();
    camOn=false; document.getElementById('btn-cam').classList.remove('off');
  }
}
async function toggleScreen(){
  if(!sharing){
    try{
      const ss=await navigator.mediaDevices.getDisplayMedia({video:true,audio:true});
      const st=ss.getVideoTracks()[0];
      Object.values(calls).forEach(c=>{ const sender=c.peerConnection?.getSenders().find(s=>s.track?.kind==='video'); if(sender)sender.replaceTrack(st); else c.peerConnection?.addTrack(st,localStream); });
      sharing=true; document.getElementById('btn-screen').classList.add('off');
      st.onended=()=>{sharing=false;document.getElementById('btn-screen').classList.remove('off');};
    }catch(e){}
  } else { sharing=false; document.getElementById('btn-screen').classList.remove('off'); }
}
async function leaveVoice(){
  await fetch('api/channels.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'leave_voice',channel_id:CH})});
  Object.values(calls).forEach(c=>c.close());
  localStream?.getTracks().forEach(t=>t.stop());
  camStream?.getTracks().forEach(t=>t.stop());
  peer?.destroy();
  window.location.href=`server.php?id=${SRV}`;
}
window.addEventListener('beforeunload',()=>{ fetch('api/channels.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'leave_voice',channel_id:CH}),keepalive:true}); });
init();
</script>
</body>
</html>

<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
updateOnlineStatus($pdo, $_SESSION['user_id']);
$userId   = $_SESSION['user_id'];
$me       = getCurrentUser($pdo);
$friendId = (int)($_GET['id'] ?? 0);
$callType = $_GET['type'] ?? 'voice';
$signalId = (int)($_GET['signal_id'] ?? 0);
$remotePeer = $_GET['peer'] ?? '';
$isCaller = !$signalId;
$s = $pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$friendId]); $friend = $s->fetch();
if (!$friend) { header('Location: friends.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>KaiVC — Call</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
body{background:#111214;color:#fff;height:100vh;display:flex;flex-direction:column;overflow:hidden;}

/* TOP BAR */
#topbar{
  height:48px;background:#1e1f22;border-bottom:1px solid rgba(255,255,255,.06);
  display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0;
}
#topbar .friend-name{font-weight:700;font-size:15px;}
#topbar .call-status-pill{
  background:rgba(35,165,90,.15);border:1px solid rgba(35,165,90,.3);
  color:#3ba55c;font-size:11px;font-weight:700;border-radius:100px;
  padding:3px 10px;letter-spacing:.3px;
}
.top-btn{
  margin-left:auto;display:flex;gap:4px;
}
.icon-btn{
  width:36px;height:36px;border-radius:4px;background:none;border:none;
  color:#96989d;cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background .1s,color .1s;text-decoration:none;
}
.icon-btn:hover{background:rgba(255,255,255,.07);color:#fff;}

/* CALL AREA */
#call-area{
  flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
  position:relative;overflow:hidden;
  background:radial-gradient(ellipse at center, #1a1c20 0%, #111214 70%);
}

/* Participant tiles */
#tiles{display:flex;gap:24px;align-items:center;justify-content:center;margin-bottom:40px;}

.tile{
  display:flex;flex-direction:column;align-items:center;gap:12px;
}
.tile-av{
  width:96px;height:96px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:36px;font-weight:900;color:#fff;
  position:relative;transition:transform .2s;
}
.tile-av.speaking{
  box-shadow:0 0 0 4px #3ba55c;
  animation:speaking .6s ease infinite alternate;
}
@keyframes speaking{from{box-shadow:0 0 0 3px rgba(59,165,92,.6);}to{box-shadow:0 0 0 6px rgba(59,165,92,.9);}}
.tile-av img{width:100%;height:100%;border-radius:50%;object-fit:cover;}
.tile-name{font-size:14px;font-weight:600;color:#dbdee1;}
.tile-muted{font-size:11px;color:#96989d;}

/* Video tiles (when video mode) */
.tile-video{
  width:280px;height:180px;border-radius:12px;background:#2b2d31;overflow:hidden;
  position:relative;border:2px solid transparent;
}
.tile-video.speaking{border-color:#3ba55c;}
.tile-video video{width:100%;height:100%;object-fit:cover;}
.tile-video .tile-label{
  position:absolute;bottom:8px;left:10px;
  background:rgba(0,0,0,.6);border-radius:4px;
  padding:2px 8px;font-size:12px;font-weight:600;
}

/* Status text */
#status-text{
  font-size:13px;color:#96989d;font-weight:500;margin-bottom:32px;
  letter-spacing:.2px;
}

/* CONTROL BAR — Discord style */
#controls{
  flex-shrink:0;height:72px;background:#292b2f;
  border-top:1px solid rgba(255,255,255,.05);
  display:flex;align-items:center;justify-content:center;gap:4px;padding:0 20px;
}
.ctrl-group{display:flex;align-items:center;gap:2px;}
.ctrl-sep{width:1px;height:32px;background:rgba(255,255,255,.1);margin:0 8px;}

.ctrl-btn{
  height:44px;min-width:44px;border-radius:4px;border:none;
  background:rgba(255,255,255,.06);color:#dbdee1;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  gap:6px;font-size:13px;font-weight:600;padding:0 12px;
  transition:background .15s,color .15s;position:relative;
}
.ctrl-btn:hover{background:rgba(255,255,255,.12);color:#fff;}
.ctrl-btn.toggled{background:rgba(255,255,255,.12);color:#fff;}
.ctrl-btn.danger{background:#da373c;color:#fff;}
.ctrl-btn.danger:hover{background:#a12828;}
.ctrl-btn.muted{background:rgba(218,55,60,.15);color:#da373c;}

.ctrl-arrow{
  width:24px;height:44px;border-radius:4px;border:none;
  background:rgba(255,255,255,.06);color:#96989d;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background .15s;margin-left:1px;
}
.ctrl-arrow:hover{background:rgba(255,255,255,.12);color:#fff;}

/* Timer */
#call-timer{position:absolute;top:16px;left:50%;transform:translateX(-50%);font-size:12px;color:#96989d;font-weight:600;letter-spacing:.5px;}

/* Screen share overlay */
#screen-area{display:none;position:absolute;inset:0;background:#000;z-index:10;}
#screen-area video{width:100%;height:100%;object-fit:contain;}
#screen-label{position:absolute;top:16px;left:16px;background:rgba(0,0,0,.7);border-radius:6px;padding:6px 12px;font-size:13px;font-weight:700;color:#3ba55c;}
</style>
</head>
<body>

<!-- TOP BAR -->
<div id="topbar">
  <svg width="16" height="16" fill="none" stroke="#3ba55c" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
  <span class="friend-name"><?= htmlspecialchars($friend['username']) ?></span>
  <span class="call-status-pill" id="status-pill">Menghubungkan</span>
  <div class="top-btn">
    <a href="chat.php?id=<?= $friendId ?>" class="icon-btn" title="Kembali ke Chat">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </a>
  </div>
</div>

<!-- CALL AREA -->
<div id="call-area">
  <div id="call-timer"></div>

  <!-- Screen share area -->
  <div id="screen-area">
    <video id="screen-video" autoplay playsinline></video>
    <div id="screen-label">Berbagi Layar Aktif</div>
  </div>

  <!-- Voice: Avatar tiles -->
  <div id="tiles">
    <!-- Me -->
    <div class="tile" id="tile-me">
      <div class="tile-av" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);" id="av-me">
        <?php if(!empty($me['avatar'])): ?>
          <img src="<?= htmlspecialchars($me['avatar']) ?>">
        <?php else: ?>
          <?= strtoupper(substr($me['username'],0,1)) ?>
        <?php endif; ?>
      </div>
      <div class="tile-name"><?= htmlspecialchars($me['username']) ?> <span style="color:#96989d;font-size:12px;">(Kamu)</span></div>
      <div class="tile-muted" id="me-muted-label"></div>
    </div>

    <!-- Friend -->
    <div class="tile" id="tile-friend">
      <div class="tile-av" style="background:#4f545c;" id="av-friend">
        <?php if(!empty($friend['avatar'])): ?>
          <img src="<?= htmlspecialchars($friend['avatar']) ?>">
        <?php else: ?>
          <?= strtoupper(substr($friend['username'],0,1)) ?>
        <?php endif; ?>
      </div>
      <div class="tile-name"><?= htmlspecialchars($friend['username']) ?></div>
      <div class="tile-muted" id="friend-label">Menghubungkan...</div>
    </div>
  </div>

  <div id="status-text"><?= $isCaller ? 'Menelepon ' : 'Menghubungkan ke ' ?><?= htmlspecialchars($friend['username']) ?>...</div>

  <!-- Hidden audio for voice calls -->
  <audio id="remote-audio" autoplay playsinline style="display:none;"></audio>

  <!-- Video tiles (hidden by default) -->
  <div id="video-tiles" style="display:none;gap:16px;flex-wrap:wrap;justify-content:center;margin-bottom:24px;">
    <div class="tile-video" id="vtile-friend">
      <video id="remote-video" autoplay playsinline></video>
      <div class="tile-label"><?= htmlspecialchars($friend['username']) ?></div>
    </div>
    <div class="tile-video" id="vtile-me">
      <video id="local-video" autoplay playsinline muted></video>
      <div class="tile-label"><?= htmlspecialchars($me['username']) ?> (Kamu)</div>
    </div>
  </div>
</div>

<!-- CONTROL BAR -->
<div id="controls">
  <!-- Mic -->
  <div class="ctrl-group">
    <button class="ctrl-btn" id="btn-mic" onclick="toggleMic()" title="Nonaktifkan Mikrofon">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/></svg>
    </button>
    <button class="ctrl-arrow">
      <svg width="10" height="10" fill="currentColor" viewBox="0 0 10 10"><path d="M5 7L1 3h8z"/></svg>
    </button>
  </div>

  <!-- Deafen -->
  <div class="ctrl-group">
    <button class="ctrl-btn" id="btn-deaf" onclick="toggleDeafen()" title="Bisukan">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
    </button>
    <button class="ctrl-arrow">
      <svg width="10" height="10" fill="currentColor" viewBox="0 0 10 10"><path d="M5 7L1 3h8z"/></svg>
    </button>
  </div>

  <div class="ctrl-sep"></div>

  <?php if($callType==='video'): ?>
  <!-- Camera -->
  <button class="ctrl-btn" id="btn-cam" onclick="toggleCam()" title="Matikan Kamera">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
  </button>
  <?php endif; ?>

  <!-- Screen Share -->
  <button class="ctrl-btn" id="btn-screen" onclick="toggleScreen()" title="Bagikan Layar">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
    <span style="font-size:12px;">Layar</span>
  </button>

  <div class="ctrl-sep"></div>

  <!-- End Call -->
  <button class="ctrl-btn danger" onclick="endCall()" title="Akhiri Panggilan">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 3.75L18 6m0 0l2.25 2.25M18 6l2.25-2.25M18 6l-2.25 2.25m-10.5 6c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25"/></svg>
  </button>
</div>

<script>
const IS_CALLER=<?= $isCaller?'true':'false' ?>, FRIEND_ID=<?= $friendId ?>, SIGNAL_ID=<?= $signalId ?>, REMOTE_PEER="<?= addslashes($remotePeer) ?>", CALL_TYPE="<?= $callType ?>";
let peer,localStream,currentCall,muted=false,deafened=false,camOn=true,sharingScreen=false,seconds=0,timerInt;

function setStatus(t,connected=false){
  document.getElementById('status-text').textContent=t;
  const pill=document.getElementById('status-pill');
  if(connected){pill.textContent='Terhubung';pill.style.background='rgba(35,165,90,.2)';pill.style.color='#3ba55c';}
  else{pill.textContent='Menghubungkan';pill.style.background='rgba(255,255,255,.06)';pill.style.color='#96989d';}
}

function startTimer(){
  timerInt=setInterval(()=>{
    seconds++;
    const m=String(Math.floor(seconds/60)).padStart(2,'0'), s=String(seconds%60).padStart(2,'0');
    document.getElementById('call-timer').textContent=m+':'+s;
  },1000);
}

function onConnected(stream){
  setStatus('Panggilan dengan <?= addslashes($friend['username']) ?> sedang berlangsung',true);
  document.getElementById('friend-label').textContent='Terhubung';
  document.getElementById('av-friend').style.boxShadow='0 0 0 4px #3ba55c';
  startTimer();

  if(CALL_TYPE==='video'){
    document.getElementById('tiles').style.display='none';
    document.getElementById('video-tiles').style.display='flex';
    document.getElementById('remote-video').srcObject=stream;
  } else {
    // Voice call: play audio
    const audio = document.getElementById('remote-audio');
    audio.srcObject = stream;
    audio.play().catch(e=>console.warn('Audio play failed:', e));
  }

  // Listen for NEW tracks added later (screen share)
  if(currentCall?.peerConnection){
    currentCall.peerConnection.addEventListener('track', (ev)=>{
      if(ev.track.kind==='video'){
        const scrArea = document.getElementById('screen-area');
        const scrVideo = document.getElementById('screen-video');
        scrVideo.srcObject = ev.streams[0] || new MediaStream([ev.track]);
        scrArea.style.display = 'block';
        document.getElementById('screen-label').textContent = '<?= addslashes($friend['username']) ?> sedang berbagi layar';
        ev.track.onended = ()=>{ scrArea.style.display='none'; };
      }
    });
  }
}

async function init(){
  try{
    if(CALL_TYPE==='video') localStream=await navigator.mediaDevices.getUserMedia({video:true,audio:true});
    else localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:false});
    if(CALL_TYPE==='video') document.getElementById('local-video').srcObject=localStream;
  }catch(e){ localStream=new MediaStream(); }

  peer=new Peer(undefined,{
    host:'0.peerjs.com', port:443, path:'/', secure:true,
    config:{ iceServers:[
      {urls:'stun:stun.l.google.com:19302'},
      {urls:'stun:stun1.l.google.com:19302'},
      {urls:'stun:stun2.l.google.com:19302'},
      {urls:'stun:stun.cloudflare.com:3478'}
    ]}
  });
  peer.on('open',async peerId=>{
    if(IS_CALLER){
      const r=await fetch('api/call_signal.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'initiate',receiver_id:FRIEND_ID,peer_id:peerId,type:CALL_TYPE})});
      const d=await r.json();
      let attempts=0;
      const poll=setInterval(async()=>{
        if(++attempts>40){clearInterval(poll);endCall();return;}
        const cr=await fetch(`api/call_signal.php?action=status&id=${d.signal_id}`);
        const cd=await cr.json();
        if(cd.status==='rejected'){clearInterval(poll);setStatus('Panggilan ditolak');setTimeout(()=>window.location.href=`chat.php?id=${FRIEND_ID}`,2000);}
        if(cd.status==='active') clearInterval(poll);
      },2000);
      peer.on('call',call=>{currentCall=call;call.answer(localStream);call.on('stream',s=>onConnected(s));});
    } else {
      await fetch('api/call_signal.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'accept',signal_id:SIGNAL_ID})});
      currentCall=peer.call(REMOTE_PEER,localStream);
      currentCall.on('stream',s=>onConnected(s));
    }
  });
  peer.on('error',e=>setStatus('Error: '+e.type));
}

function toggleMic(){
  muted=!muted;
  localStream?.getAudioTracks().forEach(t=>t.enabled=!muted);
  const btn=document.getElementById('btn-mic');
  btn.classList.toggle('muted',muted);
  document.getElementById('me-muted-label').textContent=muted?'Mikrofon dimatikan':'';
  btn.title=muted?'Aktifkan Mikrofon':'Nonaktifkan Mikrofon';
}

function toggleDeafen(){
  deafened=!deafened;
  document.getElementById('btn-deaf').classList.toggle('toggled',deafened);
  // Mute both audio and video elements
  const ra=document.getElementById('remote-audio');
  const rv=document.getElementById('remote-video');
  if(ra) ra.muted=deafened;
  if(rv) rv.muted=deafened;
}

function toggleCam(){
  camOn=!camOn;
  localStream?.getVideoTracks().forEach(t=>t.enabled=camOn);
  document.getElementById('btn-cam').classList.toggle('muted',!camOn);
}

async function toggleScreen(){
  if(!sharingScreen){
    try{
      const ss=await navigator.mediaDevices.getDisplayMedia({video:true,audio:false});
      const screenTrack=ss.getVideoTracks()[0];

      if(currentCall?.peerConnection){
        // Check if a video sender already exists
        const sender=currentCall.peerConnection.getSenders().find(s=>s.track?.kind==='video');
        if(sender){
          sender.replaceTrack(screenTrack);
        } else {
          // Voice call: add new video track
          currentCall.peerConnection.addTrack(screenTrack, localStream);
        }
      }

      // Show screen locally
      document.getElementById('screen-video').srcObject=ss;
      document.getElementById('screen-area').style.display='block';
      document.getElementById('screen-label').textContent='Kamu sedang berbagi layar';
      sharingScreen=true;
      document.getElementById('btn-screen').classList.add('toggled');
      screenTrack.onended=()=>stopScreen();
    }catch(e){ console.warn('Screen share error:',e); }
  } else stopScreen();
}

function stopScreen(){
  document.getElementById('screen-area').style.display='none';
  sharingScreen=false;
  document.getElementById('btn-screen').classList.remove('toggled');
  // Restore camera track if video call
  const camTrack=localStream?.getVideoTracks()[0];
  if(camTrack&&currentCall?.peerConnection){
    const sender=currentCall.peerConnection.getSenders().find(s=>s.track?.kind==='video');
    if(sender) sender.replaceTrack(camTrack);
  }
}

async function endCall(){
  clearInterval(timerInt);
  currentCall?.close(); peer?.destroy();
  localStream?.getTracks().forEach(t=>t.stop());
  if(SIGNAL_ID) await fetch(`api/call_signal.php?action=end&id=${SIGNAL_ID}`);
  window.location.href=`chat.php?id=${FRIEND_ID}`;
}

init();
</script>
</body>
</html>

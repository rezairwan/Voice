<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: friends.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>KaiVC — Voice & Chat Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
:root{--blue:#3b82f6;--blue-dark:#1d4ed8;--blue-dim:rgba(59,130,246,.12);--border:rgba(59,130,246,.14);}
body{background:#030b18;color:#f1f5f9;min-height:100vh;overflow-x:hidden;}

/* BG */
.bg{position:fixed;inset:0;pointer-events:none;z-index:0;
  background:radial-gradient(ellipse 70% 50% at 70% 0%,rgba(29,78,216,.22) 0%,transparent 60%),
             radial-gradient(ellipse 50% 40% at 0% 80%,rgba(59,130,246,.12) 0%,transparent 60%),
             #030b18;}

/* NAV */
nav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;padding:0 48px;justify-content:space-between;
  background:rgba(3,11,24,.75);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:#f1f5f9;font-size:18px;font-weight:900;letter-spacing:-.5px;}
.logo-mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--blue-dark),var(--blue));
  display:flex;align-items:center;justify-content:center;box-shadow:0 0 18px rgba(59,130,246,.35);}
.logo-mark svg{width:18px;height:18px;fill:#fff;}
.nav-right{display:flex;align-items:center;gap:8px;}
.btn-ghost{padding:8px 16px;border-radius:7px;font-size:13px;font-weight:600;color:#94a3b8;background:none;border:none;cursor:pointer;text-decoration:none;transition:color .15s,background .15s;}
.btn-ghost:hover{color:#f1f5f9;background:rgba(255,255,255,.06);}
.btn-primary{padding:8px 20px;border-radius:100px;font-size:13px;font-weight:700;
  background:linear-gradient(135deg,var(--blue-dark),var(--blue));color:#fff;border:none;cursor:pointer;text-decoration:none;
  box-shadow:0 4px 16px rgba(59,130,246,.3);transition:opacity .15s,transform .1s;}
.btn-primary:hover{opacity:.9;transform:translateY(-1px);}

/* HERO */
.hero{position:relative;z-index:1;padding:140px 48px 80px;max-width:1160px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;}
.tag{display:inline-flex;align-items:center;gap:8px;background:var(--blue-dim);border:1px solid var(--border);
  border-radius:100px;padding:5px 14px;font-size:12px;font-weight:700;color:var(--blue);letter-spacing:.3px;margin-bottom:28px;text-transform:uppercase;}
.tag-dot{width:6px;height:6px;border-radius:50%;background:var(--blue);animation:blink 1.5s ease infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.3;}}
h1{font-size:clamp(36px,4.5vw,60px);font-weight:900;line-height:1.08;letter-spacing:-2px;margin-bottom:22px;}
h1 em{font-style:normal;background:linear-gradient(135deg,var(--blue),#93c5fd);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero-desc{font-size:17px;line-height:1.7;color:#64748b;margin-bottom:38px;max-width:440px;}
.hero-ctas{display:flex;gap:12px;flex-wrap:wrap;}
.btn-outline{padding:12px 26px;border-radius:100px;font-size:14px;font-weight:700;
  background:none;border:1.5px solid rgba(255,255,255,.15);color:#f1f5f9;text-decoration:none;
  transition:border-color .15s,background .15s;}
.btn-outline:hover{border-color:rgba(255,255,255,.3);background:rgba(255,255,255,.05);}
.btn-cta{padding:12px 28px;font-size:14px;}
.hero-stats{display:flex;gap:28px;margin-top:40px;padding-top:32px;border-top:1px solid var(--border);}
.stat-val{font-size:22px;font-weight:900;color:#f1f5f9;}
.stat-lbl{font-size:12px;color:#475569;margin-top:2px;font-weight:500;}

/* MOCKUP */
.mockup{background:rgba(5,14,30,.9);border:1px solid var(--border);border-radius:18px;overflow:hidden;
  box-shadow:0 40px 80px rgba(0,0,0,.5);animation:float 6s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.m-bar{height:38px;background:rgba(3,8,18,.95);display:flex;align-items:center;padding:0 14px;gap:6px;border-bottom:1px solid var(--border);}
.m-dot{width:9px;height:9px;border-radius:50%;}
.m-d1{background:#f23f43;}.m-d2{background:#f0b232;}.m-d3{background:#23a55a;}
.m-title{margin-left:8px;font-size:11px;font-weight:700;color:#334155;}
.m-body{display:flex;height:260px;}
.m-rail{width:54px;background:rgba(2,6,14,.9);padding:10px 7px;display:flex;flex-direction:column;align-items:center;gap:8px;border-right:1px solid var(--border);}
.m-ri{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;transition:border-radius .2s;}
.m-ri.a{border-radius:11px;background:linear-gradient(135deg,var(--blue-dark),var(--blue));color:#fff;}
.m-ri.b{background:var(--blue-dim);color:var(--blue);}
.m-dm{width:110px;background:rgba(3,9,20,.8);padding:8px 6px;border-right:1px solid var(--border);}
.m-dml{font-size:8px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.6px;padding:4px 6px 8px;}
.m-dmi{display:flex;align-items:center;gap:6px;padding:5px 6px;border-radius:4px;margin-bottom:1px;}
.m-dmi.ac{background:var(--blue-dim);}
.m-dmav{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:800;color:#fff;flex-shrink:0;}
.m-dmn{font-size:9px;font-weight:500;color:#64748b;white-space:nowrap;}
.m-dmn.w{color:#e2e8f0;}
.m-chat{flex:1;padding:12px;display:flex;flex-direction:column;gap:8px;}
.m-msg{display:flex;gap:7px;}
.m-mav{width:22px;height:22px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:800;color:#fff;}
.m-mb{}
.m-mn{font-size:8px;font-weight:700;margin-bottom:2px;color:#93c5fd;}
.m-mc{font-size:10px;color:#94a3b8;line-height:1.4;}
.m-mc.hl{background:var(--blue-dim);border-radius:5px;padding:3px 8px;color:#93c5fd;display:inline-block;}
.m-typ{display:flex;gap:3px;margin-top:auto;align-items:center;padding:2px 0;}
.m-td{width:5px;height:5px;border-radius:50%;background:var(--blue);animation:td .9s ease infinite;}
.m-td:nth-child(2){animation-delay:.15s;}.m-td:nth-child(3){animation-delay:.3s;}
@keyframes td{0%,80%,100%{transform:translateY(0);}40%{transform:translateY(-4px);}}

/* FEATURES */
.section{position:relative;z-index:1;padding:80px 48px;max-width:1160px;margin:0 auto;}
.sec-label{font-size:11px;font-weight:800;color:var(--blue);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:14px;}
.sec-title{font-size:clamp(26px,3vw,40px);font-weight:900;letter-spacing:-1px;margin-bottom:8px;}
.sec-sub{color:#475569;font-size:16px;margin-bottom:48px;max-width:480px;}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
@media(max-width:900px){.grid{grid-template-columns:1fr 1fr;}.hero{grid-template-columns:1fr;}.hero-right{display:none;}}
@media(max-width:600px){.grid{grid-template-columns:1fr;}nav{padding:0 20px;}.hero{padding:120px 20px 60px;}}
.fcard{background:rgba(5,14,30,.8);border:1px solid var(--border);border-radius:16px;padding:28px;
  transition:border-color .2s,transform .2s;}
.fcard:hover{border-color:rgba(59,130,246,.3);transform:translateY(-3px);}
.fcard-icon{width:44px;height:44px;border-radius:12px;background:var(--blue-dim);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;margin-bottom:16px;}
.fcard-icon svg{width:22px;height:22px;stroke:var(--blue);fill:none;stroke-width:1.8;}
.fcard-name{font-size:15px;font-weight:800;margin-bottom:6px;}
.fcard-desc{font-size:13px;color:#475569;line-height:1.6;}

/* CTA */
.cta-wrap{position:relative;z-index:1;padding:60px 48px 100px;text-align:center;}
.cta-inner{max-width:520px;margin:0 auto;background:linear-gradient(135deg,rgba(29,78,216,.15),rgba(59,130,246,.08));
  border:1px solid var(--border);border-radius:24px;padding:56px 40px;backdrop-filter:blur(20px);}
.cta-inner h2{font-size:clamp(22px,3vw,36px);font-weight:900;letter-spacing:-1px;margin-bottom:12px;}
.cta-inner p{color:#475569;font-size:15px;margin-bottom:28px;}

/* FOOTER */
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:20px 48px;
  display:flex;align-items:center;justify-content:space-between;}
.ft-logo{font-size:15px;font-weight:900;color:#1e3a5f;}
.ft-logo b{color:var(--blue);}
.ft-copy{font-size:12px;color:#1e3a5f;}
</style>
</head>
<body>
<div class="bg"></div>

<!-- NAV -->
<nav>
  <a href="index.php" class="logo">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24"><path d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
    </div>
    KaiVC
  </a>
  <div class="nav-right">
    <a href="#features" class="btn-ghost">Fitur</a>
    <a href="login.php" class="btn-ghost">Masuk</a>
    <a href="register.php" class="btn-primary">Daftar Gratis</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div>
    <div class="tag"><span class="tag-dot"></span>Platform Baru · Gratis Selamanya</div>
    <h1>Komunikasi<br>tanpa batas.<br><em>Mulai sekarang.</em></h1>
    <p class="hero-desc">KaiVC menghadirkan pesan langsung, voice call, dan berbagi layar dalam satu platform yang ringan dan cepat.</p>
    <div class="hero-ctas">
      <a href="register.php" class="btn-primary btn-cta">Buat Akun Gratis</a>
      <a href="login.php" class="btn-outline">Masuk</a>
    </div>
    <div class="hero-stats">
      <div><div class="stat-val">100%</div><div class="stat-lbl">Gratis</div></div>
      <div><div class="stat-val">&lt;1s</div><div class="stat-lbl">Latensi Pesan</div></div>
      <div><div class="stat-val">WebRTC</div><div class="stat-lbl">Voice Technology</div></div>
    </div>
  </div>
  <div class="hero-right">
    <div class="mockup">
      <div class="m-bar">
        <div class="m-dot m-d1"></div><div class="m-dot m-d2"></div><div class="m-dot m-d3"></div>
        <span class="m-title">KaiVC</span>
      </div>
      <div class="m-body">
        <div class="m-rail">
          <div class="m-ri a">K</div>
          <div style="width:20px;height:1px;background:var(--border);margin:2px 0;"></div>
          <div class="m-ri b">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
          </div>
        </div>
        <div class="m-dm">
          <div class="m-dml">Pesan Langsung</div>
          <div class="m-dmi ac"><div class="m-dmav" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">A</div><div class="m-dmn w">Arya</div></div>
          <div class="m-dmi"><div class="m-dmav" style="background:#7c3aed;">B</div><div class="m-dmn">Budi</div></div>
          <div class="m-dmi"><div class="m-dmav" style="background:#059669;">C</div><div class="m-dmn">Citra</div></div>
          <div class="m-dmi"><div class="m-dmav" style="background:#b45309;">D</div><div class="m-dmn">Doni</div></div>
        </div>
        <div class="m-chat">
          <div class="m-msg">
            <div class="m-mav" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">A</div>
            <div class="m-mb"><div class="m-mn">Arya</div><div class="m-mc">Mau mulai call sekarang?</div></div>
          </div>
          <div class="m-msg">
            <div class="m-mav" style="background:#7c3aed;">B</div>
            <div class="m-mb"><div class="m-mn" style="color:#a78bfa;">Budi</div><div class="m-mc hl">Voice call dimulai</div></div>
          </div>
          <div class="m-msg">
            <div class="m-mav" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">A</div>
            <div class="m-mb"><div class="m-mn">Arya</div><div class="m-mc">Share screen juga ya</div></div>
          </div>
          <div class="m-typ"><div class="m-td"></div><div class="m-td"></div><div class="m-td"></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="sec-label">Fitur Utama</div>
  <div class="sec-title">Satu platform, semua cara komunikasi</div>
  <div class="sec-sub">Dirancang untuk komunikasi yang efisien, tanpa kerumitan.</div>
  <div class="grid">
    <div class="fcard">
      <div class="fcard-icon"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div>
      <div class="fcard-name">Pesan Langsung</div>
      <div class="fcard-desc">Kirim pesan real-time dengan latensi rendah. Riwayat pesan tersimpan aman di server.</div>
    </div>
    <div class="fcard">
      <div class="fcard-icon"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg></div>
      <div class="fcard-name">Voice Call</div>
      <div class="fcard-desc">Panggilan suara langsung dari browser menggunakan teknologi WebRTC peer-to-peer.</div>
    </div>
    <div class="fcard">
      <div class="fcard-icon"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg></div>
      <div class="fcard-name">Berbagi Layar</div>
      <div class="fcard-desc">Tampilkan layar secara real-time saat sedang dalam sesi voice call bersama teman.</div>
    </div>
    <div class="fcard">
      <div class="fcard-icon"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></div>
      <div class="fcard-name">Sistem Pertemanan</div>
      <div class="fcard-desc">Tambah teman, kelola permintaan masuk dan keluar, serta lihat status online teman.</div>
    </div>
    <div class="fcard">
      <div class="fcard-icon"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg></div>
      <div class="fcard-name">Notifikasi Panggilan</div>
      <div class="fcard-desc">Terima notifikasi panggilan masuk secara langsung tanpa meninggalkan halaman chat.</div>
    </div>
    <div class="fcard">
      <div class="fcard-icon"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg></div>
      <div class="fcard-name">Aman &amp; Gratis</div>
      <div class="fcard-desc">Password dienkripsi, koneksi aman. Tidak ada biaya, tidak ada batas penggunaan.</div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-wrap">
  <div class="cta-inner">
    <h2>Siap memulai?</h2>
    <p>Buat akun dalam 30 detik dan mulai berkomunikasi bersama tim atau teman-temanmu.</p>
    <a href="register.php" class="btn-primary btn-cta">Buat Akun Sekarang</a>
  </div>
</section>

<footer>
  <div class="ft-logo"><b>Kai</b>VC</div>
  <div class="ft-copy">2026 KaiVC. Voice & Chat Platform.</div>
</footer>
</body>
</html>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/db.php';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE id_user = ? AND is_read = 0");
$stmt->execute([$_SESSION['user']['id_user']]);
$notif_count = $stmt->fetchColumn();

$stmt2 = $pdo->prepare("SELECT saldo FROM saldo WHERE id_user = ?");
$stmt2->execute([$_SESSION['user']['id_user']]);
$saldo_row = $stmt2->fetch();
$saldo = $saldo_row ? $saldo_row['saldo'] : 0;
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root,[data-theme="light"]{
            --bg-page:#edf4ee;--bg-card:#fff;--bg-card2:#e3f0e5;
            --text-1:#0f1f0f;--text-2:#4a5e4a;--text-3:#8a9e8a;
            --border:#b0cfb3;--shadow:0 2px 12px rgba(0,0,0,.07);--shadow-h:0 8px 24px rgba(0,0,0,.12);
            --ac:#2d7d3a;--ac-dk:#1a5c28;--ac-lt:#c8e6cc;--ac-tx:#1a5c28;
            --sb-bg:linear-gradient(160deg,#0d2612 0%,#1a4a22 55%,#236130 100%);
            --sb-active:rgba(255,255,255,.18);--sb-hover:rgba(255,255,255,.10);
            --h1:#1a4a22;--inp-bg:#ebf5ec;--inp-bd:#a5cca9;
        }
        [data-theme="dark"]{
            --bg-page:#080912;--bg-card:#111326;--bg-card2:#181a2e;
            --text-1:#e8eaf6;--text-2:#9fa8da;--text-3:#5c6bc0;
            --border:#1e2140;--shadow:0 2px 20px rgba(0,0,0,.5);--shadow-h:0 8px 36px rgba(79,70,229,.3);
            --ac:#6366f1;--ac-dk:#4f46e5;--ac-lt:#1a1d38;--ac-tx:#818cf8;
            --sb-bg:linear-gradient(160deg,#05060d 0%,#0a0b18 55%,#0f1120 100%);
            --sb-active:rgba(99,102,241,.22);--sb-hover:rgba(99,102,241,.11);
            --h1:#818cf8;--inp-bg:#141628;--inp-bd:#252848;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg-page);display:flex;height:100vh;color:var(--text-1);transition:background .3s,color .3s;}

        /* Sidebar */
        .sidebar{width:260px;background:var(--sb-bg);height:100vh;display:flex;flex-direction:column;position:fixed;left:0;top:0;z-index:100;box-shadow:4px 0 28px rgba(0,0,0,.35);}
        .sidebar-header{padding:22px 18px 15px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;justify-content:space-between;}
        .sidebar-logo{display:flex;align-items:center;gap:11px;text-decoration:none;}
        .logo-icon{width:42px;height:42px;background:linear-gradient(135deg,#f97316,#ef4444);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;color:white;box-shadow:0 4px 14px rgba(249,115,22,.4);}
        .logo-text{color:white;}
        .logo-text h2{font-size:17px;font-weight:800;}
        .logo-text span{font-size:10px;opacity:.5;}
        .theme-toggle{width:34px;height:34px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.14);border-radius:10px;color:rgba(255,255,255,.8);font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;}
        .theme-toggle:hover{background:rgba(255,255,255,.18);}
        [data-theme="dark"] .theme-toggle{background:rgba(99,102,241,.2);border-color:rgba(99,102,241,.35);color:#a5b4fc;}

        .saldo-widget{margin:10px 12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:13px 15px;}
        [data-theme="dark"] .saldo-widget{background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.2);}
        .saldo-label{color:rgba(255,255,255,.45);font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;}
        .saldo-amount{color:white;font-size:21px;font-weight:800;margin:4px 0 8px;}
        [data-theme="dark"] .saldo-amount{text-shadow:0 0 20px rgba(99,102,241,.5);}
        .saldo-topup{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.14);color:white;font-size:11.5px;font-weight:600;padding:5px 12px;border-radius:20px;text-decoration:none;transition:background .2s;border:1px solid rgba(255,255,255,.18);}
        .saldo-topup:hover{background:rgba(255,255,255,.24);}

        .sidebar-user{margin:0 12px 4px;padding:10px 13px;display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.04);border-radius:11px;}
        .user-avatar{width:34px;height:34px;background:linear-gradient(135deg,#a78bfa,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;}
        [data-theme="dark"] .user-avatar{background:linear-gradient(135deg,#6366f1,#ec4899);box-shadow:0 0 12px rgba(99,102,241,.4);}
        .user-info .name{color:white;font-size:12.5px;font-weight:600;}
        .user-info .nim{color:rgba(255,255,255,.38);font-size:10.5px;}

        .sidebar-nav{flex:1;padding:6px 10px;overflow-y:auto;min-height:0;scrollbar-width:none;}
        .sidebar-nav::-webkit-scrollbar{display:none;}
        .nav-section-label{color:rgba(255,255,255,.28);font-size:9.5px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:10px 12px 5px;}
        .nav-item{display:flex;align-items:center;gap:11px;padding:10px 13px;border-radius:10px;color:rgba(255,255,255,.58);text-decoration:none;font-size:13.5px;font-weight:500;margin-bottom:2px;position:relative;transition:all .2s ease;}
        .nav-item:hover{background:var(--sb-hover);color:white;transform:translateX(3px);}
        .nav-item.active{background:var(--sb-active);color:white;font-weight:600;}
        .nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:55%;background:linear-gradient(to bottom,#2d7d3a,#1a5c28);border-radius:0 3px 3px 0;}
        [data-theme="dark"] .nav-item.active::before{background:linear-gradient(to bottom,#6366f1,#ec4899);}
        .nav-item i{width:19px;text-align:center;font-size:14px;}
        .nav-badge{margin-left:auto;background:#ef4444;color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;}
        hr.nav-divider{border:none;border-top:1px solid rgba(255,255,255,.06);margin:6px 0;}
        .sidebar-footer{padding:13px 10px;border-top:1px solid rgba(255,255,255,.06);}
        .nav-item.logout{color:rgba(255,100,100,.7);}
        .nav-item.logout:hover{background:rgba(239,68,68,.12);color:#f87171;}

        /* Dark mode ambient glow */
        [data-theme="dark"] .sidebar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:200px;background:linear-gradient(to top,rgba(99,102,241,.08),transparent);pointer-events:none;}

        /* Main content */
        .main-content{margin-left:260px;flex:1;padding:28px;height:100vh;overflow-y:auto;transition:background .3s;}
        .page-header{margin-bottom:24px;}
        .page-header h1{font-size:23px;font-weight:800;color:var(--h1);}
        .page-header p{color:var(--text-3);font-size:13.5px;margin-top:4px;}

        /* Dark mode page glow */
        [data-theme="dark"] .main-content::before{content:'';position:fixed;top:-150px;right:-150px;width:500px;height:500px;background:radial-gradient(circle,rgba(99,102,241,.06) 0%,transparent 70%);pointer-events:none;z-index:0;}

        /* Bottom navbar mobile */
        .bottom-nav{display:none;}
        @media(max-width:768px){
            .sidebar{display:none;}
            .main-content{margin-left:0;padding:16px;padding-bottom:80px;}
            .bottom-nav{
                display:flex;
                position:fixed;bottom:0;left:0;right:0;z-index:200;
                background:var(--sb-bg);
                border-top:1px solid rgba(255,255,255,.1);
                box-shadow:0 -4px 20px rgba(0,0,0,.25);
                height:64px;
                align-items:stretch;
            }
            .bottom-nav a{
                flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
                color:rgba(255,255,255,.5);text-decoration:none;
                font-size:9.5px;font-weight:600;gap:4px;
                position:relative;transition:color .2s;
                padding:6px 2px;
            }
            .bottom-nav a i{font-size:18px;}
            .bottom-nav a.active{color:white;}
            .bottom-nav a.active::before{
                content:'';position:absolute;top:0;left:15%;right:15%;
                height:2.5px;background:linear-gradient(to right,#4a9e5c,#2d7d3a);
                border-radius:0 0 4px 4px;
            }
            .bottom-nav a:hover{color:rgba(255,255,255,.85);}
            .bottom-nav .bn-badge{
                position:absolute;top:6px;right:calc(50% - 18px);
                background:#ef4444;color:white;font-size:8px;font-weight:700;
                padding:1px 5px;border-radius:20px;
            }
        }
    </style>
    <script>
        (function(){
            var t=localStorage.getItem('ekantin-theme')||'light';
            document.documentElement.setAttribute('data-theme',t);
        })();
    </script>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="beranda.php" class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-utensils"></i></div>
            <div class="logo-text">
                <h2>E-Kantin</h2>
                <span>Portal Mahasiswa</span>
            </div>
        </a>
        <button class="theme-toggle" onclick="toggleTheme()" id="theme-btn" title="Ganti tema">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>
    </div>

    <div class="saldo-widget">
        <div class="saldo-label">Saldo Kamu</div>
        <div class="saldo-amount">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
        <a href="topup.php" class="saldo-topup"><i class="fas fa-plus-circle"></i> Top Up</a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user']['nama'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['user']['nama']) ?></div>
            <div class="nim">NIM: <?= htmlspecialchars($_SESSION['user']['nim'] ?? '-') ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu</div>
        <a href="beranda.php" class="nav-item <?= $current_page==='beranda.php'?'active':'' ?>"><i class="fas fa-home"></i><span>Beranda</span></a>
        <a href="kantin.php" class="nav-item <?= $current_page==='kantin.php'?'active':'' ?>"><i class="fas fa-store"></i><span>Daftar Kantin</span></a>
        <a href="keranjang.php" class="nav-item <?= $current_page==='keranjang.php'?'active':'' ?>"><i class="fas fa-shopping-cart"></i><span>Keranjang</span></a>
        <hr class="nav-divider">
        <div class="nav-section-label">Akun</div>
        <a href="riwayat.php" class="nav-item <?= $current_page==='riwayat.php'?'active':'' ?>"><i class="fas fa-history"></i><span>Riwayat Pesanan</span></a>
        <a href="topup.php" class="nav-item <?= $current_page==='topup.php'?'active':'' ?>"><i class="fas fa-wallet"></i><span>Top Up Saldo</span></a>
        <a href="notifikasi.php" class="nav-item <?= $current_page==='notifikasi.php'?'active':'' ?>">
            <i class="fas fa-bell"></i><span>Notifikasi</span>
            <?php if($notif_count>0):?><span class="nav-badge"><?=$notif_count?></span><?php endif;?>
        </a>
        <a href="pengaturan.php" class="nav-item <?= $current_page==='pengaturan.php'?'active':'' ?>"><i class="fas fa-user-cog"></i><span>Pengaturan Akun</span></a>
    </nav>

    <div class="sidebar-footer">
        <a href="../../logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
    </div>
</aside>

<!-- Bottom Navbar (mobile only) -->
<nav class="bottom-nav">
    <a href="beranda.php" class="<?= ($current_page==='beranda.php'||$current_page==='kantin.php')?'active':'' ?>">
        <i class="fas fa-home"></i><span>Beranda</span>
    </a>
    <a href="riwayat.php" class="<?= $current_page==='riwayat.php'?'active':'' ?>">
        <i class="fas fa-history"></i><span>Riwayat</span>
    </a>
    <a href="keranjang.php" class="<?= $current_page==='keranjang.php'?'active':'' ?>">
        <i class="fas fa-shopping-cart"></i><span>Keranjang</span>
    </a>
    <a href="notifikasi.php" class="<?= $current_page==='notifikasi.php'?'active':'' ?>">
        <i class="fas fa-bell"></i>
        <?php if($notif_count>0):?><span class="bn-badge"><?=$notif_count?></span><?php endif;?>
        <span>Notif</span>
    </a>
    <a href="pengaturan.php" class="<?= $current_page==='pengaturan.php'?'active':'' ?>">
        <i class="fas fa-user-cog"></i><span>Akun</span>
    </a>
</nav>

<div class="main-content">

<script>
// Fungsi pembantu untuk mengatur gaya elemen berdasarkan tema
function applyThemeStyles(theme) {
    var saldoDisplay = document.querySelector('.saldo-display');
    var buttonTopUp = document.querySelector('.btn-topup');
    
    var isDark = (theme === 'dark');
    var gradientBg = 'linear-gradient(135deg, #064e3b, #059669)';

    // Logika untuk Saldo Display
    if (saldoDisplay) {
        saldoDisplay.style.background = isDark ? 'none' : gradientBg;
    }

    // Logika untuk Button Top Up (Samakan logikanya)
    if (buttonTopUp) {
        buttonTopUp.style.background = isDark ? 'none' : gradientBg;
    }
}

function toggleTheme() {
    var html = document.documentElement;
    var cur = html.getAttribute('data-theme') || 'light';
    var next = cur === 'dark' ? 'light' : 'dark';
    
    // 1. Simpan state
    html.setAttribute('data-theme', next);
    localStorage.setItem('ekantin-theme', next);
    
    // 2. Update Icon
    var themeIcon = document.getElementById('theme-icon');
    if (themeIcon) {
        themeIcon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    // 3. Terapkan perubahan background
    applyThemeStyles(next);
}

// Inisialisasi saat pertama kali rendering
(function() {
    // Ambil tema dari localStorage atau default ke light
    var savedTheme = localStorage.getItem('ekantin-theme') || 'light';
    
    // Terapkan tema ke tag HTML
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update Icon di awal
    var icon = document.getElementById('theme-icon');
    if (icon) {
        icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    // Jalankan logika background berdasarkan localStorage (Penting untuk awal rendering)
    applyThemeStyles(savedTheme);
})();
</script>
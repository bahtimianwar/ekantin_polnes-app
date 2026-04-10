<?php
// Session already started by page

$current_page = basename($_SERVER['PHP_SELF']);

require_once __DIR__ . '/db.php';

// Notif belum dibaca
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE id_user = ? AND is_read = 0");
$stmt->execute([$_SESSION['user']['id_user']]);
$notif_count = $stmt->fetchColumn();

// Saldo mahasiswa
$stmt2 = $pdo->prepare("SELECT saldo FROM saldo WHERE id_user = ?");
$stmt2->execute([$_SESSION['user']['id_user']]);
$saldo_row = $stmt2->fetch();
$saldo = $saldo_row ? $saldo_row['saldo'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; height: 100vh; }

        .sidebar {
            width: 260px;
            background: linear-gradient(160deg, #064e3b 0%, #065f46 60%, #059669 100%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0; top: 0;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }
        .sidebar-header {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: white;
            box-shadow: 0 4px 12px rgba(255,107,53,0.4);
        }
        .logo-text { color: white; }
        .logo-text h2 { font-size: 18px; font-weight: 700; }
        .logo-text span { font-size: 11px; opacity: 0.7; }

        .saldo-widget {
            margin: 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 14px 16px;
            backdrop-filter: blur(4px);
        }
        .saldo-label { color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }
        .saldo-amount { color: white; font-size: 22px; font-weight: 800; margin: 4px 0; }
        .saldo-topup {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.2);
            color: white; font-size: 12px; font-weight: 600;
            padding: 5px 12px; border-radius: 20px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .saldo-topup:hover { background: rgba(255,255,255,0.3); }

        .sidebar-user {
            margin: 0 12px 4px;
            padding: 12px 16px;
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
        }
        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #26c6da, #0097a7);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 14px;
        }
        .user-info .name { color: white; font-size: 13px; font-weight: 600; }
        .user-info .nim { color: rgba(255,255,255,0.5); font-size: 11px; }

        .sidebar-nav { flex: 1; padding: 8px 12px; overflow-y: auto; min-height: 0; scrollbar-width: none; }
        .sidebar-nav::-webkit-scrollbar { display: none; }
        .nav-section-label {
            color: rgba(255,255,255,0.4);
            font-size: 10px; font-weight: 700;
            letter-spacing: 1.2px; text-transform: uppercase;
            padding: 12px 12px 6px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 14px; font-weight: 500;
            margin-bottom: 2px;
            position: relative;
            transition: all 0.2s ease;
        }
        .nav-item:hover { background: rgba(255,255,255,0.12); color: white; transform: translateX(4px); }
        .nav-item.active { background: rgba(255,255,255,0.18); color: white; font-weight: 600; }
        .nav-item.active::before {
            content: ''; position: absolute;
            left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 60%;
            background: #ff6b35; border-radius: 0 4px 4px 0;
        }
        .nav-item i { width: 20px; text-align: center; font-size: 15px; }
        .nav-badge {
            margin-left: auto;
            background: #ff4444; color: white;
            font-size: 10px; font-weight: 700;
            padding: 2px 7px; border-radius: 20px;
        }
        hr.nav-divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 8px 0; }

        .sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.1); }
        .nav-item.logout { color: rgba(255,100,100,0.85); }
        .nav-item.logout:hover { background: rgba(255,100,100,0.15); color: #ff6b6b; }

        .main-content { margin-left: 260px; flex: 1; padding: 28px; height: 100vh; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 24px; font-weight: 700; color: #064e3b; }
        .page-header p { color: #666; font-size: 14px; margin-top: 4px; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header .logo-text, .sidebar-user .user-info,
            .nav-item span, .nav-section-label, .saldo-widget .saldo-label,
            .saldo-widget .saldo-amount, .saldo-widget .saldo-topup { display: none; }
            .saldo-widget { padding: 8px; display: flex; justify-content: center; }
            .main-content { margin-left: 70px; padding: 16px; }
            .nav-item { justify-content: center; padding: 12px; }
            .nav-item i { width: auto; font-size: 18px; }
        }
    </style>
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

        <a href="beranda.php" class="nav-item <?= $current_page === 'beranda.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>

        <a href="kantin.php" class="nav-item <?= $current_page === 'kantin.php' ? 'active' : '' ?>">
            <i class="fas fa-store"></i>
            <span>Daftar Kantin</span>
        </a>

        <a href="keranjang.php" class="nav-item <?= $current_page === 'keranjang.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Keranjang</span>
        </a>

        <hr class="nav-divider">
        <div class="nav-section-label">Akun</div>

        <a href="riwayat.php" class="nav-item <?= $current_page === 'riwayat.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>Riwayat Pesanan</span>
        </a>

        <a href="topup.php" class="nav-item <?= $current_page === 'topup.php' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i>
            <span>Top Up Saldo</span>
        </a>

        <a href="notifikasi.php" class="nav-item <?= $current_page === 'notifikasi.php' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i>
            <span>Notifikasi</span>
            <?php if ($notif_count > 0): ?>
                <span class="nav-badge"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>

        <a href="pengaturan.php" class="nav-item <?= $current_page === 'pengaturan.php' ? 'active' : '' ?>">
            <i class="fas fa-user-cog"></i>
            <span>Pengaturan Akun</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../../logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>
    </div>
</aside>

<div class="main-content">
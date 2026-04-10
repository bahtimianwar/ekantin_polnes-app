<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /login.php'); exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

require_once __DIR__ . '/db.php';

// Hitung pengajuan top up yang menunggu
$stmt = $pdo->query("SELECT COUNT(*) FROM topup WHERE status = 'menunggu'");
$topup_pending = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; height: 100vh; }

        .sidebar {
            width: 260px;
            background: linear-gradient(160deg, #1a237e 0%, #283593 60%, #3949ab 100%);
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

        .sidebar-user {
            margin: 14px 12px 4px;
            padding: 12px 16px;
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
        }
        .user-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 15px;
        }
        .user-info .name { color: white; font-size: 13px; font-weight: 600; }
        .user-info .role { color: rgba(255,255,255,0.5); font-size: 11px; }

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

        .main-content { margin-left: 260px; flex: 1; padding: 28px; height: 100vh; overflow-y: auto; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 24px; font-weight: 700; color: #1a237e; }
        .page-header p { color: #666; font-size: 14px; margin-top: 4px; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header .logo-text, .sidebar-user .user-info,
            .nav-item span, .nav-section-label { display: none; }
            .main-content { margin-left: 70px; padding: 16px; }
            .nav-item { justify-content: center; padding: 12px; }
            .nav-item i { width: auto; font-size: 18px; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-utensils"></i></div>
            <div class="logo-text">
                <h2>E-Kantin</h2>
                <span>Panel Admin</span>
            </div>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user']['nama'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['user']['nama']) ?></div>
            <div class="role"><i class="fas fa-shield-alt" style="margin-right:4px;"></i>Administrator</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Dashboard</div>

        <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <hr class="nav-divider">
        <div class="nav-section-label">Manajemen</div>

        <a href="topup.php" class="nav-item <?= $current_page === 'topup.php' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i>
            <span>Konfirmasi Top Up</span>
            <?php if ($topup_pending > 0): ?>
                <span class="nav-badge"><?= $topup_pending ?></span>
            <?php endif; ?>
        </a>

        <a href="mahasiswa.php" class="nav-item <?= $current_page === 'mahasiswa.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Data Mahasiswa</span>
        </a>

        <a href="penjual.php" class="nav-item <?= $current_page === 'penjual.php' ? 'active' : '' ?>">
            <i class="fas fa-store"></i>
            <span>Data Penjual</span>
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
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php"); // Sesuaikan jumlah ../ dengan kedalaman folder
    exit;
}

require_once '../../includes/db.php';
require_once '../../includes/sidebar_admin.php';

// Statistik umum
$total_mahasiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
$total_penjual   = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'penjual'")->fetchColumn();
$topup_menunggu  = $pdo->query("SELECT COUNT(*) FROM topup WHERE status = 'menunggu'")->fetchColumn();
$total_saldo     = $pdo->query("SELECT COALESCE(SUM(saldo),0) FROM saldo")->fetchColumn();

// Total top up diterima hari ini
$topup_hari = $pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM topup WHERE status='diterima' AND DATE(tanggal)=CURDATE()")->fetchColumn();

// Top up terbaru (5)
$stmt = $pdo->query("
    SELECT t.*, u.nama, u.nim
    FROM topup t
    JOIN users u ON t.id_user = u.id_user
    ORDER BY t.tanggal DESC
    LIMIT 5
");
$topup_terbaru = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt" style="color:#3949ab;margin-right:10px;"></i>Dashboard Admin</h1>
    <p>Selamat datang, <?= htmlspecialchars($_SESSION['user']['nama']) ?> — <?= date('l, d F Y') ?></p>
</div>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; margin-bottom: 28px; }
    .stat-card {
        background: white; border-radius: 16px; padding: 22px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        display: flex; align-items: center; gap: 16px;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.09); }
    .stat-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
    }
    .stat-info .label { color: #888; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-info .value { color: #1a1a1a; font-size: 24px; font-weight: 800; margin-top: 3px; }
    .stat-info .sub { color: #aaa; font-size: 11.5px; margin-top: 2px; }
    .bg-blue { background: #e3f2fd; color: #1565c0; }
    .bg-green { background: #e8f5e9; color: #2e7d32; }
    .bg-orange { background: #fff3e0; color: #e65100; }
    .bg-purple { background: #f3e5f5; color: #6a1b9a; }
    .bg-teal { background: #e0f2f1; color: #00695c; }

    .dash-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 24px; }
    .dash-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f0f0f0;
        display: flex; align-items: center; justify-content: space-between;
    }
    .dash-card-header h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; }
    .dash-card-header a { font-size: 12.5px; color: #3949ab; font-weight: 600; text-decoration: none; }
    .dash-card-header a:hover { text-decoration: underline; }

    .topup-item {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 22px; border-bottom: 1px solid #f5f5f5;
        transition: background 0.15s;
    }
    .topup-item:last-child { border-bottom: none; }
    .topup-item:hover { background: #fafafa; }
    .topup-avatar {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #3949ab, #5c6bc0);
        color: white; font-weight: 700; font-size: 14px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .topup-info { flex: 1; }
    .topup-info .name { font-size: 13.5px; font-weight: 600; color: #222; }
    .topup-info .nim { font-size: 12px; color: #aaa; margin-top: 2px; }
    .topup-right { text-align: right; flex-shrink: 0; }
    .topup-right .jumlah { font-size: 14px; font-weight: 800; color: #1a237e; }
    .topup-right .time { font-size: 11px; color: #bbb; margin-top: 2px; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-menunggu { background: #fff3e0; color: #e65100; }
    .badge-diterima { background: #e8f5e9; color: #2e7d32; }
    .badge-ditolak  { background: #fce4ec; color: #c62828; }

    .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 12px; margin-bottom: 28px; }
    .qa-btn {
        background: white; border-radius: 14px; padding: 18px 16px;
        text-align: center; text-decoration: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.2s; border: 1.5px solid transparent; display: block;
    }
    .qa-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); border-color: #e8eaf6; }
    .qa-btn i { font-size: 24px; margin-bottom: 8px; display: block; }
    .qa-btn span { font-size: 13px; font-weight: 600; color: #333; }
    .qa-blue i { color: #3949ab; }
    .qa-orange i { color: #e65100; }
    .qa-green i { color: #2e7d32; }
    .qa-purple i { color: #6a1b9a; }

    .empty-box { text-align: center; padding: 40px 20px; color: #ccc; }
    .empty-box i { font-size: 36px; margin-bottom: 10px; display: block; }
    .empty-box p { font-size: 13px; }
</style>

<!-- Statistik -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-blue"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <div class="label">Total Mahasiswa</div>
            <div class="value"><?= number_format($total_mahasiswa) ?></div>
            <div class="sub">pengguna terdaftar</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-teal"><i class="fas fa-store"></i></div>
        <div class="stat-info">
            <div class="label">Total Penjual</div>
            <div class="value"><?= number_format($total_penjual) ?></div>
            <div class="sub">kantin aktif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-orange"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="label">Top Up Menunggu</div>
            <div class="value"><?= number_format($topup_menunggu) ?></div>
            <div class="sub">perlu dikonfirmasi</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <div class="label">Top Up Hari Ini</div>
            <div class="value" style="font-size:18px;">Rp <?= number_format($topup_hari, 0, ',', '.') ?></div>
            <div class="sub">sudah dikonfirmasi</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple"><i class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <div class="label">Total Saldo Beredar</div>
            <div class="value" style="font-size:18px;">Rp <?= number_format($total_saldo, 0, ',', '.') ?></div>
            <div class="sub">seluruh mahasiswa</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="topup.php" class="qa-btn qa-orange">
        <i class="fas fa-check-circle"></i>
        <span>Konfirmasi Top Up</span>
        <?php if ($topup_menunggu > 0): ?>
            <div style="margin-top:6px;display:inline-block;background:#ff4444;color:white;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;"><?= $topup_menunggu ?> menunggu</div>
        <?php endif; ?>
    </a>
    <a href="mahasiswa.php" class="qa-btn qa-blue">
        <i class="fas fa-users"></i>
        <span>Data Mahasiswa</span>
    </a>
    <a href="penjual.php" class="qa-btn qa-teal">
        <i class="fas fa-store"></i>
        <span>Data Penjual</span>
    </a>
</div>

<!-- Top Up Terbaru -->
<div class="dash-card">
    <div class="dash-card-header">
        <h3><i class="fas fa-wallet" style="color:#3949ab;margin-right:8px;"></i>Pengajuan Top Up Terbaru</h3>
        <a href="topup.php">Lihat Semua →</a>
    </div>
    <?php if (empty($topup_terbaru)): ?>
        <div class="empty-box">
            <i class="fas fa-inbox"></i>
            <p>Belum ada pengajuan top up</p>
        </div>
    <?php else: ?>
        <?php foreach ($topup_terbaru as $t): ?>
        <div class="topup-item">
            <div class="topup-avatar"><?= strtoupper(substr($t['nama'], 0, 1)) ?></div>
            <div class="topup-info">
                <div class="name"><?= htmlspecialchars($t['nama']) ?></div>
                <div class="nim">NIM: <?= htmlspecialchars($t['nim'] ?? '-') ?> · <?= str_replace('_', ' ', ucfirst($t['metode'])) ?></div>
            </div>
            <div class="topup-right">
                <div class="jumlah">Rp <?= number_format($t['jumlah'], 0, ',', '.') ?></div>
                <div class="time">
                    <span class="badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div></body></html>
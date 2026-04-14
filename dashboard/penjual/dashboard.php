<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penjual') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_penjual = $_SESSION['penjual']['id_penjual'] ?? 0;

// Statistik hari ini
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(CASE WHEN status='selesai' THEN total_harga ELSE 0 END) as pendapatan,
        SUM(CASE WHEN status='menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status='diproses' THEN 1 ELSE 0 END) as diproses
    FROM pesanan 
    WHERE id_penjual = ? AND DATE(tanggal) = CURDATE()
");
$stmt->execute([$id_penjual]);
$stats_hari = $stmt->fetch();

// Statistik bulan ini
$stmt2 = $pdo->prepare("
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(CASE WHEN status='selesai' THEN total_harga ELSE 0 END) as pendapatan
    FROM pesanan 
    WHERE id_penjual = ? AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())
");
$stmt2->execute([$id_penjual]);
$stats_bulan = $stmt2->fetch();

// Total menu aktif
$stmt3 = $pdo->prepare("SELECT COUNT(*) as total FROM menu WHERE id_penjual = ?");
$stmt3->execute([$id_penjual]);
$total_menu = $stmt3->fetchColumn();

// Pesanan terbaru (5)
$stmt4 = $pdo->prepare("
    SELECT p.*, u.nama, u.nim,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' x', dp.jumlah) SEPARATOR ', ') as items
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    WHERE p.id_penjual = ?
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal DESC
    LIMIT 5
");
$stmt4->execute([$id_penjual]);
$pesanan_terbaru = $stmt4->fetchAll();

// Menu terlaris
$stmt5 = $pdo->prepare("
    SELECT m.nama_menu, m.harga, SUM(dp.jumlah) as total_terjual
    FROM detail_pesanan dp
    JOIN menu m ON dp.id_menu = m.id_menu
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    WHERE p.id_penjual = ? AND p.status = 'selesai'
    GROUP BY m.id_menu
    ORDER BY total_terjual DESC
    LIMIT 5
");
$stmt5->execute([$id_penjual]);
$menu_terlaris = $stmt5->fetchAll();
?>

<?php require_once '../../includes/sidebar_penjual.php'; ?>

<div class="page-header">
    <h1>👋 Halo, <?= htmlspecialchars($_SESSION['user']['nama']) ?>!</h1>
    <p>Selamat datang di dashboard <?= htmlspecialchars($_SESSION['penjual']['nama_kantin'] ?? '') ?> — <?= date('l, d F Y') ?></p>
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

    .dashboard-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; }
    @media(max-width:900px) { .dashboard-grid { grid-template-columns: 1fr; } }

    .dash-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
    .dash-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f0f0f0;
        display: flex; align-items: center; justify-content: space-between;
    }
    .dash-card-header h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; }
    .dash-card-header a { font-size: 12.5px; color: #3949ab; font-weight: 600; text-decoration: none; }
    .dash-card-header a:hover { text-decoration: underline; }
    .dash-card-body { padding: 0; }

    /* Pesanan terbaru */
    .order-item {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 22px; border-bottom: 1px solid #f5f5f5;
        transition: background 0.15s;
    }
    .order-item:last-child { border-bottom: none; }
    .order-item:hover { background: #fafafa; }
    .order-num {
        width: 36px; height: 36px; border-radius: 10px;
        background: #e8eaf6; color: #3949ab;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800; flex-shrink: 0;
    }
    .order-info { flex: 1; min-width: 0; }
    .order-info .name { font-size: 13.5px; font-weight: 600; color: #222; }
    .order-info .items { font-size: 12px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .order-right { text-align: right; flex-shrink: 0; }
    .order-right .harga { font-size: 13.5px; font-weight: 700; color: #1a237e; }
    .order-right .time { font-size: 11px; color: #bbb; margin-top: 2px; }
    .status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
    .dot-menunggu { background: #ff9800; }
    .dot-diproses { background: #2196f3; }
    .dot-selesai { background: #4caf50; }
    .dot-batal { background: #f44336; }

    /* Menu terlaris */
    .menu-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 22px; border-bottom: 1px solid #f5f5f5;
    }
    .menu-item:last-child { border-bottom: none; }
    .menu-rank {
        width: 28px; height: 28px; border-radius: 8px;
        background: linear-gradient(135deg, #ff6b35, #f7931e);
        color: white; font-size: 12px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .menu-rank.rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
    .menu-rank.rank-2 { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); }
    .menu-rank.rank-3 { background: linear-gradient(135deg, #CD7F32, #A0522D); }
    .menu-info { flex: 1; }
    .menu-info .nama { font-size: 13.5px; font-weight: 600; color: #222; }
    .menu-info .harga { font-size: 12px; color: #888; }
    .menu-terjual { font-size: 13px; font-weight: 700; color: #3949ab; }

    .empty-box { text-align: center; padding: 40px 20px; color: #ccc; }
    .empty-box i { font-size: 36px; margin-bottom: 10px; display: block; }
    .empty-box p { font-size: 13px; }

    /* Quick actions */
    .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: 12px; margin-bottom: 28px; }
    .qa-btn {
        background: white; border-radius: 14px; padding: 18px 16px;
        text-align: center; text-decoration: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.2s; border: 1.5px solid transparent;
    }
    .qa-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); border-color: #e8eaf6; }
    .qa-btn i { font-size: 24px; margin-bottom: 8px; display: block; }
    .qa-btn span { font-size: 13px; font-weight: 600; color: #333; }
    .qa-blue i { color: #3949ab; }
    .qa-green i { color: #2e7d32; }
    .qa-orange i { color: #e65100; }
    .qa-teal i { color: #00695c; }
</style>

<!-- Statistik Hari Ini -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-orange"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="label">Menunggu</div>
            <div class="value"><?= $stats_hari['menunggu'] ?? 0 ?></div>
            <div class="sub">pesanan hari ini</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue"><i class="fas fa-spinner"></i></div>
        <div class="stat-info">
            <div class="label">Diproses</div>
            <div class="value"><?= $stats_hari['diproses'] ?? 0 ?></div>
            <div class="sub">sedang disiapkan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <div class="label">Pendapatan Hari Ini</div>
            <div class="value" style="font-size:18px;">Rp <?= number_format($stats_hari['pendapatan'] ?? 0, 0, ',', '.') ?></div>
            <div class="sub"><?= $stats_hari['total_pesanan'] ?? 0 ?> pesanan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple"><i class="fas fa-chart-bar"></i></div>
        <div class="stat-info">
            <div class="label">Pendapatan Bulan Ini</div>
            <div class="value" style="font-size:18px;">Rp <?= number_format($stats_bulan['pendapatan'] ?? 0, 0, ',', '.') ?></div>
            <div class="sub"><?= $stats_bulan['total_pesanan'] ?? 0 ?> pesanan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-teal"><i class="fas fa-book-open"></i></div>
        <div class="stat-info">
            <div class="label">Total Menu</div>
            <div class="value"><?= $total_menu ?></div>
            <div class="sub">menu tersedia</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="pesanan.php" class="qa-btn qa-orange">
        <i class="fas fa-shopping-bag"></i>
        <span>Lihat Pesanan</span>
    </a>
    <a href="menu.php" class="qa-btn qa-blue">
        <i class="fas fa-plus-circle"></i>
        <span>Tambah Menu</span>
    </a>
    <a href="riwayat.php" class="qa-btn qa-green">
        <i class="fas fa-history"></i>
        <span>Riwayat</span>
    </a>
    <a href="pengaturan.php" class="qa-btn qa-teal">
        <i class="fas fa-cog"></i>
        <span>Pengaturan</span>
    </a>
</div>

<!-- Grid bawah -->
<div class="dashboard-grid">

    <!-- Pesanan Terbaru -->
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-receipt" style="color:#3949ab;margin-right:8px;"></i>Pesanan Terbaru</h3>
            <a href="pesanan.php">Lihat Semua →</a>
        </div>
        <div class="dash-card-body">
            <?php if (empty($pesanan_terbaru)): ?>
                <div class="empty-box">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada pesanan masuk</p>
                </div>
            <?php else: ?>
                <?php foreach ($pesanan_terbaru as $p): ?>
                <div class="order-item">
                    <div class="order-num">#<?= $p['id_pesanan'] ?></div>
                    <div class="order-info">
                        <div class="name">
                            <span class="status-dot dot-<?= $p['status'] ?>"></span>
                            <?= htmlspecialchars($p['nama']) ?>
                        </div>
                        <div class="items"><?= htmlspecialchars($p['items'] ?? '-') ?></div>
                    </div>
                    <div class="order-right">
                        <div class="harga">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></div>
                        <div class="time"><?= date('H:i', strtotime($p['tanggal'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menu Terlaris -->
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-fire" style="color:#ff6b35;margin-right:8px;"></i>Menu Terlaris</h3>
            <a href="menu.php">Kelola Menu →</a>
        </div>
        <div class="dash-card-body">
            <?php if (empty($menu_terlaris)): ?>
                <div class="empty-box">
                    <i class="fas fa-utensils"></i>
                    <p>Belum ada data penjualan</p>
                </div>
            <?php else: ?>
                <?php foreach ($menu_terlaris as $i => $m): ?>
                <div class="menu-item">
                    <div class="menu-rank rank-<?= $i+1 ?>"><?= $i+1 ?></div>
                    <div class="menu-info">
                        <div class="nama"><?= htmlspecialchars($m['nama_menu']) ?></div>
                        <div class="harga">Rp <?= number_format($m['harga'], 0, ',', '.') ?></div>
                    </div>
                    <div class="menu-terjual"><?= $m['total_terjual'] ?>x</div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

</div><!-- end main-content -->
</body>
</html>
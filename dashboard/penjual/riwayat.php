<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penjual') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_penjual = $_SESSION['penjual']['id_penjual'] ?? 0;

// Filter
$status_filter = $_GET['status'] ?? '';
$tanggal_dari = $_GET['dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['sampai'] ?? date('Y-m-d');

$where = "WHERE p.id_penjual = ?";
$params = [$id_penjual];

if ($status_filter !== '') {
    $where .= " AND p.status = ?";
    $params[] = $status_filter;
}
$where .= " AND DATE(p.tanggal) BETWEEN ? AND ?";
$params[] = $tanggal_dari;
$params[] = $tanggal_sampai;

// Total stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(CASE WHEN status='selesai' THEN total_harga ELSE 0 END) as total_pendapatan,
        SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) as pesanan_selesai,
        SUM(CASE WHEN status='batal' THEN 1 ELSE 0 END) as pesanan_batal
    FROM pesanan p $where
");
$stmt->execute($params);
$stats = $stmt->fetch();

// Data pesanan
$stmt2 = $pdo->prepare("
    SELECT p.*, u.nama, u.nim,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' x', dp.jumlah) SEPARATOR ', ') as items
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    $where
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal DESC
");
$stmt2->execute($params);
$pesanan_list = $stmt2->fetchAll();
?>

<?php require_once '../../includes/sidebar_penjual.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-history" style="color:#3949ab; margin-right:10px;"></i>Riwayat Transaksi</h1>
    <p>Lihat semua riwayat pesanan dan pendapatan kantin Anda</p>
</div>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .stat-card .icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
    .stat-card .label { color: #888; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card .value { color: #1a237e; font-size: 22px; font-weight: 800; margin-top: 4px; }
    .bg-blue { background: #e3f2fd; color: #1565c0; }
    .bg-green { background: #e8f5e9; color: #2e7d32; }
    .bg-orange { background: #fff3e0; color: #e65100; }
    .bg-red { background: #fce4ec; color: #c62828; }

    .filter-card { background: white; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .filter-group label { font-size: 12px; font-weight: 600; color: #555; }
    .filter-group select, .filter-group input {
        padding: 9px 14px; border: 1.5px solid #e0e0e0; border-radius: 8px;
        font-size: 13px; color: #333; background: white; outline: none;
        transition: border-color 0.2s;
    }
    .filter-group select:focus, .filter-group input:focus { border-color: #3949ab; }
    .btn-filter {
        padding: 9px 20px; background: #3949ab; color: white;
        border: none; border-radius: 8px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background 0.2s;
    }
    .btn-filter:hover { background: #1a237e; }

    .table-card { background: white; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
    .table-card table { width: 100%; border-collapse: collapse; }
    .table-card th { background: #f8f9ff; color: #3949ab; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; text-align: left; border-bottom: 2px solid #e8eaf6; }
    .table-card td { padding: 14px 16px; font-size: 13.5px; color: #333; border-bottom: 1px solid #f0f0f0; }
    .table-card tr:hover td { background: #f8f9ff; }
    .table-card tr:last-child td { border-bottom: none; }
    .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 600; }
    .status-menunggu { background: #fff3e0; color: #e65100; }
    .status-diproses { background: #e3f2fd; color: #1565c0; }
    .status-selesai { background: #e8f5e9; color: #2e7d32; }
    .status-batal { background: #fce4ec; color: #c62828; }
    .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
    .empty-state i { font-size: 50px; margin-bottom: 14px; }
    .items-text { color: #666; font-size: 12.5px; margin-top: 2px; }
</style>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon bg-blue"><i class="fas fa-receipt"></i></div>
        <div class="label">Total Pesanan</div>
        <div class="value"><?= $stats['total_pesanan'] ?></div>
    </div>
    <div class="stat-card">
        <div class="icon bg-green"><i class="fas fa-money-bill-wave"></i></div>
        <div class="label">Total Pendapatan</div>
        <div class="value" style="font-size:17px;">Rp <?= number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="stat-card">
        <div class="icon bg-orange"><i class="fas fa-check-circle"></i></div>
        <div class="label">Pesanan Selesai</div>
        <div class="value"><?= $stats['pesanan_selesai'] ?></div>
    </div>
    <div class="stat-card">
        <div class="icon bg-red"><i class="fas fa-times-circle"></i></div>
        <div class="label">Pesanan Batal</div>
        <div class="value"><?= $stats['pesanan_batal'] ?></div>
    </div>
</div>

<!-- Filter -->
<div class="filter-card">
    <form method="GET" class="filter-row">
        <div class="filter-group">
            <label>Dari Tanggal</label>
            <input type="date" name="dari" value="<?= htmlspecialchars($tanggal_dari) ?>">
        </div>
        <div class="filter-group">
            <label>Sampai Tanggal</label>
            <input type="date" name="sampai" value="<?= htmlspecialchars($tanggal_sampai) ?>">
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="">Semua Status</option>
                <option value="menunggu" <?= $status_filter==='menunggu'?'selected':'' ?>>Menunggu</option>
                <option value="diproses" <?= $status_filter==='diproses'?'selected':'' ?>>Diproses</option>
                <option value="selesai" <?= $status_filter==='selesai'?'selected':'' ?>>Selesai</option>
                <option value="batal" <?= $status_filter==='batal'?'selected':'' ?>>Batal</option>
            </select>
        </div>
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
    </form>
</div>

<!-- Tabel Riwayat -->
<div class="table-card">
    <?php if (empty($pesanan_list)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Tidak ada data transaksi untuk filter ini</p>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Pelanggan</th>
                <th>Item Pesanan</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pesanan_list as $p): ?>
            <tr>
                <td><strong>#<?= $p['id_pesanan'] ?></strong></td>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></div>
                    <div style="color:#888;font-size:12px;">NIM: <?= htmlspecialchars($p['nim'] ?? '-') ?></div>
                </td>
                <td>
                    <div class="items-text"><?= htmlspecialchars($p['items'] ?? '-') ?></div>
                </td>
                <td><strong>Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></strong></td>
                <td>
                    <span class="status-badge status-<?= $p['status'] ?>">
                        <?= ucfirst($p['status']) ?>
                    </span>
                </td>
                <td><?= date('d M Y H:i', strtotime($p['tanggal'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</div><!-- end main-content -->
</body>
</html>
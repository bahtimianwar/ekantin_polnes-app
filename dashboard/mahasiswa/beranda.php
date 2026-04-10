<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];

// Saldo
$stmt = $pdo->prepare("SELECT COALESCE(saldo, 0) as saldo FROM saldo WHERE id_user = ?");
$stmt->execute([$id_user]);
$saldo = $stmt->fetchColumn() ?: 0;

// Pesanan aktif (menunggu/diproses)
$stmt2 = $pdo->prepare("
    SELECT p.*, pj.nama_kantin,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' x', dp.jumlah) SEPARATOR ', ') as items
    FROM pesanan p
    JOIN penjual pj ON p.id_penjual = pj.id_penjual
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    WHERE p.id_user = ? AND p.status IN ('menunggu','diproses')
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal DESC
");
$stmt2->execute([$id_user]);
$pesanan_aktif = $stmt2->fetchAll();

// Semua kantin
$stmt3 = $pdo->query("SELECT pj.*, COUNT(m.id_menu) as total_menu FROM penjual pj LEFT JOIN menu m ON m.id_penjual = pj.id_penjual GROUP BY pj.id_penjual");
$kantin_list = $stmt3->fetchAll();

// Pesanan terakhir
$stmt4 = $pdo->prepare("
    SELECT p.*, pj.nama_kantin
    FROM pesanan p
    JOIN penjual pj ON p.id_penjual = pj.id_penjual
    WHERE p.id_user = ?
    ORDER BY p.tanggal DESC
    LIMIT 3
");
$stmt4->execute([$id_user]);
$pesanan_terakhir = $stmt4->fetchAll();
?>

<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>

<div class="page-header">
    <h1>Halo, <?= htmlspecialchars($_SESSION['user']['nama']) ?>! 👋</h1>
    <p>Mau makan apa hari ini? — <?= date('l, d F Y') ?></p>
</div>

<style>
    /* Saldo Banner */
    .saldo-banner {
        background: linear-gradient(135deg, #064e3b, #059669);
        border-radius: 18px; padding: 24px 28px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 28px; color: white;
        box-shadow: 0 8px 24px rgba(0,105,92,0.3);
    }
    .saldo-banner .label { opacity: 0.75; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }
    .saldo-banner .amount { font-size: 30px; font-weight: 800; margin: 6px 0; }
    .saldo-banner .btn-topup {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.2); color: white;
        padding: 10px 22px; border-radius: 24px;
        font-size: 14px; font-weight: 600; text-decoration: none;
        transition: background 0.2s; border: 1.5px solid rgba(255,255,255,0.3);
    }
    .saldo-banner .btn-topup:hover { background: rgba(255,255,255,0.3); }
    .saldo-banner .icon { font-size: 50px; opacity: 0.2; }

    /* Pesanan Aktif */
    .active-orders { margin-bottom: 28px; }
    .section-title { font-size: 16px; font-weight: 700; color: #222; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .section-title a { margin-left: auto; font-size: 13px; color: #065f46; font-weight: 600; text-decoration: none; }
    .section-title a:hover { text-decoration: underline; }

    .active-order-card {
        background: white; border-radius: 14px; padding: 16px 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        display: flex; align-items: center; gap: 14px;
        margin-bottom: 10px; border-left: 4px solid #ff9800;
    }
    .active-order-card.diproses { border-left-color: #2196f3; }
    .ao-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .ao-icon.menunggu { background: #fff3e0; color: #e65100; }
    .ao-icon.diproses { background: #e3f2fd; color: #1565c0; }
    .ao-info { flex: 1; }
    .ao-info .kantin { font-weight: 700; font-size: 14px; color: #222; }
    .ao-info .items { font-size: 12.5px; color: #888; margin-top: 3px; }
    .ao-right .status { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 12px; }
    .status-menunggu { background: #fff3e0; color: #e65100; }
    .status-diproses { background: #e3f2fd; color: #1565c0; }
    .ao-right .harga { font-size: 14px; font-weight: 800; color: #064e3b; margin-top: 4px; text-align: right; }

    /* Kantin Grid */
    .kantin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 16px; margin-bottom: 28px; }
    .kantin-card {
        background: white; border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden; text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .kantin-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .kantin-thumb {
        height: 100px;
        background: linear-gradient(135deg, #064e3b, #059669);
        display: flex; align-items: center; justify-content: center;
        font-size: 40px;
    }
    .kantin-info { padding: 14px 16px; }
    .kantin-info .nama { font-size: 15px; font-weight: 700; color: #222; }
    .kantin-info .lokasi { font-size: 12px; color: #888; margin-top: 3px; }
    .kantin-info .meta { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; }
    .kantin-info .menu-count { font-size: 12px; color: #065f46; font-weight: 600; background: #d1fae5; padding: 3px 10px; border-radius: 12px; }
    .kantin-info .btn-pesan { font-size: 12px; font-weight: 600; color: white; background: #065f46; padding: 5px 14px; border-radius: 10px; }

    /* Riwayat singkat */
    .riwayat-mini { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
    .riwayat-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #f5f5f5; }
    .riwayat-item:last-child { border-bottom: none; }
    .ri-icon { width: 38px; height: 38px; border-radius: 10px; background: #d1fae5; color: #065f46; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .ri-info { flex: 1; }
    .ri-info .kantin { font-size: 13.5px; font-weight: 600; color: #222; }
    .ri-info .date { font-size: 12px; color: #aaa; margin-top: 2px; }
    .ri-right .harga { font-size: 14px; font-weight: 700; color: #064e3b; }
    .ri-right .status { font-size: 11px; padding: 3px 10px; border-radius: 10px; font-weight: 600; margin-top: 3px; display: inline-block; }

    .empty-box { text-align: center; padding: 30px; color: #ccc; }
    .empty-box i { font-size: 32px; margin-bottom: 8px; display: block; }
    .empty-box p { font-size: 13px; }
</style>

<!-- Saldo Banner -->
<div class="saldo-banner">
    <div>
        <div class="label">Saldo Kamu</div>
        <div class="amount">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
        <a href="topup.php" class="btn-topup"><i class="fas fa-plus-circle"></i> Top Up Saldo</a>
    </div>
    <div class="icon"><i class="fas fa-wallet"></i></div>
</div>

<!-- Pesanan Aktif -->
<?php if (!empty($pesanan_aktif)): ?>
<div class="active-orders">
    <div class="section-title">
        <i class="fas fa-fire" style="color:#ff6b35;"></i> Pesanan Aktif
        <a href="riwayat.php">Lihat Semua →</a>
    </div>
    <?php foreach ($pesanan_aktif as $p): ?>
    <div class="active-order-card <?= $p['status'] ?>">
        <div class="ao-icon <?= $p['status'] ?>">
            <i class="fas <?= $p['status']==='menunggu' ? 'fa-clock' : 'fa-fire' ?>"></i>
        </div>
        <div class="ao-info">
            <div class="kantin"><?= htmlspecialchars($p['nama_kantin']) ?></div>
            <div class="items"><?= htmlspecialchars($p['items'] ?? '-') ?></div>
        </div>
        <div class="ao-right">
            <div class="status status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></div>
            <div class="harga">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Daftar Kantin -->
<div class="section-title">
    <i class="fas fa-store" style="color:#065f46;"></i> Pilih Kantin
</div>
<div class="kantin-grid">
    <?php if (empty($kantin_list)): ?>
        <div class="empty-box"><i class="fas fa-store-slash"></i><p>Belum ada kantin tersedia</p></div>
    <?php else: ?>
    <?php foreach ($kantin_list as $k): ?>
    <a href="kantin.php?id=<?= $k['id_penjual'] ?>" class="kantin-card">
        <div class="kantin-thumb">🍽️</div>
        <div class="kantin-info">
            <div class="nama"><?= htmlspecialchars($k['nama_kantin']) ?></div>
            <div class="lokasi"><i class="fas fa-map-marker-alt" style="color:#e53935;margin-right:4px;"></i><?= htmlspecialchars($k['lokasi']) ?></div>
            <div class="meta">
                <span class="menu-count"><?= $k['total_menu'] ?> menu</span>
                <span class="btn-pesan">Pesan →</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Riwayat Terakhir -->
<div class="section-title">
    <i class="fas fa-history" style="color:#065f46;"></i> Pesanan Terakhir
    <a href="riwayat.php">Lihat Semua →</a>
</div>
<div class="riwayat-mini">
    <?php if (empty($pesanan_terakhir)): ?>
        <div class="empty-box">
            <i class="fas fa-shopping-bag"></i>
            <p>Belum pernah pesan</p>
        </div>
    <?php else: ?>
    <?php foreach ($pesanan_terakhir as $p): ?>
    <div class="riwayat-item">
        <div class="ri-icon"><i class="fas fa-receipt"></i></div>
        <div class="ri-info">
            <div class="kantin"><?= htmlspecialchars($p['nama_kantin']) ?></div>
            <div class="date"><?= date('d M Y, H:i', strtotime($p['tanggal'])) ?></div>
        </div>
        <div class="ri-right">
            <div class="harga">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></div>
            <span class="status status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

</div><!-- end main-content -->
</body>
</html>
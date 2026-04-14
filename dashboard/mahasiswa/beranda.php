<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];

$stmt = $pdo->prepare("SELECT COALESCE(saldo, 0) as saldo FROM saldo WHERE id_user = ?");
$stmt->execute([$id_user]);
$saldo = $stmt->fetchColumn() ?: 0;

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

$stmt3 = $pdo->query("SELECT pj.*, COUNT(m.id_menu) as total_menu FROM penjual pj LEFT JOIN menu m ON m.id_penjual = pj.id_penjual GROUP BY pj.id_penjual");
$kantin_list = $stmt3->fetchAll();

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
    .saldo-banner {
        background: linear-gradient(135deg, var(--ac-dk), var(--ac));
        border-radius: 18px; padding: 24px 28px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 28px; color: white;
        box-shadow: 0 8px 28px rgba(79,70,229,0.3);
        position: relative; overflow: hidden;
    }
    [data-theme="dark"] .saldo-banner {
        box-shadow: 0 8px 36px rgba(99,102,241,0.4);
    }
    .saldo-banner::before {
        content: '';
        position: absolute; top: -40px; right: -40px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .saldo-banner .label { opacity: .7; font-size: 11.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
    .saldo-banner .amount { font-size: 30px; font-weight: 800; margin: 6px 0; }
    .saldo-banner .btn-topup {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,.18); color: white;
        padding: 10px 22px; border-radius: 24px;
        font-size: 13.5px; font-weight: 600; text-decoration: none;
        transition: background .2s; border: 1.5px solid rgba(255,255,255,.25);
    }
    .saldo-banner .btn-topup:hover { background: rgba(255,255,255,.28); }
    .saldo-banner .icon { font-size: 52px; opacity: .15; position: relative; z-index: 1; }

    .section-title { font-size: 15.5px; font-weight: 700; color: var(--text-1); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .section-title a { margin-left: auto; font-size: 13px; color: var(--ac-tx); font-weight: 600; text-decoration: none; }
    .section-title a:hover { text-decoration: underline; }

    .active-order-card {
        background: var(--bg-card); border-radius: 14px; padding: 16px 20px;
        box-shadow: var(--shadow);
        display: flex; align-items: center; gap: 14px;
        margin-bottom: 10px; border-left: 4px solid #f59e0b;
        transition: box-shadow .2s;
    }
    .active-order-card:hover { box-shadow: var(--shadow-h); }
    .active-order-card.diproses { border-left-color: #3b82f6; }
    .ao-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .ao-icon.menunggu { background: #fff8e1; color: #e65100; }
    .ao-icon.diproses { background: #e3f2fd; color: #1565c0; }
    [data-theme="dark"] .ao-icon.menunggu { background: rgba(245,158,11,.15); color: #fbbf24; }
    [data-theme="dark"] .ao-icon.diproses { background: rgba(59,130,246,.15); color: #60a5fa; }
    .ao-info { flex: 1; }
    .ao-info .kantin { font-weight: 700; font-size: 14px; color: var(--text-1); }
    .ao-info .items { font-size: 12.5px; color: var(--text-3); margin-top: 3px; }
    .ao-right .status { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 12px; }
    .status-menunggu { background: #fff3e0; color: #e65100; }
    .status-diproses { background: #e3f2fd; color: #1565c0; }
    [data-theme="dark"] .status-menunggu { background: rgba(245,158,11,.15); color: #fbbf24; }
    [data-theme="dark"] .status-diproses { background: rgba(59,130,246,.15); color: #60a5fa; }
    .ao-right .harga { font-size: 14px; font-weight: 800; color: var(--ac-tx); margin-top: 4px; text-align: right; }

    .kantin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px,1fr)); gap: 16px; margin-bottom: 28px; }
    .kantin-card {
        background: var(--bg-card); border-radius: 16px;
        box-shadow: var(--shadow); overflow: hidden;
        text-decoration: none; transition: transform .2s, box-shadow .2s;
        border: 1px solid var(--border);
    }
    .kantin-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-h); }
    .kantin-thumb {
        height: 100px;
        background: linear-gradient(135deg, var(--ac-dk), var(--ac));
        display: flex; align-items: center; justify-content: center;
        font-size: 40px;
    }
    [data-theme="dark"] .kantin-thumb { background: linear-gradient(135deg, #1e2040, #2d2f5e); }
    .kantin-info { padding: 14px 16px; }
    .kantin-info .nama { font-size: 14.5px; font-weight: 700; color: var(--text-1); }
    .kantin-info .lokasi { font-size: 12px; color: var(--text-3); margin-top: 3px; }
    .kantin-info .meta { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; }
    .kantin-info .menu-count { font-size: 12px; color: var(--ac-tx); font-weight: 600; background: var(--ac-lt); padding: 3px 10px; border-radius: 12px; }
    .kantin-info .btn-pesan { font-size: 12px; font-weight: 600; color: white; background: var(--ac); padding: 5px 14px; border-radius: 10px; }

    .riwayat-mini { background: var(--bg-card); border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; border: 1px solid var(--border); }
    .riwayat-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--border); }
    .riwayat-item:last-child { border-bottom: none; }
    .ri-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--ac-lt); color: var(--ac-tx); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .ri-info { flex: 1; }
    .ri-info .kantin { font-size: 13.5px; font-weight: 600; color: var(--text-1); }
    .ri-info .date { font-size: 12px; color: var(--text-3); margin-top: 2px; }
    .ri-right .harga { font-size: 14px; font-weight: 700; color: var(--ac-tx); }
    .ri-right .status { font-size: 11px; padding: 3px 10px; border-radius: 10px; font-weight: 600; margin-top: 3px; display: inline-block; }
    .empty-box { text-align: center; padding: 30px; color: var(--text-3); }
    .empty-box i { font-size: 32px; margin-bottom: 8px; display: block; opacity: .4; }
</style>

<div class="saldo-banner">
    <div>
        <div class="label">Saldo Kamu</div>
        <div class="amount">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
        <a href="topup.php" class="btn-topup"><i class="fas fa-plus-circle"></i> Top Up Saldo</a>
    </div>
    <div class="icon"><i class="fas fa-wallet"></i></div>
</div>

<?php if (!empty($pesanan_aktif)): ?>
<div style="margin-bottom:28px;">
    <div class="section-title">
        <i class="fas fa-fire" style="color:#f97316;"></i> Pesanan Aktif
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

<div class="section-title">
    <i class="fas fa-store" style="color:var(--ac);"></i> Pilih Kantin
</div>
<div class="kantin-grid">
    <?php if (empty($kantin_list)): ?>
        <div class="empty-box"><i class="fas fa-store-slash"></i><p>Belum ada kantin</p></div>
    <?php else: ?>
    <?php foreach ($kantin_list as $k): ?>
    <a href="kantin.php?id=<?= $k['id_penjual'] ?>" class="kantin-card">
        <div class="kantin-thumb">🍽️</div>
        <div class="kantin-info">
            <div class="nama"><?= htmlspecialchars($k['nama_kantin']) ?></div>
            <div class="lokasi"><i class="fas fa-map-marker-alt" style="color:#ef4444;margin-right:4px;"></i><?= htmlspecialchars($k['lokasi']) ?></div>
            <div class="meta">
                <span class="menu-count"><?= $k['total_menu'] ?> menu</span>
                <span class="btn-pesan">Pesan →</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="section-title">
    <i class="fas fa-history" style="color:var(--ac);"></i> Pesanan Terakhir
    <a href="riwayat.php">Lihat Semua →</a>
</div>
<div class="riwayat-mini">
    <?php if (empty($pesanan_terakhir)): ?>
        <div class="empty-box"><i class="fas fa-shopping-bag"></i><p>Belum pernah pesan</p></div>
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

</div></body></html>
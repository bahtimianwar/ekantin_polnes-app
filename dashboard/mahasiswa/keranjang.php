<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: /e-kantin/login.php'); exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];

// Ambil saldo
$stmt = $pdo->prepare("SELECT COALESCE(saldo,0) as saldo FROM saldo WHERE id_user=?");
$stmt->execute([$id_user]);
$saldo = $stmt->fetchColumn() ?: 0;

// Ambil semua kantin
$stmt2 = $pdo->query("SELECT pj.*, COUNT(m.id_menu) as total_menu FROM penjual pj LEFT JOIN menu m ON m.id_penjual = pj.id_penjual GROUP BY pj.id_penjual");
$kantin_list = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin</title>
</head>
<body>
<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-shopping-cart" style="color:#065f46;margin-right:10px;"></i>Keranjang</h1>
    <p>Pilih kantin untuk mulai memesan</p>
</div>

<style>
    .info-box { background:linear-gradient(135deg,#064e3b,#059669); border-radius:16px; padding:24px 28px; color:white; margin-bottom:28px; display:flex; align-items:center; justify-content:space-between; }
    .info-box .label { opacity:.75; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
    .info-box .amount { font-size:28px; font-weight:800; margin:6px 0; }
    .info-box .icon { font-size:48px; opacity:.2; }
    .info-box a { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.2); color:white; padding:8px 18px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; }
    .info-box a:hover { background:rgba(255,255,255,.3); }

    .section-title { font-size:16px; font-weight:700; color:#222; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
    .kantin-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; }
    .kantin-card { background:white; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); overflow:hidden; text-decoration:none; transition:transform .2s,box-shadow .2s; display:block; }
    .kantin-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
    .kantin-thumb { height:110px; background:linear-gradient(135deg,#064e3b,#059669); display:flex; align-items:center; justify-content:center; font-size:44px; }
    .kantin-info { padding:16px; }
    .kantin-info .nama { font-size:15px; font-weight:700; color:#222; margin-bottom:4px; }
    .kantin-info .lokasi { font-size:12.5px; color:#888; margin-bottom:12px; }
    .kantin-info .meta { display:flex; align-items:center; justify-content:space-between; }
    .menu-count { font-size:12px; color:#065f46; font-weight:600; background:#d1fae5; padding:3px 10px; border-radius:12px; }
    .btn-pesan { font-size:13px; font-weight:700; color:white; background:linear-gradient(135deg,#064e3b,#059669); padding:7px 18px; border-radius:10px; }
    .empty-state { text-align:center; padding:60px 20px; color:#bbb; }
    .empty-state i { font-size:50px; display:block; margin-bottom:14px; color:#a7f3d0; }
</style>

<!-- Saldo Info -->
<div class="info-box">
    <div>
        <div class="label">Saldo Tersedia</div>
        <div class="amount">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
        <a href="topup.php"><i class="fas fa-plus-circle"></i> Top Up</a>
    </div>
    <div class="icon"><i class="fas fa-wallet"></i></div>
</div>

<!-- Pilih Kantin -->
<div class="section-title">
    <i class="fas fa-store" style="color:#065f46;"></i>
    Pilih Kantin untuk Memesan
</div>

<?php if (empty($kantin_list)): ?>
    <div class="empty-state">
        <i class="fas fa-store-slash"></i>
        <p>Belum ada kantin tersedia</p>
    </div>
<?php else: ?>
<div class="kantin-grid">
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
</div>
<?php endif; ?>

</div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') { header('Location: /e-kantin/login.php'); exit; }
require_once '../../includes/db.php';
$id_user = $_SESSION['user']['id_user'];
$stmt = $pdo->prepare("SELECT COALESCE(saldo,0) as saldo FROM saldo WHERE id_user=?");
$stmt->execute([$id_user]); $saldo = $stmt->fetchColumn() ?: 0;
$stmt2 = $pdo->query("SELECT pj.*, COUNT(m.id_menu) as total_menu FROM penjual pj LEFT JOIN menu m ON m.id_penjual = pj.id_penjual GROUP BY pj.id_penjual");
$kantin_list = $stmt2->fetchAll();
?>
<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>
<div class="page-header">
    <h1><i class="fas fa-shopping-cart" style="color:var(--ac);margin-right:10px;"></i>Keranjang</h1>
    <p>Pilih kantin untuk mulai memesan</p>
</div>
<style>
    .info-box{background:linear-gradient(135deg,var(--ac-dk),var(--ac));border-radius:16px;padding:24px 28px;color:white;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 8px 28px rgba(79,70,229,.3);}
    [data-theme="dark"] .info-box{box-shadow:0 8px 36px rgba(99,102,241,.35);}
    .info-box .label{opacity:.7;font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
    .info-box .amount{font-size:28px;font-weight:800;margin:6px 0;}
    .info-box .icon{font-size:48px;opacity:.15;}
    .info-box a{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:white;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,.22);}
    .info-box a:hover{background:rgba(255,255,255,.28);}
    .section-title{font-size:15.5px;font-weight:700;color:var(--text-1);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .kantin-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;}
    .kantin-card{background:var(--bg-card);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;text-decoration:none;transition:transform .2s,box-shadow .2s;display:block;border:1px solid var(--border);}
    .kantin-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-h);}
    .kantin-thumb{height:110px;background:linear-gradient(135deg,var(--ac-dk),var(--ac));display:flex;align-items:center;justify-content:center;font-size:44px;}
    [data-theme="dark"] .kantin-thumb{background:linear-gradient(135deg,#1a1d3a,#2a2d5e);}
    .kantin-info{padding:16px;}
    .kantin-info .nama{font-size:15px;font-weight:700;color:var(--text-1);margin-bottom:4px;}
    .kantin-info .lokasi{font-size:12.5px;color:var(--text-3);margin-bottom:12px;}
    .kantin-info .meta{display:flex;align-items:center;justify-content:space-between;}
    .menu-count{font-size:12px;color:var(--ac-tx);font-weight:600;background:var(--ac-lt);padding:3px 10px;border-radius:12px;}
    .btn-pesan{font-size:13px;font-weight:700;color:white;background:linear-gradient(135deg,var(--ac-dk),var(--ac));padding:7px 18px;border-radius:10px;}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-3);}
    .empty-state i{font-size:50px;display:block;margin-bottom:14px;opacity:.3;}
</style>
<div class="info-box">
    <div>
        <div class="label">Saldo Tersedia</div>
        <div class="amount">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
        <a href="topup.php"><i class="fas fa-plus-circle"></i> Top Up</a>
    </div>
    <div class="icon"><i class="fas fa-wallet"></i></div>
</div>
<div class="section-title"><i class="fas fa-store" style="color:var(--ac);"></i> Pilih Kantin untuk Memesan</div>
<?php if (empty($kantin_list)): ?>
    <div class="empty-state"><i class="fas fa-store-slash"></i><p>Belum ada kantin</p></div>
<?php else: ?>
<div class="kantin-grid">
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
</div>
<?php endif; ?>
</div></body></html>
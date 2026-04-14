<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') { header('Location: /login.php'); exit; }
require_once '../../includes/db.php';
$id_user = $_SESSION['user']['id_user'];
$sf = $_GET['status'] ?? '';
$where = "WHERE p.id_user = ?"; $params = [$id_user];
if ($sf !== '') { $where .= " AND p.status = ?"; $params[] = $sf; }
$stmt = $pdo->prepare("SELECT p.*, pj.nama_kantin, pj.lokasi, GROUP_CONCAT(CONCAT(m.nama_menu, ' x', dp.jumlah) SEPARATOR ', ') as items FROM pesanan p JOIN penjual pj ON p.id_penjual = pj.id_penjual LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan LEFT JOIN menu m ON dp.id_menu = m.id_menu $where GROUP BY p.id_pesanan ORDER BY p.tanggal DESC");
$stmt->execute($params); $pesanan_list = $stmt->fetchAll();
?>
<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>
<div class="page-header">
    <h1><i class="fas fa-history" style="color:var(--ac);margin-right:10px;"></i>Riwayat Pesanan</h1>
    <p>Lihat semua pesanan yang pernah Anda buat</p>
</div>
<style>
    .filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
    .filter-tab{padding:7px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;border:1.5px solid transparent;}
    .filter-tab.all{background:var(--ac-lt);color:var(--ac-tx);border-color:var(--ac);}
    .filter-tab:not(.all){background:var(--bg-card);color:var(--text-2);border-color:var(--border);}
    .filter-tab:hover,.filter-tab.active{background:var(--ac);color:white;border-color:var(--ac);}
    .order-card{background:var(--bg-card);border-radius:16px;box-shadow:var(--shadow);margin-bottom:14px;overflow:hidden;transition:transform .15s,box-shadow .15s;border:1px solid var(--border);}
    .order-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-h);}
    .order-header{padding:14px 20px;background:var(--bg-card2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
    .order-id{font-weight:800;color:var(--ac-tx);font-size:15px;}
    .order-kantin{color:var(--text-2);font-size:13px;}
    .order-kantin i{color:var(--ac);}
    .status-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
    .status-menunggu{background:#fff3e0;color:#e65100;}
    .status-diproses{background:#e3f2fd;color:#1565c0;}
    .status-selesai{background:#e8f5e9;color:#2e7d32;}
    .status-batal{background:#fce4ec;color:#c62828;}
    [data-theme="dark"] .status-menunggu{background:rgba(245,158,11,.15);color:#fbbf24;}
    [data-theme="dark"] .status-diproses{background:rgba(59,130,246,.15);color:#60a5fa;}
    [data-theme="dark"] .status-selesai{background:rgba(34,197,94,.12);color:#4ade80;}
    [data-theme="dark"] .status-batal{background:rgba(239,68,68,.12);color:#f87171;}
    .order-body{padding:16px 20px;}
    .order-items{color:var(--text-2);font-size:13.5px;margin-bottom:12px;}
    .order-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
    .order-total{font-size:17px;font-weight:800;color:var(--ac-tx);}
    .order-date{color:var(--text-3);font-size:12px;}
    .btn-ulang{padding:7px 16px;background:var(--ac-lt);color:var(--ac-tx);border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;}
    .btn-ulang:hover{background:var(--ac);color:white;}
    .empty-state{text-align:center;padding:80px 20px;color:var(--text-3);}
    .empty-state i{font-size:60px;margin-bottom:16px;display:block;opacity:.25;}
</style>
<div class="filter-tabs">
    <a href="riwayat.php" class="filter-tab all <?=$sf===''?'active':''?>"><i class="fas fa-list"></i> Semua</a>
    <a href="?status=menunggu" class="filter-tab <?=$sf==='menunggu'?'active':''?>">🕐 Menunggu</a>
    <a href="?status=diproses" class="filter-tab <?=$sf==='diproses'?'active':''?>">🔄 Diproses</a>
    <a href="?status=selesai" class="filter-tab <?=$sf==='selesai'?'active':''?>">✅ Selesai</a>
    <a href="?status=batal" class="filter-tab <?=$sf==='batal'?'active':''?>">❌ Batal</a>
</div>
<?php if(empty($pesanan_list)): ?>
<div class="empty-state">
    <i class="fas fa-shopping-bag"></i>
    <p>Belum ada pesanan<?=$sf?' dengan status ini':''?></p>
    <a href="kantin.php" style="display:inline-block;margin-top:16px;padding:10px 24px;background:var(--ac);color:white;border-radius:10px;text-decoration:none;font-weight:600;"><i class="fas fa-store"></i> Mulai Pesan</a>
</div>
<?php else: ?>
<?php foreach($pesanan_list as $p): ?>
<div class="order-card">
    <div class="order-header">
        <div>
            <div class="order-id">#<?=$p['id_pesanan']?></div>
            <div class="order-kantin"><i class="fas fa-store"></i> <?=htmlspecialchars($p['nama_kantin'])?> &bull; <?=htmlspecialchars($p['lokasi'])?></div>
        </div>
        <span class="status-badge status-<?=$p['status']?>">
            <?php $icons=['menunggu'=>'🕐','diproses'=>'🔄','selesai'=>'✅','batal'=>'❌']; echo($icons[$p['status']]??'').' '.ucfirst($p['status']); ?>
        </span>
    </div>
    <div class="order-body">
        <div class="order-items"><i class="fas fa-utensils" style="color:var(--ac);margin-right:6px;"></i><?=htmlspecialchars($p['items']??'-')?></div>
        <div class="order-footer">
            <div class="order-total">Rp <?=number_format($p['total_harga'],0,',','.')?></div>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="order-date"><i class="far fa-clock"></i> <?=date('d M Y H:i',strtotime($p['tanggal']))?></span>
                <?php if($p['status']==='selesai'): ?><a href="kantin.php?id=<?=$p['id_penjual']?>" class="btn-ulang"><i class="fas fa-redo"></i> Pesan Lagi</a><?php endif;?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></body></html>
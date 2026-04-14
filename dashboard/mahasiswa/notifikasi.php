<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') { header('Location: /login.php'); exit; }
require_once '../../includes/db.php';
$id_user = $_SESSION['user']['id_user'];
if ($_GET['action'] ?? '' === 'read_all') { $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE id_user = ?")->execute([$id_user]); header("Location: notifikasi.php"); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM notifikasi WHERE id_notif = ? AND id_user = ?")->execute([$_GET['hapus'], $id_user]); header("Location: notifikasi.php"); exit; }
$stmt = $pdo->prepare("SELECT * FROM notifikasi WHERE id_user = ? ORDER BY tanggal DESC");
$stmt->execute([$id_user]); $notifs = $stmt->fetchAll();
$unread = array_filter($notifs, fn($n) => !$n['is_read']);
?>
<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>
<div class="page-header">
    <h1><i class="fas fa-bell" style="color:var(--ac);margin-right:10px;"></i>Notifikasi</h1>
    <p>Semua pemberitahuan untuk akun Anda</p>
</div>
<style>
    .notif-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
    .notif-count{font-size:14px;color:var(--text-2);}
    .notif-count strong{color:var(--ac-tx);}
    .btn-read-all{padding:8px 18px;background:var(--ac-lt);color:var(--ac-tx);border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .2s;}
    .btn-read-all:hover{background:var(--ac);color:white;}
    .notif-list{display:flex;flex-direction:column;gap:10px;}
    .notif-item{background:var(--bg-card);border-radius:14px;padding:16px 20px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:14px;transition:transform .15s,box-shadow .15s;border:1px solid var(--border);}
    .notif-item:hover{transform:translateY(-2px);box-shadow:var(--shadow-h);}
    .notif-item.unread{border-left:4px solid var(--ac);}
    .notif-item.read{border-left:4px solid transparent;opacity:.7;}
    .notif-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
    .icon-pesanan{background:#e3f2fd;color:#1565c0;}
    .icon-saldo{background:#e8f5e9;color:#2e7d32;}
    .icon-sistem{background:var(--ac-lt);color:var(--ac-tx);}
    [data-theme="dark"] .icon-pesanan{background:rgba(59,130,246,.15);color:#60a5fa;}
    [data-theme="dark"] .icon-saldo{background:rgba(34,197,94,.12);color:#4ade80;}
    .notif-body{flex:1;}
    .notif-judul{font-weight:700;font-size:14px;color:var(--text-1);margin-bottom:4px;}
    .notif-pesan{font-size:13px;color:var(--text-2);line-height:1.5;}
    .notif-time{font-size:11.5px;color:var(--text-3);margin-top:6px;}
    .dot-unread{width:9px;height:9px;border-radius:50%;background:var(--ac);flex-shrink:0;margin-top:4px;}
    .btn-hapus{background:none;border:none;cursor:pointer;color:var(--text-3);font-size:14px;padding:4px;border-radius:6px;transition:color .2s;}
    .btn-hapus:hover{color:#ef4444;}
    .empty-state{text-align:center;padding:80px 20px;color:var(--text-3);}
    .empty-state i{font-size:60px;margin-bottom:16px;display:block;opacity:.25;}
</style>
<div class="notif-toolbar">
    <div class="notif-count">
        <?php if(count($unread)>0): ?><strong><?=count($unread)?></strong> notifikasi belum dibaca<?php else: ?>Semua sudah dibaca<?php endif; ?>
    </div>
    <?php if(count($unread)>0): ?><a href="?action=read_all" class="btn-read-all"><i class="fas fa-check-double"></i> Tandai Semua Dibaca</a><?php endif; ?>
</div>
<?php if(empty($notifs)): ?>
<div class="empty-state"><i class="fas fa-bell-slash"></i><p>Belum ada notifikasi</p></div>
<?php else: ?>
<div class="notif-list">
    <?php foreach($notifs as $n):
        $ic='icon-sistem'; $in='fa-info-circle';
        if(stripos($n['judul'],'pesanan')!==false){$ic='icon-pesanan';$in='fa-shopping-bag';}
        elseif(stripos($n['judul'],'saldo')!==false||stripos($n['judul'],'top up')!==false){$ic='icon-saldo';$in='fa-wallet';}
        $waktu=new DateTime($n['tanggal']);$now=new DateTime();$diff=$now->diff($waktu);
        if($diff->days>0)$ts=$diff->days.' hari lalu';
        elseif($diff->h>0)$ts=$diff->h.' jam lalu';
        else $ts=max($diff->i,1).' menit lalu';
    ?>
    <div class="notif-item <?=$n['is_read']?'read':'unread'?>">
        <div class="notif-icon <?=$ic?>"><i class="fas <?=$in?>"></i></div>
        <div class="notif-body">
            <div class="notif-judul"><?=htmlspecialchars($n['judul'])?></div>
            <div class="notif-pesan"><?=htmlspecialchars($n['pesan'])?></div>
            <div class="notif-time"><i class="far fa-clock"></i> <?=$ts?> · <?=date('d M Y H:i',strtotime($n['tanggal']))?></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-left:auto;flex-shrink:0;">
            <?php if(!$n['is_read']):?><div class="dot-unread"></div><?php endif;?>
            <a href="?hapus=<?=$n['id_notif']?>" class="btn-hapus" onclick="return confirm('Hapus notifikasi ini?')"><i class="fas fa-times"></i></a>
        </div>
    </div>
    <?php endforeach;?>
</div>
<?php endif;?>
</div></body></html>
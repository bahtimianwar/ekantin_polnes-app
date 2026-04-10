<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penjual') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';


$id_penjual = $_SESSION['penjual']['id_penjual'] ?? 0;

if (!$id_penjual) {
    $stmt = $pdo->prepare("SELECT id_penjual FROM penjual WHERE id_user = ?");
    $stmt->execute([$_SESSION['user']['id_user']]);
    $row = $stmt->fetch();
    $id_penjual = $row['id_penjual'] ?? 0;
    if ($row) $_SESSION['penjual'] = $row;
}

// Update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $action = $_POST['action'] ?? '';

    $status_map = ['proses' => 'diproses', 'selesai' => 'selesai', 'batal' => 'batal'];
    if (isset($status_map[$action])) {
        $new_status = $status_map[$action];
        $pdo->prepare("UPDATE pesanan SET status=? WHERE id_pesanan=? AND id_penjual=?")
            ->execute([$new_status, $id_pesanan, $id_penjual]);

        // Kalau batal, kembalikan saldo
        if ($new_status === 'batal') {
            $stmt = $pdo->prepare("SELECT id_user, total_harga, metode_bayar FROM pesanan WHERE id_pesanan = ?");
            $stmt->execute([$id_pesanan]);
            $p = $stmt->fetch();
            if ($p && $p['metode_bayar'] === 'saldo') {
                $pdo->prepare("UPDATE saldo SET saldo = saldo + ? WHERE id_user = ?")
                    ->execute([$p['total_harga'], $p['id_user']]);
                $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Pesanan Dibatalkan', ?)")
                    ->execute([$p['id_user'], "Pesanan #$id_pesanan dibatalkan oleh penjual. Saldo Rp " . number_format($p['total_harga'],0,',','.') . " telah dikembalikan."]);
            }
        }

        // Notif ke mahasiswa kalau diproses/selesai
        if ($new_status === 'diproses') {
            $stmt = $pdo->prepare("SELECT id_user FROM pesanan WHERE id_pesanan = ?");
            $stmt->execute([$id_pesanan]);
            $p = $stmt->fetch();
            $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Pesanan Sedang Diproses 🍳', ?)")
                ->execute([$p['id_user'], "Pesanan #$id_pesanan kamu sedang disiapkan oleh penjual. Harap tunggu ya!"]);
        }
        if ($new_status === 'selesai') {
            $stmt = $pdo->prepare("SELECT id_user FROM pesanan WHERE id_pesanan = ?");
            $stmt->execute([$id_pesanan]);
            $p = $stmt->fetch();
            $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Pesanan Selesai ✅', ?)")
                ->execute([$p['id_user'], "Pesanan #$id_pesanan kamu sudah selesai. Silakan ambil pesananmu!"]);
        }
    }
    header("Location: pesanan.php");
    exit;
}

$filter = $_GET['filter'] ?? 'menunggu';
$where = $filter === 'semua' ? "WHERE p.id_penjual = ?" : "WHERE p.id_penjual = ? AND p.status = ?";
$params = $filter === 'semua' ? [$id_penjual] : [$id_penjual, $filter];

$stmt = $pdo->prepare("
    SELECT p.*, u.nama, u.nim, u.no_hp,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' (', dp.jumlah, 'x) = Rp ', FORMAT(dp.subtotal,0)) SEPARATOR '||') as items
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    $where
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal DESC
");
$stmt->execute($params);
$pesanan_list = $stmt->fetchAll();

// Count per status
$counts = [];
foreach (['menunggu','diproses','selesai','batal'] as $s) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE id_penjual = ? AND status = ?");
    $st->execute([$id_penjual, $s]);
    $counts[$s] = $st->fetchColumn();
}
?>

<?php require_once '../../includes/sidebar_penjual.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-shopping-bag" style="color:#3949ab;margin-right:10px;"></i>Pesanan Masuk</h1>
    <p>Kelola dan proses pesanan dari pelanggan</p>
</div>

<style>
    .filter-bar { display:flex; gap:10px; margin-bottom:22px; flex-wrap:wrap; }
    .filter-btn { padding:9px 20px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; transition:all .2s; border:1.5px solid #e0e0e0; background:white; color:#666; display:flex; align-items:center; gap:7px; }
    .filter-btn:hover { border-color:#3949ab; color:#3949ab; }
    .filter-btn.active { background:#3949ab; color:white; border-color:#3949ab; }
    .filter-btn .count { background:rgba(255,255,255,0.3); padding:1px 7px; border-radius:10px; font-size:11px; }
    .filter-btn:not(.active) .count { background:#f0f0f0; color:#888; }

    .pesanan-grid { display:flex; flex-direction:column; gap:14px; }
    .pesanan-card { background:white; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); overflow:hidden; }
    .pesanan-head { padding:14px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; border-bottom:1px solid #f5f5f5; }
    .pesanan-head .left { display:flex; align-items:center; gap:12px; }
    .pesanan-id { font-size:15px; font-weight:800; color:#1a237e; }
    .pesanan-user .nama { font-size:13.5px; font-weight:600; color:#333; }
    .pesanan-user .nim { font-size:12px; color:#aaa; }
    .status-badge { padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:5px; }
    .status-menunggu { background:#fff3e0; color:#e65100; }
    .status-diproses { background:#e3f2fd; color:#1565c0; }
    .status-selesai { background:#e8f5e9; color:#2e7d32; }
    .status-batal { background:#fce4ec; color:#c62828; }

    .pesanan-body { padding:16px 20px; }
    .item-list { margin-bottom:14px; }
    .item-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px dashed #f0f0f0; font-size:13.5px; }
    .item-row:last-child { border-bottom:none; }
    .item-row .item-name { color:#333; }
    .item-row .item-price { color:#666; font-weight:600; }
    .pesanan-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding-top:12px; border-top:2px solid #f5f5f5; }
    .total-harga { font-size:17px; font-weight:800; color:#1a237e; }
    .pesanan-meta { font-size:12px; color:#aaa; }
    .action-btns { display:flex; gap:8px; flex-wrap:wrap; }
    .btn-action { padding:8px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:6px; }
    .btn-proses { background:#e3f2fd; color:#1565c0; }
    .btn-proses:hover { background:#1565c0; color:white; }
    .btn-selesai { background:#e8f5e9; color:#2e7d32; }
    .btn-selesai:hover { background:#2e7d32; color:white; }
    .btn-batal { background:#fce4ec; color:#c62828; }
    .btn-batal:hover { background:#c62828; color:white; }
    .metode-badge { font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:10px; }
    .metode-saldo { background:#e8f5e9; color:#2e7d32; }
    .metode-tunai { background:#fff3e0; color:#e65100; }
    .empty-state { text-align:center; padding:60px 20px; color:#ccc; }
    .empty-state i { font-size:50px; display:block; margin-bottom:14px; }
</style>

<div class="filter-bar">
    <a href="?filter=menunggu" class="filter-btn <?= $filter==='menunggu'?'active':'' ?>">
        🕐 Menunggu <span class="count"><?= $counts['menunggu'] ?></span>
    </a>
    <a href="?filter=diproses" class="filter-btn <?= $filter==='diproses'?'active':'' ?>">
        🔄 Diproses <span class="count"><?= $counts['diproses'] ?></span>
    </a>
    <a href="?filter=selesai" class="filter-btn <?= $filter==='selesai'?'active':'' ?>">
        ✅ Selesai <span class="count"><?= $counts['selesai'] ?></span>
    </a>
    <a href="?filter=batal" class="filter-btn <?= $filter==='batal'?'active':'' ?>">
        ❌ Batal <span class="count"><?= $counts['batal'] ?></span>
    </a>
    <a href="?filter=semua" class="filter-btn <?= $filter==='semua'?'active':'' ?>">
        📋 Semua
    </a>
</div>

<?php if (empty($pesanan_list)): ?>
<div class="empty-state">
    <i class="fas fa-inbox"></i>
    <p>Tidak ada pesanan <?= $filter !== 'semua' ? "dengan status $filter" : '' ?></p>
</div>
<?php else: ?>
<div class="pesanan-grid">
    <?php foreach ($pesanan_list as $p): ?>
    <div class="pesanan-card">
        <div class="pesanan-head">
            <div class="left">
                <div>
                    <div class="pesanan-id">#<?= $p['id_pesanan'] ?></div>
                    <div style="font-size:12px;color:#aaa;"><?= date('d M Y, H:i', strtotime($p['tanggal'])) ?></div>
                </div>
                <div class="pesanan-user">
                    <div class="nama"><i class="fas fa-user" style="color:#7986cb;margin-right:5px;"></i><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="nim">NIM: <?= htmlspecialchars($p['nim'] ?? '-') ?> · <?= htmlspecialchars($p['no_hp']) ?></div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="metode-badge metode-<?= $p['metode_bayar'] ?? 'saldo' ?>">
                    <i class="fas fa-<?= ($p['metode_bayar']??'saldo')==='saldo'?'wallet':'money-bill' ?>"></i>
                    <?= ucfirst($p['metode_bayar'] ?? 'saldo') ?>
                </span>
                <span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
            </div>
        </div>
        <div class="pesanan-body">
            <div class="item-list">
                <?php
                $items = explode('||', $p['items'] ?? '');
                foreach ($items as $item):
                    if (empty(trim($item))) continue;
                    preg_match('/^(.+) \((\d+)x\) = Rp (.+)$/', $item, $m);
                ?>
                <div class="item-row">
                    <span class="item-name">🍽️ <?= htmlspecialchars($m[1] ?? $item) ?> <?= isset($m[2]) ? "<span style='color:#aaa;font-size:12px;'>x{$m[2]}</span>" : '' ?></span>
                    <span class="item-price">Rp <?= htmlspecialchars($m[3] ?? '-') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="pesanan-footer">
                <div>
                    <div class="total-harga">Total: Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></div>
                </div>
                <div class="action-btns">
                    <?php if ($p['status'] === 'menunggu'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                        <input type="hidden" name="action" value="proses">
                        <button class="btn-action btn-proses"><i class="fas fa-fire"></i> Proses</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                        <input type="hidden" name="action" value="batal">
                        <button class="btn-action btn-batal" onclick="return confirm('Batalkan pesanan ini?')"><i class="fas fa-times"></i> Batal</button>
                    </form>
                    <?php elseif ($p['status'] === 'diproses'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                        <input type="hidden" name="action" value="selesai">
                        <button class="btn-action btn-selesai"><i class="fas fa-check"></i> Selesai</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div></body></html>
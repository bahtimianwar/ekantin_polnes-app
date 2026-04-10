<?php
require_once '../../includes/db.php';
require_once '../../includes/sidebar_admin.php';

// Ambil semua penjual
$stmt = $pdo->query("
    SELECT u.id_user, u.nama, u.email, u.no_hp, u.created_at,
           p.id_penjual, p.nama_kantin, p.lokasi, p.no_rek,
           COUNT(DISTINCT m.id_menu) as total_menu,
           COUNT(DISTINCT ps.id_pesanan) as total_pesanan
    FROM users u
    JOIN penjual p ON u.id_user = p.id_user
    LEFT JOIN menu m ON p.id_penjual = m.id_penjual
    LEFT JOIN pesanan ps ON p.id_penjual = ps.id_penjual AND ps.status = 'selesai'
    WHERE u.role = 'penjual'
    GROUP BY u.id_user
    ORDER BY u.created_at DESC
");
$penjual_list = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-store" style="color:#3949ab;margin-right:10px;"></i>Data Penjual</h1>
    <p>Daftar seluruh penjual / kantin yang terdaftar</p>
</div>

<style>
    .summary-row { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .summary-card { background: white; border-radius: 14px; padding: 18px 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 14px; flex: 1; min-width: 160px; }
    .summary-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .summary-info .label { font-size: 11px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px; }
    .summary-info .val   { font-size: 22px; font-weight: 800; color: #1a1a1a; margin-top: 2px; }
    .bg-teal   { background: #e0f2f1; color: #00695c; }
    .bg-orange { background: #fff3e0; color: #e65100; }
    .bg-blue   { background: #e3f2fd; color: #1565c0; }

    .card { background: white; border-radius: 16px; box-shadow: 0 2px 14px rgba(0,0,0,0.06); overflow: hidden; }
    .card-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
    .card-header h3 { font-size: 15px; font-weight: 700; }
    .card-header span { font-size: 13px; color: #aaa; }

    .penjual-table { width: 100%; border-collapse: collapse; }
    .penjual-table th { font-size: 11px; font-weight: 700; color: #aaa; text-transform: uppercase; padding: 12px 18px; text-align: left; border-bottom: 2px solid #f0f0f0; background: #fafafa; }
    .penjual-table td { padding: 13px 18px; font-size: 13.5px; color: #333; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
    .penjual-table tr:last-child td { border-bottom: none; }
    .penjual-table tr:hover td { background: #fafafa; }

    .kantin-cell { display: flex; align-items: center; gap: 10px; }
    .kantin-avatar {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #00695c, #00897b);
        color: white; font-weight: 700; font-size: 15px;
        display: flex; align-items: center; justify-content: center;
    }
    .kantin-name  { font-weight: 700; color: #1a1a1a; }
    .kantin-owner { font-size: 11.5px; color: #aaa; margin-top: 2px; }
    .lokasi-chip  { display: inline-flex; align-items: center; gap: 5px; background: #f5f5f5; color: #555; font-size: 12px; padding: 4px 10px; border-radius: 20px; }

    .empty-box { text-align: center; padding: 50px 20px; color: #ccc; }
    .empty-box i { font-size: 40px; margin-bottom: 12px; display: block; }
</style>

<?php
$total_penjual  = count($penjual_list);
$total_menu     = array_sum(array_column($penjual_list, 'total_menu'));
$total_pesanan  = array_sum(array_column($penjual_list, 'total_pesanan'));
?>

<div class="summary-row">
    <div class="summary-card">
        <div class="summary-icon bg-teal"><i class="fas fa-store"></i></div>
        <div class="summary-info">
            <div class="label">Total Kantin</div>
            <div class="val"><?= $total_penjual ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-orange"><i class="fas fa-utensils"></i></div>
        <div class="summary-info">
            <div class="label">Total Menu</div>
            <div class="val"><?= $total_menu ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-blue"><i class="fas fa-shopping-bag"></i></div>
        <div class="summary-info">
            <div class="label">Pesanan Selesai</div>
            <div class="val"><?= number_format($total_pesanan) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Daftar Penjual / Kantin</h3>
        <span><?= $total_penjual ?> kantin</span>
    </div>
    <?php if (empty($penjual_list)): ?>
        <div class="empty-box">
            <i class="fas fa-store-slash"></i>
            <p>Belum ada penjual terdaftar</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="penjual-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Kantin</th>
                <th>Lokasi</th>
                <th>Kontak</th>
                <th>No Rekening</th>
                <th>Menu</th>
                <th>Pesanan</th>
                <th>Terdaftar</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($penjual_list as $i => $p): ?>
        <tr>
            <td style="color:#aaa;font-size:12px;"><?= $i + 1 ?></td>
            <td>
                <div class="kantin-cell">
                    <div class="kantin-avatar"><?= strtoupper(substr($p['nama_kantin'], 0, 1)) ?></div>
                    <div>
                        <div class="kantin-name"><?= htmlspecialchars($p['nama_kantin']) ?></div>
                        <div class="kantin-owner"><?= htmlspecialchars($p['nama']) ?></div>
                    </div>
                </div>
            </td>
            <td>
                <span class="lokasi-chip">
                    <i class="fas fa-map-marker-alt" style="color:#e65100;"></i>
                    <?= htmlspecialchars($p['lokasi'] ?? '-') ?>
                </span>
            </td>
            <td style="font-size:12.5px;">
                <div><?= htmlspecialchars($p['email']) ?></div>
                <div style="color:#aaa;"><?= htmlspecialchars($p['no_hp']) ?></div>
            </td>
            <td style="font-size:12.5px;color:#555;"><?= htmlspecialchars($p['no_rek'] ?? '-') ?></td>
            <td style="text-align:center;font-weight:700;color:#00695c;"><?= $p['total_menu'] ?></td>
            <td style="text-align:center;font-weight:700;color:#3949ab;"><?= $p['total_pesanan'] ?></td>
            <td style="font-size:12px;color:#aaa;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div></body></html>
<?php
require_once '../../includes/db.php';
require_once '../../includes/sidebar_admin.php';

// Ambil semua mahasiswa beserta saldo
$stmt = $pdo->query("
    SELECT u.id_user, u.nim, u.nama, u.email, u.no_hp, u.created_at,
           COALESCE(s.saldo, 0) as saldo,
           COUNT(DISTINCT t.id_topup) as total_topup
    FROM users u
    LEFT JOIN saldo s ON u.id_user = s.id_user
    LEFT JOIN topup t ON u.id_user = t.id_user AND t.status = 'diterima'
    WHERE u.role = 'mahasiswa'
    GROUP BY u.id_user
    ORDER BY u.created_at DESC
");
$mahasiswa_list = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-users" style="color:#3949ab;margin-right:10px;"></i>Data Mahasiswa</h1>
    <p>Daftar seluruh mahasiswa terdaftar beserta informasi saldo</p>
</div>

<style>
    .summary-row { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .summary-card {
        background: white; border-radius: 14px; padding: 18px 22px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        display: flex; align-items: center; gap: 14px; flex: 1; min-width: 160px;
    }
    .summary-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .summary-info .label { font-size: 11px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px; }
    .summary-info .val   { font-size: 22px; font-weight: 800; color: #1a1a1a; margin-top: 2px; }
    .bg-blue   { background: #e3f2fd; color: #1565c0; }
    .bg-green  { background: #e8f5e9; color: #2e7d32; }
    .bg-purple { background: #f3e5f5; color: #6a1b9a; }

    .card { background: white; border-radius: 16px; box-shadow: 0 2px 14px rgba(0,0,0,0.06); overflow: hidden; }
    .card-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
    .card-header h3 { font-size: 15px; font-weight: 700; }
    .card-header span { font-size: 13px; color: #aaa; }

    .mhs-table { width: 100%; border-collapse: collapse; }
    .mhs-table th { font-size: 11px; font-weight: 700; color: #aaa; text-transform: uppercase; padding: 12px 18px; text-align: left; border-bottom: 2px solid #f0f0f0; background: #fafafa; }
    .mhs-table td { padding: 13px 18px; font-size: 13.5px; color: #333; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
    .mhs-table tr:last-child td { border-bottom: none; }
    .mhs-table tr:hover td { background: #fafafa; }

    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-avatar {
        width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #3949ab, #5c6bc0);
        color: white; font-weight: 700; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
    }
    .user-name { font-weight: 600; color: #1a1a1a; }
    .user-nim  { font-size: 11.5px; color: #aaa; margin-top: 2px; }
    .saldo-val { font-weight: 800; color: #1a237e; }
    .saldo-zero { color: #ccc; }

    .empty-box { text-align: center; padding: 50px 20px; color: #ccc; }
    .empty-box i { font-size: 40px; margin-bottom: 12px; display: block; }
</style>

<?php
$total_mhs    = count($mahasiswa_list);
$total_saldo  = array_sum(array_column($mahasiswa_list, 'saldo'));
$total_topup  = array_sum(array_column($mahasiswa_list, 'total_topup'));
?>

<div class="summary-row">
    <div class="summary-card">
        <div class="summary-icon bg-blue"><i class="fas fa-user-graduate"></i></div>
        <div class="summary-info">
            <div class="label">Total Mahasiswa</div>
            <div class="val"><?= number_format($total_mhs) ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-green"><i class="fas fa-wallet"></i></div>
        <div class="summary-info">
            <div class="label">Total Saldo Beredar</div>
            <div class="val" style="font-size:16px;">Rp <?= number_format($total_saldo, 0, ',', '.') ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-purple"><i class="fas fa-check-circle"></i></div>
        <div class="summary-info">
            <div class="label">Total Top Up Sukses</div>
            <div class="val"><?= number_format($total_topup) ?>x</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Daftar Mahasiswa</h3>
        <span><?= $total_mhs ?> mahasiswa</span>
    </div>
    <?php if (empty($mahasiswa_list)): ?>
        <div class="empty-box">
            <i class="fas fa-users"></i>
            <p>Belum ada mahasiswa terdaftar</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="mhs-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Mahasiswa</th>
                <th>Email</th>
                <th>No HP</th>
                <th>Saldo</th>
                <th>Top Up</th>
                <th>Terdaftar</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mahasiswa_list as $i => $m): ?>
        <tr>
            <td style="color:#aaa;font-size:12px;"><?= $i + 1 ?></td>
            <td>
                <div class="user-cell">
                    <div class="user-avatar"><?= strtoupper(substr($m['nama'], 0, 1)) ?></div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($m['nama']) ?></div>
                        <div class="user-nim">NIM: <?= htmlspecialchars($m['nim'] ?? '-') ?></div>
                    </div>
                </div>
            </td>
            <td style="font-size:13px;color:#555;"><?= htmlspecialchars($m['email']) ?></td>
            <td style="font-size:13px;color:#555;"><?= htmlspecialchars($m['no_hp']) ?></td>
            <td class="<?= $m['saldo'] > 0 ? 'saldo-val' : 'saldo-zero' ?>">
                Rp <?= number_format($m['saldo'], 0, ',', '.') ?>
            </td>
            <td style="text-align:center;font-weight:700;color:#3949ab;"><?= $m['total_topup'] ?>x</td>
            <td style="font-size:12px;color:#aaa;"><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div></body></html>
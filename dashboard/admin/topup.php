<?php
require_once '../../includes/db.php';
require_once '../../includes/sidebar_admin.php';

$success = '';
$error   = '';

// ── Proses Aksi (Terima / Tolak) ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_topup = (int) ($_POST['id_topup'] ?? 0);
    $aksi     = $_POST['aksi'] ?? '';

    if (!in_array($aksi, ['terima', 'tolak']) || $id_topup <= 0) {
        $error = 'Permintaan tidak valid.';
    } else {
        // Ambil data top up
        $stmt = $pdo->prepare("SELECT * FROM topup WHERE id_topup = ? AND status = 'menunggu'");
        $stmt->execute([$id_topup]);
        $topup = $stmt->fetch();

        if (!$topup) {
            $error = 'Pengajuan tidak ditemukan atau sudah diproses sebelumnya.';
        } else {
            try {
                $pdo->beginTransaction();

                if ($aksi === 'terima') {
                    // 1. Update status top up → diterima
                    $pdo->prepare("UPDATE topup SET status = 'diterima' WHERE id_topup = ?")
                        ->execute([$id_topup]);

                    // 2. Tambah saldo (INSERT jika belum ada, UPDATE jika sudah ada)
                    $pdo->prepare("
                        INSERT INTO saldo (id_user, saldo) VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE saldo = saldo + VALUES(saldo)
                    ")->execute([$topup['id_user'], $topup['jumlah']]);

                    // 3. Kirim notifikasi ke mahasiswa
                    $pdo->prepare("
                        INSERT INTO notifikasi (id_user, judul, pesan)
                        VALUES (?, 'Top Up Berhasil ✅', ?)
                    ")->execute([
                        $topup['id_user'],
                        'Top up sebesar Rp ' . number_format($topup['jumlah'], 0, ',', '.') . ' telah dikonfirmasi. Saldo kamu sudah bertambah!'
                    ]);

                    $success = 'Top up berhasil dikonfirmasi dan saldo mahasiswa telah ditambahkan.';

                } else { // tolak
                    // 1. Update status top up → ditolak
                    $pdo->prepare("UPDATE topup SET status = 'ditolak' WHERE id_topup = ?")
                        ->execute([$id_topup]);

                    // 2. Kirim notifikasi penolakan
                    $pdo->prepare("
                        INSERT INTO notifikasi (id_user, judul, pesan)
                        VALUES (?, 'Top Up Ditolak ❌', ?)
                    ")->execute([
                        $topup['id_user'],
                        'Pengajuan top up sebesar Rp ' . number_format($topup['jumlah'], 0, ',', '.') . ' ditolak. Silakan hubungi admin jika ada pertanyaan.'
                    ]);

                    $success = 'Pengajuan top up berhasil ditolak.';
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan sistem. Coba lagi.';
            }
        }
    }
}

// ── Filter & Ambil Data ────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'menunggu';
$allowed = ['menunggu', 'diterima', 'ditolak', 'semua'];
if (!in_array($filter_status, $allowed)) $filter_status = 'menunggu';

$where = $filter_status !== 'semua' ? "WHERE t.status = '$filter_status'" : '';

$stmt = $pdo->query("
    SELECT t.*, u.nama, u.nim, u.no_hp
    FROM topup t
    JOIN users u ON t.id_user = u.id_user
    $where
    ORDER BY t.tanggal DESC
");
$topup_list = $stmt->fetchAll();

// Hitung per status untuk badge
$counts = $pdo->query("
    SELECT status, COUNT(*) as jumlah FROM topup GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-header">
    <h1><i class="fas fa-wallet" style="color:#3949ab;margin-right:10px;"></i>Konfirmasi Top Up</h1>
    <p>Kelola pengajuan top up saldo dari mahasiswa</p>
</div>

<style>
    .alert { padding: 13px 18px; border-radius: 12px; margin-bottom: 22px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-error   { background: #fce4ec; color: #c62828; border: 1px solid #f8bbd0; }

    .filter-tabs { display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap; }
    .filter-tab {
        padding: 8px 18px; border-radius: 20px; text-decoration: none;
        font-size: 13px; font-weight: 600; transition: all 0.2s;
        background: white; color: #555;
        box-shadow: 0 1px 6px rgba(0,0,0,0.07);
    }
    .filter-tab:hover { background: #e8eaf6; color: #3949ab; }
    .filter-tab.active { background: #3949ab; color: white; }
    .filter-tab .count {
        display: inline-block; margin-left: 6px;
        background: rgba(0,0,0,0.12); color: inherit;
        font-size: 11px; font-weight: 700;
        padding: 1px 7px; border-radius: 10px;
    }
    .filter-tab.active .count { background: rgba(255,255,255,0.25); }

    .card { background: white; border-radius: 16px; box-shadow: 0 2px 14px rgba(0,0,0,0.06); overflow: hidden; }
    .card-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
    .card-header h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; }
    .card-header span { font-size: 13px; color: #aaa; }

    .topup-table { width: 100%; border-collapse: collapse; }
    .topup-table th {
        font-size: 11px; font-weight: 700; color: #aaa;
        text-transform: uppercase; padding: 12px 18px;
        text-align: left; border-bottom: 2px solid #f0f0f0;
        background: #fafafa;
    }
    .topup-table td { padding: 14px 18px; font-size: 13.5px; color: #333; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
    .topup-table tr:last-child td { border-bottom: none; }
    .topup-table tr:hover td { background: #fafafa; }

    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-avatar {
        width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #3949ab, #5c6bc0);
        color: white; font-weight: 700; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
    }
    .user-name { font-weight: 600; color: #1a1a1a; }
    .user-nim { font-size: 11.5px; color: #aaa; margin-top: 2px; }

    .jumlah-cell { font-weight: 800; color: #1a237e; font-size: 14.5px; }
    .metode-cell { font-size: 12px; color: #666; background: #f5f5f5; display: inline-block; padding: 3px 10px; border-radius: 20px; }

    .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11.5px; font-weight: 700; }
    .badge-menunggu { background: #fff3e0; color: #e65100; }
    .badge-diterima { background: #e8f5e9; color: #2e7d32; }
    .badge-ditolak  { background: #fce4ec; color: #c62828; }

    .action-btns { display: flex; gap: 8px; }
    .btn-terima, .btn-tolak {
        padding: 7px 14px; border: none; border-radius: 8px;
        font-size: 12.5px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
        transition: all 0.2s;
    }
    .btn-terima { background: #e8f5e9; color: #2e7d32; }
    .btn-terima:hover { background: #2e7d32; color: white; }
    .btn-tolak  { background: #fce4ec; color: #c62828; }
    .btn-tolak:hover  { background: #c62828; color: white; }

    .empty-box { text-align: center; padding: 50px 20px; color: #ccc; }
    .empty-box i { font-size: 40px; margin-bottom: 12px; display: block; }
    .empty-box p { font-size: 14px; }

    /* Modal Konfirmasi */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.45); z-index: 999;
        align-items: center; justify-content: center;
    }
    .modal-overlay.show { display: flex; }
    .modal-box {
        background: white; border-radius: 20px; padding: 32px 28px;
        width: 100%; max-width: 420px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        animation: modalIn 0.2s ease;
    }
    @keyframes modalIn { from { transform: scale(0.92); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon { text-align: center; margin-bottom: 18px; }
    .modal-icon i { font-size: 48px; }
    .modal-icon.terima i { color: #2e7d32; }
    .modal-icon.tolak  i { color: #c62828; }
    .modal-title { text-align: center; font-size: 18px; font-weight: 800; color: #1a1a1a; margin-bottom: 8px; }
    .modal-desc { text-align: center; font-size: 13.5px; color: #666; margin-bottom: 22px; line-height: 1.6; }
    .modal-detail {
        background: #f5f5f5; border-radius: 12px; padding: 14px 16px;
        margin-bottom: 22px; font-size: 13px;
    }
    .modal-detail .row { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .modal-detail .row:last-child { margin-bottom: 0; }
    .modal-detail .key { color: #888; }
    .modal-detail .val { font-weight: 700; color: #1a1a1a; }
    .modal-btns { display: flex; gap: 10px; }
    .modal-btns button {
        flex: 1; padding: 12px; border: none; border-radius: 10px;
        font-size: 14px; font-weight: 700; cursor: pointer; transition: opacity 0.2s;
    }
    .modal-btns .btn-cancel { background: #f0f0f0; color: #555; }
    .modal-btns .btn-cancel:hover { background: #e0e0e0; }
    .modal-btns .btn-confirm-terima { background: linear-gradient(135deg, #2e7d32, #43a047); color: white; }
    .modal-btns .btn-confirm-tolak  { background: linear-gradient(135deg, #c62828, #e53935); color: white; }
    .modal-btns button:hover { opacity: 0.9; }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?status=menunggu" class="filter-tab <?= $filter_status === 'menunggu' ? 'active' : '' ?>">
        <i class="fas fa-clock"></i> Menunggu
        <span class="count"><?= $counts['menunggu'] ?? 0 ?></span>
    </a>
    <a href="?status=diterima" class="filter-tab <?= $filter_status === 'diterima' ? 'active' : '' ?>">
        <i class="fas fa-check-circle"></i> Diterima
        <span class="count"><?= $counts['diterima'] ?? 0 ?></span>
    </a>
    <a href="?status=ditolak" class="filter-tab <?= $filter_status === 'ditolak' ? 'active' : '' ?>">
        <i class="fas fa-times-circle"></i> Ditolak
        <span class="count"><?= $counts['ditolak'] ?? 0 ?></span>
    </a>
    <a href="?status=semua" class="filter-tab <?= $filter_status === 'semua' ? 'active' : '' ?>">
        <i class="fas fa-list"></i> Semua
    </a>
</div>

<!-- Tabel Top Up -->
<div class="card">
    <div class="card-header">
        <h3>
            <?php
            $label = ['menunggu' => 'Pengajuan Menunggu Konfirmasi', 'diterima' => 'Top Up Diterima', 'ditolak' => 'Top Up Ditolak', 'semua' => 'Semua Riwayat Top Up'];
            echo $label[$filter_status];
            ?>
        </h3>
        <span><?= count($topup_list) ?> data</span>
    </div>

    <?php if (empty($topup_list)): ?>
        <div class="empty-box">
            <i class="fas fa-inbox"></i>
            <p>Tidak ada data untuk ditampilkan</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="topup-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Mahasiswa</th>
                <th>Jumlah</th>
                <th>Metode</th>
                <th>Tanggal</th>
                <th>Status</th>
                <?php if ($filter_status === 'menunggu' || $filter_status === 'semua'): ?>
                <th>Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($topup_list as $i => $t): ?>
        <tr>
            <td style="color:#aaa;font-size:12px;"><?= $i + 1 ?></td>
            <td>
                <div class="user-cell">
                    <div class="user-avatar"><?= strtoupper(substr($t['nama'], 0, 1)) ?></div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($t['nama']) ?></div>
                        <div class="user-nim">NIM: <?= htmlspecialchars($t['nim'] ?? '-') ?></div>
                    </div>
                </div>
            </td>
            <td class="jumlah-cell">Rp <?= number_format($t['jumlah'], 0, ',', '.') ?></td>
            <td><span class="metode-cell"><?= str_replace('_', ' ', ucfirst($t['metode'])) ?></span></td>
            <td style="font-size:12.5px;color:#666;">
                <?= date('d/m/Y', strtotime($t['tanggal'])) ?><br>
                <span style="color:#bbb;"><?= date('H:i', strtotime($t['tanggal'])) ?></span>
            </td>
            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
            <?php if ($filter_status === 'menunggu' || $filter_status === 'semua'): ?>
            <td>
                <?php if ($t['status'] === 'menunggu'): ?>
                <div class="action-btns">
                    <button class="btn-terima" onclick="openModal('terima', <?= $t['id_topup'] ?>, '<?= htmlspecialchars(addslashes($t['nama'])) ?>', <?= $t['jumlah'] ?>, '<?= htmlspecialchars(addslashes(str_replace('_',' ',ucfirst($t['metode'])))) ?>')">
                        <i class="fas fa-check"></i> Terima
                    </button>
                    <button class="btn-tolak" onclick="openModal('tolak', <?= $t['id_topup'] ?>, '<?= htmlspecialchars(addslashes($t['nama'])) ?>', <?= $t['jumlah'] ?>, '<?= htmlspecialchars(addslashes(str_replace('_',' ',ucfirst($t['metode'])))) ?>')">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </div>
                <?php else: ?>
                    <span style="color:#ccc;font-size:12px;">—</span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-icon" id="modalIcon">
            <i id="modalIconEl"></i>
        </div>
        <div class="modal-title" id="modalTitle"></div>
        <div class="modal-desc" id="modalDesc"></div>
        <div class="modal-detail">
            <div class="row"><span class="key">Mahasiswa</span><span class="val" id="detailNama"></span></div>
            <div class="row"><span class="key">Jumlah</span><span class="val" id="detailJumlah"></span></div>
            <div class="row"><span class="key">Metode</span><span class="val" id="detailMetode"></span></div>
        </div>
        <form method="POST" id="modalForm">
            <input type="hidden" name="id_topup" id="inputIdTopup">
            <input type="hidden" name="aksi"     id="inputAksi">
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-confirm-terima" id="btnConfirm">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(aksi, id, nama, jumlah, metode) {
    document.getElementById('inputIdTopup').value = id;
    document.getElementById('inputAksi').value    = aksi;
    document.getElementById('detailNama').textContent   = nama;
    document.getElementById('detailJumlah').textContent = 'Rp ' + jumlah.toLocaleString('id-ID');
    document.getElementById('detailMetode').textContent = metode;

    const icon  = document.getElementById('modalIcon');
    const iconEl = document.getElementById('modalIconEl');
    const title  = document.getElementById('modalTitle');
    const desc   = document.getElementById('modalDesc');
    const btn    = document.getElementById('btnConfirm');

    if (aksi === 'terima') {
        icon.className  = 'modal-icon terima';
        iconEl.className = 'fas fa-check-circle';
        title.textContent = 'Konfirmasi Penerimaan';
        desc.textContent  = 'Saldo mahasiswa akan otomatis ditambahkan setelah kamu konfirmasi.';
        btn.className   = 'btn-confirm-terima';
        btn.textContent = '✓ Ya, Terima';
    } else {
        icon.className  = 'modal-icon tolak';
        iconEl.className = 'fas fa-times-circle';
        title.textContent = 'Konfirmasi Penolakan';
        desc.textContent  = 'Pengajuan ini akan ditolak dan mahasiswa akan mendapat notifikasi.';
        btn.className   = 'btn-confirm-tolak';
        btn.textContent = '✕ Ya, Tolak';
    }

    document.getElementById('modalOverlay').classList.add('show');
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

// Tutup modal jika klik di luar
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

</div></body></html>
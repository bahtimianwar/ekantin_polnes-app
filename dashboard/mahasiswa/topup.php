<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];
$success = ''; $error = '';

// Proses pengajuan top up
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = (int) str_replace(['.', ','], '', $_POST['jumlah'] ?? 0);
    $metode = $_POST['metode'] ?? 'transfer';

    if ($jumlah < 10000) {
        $error = 'Minimal top up adalah Rp 10.000';
    } elseif ($jumlah > 1000000) {
        $error = 'Maksimal top up adalah Rp 1.000.000 per transaksi';
    } else {
        $pdo->prepare("INSERT INTO topup (id_user, jumlah, metode, status) VALUES (?, ?, ?, 'menunggu')")
            ->execute([$id_user, $jumlah, $metode]);

        $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Pengajuan Top Up', ?)")
            ->execute([$id_user, "Pengajuan top up sebesar Rp " . number_format($jumlah, 0, ',', '.') . " sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin."]);

        $success = 'Pengajuan top up berhasil dikirim! Tunggu konfirmasi dari admin.';
    }
}

// Ambil saldo
$stmt = $pdo->prepare("SELECT COALESCE(saldo, 0) as saldo FROM saldo WHERE id_user = ?");
$stmt->execute([$id_user]);
$saldo_row = $stmt->fetch();
$saldo = $saldo_row ? $saldo_row['saldo'] : 0;

// Riwayat top up
$stmt2 = $pdo->prepare("SELECT * FROM topup WHERE id_user = ? ORDER BY tanggal DESC LIMIT 10");
$stmt2->execute([$id_user]);
$topup_list = $stmt2->fetchAll();
?>

<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-wallet" style="color:var(--ac); margin-right:10px;"></i>Top Up Saldo</h1>
    <p>Tambah saldo untuk memudahkan pembayaran di kantin</p>
</div>

<style>
    .topup-layout { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; }
    @media(max-width:900px) { .topup-layout { grid-template-columns: 1fr; } }

    .card { background: white; border-radius: 16px; box-shadow: 0 2px 14px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 20px; }

    /* ── Card Header: tidak lagi hijau, ikut warna card ── */
    .card-header {
        background: #f8fafb;
        border-bottom: 1px solid #efefef;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .card-header-icon {
        width: 40px; height: 40px;
        background: #e8f5e9;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #059669;
        font-size: 17px;
    }
    .card-header h3 { color: #1a1a1a; font-size: 16px; font-weight: 700; }
    .card-header p  { color: #888; font-size: 12px; margin-top: 2px; }

    .card-body { padding: 24px; }

    .saldo-display {
        border-radius: 14px; padding: 22px; color: white; margin-bottom: 22px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .saldo-display .label { opacity: 0.75; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }
    .saldo-display .amount { font-size: 26px; font-weight: 800; margin-top: 4px; }
    .saldo-display .icon { font-size: 40px; opacity: 0.3; }

    .nominal-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 18px; }
    .nominal-btn {
        padding: 12px 8px; border: 1.5px solid #d1fae5; border-radius: 10px;
        text-align: center; cursor: pointer; transition: all 0.2s;
        background: white; font-size: 13px; font-weight: 600; color: #065f46;
    }
    .nominal-btn:hover, .nominal-btn.selected {
        background: #065f46; color: white; border-color: #065f46;
    }
    .nominal-btn .amount { font-size: 14px; font-weight: 800; }
    .nominal-btn .label  { font-size: 11px; font-weight: 400; opacity: 0.75; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 7px; }
    .form-group input, .form-group select {
        width: 100%; padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px;
        font-size: 14px; color: #333; outline: none; transition: border-color 0.2s; background: white;
    }
    .form-group input:focus, .form-group select:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(0,137,123,0.1); }

    .info-box { background: #d1fae5; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; }
    .info-box .title    { font-size: 12px; font-weight: 700; color: #065f46; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .rekening { font-size: 15px; font-weight: 800; color: #064e3b; }
    .info-box .note     { font-size: 12px; color: #065f46; margin-top: 4px; }

    .btn-topup {
        width: 100%; padding: 13px;
        color: white; border: none; border-radius: 10px;
        font-size: 15px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: opacity 0.2s;
    }
    .btn-topup:hover { opacity: 0.9; }

    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-error   { background: #fce4ec; color: #c62828; border: 1px solid #f8bbd0; }

    .steps { counter-reset: step; }
    .step { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        background: #065f46; color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800; flex-shrink: 0;
    }
    .step-text { font-size: 13px; color: #444; padding-top: 4px; line-height: 1.5; }

    .history-table { width: 100%; border-collapse: collapse; }
    .history-table th { font-size: 11px; font-weight: 700; color: #aaa; text-transform: uppercase; padding: 8px 10px; text-align: left; border-bottom: 2px solid #f0f0f0; }
    .history-table td { padding: 11px 10px; font-size: 13px; color: #444; border-bottom: 1px solid #f9f9f9; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-menunggu { background: #fff3e0; color: #e65100; }
    .badge-diterima { background: #e8f5e9; color: #2e7d32; }
    .badge-ditolak  { background: #fce4ec; color: #c62828; }
    .empty-row td   { text-align: center; color: #bbb; padding: 30px; }

    /* ── Dark mode ── */
    [data-theme="dark"] .card      { background: var(--bg-card); box-shadow: var(--shadow); }
    [data-theme="dark"] .card-body { background: var(--bg-card); }

    /* Card header ikut gelap */
    [data-theme="dark"] .card-header {
        background: var(--bg-card);
        border-bottom-color: var(--border);
    }
    [data-theme="dark"] .card-header h3   { color: var(--text-1); }
    [data-theme="dark"] .card-header p    { color: var(--text-3); }
    [data-theme="dark"] .card-header-icon { background: var(--ac-lt); color: var(--ac); }

    [data-theme="dark"] .nominal-btn { background: var(--bg-card2); color: var(--ac-tx); border-color: var(--border); }
    [data-theme="dark"] .nominal-btn:hover,
    [data-theme="dark"] .nominal-btn.selected { background: var(--ac); color: white; border-color: var(--ac); }
    [data-theme="dark"] .form-group label { color: var(--text-2); }
    [data-theme="dark"] .form-group input,
    [data-theme="dark"] .form-group select { background: var(--inp-bg); border-color: var(--inp-bd); color: var(--text-1); }
    [data-theme="dark"] .form-group select option { background: var(--bg-card); color: var(--text-1); }
    [data-theme="dark"] .info-box          { background: var(--ac-lt); }
    [data-theme="dark"] .info-box .title   { color: var(--ac-tx); }
    [data-theme="dark"] .info-box .rekening{ color: var(--text-1); }
    [data-theme="dark"] .info-box .note    { color: var(--text-2); }
    [data-theme="dark"] .step-text         { color: var(--text-2); }
    [data-theme="dark"] .history-table th  { color: var(--text-3); border-bottom-color: var(--border); }
    [data-theme="dark"] .history-table td  { color: var(--text-2); border-bottom-color: var(--border); }
    [data-theme="dark"] .empty-row td      { color: var(--text-3); }
    [data-theme="dark"] .alert-success { background: rgba(34,197,94,.12); color: #4ade80; border-color: rgba(34,197,94,.2); }
    [data-theme="dark"] .alert-error   { background: rgba(239,68,68,.12); color: #f87171; border-color: rgba(239,68,68,.2); }
    [data-theme="dark"] .step-num      { background: var(--ac); }
    [data-theme="dark"] div[style*="background:#fff8e1"] {
        background: rgba(255,143,0,0.1) !important;
        color: var(--text-2) !important;
    }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="topup-layout">
    <div>
        <!-- Form Top Up -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-plus-circle"></i></div>
                <div>
                    <h3>Ajukan Top Up</h3>
                    <p>Pilih nominal dan metode pembayaran</p>
                </div>
            </div>
            <div class="card-body">
                <div class="saldo-display">
                    <div>
                        <div class="label">Saldo Saat Ini</div>
                        <div class="amount">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
                    </div>
                    <div class="icon"><i class="fas fa-wallet"></i></div>
                </div>

                <form method="POST" id="topupForm">
                    <div class="form-group">
                        <label>Pilih Nominal</label>
                        <div class="nominal-grid">
                            <?php
                            $nominals = [10000, 20000, 50000, 100000, 200000, 500000];
                            foreach ($nominals as $nom): ?>
                            <div class="nominal-btn" onclick="setNominal(<?= $nom ?>)">
                                <div class="amount">Rp <?= number_format($nom, 0, ',', '.') ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nominal Lainnya (opsional)</label>
                        <input type="number" id="jumlah_input" name="jumlah"
                               placeholder="Masukkan nominal, min. 10.000"
                               min="10000" max="1000000" step="1000">
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode" id="metode_select" onchange="updateRekening()">
                            <option value="transfer_bri">Transfer BRI</option>
                            <option value="transfer_bca">Transfer BCA</option>
                            <option value="transfer_mandiri">Transfer Mandiri</option>
                            <option value="qris">QRIS</option>
                            <option value="tunai">Bayar Tunai ke Admin</option>
                        </select>
                    </div>

                    <div class="info-box" id="rekening_info">
                        <div class="title">Rekening Tujuan</div>
                        <div class="rekening" id="rek_text">BRI 1234-5678-9012-3456</div>
                        <div class="note"     id="rek_note">a.n. E-Kantin Kampus · Wajib transfer sesuai nominal</div>
                    </div>

                    <button type="submit" class="btn-topup">
                        <i class="fas fa-paper-plane"></i> Kirim Pengajuan Top Up
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <!-- Cara Top Up -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-question-circle"></i></div>
                <div>
                    <h3>Cara Top Up</h3>
                    <p>Ikuti langkah-langkah berikut</p>
                </div>
            </div>
            <div class="card-body">
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div class="step-text">Pilih nominal top up yang diinginkan atau masukkan nominal sendiri (min. Rp 10.000)</div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div class="step-text">Transfer ke rekening yang tertera sesuai dengan metode pembayaran yang dipilih</div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div class="step-text">Klik <strong>"Kirim Pengajuan Top Up"</strong> untuk mengajukan konfirmasi kepada admin</div>
                    </div>
                    <div class="step">
                        <div class="step-num">4</div>
                        <div class="step-text">Admin akan mengkonfirmasi pembayaran dan saldo akan otomatis bertambah</div>
                    </div>
                    <div class="step">
                        <div class="step-num">5</div>
                        <div class="step-text">Anda akan mendapat notifikasi ketika saldo berhasil ditambahkan</div>
                    </div>
                </div>
                <div style="background:#fff8e1;border-radius:10px;padding:12px 14px;margin-top:10px;font-size:12.5px;color:#795548;">
                    <i class="fas fa-info-circle" style="color:#ff8f00;margin-right:6px;"></i>
                    Konfirmasi top up biasanya membutuhkan waktu 5-30 menit pada jam kerja.
                </div>
            </div>
        </div>

        <!-- Riwayat Top Up -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-history"></i></div>
                <div>
                    <h3>Riwayat Top Up</h3>
                    <p>10 transaksi terakhir</p>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topup_list)): ?>
                        <tr class="empty-row">
                            <td colspan="4">
                                <i class="fas fa-inbox" style="display:block;font-size:30px;margin-bottom:8px;"></i>
                                Belum ada top up
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topup_list as $t): ?>
                        <tr>
                            <td><?= date('d/m/y', strtotime($t['tanggal'])) ?></td>
                            <td><strong>Rp <?= number_format($t['jumlah'], 0, ',', '.') ?></strong></td>
                            <td style="font-size:12px;"><?= str_replace('_', ' ', ucfirst($t['metode'])) ?></td>
                            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function setNominal(amount) {
    document.getElementById('jumlah_input').value = amount;
    document.querySelectorAll('.nominal-btn').forEach(btn => btn.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
}

const rekeningData = {
    'transfer_bri':     { rek: 'BRI 1234-5678-9012-3456',   note: 'a.n. E-Kantin Kampus' },
    'transfer_bca':     { rek: 'BCA 0987-6543-2100',         note: 'a.n. E-Kantin Kampus' },
    'transfer_mandiri': { rek: 'Mandiri 108-0002-345678',    note: 'a.n. E-Kantin Kampus' },
    'qris':             { rek: 'Scan QR Code di Kantin',     note: 'Tunjukkan bukti pembayaran ke admin' },
    'tunai':            { rek: 'Bayar Langsung ke Admin',    note: 'Datang ke kantor administrasi kampus' }
};

function updateRekening() {
    const metode = document.getElementById('metode_select').value;
    const data   = rekeningData[metode] || rekeningData['transfer_bri'];
    document.getElementById('rek_text').textContent = data.rek;
    document.getElementById('rek_note').textContent = data.note;
}
</script>
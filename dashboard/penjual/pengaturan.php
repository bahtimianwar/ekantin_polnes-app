<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penjual') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];
$id_penjual = $_SESSION['penjual']['id_penjual'] ?? 0;

$success = '';
$error = '';

// Handle update profil penjual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_kantin') {
        $nama_kantin = trim($_POST['nama_kantin'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $no_rek = trim($_POST['no_rek'] ?? '');

        if (empty($nama_kantin) || empty($lokasi)) {
            $error = 'Nama kantin dan lokasi tidak boleh kosong.';
        } else {
            $stmt = $pdo->prepare("UPDATE penjual SET nama_kantin=?, lokasi=?, no_rek=? WHERE id_penjual=?");
            $stmt->execute([$nama_kantin, $lokasi, $no_rek, $id_penjual]);
            // Update session
            $_SESSION['penjual']['nama_kantin'] = $nama_kantin;
            $success = 'Data kantin berhasil diperbarui!';
        }
    }

    if ($action === 'update_akun') {
        $nama = trim($_POST['nama'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';

        if (empty($nama) || empty($no_hp)) {
            $error = 'Nama dan nomor HP tidak boleh kosong.';
        } else {
            // Cek apakah mau ganti password
            if (!empty($password_baru)) {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id_user = ?");
                $stmt->execute([$id_user]);
                $user = $stmt->fetch();
                if (!password_verify($password_lama, $user['password'])) {
                    $error = 'Password lama tidak benar.';
                } elseif (strlen($password_baru) < 6) {
                    $error = 'Password baru minimal 6 karakter.';
                } else {
                    $hash = password_hash($password_baru, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET nama=?, no_hp=?, password=? WHERE id_user=?")
                        ->execute([$nama, $no_hp, $hash, $id_user]);
                    $_SESSION['user']['nama'] = $nama;
                    $success = 'Data akun dan password berhasil diperbarui!';
                }
            } else {
                $pdo->prepare("UPDATE users SET nama=?, no_hp=? WHERE id_user=?")
                    ->execute([$nama, $no_hp, $id_user]);
                $_SESSION['user']['nama'] = $nama;
                $success = 'Data akun berhasil diperbarui!';
            }
        }
    }
}

// Ambil data terbaru
$stmt = $pdo->prepare("SELECT u.*, p.nama_kantin, p.lokasi, p.no_rek, p.teks_gambar_qris FROM users u LEFT JOIN penjual p ON p.id_user = u.id_user WHERE u.id_user = ?");
$stmt->execute([$id_user]);
$data = $stmt->fetch();
?>

<?php require_once '../../includes/sidebar_penjual.php'; ?>
<div class="page-header">
    <h1><i class="fas fa-cog" style="color:#3949ab; margin-right:10px;"></i>Pengaturan</h1>
    <p>Kelola informasi kantin dan akun Anda</p>
</div>

<style>
    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media(max-width:900px) { .settings-grid { grid-template-columns: 1fr; } }

    .settings-card {
        background: white; border-radius: 16px;
        box-shadow: 0 2px 14px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .card-header {
        background: linear-gradient(135deg, #1a237e, #3949ab);
        padding: 18px 24px;
        display: flex; align-items: center; gap: 12px;
    }
    .card-header-icon {
        width: 40px; height: 40px;
        background: rgba(255,255,255,0.15);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 17px;
    }
    .card-header h3 { color: white; font-size: 16px; font-weight: 700; }
    .card-header p { color: rgba(255,255,255,0.65); font-size: 12px; }
    .card-body { padding: 24px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 7px; }
    .form-group input, .form-group textarea, .form-group select {
        width: 100%; padding: 10px 14px;
        border: 1.5px solid #e0e0e0; border-radius: 10px;
        font-size: 14px; color: #333; outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        font-family: inherit;
    }
    .form-group input:focus, .form-group textarea:focus {
        border-color: #3949ab;
        box-shadow: 0 0 0 3px rgba(57,73,171,0.1);
    }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-group .hint { font-size: 11.5px; color: #999; margin-top: 5px; }

    .divider-label {
        font-size: 11px; font-weight: 700; color: #aaa;
        text-transform: uppercase; letter-spacing: 1px;
        margin: 20px 0 14px;
        display: flex; align-items: center; gap: 8px;
    }
    .divider-label::after {
        content: ''; flex: 1; height: 1px; background: #f0f0f0;
    }

    .btn-save {
        width: 100%; padding: 12px;
        background: linear-gradient(135deg, #1a237e, #3949ab);
        color: white; border: none; border-radius: 10px;
        font-size: 14px; font-weight: 700; cursor: pointer;
        transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-save:hover { opacity: 0.9; }

    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-error { background: #fce4ec; color: #c62828; border: 1px solid #f8bbd0; }

    .info-row { display: flex; gap: 12px; }
    .info-row .form-group { flex: 1; }

    .qris-section {
        background: #f8f9ff; border-radius: 12px; padding: 16px;
        border: 1.5px dashed #c5cae9; text-align: center;
        margin-bottom: 18px;
    }
    .qris-section p { color: #7986cb; font-size: 13px; }
    .qris-section small { color: #aaa; font-size: 11.5px; }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="settings-grid">

    <!-- Card Info Kantin -->
    <div class="settings-card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fas fa-store"></i></div>
            <div>
                <h3>Informasi Kantin</h3>
                <p>Update nama, lokasi, dan rekening kantin</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_kantin">

                <div class="form-group">
                    <label><i class="fas fa-store" style="color:#3949ab"></i> Nama Kantin</label>
                    <input type="text" name="nama_kantin" value="<?= htmlspecialchars($data['nama_kantin'] ?? '') ?>" placeholder="Contoh: Kantin Pak Budi" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt" style="color:#e53935"></i> Lokasi</label>
                    <textarea name="lokasi" placeholder="Contoh: Gedung A Lantai 1"><?= htmlspecialchars($data['lokasi'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-university" style="color:#43a047"></i> Nomor Rekening</label>
                    <input type="text" name="no_rek" value="<?= htmlspecialchars($data['no_rek'] ?? '') ?>" placeholder="Contoh: BRI 1234-5678-9012">
                    <div class="hint">Digunakan untuk pencairan saldo dari pembayaran mahasiswa</div>
                </div>

                <div class="qris-section">
                    <i class="fas fa-qrcode" style="font-size:32px; color:#9fa8da; margin-bottom:8px;"></i>
                    <p>Upload QRIS Kantin</p>
                    <small>Fitur upload QRIS akan segera tersedia</small>
                </div>

                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Informasi Kantin</button>
            </form>
        </div>
    </div>

    <!-- Card Akun -->
    <div class="settings-card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fas fa-user"></i></div>
            <div>
                <h3>Pengaturan Akun</h3>
                <p>Update profil dan keamanan akun</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_akun">

                <div class="form-group">
                    <label><i class="fas fa-user" style="color:#3949ab"></i> Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']) ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope" style="color:#fb8c00"></i> Email</label>
                    <input type="email" value="<?= htmlspecialchars($data['email']) ?>" disabled style="background:#f5f5f5; cursor:not-allowed;">
                    <div class="hint">Email tidak dapat diubah</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone" style="color:#43a047"></i> Nomor HP</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>" placeholder="08xx-xxxx-xxxx">
                </div>

                <div class="divider-label">Ganti Password (opsional)</div>

                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="password_lama" placeholder="Masukkan password saat ini">
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Minimal 6 karakter">
                    <div class="hint">Kosongkan jika tidak ingin mengganti password</div>
                </div>

                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Pengaturan Akun</button>
            </form>
        </div>
    </div>

</div>

</div><!-- end main-content -->
</body>
</html>
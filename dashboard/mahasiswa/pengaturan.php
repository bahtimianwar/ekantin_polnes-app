<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') { header('Location: /login.php'); exit; }
require_once '../../includes/db.php';
$id_user = $_SESSION['user']['id_user'];

$success = '';
$error = '';

$stmt = $pdo->prepare("SELECT nama, nim, no_hp FROM users WHERE id_user = ?");
$stmt->execute([$id_user]);
$user_data = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama  = trim($_POST['nama'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        if (empty($nama)) {
            $error = 'Nama tidak boleh kosong.';
        } else {
            $pdo->prepare("UPDATE users SET nama=?, no_hp=? WHERE id_user=?")->execute([$nama, $no_hp, $id_user]);
            $_SESSION['user']['nama'] = $nama;
            $user_data['nama']  = $nama;
            $user_data['no_hp'] = $no_hp;
            $success = 'Profil berhasil diperbarui!';
        }
    }

    if ($action === 'update_password') {
        $pw_lama  = $_POST['password_lama'] ?? '';
        $pw_baru  = $_POST['password_baru'] ?? '';
        $pw_ulang = $_POST['password_ulang'] ?? '';
        $stmt2 = $pdo->prepare("SELECT password FROM users WHERE id_user = ?");
        $stmt2->execute([$id_user]);
        $row = $stmt2->fetch();
        if (!password_verify($pw_lama, $row['password'])) {
            $error = 'Password lama tidak benar.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($pw_baru !== $pw_ulang) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $pdo->prepare("UPDATE users SET password=? WHERE id_user=?")->execute([password_hash($pw_baru, PASSWORD_DEFAULT), $id_user]);
            $success = 'Password berhasil diubah!';
        }
    }
}

require_once '../../includes/sidebar_mahasiswa.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-cog" style="color:var(--ac);margin-right:10px;"></i>Pengaturan Akun</h1>
    <p>Kelola informasi profil dan keamanan akun Anda</p>
</div>

<style>
    .settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    @media(max-width:760px){.settings-grid{grid-template-columns:1fr;}}
    .settings-card{background:var(--bg-card);border-radius:16px;box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:0;}
    .settings-card-header{padding:16px 22px;background:var(--bg-card2);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
    .settings-card-header .hdr-icon{width:34px;height:34px;background:var(--ac-lt);color:var(--ac-tx);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
    .settings-card-header h3{font-size:15px;font-weight:700;color:var(--text-1);}
    .settings-card-body{padding:22px;}
    .profile-banner{display:flex;align-items:center;gap:16px;padding:18px 22px;background:var(--ac-lt);border-bottom:1px solid var(--border);}
    .profile-avatar{width:56px;height:56px;background:linear-gradient(135deg,var(--ac),var(--ac-dk));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:22px;flex-shrink:0;}
    .profile-info .pname{font-size:16px;font-weight:800;color:var(--text-1);}
    .profile-info .pnim{font-size:12px;color:var(--text-3);margin-top:2px;}
    .form-group{margin-bottom:16px;}
    .form-group label{display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
    .form-group input{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;background:var(--inp-bg);color:var(--text-1);transition:border-color .2s;outline:none;font-family:inherit;}
    .form-group input:focus{border-color:var(--ac);}
    .form-group input[readonly]{background:var(--bg-card2);color:var(--text-3);cursor:not-allowed;}
    .btn-save{width:100%;padding:12px;background:var(--ac);color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px;font-family:inherit;}
    .btn-save:hover{background:var(--ac-dk);}
    .btn-logout{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;margin-top:12px;background:transparent;color:#ef4444;border:1.5px solid #ef4444;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;text-decoration:none;font-family:inherit;}
    .btn-logout:hover{background:#ef4444;color:white;}
    .alert{padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
    .alert-success{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;}
    .alert-error{background:#fce4ec;color:#c62828;border:1px solid #f8bbd0;}
    [data-theme="dark"] .alert-success{background:rgba(34,197,94,.12);color:#4ade80;border-color:rgba(34,197,94,.2);}
    [data-theme="dark"] .alert-error{background:rgba(239,68,68,.12);color:#f87171;border-color:rgba(239,68,68,.2);}
</style>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="settings-grid">

    <div class="settings-card">
        <div class="profile-banner">
            <div class="profile-avatar"><?= strtoupper(substr($user_data['nama'], 0, 1)) ?></div>
            <div class="profile-info">
                <div class="pname"><?= htmlspecialchars($user_data['nama']) ?></div>
                <div class="pnim">NIM: <?= htmlspecialchars($user_data['nim'] ?? '-') ?></div>
            </div>
        </div>
        <div class="settings-card-header">
            <div class="hdr-icon"><i class="fas fa-user"></i></div>
            <h3>Informasi Profil</h3>
        </div>
        <div class="settings-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profil">
                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" value="<?= htmlspecialchars($user_data['nim'] ?? '-') ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user_data['nama']) ?>" required placeholder="Nama lengkap">
                </div>
                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($user_data['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </form>
            <a href="../../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar dari Akun</a>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">
            <div class="hdr-icon"><i class="fas fa-lock"></i></div>
            <h3>Ganti Password</h3>
        </div>
        <div class="settings-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="password_lama" required placeholder="Masukkan password lama">
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="password_baru" required placeholder="Minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="password_ulang" required placeholder="Ulangi password baru">
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-key"></i> Ubah Password</button>
            </form>
        </div>
    </div>

</div>

</div></body></html>
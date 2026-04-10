<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] === 'update_profil') {
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $nim   = trim($_POST['nim'] ?? '');

        if (empty($nama) || empty($email) || empty($no_hp)) {
            $error = 'Nama, email, dan no HP tidak boleh kosong.';
        } else {
            $cek = $pdo->prepare("SELECT id_user FROM users WHERE (email=? OR no_hp=? OR nim=?) AND id_user != ?");
            $cek->execute([$email, $no_hp, $nim ?: null, $id_user]);
            if ($cek->fetch()) {
                $error = 'Email, No HP, atau NIM sudah digunakan akun lain.';
            } else {
                $pdo->prepare("UPDATE users SET nama=?, email=?, no_hp=?, nim=? WHERE id_user=?")
                    ->execute([$nama, $email, $no_hp, $nim ?: null, $id_user]);
                $_SESSION['user']['nama']  = $nama;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['no_hp'] = $no_hp;
                $_SESSION['user']['nim']   = $nim;
                $success = 'Profil berhasil diperbarui.';
            }
        }
    }

    if ($_POST['aksi'] === 'ganti_password') {
        $pw_lama    = $_POST['pw_lama'] ?? '';
        $pw_baru    = $_POST['pw_baru'] ?? '';
        $pw_konfirm = $_POST['pw_konfirm'] ?? '';

        if (empty($pw_lama) || empty($pw_baru) || empty($pw_konfirm)) {
            $error = 'Semua field password harus diisi.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($pw_baru !== $pw_konfirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id_user=?");
            $stmt->execute([$id_user]);
            $row = $stmt->fetch();
            if (!password_verify($pw_lama, $row['password'])) {
                $error = 'Password lama tidak benar.';
            } else {
                $pdo->prepare("UPDATE users SET password=? WHERE id_user=?")
                    ->execute([password_hash($pw_baru, PASSWORD_DEFAULT), $id_user]);
                $success = 'Password berhasil diubah.';
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user=?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();
?>

<?php require_once '../../includes/sidebar_mahasiswa.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-user-cog" style="color:#065f46; margin-right:10px;"></i>Pengaturan Akun</h1>
    <p>Kelola informasi profil dan keamanan akun kamu</p>
</div>

<style>
    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
    @media(max-width:900px) { .settings-grid { grid-template-columns: 1fr; } }

    .card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #064e3b, #065f46); padding: 16px 22px; display: flex; align-items: center; gap: 12px; }
    .card-header-icon { width: 36px; height: 36px; background: rgba(255,255,255,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 15px; }
    .card-header h3 { color: white; font-size: 15px; font-weight: 700; }
    .card-header p { color: rgba(255,255,255,0.65); font-size: 12px; }
    .card-body { padding: 22px; }

    .avatar-section { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0; }
    .avatar-circle { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #059669, #065f46); display: flex; align-items: center; justify-content: center; color: white; font-size: 26px; font-weight: 800; flex-shrink: 0; }
    .avatar-info .name { font-size: 16px; font-weight: 700; color: #1a1a2e; }
    .avatar-info .role { font-size: 12px; color: #888; margin-top: 2px; }
    .avatar-info .nim-badge { display: inline-block; margin-top: 6px; padding: 3px 10px; background: #d1fae5; color: #065f46; border-radius: 20px; font-size: 11px; font-weight: 700; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12.5px; font-weight: 700; color: #374151; letter-spacing: 0.3px; text-transform: uppercase; margin-bottom: 7px; }
    .form-group input { width: 100%; padding: 11px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px; font-size: 14px; color: #333; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit; background: #fafafa; }
    .form-group input:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.1); background: white; }

    .btn-save { padding: 11px 24px; background: linear-gradient(135deg, #064e3b, #059669); color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: opacity 0.2s; }
    .btn-save:hover { opacity: 0.88; }

    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error   { background: #fce4ec; color: #c62828; border: 1px solid #f8bbd0; }

    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 42px; }
    .pw-toggle { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #aaa; cursor: pointer; font-size: 14px; }
    .pw-toggle:hover { color: #555; }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="settings-grid">

    <!-- Profil -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fas fa-user"></i></div>
            <div><h3>Informasi Profil</h3><p>Perbarui data diri kamu</p></div>
        </div>
        <div class="card-body">
            <div class="avatar-section">
                <div class="avatar-circle"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
                <div class="avatar-info">
                    <div class="name"><?= htmlspecialchars($user['nama']) ?></div>
                    <div class="role">Mahasiswa</div>
                    <?php if ($user['nim']): ?>
                        <span class="nim-badge">NIM: <?= htmlspecialchars($user['nim']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="update_profil">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>
                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" name="nim" value="<?= htmlspecialchars($user['nim'] ?? '') ?>" placeholder="Opsional">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>No HP</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- Ganti Password -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fas fa-lock"></i></div>
            <div><h3>Ganti Password</h3><p>Perbarui keamanan akun kamu</p></div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="aksi" value="ganti_password">
                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="pw-wrap">
                        <input type="password" name="pw_lama" id="pw_lama" placeholder="Masukkan password lama" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('pw_lama', this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="pw-wrap">
                        <input type="password" name="pw_baru" id="pw_baru" placeholder="Min. 6 karakter" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('pw_baru', this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="pw-wrap">
                        <input type="password" name="pw_konfirm" id="pw_konfirm" placeholder="Ulangi password baru" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('pw_konfirm', this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-key"></i> Ganti Password</button>
            </form>
        </div>
    </div>

</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

</div></body></html>
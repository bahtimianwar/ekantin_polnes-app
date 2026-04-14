<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$step = $_POST['step'] ?? 'form';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'mahasiswa';
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $nim = trim($_POST['nim'] ?? '');
    $nama_kantin = trim($_POST['nama_kantin'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $no_rek = trim($_POST['no_rek'] ?? '');

    // Validasi
    if (empty($nama) || empty($email) || empty($no_hp) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif ($role === 'mahasiswa' && empty($nim)) {
        $error = 'NIM wajib diisi untuk mahasiswa.';
    } elseif ($role === 'penjual' && (empty($nama_kantin) || empty($lokasi))) {
        $error = 'Nama kantin dan lokasi wajib diisi.';
    } else {
        // Cek duplikat
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ? OR no_hp = ?");
        $stmt->execute([$email, $no_hp]);
        if ($stmt->fetch()) {
            $error = 'Email atau nomor HP sudah terdaftar.';
        } elseif ($role === 'mahasiswa') {
            $stmt2 = $pdo->prepare("SELECT id_user FROM users WHERE nim = ?");
            $stmt2->execute([$nim]);
            if ($stmt2->fetch()) $error = 'NIM sudah terdaftar.';
        }

        if (!$error) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $pdo->beginTransaction();

                $pdo->prepare("INSERT INTO users (nim, nama, email, no_hp, password, role) VALUES (?,?,?,?,?,?)")
                    ->execute([$role === 'mahasiswa' ? $nim : null, $nama, $email, $no_hp, $hash, $role]);

                $id_user = $pdo->lastInsertId();

                if ($role === 'mahasiswa') {
                    $pdo->prepare("INSERT INTO saldo (id_user, saldo) VALUES (?, 0)")->execute([$id_user]);
                    $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Selamat Datang! 🎉', ?)")
                        ->execute([$id_user, "Halo $nama! Akun E-Kantin kamu berhasil dibuat. Silakan top up saldo untuk mulai memesan."]);
                } else {
                    $pdo->prepare("INSERT INTO penjual (id_user, nama_kantin, lokasi, no_rek) VALUES (?,?,?,?)")
                        ->execute([$id_user, $nama_kantin, $lokasi, $no_rek]);
                }

                $pdo->commit();
                $success = 'Akun berhasil dibuat! Silakan login.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — E-Kantin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; min-height:100vh; background:linear-gradient(135deg,#004d40 0%,#00695c 40%,#1a237e 100%); display:flex; align-items:center; justify-content:center; padding:20px; }
        .register-box { background:white; border-radius:24px; width:100%; max-width:500px; box-shadow:0 25px 60px rgba(0,0,0,0.3); overflow:hidden; }
        .reg-header { background:linear-gradient(135deg,#1a237e,#3949ab); padding:28px 36px; color:white; }
        .reg-header .brand { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
        .brand-icon { width:44px; height:44px; background:linear-gradient(135deg,#ff6b35,#f7931e); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .reg-header h2 { font-size:22px; font-weight:800; }
        .reg-header p { opacity:.7; font-size:13px; margin-top:4px; }
        .reg-body { padding:32px 36px; }

        .role-tabs { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:24px; }
        .role-tab { padding:12px; border:2px solid #e0e0e0; border-radius:12px; text-align:center; cursor:pointer; transition:all .2s; }
        .role-tab input { display:none; }
        .role-tab i { font-size:22px; display:block; margin-bottom:6px; color:#aaa; }
        .role-tab span { font-size:13px; font-weight:600; color:#666; }
        .role-tab.selected { border-color:#3949ab; background:#f0f2ff; }
        .role-tab.selected i, .role-tab.selected span { color:#3949ab; }

        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:#444; margin-bottom:7px; }
        .input-wrap { position:relative; }
        .input-wrap i.icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#aaa; font-size:14px; }
        .input-wrap input, .input-wrap textarea {
            width:100%; padding:11px 14px 11px 40px;
            border:1.5px solid #e0e0e0; border-radius:10px;
            font-size:14px; color:#333; outline:none;
            transition:border-color .2s; font-family:inherit;
        }
        .input-wrap input:focus, .input-wrap textarea:focus { border-color:#3949ab; box-shadow:0 0 0 3px rgba(57,73,171,.1); }
        .input-wrap textarea { padding-top:11px; resize:none; height:70px; }

        .penjual-fields { display:none; }
        .penjual-fields.show { display:block; }
        .mahasiswa-fields { display:block; }
        .mahasiswa-fields.hide { display:none; }

        .divider { border:none; border-top:1px solid #f0f0f0; margin:18px 0; }

        .btn-register { width:100%; padding:13px; background:linear-gradient(135deg,#1a237e,#3949ab); color:white; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; transition:opacity .2s; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-register:hover { opacity:.9; }

        .alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:13.5px; font-weight:500; display:flex; align-items:center; gap:8px; }
        .alert-error { background:#fce4ec; color:#c62828; border:1px solid #f8bbd0; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }

        .login-link { text-align:center; margin-top:18px; font-size:13.5px; color:#888; }
        .login-link a { color:#3949ab; font-weight:600; text-decoration:none; }
        .login-link a:hover { text-decoration:underline; }
    </style>
</head>
<body>
<div class="register-box">
    <div class="reg-header">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-utensils"></i></div>
            <div>
                <div style="font-size:18px;font-weight:800;">E-Kantin</div>
                <div style="font-size:11px;opacity:.7;">Sistem Kantin Digital</div>
            </div>
        </div>
        <h2>Buat Akun Baru</h2>
        <p>Daftarkan diri kamu sebagai mahasiswa atau penjual</p>
    </div>
    <div class="reg-body">

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <div style="text-align:center;"><a href="login.php" style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#1a237e,#3949ab);color:white;border-radius:10px;font-weight:700;text-decoration:none;font-size:14px;"><i class="fas fa-sign-in-alt"></i> Login Sekarang</a></div>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="regForm">
            <!-- Pilih Role -->
            <div class="role-tabs">
                <label class="role-tab <?= ($_POST['role']??'mahasiswa')==='mahasiswa'?'selected':'' ?>" id="tab-mahasiswa">
                    <input type="radio" name="role" value="mahasiswa" <?= ($_POST['role']??'mahasiswa')==='mahasiswa'?'checked':'' ?> onchange="switchRole('mahasiswa')">
                    <i class="fas fa-user-graduate"></i>
                    <span>Mahasiswa</span>
                </label>
                <label class="role-tab <?= ($_POST['role']??'')==='penjual'?'selected':'' ?>" id="tab-penjual">
                    <input type="radio" name="role" value="penjual" <?= ($_POST['role']??'')==='penjual'?'checked':'' ?> onchange="switchRole('penjual')">
                    <i class="fas fa-store"></i>
                    <span>Penjual Kantin</span>
                </label>
            </div>

            <!-- Field Mahasiswa -->
            <div class="mahasiswa-fields <?= ($_POST['role']??'')==='penjual'?'hide':'' ?>" id="mahasiswa-fields">
                <div class="form-group">
                    <label>NIM</label>
                    <div class="input-wrap"><i class="fas fa-id-card icon"></i>
                        <input type="text" name="nim" value="<?= htmlspecialchars($_POST['nim']??'') ?>" placeholder="Nomor Induk Mahasiswa">
                    </div>
                </div>
            </div>

            <!-- Field Penjual -->
            <div class="penjual-fields <?= ($_POST['role']??'')==='penjual'?'show':'' ?>" id="penjual-fields">
                <div class="form-group">
                    <label>Nama Kantin</label>
                    <div class="input-wrap"><i class="fas fa-store icon"></i>
                        <input type="text" name="nama_kantin" value="<?= htmlspecialchars($_POST['nama_kantin']??'') ?>" placeholder="Contoh: Kantin Pak Budi">
                    </div>
                </div>
                <div class="form-group">
                    <label>Lokasi Kantin</label>
                    <div class="input-wrap"><i class="fas fa-map-marker-alt icon"></i>
                        <input type="text" name="lokasi" value="<?= htmlspecialchars($_POST['lokasi']??'') ?>" placeholder="Contoh: Gedung A Lantai 1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Nomor Rekening</label>
                    <div class="input-wrap"><i class="fas fa-university icon"></i>
                        <input type="text" name="no_rek" value="<?= htmlspecialchars($_POST['no_rek']??'') ?>" placeholder="Contoh: BRI 1234-5678-9012">
                    </div>
                </div>
            </div>

            <hr class="divider">

            <div class="form-group">
                <label>Nama Lengkap</label>
                <div class="input-wrap"><i class="fas fa-user icon"></i>
                    <input type="text" name="nama" value="<?= htmlspecialchars($_POST['nama']??'') ?>" placeholder="Nama lengkap" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap"><i class="fas fa-envelope icon"></i>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" placeholder="contoh@email.com" required>
                </div>
            </div>
            <div class="form-group">
                <label>Nomor HP</label>
                <div class="input-wrap"><i class="fas fa-phone icon"></i>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($_POST['no_hp']??'') ?>" placeholder="08xx-xxxx-xxxx" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap"><i class="fas fa-lock icon"></i>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div class="input-wrap"><i class="fas fa-lock icon"></i>
                    <input type="password" name="konfirmasi" placeholder="Ulangi password" required>
                </div>
            </div>

            <button type="submit" class="btn-register"><i class="fas fa-user-plus"></i> Daftar Sekarang</button>
        </form>

        <?php endif; ?>

        <div class="login-link">Sudah punya akun? <a href="login.php">Login di sini</a></div>
    </div>
</div>

<script>
function switchRole(role) {
    document.getElementById('mahasiswa-fields').classList.toggle('hide', role === 'penjual');
    document.getElementById('penjual-fields').classList.toggle('show', role === 'penjual');
    document.getElementById('tab-mahasiswa').classList.toggle('selected', role === 'mahasiswa');
    document.getElementById('tab-penjual').classList.toggle('selected', role === 'penjual');
}
</script>
</body>
</html>
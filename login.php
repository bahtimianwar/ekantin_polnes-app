<?php
session_start();
require_once 'includes/db.php';

// Kalau sudah login, redirect ke dashboard
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: dashboard/admin/dashboard.php");
    } elseif ($_SESSION['user']['role'] === 'penjual') {
        header("Location: dashboard/penjual/dashboard.php");
    } else {
        header("Location: dashboard/mahasiswa/beranda.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($user_id) || empty($password)) {
        $error = 'User ID dan password tidak boleh kosong.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE email = ? OR nim = ? OR no_hp = ?
            LIMIT 1
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerasi ID untuk keamanan saat login sukses
            session_regenerate_id(true);
            $_SESSION['user'] = $user;

            // Logika Redirect Berdasarkan Role
            if ($user['role'] === 'admin') {
                header("Location: dashboard/admin/dashboard.php");
            } elseif ($user['role'] === 'penjual') {
                $stmt2 = $pdo->prepare("SELECT * FROM penjual WHERE id_user = ?");
                $stmt2->execute([$user['id_user']]);
                $penjual = $stmt2->fetch();
                $_SESSION['penjual'] = $penjual;
                header("Location: dashboard/penjual/dashboard.php");
            } else {
                header("Location: dashboard/mahasiswa/beranda.php");
            }
            exit;
        } else {
            $error = 'User ID atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — E-Kantin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: #060d1f;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        /* Orbs via pseudo-elements */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            animation: orbFloat 10s ease-in-out infinite alternate;
        }
        body::before {
            width: 650px; height: 650px;
            background: radial-gradient(circle, #00897b55, transparent 70%);
            top: -200px; left: -200px;
            opacity: 0.9;
        }
        body::after {
            width: 550px; height: 550px;
            background: radial-gradient(circle, #1a237e88, transparent 70%);
            bottom: -160px; right: -160px;
            opacity: 0.9;
            animation-delay: -5s;
        }

        /* Orb tengah cyan */
        .bg-orb-mid {
            position: fixed;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, #00bcd444, transparent 70%);
            filter: blur(80px);
            top: 38%; left: 36%;
            animation: orbFloat 13s ease-in-out infinite alternate;
            animation-delay: -3s;
            pointer-events: none;
        }

        /* Orb oranye kecil */
        .bg-orb-orange {
            position: fixed;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, #ff6f0033, transparent 70%);
            filter: blur(70px);
            top: 5%; right: 10%;
            animation: orbFloat 9s ease-in-out infinite alternate-reverse;
            pointer-events: none;
        }

        /* Grid dot overlay */
        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }

        /* Bintang */
        .bg-stars { position: fixed; inset: 0; pointer-events: none; }
        .star {
            position: absolute;
            border-radius: 50%;
            background: #ffffff;
            animation: twinkle var(--d, 3s) ease-in-out infinite alternate;
            opacity: 0;
        }

        @keyframes twinkle {
            from { opacity: 0; }
            to   { opacity: var(--op, 0.5); }
        }
        @keyframes orbFloat {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 40px) scale(1.08); }
        }

        /* Card wrapper */
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 520px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.07),
                0 30px 70px rgba(0,0,0,0.55);
            position: relative;
            z-index: 10;
        }

        /* Sisi Kiri */
        .login-left {
            flex: 1;
            background: linear-gradient(160deg, #1a237e, #283593, #3949ab);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }
        .brand { display: flex; align-items: center; gap: 14px; margin-bottom: 40px; }
        .brand-icon {
            width: 54px; height: 54px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
        }
        .brand-text h1 { font-size: 26px; font-weight: 800; }
        .brand-text p { font-size: 13px; opacity: 0.7; margin-top: 2px; }
        .login-left h2 { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        .login-left p { font-size: 14px; opacity: 0.75; line-height: 1.7; margin-bottom: 30px; }
        .feature-list { list-style: none; }
        .feature-list li {
            display: flex; align-items: center; gap: 10px;
            font-size: 13.5px; opacity: 0.85;
            margin-bottom: 12px;
        }
        .feature-list li i {
            width: 28px; height: 28px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }

        /* Sisi Kanan */
        .login-right {
            flex: 1;
            background: white;
            padding: 50px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right h2 { font-size: 26px; font-weight: 800; color: #1a1a1a; margin-bottom: 6px; }
        .login-right p { font-size: 14px; color: #888; margin-bottom: 32px; }

        .alert-error {
            background: #fce4ec;
            color: #c62828;
            border: 1px solid #f8bbd0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 13px; font-weight: 600; color: #444;
            margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: #aaa; font-size: 15px;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px; color: #333;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .input-wrap input:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(57,73,171,0.1);
        }
        .toggle-pw {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer; color: #aaa; font-size: 15px;
            background: none; border: none;
        }
        .toggle-pw:hover { color: #555; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #1a237e, #3949ab);
            color: white;
            border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 8px;
        }
        .btn-login:hover { opacity: 0.92; }
        .btn-login:active { transform: scale(0.99); }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13.5px; color: #888;
        }
        .register-link a { color: #3949ab; font-weight: 600; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }

        @media (max-width: 680px) {
            .login-left { display: none; }
            .login-right { padding: 40px 28px; }
            .login-wrapper { max-width: 420px; border-radius: 20px; }
        }
    </style>
</head>
<body>

<div class="bg-orb-mid"></div>
<div class="bg-orb-orange"></div>
<div class="bg-grid"></div>
<div class="bg-stars" id="bgStars"></div>

<div class="login-wrapper">
    <!-- Kiri -->
    <div class="login-left">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-utensils"></i></div>
            <div class="brand-text">
                <h1>E-Kantin</h1>
                <p>Sistem Kantin Digital</p>
            </div>
        </div>
        <h2>Pesan Makanan Lebih Mudah</h2>
        <p>Platform digital untuk memesan makanan dari kantin kampus kapan saja dan di mana saja.</p>
        <ul class="feature-list">
            <li><i class="fas fa-bolt"></i> Pesan cepat tanpa antri</li>
            <li><i class="fas fa-wallet"></i> Bayar pakai saldo digital</li>
            <li><i class="fas fa-bell"></i> Notifikasi real-time</li>
            <li><i class="fas fa-history"></i> Riwayat transaksi lengkap</li>
        </ul>
    </div>

    <!-- Kanan -->
    <div class="login-right">
        <h2>Selamat Datang 👋</h2>
        <p>Masuk untuk pesan makanan favoritmu</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>NIM / Email / No HP</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="user_id"
                           value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>"
                           placeholder="Masukkan NIM, email, atau no HP"
                           autofocus required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password"
                           placeholder="Masukkan password" required>
                    <button type="button" class="toggle-pw" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>
</div>

<script>
// Generate bintang acak
const container = document.getElementById('bgStars');
for (let i = 0; i < 60; i++) {
    const s = document.createElement('div');
    s.className = 'star';
    const size = Math.random() * 2.5 + 0.6;
    s.style.cssText = `
        width:${size}px; height:${size}px;
        left:${Math.random() * 100}%;
        top:${Math.random() * 100}%;
        --d:${(Math.random() * 4 + 2).toFixed(1)}s;
        --op:${(Math.random() * 0.5 + 0.1).toFixed(2)};
        animation-delay:${(Math.random() * 6).toFixed(1)}s;
    `;
    container.appendChild(s);
}

function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penjual') {
    header('Location: /login.php'); exit;
}
require_once '../../includes/db.php';

$id_penjual = $_SESSION['penjual']['id_penjual'] ?? 0;
$success = ''; $error = '';

// Hapus menu
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM menu WHERE id_menu = ? AND id_penjual = ?")->execute([$_GET['hapus'], $id_penjual]);
    header("Location: menu.php?deleted=1"); exit;
}

// Tambah / Edit menu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_menu = (int)($_POST['id_menu'] ?? 0);
    $nama_menu = trim($_POST['nama_menu'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = (int)str_replace(['.', ','], '', $_POST['harga'] ?? 0);
    $gambar_lama = $_POST['gambar_lama'] ?? '';
    $gambar = $gambar_lama;

    if (empty($nama_menu) || $harga <= 0) {
        $error = 'Nama menu dan harga wajib diisi.';
    } else {
        // Upload gambar
        if (!empty($_FILES['gambar']['name'])) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array(strtolower($ext), $allowed)) {
                $error = 'Format gambar harus JPG, PNG, atau WEBP.';
            } else {
                $upload_dir = '../../assets/menu/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $filename)) {
                    $gambar = $filename;
                }
            }
        }

        if (!$error) {
            if ($id_menu > 0) {
                $pdo->prepare("UPDATE menu SET nama_menu=?, kategori=?, harga=?, gambar=? WHERE id_menu=? AND id_penjual=?")
                    ->execute([$nama_menu, $kategori, $harga, $gambar, $id_menu, $id_penjual]);
                $success = 'Menu berhasil diperbarui!';
            } else {
                $pdo->prepare("INSERT INTO menu (id_penjual, nama_menu, kategori, harga, gambar) VALUES (?,?,?,?,?)")
                    ->execute([$id_penjual, $nama_menu, $kategori, $harga, $gambar]);
                $success = 'Menu berhasil ditambahkan!';
            }
        }
    }
}

// Edit mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id_menu = ? AND id_penjual = ?");
    $stmt->execute([$_GET['edit'], $id_penjual]);
    $edit_data = $stmt->fetch();
}

// Ambil semua menu
$stmt = $pdo->prepare("SELECT * FROM menu WHERE id_penjual = ? ORDER BY kategori, nama_menu");
$stmt->execute([$id_penjual]);
$menu_list = $stmt->fetchAll();

if (isset($_GET['deleted'])) $success = 'Menu berhasil dihapus!';
?>

<?php require_once '../../includes/sidebar_penjual.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-book-open" style="color:#3949ab;margin-right:10px;"></i>Kelola Menu</h1>
    <p>Tambah, edit, dan hapus menu kantin Anda</p>
</div>

<style>
    .menu-layout { display:grid; grid-template-columns:1fr 1.5fr; gap:24px; }
    @media(max-width:900px) { .menu-layout { grid-template-columns:1fr; } }
    .form-card { background:white; border-radius:16px; box-shadow:0 2px 14px rgba(0,0,0,0.06); overflow:hidden; position:sticky; top:20px; }
    .form-card-header { background:linear-gradient(135deg,#1a237e,#3949ab); padding:18px 24px; color:white; display:flex; align-items:center; gap:10px; }
    .form-card-header i { font-size:18px; }
    .form-card-header h3 { font-size:16px; font-weight:700; }
    .form-card-body { padding:24px; }
    .form-group { margin-bottom:16px; }
    .form-group label { display:block; font-size:13px; font-weight:600; color:#444; margin-bottom:7px; }
    .form-group input, .form-group select {
        width:100%; padding:10px 14px; border:1.5px solid #e0e0e0; border-radius:10px;
        font-size:14px; color:#333; outline:none; transition:border-color .2s; background:white; font-family:inherit;
    }
    .form-group input:focus, .form-group select:focus { border-color:#3949ab; box-shadow:0 0 0 3px rgba(57,73,171,.1); }
    .btn-submit { width:100%; padding:12px; background:linear-gradient(135deg,#1a237e,#3949ab); color:white; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:opacity .2s; }
    .btn-submit:hover { opacity:.9; }
    .btn-reset { width:100%; padding:10px; background:#f5f5f5; color:#666; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; margin-top:8px; transition:background .2s; }
    .btn-reset:hover { background:#e0e0e0; }

    .alert { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13.5px; display:flex; align-items:center; gap:8px; }
    .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
    .alert-error { background:#fce4ec; color:#c62828; border:1px solid #f8bbd0; }

    .preview-img { width:100%; height:120px; object-fit:cover; border-radius:8px; margin-top:8px; display:none; }
    .preview-img.show { display:block; }
    .current-img { width:100%; height:100px; object-fit:cover; border-radius:8px; margin-top:8px; }

    .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
    .menu-card { background:white; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow:hidden; transition:transform .2s; }
    .menu-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.1); }
    .menu-img { width:100%; height:130px; object-fit:cover; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-size:40px; color:#ddd; }
    .menu-img img { width:100%; height:130px; object-fit:cover; }
    .menu-card-body { padding:14px; }
    .menu-card-body .nama { font-size:14px; font-weight:700; color:#222; margin-bottom:4px; }
    .menu-card-body .kategori { font-size:11.5px; color:#aaa; background:#f5f5f5; padding:2px 8px; border-radius:8px; display:inline-block; margin-bottom:8px; }
    .menu-card-body .harga { font-size:16px; font-weight:800; color:#1a237e; }
    .menu-card-actions { display:flex; gap:6px; margin-top:10px; }
    .btn-edit { flex:1; padding:7px; background:#e8eaf6; color:#3949ab; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; transition:background .2s; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; }
    .btn-edit:hover { background:#3949ab; color:white; }
    .btn-hapus { flex:1; padding:7px; background:#fce4ec; color:#c62828; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; transition:background .2s; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; }
    .btn-hapus:hover { background:#c62828; color:white; }
    .empty-state { text-align:center; padding:60px 20px; color:#ccc; grid-column:1/-1; }
    .empty-state i { font-size:50px; display:block; margin-bottom:14px; }
    .kategori-header { font-size:12px; font-weight:700; color:#aaa; text-transform:uppercase; letter-spacing:1px; grid-column:1/-1; margin-top:8px; padding-bottom:6px; border-bottom:1px solid #f0f0f0; }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="menu-layout">
    <!-- Form Tambah/Edit -->
    <div class="form-card">
        <div class="form-card-header">
            <i class="fas fa-<?= $edit_data ? 'edit' : 'plus-circle' ?>"></i>
            <h3><?= $edit_data ? 'Edit Menu' : 'Tambah Menu Baru' ?></h3>
        </div>
        <div class="form-card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_menu" value="<?= $edit_data['id_menu'] ?? 0 ?>">
                <input type="hidden" name="gambar_lama" value="<?= $edit_data['gambar'] ?? '' ?>">

                <div class="form-group">
                    <label>Nama Menu</label>
                    <input type="text" name="nama_menu" value="<?= htmlspecialchars($edit_data['nama_menu'] ?? '') ?>" placeholder="Contoh: Nasi Goreng Spesial" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori">
                        <?php foreach (['MAKANAN','MINUMAN','SNACK','PAKET'] as $k): ?>
                        <option value="<?= $k ?>" <?= ($edit_data['kategori']??'')===$k?'selected':'' ?>><?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" value="<?= $edit_data['harga'] ?? '' ?>" placeholder="Contoh: 15000" min="0" required>
                </div>
                <div class="form-group">
                    <label>Foto Menu (opsional)</label>
                    <input type="file" name="gambar" accept="image/*" onchange="previewImage(this)">
                    <?php if (!empty($edit_data['gambar'])): ?>
                        <img src="../../assets/menu/<?= htmlspecialchars($edit_data['gambar']) ?>" class="current-img" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <img id="preview" class="preview-img" src="" alt="Preview">
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?>"></i>
                    <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Menu' ?>
                </button>
                <?php if ($edit_data): ?>
                <a href="menu.php" class="btn-reset" style="display:block;text-align:center;text-decoration:none;padding:10px;">
                    <i class="fas fa-times"></i> Batal Edit
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Daftar Menu -->
    <div>
        <div style="font-size:15px;font-weight:700;color:#222;margin-bottom:16px;">
            Daftar Menu (<?= count($menu_list) ?> item)
        </div>
        <div class="menu-grid">
            <?php if (empty($menu_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <p>Belum ada menu. Tambahkan menu pertama kamu!</p>
                </div>
            <?php else: ?>
            <?php
            $current_kat = '';
            foreach ($menu_list as $m):
                if ($m['kategori'] !== $current_kat) {
                    $current_kat = $m['kategori'];
                    echo "<div class='kategori-header'>".htmlspecialchars($current_kat)."</div>";
                }
            ?>
            <div class="menu-card">
                <div class="menu-img">
                    <?php if ($m['gambar']): ?>
                        <img src="../../assets/menu/<?= htmlspecialchars($m['gambar']) ?>" alt="<?= htmlspecialchars($m['nama_menu']) ?>" onerror="this.parentElement.innerHTML='🍽️'">
                    <?php else: ?>
                        🍽️
                    <?php endif; ?>
                </div>
                <div class="menu-card-body">
                    <div class="nama"><?= htmlspecialchars($m['nama_menu']) ?></div>
                    <div class="kategori"><?= htmlspecialchars($m['kategori'] ?? '-') ?></div>
                    <div class="harga">Rp <?= number_format($m['harga'], 0, ',', '.') ?></div>
                    <div class="menu-card-actions">
                        <a href="?edit=<?= $m['id_menu'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <a href="?hapus=<?= $m['id_menu'] ?>" class="btn-hapus" onclick="return confirm('Hapus menu ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.add('show'); };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</div></body></html>
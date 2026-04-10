<?php
// Semua logika PHP DULU sebelum output HTML apapun
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header("Location: /login.php"); exit;
}

require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];
$id_kantin = (int)($_GET['id'] ?? 0);

if (!$id_kantin) {
    header("Location: beranda.php"); exit;
}

// Info kantin
$stmt = $pdo->prepare("SELECT pj.*, u.no_hp FROM penjual pj JOIN users u ON pj.id_user = u.id_user WHERE pj.id_penjual = ?");
$stmt->execute([$id_kantin]);
$kantin = $stmt->fetch();
if (!$kantin) { header("Location: beranda.php"); exit; }

// Menu per kategori
$stmt2 = $pdo->prepare("SELECT * FROM menu WHERE id_penjual = ? ORDER BY kategori, nama_menu");
$stmt2->execute([$id_kantin]);
$menu_all = $stmt2->fetchAll();

$menu_by_kat = [];
foreach ($menu_all as $m) {
    $menu_by_kat[$m['kategori'] ?? 'Lainnya'][] = $m;
}

// Ambil saldo untuk JS (harus sebelum sidebar output HTML)
$stmt_saldo = $pdo->prepare("SELECT COALESCE(saldo,0) FROM saldo WHERE id_user=?");
$stmt_saldo->execute([$id_user]);
$saldo_mahasiswa = (int)($stmt_saldo->fetchColumn() ?: 0);

// Baru include sidebar - ini mulai output HTML
require_once '../../includes/sidebar_mahasiswa.php';
?>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <a href="beranda.php" style="color:#065f46;font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1><?= htmlspecialchars($kantin['nama_kantin']) ?></h1>
            <p><i class="fas fa-map-marker-alt" style="color:#e53935;"></i> <?= htmlspecialchars($kantin['lokasi']) ?></p>
        </div>
    </div>
</div>

<style>
    .kantin-layout { display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start; }
    @media(max-width:960px) { .kantin-layout { grid-template-columns:1fr; } }

    .kat-label { font-size:13px; font-weight:700; color:#065f46; text-transform:uppercase; letter-spacing:1px; padding:14px 0 8px; border-bottom:2px solid #d1fae5; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
    .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; margin-bottom:24px; }
    .menu-card { background:white; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow:hidden; transition:transform .2s; }
    .menu-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.1); }
    .menu-img { width:100%; height:120px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-size:36px; }
    .menu-img img { width:100%; height:120px; object-fit:cover; }
    .menu-info { padding:12px; }
    .menu-info .nama { font-size:13.5px; font-weight:700; color:#222; margin-bottom:4px; }
    .menu-info .harga { font-size:15px; font-weight:800; color:#065f46; margin-bottom:10px; }
    .qty-control { display:flex; align-items:center; gap:8px; }
    .qty-btn { width:30px; height:30px; border:none; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; }
    .btn-minus { background:#fce4ec; color:#c62828; }
    .btn-minus:hover { background:#c62828; color:white; }
    .btn-plus { background:#d1fae5; color:#065f46; }
    .btn-plus:hover { background:#065f46; color:white; }
    .qty-num { font-size:14px; font-weight:700; min-width:24px; text-align:center; color:#333; }
    .btn-add { width:100%; padding:8px; background:#065f46; color:white; border:none; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer; transition:background .2s; display:flex; align-items:center; justify-content:center; gap:5px; margin-top:8px; }
    .btn-add:hover { background:#064e3b; }

    .cart-box { background:white; border-radius:16px; box-shadow:0 2px 14px rgba(0,0,0,0.08); overflow:hidden; position:sticky; top:20px; }
    .cart-header { background:linear-gradient(135deg,#064e3b,#059669); padding:16px 20px; color:white; display:flex; align-items:center; gap:10px; }
    .cart-header h3 { font-size:15px; font-weight:700; flex:1; }
    .cart-count { background:rgba(255,255,255,0.25); padding:2px 10px; border-radius:12px; font-size:13px; font-weight:700; }
    .cart-body { padding:16px; max-height:380px; overflow-y:auto; }
    .cart-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #f5f5f5; }
    .cart-item:last-child { border-bottom:none; }
    .cart-item-info { flex:1; }
    .cart-item-info .nama { font-size:13px; font-weight:600; color:#222; }
    .cart-item-info .harga { font-size:12px; color:#065f46; font-weight:600; }
    .cart-item-qty { display:flex; align-items:center; gap:6px; }
    .cq-btn { width:26px; height:26px; border:none; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; }
    .cq-minus { background:#fce4ec; color:#c62828; }
    .cq-plus { background:#d1fae5; color:#065f46; }
    .cq-num { font-size:13px; font-weight:700; min-width:20px; text-align:center; }
    .cart-empty { text-align:center; padding:40px 20px; color:#ccc; }
    .cart-empty i { font-size:36px; display:block; margin-bottom:10px; }
    .cart-footer { padding:16px; border-top:1px solid #f0f0f0; }
    .cart-total { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    .cart-total .label { font-size:13px; color:#666; font-weight:600; }
    .cart-total .amount { font-size:18px; font-weight:800; color:#064e3b; }
    .btn-checkout { width:100%; padding:13px; background:linear-gradient(135deg,#064e3b,#059669); color:white; border:none; border-radius:12px; font-size:15px; font-weight:700; cursor:pointer; transition:opacity .2s; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-checkout:hover { opacity:.9; }
    .btn-checkout:disabled { opacity:.4; cursor:not-allowed; }

    .empty-menu { text-align:center; padding:60px 20px; color:#ccc; }
    .empty-menu i { font-size:50px; display:block; margin-bottom:14px; }

    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; }
    .modal-overlay.show { display:flex; }
    .modal-box { background:white; border-radius:20px; padding:28px; width:90%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
</style>

<div class="kantin-layout">
    <!-- Menu -->
    <div>
        <?php if (empty($menu_all)): ?>
            <div class="empty-menu">
                <i class="fas fa-utensils"></i>
                <p>Belum ada menu tersedia</p>
            </div>
        <?php else: ?>
        <?php foreach ($menu_by_kat as $kat => $menus): ?>
            <div class="kat-label"><i class="fas fa-tag"></i> <?= htmlspecialchars($kat) ?></div>
            <div class="menu-grid">
            <?php foreach ($menus as $m): ?>
            <div class="menu-card">
                <div class="menu-img">
                    <?php if ($m['gambar']): ?>
                        <img src="../../assets/menu/<?= htmlspecialchars($m['gambar']) ?>" alt="" onerror="this.parentElement.innerHTML='🍽️'">
                    <?php else: ?>
                        🍽️
                    <?php endif; ?>
                </div>
                <div class="menu-info">
                    <div class="nama"><?= htmlspecialchars($m['nama_menu']) ?></div>
                    <div class="harga">Rp <?= number_format($m['harga'], 0, ',', '.') ?></div>
                    <div class="qty-control">
                        <button class="qty-btn btn-minus" onclick="changeQty(<?= $m['id_menu'] ?>, -1)">−</button>
                        <span class="qty-num" id="qty-<?= $m['id_menu'] ?>">0</span>
                        <button class="qty-btn btn-plus" onclick="changeQty(<?= $m['id_menu'] ?>, 1)">+</button>
                    </div>
                    <button class="btn-add" onclick="addToCart(<?= $m['id_menu'] ?>, '<?= addslashes(htmlspecialchars($m['nama_menu'])) ?>', <?= $m['harga'] ?>)">
                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Keranjang -->
    <div class="cart-box">
        <div class="cart-header">
            <i class="fas fa-shopping-cart"></i>
            <h3>Keranjang</h3>
            <span class="cart-count" id="cart-count">0</span>
        </div>
        <div class="cart-body" id="cart-body">
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Belum ada item</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span class="label">Total</span>
                <span class="amount" id="cart-total">Rp 0</span>
            </div>
            <button class="btn-checkout" id="btn-checkout" disabled onclick="openCheckout()">
                <i class="fas fa-credit-card"></i> Bayar Sekarang
            </button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal-overlay" id="modal-checkout">
    <div class="modal-box">
        <h3 style="font-size:18px;font-weight:800;margin-bottom:6px;">Konfirmasi Pesanan</h3>
        <p style="color:#888;font-size:13px;margin-bottom:20px;">Periksa pesanan sebelum membayar</p>
        <div id="modal-items" style="margin-bottom:16px;max-height:200px;overflow-y:auto;"></div>
        <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:#064e3b;padding:12px 0;border-top:2px solid #f0f0f0;margin-bottom:6px;">
            <span>Total</span><span id="modal-total"></span>
        </div>
        <div style="font-size:13px;color:#888;margin-bottom:20px;background:#f0fdf4;padding:10px 14px;border-radius:8px;">
            💰 Saldo kamu: <strong id="modal-saldo" style="color:#065f46;"></strong>
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="closeCheckout()" style="flex:1;padding:12px;background:#f5f5f5;color:#666;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Batal</button>
            <button onclick="submitOrder()" id="btn-confirm" style="flex:1;padding:12px;background:linear-gradient(135deg,#064e3b,#059669);color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">✅ Konfirmasi & Bayar</button>
        </div>
    </div>
</div>

<script>
let cart = {};
const KANTIN_ID = <?= $id_kantin ?>;
const SALDO = <?= $saldo_mahasiswa ?>;

function changeQty(id, delta) {
    const el = document.getElementById('qty-' + id);
    let val = parseInt(el.textContent) + delta;
    if (val < 0) val = 0;
    el.textContent = val;
}

function addToCart(id, nama, harga) {
    const qty = parseInt(document.getElementById('qty-' + id).textContent);
    if (qty === 0) { alert('Pilih jumlah terlebih dahulu!'); return; }
    if (cart[id]) { cart[id].qty += qty; }
    else { cart[id] = { nama, harga, qty }; }
    document.getElementById('qty-' + id).textContent = 0;
    renderCart();
}

function renderCart() {
    const body = document.getElementById('cart-body');
    const items = Object.entries(cart);
    let total = 0, count = 0;

    if (items.length === 0) {
        body.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart" style="font-size:36px;display:block;margin-bottom:10px;color:#ddd;"></i><p style="color:#ccc;">Belum ada item</p></div>';
        document.getElementById('cart-total').textContent = 'Rp 0';
        document.getElementById('cart-count').textContent = 0;
        document.getElementById('btn-checkout').disabled = true;
        return;
    }

    let html = '';
    items.forEach(([id, item]) => {
        const sub = item.harga * item.qty;
        total += sub; count += item.qty;
        html += `<div class="cart-item">
            <div class="cart-item-info">
                <div class="nama">${item.nama}</div>
                <div class="harga">Rp ${item.harga.toLocaleString('id-ID')} × ${item.qty} = <strong>Rp ${sub.toLocaleString('id-ID')}</strong></div>
            </div>
            <div class="cart-item-qty">
                <button class="cq-btn cq-minus" onclick="updateCart(${id}, -1)">−</button>
                <span class="cq-num">${item.qty}</span>
                <button class="cq-btn cq-plus" onclick="updateCart(${id}, 1)">+</button>
            </div>
        </div>`;
    });

    body.innerHTML = html;
    document.getElementById('cart-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('cart-count').textContent = count;
    document.getElementById('btn-checkout').disabled = false;
}

function updateCart(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

function openCheckout() {
    const items = Object.entries(cart);
    if (items.length === 0) return;
    let total = 0, html = '';
    items.forEach(([id, item]) => {
        const sub = item.harga * item.qty;
        total += sub;
        html += `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5f5f5;font-size:13.5px;">
            <span>${item.nama} <span style="color:#aaa;">×${item.qty}</span></span>
            <span style="font-weight:700;">Rp ${sub.toLocaleString('id-ID')}</span>
        </div>`;
    });
    document.getElementById('modal-items').innerHTML = html;
    document.getElementById('modal-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('modal-saldo').textContent = 'Rp ' + SALDO.toLocaleString('id-ID');

    if (total > SALDO) {
        document.getElementById('btn-confirm').disabled = true;
        document.getElementById('btn-confirm').style.opacity = '0.4';
        document.getElementById('modal-saldo').style.color = '#c62828';
        document.getElementById('modal-saldo').textContent += ' (tidak cukup!)';
    }

    document.getElementById('modal-checkout').classList.add('show');
}

function closeCheckout() {
    document.getElementById('modal-checkout').classList.remove('show');
}

function submitOrder() {
    const btn = document.getElementById('btn-confirm');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';

    fetch('proses_pesanan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            id_penjual: KANTIN_ID,
            items: Object.entries(cart).map(([id, i]) => ({
                id_menu: parseInt(id),
                jumlah: i.qty,
                harga: i.harga
            }))
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            cart = {};
            renderCart();
            closeCheckout();
            alert('✅ Pesanan berhasil dibuat! Tunggu konfirmasi penjual.');
            window.location.href = 'riwayat.php';
        } else {
            alert('❌ Gagal: ' + (d.message || 'Terjadi kesalahan'));
            btn.disabled = false;
            btn.textContent = '✅ Konfirmasi & Bayar';
        }
    })
    .catch(() => {
        alert('Terjadi kesalahan jaringan.');
        btn.disabled = false;
        btn.textContent = '✅ Konfirmasi & Bayar';
    });
}
</script>

</div></body></html>
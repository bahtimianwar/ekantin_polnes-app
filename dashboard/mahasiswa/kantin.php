<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header("Location: /login.php"); exit;
}
require_once '../../includes/db.php';

$id_user = $_SESSION['user']['id_user'];
$id_kantin = (int)($_GET['id'] ?? 0);
if (!$id_kantin) { header("Location: beranda.php"); exit; }

$stmt = $pdo->prepare("SELECT pj.*, u.no_hp FROM penjual pj JOIN users u ON pj.id_user = u.id_user WHERE pj.id_penjual = ?");
$stmt->execute([$id_kantin]);
$kantin = $stmt->fetch();
if (!$kantin) { header("Location: beranda.php"); exit; }

$stmt2 = $pdo->prepare("SELECT * FROM menu WHERE id_penjual = ? ORDER BY kategori, nama_menu");
$stmt2->execute([$id_kantin]);
$menu_all = $stmt2->fetchAll();

$menu_by_kat = [];
foreach ($menu_all as $m) { $menu_by_kat[$m['kategori'] ?? 'Lainnya'][] = $m; }

$stmt_saldo = $pdo->prepare("SELECT COALESCE(saldo,0) FROM saldo WHERE id_user=?");
$stmt_saldo->execute([$id_user]);
$saldo_mahasiswa = (int)($stmt_saldo->fetchColumn() ?: 0);

require_once '../../includes/sidebar_mahasiswa.php';
?>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <a href="beranda.php" style="color:var(--ac-tx);font-size:20px;text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1><?= htmlspecialchars($kantin['nama_kantin']) ?></h1>
            <p><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?= htmlspecialchars($kantin['lokasi']) ?></p>
        </div>
    </div>
</div>

<style>
    .kantin-layout { display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start; }
    @media(max-width:960px) { .kantin-layout { grid-template-columns:1fr; } }

    .kat-label { font-size:13px; font-weight:700; color:var(--ac-tx); text-transform:uppercase; letter-spacing:1px; padding:14px 0 8px; border-bottom:2px solid var(--ac-lt); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
    .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(175px,1fr)); gap:14px; margin-bottom:24px; }
    .menu-card { background:var(--bg-card); border-radius:14px; box-shadow:var(--shadow); overflow:hidden; transition:transform .2s,box-shadow .2s; border:1px solid var(--border); }
    .menu-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-h); }
    .menu-img { width:100%; height:120px; background:var(--bg-card2); display:flex; align-items:center; justify-content:center; font-size:36px; }
    .menu-img img { width:100%; height:120px; object-fit:cover; }
    .menu-info { padding:12px; }
    .menu-info .nama { font-size:13.5px; font-weight:700; color:var(--text-1); margin-bottom:4px; }
    .menu-info .harga { font-size:15px; font-weight:800; color:var(--ac-tx); margin-bottom:10px; }
    .qty-control { display:flex; align-items:center; gap:8px; }
    .qty-btn { width:30px; height:30px; border:none; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; }
    .btn-minus { background:#fce4ec; color:#c62828; }
    .btn-minus:hover { background:#c62828; color:white; }
    .btn-plus { background:var(--ac-lt); color:var(--ac-tx); }
    .btn-plus:hover { background:var(--ac); color:white; }
    .qty-num { font-size:14px; font-weight:700; min-width:24px; text-align:center; color:var(--text-1); }
    .btn-add { width:100%; padding:8px; background:var(--ac); color:white; border:none; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer; transition:background .2s; display:flex; align-items:center; justify-content:center; gap:5px; margin-top:8px; }
    .btn-add:hover { background:var(--ac-dk); }
    [data-theme="dark"] .btn-minus { background:rgba(239,68,68,.15); color:#f87171; }
    [data-theme="dark"] .btn-minus:hover { background:#ef4444; color:white; }

    .cart-box { background:var(--bg-card); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; position:sticky; top:20px; border:1px solid var(--border); }
    .cart-header { background:linear-gradient(135deg,var(--ac-dk),var(--ac)); padding:16px 20px; color:white; display:flex; align-items:center; gap:10px; }
    [data-theme="dark"] .cart-header { background:linear-gradient(135deg,#1a1d3a,#2d2f60); border-bottom:1px solid rgba(22,101,52,.3); }
    .cart-header h3 { font-size:15px; font-weight:700; flex:1; }
    .cart-count { background:rgba(255,255,255,.25); padding:2px 10px; border-radius:12px; font-size:13px; font-weight:700; }
    [data-theme="dark"] .cart-count { background:rgba(99,102,241,.4); }
    .cart-body { padding:16px; max-height:380px; overflow-y:auto; }
    .cart-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); }
    .cart-item:last-child { border-bottom:none; }
    .cart-item-info { flex:1; }
    .cart-item-info .nama { font-size:13px; font-weight:600; color:var(--text-1); }
    .cart-item-info .harga { font-size:12px; color:var(--ac-tx); font-weight:600; }
    .cart-item-qty { display:flex; align-items:center; gap:6px; }
    .cq-btn { width:26px; height:26px; border:none; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; }
    .cq-minus { background:#fce4ec; color:#c62828; }
    .cq-plus { background:var(--ac-lt); color:var(--ac-tx); }
    [data-theme="dark"] .cq-minus { background:rgba(239,68,68,.15); color:#f87171; }
    .cq-num { font-size:13px; font-weight:700; min-width:20px; text-align:center; color:var(--text-1); }
    .cart-empty { text-align:center; padding:40px 20px; color:var(--text-3); }
    .cart-empty i { font-size:36px; display:block; margin-bottom:10px; opacity:.3; }
    .cart-footer { padding:16px; border-top:1px solid var(--border); }
    .cart-total { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    .cart-total .label { font-size:13px; color:var(--text-2); font-weight:600; }
    .cart-total .amount { font-size:18px; font-weight:800; color:var(--ac-dk); }
    .btn-checkout { width:100%; padding:13px; background:linear-gradient(135deg,var(--ac-dk),var(--ac)); color:white; border:none; border-radius:12px; font-size:15px; font-weight:700; cursor:pointer; transition:opacity .2s; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-checkout:hover { opacity:.9; }
    .btn-checkout:disabled { opacity:.35; cursor:not-allowed; }
    [data-theme="dark"] .btn-checkout { background:linear-gradient(135deg,#1a5c28,#1a4a22); box-shadow:0 4px 18px rgba(22,101,52,.3); }

    .empty-menu { text-align:center; padding:60px 20px; color:var(--text-3); }
    .empty-menu i { font-size:50px; display:block; margin-bottom:14px; opacity:.3; }

    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:999; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
    .modal-overlay.show { display:flex; }
    .modal-box { background:var(--bg-card); border-radius:20px; padding:28px; width:90%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.4); border:1px solid var(--border); }
    .modal-box h3 { color:var(--text-1); }
    .modal-box p { color:var(--text-2); }
</style>

<div class="kantin-layout">
    <div>
        <?php if (empty($menu_all)): ?>
            <div class="empty-menu"><i class="fas fa-utensils"></i><p>Belum ada menu</p></div>
        <?php else: ?>
        <?php foreach ($menu_by_kat as $kat => $menus): ?>
            <div class="kat-label"><i class="fas fa-tag"></i> <?= htmlspecialchars($kat) ?></div>
            <div class="menu-grid">
            <?php foreach ($menus as $m): ?>
            <div class="menu-card">
                <div class="menu-img">
                    <?php if ($m['gambar']): ?>
                        <img src="../../assets/menu/<?= htmlspecialchars($m['gambar']) ?>" alt="" onerror="this.parentElement.innerHTML='🍽️'">
                    <?php else: ?>🍽️<?php endif; ?>
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
                        <i class="fas fa-cart-plus"></i> Tambah
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-box">
        <div class="cart-header">
            <i class="fas fa-shopping-cart"></i>
            <h3>Keranjang</h3>
            <span class="cart-count" id="cart-count">0</span>
        </div>
        <div class="cart-body" id="cart-body">
            <div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Belum ada item</p></div>
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

<div class="modal-overlay" id="modal-checkout">
    <div class="modal-box">
        <h3 style="font-size:18px;font-weight:800;margin-bottom:6px;">Konfirmasi Pesanan</h3>
        <p style="font-size:13px;margin-bottom:20px;">Periksa pesanan sebelum membayar</p>
        <div id="modal-items" style="margin-bottom:16px;max-height:200px;overflow-y:auto;"></div>
        <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:var(--ac-tx);padding:12px 0;border-top:2px solid var(--border);margin-bottom:6px;">
            <span>Total</span><span id="modal-total"></span>
        </div>
        <div style="font-size:13px;color:var(--text-2);margin-bottom:20px;background:var(--ac-lt);padding:10px 14px;border-radius:8px;">
            💰 Saldo kamu: <strong id="modal-saldo" style="color:var(--ac-tx);"></strong>
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="closeCheckout()" style="flex:1;padding:12px;background:var(--bg-card2);color:var(--text-2);border:1px solid var(--border);border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Batal</button>
            <button onclick="submitOrder()" id="btn-confirm" style="flex:1;padding:12px;background:linear-gradient(135deg,var(--ac-dk),var(--ac));color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">✅ Konfirmasi & Bayar</button>
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
    if (cart[id]) { cart[id].qty += qty; } else { cart[id] = { nama, harga, qty }; }
    document.getElementById('qty-' + id).textContent = 0;
    renderCart();
}
function renderCart() {
    const body = document.getElementById('cart-body');
    const items = Object.entries(cart);
    let total = 0, count = 0;
    if (items.length === 0) {
        body.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Belum ada item</p></div>';
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
                <div class="harga">Rp ${item.harga.toLocaleString('id-ID')} × ${item.qty}</div>
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
        html += `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13.5px;color:var(--text-1);">
            <span>${item.nama} <span style="color:var(--text-3);">×${item.qty}</span></span>
            <span style="font-weight:700;">Rp ${sub.toLocaleString('id-ID')}</span>
        </div>`;
    });
    document.getElementById('modal-items').innerHTML = html;
    document.getElementById('modal-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('modal-saldo').textContent = 'Rp ' + SALDO.toLocaleString('id-ID');
    if (total > SALDO) {
        document.getElementById('btn-confirm').disabled = true;
        document.getElementById('btn-confirm').style.opacity = '0.4';
        document.getElementById('modal-saldo').style.color = '#ef4444';
        document.getElementById('modal-saldo').textContent += ' (tidak cukup!)';
    }
    document.getElementById('modal-checkout').classList.add('show');
}
function closeCheckout() { document.getElementById('modal-checkout').classList.remove('show'); }
function submitOrder() {
    const btn = document.getElementById('btn-confirm');
    btn.disabled = true; btn.textContent = '⏳ Memproses...';
    fetch('proses_pesanan.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ id_penjual: KANTIN_ID, items: Object.entries(cart).map(([id,i]) => ({id_menu:parseInt(id),jumlah:i.qty,harga:i.harga})) })
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.success){ cart={}; renderCart(); closeCheckout(); alert('✅ Pesanan berhasil! Tunggu konfirmasi penjual.'); window.location.href='riwayat.php'; }
        else { alert('❌ Gagal: '+(d.message||'Terjadi kesalahan')); btn.disabled=false; btn.textContent='✅ Konfirmasi & Bayar'; }
    })
    .catch(()=>{ alert('Kesalahan jaringan.'); btn.disabled=false; btn.textContent='✅ Konfirmasi & Bayar'; });
}
</script>

</div></body></html>
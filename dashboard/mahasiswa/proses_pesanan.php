<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$id_user = $_SESSION['user']['id_user'];
$data = json_decode(file_get_contents('php://input'), true);

$id_penjual = (int)($data['id_penjual'] ?? 0);
$items = $data['items'] ?? [];

if (!$id_penjual || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']); exit;
}

// Hitung total
$total = 0;
foreach ($items as $item) {
    $stmt = $pdo->prepare("SELECT harga FROM menu WHERE id_menu = ? AND id_penjual = ?");
    $stmt->execute([$item['id_menu'], $id_penjual]);
    $menu = $stmt->fetch();
    if (!$menu) { echo json_encode(['success' => false, 'message' => 'Menu tidak valid']); exit; }
    $total += $menu['harga'] * (int)$item['jumlah'];
}

// Cek saldo
$stmt = $pdo->prepare("SELECT saldo FROM saldo WHERE id_user = ?");
$stmt->execute([$id_user]);
$saldo_row = $stmt->fetch();
$saldo = $saldo_row ? $saldo_row['saldo'] : 0;

if ($saldo < $total) {
    echo json_encode(['success' => false, 'message' => 'Saldo tidak cukup']); exit;
}

try {
    $pdo->beginTransaction();

    // Kurangi saldo
    $pdo->prepare("UPDATE saldo SET saldo = saldo - ? WHERE id_user = ?")
        ->execute([$total, $id_user]);

    // Buat pesanan
    $pdo->prepare("INSERT INTO pesanan (id_user, id_penjual, total_harga, metode_bayar, status) VALUES (?, ?, ?, 'saldo', 'menunggu')")
        ->execute([$id_user, $id_penjual, $total]);
    $id_pesanan = $pdo->lastInsertId();

    // Insert detail
    foreach ($items as $item) {
        $stmt = $pdo->prepare("SELECT harga FROM menu WHERE id_menu = ?");
        $stmt->execute([$item['id_menu']]);
        $menu = $stmt->fetch();
        $subtotal = $menu['harga'] * (int)$item['jumlah'];

        $pdo->prepare("INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, subtotal) VALUES (?, ?, ?, ?)")
            ->execute([$id_pesanan, $item['id_menu'], $item['jumlah'], $subtotal]);
    }

    // Notifikasi ke penjual
    $stmt_pj = $pdo->prepare("SELECT id_user FROM penjual WHERE id_penjual = ?");
    $stmt_pj->execute([$id_penjual]);
    $penjual = $stmt_pj->fetch();
    $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Pesanan Baru Masuk! 🛒', ?)")
        ->execute([$penjual['id_user'], "Ada pesanan baru #$id_pesanan dari " . $_SESSION['user']['nama'] . " senilai Rp " . number_format($total, 0, ',', '.') . "."]);

    // Notifikasi ke mahasiswa
    $pdo->prepare("INSERT INTO notifikasi (id_user, judul, pesan) VALUES (?, 'Pesanan Berhasil Dibuat ✅', ?)")
        ->execute([$id_user, "Pesanan #$id_pesanan senilai Rp " . number_format($total, 0, ',', '.') . " berhasil dibuat. Tunggu konfirmasi penjual."]);

    $pdo->commit();
    echo json_encode(['success' => true, 'id_pesanan' => $id_pesanan]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pesanan: ' . $e->getMessage()]);
}
?>
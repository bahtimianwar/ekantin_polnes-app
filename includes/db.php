<?php
$host     = 'localhost';
$port     = '5400';
$dbname   = 'ekantin_db';
$username = 'user';
$password = 'user123';

try {
    // Driver diganti ke pgsql, hapus charset=utf8mb4 (PostgreSQL pakai konfigurasi global)
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tetapkan header JSON agar response error konsisten jika ini berupa API
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
}
?>
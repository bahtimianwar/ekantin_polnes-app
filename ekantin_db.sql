-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Apr 2026 pada 02.53
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ekantin_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL,
  `id_menu` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_menu`, `jumlah`, `subtotal`) VALUES
(1, 1, 5, 1, 200000),
(2, 2, 5, 15, 3000000),
(3, 3, 4, 1, 15000),
(4, 4, 5, 2, 400000),
(5, 5, 5, 16, 3200000),
(6, 6, 4, 5, 75000),
(7, 7, 4, 1, 15000),
(8, 8, 6, 1, 15000),
(9, 8, 7, 1, 5000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu`
--

CREATE TABLE `menu` (
  `id_menu` int(11) NOT NULL,
  `id_penjual` int(11) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `harga` int(11) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menu`
--

INSERT INTO `menu` (`id_menu`, `id_penjual`, `nama_menu`, `kategori`, `harga`, `gambar`, `created_at`) VALUES
(4, 4, 'NASI GORENG', 'MAKANAN', 15000, '1773030740_5-Resep-Nasi-Goreng-Sederhana-hingga-Spesial-Mudah-dan-Praktis.jpg', '2026-03-12 08:01:25'),
(5, 3, 'nasgor kece', 'MAKANAN', 200000, '1773327097_69b2d2f99e411.jpg', '2026-03-12 14:51:37'),
(6, 5, 'Nasi Padang', 'MAKANAN', 15000, '1775445084_69d3245cebe44.jpeg', '2026-04-06 03:11:24'),
(7, 5, 'ES TEH', 'MINUMAN', 5000, '1775445103_69d3246f99f29.jpg', '2026-04-06 03:11:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notif` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id_notif`, `id_user`, `judul`, `pesan`, `is_read`, `tanggal`) VALUES
(1, 17, 'Selamat Datang di E-Kantin! 🎉', 'Akun Anda berhasil dibuat. Mulai pesan makanan favorit dari kantin kampus sekarang!', 1, '2026-03-12 08:01:25'),
(2, 17, 'Cara Menggunakan E-Kantin', 'Top up saldo Anda terlebih dahulu, kemudian pilih kantin dan menu yang Anda inginkan. Pembayaran dilakukan otomatis menggunakan saldo.', 1, '2026-03-12 08:01:25'),
(3, 17, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 100.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 1, '2026-03-12 14:54:42'),
(4, 17, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 200.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 1, '2026-03-12 15:03:30'),
(5, 18, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #1 dari HM senilai Rp 200.000.', 0, '2026-03-12 15:05:07'),
(6, 17, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #1 senilai Rp 200.000 berhasil dibuat. Tunggu konfirmasi penjual.', 1, '2026-03-12 15:05:07'),
(7, 17, 'Pesanan Dibatalkan', 'Pesanan #1 dibatalkan oleh penjual. Saldo Rp 200.000 telah dikembalikan.', 1, '2026-03-12 15:16:40'),
(8, 18, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #2 dari HM senilai Rp 3.000.000.', 0, '2026-03-12 15:17:35'),
(9, 17, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #2 senilai Rp 3.000.000 berhasil dibuat. Tunggu konfirmasi penjual.', 1, '2026-03-12 15:17:35'),
(10, 17, 'Pesanan Dibatalkan', 'Pesanan #2 dibatalkan oleh penjual. Saldo Rp 3.000.000 telah dikembalikan.', 1, '2026-03-12 15:17:59'),
(11, 19, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #3 dari aldi langit senilai Rp 15.000.', 1, '2026-03-12 17:15:47'),
(12, 17, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #3 senilai Rp 15.000 berhasil dibuat. Tunggu konfirmasi penjual.', 1, '2026-03-12 17:15:47'),
(13, 17, 'Pesanan Sedang Diproses 🍳', 'Pesanan #3 kamu sedang disiapkan oleh penjual. Harap tunggu ya!', 1, '2026-03-12 17:16:01'),
(14, 17, 'Pesanan Selesai ✅', 'Pesanan #3 kamu sudah selesai. Silakan ambil pesananmu!', 1, '2026-03-12 17:16:18'),
(15, 18, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #4 dari aldi langit senilai Rp 400.000.', 0, '2026-03-24 08:03:15'),
(16, 17, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #4 senilai Rp 400.000 berhasil dibuat. Tunggu konfirmasi penjual.', 1, '2026-03-24 08:03:15'),
(17, 17, 'Pesanan Sedang Diproses 🍳', 'Pesanan #4 kamu sedang disiapkan oleh penjual. Harap tunggu ya!', 1, '2026-03-24 08:03:25'),
(18, 17, 'Pesanan Selesai ✅', 'Pesanan #4 kamu sudah selesai. Silakan ambil pesananmu!', 1, '2026-03-24 08:03:30'),
(19, 18, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #5 dari aldi langit senilai Rp 3.200.000.', 0, '2026-03-30 06:27:12'),
(20, 17, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #5 senilai Rp 3.200.000 berhasil dibuat. Tunggu konfirmasi penjual.', 1, '2026-03-30 06:27:12'),
(21, 17, 'Pesanan Sedang Diproses 🍳', 'Pesanan #5 kamu sedang disiapkan oleh penjual. Harap tunggu ya!', 1, '2026-03-30 06:28:46'),
(22, 17, 'Pesanan Selesai ✅', 'Pesanan #5 kamu sudah selesai. Silakan ambil pesananmu!', 1, '2026-03-30 06:28:51'),
(23, 17, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 20.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 1, '2026-04-02 09:42:24'),
(24, 17, 'Top Up Berhasil ✅', 'Top up sebesar Rp 20.000 telah dikonfirmasi. Saldo kamu sudah bertambah!', 1, '2026-04-02 09:43:09'),
(26, 17, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 500.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 1, '2026-04-02 09:46:45'),
(27, 17, 'Top Up Berhasil ✅', 'Top up sebesar Rp 500.000 telah dikonfirmasi. Saldo kamu sudah bertambah!', 1, '2026-04-02 09:47:04'),
(28, 17, 'Top Up Berhasil ✅', 'Top up sebesar Rp 20.000 telah dikonfirmasi. Saldo kamu sudah bertambah!', 1, '2026-04-02 09:47:07'),
(29, 19, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #6 dari aldi langit cihuy senilai Rp 75.000.', 0, '2026-04-02 09:51:15'),
(30, 17, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #6 senilai Rp 75.000 berhasil dibuat. Tunggu konfirmasi penjual.', 0, '2026-04-02 09:51:15'),
(31, 17, 'Pesanan Sedang Diproses 🍳', 'Pesanan #6 kamu sedang disiapkan oleh penjual. Harap tunggu ya!', 0, '2026-04-02 09:51:25'),
(32, 17, 'Pesanan Selesai ✅', 'Pesanan #6 kamu sudah selesai. Silakan ambil pesananmu!', 0, '2026-04-02 09:51:32'),
(33, 24, 'Selamat Datang! 🎉', 'Halo HAPIS! Akun E-Kantin kamu berhasil dibuat. Silakan top up saldo untuk mulai memesan.', 1, '2026-04-06 02:37:32'),
(34, 24, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 20.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 0, '2026-04-06 02:39:18'),
(35, 24, 'Top Up Berhasil ✅', 'Top up sebesar Rp 20.000 telah dikonfirmasi. Saldo kamu sudah bertambah!', 0, '2026-04-06 02:49:53'),
(36, 19, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #7 dari HAPIS senilai Rp 15.000.', 0, '2026-04-06 02:50:52'),
(37, 24, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #7 senilai Rp 15.000 berhasil dibuat. Tunggu konfirmasi penjual.', 0, '2026-04-06 02:50:52'),
(38, 24, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 500.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 0, '2026-04-06 03:13:14'),
(39, 24, 'Top Up Berhasil ✅', 'Top up sebesar Rp 500.000 telah dikonfirmasi. Saldo kamu sudah bertambah!', 0, '2026-04-06 03:14:49'),
(40, 24, 'Pengajuan Top Up', 'Pengajuan top up sebesar Rp 500.000 sedang diproses. Saldo akan ditambahkan setelah dikonfirmasi admin.', 0, '2026-04-06 03:14:55'),
(41, 25, 'Pesanan Baru Masuk! 🛒', 'Ada pesanan baru #8 dari HAPIS senilai Rp 20.000.', 0, '2026-04-06 03:15:15'),
(42, 24, 'Pesanan Berhasil Dibuat ✅', 'Pesanan #8 senilai Rp 20.000 berhasil dibuat. Tunggu konfirmasi penjual.', 0, '2026-04-06 03:15:15'),
(43, 24, 'Pesanan Sedang Diproses 🍳', 'Pesanan #8 kamu sedang disiapkan oleh penjual. Harap tunggu ya!', 0, '2026-04-06 03:15:30'),
(44, 24, 'Pesanan Selesai ✅', 'Pesanan #8 kamu sudah selesai. Silakan ambil pesananmu!', 0, '2026-04-06 03:15:48'),
(45, 24, 'Pesanan Dibatalkan', 'Pesanan #7 dibatalkan oleh penjual. Saldo Rp 15.000 telah dikembalikan.', 0, '2026-04-06 03:18:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjual`
--

CREATE TABLE `penjual` (
  `id_penjual` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_kantin` varchar(50) NOT NULL,
  `lokasi` text NOT NULL,
  `teks_gambar_qris` varchar(100) DEFAULT NULL,
  `no_rek` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penjual`
--

INSERT INTO `penjual` (`id_penjual`, `id_user`, `nama_kantin`, `lokasi`, `teks_gambar_qris`, `no_rek`) VALUES
(3, 18, 'KANTIN MAHBOI', 'GEDUNG TI', NULL, ''),
(4, 19, 'KANTIN PAK MAHDI', 'KANTIN DANAU', NULL, '123456789'),
(5, 25, 'KANTIN BERKAH', 'KANTIN DANAU', NULL, '1234-0123-9876');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_penjual` int(11) DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `status` enum('menunggu','diproses','selesai','batal') DEFAULT 'menunggu',
  `total_harga` int(11) DEFAULT NULL,
  `metode_bayar` enum('saldo','tunai') DEFAULT 'saldo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_user`, `id_penjual`, `tanggal`, `status`, `total_harga`, `metode_bayar`) VALUES
(1, 17, 3, '2026-03-12 23:05:07', 'batal', 200000, 'saldo'),
(2, 17, 3, '2026-03-12 23:17:35', 'batal', 3000000, 'saldo'),
(3, 17, 4, '2026-03-13 01:15:47', 'selesai', 15000, 'saldo'),
(4, 17, 3, '2026-03-24 16:03:15', 'selesai', 400000, 'saldo'),
(5, 17, 3, '2026-03-30 14:27:12', 'selesai', 3200000, 'saldo'),
(6, 17, 4, '2026-04-02 17:51:15', 'selesai', 75000, 'saldo'),
(7, 24, 4, '2026-04-06 10:50:52', 'batal', 15000, 'saldo'),
(8, 24, 5, '2026-04-06 11:15:15', 'selesai', 20000, 'saldo');

-- --------------------------------------------------------

--
-- Struktur dari tabel `saldo`
--

CREATE TABLE `saldo` (
  `id_saldo` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `saldo` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `saldo`
--

INSERT INTO `saldo` (`id_saldo`, `id_user`, `saldo`, `updated_at`) VALUES
(1, 17, 46850000, '2026-04-02 09:51:15'),
(5, 24, 500000, '2026-04-06 03:18:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `topup`
--

CREATE TABLE `topup` (
  `id_topup` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `metode` varchar(50) DEFAULT 'transfer_bri',
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diterima','ditolak') DEFAULT 'menunggu',
  `catatan` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp(),
  `diproses_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `topup`
--

INSERT INTO `topup` (`id_topup`, `id_user`, `jumlah`, `metode`, `bukti`, `status`, `catatan`, `tanggal`, `diproses_at`) VALUES
(1, 17, 100000, 'qris', NULL, 'diterima', NULL, '2026-03-12 14:54:42', NULL),
(2, 17, 200000, NULL, NULL, 'diterima', NULL, '2026-03-12 15:03:30', NULL),
(3, 17, 20000, 'transfer_bri', NULL, 'diterima', NULL, '2026-04-02 09:42:24', NULL),
(4, 17, 20000, 'transfer_bri', NULL, 'diterima', NULL, '2026-04-02 09:43:15', NULL),
(5, 17, 500000, 'qris', NULL, 'diterima', NULL, '2026-04-02 09:46:45', NULL),
(6, 24, 20000, 'transfer_mandiri', NULL, 'diterima', NULL, '2026-04-06 02:39:18', NULL),
(7, 24, 500000, 'transfer_bri', NULL, 'diterima', NULL, '2026-04-06 03:13:14', NULL),
(8, 24, 500000, 'transfer_bri', NULL, 'menunggu', NULL, '2026-04-06 03:14:55', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('mahasiswa','penjual','admin') NOT NULL DEFAULT 'mahasiswa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nim`, `nama`, `email`, `no_hp`, `password`, `created_at`, `role`) VALUES
(17, '246661031', 'aldi langit cihuy', 'ADMIN@MAHASISWA', '087886804403', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-31 02:43:29', 'mahasiswa'),
(18, NULL, 'MAHBOI', 'ADMIN@KANTIN', '08123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-12 16:26:29', 'penjual'),
(19, NULL, 'MAHDI', 'ADMIN@MAHDI', '08321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-12 08:01:25', 'penjual'),
(20, NULL, 'Administrator', 'admin@ekantin.com', '08000000000', '$2y$10$TKh8H1.PfbuNIAEpFs0o.ezZ3KxaH7lG6Qg3TUa0vIBzDq3vQ1qTm', '2026-04-02 09:13:05', ''),
(23, NULL, 'habib', 'admin@gmail.com', '0813', '$2y$10$yaikRD69ThD0o7Xl5aOOsua7fW4dqVzV9vOkpZI7NcJgUXFVxhove', '2026-04-02 09:31:54', 'admin'),
(24, '246661053', 'HAPIS', 'hapismahdi@gmail.com', '08129834765', '$2y$10$nXdKOE6kAWTBhmTwrKDWleHorcqN5oXkPovD6Y4FzTmCWij1AkquS', '2026-04-06 02:37:32', 'mahasiswa'),
(25, NULL, 'ANDI MASUD', 'kantinberkah@gmail.com', '0843341221', '$2y$10$FG7rAP1zGGeVcVWP9GLbi.QOgocK4UXFazR07ObcvLplpmELd8DWK', '2026-04-06 03:09:16', 'penjual');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indeks untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD KEY `id_penjual` (`id_penjual`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notif`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `penjual`
--
ALTER TABLE `penjual`
  ADD PRIMARY KEY (`id_penjual`),
  ADD UNIQUE KEY `teks_gambar_qris` (`teks_gambar_qris`),
  ADD KEY `fk_penjual_id_user` (`id_user`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_penjual` (`id_penjual`);

--
-- Indeks untuk tabel `saldo`
--
ALTER TABLE `saldo`
  ADD PRIMARY KEY (`id_saldo`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `topup`
--
ALTER TABLE `topup`
  ADD PRIMARY KEY (`id_topup`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_nohp` (`no_hp`),
  ADD UNIQUE KEY `unique_nim` (`nim`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT untuk tabel `penjual`
--
ALTER TABLE `penjual`
  MODIFY `id_penjual` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `saldo`
--
ALTER TABLE `saldo`
  MODIFY `id_saldo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `topup`
--
ALTER TABLE `topup`
  MODIFY `id_topup` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`),
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`);

--
-- Ketidakleluasaan untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`id_penjual`) REFERENCES `penjual` (`id_penjual`);

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penjual`
--
ALTER TABLE `penjual`
  ADD CONSTRAINT `fk_penjual_id_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`id_penjual`) REFERENCES `penjual` (`id_penjual`);

--
-- Ketidakleluasaan untuk tabel `saldo`
--
ALTER TABLE `saldo`
  ADD CONSTRAINT `saldo_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `topup`
--
ALTER TABLE `topup`
  ADD CONSTRAINT `topup_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

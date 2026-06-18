-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 07:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbkarya`
--

-- --------------------------------------------------------

--
-- Table structure for table `t_admin`
--

CREATE TABLE `t_admin` (
  `id_admin` int(11) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_admin`
--

INSERT INTO `t_admin` (`id_admin`, `nama_admin`, `email`, `foto`, `password`) VALUES
(14, 'dinda cantik', 'norlaila014@gmail.com', 'admin_14_1763452342.jpg', '$2y$10$ZbiCZR4jdjH4KHD8GOMupOizl2yBzZayeFjLUx.jRSBTD0a.Liu3q'),
(16, 'ghina', 'ghinsor@gmail.com', 'admin_16_1767582678.jpeg', 'bismillah');

-- --------------------------------------------------------

--
-- Table structure for table `t_bidding`
--

CREATE TABLE `t_bidding` (
  `id_bid` int(11) NOT NULL,
  `id_design` int(11) NOT NULL,
  `id_buyer` int(11) NOT NULL,
  `harga_tawaran` decimal(12,2) NOT NULL,
  `tanggal_bid` datetime DEFAULT current_timestamp(),
  `status_bid` enum('aktif','ditolak','diterima','selesai') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_bidding`
--

INSERT INTO `t_bidding` (`id_bid`, `id_design`, `id_buyer`, `harga_tawaran`, `tanggal_bid`, `status_bid`) VALUES
(22, 21, 418, 301231.00, '2025-12-23 02:18:09', ''),
(23, 21, 418, 421231.00, '2025-12-23 10:32:01', ''),
(24, 21, 418, 501231.00, '2025-12-23 11:31:42', ''),
(25, 21, 418, 551231.00, '2025-12-23 13:16:57', ''),
(26, 21, 418, 581231.00, '2025-12-23 13:17:34', ''),
(27, 21, 418, 591231.00, '2025-12-23 13:21:07', ''),
(28, 22, 397, 110000.00, '2025-12-27 03:04:59', ''),
(29, 22, 397, 150000.00, '2025-12-27 03:05:15', ''),
(30, 22, 397, 160000.00, '2025-12-27 03:07:01', ''),
(31, 22, 397, 200000.00, '2025-12-27 03:10:22', ''),
(32, 24, 230, 120000.00, '2025-12-31 00:35:26', '');

-- --------------------------------------------------------

--
-- Table structure for table `t_chat`
--

CREATE TABLE `t_chat` (
  `id_chat` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `id_penerima` int(11) NOT NULL,
  `isi_pesan` text NOT NULL,
  `waktu_kirim` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_chat`
--

INSERT INTO `t_chat` (`id_chat`, `id_pengirim`, `id_penerima`, `isi_pesan`, `waktu_kirim`) VALUES
(7, 202, 303, 'Halo, saya sudah menerima detail order desain Anda. Kapan kita bisa mulai?', '2025-11-18 15:26:22'),
(8, 303, 202, 'Bisa dimulai sekarang. Saya tunggu drafnya ya.', '2025-11-18 15:26:38'),
(9, 404, 202, 'Saya tertarik dengan desain logo Anda. Berapa estimasi waktu pengerjaannya?', '2025-11-18 15:27:19'),
(10, 418, 739, 'halo kak', '2025-12-22 23:42:18'),
(11, 739, 418, 'halooo', '2025-12-22 23:42:51'),
(12, 418, 739, 'ad', '2025-12-22 23:44:08'),
(13, 418, 739, 'd', '2025-12-22 23:44:10'),
(14, 418, 739, 'h', '2025-12-22 23:44:12'),
(15, 418, 739, 'werwrewr', '2025-12-22 23:44:17'),
(16, 418, 739, 'wrwe', '2025-12-22 23:44:19'),
(17, 418, 739, 'wer', '2025-12-22 23:44:20'),
(18, 418, 739, 'wer', '2025-12-22 23:44:21'),
(19, 418, 739, 'wer', '2025-12-22 23:44:22'),
(20, 418, 739, 'erw', '2025-12-22 23:44:23'),
(21, 418, 739, 'wre', '2025-12-22 23:44:24'),
(22, 418, 739, 'wer', '2025-12-22 23:44:25'),
(23, 418, 739, 'wer', '2025-12-22 23:44:26'),
(24, 418, 739, 'rew', '2025-12-22 23:44:27'),
(25, 418, 739, 'erw', '2025-12-22 23:44:29'),
(26, 418, 739, 'qrew', '2025-12-22 23:44:30'),
(27, 418, 739, 'dwa', '2025-12-22 23:44:31'),
(28, 418, 739, 'sd', '2025-12-22 23:44:33'),
(29, 418, 739, 'fawsd', '2025-12-22 23:44:34'),
(30, 418, 739, 'd', '2025-12-22 23:45:17'),
(31, 418, 739, 'd', '2025-12-22 23:45:18'),
(32, 418, 739, 's', '2025-12-22 23:45:19'),
(33, 418, 739, 'af', '2025-12-22 23:45:21'),
(34, 418, 739, 'f', '2025-12-22 23:45:22'),
(35, 418, 739, 'a', '2025-12-22 23:45:23'),
(36, 418, 739, 'f', '2025-12-22 23:45:25'),
(37, 418, 739, 'w', '2025-12-22 23:45:26'),
(38, 418, 739, 'f', '2025-12-22 23:45:28'),
(39, 418, 739, 'f', '2025-12-22 23:45:29'),
(40, 418, 739, 'a', '2025-12-22 23:45:30'),
(41, 739, 739, 'halo', '2025-12-27 00:31:54');

-- --------------------------------------------------------

--
-- Table structure for table `t_design`
--

CREATE TABLE `t_design` (
  `id_design` int(11) NOT NULL,
  `id_designer` int(11) DEFAULT NULL,
  `judul` varchar(150) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` enum('ilustrasi','tipografi','mockup','uiux','animasi') DEFAULT NULL,
  `harga_awal` decimal(12,2) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `tanggal_upload` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','sold') DEFAULT NULL,
  `waktu_berakhir` datetime DEFAULT NULL,
  `file_master` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_design`
--

INSERT INTO `t_design` (`id_design`, `id_designer`, `judul`, `deskripsi`, `kategori`, `harga_awal`, `gambar`, `tanggal_upload`, `status`, `waktu_berakhir`, `file_master`) VALUES
(21, 739, 'gambar baru', 'cantik', 'ilustrasi', 231231.00, '4274_DSC00626.JPG', '2025-12-22 23:06:39', 'approved', '2025-12-24 23:06:00', ''),
(22, 950, '3D UNIKOM', 'UNIKOM HEBAT', 'ilustrasi', 100000.00, '4621_UNIKOM-LOGO-2025-High-Resolution-1024x1024.png', '2025-12-27 00:50:55', 'approved', '2025-12-27 00:54:00', ''),
(23, 202, 'anak sd', 'foto anak sd', 'tipografi', 120000.00, '2667_IMG_2932.JPG', '2025-12-31 00:33:16', 'approved', '2025-12-31 00:32:00', ''),
(24, 202, 'sd atuh', 'ya gitulah', 'mockup', 100000.00, '6869_IMG_2994.JPG', '2025-12-31 00:34:30', 'approved', '2026-01-01 13:35:00', ''),
(25, 202, 'anak sd jalan jalan', 'anak sd pulang sekolah nih', 'ilustrasi', 140.00, '9828_IMG_2923.JPG', '2025-12-31 00:47:34', 'approved', '2026-01-01 00:46:00', '7281_MASTER_IMG_2923.JPG');

-- --------------------------------------------------------

--
-- Table structure for table `t_karya`
--

CREATE TABLE `t_karya` (
  `id_karya` int(11) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `desainer` varchar(100) DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `status` enum('Aktif','Menunggu','Nonaktif') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_password_reset`
--

CREATE TABLE `t_password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_password_reset`
--

INSERT INTO `t_password_reset` (`id`, `email`, `token`, `expiry`, `created_at`) VALUES
(1, 'norlaila014@gmail.com', 'd07b8ab1c4c9e9c2f3ffc41cd570616af33b1a810aa3255105139a86b6541b73', '2026-01-04 01:32:48', '2026-01-03 17:32:48');

-- --------------------------------------------------------

--
-- Table structure for table `t_blokir_nik`
-- (Ban berbasis NIK: pelaku yang dibanned tetap tidak bisa ikut lelang
--  walau membuat akun baru dengan NIK yang sama.)
--

CREATE TABLE `t_blokir_nik` (
  `id_blokir` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `tanggal_blokir` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_premium`
--

CREATE TABLE `t_premium` (
  `id_premium` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tipe_premium` enum('buyer','designer') NOT NULL,
  `tanggal_aktif` date NOT NULL,
  `tanggal_berakhir` date NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_premium`
--

INSERT INTO `t_premium` (`id_premium`, `id_user`, `tipe_premium`, `tanggal_aktif`, `tanggal_berakhir`, `status`) VALUES
(4, 404, 'buyer', '2025-08-20', '2025-09-20', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `t_favorit_desainer`
-- (Fitur premium "Desainer Favorit": pembeli premium mem-follow desainer)
--

CREATE TABLE `t_favorit_desainer` (
  `id_favorit` int(11) NOT NULL AUTO_INCREMENT,
  `id_buyer` int(11) NOT NULL,
  `id_designer` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_favorit`),
  UNIQUE KEY `uniq_fav` (`id_buyer`,`id_designer`),
  KEY `id_buyer` (`id_buyer`),
  KEY `id_designer` (`id_designer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_pencairan`
-- (Pencairan dana desainer — model escrow. Dana ditahan platform sampai
--  desainer mengajukan tarik & admin memproses transfer manual.)
--

CREATE TABLE `t_pencairan` (
  `id_pencairan` int(11) NOT NULL,
  `id_designer` int(11) NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `jumlah_diterima` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bank` varchar(50) NOT NULL,
  `no_rekening` varchar(40) NOT NULL,
  `nama_pemilik_rek` varchar(100) NOT NULL,
  `status` enum('pending','diproses','selesai','ditolak') DEFAULT 'pending',
  `catatan_admin` varchar(255) DEFAULT NULL,
  `tanggal_request` datetime DEFAULT current_timestamp(),
  `tanggal_proses` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_transaksi`
--

CREATE TABLE `t_transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_design` int(11) DEFAULT NULL,
  `id_buyer` int(11) NOT NULL,
  `harga_final` decimal(12,2) NOT NULL,
  `metode_pembayaran` enum('transfer','qris','midtrans') NOT NULL,
  `status_pembayaran` enum('pending','berhasil','gagal') DEFAULT 'pending',
  `tanggal_transaksi` datetime DEFAULT current_timestamp(),
  `id_midtrans_order` varchar(100) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_transaksi`
--

INSERT INTO `t_transaksi` (`id_transaksi`, `id_design`, `id_buyer`, `harga_final`, `metode_pembayaran`, `status_pembayaran`, `tanggal_transaksi`, `id_midtrans_order`, `bukti_pembayaran`) VALUES
(45, 21, 418, 601231.00, 'transfer', 'pending', '2025-12-23 13:21:21', 'ORD-1766470881-663', NULL),
(46, 21, 418, 601231.00, 'transfer', 'pending', '2025-12-23 13:21:33', 'ORD-1766470893-483', NULL),
(47, 22, 397, 160000.00, 'transfer', 'pending', '2025-12-27 03:06:15', 'ORD-1766779575-271', NULL),
(48, 24, 230, 130000.00, 'transfer', 'pending', '2025-12-31 00:35:42', 'ORD-1767116142-527', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `t_user`
--

CREATE TABLE `t_user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` enum('admin','designer','pelanggan') DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT NULL,
  `premium` tinyint(1) DEFAULT NULL,
  `foto` mediumtext NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `foto_ktp` varchar(255) DEFAULT NULL,
  `status_verifikasi` enum('unverified','pending','verified') DEFAULT 'unverified',
  `status_member` enum('free','premium') DEFAULT 'free'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_user`
--

INSERT INTO `t_user` (`id_user`, `nama`, `email`, `password`, `no_telp`, `alamat`, `role`, `status`, `premium`, `foto`, `foto_profil`, `foto_ktp`, `status_verifikasi`, `status_member`) VALUES
(202, 'Alvi Designer', 'alvi@gmail.com', '5f249e6f5367a84cab13c8bb4cb7440f', '08995117514', 'norlaila014@gmail.com', 'designer', 'aktif', 1, 'WhatsApp Image 2020-10-27 at 08.45.16 (1).jpeg', NULL, NULL, 'unverified', 'free'),
(230, 'Hasan', 'Hasan@gmail.com', '$2y$10$75F9cZOqr96D.JLe4GLM5Ok1C6FJYpUb7cJ/nH928Yls83nirN7Oe', NULL, NULL, 'pelanggan', 'aktif', 0, 'default.jpg', NULL, NULL, 'unverified', 'free'),
(231, 'DEN DEN MAULUD', 'dendenmaulud16@gmail.com', '$2y$10$niZ9PEvPvFE7lTBa0lNAreVywtQBs01tKXz3LN0AS6akgzQ2cgYyW', '', '', 'pelanggan', 'aktif', 0, '917_WhatsApp Image 2020-10-27 at 08.45.16 (1).jpeg', NULL, NULL, 'unverified', 'free'),
(303, 'Maya Beli', 'maya@gmail.com', NULL, NULL, NULL, 'pelanggan', 'aktif', 0, '', NULL, NULL, 'unverified', 'free'),
(397, 'Jojo', 'Jojo@gmail.com', '$2y$10$2PBxj5zIke1Ec6/9aWCZpuGHexlydAMP/2uZ0U5p3dRPulkEjl9YS', NULL, NULL, 'pelanggan', 'aktif', 0, 'default.jpg', NULL, NULL, 'unverified', 'free'),
(404, 'Fajar Bida', 'fajar@gmail.com', NULL, NULL, NULL, 'pelanggan', 'aktif', 0, '', NULL, NULL, 'unverified', 'free'),
(418, 'dinda', 'dinda@gmail.com', '$2y$10$fdV0gTN0wR1DXC40vVDKiOz4izJVbKYcrYKxVms9tsa0fmirMV1UC', '', '', 'pelanggan', 'aktif', 0, '469_Orange and White Modern Customer Experience Presentation.png', NULL, '418_KTP_238.jpg', 'verified', 'free'),
(483, 'Denden Mauludi', 'norlaila014@gmail.com', '$2y$10$VPVmihTTzi8uyJWiqtXnvuelt8DY5tFH4yke7JbcPbb7lMBpxlCgG', NULL, NULL, 'pelanggan', 'aktif', 0, 'default.jpg', NULL, NULL, 'unverified', 'free'),
(739, 'shabrina', 'shabrina@gmail.com', 'lolipop123', '4254252', 'Jl. Jalan yuk', 'designer', NULL, NULL, 'logo himabig.png', NULL, NULL, 'unverified', 'free'),
(950, 'Jojo 3D ', 'Jojo3D@gmail.com', 'jojo123', NULL, NULL, 'designer', NULL, NULL, '', NULL, NULL, 'unverified', 'free'),
(951, 'zildji', 'zot155@gmail.com', 'zotcimahi', '', '', 'designer', NULL, NULL, 'WhatsApp Image 2020-10-27 at 08.45.16.jpeg', NULL, NULL, 'unverified', 'free');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `t_admin`
--
ALTER TABLE `t_admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `t_bidding`
--
ALTER TABLE `t_bidding`
  ADD PRIMARY KEY (`id_bid`),
  ADD KEY `id_design` (`id_design`),
  ADD KEY `id_buyer` (`id_buyer`);

--
-- Indexes for table `t_chat`
--
ALTER TABLE `t_chat`
  ADD PRIMARY KEY (`id_chat`);

--
-- Indexes for table `t_design`
--
ALTER TABLE `t_design`
  ADD PRIMARY KEY (`id_design`),
  ADD KEY `id_designer` (`id_designer`);

--
-- Indexes for table `t_karya`
--
ALTER TABLE `t_karya`
  ADD PRIMARY KEY (`id_karya`);

--
-- Indexes for table `t_password_reset`
--
ALTER TABLE `t_password_reset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `t_premium`
--
ALTER TABLE `t_premium`
  ADD PRIMARY KEY (`id_premium`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `t_pencairan`
--
ALTER TABLE `t_pencairan`
  ADD PRIMARY KEY (`id_pencairan`),
  ADD KEY `id_designer` (`id_designer`);

--
-- Indexes for table `t_blokir_nik`
--
ALTER TABLE `t_blokir_nik`
  ADD PRIMARY KEY (`id_blokir`),
  ADD UNIQUE KEY `uniq_nik` (`nik`);

--
-- Indexes for table `t_transaksi`
--
ALTER TABLE `t_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `fk_transaksi_design` (`id_design`),
  ADD KEY `fk_transaksi_user` (`id_buyer`);

--
-- Indexes for table `t_user`
--
ALTER TABLE `t_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `t_admin`
--
ALTER TABLE `t_admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `t_bidding`
--
ALTER TABLE `t_bidding`
  MODIFY `id_bid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `t_chat`
--
ALTER TABLE `t_chat`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `t_design`
--
ALTER TABLE `t_design`
  MODIFY `id_design` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `t_karya`
--
ALTER TABLE `t_karya`
  MODIFY `id_karya` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `t_password_reset`
--
ALTER TABLE `t_password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `t_premium`
--
ALTER TABLE `t_premium`
  MODIFY `id_premium` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `t_pencairan`
--
ALTER TABLE `t_pencairan`
  MODIFY `id_pencairan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_blokir_nik`
--
ALTER TABLE `t_blokir_nik`
  MODIFY `id_blokir` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_transaksi`
--
ALTER TABLE `t_transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `t_user`
--
ALTER TABLE `t_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=952;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `t_bidding`
--
ALTER TABLE `t_bidding`
  ADD CONSTRAINT `t_bidding_ibfk_1` FOREIGN KEY (`id_design`) REFERENCES `t_design` (`id_design`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_bidding_ibfk_2` FOREIGN KEY (`id_buyer`) REFERENCES `t_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_design`
--
ALTER TABLE `t_design`
  ADD CONSTRAINT `t_design_ibfk_1` FOREIGN KEY (`id_designer`) REFERENCES `t_user` (`id_user`);

--
-- Constraints for table `t_premium`
--
ALTER TABLE `t_premium`
  ADD CONSTRAINT `t_premium_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_pencairan`
--
ALTER TABLE `t_pencairan`
  ADD CONSTRAINT `t_pencairan_ibfk_1` FOREIGN KEY (`id_designer`) REFERENCES `t_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_transaksi`
--
ALTER TABLE `t_transaksi`
  ADD CONSTRAINT `fk_transaksi_design` FOREIGN KEY (`id_design`) REFERENCES `t_design` (`id_design`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_buyer`) REFERENCES `t_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_transaksi_ibfk_2` FOREIGN KEY (`id_buyer`) REFERENCES `t_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

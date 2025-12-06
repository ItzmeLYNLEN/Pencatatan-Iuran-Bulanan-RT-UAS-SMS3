-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 06, 2025 at 03:07 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_iuran_rt`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int NOT NULL,
  `id_transaksi` int NOT NULL,
  `nama_item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_biaya` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `nama_item`, `jumlah_biaya`) VALUES
(1, 1, 'Iuran Warga (Keamanan & Sampah)', 50000.00),
(2, 2, 'Iuran Warga (Keamanan & Sampah)', 50000.00),
(3, 3, 'Iuran Warga (Keamanan & Sampah)', 50000.00),
(4, 4, 'Iuran Warga (Keamanan & Sampah)', 50000.00),
(5, 5, 'Iuran Warga (Keamanan & Sampah)', 50000.00),
(6, 6, 'Iuran Warga (Keamanan & Sampah)', 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','warga') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'warga'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `username`, `password`, `role`) VALUES
(1, 'admin_rw', 'admin123', 'admin'),
(2, 'BB 33', '123', 'admin'),
(4, 'BB 44', '123', 'warga'),
(5, 'BB 43', '123', 'warga'),
(6, 'BB 42', '123', 'warga');

-- --------------------------------------------------------

--
-- Table structure for table `profil_admin`
--

CREATE TABLE `profil_admin` (
  `id_admin` int NOT NULL,
  `id_pengguna` int NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_admin`
--

INSERT INTO `profil_admin` (`id_admin`, `id_pengguna`, `nama_lengkap`) VALUES
(2, 2, 'Saripudin');

-- --------------------------------------------------------

--
-- Table structure for table `profil_warga`
--

CREATE TABLE `profil_warga` (
  `id_warga` int NOT NULL,
  `id_pengguna` int DEFAULT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `no_rumah` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `no_telepon` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_warga`
--

INSERT INTO `profil_warga` (`id_warga`, `id_pengguna`, `nama_lengkap`, `no_rumah`, `no_telepon`) VALUES
(1, 2, 'Saripudin', 'BB-33', '081211111111'),
(3, 4, 'Prasojo Henri', 'BB 44', '0854652'),
(4, 5, 'Mukhammad Jayadi', 'BB 43', '08617538'),
(5, 6, 'Atep', 'BB 42', '0834142');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int NOT NULL,
  `kode_transaksi` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_warga` int NOT NULL,
  `id_admin` int DEFAULT NULL,
  `periode_tagihan` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `total_tagihan` decimal(15,2) DEFAULT '0.00',
  `metode_pembayaran` enum('manual','online') COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_bayar` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `kode_transaksi`, `id_warga`, `id_admin`, `periode_tagihan`, `total_tagihan`, `metode_pembayaran`, `tanggal_bayar`) VALUES
(1, 'INV-20251206012646-5', 5, NULL, '2025-11', 50000.00, 'manual', '2025-12-06 08:26:46'),
(2, 'INV-20251206012650-4', 4, NULL, '2025-11', 50000.00, 'manual', '2025-12-06 08:26:50'),
(3, 'INV-ON-20251206213014-3', 3, NULL, '2025-12', 50000.00, 'online', '2025-12-06 21:30:14'),
(4, 'INV-ON-20251206213136-3', 3, NULL, '2025-11', 50000.00, 'online', '2025-12-06 21:31:36'),
(5, 'INV-ON-20251206215530-1', 1, NULL, '2025-12', 50000.00, 'online', '2025-12-06 21:55:30'),
(6, 'INV-ON-20251206220730-4', 4, NULL, '2025-10', 50000.00, 'online', '2025-12-06 22:07:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `profil_admin`
--
ALTER TABLE `profil_admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `profil_warga`
--
ALTER TABLE `profil_warga`
  ADD PRIMARY KEY (`id_warga`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `id_warga` (`id_warga`),
  ADD KEY `id_admin` (`id_admin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `profil_admin`
--
ALTER TABLE `profil_admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `profil_warga`
--
ALTER TABLE `profil_warga`
  MODIFY `id_warga` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE;

--
-- Constraints for table `profil_admin`
--
ALTER TABLE `profil_admin`
  ADD CONSTRAINT `profil_admin_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `profil_warga`
--
ALTER TABLE `profil_warga`
  ADD CONSTRAINT `profil_warga_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_warga`) REFERENCES `profil_warga` (`id_warga`) ON DELETE RESTRICT,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `profil_admin` (`id_admin`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 03:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sikes_rpl`
--

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id_guru` int(11) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `nip` varchar(25) DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `jabatan` varchar(50) NOT NULL,
  `id_login` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id_guru`, `nama_guru`, `nip`, `no_telp`, `jabatan`, `id_login`, `created_at`, `updated_at`) VALUES
(1, 'Yusharizal, S. ST', '197409062022211001', '08159768823', 'Kaprog', 2, '2026-01-05 11:15:51', '2026-05-08 10:41:02'),
(3, 'Arief Yunianto, S. Kom', '198406232022211017', '08561950305', 'Wali Kelas', 4, '2026-01-05 11:15:51', '2026-05-08 10:48:55'),
(4, 'Meyfa Noer K. Melani, S.ST', '8840760661230202', '085772341512', 'Wali Kelas', 5, '2026-01-05 11:15:51', '2026-05-08 10:47:55'),
(5, 'Ade Suci Romadhona, S.Pd', '198705122010011001', '081384077476', 'Wali Kelas', 6, '2026-01-05 11:15:51', '2026-05-08 10:46:04'),
(6, 'Fani Indriyaningsih, S. Kom', '199707242025212114', '087809883528', 'Wali Kelas', 7, '2026-01-05 11:15:51', '2026-05-08 10:45:07'),
(7, 'Siti Salbiyah, S.Pd.I', '1833768669230222', '08984960093', 'Wali Kelas', 8, '2026-01-05 11:15:51', '2026-05-08 10:42:35'),
(8, 'Yuli Dianah, ST', '198308282022212020', '087770733944', 'Wali Kelas', 9, '2026-01-05 11:15:51', '2026-05-08 10:38:39'),
(9, 'Kiki Amirullah S.Pd', '199305152017032001', '081120949100', 'Admin', 1, '2026-04-29 01:30:53', '2026-04-29 01:30:53');

-- --------------------------------------------------------

--
-- Table structure for table `kedisiplinan`
--

CREATE TABLE `kedisiplinan` (
  `id_kedisiplinan` int(11) NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_pelanggaran` int(11) NOT NULL,
  `poin` int(11) NOT NULL,
  `kategori` enum('Ringan','Sedang','Berat') NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `id_petugas` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kedisiplinan`
--

INSERT INTO `kedisiplinan` (`id_kedisiplinan`, `tanggal_kejadian`, `id_siswa`, `id_pelanggaran`, `poin`, `kategori`, `bukti`, `id_petugas`, `created_at`) VALUES
(22, '2026-02-28', 4, 6, 25, 'Ringan', 'bukti_1772265994.png', 1, '2026-02-28 08:06:34'),
(27, '2026-03-19', 18, 3, 90, 'Berat', 'bukti_1772594492.jpg', 1, '2026-03-04 03:21:32'),
(28, '2026-04-13', 19, 8, 30, 'Sedang', 'bukti_1776069673.jpg', 1, '2026-04-13 08:41:13'),
(29, '2026-04-14', 18, 6, 25, 'Ringan', 'bukti_1776144037.png', 1, '2026-04-14 05:20:37'),
(30, '2026-04-16', 15, 6, 25, 'Ringan', 'bukti_1776342146.jpg', 1, '2026-04-16 12:22:26'),
(32, '2026-04-17', 18, 5, 50, 'Sedang', 'bukti_1776350954.png', 3, '2026-04-16 14:49:14'),
(33, '2026-04-17', 21, 8, 30, 'Sedang', 'bukti_1776391656.jpg', 1, '2026-04-17 02:07:36'),
(34, '2026-04-18', 22, 5, 50, 'Sedang', 'bukti_1776393036.jpg', 3, '2026-04-17 02:30:36'),
(43, '2026-05-08', 23, 1, 20, 'Ringan', 'bukti_1778212079_8062.jpg', 9, '2026-05-08 03:47:59'),
(44, '2026-05-01', 24, 5, 50, 'Sedang', 'bukti_1778245327_1010.png', 9, '2026-05-08 13:02:07');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `tingkat` enum('X','XI','XII') NOT NULL,
  `id_wali` int(11) NOT NULL,
  `jumlah_siswa` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `tingkat`, `id_wali`, `jumlah_siswa`, `created_at`, `updated_at`) VALUES
(1, 'XII RPL 2', 'XII', 8, 0, '2026-01-05 13:11:30', '2026-01-05 13:45:30'),
(2, 'XI RPL 1', 'XI', 5, 0, '2026-01-05 14:29:48', '2026-01-05 14:29:48'),
(3, 'XI RPL 2', 'XI', 6, 0, '2026-01-05 14:36:40', '2026-01-05 14:36:40'),
(4, 'XII RPL 1', 'XII', 7, 0, '2026-01-05 14:37:12', '2026-01-05 14:37:12'),
(5, 'X RPL 1', 'X', 3, 0, '2026-01-05 14:37:33', '2026-01-05 14:37:33'),
(6, 'X RPL 2', 'X', 4, 0, '2026-01-05 14:37:56', '2026-01-05 14:37:56');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id_login` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','kaprog','kesiswaan','walas') NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id_login`, `username`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin123', 'admin', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(2, 'kaprog_rpl', 'kaprog123', 'kaprog', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(4, 'walas_10rpl1', 'walas123', 'walas', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(5, 'walas_10rpl2', 'walas123', 'walas', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(6, 'walas_11rpl1', 'walas123', 'walas', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(7, 'walas_11rpl2', 'walas123', 'walas', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(8, 'walas_12rpl1', 'walas123', 'walas', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29'),
(9, 'walas_12rpl2', 'walas123', 'walas', 'aktif', '2026-01-05 11:15:29', '2026-01-05 11:15:29');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggaran`
--

CREATE TABLE `pelanggaran` (
  `id_pelanggaran` int(11) NOT NULL,
  `nama_pelanggaran` varchar(150) NOT NULL,
  `kategori` enum('Ringan','Sedang','Berat') NOT NULL,
  `poin` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggaran`
--

INSERT INTO `pelanggaran` (`id_pelanggaran`, `nama_pelanggaran`, `kategori`, `poin`, `created_at`, `updated_at`) VALUES
(1, 'Seragam TIdak Rapi', 'Ringan', 20, '2026-01-05 16:12:13', '2026-01-07 12:36:16'),
(3, 'Mabok Menggunakan Seragam', 'Berat', 90, '2026-01-06 12:55:05', '2026-01-06 12:55:05'),
(5, 'Tauran', 'Sedang', 50, '2026-02-27 03:07:34', '2026-02-27 03:07:34'),
(6, 'Memberikan Keterangan Palsu', 'Ringan', 23, '2026-02-28 06:32:08', '2026-05-08 13:01:32'),
(8, 'Bercanda saat guru menerangkan', 'Sedang', 30, '2026-04-13 08:40:43', '2026-04-13 08:40:43'),
(10, 'pelecehan seksual', 'Berat', 70, '2026-04-17 02:29:39', '2026-04-17 02:29:39'),
(11, 'Datang Terlambat', 'Ringan', 28, '2026-05-08 03:14:25', '2026-05-08 10:32:41');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `poin_total` int(11) DEFAULT 0,
  `status` enum('aktif','keluar','lulus') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_siswa`, `jenis_kelamin`, `id_kelas`, `poin_total`, `status`, `created_at`, `updated_at`) VALUES
(1, '1234567890', 'Revan Oknanda', 'L', 2, 0, 'lulus', '2026-01-05 14:40:16', '2026-02-28 07:03:02'),
(3, '1234567892', 'Muhammad Zaky Arrosyid', 'L', 5, 70, 'lulus', '2026-01-05 16:29:16', '2026-02-27 04:04:14'),
(4, '1234567893', 'Geraldy Febriansyah', 'L', 6, 25, 'lulus', '2026-01-05 16:29:48', '2026-03-04 02:44:50'),
(5, '1234567894', 'Gibran Rajendra Sianturi', 'L', 4, 0, 'lulus', '2026-01-05 16:30:10', '2026-02-28 07:43:40'),
(10, '109988778', 'Alya', 'P', 3, 0, 'keluar', '2026-01-28 02:11:49', '2026-02-27 04:15:08'),
(11, '12345', 'nabil satria', 'L', 1, 0, 'keluar', '2026-02-13 04:01:36', '2026-02-27 04:02:44'),
(13, '1234567899', 'Nazil', 'L', 1, 0, 'lulus', '2026-02-27 04:02:39', '2026-04-14 05:18:51'),
(15, '10243424329', 'Faisal', 'L', 1, 25, 'aktif', '2026-02-28 06:31:29', '2026-04-16 12:22:26'),
(17, '1234567897', 'novi aulia', 'P', 6, 0, 'lulus', '2026-03-04 03:11:15', '2026-04-17 02:13:20'),
(18, '21563415436', 'dimas ramadhan', 'L', 2, 165, 'lulus', '2026-03-04 03:20:39', '2026-05-08 10:11:16'),
(19, '123758374929', 'Azzam', 'L', 4, 30, 'lulus', '2026-04-13 08:40:03', '2026-04-13 08:41:37'),
(20, '1024690', 'kaila', 'P', 5, 0, 'lulus', '2026-04-17 02:00:06', '2026-04-17 02:12:54'),
(21, '102412345', 'alayaaa', 'P', 2, 30, 'aktif', '2026-04-17 02:05:34', '2026-04-17 02:07:36'),
(22, '2028283', 'Naufal Imania', 'L', 4, 50, 'aktif', '2026-04-17 02:28:51', '2026-05-08 13:01:13'),
(23, '12345678912', 'Revan Oknanda', 'L', 5, 125, 'aktif', '2026-05-08 03:13:18', '2026-05-08 10:33:16'),
(24, '12345678913', 'Muhammad Carel Azzami', 'L', 1, 50, 'aktif', '2026-05-08 13:00:25', '2026-05-08 13:02:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD KEY `guru_ibfk_1` (`id_login`);

--
-- Indexes for table `kedisiplinan`
--
ALTER TABLE `kedisiplinan`
  ADD PRIMARY KEY (`id_kedisiplinan`),
  ADD KEY `fk_kedisiplinan_siswa` (`id_siswa`),
  ADD KEY `fk_kedisiplinan_petugas` (`id_petugas`),
  ADD KEY `fk_kedisiplinan_pelanggaran` (`id_pelanggaran`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `fk_kelas_wali` (`id_wali`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id_login`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `pelanggaran`
--
ALTER TABLE `pelanggaran`
  ADD PRIMARY KEY (`id_pelanggaran`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `fk_siswa_kelas` (`id_kelas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `kedisiplinan`
--
ALTER TABLE `kedisiplinan`
  MODIFY `id_kedisiplinan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id_login` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pelanggaran`
--
ALTER TABLE `pelanggaran`
  MODIFY `id_pelanggaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `guru_ibfk_1` FOREIGN KEY (`id_login`) REFERENCES `login` (`id_login`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kedisiplinan`
--
ALTER TABLE `kedisiplinan`
  ADD CONSTRAINT `fk_kedisiplinan_pelanggaran` FOREIGN KEY (`id_pelanggaran`) REFERENCES `pelanggaran` (`id_pelanggaran`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kedisiplinan_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `guru` (`id_guru`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kedisiplinan_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_wali` FOREIGN KEY (`id_wali`) REFERENCES `guru` (`id_guru`) ON UPDATE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

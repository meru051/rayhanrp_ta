-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 08:50 AM
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
-- Database: `rayhanrp_database_ta`
--

-- --------------------------------------------------------

--
-- Table structure for table `akun`
--

CREATE TABLE `akun` (
  `akun_id` int(11) NOT NULL,
  `nis_nip` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','guru','admin','') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`akun_id`, `nis_nip`, `password`, `role`, `created_at`) VALUES
(1, '10243313', '$2y$10$U9F8MtO3R2NtH5XyxYao0e5koYtG6CwE.TQH0L43D0wU0C0pcpyKW', 'siswa', '2026-02-12 10:33:23'),
(2, 'nip_guru', '$2y$10$62.K3.rFrjQqEOjW3PtRs./2fw4TxPFRK74nCjVBStz/0uiJi2ali', 'guru', '2026-02-12 10:33:23'),
(3, '10243305', '$2y$10$CylmBBYjqhbvGeI9xwELk.ZQR/LuEqNITrEcry222oJE2G8EuKEZa', 'siswa', '2026-02-12 11:30:43'),
(4, 'admin001', '$2y$10$3Tqty5eIfHwjDaoPzy2A..AsIGdf0Zdiq8Wd.qVtW4RJyi/75mikW', 'admin', '2026-02-12 13:00:25');

-- --------------------------------------------------------

--
-- Table structure for table `grup`
--

CREATE TABLE `grup` (
  `id_grup` int(11) NOT NULL,
  `nama_grup` varchar(100) DEFAULT NULL,
  `dibuat_oleh_akun_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grup`
--

INSERT INTO `grup` (`id_grup`, `nama_grup`, `dibuat_oleh_akun_id`) VALUES
(1, 'Grup Matematika', 2),
(2, 'Grup Fisika', 2),
(3, 'XI RPL B', 3);

-- --------------------------------------------------------

--
-- Table structure for table `grup_anggota`
--

CREATE TABLE `grup_anggota` (
  `grup_id` int(11) NOT NULL,
  `akun_id` int(11) NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grup_anggota`
--

INSERT INTO `grup_anggota` (`grup_id`, `akun_id`, `joined_at`, `deleted_at`) VALUES
(3, 1, '2026-02-12 14:33:18', NULL),
(3, 3, '2026-02-12 14:33:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int(11) NOT NULL,
  `grup_id` int(11) DEFAULT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `grup_id`, `judul`, `deskripsi`, `tanggal`, `jam_mulai`, `jam_selesai`) VALUES
(1, 1, 'Ulangan Harian', 'Ulangan Matematika Bab 3', '2024-04-01', '07:00:00', '08:30:00'),
(2, 2, 'Praktikum', 'Praktikum Fisika Minggu ke-2', '2024-04-02', '10:00:00', '12:00:00'),
(3, 3, 'Inggris', 'Bersama Bu Kiki', NULL, '07:00:00', '08:00:00'),
(4, 3, 'Olahraga', 'Bersama Pa Engkus', NULL, '08:00:00', '09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `akun_id` int(11) DEFAULT NULL,
  `jadwal_id` int(11) DEFAULT NULL,
  `tugas_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `waktu_kirim` datetime DEFAULT NULL,
  `pesan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `akun_id`, `jadwal_id`, `tugas_id`, `template_id`, `waktu_kirim`, `pesan`) VALUES
(1, 1, 1, NULL, 1, '2024-03-04 08:00:00', 'Reminder: Ulangan Matematika Besok'),
(2, 2, NULL, 1, 2, '2024-03-09 09:00:00', 'Reminder: Laporan Praktikum Fisika');

-- --------------------------------------------------------

--
-- Table structure for table `prefrensi_user`
--

CREATE TABLE `prefrensi_user` (
  `id_preferensi` int(11) NOT NULL,
  `akun_id` int(11) DEFAULT NULL,
  `pengingat_aktif` tinyint(1) DEFAULT NULL,
  `waktu_default` time DEFAULT NULL,
  `snooze` int(11) DEFAULT NULL,
  `zona_waktu` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prefrensi_user`
--

INSERT INTO `prefrensi_user` (`id_preferensi`, `akun_id`, `pengingat_aktif`, `waktu_default`, `snooze`, `zona_waktu`) VALUES
(1, 1, 1, '08:00:00', 5, 'Asia/Jakarta'),
(2, 2, 0, '09:00:00', 10, 'Asia/Jakarta');

-- --------------------------------------------------------

--
-- Table structure for table `template_pengingat`
--

CREATE TABLE `template_pengingat` (
  `id_template` int(11) NOT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `isi_template` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_pengingat`
--

INSERT INTO `template_pengingat` (`id_template`, `judul`, `isi_template`) VALUES
(1, 'Template 1', 'Jangan lupa tugas Anda!'),
(2, 'Template 2', 'Jadwal telah diupdate.');

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id_tugas` int(11) NOT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `tenggat` datetime DEFAULT NULL,
  `grup_id` int(11) DEFAULT NULL,
  `jadwal_id` int(11) DEFAULT NULL,
  `dibuat_oleh_akun_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tugas`
--

INSERT INTO `tugas` (`id_tugas`, `judul`, `deskripsi`, `tenggat`, `grup_id`, `jadwal_id`, `dibuat_oleh_akun_id`) VALUES
(1, 'Kerjakan Latihan', 'Kerjakan latihan nomor 1-10', '2024-03-05 23:59:00', 1, 1, 2),
(2, 'Buat Laporan', 'Laporan praktikum Fisika', '2024-03-10 23:59:00', 2, 2, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`akun_id`);

--
-- Indexes for table `grup`
--
ALTER TABLE `grup`
  ADD PRIMARY KEY (`id_grup`),
  ADD KEY `dibuat_oleh_akun_id` (`dibuat_oleh_akun_id`);

--
-- Indexes for table `grup_anggota`
--
ALTER TABLE `grup_anggota`
  ADD PRIMARY KEY (`grup_id`,`akun_id`),
  ADD KEY `idx_akun` (`akun_id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `grup_id` (`grup_id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `akun_id` (`akun_id`),
  ADD KEY `jadwal_id` (`jadwal_id`),
  ADD KEY `tugas_id` (`tugas_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `prefrensi_user`
--
ALTER TABLE `prefrensi_user`
  ADD PRIMARY KEY (`id_preferensi`),
  ADD KEY `akun_id` (`akun_id`);

--
-- Indexes for table `template_pengingat`
--
ALTER TABLE `template_pengingat`
  ADD PRIMARY KEY (`id_template`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id_tugas`),
  ADD KEY `grup_id` (`grup_id`),
  ADD KEY `jadwal_id` (`jadwal_id`),
  ADD KEY `dibuat_oleh_akun_id` (`dibuat_oleh_akun_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `akun_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grup`
--
ALTER TABLE `grup`
  MODIFY `id_grup` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prefrensi_user`
--
ALTER TABLE `prefrensi_user`
  MODIFY `id_preferensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `template_pengingat`
--
ALTER TABLE `template_pengingat`
  MODIFY `id_template` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id_tugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grup`
--
ALTER TABLE `grup`
  ADD CONSTRAINT `pembuat_grup_fk` FOREIGN KEY (`dibuat_oleh_akun_id`) REFERENCES `akun` (`akun_id`);

--
-- Constraints for table `grup_anggota`
--
ALTER TABLE `grup_anggota`
  ADD CONSTRAINT `fk_grup_anggota_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`akun_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grup_anggota_grup` FOREIGN KEY (`grup_id`) REFERENCES `grup` (`id_grup`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`grup_id`) REFERENCES `grup` (`id_grup`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`akun_id`),
  ADD CONSTRAINT `notifikasi_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id_jadwal`),
  ADD CONSTRAINT `notifikasi_ibfk_3` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id_tugas`),
  ADD CONSTRAINT `notifikasi_ibfk_4` FOREIGN KEY (`template_id`) REFERENCES `template_pengingat` (`id_template`);

--
-- Constraints for table `prefrensi_user`
--
ALTER TABLE `prefrensi_user`
  ADD CONSTRAINT `prefrensi_user_ibfk_1` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`akun_id`);

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`grup_id`) REFERENCES `grup` (`id_grup`),
  ADD CONSTRAINT `tugas_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id_jadwal`),
  ADD CONSTRAINT `tugas_ibfk_3` FOREIGN KEY (`dibuat_oleh_akun_id`) REFERENCES `akun` (`akun_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

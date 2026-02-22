-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 22, 2026 at 01:49 PM
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
(1, '10243313', '$2y$10$RedoOY.5ao8YcMP6ycmLHeprSvAtgpMXZsxv0cWkiYtYJQX2pW3cK', 'admin', '2026-02-12 10:33:23'),
(2, 'nip_guru', '$2y$10$62.K3.rFrjQqEOjW3PtRs./2fw4TxPFRK74nCjVBStz/0uiJi2ali', 'guru', '2026-02-12 10:33:23'),
(3, '10243305', '$2y$10$CylmBBYjqhbvGeI9xwELk.ZQR/LuEqNITrEcry222oJE2G8EuKEZa', 'siswa', '2026-02-12 11:30:43'),
(6, '10242026', '$2y$10$M2YrVDYV2YuqSERZY6NFTusSuWBnTnre1FnIV1m61O.WImOeTYrCm', 'guru', '2026-02-22 16:50:52'),
(7, 'akun_tes', '$2y$10$W9JMJfwQq3J6AyB1G/PcOunrG3a1mG8tvkksQrFVdZM6TOyzwpB1.', 'siswa', '2026-02-22 18:53:47'),
(8, '102306363', '$2y$10$6W08PjLEAyQz73EqWJIwbumeiPkFeZdC8NHtk73DNdndO8egkixKC', 'siswa', '2026-02-22 19:16:32');

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
(3, 'XI RPL B', 6);

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
(3, 1, '2026-02-22 17:21:22', NULL),
(3, 2, '2026-02-22 17:20:58', '2026-02-22 17:21:02'),
(3, 3, '2026-02-22 17:21:22', NULL),
(3, 6, '2026-02-22 17:21:18', '2026-02-22 17:21:22'),
(3, 7, '2026-02-22 19:07:50', NULL),
(3, 8, '2026-02-22 19:19:00', NULL);

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
(2, 'Buat Laporan', 'Laporan praktikum Fisika', '2024-03-10 23:59:00', 2, 2, 2),
(3, 'Speaking in English', 'Kirim Foto Tugas', '2026-02-23 23:59:00', 3, 3, 6);

-- --------------------------------------------------------

--
-- Table structure for table `tugas_pengumpulan`
--

CREATE TABLE `tugas_pengumpulan` (
  `id_pengumpulan` int(11) NOT NULL,
  `tugas_id` int(11) NOT NULL,
  `akun_id` int(11) NOT NULL,
  `telegram_chat_id` bigint(20) DEFAULT NULL,
  `file_type` varchar(20) NOT NULL DEFAULT 'document',
  `telegram_file_id` varchar(255) NOT NULL,
  `telegram_file_unique_id` varchar(255) DEFAULT NULL,
  `telegram_file_path` varchar(255) DEFAULT NULL,
  `nama_file_asli` varchar(255) DEFAULT NULL,
  `file_mime` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_lokal` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `status` enum('dikumpulkan','dinilai','revisi','terlambat') NOT NULL DEFAULT 'dikumpulkan',
  `nilai` decimal(5,2) DEFAULT NULL,
  `catatan_guru` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tugas_pengumpulan`
--

INSERT INTO `tugas_pengumpulan` (`id_pengumpulan`, `tugas_id`, `akun_id`, `telegram_chat_id`, `file_type`, `telegram_file_id`, `telegram_file_unique_id`, `telegram_file_path`, `nama_file_asli`, `file_mime`, `file_size`, `file_lokal`, `caption`, `status`, `nilai`, `catatan_guru`, `submitted_at`, `graded_at`, `updated_at`) VALUES
(1, 3, 1, 8445211581, 'photo', 'AgACAgUAAxkBAAIBY2ma5dL3OydpacRsVezFek3LvmwpAAITDmsbLrLZVKhKg5rHYeImAQADAgADeAADOgQ', 'AQADEw5rGy6y2VR9', 'photos/file_0.jpg', 'foto_tugas.jpg', 'image/jpeg', 64053, 'data/tugas_uploads/tugas_3_akun_1_20260222_121739_7be239d0.jpg', '', 'dinilai', 100.00, '👍', '2026-02-22 18:17:41', '2026-02-22 18:20:23', '2026-02-22 18:20:23'),
(2, 3, 8, 6202447439, 'photo', 'AgACAgUAAxkBAAIBtWma9MrJHf614IHszmwCwYswahzWAALkEGsbkdDYVB3Bncph8-UqAQADAgADeAADOgQ', 'AQAD5BBrG5HQ2FR9', 'photos/file_2.jpg', 'foto_tugas.jpg', 'image/jpeg', 28725, 'data/tugas_uploads/tugas_3_akun_8_20260222_132131_2b2cc892.jpg', '', 'dikumpulkan', NULL, NULL, '2026-02-22 19:21:32', NULL, '2026-02-22 19:21:32');

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
-- Indexes for table `tugas_pengumpulan`
--
ALTER TABLE `tugas_pengumpulan`
  ADD PRIMARY KEY (`id_pengumpulan`),
  ADD UNIQUE KEY `uniq_tugas_akun` (`tugas_id`,`akun_id`),
  ADD KEY `idx_tp_akun` (`akun_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `akun_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id_tugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tugas_pengumpulan`
--
ALTER TABLE `tugas_pengumpulan`
  MODIFY `id_pengumpulan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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

--
-- Constraints for table `tugas_pengumpulan`
--
ALTER TABLE `tugas_pengumpulan`
  ADD CONSTRAINT `fk_tp_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`akun_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_tugas` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id_tugas`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

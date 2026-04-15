-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 12:45 AM
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
  `nama_lengkap` varchar(120) DEFAULT NULL,
  `kelas_label` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `role` enum('siswa','guru','admin','') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`akun_id`, `nis_nip`, `password`, `nama_lengkap`, `kelas_label`, `jenis_kelamin`, `role`, `created_at`) VALUES
(1, '10243313', '$2y$10$RedoOY.5ao8YcMP6ycmLHeprSvAtgpMXZsxv0cWkiYtYJQX2pW3cK', NULL, NULL, NULL, 'admin', '2026-02-12 10:33:23'),
(2, 'nip_guru', '$2y$10$62.K3.rFrjQqEOjW3PtRs./2fw4TxPFRK74nCjVBStz/0uiJi2ali', NULL, NULL, NULL, 'guru', '2026-02-12 10:33:23'),
(3, '10243305', '$2y$10$CylmBBYjqhbvGeI9xwELk.ZQR/LuEqNITrEcry222oJE2G8EuKEZa', NULL, NULL, NULL, 'siswa', '2026-02-12 11:30:43'),
(6, '10242026', '$2y$10$M2YrVDYV2YuqSERZY6NFTusSuWBnTnre1FnIV1m61O.WImOeTYrCm', NULL, NULL, NULL, 'guru', '2026-02-22 16:50:52'),
(7, 'akun_tes', '$2y$10$W9JMJfwQq3J6AyB1G/PcOunrG3a1mG8tvkksQrFVdZM6TOyzwpB1.', NULL, NULL, NULL, 'siswa', '2026-02-22 18:53:47'),
(8, '102306363', '$2y$10$IGydGT6whcSG8QShLIImfeRUe6qXdJarrAlZuk0AUazlA4mMEi3Oe', NULL, NULL, NULL, 'siswa', '2026-02-22 19:16:32'),
(9, '102406935', '$2y$10$UP/1AMSq4S9x9XqV7rtci.mPiCPZZng7H/2zVXRAVoQMxG0DB30XK', 'ADITYA FIRMANSYAH ANDIRA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:56'),
(10, '102406936', '$2y$10$i6xsuzH03y3AF/SL.WBkDeOeGINMbqL/KHAchGkv.WLpjTxctF1eq', 'AHMAD DANI', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:56'),
(11, '102406937', '$2y$10$5F4t8RS9n7CogB9FMC5MDuCcnJgEe48/RriXhFoxdOclW8MgGpIrO', 'ALYA NUR FAUZIYYAH', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:56'),
(12, '102406938', '$2y$10$tg3fV.ksE.POA3Qcqv2a0uTL9.bd7JKclIuLwNYLGbq3sNGT42Npq', 'ANDHIKA ANDRIANA PUTRA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:56'),
(13, '102406939', '$2y$10$g9jsg1Tje6gNhsd3M/Mw0.YU4IJtVgkai/NrcqUP39UF1VU5IObde', 'ANDIKA GUSTIAWAN', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:56'),
(14, '102406940', '$2y$10$kgKi3xkqzNWqPmR0QL36luE2Za2OGPp0WtFFBy2k752cW/vhSP61C', 'ANGGI ANGGRAENI', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:56'),
(15, '102406941', '$2y$10$IyWKl./ptLzuv525tsds1./rFrnqIYfwoewGOH0.n2L5IsGLDr1vm', 'ANNISA SALSABILA KURNIA', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:57'),
(16, '102406942', '$2y$10$k0ncEN8zTkswjyvNXNNkt.qMyewr5O19eVkGEW5nSgEWGNeql/KtK', 'ARIPIN FIRMANSYAH', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(17, '102406943', '$2y$10$cEgmFVIpuzwkxAVwkReI2.Zqx3f.ut8XWhz6t9TdNtOfSt3SGc7gu', 'AZZAM NASYWAN EL FAWWAZ', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(18, '102406944', '$2y$10$MFA8Wkj5IZie2ptVRoJH3u3bBjVVHr9a7QNLUuz2uVE.Fmxw3i8ZC', 'DIMAS ARIO SUGIYANTO', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(19, '102406945', '$2y$10$IBN3CptRY3Ml6XmCv8SZoOdyj27B9aTxGguyISd/xC5TX.7lRLidy', 'ESHAN MUHAMAD SAPUTRA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(20, '102406946', '$2y$10$Bd9M9F4mIHTPaQwbgsWuauDIVU9.kxYf9jW8T8/kujEMEUKhxjrK.', 'GAVIN ADITYA WISMAYA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(21, '102406947', '$2y$10$ivHpALpB5CtqYXTvO5TrGOrs1Sz2KLOKPWHF8frzQEYK4KOu47CZ2', 'GRANDY SAMIE EL GHIFARI', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(22, '102406948', '$2y$10$M2qdkUo9J18.VQsx00Ec9u/NPg4W24BI1p3lHukAlTblbu9C5TINe', 'HAIDAR ALIF FAJAR MAULANA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(23, '102406949', '$2y$10$aHO6ZKDmAmvEOrHlr5KRCOuOO2enRrNQ0GfmUTNzTjX9Ft8aPFpqG', 'IBNU RAMADHAN', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(24, '102406950', '$2y$10$krrNccxt6qO5p4sRuD9H4uJMeEayHs7tS.rKDkdcYpl5bZhlbbROC', 'LUTHFI QIDWATUL HAQ', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(25, '102406951', '$2y$10$YeiLajmhHa8BmUxtdzJFV.UmkMTT9pQMDjgrYmIdvP5n2TPDo48Ba', 'MUHAMAD FARISHKY', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(26, '102406952', '$2y$10$PsPIheVqF.Z8pCdYQ0EZoukVfqE5bKmbjOQMDWD.ZNGfZ2.3M8pRy', 'MUHAMMAD BAYHAQQI AL\'AYUBI', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(27, '102406953', '$2y$10$QnxyI364jb9gj1FHULIJteMxN0760C8FUjRT2t6gU4/lefEtptY6i', 'MUHAMMAD FAEYZA ZAKI DEBIAN', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(28, '102406954', '$2y$10$8NdyDj/Fr3xNcGsXTxJV0uunp/WxN.uT4O2Ao0kJIiN/PY8.1YgeS', 'MUHAMMAD LUTHFAN AL-FAHRI', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(29, '102406955', '$2y$10$1ee/aIlgwJs3kQ/TRo1Ezem4coq2a4cElcSWH75RFAmP9eSQz2eZ6', 'MUHAMMAD ZIDANE RUDIANSYAH', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(30, '102406956', '$2y$10$lFAFpxU3ZTl3ydiiTaQm7emfFkVGvcJLOVEXL66R7JkqBY/Axq7BC', 'NAISHA ASHIFA AURELYA', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:57'),
(31, '102406957', '$2y$10$yH5ayJDBw8i2JGjRUJ6POuLPZjlUB6I0qlSIWP.MSfmQaMURJfmc.', 'NIKOLAS DWI SETIO PUTRA SUHARJONO', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:57'),
(32, '102406958', '$2y$10$7.O2SYhPP5lBLfrz6g9ypeCm2UrCP3KuHTqng4raiMcu/HXgv.sRC', 'RADITYA SAVERO', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(33, '102406959', '$2y$10$iMnxRmcaL1kXbDv9YhWcGe2erF1N86VYTumbDJ/Eoie/f/74d0pbi', 'RAKA PUTRA PRATAMA SETIAWAN', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(34, '102406960', '$2y$10$QAFpkpDX1M84pggBJRQ8F.ccYvvoxHuEAknX871rHNnMizSyNWJXC', 'RASYAD SAKHIY ZAHRAN', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(35, '102406961', '$2y$10$38GpQO22y/SFnUZyjdthD.cA/NfZxuSbz4dGYwR0RHuz4gpvSzlra', 'REIVAN HIDAYAT KERTADIRDJA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(36, '102406962', '$2y$10$BuSeEtmFCDQw9xoWveoro.tnz1vbzuqvn2Unax52gncwFO4UMgqzq', 'RENRI IBRAHIM RAMDAN PRATAMA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(37, '102406963', '$2y$10$i5tB/FexFxAiLIaImixqxecg3z.xQuONi83CEMmZhlgPhlhny5o2u', 'RIFA ALFIANSYAH', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(38, '102406964', '$2y$10$tg3cDA4jkm7pajQXJY./vedlTrVxbu/yRhBSwfvtU6D3nLX7kq7FW', 'RIFQI ARIQ FAEYZA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(39, '102406965', '$2y$10$j0IimpdxAkpGtSoAh8xz3uOmjzqD4UeiTMDPp0XwWVbvGBg1U0Xxq', 'RINO BOARGEZ VAAKO', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(40, '102406966', '$2y$10$onj5BOz/1FpChY5YrjeEfOVP7ojeePjIN33iSI0GbsXLwAxVQZf/m', 'RIO PUTRA AGUMANDA', 'X PPLG A', 'L', 'siswa', '2026-04-15 18:58:58'),
(41, '102406967', '$2y$10$x/ubp87pEn9uAO9y6mSSVuYj32iXiKj5U8/Bh.sZKaNzWoY51eNDy', 'SALSA ANJANI', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:58'),
(42, '102406968', '$2y$10$BLWmwzrxEe2q.4vX3GoYNu0Xgz5AsuTX7R5m34qwQ01ZD/23D8o8.', 'SYAHNAZ NUR SYAFA', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:58'),
(43, '102406969', '$2y$10$u4KKCOBlWoAEVZNJX5zjHO40nlknj5prcjgoJzYDzL8gTobW4f4fy', 'ZHAVIRA NINDYA PERTIWI', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:58'),
(44, '102406970', '$2y$10$kNdsq2xbwAmpfML/yhD2w.yJgwnXmBMljOL39uX1fsI7WbZo0nrsu', 'ZIVANA SYIFA ALFIYYAH', 'X PPLG A', 'P', 'siswa', '2026-04-15 18:58:58'),
(45, '102406971', '$2y$10$zFeV2Bpq0W4PmLf6awq4w.tVbalWp72VucRwxq1VrtHhz4WiBpJEO', 'AHKAM LISANUL MIZAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:52'),
(46, '102406972', '$2y$10$OT5ohEkdYOaFW7.U3LSTy.aKNr9EQ/n.ZPKqb0h2oLhCpkAsFEJ1C', 'ALFIN BATHOSAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:52'),
(47, '102406973', '$2y$10$4KUNbiGR1KQEQm8oyZeq6.n3STqrJVNPaPs6XB.Kexmz/iMSbDSCG', 'ANISA JAYA LESTARI', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:52'),
(48, '102406974', '$2y$10$/UxQWps3xpK.OOTFEf9vUObvjo5ZrJKBbTWuNlJmIZyi4fJpX2/9W', 'DEAN PETRA BETTI RUNESI', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:52'),
(49, '102406975', '$2y$10$n0HEYbWha/1Hgg5slaBBFevc.GpQGkXwaFGe.Zil.lwvLdVSD3F2y', 'ELVA CELIA FEBRIANA', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:52'),
(50, '102406976', '$2y$10$pRsSs5qIMoungl/5M5pNgODrgvX4fT3Z45jQsd8tNaLeEP8PY97z.', 'EXCHEL RINDA DWIKY DARMAWAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:52'),
(51, '102406977', '$2y$10$3Fmslp6ebt52gNanAYudWuUYAciZim1OQm7WuZoEIlTFnzf47KVp6', 'FADLAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:52'),
(52, '102406978', '$2y$10$rW1MSH8ljou64RzABGw8vOleEtwY9kgIV3E5H6OrCCPGnXl8bb81e', 'FAHRI HADI PRATAMA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:52'),
(53, '102406979', '$2y$10$1Rza3Z72VBO3JBtFWLowCuWYM78cc9.OP3/56OkEp1c8eN66V6NaO', 'FAUZAN ABY PRATAMA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(54, '102406980', '$2y$10$HzJVMH9y8jEGrmFB4Uw4ougU4uXZGOI8QdgdArNsQhF18WLF7di62', 'FAUZI IRWANA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(55, '102406981', '$2y$10$1BFkha56bs9Jj2bWl5JAiemfnqFKa7r8KW3pD/PSlJIMp/8oIIxBO', 'HAIL SUKMA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(56, '102406982', '$2y$10$H.XtotZw.E4jT3jl0P/dyu.iiMZoCRqfaVU1.M.S4txAueZvNMPzq', 'ISTIFA FALASIFAH KHATAMI', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:53'),
(57, '102406983', '$2y$10$inUZHFfnqQsrJWd86aedqO1T5bNGwTOoM1cPLoplJG/wdKwMr7zp2', 'MUHAMAD DIRGAHAYU DWI SAPUTRA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(58, '102406984', '$2y$10$WTnIfM6Hx6Vh.vrn0K0Dru.eIkEVaoJCvJRz9FqU1n1W7TUu0g0qq', 'MUHAMAD RAMDAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(59, '102406985', '$2y$10$q3bcuHAzN5H5biPdGHcEZ.Wi34cQYM6dNDKrbTxRq7/I88ZKRe3Pa', 'MUHAMAD RIZKI ARDIANSAH', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(60, '102406986', '$2y$10$IY2c9Af2cJS4KF5JSrU1NuVrhimQlTqX9u9vMgEBXCyEBknAaLoWu', 'MUHAMMAD AKBAR', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(61, '102406987', '$2y$10$qzHsIroD7SZuFrtG03DLLezvCtygC7/viDnSgMc5g.Fxyq4Sz2D3W', 'MUHAMMAD ALIF FIRDAUS', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(62, '102406988', '$2y$10$ZKD2XOW4cVfh0N38SDsl0.nbObtu27XkbOE.ihK7zOdRraF7Xb4Oq', 'MUHAMMAD AZAM IZZATULHAQ', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(63, '102406989', '$2y$10$BmaEw0GiQjUr8.gFlcjeVe/CB3JIY6e0a9A5NOoDc01v..l3Z.0MO', 'MUHAMMAD AZKA SA\'ADI NABHAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(64, '102406990', '$2y$10$letPJqYnw8MSTvtnOKqbLOG28wDQm2CsuQ7JFkh1LI.zEATUoopN6', 'MUHAMMAD CHANDRA PRATAMA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(65, '102406991', '$2y$10$HX1F9Z22mrTIEWpAGLk2oOAwlpl.e5ygNjCgE53QWwlpdWARnmvKW', 'MUHAMMAD EZRA FEBRITAUFANI', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(66, '102406992', '$2y$10$9IxK7ILY90MTm68kzrzXzeOMpAeZ9pRSGgzx4viB49NYZz10vA.Q.', 'MULKI IKRAM MAULANA LUBIS', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(67, '102406993', '$2y$10$CjzerMZETRYc4fvNe9ubfuePR2hjmdxeAFr0Xxc3c1pD9r.JG8XZ2', 'NOVI ROPIAH', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:53'),
(68, '102406994', '$2y$10$1dZCJu6ySIcOKvFk.8xgwe0STLM6jDJAo/tv7jREFalP6HrrGO9nC', 'NOVRI KRISNA PRATAMA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:53'),
(69, '102406995', '$2y$10$uz.WNyNCBDdN8YRt5eORuOVx7d3nEm78Q9BT4m/LpIQvpK3VkTZFe', 'RADITYA RAYGA MULANA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(70, '102406996', '$2y$10$tsVQ37oZ34VBnoVsPv6W8OXeyjNpiCAZusILjuZFm2/2kg9nO3LSy', 'RAFASYA ATHAULLAH RUIZKHA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(71, '102406997', '$2y$10$p39RwFUIDTCiEwStuiF0a.1Y.MGVgX76/C1HNVVEFgpZWM7P78XcG', 'RAHMA KHOYRUL HAWA', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:54'),
(72, '102406998', '$2y$10$qUK8JTgkPLFvyvmCLXIdcO5cL0mbBx8CY.V4HK9RagArSssDJtW72', 'RAIHAN FADLANSYAH', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(73, '102406999', '$2y$10$SDUR8242JRIHvYZoLfr.Luzuy126o6zrGvGLR8hxoYmdIim41afwS', 'RAYHAN RIZKY PRATAMA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(74, '102407000', '$2y$10$k/T7g.dV8Z.Gr/l7YxV1yO/84NTQkuRZuK5dWxV57vaTKOEqeLQoW', 'REVITA GADIS AMIJAYA', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:54'),
(75, '102407001', '$2y$10$bw6iZGerZdyQnPXnZBCItO5oYKDjEITTEPL7ADJWwcI7zWXnSP32S', 'RIZKI AHMAD MAULANA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(76, '102407002', '$2y$10$YUQK73FrMh1LNGgwQPPsS.1tKEAE6e55w0nUXWiMDPsvOLg4P8gry', 'RIZQY RAMADHAN INDRAWAN PUTRA', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(77, '102407003', '$2y$10$3c1RPuSq/t.M08U/C58fuuFvi1uDtEFRtoo.h2rx9yUucUFArZ5li', 'SALMA ASHANADIYA', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:54'),
(78, '102407004', '$2y$10$gllsLOVtONuc8tvziLI//eG6gaWrA6SMzIxcqFdMLT2DE9jZiXa/u', 'SAZKIYA LUTHFIAH ADZANI', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:54'),
(79, '102407005', '$2y$10$FmHo5IsTp70Hdf9MBghXf.B/Ox2z055D7hZQ9IH7k71KTyKnVAAtu', 'YOGA PRATAMA SETIAWAN', 'XI PPLG B', 'L', 'siswa', '2026-04-15 20:10:54'),
(80, '102407006', '$2y$10$vkOtxLj5UbC9s.Xgpj4JJOL2ZXOp.IYak9AfSMNK/C0huOIjLhJNe', 'YUNIFA RIZKY', 'XI PPLG B', 'P', 'siswa', '2026-04-15 20:10:54');

-- --------------------------------------------------------

--
-- Table structure for table `akun_telegram`
--

CREATE TABLE `akun_telegram` (
  `akun_id` int(11) NOT NULL,
  `telegram_chat_id` bigint(20) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun_telegram`
--

INSERT INTO `akun_telegram` (`akun_id`, `telegram_chat_id`, `updated_at`) VALUES
(1, 8445211581, '2026-04-15 19:49:13'),
(8, 6202447439, '2026-04-06 20:26:23');

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
(2, 'Grup Fisika', 2),
(1, 'Grup Matematika', 2),
(4, 'X PPLG A', 1),
(5, 'XI PPLG B', 1),
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
(3, 8, '2026-02-22 19:19:00', NULL),
(4, 9, '2026-04-15 18:58:56', NULL),
(4, 10, '2026-04-15 18:58:56', NULL),
(4, 11, '2026-04-15 18:58:56', NULL),
(4, 12, '2026-04-15 18:58:56', NULL),
(4, 13, '2026-04-15 18:58:56', NULL),
(4, 14, '2026-04-15 18:58:56', NULL),
(4, 15, '2026-04-15 18:58:57', NULL),
(4, 16, '2026-04-15 18:58:57', NULL),
(4, 17, '2026-04-15 18:58:57', NULL),
(4, 18, '2026-04-15 18:58:57', NULL),
(4, 19, '2026-04-15 18:58:57', NULL),
(4, 20, '2026-04-15 18:58:57', NULL),
(4, 21, '2026-04-15 18:58:57', NULL),
(4, 22, '2026-04-15 18:58:57', NULL),
(4, 23, '2026-04-15 18:58:57', NULL),
(4, 24, '2026-04-15 18:58:57', NULL),
(4, 25, '2026-04-15 18:58:57', NULL),
(4, 26, '2026-04-15 18:58:57', NULL),
(4, 27, '2026-04-15 18:58:57', NULL),
(4, 28, '2026-04-15 18:58:57', NULL),
(4, 29, '2026-04-15 18:58:57', NULL),
(4, 30, '2026-04-15 18:58:57', NULL),
(4, 31, '2026-04-15 18:58:57', NULL),
(4, 32, '2026-04-15 18:58:58', NULL),
(4, 33, '2026-04-15 18:58:58', NULL),
(4, 34, '2026-04-15 18:58:58', NULL),
(4, 35, '2026-04-15 18:58:58', NULL),
(4, 36, '2026-04-15 18:58:58', NULL),
(4, 37, '2026-04-15 18:58:58', NULL),
(4, 38, '2026-04-15 18:58:58', NULL),
(4, 39, '2026-04-15 18:58:58', NULL),
(4, 40, '2026-04-15 18:58:58', NULL),
(4, 41, '2026-04-15 18:58:58', NULL),
(4, 42, '2026-04-15 18:58:58', NULL),
(4, 43, '2026-04-15 18:58:58', NULL),
(4, 44, '2026-04-15 18:58:58', NULL),
(5, 45, '2026-04-15 20:10:52', NULL),
(5, 46, '2026-04-15 20:10:52', NULL),
(5, 47, '2026-04-15 20:10:52', NULL),
(5, 48, '2026-04-15 20:10:52', NULL),
(5, 49, '2026-04-15 20:10:52', NULL),
(5, 50, '2026-04-15 20:10:52', NULL),
(5, 51, '2026-04-15 20:10:52', NULL),
(5, 52, '2026-04-15 20:10:53', NULL),
(5, 53, '2026-04-15 20:10:53', NULL),
(5, 54, '2026-04-15 20:10:53', NULL),
(5, 55, '2026-04-15 20:10:53', NULL),
(5, 56, '2026-04-15 20:10:53', NULL),
(5, 57, '2026-04-15 20:10:53', NULL),
(5, 58, '2026-04-15 20:10:53', NULL),
(5, 59, '2026-04-15 20:10:53', NULL),
(5, 60, '2026-04-15 20:10:53', NULL),
(5, 61, '2026-04-15 20:10:53', NULL),
(5, 62, '2026-04-15 20:10:53', NULL),
(5, 63, '2026-04-15 20:10:53', NULL),
(5, 64, '2026-04-15 20:10:53', NULL),
(5, 65, '2026-04-15 20:10:53', NULL),
(5, 66, '2026-04-15 20:10:53', NULL),
(5, 67, '2026-04-15 20:10:53', NULL),
(5, 68, '2026-04-15 20:10:53', NULL),
(5, 69, '2026-04-15 20:10:54', NULL),
(5, 70, '2026-04-15 20:10:54', NULL),
(5, 71, '2026-04-15 20:10:54', NULL),
(5, 72, '2026-04-15 20:10:54', NULL),
(5, 73, '2026-04-15 20:10:54', NULL),
(5, 74, '2026-04-15 20:10:54', NULL),
(5, 75, '2026-04-15 20:10:54', NULL),
(5, 76, '2026-04-15 20:10:54', NULL),
(5, 77, '2026-04-15 20:10:54', NULL),
(5, 78, '2026-04-15 20:10:54', NULL),
(5, 79, '2026-04-15 20:10:54', NULL),
(5, 80, '2026-04-15 20:10:54', NULL);

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
(4, 3, 'Olahraga', 'Bersama Pa Engkus', NULL, '08:00:00', '09:00:00'),
(5, 5, 'Inggris', 'Bersama Bu Yulie', '2026-04-20', '07:00:00', '08:00:00');

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
(2, 2, NULL, 1, 2, '2024-03-09 09:00:00', 'Reminder: Laporan Praktikum Fisika'),
(3, 1, NULL, NULL, NULL, '2026-04-06 19:55:26', 'tes'),
(4, 8, NULL, NULL, NULL, '2026-04-06 20:19:13', '[TEST] Notifikasi simulasi end-to-end'),
(5, 8, NULL, NULL, 1, '2026-04-06 20:22:17', 'Jangan lupa tugas Anda!');

-- --------------------------------------------------------

--
-- Table structure for table `pengingat_terkirim`
--

CREATE TABLE `pengingat_terkirim` (
  `id_pengingat` bigint(20) NOT NULL,
  `akun_id` int(11) NOT NULL,
  `jenis` enum('jadwal','tugas') NOT NULL,
  `ref_id` int(11) NOT NULL,
  `offset_menit` int(11) NOT NULL,
  `target_waktu` datetime NOT NULL,
  `dikirim_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prefrensi_user`
--

CREATE TABLE `prefrensi_user` (
  `id_preferensi` int(11) NOT NULL,
  `akun_id` int(11) DEFAULT NULL,
  `pengingat_aktif` tinyint(1) DEFAULT NULL,
  `waktu_default` time DEFAULT NULL,
  `offset_custom_menit` int(11) DEFAULT NULL,
  `snooze` int(11) DEFAULT NULL,
  `snooze_sampai` datetime DEFAULT NULL,
  `zona_waktu` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prefrensi_user`
--

INSERT INTO `prefrensi_user` (`id_preferensi`, `akun_id`, `pengingat_aktif`, `waktu_default`, `offset_custom_menit`, `snooze`, `snooze_sampai`, `zona_waktu`) VALUES
(1, 1, 1, '08:00:00', NULL, 5, NULL, 'Asia/Jakarta'),
(2, 2, 0, '09:00:00', NULL, 10, NULL, 'Asia/Jakarta'),
(3, 8, 1, '08:00:00', 45, 15, '2026-04-06 15:51:13', 'Asia/Jakarta');

-- --------------------------------------------------------

--
-- Table structure for table `rayhanrp_schema_migrations`
--

CREATE TABLE `rayhanrp_schema_migrations` (
  `version` int(11) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rayhanrp_schema_migrations`
--

INSERT INTO `rayhanrp_schema_migrations` (`version`, `applied_at`) VALUES
(1, '2026-04-15 18:16:26'),
(2, '2026-04-15 18:16:26'),
(3, '2026-04-15 18:16:26');

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
  ADD PRIMARY KEY (`akun_id`),
  ADD UNIQUE KEY `uniq_akun_nis_nip` (`nis_nip`);

--
-- Indexes for table `akun_telegram`
--
ALTER TABLE `akun_telegram`
  ADD PRIMARY KEY (`akun_id`),
  ADD UNIQUE KEY `uniq_telegram_chat_id` (`telegram_chat_id`);

--
-- Indexes for table `grup`
--
ALTER TABLE `grup`
  ADD PRIMARY KEY (`id_grup`),
  ADD UNIQUE KEY `uniq_grup_owner_name` (`nama_grup`,`dibuat_oleh_akun_id`),
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
-- Indexes for table `pengingat_terkirim`
--
ALTER TABLE `pengingat_terkirim`
  ADD PRIMARY KEY (`id_pengingat`),
  ADD UNIQUE KEY `uniq_pengingat` (`akun_id`,`jenis`,`ref_id`,`offset_menit`,`target_waktu`),
  ADD KEY `idx_pengingat_waktu` (`dikirim_pada`);

--
-- Indexes for table `prefrensi_user`
--
ALTER TABLE `prefrensi_user`
  ADD PRIMARY KEY (`id_preferensi`),
  ADD UNIQUE KEY `uniq_prefrensi_akun` (`akun_id`),
  ADD KEY `akun_id` (`akun_id`);

--
-- Indexes for table `rayhanrp_schema_migrations`
--
ALTER TABLE `rayhanrp_schema_migrations`
  ADD PRIMARY KEY (`version`);

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
  MODIFY `akun_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `grup`
--
ALTER TABLE `grup`
  MODIFY `id_grup` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pengingat_terkirim`
--
ALTER TABLE `pengingat_terkirim`
  MODIFY `id_pengingat` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prefrensi_user`
--
ALTER TABLE `prefrensi_user`
  MODIFY `id_preferensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `akun_telegram`
--
ALTER TABLE `akun_telegram`
  ADD CONSTRAINT `fk_akun_telegram_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`akun_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

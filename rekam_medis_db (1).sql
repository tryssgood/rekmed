-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Jun 2025 pada 13.55
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
-- Database: `rekam_medis_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_profile`
--

CREATE TABLE `admin_profile` (
  `id_admin` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nm_admin` varchar(100) NOT NULL,
  `email_admin` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_profile`
--

INSERT INTO `admin_profile` (`id_admin`, `id_user`, `nm_admin`, `email_admin`, `created_at`) VALUES
(1, 7, 'pirr', '555@gmail.com', '2025-06-17 03:05:43'),
(2, 11, 'pirr', '555@gmail.com', '2025-06-17 08:11:45'),
(3, 16, 'ahmad', 'bbb@gmail.com', '2025-06-17 13:56:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokter`
--

CREATE TABLE `dokter` (
  `id_dokter` int(11) NOT NULL,
  `id_poli` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nm_dokter` varchar(100) NOT NULL,
  `SIP` varchar(50) DEFAULT NULL,
  `tempat_lhr` varchar(100) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dokter`
--

INSERT INTO `dokter` (`id_dokter`, `id_poli`, `id_user`, `nm_dokter`, `SIP`, `tempat_lhr`, `no_hp`, `alamat`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Dr. Ahmad Wijaya, Sp.PD', 'SIP/001/2023', 'Jakarta', '081234567890', 'Jl. Kesehatan No. 1, Jakarta', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(2, 2, 3, 'Dr. Sari Indah, Sp.KG', 'SIP/002/2023', 'Bandung', '081234567891', 'Jl. Gigi Sehat No. 2, Bandung', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(3, 3, 4, 'Dr. Budi Santoso, Sp.A', 'SIP/003/2023', 'Surabaya', '081234567892', 'Jl. Anak Sehat No. 3, Surabaya', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(4, 6, 8, 'BAGUS', 'SIP/003/2023', 'mmmm', '0898', 'jln desa', '2025-06-17 03:12:09', '2025-06-17 03:12:09'),
(5, 4, 13, 'dandi', 'SIP/008/2021', 'SURABAYA', '0898', 'JLN MAJU MUNDUR NO 19', '2025-06-17 10:49:32', '2025-06-17 10:49:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kunjungan`
--

CREATE TABLE `kunjungan` (
  `id_kunjungan` int(11) NOT NULL,
  `tgl_kunjungan` date NOT NULL,
  `id_pasien` int(11) DEFAULT NULL,
  `id_dokter` int(11) DEFAULT NULL,
  `id_poli` int(11) DEFAULT NULL,
  `jam_kunjungan` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `keluhan` text DEFAULT NULL,
  `tindakan_resep` varchar(255) DEFAULT NULL,
  `petugas_medis` varchar(100) DEFAULT NULL,
  `status` enum('menunggu','sedang_diperiksa','selesai','batal') NOT NULL DEFAULT 'menunggu',
  `status_kunjungan` varchar(20) NOT NULL DEFAULT 'menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kunjungan`
--

INSERT INTO `kunjungan` (`id_kunjungan`, `tgl_kunjungan`, `id_pasien`, `id_dokter`, `id_poli`, `jam_kunjungan`, `created_at`, `updated_at`, `keluhan`, `tindakan_resep`, `petugas_medis`, `status`, `status_kunjungan`) VALUES
(1, '2025-01-29', 1, NULL, 1, '09:00:00', '2025-06-17 02:46:03', '2025-06-17 02:46:03', NULL, NULL, NULL, 'menunggu', 'menunggu'),
(2, '2025-01-29', 2, NULL, 2, '10:30:00', '2025-06-17 02:46:03', '2025-06-17 02:46:03', NULL, NULL, NULL, 'menunggu', 'menunggu'),
(3, '2025-01-30', 1, NULL, 3, '14:00:00', '2025-06-17 02:46:03', '2025-06-17 10:41:34', 'm', NULL, NULL, 'sedang_diperiksa', 'menunggu'),
(5, '2025-06-17', 3, NULL, 2, '17:13:00', '2025-06-17 09:14:15', '2025-06-17 10:41:19', 'bb', NULL, NULL, 'selesai', 'menunggu'),
(18, '2025-06-18', 9, 5, 4, '16:39:00', '2025-06-18 09:33:50', '2025-06-18 09:33:50', NULL, NULL, NULL, 'menunggu', 'menunggu');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laboratorium`
--

CREATE TABLE `laboratorium` (
  `id_lab` int(11) NOT NULL,
  `no_rm` varchar(20) DEFAULT NULL,
  `hasil_lab` text DEFAULT NULL,
  `ket` text DEFAULT NULL,
  `tgl_lab` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laboratorium`
--

INSERT INTO `laboratorium` (`id_lab`, `no_rm`, `hasil_lab`, `ket`, `tgl_lab`, `created_at`, `updated_at`) VALUES
(1, 'RM-202501-0001', 'Hemoglobin: 14.2 g/dL (Normal)\nLeukosit: 7.500/μL (Normal)\nTrombosit: 250.000/μL (Normal)', 'Hasil laboratorium dalam batas normal', '2025-01-29', '2025-06-17 02:46:03', '2025-06-17 02:46:03'),
(2, 'RM-202501-0002', 'Gula Darah Puasa: 95 mg/dL (Normal)\nKolesterol Total: 180 mg/dL (Normal)', 'Pemeriksaan rutin tahunan', '2025-01-29', '2025-06-17 02:46:03', '2025-06-17 02:46:03'),
(3, 'RM-202506-0004', 'mmm', 'mmm', '2025-06-18', '2025-06-18 11:12:45', '2025-06-18 11:12:45'),
(4, 'RM-202506-0007', 'bb', 'bbb', '2025-06-18', '2025-06-18 11:48:26', '2025-06-18 11:48:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `login`
--

CREATE TABLE `login` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','dokter','pasien') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `login`
--

INSERT INTO `login` (`id_user`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(2, 'dr.ahmad', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dokter', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(3, 'dr.sari', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dokter', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(4, 'dr.budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dokter', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(5, 'pasien001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pasien', '2025-06-17 02:46:03', '2025-06-17 02:46:03'),
(6, 'pasien002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pasien', '2025-06-17 02:46:03', '2025-06-17 02:46:03'),
(7, 'tedduh', '$2y$10$qS6UsGdOSi7goUyodoR/XelLwTJYfcTW24xqRIywpM4hwOG5e8MZG', 'admin', '2025-06-17 03:05:43', '2025-06-17 03:05:43'),
(8, 'trysthan', '$2y$10$HdKEoDqUpPp8H2ywMmyX4.FG0gf8AVW5hxuJBhPr8hY9k.D5481Ju', 'dokter', '2025-06-17 03:12:09', '2025-06-17 03:12:09'),
(9, 'Rasyid', '$2y$10$vN9hCA8SAJRuYvzkhogQVO5qD2QaSyc5GQxCyE5YcD1imNfakiGY.', 'pasien', '2025-06-17 03:25:32', '2025-06-17 03:25:32'),
(10, 'bali', '$2y$10$N/DZCpV4o8iuWUNHD5jaVufn4uWkZ2L2jnaYwnStaJOiLRtJBb8p6', 'pasien', '2025-06-17 03:27:19', '2025-06-17 03:27:19'),
(11, 'alifalif', '$2y$10$EhZMmmB4cuED08mkHznwXOTxiiEZNsluj/ocYw9CTqmH8HxfL1c4C', 'admin', '2025-06-17 08:11:45', '2025-06-17 08:11:45'),
(12, 'marga', '$2y$10$tkMwk/YnBG.VATmn53Nt.OV/7I0g3zg0RkapuHTl.u3FU2FKxzPAG', 'pasien', '2025-06-17 10:42:52', '2025-06-17 10:42:52'),
(13, 'dandi', '$2y$10$7UUMssOdtfosPiP8Q/4Z8u/uYP408r04mX1WxcKXiLmjKqGhcpxG6', 'dokter', '2025-06-17 10:49:32', '2025-06-17 10:49:32'),
(14, 'geny', '$2y$10$DPag.WeS6pQDJvLdjrcYW.nEmjLLmI0n1jQw.dHOxl2eFRHg2H0Eq', 'pasien', '2025-06-17 12:30:45', '2025-06-17 12:30:45'),
(15, 'zam', '$2y$10$MPHCXjLKEJIynRVWl2VAEugAiF7i3Ytg9wSF4Pop227f9g.SP7yJC', 'pasien', '2025-06-17 13:32:55', '2025-06-17 13:32:55'),
(16, 'admin13', '$2y$10$yvYfHCLpNWOOwyXi46iSa..LqxP2v9y1AUztor1wtX7Ux/Pc8kj8.', 'admin', '2025-06-17 13:56:03', '2025-06-17 13:56:03'),
(17, 'pepep', '$2y$10$w4YyOYYaDXKgeR8KM9w2KuL0bGADhSV4bIa5qsgFGYXfbdp0a/2Oa', 'pasien', '2025-06-17 13:57:44', '2025-06-17 13:57:44'),
(18, 'hyter', '$2y$10$.8hOBMrSN4bjNosIi25PgOLTxdv8AiweSrJkNeRvh.3n4MfX9r69W', 'pasien', '2025-06-18 05:51:23', '2025-06-18 05:51:23'),
(19, 'nadhira', '$2y$10$qshLsRGhgpEgHB5hYs8Awu0DD814PeKVKe6CqN5minOtryaz6vJBm', 'pasien', '2025-06-18 07:22:20', '2025-06-18 07:22:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `medical_records`
--

CREATE TABLE `medical_records` (
  `id_rekam_medis` int(11) NOT NULL,
  `no_rm` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `keluhan` text NOT NULL,
  `diagnosa` text NOT NULL,
  `resep` text DEFAULT NULL,
  `dokter` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `medical_records`
--

INSERT INTO `medical_records` (`id_rekam_medis`, `no_rm`, `tanggal`, `keluhan`, `diagnosa`, `resep`, `dokter`, `created_at`) VALUES
(1, 'RM-202506-0001', '2025-06-07', 'sakit kepala', 'sakit kepala', 'bodrex', 'ahmad sanusi maulana', '2025-06-17 08:38:33'),
(2, 'RM-202501-0001', '2020-02-18', 'sakit pangkal paha', 'ligament robek', 'istirahat', 'agus bakar', '2025-06-17 09:22:27'),
(3, 'RM-202506-0008', '2025-06-18', 'sakit kepala', 'obat bodrex', 'mmm', 'ahmad', '2025-06-18 08:43:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `obat`
--

CREATE TABLE `obat` (
  `id_obat` int(11) NOT NULL,
  `nm_obat` varchar(100) NOT NULL,
  `jml_obat` int(11) DEFAULT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kemasan` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `stok` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `obat`
--

INSERT INTO `obat` (`id_obat`, `nm_obat`, `jml_obat`, `ukuran`, `harga`, `created_at`, `updated_at`, `kemasan`, `keterangan`, `stok`) VALUES
(15, 'nn', 12, NULL, 23.00, '2025-06-18 08:40:30', '2025-06-18 09:45:34', 'nn', '0', 38);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasien`
--

CREATE TABLE `pasien` (
  `id_pasien` int(11) NOT NULL,
  `no_rm` varchar(20) NOT NULL,
  `nm_pasien` varchar(100) NOT NULL,
  `tgl_lhr` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(15) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `nm_kk` varchar(100) DEFAULT NULL,
  `hub_kel` varchar(50) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pasien`
--

INSERT INTO `pasien` (`id_pasien`, `no_rm`, `nm_pasien`, `tgl_lhr`, `alamat`, `telepon`, `no_hp`, `jenis_kelamin`, `nm_kk`, `hub_kel`, `id_user`, `created_at`, `updated_at`) VALUES
(1, 'RM-202501-0001', 'John Doe', '1990-05-15', 'Jl. Mawar No. 10, Jakarta', '021-1234567', '081234567893', 'L', 'John Doe Sr.', 'Anak', 5, '2025-06-17 02:46:03', '2025-06-17 02:46:03'),
(2, 'RM-202501-0002', 'Jane Smith', '1985-08-20', 'Jl. Melati No. 15, Bandung', '022-2345678', '081234567894', 'P', 'Robert Smith', 'Istri', 6, '2025-06-17 02:46:03', '2025-06-17 02:46:03'),
(3, 'RM-202506-0001', 'rasyid', '2025-06-17', 'mmmmm', '08986137356', '0989324', 'P', 'yow', 'test', 9, '2025-06-17 03:25:32', '2025-06-17 03:25:32'),
(4, 'RM-202506-0002', 'rasyid', '2025-06-07', 'vvvv', '08946464312', '08648253735', 'L', 'yow', 'test', 10, '2025-06-17 03:27:19', '2025-06-17 03:27:19'),
(5, 'RM-202506-0003', 'marga', '2025-06-07', 'jalan desa cipadung', '0832178234', '08225373937', 'P', 'test', '77', 12, '2025-06-17 10:42:52', '2025-06-17 10:42:52'),
(6, 'RM-202506-0004', 'geny', '2025-06-07', 'jln desa', '0832178234', '08225373937', 'P', 'wedy', 'test', 14, '2025-06-17 12:30:45', '2025-06-17 12:30:45'),
(7, 'RM-202506-0005', 'zamzam', '2025-06-19', 'jln bebedahan', '08986137356', '08986137356', 'L', 'yow', 'yy', 15, '2025-06-17 13:32:55', '2025-06-17 13:32:55'),
(8, 'RM-202506-0006', 'pepep', '2025-06-14', '123', '123', '123', 'L', '123', 'yoww', 17, '2025-06-17 13:57:44', '2025-06-17 13:57:44'),
(9, 'RM-202506-0007', 'hyter', '2025-06-20', 'jln desa cipadung', '08946464312', '08946464312', 'L', 'pepep', 'yoww', 18, '2025-06-18 05:51:23', '2025-06-18 05:51:23'),
(10, 'RM-202506-0008', 'nadhira', '2023-02-01', 'jln desa', '08946464312', '08648253735', 'P', 'Hyter', 'yoww', 19, '2025-06-18 07:22:20', '2025-06-18 07:22:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `poliklinik`
--

CREATE TABLE `poliklinik` (
  `id_poli` int(11) NOT NULL,
  `nm_poli` varchar(100) NOT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `poliklinik`
--

INSERT INTO `poliklinik` (`id_poli`, `nm_poli`, `lokasi`, `created_at`, `updated_at`) VALUES
(1, 'Poli Umum', 'Lantai 1 Ruang 101', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(2, 'Poli Gigi', 'Lantai 1 Ruang 102', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(3, 'Poli Anak', 'Lantai 2 Ruang 201', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(4, 'Poli Mata', 'Lantai 2 Ruang 202', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(5, 'Poli Jantung', 'Lantai 3 Ruang 301', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(6, 'Poli Kandungan', 'Lantai 3 Ruang 302', '2025-06-17 02:46:02', '2025-06-17 02:46:02'),
(7, 'poli gigi', 'lantai 2 ruang 6', '2025-06-17 08:48:26', '2025-06-17 08:48:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekam_medis`
--

CREATE TABLE `rekam_medis` (
  `id_rekam_medis` int(11) NOT NULL,
  `id_kunjungan` int(11) DEFAULT NULL,
  `id_dokter` int(11) NOT NULL,
  `tgl_kunjungan` date DEFAULT NULL,
  `no_rm` varchar(20) DEFAULT NULL,
  `id_tindakan` int(11) DEFAULT NULL,
  `id_obat` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_pasien` int(11) DEFAULT NULL,
  `tgl_rekam_medis` date NOT NULL DEFAULT curdate(),
  `diagnosa` text DEFAULT NULL,
  `tindakan_resep` text DEFAULT NULL,
  `petugas_medis` varchar(255) DEFAULT NULL,
  `resep` text DEFAULT NULL,
  `keluhan` text DEFAULT NULL,
  `tgl_pemeriksaan` date DEFAULT NULL,
  `ket` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `biaya` decimal(10,2) DEFAULT 0.00,
  `tindakan` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rekam_medis`
--

INSERT INTO `rekam_medis` (`id_rekam_medis`, `id_kunjungan`, `id_dokter`, `tgl_kunjungan`, `no_rm`, `id_tindakan`, `id_obat`, `id_user`, `id_pasien`, `tgl_rekam_medis`, `diagnosa`, `tindakan_resep`, `petugas_medis`, `resep`, `keluhan`, `tgl_pemeriksaan`, `ket`, `created_at`, `updated_at`, `biaya`, `tindakan`, `keterangan`) VALUES
(1, NULL, 0, NULL, 'RM-202501-0001', NULL, NULL, 2, 1, '2025-06-18', 'Demam ringan', NULL, NULL, 'Paracetamol 3x1 tablet', 'Demam dan sakit kepala', '2025-01-29', 'Kontrol 3 hari', '2025-06-17 02:46:03', '2025-06-17 02:46:03', 0.00, NULL, NULL),
(2, NULL, 0, NULL, 'RM-202501-0002', NULL, NULL, 3, 2, '2025-06-18', 'Gingivitis ringan', NULL, NULL, 'Vitamin C 2x1 tablet', 'Gusi berdarah', '2025-01-29', 'Kontrol 1 minggu', '2025-06-17 02:46:03', '2025-06-17 02:46:03', 0.00, NULL, NULL),
(3, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18', NULL, 'OPRASI ', 'BERHASIL', NULL, NULL, NULL, NULL, '2025-06-17 11:21:01', '2025-06-17 11:21:01', 1000000.00, NULL, NULL),
(4, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18', NULL, 'SUNTIK', 'BERHASIL', NULL, NULL, NULL, NULL, '2025-06-17 11:21:39', '2025-06-17 11:21:39', 120.00, NULL, NULL),
(5, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18', NULL, 'OPRASI ', 'AMAN', NULL, NULL, NULL, NULL, '2025-06-17 11:25:52', '2025-06-17 11:25:52', 123.00, NULL, NULL),
(6, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18', NULL, 'MRI', 'WWW', NULL, NULL, NULL, NULL, '2025-06-17 11:27:14', '2025-06-17 11:27:14', 123456.00, NULL, NULL),
(13, 18, 5, NULL, NULL, NULL, NULL, NULL, 9, '2025-06-18', 'sakit gigi', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18 09:33:50', '2025-06-18 09:33:50', 0.00, 'mmmmm', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `resep`
--

CREATE TABLE `resep` (
  `id_resep` int(11) NOT NULL,
  `id_kunjungan` int(11) DEFAULT NULL,
  `id_obat` int(11) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `aturan_pakai` varchar(255) NOT NULL,
  `tgl_resep` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_pasien` int(11) DEFAULT NULL,
  `id_dokter` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tindakan`
--

CREATE TABLE `tindakan` (
  `id_tindakan` int(11) NOT NULL,
  `nm_tindakan` varchar(100) NOT NULL,
  `biaya` decimal(10,2) DEFAULT 0.00,
  `ket` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tindakan`
--

INSERT INTO `tindakan` (`id_tindakan`, `nm_tindakan`, `biaya`, `ket`, `created_at`, `updated_at`) VALUES
(17, 'SUNTIK', 12345.00, 'aman', '2025-06-17 15:28:48', '2025-06-17 15:28:48');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`id_admin`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `dokter`
--
ALTER TABLE `dokter`
  ADD PRIMARY KEY (`id_dokter`),
  ADD KEY `fk_dokter_poli` (`id_poli`),
  ADD KEY `fk_dokter_user` (`id_user`),
  ADD KEY `idx_dokter_nama` (`nm_dokter`);

--
-- Indeks untuk tabel `kunjungan`
--
ALTER TABLE `kunjungan`
  ADD PRIMARY KEY (`id_kunjungan`),
  ADD KEY `fk_kunjungan_pasien` (`id_pasien`),
  ADD KEY `fk_kunjungan_poli` (`id_poli`),
  ADD KEY `idx_kunjungan_tanggal` (`tgl_kunjungan`);

--
-- Indeks untuk tabel `laboratorium`
--
ALTER TABLE `laboratorium`
  ADD PRIMARY KEY (`id_lab`),
  ADD KEY `fk_laboratorium_pasien` (`no_rm`),
  ADD KEY `idx_laboratorium_tanggal` (`tgl_lab`);

--
-- Indeks untuk tabel `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_login_username` (`username`),
  ADD KEY `idx_login_role` (`role`);

--
-- Indeks untuk tabel `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id_rekam_medis`),
  ADD KEY `no_rm` (`no_rm`);

--
-- Indeks untuk tabel `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id_obat`);

--
-- Indeks untuk tabel `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`id_pasien`),
  ADD UNIQUE KEY `no_rm` (`no_rm`),
  ADD KEY `fk_pasien_user` (`id_user`),
  ADD KEY `idx_pasien_no_rm` (`no_rm`),
  ADD KEY `idx_pasien_nama` (`nm_pasien`);

--
-- Indeks untuk tabel `poliklinik`
--
ALTER TABLE `poliklinik`
  ADD PRIMARY KEY (`id_poli`);

--
-- Indeks untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id_rekam_medis`),
  ADD KEY `fk_rekam_medis_tindakan` (`id_tindakan`),
  ADD KEY `fk_rekam_medis_obat` (`id_obat`),
  ADD KEY `fk_rekam_medis_user` (`id_user`),
  ADD KEY `fk_rekam_medis_pasien` (`id_pasien`),
  ADD KEY `idx_rekam_medis_tanggal` (`tgl_pemeriksaan`),
  ADD KEY `idx_rekam_medis_no_rm` (`no_rm`);

--
-- Indeks untuk tabel `resep`
--
ALTER TABLE `resep`
  ADD PRIMARY KEY (`id_resep`),
  ADD KEY `id_obat` (`id_obat`),
  ADD KEY `fk_pasien_resep` (`id_pasien`);

--
-- Indeks untuk tabel `tindakan`
--
ALTER TABLE `tindakan`
  ADD PRIMARY KEY (`id_tindakan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `dokter`
--
ALTER TABLE `dokter`
  MODIFY `id_dokter` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `kunjungan`
--
ALTER TABLE `kunjungan`
  MODIFY `id_kunjungan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `laboratorium`
--
ALTER TABLE `laboratorium`
  MODIFY `id_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `login`
--
ALTER TABLE `login`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id_rekam_medis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `obat`
--
ALTER TABLE `obat`
  MODIFY `id_obat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `pasien`
--
ALTER TABLE `pasien`
  MODIFY `id_pasien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `poliklinik`
--
ALTER TABLE `poliklinik`
  MODIFY `id_poli` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  MODIFY `id_rekam_medis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `resep`
--
ALTER TABLE `resep`
  MODIFY `id_resep` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tindakan`
--
ALTER TABLE `tindakan`
  MODIFY `id_tindakan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD CONSTRAINT `admin_profile_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `login` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `dokter`
--
ALTER TABLE `dokter`
  ADD CONSTRAINT `fk_dokter_poli` FOREIGN KEY (`id_poli`) REFERENCES `poliklinik` (`id_poli`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dokter_user` FOREIGN KEY (`id_user`) REFERENCES `login` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kunjungan`
--
ALTER TABLE `kunjungan`
  ADD CONSTRAINT `fk_kunjungan_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kunjungan_poli` FOREIGN KEY (`id_poli`) REFERENCES `poliklinik` (`id_poli`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laboratorium`
--
ALTER TABLE `laboratorium`
  ADD CONSTRAINT `fk_laboratorium_pasien` FOREIGN KEY (`no_rm`) REFERENCES `pasien` (`no_rm`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`no_rm`) REFERENCES `pasien` (`no_rm`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pasien`
--
ALTER TABLE `pasien`
  ADD CONSTRAINT `fk_pasien_user` FOREIGN KEY (`id_user`) REFERENCES `login` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD CONSTRAINT `fk_rekam_medis_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rekam_medis_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rekam_medis_pasien_no_rm` FOREIGN KEY (`no_rm`) REFERENCES `pasien` (`no_rm`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rekam_medis_tindakan` FOREIGN KEY (`id_tindakan`) REFERENCES `tindakan` (`id_tindakan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rekam_medis_user` FOREIGN KEY (`id_user`) REFERENCES `login` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `resep`
--
ALTER TABLE `resep`
  ADD CONSTRAINT `fk_pasien_resep` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`),
  ADD CONSTRAINT `resep_ibfk_1` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

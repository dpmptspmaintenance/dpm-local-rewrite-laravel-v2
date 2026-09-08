-- --------------------------------------------------------
-- Host:                         10.1.1.14
-- Server version:               8.0.44 - MySQL Community Server - GPL
-- Server OS:                    Linux
-- HeidiSQL Version:             12.17.0.7270
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for data_local
CREATE DATABASE IF NOT EXISTS `data_local` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `data_local`;

-- Dumping structure for table data_local.2023_dp_nib_kantor
CREATE TABLE IF NOT EXISTS `2023_dp_nib_kantor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nib` varchar(20) DEFAULT NULL,
  `day_of_tanggal_terbit_oss` date DEFAULT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `status_penanaman_modal` varchar(50) DEFAULT NULL,
  `uraian_jenis_perusahaan` varchar(255) DEFAULT NULL,
  `uraian_skala_usaha` varchar(50) DEFAULT NULL,
  `alamat_perusahaan` text,
  `kelurahan` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kab_kota` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nomor_telp` varchar(50) DEFAULT NULL,
  `flag` varchar(50) DEFAULT NULL,
  `tahun_pengambilan_data` year DEFAULT NULL,
  `bulan_pengambilan_data` varchar(2) DEFAULT NULL,
  `hari_pengambilan_data` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nib_kantor` (`nib`)
) ENGINE=InnoDB AUTO_INCREMENT=134967 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.2023_dp_proyek
CREATE TABLE IF NOT EXISTS `2023_dp_proyek` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_proyek` varchar(50) DEFAULT NULL,
  `uraian_jenis_proyek` varchar(255) DEFAULT NULL,
  `nib` varchar(20) DEFAULT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `tanggal_terbit_oss` date DEFAULT NULL,
  `uraian_status_penanaman_modal` varchar(100) DEFAULT NULL,
  `uraian_jenis_perusahaan` varchar(255) DEFAULT NULL,
  `uraian_risiko_proyek` varchar(100) DEFAULT NULL,
  `nama_proyek` varchar(255) DEFAULT NULL,
  `uraian_skala_usaha` varchar(50) DEFAULT NULL,
  `alamat_usaha` text,
  `kab_kota_kantor_pusat` varchar(100) DEFAULT NULL,
  `kecamatan_usaha` varchar(100) DEFAULT NULL,
  `kelurahan_usaha` varchar(100) DEFAULT NULL,
  `day_of_tanggal_pengajuan_proyek` date DEFAULT NULL,
  `kbli` varchar(10) DEFAULT NULL,
  `judul_kbli` varchar(255) DEFAULT NULL,
  `sektor_pembina` varchar(100) DEFAULT NULL,
  `nama_user` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nomor_telp` varchar(50) DEFAULT NULL,
  `luas_tanah` decimal(15,2) DEFAULT NULL,
  `satuan_tanah` varchar(20) DEFAULT NULL,
  `jumlah_investasi3` decimal(20,2) DEFAULT NULL,
  `tki` int DEFAULT NULL,
  `tanggal_proyek` date DEFAULT NULL,
  `tahun_pengambilan_data` year DEFAULT NULL,
  `bulan_pengambilan_data` varchar(2) DEFAULT NULL,
  `hari_pengambilan_data` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_id_proyek` (`id_proyek`),
  KEY `idx_proyek_id` (`id_proyek`),
  KEY `idx_proyek_nib` (`nib`)
) ENGINE=InnoDB AUTO_INCREMENT=392459 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.2023_list_izin
CREATE TABLE IF NOT EXISTS `2023_list_izin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_permohonan_izin` varchar(50) DEFAULT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `nib` varchar(20) DEFAULT NULL,
  `day_of_tanggal_terbit_oss` date DEFAULT NULL,
  `uraian_status_penanaman_modal` varchar(50) DEFAULT NULL,
  `propinsi` varchar(100) DEFAULT NULL,
  `kab_kota` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kelurahan` varchar(100) DEFAULT NULL,
  `id_proyek` varchar(50) DEFAULT NULL,
  `kd_resiko` varchar(10) DEFAULT NULL,
  `kbli` varchar(10) DEFAULT NULL,
  `day_of_tanggal_izin` date DEFAULT NULL,
  `uraian_jenis_perizinan` varchar(255) DEFAULT NULL,
  `nama_dokumen` varchar(255) DEFAULT NULL,
  `uraian_kewenangan` varchar(255) DEFAULT NULL,
  `uraian_status_respon` varchar(50) DEFAULT NULL,
  `kewenangan` varchar(100) DEFAULT NULL,
  `kl_sektor` varchar(100) DEFAULT NULL,
  `tahun_pengambilan_data` year DEFAULT NULL,
  `bulan_pengambilan_data` varchar(2) DEFAULT NULL,
  `hari_pengambilan_data` varchar(2) DEFAULT NULL,
  `tanggal_permohonan` date DEFAULT NULL,
  `tanggal_proyek` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_id_permohonan` (`id_permohonan_izin`),
  KEY `idx_izin_proyek_id` (`id_proyek`)
) ENGINE=InnoDB AUTO_INCREMENT=178588 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.access_page_user
CREATE TABLE IF NOT EXISTS `access_page_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `access_page_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `access_page_user_user_id_foreign` (`user_id`),
  KEY `access_page_user_access_page_id_foreign` (`access_page_id`),
  CONSTRAINT `access_page_user_access_page_id_foreign` FOREIGN KEY (`access_page_id`) REFERENCES `access_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `access_page_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.access_pages
CREATE TABLE IF NOT EXISTS `access_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_data_pembina
CREATE TABLE IF NOT EXISTS `dasi_data_pembina` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pembina` varchar(255) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_data_proyek
CREATE TABLE IF NOT EXISTS `dasi_data_proyek` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `kbli` varchar(255) DEFAULT NULL,
  `nama_proyek` varchar(255) DEFAULT NULL,
  `id_data_sektor` varchar(11) DEFAULT NULL,
  `id_data_source` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=2182 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_data_sektor
CREATE TABLE IF NOT EXISTS `dasi_data_sektor` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `nama_sektor` varchar(255) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_data_source
CREATE TABLE IF NOT EXISTS `dasi_master_data_source` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `nama_sumber` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_dokumen_izin
CREATE TABLE IF NOT EXISTS `dasi_master_dokumen_izin` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `dok_izin` varchar(255) DEFAULT NULL,
  `is_aktif` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_izin
CREATE TABLE IF NOT EXISTS `dasi_master_izin` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `jenis_izin` varchar(50) DEFAULT NULL,
  `is_aktif` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_jenis_perusahaan
CREATE TABLE IF NOT EXISTS `dasi_master_jenis_perusahaan` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Uraian` varchar(100) DEFAULT NULL,
  `is_aktif` int DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_kecamatan
CREATE TABLE IF NOT EXISTS `dasi_master_kecamatan` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Kecamatan` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_mata_uang
CREATE TABLE IF NOT EXISTS `dasi_master_mata_uang` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `kode` char(3) NOT NULL DEFAULT '0' COMMENT 'kode mata uang',
  `nama_mata_uang` varchar(10) DEFAULT NULL COMMENT 'nama mata uang',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_resiko
CREATE TABLE IF NOT EXISTS `dasi_master_resiko` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `kd_resiko` varchar(2) DEFAULT NULL,
  `Resiko` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_skala_usaha
CREATE TABLE IF NOT EXISTS `dasi_master_skala_usaha` (
  `id` int NOT NULL AUTO_INCREMENT,
  `skala_usaha` varchar(100) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_status_penanaman_modal
CREATE TABLE IF NOT EXISTS `dasi_master_status_penanaman_modal` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `kode_sandal` varchar(4) DEFAULT NULL,
  `nama_sandal` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_status_respon
CREATE TABLE IF NOT EXISTS `dasi_master_status_respon` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `status_respon` varchar(255) DEFAULT NULL,
  `is_aktif` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_triwulan
CREATE TABLE IF NOT EXISTS `dasi_master_triwulan` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Triwulan` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_master_variabel_investasi
CREATE TABLE IF NOT EXISTS `dasi_master_variabel_investasi` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Uraian` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_nswi_non_iumk_list_nib
CREATE TABLE IF NOT EXISTS `dasi_nswi_non_iumk_list_nib` (
  `No` int NOT NULL AUTO_INCREMENT,
  `Nama_Perusahaan` varchar(101) DEFAULT NULL,
  `Jenis_Perusahaan` varchar(52) DEFAULT NULL,
  `Status_PM` varchar(16) DEFAULT NULL,
  `NIB` varchar(13) DEFAULT NULL,
  `Tanggal_NIB` date DEFAULT NULL,
  `Status_NIB` varchar(5) DEFAULT NULL,
  `alamat_perusahaan` varchar(158) DEFAULT NULL,
  `Lokasi_Perusahaan` varchar(48) DEFAULT NULL,
  `Kelurahan_Perusahaan` varchar(18) DEFAULT NULL,
  `Kecamatan_Perusahaan` varchar(16) DEFAULT NULL,
  `Kota_Perusahaan` varchar(13) DEFAULT NULL,
  `No_Telp` varchar(20) DEFAULT NULL,
  `email_perusahaan` varchar(47) DEFAULT NULL,
  PRIMARY KEY (`No`)
) ENGINE=InnoDB AUTO_INCREMENT=15306 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_nswi_non_iumk_list_nib_proyek
CREATE TABLE IF NOT EXISTS `dasi_nswi_non_iumk_list_nib_proyek` (
  `No` int NOT NULL AUTO_INCREMENT,
  `Nama_Perusahaan` varchar(101) DEFAULT NULL,
  `Jenis_Perusahaan` varchar(52) DEFAULT NULL,
  `Status_PM` varchar(16) DEFAULT NULL,
  `NIB` varchar(13) DEFAULT NULL,
  `Tanggal_NIB` date DEFAULT NULL,
  `Status_NIB` varchar(5) DEFAULT NULL,
  `Alamat_Proyek` varchar(254) DEFAULT NULL,
  `Lokasi_Proyek` varchar(48) DEFAULT NULL,
  `Kelurahan_Proyek` varchar(18) DEFAULT NULL,
  `Kecamatan_Proyek` varchar(16) DEFAULT NULL,
  `Kota_Proyek` varchar(13) DEFAULT NULL,
  `id_proyek` varchar(23) DEFAULT NULL,
  `Tgl_id_proyek` date DEFAULT NULL,
  `KBLI` varchar(7) DEFAULT NULL,
  `uraian_usaha` varchar(255) DEFAULT NULL,
  `is_kawasan_industri` varchar(24) DEFAULT NULL,
  `Investasi` int DEFAULT NULL,
  `Tenaga_Kerja` int DEFAULT NULL,
  PRIMARY KEY (`No`)
) ENGINE=InnoDB AUTO_INCREMENT=126233 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_nswi_realisasi_daftar_perusahaan
CREATE TABLE IF NOT EXISTS `dasi_nswi_realisasi_daftar_perusahaan` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Nama_Perusahaan` varchar(100) DEFAULT NULL,
  `Status` varchar(4) DEFAULT NULL,
  `Negara` varchar(25) DEFAULT NULL,
  `Tahun` varchar(24) DEFAULT NULL,
  `Triwulan` varchar(1) DEFAULT NULL,
  `Alamat_Perusahaan` varchar(100) DEFAULT NULL,
  `Provinsi` varchar(11) DEFAULT NULL,
  `Kabkot` varchar(13) DEFAULT NULL,
  `Lokasi_Proyek` varchar(2800) DEFAULT '',
  `Email` varchar(100) DEFAULT NULL,
  `Referensi_Izin` varchar(40) DEFAULT NULL,
  `Desk_KBLI_2_DIGIT` varchar(125) DEFAULT NULL,
  `Desk_KBLI_2_DIGIT_A` varchar(2) DEFAULT NULL,
  `Desk_KBLI_2_DIGIT_B` varchar(120) DEFAULT NULL,
  `Nama_Sektor` varchar(100) DEFAULT NULL,
  `Nilai_Investasi_Rupiah` int DEFAULT NULL,
  `Nilai_Investasi_Dollar` int DEFAULT NULL,
  `TKI` int DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=24077 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_oss_dp_nib_kantor
CREATE TABLE IF NOT EXISTS `dasi_oss_dp_nib_kantor` (
  `No` int NOT NULL AUTO_INCREMENT,
  `Nib` varchar(20) DEFAULT NULL,
  `Day_of_Tanggal_Terbit_Oss` date DEFAULT NULL,
  `Nama_Perusahaan` varchar(250) DEFAULT NULL,
  `Status_Penanaman_Modal` varchar(20) DEFAULT NULL,
  `Uraian_Jenis_Perusahaan` varchar(60) DEFAULT NULL,
  `Alamat_Perusahaan` varchar(250) DEFAULT NULL,
  `Kab_Kota` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nomor_telp` varchar(30) DEFAULT NULL,
  `unknown_flag` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`No`)
) ENGINE=InnoDB AUTO_INCREMENT=51337 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_oss_dp_proyek
CREATE TABLE IF NOT EXISTS `dasi_oss_dp_proyek` (
  `No` int NOT NULL AUTO_INCREMENT,
  `Tgl_Id_Proyek` date DEFAULT NULL,
  `Id_Proyek` varchar(21) DEFAULT NULL,
  `Nib` varchar(15) DEFAULT NULL,
  `Npwp_Perusahaan` varchar(15) DEFAULT NULL,
  `Nama_Perusahaan` varchar(150) DEFAULT NULL,
  `Uraian_Status_Penanaman_Modal` varchar(15) DEFAULT NULL,
  `Uraian_Jenis_Perusahaan` varchar(60) DEFAULT NULL,
  `Uraian_Risiko_Proyek` varchar(15) DEFAULT NULL,
  `Uraian_Skala_Usaha` varchar(15) DEFAULT NULL,
  `Alamat_Usaha` varchar(250) DEFAULT NULL,
  `kecamatan_usaha` varchar(16) DEFAULT NULL,
  `kelurahan_usaha` varchar(18) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `Kbli` varchar(5) DEFAULT NULL,
  `Judul_Kbli` varchar(200) DEFAULT NULL,
  `KL_Sektor_Pembina` varchar(60) DEFAULT NULL,
  `Nama_User` varchar(50) DEFAULT NULL,
  `Nomor_Identitas_User` varchar(50) DEFAULT NULL,
  `Email` varchar(60) DEFAULT NULL,
  `Telp` varchar(50) DEFAULT NULL,
  `Jumlah_Investasi_a` int DEFAULT NULL,
  `Jumlah_Investasi_b` int DEFAULT NULL,
  `Luas_Tanah` int DEFAULT NULL,
  `Satuan_Tanah` varchar(255) DEFAULT 'm2',
  `Mesin_Peralatan` int DEFAULT NULL,
  `Mesin_Peralatan_Impor` int DEFAULT NULL,
  `Pembelian_Pematangan_Tanah` int DEFAULT NULL,
  `Bangunan_Gedung` int DEFAULT NULL,
  `Modal_Kerja` int DEFAULT NULL,
  `Lain_Lain` int DEFAULT NULL,
  `Jumlah_Investasi` int DEFAULT NULL,
  `TKI` int DEFAULT NULL,
  PRIMARY KEY (`No`)
) ENGINE=InnoDB AUTO_INCREMENT=98512 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.dasi_oss_list_izin
CREATE TABLE IF NOT EXISTS `dasi_oss_list_izin` (
  `No` int NOT NULL AUTO_INCREMENT,
  `Tgl_Id_Permohonan_Izin` date DEFAULT NULL,
  `Id_Permohonan_Izin` varchar(23) DEFAULT NULL,
  `Nama_Perusahaan` varchar(150) DEFAULT NULL,
  `Nib` varchar(13) DEFAULT NULL,
  `Day_of_Tanggal_Terbit_Oss` date DEFAULT NULL,
  `Uraian_Status_Penanaman_Modal` varchar(15) DEFAULT NULL,
  `Propinsi` varchar(11) DEFAULT NULL,
  `Kab_Kota` varchar(13) DEFAULT NULL,
  `id_Proyek` varchar(23) DEFAULT NULL,
  `Kd_Resiko` varchar(2) DEFAULT NULL,
  `Kbli` varchar(5) DEFAULT NULL,
  `Day_of_Tgl_Izin` date DEFAULT NULL,
  `Uraian_Jenis_Perizinan` varchar(20) DEFAULT NULL,
  `Nama_Dokumen` varchar(150) DEFAULT NULL,
  `Uraian_Kewenangan` varchar(15) DEFAULT NULL,
  `Uraian_Status_Respon` varchar(50) DEFAULT NULL,
  `Kewenangan` varchar(42) DEFAULT NULL,
  PRIMARY KEY (`No`)
) ENGINE=InnoDB AUTO_INCREMENT=38981 DEFAULT CHARSET=utf8mb3;

-- Data exporting was unselected.

-- Dumping structure for table data_local.data_fasyankes
CREATE TABLE IF NOT EXISTS `data_fasyankes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jenis_fasyankes` varchar(100) DEFAULT NULL,
  `kode_fasyankes` varchar(50) DEFAULT NULL,
  `nama_fasyankes` varchar(255) DEFAULT NULL,
  `alamat_jalan` text,
  `kelurahan` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kab_kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `no_hp` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=665 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.data_master_kbli
CREATE TABLE IF NOT EXISTS `data_master_kbli` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kbli` varchar(50) NOT NULL DEFAULT '0',
  `judul_kbli` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1095 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.data_pengaduan
CREATE TABLE IF NOT EXISTS `data_pengaduan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipe` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesan_masuk` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pesan_keluar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tanggal` datetime DEFAULT NULL,
  `nama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.datakita_upload_file
CREATE TABLE IF NOT EXISTS `datakita_upload_file` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_file` varchar(255) NOT NULL,
  `lokasi_file` text NOT NULL,
  `klasifikasi` varchar(100) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_klasifikasi` (`klasifikasi`),
  KEY `idx_is_aktif` (`is_aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=1708 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.datakita_upload_files
CREATE TABLE IF NOT EXISTS `datakita_upload_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_file` varchar(255) NOT NULL,
  `klasifikasi` varchar(100) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_klasifikasi` (`klasifikasi`),
  KEY `idx_is_aktif` (`is_aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=527 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.ipr_pendaftaran
CREATE TABLE IF NOT EXISTS `ipr_pendaftaran` (
  `id` varchar(20) NOT NULL,
  `no_agenda` varchar(200) NOT NULL,
  `no_agenda2` varchar(200) NOT NULL DEFAULT '',
  `atas_nama` varchar(100) DEFAULT NULL,
  `status_pemohon` varchar(100) DEFAULT NULL,
  `nama_usaha` varchar(100) DEFAULT NULL,
  `jabatan_pemohon` varchar(100) DEFAULT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `alamat_perusahaan` varchar(100) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `status_jalan` varchar(100) DEFAULT NULL,
  `tata_letak_reklame` varchar(100) DEFAULT NULL,
  `ukuran_reklame` varchar(500) DEFAULT NULL,
  `panjang_reklame` float DEFAULT NULL,
  `lebar_reklame` float DEFAULT NULL,
  `sisi` enum('1','2','3') DEFAULT NULL,
  `kawasan_reklame` varchar(100) DEFAULT NULL,
  `kode_bayar_pajak_daerah` varchar(100) DEFAULT NULL,
  `rupiah_ketentuan_pajak_daerah` int DEFAULT NULL,
  `nib` varchar(13) DEFAULT NULL,
  `lokasi` text,
  `tgl_daftar` datetime DEFAULT NULL,
  `id_pem` varchar(20) NOT NULL,
  `id_milik` varchar(20) NOT NULL,
  `id_permohonan` varchar(20) NOT NULL,
  `status_pendaftaran` varchar(50) NOT NULL,
  `hp` char(20) DEFAULT NULL,
  `no_sk_lama` varchar(100) DEFAULT NULL,
  `status_tanah` varchar(50) DEFAULT NULL,
  `jenis_tanah` varchar(50) DEFAULT NULL,
  `sk_pertama_kali` varchar(150) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updater` varchar(500) DEFAULT NULL,
  `proses_tahapan` varchar(500) DEFAULT NULL,
  `spm` varchar(12) DEFAULT NULL,
  `no_sk` varchar(90) DEFAULT NULL,
  `tgl_sk` datetime DEFAULT NULL,
  `kec` varchar(50) DEFAULT NULL,
  `kel` varchar(50) DEFAULT NULL,
  `loc` varchar(250) DEFAULT NULL,
  `exp` date DEFAULT NULL,
  `lama` int DEFAULT NULL,
  PRIMARY KEY (`id`,`no_agenda`,`id_pem`,`id_milik`,`id_permohonan`),
  KEY `no_agenda` (`no_agenda`),
  KEY `tgl_daftar` (`tgl_daftar`),
  KEY `id_pem` (`id_pem`),
  KEY `id_milik` (`id_milik`),
  KEY `id_permohonan` (`id_permohonan`),
  FULLTEXT KEY `lokasi` (`lokasi`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table data_local.ipr_permohonan
CREATE TABLE IF NOT EXISTS `ipr_permohonan` (
  `id_permohonan` varchar(100) NOT NULL,
  `jabatan_pemohon` varchar(100) DEFAULT NULL,
  `nama_jasa` varchar(100) DEFAULT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `alamat_perusahaan` varchar(100) DEFAULT NULL,
  `status_jalan` varchar(100) DEFAULT NULL,
  `no_surat_kuasa` varchar(100) DEFAULT NULL,
  `tgl_surat_kuasa` varchar(50) DEFAULT NULL,
  `teks` text,
  `ukuran` text,
  `ketinggian` varchar(50) DEFAULT '0',
  `jenis_reklame` varchar(100) DEFAULT NULL,
  `daerah_reklame` varchar(255) DEFAULT '0',
  `bahan_konstruksi_visual` varchar(100) DEFAULT NULL,
  `tata_letak_pemkot_no_titik` varchar(100) DEFAULT NULL,
  `tata_letak_instansi_lain` varchar(100) DEFAULT NULL,
  `tata_letak_sendiri` varchar(100) DEFAULT NULL,
  `tata_letak_reklame` varchar(100) DEFAULT NULL,
  `jumlah_reklame` varchar(50) DEFAULT '0',
  `lokasi` text,
  `kelurahan` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `jangka_waktu` varchar(50) DEFAULT '0',
  `lampiran` text,
  `total` varchar(50) NOT NULL DEFAULT '0',
  `status_ujb` tinyint(1) NOT NULL DEFAULT '1',
  `retribusi` float DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updater` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_permohonan`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table data_local.it_request_files
CREATE TABLE IF NOT EXISTS `it_request_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  CONSTRAINT `fk_files_request` FOREIGN KEY (`request_id`) REFERENCES `it_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.it_requests
CREATE TABLE IF NOT EXISTS `it_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `request_type` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'belum_dikerjakan',
  `priority` int DEFAULT '0',
  `rejection_reason` text,
  `pending_reason` text,
  `approved_by` int DEFAULT NULL,
  `accepted_by` int DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `approved_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `finish_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.itr_pendaftaran
CREATE TABLE IF NOT EXISTS `itr_pendaftaran` (
  `id` varchar(20) NOT NULL,
  `no_agenda` varchar(200) NOT NULL,
  `no_agenda2` varchar(200) NOT NULL DEFAULT '',
  `nomor_rekomendasi_titik_reklame` varchar(100) DEFAULT NULL,
  `tanggal_rekomendasi_titik_reklame` date DEFAULT NULL,
  `tanggal_mulai_izin` date DEFAULT NULL,
  `tanggal_berakhir_izin` date DEFAULT NULL,
  `atas_nama` varchar(100) DEFAULT NULL,
  `nama_usaha` varchar(100) DEFAULT NULL,
  `nib` varchar(13) DEFAULT NULL,
  `jabatan_pemohon` varchar(100) DEFAULT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `alamat_perusahaan` varchar(100) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `panjang_reklame` float DEFAULT NULL,
  `lebar_reklame` float DEFAULT NULL,
  `sisi` enum('1','2','3') DEFAULT NULL,
  `lokasi` text,
  `status_jalan` varchar(100) DEFAULT NULL,
  `tata_letak_reklame` varchar(100) DEFAULT NULL,
  `ukuran_reklame` varchar(100) DEFAULT NULL,
  `kawasan_reklame` varchar(100) DEFAULT NULL,
  `kode_bayar_pajak_daerah` varchar(100) DEFAULT NULL,
  `rupiah_ketentuan_pajak_daerah` int DEFAULT NULL,
  `tgl_daftar` datetime DEFAULT NULL,
  `id_pem` varchar(20) NOT NULL,
  `id_milik` varchar(20) NOT NULL,
  `id_permohonan` varchar(20) NOT NULL,
  `status_pendaftaran` varchar(50) NOT NULL,
  `hp` char(20) DEFAULT NULL,
  `no_sk_lama` varchar(100) DEFAULT NULL,
  `status_tanah` varchar(100) DEFAULT NULL,
  `sk_pertama_kali` varchar(150) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updater` varchar(500) DEFAULT NULL,
  `proses_tahapan` varchar(90) DEFAULT NULL,
  `spm` varchar(12) DEFAULT NULL,
  `no_sk` varchar(90) DEFAULT NULL,
  `tgl_sk` datetime DEFAULT NULL,
  `kec` varchar(50) DEFAULT NULL,
  `kel` varchar(50) DEFAULT NULL,
  `loc` varchar(250) DEFAULT NULL,
  `exp` date DEFAULT NULL,
  `lama` int DEFAULT NULL,
  PRIMARY KEY (`id`,`no_agenda`,`id_pem`,`id_milik`,`id_permohonan`),
  KEY `no_agenda` (`no_agenda`),
  KEY `tgl_daftar` (`tgl_daftar`),
  KEY `id_pem` (`id_pem`),
  KEY `id_milik` (`id_milik`),
  KEY `id_permohonan` (`id_permohonan`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table data_local.itr_permohonan
CREATE TABLE IF NOT EXISTS `itr_permohonan` (
  `id_permohonan` varchar(100) NOT NULL,
  `jabatan_pemohon` varchar(100) DEFAULT NULL,
  `nama_jasa` varchar(100) DEFAULT NULL,
  `no_surat_kuasa` varchar(100) DEFAULT NULL,
  `tgl_surat_kuasa` varchar(11) DEFAULT '0000-00-00',
  `no_gs` varchar(50) DEFAULT NULL,
  `tgl_gs` varchar(50) DEFAULT '0000-00-00',
  `no_imb_pertandaan` varchar(50) DEFAULT NULL,
  `tgl_imb_pertandaan` varchar(50) DEFAULT '0000-00-00',
  `teks` text,
  `ukuran` varchar(100) DEFAULT NULL,
  `ketinggian` varchar(50) DEFAULT '0',
  `jenis_reklame` varchar(100) DEFAULT NULL,
  `daerah_reklame` varchar(50) DEFAULT '0',
  `bahan_konstruksi_visual` varchar(100) DEFAULT NULL,
  `tata_letak_pemkot_no_titik` varchar(100) DEFAULT NULL,
  `tata_letak_instansi_lain` varchar(100) DEFAULT NULL,
  `tata_letak_sendiri` varchar(100) DEFAULT NULL,
  `jumlah_reklame` varchar(50) DEFAULT '0',
  `lokasi` text,
  `lokasi_reklame` varchar(255) DEFAULT NULL,
  `satuan` varchar(100) DEFAULT NULL,
  `kelurahan` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `jangka_waktu` varchar(50) DEFAULT '0',
  `lampiran` text,
  `total` double DEFAULT NULL,
  `total_luas` double DEFAULT NULL,
  `harga_sewa_per_tahun` double DEFAULT NULL,
  `uang_jaminan_bongkar` double unsigned DEFAULT NULL,
  `jumlah` double DEFAULT NULL,
  `harga_pembayaran` double DEFAULT NULL,
  `nilai_harga_pembayaran` varchar(200) DEFAULT NULL,
  `no_rekening` int unsigned DEFAULT NULL,
  `tgl_pembayaran` date DEFAULT NULL,
  `status_ujb` tinyint(1) NOT NULL DEFAULT '1',
  `username` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updater` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_permohonan`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table data_local.job_batches
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.laporan_liputan
CREATE TABLE IF NOT EXISTS `laporan_liputan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` varchar(100) NOT NULL,
  `tempat` varchar(255) NOT NULL,
  `penyelenggara` varchar(255) NOT NULL,
  `dasar` text,
  `petugas` text,
  `pelaksanaan` text,
  `jml_nib` varchar(50) DEFAULT NULL,
  `jml_sppirt` varchar(50) DEFAULT NULL,
  `jml_info` varchar(50) DEFAULT NULL,
  `tgl_laporan` date DEFAULT NULL,
  `pembuat` varchar(50) DEFAULT 'munir',
  `foto1` varchar(255) DEFAULT NULL,
  `foto2` varchar(255) DEFAULT NULL,
  `foto3` varchar(255) DEFAULT NULL,
  `foto4` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.laporan_realisasi_investasi
CREATE TABLE IF NOT EXISTS `laporan_realisasi_investasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_laporan` varchar(100) DEFAULT NULL,
  `no_proyek` varchar(100) DEFAULT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `negara` varchar(100) DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `triwulan` varchar(20) DEFAULT NULL,
  `periode_tahap` varchar(50) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `lokasi_usaha` text,
  `kab_kot` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_izin` varchar(100) DEFAULT NULL,
  `deskripsi_kbli` text,
  `deskripsi_kbli_5digit` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `nama_sektor` varchar(150) DEFAULT NULL,
  `nilai_investasi` decimal(20,2) DEFAULT NULL,
  `tki` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76677 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.mppdig_faskes
CREATE TABLE IF NOT EXISTS `mppdig_faskes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `alamat` text,
  `kelurahan` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2505 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.mppdig_permohonan
CREATE TABLE IF NOT EXISTS `mppdig_permohonan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_reg` varchar(100) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `profesi` varchar(100) DEFAULT NULL,
  `tempat_praktik` text,
  `status_permohonan` varchar(100) DEFAULT NULL,
  `tgl_permohonan` datetime DEFAULT NULL,
  `nomor_sip` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_search` (`nama`,`nik`),
  KEY `idx_filter_profesi` (`profesi`),
  KEY `idx_filter_status` (`status_permohonan`),
  KEY `idx_filter_tgl` (`tgl_permohonan`)
) ENGINE=InnoDB AUTO_INCREMENT=6698 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.mppdig_permohonan_sip_semua
CREATE TABLE IF NOT EXISTS `mppdig_permohonan_sip_semua` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor_registrasi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `profesi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tempat_praktik` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nama_lengkap` varchar(150) DEFAULT NULL,
  `alamat` text,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `waktu_input` datetime DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `no_sip` varchar(50) DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25560 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.pengajuan_cuti
CREATE TABLE IF NOT EXISTS `pengajuan_cuti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_surat` varchar(100) DEFAULT NULL,
  `nip` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `opd` varchar(200) DEFAULT 'DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU',
  `unit_kerja` varchar(200) DEFAULT 'DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU',
  `lokasi_kerja` varchar(200) DEFAULT 'DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU',
  `status` varchar(100) NOT NULL,
  `keperluan` text NOT NULL,
  `jenis` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.personal_access_tokens
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.rapat_kita_notulen
CREATE TABLE IF NOT EXISTS `rapat_kita_notulen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_kegiatan` int NOT NULL,
  `isi_notulen` text,
  `hasil_keputusan` text,
  `dokumentasi` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `nama_kegiatan` varchar(50) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `user_id_pembuat_notulen` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notulen_kegiatan` (`id_kegiatan`),
  CONSTRAINT `fk_notulen_kegiatan` FOREIGN KEY (`id_kegiatan`) REFERENCES `rapat_kita_schedule_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.rapat_kita_schedule_list
CREATE TABLE IF NOT EXISTS `rapat_kita_schedule_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `pelaksana` varchar(100) DEFAULT NULL,
  `dihadiri` varchar(100) DEFAULT NULL,
  `dispo` varchar(255) DEFAULT NULL,
  `id_rapat_sebelumnya` int DEFAULT NULL,
  `bidang_pembuat_jadwal` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_start` (`start_datetime`),
  KEY `idx_aktif` (`is_aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.rekap_dp_proyek
CREATE TABLE IF NOT EXISTS `rekap_dp_proyek` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun_pengambilan_data` year NOT NULL,
  `jml_investasi` decimal(20,2) DEFAULT '0.00',
  `bulan_pengambilan_data` int DEFAULT NULL,
  `nama_proyek` varchar(255) DEFAULT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `sektor` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now()),
  `jml_tki` int NOT NULL DEFAULT '0',
  `kbli` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tahun` (`tahun_pengambilan_data`),
  KEY `idx_bulan` (`bulan_pengambilan_data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.satu_data_realisasi_investasi
CREATE TABLE IF NOT EXISTS `satu_data_realisasi_investasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  `nib` varchar(50) DEFAULT NULL,
  `no_proyek` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `negara` varchar(100) DEFAULT NULL,
  `tahun` year DEFAULT NULL,
  `triwulan` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tahun` (`tahun`),
  KEY `idx_status` (`status`),
  KEY `idx_negara` (`negara`),
  KEY `idx_search` (`nama_perusahaan`,`nib`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.scrape_status
CREATE TABLE IF NOT EXISTS `scrape_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipe` varchar(100) DEFAULT NULL,
  `tanggal_awal` date DEFAULT NULL,
  `tanggal_akhir` date DEFAULT NULL,
  `updated_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.sekre_bangkit_user
CREATE TABLE IF NOT EXISTS `sekre_bangkit_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pangkat` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `is_bpp` tinyint(1) DEFAULT '0',
  `bidang` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `nip` varchar(100) DEFAULT NULL,
  `is_aktif` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.sekre_data_user_gmail
CREATE TABLE IF NOT EXISTS `sekre_data_user_gmail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username_id` int NOT NULL DEFAULT '0',
  `google_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.sekre_log_aktivitas
CREATE TABLE IF NOT EXISTS `sekre_log_aktivitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `halaman` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `waktu_akses` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.siimut_ijin_reklame
CREATE TABLE IF NOT EXISTS `siimut_ijin_reklame` (
  `id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_agenda` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_agenda2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_rekomendasi_titik_reklame` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_rekomendasi_titik_reklame` date DEFAULT NULL,
  `tanggal_mulai_izin` date DEFAULT NULL,
  `tanggal_berakhir_izin` date DEFAULT NULL,
  `atas_nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_usaha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nib` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan_pemohon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_perusahaan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_perusahaan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `panjang_reklame` decimal(10,2) DEFAULT NULL,
  `lebar_reklame` decimal(10,2) DEFAULT NULL,
  `sisi` int DEFAULT NULL,
  `lokasi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_jalan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tata_letak_reklame` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ukuran_reklame` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kawasan_reklame` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_bayar_pajak_daerah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rupiah_ketentuan_pajak_daerah` decimal(15,2) DEFAULT NULL,
  `tgl_daftar` datetime DEFAULT NULL,
  `id_pem` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_milik` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_permohonan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_pendaftaran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_sk_lama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_tanah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sk_pertama_kali` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `updater` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proses_tahapan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spm` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_sk` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_sk` datetime DEFAULT NULL,
  `kec` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exp` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lama` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.sikenut
CREATE TABLE IF NOT EXISTS `sikenut` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` varchar(255) NOT NULL,
  `surat_tugas` varchar(100) NOT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `tanggal_acara` date DEFAULT NULL,
  `tipe_anggaran` varchar(50) DEFAULT NULL,
  `anggaran_bulan` varchar(20) DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `disposisi` int DEFAULT NULL,
  `bidang` varchar(100) DEFAULT NULL,
  `anggaran_bidang` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kegiatan_surat` (`nama_kegiatan`,`surat_tugas`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_data_tambahan
CREATE TABLE IF NOT EXISTS `simbg_data_tambahan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_registrasi` varchar(100) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `hak_atas_tanah` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_add_noreg` (`no_registrasi`),
  CONSTRAINT `fk_data_tambahan` FOREIGN KEY (`no_registrasi`) REFERENCES `simbg_penyerahan_dokumen_pbg` (`no_registrasi`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1716 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_master_fungsi_bangunan_gedung
CREATE TABLE IF NOT EXISTS `simbg_master_fungsi_bangunan_gedung` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fungsi_bg` varchar(100) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_master_jenis_konsultasi
CREATE TABLE IF NOT EXISTS `simbg_master_jenis_konsultasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_konsultasi` varchar(100) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_master_jenis_regist
CREATE TABLE IF NOT EXISTS `simbg_master_jenis_regist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_registrasi` varchar(100) NOT NULL,
  `uraian_jenis_registrasi` varchar(255) NOT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_monitoring
CREATE TABLE IF NOT EXISTS `simbg_monitoring` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_registrasi` varchar(100) NOT NULL,
  `no_dokumen` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `jenis_permohonan` varchar(255) DEFAULT NULL,
  `jenis_registrasi` varchar(50) DEFAULT NULL,
  `nama_pemilik` varchar(255) DEFAULT NULL,
  `alamat` text,
  `kota_kab_bangunan` varchar(100) DEFAULT NULL,
  `kecamatan_bangunan` varchar(100) DEFAULT NULL,
  `kelurahan_bangunan` varchar(100) DEFAULT NULL,
  `alamat_pemilik` text,
  `no_kontak` varchar(50) DEFAULT NULL,
  `e_mail` varchar(150) DEFAULT NULL,
  `no_identitas` varchar(50) DEFAULT NULL,
  `nama_bangunan` varchar(255) DEFAULT NULL,
  `fungsi_bangunan` varchar(100) DEFAULT NULL,
  `sub_fungsi_bangunan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tipe_bangunan` varchar(100) DEFAULT NULL,
  `luas_bangunan` decimal(15,2) DEFAULT '0.00',
  `tinggi_bangunan` decimal(15,2) DEFAULT '0.00',
  `luas_basement` decimal(15,2) DEFAULT '0.00',
  `jumlah_lantai` int DEFAULT '0',
  `lapis_basement` int DEFAULT '0',
  `jumlah_unit` int DEFAULT '0',
  `okupansi` varchar(100) DEFAULT NULL,
  `permanensi` varchar(50) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `status_slf` varchar(100) DEFAULT NULL,
  `fungsi` varchar(100) DEFAULT NULL,
  `tipe_konsultasi_1` varchar(100) DEFAULT NULL,
  `tipe_konsultasi_2` varchar(100) DEFAULT NULL,
  `tgl_registrasi` date DEFAULT NULL,
  `tgl_sk` date DEFAULT NULL,
  `is_sync` tinyint(1) DEFAULT '0',
  `tahun_pengambilan_data` year DEFAULT NULL,
  `bulan_pengambilan_data` varchar(2) DEFAULT NULL,
  `hari_pengambilan_data` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_no_registrasi` (`no_registrasi`),
  KEY `idx_nama_pemilik` (`nama_pemilik`),
  KEY `idx_no_dokumen` (`no_dokumen`)
) ENGINE=InnoDB AUTO_INCREMENT=20731 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_monitoring_scrape
CREATE TABLE IF NOT EXISTS `simbg_monitoring_scrape` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_registrasi` varchar(100) NOT NULL,
  `no_dokumen` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `jenis_permohonan` varchar(255) DEFAULT NULL,
  `jenis_registrasi` varchar(50) DEFAULT NULL,
  `nama_pemilik` varchar(255) DEFAULT NULL,
  `alamat` text,
  `kota_kab_bangunan` varchar(100) DEFAULT NULL,
  `kecamatan_bangunan` varchar(100) DEFAULT NULL,
  `kelurahan_bangunan` varchar(100) DEFAULT NULL,
  `alamat_pemilik` text,
  `no_kontak` varchar(50) DEFAULT NULL,
  `e_mail` varchar(150) DEFAULT NULL,
  `no_identitas` varchar(50) DEFAULT NULL,
  `nama_bangunan` varchar(255) DEFAULT NULL,
  `fungsi_bangunan` varchar(100) DEFAULT NULL,
  `sub_fungsi_bangunan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tipe_bangunan` varchar(100) DEFAULT NULL,
  `luas_bangunan` decimal(15,2) DEFAULT '0.00',
  `tinggi_bangunan` decimal(15,2) DEFAULT '0.00',
  `luas_basement` decimal(15,2) DEFAULT '0.00',
  `jumlah_lantai` int DEFAULT '0',
  `lapis_basement` int DEFAULT '0',
  `jumlah_unit` int DEFAULT '0',
  `okupansi` varchar(100) DEFAULT NULL,
  `permanensi` varchar(50) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `status_slf` varchar(100) DEFAULT NULL,
  `fungsi` varchar(100) DEFAULT NULL,
  `tipe_konsultasi_1` varchar(100) DEFAULT NULL,
  `tipe_konsultasi_2` varchar(100) DEFAULT NULL,
  `tgl_registrasi` date DEFAULT NULL,
  `tgl_sk` date DEFAULT NULL,
  `is_sync` tinyint(1) DEFAULT '0',
  `tahun_pengambilan_data` year DEFAULT NULL,
  `bulan_pengambilan_data` varchar(2) DEFAULT NULL,
  `hari_pengambilan_data` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_no_registrasi` (`no_registrasi`),
  KEY `idx_nama_pemilik` (`nama_pemilik`),
  KEY `idx_no_dokumen` (`no_dokumen`)
) ENGINE=InnoDB AUTO_INCREMENT=15958 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.simbg_penyerahan_dokumen_pbg
CREATE TABLE IF NOT EXISTS `simbg_penyerahan_dokumen_pbg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_registrasi` varchar(100) NOT NULL,
  `jenis_permohonan` varchar(255) DEFAULT NULL,
  `tgl_registrasi` date DEFAULT NULL,
  `no_dokumen_pbg` varchar(100) DEFAULT NULL,
  `tgl_dokumen_pbg` date DEFAULT NULL,
  `nama_pemilik` varchar(255) DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `tgl_ambil_data` date DEFAULT NULL,
  `jam_ambil_data` time DEFAULT NULL,
  `tgl_pengambilan_sk` date DEFAULT NULL,
  `nama_pengambil_sk` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pbg_noreg` (`no_registrasi`)
) ENGINE=InnoDB AUTO_INCREMENT=4460 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.surat_jawaban
CREATE TABLE IF NOT EXISTS `surat_jawaban` (
  `id` int NOT NULL AUTO_INCREMENT,
  `surat_masuk_id` int NOT NULL,
  `nomor_naskah` varchar(100) DEFAULT NULL,
  `tanggal_naskah` date DEFAULT NULL,
  `perihal_jawaban` varchar(255) NOT NULL,
  `yth` text NOT NULL,
  `dasar` text NOT NULL,
  `dasar_jawaban` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_surat_masuk` (`surat_masuk_id`),
  CONSTRAINT `fk_surat_masuk` FOREIGN KEY (`surat_masuk_id`) REFERENCES `surat_masuk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.surat_masuk
CREATE TABLE IF NOT EXISTS `surat_masuk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `pengirim` varchar(150) NOT NULL,
  `perihal` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.target_investasi
CREATE TABLE IF NOT EXISTS `target_investasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'PMDN' COMMENT 'PMA atau PMDN',
  `kecamatan` varchar(150) DEFAULT NULL,
  `target_nilai` decimal(20,2) DEFAULT '0.00',
  `keterangan` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.user_activities
CREATE TABLE IF NOT EXISTS `user_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `user_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=666 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table data_local.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_jabatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pangkat` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` tinyint unsigned NOT NULL DEFAULT '6',
  `bidang` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shared_pages` json DEFAULT NULL,
  `is_bpp` tinyint DEFAULT '0',
  `is_admin_persediaan` tinyint DEFAULT '0',
  `is_admin_kepegawaian` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.


-- Dumping database structure for kepegawaian
CREATE DATABASE IF NOT EXISTS `kepegawaian` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `kepegawaian`;

-- Dumping structure for table kepegawaian.cuti
CREATE TABLE IF NOT EXISTS `cuti` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no_surat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai_diajukan` date NOT NULL,
  `tanggal_selesai_diajukan` date NOT NULL,
  `durasi_hari` int DEFAULT '0',
  `opd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_kerja` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lokasi_kerja` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keperluan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `jenis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cuti_no_surat_unique` (`no_surat`)
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table kepegawaian.hari_libur
CREATE TABLE IF NOT EXISTS `hari_libur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hari_libur_tanggal_unique` (`tanggal`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table kepegawaian.pegawai_anak
CREATE TABLE IF NOT EXISTS `pegawai_anak` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_urut` int DEFAULT NULL,
  `nama_anak` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender_anak` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_lahir_anak` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir_anak` date DEFAULT NULL,
  `usia_anak` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tingkat_pendidikan_anak` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tunjangan_anak` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hubungan_keluarga_anak` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pegawai_anak_nip_idx` (`nip`),
  CONSTRAINT `fk_pegawai_anak_nip` FOREIGN KEY (`nip`) REFERENCES `pegawai_profil` (`nip`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=188 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table kepegawaian.pegawai_kompetensi
CREATE TABLE IF NOT EXISTS `pegawai_kompetensi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nip` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_jam` int DEFAULT '0',
  `nama_kompetensi` text COLLATE utf8mb4_unicode_ci,
  `nomor_sertifikat` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penyelenggara` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `tanggal_sertifikat` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nip` (`nip`),
  CONSTRAINT `fk_kompetensi_pegawai` FOREIGN KEY (`nip`) REFERENCES `pegawai_profil` (`nip`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1019 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table kepegawaian.pegawai_masuk
CREATE TABLE IF NOT EXISTS `pegawai_masuk` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pegawai_masuk_nip_unique` (`nip`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table kepegawaian.pegawai_profil
CREATE TABLE IF NOT EXISTS `pegawai_profil` (
  `nip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pangkat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `golongan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_pegawai` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_lahir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `usia_keterangan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_perkawinan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pendidikan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelas_jabatan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capaian_bangkom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bup_but` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tmt_bup_but` date DEFAULT NULL,
  `tmt_golongan` date DEFAULT NULL,
  `kgb_selanjutnya` date DEFAULT NULL,
  `nip_lama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_ktp` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rt_ktp` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rw_ktp` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelurahan_ktp` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecamatan_ktp` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kota_ktp` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provinsi_ktp` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_pos_ktp` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_domisili` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rt_domisili` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rw_domisili` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelurahan_domisili` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecamatan_domisili` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kota_domisili` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provinsi_domisili` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_pos_domisili` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_domisili` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sumber_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.


-- Dumping database structure for persediaan
CREATE DATABASE IF NOT EXISTS `persediaan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `persediaan`;

-- Dumping structure for table persediaan.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `nama_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `aksi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tabel_terkait` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `id_record` int DEFAULT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.master_barang
CREATE TABLE IF NOT EXISTS `master_barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_rekening` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nama_barang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `id_satuan` int NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_satuan` (`id_satuan`) USING BTREE,
  KEY `kode_rekening` (`kode_rekening`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.master_rekening
CREATE TABLE IF NOT EXISTS `master_rekening` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_rekening` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nama_rekening` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `parent_kode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_rekening` (`kode_rekening`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.master_satuan
CREATE TABLE IF NOT EXISTS `master_satuan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_satuan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_satuan` (`nama_satuan`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.penguncian_laporan
CREATE TABLE IF NOT EXISTS `penguncian_laporan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tahun` int NOT NULL,
  `bulan` int NOT NULL,
  `is_locked` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bulan_tahun` (`bulan`,`tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.stok_opname_detail
CREATE TABLE IF NOT EXISTS `stok_opname_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_opname_header` int NOT NULL,
  `id_barang` int NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stok_sistem` int NOT NULL DEFAULT '0',
  `stok_fisik` int NOT NULL DEFAULT '0',
  `selisih` int NOT NULL DEFAULT '0',
  `alasan_selisih` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_opname_header` (`id_opname_header`),
  CONSTRAINT `stok_opname_detail_ibfk_1` FOREIGN KEY (`id_opname_header`) REFERENCES `stok_opname_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.stok_opname_header
CREATE TABLE IF NOT EXISTS `stok_opname_header` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_opname` varchar(50) NOT NULL,
  `tanggal_opname` date NOT NULL,
  `nama_kegiatan` varchar(255) DEFAULT 'Stok Opname Insidental',
  `ttd_kiri` varchar(255) DEFAULT 'Naelu Shulhal Majid, A.Md.Ak',
  `ttd_tengah` varchar(255) DEFAULT 'Nany Marlina, SE',
  `ttd_kanan` varchar(255) DEFAULT '',
  `ttd_sekretaris` varchar(255) DEFAULT 'Anton Siswartono, S.Sos, M.M',
  `status` enum('draft','final') DEFAULT 'draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_opname` (`kode_opname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.transaksi_detail
CREATE TABLE IF NOT EXISTS `transaksi_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_header` bigint unsigned NOT NULL,
  `id_barang` int NOT NULL,
  `qty` int NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_barang` (`id_barang`) USING BTREE,
  KEY `id_header` (`id_header`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=462 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table persediaan.transaksi_header
CREATE TABLE IF NOT EXISTS `transaksi_header` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `no_bukti` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `jenis_mutasi` enum('saldo_awal','masuk','keluar') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `status` enum('draft','menunggu','disetujui','ditolak') DEFAULT 'draft',
  `lampiran` text,
  `tanggal_transaksi` date NOT NULL,
  `pihak_terkait` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `alasan_pengambilan` text,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `ttd_kiri` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ttd_tengah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ttd_kanan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ttd_sekretaris` varchar(255) DEFAULT 'Anton Siswartono, S.Sos, M.M',
  `ttd_bmd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

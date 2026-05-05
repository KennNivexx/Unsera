/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') COLLATE utf8mb4_general_ci DEFAULT 'Laki-laki',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `admin` VALUES (2,'aulia ','auliasyaharbanu@gmail.com','111','Laki-laki'),(3,'1','1@gmail.com','$2y$10$qfOEvjM/G5zJz1BaUNXo8uIaHE8wsPmsTBDjHUuPbrFtMmZ6J90RC','Laki-laki'),(4,'Kevin','11@gmail.com','$2y$10$RkFPL4nKvRo46O6V9P.qH.2HWXkXbFt4Dz2y36JB5X47teFB9yT7W','Laki-laki'),(5,'MasBro','masbro@gmail.com','$2y$10$aRAUTT3VWHFyLpMpImE5LO1nFaGq.OWois/FDqhg3d1K17JiMJt0O','Laki-laki'),(6,'Test Admin','test@unsera.ac.id','$2y$10$fN.L65aFGqTAYKP/1gVxtu9zaBBfJgnRISh2a2XyMSREjrwENZ8hi','Laki-laki');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_surat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_id` int NOT NULL,
  `no_surat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dokumen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dokumen_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dokumen_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jenis_id` (`jenis_id`),
  CONSTRAINT `data_surat_ibfk_1` FOREIGN KEY (`jenis_id`) REFERENCES `jenis_surat` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `data_surat` VALUES (2,1,'1','2026-04-26','1','','','');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dosen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl_tempat` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl_tanggal` date DEFAULT NULL,
  `status_dosen` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_dosen` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan_dosen` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan_struktural` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmk` date DEFAULT NULL,
  `tmtk` date DEFAULT NULL,
  `ket_tidak_kerja` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_tidak_kerja` longblob,
  `dok_tidak_kerja_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_tidak_kerja_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabfung_akademik` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_jabfung` date DEFAULT NULL,
  `dok_jabfung` longblob,
  `dok_jabfung_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_jabfung_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gol_lldikti` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_gol_lldikti` date DEFAULT NULL,
  `dok_gol_lldikti` longblob,
  `dok_gol_lldikti_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_gol_lldikti_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gol_yayasan` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_gol_yayasan` date DEFAULT NULL,
  `dok_gol_yayasan` longblob,
  `dok_gol_yayasan_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_gol_yayasan_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reward` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tgl_reward` date DEFAULT NULL,
  `dok_reward` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `punishment` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_punishment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `homebase_prodi` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit_kerja` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_serdos` int DEFAULT NULL,
  `dok_serdos` longblob,
  `dok_serdos_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_serdos_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `riwayat_pendidikan` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pribadi` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_ktp` longblob,
  `dok_ktp_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_ktp_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_kk` longblob,
  `dok_kk_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_kk_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_keaktifan` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Aktif',
  `keterangan_keaktifan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tgl_mulai_tidak_bekerja` date DEFAULT NULL,
  `nip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nidn` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nuptk` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_serdos` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `dosen` VALUES (6,'pa ahmad','cilegon','cilegon','2026-03-11','Tidak Tetap','Non Struktural',NULL,'','2026-03-11','2026-03-11','Resign','',NULL,NULL,'humas','2026-03-11','',NULL,NULL,'humas','2026-03-11','',NULL,NULL,'kepegawaian','2026-03-11','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'unsera','kepegawaian',1900,'',NULL,NULL,'S2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Aktif',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-28 13:29:41'),(7,'pa zain','serang','cilegon','2026-03-11','Homebase','Struktural',NULL,'dosen','2026-03-11','2026-03-11','Putus Kontrak','',NULL,NULL,'humas','2026-03-11','',NULL,NULL,'humas','2026-03-11','',NULL,NULL,'kepegawaian','2026-03-11','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'unsera','kepegawaian',1900,'',NULL,NULL,'S3','Menikah',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Aktif',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-28 13:29:41'),(11,'Erik','Cilegon Hiils','Cilegon','2003-02-04','Tetap','Struktural',NULL,'Wakil Rektor 1',NULL,NULL,'','',NULL,NULL,'Tenaga Pengajar',NULL,'',NULL,NULL,'',NULL,'',NULL,NULL,'',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Informatika','Teknik',NULL,'',NULL,NULL,'S3','Menikah',_binary 'uploads/1773505239_download.png',NULL,NULL,'',NULL,NULL,'','Aktif','',NULL,NULL,NULL,NULL,NULL,'2026-04-28 13:29:41'),(13,'1','1','1','2026-04-26','Tetap','Non Struktural',NULL,'',NULL,NULL,'','',NULL,NULL,'Tenaga Pengajar',NULL,'',NULL,NULL,'',NULL,'',NULL,NULL,'',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'S1 Teknik Informatika','Fakultas Teknologi Informasi',NULL,'',NULL,NULL,'S1','Menikah','',NULL,NULL,'',NULL,NULL,'uploads/foto_1777211572_Elaina.webp','Aktif','',NULL,NULL,NULL,NULL,NULL,'2026-04-28 13:29:41'),(15,'1','1','1','2026-04-26','Tetap','Non Struktural',NULL,'',NULL,NULL,'','',NULL,NULL,'',NULL,'',NULL,NULL,'',NULL,'',NULL,NULL,'',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'S1 Sistem Informasi','Fakultas Ekonomi & Bisnis',NULL,'',NULL,NULL,'S1','Belum Menikah','',NULL,NULL,'',NULL,NULL,'','Aktif','-',NULL,'','','',NULL,'2026-04-28 13:29:41');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jabfung_dosen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `jabfung_dosen` VALUES (3,12,'ff','2026-03-15',''),(4,12,'fd','2026-03-15',''),(5,11,'Tenaga Pengajar',NULL,''),(7,13,'Tenaga Pengajar',NULL,'');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jenis_surat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_jenis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `jenis_surat` VALUES (1,'Surat Keputusan'),(2,'Surat Pernyataan'),(3,'Surat Izin Belajar'),(4,'Surat Tugas Belajar');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lldikti_dosen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int DEFAULT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pegawai` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ttl` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_pegawai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `posisi_jabatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_mulai_kerja` date DEFAULT NULL,
  `tmt_tidak_kerja` date DEFAULT NULL,
  `unit_kerja` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pendidikan_terakhir` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pribadi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `riwayat_pendidikan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ket_tidak_kerja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dok_tmtk` longblob,
  `dok_tmtk_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_tmtk_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_keaktifan` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Aktif',
  `keterangan_keaktifan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tgl_mulai_tidak_bekerja` date DEFAULT NULL,
  `ttl_tempat` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl_tanggal` date DEFAULT NULL,
  `status_pegawai` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_ktp` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_kk` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_status_pegawai` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `pegawai` VALUES (2,'Kevin','2','serdang 12 mei, 22 April 2026','tetap','g','2026-03-14',NULL,'jg',NULL,'Belum Menikah','SMP','','',NULL,NULL,'','Aktif',NULL,NULL,'serdang 12 mei','2026-04-22','Tetap',NULL,NULL,NULL,'2026-04-28 13:29:42'),(3,'1','1','1, 26 April 2026',NULL,'1','2026-04-26',NULL,'1',NULL,'Menikah','SMP','','',NULL,NULL,'','Aktif',NULL,NULL,'1','2026-04-26','Tetap','','',NULL,'2026-04-28 13:29:42'),(4,'1','1','1, 26 April 2026',NULL,'1','2026-04-26',NULL,'1',NULL,'Belum Menikah','SD','','',NULL,NULL,'','Aktif',NULL,NULL,'1','2026-04-26','Tetap','','',NULL,'2026-04-28 13:29:42');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pendidikan_dosen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int NOT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `institusi` varchar(255) DEFAULT NULL,
  `tahun_lulus` varchar(10) DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `pendidikan_dosen` VALUES (3,11,'S3','q','1950',''),(6,14,'S1','1','2026-04-26',''),(7,13,'S1','u','',''),(8,15,'S1','1','2026-04-26','');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pendidikan_pegawai` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pegawai_id` int NOT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `institusi` varchar(255) DEFAULT NULL,
  `tahun_lulus` varchar(10) DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `pendidikan_pegawai` VALUES (5,2,'SMP','SMP','',''),(6,3,'SMP','1','2026-04-26',''),(8,4,'SD','k','2026-04-26','');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penugasan_dosen_riwayat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int DEFAULT NULL,
  `jenis_dosen` varchar(50) DEFAULT NULL,
  `jabatan_struktural` varchar(150) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `punishment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int NOT NULL,
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date DEFAULT NULL,
  `file_upload` longblob,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dosen_id` (`dosen_id`),
  CONSTRAINT `punishment_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `punishment` VALUES (5,6,'sp1','2026-03-11','',NULL,NULL),(6,6,'sp2','2026-03-11','',NULL,NULL),(11,7,'sp1','2026-03-12','',NULL,NULL),(12,7,'sp2','2026-03-13','',NULL,NULL),(17,11,'TIDAK DISIPLIN','2026-03-01','',NULL,NULL);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `punishment_pegawai` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pegawai_id` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dokumen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pegawai_id` (`pegawai_id`),
  CONSTRAINT `punishment_pegawai_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int NOT NULL,
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date DEFAULT NULL,
  `file_upload` longblob,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dosen_id` (`dosen_id`),
  CONSTRAINT `reward_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `reward` VALUES (6,6,'pemberian sertifikat','2026-03-11','',NULL,NULL),(7,6,'sertifikat it','2026-03-11','',NULL,NULL),(13,7,'sertifikat it','2026-03-11','',NULL,NULL),(14,7,'pemberian sertifikat','2026-03-04','',NULL,NULL),(20,11,'DOSEN TERBAIK','2026-03-09','',NULL,NULL);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_pegawai` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pegawai_id` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dokumen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pegawai_id` (`pegawai_id`),
  CONSTRAINT `reward_pegawai_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sertifikasi_dosen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int DEFAULT NULL,
  `no_serdos` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_dosen_riwayat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int DEFAULT NULL,
  `status_dosen` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `tgl_berhenti` date DEFAULT NULL,
  `alasan` varchar(100) DEFAULT NULL,
  `alasan_lainnya` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `status_dosen_riwayat` VALUES (3,11,'Tetap',NULL,'',NULL,NULL,NULL),(6,14,'Tetap','2026-05-09','',NULL,NULL,NULL),(7,13,'Tetap',NULL,'',NULL,NULL,NULL),(8,15,'Tetap','2026-04-26','',NULL,NULL,NULL);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_pegawai_riwayat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pegawai_id` int NOT NULL,
  `status_pegawai` varchar(64) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `tgl_berhenti` date DEFAULT NULL,
  `posisi_jabatan` varchar(150) DEFAULT NULL,
  `unit_kerja` varchar(150) DEFAULT NULL,
  `tmt_mulai_kerja` date DEFAULT NULL,
  `tmt_tidak_kerja` date DEFAULT NULL,
  `alasan` varchar(100) DEFAULT NULL,
  `alasan_lainnya` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pegawai_id` (`pegawai_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `status_pegawai_riwayat` VALUES (7,2,'Tetap',NULL,'',NULL,NULL,NULL,'2026-03-14',NULL,NULL,NULL),(8,2,'Tetap',NULL,'',NULL,NULL,NULL,'2026-03-14',NULL,NULL,NULL),(9,2,'Tetap',NULL,'',NULL,NULL,NULL,'2026-03-14',NULL,NULL,NULL),(10,3,'Tetap','2026-04-26','',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,4,'Tetap',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `struktur_organisasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_jabatan` varchar(255) NOT NULL,
  `nama_pejabat` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `parent_id` int DEFAULT NULL,
  `urutan` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `struktur_organisasi` VALUES (1,'s','ssss','sssss',NULL,0);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yayasan_dosen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int DEFAULT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yayasan_pegawai` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pegawai_id` int NOT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


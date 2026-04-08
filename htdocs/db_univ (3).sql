
CREATE TABLE `admin` (
  `id` int NOT NULL,
  `nama` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `admin` (`id`, `nama`, `email`, `password`) VALUES
(2, 'aulia ', 'auliasyaharbanu@gmail.com', '111'),
(3, '1', '1@gmail.com', '$2y$10$qfOEvjM/G5zJz1BaUNXo8uIaHE8wsPmsTBDjHUuPbrFtMmZ6J90RC'),
(4, 'Kevin', '11@gmail.com', '$2y$10$RkFPL4nKvRo46O6V9P.qH.2HWXkXbFt4Dz2y36JB5X47teFB9yT7W'),
(5, 'MasBro', 'masbro@gmail.com', '$2y$10$aRAUTT3VWHFyLpMpImE5LO1nFaGq.OWois/FDqhg3d1K17JiMJt0O');

-- --------------------------------------------------------

--
-- Table structure for table `data_surat`
--

CREATE TABLE `data_surat` (
  `id` int NOT NULL,
  `jenis_id` int NOT NULL,
  `no_surat` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dokumen` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dokumen_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dokumen_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int NOT NULL,
  `nama_lengkap` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl_tempat` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl_tanggal` date DEFAULT NULL,
  `status_dosen` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_dosen` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan_struktural` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmk` date DEFAULT NULL,
  `tmtk` date DEFAULT NULL,
  `ket_tidak_kerja` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_tidak_kerja` longblob,
  `dok_tidak_kerja_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_tidak_kerja_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabfung_akademik` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_jabfung` date DEFAULT NULL,
  `dok_jabfung` longblob,
  `dok_jabfung_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_jabfung_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gol_lldikti` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_gol_lldikti` date DEFAULT NULL,
  `dok_gol_lldikti` longblob,
  `dok_gol_lldikti_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_gol_lldikti_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gol_yayasan` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_gol_yayasan` date DEFAULT NULL,
  `dok_gol_yayasan` longblob,
  `dok_gol_yayasan_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_gol_yayasan_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reward` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tgl_reward` date DEFAULT NULL,
  `dok_reward` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `punishment` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_punishment` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `homebase_prodi` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit_kerja` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_serdos` int DEFAULT NULL,
  `dok_serdos` longblob,
  `dok_serdos_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_serdos_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `riwayat_pendidikan` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pribadi` char(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_ktp` longblob,
  `dok_ktp_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_ktp_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_kk` longblob,
  `dok_kk_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_kk_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_profil` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `nama_lengkap`, `alamat`, `ttl_tempat`, `ttl_tanggal`, `status_dosen`, `jenis_dosen`, `jabatan_struktural`, `tmk`, `tmtk`, `ket_tidak_kerja`, `dok_tidak_kerja`, `dok_tidak_kerja_name`, `dok_tidak_kerja_type`, `jabfung_akademik`, `tmt_jabfung`, `dok_jabfung`, `dok_jabfung_name`, `dok_jabfung_type`, `gol_lldikti`, `tmt_gol_lldikti`, `dok_gol_lldikti`, `dok_gol_lldikti_name`, `dok_gol_lldikti_type`, `gol_yayasan`, `tmt_gol_yayasan`, `dok_gol_yayasan`, `dok_gol_yayasan_name`, `dok_gol_yayasan_type`, `reward`, `tgl_reward`, `dok_reward`, `punishment`, `dok_punishment`, `homebase_prodi`, `unit_kerja`, `no_serdos`, `dok_serdos`, `dok_serdos_name`, `dok_serdos_type`, `riwayat_pendidikan`, `status_pribadi`, `dok_ktp`, `dok_ktp_name`, `dok_ktp_type`, `dok_kk`, `dok_kk_name`, `dok_kk_type`) VALUES
(1, 'pa iman', 'serang', 'cilegon', '1995-07-10', 'Tetap', 'Non Struktural', '', '2020-06-03', '2025-08-15', 'Putus Kontrak', '', NULL, NULL, 'humas', '2022-11-19', 0x6a616266756e675f696d616e2e706466, NULL, NULL, 'humas', '2024-08-24', '', NULL, NULL, 'kepegawaian', '2022-09-23', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'unsera', 'kepegawaia', 2001, '', NULL, NULL, 'S1', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'bu nisa', 'cilegon', 'cilegon', '2004-01-07', 'Tetap', 'Struktural', 'kepegawaian', '2018-07-01', '2023-07-22', 'Resign', 0x75706c6f6164732f313737333230313534345f53637265656e73686f74202833292e706e67, NULL, NULL, 'humas', '2024-12-04', 0x75706c6f6164732f313737333230313534345f53637265656e73686f74202837292e706e67, NULL, NULL, 'humas', '2025-12-04', 0x75706c6f6164732f313737333230313534345f53637265656e73686f74202838292e706e67, NULL, NULL, 'kepegawaian', '2022-08-12', 0x75706c6f6164732f313737333230313534345f53637265656e73686f7420283138292e706e67, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'unsera', 'kepegawaian', 2005, 0x75706c6f6164732f313737333230313534345f53637265656e73686f7420283133292e706e67, NULL, NULL, 'S2', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'pa ahmad', 'cilegon', 'cilegon', '2026-03-11', 'Tidak Tetap', 'Non Struktural', '', '2026-03-11', '2026-03-11', 'Resign', '', NULL, NULL, 'humas', '2026-03-11', '', NULL, NULL, 'humas', '2026-03-11', '', NULL, NULL, 'kepegawaian', '2026-03-11', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'unsera', 'kepegawaian', 1900, '', NULL, NULL, 'S2', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'pa zain', 'serang', 'cilegon', '2026-03-11', 'Homebase', 'Struktural', 'dosen', '2026-03-11', '2026-03-11', 'Putus Kontrak', '', NULL, NULL, 'humas', '2026-03-11', '', NULL, NULL, 'humas', '2026-03-11', '', NULL, NULL, 'kepegawaian', '2026-03-11', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'unsera', 'kepegawaian', 1900, '', NULL, NULL, 'S3', 'Menikah', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Erik', 'Cilegon Hiils', 'Cilegon', '2003-02-04', 'Tetap', 'Struktural', 'Wakil Rektor 1', '2026-03-10', NULL, '', '', NULL, NULL, 'Lektor Kepala', '2026-03-13', '', NULL, NULL, 'III/C', '2026-03-06', '', NULL, NULL, 'G-2', '2026-03-02', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Informatika', 'Teknik', 2019, '', NULL, NULL, 'S3', 'Menikah', 0x75706c6f6164732f313737333530353233395f646f776e6c6f61642e706e67, NULL, NULL, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jabfung_dosen`
--

CREATE TABLE `jabfung_dosen` (
  `id` int NOT NULL,
  `dosen_id` int DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jabfung_dosen`
--

INSERT INTO `jabfung_dosen` (`id`, `dosen_id`, `jabatan`, `tmt`, `dokumen`) VALUES
(3, 12, 'ff', '2026-03-15', ''),
(4, 12, 'fd', '2026-03-15', '');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_surat`
--

CREATE TABLE `jenis_surat` (
  `id` int NOT NULL,
  `nama_jenis` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_surat`
--

INSERT INTO `jenis_surat` (`id`, `nama_jenis`) VALUES
(1, 'Surat Keputusan'),
(2, 'Surat Pernyataan'),
(3, 'Surat Izin Belajar'),
(4, 'Surat Tugas Belajar');

-- --------------------------------------------------------

--
-- Table structure for table `lldikti_dosen`
--

CREATE TABLE `lldikti_dosen` (
  `id` int NOT NULL,
  `dosen_id` int DEFAULT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `ttl` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_pegawai` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `posisi_jabatan` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tmt_mulai_kerja` date DEFAULT NULL,
  `tmt_tidak_kerja` date DEFAULT NULL,
  `unit_kerja` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pendidikan_terakhir` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pribadi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `riwayat_pendidikan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ket_tidak_kerja` text COLLATE utf8mb4_general_ci,
  `dok_tmtk` longblob,
  `dok_tmtk_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dok_tmtk_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_profil` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id`, `nama_lengkap`, `alamat`, `ttl`, `jenis_pegawai`, `posisi_jabatan`, `tmt_mulai_kerja`, `tmt_tidak_kerja`, `unit_kerja`, `pendidikan_terakhir`, `status_pribadi`, `riwayat_pendidikan`, `ket_tidak_kerja`, `dok_tmtk`, `dok_tmtk_name`, `dok_tmtk_type`) VALUES
(2, 'Kevin', '2', 'serdang 12 mei', 'tetap', 'g', '2026-03-14', NULL, 'jg', NULL, 'Belum Menikah', 'SMP', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `punishment`
--

CREATE TABLE `punishment` (
  `id` int NOT NULL,
  `dosen_id` int NOT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date DEFAULT NULL,
  `file_upload` longblob,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `punishment`
--

INSERT INTO `punishment` (`id`, `dosen_id`, `deskripsi`, `tanggal`, `file_upload`, `file_name`, `file_type`) VALUES
(1, 1, 'sp1', '2024-05-06', 0x75706c6f6164732f313737333139333631375f53637265656e73686f7420283139292e706e67, NULL, NULL),
(2, 2, 'sp1', '2024-04-26', 0x75706c6f6164732f313737333230313534345f53637265656e73686f74202837292e706e67, NULL, NULL),
(5, 6, 'sp1', '2026-03-11', '', NULL, NULL),
(6, 6, 'sp2', '2026-03-11', '', NULL, NULL),
(11, 7, 'sp1', '2026-03-12', '', NULL, NULL),
(12, 7, 'sp2', '2026-03-13', '', NULL, NULL),
(14, 11, 'TIDAK DISIPLIN', '2026-03-01', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `punishment_pegawai`
--

CREATE TABLE `punishment_pegawai` (
  `id` int NOT NULL,
  `pegawai_id` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dokumen` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reward`
--

CREATE TABLE `reward` (
  `id` int NOT NULL,
  `dosen_id` int NOT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date DEFAULT NULL,
  `file_upload` longblob,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward`
--

INSERT INTO `reward` (`id`, `dosen_id`, `deskripsi`, `tanggal`, `file_upload`, `file_name`, `file_type`) VALUES
(1, 1, 'sertifikat it', '2024-12-18', 0x75706c6f6164732f313737333139333631375f53637265656e73686f7420283139292e706e67, NULL, NULL),
(2, 2, 'pemberian sertifikat', '2025-01-30', 0x75706c6f6164732f313737333230313534345f53637265656e73686f74202835292e706e67, NULL, NULL),
(6, 6, 'pemberian sertifikat', '2026-03-11', '', NULL, NULL),
(7, 6, 'sertifikat it', '2026-03-11', '', NULL, NULL),
(13, 7, 'sertifikat it', '2026-03-11', '', NULL, NULL),
(14, 7, 'pemberian sertifikat', '2026-03-04', '', NULL, NULL),
(16, 11, 'DOSEN TERBAIK', '2026-03-09', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reward_pegawai`
--

CREATE TABLE `reward_pegawai` (
  `id` int NOT NULL,
  `pegawai_id` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `dokumen` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `yayasan_dosen`
--

CREATE TABLE `yayasan_dosen` (
  `id` int NOT NULL,
  `dosen_id` int DEFAULT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `yayasan_pegawai`
--

CREATE TABLE `yayasan_pegawai` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pendidikan_dosen`
--

CREATE TABLE `pendidikan_dosen` (
  `id` int NOT NULL,
  `dosen_id` int NOT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `institusi` varchar(255) DEFAULT NULL,
  `tahun_lulus` varchar(10) DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pendidikan_pegawai`
--

CREATE TABLE `pendidikan_pegawai` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `institusi` varchar(255) DEFAULT NULL,
  `tahun_lulus` varchar(10) DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `data_surat`
--
ALTER TABLE `data_surat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jenis_id` (`jenis_id`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jabfung_dosen`
--
ALTER TABLE `jabfung_dosen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jenis_surat`
--
ALTER TABLE `jenis_surat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lldikti_dosen`
--
ALTER TABLE `lldikti_dosen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `punishment`
--
ALTER TABLE `punishment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `punishment_pegawai`
--
ALTER TABLE `punishment_pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `reward`
--
ALTER TABLE `reward`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `reward_pegawai`
--
ALTER TABLE `reward_pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `yayasan_dosen`
--
ALTER TABLE `yayasan_dosen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `yayasan_pegawai`
--
ALTER TABLE `yayasan_pegawai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pendidikan_dosen`
--
ALTER TABLE `pendidikan_dosen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pendidikan_pegawai`
--
ALTER TABLE `pendidikan_pegawai`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `data_surat`
--
ALTER TABLE `data_surat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jabfung_dosen`
--
ALTER TABLE `jabfung_dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jenis_surat`
--
ALTER TABLE `jenis_surat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lldikti_dosen`
--
ALTER TABLE `lldikti_dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `punishment`
--
ALTER TABLE `punishment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `punishment_pegawai`
--
ALTER TABLE `punishment_pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward`
--
ALTER TABLE `reward`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reward_pegawai`
--
ALTER TABLE `reward_pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `yayasan_dosen`
--
ALTER TABLE `yayasan_dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `yayasan_pegawai`
--
ALTER TABLE `yayasan_pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pendidikan_dosen`
--
ALTER TABLE `pendidikan_dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pendidikan_pegawai`
--
ALTER TABLE `pendidikan_pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `data_surat`
--
ALTER TABLE `data_surat`
  ADD CONSTRAINT `data_surat_ibfk_1` FOREIGN KEY (`jenis_id`) REFERENCES `jenis_surat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `punishment`
--
ALTER TABLE `punishment`
  ADD CONSTRAINT `punishment_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `punishment_pegawai`
--
ALTER TABLE `punishment_pegawai`
  ADD CONSTRAINT `punishment_pegawai_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reward`
--
ALTER TABLE `reward`
  ADD CONSTRAINT `reward_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reward_pegawai`
--
ALTER TABLE `reward_pegawai`
  ADD CONSTRAINT `reward_pegawai_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

<?php
require 'db.php';

$migrations = [
    // --- DOSEN TABLES ---
    "CREATE TABLE IF NOT EXISTS `homebase_dosen_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `prodi` varchar(255) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `unit_kerja_dosen_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `unit` varchar(255) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `status_dosen_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `status_dosen` varchar(64) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `tgl_berhenti` date DEFAULT NULL,
        `alasan` varchar(255) DEFAULT NULL,
        `alasan_lainnya` text DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `jabfung_dosen` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `jabatan` varchar(100) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `lldikti_dosen` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `golongan` varchar(50) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `yayasan_dosen` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `golongan` varchar(50) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `sertifikasi_dosen` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `no_serdos` varchar(100) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `pendidikan_dosen` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `jenjang` varchar(10) DEFAULT NULL,
        `institusi` varchar(255) DEFAULT NULL,
        `tahun_lulus` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // --- PEGAWAI TABLES ---
    "CREATE TABLE IF NOT EXISTS `unit_kerja_pegawai_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `unit_kerja` varchar(255) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `status_pegawai_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `status_pegawai` varchar(64) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `tgl_berhenti` date DEFAULT NULL,
        `alasan` varchar(255) DEFAULT NULL,
        `alasan_lainnya` text DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `jabfung_pegawai` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `jabatan` varchar(100) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `lldikti_pegawai` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `golongan` varchar(50) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `yayasan_pegawai` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `golongan` varchar(50) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `pendidikan_pegawai` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `jenjang` varchar(10) DEFAULT NULL,
        `institusi` varchar(255) DEFAULT NULL,
        `tahun_lulus` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `reward_pegawai` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `keterangan` text,
        `tanggal` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `punishment_pegawai` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `keterangan` text,
        `tanggal` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // --- MAIN TABLE UPDATES ---
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_penugasan_struktural VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_status_dosen VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_tmb VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_berhenti_bertugas VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS status_keaktifan VARCHAR(20) DEFAULT 'Aktif'",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS keterangan_keaktifan VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_tmtk VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) DEFAULT NULL",
];

echo "<h2>Running Universal History Migration...</h2>";
foreach ($migrations as $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>SUCCESS: " . substr($sql, 0, 60) . "...</p>";
    } else {
        echo "<p style='color:red;'>ERROR: " . $conn->error . " | SQL: " . substr($sql, 0, 60) . "...</p>";
    }
}
echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
?>

<?php
require 'db.php';

$migrations = [
    // ADD missing columns to dosen table
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS nip VARCHAR(30) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS nidn VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS nuptk VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_penugasan_struktural VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_status_dosen VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_tmb VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_berhenti_bertugas VARCHAR(255) DEFAULT NULL",

    // ADD missing columns to pegawai table  
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS ttl_tempat VARCHAR(64) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS ttl_tanggal DATE DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_ktp VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_kk VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS status_pegawai VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_status_pegawai VARCHAR(255) DEFAULT NULL",

    // CREATE status_dosen_riwayat table if not exists
    "CREATE TABLE IF NOT EXISTS `status_dosen_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `status_dosen` varchar(64) DEFAULT NULL,
        `jenis_dosen` varchar(64) DEFAULT NULL,
        `jabatan_struktural` varchar(64) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `tmtbt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // CREATE status_pegawai_riwayat table if not exists
    "CREATE TABLE IF NOT EXISTS `status_pegawai_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `status_pegawai` varchar(64) DEFAULT NULL,
        `posisi_jabatan` varchar(100) DEFAULT NULL,
        `unit_kerja` varchar(100) DEFAULT NULL,
        `tmt_mulai_kerja` date DEFAULT NULL,
        `tmt_tidak_kerja` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

$success = 0;
$errors = [];
foreach ($migrations as $sql) {
    if ($conn->query($sql)) {
        $success++;
    } else {
        $errors[] = $conn->error . " — SQL: " . substr($sql, 0, 80);
    }
}

echo "<h2>Migration Selesai</h2>";
echo "<p style='color:green;'>Berhasil: $success dari " . count($migrations) . "</p>";
if ($errors) {
    echo "<ul style='color:red;'>";
    foreach ($errors as $e) echo "<li>$e</li>";
    echo "</ul>";
}
echo "<br><a href='dashboard.php'>Kembali ke Dashboard</a>";
?>

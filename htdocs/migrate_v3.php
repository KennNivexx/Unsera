<?php
require 'db.php';

$sqls = [
    "ALTER TABLE status_dosen_riwayat ADD COLUMN IF NOT EXISTS tgl_berhenti DATE DEFAULT NULL",
    "ALTER TABLE penugasan_dosen_riwayat ADD COLUMN IF NOT EXISTS tgl_berhenti DATE DEFAULT NULL"
];

foreach ($sqls as $sql) {
    if ($conn->query($sql)) {
        echo "OK: $sql<br>";
    } else {
        echo "ERR: " . $conn->error . " | SQL: $sql<br>";
    }
}

// Add status_dosen_riwayat if it doesn't exist (since it wasn't in db_univ 3 sql dump)
$create_sdr = "CREATE TABLE IF NOT EXISTS `status_dosen_riwayat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int NOT NULL,
  `status_dosen` varchar(64) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `tgl_berhenti` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($create_sdr)) {
    echo "OK: CREATE TABLE status_dosen_riwayat<br>";
}

$create_pdr = "CREATE TABLE IF NOT EXISTS `penugasan_dosen_riwayat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dosen_id` int NOT NULL,
  `jenis_dosen` varchar(64) DEFAULT NULL,
  `jabatan_struktural` varchar(64) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `tgl_berhenti` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($create_pdr)) {
    echo "OK: CREATE TABLE penugasan_dosen_riwayat<br>";
}

echo "Database Migration V3 (tgl_berhenti) is done!";

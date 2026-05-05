<?php
require '../db.php';

$sqls = [
    "CREATE TABLE IF NOT EXISTS `homebase_dosen_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `homebase_prodi` varchar(255) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `unit_kerja_dosen_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dosen_id` int NOT NULL,
        `unit_kerja` varchar(255) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `unit_kerja_pegawai_riwayat` (
        `id` int NOT NULL AUTO_INCREMENT,
        `pegawai_id` int NOT NULL,
        `unit_kerja` varchar(255) DEFAULT NULL,
        `tmt` date DEFAULT NULL,
        `dokumen` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `pegawai_id` (`pegawai_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($sqls as $sql) {
    if ($conn->query($sql)) {
        echo "OK: " . substr($sql, 0, 50) . "...<br>";
    } else {
        echo "ERR: " . $conn->error . "<br>";
    }
}

echo "Migration done!";
?>

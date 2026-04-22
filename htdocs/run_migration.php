<?php
require 'db.php';
$conn->query("CREATE TABLE IF NOT EXISTS penugasan_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jenis_dosen VARCHAR(50), 
    jabatan_struktural VARCHAR(150),
    tmt DATE, 
    dokumen VARCHAR(255)
)");
$conn->query("ALTER TABLE pegawai ADD COLUMN jenis_pegawai VARCHAR(100) DEFAULT 'Tidak Tetap'");
echo "Success";
?>

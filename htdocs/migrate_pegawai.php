<?php
require 'db.php';

// Ensure reward_pegawai table
$conn->query("CREATE TABLE IF NOT EXISTS reward_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    keterangan TEXT,
    tanggal DATE,
    dokumen VARCHAR(255)
)");

// Ensure punishment_pegawai table
$conn->query("CREATE TABLE IF NOT EXISTS punishment_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    keterangan TEXT,
    tanggal DATE,
    dokumen VARCHAR(255)
)");

// Ensure pendidikan_dosen table (riwayat pendidikan multi-entry)
$conn->query("CREATE TABLE IF NOT EXISTS pendidikan_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    jenjang VARCHAR(20),
    institusi VARCHAR(255),
    tahun_lulus VARCHAR(10),
    dokumen VARCHAR(255)
)");

// Ensure pendidikan_pegawai table (riwayat pendidikan multi-entry)
$conn->query("CREATE TABLE IF NOT EXISTS pendidikan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    jenjang VARCHAR(20),
    institusi VARCHAR(255),
    tahun_lulus VARCHAR(10),
    dokumen VARCHAR(255)
)");

// Ensure yayasan_pegawai table (golongan yayasan untuk pegawai)
$conn->query("CREATE TABLE IF NOT EXISTS yayasan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");

// Add missing columns to pegawai if any
$columns = $conn->query("SHOW COLUMNS FROM pegawai");
$existing = [];
while($row = $columns->fetch_assoc()) $existing[] = $row['Field'];

if(!in_array('status_pribadi', $existing)) $conn->query("ALTER TABLE pegawai ADD COLUMN status_pribadi VARCHAR(50)");
if(!in_array('riwayat_pendidikan', $existing)) $conn->query("ALTER TABLE pegawai ADD COLUMN riwayat_pendidikan VARCHAR(50)");
if(!in_array('ket_tidak_kerja', $existing)) $conn->query("ALTER TABLE pegawai ADD COLUMN ket_tidak_kerja TEXT");
if(!in_array('dok_tmtk', $existing)) $conn->query("ALTER TABLE pegawai ADD COLUMN dok_tmtk VARCHAR(255)");
if(!in_array('foto_profil', $existing)) $conn->query("ALTER TABLE pegawai ADD COLUMN foto_profil VARCHAR(255)");

// Add foto_profil to dosen if not exists
$colsDosen = $conn->query("SHOW COLUMNS FROM dosen");
$existingDosen = [];
while($row = $colsDosen->fetch_assoc()) $existingDosen[] = $row['Field'];
if(!in_array('foto_profil', $existingDosen)) $conn->query("ALTER TABLE dosen ADD COLUMN foto_profil VARCHAR(255)");

echo "Migration Successful";
?>

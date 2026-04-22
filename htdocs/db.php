<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_unseraa_univ";

$conn = mysqli_connect($host,$user,$pass,$db);

if(!$conn){
    die("Koneksi gagal");
}

// Ensure jenis_kelamin column exists in admin table
$res_admin_jk = $conn->query("SHOW COLUMNS FROM admin LIKE 'jenis_kelamin'");
if ($res_admin_jk && $res_admin_jk->num_rows == 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT 'Laki-laki'");
}

// Ensure required columns in pegawai
$res_peg_check = $conn->query("SHOW COLUMNS FROM pegawai LIKE 'jenis_pegawai'");
if ($res_peg_check && $res_peg_check->num_rows == 0) {
    $conn->query("ALTER TABLE pegawai ADD COLUMN jenis_pegawai VARCHAR(100) DEFAULT 'Tidak Tetap'");
}
$res_peg_check2 = $conn->query("SHOW COLUMNS FROM pegawai LIKE 'status_pribadi'");
if ($res_peg_check2 && $res_peg_check2->num_rows == 0) {
    $conn->query("ALTER TABLE pegawai ADD COLUMN status_pribadi VARCHAR(50) DEFAULT 'Belum Menikah'");
}



// Ensure required tables exist for multiple data without needing a separate migration file
$conn->query("CREATE TABLE IF NOT EXISTS jabfung_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jabatan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");
$conn->query("CREATE TABLE IF NOT EXISTS lldikti_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");
$conn->query("CREATE TABLE IF NOT EXISTS yayasan_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");

// Dynamic check and add new columns to dosen
$res_check = $conn->query("SHOW COLUMNS FROM dosen LIKE 'nip'");
if ($res_check && $res_check->num_rows == 0) {
    $conn->query("ALTER TABLE dosen ADD COLUMN nip VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE dosen ADD COLUMN nidn VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE dosen ADD COLUMN nuptk VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE dosen ADD COLUMN status_keaktifan VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE dosen ADD COLUMN keterangan_keaktifan VARCHAR(100) DEFAULT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS status_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    status_dosen VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS status_pegawai_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    status_pegawai VARCHAR(100),
    unit_kerja VARCHAR(150),
    posisi_jabatan VARCHAR(150),
    tmt_mulai_kerja DATE,
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS penugasan_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jenis_dosen VARCHAR(50), 
    jabatan_struktural VARCHAR(150),
    tmt DATE, 
    dokumen VARCHAR(255)
)");
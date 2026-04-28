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

$res_peg_ttl = $conn->query("SHOW COLUMNS FROM pegawai LIKE 'ttl_tempat'");
if ($res_peg_ttl && $res_peg_ttl->num_rows == 0) {
    $conn->query("ALTER TABLE pegawai ADD COLUMN ttl_tempat VARCHAR(100) DEFAULT NULL");
    $conn->query("ALTER TABLE pegawai ADD COLUMN ttl_tanggal DATE DEFAULT NULL");
}

$pegawai_cols = [
    'status_pegawai' => 'VARCHAR(100) DEFAULT NULL',
    'posisi_jabatan' => 'VARCHAR(150) DEFAULT NULL',
    'unit_kerja' => 'VARCHAR(150) DEFAULT NULL',
    'tmt_mulai_kerja' => 'DATE DEFAULT NULL',
    'tmt_tidak_kerja' => 'DATE DEFAULT NULL',
    'riwayat_pendidikan' => 'TEXT DEFAULT NULL',
    'ket_tidak_kerja' => 'VARCHAR(255) DEFAULT NULL',
    'dok_tmtk' => 'VARCHAR(255) DEFAULT NULL',
    'dok_ktp' => 'VARCHAR(255) DEFAULT NULL',
    'dok_kk' => 'VARCHAR(255) DEFAULT NULL',
    'dok_status_pegawai' => 'VARCHAR(255) DEFAULT NULL',
    'foto_profil' => 'VARCHAR(255) DEFAULT NULL',
    'ttl' => 'VARCHAR(255) DEFAULT NULL'
];

foreach ($pegawai_cols as $col => $type) {
    $res = $conn->query("SHOW COLUMNS FROM pegawai LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE pegawai ADD COLUMN $col $type");
    }
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

$conn->query("CREATE TABLE IF NOT EXISTS pendidikan_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jenjang VARCHAR(50),
    institusi VARCHAR(255),
    tahun_lulus VARCHAR(10),
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS reward (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    deskripsi TEXT,
    tanggal DATE,
    file_upload VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS punishment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    deskripsi TEXT,
    tanggal DATE,
    file_upload VARCHAR(255)
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

$res_check2 = $conn->query("SHOW COLUMNS FROM dosen LIKE 'dok_ktp'");
if ($res_check2 && $res_check2->num_rows == 0) {
    $conn->query("ALTER TABLE dosen ADD COLUMN dok_ktp VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE dosen ADD COLUMN dok_kk VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE dosen ADD COLUMN tgl_mulai_tidak_bekerja DATE DEFAULT NULL");
}

$res_check3 = $conn->query("SHOW COLUMNS FROM dosen LIKE 'dok_tidak_kerja'");
if ($res_check3 && $res_check3->num_rows == 0) {
    $conn->query("ALTER TABLE dosen ADD COLUMN dok_tidak_kerja VARCHAR(255) DEFAULT NULL");
}

$res_serdos_tmt = $conn->query("SHOW COLUMNS FROM dosen LIKE 'tmt_serdos'");
if ($res_serdos_tmt && $res_serdos_tmt->num_rows == 0) {
    $conn->query("ALTER TABLE dosen ADD COLUMN tmt_serdos DATE DEFAULT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS sertifikasi_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    no_serdos VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS status_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    status_dosen VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");

$res_status_dos = $conn->query("SHOW COLUMNS FROM status_dosen_riwayat LIKE 'tgl_berhenti'");
if ($res_status_dos && $res_status_dos->num_rows == 0) {
    $conn->query("ALTER TABLE status_dosen_riwayat ADD COLUMN tgl_berhenti DATE DEFAULT NULL");
}
$res_sd_alasan = $conn->query("SHOW COLUMNS FROM status_dosen_riwayat LIKE 'alasan'");
if ($res_sd_alasan && $res_sd_alasan->num_rows == 0) {
    $conn->query("ALTER TABLE status_dosen_riwayat ADD COLUMN alasan VARCHAR(100) DEFAULT NULL");
    $conn->query("ALTER TABLE status_dosen_riwayat ADD COLUMN alasan_lainnya VARCHAR(255) DEFAULT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS status_pegawai_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    status_pegawai VARCHAR(100),
    unit_kerja VARCHAR(150),
    posisi_jabatan VARCHAR(150),
    tmt_mulai_kerja DATE,
    dokumen VARCHAR(255)
)");

$status_peg_riw_cols = [
    'posisi_jabatan' => 'VARCHAR(150) DEFAULT NULL',
    'unit_kerja' => 'VARCHAR(150) DEFAULT NULL',
    'tmt_mulai_kerja' => 'DATE DEFAULT NULL',
    'tmt_tidak_kerja' => 'DATE DEFAULT NULL',
    'tgl_berhenti' => 'DATE DEFAULT NULL',
    'tmt' => 'DATE DEFAULT NULL',
    'dokumen' => 'VARCHAR(255) DEFAULT NULL',
    'alasan' => 'VARCHAR(100) DEFAULT NULL',
    'alasan_lainnya' => 'VARCHAR(255) DEFAULT NULL'
];

foreach ($status_peg_riw_cols as $col => $type) {
    $res = $conn->query("SHOW COLUMNS FROM status_pegawai_riwayat LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE status_pegawai_riwayat ADD COLUMN $col $type");
    }
}

$conn->query("CREATE TABLE IF NOT EXISTS penugasan_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jenis_dosen VARCHAR(50), 
    jabatan_struktural VARCHAR(150),
    tmt DATE, 
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS yayasan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS pendidikan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    jenjang VARCHAR(50),
    institusi VARCHAR(255),
    tahun_lulus VARCHAR(10),
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS reward_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    keterangan TEXT,
    tanggal DATE,
    dokumen VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS punishment_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    keterangan TEXT,
    tanggal DATE,
    dokumen VARCHAR(255)
)");

// Ensure struktur_organisasi table and its columns exist
$conn->query("CREATE TABLE IF NOT EXISTS struktur_organisasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(255) NOT NULL,
    nama_pejabat VARCHAR(255) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL
)");

$res_struct = $conn->query("SHOW COLUMNS FROM struktur_organisasi LIKE 'parent_id'");
if ($res_struct && $res_struct->num_rows == 0) {
    $conn->query("ALTER TABLE struktur_organisasi ADD COLUMN parent_id INT DEFAULT NULL");
    $conn->query("ALTER TABLE struktur_organisasi ADD COLUMN urutan INT DEFAULT 0");
}

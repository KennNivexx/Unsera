-- =============================================
-- UNSERA Schema — Run di phpMyAdmin InfinityFree
-- =============================================

-- 1. Admin table: tambah jenis_kelamin
ALTER TABLE admin ADD COLUMN IF NOT EXISTS jenis_kelamin ENUM('Laki-laki','Perempuan') DEFAULT 'Laki-laki';

-- 2. Pegawai columns
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS jenis_pegawai VARCHAR(100) DEFAULT 'Tidak Tetap';
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS status_pribadi VARCHAR(50) DEFAULT 'Belum Menikah';
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS ttl_tempat VARCHAR(100) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS ttl_tanggal DATE DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS status_pegawai VARCHAR(100) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS posisi_jabatan VARCHAR(150) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS unit_kerja VARCHAR(150) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS tmt_mulai_kerja DATE DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS tmt_tidak_kerja DATE DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS riwayat_pendidikan TEXT DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS ket_tidak_kerja VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_tmtk VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_ktp VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_kk VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS dok_status_pegawai VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS ttl VARCHAR(255) DEFAULT NULL;
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 3. Dosen columns
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS nip VARCHAR(50) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS nidn VARCHAR(50) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS nuptk VARCHAR(50) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS status_keaktifan VARCHAR(50) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS keterangan_keaktifan VARCHAR(100) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_ktp VARCHAR(255) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_kk VARCHAR(255) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS tgl_mulai_tidak_bekerja DATE DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_tidak_kerja VARCHAR(255) DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS tmt_serdos DATE DEFAULT NULL;
ALTER TABLE dosen ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 4. Create tables
CREATE TABLE IF NOT EXISTS jabfung_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jabatan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS lldikti_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS yayasan_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS pendidikan_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jenjang VARCHAR(50),
    institusi VARCHAR(255),
    tahun_lulus VARCHAR(10),
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS reward (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    deskripsi TEXT,
    tanggal DATE,
    file_upload VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS punishment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    deskripsi TEXT,
    tanggal DATE,
    file_upload VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS sertifikasi_dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    no_serdos VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS status_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    status_dosen VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255),
    tgl_berhenti DATE DEFAULT NULL,
    alasan VARCHAR(100) DEFAULT NULL,
    alasan_lainnya VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS status_pegawai_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    status_pegawai VARCHAR(100),
    unit_kerja VARCHAR(150),
    posisi_jabatan VARCHAR(150),
    tmt_mulai_kerja DATE,
    dokumen VARCHAR(255),
    tmt_tidak_kerja DATE DEFAULT NULL,
    tgl_berhenti DATE DEFAULT NULL,
    tmt DATE DEFAULT NULL,
    alasan VARCHAR(100) DEFAULT NULL,
    alasan_lainnya VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS penugasan_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT,
    jenis_dosen VARCHAR(50),
    jabatan_struktural VARCHAR(150),
    tmt DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS yayasan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    golongan VARCHAR(100),
    tmt DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS pendidikan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    jenjang VARCHAR(50),
    institusi VARCHAR(255),
    tahun_lulus VARCHAR(10),
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS reward_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    keterangan TEXT,
    tanggal DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS punishment_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT,
    keterangan TEXT,
    tanggal DATE,
    dokumen VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS struktur_organisasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(255) NOT NULL,
    nama_pejabat VARCHAR(255) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    urutan INT DEFAULT 0
);

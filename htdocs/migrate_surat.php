<?php
include 'db.php';

// Add KTP and KK to Dosen table
$sql1 = "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS dok_ktp LONGBLOB, 
        ADD COLUMN IF NOT EXISTS dok_ktp_name VARCHAR(255), 
        ADD COLUMN IF NOT EXISTS dok_ktp_type VARCHAR(100),
        ADD COLUMN IF NOT EXISTS dok_kk LONGBLOB,
        ADD COLUMN IF NOT EXISTS dok_kk_name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS dok_kk_type VARCHAR(100)";

if ($conn->query($sql1)) {
    echo "Dosen table updated.<br>";
} else {
    echo "Error updating Dosen table: " . $conn->error . "<br>";
}

// Create jenis_surat table
$sql2 = "CREATE TABLE IF NOT EXISTS jenis_surat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jenis VARCHAR(100) NOT NULL
)";

if ($conn->query($sql2)) {
    echo "Table 'jenis_surat' created or already exists.<br>";
} else {
    echo "Error creating 'jenis_surat' table: " . $conn->error . "<br>";
}

// Create data_surat table
$sql3 = "CREATE TABLE IF NOT EXISTS data_surat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_id INT NOT NULL,
    no_surat VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT,
    dokumen LONGBLOB,
    dokumen_name VARCHAR(255),
    dokumen_type VARCHAR(100),
    FOREIGN KEY (jenis_id) REFERENCES jenis_surat(id) ON DELETE CASCADE
)";

if ($conn->query($sql3)) {
    echo "Table 'data_surat' created or already exists.<br>";
} else {
    echo "Error creating 'data_surat' table: " . $conn->error . "<br>";
}

// Seed initial types if empty
$check_empty = $conn->query("SELECT COUNT(*) as count FROM jenis_surat");
$row = $check_empty->fetch_assoc();
if ($row['count'] == 0) {
    $initial_types = ['Surat Keputusan', 'Surat Pernyataan', 'Surat Izin Belajar', 'Surat Tugas Belajar'];
    foreach ($initial_types as $type) {
        $conn->query("INSERT INTO jenis_surat (nama_jenis) VALUES ('$type')");
    }
    echo "Initial types seeded.<br>";
}

echo "Migration completed.";
?>

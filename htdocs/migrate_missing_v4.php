<?php
require 'db.php';

// 1. Add missing columns to 'dosen'
$columns_to_add = [
    'dok_status_dosen' => 'VARCHAR(255)',
    'dok_tmb' => 'VARCHAR(255)',
    'dok_berhenti_bertugas' => 'VARCHAR(255)',
    'dok_penugasan_struktural' => 'VARCHAR(255)'
];

foreach ($columns_to_add as $col => $type) {
    $check = $conn->query("SHOW COLUMNS FROM dosen LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE dosen ADD COLUMN $col $type");
        echo "Added column $col to dosen\n";
    }
}

// 2. Create homebase_dosen_riwayat
$conn->query("CREATE TABLE IF NOT EXISTS homebase_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    homebase_prodi VARCHAR(255),
    tmt DATE,
    dokumen VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "Table homebase_dosen_riwayat ready\n";

// 3. Create unit_kerja_dosen_riwayat
$conn->query("CREATE TABLE IF NOT EXISTS unit_kerja_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    unit_kerja VARCHAR(255),
    tmt DATE,
    dokumen VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "Table unit_kerja_dosen_riwayat ready\n";

// 4. Update reward & punishment tables to use varchar for file paths
$conn->query("ALTER TABLE reward MODIFY COLUMN file_upload VARCHAR(255)");
$conn->query("ALTER TABLE punishment MODIFY COLUMN file_upload VARCHAR(255)");
echo "Updated reward & punishment tables\n";

echo "Migration completed successfully!\n";
?>

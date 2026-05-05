<?php
require 'db.php';
$tables = ['dosen', 'status_dosen_riwayat', 'homebase_dosen_riwayat', 'unit_kerja_dosen_riwayat', 'reward', 'punishment', 'jabfung_dosen', 'lldikti_dosen', 'yayasan_dosen', 'pendidikan_dosen', 'sertifikasi_dosen'];
foreach($tables as $t) {
    echo "--- Table: $t ---\n";
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if($res->num_rows > 0) {
        $result = $conn->query("SHOW COLUMNS FROM $t");
        while($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "MISSING\n";
    }
    echo "\n";
}
?>

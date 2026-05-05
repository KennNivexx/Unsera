<?php
require 'db.php';
$result = $conn->query("SHOW COLUMNS FROM dosen");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
echo "--- Histories ---\n";
$tables = ['status_dosen_riwayat', 'homebase_dosen_riwayat', 'unit_kerja_dosen_riwayat', 'reward', 'punishment', 'jabfung_dosen', 'lldikti_dosen', 'yayasan_dosen', 'pendidikan_dosen', 'sertifikasi_dosen'];
foreach($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if($res->num_rows > 0) {
        echo "$t: EXISTS\n";
    } else {
        echo "$t: MISSING\n";
    }
}
?>

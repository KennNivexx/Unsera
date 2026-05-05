<?php
require __DIR__ . '/../htdocs/db.php';
$res = $conn->query("SHOW COLUMNS FROM status_dosen_riwayat");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "----\n";
$res = $conn->query("SHOW COLUMNS FROM jabfung_dosen");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

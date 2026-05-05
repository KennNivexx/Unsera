<?php
require __DIR__ . '/../htdocs/db.php';
$res = $conn->query("SHOW COLUMNS FROM dosen");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
echo "----\n";
$res = $conn->query("SHOW COLUMNS FROM pegawai");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

<?php
require __DIR__ . '/../htdocs/db.php';
$res = $conn->query("SHOW COLUMNS FROM yayasan_pegawai");
if (!$res) die($conn->error);
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

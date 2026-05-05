<?php
require 'db.php';
echo "--- status_dosen_riwayat ---\n";
$res = $conn->query("SHOW COLUMNS FROM status_dosen_riwayat");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>

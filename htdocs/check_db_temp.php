<?php
require 'db.php';
$res = $conn->query("SHOW COLUMNS FROM dosen");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

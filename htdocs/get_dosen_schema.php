<?php
require 'db.php';
$r = $conn->query("DESCRIBE dosen");
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>

<?php
require 'db.php';
header('Content-Type: text/plain');
foreach(['dosen', 'pegawai'] as $table) {
    echo "Table: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  Error: " . $conn->error . "\n";
    }
    echo "\n";
}

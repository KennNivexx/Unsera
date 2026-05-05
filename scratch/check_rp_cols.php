<?php
require __DIR__ . '/../htdocs/db.php';
$res = $conn->query("SHOW COLUMNS FROM reward");
if (!$res) die($conn->error);
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "----\n";
$res = $conn->query("SHOW COLUMNS FROM punishment");
if (!$res) die($conn->error);
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

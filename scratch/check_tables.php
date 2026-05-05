<?php
require __DIR__ . '/../htdocs/db.php';
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}

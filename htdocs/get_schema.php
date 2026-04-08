<?php
require 'db.php';
$r = $conn->query("DESCRIBE pegawai");
$out = "";
while($row = $r->fetch_assoc()) {
    $out .= $row['Field'] . " - " . $row['Type'] . PHP_EOL;
}
file_put_contents('schema_pegawai.txt', $out);
echo "Schema written to schema_pegawai.txt";
?>

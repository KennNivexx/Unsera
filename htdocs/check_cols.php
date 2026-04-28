<?php
require 'db.php';
$res = $conn->query("SHOW COLUMNS FROM dosen");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
file_put_contents('dosen_cols.txt', implode("\n", $cols));

$res_peg = $conn->query("SHOW COLUMNS FROM pegawai");
$cols_peg = [];
while($row = $res_peg->fetch_assoc()) {
    $cols_peg[] = $row['Field'];
}
file_put_contents('pegawai_cols.txt', implode("\n", $cols_peg));
?>

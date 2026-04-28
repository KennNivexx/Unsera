<?php
require 'db.php';
$res = mysqli_query($conn, 'DESCRIBE admin');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

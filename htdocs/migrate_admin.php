<?php
require 'db.php';

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM admin LIKE 'jenis_kelamin'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN jenis_kelamin VARCHAR(20) DEFAULT 'Laki-laki'");
    echo "<div style='color:green'>SUCCESS: Added column jenis_kelamin</div>";
} else {
    echo "<div style='color:blue'>INFO: Column jenis_kelamin already exists</div>";
}

$conn->query("UPDATE admin SET jenis_kelamin = 'Laki-laki' WHERE jenis_kelamin IS NULL");
echo "<div style='color:green'>SUCCESS: Updated NULL values</div>";
?>
<br>
<a href="login.php">Kembali ke Login</a>

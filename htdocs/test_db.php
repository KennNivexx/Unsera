<?php
// DIAGNOSTIC FILE - Upload to InfinityFree, open in browser, then DELETE immediately
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>1. PHP OK</h2>";

echo "<h2>2. Testing DB Connection...</h2>";

// CHANGE THESE TO YOUR INFINITYFREE CREDENTIALS
$host = "localhost"; // or sql123.infinityfree.com etc
$user = "root";
$pass = "";
$db   = "db_unseraa_univ";

echo "Host: $host<br>User: $user<br>DB: $db<br><br>";

$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    echo "<p style='color:red;font-size:20px;'>CONNECTION FAILED: " . mysqli_connect_error() . "</p>";
} else {
    echo "<p style='color:green;font-size:20px;'>CONNECTION SUCCESS!</p>";
    
    echo "<h2>3. Tables in database:</h2>";
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<ul>";
        while ($row = $result->fetch_row()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        echo "<p>Total: " . $result->num_rows . " tables</p>";
    } else {
        echo "<p style='color:red;'>Cannot list tables: " . $conn->error . "</p>";
    }
    
    echo "<h2>4. Quick data check:</h2>";
    $r = $conn->query("SELECT COUNT(*) as c FROM dosen");
    if ($r) {
        $row = $r->fetch_assoc();
        echo "Dosen count: " . $row['c'] . "<br>";
    } else {
        echo "<p style='color:red;'>Dosen query failed: " . $conn->error . "</p>";
    }
    
    $r = $conn->query("SELECT COUNT(*) as c FROM pegawai");
    if ($r) {
        $row = $r->fetch_assoc();
        echo "Pegawai count: " . $row['c'] . "<br>";
    } else {
        echo "<p style='color:red;'>Pegawai query failed: " . $conn->error . "</p>";
    }
}

echo "<h2>5. PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Client: " . (function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'N/A') . "<br>";

echo "<hr><p style='color:red;'><b>DELETE THIS FILE AFTER TESTING!</b></p>";
?>

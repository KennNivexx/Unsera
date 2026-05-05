<?php

// Suppress all PHP errors from being output inline (critical for shared hosting like InfinityFree)
error_reporting(0);
@ini_set('display_errors', '0');

// Suppress MySQLi exceptions - handle failures gracefully
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

// Auto-detect environment
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'page.gd') !== false || strpos($_SERVER['SERVER_NAME'] ?? '', 'infinityfree') !== false) {
    // InfinityFree Production
    $host = "sql107.infinityfree.com";
    $user = "if0_41391134";
    $pass = "Y46I85hdjRO";
    $db   = "if0_41391134_unsera";
} else {
    // Local Development (Laragon)
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "db_unseraa_univ";
}

$conn = mysqli_connect($host,$user,$pass,$db);

if($conn){
    mysqli_query($conn, "SET time_zone = '+07:00'");
}

if(!$conn){
    die("Koneksi gagal");
}


// ============================================================
// Note: Migration logic removed to improve performance on production.
// Tables and columns are assumed to be correctly created via SQL import.
$_SESSION['schema_checked'] = true;



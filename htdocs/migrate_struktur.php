<?php
require 'db.php';

$db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0];

function addColumnIfNotExists($conn, $table, $column, $definition, $db_name) {
    $check = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table' AND COLUMN_NAME='$column'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] == 0) {
        $sql = "ALTER TABLE $table ADD COLUMN $column $definition";
        if ($conn->query($sql)) {
            echo "✅ Added <strong>$column</strong> to $table<br>";
        } else {
            echo "❌ Error adding $column: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Column <strong>$column</strong> already exists in $table, skipped.<br>";
    }
}

addColumnIfNotExists($conn, 'struktur_organisasi', 'parent_id', 'INT DEFAULT NULL', $db_name);
addColumnIfNotExists($conn, 'struktur_organisasi', 'urutan',    'INT DEFAULT 0',    $db_name);

echo "<br><strong>Migration selesai!</strong><br><a href='struktur_organisasi.php'>Kembali ke Struktur Organisasi</a>";

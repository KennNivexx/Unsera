<?php
require 'db.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$value = $_GET['value'] ?? '';
$exclude_id = intval($_GET['exclude_id'] ?? 0);

if(empty($type) || empty($value)) {
    echo json_encode(['exists' => false]);
    exit;
}

$column = '';
if($type === 'nip') $column = 'nip';
elseif($type === 'nidn') $column = 'nidn';
elseif($type === 'nuptk') $column = 'nuptk';

if($column) {
    $value = $conn->real_escape_string($value);
    $sql = "SELECT nama_lengkap FROM dosen WHERE $column='$value'";
    if($exclude_id > 0) {
        $sql .= " AND id != $exclude_id";
    }
    $sql .= " LIMIT 1";
    $q = $conn->query($sql);
    if($q && $q->num_rows > 0) {
        $row = $q->fetch_assoc();
        echo json_encode(['exists' => true, 'name' => $row['nama_lengkap']]);
        exit;
    }
}

echo json_encode(['exists' => false]);

<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$filename = "Data_Dosen_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$data = $conn->query("SELECT * FROM dosen ORDER BY nama_lengkap ASC");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"></head>';
echo '<body>';
echo '<table border="1">';
echo '<tr>';
echo '<th style="background-color: #3b82f6; color: white;">No</th>';
echo '<th style="background-color: #3b82f6; color: white;">Nama Lengkap</th>';
echo '<th style="background-color: #3b82f6; color: white;">Alamat</th>';
echo '<th style="background-color: #3b82f6; color: white;">Tempat Lahir</th>';
echo '<th style="background-color: #3b82f6; color: white;">Tanggal Lahir</th>';
echo '<th style="background-color: #3b82f6; color: white;">NIP</th>';
echo '<th style="background-color: #3b82f6; color: white;">NIDN</th>';
echo '<th style="background-color: #3b82f6; color: white;">NUPTK</th>';
echo '<th style="background-color: #3b82f6; color: white;">Status Dosen</th>';
echo '<th style="background-color: #3b82f6; color: white;">Status Keaktifan</th>';
echo '<th style="background-color: #3b82f6; color: white;">Jenis Penugasan</th>';
echo '<th style="background-color: #3b82f6; color: white;">Homebase Prodi</th>';
echo '<th style="background-color: #3b82f6; color: white;">Unit Kerja</th>';
echo '<th style="background-color: #3b82f6; color: white;">Jabatan Terakhir</th>';
echo '</tr>';

$no = 1;
while($row = $data->fetch_assoc()) {
    echo '<tr>';
    echo '<td>'.$no++.'</td>';
    echo '<td>'.$row['nama_lengkap'].'</td>';
    echo '<td>'.$row['alamat'].'</td>';
    echo '<td>'.$row['ttl_tempat'].'</td>';
    echo '<td>'.$row['ttl_tanggal'].'</td>';
    // Gunakan strval dan prepend petik tunggal atau simpan sebagai raw text di excel
    echo '<td style="mso-number-format:\'@\';">'.$row['nip'].'</td>';
    echo '<td style="mso-number-format:\'@\';">'.$row['nidn'].'</td>';
    echo '<td style="mso-number-format:\'@\';">'.$row['nuptk'].'</td>';
    echo '<td>'.$row['status_dosen'].'</td>';
    echo '<td>'.($row['status_keaktifan'] ?? 'Aktif').'</td>';
    echo '<td>'.$row['jenis_dosen'].'</td>';
    echo '<td>'.$row['homebase_prodi'].'</td>';
    echo '<td>'.$row['unit_kerja'].'</td>';
    echo '<td>'.$row['jabfung_akademik'].'</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body></html>';
?>

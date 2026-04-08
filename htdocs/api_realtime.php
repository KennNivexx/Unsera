<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'stats';

if ($action === 'stats') {
    $totalDosen    = $conn->query("SELECT COUNT(*) as c FROM dosen")->fetch_assoc()['c'];
    $dosenTetap    = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='tetap'")->fetch_assoc()['c'];
    $dosenTidakTetap = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='tidak tetap'")->fetch_assoc()['c'];
    $dosenHomebase = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='homebase'")->fetch_assoc()['c'];

    $totalPegawai      = $conn->query("SELECT COUNT(*) as c FROM pegawai")->fetch_assoc()['c'];
    $pegawaiTetap      = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE jenis_pegawai='tetap'")->fetch_assoc()['c'];
    $pegawaiTidakTetap = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE jenis_pegawai='tdk tetap'")->fetch_assoc()['c'];

    // Jabfung breakdown per jenis
    $jabfungBreakdown = [];
    $dosenRows = $conn->query("SELECT id, jabfung_akademik FROM dosen");
    if ($dosenRows) {
        while($row = $dosenRows->fetch_assoc()) {
            $did = $row['id'];
            $j = trim($row['jabfung_akademik'] ?? '');
            $qj = $conn->query("SELECT jabatan FROM jabfung_dosen WHERE dosen_id=$did ORDER BY tmt DESC, id DESC LIMIT 1");
            if($qj && $rj = $qj->fetch_assoc()) {
                if (trim($rj['jabatan']) !== '') $j = trim($rj['jabatan']);
            }
            if(!empty($j) && $j !== '-') {
                $jabfungBreakdown[$j] = ($jabfungBreakdown[$j] ?? 0) + 1;
            }
        }
    }
    arsort($jabfungBreakdown);

    $golLldikti = $golLldikti_tmt = '-';
    $maxId = 0;
    $q1 = $conn->query("SELECT id, gol_lldikti, tmt_gol_lldikti FROM dosen WHERE gol_lldikti!='' AND gol_lldikti!='-' ORDER BY id DESC LIMIT 1");
    if ($q1 && $r1 = $q1->fetch_assoc()) {
        $maxId = $r1['id'];
        $golLldikti = $r1['gol_lldikti'];
        $golLldikti_tmt = $r1['tmt_gol_lldikti'] ? date('d M Y', strtotime($r1['tmt_gol_lldikti'])) : '-';
    }
    $q2 = $conn->query("SELECT id, golongan, tmt FROM lldikti_dosen WHERE golongan!='' AND golongan!='-' ORDER BY id DESC LIMIT 1");
    if ($q2 && $r2 = $q2->fetch_assoc()) {
        if ($r2['id'] > $maxId) {
            $golLldikti = $r2['golongan'];
            $golLldikti_tmt = $r2['tmt'] ? date('d M Y', strtotime($r2['tmt'])) : '-';
        }
    }

    $golYayasan = $golYayasan_tmt = '-';
    $maxYId = 0;
    $q3 = $conn->query("SELECT id, gol_yayasan, tmt_gol_yayasan FROM dosen WHERE gol_yayasan!='' AND gol_yayasan!='-' ORDER BY id DESC LIMIT 1");
    if ($q3 && $r3 = $q3->fetch_assoc()) {
        $maxYId = $r3['id'];
        $golYayasan = $r3['gol_yayasan'];
        $golYayasan_tmt = $r3['tmt_gol_yayasan'] ? date('d M Y', strtotime($r3['tmt_gol_yayasan'])) : '-';
    }
    $q4 = $conn->query("SELECT id, golongan, tmt FROM yayasan_dosen WHERE golongan!='' AND golongan!='-' ORDER BY id DESC LIMIT 1");
    if ($q4 && $r4 = $q4->fetch_assoc()) {
        if ($r4['id'] > $maxYId) {
            $maxYId = $r4['id'];
            $golYayasan = $r4['golongan'];
            $golYayasan_tmt = $r4['tmt'] ? date('d M Y', strtotime($r4['tmt'])) : '-';
        }
    }
    $q5 = $conn->query("SELECT id, golongan, tmt FROM yayasan_pegawai WHERE golongan!='' AND golongan!='-' ORDER BY id DESC LIMIT 1");
    if ($q5 && $r5 = $q5->fetch_assoc()) {
        if ($r5['id'] > $maxYId) {
            $golYayasan = $r5['golongan'];
            $golYayasan_tmt = $r5['tmt'] ? date('d M Y', strtotime($r5['tmt'])) : '-';
        }
    }

    echo json_encode([
        'totalDosen'        => $totalDosen,
        'dosenTetap'        => $dosenTetap,
        'dosenTidakTetap'   => $dosenTidakTetap,
        'dosenHomebase'     => $dosenHomebase,
        'totalPegawai'      => $totalPegawai,
        'pegawaiTetap'      => $pegawaiTetap,
        'pegawaiTidakTetap' => $pegawaiTidakTetap,
        'jabfungBreakdown'  => $jabfungBreakdown,
        'golLldikti'        => $golLldikti,
        'golLldikti_tmt'    => $golLldikti_tmt,
        'golYayasan'        => $golYayasan,
        'golYayasan_tmt'    => $golYayasan_tmt,
        'timestamp'         => date('H:i:s') . ' WIB',
    ]);

} elseif ($action === 'dosen_list') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $jabfung = $_GET['jabfung'] ?? '';
    
    $where_clauses = [];
    if ($search) {
        $s = $conn->real_escape_string($search);
        $where_clauses[] = "(nama_lengkap LIKE '%$s%' OR status_dosen LIKE '%$s%')";
    }
    if ($status) {
        $s_status = $conn->real_escape_string($status);
        $where_clauses[] = "status_dosen = '$s_status'";
    }
    if ($jabfung) {
        $s_jabfung = $conn->real_escape_string($jabfung);
        $where_clauses[] = "jabfung_akademik = '$s_jabfung'";
    }
    
    $where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
    
    $data = $conn->query("SELECT id, nama_lengkap, status_dosen, homebase_prodi, no_serdos, foto_profil FROM dosen$where ORDER BY id DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'pegawai_list') {
    $search = $_GET['search'] ?? '';
    $where  = '';
    if ($search) {
        $s = $conn->real_escape_string($search);
        $where = " WHERE nama_lengkap LIKE '%$s%' OR jenis_pegawai LIKE '%$s%'";
    }
    $data = $conn->query("SELECT id, nama_lengkap, jenis_pegawai, posisi_jabatan, unit_kerja, riwayat_pendidikan, foto_profil FROM pegawai$where ORDER BY id DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'surat_list') {
    $jenis_id = (int)($_GET['jenis_id'] ?? 0);
    if (!$jenis_id) { echo json_encode(['rows' => []]); exit; }
    $data = $conn->query("SELECT * FROM data_surat WHERE jenis_id=$jenis_id ORDER BY tanggal DESC, id DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'jenis_surat_list') {
    $data = $conn->query("SELECT js.*, (SELECT COUNT(*) FROM data_surat WHERE jenis_id = js.id) as total_surat FROM jenis_surat js ORDER BY js.id ASC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
?>

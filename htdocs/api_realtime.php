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
    $q_td = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif'");
    $totalDosen = ($q_td) ? $q_td->fetch_assoc()['c'] : 0;
    
    $q_dt = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif' AND LOWER(status_dosen)='tetap'");
    $dosenTetap = ($q_dt) ? $q_dt->fetch_assoc()['c'] : 0;
    
    $q_dtt = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif' AND LOWER(status_dosen)='tidak tetap'");
    $dosenTidakTetap = ($q_dtt) ? $q_dtt->fetch_assoc()['c'] : 0;
    
    $q_dh = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif' AND LOWER(status_dosen)='homebase'");
    $dosenHomebase = ($q_dh) ? $q_dh->fetch_assoc()['c'] : 0;

    $q_tp = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE status_keaktifan != 'Tidak Aktif'");
    $totalPegawai = ($q_tp) ? $q_tp->fetch_assoc()['c'] : 0;
    
    $q_pt = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE status_keaktifan != 'Tidak Aktif' AND (LOWER(status_pegawai)='tetap' OR LOWER(jenis_pegawai)='tetap')");
    $pegawaiTetap = ($q_pt) ? $q_pt->fetch_assoc()['c'] : 0;
    
    $q_ptt = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE status_keaktifan != 'Tidak Aktif' AND (LOWER(status_pegawai)='tidak tetap' OR LOWER(status_pegawai)='tdk tetap' OR LOWER(jenis_pegawai)='tidak tetap' OR LOWER(jenis_pegawai)='tdk tetap')");
    $pegawaiTidakTetap = ($q_ptt) ? $q_ptt->fetch_assoc()['c'] : 0;


    // Optimized Jabfung breakdown: Get latest jabfung for each dosen and count them
    $jabfungBreakdown = [];
    $jabfungQuery = "SELECT jabatan, COUNT(*) as count 
                    FROM (
                        SELECT d.id, COALESCE(
                            (SELECT jd.jabatan FROM jabfung_dosen jd WHERE jd.dosen_id = d.id ORDER BY jd.tmt DESC, jd.id DESC LIMIT 1),
                            d.jabfung_akademik
                        ) as jabatan
                        FROM dosen d
                        WHERE d.status_keaktifan != 'Tidak Aktif'
                    ) as latest_jabfungs
                    WHERE jabatan IS NOT NULL AND jabatan != '' AND jabatan != '-'
                    GROUP BY jabatan 
                    ORDER BY count DESC";
    $resJab = $conn->query($jabfungQuery);
    if ($resJab) {
        while($row = $resJab->fetch_assoc()) {
            $jabfungBreakdown[$row['jabatan']] = (int)$row['count'];
        }
    }

    $golLldikti = $golLldikti_tmt = '-';
    $maxId = 0;
    $q1 = $conn->query("SELECT id, gol_lldikti, tmt_gol_lldikti FROM dosen WHERE gol_lldikti!='' AND gol_lldikti!='-' ORDER BY id DESC LIMIT 1");
    if ($q1 && $r1 = $q1->fetch_assoc()) {
        $maxId = $r1['id'];
        $golLldikti = $r1['gol_lldikti'];
        $golLldikti_tmt = !empty($r1['tmt_gol_lldikti']) ? date('d M Y', strtotime($r1['tmt_gol_lldikti'])) : '-';
    }
    $q2 = $conn->query("SELECT id, golongan, tmt FROM lldikti_dosen WHERE golongan!='' AND golongan!='-' ORDER BY id DESC LIMIT 1");
    if ($q2 && $r2 = $q2->fetch_assoc()) {
        if ($r2['id'] > $maxId) {
            $golLldikti = $r2['golongan'];
            $golLldikti_tmt = !empty($r2['tmt']) ? date('d M Y', strtotime($r2['tmt'])) : '-';
        }
    }

    $golYayasan = $golYayasan_tmt = '-';
    $maxYId = 0;
    $q3 = $conn->query("SELECT id, gol_yayasan, tmt_gol_yayasan FROM dosen WHERE gol_yayasan!='' AND gol_yayasan!='-' ORDER BY id DESC LIMIT 1");
    if ($q3 && $r3 = $q3->fetch_assoc()) {
        $maxYId = $r3['id'];
        $golYayasan = $r3['gol_yayasan'];
        $golYayasan_tmt = !empty($r3['tmt_gol_yayasan']) ? date('d M Y', strtotime($r3['tmt_gol_yayasan'])) : '-';
    }
    $q4 = $conn->query("SELECT id, golongan, tmt FROM yayasan_dosen WHERE golongan!='' AND golongan!='-' ORDER BY id DESC LIMIT 1");
    if ($q4 && $r4 = $q4->fetch_assoc()) {
        if ($r4['id'] > $maxYId) {
            $maxYId = $r4['id'];
            $golYayasan = $r4['golongan'];
            $golYayasan_tmt = !empty($r4['tmt']) ? date('d M Y', strtotime($r4['tmt'])) : '-';
        }
    }
    $q5 = $conn->query("SELECT id, golongan, tmt FROM yayasan_pegawai WHERE golongan!='' AND golongan!='-' ORDER BY id DESC LIMIT 1");
    if ($q5 && $r5 = $q5->fetch_assoc()) {
        if ($r5['id'] > $maxYId) {
            $golYayasan = $r5['golongan'];
            $golYayasan_tmt = !empty($r5['tmt']) ? date('d M Y', strtotime($r5['tmt'])) : '-';
        }
    }

    // Status Keaktifan (Combined)
    $dosenAktif = $conn->query("SELECT COUNT(*) as count FROM dosen WHERE status_keaktifan='Aktif'")->fetch_assoc()['count'];
    $dosenTidakAktif = $conn->query("SELECT COUNT(*) as count FROM dosen WHERE status_keaktifan='Tidak Aktif'")->fetch_assoc()['count'];
    $pegawaiAktif = $conn->query("SELECT COUNT(*) as count FROM pegawai WHERE status_keaktifan='Aktif'")->fetch_assoc()['count'];
    $pegawaiTidakAktif = $conn->query("SELECT COUNT(*) as count FROM pegawai WHERE status_keaktifan='Tidak Aktif'")->fetch_assoc()['count'];

    $totalAktif = (int)$dosenAktif + (int)$pegawaiAktif;
    $totalTidakAktif = (int)$dosenTidakAktif + (int)$pegawaiTidakAktif;

    // Education Aggregation
    $eduDosen = [];
    $resEduD = $conn->query("SELECT jenjang, COUNT(*) as count FROM pendidikan_dosen GROUP BY jenjang");
    if ($resEduD) {
        while($row = $resEduD->fetch_assoc()) {
            $j = strtoupper(trim($row['jenjang']));
            if ($j === '' || $j === '-') continue;
            $eduDosen[$j] = (int)$row['count'];
        }
    }
    arsort($eduDosen);

    $eduPegawai = [];
    $resEduP = $conn->query("SELECT jenjang, COUNT(*) as count FROM pendidikan_pegawai GROUP BY jenjang");
    if ($resEduP) {
        while($row = $resEduP->fetch_assoc()) {
            $j = strtoupper(trim($row['jenjang']));
            if ($j === '' || $j === '-') continue;
            $eduPegawai[$j] = (int)$row['count'];
        }
    }
    arsort($eduPegawai);

    echo json_encode([
        'totalDosen'        => $totalDosen,
        'dosenTetap'        => $dosenTetap,
        'dosenTidakTetap'   => $dosenTidakTetap,
        'dosenHomebase'     => $dosenHomebase,
        'totalPegawai'      => $totalPegawai,
        'pegawaiTetap'      => $pegawaiTetap,
        'pegawaiTidakTetap' => $pegawaiTidakTetap,
        'totalAktif'        => $totalAktif,
        'totalTidakAktif'   => $totalTidakAktif,
        'jabfungBreakdown'  => $jabfungBreakdown,
        'eduDosen'         => $eduDosen,
        'eduPegawai'       => $eduPegawai,
        'timestamp'         => date('H:i:s') . ' WIB',
    ]);

} elseif ($action === 'dosen_list') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $jabfung = $_GET['jabfung'] ?? '';
    
    $where_clauses = ["status_keaktifan != 'Tidak Aktif'"];
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
    
    $where = " WHERE " . implode(" AND ", $where_clauses);
    
    $data = $conn->query("SELECT id, nama_lengkap, status_dosen, jenis_dosen, jabatan_struktural, homebase_prodi, no_serdos, foto_profil FROM dosen$where ORDER BY id DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) {
        // Fetch status riwayat for each dosen
        $riwayat = [];
        $rw_result = $conn->query("SELECT status_dosen, tmt, dokumen, 'kepegawaian' as type FROM status_dosen_riwayat WHERE dosen_id = {$r['id']} ORDER BY tmt DESC, id DESC");
        if ($rw_result) {
            while ($rw = $rw_result->fetch_assoc()) $riwayat[] = $rw;
        }

        // Fetch penugasan riwayat
        $rw_pen = $conn->query("SELECT jenis_dosen as status_dosen, tmt, dokumen, 'penugasan' as type, jabatan_struktural FROM penugasan_dosen_riwayat WHERE dosen_id = {$r['id']} ORDER BY tmt DESC, id DESC");
        if ($rw_pen) {
            while ($rw = $rw_pen->fetch_assoc()) {
                $riwayat[] = $rw;
            }
        }

        // Sort combined riwayat by TMT
        usort($riwayat, function($a, $b) {
            return strtotime($b['tmt'] ?? '1970-01-01') - strtotime($a['tmt'] ?? '1970-01-01');
        });

        $r['status_riwayat'] = $riwayat;
        $rows[] = $r;
    }
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'pegawai_list') {
    $status = $_GET['status'] ?? '';
    
    $where_clauses = ["status_keaktifan != 'Tidak Aktif'"];
    if ($search) {
        $s = $conn->real_escape_string($search);
        $where_clauses[] = "(nama_lengkap LIKE '%$s%' OR status_pegawai LIKE '%$s%')";
    }
    if ($status) {
        $s_status = $conn->real_escape_string($status);
        $where_clauses[] = "status_pegawai = '$s_status'";
    }
    $where = " WHERE " . implode(" AND ", $where_clauses);
    $data = $conn->query("SELECT id, nama_lengkap, status_pegawai, posisi_jabatan, unit_kerja, riwayat_pendidikan, foto_profil FROM pegawai$where ORDER BY id DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) {
        $id_peg = $r['id'];
        $riw = [];
        $res_riw = $conn->query("SELECT status_pegawai as status_dosen, tmt as tmt, dokumen FROM status_pegawai_riwayat WHERE pegawai_id = $id_peg ORDER BY tmt DESC, id DESC");
        if ($res_riw) {
            while($rw = $res_riw->fetch_assoc()) $riw[] = $rw;
        }
        $r['status_riwayat'] = $riw;
        $rows[] = $r;
    }
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'surat_list') {
    $jenis_id = (int)($_GET['jenis_id'] ?? 0);
    if (!$jenis_id) { echo json_encode(['rows' => []]); exit; }
    $data = $conn->query("SELECT * FROM data_surat WHERE jenis_id=$jenis_id ORDER BY tanggal ASC, id ASC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'jenis_surat_list') {
    $data = $conn->query("SELECT js.*, (SELECT COUNT(*) FROM data_surat WHERE jenis_id = js.id) as total_surat FROM jenis_surat js ORDER BY js.id ASC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'tidak_aktif_dosen') {
    $data = $conn->query("SELECT id, nama_lengkap, nip, nidn, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja, foto_profil 
                          FROM dosen WHERE status_keaktifan = 'Tidak Aktif' OR status_dosen = 'Tidak Aktif' ORDER BY tgl_mulai_tidak_bekerja DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'tidak_aktif_pegawai') {
    $data = $conn->query("SELECT id, nama_lengkap, posisi_jabatan, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja as tmtk, tmt_tidak_kerja, unit_kerja, foto_profil 
                          FROM pegawai WHERE status_keaktifan = 'Tidak Aktif' OR tmt_tidak_kerja IS NOT NULL ORDER BY tmt_tidak_kerja DESC");
    $rows = [];
    while ($r = $data->fetch_assoc()) $rows[] = $r;
    echo json_encode(['rows' => $rows, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'struktur_list') {
    $roots = [];
    $rootsResult = $conn->query("SELECT * FROM struktur_organisasi WHERE parent_id IS NULL ORDER BY id ASC");
    if ($rootsResult) {
        while ($r = $rootsResult->fetch_assoc()) $roots[] = $r;
    }
    $allChildren = [];
    $childResult = $conn->query("SELECT * FROM struktur_organisasi WHERE parent_id IS NOT NULL ORDER BY id ASC");
    if ($childResult) {
        while ($row = $childResult->fetch_assoc()) {
            $allChildren[$row['parent_id']][] = $row;
        }
    }
    echo json_encode(['roots' => $roots, 'children' => $allChildren, 'timestamp' => date('H:i:s') . ' WIB']);

} elseif ($action === 'update_sub_item') {
    $id = (int)($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');
    if ($field === 'nama_jabatan' || $field === 'nama_pejabat') {
        $stmt = $conn->prepare("UPDATE struktur_organisasi SET $field = ? WHERE id = ?");
        $stmt->bind_param("si", $value, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid field']);
    }

} elseif ($action === 'delete_sub_item') {
    $id = (int)($_POST['id'] ?? 0);
    $conn->query("DELETE FROM struktur_organisasi WHERE id = $id");
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
?>


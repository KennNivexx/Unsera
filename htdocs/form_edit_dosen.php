<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: daftar_dosen.php');
    exit;
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM dosen WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header('Location: daftar_dosen.php');
    exit;
}

// Fetch histories
$rewards             = ($q = $conn->query("SELECT * FROM reward WHERE dosen_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$punishments         = ($q = $conn->query("SELECT * FROM punishment WHERE dosen_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$jabfungs            = ($q = $conn->query("SELECT * FROM jabfung_dosen WHERE dosen_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$lldiktis            = ($q = $conn->query("SELECT * FROM lldikti_dosen WHERE dosen_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$yayasans            = ($q = $conn->query("SELECT * FROM yayasan_dosen WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$pendidikans         = ($q = $conn->query("SELECT * FROM pendidikan_dosen WHERE dosen_id = $id ORDER BY tahun_lulus DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$status_riwayats     = ($q = $conn->query("SELECT * FROM status_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$status_dosens       = $status_riwayats;
$homebase_riwayats   = ($q = $conn->query("SELECT * FROM homebase_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$unit_kerja_riwayats = ($q = $conn->query("SELECT * FROM unit_kerja_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$serdoses            = ($q = $conn->query("SELECT * FROM sertifikasi_dosen WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$gols_dosen          = ['III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d'];


// Ensure all columns exist
$cols = [
    'dok_status_dosen' => "VARCHAR(255) DEFAULT ''",
    'dok_tmb' => "VARCHAR(255) DEFAULT ''",
    'dok_berhenti_bertugas' => "VARCHAR(255) DEFAULT ''",
    'dok_penugasan_struktural' => "VARCHAR(255) DEFAULT ''",
    'tgl_mulai_tidak_bekerja' => "DATE DEFAULT NULL",
    'ket_tidak_kerja' => "TEXT DEFAULT NULL",
    'dok_tidak_kerja' => "VARCHAR(255) DEFAULT ''"
];
foreach($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM dosen LIKE '$col'");
    if($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE dosen ADD COLUMN $col $def");
    }
}

// Ensure new history tables exist
$conn->query("CREATE TABLE IF NOT EXISTS homebase_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    homebase_prodi VARCHAR(255),
    tmt DATE,
    dokumen VARCHAR(255)
)");
$conn->query("CREATE TABLE IF NOT EXISTS unit_kerja_dosen_riwayat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    unit_kerja VARCHAR(255),
    tmt DATE,
    dokumen VARCHAR(255)
)");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? $id;
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'];
    $ttl_tanggal = $_POST['ttl_tanggal'];
    $nip = !empty($_POST['nip']) ? trim($_POST['nip']) : null;
    $nidn = !empty($_POST['nidn']) ? trim($_POST['nidn']) : null;
    $nuptk = !empty($_POST['nuptk']) ? trim($_POST['nuptk']) : null;

    $check_query = "SELECT id FROM dosen WHERE (nip = ? OR nidn = ? OR nuptk = ?) AND id != ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("sssi", $nip, $nidn, $nuptk, $id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo "<script>alert('Gagal! NIP/NIDN/NUPTK sudah terdaftar digunakan oleh dosen lain.');history.back();</script>";
        exit;
    }
    $stmt_check->close();

    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jenis_dosen = $_POST['jenis_dosen'] ?? '';
    $jabatan_struktural = $_POST['jabatan_struktural'] ?? '';
    $tmk = !empty($_POST['tmk']) ? $_POST['tmk'] : null;
    $tmtk = !empty($_POST['tmtk']) ? $_POST['tmtk'] : null;
    $ket_tidak_kerja = $_POST['ket_tidak_kerja'] ?? '';
    $status_keaktifan_utama = $_POST['status_utama'] ?? 'Aktif';
    $status_keaktifan_sub = $_POST['status_keaktifan'] ?? '-';
    $tgl_mulai_tidak_bekerja = !empty($_POST['tgl_mulai_tidak_bekerja']) ? $_POST['tgl_mulai_tidak_bekerja'] : null;
    
    $keterangan_keaktifan = ($status_keaktifan_sub === 'Lainnya') ? ($_POST['ket_tidak_aktif_lainnya'] ?? 'Lainnya') : $status_keaktifan_sub;
    $status_keaktifan = $status_keaktifan_utama;

    $status_list = [];
    if(!empty($_POST['status_dosen'])) {
        foreach($_POST['status_dosen'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status'][$i]) ? $_POST['tmt_status'][$i] : null;
                $tgl_berhenti = !empty($_POST['tgl_berhenti_status'][$i]) ? $_POST['tgl_berhenti_status'][$i] : null;
                $alasan = !empty($_POST['alasan_berhenti_status'][$i]) ? $_POST['alasan_berhenti_status'][$i] : null;
                $alasan_lain = ($alasan === 'Dan Lainnya') ? ($_POST['alasan_lainnya_status'][$i] ?? '') : null;
                $filename = $_POST['existing_dok_status'][$i] ?? '';
                if(!empty($_FILES['dok_status']['name'][$i])) {
                    $filename = 'uploads/'.time().'_status_'.basename($_FILES['dok_status']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status']['tmp_name'][$i], $filename);
                }
                $status_list[] = ['status' => $std, 'tmt' => $tmt, 'tgl_berhenti' => $tgl_berhenti, 'alasan' => $alasan, 'alasan_lainnya' => $alasan_lain, 'dokumen' => $filename];
            }
        }
    }
    // Determine main status (latest by TMT)
    usort($status_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $status_dosen_main = $status_list[0]['status'] ?? ($data['status_dosen'] ?? '');
    $jenis_dosen = $status_dosen_main;

    $jabfung_list = [];
    if(!empty($_POST['jabfung_akademik'])) {
        foreach($_POST['jabfung_akademik'] as $i => $jab) {
            if(trim($jab) !== '') {
                $tmt = !empty($_POST['tmt_jabfung'][$i]) ? $_POST['tmt_jabfung'][$i] : null;
                $filename = $_POST['existing_dok_jabfung'][$i] ?? '';
                if(!empty($_FILES['dok_jabfung']['name'][$i])) {
                    $filename = 'uploads/'.time().'_jf_'.basename($_FILES['dok_jabfung']['name'][$i]);
                    move_uploaded_file($_FILES['dok_jabfung']['tmp_name'][$i], $filename);
                }
                $jabfung_list[] = ['jabatan' => $jab, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    // Determine main position (latest by TMT)
    usort($jabfung_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $jabfung_akademik = $jabfung_list[0]['jabatan'] ?? ($data['jabatan_akademik'] ?? '');
    $tmt_jabfung = $jabfung_list[0]['tmt'] ?? ($data['tmt_jabfung'] ?? null);
    $dok_jabfung = $jabfung_list[0]['dokumen'] ?? ($data['dok_jabfung'] ?? '');

    $pendidikan_list = [];
    if(!empty($_POST['pend_jenjang'])) {
        foreach($_POST['pend_jenjang'] as $i => $jenjang) {
            if(trim($jenjang) !== '') {
                $filename = $_POST['existing_dok_pendidikan'][$i] ?? '';
                if(!empty($_FILES['dok_pendidikan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_edu_'.basename($_FILES['dok_pendidikan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_pendidikan']['tmp_name'][$i], $filename);
                }
                $pendidikan_list[] = ['jenjang' => $jenjang, 'institusi' => $_POST['pend_institusi'][$i] ?? '', 'tahun_lulus' => $_POST['pend_tahun'][$i] ?? '', 'dokumen' => $filename];
            }
        }
    }
    // Determine main education (latest by date)
    usort($pendidikan_list, function($a, $b) {
        if (!$a['tahun_lulus']) return 1;
        if (!$b['tahun_lulus']) return -1;
        return strtotime($b['tahun_lulus']) - strtotime($a['tahun_lulus']);
    });
    $riwayat_pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

    $lldikti_list = [];
    if(!empty($_POST['gol_lldikti'])) {
        foreach($_POST['gol_lldikti'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = !empty($_POST['tmt_gol_lldikti'][$i]) ? $_POST['tmt_gol_lldikti'][$i] : null;
                $filename = $_POST['existing_dok_gol_lldikti'][$i] ?? '';
                if(!empty($_FILES['dok_gol_lldikti']['name'][$i])) {
                    $filename = 'uploads/'.time().'_ld_'.basename($_FILES['dok_gol_lldikti']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_lldikti']['tmp_name'][$i], $filename);
                }
                $lldikti_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    $gol_lldikti_main = $lldikti_list[0]['golongan'] ?? '';
    $tmt_gol_lldikti = $lldikti_list[0]['tmt'] ?? null;
    $dok_gol_lldikti = $lldikti_list[0]['dokumen'] ?? ($data['dok_gol_lldikti'] ?? '');

    $yayasan_list = [];
    if(!empty($_POST['gol_yayasan'])) {
        foreach($_POST['gol_yayasan'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = !empty($_POST['tmt_gol_yayasan'][$i]) ? $_POST['tmt_gol_yayasan'][$i] : null;
                $filename = $_POST['existing_dok_gol_yayasan'][$i] ?? '';
                if(!empty($_FILES['dok_gol_yayasan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_yy_'.basename($_FILES['dok_gol_yayasan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_yayasan']['tmp_name'][$i], $filename);
                }
                $yayasan_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    $gol_yayasan_main = $yayasan_list[0]['golongan'] ?? '';
    $tmt_gol_yayasan = $yayasan_list[0]['tmt'] ?? null;
    $dok_gol_yayasan = $yayasan_list[0]['dokumen'] ?? ($data['dok_gol_yayasan'] ?? '');

    $serdos_list = [];
    if(!empty($_POST['no_serdos'])) {
        foreach($_POST['no_serdos'] as $i => $no) {
            if(trim($no) !== '') {
                $tmt = !empty($_POST['tmt_serdos'][$i]) ? $_POST['tmt_serdos'][$i] : null;
                $filename = $_POST['existing_dok_serdos'][$i] ?? '';
                if(!empty($_FILES['dok_serdos']['name'][$i])) {
                    $filename = 'uploads/'.time().'_sd_'.basename($_FILES['dok_serdos']['name'][$i]);
                    move_uploaded_file($_FILES['dok_serdos']['tmp_name'][$i], $filename);
                }
                $serdos_list[] = ['no' => $no, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    $no_serdos = $serdos_list[0]['no'] ?? null;
    $tmt_serdos = $serdos_list[0]['tmt'] ?? null;
    $dok_serdos = $serdos_list[0]['dokumen'] ?? ($data['dok_serdos'] ?? '');

    $homebase_list = [];
    if(!empty($_POST['homebase_prodi_hist'])) {
        foreach($_POST['homebase_prodi_hist'] as $i => $hb) {
            if(trim($hb) !== '') {
                $filename = $_POST['existing_dok_homebase'][$i] ?? '';
                if(!empty($_FILES['dok_homebase']['name'][$i])) {
                    $filename = 'uploads/'.time().'_hb_'.basename($_FILES['dok_homebase']['name'][$i]);
                    move_uploaded_file($_FILES['dok_homebase']['tmp_name'][$i], $filename);
                }
                $homebase_list[] = ['prodi' => $hb, 'tmt' => !empty($_POST['tmt_homebase'][$i]) ? $_POST['tmt_homebase'][$i] : null, 'dokumen' => $filename];
            }
        }
    }
    $homebase_prodi = $homebase_list[0]['prodi'] ?? ($data['homebase_prodi'] ?? '');

    $unit_list = [];
    if(!empty($_POST['unit_kerja_hist'])) {
        foreach($_POST['unit_kerja_hist'] as $i => $uk) {
            if(trim($uk) !== '') {
                $filename = $_POST['existing_dok_unit'][$i] ?? '';
                if(!empty($_FILES['dok_unit']['name'][$i])) {
                    $filename = 'uploads/'.time().'_uk_'.basename($_FILES['dok_unit']['name'][$i]);
                    move_uploaded_file($_FILES['dok_unit']['tmp_name'][$i], $filename);
                }
                $unit_list[] = ['unit' => $uk, 'tmt' => !empty($_POST['tmt_unit'][$i]) ? $_POST['tmt_unit'][$i] : null, 'dokumen' => $filename];
            }
        }
    }
    $unit_kerja = $unit_list[0]['unit'] ?? ($data['unit_kerja'] ?? '');

    $dok_ktp = !empty($_FILES['dok_ktp']['name']) ? ('uploads/'.time().'_ktp_'.basename($_FILES['dok_ktp']['name'])) : ($data['dok_ktp'] ?? '');
    if(!empty($_FILES['dok_ktp']['name'])) move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
    
    $dok_kk = !empty($_FILES['dok_kk']['name']) ? ('uploads/'.time().'_kk_'.basename($_FILES['dok_kk']['name'])) : ($data['dok_kk'] ?? '');
    if(!empty($_FILES['dok_kk']['name'])) move_uploaded_file($_FILES['dok_kk']['tmp_name'], $dok_kk);

    $dok_tidak_kerja = !empty($_FILES['dok_tidak_kerja']['name']) ? ('uploads/'.time().'_tj_'.basename($_FILES['dok_tidak_kerja']['name'])) : ($data['dok_tidak_kerja'] ?? '');
    if(!empty($_FILES['dok_tidak_kerja']['name'])) move_uploaded_file($_FILES['dok_tidak_kerja']['tmp_name'], $dok_tidak_kerja);

    $foto_profil = !empty($_FILES['foto_profil']['name']) ? ('uploads/foto_'.time().'_'.basename($_FILES['foto_profil']['name'])) : ($data['foto_profil'] ?? '');
    if(!empty($_FILES['foto_profil']['name'])) move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);

    $dok_status_dosen = $status_list[0]['dokumen'] ?? ($data['dok_status_dosen'] ?? '');
    $dok_tmb = !empty($_FILES['dok_tmb']['name']) ? ('uploads/'.time().'_tmb_'.basename($_FILES['dok_tmb']['name'])) : ($data['dok_tmb'] ?? '');
    if(!empty($_FILES['dok_tmb']['name'])) move_uploaded_file($_FILES['dok_tmb']['tmp_name'], $dok_tmb);
    
    $dok_berhenti_bertugas = !empty($_FILES['dok_berhenti_bertugas']['name']) ? ('uploads/'.time().'_br_'.basename($_FILES['dok_berhenti_bertugas']['name'])) : ($data['dok_berhenti_bertugas'] ?? '');
    if(!empty($_FILES['dok_berhenti_bertugas']['name'])) move_uploaded_file($_FILES['dok_berhenti_bertugas']['tmp_name'], $dok_berhenti_bertugas);
    
    $dok_penugasan_struktural = !empty($_FILES['dok_penugasan_struktural']['name']) ? ('uploads/'.time().'_str_'.basename($_FILES['dok_penugasan_struktural']['name'])) : ($data['dok_penugasan_struktural'] ?? '');
    if(!empty($_FILES['dok_penugasan_struktural']['name'])) move_uploaded_file($_FILES['dok_penugasan_struktural']['tmp_name'], $dok_penugasan_struktural);

    $sql = "UPDATE dosen SET 
        nama_lengkap = ?, alamat = ?, ttl_tempat = ?, ttl_tanggal = ?, 
        nip = ?, nidn = ?, nuptk = ?, status_dosen = ?, status_pribadi = ?, 
        dok_ktp = ?, dok_kk = ?, jenis_dosen = ?, jabatan_struktural = ?, 
        tmk = ?, tmtk = ?, ket_tidak_kerja = ?, dok_tidak_kerja = ?,
        jabfung_akademik = ?, tmt_jabfung = ?, dok_jabfung = ?,
        gol_lldikti = ?, tmt_gol_lldikti = ?, dok_gol_lldikti = ?,
        gol_yayasan = ?, tmt_gol_yayasan = ?, dok_gol_yayasan = ?,
        homebase_prodi = ?, unit_kerja = ?, no_serdos = ?, tmt_serdos = ?, dok_serdos = ?, 
        riwayat_pendidikan = ?, foto_profil = ?, status_keaktifan = ?, 
        keterangan_keaktifan = ?, tgl_mulai_tidak_bekerja = ?,
        dok_status_dosen = ?, dok_tmb = ?, dok_berhenti_bertugas = ?, dok_penugasan_struktural = ?
        WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Gagal menyiapkan query: " . $conn->error);
    }

    $stmt->bind_param("ssssssssssssssssssssssssssssssssssssssssi", 
        $nama, $alamat, $ttl_tempat, $ttl_tanggal, $nip, $nidn, $nuptk, $status_dosen_main, $status_pribadi, $dok_ktp, $dok_kk, $jenis_dosen, $jabatan_struktural, $tmk, $tmtk, $ket_tidak_kerja, $dok_tidak_kerja,
        $jabfung_akademik, $tmt_jabfung, $dok_jabfung,
        $gol_lldikti_main, $tmt_gol_lldikti, $dok_gol_lldikti,
        $gol_yayasan_main, $tmt_gol_yayasan, $dok_gol_yayasan,
        $homebase_prodi, $unit_kerja, $no_serdos, $tmt_serdos, $dok_serdos, $riwayat_pendidikan, $foto_profil, $status_keaktifan, $keterangan_keaktifan, $tgl_mulai_tidak_bekerja,
        $dok_status_dosen, $dok_tmb, $dok_berhenti_bertugas, $dok_penugasan_struktural,
        $id
    );
    
    if($stmt->execute()) {
        $conn->query("DELETE FROM status_dosen_riwayat WHERE dosen_id = $id");
        foreach ($status_list as $stt) {
            $st = $conn->prepare("INSERT INTO status_dosen_riwayat (dosen_id, status_dosen, tmt, tgl_berhenti, alasan, alasan_lainnya, dokumen) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if(!$st) die("Gagal prepare status: " . $conn->error);
            $st->bind_param("issssss", $id, $stt['status'], $stt['tmt'], $stt['tgl_berhenti'], $stt['alasan'], $stt['alasan_lainnya'], $stt['dokumen']);
            $st->execute();
        }
        
        $conn->query("DELETE FROM jabfung_dosen WHERE dosen_id = $id");
        foreach ($jabfung_list as $jf) {
            $st = $conn->prepare("INSERT INTO jabfung_dosen (dosen_id, jabatan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare jabfung: " . $conn->error);
            $st->bind_param("isss", $id, $jf['jabatan'], $jf['tmt'], $jf['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM pendidikan_dosen WHERE dosen_id = $id");
        foreach ($pendidikan_list as $pend) {
            $st = $conn->prepare("INSERT INTO pendidikan_dosen (dosen_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
            if(!$st) die("Gagal prepare pendidikan: " . $conn->error);
            $st->bind_param("issss", $id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM lldikti_dosen WHERE dosen_id = $id");
        foreach ($lldikti_list as $ld) {
            $st = $conn->prepare("INSERT INTO lldikti_dosen (dosen_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare lldikti: " . $conn->error);
            $st->bind_param("isss", $id, $ld['golongan'], $ld['tmt'], $ld['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM yayasan_dosen WHERE dosen_id = $id");
        foreach ($yayasan_list as $yy) {
            $st = $conn->prepare("INSERT INTO yayasan_dosen (dosen_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare yayasan: " . $conn->error);
            $st->bind_param("isss", $id, $yy['golongan'], $yy['tmt'], $yy['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM sertifikasi_dosen WHERE dosen_id = $id");
        foreach ($serdos_list as $sd) {
            $st = $conn->prepare("INSERT INTO sertifikasi_dosen (dosen_id, no_serdos, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare serdos: " . $conn->error);
            $st->bind_param("isss", $id, $sd['no'], $sd['tmt'], $sd['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM homebase_dosen_riwayat WHERE dosen_id = $id");
        foreach ($homebase_list as $hb) {
            $st = $conn->prepare("INSERT INTO homebase_dosen_riwayat (dosen_id, homebase_prodi, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare homebase: " . $conn->error);
            $st->bind_param("isss", $id, $hb['prodi'], $hb['tmt'], $hb['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM unit_kerja_dosen_riwayat WHERE dosen_id = $id");
        foreach ($unit_list as $uk) {
            $st = $conn->prepare("INSERT INTO unit_kerja_dosen_riwayat (dosen_id, unit_kerja, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare unit: " . $conn->error);
            $st->bind_param("isss", $id, $uk['unit'], $uk['tmt'], $uk['dokumen']);
            $st->execute();
        }
        
        $conn->query("DELETE FROM reward WHERE dosen_id = $id");
        if (!empty($_POST['reward_deskripsi'])) {
            foreach ($_POST['reward_deskripsi'] as $i => $desc) {
                if (trim($desc) !== '') {
                    $tanggal = !empty($_POST['reward_tanggal'][$i]) ? $_POST['reward_tanggal'][$i] : null;
                    $filename = $_POST['existing_reward_file'][$i] ?? '';
                    if (!empty($_FILES['reward_file']['name'][$i])) {
                        $filename = 'uploads/'.time().'_rw_'.basename($_FILES['reward_file']['name'][$i]);
                        move_uploaded_file($_FILES['reward_file']['tmp_name'][$i], $filename);
                    }
                    $st_rev = $conn->prepare("INSERT INTO reward (dosen_id, deskripsi, tanggal, file_upload) VALUES (?, ?, ?, ?)");
                    $st_rev->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_rev->execute();
                }
            }
        }

        $conn->query("DELETE FROM punishment WHERE dosen_id = $id");
        if (!empty($_POST['punishment_deskripsi'])) {
            foreach ($_POST['punishment_deskripsi'] as $i => $desc) {
                if (trim($desc) !== '') {
                    $tanggal = !empty($_POST['punishment_tanggal'][$i]) ? $_POST['punishment_tanggal'][$i] : null;
                    $filename = $_POST['existing_punishment_file'][$i] ?? '';
                    if (!empty($_FILES['punishment_file']['name'][$i])) {
                        $filename = 'uploads/'.time().'_pn_'.basename($_FILES['punishment_file']['name'][$i]);
                        move_uploaded_file($_FILES['punishment_file']['tmp_name'][$i], $filename);
                    }
                    $st_pn = $conn->prepare("INSERT INTO punishment (dosen_id, deskripsi, tanggal, file_upload) VALUES (?, ?, ?, ?)");
                    $st_pn->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_pn->execute();
                }
            }
        }

        echo "<script>alert('Data dosen berhasil diperbarui!');location='detail_dosen.php?id=$id';</script>";
    } else {
        die("Gagal menyimpan data: " . $stmt->error);
    }
}

$breadcrumbs = [
    ['label' => 'Daftar Dosen', 'url' => 'daftar_dosen.php'],
    ['label' => 'Detail Dosen', 'url' => 'detail_dosen.php?id='.$id],
    ['label' => 'Edit Data', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dosen | UNSERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-main: #f8fafc;
            --border-color: #e2e8f0;
        }
        body { background-color: var(--bg-main); font-family: 'Inter', sans-serif; }
        .form-container { max-width: 1200px; margin: 0 auto; padding-top: 30px; background: white; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 1.5rem; }
        .nav-tabs-custom { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0 10px; display: flex; gap: 5px; }
        .nav-tabs-custom .nav-link { border: none; padding: 1rem 1.2rem; color: #64748b; font-weight: 600; font-size: 0.85rem; position: relative; transition: all 0.3s; }
        .nav-tabs-custom .nav-link.active { color: var(--primary); background: transparent; }
        .nav-tabs-custom .nav-link.active::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; background: var(--primary); border-radius: 3px 3px 0 0; }
        .tab-content { padding: 20px; }
        .form-label { font-weight: 600; color: #334155; font-size: 0.8rem; margin-bottom: 6px; display: block; }
        .form-control, .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 0.6rem 0.9rem; font-size: 0.85rem; }
        .btn-xs { padding: 0.2rem 0.5rem; font-size: 0.75rem; border-radius: 6px; }
        .tiny { font-size: 0.7rem; margin-top: 2px; display: block; }
        .dynamic-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin-bottom: 10px; position: relative; }
        .btn-remove { position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border-radius: 6px; background: #fee2e2; color: #ef4444; border: none; display: flex; align-items: center; justify-content: center; }
        .btn-remove:hover { background: #dc2626; transform: scale(1.1); }
        .hidden { display: none !important; }
        .section-title { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .section-title i { color: var(--primary); }
        .sticky-bottom { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #e2e8f0; padding: 15px 25px; z-index: 10; }
        /* Compact history tab spacing */
        #history .section-title { margin-bottom: 0.4rem; }
        #history .dynamic-item { padding: 8px 10px; margin-bottom: 6px; }

    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Edit Profil Akademisi</h2>
                <p class="text-muted small mb-0">ID Dosen: <span class="fw-bold text-primary">#<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span></p>
            </div>
            <a href="detail_dosen.php?id=<?= $id ?>" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin ingin menyimpan perubahan data ini?')" id="editDosenForm">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-container">
                <ul class="nav nav-tabs-custom" id="formTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pribadi" type="button">1. Data Pribadi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kepegawaian" type="button">2. Kepegawaian</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kualifikasi" type="button">3. Kualifikasi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">4. Reward & Punishment</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pribadi">
                        <div class="row g-3">
                            <div class="col-md-3 text-center border-end">
                                <div class="mb-3">
                                    <label class="form-label">Foto Profil</label>
                                    <div class="mx-auto mb-3" style="width: 130px; height: 130px; border-radius: 16px; overflow: hidden; border: 4px solid #f1f5f9;">
                                        <img id="previewFoto" src="<?= !empty($data['foto_profil']) ? $data['foto_profil'] : 'https://ui-avatars.com/api/?name='.urlencode($data['nama_lengkap']).'&size=130' ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="section-title"><i class="fas fa-id-card"></i> Informasi Identitas</div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Nama Lengkap (beserta gelar)</label>
                                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status Pernikahan</label>
                                        <select name="status_pribadi" class="form-select">
                                            <option value="Belum Menikah" <?= ($data['status_pribadi']=='Belum Menikah'?'selected':'') ?>>Belum Menikah</option>
                                            <option value="Menikah" <?= ($data['status_pribadi']=='Menikah'?'selected':'') ?>>Menikah</option>
                                            <option value="Janda/Duda" <?= ($data['status_pribadi']=='Janda/Duda'?'selected':'') ?>>Janda/Duda</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tempat Lahir</label>
                                        <input type="text" name="ttl_tempat" class="form-control" value="<?= htmlspecialchars($data['ttl_tempat']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lahir</label>
                                        <input type="date" name="ttl_tanggal" class="form-control" value="<?= $data['ttl_tanggal'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NIP</label>
                                        <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($data['nip'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NIDN</label>
                                        <input type="text" name="nidn" class="form-control" value="<?= htmlspecialchars($data['nidn'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NUPTK</label>
                                        <input type="text" name="nuptk" class="form-control" value="<?= htmlspecialchars($data['nuptk'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($data['alamat']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="kepegawaian">
                        <div class="section-title"><i class="fas fa-id-card"></i> Status Kepegawaian</div>
                        <div class="p-4 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 mb-4">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <label class="form-label text-primary fw-bold small">Riwayat Status Dosen</label>
                                    <div id="status-wrapper">
                                        <?php if(empty($status_dosens)): ?>
                                            <div class="dynamic-item mb-2">
                                                <div class="row g-2">
                                                    <div class="col-md-12">
                                                        <select name="status_dosen[]" class="form-select form-select-sm" required>
                                                            <option value="">- Pilih Status -</option>
                                                            <option value="Tetap">Tetap</option>
                                                            <option value="Tidak Tetap">Tidak Tetap</option>
                                                            <option value="Homebase">Homebase</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6"><input type="date" name="tmt_status[]" class="form-control form-control-sm" title="TMT Mulai"></div>
                                                    <div class="col-6"><input type="file" name="dok_status[]" class="form-control form-control-sm"></div>
                                                </div>
                                            </div>
                                        <?php else: foreach($status_dosens as $i => $sd): ?>
                                            <div class="dynamic-item mb-2">
                                                <?php if($i > 0): ?><button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button><?php endif; ?>
                                                <div class="row g-2">
                                                    <div class="col-md-12">
                                                        <select name="status_dosen[]" class="form-select form-select-sm" required>
                                                            <option value="Tetap" <?= $sd['status_dosen'] == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                                            <option value="Tidak Tetap" <?= $sd['status_dosen'] == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                                                            <option value="Homebase" <?= $sd['status_dosen'] == 'Homebase' ? 'selected' : '' ?>>Homebase</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6"><input type="date" name="tmt_status[]" class="form-control form-control-sm" value="<?= $sd['tmt'] ?>" title="TMT Mulai"></div>
                                                    <div class="col-6">
                                                        <input type="file" name="dok_status[]" class="form-control form-control-sm">
                                                        <?php if(!empty($sd['dokumen'])): ?><a href="<?= $sd['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat SK</a><?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="existing_dok_status[]" value="<?= $sd['dokumen'] ?>">
                                                </div>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" onclick="addStatusRow()"><i class="fas fa-plus"></i> Tambah Riwayat</button>
                                </div>
                                <div class="col-md-7 border-start border-primary border-opacity-10">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="toggleStruk" <?= !empty($data['jabatan_struktural']) ? 'checked' : '' ?> onchange="document.getElementById('area_jabatan_struktural').classList.toggle('d-none', !this.checked)">
                                        <label class="form-check-label fw-bold text-primary small" for="toggleStruk">Memiliki Jabatan Struktural</label>
                                    </div>
                                    <div id="area_jabatan_struktural" class="<?= empty($data['jabatan_struktural']) ? 'd-none' : '' ?>">
                                        <div class="row g-2">
                                            <div class="col-md-7">
                                                <input type="text" name="jabatan_struktural" class="form-control form-control-sm" value="<?= htmlspecialchars($data['jabatan_struktural'] ?? '') ?>" placeholder="Nama Jabatan Struktural">
                                            </div>
                                            <div class="col-md-5">
                                                <input type="date" name="tmk" class="form-control form-control-sm" value="<?= $data['tmk'] ?>" title="TMT Jabatan Struktural">
                                            </div>
                                            <div class="col-12 mt-1">
                                                <label class="tiny text-muted">SK Penugasan (PDF/JPG)</label>
                                                <input type="file" name="dok_penugasan_struktural" class="form-control form-control-sm">
                                                <?php if(!empty($data['dok_penugasan_struktural'])): ?>
                                                    <a href="<?= $data['dok_penugasan_struktural'] ?>" target="_blank" class="tiny text-primary mt-1 d-block"><i class="fas fa-file-pdf"></i> Lihat SK Terupload</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label text-primary fw-bold small">TMT Berhenti (Jika Ada)</label>
                                        <input type="date" name="tmtk" class="form-control form-control-sm" value="<?= $data['tmtk'] ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                             <h3 class="section-title text-danger"><i class="fas fa-user-slash"></i>Status Keaktifan</h3>
                             <div class="p-4 bg-danger bg-opacity-10 rounded-4 border border-danger border-opacity-25">
                                 <div class="mb-4">
                                     <label class="form-label d-block mb-2">Status Utama</label>
                                     <div class="btn-group w-100" role="group">
                                         <input type="radio" class="btn-check" name="status_utama" id="status_aktif" value="Aktif" <?= ($data['status_keaktifan']??'Aktif') == 'Aktif' ? 'checked' : '' ?> onclick="toggleStatusKeaktifan('Aktif')">
                                         <label class="btn btn-outline-success rounded-start-pill" for="status_aktif">Aktif</label>
                                         
                                         <input type="radio" class="btn-check" name="status_utama" id="status_tidak_aktif" value="Tidak Aktif" <?= ($data['status_keaktifan']??'') == 'Tidak Aktif' ? 'checked' : '' ?> onclick="toggleStatusKeaktifan('Tidak Aktif')">
                                         <label class="btn btn-outline-danger rounded-end-pill" for="status_tidak_aktif">Tidak Aktif</label>
                                     </div>
                                 </div>

                                 <div id="wrapper_sub_status" class="mb-3">
                                     <label class="form-label small">Sub-Pilihan Status</label>
                                     <select name="status_keaktifan" id="select_sub_status" class="form-select" onchange="handleSubStatus(this)"></select>
                                 </div>

                                 <div id="wrapper_keaktifan_lainnya" class="hidden mb-3">
                                     <label class="form-label text-danger small">Keterangan Lainnya</label>
                                     <input type="text" name="ket_tidak_aktif_lainnya" class="form-control" value="<?= htmlspecialchars($data['keterangan_keaktifan']??'') ?>" placeholder="Jelaskan status...">
                                 </div>

                                 <div id="area_keaktifan_details" class="pt-3 border-top border-danger border-opacity-25 hidden">
                                     <div class="mb-3">
                                         <label class="form-label text-danger small">TMT Mulai Tidak Bekerja</label>
                                         <input type="date" name="tgl_mulai_tidak_bekerja" class="form-control" value="<?= $data['tgl_mulai_tidak_bekerja'] ?>">
                                     </div>
                                     <div>
                                         <label class="form-label text-danger small">Upload Dokumen Pendukung (SK/Surat)</label>
                                         <input type="file" name="dok_tidak_kerja" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                         <?php if(!empty($data['dok_tidak_kerja'])): ?><a href="<?= $data['dok_tidak_kerja'] ?>" target="_blank" class="text-danger tiny mt-1 d-block"><i class="fas fa-file-pdf me-1"></i>Lihat SK Terupload</a><?php endif; ?>
                                     </div>
                                 </div>
                             </div>
                         </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="section-title"><i class="fas fa-map-marker-alt"></i> Riwayat Program Studi (Homebase)</div>
                                <div id="homebase-wrapper">
                                    <?php if(empty($homebases)): ?>
                                        <div class="dynamic-item p-2 mb-2 bg-white border">
                                            <div class="row g-2">
                                                <div class="col-12"><input type="text" name="homebase_prodi_hist[]" class="form-control form-control-sm" placeholder="Nama Prodi" required value="<?= htmlspecialchars($data['homebase_prodi']) ?>"></div>
                                                <div class="col-6"><input type="date" name="tmt_homebase[]" class="form-control form-control-sm"></div>
                                                <div class="col-6"><input type="file" name="dok_homebase[]" class="form-control form-control-sm"></div>
                                            </div>
                                        </div>
                                    <?php else: foreach($homebases as $i => $hb): ?>
                                        <div class="dynamic-item p-2 mb-2 bg-white border">
                                            <?php if($i > 0): ?><button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button><?php endif; ?>
                                            <div class="row g-2">
                                                <div class="col-12"><input type="text" name="homebase_prodi_hist[]" class="form-control form-control-sm" value="<?= htmlspecialchars($hb['homebase_prodi']) ?>" required></div>
                                                <div class="col-6"><input type="date" name="tmt_homebase[]" class="form-control form-control-sm" value="<?= $hb['tmt'] ?>"></div>
                                                <div class="col-6">
                                                    <input type="file" name="dok_homebase[]" class="form-control form-control-sm">
                                                    <?php if(!empty($hb['dokumen'])): ?><a href="<?= $hb['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                                </div>
                                                <input type="hidden" name="existing_dok_homebase[]" value="<?= $hb['dokumen'] ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary mt-1 mb-4" onclick="addHomebaseRow()"><i class="fas fa-plus"></i> Tambah Riwayat Homebase</button>
                            </div>

                            <div class="col-md-6">
                                <div class="section-title"><i class="fas fa-building"></i> Riwayat Fakultas / Unit Kerja</div>
                                <div id="unit-wrapper">
                                    <?php if(empty($units)): ?>
                                        <div class="dynamic-item p-2 mb-2 bg-white border">
                                            <div class="row g-2">
                                                <div class="col-12"><input type="text" name="unit_kerja_hist[]" class="form-control form-control-sm" placeholder="Nama Unit" required value="<?= htmlspecialchars($data['unit_kerja']) ?>"></div>
                                                <div class="col-6"><input type="date" name="tmt_unit[]" class="form-control form-control-sm"></div>
                                                <div class="col-6"><input type="file" name="dok_unit[]" class="form-control form-control-sm"></div>
                                            </div>
                                        </div>
                                    <?php else: foreach($units as $i => $uk): ?>
                                        <div class="dynamic-item p-2 mb-2 bg-white border">
                                            <?php if($i > 0): ?><button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button><?php endif; ?>
                                            <div class="row g-2">
                                                <div class="col-12"><input type="text" name="unit_kerja_hist[]" class="form-control form-control-sm" value="<?= htmlspecialchars($uk['unit_kerja']) ?>" required></div>
                                                <div class="col-6"><input type="date" name="tmt_unit[]" class="form-control form-control-sm" value="<?= $uk['tmt'] ?>"></div>
                                                <div class="col-6">
                                                    <input type="file" name="dok_unit[]" class="form-control form-control-sm">
                                                    <?php if(!empty($uk['dokumen'])): ?><a href="<?= $uk['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                                </div>
                                                <input type="hidden" name="existing_dok_unit[]" value="<?= $uk['dokumen'] ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary mt-1 mb-4" onclick="addUnitRow()"><i class="fas fa-plus"></i> Tambah Riwayat Unit Kerja</button>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="section-title"><i class="fas fa-award"></i> Jabatan Akademik</div>
                                <div id="jab-wrapper">
                                    <?php foreach($jabfungs as $jf): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <select name="jabfung_akademik[]" class="form-select form-select-sm">
                                                    <option value="">- Pilih Jabatan Akademik -</option>
                                                    <option value="Asisten Ahli" <?= $jf['jabatan'] == 'Asisten Ahli' ? 'selected' : '' ?>>Asisten Ahli</option>
                                                    <option value="Lektor" <?= $jf['jabatan'] == 'Lektor' ? 'selected' : '' ?>>Lektor</option>
                                                    <option value="Lektor Kepala" <?= $jf['jabatan'] == 'Lektor Kepala' ? 'selected' : '' ?>>Lektor Kepala</option>
                                                    <option value="Guru Besar" <?= $jf['jabatan'] == 'Guru Besar' ? 'selected' : '' ?>>Guru Besar</option>
                                                </select>
                                            </div>
                                            <div class="col-6"><input type="date" name="tmt_jabfung[]" class="form-control form-control-sm" value="<?= $jf['tmt'] ?>"></div>
                                            <div class="col-6">
                                                <input type="file" name="dok_jabfung[]" class="form-control form-control-sm">
                                                <?php if(!empty($jf['dokumen'])): ?><a href="<?= $jf['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                            </div>
                                            <input type="hidden" name="existing_dok_jabfung[]" value="<?= $jf['dokumen'] ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="addJabRow()"><i class="fas fa-plus"></i> Tambah Jabatan</button>
                            </div>
                            <div class="col-md-4">
                                <div class="section-title"><i class="fas fa-layer-group"></i> Golongan DIKTI</div>
                                <div id="lldikti-wrapper">
                                    <?php foreach($lldiktis as $ld): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <select name="gol_lldikti[]" class="form-select form-select-sm">
                                                    <?php foreach($gols_dosen as $g): ?>
                                                    <option value="<?= $g ?>" <?= ($ld['golongan']==$g?'selected':'') ?>><?= $g ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6"><input type="date" name="tmt_gol_lldikti[]" class="form-control form-control-sm" value="<?= $ld['tmt'] ?>"></div>
                                            <div class="col-6">
                                                <input type="file" name="dok_gol_lldikti[]" class="form-control form-control-sm">
                                                <?php if(!empty($ld['dokumen'])): ?><a href="<?= $ld['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                            </div>
                                            <input type="hidden" name="existing_dok_gol_lldikti[]" value="<?= $ld['dokumen'] ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="addLldiktiRow()"><i class="fas fa-plus"></i> Tambah Golongan</button>
                            </div>
                            <div class="col-md-4">
                                <div class="section-title"><i class="fas fa-university"></i> Golongan Yayasan</div>
                                <div id="yayasan-wrapper">
                                    <?php foreach($yayasans as $yy): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <select name="gol_yayasan[]" class="form-select form-select-sm">
                                                    <?php foreach($gols_dosen as $g): ?>
                                                    <option value="<?= $g ?>" <?= ($yy['golongan']==$g?'selected':'') ?>><?= $g ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6"><input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm" value="<?= $yy['tmt'] ?>"></div>
                                            <div class="col-6">
                                                <input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm">
                                                <?php if(!empty($yy['dokumen'])): ?><a href="<?= $yy['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                            </div>
                                            <input type="hidden" name="existing_dok_gol_yayasan[]" value="<?= $yy['dokumen'] ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="addYayasanRow()"><i class="fas fa-plus"></i> Tambah Golongan</button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="kualifikasi">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="section-title"><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</div>
                                <div id="pend-wrapper">
                                    <?php if(!empty($pendidikans)): foreach($pendidikans as $p): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                             <div class="col-md-2">
                                                 <select name="pend_jenjang[]" class="form-select form-select-sm">
                                                     <option value="S1" <?= ($p['jenjang']=='S1'?'selected':'') ?>>S1</option>
                                                     <option value="S2" <?= ($p['jenjang']=='S2'?'selected':'') ?>>S2</option>
                                                     <option value="S3" <?= ($p['jenjang']=='S3'?'selected':'') ?>>S3</option>
                                                 </select>
                                             </div>
                                             <div class="col-md-5"><input type="text" name="pend_institusi[]" class="form-control form-control-sm" value="<?= htmlspecialchars($p['institusi']) ?>" placeholder="Nama Perguruan Tinggi"></div>
                                             <div class="col-md-2"><input type="number" name="pend_tahun[]" class="form-control form-control-sm" value="<?= $p['tahun_lulus'] ?>" placeholder="Lulus"></div>
                                             <div class="col-md-3">
                                                 <input type="file" name="dok_pendidikan[]" class="form-control form-control-sm">
                                                 <?php if(!empty($p['dokumen'])): ?><a href="<?= $p['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                             </div>
                                             <input type="hidden" name="existing_dok_pendidikan[]" value="<?= $p['dokumen'] ?>">
                                         </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mb-4" onclick="addPendRow()"><i class="fas fa-plus me-1"></i> Tambah Pendidikan</button>
                            </div>
                            <div class="col-md-5">
                                <div class="section-title"><i class="fas fa-certificate"></i> Sertifikasi Dosen (Serdos)</div>
                                <div id="serdos-wrapper">
                                    <?php foreach($serdoses as $sd): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                             <div class="col-12"><input type="text" name="no_serdos[]" class="form-control form-control-sm" value="<?= $sd['no_serdos'] ?>" placeholder="Nomor Sertifikat"></div>
                                             <div class="col-6"><input type="date" name="tmt_serdos[]" class="form-control form-control-sm" value="<?= $sd['tmt'] ?>"></div>
                                             <div class="col-6">
                                                 <input type="file" name="dok_serdos[]" class="form-control form-control-sm">
                                                 <?php if(!empty($sd['dokumen'])): ?><a href="<?= $sd['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                             </div>
                                             <input type="hidden" name="existing_dok_serdos[]" value="<?= $sd['dokumen'] ?>">
                                         </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSerdosRow()"><i class="fas fa-plus me-1"></i> Tambah Serdos</button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="history">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="section-title text-success"><i class="fas fa-trophy"></i> Penghargaan (Reward)</div>
                                <div id="reward-wrapper">
                                    <?php foreach($rewards as $rw): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                             <div class="col-12"><input type="text" name="reward_deskripsi[]" class="form-control form-control-sm" value="<?= htmlspecialchars($rw['deskripsi']) ?>" placeholder="Deskripsi Penghargaan"></div>
                                             <div class="col-6"><input type="date" name="reward_tanggal[]" class="form-control form-control-sm" value="<?= $rw['tanggal'] ?>"></div>
                                             <div class="col-6">
                                                 <input type="file" name="reward_file[]" class="form-control form-control-sm">
                                                 <?php if(!empty($rw['file_upload'])): ?><a href="<?= $rw['file_upload'] ?>" target="_blank" class="text-success tiny">Lihat Dokumen</a><?php endif; ?>
                                             </div>
                                             <input type="hidden" name="existing_reward_file[]" value="<?= $rw['file_upload'] ?>">
                                         </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success mb-2" onclick="addRewardRow()"><i class="fas fa-plus"></i> Tambah Reward</button>
                            </div>
                            <div class="col-md-6">
                                <div class="section-title text-danger"><i class="fas fa-gavel"></i> Sanksi (Punishment)</div>
                                <div id="punishment-wrapper">
                                    <?php foreach($punishments as $pn): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                             <div class="col-12"><input type="text" name="punishment_deskripsi[]" class="form-control form-control-sm" value="<?= htmlspecialchars($pn['deskripsi']) ?>" placeholder="Deskripsi Sanksi"></div>
                                             <div class="col-6"><input type="date" name="punishment_tanggal[]" class="form-control form-control-sm" value="<?= $pn['tanggal'] ?>"></div>
                                             <div class="col-6">
                                                 <input type="file" name="punishment_file[]" class="form-control form-control-sm">
                                                 <?php if(!empty($pn['file_upload'])): ?><a href="<?= $pn['file_upload'] ?>" target="_blank" class="text-danger tiny">Lihat Dokumen</a><?php endif; ?>
                                             </div>
                                             <input type="hidden" name="existing_punishment_file[]" value="<?= $pn['file_upload'] ?>">
                                         </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger mb-2" onclick="addPunishRow()"><i class="fas fa-plus"></i> Tambah Punishment</button>
                            </div>
                        </div>

                        <div class="section-title border-top pt-3 mt-2"><i class="fas fa-file-pdf"></i> Dokumen Pendukung (Upload Baru Jika Ingin Mengganti)</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Scan KTP</label>
                                <input type="file" name="dok_ktp" class="form-control form-control-sm">
                                <?php if($data['dok_ktp']): ?><a href="<?= $data['dok_ktp'] ?>" target="_blank" class="text-primary small">Lihat Dokumen</a><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Scan KK</label>
                                <input type="file" name="dok_kk" class="form-control form-control-sm">
                                <?php if($data['dok_kk']): ?><a href="<?= $data['dok_kk'] ?>" target="_blank" class="text-primary small">Lihat Dokumen</a><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SK TMB</label>
                                <input type="file" name="dok_tmb" class="form-control form-control-sm">
                                <?php if($data['dok_tmb']): ?><a href="<?= $data['dok_tmb'] ?>" target="_blank" class="text-primary small">Lihat Dokumen</a><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SK Berhenti</label>
                                <input type="file" name="dok_berhenti_bertugas" class="form-control form-control-sm">
                                <?php if($data['dok_berhenti_bertugas']): ?><a href="<?= $data['dok_berhenti_bertugas'] ?>" target="_blank" class="text-primary small">Lihat Dokumen</a><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SK Struktural</label>
                                <input type="file" name="dok_penugasan_struktural" class="form-control form-control-sm">
                                <?php if($data['dok_penugasan_struktural']): ?><a href="<?= $data['dok_penugasan_struktural'] ?>" target="_blank" class="text-primary small">Lihat Dokumen</a><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky-bottom text-end">
                    <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Reset</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { $('#previewFoto').attr('src', e.target.result); }
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleStatusKeaktifan(main) {
    const selectSub = document.getElementById('select_sub_status');
    const areaLainnya = document.getElementById('wrapper_keaktifan_lainnya');
    const areaDetails = document.getElementById('area_keaktifan_details');
    const currentSub = "<?= $data['keterangan_keaktifan']??'' ?>";
    
    selectSub.innerHTML = '';
    areaLainnya.classList.add('hidden');

    let opts = [];
    if (main === 'Aktif') {
        areaDetails.classList.add('hidden');
        opts = [
            {v: '-', t: 'Aktif Normal'},
            {v: 'Cuti', t: 'Cuti'},
            {v: 'Izin Belajar', t: 'Izin Belajar'},
            {v: 'Tugas Belajar', t: 'Tugas Belajar'},
            {v: 'Lainnya', t: 'Lainnya'}
        ];
    } else {
        areaDetails.classList.remove('hidden');
        opts = [
            {v: 'Diberhentikan', t: 'Diberhentikan'},
            {v: 'Resign', t: 'Resign'},
            {v: 'Pensiun', t: 'Pensiun'},
            {v: 'Lainnya', t: 'Lainnya'}
        ];
    }

    let found = false;
    opts.forEach(o => {
        let opt = new Option(o.t, o.v);
        if (o.v === currentSub) {
            opt.selected = true;
            found = true;
        }
        selectSub.add(opt);
    });

    if (!found && currentSub && currentSub !== '-') {
        let opt = new Option(currentSub, 'Lainnya');
        opt.selected = true;
        selectSub.add(opt);
        areaLainnya.classList.remove('hidden');
    } else if (selectSub.value === 'Lainnya') {
        areaLainnya.classList.remove('hidden');
    }
}

function handleSubStatus(el) {
    const areaLainnya = document.getElementById('wrapper_keaktifan_lainnya');
    if (el.value === 'Lainnya') {
        areaLainnya.classList.remove('d-none');
    } else {
        areaLainnya.classList.add('d-none');
    }
}
// Initial call removed, handled by global listener at the end of script

function addStatusRow() {
    const html = `<div class="dynamic-item mb-2">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-md-12">
                <select name="status_dosen[]" class="form-select form-select-sm" required>
                    <option value="">- Pilih Status -</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Tidak Tetap">Tidak Tetap</option>
                    <option value="Homebase">Homebase</option>
                </select>
            </div>
            <div class="col-6"><input type="date" name="tmt_status[]" class="form-control form-control-sm" title="TMT Mulai"></div>
            <div class="col-6"><input type="file" name="dok_status[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_status[]" value="">
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPendRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-md-2"><select name="pend_jenjang[]" class="form-select form-select-sm"><option>S1</option><option>S2</option><option>S3</option></select></div>
            <div class="col-md-6"><input type="text" name="pend_institusi[]" class="form-control form-control-sm" placeholder="Institusi"></div>
            <div class="col-md-2"><input type="number" name="pend_tahun[]" class="form-control form-control-sm" placeholder="Tahun"></div>
            <div class="col-md-2"><input type="file" name="dok_pendidikan[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_pendidikan[]" value="">
        </div>
    </div>`;
    document.getElementById('pend-wrapper').insertAdjacentHTML('beforeend', html);
}

function addJabRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12">
                <select name="jabfung_akademik[]" class="form-select form-select-sm">
                    <option value="">- Pilih Jabatan Akademik -</option>
                    <option value="Asisten Ahli">Asisten Ahli</option>
                    <option value="Lektor">Lektor</option>
                    <option value="Lektor Kepala">Lektor Kepala</option>
                    <option value="Guru Besar">Guru Besar</option>
                </select>
            </div>
            <div class="col-6"><input type="date" name="tmt_jabfung[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_jabfung[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_jabfung[]" value="">
        </div>
    </div>`;
    document.getElementById('jab-wrapper').insertAdjacentHTML('beforeend', html);
}

function addRewardRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-6"><input type="text" name="reward_deskripsi[]" class="form-control form-control-sm" placeholder="Deskripsi Penghargaan"></div>
            <div class="col-3"><input type="date" name="reward_tanggal[]" class="form-control form-control-sm"></div>
            <div class="col-3"><input type="file" name="reward_file[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_reward_file[]" value="">
        </div>
    </div>`;
    document.getElementById('reward-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPunishRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-6"><input type="text" name="punishment_deskripsi[]" class="form-control form-control-sm" placeholder="Deskripsi Sanksi"></div>
            <div class="col-3"><input type="date" name="punishment_tanggal[]" class="form-control form-control-sm"></div>
            <div class="col-3"><input type="file" name="punishment_file[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_punishment_file[]" value="">
        </div>
    </div>`;
    document.getElementById('punishment-wrapper').insertAdjacentHTML('beforeend', html);
}

function addLldiktiRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12">
                <select name="gol_lldikti[]" class="form-select form-select-sm">
                    <option value="III/a">III/a</option><option value="III/b">III/b</option>
                    <option value="III/c">III/c</option><option value="III/d">III/d</option>
                    <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                    <option value="IV/c">IV/c</option><option value="IV/d">IV/d</option>
                </select>
            </div>
            <div class="col-6"><input type="date" name="tmt_gol_lldikti[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_gol_lldikti[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_gol_lldikti[]" value="">
        </div>
    </div>`;
    document.getElementById('lldikti-wrapper').insertAdjacentHTML('beforeend', html);
}

function addYayasanRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12">
                <select name="gol_yayasan[]" class="form-select form-select-sm">
                    <option value="III/a">III/a</option><option value="III/b">III/b</option>
                    <option value="III/c">III/c</option><option value="III/d">III/d</option>
                    <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                    <option value="IV/c">IV/c</option><option value="IV/d">IV/d</option>
                </select>
            </div>
            <div class="col-6"><input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
        </div>
    </div>`;
    document.getElementById('yayasan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addSerdosRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12"><input type="text" name="no_serdos[]" class="form-control form-control-sm" placeholder="No Sertifikat"></div>
            <div class="col-6"><input type="date" name="tmt_serdos[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_serdos[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_serdos[]" value="">
        </div>
    </div>`;
    document.getElementById('serdos-wrapper').insertAdjacentHTML('beforeend', html);
}

function addHomebaseRow() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12"><input type="text" name="homebase_prodi_hist[]" class="form-control form-control-sm" placeholder="Nama Prodi"></div>
            <div class="col-6"><input type="date" name="tmt_homebase[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_homebase[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_homebase[]" value="">
        </div>
    </div>`;
    document.getElementById('homebase-wrapper').insertAdjacentHTML('beforeend', html);
}

function addUnitRow() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12"><input type="text" name="unit_kerja_hist[]" class="form-control form-control-sm" placeholder="Nama Unit"></div>
            <div class="col-6"><input type="date" name="tmt_unit[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_unit[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_unit[]" value="">
        </div>
    </div>`;
    document.getElementById('unit-wrapper').insertAdjacentHTML('beforeend', html);
}

// Global handler for hidden required fields in tabs (with debouncing to prevent blinking)
let lastInvalidTime = 0;
document.addEventListener('invalid', function(e) {
    const now = Date.now();
    const field = e.target;
    const pane = field.closest('.tab-pane');
    
    // If it's a hidden tab
    if (pane && !pane.classList.contains('active')) {
        e.preventDefault(); // Stop native focus attempt
        
        // Only trigger switch for the first one detected in a batch
        if (now - lastInvalidTime > 500) {
            lastInvalidTime = now;
            const tabId = pane.id;
            const tabTrigger = document.querySelector(`[data-bs-target="#${tabId}"], [href="#${tabId}"]`);
            if (tabTrigger) {
                const tab = new bootstrap.Tab(tabTrigger);
                tab.show();
                setTimeout(() => {
                    field.focus();
                    field.reportValidity();
                }, 300);
            }
        }
    }
}, true);
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>

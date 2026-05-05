<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: data_pegawai.php');
    exit;
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pegawai = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pegawai) {
    header('Location: data_pegawai.php');
    exit;
}

// Support older records single 'ttl' field vs split
$ttl_tempat_val = $pegawai['ttl_tempat'];
$ttl_tanggal_val = $pegawai['ttl_tanggal'];
if (!$ttl_tempat_val && !$ttl_tanggal_val && !empty($pegawai['ttl'])) {
    $parts = explode(',', $pegawai['ttl'], 2);
    if(count($parts) == 2) {
        $ttl_tempat_val = trim($parts[0]);
        $ttl_tanggal_val = date('Y-m-d', strtotime(trim($parts[1])));
    } else {
        $ttl_tempat_val = trim($pegawai['ttl']);
    }
}

// Fetch riwayat
$yayasans = ($q = $conn->query("SELECT * FROM yayasan_pegawai WHERE pegawai_id = $id ORDER BY tmt ASC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$lldiktis = ($q = $conn->query("SELECT * FROM lldikti_pegawai WHERE pegawai_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$jabfungs = ($q = $conn->query("SELECT * FROM jabfung_pegawai WHERE pegawai_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$pendidikans = ($q = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id ORDER BY tahun_lulus ASC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$status_riwayats = ($q = $conn->query("SELECT * FROM status_pegawai_riwayat WHERE pegawai_id = $id ORDER BY tmt_mulai_kerja DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$rewards = ($q = $conn->query("SELECT * FROM reward_pegawai WHERE pegawai_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$punishments = ($q = $conn->query("SELECT * FROM punishment_pegawai WHERE pegawai_id = $id")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$units = ($q = $conn->query("SELECT * FROM unit_kerja_pegawai_riwayat WHERE pegawai_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];

// Ensure all columns exist
$cols = [
    'status_keaktifan' => "VARCHAR(50) DEFAULT 'Aktif'",
    'keterangan_keaktifan' => "TEXT DEFAULT NULL",
    'dok_tmtk' => "VARCHAR(255) DEFAULT ''",
    'riwayat_pendidikan' => "TEXT DEFAULT NULL",
    'ket_tidak_kerja' => "TEXT DEFAULT NULL",
    'tmt_tidak_kerja' => "DATE DEFAULT NULL",
    'jenis_pegawai' => "VARCHAR(100) DEFAULT ''",
    'jabatan_struktural' => "VARCHAR(100) DEFAULT ''",
    'tmk' => "DATE DEFAULT NULL",
    'dok_penugasan_struktural' => "VARCHAR(255) DEFAULT ''"
];
foreach($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM pegawai LIKE '$col'");
    if($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE pegawai ADD COLUMN $col $def");
    }
}

// Ensure history tables exist for pegawai
$conn->query("CREATE TABLE IF NOT EXISTS jabfung_pegawai (id INT AUTO_INCREMENT PRIMARY KEY, pegawai_id INT, jabatan VARCHAR(100), tmt DATE, dokumen VARCHAR(255))");
$conn->query("CREATE TABLE IF NOT EXISTS lldikti_pegawai (id INT AUTO_INCREMENT PRIMARY KEY, pegawai_id INT, golongan VARCHAR(100), tmt DATE, dokumen VARCHAR(255))");
$conn->query("CREATE TABLE IF NOT EXISTS unit_kerja_pegawai_riwayat (id INT AUTO_INCREMENT PRIMARY KEY, pegawai_id INT, unit_kerja VARCHAR(255), tmt DATE, dokumen VARCHAR(255))");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? $id;
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'] ?? '';
    $ttl_tanggal = $_POST['ttl_tanggal'] ?: null;
    $ttl_lama = ($ttl_tempat ? $ttl_tempat . ', ' : '') . (!empty($ttl_tanggal) ? date('d F Y', strtotime($ttl_tanggal)) : '');
    
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $status_keaktifan_utama = $_POST['status_utama'] ?? 'Aktif';
    $status_keaktifan_sub = $_POST['status_keaktifan'] ?? '-';
    $tmk = $_POST['tmt_mulai_kerja'] ?: null;
    $tmtk = !empty($_POST['tmt_tidak_kerja']) ? $_POST['tmt_tidak_kerja'] : null;
    
    $keterangan_keaktifan = $status_keaktifan_sub;
    if ($status_keaktifan_sub === 'Lainnya') {
        $keterangan_keaktifan = $_POST['ket_tidak_aktif_lainnya'] ?? 'Lainnya';
    }

    $status_keaktifan = $status_keaktifan_utama;
    $ket_tmtk = $keterangan_keaktifan;
    
    $jenis_pegawai = $_POST['jenis_pegawai'] ?? '';
    $jabatan_struktural = $_POST['jabatan_struktural'] ?? '';
    $tmk_struktural = !empty($_POST['tmk']) ? $_POST['tmk'] : null;
    $dok_penugasan_struktural = !empty($_FILES['dok_penugasan_struktural']['name']) ? ('uploads/'.time().'_str_'.basename($_FILES['dok_penugasan_struktural']['name'])) : ($pegawai['dok_penugasan_struktural'] ?? '');
    if(!empty($_FILES['dok_penugasan_struktural']['name'])) move_uploaded_file($_FILES['dok_penugasan_struktural']['tmp_name'], $dok_penugasan_struktural);

    // Handle Status Riwayat
    $status_list = [];
    if(!empty($_POST['status_pegawai'])) {
        foreach($_POST['status_pegawai'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status'][$i]) ? $_POST['tmt_status'][$i] : null;
                $filename = $_POST['existing_dok_status'][$i] ?? '';
                if(!empty($_FILES['dok_status']['name'][$i])) {
                    $filename = 'uploads/'.time().'_sp_'.basename($_FILES['dok_status']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status']['tmp_name'][$i], $filename);
                }
                $status_list[] = ['status' => $std, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    // Determine main status (latest by TMT)
    usort($status_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $status_pegawai_main = $status_list[0]['status'] ?? ($data['status_pegawai'] ?? '');
    $jenis_pegawai = $status_pegawai_main;

    // Handle Jabfung
    $jabfung_list = [];
    if(!empty($_POST['jabfung'])) {
        foreach($_POST['jabfung'] as $i => $jab) {
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
    $jabatan = $jabfung_list[0]['jabatan'] ?? ($pegawai['posisi_jabatan'] ?? '');

    // Handle Golongan Yayasan
    $yayasan_list = [];
    if(!empty($_POST['gol_yayasan'])) {
        foreach($_POST['gol_yayasan'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = !empty($_POST['tmt_gol_yayasan'][$i]) ? $_POST['tmt_gol_yayasan'][$i] : null;
                $filename = $_POST['existing_dok_gol_yayasan'][$i] ?? '';
                if(!empty($_FILES['dok_gol_yayasan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_py_'.basename($_FILES['dok_gol_yayasan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_yayasan']['tmp_name'][$i], $filename);
                }
                $yayasan_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    // Handle Golongan DIKTI
    $lldikti_list = [];
    if(!empty($_POST['gol_lldikti'])) {
        foreach($_POST['gol_lldikti'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = !empty($_POST['tmt_gol_lldikti'][$i]) ? $_POST['tmt_gol_lldikti'][$i] : null;
                $filename = $_POST['existing_dok_gol_lldikti'][$i] ?? '';
                if(!empty($_FILES['dok_gol_lldikti']['name'][$i])) {
                    $filename = 'uploads/'.time().'_pl_'.basename($_FILES['dok_gol_lldikti']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_lldikti']['tmp_name'][$i], $filename);
                }
                $lldikti_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    // Handle Pendidikan
    $pendidikan_list = [];
    if (!empty($_POST['pend_jenjang'])) {
        foreach ($_POST['pend_jenjang'] as $i => $jenjang) {
            if (trim($jenjang) !== '') {
                $institusi = $_POST['pend_institusi'][$i] ?? '';
                $tahun = $_POST['pend_tahun'][$i] ?? '';
                $filename = $_POST['existing_dok_pendidikan'][$i] ?? '';
                if (!empty($_FILES['dok_pendidikan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_edu_p_'.basename($_FILES['dok_pendidikan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_pendidikan']['tmp_name'][$i], $filename);
                }
                $pendidikan_list[] = ['jenjang' => $jenjang, 'institusi' => $institusi, 'tahun_lulus' => $tahun, 'dokumen' => $filename];
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

    // Handle Unit Kerja History
    $unit_list = [];
    if(!empty($_POST['unit_kerja_hist'])) {
        foreach($_POST['unit_kerja_hist'] as $i => $uk) {
            if(trim($uk) !== '') {
                $tmt = !empty($_POST['tmt_unit'][$i]) ? $_POST['tmt_unit'][$i] : null;
                $filename = $_POST['existing_dok_unit'][$i] ?? '';
                if(!empty($_FILES['dok_unit']['name'][$i])) {
                    $filename = 'uploads/'.time().'_uk_p_'.basename($_FILES['dok_unit']['name'][$i]);
                    move_uploaded_file($_FILES['dok_unit']['tmp_name'][$i], $filename);
                }
                $unit_list[] = ['unit' => $uk, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    // Determine main unit (latest by TMT)
    usort($unit_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $unit_kerja = $unit_list[0]['unit'] ?? ($_POST['unit_kerja_main'] ?? '');

    // Handle Files
    $dok_ktp = $pegawai['dok_ktp'];
    if(!empty($_FILES['dok_ktp']['name'])) {
        $dok_ktp = 'uploads/'.time().'_ktp_p_'.basename($_FILES['dok_ktp']['name']);
        move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
    }
    $dok_kk = $pegawai['dok_kk'];
    if(!empty($_FILES['dok_kk']['name'])) {
        $dok_kk = 'uploads/'.time().'_kk_p_'.basename($_FILES['dok_kk']['name']);
        move_uploaded_file($_FILES['dok_kk']['tmp_name'], $dok_kk);
    }
    $foto_profil = $pegawai['foto_profil'];
    if(!empty($_FILES['foto_profil']['name'])) {
        $foto_profil = 'uploads/foto_p_'.time().'_'.basename($_FILES['foto_profil']['name']);
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }

    $dok_tmtk = $pegawai['dok_tmtk'] ?? '';
    if(!empty($_FILES['dok_tmtk']['name'])) {
        $dok_tmtk = 'uploads/'.time().'_tmtk_p_'.basename($_FILES['dok_tmtk']['name']);
        move_uploaded_file($_FILES['dok_tmtk']['tmp_name'], $dok_tmtk);
    }

    $sql = "UPDATE pegawai SET 
        nama_lengkap = ?, alamat = ?, ttl = ?, ttl_tempat = ?, ttl_tanggal = ?, 
        status_pegawai = ?, status_pribadi = ?, posisi_jabatan = ?, unit_kerja = ?, 
        tmt_mulai_kerja = ?, tmt_tidak_kerja = ?, riwayat_pendidikan = ?, ket_tidak_kerja = ?, keterangan_keaktifan = ?,
        dok_tmtk = ?, dok_ktp = ?, dok_kk = ?, foto_profil = ?, status_keaktifan = ?,
        jenis_pegawai = ?, jabatan_struktural = ?, tmk = ?, dok_penugasan_struktural = ?
        WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if(!$stmt) {
        die("Gagal menyiapkan query: " . $conn->error);
    }
    $types = str_repeat("s", 23) . "i";
    $stmt->bind_param($types, 
        $nama, $alamat, $ttl_lama, $ttl_tempat, $ttl_tanggal, 
        $status_pegawai_main, $status_pribadi, $jabatan, $unit_kerja, 
        $tmk, $tmtk, $riwayat_pendidikan, $ket_tmtk, $keterangan_keaktifan,
        $dok_tmtk, $dok_ktp, $dok_kk, $foto_profil, $status_keaktifan,
        $jenis_pegawai, $jabatan_struktural, $tmk_struktural, $dok_penugasan_struktural, $id);
    
    if($stmt->execute()) {
        // Sync Unit Kerja History
        $conn->query("DELETE FROM unit_kerja_pegawai_riwayat WHERE pegawai_id = $id");
        foreach ($unit_list as $ul) {
            $st = $conn->prepare("INSERT INTO unit_kerja_pegawai_riwayat (pegawai_id, unit_kerja, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare unit pegawai: " . $conn->error);
            $st->bind_param("isss", $id, $ul['unit'], $ul['tmt'], $ul['dokumen']);
            $st->execute();
        }
        // Sync Riwayat Status
        $conn->query("DELETE FROM status_pegawai_riwayat WHERE pegawai_id = $id");
        foreach ($status_list as $sl) {
            $st = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, tmt_mulai_kerja, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare status pegawai: " . $conn->error);
            $st->bind_param("isss", $id, $sl['status'], $sl['tmt'], $sl['dokumen']);
            $st->execute();
        }

        // Sync Golongan Yayasan
        $conn->query("DELETE FROM yayasan_pegawai WHERE pegawai_id = $id");
        foreach ($yayasan_list as $yy) {
            $st = $conn->prepare("INSERT INTO yayasan_pegawai (pegawai_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare yayasan pegawai: " . $conn->error);
            $st->bind_param("isss", $id, $yy['golongan'], $yy['tmt'], $yy['dokumen']);
            $st->execute();
        }

        // Sync Golongan DIKTI
        $conn->query("DELETE FROM lldikti_pegawai WHERE pegawai_id = $id");
        foreach ($lldikti_list as $ld) {
            $st = $conn->prepare("INSERT INTO lldikti_pegawai (pegawai_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare lldikti pegawai: " . $conn->error);
            $st->bind_param("isss", $id, $ld['golongan'], $ld['tmt'], $ld['dokumen']);
            $st->execute();
        }

        // Sync Jabfung
        $conn->query("DELETE FROM jabfung_pegawai WHERE pegawai_id = $id");
        foreach ($jabfung_list as $jf) {
            $st = $conn->prepare("INSERT INTO jabfung_pegawai (pegawai_id, jabatan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            if(!$st) die("Gagal prepare jabfung pegawai: " . $conn->error);
            $st->bind_param("isss", $id, $jf['jabatan'], $jf['tmt'], $jf['dokumen']);
            $st->execute();
        }

        // Sync Pendidikan
        $conn->query("DELETE FROM pendidikan_pegawai WHERE pegawai_id = $id");
        foreach ($pendidikan_list as $pend) {
            $st = $conn->prepare("INSERT INTO pendidikan_pegawai (pegawai_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
            if(!$st) die("Gagal prepare pendidikan pegawai: " . $conn->error);
            $st->bind_param("issss", $id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
            $st->execute();
        }

        // Rewards
        $conn->query("DELETE FROM reward_pegawai WHERE pegawai_id = $id");
        if (!empty($_POST['reward_desc'])) {
            foreach ($_POST['reward_desc'] as $i => $desc) {
                if (trim($desc) !== '') {
                    $tanggal = $_POST['reward_date'][$i] ?? null;
                    $filename = $_POST['existing_reward_file'][$i] ?? '';
                    if (!empty($_FILES['reward_file']['name'][$i])) {
                        $filename = 'uploads/'.time().'_rw_p_'.basename($_FILES['reward_file']['name'][$i]);
                        move_uploaded_file($_FILES['reward_file']['tmp_name'][$i], $filename);
                    }
                    $st_rev = $conn->prepare("INSERT INTO reward_pegawai (pegawai_id, keterangan, tanggal, dokumen) VALUES (?, ?, ?, ?)");
                    if(!$st_rev) die("Gagal prepare reward pegawai: " . $conn->error);
                    $st_rev->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_rev->execute();
                }
            }
        }

        // Punishments
        $conn->query("DELETE FROM punishment_pegawai WHERE pegawai_id = $id");
        if (!empty($_POST['punish_desc'])) {
            foreach ($_POST['punish_desc'] as $i => $desc) {
                if (trim($desc) !== '') {
                    $tanggal = $_POST['punish_date'][$i] ?? null;
                    $filename = $_POST['existing_punish_file'][$i] ?? '';
                    if (!empty($_FILES['punish_file']['name'][$i])) {
                        $filename = 'uploads/'.time().'_pn_p_'.basename($_FILES['punish_file']['name'][$i]);
                        move_uploaded_file($_FILES['punish_file']['tmp_name'][$i], $filename);
                    }
                    $st_pn = $conn->prepare("INSERT INTO punishment_pegawai (pegawai_id, keterangan, tanggal, dokumen) VALUES (?, ?, ?, ?)");
                    if(!$st_pn) die("Gagal prepare punishment pegawai: " . $conn->error);
                    $st_pn->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_pn->execute();
                }
            }
        }

        echo "<script>alert('Data pegawai berhasil diperbarui!');location='detail_pegawai.php?id=$id';</script>";
    } else {
        die("Gagal menyimpan data: " . $stmt->error);
    }
}

$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'],
    ['label' => 'Detail Pegawai', 'url' => 'detail_pegawai.php?id='.$id],
    ['label' => 'Edit Data', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pegawai | UNSERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .form-container { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; }
        .nav-tabs-custom { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0 10px; display: flex; gap: 2px; }
        .nav-tabs-custom .nav-link { border: none; padding: 0.8rem 1.2rem; color: #64748b; font-weight: 600; font-size: 0.85rem; position: relative; transition: all 0.2s; background: transparent; }
        .nav-tabs-custom .nav-link.active { color: #2563eb; }
        .nav-tabs-custom .nav-link.active::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; background: #2563eb; border-radius: 3px 3px 0 0; }
        .tab-content { padding: 15px; }
        .form-label { font-weight: 600; color: #475569; font-size: 0.78rem; margin-bottom: 4px; display: block; text-transform: uppercase; letter-spacing: 0.025em; }
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid #e2e8f0; padding: 0.45rem 0.75rem; font-size: 0.85rem; transition: all 0.2s; background-color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08); outline: none; }
        .dynamic-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 10px; position: relative; }
        .btn-remove { position: absolute; top: -8px; right: -8px; width: 22px; height: 22px; border-radius: 50%; background: #ef4444; color: white; border: 2px solid white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-remove:hover { background: #dc2626; transform: scale(1.1); }
        .hidden { display: none !important; }
        .section-title { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .section-title i { color: #2563eb; width: 20px; text-align: center; }
        .sticky-bottom { position: sticky; bottom: 0; background: white; padding: 12px 20px; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 12px rgba(0,0,0,0.03); z-index: 100; margin: 0 -15px -15px -15px; }
        /* Compact history tab spacing */
        #history .section-title { margin-bottom: 0.4rem; }
        #history .dynamic-item { padding: 8px 10px; margin-bottom: 6px; }
        .btn-xs { padding: 0.2rem 0.5rem; font-size: 0.75rem; border-radius: 6px; }
        .tiny { font-size: 0.7rem; margin-top: 2px; display: block; }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Edit Profil Pegawai</h2>
                <p class="text-muted small mb-0">Staf Kependidikan ID: <span class="fw-bold text-primary">#<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span></p>
            </div>
            <a href="detail_pegawai.php?id=<?= $id ?>" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin ingin menyimpan perubahan data ini?')">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-container shadow-sm">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs-custom" id="formTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pribadi" type="button">1. Data Pribadi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kepegawaian" type="button">2. Kepegawaian</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kualifikasi" type="button">3. Kualifikasi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">4. History & Reward</button></li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Data Pribadi -->
                    <div class="tab-pane fade show active" id="pribadi">
                        <div class="row g-3">
                            <div class="col-md-3 text-center border-end">
                                <div class="mb-3">
                                    <label class="form-label">Foto Profil</label>
                                    <div class="mx-auto mb-3" style="width: 150px; height: 150px; border-radius: 20px; overflow: hidden; border: 4px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                        <img id="previewFoto" src="<?= !empty($pegawai['foto_profil']) ? $pegawai['foto_profil'] : 'https://ui-avatars.com/api/?name='.urlencode($pegawai['nama_lengkap']).'&size=150' ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="section-title"><i class="fas fa-id-card"></i> Informasi Identitas</div>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($pegawai['nama_lengkap']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tempat Lahir</label>
                                        <input type="text" name="ttl_tempat" class="form-control" value="<?= htmlspecialchars($ttl_tempat_val) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lahir</label>
                                        <input type="date" name="ttl_tanggal" class="form-control" value="<?= $ttl_tanggal_val ?>" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Alamat Sesuai KTP</label>
                                        <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($pegawai['alamat']) ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Pernikahan</label>
                                        <select name="status_pribadi" class="form-select" required>
                                            <option value="Menikah" <?= $pegawai['status_pribadi'] == 'Menikah' ? 'selected' : '' ?>>Menikah</option>
                                            <option value="Belum Menikah" <?= $pegawai['status_pribadi'] == 'Belum Menikah' ? 'selected' : '' ?>>Belum Menikah</option>
                                            <option value="Bercerai" <?= $pegawai['status_pribadi'] == 'Bercerai' ? 'selected' : '' ?>>Bercerai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Kepegawaian -->
                    <div class="tab-pane fade" id="kepegawaian">
                        <div class="row g-4">
                            <!-- Left Column: Status & Unit Kerja -->
                            <div class="col-md-6 border-end">
                                <div class="p-3 bg-light rounded-4">
                                    <h4 class="section-title small mb-3"><i class="fas fa-map-marker-alt"></i>Unit Kerja & Jabatan</h4>
                                    
                                    <label class="form-label small">Riwayat Unit Kerja</label>
                                    <div id="unit-wrapper">
                                        <?php if(!empty($units)): foreach($units as $un): ?>
                                        <div class="dynamic-item p-2 mb-2 bg-white border">
                                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                            <div class="row g-2 align-items-center">
                                                <div class="col-12"><input type="text" name="unit_kerja_hist[]" class="form-control form-control-sm" value="<?= htmlspecialchars($un['unit_kerja']) ?>" placeholder="Nama Unit"></div>
                                                <div class="col-6"><input type="date" name="tmt_unit[]" class="form-control form-control-sm" value="<?= $un['tmt'] ?>"></div>
                                                <div class="col-6">
                                                    <input type="file" name="dok_unit[]" class="form-control form-control-sm">
                                                    <?php if($un['dokumen']): ?><a href="<?= $un['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                                </div>
                                                <input type="hidden" name="existing_dok_unit[]" value="<?= $un['dokumen'] ?>">
                                            </div>
                                        </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-primary mb-3" onclick="addUnitRow()"><i class="fas fa-plus me-1"></i>Tambah Unit</button>

                                    <label class="form-label small mt-2">Riwayat Jabatan</label>
                                    <div id="jab-wrapper">
                                        <?php if(!empty($jabfungs)): foreach($jabfungs as $jf): ?>
                                        <div class="dynamic-item p-2 mb-2 bg-white border">
                                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                            <div class="row g-2 align-items-center">
                                                <div class="col-12"><input type="text" name="jabfung[]" class="form-control form-control-sm" value="<?= htmlspecialchars($jf['jabatan']) ?>" placeholder="Posisi Jabatan"></div>
                                                <div class="col-6"><input type="date" name="tmt_jabfung[]" class="form-control form-control-sm" value="<?= $jf['tmt'] ?>"></div>
                                                <div class="col-6">
                                                    <input type="file" name="dok_jabfung[]" class="form-control form-control-sm">
                                                    <?php if($jf['dokumen']): ?><a href="<?= $jf['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat SK</a><?php endif; ?>
                                                </div>
                                                <input type="hidden" name="existing_dok_jabfung[]" value="<?= $jf['dokumen'] ?>">
                                            </div>
                                        </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addJabRow()"><i class="fas fa-plus me-1"></i>Tambah Jabatan</button>
                                </div>
                            </div>

                             <!-- Right Column: Golongan & Keaktifan -->
                             <div class="col-md-6">
                                 <div class="section-title"><i class="fas fa-award"></i>Jabatan & Pangkat</div>
                                 
                                 <div class="section-title"><i class="fas fa-id-card"></i> Status Kepegawaian</div>
                        <div class="p-4 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 mb-4">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <label class="form-label text-primary fw-bold small">Riwayat Status Pegawai</label>
                                    <div id="status-wrapper">
                                        <?php if(empty($status_riwayats)): ?>
                                            <div class="dynamic-item mb-2">
                                                <div class="row g-2">
                                                    <div class="col-md-12">
                                                        <select name="status_pegawai[]" class="form-select form-select-sm" required>
                                                            <option value="">- Pilih Status -</option>
                                                            <option value="Tetap">Tetap</option>
                                                            <option value="Kontrak">Kontrak</option>
                                                            <option value="Honorer">Honorer</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6"><input type="date" name="tmt_status[]" class="form-control form-control-sm" title="TMT Mulai"></div>
                                                    <div class="col-6"><input type="file" name="dok_status[]" class="form-control form-control-sm"></div>
                                                </div>
                                            </div>
                                        <?php else: foreach($status_riwayats as $i => $sr): ?>
                                            <div class="dynamic-item mb-2">
                                                <?php if($i > 0): ?><button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button><?php endif; ?>
                                                <div class="row g-2">
                                                    <div class="col-md-12">
                                                        <select name="status_pegawai[]" class="form-select form-select-sm" required>
                                                            <option value="Tetap" <?= ($sr['status_pegawai'] ?? '') == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                                            <option value="Kontrak" <?= ($sr['status_pegawai'] ?? '') == 'Kontrak' ? 'selected' : '' ?>>Kontrak</option>
                                                            <option value="Honorer" <?= ($sr['status_pegawai'] ?? '') == 'Honorer' ? 'selected' : '' ?>>Honorer</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6"><input type="date" name="tmt_status[]" class="form-control form-control-sm" value="<?= $sr['tmt_mulai_kerja'] ?? $sr['tmt'] ?? '' ?>" title="TMT Mulai"></div>
                                                    <div class="col-6">
                                                        <input type="file" name="dok_status[]" class="form-control form-control-sm">
                                                        <?php if(!empty($sr['dokumen'])): ?><a href="<?= $sr['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat SK</a><?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="existing_dok_status[]" value="<?= $sr['dokumen'] ?>">
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

                                 <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label small text-primary">Golongan Yayasan</label>
                                        <div id="yayasan-wrapper">
                                            <?php if(!empty($yayasans)): foreach($yayasans as $yy): ?>
                                            <div class="dynamic-item p-2 mb-2 bg-white border">
                                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <select name="gol_yayasan[]" class="form-select form-select-sm">
                                                            <?php $gols=['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d']; 
                                                            foreach($gols as $g): ?>
                                                                <option value="<?= $g ?>" <?= $yy['golongan']==$g?'selected':'' ?>><?= $g ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-6"><input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm" value="<?= $yy['tmt'] ?>"></div>
                                                    <div class="col-6">
                                                        <input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm">
                                                        <?php if($yy['dokumen']): ?><a href="<?= $yy['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="existing_dok_gol_yayasan[]" value="<?= $yy['dokumen'] ?>">
                                                </div>
                                            </div>
                                            <?php endforeach; endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-xs btn-outline-primary mt-1" onclick="addYayasanRow()"><i class="fas fa-plus me-1"></i>Tambah Yayasan</button>
                                    </div>


                                </div>

                                <div class="col-md-6">
                                    <h3 class="section-title text-danger"><i class="fas fa-user-slash"></i>Status Keaktifan</h3>
                                    <div class="p-4 bg-danger bg-opacity-10 rounded-4 border border-danger border-opacity-25">
                                        <div class="mb-4">
                                            <label class="form-label d-block mb-2">Status Utama</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="status_utama" id="status_aktif" value="Aktif" <?= ($pegawai['status_keaktifan']??'Aktif') == 'Aktif' ? 'checked' : '' ?> onclick="toggleStatusKeaktifan('Aktif')">
                                                <label class="btn btn-outline-success rounded-start-pill" for="status_aktif">Aktif</label>
                                                
                                                <input type="radio" class="btn-check" name="status_utama" id="status_tidak_aktif" value="Tidak Aktif" <?= ($pegawai['status_keaktifan']??'') == 'Tidak Aktif' ? 'checked' : '' ?> onclick="toggleStatusKeaktifan('Tidak Aktif')">
                                                <label class="btn btn-outline-danger rounded-end-pill" for="status_tidak_aktif">Tidak Aktif</label>
                                            </div>
                                        </div>

                                        <div id="wrapper_sub_status" class="mb-3">
                                            <label class="form-label small">Sub-Pilihan Status</label>
                                            <select name="status_keaktifan" id="select_sub_status" class="form-select" onchange="handleSubStatus(this)"></select>
                                        </div>

                                        <div id="wrapper_keaktifan_lainnya" class="hidden mb-3">
                                            <label class="form-label text-danger small">Keterangan Lainnya</label>
                                            <input type="text" name="ket_tidak_aktif_lainnya" class="form-control" value="<?= htmlspecialchars($pegawai['keterangan_keaktifan']??'') ?>" placeholder="Jelaskan status...">
                                        </div>

                                        <div id="area_keaktifan_details" class="pt-3 border-top border-danger border-opacity-25 hidden">
                                            <div class="mb-3">
                                                <label class="form-label text-danger small">TMT Mulai Tidak Bekerja</label>
                                                <input type="date" name="tmt_tidak_kerja" class="form-control" value="<?= $pegawai['tmt_tidak_kerja'] ?>">
                                            </div>
                                            <div>
                                                <label class="form-label text-danger small">Upload Dokumen Pendukung (SK/Surat)</label>
                                                <input type="file" name="dok_tmtk" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                <?php if(!empty($pegawai['dok_tmtk'])): ?><a href="<?= $pegawai['dok_tmtk'] ?>" target="_blank" class="text-danger tiny mt-1 d-block"><i class="fas fa-file-pdf me-1"></i>Lihat SK Terupload</a><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Kualifikasi -->
                    <div class="tab-pane fade" id="kualifikasi">
                        <div class="section-title"><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</div>
                        <div id="pend-wrapper">
                            <?php if(!empty($pendidikans)): foreach($pendidikans as $p): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small">Jenjang</label>
                                        <select name="pend_jenjang[]" class="form-select form-select-sm">
                                            <?php foreach(['SD','SMP','SMA/SMK','D3','D4','S1','S2','S3'] as $j): ?>
                                                <option value="<?= $j ?>" <?= $p['jenjang']==$j?'selected':'' ?>><?= $j ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Institusi</label>
                                        <input type="text" name="pend_institusi[]" class="form-control form-control-sm" value="<?= htmlspecialchars($p['institusi']) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Tgl Lulus</label>
                                        <input type="date" name="pend_tahun[]" class="form-control form-control-sm" value="<?= $p['tahun_lulus'] ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Ijazah</label>
                                        <input type="file" name="dok_pendidikan[]" class="form-control form-control-sm">
                                        <?php if($p['dokumen']): ?><a href="<?= $p['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Ijazah</a><?php endif; ?>
                                    </div>
                                    <input type="hidden" name="existing_dok_pendidikan[]" value="<?= $p['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="dynamic-item">
                                <div class="row g-2">
                                    <div class="col-md-2"><select name="pend_jenjang[]" class="form-select form-select-sm"><option>S1</option></select></div>
                                    <div class="col-md-5"><input type="text" name="pend_institusi[]" class="form-control form-control-sm" placeholder="Institusi"></div>
                                    <div class="col-md-2"><input type="date" name="pend_tahun[]" class="form-control form-control-sm"></div>
                                    <div class="col-md-2"><input type="file" name="dok_pendidikan[]" class="form-control form-control-sm"></div>
                                    <input type="hidden" name="existing_dok_pendidikan[]" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-3 mt-2" onclick="addPendRow()"><i class="fas fa-plus me-1"></i> Tambah Pendidikan</button>
                    </div>

                    <!-- Tab 4: History -->
                    <div class="tab-pane fade" id="history">
                        <div class="row g-3">
                            <div class="col-md-6 border-end">
                                <div class="section-title"><i class="fas fa-medal"></i> Penghargaan</div>
                                <div id="reward-wrapper">
                                    <?php if(!empty($rewards)): foreach($rewards as $rw): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                            <div class="col-12"><input type="text" name="reward_desc[]" class="form-control form-control-sm" value="<?= htmlspecialchars($rw['keterangan']) ?>" placeholder="Keterangan Reward"></div>
                                            <div class="col-6"><input type="date" name="reward_date[]" class="form-control form-control-sm" value="<?= $rw['tanggal'] ?>"></div>
                                            <div class="col-6">
                                                <input type="file" name="reward_file[]" class="form-control form-control-sm">
                                                <?php if($rw['dokumen']): ?><a href="<?= $rw['dokumen'] ?>" target="_blank" class="text-primary tiny">Lihat Dokumen</a><?php endif; ?>
                                            </div>
                                            <input type="hidden" name="existing_reward_file[]" value="<?= $rw['dokumen'] ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary mb-2" onclick="addRewardRow()"><i class="fas fa-plus me-1"></i> Tambah Penghargaan</button>

                                <div class="section-title pt-3 border-top"><i class="fas fa-exclamation-triangle"></i> Sanksi / Hukuman</div>
                                <div id="punish-wrapper">
                                    <?php if(!empty($punishments)): foreach($punishments as $pn): ?>
                                    <div class="dynamic-item">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                        <div class="row g-2">
                                            <div class="col-12"><input type="text" name="punish_desc[]" class="form-control form-control-sm" value="<?= htmlspecialchars($pn['keterangan']) ?>" placeholder="Keterangan Sanksi"></div>
                                            <div class="col-6"><input type="date" name="punish_date[]" class="form-control form-control-sm" value="<?= $pn['tanggal'] ?>"></div>
                                            <div class="col-6">
                                                <input type="file" name="punish_file[]" class="form-control form-control-sm">
                                                <?php if($pn['dokumen']): ?><a href="<?= $pn['dokumen'] ?>" target="_blank" class="text-danger tiny">Lihat Dokumen</a><?php endif; ?>
                                            </div>
                                            <input type="hidden" name="existing_punish_file[]" value="<?= $pn['dokumen'] ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-danger mb-2" onclick="addPunishRow()"><i class="fas fa-plus me-1"></i> Tambah Sanksi</button>
                            </div>

                            <div class="col-md-6">
                                <div class="section-title"><i class="fas fa-file-shield"></i> Dokumen Pendukung</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small">Scan KTP</label>
                                        <input type="file" name="dok_ktp" class="form-control form-control-sm">
                                        <?php if($pegawai['dok_ktp']): ?><a href="<?= $pegawai['dok_ktp'] ?>" target="_blank" class="text-primary tiny">Lihat KTP Terupload</a><?php endif; ?>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Scan KK</label>
                                        <input type="file" name="dok_kk" class="form-control form-control-sm">
                                        <?php if($pegawai['dok_kk']): ?><a href="<?= $pegawai['dok_kk'] ?>" target="_blank" class="text-primary tiny">Lihat KK Terupload</a><?php endif; ?>
                                    </div>
                                </div>
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
        reader.onload = function(e) { document.getElementById('previewFoto').src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleStatusKeaktifan(main) {
    const selectSub = document.getElementById('select_sub_status');
    const areaLainnya = document.getElementById('wrapper_keaktifan_lainnya');
    const areaDetails = document.getElementById('area_keaktifan_details');
    const currentSub = "<?= $pegawai['keterangan_keaktifan']??'' ?>";
    
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
        areaLainnya.classList.remove('hidden');
    } else {
        areaLainnya.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Status Keaktifan
    const activeMain = document.querySelector('input[name="status_utama"]:checked');
    if(activeMain) toggleStatusKeaktifan(activeMain.value);
});

document.addEventListener('DOMContentLoaded', function() {
    toggleStatusKeaktifan("<?= $pegawai['status_keaktifan']??'Aktif' ?>");
});

function addStatusRow() {
    const html = `<div class="dynamic-item mb-2">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-md-12">
                <select name="status_pegawai[]" class="form-select form-select-sm" required>
                    <option value="">- Pilih Status -</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Kontrak">Kontrak</option>
                    <option value="Honorer">Honorer</option>
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
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label small">Jenjang</label><select name="pend_jenjang[]" class="form-select form-select-sm"><option>SD</option><option>SMP</option><option>SMA/SMK</option><option>D3</option><option>D4</option><option value="S1" selected>S1</option><option>S2</option><option>S3</option></select></div>
            <div class="col-md-4"><label class="form-label small">Institusi</label><input type="text" name="pend_institusi[]" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Tgl Lulus</label><input type="date" name="pend_tahun[]" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="form-label small">Upload Ijazah & Transkrip</label><input type="file" name="dok_pendidikan[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_pendidikan[]" value="">
        </div>
    </div>`;
    document.getElementById('pend-wrapper').insertAdjacentHTML('beforeend', html);
}

function addRewardRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12"><input type="text" name="reward_desc[]" class="form-control form-control-sm" placeholder="Keterangan Reward"></div>
            <div class="col-6"><input type="date" name="reward_date[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="reward_file[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_reward_file[]" value="">
        </div>
    </div>`;
    document.getElementById('reward-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPunishRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12"><input type="text" name="punish_desc[]" class="form-control form-control-sm" placeholder="Keterangan Sanksi"></div>
            <div class="col-6"><input type="date" name="punish_date[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="punish_file[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_punish_file[]" value="">
        </div>
    </div>`;
    document.getElementById('punish-wrapper').insertAdjacentHTML('beforeend', html);
}


function addYayasanRow() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12"><select name="gol_yayasan[]" class="form-select form-select-sm"><?php foreach(['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b'] as $g): ?><option value="<?= $g ?>"><?= $g ?></option><?php endforeach; ?></select></div>
            <div class="col-6"><input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
        </div>
    </div>`;
    document.getElementById('yayasan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addJabRow() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2 align-items-center">
            <div class="col-12"><input type="text" name="jabfung[]" class="form-control form-control-sm" placeholder="Posisi Jabatan"></div>
            <div class="col-6"><input type="date" name="tmt_jabfung[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_jabfung[]" class="form-control form-control-sm"></div>
            <input type="hidden" name="existing_dok_jabfung[]" value="">
        </div>
    </div>`;
    document.getElementById('jab-wrapper').insertAdjacentHTML('beforeend', html);
}

function addUnitRow() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-2 align-items-center">
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
</body>
</html>

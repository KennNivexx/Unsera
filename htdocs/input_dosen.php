<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'];
    $ttl_tanggal = $_POST['ttl_tanggal'];
    $nip = $_POST['nip'] ?? null;
    $nidn = $_POST['nidn'] ?? null;
    $nuptk = $_POST['nuptk'] ?? null;
    
    // Duplicate check
    $errors = [];
    if (!empty($nip)) {
        $q = $conn->query("SELECT id FROM dosen WHERE nip='$nip'");
        if($q->num_rows > 0) $errors[] = "NIP sudah terdaftar.";
    }
    if (!empty($nidn)) {
        $q = $conn->query("SELECT id FROM dosen WHERE nidn='$nidn'");
        if($q->num_rows > 0) $errors[] = "NIDN sudah terdaftar.";
    }
    if (!empty($nuptk)) {
        $q = $conn->query("SELECT id FROM dosen WHERE nuptk='$nuptk'");
        if($q->num_rows > 0) $errors[] = "NUPTK sudah terdaftar.";
    }
    if (!empty($errors)) {
        $err_msg = implode(" ", $errors);
        echo "<script>alert('Gagal menyimpan Data Dosen! $err_msg');history.back();</script>";
        exit;
    }

    $status_dosen = $_POST['status_dosen'][0] ?? ''; // Latest status
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jenis_dosen = $_POST['jenis_dosen'] ?? '';
    $jabatan_struktural = $_POST['jabatan_struktural'] ?? '';
    $tmk = !empty($_POST['tmk']) ? $_POST['tmk'] : null;
    $tmtk = !empty($_POST['tmtk']) ? $_POST['tmtk'] : null;
    $ket_tidak_kerja = $_POST['ket_tidak_kerja'] ?? '';
    $tgl_mulai_tidak_bekerja = !empty($_POST['tgl_mulai_tidak_bekerja']) ? $_POST['tgl_mulai_tidak_bekerja'] : null;
    
    $status_keaktifan_raw = $_POST['status_keaktifan'] ?? 'Aktif';
    $status_keaktifan = 'Aktif'; // Default to Aktif for special statuses
    $keterangan_keaktifan = '';
    
    if($status_keaktifan_raw === 'Tidak Aktif') {
        $status_keaktifan = 'Tidak Aktif';
        $keterangan_keaktifan = $_POST['ket_tidak_aktif'] ?? '';
        if($keterangan_keaktifan === 'Lainnya') {
            $keterangan_keaktifan = $_POST['ket_tidak_aktif_lainnya'] ?? '';
        }
    } else {
        // Special active statuses: -, Cuti, Izin Belajar, etc.
        $status_keaktifan = 'Aktif';
        $keterangan_keaktifan = $status_keaktifan_raw;
        if($status_keaktifan_raw === 'Lainnya') {
            $keterangan_keaktifan = $_POST['ket_tidak_aktif_lainnya'] ?? '';
        }
    }
    
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);

    // KTP & KK uploads
    $dok_ktp = '';
    if(!empty($_FILES['dok_ktp']['name'])) {
        $dok_ktp = 'uploads/'.time().'_ktp_'.basename($_FILES['dok_ktp']['name']);
        move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
    }
    $dok_kk = '';
    if(!empty($_FILES['dok_kk']['name'])) {
        $dok_kk = 'uploads/'.time().'_kk_'.basename($_FILES['dok_kk']['name']);
        move_uploaded_file($_FILES['dok_kk']['tmp_name'], $dok_kk);
    }

    $dok_tidak_kerja = '';
    if(!empty($_FILES['dok_tidak_kerja']['name'])) {
        $dok_tidak_kerja = 'uploads/'.time().'_'.basename($_FILES['dok_tidak_kerja']['name']);
        move_uploaded_file($_FILES['dok_tidak_kerja']['tmp_name'], $dok_tidak_kerja);
    }

    $status_list = [];
    if(!empty($_POST['status_dosen'])) {
        foreach($_POST['status_dosen'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status'][$i]) ? $_POST['tmt_status'][$i] : null;
                $tgl_berhenti = !empty($_POST['tgl_berhenti_status'][$i]) ? $_POST['tgl_berhenti_status'][$i] : null;
                $alasan = !empty($_POST['alasan_berhenti_status'][$i]) ? $_POST['alasan_berhenti_status'][$i] : null;
                $alasan_lain = ($alasan === 'Dan Lainnya') ? ($_POST['alasan_lainnya_status'][$i] ?? '') : null;
                
                $filename = '';
                if(!empty($_FILES['dok_status']['name'][$i])) {
                    $filename = 'uploads/'.time().'_status_'.basename($_FILES['dok_status']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status']['tmp_name'][$i], $filename);
                }
                $status_list[] = [
                    'status' => $std, 
                    'tmt' => $tmt, 
                    'tgl_berhenti' => $tgl_berhenti, 
                    'alasan' => $alasan,
                    'alasan_lainnya' => $alasan_lain,
                    'dokumen' => $filename
                ];
            }
        }
    }

    $jabfung_list = [];
    if(!empty($_POST['jabfung_akademik'])) {
        foreach($_POST['jabfung_akademik'] as $i => $jab) {
            if(trim($jab) !== '') {
                $tmt = !empty($_POST['tmt_jabfung'][$i]) ? $_POST['tmt_jabfung'][$i] : null;
                $filename = '';
                if(!empty($_FILES['dok_jabfung']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['dok_jabfung']['name'][$i]);
                    move_uploaded_file($_FILES['dok_jabfung']['tmp_name'][$i], $filename);
                }
                $jabfung_list[] = ['jabatan' => $jab, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    $lldikti_list = [];
    if(!empty($_POST['gol_lldikti'])) {
        foreach($_POST['gol_lldikti'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = $_POST['tmt_gol_lldikti'][$i] ?: null;
                $filename = '';
                if(!empty($_FILES['dok_gol_lldikti']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['dok_gol_lldikti']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_lldikti']['tmp_name'][$i], $filename);
                }
                $lldikti_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    $yayasan_list = [];
    if(!empty($_POST['gol_yayasan'])) {
        foreach($_POST['gol_yayasan'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = $_POST['tmt_gol_yayasan'][$i] ?: null;
                $filename = '';
                if(!empty($_FILES['dok_gol_yayasan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['dok_gol_yayasan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_yayasan']['tmp_name'][$i], $filename);
                }
                $yayasan_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    // Latest/Primary values for dashboard
    $jabfung_akademik = $jabfung_list[0]['jabatan'] ?? '';
    $tmt_jabfung = $jabfung_list[0]['tmt'] ?? null;
    $dok_jabfung = $jabfung_list[0]['dokumen'] ?? '';

    $gol_lldikti = $lldikti_list[0]['golongan'] ?? '';
    $tmt_gol_lldikti = $lldikti_list[0]['tmt'] ?? null;
    $dok_gol_lldikti = $lldikti_list[0]['dokumen'] ?? '';

    $gol_yayasan = $yayasan_list[0]['golongan'] ?? '';
    $tmt_gol_yayasan = $yayasan_list[0]['tmt'] ?? null;
    $dok_gol_yayasan = $yayasan_list[0]['dokumen'] ?? '';

    $homebase_prodi = $_POST['homebase_prodi'];
    $unit_kerja = $_POST['unit_kerja'];
    
    $serdos_list = [];
    if(!empty($_POST['no_serdos'])) {
        foreach($_POST['no_serdos'] as $i => $no) {
            if(trim($no) !== '') {
                $tmt = !empty($_POST['tmt_serdos'][$i]) ? $_POST['tmt_serdos'][$i] : null;
                $filename = '';
                if(!empty($_FILES['dok_serdos']['name'][$i])) {
                    $filename = 'uploads/'.time().'_serdos_'.basename($_FILES['dok_serdos']['name'][$i]);
                    move_uploaded_file($_FILES['dok_serdos']['tmp_name'][$i], $filename);
                }
                $serdos_list[] = ['no' => $no, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    $no_serdos = $serdos_list[0]['no'] ?? null;
    $tmt_serdos = $serdos_list[0]['tmt'] ?? null;
    $dok_serdos = $serdos_list[0]['dokumen'] ?? '';
    $pendidikan_list = [];
    if (!empty($_POST['pend_jenjang'])) {
        foreach ($_POST['pend_jenjang'] as $i => $jenjang) {
            if (trim($jenjang) !== '') {
                $institusi = $_POST['pend_institusi'][$i] ?? '';
                $tahun = $_POST['pend_tahun'][$i] ?? '';
                $filename = '';
                if (!empty($_FILES['dok_pendidikan']['name'][$i]) && $_FILES['dok_pendidikan']['error'][$i] == 0) {
                    $filename = 'uploads/' . time() . '_' . basename($_FILES['dok_pendidikan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_pendidikan']['tmp_name'][$i], $filename);
                }
                $pendidikan_list[] = ['jenjang' => $jenjang, 'institusi' => $institusi, 'tahun_lulus' => $tahun, 'dokumen' => $filename];
            }
        }
    }
    
    $riwayat_pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

    $foto_profil = '';
    if(!empty($_FILES['foto_profil']['name'])) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = 'uploads/foto_'.time().'.'.$ext;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }

    // Insert using Prepared Statement
    $sql = "INSERT INTO dosen (
        nama_lengkap, alamat, ttl_tempat, ttl_tanggal, nip, nidn, nuptk, status_dosen, status_pribadi, dok_ktp, dok_kk, jenis_dosen, jabatan_struktural, tmk, tmtk, ket_tidak_kerja, dok_tidak_kerja,
        jabfung_akademik, tmt_jabfung, dok_jabfung,
        gol_lldikti, tmt_gol_lldikti, dok_gol_lldikti,
        gol_yayasan, tmt_gol_yayasan, dok_gol_yayasan,
        homebase_prodi, unit_kerja, no_serdos, tmt_serdos, dok_serdos, riwayat_pendidikan, foto_profil, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssssssssssssssssssss", 
        $nama, $alamat, $ttl_tempat, $ttl_tanggal, $nip, $nidn, $nuptk, $status_dosen, $status_pribadi, $dok_ktp, $dok_kk, $jenis_dosen, $jabatan_struktural, $tmk, $tmtk, $ket_tidak_kerja, $dok_tidak_kerja,
        $jabfung_akademik, $tmt_jabfung, $dok_jabfung,
        $gol_lldikti, $tmt_gol_lldikti, $dok_gol_lldikti,
        $gol_yayasan, $tmt_gol_yayasan, $dok_gol_yayasan,
        $homebase_prodi, $unit_kerja, $no_serdos, $tmt_serdos, $dok_serdos, $riwayat_pendidikan, $foto_profil, $status_keaktifan, $keterangan_keaktifan, $tgl_mulai_tidak_bekerja
    );
    $stmt->execute();
    $last_dosen_id = $conn->insert_id;
    $stmt->close();

    // Rewards & Punishments
    if(!empty($_POST['reward_deskripsi'])) {
        foreach ($_POST['reward_deskripsi'] as $i => $desc) {
            if(trim($desc) !== '') {
                $tanggal = !empty($_POST['reward_tanggal'][$i]) ? $_POST['reward_tanggal'][$i] : null;
                $filename = '';
                if(!empty($_FILES['reward_file']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['reward_file']['name'][$i]);
                    move_uploaded_file($_FILES['reward_file']['tmp_name'][$i], $filename);
                }
                $stmt = $conn->prepare("INSERT INTO reward (dosen_id, deskripsi, tanggal, file_upload) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $last_dosen_id, $desc, $tanggal, $filename);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    if(!empty($_POST['punishment_deskripsi'])) {
        foreach ($_POST['punishment_deskripsi'] as $i => $desc) {
            if(trim($desc) !== '') {
                $tanggal = !empty($_POST['punishment_tanggal'][$i]) ? $_POST['punishment_tanggal'][$i] : null;
                $filename = '';
                if(!empty($_FILES['punishment_file']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['punishment_file']['name'][$i]);
                    move_uploaded_file($_FILES['punishment_file']['tmp_name'][$i], $filename);
                }
                $stmt = $conn->prepare("INSERT INTO punishment (dosen_id, deskripsi, tanggal, file_upload) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $last_dosen_id, $desc, $tanggal, $filename);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Insert Histories
    foreach ($pendidikan_list as $pend) {
        $st = $conn->prepare("INSERT INTO pendidikan_dosen (dosen_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
        $st->bind_param("issss", $last_dosen_id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
        $st->execute();
        $st->close();
    }
    
    foreach ($status_list as $stt) {
        $st = $conn->prepare("INSERT INTO status_dosen_riwayat (dosen_id, status_dosen, tmt, tgl_berhenti, alasan, alasan_lainnya, dokumen) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $st->bind_param("issssss", $last_dosen_id, $stt['status'], $stt['tmt'], $stt['tgl_berhenti'], $stt['alasan'], $stt['alasan_lainnya'], $stt['dokumen']);
        $st->execute();
        $st->close();
    }
    
    foreach ($jabfung_list as $jf) {
        $st = $conn->prepare("INSERT INTO jabfung_dosen (dosen_id, jabatan, tmt, dokumen) VALUES (?, ?, ?, ?)");
        $st->bind_param("isss", $last_dosen_id, $jf['jabatan'], $jf['tmt'], $jf['dokumen']);
        $st->execute();
        $st->close();
    }
    foreach ($lldikti_list as $ld) {
        $st = $conn->prepare("INSERT INTO lldikti_dosen (dosen_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
        $st->bind_param("isss", $last_dosen_id, $ld['golongan'], $ld['tmt'], $ld['dokumen']);
        $st->execute();
        $st->close();
    }
    foreach ($yayasan_list as $yy) {
        $st = $conn->prepare("INSERT INTO yayasan_dosen (dosen_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
        $st->bind_param("isss", $last_dosen_id, $yy['golongan'], $yy['tmt'], $yy['dokumen']);
        $st->execute();
        $st->close();
    }
    foreach ($serdos_list as $sd) {
        $st = $conn->prepare("INSERT INTO sertifikasi_dosen (dosen_id, no_serdos, tmt, dokumen) VALUES (?, ?, ?, ?)");
        $st->bind_param("isss", $last_dosen_id, $sd['no'], $sd['tmt'], $sd['dokumen']);
        $st->execute();
        $st->close();
    }

    echo "<script>alert('Data dosen berhasil disimpan!');location='daftar_dosen.php';</script>"; 
    exit;
}

$current_page = 'dosen_tambah';
$breadcrumbs = [
    ['label' => 'Dosen', 'url' => 'daftar_dosen.php'],
    ['label' => 'Tambah Baru', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dosen | Kepegawaian UNSERA</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bs-primary: #2563eb;
            --bs-primary-rgb: 37, 99, 235;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .form-label { font-weight: 600; color: #475569; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .form-control, .form-select { 
            border-radius: 10px; 
            padding: 0.6rem 1rem; 
            border: 1.5px solid #e2e8f0; 
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus { 
            border-color: var(--bs-primary); 
            box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1); 
        }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; }
        .nav-tabs { border: none; background: #f1f5f9; padding: 6px; border-radius: 14px; display: inline-flex; }
        .nav-tabs .nav-link { 
            border: none; 
            border-radius: 10px; 
            color: #64748b; 
            font-weight: 600; 
            padding: 10px 20px; 
            transition: all 0.3s;
        }
        .nav-tabs .nav-link.active { 
            background: white; 
            color: var(--bs-primary); 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        }
        .section-title { 
            font-family: 'Outfit', sans-serif; 
            font-weight: 700; 
            color: #0f172a; 
            font-size: 1.25rem; 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-title i { color: var(--bs-primary); }
        .dynamic-item { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            border-radius: 16px; 
            padding: 20px; 
            margin-bottom: 20px; 
            position: relative; 
            transition: all 0.2s;
        }
        .dynamic-item:hover { border-color: #cbd5e1; background: #f1f5f9; }
        .btn-remove { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            background: #fee2e2; 
            color: #ef4444; 
            border: none; 
            width: 32px; 
            height: 32px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-remove:hover { background: #ef4444; color: white; }
        .upload-placeholder {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .upload-placeholder:hover { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.05); }
        .hidden { display: none !important; }
        
        .sticky-save-bar {
            position: sticky;
            bottom: 20px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 12px 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            margin-top: 40px;
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">Registrasi Dosen Baru</h1>
                <p class="text-muted mb-0">Lengkapi formulir pendaftaran di bawah untuk menambahkan dosen baru ke sistem.</p>
            </div>
            <a href="daftar_dosen.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formDosen" onsubmit="return confirm('Apakah Anda yakin ingin menyimpan data ini?')">
            
            <div class="card mb-4 border-0">
                <div class="card-body p-4">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-4" id="dosenTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pribadi-tab" data-bs-toggle="tab" data-bs-target="#pribadi" type="button" role="tab"><i class="fas fa-user me-2"></i>Data Pribadi</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="kepegawaian-tab" data-bs-toggle="tab" data-bs-target="#kepegawaian" type="button" role="tab"><i class="fas fa-id-badge me-2"></i>Kepegawaian</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="kualifikasi-tab" data-bs-toggle="tab" data-bs-target="#kualifikasi" type="button" role="tab"><i class="fas fa-graduation-cap me-2"></i>Kualifikasi</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lainnya-tab" data-bs-toggle="tab" data-bs-target="#lainnya" type="button" role="tab"><i class="fas fa-folder-open me-2"></i>Lainnya</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="dosenTabsContent">
                        
                        <!-- TAB 1: DATA PRIBADI -->
                        <div class="tab-pane fade show active" id="pribadi" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="text-center p-4 bg-light rounded-4 border">
                                        <div class="mb-3">
                                            <div id="photoPreview" class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px; overflow: hidden; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                                <i class="fas fa-user text-secondary" style="font-size: 5rem;"></i>
                                            </div>
                                        </div>
                                        <label class="form-label d-block">Foto Profil Resmi</label>
                                        <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                        <small class="text-muted d-block mt-2">JPG/PNG, Maks 2MB</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h3 class="section-title"><i class="fas fa-id-card"></i>Identitas Diri</h3>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Nama Lengkap & Gelar</label>
                                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Dr. Ir. Budi Santoso, M.Kom" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tempat Lahir</label>
                                            <input type="text" name="ttl_tempat" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="ttl_tanggal" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status Pernikahan</label>
                                            <select name="status_pribadi" class="form-select" required>
                                                <option value="">- Pilih Status -</option>
                                                <option value="Menikah">Menikah</option>
                                                <option value="Belum Menikah">Belum Menikah</option>
                                                <option value="Bercerai">Bercerai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Alamat Sesuai KTP</label>
                                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <h3 class="section-title"><i class="fas fa-fingerprint"></i>Nomor Identitas</h3>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">NIP</label>
                                            <input type="text" name="nip" class="form-control" id="inp_nip" placeholder="Masukkan NIP">
                                            <div id="warn_nip" class="text-danger small mt-1 fw-bold" style="display:none;"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">NIDN</label>
                                            <input type="text" name="nidn" class="form-control" id="inp_nidn" placeholder="Masukkan NIDN">
                                            <div id="warn_nidn" class="text-danger small mt-1 fw-bold" style="display:none;"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">NUPTK</label>
                                            <input type="text" name="nuptk" class="form-control" id="inp_nuptk" placeholder="Masukkan NUPTK">
                                            <div id="warn_nuptk" class="text-danger small mt-1 fw-bold" style="display:none;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: KEPEGAWAIAN -->
                        <div class="tab-pane fade" id="kepegawaian" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-briefcase"></i>Status Kepegawaian</h3>
                                    <div id="status-wrapper">
                                        <div class="dynamic-item">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Status Dosen</label>
                                                    <select name="status_dosen[]" class="form-select" required>
                                                        <option value="">- Pilih Status -</option>
                                                        <option value="Tetap">Tetap</option>
                                                        <option value="Tidak Tetap">Tidak Tetap</option>
                                                        <option value="Homebase">Homebase</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">TMT Status</label>
                                                    <input type="date" name="tmt_status[]" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Upload SK Status</label>
                                                    <input type="file" name="dok_status[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addStatusDosen()" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fas fa-plus me-1"></i>Tambah Riwayat Status
                                    </button>

                                    <div class="mt-5 p-4 bg-light rounded-4">
                                        <h3 class="section-title mb-3"><i class="fas fa-map-marker-alt"></i>Homebase & Unit</h3>
                                        <div class="row g-3">
                                                <div class="col-md-10">
                                                    <label class="form-label">Program Studi (Homebase)</label>
                                                    <select name="homebase_prodi" class="form-select" id="select_homebase" required>
                                                        <option value="">- Pilih Prodi -</option>
                                                        <option value="S1 Teknik Informatika">S1 Teknik Informatika</option>
                                                        <option value="S1 Sistem Informasi">S1 Sistem Informasi</option>
                                                        <option value="S1 Akuntansi">S1 Akuntansi</option>
                                                        <option value="S1 Manajemen">S1 Manajemen</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="button" onclick="addNewItem('select_homebase', 'Program Studi')" class="btn btn-outline-primary w-100 rounded-10"><i class="fas fa-plus"></i></button>
                                                </div>
                                            </div>
                                            <div class="row g-3 mt-1">
                                                <div class="col-md-10">
                                                    <label class="form-label">Fakultas / Unit Kerja</label>
                                                    <select name="unit_kerja" class="form-select" id="select_unit" required>
                                                        <option value="">- Pilih Unit Kerja -</option>
                                                        <option value="Fakultas Teknologi Informasi">Fakultas Teknologi Informasi</option>
                                                        <option value="Fakultas Ekonomi & Bisnis">Fakultas Ekonomi & Bisnis</option>
                                                        <option value="Fakultas Teknik">Fakultas Teknik</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="button" onclick="addNewItem('select_unit', 'Unit Kerja')" class="btn btn-outline-primary w-100 rounded-10"><i class="fas fa-plus"></i></button>
                                                </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-award"></i>Jabatan & Pangkat</h3>
                                    
                                    <div class="p-4 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 mb-4">
                                        <label class="form-label d-block text-primary">Penugasan Struktural?</label>
                                        <div class="d-flex gap-3 mt-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="jenis_dosen" id="nonStruk" value="Non Struktural" checked>
                                                <label class="form-check-label fw-bold" for="nonStruk">Non-Struktural</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="jenis_dosen" id="struk" value="Struktural">
                                                <label class="form-check-label fw-bold" for="struk">Struktural</label>
                                            </div>
                                        </div>

                                        <div id="area_jabatan_struktural" class="mt-3 hidden pt-3 border-top border-primary border-opacity-25">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Nama Jabatan Struktural</label>
                                                    <input type="text" name="jabatan_struktural" class="form-control" placeholder="Contoh: Dekan / Kaprodi">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Tanggal Mulai (TMK)</label>
                                                    <input type="date" name="tmk" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="jabfung-wrapper">
                                        <div class="dynamic-item">
                                            <label class="form-label">Jabatan Fungsional Terakhir</label>
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <select name="jabfung_akademik[]" class="form-select">
                                                        <option value="">- Pilih Jabatan Akademik -</option>
                                                        <option value="Asisten Ahli">Asisten Ahli</option>
                                                        <option value="Asisten Ahli">Asisten Ahli</option>
                                                        <option value="Lektor">Lektor</option>
                                                        <option value="Lektor Kepala">Lektor Kepala</option>
                                                        <option value="Guru Besar">Guru Besar</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">TMT Jabfung</label>
                                                    <input type="date" name="tmt_jabfung[]" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Upload SK</label>
                                                    <input type="file" name="dok_jabfung[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addJabfung()" class="btn btn-sm btn-outline-primary rounded-pill px-3 mb-4">
                                        <i class="fas fa-plus me-1"></i>Tambah Riwayat Jabfung
                                    </button>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-light rounded-4">
                                                <label class="form-label">Pangkat/Gol (DIKTI)</label>
                                                <select name="gol_lldikti[]" class="form-select mb-2">
                                                    <option value="">- Pilih -</option>
                                                    <option value="III/a">III/a</option><option value="III/b">III/b</option>
                                                    <option value="III/c">III/c</option><option value="III/d">III/d</option>
                                                    <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                                                </select>
                                                <input type="date" name="tmt_gol_lldikti[]" class="form-control form-control-sm mb-2" placeholder="TMT">
                                                <input type="file" name="dok_gol_lldikti[]" class="form-control form-control-sm" accept=".pdf,.jpg,.png">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-light rounded-4">
                                                <label class="form-label">Golongan (Yayasan)</label>
                                                <select name="gol_yayasan[]" class="form-select mb-2">
                                                    <option value="">- Pilih -</option>
                                                    <option value="III/a">III/a</option><option value="III/b">III/b</option>
                                                    <option value="III/c">III/c</option><option value="III/d">III/d</option>
                                                    <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                                                    <option value="IV/c">IV/c</option><option value="IV/d">IV/d</option>
                                                    <option value="IV/e">IV/e</option>
                                                </select>
                                                <input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm mb-2" placeholder="TMT">
                                                <input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm" accept=".pdf,.jpg,.png">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: KUALIFIKASI -->
                        <div class="tab-pane fade" id="kualifikasi" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-7">
                                    <h3 class="section-title"><i class="fas fa-user-graduate"></i>Riwayat Pendidikan Tinggi</h3>
                                    <div id="pendidikan-wrapper">
                                        <div class="dynamic-item" style="border-left: 4px solid #10b981;">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Jenjang</label>
                                                    <select name="pend_jenjang[]" class="form-select" required>
                                                        <option value="">- Pilih -</option>
                                                        <option value="S1">S1</option>
                                                        <option value="S2">S2</option>
                                                        <option value="S3">S3</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">Institusi / Universitas</label>
                                                    <input type="text" name="pend_institusi[]" class="form-control" placeholder="Contoh: Universitas Gadjah Mada" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">TMT Lulus (Bulan/Tahun)</label>
                                                    <input type="date" name="pend_tahun[]" class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Upload Ijazah/Transkrip</label>
                                                    <input type="file" name="dok_pendidikan[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addPendidikan()" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                        <i class="fas fa-plus me-1"></i>Tambah Riwayat Pendidikan
                                    </button>
                                </div>

                                <div class="col-md-5">
                                    <h3 class="section-title"><i class="fas fa-certificate"></i>Sertifikasi Dosen (Serdos)</h3>
                                    <div class="p-4 bg-success bg-opacity-10 rounded-4 border border-success border-opacity-25 mb-4">
                                        <div id="serdos-list-area">
                                            <div class="mb-3">
                                                <label class="form-label">Nomor Sertifikat</label>
                                                <input type="text" name="no_serdos[]" class="form-control mb-3" placeholder="Masukkan nomor Serdos">
                                                
                                                <label class="form-label">TMT Sertifikasi</label>
                                                <input type="date" name="tmt_serdos[]" class="form-control mb-3">
                                                
                                                <label class="form-label">Upload Dokumen Serdos</label>
                                                <input type="file" name="dok_serdos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                            </div>
                                        </div>
                                        <button type="button" onclick="addSerdos()" class="btn btn-sm btn-success rounded-pill w-100 mt-2">
                                            <i class="fas fa-plus me-1"></i>Tambah Serdos Lain
                                        </button>
                                    </div>

                                    <h3 class="section-title mt-5"><i class="fas fa-medal"></i>Penghargaan dan Sanksi</h3>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div id="reward-wrapper"></div>
                                            <button type="button" onclick="addReward()" class="btn btn-sm btn-outline-warning w-100 rounded-pill mb-3">
                                                <i class="fas fa-plus me-1"></i>Tambah Reward
                                            </button>
                                        </div>
                                        <div class="col-12">
                                            <div id="punishment-wrapper"></div>
                                            <button type="button" onclick="addPunishment()" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                                                <i class="fas fa-plus me-1"></i>Tambah Sanksi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: DOKUMEN & LAINNYA -->
                        <div class="tab-pane fade" id="lainnya" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-file-pdf"></i>Dokumen Identitas (Scan)</h3>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="upload-placeholder" onclick="document.querySelector('input[name=\'dok_ktp\']').click()">
                                                <i class="fas fa-id-card fa-2x mb-2 text-primary"></i>
                                                <p class="mb-1 fw-bold">Upload Scan KTP</p>
                                                <p class="text-muted small mb-0">Format PDF/JPG/PNG. Pastikan teks terbaca jelas.</p>
                                                <input type="file" name="dok_ktp" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                                <div id="ktp_name" class="mt-2 text-primary fw-bold small"></div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="upload-placeholder" onclick="document.querySelector('input[name=\'dok_kk\']').click()">
                                                <i class="fas fa-users fa-2x mb-2 text-primary"></i>
                                                <p class="mb-1 fw-bold">Upload Scan Kartu Keluarga</p>
                                                <p class="text-muted small mb-0">Pastikan seluruh halaman KK terlihat.</p>
                                                <input type="file" name="dok_kk" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                                <div id="kk_name" class="mt-2 text-primary fw-bold small"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h3 class="section-title text-danger"><i class="fas fa-user-slash"></i>Status Keaktifan</h3>
                                    <div class="p-4 bg-danger bg-opacity-10 rounded-4 border border-danger border-opacity-25">
                                        <div class="mb-4">
                                            <label class="form-label d-block mb-2">Status Utama</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="status_utama" id="status_aktif" value="Aktif" checked onclick="toggleStatusKeaktifan('Aktif')">
                                                <label class="btn btn-outline-success rounded-start-pill" for="status_aktif">Aktif</label>
                                                
                                                <input type="radio" class="btn-check" name="status_utama" id="status_tidak_aktif" value="Tidak Aktif" onclick="toggleStatusKeaktifan('Tidak Aktif')">
                                                <label class="btn btn-outline-danger rounded-end-pill" for="status_tidak_aktif">Tidak Aktif</label>
                                            </div>
                                        </div>

                                        <div id="wrapper_sub_status" class="mb-3">
                                            <label class="form-label small">Sub-Pilihan Status</label>
                                            <select name="status_keaktifan" id="select_sub_status" class="form-select" onchange="handleSubStatus(this)">
                                                <option value="-">Aktif Normal</option>
                                                <option value="Cuti">Cuti</option>
                                                <option value="Izin Belajar">Izin Belajar</option>
                                                <option value="Tugas Belajar">Tugas Belajar</option>
                                                <option value="Lainnya">Lainnya</option>
                                            </select>
                                        </div>

                                        <div id="wrapper_keaktifan_lainnya" class="hidden mb-3">
                                            <label class="form-label text-danger small">Keterangan Lainnya</label>
                                            <input type="text" name="ket_tidak_aktif_lainnya" class="form-control" placeholder="Jelaskan status...">
                                        </div>

                                        <div id="area_keaktifan_details" class="pt-3 border-top border-danger border-opacity-25">
                                            <div class="mb-3">
                                                <label class="form-label text-danger small">TMT Status (Terhitung Mulai Tanggal)</label>
                                                <input type="date" name="tgl_mulai_tidak_bekerja" class="form-control" required>
                                            </div>
                                            <div>
                                                <label class="form-label text-danger small">Upload Dokumen Pendukung (Wajib)</label>
                                                <input type="file" name="dok_tidak_kerja" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar d-flex justify-content-between align-items-center">
                <div class="text-muted small d-none d-md-block">
                    <i class="fas fa-info-circle me-1"></i>Pastikan data telah diperiksa dengan benar sebelum menyimpan.
                </div>
                <div class="d-flex gap-3">
                    <button type="button" onclick="location.href='daftar_dosen.php'" class="btn btn-light rounded-pill px-4 fw-bold">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i>Simpan Data Dosen
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let isDuplicate = false;

document.addEventListener("DOMContentLoaded", function() {
    // Duplicate Checking Logic
    const inpNip = document.getElementById('inp_nip');
    const inpNidn = document.getElementById('inp_nidn');
    const inpNuptk = document.getElementById('inp_nuptk');

    async function checkDup(type, value, warnId) {
        if(!value) {
            document.getElementById(warnId).style.display = 'none';
            return false;
        }
        try {
            let res = await fetch(`check_duplicate.php?type=${type}&value=${encodeURIComponent(value)}`);
            let data = await res.json();
            if(data.exists) {
                document.getElementById(warnId).innerHTML = `<i class="fas fa-exclamation-triangle"></i> Terdaftar a.n. ${data.name}`;
                document.getElementById(warnId).style.display = 'block';
                return true;
            } else {
                document.getElementById(warnId).style.display = 'none';
                return false;
            }
        } catch(e) { return false; }
    }

    const validateAllDups = async () => {
        let n1 = await checkDup('nip', inpNip.value, 'warn_nip');
        let n2 = await checkDup('nidn', inpNidn.value, 'warn_nidn');
        let n3 = await checkDup('nuptk', inpNuptk.value, 'warn_nuptk');
        isDuplicate = n1 || n2 || n3;
    };

    if(inpNip) inpNip.addEventListener('blur', validateAllDups);
    if(inpNidn) inpNidn.addEventListener('blur', validateAllDups);
    if(inpNuptk) inpNuptk.addEventListener('blur', validateAllDups);
    
    // Prevent Form Submit if duplicate
    document.getElementById('formDosen').addEventListener('submit', function(e) {
        if(isDuplicate) {
            e.preventDefault();
            alert("Gagal menyimpan: NIP / NIDN / NUPTK sudah terdaftar! Mohon periksa kembali input Anda.");
            // Switch to Identitas tab
            const triggerEl = document.querySelector('#pribadi-tab');
            if(triggerEl) {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }
    });

    // File name displays
    document.querySelector('input[name="dok_ktp"]').addEventListener('change', function(){
        document.getElementById('ktp_name').textContent = this.files[0]?.name || '';
    });
    document.querySelector('input[name="dok_kk"]').addEventListener('change', function(){
        document.getElementById('kk_name').textContent = this.files[0]?.name || '';
    });

    // Handle Structural/Non Switch
    document.querySelectorAll('input[name="jenis_dosen"]').forEach(r => {
        r.addEventListener('change', function() {
            const area = document.getElementById('area_jabatan_struktural');
            if (this.value === 'Struktural') {
                area.classList.remove('hidden');
            } else {
                area.classList.add('hidden');
            }
        });
    });
});

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function addStatusDosen() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label text-primary small">Status Dosen</label>
                <select name="status_dosen[]" class="form-select form-select-sm" required>
                    <option value="">- Pilih Status -</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Tidak Tetap">Tidak Tetap</option>
                    <option value="Homebase">Homebase</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">TMT Status</label>
                <input type="date" name="tmt_status[]" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label small">SK Status</label>
                <input type="file" name="dok_status[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function addJabfung() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label text-primary small">Jabatan Akademik</label>
                <select name="jabfung_akademik[]" class="form-select form-select-sm">
                    <option value="">- Pilih Jabatan Akademik -</option>
                    <option value="Asisten Ahli">Asisten Ahli</option>
                    <option value="Asisten Ahli">Asisten Ahli</option>
                    <option value="Lektor">Lektor</option>
                    <option value="Lektor Kepala">Lektor Kepala</option>
                    <option value="Guru Besar">Guru Besar</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">TMT</label>
                <input type="date" name="tmt_jabfung[]" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Upload SK</label>
                <input type="file" name="dok_jabfung[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
    </div>`;
    document.getElementById('jabfung-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPendidikan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label text-success small">Jenjang</label>
                <select name="pend_jenjang[]" class="form-select form-select-sm" required>
                    <option value="">- Pilih -</option>
                    <option value="S1">S1</option><option value="S2">S2</option><option value="S3">S3</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label small">Institusi</label>
                <input type="text" name="pend_institusi[]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Tahun Lulus</label>
                <input type="date" name="pend_tahun[]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">File Ijazah</label>
                <input type="file" name="dok_pendidikan[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
    </div>`;
    document.getElementById('pendidikan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addSerdos() {
    const html = `<div class="p-3 bg-white border rounded-4 mb-2 position-relative">
        <button type="button" onclick="this.closest('div').remove()" class="btn-remove" style="top:10px; right:10px;"><i class="fas fa-times"></i></button>
        <label class="form-label small">Nomor Sertifikat</label>
        <input type="text" name="no_serdos[]" class="form-control form-control-sm mb-2">
        <label class="form-label small">TMT</label>
        <input type="date" name="tmt_serdos[]" class="form-control form-control-sm mb-2">
        <label class="form-label small">File</label>
        <input type="file" name="dok_serdos[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
    </div>`;
    document.getElementById('serdos-list-area').insertAdjacentHTML('beforeend', html);
}

function addReward() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <label class="form-label text-warning small">Deskripsi Penghargaan</label>
        <input type="text" name="reward_deskripsi[]" class="form-control form-control-sm mb-2">
        <div class="row g-2">
            <div class="col-12"><input type="file" name="reward_file[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('reward-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPunishment() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <label class="form-label text-danger small">Deskripsi Sanksi</label>
        <input type="text" name="punishment_deskripsi[]" class="form-control form-control-sm mb-2">
        <div class="row g-2">
            <div class="col-12"><input type="file" name="punishment_file[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('punishment-wrapper').insertAdjacentHTML('beforeend', html);
}

function toggleStatusKeaktifan(main) {
    const selectSub = document.getElementById('select_sub_status');
    const areaLainnya = document.getElementById('wrapper_keaktifan_lainnya');
    
    // Clear sub-options
    selectSub.innerHTML = '';
    areaLainnya.classList.add('hidden');

    if (main === 'Aktif') {
        const opts = [
            {v: '-', t: 'Aktif Normal'},
            {v: 'Cuti', t: 'Cuti'},
            {v: 'Izin Belajar', t: 'Izin Belajar'},
            {v: 'Tugas Belajar', t: 'Tugas Belajar'},
            {v: 'Lainnya', t: 'Lainnya'}
        ];
        opts.forEach(o => {
            let opt = new Option(o.t, o.v);
            selectSub.add(opt);
        });
    } else {
        const opts = [
            {v: 'Diberhentikan', t: 'Diberhentikan'},
            {v: 'Resign', t: 'Resign'},
            {v: 'Pensiun', t: 'Pensiun'},
            {v: 'Lainnya', t: 'Lainnya'}
        ];
        opts.forEach(o => {
            let opt = new Option(o.t, o.v);
            selectSub.add(opt);
        });
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

function addNewItem(selectId, label) {
    const select = document.getElementById(selectId);
    const newVal = prompt("Masukkan " + label + " baru:");
    if (newVal && newVal.trim() !== "") {
        const opt = document.createElement('option');
        opt.value = newVal;
        opt.textContent = newVal;
        opt.selected = true;
        select.insertBefore(opt, select.firstChild);
    }
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

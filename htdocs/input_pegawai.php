<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan'])) {
    $nama          = $_POST['nama_lengkap'];
    $alamat        = $_POST['alamat'];
    $ttl_tempat    = $_POST['ttl_tempat'] ?? '';
    $ttl_tanggal   = $_POST['ttl_tanggal'] ?: null;
    $ttl_lama      = (!empty($ttl_tempat) ? $ttl_tempat . ', ' : '') . (!empty($ttl_tanggal) ? date('d F Y', strtotime($ttl_tanggal)) : '');
    $status_pribadi= $_POST['status_pribadi'] ?? '';
    $jabatan       = $_POST['posisi_jabatan'];
    $unit          = $_POST['unit_kerja'];
    $tmk           = $_POST['tmt_mulai_bekerja'] ?: null;
    $tmtk          = !empty($_POST['tmt_tidak_bekerja']) ? $_POST['tmt_tidak_bekerja'] : null;
    $ket_tmtk      = '';
    if (!empty($tmtk)) {
        $alasan_tmtk = $_POST['alasan_tmtk'] ?? '';
        if ($alasan_tmtk === 'Lainnya') {
            $ket_tmtk = $_POST['alasan_tmtk_lainnya'] ?? 'Lainnya';
        } else {
            $ket_tmtk = $alasan_tmtk;
        }
    }

    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
    if (!is_dir('dokumen')) mkdir('dokumen', 0777, true);

    // Handle dok_status_pegawai upload
    $dok_status_pegawai = '';
    if (!empty($_FILES['dok_status_pegawai']['name']) && $_FILES['dok_status_pegawai']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_status_pegawai']['name'], PATHINFO_EXTENSION);
        $dok_status_pegawai = 'uploads/dok_sp_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['dok_status_pegawai']['tmp_name'], $dok_status_pegawai);
    }

    // Handle KTP upload
    $dok_ktp = '';
    if (!empty($_FILES['dok_ktp']['name']) && $_FILES['dok_ktp']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_ktp']['name'], PATHINFO_EXTENSION);
        $dok_ktp = 'uploads/ktp_p_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
    }

    // Handle KK upload
    $dok_kk = '';
    if (!empty($_FILES['dok_kk']['name']) && $_FILES['dok_kk']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_kk']['name'], PATHINFO_EXTENSION);
        $dok_kk = 'uploads/kk_p_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['dok_kk']['tmp_name'], $dok_kk);
    }

    // Pendidikan list
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
    $pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

    // Handle TMTK Document
    $dok_tmtk = "";
    if (isset($_FILES['dok_tmtk']) && $_FILES['dok_tmtk']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_tmtk']['name'], PATHINFO_EXTENSION);
        $dok_tmtk = "tmtk_" . time() . "." . $ext;
        move_uploaded_file($_FILES['dok_tmtk']['tmp_name'], "dokumen/" . $dok_tmtk);
    }

    $foto_profil = '';
    if(!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = 'uploads/foto_p_'.time().'.'.$ext;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }

    $status_peg_list = [];
    if(!empty($_POST['status_pegawai'])) {
        foreach($_POST['status_pegawai'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status'][$i]) ? $_POST['tmt_status'][$i] : null;
                $filename = $_POST['existing_dok_status'][$i] ?? '';
                if(!empty($_FILES['dok_status']['name'][$i])) {
                    $filename = 'uploads/'.time().'_sp_p_'.basename($_FILES['dok_status']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status']['tmp_name'][$i], $filename);
                }
                $status_peg_list[] = ['status' => $std, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    // Determine main status (latest by TMT)
    usort($status_peg_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $status_pegawai_main = $status_peg_list[0]['status'] ?? '';
    $jenis_pegawai = $status_pegawai_main;

    // Handle Unit Kerja History
    $unit_list = [];
    if(!empty($_POST['unit_kerja_hist'])) {
        foreach($_POST['unit_kerja_hist'] as $i => $uk) {
            if(trim($uk) !== '') {
                $tmt = !empty($_POST['tmt_unit'][$i]) ? $_POST['tmt_unit'][$i] : null;
                $filename = '';
                if(!empty($_FILES['dok_unit']['name'][$i])) {
                    $filename = 'uploads/'.time().'_uk_p_'.basename($_FILES['dok_unit']['name'][$i]);
                    move_uploaded_file($_FILES['dok_unit']['tmp_name'][$i], $filename);
                }
                $unit_list[] = ['unit' => $uk, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    // Sort Unit by TMT DESC
    usort($unit_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $unit_kerja_main = $unit_list[0]['unit'] ?? $unit;

    // Handle Jabfung History
    $jabfung_list = [];
    if(!empty($_POST['jabfung'])) {
        foreach($_POST['jabfung'] as $i => $jab) {
            if(trim($jab) !== '') {
                $tmt = !empty($_POST['tmt_jabfung'][$i]) ? $_POST['tmt_jabfung'][$i] : null;
                $filename = '';
                if(!empty($_FILES['dok_jabfung']['name'][$i])) {
                    $filename = 'uploads/'.time().'_jf_p_'.basename($_FILES['dok_jabfung']['name'][$i]);
                    move_uploaded_file($_FILES['dok_jabfung']['tmp_name'][$i], $filename);
                }
                $jabfung_list[] = ['jabatan' => $jab, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    // Sort Jabfung by TMT DESC
    usort($jabfung_list, function($a, $b) {
        if (!$a['tmt']) return 1;
        if (!$b['tmt']) return -1;
        return strtotime($b['tmt']) - strtotime($a['tmt']);
    });
    $jabatan_main = $jabfung_list[0]['jabatan'] ?? $jabatan;

    // Handle Golongan DIKTI
    $lldikti_list = [];
    if(!empty($_POST['gol_lldikti'])) {
        foreach($_POST['gol_lldikti'] as $i => $gol) {
            if(trim($gol) !== '') {
                $tmt = !empty($_POST['tmt_gol_lldikti'][$i]) ? $_POST['tmt_gol_lldikti'][$i] : null;
                $filename = '';
                if(!empty($_FILES['dok_gol_lldikti']['name'][$i])) {
                    $filename = 'uploads/'.time().'_pl_p_'.basename($_FILES['dok_gol_lldikti']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_lldikti']['tmp_name'][$i], $filename);
                }
                $lldikti_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    $status_peg_latest = $status_peg_list[0]['status'] ?? '';

    // Auto-set status_keaktifan: jika tmt_tidak_bekerja diisi → Tidak Aktif
    $status_keaktifan_insert = !empty($tmtk) ? 'Tidak Aktif' : 'Aktif';
    $keterangan_keaktifan_insert = !empty($ket_tmtk) ? $ket_tmtk : ($status_keaktifan_insert === 'Tidak Aktif' ? 'Diberhentikan' : '-');

    $jenis_pegawai = $_POST['jenis_pegawai'] ?? '';
    $jabatan_struktural = $_POST['jabatan_struktural'] ?? '';
    $tmk_struktural = !empty($_POST['tmk']) ? $_POST['tmk'] : null;
    $dok_penugasan_struktural = '';
    if(!empty($_FILES['dok_penugasan_struktural']['name'])) {
        $dok_penugasan_struktural = 'uploads/'.time().'_str_'.basename($_FILES['dok_penugasan_struktural']['name']);
        move_uploaded_file($_FILES['dok_penugasan_struktural']['tmp_name'], $dok_penugasan_struktural);
    }

    // Insert into pegawai
    $sql = "INSERT INTO pegawai (nama_lengkap, alamat, ttl, ttl_tempat, ttl_tanggal, status_pegawai, status_pribadi, posisi_jabatan, unit_kerja, tmt_mulai_kerja, tmt_tidak_kerja, riwayat_pendidikan, ket_tidak_kerja, keterangan_keaktifan, dok_tmtk, dok_ktp, dok_kk, foto_profil, status_keaktifan, jenis_pegawai, jabatan_struktural, tmk, dok_penugasan_struktural) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $types = str_repeat("s", 23);
    $stmt->bind_param($types,
        $nama, $alamat, $ttl_lama, $ttl_tempat, $ttl_tanggal,
        $status_peg_latest, $status_pribadi,
        $jabatan_main, $unit_kerja_main, $tmk, $tmtk,
        $pendidikan, $ket_tmtk, $keterangan_keaktifan_insert, $dok_tmtk,
        $dok_ktp, $dok_kk, $foto_profil, $status_keaktifan_insert,
        $jenis_pegawai, $jabatan_struktural, $tmk_struktural, $dok_penugasan_struktural
    );
    
    if ($stmt->execute()) {
        $pegawai_id = $conn->insert_id;

        // Dynamic Yayasan
        if (!empty($_POST['gol_yayasan'])) {
            foreach ($_POST['gol_yayasan'] as $i => $gol) {
                if (trim($gol) !== '') {
                    $ytmt = $_POST['tmt_gol_yayasan'][$i] ?: null;
                    $filename = '';
                    if (!empty($_FILES['dok_gol_yayasan']['name'][$i]) && $_FILES['dok_gol_yayasan']['error'][$i] == 0) {
                        $filename = 'uploads/' . time() . '_' . basename($_FILES['dok_gol_yayasan']['name'][$i]);
                        move_uploaded_file($_FILES['dok_gol_yayasan']['tmp_name'][$i], $filename);
                    }
                    $st = $conn->prepare("INSERT INTO yayasan_pegawai (pegawai_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
                    $st->bind_param("isss", $pegawai_id, $gol, $ytmt, $filename);
                    $st->execute();
                    $st->close();
                }
            }
        }

        // Unit Kerja History
        foreach ($unit_list as $ul) {
            $st = $conn->prepare("INSERT INTO unit_kerja_pegawai_riwayat (pegawai_id, unit_kerja, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $pegawai_id, $ul['unit'], $ul['tmt'], $ul['dokumen']);
            $st->execute();
            $st->close();
        }

        // Status History
        foreach ($status_peg_list as $stt) {
            $st = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $pegawai_id, $stt['status'], $stt['tmt'], $stt['dokumen']);
            $st->execute();
            $st->close();
        }

        // Jabfung History
        foreach ($jabfung_list as $jf) {
            $st = $conn->prepare("INSERT INTO jabfung_pegawai (pegawai_id, jabatan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $pegawai_id, $jf['jabatan'], $jf['tmt'], $jf['dokumen']);
            $st->execute();
            $st->close();
        }

        // Golongan DIKTI
        foreach ($lldikti_list as $ld) {
            $st = $conn->prepare("INSERT INTO lldikti_pegawai (pegawai_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $pegawai_id, $ld['golongan'], $ld['tmt'], $ld['dokumen']);
            $st->execute();
            $st->close();
        }

        // Insert Pendidikan
        foreach ($pendidikan_list as $pend) {
            $st = $conn->prepare("INSERT INTO pendidikan_pegawai (pegawai_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
            $st->bind_param("issss", $pegawai_id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
            $st->execute();
            $st->close();
        }

        // Dynamic Rewards
        if (!empty($_POST['reward_desc'])) {
            foreach ($_POST['reward_desc'] as $key => $desc) {
                if (trim($desc) !== '') {
                    $r_date = $_POST['reward_date'][$key] ?: null;
                    $r_file = "";
                    if (isset($_FILES['reward_file']['name'][$key]) && $_FILES['reward_file']['error'][$key] == 0) {
                        $ext = pathinfo($_FILES['reward_file']['name'][$key], PATHINFO_EXTENSION);
                        $r_file = "rew_p_" . time() . "_" . $key . "." . $ext;
                        move_uploaded_file($_FILES['reward_file']['tmp_name'][$key], "dokumen/" . $r_file);
                    }
                    $stmt_r = $conn->prepare("INSERT INTO reward_pegawai (pegawai_id, keterangan, tanggal, dokumen) VALUES (?, ?, ?, ?)");
                    $stmt_r->bind_param("isss", $pegawai_id, $desc, $r_date, $r_file);
                    $stmt_r->execute();
                    $stmt_r->close();
                }
            }
        }

        // Dynamic Punishments
        if (!empty($_POST['punish_desc'])) {
            foreach ($_POST['punish_desc'] as $key => $desc) {
                if (trim($desc) !== '') {
                    $p_date = $_POST['punish_date'][$key] ?: null;
                    $p_file = "";
                    if (isset($_FILES['punish_file']['name'][$key]) && $_FILES['punish_file']['error'][$key] == 0) {
                        $ext = pathinfo($_FILES['punish_file']['name'][$key], PATHINFO_EXTENSION);
                        $p_file = "pun_p_" . time() . "_" . $key . "." . $ext;
                        move_uploaded_file($_FILES['punish_file']['tmp_name'][$key], "dokumen/" . $p_file);
                    }
                    $stmt_p = $conn->prepare("INSERT INTO punishment_pegawai (pegawai_id, keterangan, tanggal, dokumen) VALUES (?, ?, ?, ?)");
                    $stmt_p->bind_param("isss", $pegawai_id, $desc, $p_date, $p_file);
                    $stmt_p->execute();
                    $stmt_p->close();
                }
            }
        }

        echo "<script>alert('Data pegawai berhasil disimpan!');location='data_pegawai.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan data: " . addslashes($conn->error) . "');</script>";
    }
    $stmt->close();
}

$current_page = 'pegawai_tambah';
$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'],
    ['label' => 'Registrasi Baru', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pegawai | Kepegawaian UNSERA</title>
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
            font-size: 1.1rem; 
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-title i { color: var(--bs-primary); }
        .dynamic-item { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 12px; 
            margin-bottom: 12px; 
            position: relative; 
            transition: all 0.2s;
        }
        .dynamic-item:hover { border-color: #cbd5e1; background: #f1f5f9; }
        .btn-remove { 
            position: absolute; 
            top: 8px; 
            right: 8px; 
            background: #fee2e2; 
            color: #ef4444; 
            border: none; 
            width: 24px; 
            height: 24px; 
            border-radius: 6px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: all 0.2s;
            z-index: 10;
        }
        .btn-remove:hover { background: #ef4444; color: white; }
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
                <h1 class="h2 fw-bold text-dark mb-1">Registrasi Pegawai Baru</h1>
                <p class="text-muted mb-0">Pendaftaran tenaga kependidikan dan staf administrasi baru UNSERA.</p>
            </div>
            <a href="data_pegawai.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formPegawai" onsubmit="return confirm('Apakah Anda yakin ingin menyimpan data ini?')">
            
            <div class="card mb-4 border-0">
                <div class="card-body p-4">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-4" id="pegawaiTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pribadi-tab" data-bs-toggle="tab" data-bs-target="#pribadi" type="button" role="tab"><i class="fas fa-user me-2"></i>Profil Pegawai</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="kepegawaian-tab" data-bs-toggle="tab" data-bs-target="#kepegawaian" type="button" role="tab"><i class="fas fa-briefcase me-2"></i>Kepegawaian</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="kualifikasi-tab" data-bs-toggle="tab" data-bs-target="#kualifikasi" type="button" role="tab"><i class="fas fa-graduation-cap me-2"></i>Kualifikasi</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lainnya-tab" data-bs-toggle="tab" data-bs-target="#lainnya" type="button" role="tab"><i class="fas fa-folder-open me-2"></i>Lainnya</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="pegawaiTabsContent">
                        
                        <!-- TAB 1: DATA PRIBADI -->
                        <div class="tab-pane fade show active" id="pribadi" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="text-center p-4 bg-light rounded-4 border">
                                        <div class="mb-3">
                                            <div id="photoPreview" class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px; overflow: hidden; border: 4px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                                <i class="fas fa-camera text-secondary" style="font-size: 5rem;"></i>
                                            </div>
                                        </div>
                                        <label class="form-label d-block">Foto Profil Pegawai</label>
                                        <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                        <small class="text-muted d-block mt-2">JPG/PNG, Maks 2MB</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h3 class="section-title"><i class="fas fa-id-card"></i>Identitas Diri</h3>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Nama Lengkap & Gelar</label>
                                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Ahmad Subarjo, S.Kom." required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tempat Lahir</label>
                                            <input type="text" name="ttl_tempat" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="ttl_tanggal" class="form-control" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Alamat Sesuai KTP</label>
                                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: KEPEGAWAIAN -->
                        <div class="tab-pane fade" id="kepegawaian" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="section-title"><i class="fas fa-id-card"></i> Status Kepegawaian & Jabatan</div>
                                    <div class="p-4 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 mb-4">
                                        <div class="row g-4">
                                            <div class="col-md-5">
                                                <label class="form-label text-primary fw-bold small">Riwayat Status Pegawai</label>
                                                <div id="status-wrapper">
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
                                                            <div class="col-6"><input type="date" name="tmt_status[]" class="form-control form-control-sm" title="TMT Mulai" required></div>
                                                            <div class="col-6"><input type="file" name="dok_status[]" class="form-control form-control-sm"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-xs btn-outline-primary mt-1" onclick="addStatusRow()"><i class="fas fa-plus"></i> Tambah Riwayat</button>
                                            </div>
                                            <div class="col-md-7 border-start border-primary border-opacity-10">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" id="toggleStruk" onchange="document.getElementById('area_jabatan_struktural').classList.toggle('d-none', !this.checked)">
                                                    <label class="form-check-label fw-bold text-primary small" for="toggleStruk">Memiliki Jabatan Struktural</label>
                                                </div>
                                                <div id="area_jabatan_struktural" class="d-none">
                                                    <div class="row g-2">
                                                        <div class="col-md-7">
                                                            <input type="text" name="jabatan_struktural" class="form-control form-control-sm" placeholder="Nama Jabatan Struktural">
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="date" name="tmk" class="form-control form-control-sm" title="TMT Jabatan Struktural">
                                                        </div>
                                                        <div class="col-12 mt-1">
                                                            <label class="tiny text-muted">SK Penugasan (PDF/JPG)</label>
                                                            <input type="file" name="dok_penugasan_struktural" class="form-control form-control-sm">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                   <label class="form-label text-primary fw-bold small">TMT Berhenti (Jika Ada)</label>
                                                   <input type="date" name="tmtk" class="form-control form-control-sm">
                                               </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-4 bg-light rounded-4">
                                         <div class="row g-4">
                                             <div class="col-md-6">
                                                 <h3 class="section-title mb-3"><i class="fas fa-map-marker-alt"></i>Riwayat Unit Kerja</h3>
                                                 <div id="unit-kerja-wrapper">
                                                     <div class="dynamic-item p-2 mb-2 bg-white border rounded shadow-sm">
                                                         <div class="row g-2">
                                                             <div class="col-12">
                                                                 <input type="text" name="unit_kerja_hist[]" class="form-control form-control-sm" placeholder="Nama Unit Kerja (Biro/Bagian/UPT)" required>
                                                             </div>
                                                             <div class="col-6"><input type="date" name="tmt_unit[]" class="form-control form-control-sm"></div>
                                                             <div class="col-6"><input type="file" name="dok_unit[]" class="form-control form-control-sm"></div>
                                                         </div>
                                                     </div>
                                                 </div>
                                                 <button type="button" onclick="addUnitKerja()" class="btn btn-xs btn-outline-primary mt-1"><i class="fas fa-plus"></i> Tambah Unit Kerja</button>
                                             </div>
                                             <div class="col-md-6">
                                                 <h3 class="section-title mb-3"><i class="fas fa-briefcase"></i>Riwayat Jabatan</h3>
                                                 <div id="jabatan-wrapper">
                                                     <div class="dynamic-item p-2 mb-2 bg-white border rounded shadow-sm">
                                                         <div class="row g-2">
                                                             <div class="col-12">
                                                                 <input type="text" name="jabfung[]" class="form-control form-control-sm" placeholder="Posisi Jabatan" required>
                                                             </div>
                                                             <div class="col-6"><input type="date" name="tmt_jabfung[]" class="form-control form-control-sm"></div>
                                                             <div class="col-6"><input type="file" name="dok_jabfung[]" class="form-control form-control-sm"></div>
                                                         </div>
                                                     </div>
                                                 </div>
                                                 <button type="button" onclick="addJabatan()" class="btn btn-xs btn-outline-primary mt-1"><i class="fas fa-plus"></i> Tambah Jabatan</button>
                                             </div>
                                         </div>

                                         <div class="mt-4 pt-4 border-top">
                                             <h3 class="section-title mb-3"><i class="fas fa-award"></i>Golongan & Kepangkatan</h3>
                                             <div id="yayasan-wrapper">
                                                 <div class="dynamic-item p-2 mb-2 bg-white border rounded shadow-sm" style="max-width: 500px;">
                                                     <div class="row g-2">
                                                         <div class="col-12">
                                                             <select name="gol_yayasan[]" class="form-select form-select-sm">
                                                                 <option value="">- Pilih Golongan -</option>
                                                                 <?php $gols=['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d']; 
                                                                 foreach($gols as $g) echo "<option value='$g'>$g</option>"; ?>
                                                             </select>
                                                         </div>
                                                         <div class="col-6"><input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm"></div>
                                                         <div class="col-6"><input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm"></div>
                                                     </div>
                                                 </div>
                                             </div>
                                             <button type="button" onclick="addYayasan()" class="btn btn-xs btn-outline-primary mt-1"><i class="fas fa-plus"></i> Tambah Riwayat Yayasan</button>
                                         </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: KUALIFIKASI -->
                        <div class="tab-pane fade" id="kualifikasi" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-7">
                                    <h3 class="section-title"><i class="fas fa-user-graduate"></i>Riwayat Pendidikan</h3>
                                    <div id="pendidikan-wrapper">
                                        <div class="dynamic-item" style="border-left: 4px solid #10b981;">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Jenjang</label>
                                                    <select name="pend_jenjang[]" class="form-select" required>
                                                        <option value="">- Pilih -</option>
                                                        <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $p): ?>
                                                            <option value="<?= $p ?>"><?= $p ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">Institusi / Sekolah</label>
                                                    <input type="text" name="pend_institusi[]" class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">TMT Lulus</label>
                                                    <input type="date" name="pend_tahun[]" class="form-control" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Upload Ijazah & Transkrip</label>
                                                    <input type="file" name="dok_pendidikan[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addPendidikan()" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                        <i class="fas fa-plus me-1"></i>Tambah Pendidikan
                                    </button>
                                </div>

                                <div class="col-md-5">
                                    <h3 class="section-title"><i class="fas fa-medal"></i>Penghargaan dan Sanksi</h3>
                                    <div id="reward-wrapper"></div>
                                    <button type="button" onclick="addReward()" class="btn btn-sm btn-outline-warning w-100 rounded-pill mb-3">
                                        <i class="fas fa-plus me-1"></i>Tambah Penghargaan
                                    </button>

                                    <div id="punishment-wrapper"></div>
                                    <button type="button" onclick="addPunish()" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                                        <i class="fas fa-plus me-1"></i>Tambah Sanksi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: LAINNYA -->
                        <div class="tab-pane fade" id="lainnya" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-file-pdf"></i>Dokumen Identitas</h3>
                                    <div class="p-4 bg-light rounded-4">
                                        <div class="mb-4">
                                            <label class="form-label">Scan KTP (PDF/JPG)</label>
                                            <input type="file" name="dok_ktp" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">Scan Kartu Keluarga</label>
                                            <input type="file" name="dok_kk" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
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

                                        <div id="area_keaktifan_details" class="pt-3 border-top border-danger border-opacity-25 hidden">
                                            <div class="mb-3">
                                                <label class="form-label text-danger small">TMT Mulai Tidak Bekerja</label>
                                                <input type="date" name="tmt_tidak_bekerja" class="form-control">
                                            </div>
                                            <div>
                                                <label class="form-label text-danger small">Upload Dokumen Pendukung (SK/Surat)</label>
                                                <input type="file" name="dok_tmtk" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
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
                    <i class="fas fa-info-circle me-1"></i>Periksa kembali semua tab sebelum menyimpan data.
                </div>
                <div class="d-flex gap-3">
                    <button type="button" onclick="location.href='data_pegawai.php'" class="btn btn-light rounded-pill px-4 fw-bold">Batal</button>
                    <button type="submit" name="simpan" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i>Simpan Data Pegawai
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}function addStatusRow() {
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
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function addUnitKerja() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12">
                <input type="text" name="unit_kerja_hist[]" class="form-control form-control-sm" placeholder="Nama Unit Kerja (Biro/Bagian/UPT)" required>
            </div>
            <div class="col-6"><input type="date" name="tmt_unit[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_unit[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('unit-kerja-wrapper').insertAdjacentHTML('beforeend', html);
}

function addJabatan() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12">
                <input type="text" name="jabfung[]" class="form-control form-control-sm" placeholder="Posisi Jabatan" required>
            </div>
            <div class="col-6"><input type="date" name="tmt_jabfung[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_jabfung[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('jabatan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addYayasan() {
    const html = `<div class="dynamic-item p-2 mb-2 bg-white border">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-2">
            <div class="col-12">
                <select name="gol_yayasan[]" class="form-select form-select-sm">
                    <option value="">- Pilih Golongan -</option>
                    <option value="I/a">I/a</option><option value="I/b">I/b</option>
                    <option value="I/c">I/c</option><option value="I/d">I/d</option>
                    <option value="II/a">II/a</option><option value="II/b">II/b</option>
                    <option value="II/c">II/c</option><option value="II/d">II/d</option>
                    <option value="III/a">III/a</option><option value="III/b">III/b</option>
                    <option value="III/c">III/c</option><option value="III/d">III/d</option>
                    <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                </select>
            </div>
            <div class="col-6"><input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm"></div>
            <div class="col-6"><input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('yayasan-wrapper').insertAdjacentHTML('beforeend', html);
}



function addPendidikan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label text-success small">Jenjang</label>
                <select name="pend_jenjang[]" class="form-select form-select-sm" required>
                    <option value="">- Pilih -</option>
                    <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $p): ?>
                        <option value="<?= $p ?>"><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label small">Institusi</label>
                <input type="text" name="pend_institusi[]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">TMT Lulus</label>
                <input type="date" name="pend_tahun[]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Upload File</label>
                <input type="file" name="dok_pendidikan[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
    </div>`;
    document.getElementById('pendidikan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addReward() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <label class="form-label text-warning small">Deskripsi Penghargaan</label>
        <input type="text" name="reward_desc[]" class="form-control form-control-sm mb-2" placeholder="Contoh: Karyawan Teladan 2023">
        <div class="row g-2">
            <div class="col-md-6"><label class="small">TMT/Tanggal</label><input type="date" name="reward_date[]" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="small">File Sertifikat</label><input type="file" name="reward_file[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('reward-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPunish() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <label class="form-label text-danger small">Deskripsi Sanksi</label>
        <input type="text" name="punish_desc[]" class="form-control form-control-sm mb-2" placeholder="Deskripsi sanksi...">
        <div class="row g-2">
            <div class="col-md-6"><label class="small">TMT/Tanggal</label><input type="date" name="punish_date[]" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="small">File Pendukung</label><input type="file" name="punish_file[]" class="form-control form-control-sm"></div>
        </div>
    </div>`;
    document.getElementById('punishment-wrapper').insertAdjacentHTML('beforeend', html);
}

function toggleStatusKeaktifan(main) {
    const selectSub = document.getElementById('select_sub_status');
    const areaLainnya = document.getElementById('wrapper_keaktifan_lainnya');
    const areaDetails = document.getElementById('area_keaktifan_details');
    
    selectSub.innerHTML = '';
    areaLainnya.classList.add('hidden');

    if (main === 'Aktif') {
        areaDetails.classList.add('hidden');
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
        areaDetails.classList.remove('hidden');
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

function toggleAlasanBerhenti() {
    const tmt = document.getElementById('tmtTidakBekerja');
    const wrapper = document.getElementById('wrapper_alasan_berhenti');
    if (tmt && tmt.value) {
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
        document.getElementById('wrapper_alasan_lainnya').style.display = 'none';
    }
}

function toggleAlasanLainnya() {
    const sel = document.getElementById('alasanBerhenti');
    const wrapper = document.getElementById('wrapper_alasan_lainnya');
    if (sel && sel.value === 'Lainnya') {
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
    }
}


// Initialize custom selects on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#jabatan-wrapper select, #unitkerja-wrapper select').forEach(setupCustomSelect);
    
    // Structural toggle handled via inline onchange in HTML
});
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
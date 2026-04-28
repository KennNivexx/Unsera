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
    $ttl_lama      = ($ttl_tempat ? $ttl_tempat . ', ' : '') . ($ttl_tanggal ? date('d F Y', strtotime($ttl_tanggal)) : '');
    $status_pribadi= $_POST['status_pribadi'] ?? '';
    $jabatan       = $_POST['posisi_jabatan'];
    $unit          = $_POST['unit_kerja'];
    $tmk           = $_POST['tmt_mulai_bekerja'] ?: null;
    $tmtk          = $_POST['tmt_tidak_bekerja'] ?: null;
    $ket_tmtk      = $_POST['ket_tmtk'] ?? '';

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
                $tmt = !empty($_POST['tmt_status_pegawai'][$i]) ? $_POST['tmt_status_pegawai'][$i] : null;
                $tgl_berhenti = !empty($_POST['tmt_tidak_kerja_status'][$i]) ? $_POST['tmt_tidak_kerja_status'][$i] : null;
                $alasan = !empty($_POST['alasan_berhenti_status_riw'][$i]) ? $_POST['alasan_berhenti_status_riw'][$i] : null;
                $alasan_lain = ($alasan === 'Dan Lainnya') ? ($_POST['alasan_lainnya_status_riw'][$i] ?? '') : null;

                $filename = '';
                if(!empty($_FILES['dok_status_peg_riwayat']['name'][$i])) {
                    $filename = 'uploads/'.time().'_sp_'.basename($_FILES['dok_status_peg_riwayat']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status_peg_riwayat']['tmp_name'][$i], $filename);
                }
                $status_peg_list[] = [
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
    $status_peg_latest = $status_peg_list[0]['status'] ?? '';

    // Insert into pegawai
    $sql = "INSERT INTO pegawai (nama_lengkap, alamat, ttl, ttl_tempat, ttl_tanggal, status_pegawai, status_pribadi, posisi_jabatan, unit_kerja, tmt_mulai_kerja, tmt_tidak_kerja, riwayat_pendidikan, ket_tidak_kerja, dok_tmtk, dok_ktp, dok_kk, foto_profil) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssss",
        $nama, $alamat, $ttl_lama, $ttl_tempat, $ttl_tanggal,
        $status_peg_latest, $status_pribadi,
        $jabatan, $unit, $tmk, $tmtk,
        $pendidikan, $ket_tmtk, $dok_tmtk,
        $dok_ktp, $dok_kk, $foto_profil
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

        // Status History
        foreach ($status_peg_list as $stt) {
            $st = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, tmt, tgl_berhenti, alasan, alasan_lainnya, dokumen) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $st->bind_param("issssss", $pegawai_id, $stt['status'], $stt['tmt'], $stt['tgl_berhenti'], $stt['alasan'], $stt['alasan_lainnya'], $stt['dokumen']);
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
                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-briefcase"></i>Status Pegawai</h3>
                                    <div id="status-wrapper">
                                        <div class="dynamic-item">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Status Saat Ini</label>
                                                    <select name="status_pegawai[]" class="form-select" required>
                                                        <option value="">- Pilih Status -</option>
                                                        <option value="Tetap">Tetap</option>
                                                        <option value="Tidak Tetap">Tidak Tetap</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">TMT Status</label>
                                                    <input type="date" name="tmt_status_pegawai[]" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">SK Status</label>
                                                    <input type="file" name="dok_status_peg_riwayat[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addStatusPegawai()" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fas fa-plus me-1"></i>Tambah Riwayat Status
                                    </button>

                                    <div class="mt-5 p-4 bg-light rounded-4">
                                        <h3 class="section-title mb-3"><i class="fas fa-map-marker-alt"></i>Jabatan</h3>
                                        <div class="row g-3">
                                            <div class="col-md-10">
                                                <label class="form-label">Jabatan</label>
                                                <select name="posisi_jabatan" id="input_jabatan" class="form-select" required>
                                                    <option value="">- Pilih Jabatan -</option>
                                                    <option value="Staf IT">Staf IT</option>
                                                    <option value="Administrasi">Administrasi</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" onclick="addNewItem('input_jabatan', 'Jabatan')" class="btn btn-outline-primary w-100 rounded-10">+ Baru</button>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-10">
                                                <label class="form-label">Unit Kerja</label>
                                                <select name="unit_kerja" class="form-select" id="select_unit" required>
                                                    <option value="">- Pilih Unit Kerja -</option>
                                                    <option value="Biro Umum">Biro Umum</option>
                                                    <option value="Biro Kepegawaian">Biro Kepegawaian</option>
                                                    <option value="BAAK">BAAK</option>
                                                    <option value="UPT Perpustakaan">UPT Perpustakaan</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" onclick="addNewItem('select_unit', 'Unit Kerja')" class="btn btn-outline-primary w-100 rounded-10">+ Baru</button>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-12">
                                                <label class="form-label">Tanggal Mulai Bekerja (Pertama Kali)</label>
                                                <input type="date" name="tmt_mulai_bekerja" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-university"></i>Golongan Yayasan</h3>
                                    <div id="yayasan-wrapper">
                                        <div class="dynamic-item">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Golongan Yayasan</label>
                                                    <select name="gol_yayasan[]" class="form-select">
                                                        <option value="">- Pilih Golongan -</option>
                                                        <optgroup label="Golongan I">
                                                            <option value="I/a">I/a</option><option value="I/b">I/b</option>
                                                        </optgroup>
                                                        <optgroup label="Golongan II">
                                                            <option value="II/a">II/a</option><option value="II/b">II/b</option>
                                                        </optgroup>
                                                        <optgroup label="Golongan III">
                                                            <option value="III/a">III/a</option><option value="III/b">III/b</option><option value="III/c">III/c</option>
                                                        </optgroup>
                                                        <optgroup label="Golongan IV">
                                                            <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option><option value="IV/c">IV/c</option><option value="IV/d">IV/d</option><option value="IV/e">IV/e</option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">TMT Golongan</label>
                                                    <input type="date" name="tmt_gol_yayasan[]" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Upload SK Golongan</label>
                                                    <input type="file" name="dok_gol_yayasan[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addYayasan()" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fas fa-plus me-1"></i>Tambah Riwayat Golongan
                                    </button>
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
                                                    <label class="form-label">Ijazah & Transkrip</label>
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

                                        <div id="area_keaktifan_details" class="pt-3 border-top border-danger border-opacity-25">
                                            <div class="mb-3">
                                                <label class="form-label text-danger small">TMT Status (Terhitung Mulai Tanggal)</label>
                                                <input type="date" name="tmt_tidak_bekerja" class="form-control" required>
                                            </div>
                                            <div>
                                                <label class="form-label text-danger small">Upload Dokumen Pendukung (Wajib)</label>
                                                <input type="file" name="dok_tmtk" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
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
}

function addStatusPegawai() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label text-primary small">Status Pegawai</label>
                <select name="status_pegawai[]" class="form-select form-select-sm" required>
                    <option value="">- Pilih Status -</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Tidak Tetap">Tidak Tetap</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">TMT Status</label>
                <input type="date" name="tmt_status_pegawai[]" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label small">SK Status</label>
                <input type="file" name="dok_status_peg_riwayat[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function addYayasan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-remove"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label text-primary small">Golongan Yayasan</label>
                <select name="gol_yayasan[]" class="form-select form-select-sm">
                    <option value="">- Pilih Golongan -</option>
                    <optgroup label="Golongan I"><option value="I/a">I/a</option><option value="I/b">I/b</option></optgroup>
                    <optgroup label="Golongan II"><option value="II/a">II/a</option><option value="II/b">II/b</option></optgroup>
                    <optgroup label="Golongan III"><option value="III/a">III/a</option><option value="III/b">III/b</option><option value="III/c">III/c</option></optgroup>
                    <optgroup label="Golongan IV"><option value="IV/a">IV/a</option><option value="IV/b">IV/b</option><option value="IV/c">IV/c</option><option value="IV/d">IV/d</option><option value="IV/e">IV/e</option></optgroup>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">TMT</label>
                <input type="date" name="tmt_gol_yayasan[]" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label small">File SK</label>
                <input type="file" name="dok_gol_yayasan[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
            </div>
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
            <div class="col-12"><input type="file" name="reward_file[]" class="form-control form-control-sm"></div>
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
            <div class="col-12"><input type="file" name="punish_file[]" class="form-control form-control-sm"></div>
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

function addNewItem(id, label) {
    const el = document.getElementById(id);
    const newVal = prompt("Masukkan " + label + " baru:");
    if (newVal && newVal.trim() !== "") {
        if (el.tagName === 'SELECT') {
            const opt = document.createElement('option');
            opt.value = newVal;
            opt.textContent = newVal;
            opt.selected = true;
            el.insertBefore(opt, el.firstChild);
        } else {
            // If it's a select with no current options or we want to turn it into a list
            const opt = document.createElement('option');
            opt.value = newVal;
            opt.textContent = newVal;
            opt.selected = true;
            if (el.tagName === 'INPUT') {
                // For input field, we just set the value
                el.value = newVal;
            }
        }
    }
}
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
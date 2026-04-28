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
$rewards = $conn->query("SELECT * FROM reward WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$punishments = $conn->query("SELECT * FROM punishment WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$jabfungs = $conn->query("SELECT * FROM jabfung_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$lldiktis = $conn->query("SELECT * FROM lldikti_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$yayasans = $conn->query("SELECT * FROM yayasan_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_dosen WHERE dosen_id = $id ORDER BY tahun_lulus DESC")->fetch_all(MYSQLI_ASSOC);
$status_dosens = $conn->query("SELECT * FROM status_dosen_riwayat WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$serdoses = $conn->query("SELECT * FROM sertifikasi_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'];
    $ttl_tanggal = $_POST['ttl_tanggal'];
    $nip = !empty($_POST['nip']) ? trim($_POST['nip']) : null;
    $nidn = !empty($_POST['nidn']) ? trim($_POST['nidn']) : null;
    $nuptk = !empty($_POST['nuptk']) ? trim($_POST['nuptk']) : null;

    // Check for duplicates excluding current dosen ID
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
    $status_keaktifan = $_POST['status_keaktifan'] ?? 'Aktif';
    $tgl_mulai_tidak_bekerja = !empty($_POST['tgl_mulai_tidak_bekerja']) ? $_POST['tgl_mulai_tidak_bekerja'] : null;
    
    $keterangan_keaktifan = '';
    if($status_keaktifan === 'Tidak Aktif') {
        $keterangan_keaktifan = $_POST['ket_tidak_aktif'] ?? '';
        if($keterangan_keaktifan === 'Dan Lainnya') {
            $keterangan_keaktifan = $_POST['ket_tidak_aktif_lainnya'] ?? '';
        }
    }

    // Handle Status Riwayat
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

    // Handle Jabfung
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

    // Latest values for main table
    $status_dosen_main = $status_list[0]['status'] ?? '';
    $jabfung_akademik = $jabfung_list[0]['jabatan'] ?? '';
    $tmt_jabfung = $jabfung_list[0]['tmt'] ?? null;
    $dok_jabfung_main = $jabfung_list[0]['dokumen'] ?? '';

    // Handle Pendidikan
    $pendidikan_list = [];
    if(!empty($_POST['pend_jenjang'])) {
        foreach($_POST['pend_jenjang'] as $i => $jenjang) {
            if(trim($jenjang) !== '') {
                $institusi = $_POST['pend_institusi'][$i] ?? '';
                $tahun = $_POST['pend_tahun'][$i] ?? '';
                $filename = $_POST['existing_dok_pendidikan'][$i] ?? '';
                if(!empty($_FILES['dok_pendidikan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_edu_'.basename($_FILES['dok_pendidikan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_pendidikan']['tmp_name'][$i], $filename);
                }
                $pendidikan_list[] = ['jenjang' => $jenjang, 'institusi' => $institusi, 'tahun_lulus' => $tahun, 'dokumen' => $filename];
            }
        }
    }
    $riwayat_pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

    // Other Golongan Histroy (simplified latest)
    $gol_lldikti = $_POST['gol_lldikti'][0] ?? '';
    $tmt_gol_lldikti = $_POST['tmt_gol_lldikti'][0] ?? null;
    $gol_yayasan = $_POST['gol_yayasan'][0] ?? '';
    $tmt_gol_yayasan = $_POST['tmt_gol_yayasan'][0] ?? null;
    
    // Serdos
    $no_serdos_main = $_POST['no_serdos'][0] ?? null;
    $tmt_serdos_main = $_POST['tmt_serdos'][0] ?? null;

    // Files
    $dok_ktp = $data['dok_ktp'];
    if(!empty($_FILES['dok_ktp']['name'])) {
        $dok_ktp = 'uploads/'.time().'_ktp_'.basename($_FILES['dok_ktp']['name']);
        move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
    }
    $dok_kk = $data['dok_kk'];
    if(!empty($_FILES['dok_kk']['name'])) {
        $dok_kk = 'uploads/'.time().'_kk_'.basename($_FILES['dok_kk']['name']);
        move_uploaded_file($_FILES['dok_kk']['tmp_name'], $dok_kk);
    }
    $dok_tidak_kerja = $data['dok_tidak_kerja'];
    if(!empty($_FILES['dok_tidak_kerja']['name'])) {
        $dok_tidak_kerja = 'uploads/'.time().'_tj_'.basename($_FILES['dok_tidak_kerja']['name']);
        move_uploaded_file($_FILES['dok_tidak_kerja']['tmp_name'], $dok_tidak_kerja);
    }
    $foto_profil = $data['foto_profil'];
    if(!empty($_FILES['foto_profil']['name'])) {
        $foto_profil = 'uploads/foto_'.time().'_'.basename($_FILES['foto_profil']['name']);
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }

    // Ensure these document paths are preserved or updated (if inputs existed)
    $dok_gol_lldikti = $data['dok_gol_lldikti'] ?? '';
    $dok_gol_yayasan = $data['dok_gol_yayasan'] ?? '';
    $dok_serdos = $data['dok_serdos'] ?? '';

    $homebase_prodi = $_POST['homebase_prodi'];
    $unit_kerja = $_POST['unit_kerja'];

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
        keterangan_keaktifan = ?, tgl_mulai_tidak_bekerja = ?
        WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    // 36 columns in SET + 1 in WHERE = 37 parameters
    $types = str_repeat("s", 36) . "i";
    
    $stmt->bind_param($types, 
        $nama, $alamat, $ttl_tempat, $ttl_tanggal, 
        $nip, $nidn, $nuptk, $status_dosen_main, $status_pribadi, 
        $dok_ktp, $dok_kk, $jenis_dosen, $jabatan_struktural, 
        $tmk, $tmtk, $ket_tidak_kerja, $dok_tidak_kerja,
        $jabfung_akademik, $tmt_jabfung, $dok_jabfung_main,
        $gol_lldikti, $tmt_gol_lldikti, $dok_gol_lldikti,
        $gol_yayasan, $tmt_gol_yayasan, $dok_gol_yayasan,
        $homebase_prodi, $unit_kerja, $no_serdos_main, $tmt_serdos_main, $dok_serdos,
        $riwayat_pendidikan, $foto_profil, $status_keaktifan, 
        $keterangan_keaktifan, $tgl_mulai_tidak_bekerja,
        $id
    );
    
    if ($stmt->execute()) {
        // Sync Histories
        $conn->query("DELETE FROM status_dosen_riwayat WHERE dosen_id = $id");
        foreach ($status_list as $stt) {
            $st = $conn->prepare("INSERT INTO status_dosen_riwayat (dosen_id, status_dosen, tmt, tgl_berhenti, alasan, alasan_lainnya, dokumen) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $st->bind_param("issssss", $id, $stt['status'], $stt['tmt'], $stt['tgl_berhenti'], $stt['alasan'], $stt['alasan_lainnya'], $stt['dokumen']);
            $st->execute();
        }
        
        $conn->query("DELETE FROM jabfung_dosen WHERE dosen_id = $id");
        foreach ($jabfung_list as $jf) {
            $st = $conn->prepare("INSERT INTO jabfung_dosen (dosen_id, jabatan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $id, $jf['jabatan'], $jf['tmt'], $jf['dokumen']);
            $st->execute();
        }

        $conn->query("DELETE FROM pendidikan_dosen WHERE dosen_id = $id");
        foreach ($pendidikan_list as $pend) {
            $st = $conn->prepare("INSERT INTO pendidikan_dosen (dosen_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
            $st->bind_param("issss", $id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
            $st->execute();
        }
        
        // Rewards & Punishments
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

        echo "<script>alert('Data dosen berhasil diperbarui!');location='detail_dosen.php?id=$id';</script>";
    } else {
        echo "<script>alert('Error: " . addslashes($stmt->error) . "');</script>";
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
        .form-container { background: white; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 3rem; }
        .nav-tabs-custom { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0 20px; display: flex; gap: 5px; }
        .nav-tabs-custom .nav-link { border: none; padding: 1.2rem 1.5rem; color: #64748b; font-weight: 600; font-size: 0.9rem; position: relative; transition: all 0.3s; }
        .nav-tabs-custom .nav-link.active { color: var(--primary); background: transparent; }
        .nav-tabs-custom .nav-link.active::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; background: var(--primary); border-radius: 3px 3px 0 0; }
        .tab-content { padding: 30px; }
        .form-label { font-weight: 600; color: #334155; font-size: 0.85rem; margin-bottom: 8px; display: block; }
        .form-control, .form-select { border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0.75rem 1rem; font-size: 0.9rem; transition: all 0.2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .dynamic-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 20px; position: relative; }
        .btn-remove { position: absolute; top: 15px; right: 15px; width: 32px; height: 32px; border-radius: 8px; background: #fee2e2; color: #ef4444; border: none; display: flex; align-items: center; justify-content: center; }
        .section-title { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--primary); }
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
            <div class="form-container shadow-sm">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs-custom" id="formTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pribadi" type="button">1. Data Pribadi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kepegawaian" type="button">2. Kepegawaian</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kualifikasi" type="button">3. Kualifikasi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">4. Reward & Punishment</button></li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Data Pribadi -->
                    <div class="tab-pane fade show active" id="pribadi">
                        <div class="row g-4">
                            <div class="col-md-3 text-center border-end">
                                <div class="mb-3">
                                    <label class="form-label">Foto Profil</label>
                                    <div class="mx-auto mb-3" style="width: 150px; height: 150px; border-radius: 20px; overflow: hidden; border: 4px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                        <img id="previewFoto" src="<?= !empty($data['foto_profil']) ? $data['foto_profil'] : 'https://ui-avatars.com/api/?name='.urlencode($data['nama_lengkap']).'&size=150' ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="section-title"><i class="fas fa-id-card"></i> Informasi Identitas</div>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Nama Lengkap (beserta gelar)</label>
                                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" required>
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
                                        <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($data['nip'] ?? '') ?>" placeholder="Nomor Induk Pegawai">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NIDN</label>
                                        <input type="text" name="nidn" class="form-control" value="<?= htmlspecialchars($data['nidn'] ?? '') ?>" placeholder="Nomor Induk Dosen Nasional">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NUPTK</label>
                                        <input type="text" name="nuptk" class="form-control" value="<?= htmlspecialchars($data['nuptk'] ?? '') ?>" placeholder="Nomor Unik Pendidik">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Alamat Sesuai KTP</label>
                                        <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($data['alamat']) ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Pernikahan</label>
                                        <select name="status_pribadi" class="form-select" required>
                                            <option value="Menikah" <?= $data['status_pribadi'] == 'Menikah' ? 'selected' : '' ?>>Menikah</option>
                                            <option value="Belum Menikah" <?= $data['status_pribadi'] == 'Belum Menikah' ? 'selected' : '' ?>>Belum Menikah</option>
                                            <option value="Bercerai" <?= $data['status_pribadi'] == 'Bercerai' ? 'selected' : '' ?>>Bercerai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Kepegawaian -->
                    <div class="tab-pane fade" id="kepegawaian">
                        <div class="section-title"><i class="fas fa-briefcase"></i> Riwayat & Status Kerja</div>
                        
                        <div id="status-wrapper">
                            <?php if(!empty($status_dosens)): foreach($status_dosens as $i => $sd): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Status Dosen</label>
                                        <select name="status_dosen[]" class="form-select" required>
                                            <option value="Tetap" <?= $sd['status_dosen'] == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                            <option value="Tidak Tetap" <?= $sd['status_dosen'] == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                                            <option value="Homebase" <?= $sd['status_dosen'] == 'Homebase' ? 'selected' : '' ?>>Homebase</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">TMT Mulai</label>
                                        <input type="date" name="tmt_status[]" class="form-control" value="<?= $sd['tmt'] ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">TMT Selesai (Jika Berhenti)</label>
                                        <input type="date" name="tgl_berhenti_status[]" class="form-control" value="<?= $sd['tgl_berhenti'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_dok_status[]" value="<?= $sd['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="dynamic-item">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Status Dosen</label>
                                        <select name="status_dosen[]" class="form-select" required>
                                            <option value="Tetap">Tetap</option>
                                            <option value="Tidak Tetap">Tidak Tetap</option>
                                            <option value="Homebase">Homebase</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">TMT Mulai</label>
                                        <input type="date" name="tmt_status[]" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">TMT Selesai</label>
                                        <input type="date" name="tgl_berhenti_status[]" class="form-control">
                                    </div>
                                    <input type="hidden" name="existing_dok_status[]" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-4" onclick="addStatusRow()"><i class="fas fa-plus me-1"></i> Tambah Riwayat Status</button>

                        <div class="row g-3 mt-2 border-top pt-4">
                            <div class="col-md-6">
                                <label class="form-label">Homebase Prodi</label>
                                <input type="text" name="homebase_prodi" class="form-control" value="<?= htmlspecialchars($data['homebase_prodi']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit Kerja</label>
                                <input type="text" name="unit_kerja" class="form-control" value="<?= htmlspecialchars($data['unit_kerja']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Keaktifan</label>
                                <select name="status_keaktifan" class="form-select" onchange="toggleKeaktifan(this.value)">
                                    <option value="Aktif" <?= $data['status_keaktifan'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Tidak Aktif" <?= $data['status_keaktifan'] == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-4 <?= $data['status_keaktifan'] == 'Tidak Aktif' ? '' : 'd-none' ?>" id="tglTidakAktif">
                                <label class="form-label">Tgl Berhenti Kerja</label>
                                <input type="date" name="tgl_mulai_tidak_bekerja" class="form-control" value="<?= $data['tgl_mulai_tidak_bekerja'] ?>">
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded-3">
                            <label class="form-label fw-bold">Jabatan Struktural</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <select name="jenis_dosen" class="form-select" onchange="toggleStruktural(this.value)">
                                        <option value="Non Struktural" <?= $data['jenis_dosen'] == 'Non Struktural' ? 'selected' : '' ?>>Dosen Biasa (Non-Struktural)</option>
                                        <option value="Struktural" <?= $data['jenis_dosen'] == 'Struktural' ? 'selected' : '' ?>>Dosen Struktural</option>
                                    </select>
                                </div>
                                <div class="col-md-8 <?= $data['jenis_dosen'] == 'Struktural' ? '' : 'd-none' ?>" id="strukField">
                                    <input type="text" name="jabatan_struktural" class="form-control" value="<?= htmlspecialchars($data['jabatan_struktural']) ?>" placeholder="Sebutkan jabatan (Contoh: Dekan Teknik)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Kualifikasi -->
                    <div class="tab-pane fade" id="kualifikasi">
                        <div class="section-title"><i class="fas fa-graduation-cap"></i> Pendidikan & Sertifikasi</div>
                        
                        <div id="pend-wrapper">
                            <?php if(!empty($pendidikans)): foreach($pendidikans as $p): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Jenjang</label>
                                        <select name="pend_jenjang[]" class="form-select">
                                            <option value="S1" <?= $p['jenjang'] == 'S1' ? 'selected' : '' ?>>S1</option>
                                            <option value="S2" <?= $p['jenjang'] == 'S2' ? 'selected' : '' ?>>S2</option>
                                            <option value="S3" <?= $p['jenjang'] == 'S3' ? 'selected' : '' ?>>S3</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Institusi / Universitas</label>
                                        <input type="text" name="pend_institusi[]" class="form-control" value="<?= htmlspecialchars($p['institusi']) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Thn Lulus</label>
                                        <input type="number" name="pend_tahun[]" class="form-control" value="<?= $p['tahun_lulus'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_dok_pendidikan[]" value="<?= $p['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="dynamic-item">
                                <div class="row g-3">
                                    <div class="col-md-2"><label class="form-label">Jenjang</label><select name="pend_jenjang[]" class="form-select"><option>S1</option><option>S2</option><option>S3</option></select></div>
                                    <div class="col-md-8"><label class="form-label">Institusi</label><input type="text" name="pend_institusi[]" class="form-control"></div>
                                    <div class="col-md-2"><label class="form-label">Tahun</label><input type="number" name="pend_tahun[]" class="form-control"></div>
                                    <input type="hidden" name="existing_dok_pendidikan[]" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-5" onclick="addPendRow()"><i class="fas fa-plus me-1"></i> Tambah Pendidikan</button>

                        <div class="section-title pt-4 border-top"><i class="fas fa-award"></i> Jabatan Akademik (Jabfung)</div>
                        <div id="jab-wrapper">
                            <?php if(!empty($jabfungs)): foreach($jabfungs as $jf): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Jabatan Fungsional</label>
                                        <select name="jabfung_akademik[]" class="form-select">
                                            <option value="Tenaga Pengajar" <?= $jf['jabatan'] == 'Tenaga Pengajar' ? 'selected' : '' ?>>Tenaga Pengajar</option>
                                            <option value="Asisten Ahli" <?= $jf['jabatan'] == 'Asisten Ahli' ? 'selected' : '' ?>>Asisten Ahli</option>
                                            <option value="Lektor" <?= $jf['jabatan'] == 'Lektor' ? 'selected' : '' ?>>Lektor</option>
                                            <option value="Lektor Kepala" <?= $jf['jabatan'] == 'Lektor Kepala' ? 'selected' : '' ?>>Lektor Kepala</option>
                                            <option value="Guru Besar" <?= $jf['jabatan'] == 'Guru Besar' ? 'selected' : '' ?>>Guru Besar</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TMT Jabfung</label>
                                        <input type="date" name="tmt_jabfung[]" class="form-control" value="<?= $jf['tmt'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_dok_jabfung[]" value="<?= $jf['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="dynamic-item">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label">Jabfung</label><select name="jabfung_akademik[]" class="form-select"><option>Tenaga Pengajar</option><option>Asisten Ahli</option><option>Lektor</option></select></div>
                                    <div class="col-md-6"><label class="form-label">TMT</label><input type="date" name="tmt_jabfung[]" class="form-control"></div>
                                    <input type="hidden" name="existing_dok_jabfung[]" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="addJabRow()"><i class="fas fa-plus me-1"></i> Tambah Jabfung</button>
                    </div>

                    <!-- Tab 4: History -->
                    <div class="tab-pane fade" id="history">
                        <div class="section-title"><i class="fas fa-trophy"></i> Penghargaan & Sertifikat</div>
                        <div id="reward-wrapper">
                            <?php if(!empty($rewards)): foreach($rewards as $rw): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Deskripsi Penghargaan</label>
                                        <input type="text" name="reward_deskripsi[]" class="form-control" value="<?= htmlspecialchars($rw['deskripsi']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" name="reward_tanggal[]" class="form-control" value="<?= $rw['tanggal'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_reward_file[]" value="<?= $rw['file_upload'] ?>">
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-4" onclick="addRewardRow()"><i class="fas fa-plus me-1"></i> Tambah Reward</button>

                        <div class="section-title pt-4 border-top"><i class="fas fa-file-shield"></i> Dokumen Pendukung Lainnya</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Scan KTP (PDF/JPG)</label>
                                <input type="file" name="dok_ktp" class="form-control">
                                <?php if($data['dok_ktp']): ?><small class="text-primary mt-1 d-block"><i class="fas fa-check-circle"></i> File Tersedia</small><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Scan Kartu Keluarga (PDF/JPG)</label>
                                <input type="file" name="dok_kk" class="form-control">
                                <?php if($data['dok_kk']): ?><small class="text-primary mt-1 d-block"><i class="fas fa-check-circle"></i> File Tersedia</small><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action -->
                <div class="card-footer bg-white p-4 border-top text-end">
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

function toggleStruktural(val) {
    document.getElementById('strukField').classList.toggle('d-none', val !== 'Struktural');
}

function toggleKeaktifan(val) {
    document.getElementById('tglTidakAktif').classList.toggle('d-none', val !== 'Tidak Aktif');
}

function addStatusRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status Dosen</label>
                <select name="status_dosen[]" class="form-select" required>
                    <option value="Tetap">Tetap</option><option value="Tidak Tetap">Tidak Tetap</option><option value="Homebase">Homebase</option>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">TMT Mulai</label><input type="date" name="tmt_status[]" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">TMT Selesai</label><input type="date" name="tgl_berhenti_status[]" class="form-control"></div>
            <input type="hidden" name="existing_dok_status[]" value="">
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPendRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Jenjang</label><select name="pend_jenjang[]" class="form-select"><option>S1</option><option>S2</option><option>S3</option></select></div>
            <div class="col-md-8"><label class="form-label">Institusi</label><input type="text" name="pend_institusi[]" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Tahun</label><input type="number" name="pend_tahun[]" class="form-control"></div>
            <input type="hidden" name="existing_dok_pendidikan[]" value="">
        </div>
    </div>`;
    document.getElementById('pend-wrapper').insertAdjacentHTML('beforeend', html);
}

function addJabRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Jabfung</label><select name="jabfung_akademik[]" class="form-select"><option>Tenaga Pengajar</option><option>Asisten Ahli</option><option>Lektor</option><option>Lektor Kepala</option><option>Guru Besar</option></select></div>
            <div class="col-md-6"><label class="form-label">TMT</label><input type="date" name="tmt_jabfung[]" class="form-control"></div>
            <input type="hidden" name="existing_dok_jabfung[]" value="">
        </div>
    </div>`;
    document.getElementById('jab-wrapper').insertAdjacentHTML('beforeend', html);
}

function addRewardRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Deskripsi</label><input type="text" name="reward_deskripsi[]" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="reward_tanggal[]" class="form-control"></div>
            <input type="hidden" name="existing_reward_file[]" value="">
        </div>
    </div>`;
    document.getElementById('reward-wrapper').insertAdjacentHTML('beforeend', html);
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

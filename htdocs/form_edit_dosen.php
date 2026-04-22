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

// Fetch rewards and punishments
$rewards = $conn->query("SELECT * FROM reward WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$punishments = $conn->query("SELECT * FROM punishment WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);

// Fetch histories
$jabfungs = $conn->query("SELECT * FROM jabfung_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$lldiktis = $conn->query("SELECT * FROM lldikti_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$yayasans = $conn->query("SELECT * FROM yayasan_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_dosen WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);
$status_dosens = $conn->query("SELECT * FROM status_dosen_riwayat WHERE dosen_id = $id")->fetch_all(MYSQLI_ASSOC);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'];
    $ttl_tanggal = $_POST['ttl_tanggal'];
    $nip = !empty($_POST['nip']) ? trim($_POST['nip']) : null;
    $nidn = !empty($_POST['nidn']) ? trim($_POST['nidn']) : null;
    $nuptk = !empty($_POST['nuptk']) ? trim($_POST['nuptk']) : null;

    // Check for duplicates excluding current dosen ID
    $check_query = "SELECT nip, nidn, nuptk FROM dosen WHERE id != ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    $duplicates = [];
    while ($row = $result_check->fetch_assoc()) {
        if ($nip && $row['nip'] === $nip) $duplicates[] = 'NIP';
        if ($nidn && $row['nidn'] === $nidn) $duplicates[] = 'NIDN';
        if ($nuptk && $row['nuptk'] === $nuptk) $duplicates[] = 'NUPTK';
    }
    $stmt_check->close();

    if (!empty($duplicates)) {
        $dup_str = implode(', ', array_unique($duplicates));
        echo "<script>alert('Gagal Edit! $dup_str sudah terdaftar digunakan oleh dosen lain.');history.back();</script>";
        exit;
    }
    $status_dosen = $_POST['status_dosen'][0] ?? ''; // Latest status
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jenis_dosen = $_POST['jenis_dosen'] ?? '';
    $jabatan_struktural = $_POST['jabatan_struktural'] ?? '';
    $tmk = !empty($_POST['tmk']) ? $_POST['tmk'] : null;
    $tmtk = !empty($_POST['tmtk']) ? $_POST['tmtk'] : null;
    $ket_tidak_kerja = $_POST['ket_tidak_kerja'] ?? '';
    
    $status_keaktifan = $_POST['status_keaktifan'] ?? null;
    $keterangan_keaktifan = '';
    $tgl_mulai_tidak_bekerja = !empty($_POST['tgl_mulai_tidak_bekerja']) ? $_POST['tgl_mulai_tidak_bekerja'] : null;

    if($status_keaktifan === 'Tidak Aktif') {
        $keterangan_keaktifan = $_POST['ket_tidak_aktif'] ?? '';
        if($keterangan_keaktifan === 'Lainnya') {
            $keterangan_keaktifan = $_POST['ket_tidak_aktif_lainnya'] ?? '';
        }
    }
    
    $status_list = [];
    if(!empty($_POST['status_dosen'])) {
        foreach($_POST['status_dosen'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status'][$i]) ? $_POST['tmt_status'][$i] : null;
                $tgl_berhenti = !empty($_POST['tgl_berhenti_status'][$i]) ? $_POST['tgl_berhenti_status'][$i] : null;
                $old_file = $_POST['existing_dok_status'][$i] ?? '';
                $filename = $old_file;
                if(!empty($_FILES['dok_status']['name'][$i])) {
                    $filename = 'uploads/'.time().'_status_'.basename($_FILES['dok_status']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status']['tmp_name'][$i], $filename);
                }
                $status_list[] = ['status' => $std, 'tmt' => $tmt, 'tgl_berhenti' => $tgl_berhenti, 'dokumen' => $filename];
            }
        }
    }

    $jabfung_list = [];
    if(!empty($_POST['jabfung_akademik'])) {
        foreach($_POST['jabfung_akademik'] as $i => $jab) {
            if(trim($jab) !== '') {
                $tmt = !empty($_POST['tmt_jabfung'][$i]) ? $_POST['tmt_jabfung'][$i] : null;
                $old_file = $_POST['existing_dok_jabfung'][$i] ?? '';
                $filename = $old_file;
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
                $old_file = $_POST['existing_dok_gol_lldikti'][$i] ?? '';
                $filename = $old_file;
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
                $old_file = $_POST['existing_dok_gol_yayasan'][$i] ?? '';
                $filename = $old_file;
                if(!empty($_FILES['dok_gol_yayasan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['dok_gol_yayasan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_yayasan']['tmp_name'][$i], $filename);
                }
                $yayasan_list[] = ['golongan' => $gol, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }

    // Latest values for dosen table (dashboard compatibility)
    $jabfung_akademik = $jabfung_list[0]['jabatan'] ?? '';
    $tmt_jabfung = $jabfung_list[0]['tmt'] ?? null;
    $dok_jabfung_main = $jabfung_list[0]['dokumen'] ?? '';

    $gol_lldikti = $lldikti_list[0]['golongan'] ?? '';
    $tmt_gol_lldikti = $lldikti_list[0]['tmt'] ?? null;
    $dok_gol_lldikti_main = $lldikti_list[0]['dokumen'] ?? '';

    $gol_yayasan = $yayasan_list[0]['golongan'] ?? '';
    $tmt_gol_yayasan = $yayasan_list[0]['tmt'] ?? null;
    $dok_gol_yayasan_main = $yayasan_list[0]['dokumen'] ?? '';
    $homebase_prodi = $_POST['homebase_prodi'];
    $unit_kerja = $_POST['unit_kerja'];
    $no_serdos = $_POST['no_serdos'] ?: null;

    $pendidikan_list = [];
    if(!empty($_POST['pend_jenjang'])) {
        foreach($_POST['pend_jenjang'] as $i => $jenjang) {
            if(trim($jenjang) !== '') {
                $institusi = $_POST['pend_institusi'][$i] ?? '';
                $tahun = $_POST['pend_tahun'][$i] ?? '';
                $old_file = $_POST['existing_dok_pendidikan'][$i] ?? '';
                $filename = $old_file;
                if(!empty($_FILES['dok_pendidikan']['name'][$i])) {
                    $filename = 'uploads/'.time().'_'.basename($_FILES['dok_pendidikan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_pendidikan']['tmp_name'][$i], $filename);
                }
                $pendidikan_list[] = ['jenjang' => $jenjang, 'institusi' => $institusi, 'tahun_lulus' => $tahun, 'dokumen' => $filename];
            }
        }
    }

    $riwayat_pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

    // Handle File Uploads
    $docs = [
        'dok_tidak_kerja' => $data['dok_tidak_kerja'],
        'dok_serdos' => $data['dok_serdos'],
        'dok_ktp' => $data['dok_ktp'] ?? '',
        'dok_kk' => $data['dok_kk'] ?? ''
    ];
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);

    foreach ($docs as $key => $current_val) {
        if (!empty($_FILES[$key]['name'])) {
            $filename = 'uploads/' . time() . '_' . basename($_FILES[$key]['name']);
            if (move_uploaded_file($_FILES[$key]['tmp_name'], $filename)) {
                $docs[$key] = $filename;
            }
        }
    }

    // Handle foto profil upload
    $foto_profil = $data['foto_profil'] ?? '';
    if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = 'uploads/foto_' . $id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }
    $sql = "UPDATE dosen SET 
        nama_lengkap = ?, alamat = ?, ttl_tempat = ?, ttl_tanggal = ?, nip = ?, nidn = ?, nuptk = ?, status_dosen = ?, status_pribadi = ?, 
        dok_ktp = ?, dok_kk = ?,
        jenis_dosen = ?, jabatan_struktural = ?, tmk = ?, tmtk = ?, ket_tidak_kerja = ?, dok_tidak_kerja = ?,
        jabfung_akademik = ?, tmt_jabfung = ?, dok_jabfung = ?,
        gol_lldikti = ?, tmt_gol_lldikti = ?, dok_gol_lldikti = ?, 
        gol_yayasan = ?, tmt_gol_yayasan = ?, dok_gol_yayasan = ?, 
        homebase_prodi = ?, unit_kerja = ?, 
        no_serdos = ?, dok_serdos = ?, riwayat_pendidikan = ?, foto_profil = ?, status_keaktifan = ?, keterangan_keaktifan = ?, tgl_mulai_tidak_bekerja = ?
        WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssssssssssssssssssssssi", 
        $nama, $alamat, $ttl_tempat, $ttl_tanggal, $nip, $nidn, $nuptk, $status_dosen, $status_pribadi, 
        $docs['dok_ktp'], $docs['dok_kk'],
        $jenis_dosen, $jabatan_struktural, $tmk, $tmtk, $ket_tidak_kerja, $docs['dok_tidak_kerja'],
        $jabfung_akademik, $tmt_jabfung, $dok_jabfung_main,
        $gol_lldikti, $tmt_gol_lldikti, $dok_gol_lldikti_main,
        $gol_yayasan, $tmt_gol_yayasan, $dok_gol_yayasan_main,
        $homebase_prodi, $unit_kerja, 
        $no_serdos, $docs['dok_serdos'], $riwayat_pendidikan, $foto_profil, $status_keaktifan, $keterangan_keaktifan, $tgl_mulai_tidak_bekerja, $id
    );
    
    if ($stmt->execute()) {
        // Handle Penugasan History (Struktural/Non)
        $old_jenis = $data['jenis_dosen'] ?? 'Non Struktural';
        $old_jab_struk = $data['jabatan_struktural'] ?? '';
        $old_tmk = $data['tmk'] ?? null;
        $old_tmtk = $data['tmtk'] ?? null;
        
        if ($old_jenis != $jenis_dosen || $old_jab_struk != $jabatan_struktural || $old_tmk != $tmk || $old_tmtk != $tmtk) {
            // If it was Struktural, Archive it
            if ($old_jenis == 'Struktural' || !empty($old_jab_struk)) {
                $st_pen = $conn->prepare("INSERT INTO penugasan_dosen_riwayat (dosen_id, jenis_dosen, jabatan_struktural, tmt, dokumen) VALUES (?, ?, ?, ?, ?)");
                $p_dok = $data['dok_tidak_kerja'] ?? ''; // simplified, use current relevant doc
                $st_pen->bind_param("issss", $id, $old_jenis, $old_jab_struk, $old_tmk, $p_dok);
                $st_pen->execute();
                $st_pen->close();
            }
        }

        // Handle Rewards & Punishments
        $conn->query("DELETE FROM reward WHERE dosen_id = $id");
        if (!empty($_POST['reward_deskripsi'])) {
            foreach ($_POST['reward_deskripsi'] as $i => $desc) {
                if (trim($desc) !== '') {
                    $tanggal = !empty($_POST['reward_tanggal'][$i]) ? $_POST['reward_tanggal'][$i] : null;
                    $old_file = $_POST['existing_reward_file'][$i] ?? '';
                    $filename = $old_file;
                    if (!empty($_FILES['reward_file']['name'][$i])) {
                        $filename = 'uploads/' . time() . '_' . basename($_FILES['reward_file']['name'][$i]);
                        move_uploaded_file($_FILES['reward_file']['tmp_name'][$i], $filename);
                    }
                    $st_rev = $conn->prepare("INSERT INTO reward (dosen_id, deskripsi, tanggal, file_upload) VALUES (?, ?, ?, ?)");
                    $st_rev->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_rev->execute();
                    $st_rev->close();
                }
            }
        }

        $conn->query("DELETE FROM punishment WHERE dosen_id = $id");
        if (!empty($_POST['punishment_deskripsi'])) {
            foreach ($_POST['punishment_deskripsi'] as $i => $desc) {
                if (trim($desc) !== '') {
                    $tanggal = !empty($_POST['punishment_tanggal'][$i]) ? $_POST['punishment_tanggal'][$i] : null;
                    $old_file = $_POST['existing_punishment_file'][$i] ?? '';
                    $filename = $old_file;
                    if (!empty($_FILES['punishment_file']['name'][$i])) {
                        $filename = 'uploads/' . time() . '_' . basename($_FILES['punishment_file']['name'][$i]);
                        move_uploaded_file($_FILES['punishment_file']['tmp_name'][$i], $filename);
                    }
                    $st_pun = $conn->prepare("INSERT INTO punishment (dosen_id, deskripsi, tanggal, file_upload) VALUES (?, ?, ?, ?)");
                    $st_pun->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_pun->execute();
                    $st_pun->close();
                }
            }
        }

        // Handle Histories
        $conn->query("DELETE FROM status_dosen_riwayat WHERE dosen_id = $id");
        foreach ($status_list as $stt) {
            $st = $conn->prepare("INSERT INTO status_dosen_riwayat (dosen_id, status_dosen, tmt, tgl_berhenti, dokumen) VALUES (?, ?, ?, ?, ?)");
            $st->bind_param("issss", $id, $stt['status'], $stt['tmt'], $stt['tgl_berhenti'], $stt['dokumen']);
            $st->execute();
            $st->close();
        }
        $conn->query("DELETE FROM jabfung_dosen WHERE dosen_id = $id");
        foreach ($jabfung_list as $jf) {
            $st = $conn->prepare("INSERT INTO jabfung_dosen (dosen_id, jabatan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $id, $jf['jabatan'], $jf['tmt'], $jf['dokumen']);
            $st->execute();
            $st->close();
        }
        $conn->query("DELETE FROM lldikti_dosen WHERE dosen_id = $id");
        foreach ($lldikti_list as $ld) {
            $st = $conn->prepare("INSERT INTO lldikti_dosen (dosen_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $id, $ld['golongan'], $ld['tmt'], $ld['dokumen']);
            $st->execute();
            $st->close();
        }
        $conn->query("DELETE FROM yayasan_dosen WHERE dosen_id = $id");
        foreach ($yayasan_list as $yy) {
            $st = $conn->prepare("INSERT INTO yayasan_dosen (dosen_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $id, $yy['golongan'], $yy['tmt'], $yy['dokumen']);
            $st->execute();
            $st->close();
        }
        $conn->query("DELETE FROM pendidikan_dosen WHERE dosen_id = $id");
        foreach ($pendidikan_list as $pend) {
            $st = $conn->prepare("INSERT INTO pendidikan_dosen (dosen_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
            $st->bind_param("issss", $id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
            $st->execute();
            $st->close();
        }

        echo "<script>alert('Data dosen berhasil diperbarui!');location='detail_dosen.php?id=$id';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan saat memperbarui data.');</script>";
    }
    $stmt->close();
}

$breadcrumbs = [
    ['label' => 'Dosen', 'url' => 'daftar_dosen.php'],
    ['label' => 'Detail', 'url' => 'detail_dosen.php?id=' . $id],
    ['label' => 'Edit', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Dosen | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section { margin-bottom: 40px; }
        .form-section h3 { 
            padding-bottom: 15px; 
            border-bottom: 2px solid #e2e8f0; 
            margin-bottom: 25px; 
            color: var(--primary);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .multi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .dynamic-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .hidden { display: none !important; }
        .file-current {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div>
                <h1 style="margin:0">Edit Data Dosen</h1>
                <p style="margin:0; opacity: 0.8;">Perbarui informasi untuk <strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong></p>
            </div>
        </div>
        <div>
            <a href="detail_dosen.php?id=<?= $id ?>" class="btn btn-outline" title="Kembali">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <!-- Foto Profil -->
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-camera"></i> Foto Profil</h3>
            <div class="form-group">
                <label>Upload Foto Profil (JPG/PNG)</label>
                <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png">
                <?php if(!empty($data['foto_profil'])): ?>
                    <div style="margin-top:12px;">
                        <img src="<?= htmlspecialchars($data['foto_profil']) ?>" alt="Foto Profil" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-light);">
                        <span class="file-current" style="display:inline-block;margin-left:10px;">Foto saat ini: <?= basename($data['foto_profil']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-user"></i> Informasi Pribadi</h3>
            <div class="form-group">
                <label>Nama Lengkap (beserta gelar)</label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" required>
            </div>
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="2" required><?= htmlspecialchars($data['alamat']) ?></textarea>
            </div>
            <div class="multi-row">
                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="ttl_tempat" value="<?= htmlspecialchars($data['ttl_tempat']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="ttl_tanggal" value="<?= $data['ttl_tanggal'] ?>" required>
                </div>
            </div>
            
            <!-- Tambahan NIP, NIDN, NUPTK -->
            <div class="multi-row" style="margin-top: 16px;">
                <div class="form-group">
                    <label>NIP</label>
                    <input type="text" name="nip" id="inp_nip" value="<?= htmlspecialchars($data['nip'] ?? '') ?>" placeholder="Nomor Induk Pegawai (Optional)">
                    <small id="warn_nip" style="color:var(--danger); display:none; margin-top:4px;"><i class="fas fa-exclamation-triangle"></i> NIP sudah terdaftar!</small>
                </div>
                <div class="form-group">
                    <label>NIDN</label>
                    <input type="text" name="nidn" id="inp_nidn" value="<?= htmlspecialchars($data['nidn'] ?? '') ?>" placeholder="Nomor Induk Dosen Nasional (Optional)">
                    <small id="warn_nidn" style="color:var(--danger); display:none; margin-top:4px;"><i class="fas fa-exclamation-triangle"></i> NIDN sudah terdaftar!</small>
                </div>
            </div>
            <div class="form-group" style="margin-top: 16px;">
                <label>NUPTK</label>
                <input type="text" name="nuptk" id="inp_nuptk" value="<?= htmlspecialchars($data['nuptk'] ?? '') ?>" placeholder="Nomor Unik Pendidik dan Tenaga Kependidikan (Optional)">
                <small id="warn_nuptk" style="color:var(--danger); display:none; margin-top:4px;"><i class="fas fa-exclamation-triangle"></i> NUPTK sudah terdaftar!</small>
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label>Status Pernikahan</label>
                <select name="status_pribadi" required>
                    <option value="">- Pilih -</option>
                    <option value="Menikah" <?= $data['status_pribadi'] == 'Menikah' ? 'selected' : '' ?>>Menikah</option>
                    <option value="Belum Menikah" <?= $data['status_pribadi'] == 'Belum Menikah' ? 'selected' : '' ?>>Belum Menikah</option>
                    <option value="Bercerai" <?= $data['status_pribadi'] == 'Bercerai' ? 'selected' : '' ?>>Bercerai</option>
                </select>
            </div>

            <div class="multi-row" style="margin-top: 16px;">
                <div class="form-group">
                    <label><i class="fas fa-id-card" style="color: var(--primary); margin-right: 6px;"></i>Upload KTP</label>
                    <input type="file" name="dok_ktp" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if(!empty($data['dok_ktp'])): ?>
                        <a href="<?= $data['dok_ktp'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($data['dok_ktp']) ?></a>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-users" style="color: var(--primary); margin-right: 6px;"></i>Upload KK (Kartu Keluarga)</label>
                    <input type="file" name="dok_kk" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if(!empty($data['dok_kk'])): ?>
                        <a href="<?= $data['dok_kk'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($data['dok_kk']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Status Kepegawaian -->
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-briefcase"></i> Status Kepegawaian</h3>

            <div id="status-wrapper">
                <?php if (count($status_dosens) > 0): ?>
                    <?php foreach($status_dosens as $std): ?>
                        <div class="dynamic-item">
                            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                            <div class="form-group">
                                <label>Status Dosen</label>
                                <select name="status_dosen[]" required>
                                    <option value="">- Pilih Status Dosen -</option>
                                    <option value="Tetap" <?= $std['status_dosen'] == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                    <option value="Tidak Tetap" <?= $std['status_dosen'] == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                                    <option value="Homebase" <?= $std['status_dosen'] == 'Homebase' ? 'selected' : '' ?>>Homebase</option>
                                </select>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top:12px;">
                                <div class="form-group">
                                    <label>Terhitung Mulai Bekerja</label>
                                    <input type="date" name="tmt_status[]" value="<?= $std['tmt'] ?>">
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Berhenti <span style="color:var(--text-muted); font-weight:400;">(Jika Ada)</span></label>
                                    <input type="date" name="tgl_berhenti_status[]" value="<?= $std['tgl_berhenti'] ?? '' ?>">
                                </div>
                                <div class="form-group">
                                    <label>Upload Dokumen <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                    <input type="file" name="dok_status[]" accept=".pdf,.jpg,.png">
                                    <?php if($std['dokumen']): ?>
                                        <a href="<?= $std['dokumen'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($std['dokumen']) ?></a>
                                        <input type="hidden" name="existing_dok_status[]" value="<?= $std['dokumen'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_dok_status[]" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-item">
                        <div class="form-group">
                            <label>Status Dosen</label>
                            <select name="status_dosen[]" required>
                                <option value="">- Pilih Status Dosen -</option>
                                <option value="Tetap" <?= $data['status_dosen'] == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                <option value="Tidak Tetap" <?= $data['status_dosen'] == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                                <option value="Homebase" <?= $data['status_dosen'] == 'Homebase' ? 'selected' : '' ?>>Homebase</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top:12px;">
                            <div class="form-group">
                                <label>Terhitung Mulai Bekerja</label>
                                <input type="date" name="tmt_status[]">
                            </div>
                            <div class="form-group">
                                <label>Tanggal Berhenti <span style="color:var(--text-muted); font-weight:400;">(Jika Ada)</span></label>
                                <input type="date" name="tgl_berhenti_status[]">
                            </div>
                            <div class="form-group">
                                <label>Upload Dokumen <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                <input type="file" name="dok_status[]" accept=".pdf,.jpg,.png">
                                <input type="hidden" name="existing_dok_status[]" value="">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addStatusDosen()" class="btn btn-outline" style="width:100%; margin-bottom:15px;"><i class="fas fa-plus"></i> Tambah Riwayat Status Dosen</button>

            <!-- Moved Homebase & Unit Kerja Here -->
            <div class="multi-row" style="margin-bottom:20px;">
                <div class="form-group">
                    <label>Homebase Prodi</label>
                    <input type="text" name="homebase_prodi" value="<?= htmlspecialchars($data['homebase_prodi']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Unit Kerja</label>
                    <input type="text" name="unit_kerja" value="<?= htmlspecialchars($data['unit_kerja']) ?>" required>
                </div>
            </div>

            <!-- SECTION 3: Jabatan Dosen -->
            <div id="area_jenis_penugasan" style="margin-top:8px; background:#f0f6ff; border:1.5px solid #bfdbfe; border-radius:10px; padding:20px;">
                <label style="font-weight:700; font-size:0.9rem; color:var(--primary); display:block; margin-bottom:12px;"><i class="fas fa-tag"></i> Jabatan Dosen</label>
                <div style="display:flex; gap:24px; flex-wrap:wrap;">
                    <label class="radio-label"><input type="radio" name="jenis_dosen" value="Non Struktural" <?= $data['jenis_dosen'] == 'Non Struktural' || empty($data['jenis_dosen']) ? 'checked' : '' ?>> Non Struktural</label>
                    <label class="radio-label"><input type="radio" name="jenis_dosen" value="Struktural" <?= $data['jenis_dosen'] == 'Struktural' ? 'checked' : '' ?>> Struktural</label>
                </div>
                
                <div id="area_jabatan_struktural" class="<?= $data['jenis_dosen'] == 'Struktural' ? '' : 'hidden' ?>" style="margin-top:14px;">
                    <label style="font-weight:600; font-size:0.85rem;">Nama Jabatan Struktural</label>
                    <input type="text" name="jabatan_struktural" value="<?= htmlspecialchars($data['jabatan_struktural']) ?>" placeholder="Contoh: Wakil Rektor I" style="margin-top:6px;">
                </div>

                <!-- Struktural: Terhitung Mulai Bertugas -->
                <div class="<?= ($data['jenis_dosen'] == 'Struktural') ? '' : 'hidden' ?>" id="group_tmk" style="margin-top:16px;">
                    <div class="form-group">
                        <label>Terhitung Mulai Bertugas (TMBT)</label>
                        <input type="date" name="tmk" id="tmk_input" value="<?= $data['terhitung_mulai_kerja'] ?? ($data['tmk'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <label style="font-size:0.8rem; font-weight:700;">Upload Dokumen Penugasan Struktural</label>
                        <input type="file" name="dok_penugasan_struktural" accept=".pdf,.jpg,.png">
                    </div>
                </div>

                <!-- Non Struktural: Terhitung Mulai Tidak Bertugas -->
                <div class="<?= ($data['jenis_dosen'] == 'Struktural') ? 'hidden' : '' ?>" id="group_tmtk" style="margin-top:16px;">
                    <div class="form-group">
                        <label>Terhitung Mulai Tidak Bertugas (TMTBT)</label>
                        <input type="date" name="tmtk" id="tmtk_input" value="<?= $data['terhitung_mulai_tidak_kerja'] ?? ($data['tmtk'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <label style="font-size:0.8rem; font-weight:700;">Upload Dokumen Penugasan Non Struktural</label>
                        <input type="file" name="dok_tidak_kerja" accept=".pdf,.jpg,.png">
                        <?php if(!empty($data['dokumen_tidak_kerja'] ?? $data['dok_tidak_kerja'] ?? '')): ?>
                            <a href="<?= $data['dokumen_tidak_kerja'] ?? $data['dok_tidak_kerja'] ?>" target="_blank" class="file-current" style="display:block; margin-top:4px;"><i class="fas fa-file-pdf"></i> Lihat Dokumen</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Jabatan Akademik Dosen -->
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-award"></i> Jabatan Akademik Dosen</h3>
            <div id="jabfung-wrapper">
                <?php if (count($jabfungs) > 0): ?>
                    <?php foreach($jabfungs as $jf): ?>
                        <div class="dynamic-item">
                            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                            <div class="form-group">
                                <label>Jabatan Akademik Dosen <span style="color:var(--text-muted); font-weight:400; font-size:0.85rem;">(TMT Jabfung)</span></label>
                                <select name="jabfung_akademik[]">
                                    <option value="">- Pilih Jabatan Akademik -</option>
                                    <option value="Asisten Ahli" <?= $jf['jabatan'] == 'Asisten Ahli' ? 'selected' : '' ?>>Asisten Ahli</option>
                                    <option value="Lektor" <?= $jf['jabatan'] == 'Lektor' ? 'selected' : '' ?>>Lektor</option>
                                    <option value="Lektor Kepala" <?= $jf['jabatan'] == 'Lektor Kepala' ? 'selected' : '' ?>>Lektor Kepala</option>
                                    <option value="Guru Besar" <?= $jf['jabatan'] == 'Guru Besar' ? 'selected' : '' ?>>Guru Besar</option>
                                </select>
                            </div>
                            <div class="multi-row" style="margin-top:12px;">
                                <div class="form-group">
                                    <label>TMT Jabfung <span style="color:var(--text-muted); font-weight:400;">(Tanggal Mulai Berlaku)</span></label>
                                    <input type="date" name="tmt_jabfung[]" value="<?= $jf['tmt'] ?>">
                                    <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">Pilih tanggal surat keputusan jabatan fungsional mulai berlaku.</small>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" name="ket_jabfung[]" value="<?= htmlspecialchars($jf['keterangan'] ?? '') ?>" placeholder="Keterangan tambahan (opsional)">
                                </div>
                                <div class="form-group">
                                    <label>Upload SK Jabfung <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                    <input type="file" name="dok_jabfung[]" accept=".pdf,.jpg,.png">
                                    <?php if($jf['dokumen']): ?>
                                        <a href="<?= $jf['dokumen'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($jf['dokumen']) ?></a>
                                        <input type="hidden" name="existing_dok_jabfung[]" value="<?= $jf['dokumen'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_dok_jabfung[]" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-item">
                        <div class="form-group">
                            <label>Jabatan Akademik Dosen <span style="color:var(--text-muted); font-weight:400; font-size:0.85rem;">(TMT Jabfung)</span></label>
                            <select name="jabfung_akademik[]">
                                <option value="">- Pilih Jabatan Akademik -</option>
                                <option value="Tenaga Pengajar">Tenaga Pengajar</option>
                                <option value="Asisten Ahli">Asisten Ahli</option>
                                <option value="Lektor">Lektor</option>
                                <option value="Lektor Kepala">Lektor Kepala</option>
                                <option value="Guru Besar">Guru Besar</option>
                            </select>
                        </div>
                        <div class="multi-row" style="margin-top:12px;">
                            <div class="form-group">
                                <label>TMT Jabfung <span style="color:var(--text-muted); font-weight:400;">(Tanggal Mulai Berlaku)</span></label>
                                <input type="date" name="tmt_jabfung[]">
                                <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">Pilih tanggal surat keputusan jabatan fungsional mulai berlaku.</small>
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" name="ket_jabfung[]" placeholder="Keterangan tambahan (opsional)">
                            </div>
                            <div class="form-group"><label>Upload SK Jabfung <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label><input type="file" name="dok_jabfung[]" accept=".pdf,.jpg,.png"></div>
                        </div>
                        <input type="hidden" name="existing_dok_jabfung[]" value="">
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addJabfung()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Jabfung</button>
        </div>

        <!-- SECTION 4 & 5: Pangkat/Golongan -->
        <div class="multi-row" style="margin-bottom: 20px;">
            <div class="card" style="margin-bottom:0">
                <h3><i class="fas fa-university"></i> Pangkat/Golongan sesuai DIKTI</h3>
                <div id="lldikti-wrapper">
                    <?php if (count($lldiktis) > 0): ?>
                        <?php foreach($lldiktis as $ld): ?>
                            <div class="dynamic-item">
                                <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                                <div class="form-group">
                                    <select name="gol_lldikti[]">
                                        <option value="">- Pilih Golongan -</option>
                                        <option value="III/a" <?= $ld['golongan']=='III/a'?'selected':'' ?>>III/a</option>
                                        <option value="III/b" <?= $ld['golongan']=='III/b'?'selected':'' ?>>III/b</option>
                                        <option value="III/c" <?= $ld['golongan']=='III/c'?'selected':'' ?>>III/c</option>
                                        <option value="III/d" <?= $ld['golongan']=='III/d'?'selected':'' ?>>III/d</option>
                                        <option value="IV/a" <?= $ld['golongan']=='IV/a'?'selected':'' ?>>IV/a</option>
                                        <option value="IV/b" <?= $ld['golongan']=='IV/b'?'selected':'' ?>>IV/b</option>
                                        <option value="IV/c" <?= $ld['golongan']=='IV/c'?'selected':'' ?>>IV/c</option>
                                        <option value="IV/d" <?= $ld['golongan']=='IV/d'?'selected':'' ?>>IV/d</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-top:12px;">
                                    <label style="font-size:0.8rem">TMT</label>
                                    <input type="date" name="tmt_gol_lldikti[]" value="<?= $ld['tmt'] ?>">
                                </div>
                                <div class="form-group" style="margin-top:12px;">
                                    <label style="font-size:0.8rem">Upload SK</label>
                                    <input type="file" name="dok_gol_lldikti[]" accept=".pdf,.jpg,.png">
                                    <?php if($ld['dokumen']): ?>
                                        <a href="<?= $ld['dokumen'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($ld['dokumen']) ?></a>
                                        <input type="hidden" name="existing_dok_gol_lldikti[]" value="<?= $ld['dokumen'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_dok_gol_lldikti[]" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addLldikti()" class="btn btn-outline" style="width:100%; font-size: 0.8rem; padding: 6px;"><i class="fas fa-plus"></i> Tambah</button>
            </div>

            <div class="card" style="margin-bottom:0">
                <h3><i class="fas fa-building"></i> Golongan Yayasan</h3>
                <div id="yayasan-wrapper">
                    <?php if (count($yayasans) > 0): ?>
                        <?php foreach($yayasans as $yy): ?>
                            <div class="dynamic-item">
                                <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                                <div class="form-group">
                                    <select name="gol_yayasan[]">
                                        <option value="">- Pilih Golongan -</option>
                                        <option value="III/a" <?= $yy['golongan']=='III/a'?'selected':'' ?>>III/a</option>
                                        <option value="III/b" <?= $yy['golongan']=='III/b'?'selected':'' ?>>III/b</option>
                                        <option value="III/c" <?= $yy['golongan']=='III/c'?'selected':'' ?>>III/c</option>
                                        <option value="III/d" <?= $yy['golongan']=='III/d'?'selected':'' ?>>III/d</option>
                                        <option value="IV/a" <?= $yy['golongan']=='IV/a'?'selected':'' ?>>IV/a</option>
                                        <option value="IV/b" <?= $yy['golongan']=='IV/b'?'selected':'' ?>>IV/b</option>
                                        <option value="IV/c" <?= $yy['golongan']=='IV/c'?'selected':'' ?>>IV/c</option>
                                        <option value="IV/d" <?= $yy['golongan']=='IV/d'?'selected':'' ?>>IV/d</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-top:12px;">
                                    <label style="font-size:0.8rem">TMT</label>
                                    <input type="date" name="tmt_gol_yayasan[]" value="<?= $yy['tmt'] ?>">
                                </div>
                                <div class="form-group" style="margin-top:12px;">
                                    <label style="font-size:0.8rem">Upload SK</label>
                                    <input type="file" name="dok_gol_yayasan[]" accept=".pdf,.jpg,.png">
                                    <?php if($yy['dokumen']): ?>
                                        <a href="<?= $yy['dokumen'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($yy['dokumen']) ?></a>
                                        <input type="hidden" name="existing_dok_gol_yayasan[]" value="<?= $yy['dokumen'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addYayasan()" class="btn btn-outline" style="width:100%; font-size: 0.8rem; padding: 6px;"><i class="fas fa-plus"></i> Tambah</button>
            </div>
        </div>

        <!-- SECTION 6: Pendidikan & Sertifikasi -->
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</h3>
            <div id="pendidikan-wrapper">
                <?php if (count($pendidikans) > 0): ?>
                    <?php foreach($pendidikans as $pend): ?>
                        <div class="dynamic-item">
                            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                            <div class="multi-row">
                                <div class="form-group">
                                    <label>Jenjang / Tingkat</label>
                                    <select name="pend_jenjang[]" required>
                                        <option value="">- Pilih -</option>
                                        <?php foreach(['S1', 'S2', 'S3'] as $p): ?>
                                        <option value="<?= $p ?>" <?= $pend['jenjang'] == $p ? 'selected' : '' ?>><?= $p ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Nama Institusi / Universitas</label>
                                    <input type="text" name="pend_institusi[]" value="<?= htmlspecialchars($pend['institusi']) ?>" placeholder="Contoh: Universitas Indonesia" required>
                                </div>
                            </div>
                            <div class="multi-row" style="margin-top:12px;">
                                <div class="form-group">
                                    <label>Tahun Lulus</label>
                                    <input type="number" name="pend_tahun[]" value="<?= htmlspecialchars($pend['tahun_lulus']) ?>" min="1950" max="2100" placeholder="YYYY" required>
                                </div>
                                <div class="form-group">
                                    <label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                    <input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png">
                                    <?php if($pend['dokumen']): ?>
                                        <a href="<?= $pend['dokumen'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($pend['dokumen']) ?></a>
                                        <input type="hidden" name="existing_dok_pendidikan[]" value="<?= $pend['dokumen'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_dok_pendidikan[]" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-item">
                        <div class="multi-row">
                            <div class="form-group">
                                <label>Jenjang / Tingkat</label>
                                <select name="pend_jenjang[]" required>
                                    <option value="">- Pilih -</option>
                                    <?php foreach(['S1', 'S2', 'S3'] as $p): ?>
                                    <option value="<?= $p ?>" <?= $data['riwayat_pendidikan'] == $p ? 'selected' : '' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nama Institusi / Universitas</label>
                                <input type="text" name="pend_institusi[]" placeholder="Contoh: Universitas Indonesia" required>
                            </div>
                        </div>
                        <div class="multi-row" style="margin-top:12px;">
                            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" min="1950" max="2100" placeholder="YYYY" required></div>
                            <div class="form-group">
                                <label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                <input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png">
                                <input type="hidden" name="existing_dok_pendidikan[]" value="">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addPendidikan()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Riwayat Pendidikan</button>
            
            <hr style="border:0; border-top:1px dashed #cbd5e1; margin:25px 0;">
            <?php $has_serdos = !empty($data['no_serdos']) || !empty($data['dok_serdos']); ?>
            <h3 style="margin-top:0;"><i class="fas fa-certificate"></i> Sertifikasi</h3>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Apakah dosen sudah sertifikasi (serdos)?</label>
                <div style="display:flex; gap:20px;">
                    <label class="radio-label"><input type="radio" name="is_serdos" value="Ya" onclick="document.getElementById('area_serdos').style.display='flex'" <?= $has_serdos ? 'checked' : '' ?>> Ya</label>
                    <label class="radio-label"><input type="radio" name="is_serdos" value="Tidak" onclick="document.getElementById('area_serdos').style.display='none'" <?= !$has_serdos ? 'checked' : '' ?>> Tidak</label>
                </div>
            </div>
            
            <div id="area_serdos" class="multi-row" style="<?= $has_serdos ? 'display:flex;' : 'display:none;' ?> background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:8px;">
                <div class="form-group">
                    <label>Nomor Sertifikasi Dosen</label>
                    <input type="text" name="no_serdos" value="<?= htmlspecialchars($data['no_serdos'] ?? '') ?>" placeholder="Contoh: 123456789">
                </div>
                <div class="form-group">
                    <label>Upload Dokumen Sertifikasi (Serdos) <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                    <input type="file" name="dok_serdos" accept=".pdf,.jpg,.png">
                    <?php if($data['dok_serdos']): ?>
                        <a href="<?= $data['dok_serdos'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($data['dok_serdos']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SECTION 7: Penghargaan & Sanksi -->
        <div class="multi-row" style="margin-bottom:20px;">
            <div class="card">
                <h3><i class="fas fa-medal" style="color:#d97706;"></i> Penghargaan (Reward)</h3>
                <div id="reward-wrapper">
                    <?php foreach($rewards as $i => $rev): ?>
                        <div class="dynamic-item" style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:12px;">
                            <div class="form-group"><input type="text" name="reward_deskripsi[]" value="<?= htmlspecialchars($rev['deskripsi']) ?>" placeholder="Deskripsi penghargaan..."></div>
                            <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; align-items:end;">
                                <div class="form-group"><label style="font-size:0.8rem;">Tanggal</label><input type="date" name="reward_tanggal[]" value="<?= $rev['tanggal'] ?>"></div>
                                <div class="form-group">
                                    <label style="font-size:0.8rem;">Dokumen</label>
                                    <input type="file" name="reward_file[]" accept=".pdf,.jpg,.png">
                                    <?php if($rev['file_upload']): ?>
                                        <a href="<?= $rev['file_upload'] ?>" target="_blank" class="file-current" style="margin-top:4px;"><i class="fas fa-file"></i> <?= basename($rev['file_upload']) ?></a>
                                        <input type="hidden" name="existing_reward_file[]" value="<?= $rev['file_upload'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_reward_file[]" value="">
                                    <?php endif; ?>
                                </div>
                                <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); margin-bottom:20px;"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addReward()" class="btn btn-outline" style="width:100%; margin-top:10px;"><i class="fas fa-plus"></i> Tambah Reward</button>
            </div>
            
            <div class="card">
                <h3><i class="fas fa-gavel" style="color:#dc2626;"></i> Sanksi (Punishment)</h3>
                <div id="punishment-wrapper">
                    <?php foreach($punishments as $i => $pun): ?>
                        <div class="dynamic-item" style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:12px;">
                            <div class="form-group"><input type="text" name="punishment_deskripsi[]" value="<?= htmlspecialchars($pun['deskripsi']) ?>" placeholder="Deskripsi sanksi/pelanggaran..."></div>
                            <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; align-items:end;">
                                <div class="form-group"><label style="font-size:0.8rem;">Tanggal</label><input type="date" name="punishment_tanggal[]" value="<?= $pun['tanggal'] ?>"></div>
                                <div class="form-group">
                                    <label style="font-size:0.8rem;">Dokumen</label>
                                    <input type="file" name="punishment_file[]" accept=".pdf,.jpg,.png">
                                    <?php if($pun['file_upload']): ?>
                                        <a href="<?= $pun['file_upload'] ?>" target="_blank" class="file-current" style="margin-top:4px;"><i class="fas fa-file"></i> <?= basename($pun['file_upload']) ?></a>
                                        <input type="hidden" name="existing_punishment_file[]" value="<?= $pun['file_upload'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_punishment_file[]" value="">
                                    <?php endif; ?>
                                </div>
                                <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); margin-bottom:20px;"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addPunishment()" class="btn btn-outline" style="width:100%; margin-top:10px;"><i class="fas fa-plus"></i> Tambah Sanksi</button>
            </div>
        </div>

        <!-- SECTION 8: Status Keaktifan -->
        <?php 
            $sts_aktif = $data['status_keaktifan'] ?? 'Aktif'; 
            $ket_aktif = $data['keterangan_keaktifan'] ?? '';
            $isLainnya = !in_array($ket_aktif, ['', 'Cuti', 'Izin Belajar', 'Tugas Belajar']);
            $valLainnya = $isLainnya ? $ket_aktif : '';
        ?>
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-user-check"></i> Status Keaktifan</h3>
            <div class="form-group">
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <label class="radio-label"><input type="radio" name="status_keaktifan" value="Aktif" onclick="toggleKeaktifan(this)" <?= $sts_aktif == 'Aktif' ? 'checked' : '' ?>> Aktif</label>
                    <label class="radio-label"><input type="radio" name="status_keaktifan" value="Tidak Aktif" onclick="toggleKeaktifan(this)" <?= $sts_aktif == 'Tidak Aktif' ? 'checked' : '' ?>> Tidak Aktif</label>
                </div>
            </div>
            <div id="area_keaktifan_date" class="<?= $sts_aktif == 'Tidak Aktif' ? '' : 'hidden' ?>" style="margin-top:15px;">
                <div class="form-group">
                    <label>Tanggal Mulai Tidak Bekerja</label>
                    <input type="date" name="tgl_mulai_tidak_bekerja" value="<?= $data['tgl_mulai_tidak_bekerja'] ?>">
                </div>
            </div>
            <div id="area_keaktifan_pilihan" class="<?= $sts_aktif == 'Tidak Aktif' ? '' : 'hidden' ?>" style="margin-top:15px; background:#fff1f2; border:1px solid #fecaca; padding:15px; border-radius:8px;">
                <label style="font-weight:600; font-size:0.9rem; color:#e11d48;">Alasan Tidak Aktif</label>
                <div style="display:flex; gap:20px; margin-top:10px; flex-wrap:wrap;">
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Cuti" onclick="toggleKeaktifanLainnya(this)" <?= $ket_aktif == 'Cuti' ? 'checked' : '' ?>> Cuti</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Izin Belajar" onclick="toggleKeaktifanLainnya(this)" <?= $ket_aktif == 'Izin Belajar' ? 'checked' : '' ?>> Izin Belajar</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Tugas Belajar" onclick="toggleKeaktifanLainnya(this)" <?= $ket_aktif == 'Tugas Belajar' ? 'checked' : '' ?>> Tugas Belajar</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Resign" onclick="toggleKeaktifanLainnya(this)" <?= $ket_aktif == 'Resign' ? 'checked' : '' ?>> Resign</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Pensiun" onclick="toggleKeaktifanLainnya(this)" <?= $ket_aktif == 'Pensiun' ? 'checked' : '' ?>> Pensiun</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Lainnya" onclick="toggleKeaktifanLainnya(this)" <?= $isLainnya && $ket_aktif !== '' ? 'checked' : '' ?>> Lainnya</label>
                </div>
                <div id="area_tidak_aktif_lainnya" class="<?= $isLainnya && $ket_aktif !== '' ? '' : 'hidden' ?>" style="margin-top:12px;">
                    <input type="text" name="ket_tidak_aktif_lainnya" value="<?= htmlspecialchars($valLainnya) ?>" placeholder="Deskripsi Lainnya...">
                </div>
            </div>
        </div>

        <div class="card" style="display:flex; justify-content:flex-end; gap:12px; align-items:center;">
            <a href="detail_dosen.php?id=<?= $id ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Batal</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 36px;"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
const CURRENT_DOSEN_ID = <?= $id ?>;
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
        let res = await fetch(`check_duplicate.php?type=${type}&value=${encodeURIComponent(value)}&exclude_id=${CURRENT_DOSEN_ID}`);
        let data = await res.json();
        if(data.exists) {
            document.getElementById(warnId).innerHTML = `<i class="fas fa-exclamation-triangle"></i> Terdaftar a.n. ${data.name}`;
            document.getElementById(warnId).style.display = 'block';
            return true;
        } else {
            document.getElementById(warnId).style.display = 'none';
            return false;
        }
    }

    const validateAllDups = async () => {
        let n1 = await checkDup('nip', inpNip.value, 'warn_nip');
        let n2 = await checkDup('nidn', inpNidn.value, 'warn_nidn');
        let n3 = await checkDup('nuptk', inpNuptk.value, 'warn_nuptk');
        isDuplicate = n1 || n2 || n3;
    };

    inpNip.addEventListener('blur', validateAllDups);
    inpNidn.addEventListener('blur', validateAllDups);
    inpNuptk.addEventListener('blur', validateAllDups);

    document.querySelector('form').addEventListener('submit', function(e) {
        if(isDuplicate) {
            e.preventDefault();
            alert("Gagal menyimpan: NIP / NIDN / NUPTK sudah terdaftar oleh dosen lain! Mohon periksa kembali input Anda.");
        }
    });
    // Jenis Dosen Struktural / Non Struktural logic
    const jabArea = document.getElementById('area_jabatan_struktural');
    const grpTmk = document.getElementById('group_tmk');
    const grpTmtk = document.getElementById('group_tmtk');

    document.querySelectorAll('input[name="jenis_dosen"]').forEach(r => {
        r.addEventListener('change', function() {
            const currentVal = this.value;
            
            if (currentVal === 'Struktural') {
                jabArea.classList.remove('hidden');
                grpTmk?.classList.remove('hidden');
                grpTmtk?.classList.add('hidden');
            } else {
                jabArea.classList.add('hidden');
                grpTmk?.classList.add('hidden');
                grpTmtk?.classList.remove('hidden');
            }
        });
    });
});

function addJabfung() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <label>Jabatan Akademik Dosen</label>
            <select name="jabfung_akademik[]">
                <option value="">- Pilih Jabatan Akademik -</option>
                <option value="Asisten Ahli">Asisten Ahli</option>
                <option value="Lektor">Lektor</option>
                <option value="Lektor Kepala">Lektor Kepala</option>
                <option value="Guru Besar">Guru Besar</option>
            </select>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group">
                <label>TMT Jabfung <span style="color:var(--text-muted); font-weight:400;">(Tanggal Mulai Berlaku)</span></label>
                <input type="date" name="tmt_jabfung[]">
                <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">Pilih tanggal surat keputusan jabatan fungsional mulai berlaku.</small>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <input type="text" name="ket_jabfung[]" placeholder="Keterangan tambahan (opsional)">
            </div>
            <div class="form-group">
                <label>Upload SK Jabfung <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                <input type="file" name="dok_jabfung[]" accept=".pdf,.jpg,.png">
                <input type="hidden" name="existing_dok_jabfung[]" value="">
            </div>
        </div>
    </div>`;
    document.getElementById('jabfung-wrapper').insertAdjacentHTML('beforeend', html);
}

function addLldikti() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <select name="gol_lldikti[]">
                <option value="">- Pilih Golongan -</option>
                <option value="III/a">III/a</option>
                <option value="III/b">III/b</option>
                <option value="III/c">III/c</option>
                <option value="III/d">III/d</option>
                <option value="IV/a">IV/a</option>
                <option value="IV/b">IV/b</option>
                <option value="IV/c">IV/c</option>
                <option value="IV/d">IV/d</option>
            </select>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label style="font-size:0.8rem">TMT</label>
            <input type="date" name="tmt_gol_lldikti[]">
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label style="font-size:0.8rem">Upload SK</label>
            <input type="file" name="dok_gol_lldikti[]" accept=".pdf,.jpg,.png">
            <input type="hidden" name="existing_dok_gol_lldikti[]" value="">
        </div>
    </div>`;
    document.getElementById('lldikti-wrapper').insertAdjacentHTML('beforeend', html);
}

function addYayasan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <select name="gol_yayasan[]">
                <option value="">- Pilih Golongan -</option>
                <option value="III/a">III/a</option>
                <option value="III/b">III/b</option>
                <option value="III/c">III/c</option>
                <option value="III/d">III/d</option>
                <option value="IV/a">IV/a</option>
                <option value="IV/b">IV/b</option>
                <option value="IV/c">IV/c</option>
                <option value="IV/d">IV/d</option>
            </select>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label style="font-size:0.8rem">TMT</label>
            <input type="date" name="tmt_gol_yayasan[]">
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label style="font-size:0.8rem">Upload SK</label>
            <input type="file" name="dok_gol_yayasan[]" accept=".pdf,.jpg,.png">
            <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
        </div>
    </div>`;
    document.getElementById('yayasan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPendidikan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="multi-row">
            <div class="form-group">
                <label>Jenjang / Tingkat</label>
                <select name="pend_jenjang[]" required>
                    <option value="">- Pilih -</option>
                    <option value="S1">S1</option><option value="S2">S2</option><option value="S3">S3</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Institusi / Universitas</label>
                <input type="text" name="pend_institusi[]" placeholder="Contoh: Universitas Indonesia" required>
            </div>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" min="1950" max="2100" placeholder="YYYY" required></div>
            <div class="form-group">
                <label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                <input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png">
                <input type="hidden" name="existing_dok_pendidikan[]" value="">
            </div>
        </div>
    </div>`;
    document.getElementById('pendidikan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addReward() {
    const html = `<div class="dynamic-item" style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:12px;">
        <div class="form-group"><input type="text" name="reward_deskripsi[]" placeholder="Deskripsi penghargaan..."></div>
        <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; align-items:end;">
            <div class="form-group"><label style="font-size:0.8rem;">Tanggal</label><input type="date" name="reward_tanggal[]"></div>
            <div class="form-group">
                <label style="font-size:0.8rem;">Dokumen</label>
                <input type="file" name="reward_file[]" accept=".pdf,.jpg,.png">
                <input type="hidden" name="existing_reward_file[]" value="">
            </div>
            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); margin-bottom:20px;"><i class="fas fa-trash"></i></button>
        </div>
    </div>`;
    document.getElementById('reward-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPunishment() {
    const html = `<div class="dynamic-item" style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:12px;">
        <div class="form-group"><input type="text" name="punishment_deskripsi[]" placeholder="Deskripsi sanksi/pelanggaran..."></div>
        <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; align-items:end;">
            <div class="form-group"><label style="font-size:0.8rem;">Tanggal</label><input type="date" name="punishment_tanggal[]"></div>
            <div class="form-group">
                <label style="font-size:0.8rem;">Dokumen</label>
                <input type="file" name="punishment_file[]" accept=".pdf,.jpg,.png">
                <input type="hidden" name="existing_punishment_file[]" value="">
            </div>
            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); margin-bottom:20px;"><i class="fas fa-trash"></i></button>
        </div>
    </div>`;
    document.getElementById('punishment-wrapper').insertAdjacentHTML('beforeend', html);
}

function addStatusDosen() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <label>Status Dosen</label>
            <select name="status_dosen[]" required>
                <option value="">- Pilih Status Dosen -</option>
                <option value="Tetap">Tetap</option>
                <option value="Tidak Tetap">Tidak Tetap</option>
                <option value="Homebase">Homebase</option>
            </select>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top:12px;">
            <div class="form-group"><label>Terhitung Mulai Bekerja</label><input type="date" name="tmt_status[]"></div>
            <div class="form-group"><label>Tanggal Berhenti <span style="color:var(--text-muted); font-weight:400;">(Jika Ada)</span></label><input type="date" name="tgl_berhenti_status[]"></div>
            <div class="form-group">
                <label>Upload Dokumen</label>
                <input type="file" name="dok_status[]" accept=".pdf,.jpg,.png">
                <input type="hidden" name="existing_dok_status[]" value="">
            </div>
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function toggleKeaktifan(el) {
    const areaDate = document.getElementById('area_keaktifan_date');
    const areaPilihan = document.getElementById('area_keaktifan_pilihan');
    if(el.value === 'Tidak Aktif') {
        areaDate.classList.remove('hidden');
        areaPilihan.classList.remove('hidden');
    } else {
        areaDate.classList.add('hidden');
        areaPilihan.classList.add('hidden');
    }
}
function toggleKeaktifanLainnya(el) {
    const area = document.getElementById('area_tidak_aktif_lainnya');
    if(el.value === 'Lainnya') {
        area.classList.remove('hidden');
    } else {
        area.classList.add('hidden');
    }
}
</script>

</body>
</html>

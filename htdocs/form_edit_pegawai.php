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
$yayasans = $conn->query("SELECT * FROM yayasan_pegawai WHERE pegawai_id = $id ORDER BY tmt ASC")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id ORDER BY tahun_lulus ASC")->fetch_all(MYSQLI_ASSOC);
$status_riwayats = $conn->query("SELECT * FROM status_pegawai_riwayat WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'] ?? '';
    $ttl_tanggal = $_POST['ttl_tanggal'] ?: null;
    $ttl_lama = ($ttl_tempat ? $ttl_tempat . ', ' : '') . ($ttl_tanggal ? date('d F Y', strtotime($ttl_tanggal)) : '');
    $status_peg = $_POST['status_pegawai'][0] ?? ''; // Latest primary status
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jabatan = $_POST['posisi_jabatan'];
    $tmk = $_POST['tmt_mulai_kerja'] ?: null;
    $tmtk = $_POST['tmt_tidak_kerja'] ?: null;
    $unit = $_POST['unit_kerja'];
    $ket_tmtk = $_POST['ket_tmtk'] ?? '';

    // Record previously valid statuses for change detection
    $old_status_peg = $pegawai['status_pegawai'] ?? $pegawai['jenis_pegawai'];
    $old_jabatan = $pegawai['posisi_jabatan'];
    $old_unit = $pegawai['unit_kerja'];
    
    // Status Kepegawaian Riwayat Logic - Archive old values if primary status data changed
    if ($old_status_peg != $status_peg || $old_jabatan != $jabatan || $old_unit != $unit || $pegawai['tmt_mulai_kerja'] != $tmk) {
        if (!empty($old_status_peg)) {
            $arch_stmt = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, posisi_jabatan, unit_kerja, tmt_mulai_kerja, tmt_tidak_kerja, dokumen) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $end_date = date('Y-m-d');
            $arch_stmt->bind_param("issssss", 
                $id, 
                $old_status_peg, 
                $old_jabatan, 
                $old_unit, 
                $pegawai['tmt_mulai_kerja'], 
                $end_date, 
                $pegawai['dok_status_pegawai']
            );
            $arch_stmt->execute();
            $arch_stmt->close();
        }
    }

    $pendidikan_list = [];
    if (!empty($_POST['pend_jenjang'])) {
        foreach ($_POST['pend_jenjang'] as $i => $jenjang) {
            if (trim($jenjang) !== '') {
                $institusi = $_POST['pend_institusi'][$i] ?? '';
                $tahun = $_POST['pend_tahun'][$i] ?? '';
                $old_file = $_POST['existing_dok_pendidikan'][$i] ?? '';
                $filename = $old_file;
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                if (!empty($_FILES['dok_pendidikan']['name'][$i]) && $_FILES['dok_pendidikan']['error'][$i] == 0) {
                    $filename = 'uploads/' . time() . '_' . basename($_FILES['dok_pendidikan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_pendidikan']['tmp_name'][$i], $filename);
                }
                $pendidikan_list[] = ['jenjang' => $jenjang, 'institusi' => $institusi, 'tahun_lulus' => $tahun, 'dokumen' => $filename];
            }
        }
    }

    $pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

    // Handle KTP Document
    $dok_ktp = $pegawai['dok_ktp'];
    if (!empty($_FILES['dok_ktp']['name']) && $_FILES['dok_ktp']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_ktp']['name'], PATHINFO_EXTENSION);
        $dok_ktp = "uploads/ktp_p_" . time() . "." . $ext;
        move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
        if ($pegawai['dok_ktp'] && file_exists($pegawai['dok_ktp'])) @unlink($pegawai['dok_ktp']);
    }

    // Handle KK Document
    $dok_kk = $pegawai['dok_kk'];
    if (!empty($_FILES['dok_kk']['name']) && $_FILES['dok_kk']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_kk']['name'], PATHINFO_EXTENSION);
        $dok_kk = "uploads/kk_p_" . time() . "." . $ext;
        move_uploaded_file($_FILES['dok_kk']['tmp_name'], $dok_kk);
        if ($pegawai['dok_kk'] && file_exists($pegawai['dok_kk'])) @unlink($pegawai['dok_kk']);
    }

    // Handle Status Pegawai Document
    $dok_status_pegawai = $pegawai['dok_status_pegawai'];
    if (!empty($_FILES['dok_status_pegawai']['name']) && $_FILES['dok_status_pegawai']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_status_pegawai']['name'], PATHINFO_EXTENSION);
        $dok_status_pegawai = "uploads/dok_sp_" . time() . "." . $ext;
        move_uploaded_file($_FILES['dok_status_pegawai']['tmp_name'], $dok_status_pegawai);
        // Note: we don't unlink old status document here if we archive it, but for simplicity let's leave it as is.
    }

    // Handle TMTK Document
    $dok_tmtk = $pegawai['dok_tmtk'];
    if (isset($_FILES['dok_tmtk']) && $_FILES['dok_tmtk']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_tmtk']['name'], PATHINFO_EXTENSION);
        $dok_tmtk = "tmtk_p_" . time() . "." . $ext;
        if (!is_dir('dokumen')) mkdir('dokumen', 0777, true);
        move_uploaded_file($_FILES['dok_tmtk']['tmp_name'], "dokumen/" . $dok_tmtk);
        if ($pegawai['dok_tmtk'] && file_exists("dokumen/" . $pegawai['dok_tmtk'])) @unlink("dokumen/" . $pegawai['dok_tmtk']);
    }

    // Handle foto profil
    $foto_profil = $pegawai['foto_profil'] ?? '';
    if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] == 0) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = 'uploads/foto_p_' . $id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }

    $sql = "UPDATE pegawai SET 
            nama_lengkap=?, alamat=?, ttl=?, ttl_tempat=?, ttl_tanggal=?, status_pegawai=?, status_pribadi=?, 
            posisi_jabatan=?, tmt_mulai_kerja=?, tmt_tidak_kerja=?, unit_kerja=?, 
            riwayat_pendidikan=?, ket_tidak_kerja=?, dok_tmtk=?, dok_ktp=?, dok_kk=?, dok_status_pegawai=?, foto_profil=?
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssssssi", 
        $nama, $alamat, $ttl_lama, $ttl_tempat, $ttl_tanggal, $status_peg, $status_pribadi, 
        $jabatan, $tmk, $tmtk, $unit, $pendidikan, $ket_tmtk, $dok_tmtk, $dok_ktp, $dok_kk, $dok_status_pegawai, $foto_profil, $id);
    $stmt->execute();
    $stmt->close();

    // --- RIWAYAT STATUS PEGAWAI MANAGEMENT ---
    // Handle deletes
    if (!empty($_POST['delete_riwayat_status'])) {
        foreach ($_POST['delete_riwayat_status'] as $rs_id) {
            $rs_id = (int)$rs_id;
            $res = $conn->query("SELECT dokumen FROM status_pegawai_riwayat WHERE id = $rs_id");
            if ($f = $res->fetch_assoc()) {
                if ($f['dokumen'] && file_exists($f['dokumen'])) @unlink($f['dokumen']);
            }
            $conn->query("DELETE FROM status_pegawai_riwayat WHERE id = $rs_id");
        }
    }
    // Handle existing updates
    if (!empty($_POST['riwayat_id'])) {
        foreach ($_POST['riwayat_id'] as $rs_id) {
            $rs_id = (int)$rs_id;
            $r_status = $_POST['riwayat_status_pegawai'][$rs_id] ?? '';
            $r_jabatan = $_POST['riwayat_posisi_jabatan'][$rs_id] ?? '';
            $r_unit = $_POST['riwayat_unit_kerja'][$rs_id] ?? '';
            $r_tmt = $_POST['riwayat_tmt_mulai'][$rs_id] ?: null;
            $r_tmtbt = $_POST['riwayat_tmt_tidak'][$rs_id] ?: null;
            $r_file_name = $_POST['existing_riwayat_file'][$rs_id] ?? '';

            if (isset($_FILES['riwayat_file']['name'][$rs_id]) && $_FILES['riwayat_file']['error'][$rs_id] == 0) {
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                $ext = pathinfo($_FILES['riwayat_file']['name'][$rs_id], PATHINFO_EXTENSION);
                $new_file = "uploads/riwayat_sp_" . time() . "_" . $rs_id . "." . $ext;
                if (move_uploaded_file($_FILES['riwayat_file']['tmp_name'][$rs_id], $new_file)) {
                    if ($r_file_name && file_exists($r_file_name)) @unlink($r_file_name);
                    $r_file_name = $new_file;
                }
            }

            $stmt_rs = $conn->prepare("UPDATE status_pegawai_riwayat SET status_pegawai=?, posisi_jabatan=?, unit_kerja=?, tmt_mulai_kerja=?, tmt_tidak_kerja=?, dokumen=? WHERE id=?");
            $stmt_rs->bind_param("ssssssi", $r_status, $r_jabatan, $r_unit, $r_tmt, $r_tmtbt, $r_file_name, $rs_id);
            $stmt_rs->execute();
            $stmt_rs->close();
        }
    }
    // Handle new inserts
    if (!empty($_POST['new_riwayat_status_pegawai'])) {
        foreach ($_POST['new_riwayat_status_pegawai'] as $key => $n_status) {
            if (trim($n_status) !== '') {
                $n_jabatan = $_POST['new_riwayat_posisi_jabatan'][$key] ?? '';
                $n_unit = $_POST['new_riwayat_unit_kerja'][$key] ?? '';
                $n_tmt = $_POST['new_riwayat_tmt_mulai'][$key] ?: null;
                $n_tmtbt = $_POST['new_riwayat_tmt_tidak'][$key] ?: null;
                
                $n_file_name = '';
                if (isset($_FILES['new_riwayat_file']['name'][$key]) && $_FILES['new_riwayat_file']['error'][$key] == 0) {
                    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                    $ext = pathinfo($_FILES['new_riwayat_file']['name'][$key], PATHINFO_EXTENSION);
                    $n_file_name = "uploads/riwayat_sp_" . time() . "n_" . $key . "." . $ext;
                    move_uploaded_file($_FILES['new_riwayat_file']['tmp_name'][$key], $n_file_name);
                }

                $stmt_nrs = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, posisi_jabatan, unit_kerja, tmt_mulai_kerja, tmt_tidak_kerja, dokumen) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_nrs->bind_param("issssss", $id, $n_status, $n_jabatan, $n_unit, $n_tmt, $n_tmtbt, $n_file_name);
                $stmt_nrs->execute();
                $stmt_nrs->close();
            }
        }
    }


    // --- YAYASAN PEGAWAI MANAGEMENT ---
    $yayasan_list = [];
    if (!empty($_POST['gol_yayasan'])) {
        foreach ($_POST['gol_yayasan'] as $i => $gol) {
            if (trim($gol) !== '') {
                $ytmt = $_POST['tmt_gol_yayasan'][$i] ?: null;
                $old_file = $_POST['existing_dok_gol_yayasan'][$i] ?? '';
                $filename = $old_file;
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                if (!empty($_FILES['dok_gol_yayasan']['name'][$i]) && $_FILES['dok_gol_yayasan']['error'][$i] == 0) {
                    $filename = 'uploads/' . time() . '_' . basename($_FILES['dok_gol_yayasan']['name'][$i]);
                    move_uploaded_file($_FILES['dok_gol_yayasan']['tmp_name'][$i], $filename);
                }
                $yayasan_list[] = ['golongan' => $gol, 'tmt' => $ytmt, 'dokumen' => $filename];
            }
        }
    }
    $conn->query("DELETE FROM yayasan_pegawai WHERE pegawai_id = $id");
    foreach ($yayasan_list as $y) {
        $st = $conn->prepare("INSERT INTO yayasan_pegawai (pegawai_id, golongan, tmt, dokumen) VALUES (?, ?, ?, ?)");
        $st->bind_param("isss", $id, $y['golongan'], $y['tmt'], $y['dokumen']);
        $st->execute();
        $st->close();
    }

    // --- PENDIDIKAN PEGAWAI MANAGEMENT ---
    $conn->query("DELETE FROM pendidikan_pegawai WHERE pegawai_id = $id");
    foreach ($pendidikan_list as $pend) {
        $st = $conn->prepare("INSERT INTO pendidikan_pegawai (pegawai_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
        $st->bind_param("issss", $id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
        $st->execute();
        $st->close();
    }

    // --- REWARDS MANAGEMENT ---
    if (!empty($_POST['delete_rewards'])) {
        foreach ($_POST['delete_rewards'] as $rid) {
            $rid = (int)$rid;
            $res = $conn->query("SELECT dokumen FROM reward_pegawai WHERE id = $rid");
            if ($f = $res->fetch_assoc()) {
                if ($f['dokumen'] && file_exists("dokumen/" . $f['dokumen'])) @unlink("dokumen/" . $f['dokumen']);
            }
            $conn->query("DELETE FROM reward_pegawai WHERE id = $rid");
        }
    }
    if (!empty($_POST['reward_desc'])) {
        foreach ($_POST['reward_desc'] as $key => $desc) {
            if (trim($desc) !== '') {
                $rid = $_POST['reward_id'][$key] ?? null;
                $date = $_POST['reward_date'][$key] ?: null;
                $file_name = $_POST['existing_reward_file'][$key] ?? '';

                if (isset($_FILES['reward_file']['name'][$key]) && $_FILES['reward_file']['error'][$key] == 0) {
                    $ext = pathinfo($_FILES['reward_file']['name'][$key], PATHINFO_EXTENSION);
                    $new_file = "rew_p_" . time() . "_" . $key . "." . $ext;
                    if (move_uploaded_file($_FILES['reward_file']['tmp_name'][$key], "dokumen/" . $new_file)) {
                        if ($file_name && file_exists("dokumen/" . $file_name)) @unlink("dokumen/" . $file_name);
                        $file_name = $new_file;
                    }
                }

                if ($rid) {
                    $stmt_r = $conn->prepare("UPDATE reward_pegawai SET keterangan=?, tanggal=?, dokumen=? WHERE id=?");
                    $stmt_r->bind_param("sssi", $desc, $date, $file_name, $rid);
                    $stmt_r->execute();
                    $stmt_r->close();
                } else {
                    $stmt_r = $conn->prepare("INSERT INTO reward_pegawai (pegawai_id, keterangan, tanggal, dokumen) VALUES (?, ?, ?, ?)");
                    $stmt_r->bind_param("isss", $id, $desc, $date, $file_name);
                    $stmt_r->execute();
                    $stmt_r->close();
                }
            }
        }
    }

    // --- PUNISHMENTS MANAGEMENT ---
    if (!empty($_POST['delete_punishments'])) {
        foreach ($_POST['delete_punishments'] as $pid) {
            $pid = (int)$pid;
            $res = $conn->query("SELECT dokumen FROM punishment_pegawai WHERE id = $pid");
            if ($f = $res->fetch_assoc()) {
                if ($f['dokumen'] && file_exists("dokumen/" . $f['dokumen'])) @unlink("dokumen/" . $f['dokumen']);
            }
            $conn->query("DELETE FROM punishment_pegawai WHERE id = $pid");
        }
    }
    if (!empty($_POST['punish_desc'])) {
        foreach ($_POST['punish_desc'] as $key => $desc) {
            if (trim($desc) !== '') {
                $pid = $_POST['punish_id'][$key] ?? null;
                $date = $_POST['punish_date'][$key] ?: null;
                $file_name = $_POST['existing_punish_file'][$key] ?? '';

                if (isset($_FILES['punish_file']['name'][$key]) && $_FILES['punish_file']['error'][$key] == 0) {
                    $ext = pathinfo($_FILES['punish_file']['name'][$key], PATHINFO_EXTENSION);
                    $new_file = "pun_p_" . time() . "_" . $key . "." . $ext;
                    if (move_uploaded_file($_FILES['punish_file']['tmp_name'][$key], "dokumen/" . $new_file)) {
                        if ($file_name && file_exists("dokumen/" . $file_name)) @unlink("dokumen/" . $file_name);
                        $file_name = $new_file;
                    }
                }

                if ($pid) {
                    $stmt_p = $conn->prepare("UPDATE punishment_pegawai SET keterangan=?, tanggal=?, dokumen=? WHERE id=?");
                    $stmt_p->bind_param("sssi", $desc, $date, $file_name, $pid);
                    $stmt_p->execute();
                    $stmt_p->close();
                } else {
                    $stmt_p = $conn->prepare("INSERT INTO punishment_pegawai (pegawai_id, keterangan, tanggal, dokumen) VALUES (?, ?, ?, ?)");
                    $stmt_p->bind_param("isss", $id, $desc, $date, $file_name);
                    $stmt_p->execute();
                    $stmt_p->close();
                }
            }
        }
    }

    echo "<script>alert('Data pegawai berhasil diupdate!');location='detail_pegawai.php?id=$id';</script>";
    exit;
}

// Fetch Rewards & Punishments for Form
$rewards = $conn->query("SELECT * FROM reward_pegawai WHERE pegawai_id = $id");
$punishments = $conn->query("SELECT * FROM punishment_pegawai WHERE pegawai_id = $id");

$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'],
    ['label' => 'Detail Pegawai', 'url' => 'detail_pegawai.php?id='.$id],
    ['label' => 'Edit Pegawai', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pegawai | UNSERA</title>
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
            align-items: center; gap: 10px;
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
        .file-preview {
            margin-top: 8px; font-size: 0.8rem;
            display: flex; align-items: center; gap: 8px;
        }
        .hidden { display: none !important; }
        .delete-btn { display: inline-flex; align-items: center; gap: 5px; color: var(--danger); font-size: 0.85rem; cursor: pointer; }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Edit Data Pegawai</h1>
            <p>Perbarui informasi profil dan kepegawaian staf kependidikan.</p>
        </div>
        <div>
            <a href="detail_pegawai.php?id=<?= $id ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="card">
        
        <!-- Foto Profil -->
        <div class="form-section">
            <h3><i class="fas fa-camera"></i> Foto Profil</h3>
            <div style="display: flex; gap: 20px; align-items: center;">
                <?php if(!empty($pegawai['foto_profil']) && file_exists($pegawai['foto_profil'])): ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 3px solid #e2e8f0; flex-shrink: 0;">
                        <img src="<?= htmlspecialchars($pegawai['foto_profil']) ?>" alt="Foto" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                <?php else: ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e2e8f0; font-size: 2rem; font-weight: bold; flex-shrink: 0; color: #94a3b8;">
                        <?= strtoupper(substr($pegawai['nama_lengkap'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="form-group" style="flex: 1;">
                    <label>Ubah Foto Profil (Hanya isi jika ingin mengganti)</label>
                    <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png">
                </div>
            </div>
        </div>

        <!-- Informasi Pribadi -->
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Informasi Pribadi</h3>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($pegawai['nama_lengkap']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="2" required><?= htmlspecialchars($pegawai['alamat']) ?></textarea>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="ttl_tempat" value="<?= htmlspecialchars($ttl_tempat_val) ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="ttl_tanggal" value="<?= $ttl_tanggal_val ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Status Pernikahan</label>
                <select name="status_pribadi" required>
                    <option value="">Pilih Status</option>
                    <option value="Menikah" <?= ($pegawai['status_pribadi'] ?? '') == 'Menikah' ? 'selected' : '' ?>>Menikah</option>
                    <option value="Belum Menikah" <?= ($pegawai['status_pribadi'] ?? '') == 'Belum Menikah' ? 'selected' : '' ?>>Belum Menikah</option>
                    <option value="Bercerai" <?= ($pegawai['status_pribadi'] ?? '') == 'Bercerai' ? 'selected' : '' ?>>Bercerai</option>
                </select>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Dokumen KTP</label>
                    <input type="file" name="dok_ktp" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if(!empty($pegawai['dok_ktp'])): ?>
                        <div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> KTP telah diunggah</div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Dokumen KK</label>
                    <input type="file" name="dok_kk" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if(!empty($pegawai['dok_kk'])): ?>
                        <div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> KK telah diunggah</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Kepegawaian -->
        <div class="form-section">
            <h3><i class="fas fa-briefcase"></i> Status Kepegawaian</h3>
            
            <div id="status-pegawai-wrapper">
                <?php if (count($status_riwayats) > 0): ?>
                    <?php foreach($status_riwayats as $idx => $sr): ?>
                        <div class="dynamic-item">
                            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                            <div class="multi-row">
                                <div class="form-group">
                                    <label>Status Pegawai</label>
                                    <select name="status_pegawai[]" required>
                                        <option value="Tetap" <?= $sr['status_pegawai']=='Tetap'?'selected':'' ?>>Tetap</option>
                                        <option value="Tidak Tetap" <?= $sr['status_pegawai']=='Tidak Tetap'?'selected':'' ?>>Tidak Tetap</option>
                                        <option value="Honorer" <?= $sr['status_pegawai']=='Honorer'?'selected':'' ?>>Honorer</option>
                                        <option value="Kontrak" <?= $sr['status_pegawai']=='Kontrak'?'selected':'' ?>>Kontrak</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>TMT Status</label>
                                    <input type="date" name="tmt_status_pegawai[]" value="<?= $sr['tmt'] ?? $sr['tmt_mulai_kerja'] ?>">
                                </div>
                            </div>
                            <div class="form-group" style="margin-top:10px;">
                                <label>Upload Dokumen <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                <input type="file" name="dok_status_peg_riwayat[]" accept=".pdf,.jpg,.jpeg,.png">
                                <?php if(!empty($sr['dokumen'])): ?>
                                    <a href="<?= $sr['dokumen'] ?>" target="_blank" class="file-preview"><i class="fas fa-file-pdf"></i> <?= basename($sr['dokumen']) ?></a>
                                    <input type="hidden" name="existing_dok_status_peg[]" value="<?= $sr['dokumen'] ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-item">
                        <div class="multi-row">
                            <div class="form-group">
                                <label>Status Pegawai</label>
                                <select name="status_pegawai[]" required>
                                    <option value="">- Pilih Status -</option>
                                    <option value="Tetap" <?= ($pegawai['status_pegawai']??'') == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                    <option value="Tidak Tetap" <?= ($pegawai['status_pegawai']??'') == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>TMT Status</label>
                                <input type="date" name="tmt_status_pegawai[]" value="<?= $pegawai['tmt_mulai_kerja'] ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addStatusPegawai()" class="btn btn-outline" style="width:100%; margin-bottom:20px;"><i class="fas fa-plus"></i> Tambah Riwayat Status Pegawai</button>

            <div class="multi-row">
                <div class="form-group">
                    <label>Upload Dokumen Status Pegawai</label>
                    <input type="file" name="dok_status_pegawai" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if(!empty($pegawai['dok_status_pegawai'])): ?>
                        <div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> Dokumen telah diunggah</div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="posisi_jabatan" value="<?= htmlspecialchars($pegawai['posisi_jabatan']) ?>" required>
                </div>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Unit Kerja</label>
                    <input type="text" name="unit_kerja" value="<?= htmlspecialchars($pegawai['unit_kerja']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Terhitung Mulai Bekerja</label>
                    <input type="date" name="tmt_mulai_kerja" value="<?= $pegawai['tmt_mulai_kerja'] ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Terhitung Mulai Tidak Bekerja</label>
                <input type="date" name="tmt_tidak_kerja" id="tmtk_input" value="<?= $pegawai['tmt_tidak_kerja'] ?>">
            </div>

            <div id="area_tmtk" class="<?= empty($pegawai['tmt_tidak_kerja']) ? 'hidden' : '' ?> dynamic-item">
                <div class="multi-row">
                    <div class="form-group">
                        <label>Alasan Berhenti</label>
                        <select name="ket_tmtk">
                            <option value="">Pilih Alasan</option>
                            <?php $ket = $pegawai['ket_tidak_kerja'] ?? ''; ?>
                            <option value="Resign" <?= $ket=='Resign'?'selected':'' ?>>Resign</option>
                            <option value="Pensiun" <?= $ket=='Pensiun'?'selected':'' ?>>Pensiun</option>
                            <option value="Putus Kontrak" <?= $ket=='Putus Kontrak'?'selected':'' ?>>Putus Kontrak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dokumen SK Pemberhentian</label>
                        <input type="file" name="dok_tmtk" accept=".pdf,.png,.jpg,.jpeg">
                        <?php if(!empty($pegawai['dok_tmtk'])): ?>
                            <div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> SK Pemberhentian diunggah</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Status Kepegawaian (Historical) -->
        <div class="form-section" style="margin-top:20px;">
            <h3><i class="fas fa-history"></i> Riwayat Status Kepegawaian</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom:  १५px;">Kelola riwayat perubahan jenis staf, unit kerja, atau pindah bagian.</p>
            
            <div id="riwayat-status-wrapper">
                <?php foreach($status_riwayats as $idx => $sr): ?>
                <div class="dynamic-item">
                    <label class="delete-btn" style="position:absolute; right:15px; top:15px;">
                        <input type="checkbox" name="delete_riwayat_status[]" value="<?= $sr['id'] ?>"> Hapus
                    </label>
                    <input type="hidden" name="riwayat_id[<?= $sr['id'] ?>]" value="<?= $sr['id'] ?>">
                    
                    <div class="multi-row">
                        <div class="form-group">
                            <label>Status Pegawai</label>
                            <select name="riwayat_status_pegawai[<?= $sr['id'] ?>]" required>
                                <option value="Tetap" <?= $sr['status_pegawai']=='Tetap'?'selected':'' ?>>Tetap</option>
                                <option value="Tidak Tetap" <?= $sr['status_pegawai']=='Tidak Tetap'?'selected':'' ?>>Tidak Tetap</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Unit Kerja</label>
                            <input type="text" name="riwayat_unit_kerja[<?= $sr['id'] ?>]" value="<?= htmlspecialchars($sr['unit_kerja']??'') ?>">
                        </div>
                    </div>
                    <div class="multi-row" style="margin-top:12px;">
                        <div class="form-group">
                            <label>Jabatan</label>
                            <input type="text" name="riwayat_posisi_jabatan[<?= $sr['id'] ?>]" value="<?= htmlspecialchars($sr['posisi_jabatan']??'') ?>">
                        </div>
                        <div class="form-group">
                            <label>TMT Berlaku</label>
                            <input type="date" name="riwayat_tmt_mulai[<?= $sr['id'] ?>]" value="<?= $sr['tmt_mulai_kerja'] ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:12px;">
                        <label>Dokumen Perubahan/SK Riwayat</label>
                        <input type="hidden" name="existing_riwayat_file[<?= $sr['id'] ?>]" value="<?= htmlspecialchars($sr['dokumen']??'') ?>">
                        <input type="file" name="riwayat_file[<?= $sr['id'] ?>]" accept=".pdf,.png,.jpg,.jpeg">
                        <?php if(!empty($sr['dokumen'])): ?>
                            <div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> Ada dokumen tersimpan</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addRiwayatStatus()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Riwayat Secara Manual</button>
        </div>


        <!-- Pendidikan -->
        <div class="form-section">
            <h3><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</h3>
            <div id="pendidikan-wrapper">
                <?php if(empty($pendidikans)): ?>
                    <div class="dynamic-item">
                        <div class="multi-row">
                            <div class="form-group">
                                <label>Jenjang / Tingkat</label>
                                <select name="pend_jenjang[]">
                                    <option value="">- Pilih -</option>
                                    <?php 
                                    $riw_text = $pegawai['riwayat_pendidikan'] ?? '';
                                    foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $p): ?>
                                    <option value="<?= $p ?>" <?= strpos($riw_text, $p)!==false?'selected':'' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Nama Institusi / Universitas</label><input type="text" name="pend_institusi[]" value="<?= htmlspecialchars($riw_text) ?>"></div>
                        </div>
                        <div class="multi-row" style="margin-top:12px;">
                            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" placeholder="YYYY"></div>
                            <div class="form-group"><label>Upload Ijazah</label><input type="file" name="dok_pendidikan[]"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($pendidikans as $idx => $pend): ?>
                    <div class="dynamic-item">
                        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                        <div class="multi-row">
                            <div class="form-group">
                                <label>Jenjang</label>
                                <select name="pend_jenjang[]" required>
                                    <option value="">- Pilih -</option>
                                    <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $p): ?>
                                    <option value="<?= $p ?>" <?= $p==$pend['jenjang']?'selected':'' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Nama Institusi</label><input type="text" name="pend_institusi[]" value="<?= htmlspecialchars($pend['institusi']) ?>" required></div>
                        </div>
                        <div class="multi-row" style="margin-top:12px;">
                            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" value="<?= htmlspecialchars($pend['tahun_lulus']) ?>" required></div>
                            <div class="form-group">
                                <label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                <input type="hidden" name="existing_dok_pendidikan[]" value="<?= htmlspecialchars($pend['dokumen']) ?>">
                                <input type="file" name="dok_pendidikan[]">
                                <?php if($pend['dokumen']): ?><div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> File tersimpan</div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addPendidikan()" class="btn btn-outline" style="width:100%; margin-bottom: 20px;"><i class="fas fa-plus"></i> Tambah Pendidikan Lain</button>
        </div>

        <!-- Yayasan -->
        <div class="form-section">
            <h3><i class="fas fa-building"></i> Golongan Yayasan</h3>
            <div id="yayasan-wrapper">
                <?php foreach($yayasans as $yy): ?>
                <div class="dynamic-item">
                    <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                    <div class="form-group">
                        <label>Golongan Yayasan</label>
                        <select name="gol_yayasan[]">
                            <option value="">- Pilih Golongan -</option>
                            <?php foreach(['III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d'] as $g): ?>
                            <option value="<?= $g ?>" <?= $g==$yy['golongan']?'selected':'' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="multi-row" style="margin-top:12px;">
                        <div class="form-group"><label>TMT Golongan</label><input type="date" name="tmt_gol_yayasan[]" value="<?= $yy['tmt'] ?>"></div>
                        <div class="form-group">
                            <label>Upload SK Golongan</label>
                            <input type="hidden" name="existing_dok_gol_yayasan[]" value="<?= htmlspecialchars($yy['dokumen']) ?>">
                            <input type="file" name="dok_gol_yayasan[]">
                            <?php if($yy['dokumen']): ?><div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> File tersimpan</div><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addYayasan()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Golongan Yayasan</button>
        </div>

        <!-- Reward & Punishment -->
        <div class="form-section">
            <div class="multi-row">
                <div>
                    <h3><i class="fas fa-medal" style="color: #ed8936;"></i> Penghargaan</h3>
                    <div id="reward-wrapper">
                        <?php if($rewards) while($r = $rewards->fetch_assoc()): ?>
                        <div class="dynamic-item">
                            <label class="delete-btn" style="position:absolute; right:15px; top:15px;">
                                <input type="checkbox" name="delete_rewards[]" value="<?= $r['id'] ?>"> Hapus
                            </label>
                            <input type="hidden" name="reward_id[<?= $r['id'] ?>]" value="<?= $r['id'] ?>">
                            <div class="form-group">
                                <label>Deskripsi Penghargaan</label>
                                <input type="text" name="reward_desc[<?= $r['id'] ?>]" value="<?= htmlspecialchars($r['keterangan']) ?>" required>
                            </div>
                            <div class="multi-row" style="margin-top:12px;">
                                <div class="form-group"><label>Tanggal</label><input type="date" name="reward_date[<?= $r['id'] ?>]" value="<?= $r['tanggal'] ?>"></div>
                                <div class="form-group">
                                    <label>Upload Bukti</label>
                                    <input type="hidden" name="existing_reward_file[<?= $r['id'] ?>]" value="<?= htmlspecialchars($r['dokumen']??'') ?>">
                                    <input type="file" name="reward_file[<?= $r['id'] ?>]">
                                    <?php if($r['dokumen']): ?><div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> File tersimpan</div><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <button type="button" onclick="addReward()" class="btn btn-outline" style="width: 100%;"><i class="fas fa-plus"></i> Tambah Penghargaan</button>
                </div>
                <div>
                    <h3><i class="fas fa-gavel" style="color: #e53e3e;"></i> Sanksi / Catatan</h3>
                    <div id="punishment-wrapper">
                        <?php if($punishments) while($p = $punishments->fetch_assoc()): ?>
                        <div class="dynamic-item" style="border-top: 4px solid var(--danger);">
                            <label class="delete-btn" style="position:absolute; right:15px; top:15px;">
                                <input type="checkbox" name="delete_punishments[]" value="<?= $p['id'] ?>"> Hapus
                            </label>
                            <input type="hidden" name="punish_id[<?= $p['id'] ?>]" value="<?= $p['id'] ?>">
                            <div class="form-group">
                                <label>Deskripsi Sanksi</label>
                                <input type="text" name="punish_desc[<?= $p['id'] ?>]" value="<?= htmlspecialchars($p['keterangan']) ?>" required>
                            </div>
                            <div class="multi-row" style="margin-top:12px;">
                                <div class="form-group"><label>Tanggal</label><input type="date" name="punish_date[<?= $p['id'] ?>]" value="<?= $p['tanggal'] ?>"></div>
                                <div class="form-group">
                                    <label>Upload Bukti</label>
                                    <input type="hidden" name="existing_punish_file[<?= $p['id'] ?>]" value="<?= htmlspecialchars($p['dokumen']??'') ?>">
                                    <input type="file" name="punish_file[<?= $p['id'] ?>]">
                                    <?php if($p['dokumen']): ?><div class="file-preview"><i class="fas fa-check-circle" style="color:var(--success)"></i> File tersimpan</div><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <button type="button" onclick="addPunish()" class="btn btn-outline" style="width: 100%;"><i class="fas fa-plus"></i> Tambah Sanksi</button>
                </div>
            </div>
        </div>

        <div style="margin-top: 50px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 30px;">
            <a href="detail_pegawai.php?id=<?= $id ?>" class="btn" style="color: var(--text-muted); margin-right: 15px;">Batal</a>
            <button type="submit" name="update" class="btn btn-primary" style="padding: 12px 40px;"><i class="fas fa-save"></i> Perbarui Data</button>
        </div>
    </form>
</div>

<script>
let newCounter = 0;
function addReward() {
  newCounter++;
  const container = document.getElementById('reward-wrapper');
  const html = `
    <div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group"><label>Deskripsi Penghargaan</label><input type="text" name="reward_desc[n_${newCounter}]" required></div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Tanggal</label><input type="date" name="reward_date[n_${newCounter}]"></div>
            <div class="form-group"><label>Upload Bukti</label><input type="file" name="reward_file[n_${newCounter}]"></div>
        </div>
    </div>`;
  container.insertAdjacentHTML('beforeend', html);
}

function addPunish() {
  newCounter++;
  const container = document.getElementById('punishment-wrapper');
  const html = `
    <div class="dynamic-item" style="border-top: 4px solid var(--danger);">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group"><label>Deskripsi Sanksi</label><input type="text" name="punish_desc[n_${newCounter}]" required></div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Tanggal</label><input type="date" name="punish_date[n_${newCounter}]"></div>
            <div class="form-group"><label>Upload Bukti</label><input type="file" name="punish_file[n_${newCounter}]"></div>
        </div>
    </div>`;
  container.insertAdjacentHTML('beforeend', html);
}

document.getElementById('tmtk_input').addEventListener('input', function() {
    if(this.value) document.getElementById('area_tmtk').classList.remove('hidden');
    else document.getElementById('area_tmtk').classList.add('hidden');
});

function addPendidikan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="multi-row">
            <div class="form-group">
                <label>Jenjang / Tingkat</label>
                <select name="pend_jenjang[]" required>
                    <option value="">- Pilih -</option>
                    <option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA/SMK">SMA/SMK</option>
                    <option value="D3">D3</option><option value="D4">D4</option><option value="S1">S1</option>
                    <option value="S2">S2</option><option value="S3">S3</option>
                </select>
            </div>
            <div class="form-group"><label>Nama Institusi / Universitas</label><input type="text" name="pend_institusi[]" required></div>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" required></div>
            <div class="form-group"><label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label><input type="file" name="dok_pendidikan[]"></div>
        </div>
    </div>`;
    document.getElementById('pendidikan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addYayasan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <label>Golongan Yayasan</label>
            <select name="gol_yayasan[]">
                <option value="">- Pilih Golongan -</option>
                <option value="III/a">III/a</option><option value="III/b">III/b</option><option value="III/c">III/c</option><option value="III/d">III/d</option>
                <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option><option value="IV/c">IV/c</option><option value="IV/d">IV/d</option>
            </select>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>TMT Golongan</label><input type="date" name="tmt_gol_yayasan[]"></div>
            <div class="form-group"><label>Upload SK Golongan</label><input type="file" name="dok_gol_yayasan[]"></div>
        </div>
    </div>`;
    document.getElementById('yayasan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addRiwayatStatus() {
    newCounter++;
    const html = `
    <div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="multi-row">
            <div class="form-group">
                <label>Status Pegawai</label>
                <select name="new_riwayat_status_pegawai[n_${newCounter}]" required>
                    <option value="">- Pilih Status -</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Tidak Tetap">Tidak Tetap</option>
                </select>
            </div>
            <div class="form-group"><label>Unit Kerja</label><input type="text" name="new_riwayat_unit_kerja[n_${newCounter}]"></div>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Jabatan</label><input type="text" name="new_riwayat_posisi_jabatan[n_${newCounter}]"></div>
            <div class="form-group"><label>TMT Berlaku</label><input type="date" name="new_riwayat_tmt_mulai[n_${newCounter}]" required></div>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label>Dokumen SK Riwayat</label>
            <input type="file" name="new_riwayat_file[n_${newCounter}]" accept=".pdf,.png,.jpg,.jpeg">
        </div>
    </div>`;
    document.getElementById('riwayat-status-wrapper').insertAdjacentHTML('beforeend', html);
}
</script>

</body>
</html>

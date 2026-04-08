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

// Fetch golongan yayasan pegawai
$yayasans = $conn->query("SELECT * FROM yayasan_pegawai WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);
// Fetch riwayat pendidikan pegawai
$pendidikans = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl = $_POST['ttl'];
    $jenis = $_POST['jenis_pegawai'];
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jabatan = $_POST['posisi_jabatan'];
    $tmk = $_POST['tmt_mulai_kerja'] ?: null;
    $tmtk = $_POST['tmt_tidak_kerja'] ?: null;
    $unit = $_POST['unit_kerja'];
    $ket_tmtk = $_POST['ket_tmtk'] ?? '';

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

    // Handle TMTK Document
    $dok_tmtk = $pegawai['dok_tmtk'];
    if (isset($_FILES['dok_tmtk']) && $_FILES['dok_tmtk']['error'] == 0) {
        $ext = pathinfo($_FILES['dok_tmtk']['name'], PATHINFO_EXTENSION);
        $dok_tmtk = "tmtk_" . time() . "." . $ext;
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
            nama_lengkap=?, alamat=?, ttl=?, jenis_pegawai=?, status_pribadi=?, 
            posisi_jabatan=?, tmt_mulai_kerja=?, tmt_tidak_kerja=?, unit_kerja=?, 
            riwayat_pendidikan=?, ket_tidak_kerja=?, dok_tmtk=?, foto_profil=?
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssi", $nama, $alamat, $ttl, $jenis, $status_pribadi, $jabatan, $tmk, $tmtk, $unit, $pendidikan, $ket_tmtk, $dok_tmtk, $foto_profil, $id);
    $stmt->execute();
    $stmt->close();

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
// Re-fetch yayasans (in case page is just showing the form)
$yayasans = $conn->query("SELECT * FROM yayasan_pegawai WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);

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
        .hidden { display: none !important; }
        .delete-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            font-weight: 700;
            color: var(--danger);
            border-radius: var(--radius-md);
        }
        .file-current {
            display: inline-block;
            margin-top: 6px;
            font-size: 0.78rem;
            color: var(--accent);
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Edit Profil Staf</h1>
            <p>Perbarui informasi data personal dan kepegawaian staf Universitas Serang Raya.</p>
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
            <div class="form-group">
                <label>Upload Foto Profil (JPG/PNG)</label>
                <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png">
                <?php if(!empty($pegawai['foto_profil'])): ?>
                    <div style="margin-top:12px;">
                        <img src="<?= htmlspecialchars($pegawai['foto_profil']) ?>" alt="Foto Profil" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-light);">
                        <span class="file-current" style="display:inline-block;margin-left:10px;">Foto saat ini: <?= basename($pegawai['foto_profil']) ?></span>
                    </div>
                <?php endif; ?>
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
                <label>Alamat Domisili</label>
                <textarea name="alamat" rows="2" required><?= htmlspecialchars($pegawai['alamat']) ?></textarea>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Tempat & Tanggal Lahir (TTL)</label>
                    <input type="text" name="ttl" value="<?= htmlspecialchars($pegawai['ttl']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Status Pernikahan</label>
                    <select name="status_pribadi" required>
                        <option value="Menikah" <?= $pegawai['status_pribadi'] == 'Menikah' ? 'selected' : '' ?>>Menikah</option>
                        <option value="Belum Menikah" <?= $pegawai['status_pribadi'] == 'Belum Menikah' ? 'selected' : '' ?>>Belum Menikah</option>
                        <option value="Bercerai" <?= $pegawai['status_pribadi'] == 'Bercerai' ? 'selected' : '' ?>>Bercerai</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Kepegawaian -->
        <div class="form-section">
            <h3><i class="fas fa-briefcase"></i> Status Kepegawaian</h3>
            
            <div class="multi-row">
                <div class="form-group">
                    <label>Jenis Pegawai</label>
                    <div style="display: flex; gap: 25px; margin-top: 10px;">
                        <label class="radio-label"><input type="radio" name="jenis_pegawai" value="tetap" <?= $pegawai['jenis_pegawai'] == 'tetap' ? 'checked' : '' ?> required> Tetap</label>
                        <label class="radio-label"><input type="radio" name="jenis_pegawai" value="tdk tetap" <?= $pegawai['jenis_pegawai'] == 'tdk tetap' ? 'checked' : '' ?>> Tidak Tetap</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Posisi Jabatan / Role</label>
                    <input type="text" name="posisi_jabatan" value="<?= htmlspecialchars($pegawai['posisi_jabatan']) ?>" required>
                </div>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Terhitung Mulai Kerja (TMK)</label>
                    <input type="date" name="tmt_mulai_kerja" value="<?= $pegawai['tmt_mulai_kerja'] ?>" required>
                </div>
                <div class="form-group">
                    <label>TMT Tidak Kerja (Opsional)</label>
                    <input type="date" name="tmt_tidak_kerja" id="tmtk_input" value="<?= $pegawai['tmt_tidak_kerja'] ?>">
                </div>
            </div>

            <div id="area_tmtk" class="<?= $pegawai['tmt_tidak_kerja'] ? '' : 'hidden' ?> dynamic-item">
                <div class="multi-row">
                    <div class="form-group">
                        <label>Alasan Berhenti</label>
                        <select name="ket_tmtk">
                            <option value="">Pilih Alasan</option>
                            <option value="Resign" <?= $pegawai['ket_tidak_kerja'] == 'Resign' ? 'selected' : '' ?>>Resign</option>
                            <option value="Pensiun" <?= $pegawai['ket_tidak_kerja'] == 'Pensiun' ? 'selected' : '' ?>>Pensiun</option>
                            <option value="Putus Kontrak" <?= $pegawai['ket_tidak_kerja'] == 'Putus Kontrak' ? 'selected' : '' ?>>Putus Kontrak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dokumen SK Pemberhentian</label>
                        <input type="file" name="dok_tmtk" accept=".pdf,.png,.jpg,.jpeg">
                        <?php if(!empty($pegawai['dok_tmtk'])): ?>
                            <div style="margin-top: 8px; font-size: 0.8rem; color: var(--accent);">
                                <i class="fas fa-file-pdf"></i> Dokumen saat ini: <?= $pegawai['dok_tmtk'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Unit Kerja</label>
                    <input type="text" name="unit_kerja" value="<?= htmlspecialchars($pegawai['unit_kerja']) ?>" required>
                </div>
            </div>
        </div>

        <!-- SECTION: Riwayat Pendidikan -->
        <div class="form-section">
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
                                        <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2', 'S3'] as $p): ?>
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
                                    <label>Upload Ijazah <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
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
                                    <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2', 'S3'] as $p): ?>
                                    <option value="<?= $p ?>" <?= $pegawai['riwayat_pendidikan'] == $p ? 'selected' : '' ?>><?= $p ?></option>
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
                            <div class="form-group"><label>Upload Ijazah <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label><input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png"></div>
                        </div>
                        <input type="hidden" name="existing_dok_pendidikan[]" value="">
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addPendidikan()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Riwayat Pendidikan</button>
        </div>

        <!-- SECTION: Pangkat/Golongan Yayasan -->
        <div class="form-section">
            <h3><i class="fas fa-layer-group"></i> Pangkat/Golongan Yayasan</h3>
            <div id="yayasan-wrapper">
                <?php if (count($yayasans) > 0): ?>
                    <?php foreach($yayasans as $y): ?>
                        <div class="dynamic-item">
                            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
                            <div class="form-group">
                                <label>Golongan Yayasan</label>
                                <select name="gol_yayasan[]">
                                    <option value="">- Pilih Golongan -</option>
                                    <option value="III/a" <?= $y['golongan']=='III/a'?'selected':'' ?>>III/a</option>
                                    <option value="III/b" <?= $y['golongan']=='III/b'?'selected':'' ?>>III/b</option>
                                    <option value="III/c" <?= $y['golongan']=='III/c'?'selected':'' ?>>III/c</option>
                                    <option value="III/d" <?= $y['golongan']=='III/d'?'selected':'' ?>>III/d</option>
                                    <option value="IV/a" <?= $y['golongan']=='IV/a'?'selected':'' ?>>IV/a</option>
                                    <option value="IV/b" <?= $y['golongan']=='IV/b'?'selected':'' ?>>IV/b</option>
                                    <option value="IV/c" <?= $y['golongan']=='IV/c'?'selected':'' ?>>IV/c</option>
                                    <option value="IV/d" <?= $y['golongan']=='IV/d'?'selected':'' ?>>IV/d</option>
                                </select>
                            </div>
                            <div class="multi-row" style="margin-top:12px;">
                                <div class="form-group">
                                    <label>TMT Golongan Yayasan</label>
                                    <input type="date" name="tmt_gol_yayasan[]" value="<?= $y['tmt'] ?>">
                                </div>
                                <div class="form-group">
                                    <label>Upload SK Golongan Yayasan <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                    <input type="file" name="dok_gol_yayasan[]" accept=".pdf,.jpg,.png">
                                    <?php if($y['dokumen']): ?>
                                        <a href="<?= $y['dokumen'] ?>" target="_blank" class="file-current"><i class="fas fa-file-pdf"></i> <?= basename($y['dokumen']) ?></a>
                                        <input type="hidden" name="existing_dok_gol_yayasan[]" value="<?= $y['dokumen'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-item">
                        <div class="form-group">
                            <label>Golongan Yayasan</label>
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
                        <div class="multi-row" style="margin-top:12px;">
                            <div class="form-group"><label>TMT Golongan Yayasan</label><input type="date" name="tmt_gol_yayasan[]"></div>
                            <div class="form-group"><label>Upload SK Golongan Yayasan <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label><input type="file" name="dok_gol_yayasan[]" accept=".pdf,.jpg,.png"></div>
                        </div>
                        <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addYayasan()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Pangkat/Golongan Yayasan</button>
        </div>

        <!-- Reward & Punishment -->
        <div class="form-section">
            <div class="multi-row">
                <!-- Rewards -->
                <div>
                    <h3><i class="fas fa-medal" style="color: #ed8936;"></i> Penghargaan</h3>
                    <div id="reward-wrapper">
                        <?php while($r = $rewards->fetch_assoc()): ?>
                        <div class="dynamic-item">
                            <input type="hidden" name="reward_id[]" value="<?= $r['id'] ?>">
                            <input type="hidden" name="existing_reward_file[]" value="<?= $r['dokumen'] ?>">
                            <input type="text" name="reward_desc[]" value="<?= htmlspecialchars($r['keterangan']) ?>" style="margin-bottom:8px">
                            <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; gap: 10px;">
                                <input type="date" name="reward_date[]" value="<?= $r['tanggal'] ?>">
                                <input type="file" name="reward_file[]">
                                <button type="button" class="btn-icon delete-btn" style="color: var(--danger)"><i class="fas fa-trash"></i></button>
                            </div>
                            <?php if($r['dokumen']): ?>
                                <div style="font-size: 0.7rem; color: var(--accent); margin-top: 5px;"><i class="fas fa-file"></i> <?= $r['dokumen'] ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <button type="button" onclick="addReward()" class="btn btn-outline" style="width: 100%;"><i class="fas fa-plus"></i> Tambah Penghargaan</button>
                    <div id="deleted-rewards"></div>
                </div>

                <!-- Punishments -->
                <div>
                    <h3><i class="fas fa-gavel" style="color: #e53e3e;"></i> Sanksi / Catatan</h3>
                    <div id="punishment-wrapper">
                        <?php while($p = $punishments->fetch_assoc()): ?>
                        <div class="dynamic-item">
                            <input type="hidden" name="punish_id[]" value="<?= $p['id'] ?>">
                            <input type="hidden" name="existing_punish_file[]" value="<?= $p['dokumen'] ?>">
                            <input type="text" name="punish_desc[]" value="<?= htmlspecialchars($p['keterangan']) ?>" style="margin-bottom:8px">
                            <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; gap: 10px;">
                                <input type="date" name="punish_date[]" value="<?= $p['tanggal'] ?>">
                                <input type="file" name="punish_file[]">
                                <button type="button" class="btn-icon delete-btn" style="color: var(--danger)"><i class="fas fa-trash"></i></button>
                            </div>
                            <?php if($p['dokumen']): ?>
                                <div style="font-size: 0.7rem; color: var(--accent); margin-top: 5px;"><i class="fas fa-file"></i> <?= $p['dokumen'] ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <button type="button" onclick="addPunish()" class="btn btn-outline" style="width: 100%;"><i class="fas fa-plus"></i> Tambah Sanksi</button>
                    <div id="deleted-punishments"></div>
                </div>
            </div>
        </div>

        <div style="margin-top: 50px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 30px;">
            <a href="detail_pegawai.php?id=<?= $id ?>" class="btn" style="color: var(--text-muted); margin-right: 15px;">Batal</a>
            <button type="submit" name="update" class="btn btn-primary" style="padding: 12px 40px;"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
document.getElementById('tmtk_input').addEventListener('input', function() {
    if(this.value) document.getElementById('area_tmtk').classList.remove('hidden');
    else document.getElementById('area_tmtk').classList.add('hidden');
});

function addYayasan() {
    const container = document.getElementById('yayasan-wrapper');
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <label>Golongan Yayasan</label>
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
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>TMT Golongan Yayasan</label><input type="date" name="tmt_gol_yayasan[]"></div>
            <div class="form-group"><label>Upload SK Golongan Yayasan</label><input type="file" name="dok_gol_yayasan[]" accept=".pdf,.jpg,.png"></div>
        </div>
        <input type="hidden" name="existing_dok_gol_yayasan[]" value="">
    `;
    container.appendChild(div);
}

function addPendidikan() {
    const container = document.getElementById('pendidikan-wrapper');
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="multi-row">
            <div class="form-group">
                <label>Jenjang / Tingkat</label>
                <select name="pend_jenjang[]" required>
                    <option value="">- Pilih -</option>
                    <option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA/SMK">SMA/SMK</option><option value="D3">D3</option><option value="S1">S1</option><option value="S2">S2</option><option value="S3">S3</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Institusi / Universitas</label>
                <input type="text" name="pend_institusi[]" placeholder="Contoh: Universitas Indonesia" required>
            </div>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" min="1950" max="2100" placeholder="YYYY" required></div>
            <div class="form-group"><label>Upload Ijazah <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label><input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png"></div>
        </div>
        <input type="hidden" name="existing_dok_pendidikan[]" value="">
    `;
    container.appendChild(div);
}

function addReward() {
    const container = document.getElementById('reward-wrapper');
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <input type="text" name="reward_desc[]" placeholder="Deskripsi Penghargaan" style="margin-bottom:8px">
        <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; gap: 10px;">
            <input type="date" name="reward_date[]">
            <input type="file" name="reward_file[]">
            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color: var(--danger)"><i class="fas fa-times"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function addPunish() {
    const container = document.getElementById('punishment-wrapper');
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <input type="text" name="punish_desc[]" placeholder="Deskripsi Sanksi" style="margin-bottom:8px">
        <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; gap: 10px;">
            <input type="date" name="punish_date[]">
            <input type="file" name="punish_file[]">
            <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color: var(--danger)"><i class="fas fa-times"></i></button>
        </div>
    `;
    container.appendChild(div);
}

// Handle deletions of existing items
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const item = this.closest('.dynamic-item');
        const rid = item.querySelector('input[name="reward_id[]"]');
        const pid = item.querySelector('input[name="punish_id[]"]');
        
        if (rid) {
            const delWrapper = document.getElementById('deleted-rewards');
            delWrapper.innerHTML += `<input type="hidden" name="delete_rewards[]" value="${rid.value}">`;
        }
        if (pid) {
            const delWrapper = document.getElementById('deleted-punishments');
            delWrapper.innerHTML += `<input type="hidden" name="delete_punishments[]" value="${pid.value}">`;
        }
        
        item.style.position = 'relative';
        item.innerHTML += '<div class="delete-overlay">Dihapus</div>';
        setTimeout(() => item.classList.add('hidden'), 500);
    });
});
</script>

</body>
</html>

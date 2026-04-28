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
$rewards = $conn->query("SELECT * FROM reward_pegawai WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);
$punishments = $conn->query("SELECT * FROM punishment_pegawai WHERE pegawai_id = $id")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl_tempat = $_POST['ttl_tempat'] ?? '';
    $ttl_tanggal = $_POST['ttl_tanggal'] ?: null;
    $ttl_lama = ($ttl_tempat ? $ttl_tempat . ', ' : '') . ($ttl_tanggal ? date('d F Y', strtotime($ttl_tanggal)) : '');
    
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jabatan = $_POST['posisi_jabatan'];
    $unit = $_POST['unit_kerja'];
    $tmk = $_POST['tmt_mulai_kerja'] ?: null;
    $tmtk = $_POST['tmt_tidak_kerja'] ?: null;
    $ket_tmtk = $_POST['ket_tmtk'] ?? '';

    // Handle Status Riwayat
    $status_list = [];
    if(!empty($_POST['status_pegawai'])) {
        foreach($_POST['status_pegawai'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status_pegawai'][$i]) ? $_POST['tmt_status_pegawai'][$i] : null;
                $filename = $_POST['existing_dok_status_peg'][$i] ?? '';
                if(!empty($_FILES['dok_status_peg_riwayat']['name'][$i])) {
                    $filename = 'uploads/'.time().'_sp_'.basename($_FILES['dok_status_peg_riwayat']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status_peg_riwayat']['tmp_name'][$i], $filename);
                }
                $status_list[] = ['status' => $std, 'tmt' => $tmt, 'dokumen' => $filename];
            }
        }
    }
    $status_peg_main = $status_list[0]['status'] ?? $pegawai['status_pegawai'];

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
    $riwayat_pendidikan = $pendidikan_list[0]['jenjang'] ?? ($_POST['riwayat_pendidikan'] ?? '');

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
            nama_lengkap=?, alamat=?, ttl=?, ttl_tempat=?, ttl_tanggal=?, 
            status_pegawai=?, status_pribadi=?, posisi_jabatan=?, unit_kerja=?, 
            tmt_mulai_kerja=?, tmt_tidak_kerja=?, riwayat_pendidikan=?, 
            ket_tidak_kerja=?, dok_tmtk=?, dok_ktp=?, dok_kk=?, foto_profil=?
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $types = str_repeat("s", 17) . "i";
    $stmt->bind_param($types, 
        $nama, $alamat, $ttl_lama, $ttl_tempat, $ttl_tanggal, 
        $status_peg_main, $status_pribadi, $jabatan, $unit, 
        $tmk, $tmtk, $riwayat_pendidikan, $ket_tmtk, 
        $dok_tmtk, $dok_ktp, $dok_kk, $foto_profil, $id);
    
    if($stmt->execute()) {
        // Sync Riwayat Status
        $conn->query("DELETE FROM status_pegawai_riwayat WHERE pegawai_id = $id");
        foreach ($status_list as $sl) {
            $st = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, tmt_mulai_kerja, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $id, $sl['status'], $sl['tmt'], $sl['dokumen']);
            $st->execute();
        }

        // Sync Pendidikan
        $conn->query("DELETE FROM pendidikan_pegawai WHERE pegawai_id = $id");
        foreach ($pendidikan_list as $pend) {
            $st = $conn->prepare("INSERT INTO pendidikan_pegawai (pegawai_id, jenjang, institusi, tahun_lulus, dokumen) VALUES (?, ?, ?, ?, ?)");
            $st->bind_param("issss", $id, $pend['jenjang'], $pend['institusi'], $pend['tahun_lulus'], $pend['dokumen']);
            $st->execute();
        }

        // Rewards & Punishments
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
                    $st_rev->bind_param("isss", $id, $desc, $tanggal, $filename);
                    $st_rev->execute();
                }
            }
        }

        echo "<script>alert('Data pegawai berhasil diperbarui!');location='detail_pegawai.php?id=$id';</script>";
    } else {
        echo "<script>alert('Error: " . addslashes($stmt->error) . "');</script>";
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
                        <div class="row g-4">
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
                        <div class="section-title"><i class="fas fa-briefcase"></i> Riwayat Status Pegawai</div>
                        <div id="status-wrapper">
                            <?php if(!empty($status_riwayats)): foreach($status_riwayats as $sr): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Status Pegawai</label>
                                        <select name="status_pegawai[]" class="form-select" required>
                                            <option value="Tetap" <?= $sr['status_pegawai'] == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                                            <option value="Tidak Tetap" <?= $sr['status_pegawai'] == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TMT Mulai</label>
                                        <input type="date" name="tmt_status_pegawai[]" class="form-control" value="<?= $sr['tmt_mulai_kerja'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_dok_status_peg[]" value="<?= $sr['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="dynamic-item">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label">Status</label><select name="status_pegawai[]" class="form-select"><option>Tetap</option><option>Tidak Tetap</option></select></div>
                                    <div class="col-md-6"><label class="form-label">TMT</label><input type="date" name="tmt_status_pegawai[]" class="form-control"></div>
                                    <input type="hidden" name="existing_dok_status_peg[]" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-4" onclick="addStatusRow()"><i class="fas fa-plus me-1"></i> Tambah Riwayat Status</button>

                        <div class="row g-3 border-top pt-4">
                            <div class="col-md-6">
                                <label class="form-label">Jabatan Saat Ini</label>
                                <input type="text" name="posisi_jabatan" class="form-control" value="<?= htmlspecialchars($pegawai['posisi_jabatan']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit Kerja</label>
                                <input type="text" name="unit_kerja" class="form-control" value="<?= htmlspecialchars($pegawai['unit_kerja']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TMT Mulai Kerja (Pertama)</label>
                                <input type="date" name="tmt_mulai_kerja" class="form-control" value="<?= $pegawai['tmt_mulai_kerja'] ?>">
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
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Jenjang</label>
                                        <select name="pend_jenjang[]" class="form-select">
                                            <?php foreach(['SD','SMP','SMA/SMK','D3','D4','S1','S2','S3'] as $j): ?>
                                                <option value="<?= $j ?>" <?= $p['jenjang']==$j?'selected':'' ?>><?= $j ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Institusi</label>
                                        <input type="text" name="pend_institusi[]" class="form-control" value="<?= htmlspecialchars($p['institusi']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tgl Lulus</label>
                                        <input type="date" name="pend_tahun[]" class="form-control" value="<?= $p['tahun_lulus'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_dok_pendidikan[]" value="<?= $p['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="dynamic-item">
                                <div class="row g-3">
                                    <div class="col-md-2"><label class="form-label">Jenjang</label><select name="pend_jenjang[]" class="form-select"><option>SMA/SMK</option><option>S1</option><option>S2</option></select></div>
                                    <div class="col-md-6"><label class="form-label">Institusi</label><input type="text" name="pend_institusi[]" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label">Tgl Lulus</label><input type="date" name="pend_tahun[]" class="form-control"></div>
                                    <input type="hidden" name="existing_dok_pendidikan[]" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-4" onclick="addPendRow()"><i class="fas fa-plus me-1"></i> Tambah Pendidikan</button>
                    </div>

                    <!-- Tab 4: History -->
                    <div class="tab-pane fade" id="history">
                        <div class="section-title"><i class="fas fa-medal"></i> Penghargaan & Sanksi</div>
                        <div id="reward-wrapper">
                            <?php if(!empty($rewards)): foreach($rewards as $rw): ?>
                            <div class="dynamic-item">
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Keterangan Reward</label>
                                        <input type="text" name="reward_desc[]" class="form-control" value="<?= htmlspecialchars($rw['keterangan']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" name="reward_date[]" class="form-control" value="<?= $rw['tanggal'] ?>">
                                    </div>
                                    <input type="hidden" name="existing_reward_file[]" value="<?= $rw['dokumen'] ?>">
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-4" onclick="addRewardRow()"><i class="fas fa-plus me-1"></i> Tambah Penghargaan</button>

                        <div class="section-title pt-4 border-top"><i class="fas fa-file-shield"></i> Dokumen Pendukung</div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Scan KTP</label><input type="file" name="dok_ktp" class="form-control"><?php if($pegawai['dok_ktp']): ?><small class="text-primary mt-1 d-block"><i class="fas fa-check"></i> Tersedia</small><?php endif; ?></div>
                            <div class="col-md-6"><label class="form-label">Scan KK</label><input type="file" name="dok_kk" class="form-control"><?php if($pegawai['dok_kk']): ?><small class="text-primary mt-1 d-block"><i class="fas fa-check"></i> Tersedia</small><?php endif; ?></div>
                        </div>
                    </div>
                </div>

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
        reader.onload = function(e) { document.getElementById('previewFoto').src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}

function addStatusRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Status</label><select name="status_pegawai[]" class="form-select"><option value="Tetap">Tetap</option><option value="Tidak Tetap">Tidak Tetap</option></select></div>
            <div class="col-md-6"><label class="form-label">TMT</label><input type="date" name="tmt_status_pegawai[]" class="form-control"></div>
            <input type="hidden" name="existing_dok_status_peg[]" value="">
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function addPendRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Jenjang</label><select name="pend_jenjang[]" class="form-select"><option>SMA/SMK</option><option>S1</option><option>S2</option><option>S3</option></select></div>
            <div class="col-md-6"><label class="form-label">Institusi</label><input type="text" name="pend_institusi[]" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Tgl Lulus</label><input type="date" name="pend_tahun[]" class="form-control"></div>
            <input type="hidden" name="existing_dok_pendidikan[]" value="">
        </div>
    </div>`;
    document.getElementById('pend-wrapper').insertAdjacentHTML('beforeend', html);
}

function addRewardRow() {
    const html = `<div class="dynamic-item">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Keterangan</label><input type="text" name="reward_desc[]" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="reward_date[]" class="form-control"></div>
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
</body>
</html>

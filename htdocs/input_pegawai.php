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

    // Handle dok_status_pegawai upload
    $dok_status_pegawai = '';
    if (!empty($_FILES['dok_status_pegawai']['name']) && $_FILES['dok_status_pegawai']['error'] == 0) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $ext = pathinfo($_FILES['dok_status_pegawai']['name'], PATHINFO_EXTENSION);
        $dok_status_pegawai = 'uploads/dok_sp_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['dok_status_pegawai']['tmp_name'], $dok_status_pegawai);
    }

    // Handle KTP upload
    $dok_ktp = '';
    if (!empty($_FILES['dok_ktp']['name']) && $_FILES['dok_ktp']['error'] == 0) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $ext = pathinfo($_FILES['dok_ktp']['name'], PATHINFO_EXTENSION);
        $dok_ktp = 'uploads/ktp_p_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['dok_ktp']['tmp_name'], $dok_ktp);
    }

    // Handle KK upload
    $dok_kk = '';
    if (!empty($_FILES['dok_kk']['name']) && $_FILES['dok_kk']['error'] == 0) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
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
        if (!is_dir('dokumen')) mkdir('dokumen', 0777, true);
        move_uploaded_file($_FILES['dok_tmtk']['tmp_name'], "dokumen/" . $dok_tmtk);
    }

    $foto_profil = '';
    if(!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] == 0) {
        if(!is_dir('uploads')) mkdir('uploads', 0777, true);
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = 'uploads/foto_p_'.time().'.'.$ext;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $foto_profil);
    }

    $status_peg_list = [];
    if(!empty($_POST['status_pegawai'])) {
        foreach($_POST['status_pegawai'] as $i => $std) {
            if(trim($std) !== '') {
                $tmt = !empty($_POST['tmt_status_pegawai'][$i]) ? $_POST['tmt_status_pegawai'][$i] : null;
                $filename = '';
                if(!empty($_FILES['dok_status_peg_riwayat']['name'][$i])) {
                    $filename = 'uploads/'.time().'_sp_'.basename($_FILES['dok_status_peg_riwayat']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status_peg_riwayat']['tmp_name'][$i], $filename);
                }
                $status_peg_list[] = ['status' => $std, 'tmt' => $tmt, 'dokumen' => $filename];
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
            $st = $conn->prepare("INSERT INTO status_pegawai_riwayat (pegawai_id, status_pegawai, tmt, dokumen) VALUES (?, ?, ?, ?)");
            $st->bind_param("isss", $pegawai_id, $stt['status'], $stt['tmt'], $stt['dokumen']);
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

$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'],
    ['label' => 'Tambah Pegawai', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pegawai | UNSERA</title>
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
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: centre; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 style="font-size: 1.5rem; color: var(--text-main); font-weight: 600;">Registrasi Pegawai Baru</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">Pendaftaran tenaga kependidikan dan staf administrasi baru.</p>
            </div>
            <a href="data_pegawai.php" class="btn btn-outline" style="height: fit-content;">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" style="max-width: 1100px; margin: 0 auto;">
        
        <!-- Profil Picture Section -->
        <div class="card" style="display: flex; align-items: center; gap: 20px; padding: 20px;">
            <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); flex-shrink: 0;">
                <i class="fas fa-camera" style="font-size: 2rem; color: #94a3b8;"></i>
            </div>
            <div style="flex: 1;">
                <h3 style="margin-bottom: 5px; font-size: 1rem; border: none; padding: 0;">Foto Profil Pegawai</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 10px;">Unggah foto formal staf. Format JPG/PNG, Maks 2MB.</p>
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png" style="max-width: 400px; padding: 5px; font-size: 0.85rem;">
                </div>
            </div>
        </div>

        <!-- Informasi Pribadi -->
        <div class="card" style="padding: 24px;">
            <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; font-size: 1.05rem;">Data Identitas Pegawai</h3>
            <div class="form-group" style="margin-top: 15px;">
                <label>Nama Lengkap (beserta gelar)</label>
                <input type="text" name="nama_lengkap" placeholder="Contoh: Ahmad Subarjo, S.Kom." required>
            </div>
            
            <div class="multi-row" style="margin-top: 20px;">
                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="ttl_tempat" placeholder="Kota Kelahiran" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="ttl_tanggal" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>Alamat Tinggal Sesuai KTP</label>
                <textarea name="alamat" rows="3" placeholder="Tuliskan alamat lengkap..." required></textarea>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>Status Pernikahan</label>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php foreach(['Menikah', 'Belum Menikah', 'Bercerai'] as $stat): ?>
                        <label class="radio-label" style="background: #f8fafc; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: var(--radius-sm);  flex: 1; text-align: center; justify-content: center;">
                            <input type="radio" name="status_pribadi" value="<?= $stat ?>" required style="margin-right: 8px;"> <?= $stat ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="multi-row" style="margin-top: 20px; background: #f8fafc; padding: 15px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Dokumen KTP (PDF/JPG)</label>
                    <input type="file" name="dok_ktp" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Dokumen Kartu Keluarga</label>
                    <input type="file" name="dok_kk" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
        </div>

        <!-- Status Kepegawaian -->
        <div class="card" style="padding: 24px;">
            <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; font-size: 1.05rem;">Status Kepegawaian & Penugasan</h3>
            
            <div id="status-pegawai-wrapper" style="margin-top: 15px;">
                <div class="dynamic-item" style="border-left: 3px solid var(--primary); background: #ffffff;">
                    <div class="multi-row">
                        <div class="form-group">
                            <label>Status Pegawai Saat Ini</label>
                            <select name="status_pegawai[]" required>
                                <option value="">- Pilih Status -</option>
                                <option value="Tetap">Tetap</option>
                                <option value="Tidak Tetap">Tidak Tetap</option>
                                <option value="Honorer">Honorer</option>
                                <option value="Kontrak">Kontrak</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>TMT Status</label>
                            <input type="date" name="tmt_status_pegawai[]">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:15px; margin-bottom: 0;">
                        <label>SK Status Kepegawaian</label>
                        <input type="file" name="dok_status_peg_riwayat[]" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>
            <button type="button" onclick="addStatusPegawai()" class="btn btn-outline" style="width:100%; margin-bottom: 20px; border-style: dashed;">
                <i class="fas fa-plus"></i> Tambah Riwayat Status Pegawai
            </button>

            <div class="multi-row" style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Jabatan / Posisi</label>
                    <input type="text" name="posisi_jabatan" placeholder="Contoh: Staf IT / Administrasi" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Unit Kerja / Divisi</label>
                    <input type="text" name="unit_kerja" placeholder="Contoh: Biro Umum / Fakultas" required>
                </div>
            </div>

            <div class="multi-row" style="margin-bottom: 25px;">
                <div class="form-group">
                    <label>Tanggal Mulai Bekerja (TMK)</label>
                    <input type="date" name="tmt_mulai_bekerja" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai Tidak Bekerja (TMTK)</label>
                    <input type="date" name="tmt_tidak_bekerja" id="tmtk_input">
                </div>
            </div>

            <div id="area_tmtk" class="hidden dynamic-item" style="background: #fff1f2; border: 1px solid #fecaca;">
                <h4 style="font-size: 0.9rem; color: #b91c1c; font-weight: 800; margin-bottom: 15px; text-transform: uppercase;">Informasi Pemberhentian</h4>
                <div class="multi-row">
                    <div class="form-group">
                        <label style="color: #b91c1c;">Alasan Berhenti</label>
                        <select name="ket_tmtk" style="background: white;">
                            <option value="">Pilih Alasan</option>
                            <option value="Resign">Resign</option>
                            <option value="Pensiun">Pensiun</option>
                            <option value="Putus Kontrak">Putus Kontrak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="color: #b91c1c;">SK Pemberhentian</label>
                        <input type="file" name="dok_tmtk" accept=".pdf,.png,.jpg,.jpeg" style="background: white;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Kualifikasi Pendidikan -->
        <div class="card">
            <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; font-size: 1.05rem; margin-bottom: 20px;">Kualifikasi Pendidikan Terakhir</h3>
            <div id="pendidikan-wrapper">
                <div class="dynamic-item" style="border-left: 4px solid var(--success); background: #ffffff;">
                    <div class="multi-row">
                        <div class="form-group">
                            <label>Jenjang / Tingkat</label>
                            <select name="pend_jenjang[]" required style="font-weight:600;">
                                <option value="">- Pilih Jenjang -</option>
                                <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $p): ?>
                                <option value="<?= $p ?>"><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nama Institusi / Sekolah</label>
                            <input type="text" name="pend_institusi[]" placeholder="Contoh: Universitas Serang Raya" required>
                        </div>
                    </div>
                    <div class="multi-row" style="margin-top:20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Tahun Lulus</label>
                            <input type="number" name="pend_tahun[]" min="1950" max="2100" placeholder="YYYY" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Upload Ijazah / Transkrip</label>
                            <input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png" style="background: #f8fafc;">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" onclick="addPendidikan()" class="btn btn-outline" style="width:100%; margin-bottom: 25px; border-style: dashed; border-width: 2px; color: var(--success); border-color: rgba(16, 185, 129, 0.3);">
                <i class="fas fa-plus"></i> Tambah Riwayat Pendidikan
            </button>
        </div>

        <!-- Reward & Punishment -->
        <div class="multi-row" style="margin-bottom: 30px;">
            <div class="card" style="margin-bottom: 0;">
                <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; font-size: 1.05rem; margin-bottom: 15px;">Penghargaan</h3>
                <div id="reward-wrapper"></div>
                <button type="button" onclick="addReward()" class="btn btn-outline" style="width: 100%; border-style: dashed;"><i class="fas fa-plus"></i> Tambah</button>
            </div>
            <div class="card" style="margin-bottom: 0;">
                <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; font-size: 1.05rem; margin-bottom: 15px;">Sanksi & Catatan</h3>
                <div id="punishment-wrapper"></div>
                <button type="button" onclick="addPunish()" class="btn btn-outline" style="width: 100%; border-style: dashed;"><i class="fas fa-plus"></i> Tambah</button>
            </div>
        </div>

        <div style="background: white; border-top: 1px solid var(--border-color); padding: 15px 20px; display: flex; justify-content: flex-end; gap: 15px; align-items: center; border-radius: var(--radius-sm); box-shadow: var(--shadow-sm);">
            <a href="data_pegawai.php" class="btn btn-outline">Batal</a>
            <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save" style="margin-right: 6px;"></i> Simpan Data Pegawai</button>
        </div>
    </form>
</div>

<script>
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
                    <option value="SD">SD</option>
                    <option value="SMP">SMP</option>
                    <option value="SMA/SMK">SMA/SMK</option>
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Institusi / Universitas</label>
                <input type="text" name="pend_institusi[]" placeholder="Contoh: Universitas Indonesia" required>
            </div>
        </div>
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>Tahun Lulus</label><input type="number" name="pend_tahun[]" min="1950" max="2100" placeholder="YYYY" required></div>
            <div class="form-group"><label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label><input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png"></div>
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
    </div>`;
    document.getElementById('yayasan-wrapper').insertAdjacentHTML('beforeend', html);
}

function addStatusPegawai() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="multi-row">
            <div class="form-group">
                <label>Status Pegawai</label>
                <select name="status_pegawai[]" required>
                    <option value="">- Pilih Status -</option>
                    <option value="Tetap">Tetap</option>
                    <option value="Tidak Tetap">Tidak Tetap</option>
                    <option value="Honorer">Honorer</option>
                    <option value="Kontrak">Kontrak</option>
                </select>
            </div>
            <div class="form-group">
                <label>TMT Status</label>
                <input type="date" name="tmt_status_pegawai[]">
            </div>
        </div>
        <div class="form-group" style="margin-top:10px;">
            <label>Upload Dokumen Status</label>
            <input type="file" name="dok_status_peg_riwayat[]" accept=".pdf,.jpg,.jpeg,.png">
        </div>
    </div>`;
    document.getElementById('status-pegawai-wrapper').insertAdjacentHTML('beforeend', html);
}
</script>

</body>
</html>
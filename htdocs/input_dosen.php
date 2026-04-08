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
    
    $status_keaktifan = $_POST['status_keaktifan'] ?? null;
    $keterangan_keaktifan = '';
    if($status_keaktifan === 'Tidak Aktif') {
        $keterangan_keaktifan = $_POST['ket_tidak_aktif'] ?? '';
        if($keterangan_keaktifan === 'Lainnya') {
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
                $filename = '';
                if(!empty($_FILES['dok_status']['name'][$i])) {
                    $filename = 'uploads/'.time().'_status_'.basename($_FILES['dok_status']['name'][$i]);
                    move_uploaded_file($_FILES['dok_status']['tmp_name'][$i], $filename);
                }
                $status_list[] = ['status' => $std, 'tmt' => $tmt, 'dokumen' => $filename];
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
    $no_serdos = $_POST['no_serdos'] ?: null;
    $dok_serdos = '';
    if(!empty($_FILES['dok_serdos']['name'])) {
        $dok_serdos = 'uploads/'.time().'_'.basename($_FILES['dok_serdos']['name']);
        move_uploaded_file($_FILES['dok_serdos']['tmp_name'], $dok_serdos);
    }
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
        homebase_prodi, unit_kerja, no_serdos, dok_serdos, riwayat_pendidikan, foto_profil, status_keaktifan, keterangan_keaktifan
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssssssssssssssssss", 
        $nama, $alamat, $ttl_tempat, $ttl_tanggal, $nip, $nidn, $nuptk, $status_dosen, $status_pribadi, $dok_ktp, $dok_kk, $jenis_dosen, $jabatan_struktural, $tmk, $tmtk, $ket_tidak_kerja, $dok_tidak_kerja,
        $jabfung_akademik, $tmt_jabfung, $dok_jabfung,
        $gol_lldikti, $tmt_gol_lldikti, $dok_gol_lldikti,
        $gol_yayasan, $tmt_gol_yayasan, $dok_gol_yayasan,
        $homebase_prodi, $unit_kerja, $no_serdos, $dok_serdos, $riwayat_pendidikan, $foto_profil, $status_keaktifan, $keterangan_keaktifan
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
        $st = $conn->prepare("INSERT INTO status_dosen_riwayat (dosen_id, status_dosen, tmt, dokumen) VALUES (?, ?, ?, ?)");
        $st->bind_param("isss", $last_dosen_id, $stt['status'], $stt['tmt'], $stt['dokumen']);
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

    echo "<script>alert('Data dosen berhasil disimpan!');location='daftar_dosen.php';</script>"; 
    exit;
}

$breadcrumbs = [
    ['label' => 'Dosen', 'url' => 'daftar_dosen.php'],
    ['label' => 'Tambah', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Dosen | UNSERA</title>
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

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Tambah Data Dosen</h1>
            <p>Lengkapi formulir di bawah untuk menambahkan dosen baru ke sistem.</p>
        </div>
        <div>
            <a href="daftar_dosen.php" class="btn btn-outline">
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
            </div>
        </div>

        <!-- SECTION 1: Informasi Pribadi -->
        <div class="card" style="margin-bottom: 20px;">
            <h3><i class="fas fa-user"></i> Informasi Pribadi</h3>
            <div class="form-group">
                <label>Nama Lengkap (beserta gelar)</label>
                <input type="text" name="nama_lengkap" placeholder="Contoh: Dr. Budi Santoso, M.Kom" required>
            </div>
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="2" placeholder="Alamat sesuai KTP" required></textarea>
            </div>
            <div class="multi-row">
                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="ttl_tempat" placeholder="Kota" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="ttl_tanggal" required>
                </div>
            </div>
            
            <!-- Tambahan NIP, NIDN, NUPTK -->
            <div class="multi-row" style="margin-top: 16px;">
                <div class="form-group">
                    <label>NIP</label>
                    <input type="text" name="nip" placeholder="Nomor Induk Pegawai (Optional)">
                </div>
                <div class="form-group">
                    <label>NIDN</label>
                    <input type="text" name="nidn" placeholder="Nomor Induk Dosen Nasional (Optional)">
                </div>
            </div>
            <div class="form-group" style="margin-top: 16px;">
                <label>NUPTK</label>
                <input type="text" name="nuptk" placeholder="Nomor Unik Pendidik dan Tenaga Kependidikan (Optional)">
            </div>
            <div class="form-group">
                <label>Status Pernikahan</label>
                <select name="status_pribadi" required>
                    <option value="">- Pilih -</option>
                    <option value="Menikah">Menikah</option>
                    <option value="Belum Menikah">Belum Menikah</option>
                    <option value="Bercerai">Bercerai</option>
                </select>
            </div>

            <div class="multi-row" style="margin-top: 16px;">
                <div class="form-group">
                    <label><i class="fas fa-id-card" style="color: var(--primary); margin-right: 6px;"></i>Upload KTP</label>
                    <input type="file" name="dok_ktp" accept=".pdf,.jpg,.jpeg,.png">
                    <small style="color: var(--text-muted); font-size: 0.8rem;">Format: PDF, JPG, PNG</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-users" style="color: var(--primary); margin-right: 6px;"></i>Upload KK (Kartu Keluarga)</label>
                    <input type="file" name="dok_kk" accept=".pdf,.jpg,.jpeg,.png">
                    <small style="color: var(--text-muted); font-size: 0.8rem;">Format: PDF, JPG, PNG</small>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Status Kepegawaian -->
        <div class="card" style="margin-bottom: 20px;">
            <h3><i class="fas fa-briefcase"></i> Status Kepegawaian</h3>

            <div id="status-wrapper">
                <div class="dynamic-item">
                    <div class="form-group">
                        <label>Status Dosen</label>
                        <select name="status_dosen[]" required>
                            <option value="">- Pilih Status Dosen -</option>
                            <option value="Tetap">Tetap</option>
                            <option value="Tidak Tetap">Tidak Tetap</option>
                            <option value="Homebase">Homebase</option>
                        </select>
                    </div>
                    <div class="multi-row" style="margin-top:12px;">
                        <div class="form-group">
                            <label>TMT <span style="color:var(--text-muted); font-weight:400;">(Tanggal Mulai Bekerja)</span></label>
                            <input type="date" name="tmt_status[]">
                        </div>
                        <div class="form-group">
                            <label>Upload Dokumen <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                            <input type="file" name="dok_status[]" accept=".pdf,.jpg,.png">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" onclick="addStatusDosen()" class="btn btn-outline" style="width:100%; margin-bottom:15px;"><i class="fas fa-plus"></i> Tambah Riwayat Status Dosen</button>

            <!-- Moved Homebase & Unit Kerja Here -->
            <div class="multi-row" style="margin-bottom:20px;">
                <div class="form-group">
                    <label>Homebase Prodi</label>
                    <input type="text" name="homebase_prodi" placeholder="Contoh: Teknik Informatika" required>
                </div>
                <div class="form-group">
                    <label>Unit Kerja</label>
                    <input type="text" name="unit_kerja" placeholder="Contoh: Fakultas Teknik" required>
                </div>
            </div>

            <!-- Jenis Penugasan -->
            <div id="area_jenis_penugasan" style="margin-top:8px; background:#f0f6ff; border:1.5px solid #bfdbfe; border-radius:10px; padding:20px;">
                <label style="font-weight:700; font-size:0.9rem; color:var(--primary); display:block; margin-bottom:12px;"><i class="fas fa-tag"></i> Jenis Penugasan</label>
                <div style="display:flex; gap:24px; flex-wrap:wrap; align-items:center;">
                    <label class="radio-label"><input type="radio" name="jenis_dosen" value="Struktural"> Struktural</label>
                    <label class="radio-label"><input type="radio" name="jenis_dosen" value="Non Struktural" checked> Non Struktural</label>
                </div>
                <div id="area_jabatan_struktural" class="hidden" style="margin-top:14px;">
                    <label style="font-weight:600; font-size:0.85rem;">Nama Jabatan Struktural</label>
                    <input type="text" name="jabatan_struktural" placeholder="Contoh: Wakil Rektor I" style="margin-top:6px;">
                </div>
            </div>

            <div class="multi-row" style="margin-top:20px;">
                <div class="form-group hidden" id="group_tmk">
                    <label>Terhitung Mulai Bertugas (TMBT)</label>
                    <input type="date" name="tmk">
                </div>
                <div class="form-group hidden" id="group_tmtk">
                    <label>Terhitung Mulai Tidak Bertugas (TMTBT)</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="date" name="tmtk" id="tmtk_input" style="flex: 1;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; font-weight: 700; margin-bottom: 4px; display: block;">UPLOAD DOKUMEN TMTBT</label>
                            <input type="file" name="dok_tidak_kerja" accept=".pdf,.jpg,.png">
                        </div>
                    </div>
                </div>
            </div>

            <div id="area_tmtk" class="hidden" style="background:#fff5f5; border:1.5px solid #fecaca; border-radius:10px; padding:20px; margin-top:4px;">
                <label style="font-weight:700; font-size:0.9rem; color:var(--danger); display:block; margin-bottom:12px;"><i class="fas fa-exclamation-circle"></i> Informasi Pemberhentian</label>
                <div class="form-group">
                    <label>Alasan Berhenti</label>
                    <select name="ket_tidak_kerja">
                        <option value="">- Pilih Alasan -</option>
                        <option value="Putus Kontrak">Putus Kontrak</option>
                        <option value="Diberhentikan">Diberhentikan</option>
                        <option value="Resign">Resign</option>
                        <option value="Pensiun">Pensiun</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Jabatan Akademik Dosen -->
        <div class="card" style="margin-bottom: 20px;">
            <h3><i class="fas fa-award"></i> Jabatan Akademik Dosen</h3>
            <div id="jabfung-wrapper">
                <div class="dynamic-item">
                    <div class="form-group">
                        <select name="jabfung_akademik[]">
                            <option value="">- Pilih Jabatan -</option>
                            <option value="Asisten Ahli">Asisten Ahli</option>
                            <option value="Lektor">Lektor</option>
                            <option value="Lektor Kepala">Lektor Kepala</option>
                            <option value="Guru Besar">Guru Besar</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top:12px;">
                        <label style="font-size:0.8rem">TMT <span style="color:var(--text-muted);">(Terhitung Mulai Tanggal SK)</span></label>
                        <input type="date" name="tmt_jabfung[]">
                    </div>
                    <div class="form-group" style="margin-top:12px;">
                        <label style="font-size:0.8rem">Upload SK</label>
                        <input type="file" name="dok_jabfung[]" accept=".pdf,.jpg,.png">
                    </div>
                </div>
            </div>
            <button type="button" onclick="addJabfung()" class="btn btn-outline" style="width:100%; font-size: 0.8rem; padding: 6px;"><i class="fas fa-plus"></i> Tambah</button>
        </div>

        <!-- Row for DIKTI and Yayasan -->
        <div class="multi-row" style="margin-bottom: 20px;">
            <!-- SECTION 4: Pangkat/Golongan Sesuai DIKTI -->
            <div class="card" style="margin-bottom: 0;">
                <h3><i class="fas fa-university"></i> Pangkat/Golongan Sesuai DIKTI</h3>
                <div id="lldikti-wrapper">
                    <div class="dynamic-item">
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
                            <label style="font-size: 0.8rem;">TMT</label>
                            <input type="date" name="tmt_gol_lldikti[]">
                        </div>
                        <div class="form-group" style="margin-top:12px;">
                            <label style="font-size: 0.8rem;">Upload SK</label>
                            <input type="file" name="dok_gol_lldikti[]" accept=".pdf,.jpg,.png">
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addLldikti()" class="btn btn-outline" style="width:100%; font-size: 0.8rem; padding: 6px;"><i class="fas fa-plus"></i> Tambah</button>
            </div>

            <!-- SECTION 5: Golongan Yayasan -->
            <div class="card" style="margin-bottom: 0;">
                <h3><i class="fas fa-building"></i> Golongan Yayasan</h3>
                <div id="yayasan-wrapper">
                    <div class="dynamic-item">
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
                            <label style="font-size: 0.8rem;">TMT</label>
                            <input type="date" name="tmt_gol_yayasan[]">
                        </div>
                        <div class="form-group" style="margin-top:12px;">
                            <label style="font-size: 0.8rem;">Upload SK</label>
                            <input type="file" name="dok_gol_yayasan[]" accept=".pdf,.jpg,.png">
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addYayasan()" class="btn btn-outline" style="width:100%; font-size: 0.8rem; padding: 6px;"><i class="fas fa-plus"></i> Tambah</button>
            </div>
        </div>

        <!-- SECTION 6: Pendidikan & Sertifikasi -->
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</h3>
            <div id="pendidikan-wrapper">
                <div class="dynamic-item">
                    <div class="multi-row">
                        <div class="form-group">
                            <label>Jenjang / Tingkat</label>
                            <select name="pend_jenjang[]" required>
                                <option value="">- Pilih -</option>
                                <?php foreach(['S1', 'S2', 'S3'] as $p): ?>
                                <option value="<?= $p ?>"><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nama Institusi / Universitas</label>
                            <input type="text" name="pend_institusi[]" placeholder="Contoh: Universitas Indonesia" required>
                        </div>
                    </div>
                    <div class="multi-row" style="margin-top:12px;">
                        <div class="form-group">
                            <label>Tahun Lulus</label>
                            <input type="number" name="pend_tahun[]" min="1950" max="2100" placeholder="YYYY" required>
                        </div>
                        <div class="form-group">
                            <label>Upload Ijazah/Transkrip <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                            <input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" onclick="addPendidikan()" class="btn btn-outline" style="width:100%; margin-bottom: 20px;"><i class="fas fa-plus"></i> Tambah Riwayat Pendidikan</button>

            <h3 style="margin-top:20px;"><i class="fas fa-certificate"></i> Sertifikasi</h3>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Apakah dosen sudah sertifikasi (serdos)?</label>
                <div style="display:flex; gap:20px;">
                    <label class="radio-label"><input type="radio" name="is_serdos" value="Ya" onclick="document.getElementById('area_serdos').style.display='flex'"> Ya</label>
                    <label class="radio-label"><input type="radio" name="is_serdos" value="Tidak" onclick="document.getElementById('area_serdos').style.display='none'" checked> Tidak</label>
                </div>
            </div>
            
            <div id="area_serdos" class="multi-row" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:8px;">
                <div class="form-group">
                    <label>Nomor Sertifikasi Dosen</label>
                    <input type="text" name="no_serdos" placeholder="Contoh: 123456789">
                </div>
                <div class="form-group">
                    <label>Upload Dokumen Sertifikasi (Serdos) <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                    <input type="file" name="dok_serdos" accept=".pdf,.jpg,.png">
                </div>
            </div>
        </div>

        <!-- SECTION 7: Penghargaan & Sanksi -->
        <div class="multi-row" style="margin-bottom:20px;">
            <div class="card">
                <h3><i class="fas fa-medal" style="color:#d97706;"></i> Penghargaan (Reward)</h3>
                <div id="reward-wrapper"></div>
                <button type="button" onclick="addReward()" class="btn btn-outline" style="width:100%; margin-top:10px;"><i class="fas fa-plus"></i> Tambah Reward</button>
            </div>
            <div class="card">
                <h3><i class="fas fa-gavel" style="color:#dc2626;"></i> Sanksi (Punishment)</h3>
                <div id="punishment-wrapper"></div>
                <button type="button" onclick="addPunishment()" class="btn btn-outline" style="width:100%; margin-top:10px;"><i class="fas fa-plus"></i> Tambah Sanksi</button>
            </div>
        </div>

        <!-- SECTION 8: Status Keaktifan -->
        <div class="card" style="margin-bottom:20px;">
            <h3><i class="fas fa-user-check"></i> Status Keaktifan</h3>
            <div class="form-group">
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <label class="radio-label"><input type="radio" name="status_keaktifan" value="Aktif" onclick="toggleKeaktifan(this)"> Aktif</label>
                    <label class="radio-label"><input type="radio" name="status_keaktifan" value="Tidak Aktif" onclick="toggleKeaktifan(this)"> Tidak Aktif</label>
                </div>
            </div>
            <div id="area_keaktifan_pilihan" class="hidden" style="margin-top:15px; background:#f0f6ff; border:1px solid #bfdbfe; padding:15px; border-radius:8px;">
                <label style="font-weight:600; font-size:0.9rem;">Status Detail</label>
                <div style="display:flex; gap:20px; margin-top:10px; flex-wrap:wrap;">
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Cuti" onclick="toggleKeaktifanLainnya(this)"> Cuti</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Izin Belajar" onclick="toggleKeaktifanLainnya(this)"> Izin Belajar</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Tugas Belajar" onclick="toggleKeaktifanLainnya(this)"> Tugas Belajar</label>
                    <label class="radio-label"><input type="radio" name="ket_tidak_aktif" value="Lainnya" onclick="toggleKeaktifanLainnya(this)"> Lainnya</label>
                </div>
                <div id="area_tidak_aktif_lainnya" class="hidden" style="margin-top:12px;">
                    <input type="text" name="ket_tidak_aktif_lainnya" placeholder="Deskripsi Lainnya...">
                </div>
            </div>
        </div>

        <div class="card" style="display:flex; justify-content:flex-end; gap:12px; align-items:center;">
            <a href="daftar_dosen.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Batal</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 36px;"><i class="fas fa-save"></i> Simpan Data Dosen</button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Jenis Dosen Struktural → tampilkan Jabatan Struktural
    document.querySelectorAll('input[name="jenis_dosen"]').forEach(r => {
        r.addEventListener('change', function() {
            const jabArea = document.getElementById('area_jabatan_struktural');
            const groupTmk = document.getElementById('group_tmk');
            const groupTmtk = document.getElementById('group_tmtk');
            
            if (this.value === 'Struktural') {
                jabArea.classList.remove('hidden');
                groupTmk.classList.remove('hidden');
                groupTmtk.classList.add('hidden');
            } else {
                jabArea.classList.add('hidden');
                groupTmk.classList.add('hidden');
                groupTmtk.classList.remove('hidden');
            }
        });
    });

    // TMTK → tampilkan area pemberhentian
    document.getElementById('tmtk_input').addEventListener('change', function() {
        const area = document.getElementById('area_tmtk');
        if (this.value) area.classList.remove('hidden');
        else area.classList.add('hidden');
    });
});

function addReward() {
    const html = `<div class="dynamic-item" style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:12px;">
        <div class="form-group"><input type="text" name="reward_deskripsi[]" placeholder="Deskripsi penghargaan..."></div>
        <div class="multi-row" style="grid-template-columns: 1fr 1fr auto; align-items:end;">
            <div class="form-group"><label style="font-size:0.8rem;">Tanggal</label><input type="date" name="reward_tanggal[]"></div>
            <div class="form-group"><label style="font-size:0.8rem;">Dokumen</label><input type="file" name="reward_file[]"></div>
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
            <div class="form-group"><label style="font-size:0.8rem;">Dokumen</label><input type="file" name="punishment_file[]"></div>
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
        <div class="multi-row" style="margin-top:12px;">
            <div class="form-group"><label>TMT <span style="color:var(--text-muted); font-weight:400;">(Tanggal Mulai Bekerja)</span></label><input type="date" name="tmt_status[]"></div>
            <div class="form-group"><label>Upload Dokumen</label><input type="file" name="dok_status[]" accept=".pdf,.jpg,.png"></div>
        </div>
    </div>`;
    document.getElementById('status-wrapper').insertAdjacentHTML('beforeend', html);
}

function toggleKeaktifan(el) {
    const area = document.getElementById('area_keaktifan_pilihan');
    if(el.value === 'Tidak Aktif' || el.value === 'Aktif') {
        area.classList.remove('hidden');
    } else {
        area.classList.add('hidden');
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

function addJabfung() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="form-group">
            <select name="jabfung_akademik[]">
                <option value="">- Pilih Jabatan -</option>
                <option value="Asisten Ahli">Asisten Ahli</option>
                <option value="Lektor">Lektor</option>
                <option value="Lektor Kepala">Lektor Kepala</option>
                <option value="Guru Besar">Guru Besar</option>
            </select>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label style="font-size:0.8rem">TMT <span style="color:var(--text-muted);">(Terhitung Mulai Tanggal SK)</span></label>
            <input type="date" name="tmt_jabfung[]">
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label style="font-size:0.8rem">Upload SK</label>
            <input type="file" name="dok_jabfung[]" accept=".pdf,.jpg,.png">
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
        </div>
    </div>`;
    document.getElementById('lldikti-wrapper').insertAdjacentHTML('beforeend', html);
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

function addPendidikan() {
    const html = `<div class="dynamic-item">
        <button type="button" onclick="this.closest('.dynamic-item').remove()" class="btn-icon" style="color:var(--danger); position:absolute; right:15px; top:15px;"><i class="fas fa-trash"></i></button>
        <div class="multi-row">
            <div class="form-group">
                <label>Jenjang / Tingkat</label>
                <select name="pend_jenjang[]" required>
                    <option value="">- Pilih -</option>
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
            <div class="form-group"><label>Upload Ijazah/Transkrip</label><input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png"></div>
        </div>
    </div>`;
    document.getElementById('pendidikan-wrapper').insertAdjacentHTML('beforeend', html);
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const jabArea = document.getElementById('area_jabatan_struktural');
    const grpTmk = document.getElementById('group_tmk');
    const grpTmtk = document.getElementById('group_tmtk');
    const tmkInput = document.getElementsByName('tmk')[0];
    const tmtkInput = document.getElementById('tmtk_input');
    
    // Default hiding
    jabArea.classList.add('hidden');
    grpTmk?.classList.add('hidden');
    grpTmtk?.classList.add('hidden');

    document.querySelectorAll('input[name="jenis_dosen"]').forEach(r => {
        r.addEventListener('change', function() {
            const currentVal = this.value;
            // Clear inputs when switching
            if (tmkInput) tmkInput.value = '';
            if (tmtkInput) tmtkInput.value = '';
            
            if (currentVal === 'Struktural') {
                jabArea.classList.remove('hidden');
                grpTmk?.classList.remove('hidden');
                grpTmtk?.classList.add('hidden');
            } else if (currentVal === 'Non Struktural') {
                jabArea.classList.add('hidden');
                grpTmk?.classList.add('hidden');
                grpTmtk?.classList.add('hidden');
            }
        });
    });
});
</script>

</body>
</html>


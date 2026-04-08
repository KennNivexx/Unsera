<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan'])) {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $ttl = $_POST['ttl'];
    $jenis = $_POST['jenis_pegawai'];
    $status_pribadi = $_POST['status_pribadi'] ?? '';
    $jabatan = $_POST['posisi_jabatan'];
    $tmk = $_POST['tmt_mulai_kerja'] ?: null;
    $tmtk = $_POST['tmt_tidak_kerja'] ?: null;
    $unit = $_POST['unit_kerja'];
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

    $ket_tmtk = $_POST['ket_tmtk'] ?? '';

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

    // Insert into pegawai
    $sql = "INSERT INTO pegawai (nama_lengkap, alamat, ttl, jenis_pegawai, status_pribadi, posisi_jabatan, tmt_mulai_kerja, tmt_tidak_kerja, unit_kerja, riwayat_pendidikan, ket_tidak_kerja, dok_tmtk, foto_profil) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssss", $nama, $alamat, $ttl, $jenis, $status_pribadi, $jabatan, $tmk, $tmtk, $unit, $pendidikan, $ket_tmtk, $dok_tmtk, $foto_profil);
    
    if ($stmt->execute()) {
        $pegawai_id = $conn->insert_id;

        // Dynamic Yayasan
        $yayasan_list = [];
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
        echo "<script>alert('Gagal menyimpan data.');</script>";
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

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Tambah Pegawai Baru</h1>
            <p>Lengkapi profil staf kependidikan untuk ditambahkan ke sistem Universitas Serang Raya.</p>
        </div>
        <div>
            <a href="data_pegawai.php" class="btn btn-outline">
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
            </div>
        </div>

        <!-- Informasi Pribadi -->
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Informasi Pribadi</h3>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" placeholder="Contoh: Ahmad Subarjo, S.Kom." required>
            </div>
            
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="2" placeholder="Alamat lengkap sesuai KTP" required></textarea>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Tempat & Tanggal Lahir (TTL)</label>
                    <input type="text" name="ttl" placeholder="Contoh: Serdang Bedagai, 1 Januari 1990" required>
                </div>
                <div class="form-group">
                    <label>Status Pernikahan</label>
                    <select name="status_pribadi" required>
                        <option value="">Pilih Status</option>
                        <option value="Menikah">Menikah</option>
                        <option value="Belum Menikah">Belum Menikah</option>
                        <option value="Bercerai">Bercerai</option>
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
                        <label class="radio-label"><input type="radio" name="jenis_pegawai" value="tetap" required> Tetap</label>
                        <label class="radio-label"><input type="radio" name="jenis_pegawai" value="tdk tetap"> Tidak Tetap</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Posisi Jabatan / Unit Kerja</label>
                    <div style="display: flex; gap: 15px;">
                        <input type="text" name="posisi_jabatan" placeholder="Jabatan (Staf IT)" required style="flex: 1;">
                        <input type="text" name="unit_kerja" placeholder="Unit (Biro Umum)" required style="flex: 1;">
                    </div>
                </div>
            </div>

            <div class="multi-row">
                <div class="form-group">
                    <label>Terhitung Mulai Kerja (TMK)</label>
                    <input type="date" name="tmt_mulai_kerja" required>
                </div>
                <div class="form-group">
                    <label>TMT Tidak Kerja (Opsional)</label>
                    <input type="date" name="tmt_tidak_kerja" id="tmtk_input">
                </div>
            </div>

            <div id="area_tmtk" class="hidden dynamic-item">
                <div class="multi-row">
                    <div class="form-group">
                        <label>Alasan Berhenti</label>
                        <select name="ket_tmtk">
                            <option value="">Pilih Alasan</option>
                            <option value="Resign">Resign</option>
                            <option value="Pensiun">Pensiun</option>
                            <option value="Putus Kontrak">Putus Kontrak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dokumen SK Pemberhentian</label>
                        <input type="file" name="dok_tmtk" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                </div>
            </div>

            <!-- Pendidikan -->
            <div class="form-section" style="margin-top:20px;">
                <h3><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</h3>
                <div id="pendidikan-wrapper">
                    <div class="dynamic-item">
                        <div class="multi-row">
                            <div class="form-group">
                                <label>Jenjang / Tingkat</label>
                                <select name="pend_jenjang[]" required>
                                    <option value="">- Pilih -</option>
                                    <?php foreach(['SD', 'SMP', 'SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $p): ?>
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
                                <label>Upload Ijazah <span style="color:var(--text-muted); font-weight:400;">(PDF/JPG)</span></label>
                                <input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addPendidikan()" class="btn btn-outline" style="width:100%; margin-bottom: 20px;"><i class="fas fa-plus"></i> Tambah Riwayat Pendidikan</button>
            </div>

            <!-- Yayasan -->
            <div class="form-section" style="margin-top:20px;">
                <h3><i class="fas fa-building"></i> Golongan Yayasan</h3>
                <div id="yayasan-wrapper">
                    <!-- Dynamic -->
                </div>
                <button type="button" onclick="addYayasan()" class="btn btn-outline" style="width:100%;"><i class="fas fa-plus"></i> Tambah Golongan Yayasan</button>
            </div>
        </div>

        <!-- Reward & Punishment -->
        <div class="form-section">
            <div class="multi-row">
                <div>
                    <h3><i class="fas fa-medal" style="color: #ed8936;"></i> Penghargaan</h3>
                    <div id="reward-wrapper"></div>
                    <button type="button" onclick="addReward()" class="btn btn-outline" style="width: 100%;"><i class="fas fa-plus"></i> Tambah Penghargaan</button>
                </div>
                <div>
                    <h3><i class="fas fa-gavel" style="color: #e53e3e;"></i> Sanksi / Catatan</h3>
                    <div id="punishment-wrapper"></div>
                    <button type="button" onclick="addPunish()" class="btn btn-outline" style="width: 100%;"><i class="fas fa-plus"></i> Tambah Sanksi</button>
                </div>
            </div>
        </div>

        <div style="margin-top: 50px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 30px;">
            <a href="data_pegawai.php" class="btn" style="color: var(--text-muted); margin-right: 15px;">Batal</a>
            <button type="submit" name="simpan" class="btn btn-primary" style="padding: 12px 40px;"><i class="fas fa-save"></i> Simpan Data Pegawai</button>
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
            <div class="form-group"><label>Upload Ijazah</label><input type="file" name="dok_pendidikan[]" accept=".pdf,.jpg,.png"></div>
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
</script>

</body>
</html>
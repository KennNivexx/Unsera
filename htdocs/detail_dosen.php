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

// Fetch basic data using Prepared Statement
$stmt = $conn->prepare("SELECT * FROM dosen WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$d) {
    echo "<script>alert('Data tidak ditemukan!');location='daftar_dosen.php';</script>";
    exit;
}

// Get reward using Prepared Statement
$stmt = $conn->prepare("SELECT * FROM reward WHERE dosen_id = ? ORDER BY tanggal DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$reward = $stmt->get_result();
$stmt->close();

// Get punishment using Prepared Statement
$stmt = $conn->prepare("SELECT * FROM punishment WHERE dosen_id = ? ORDER BY tanggal DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$punishment = $stmt->get_result();
$stmt->close();

$jabfungs = $conn->query("SELECT * FROM jabfung_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$lldiktis = $conn->query("SELECT * FROM lldikti_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$yayasans = $conn->query("SELECT * FROM yayasan_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_dosen WHERE dosen_id = $id ORDER BY tahun_lulus DESC")->fetch_all(MYSQLI_ASSOC);
$status_riwayats = $conn->query("SELECT * FROM status_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Dosen | <?= htmlspecialchars($d['nama_lengkap']) ?></title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            display: flex;
            align-items: center;
            gap: 40px;
            padding: 40px;
            background: #ffffff;
            color: var(--text-main);
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
            border-left: 8px solid var(--primary);
            box-shadow: var(--shadow-sm);
        }
        .profile-avatar {
            width: 140px;
            height: 140px;
            background: #f8fafc;
            border: 4px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: 800;
            overflow: hidden;
            flex-shrink: 0;
            z-index: 1;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
        }
        .info-section-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f8fafc;
            transition: all 0.2s;
        }
        .info-row:hover { background: #fcfdfe; }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            width: 180px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1rem;
        }
        .badge-status {
            padding: 5px 15px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 20px;
            border: 1.5px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .doc-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.05); border-color: var(--primary-soft); }
        .doc-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

<?php 
$page_title = "Detail Dosen";
include 'components/sidebar.php'; 
include 'components/navbar.php';
?>

<div class="main-content">
    <div class="profile-card">
        <div class="profile-avatar" onclick="openProfileModal()" title="Klik untuk lihat detail profil" style="cursor:pointer; position:relative;">
            <?php if(!empty($d['foto_profil']) && file_exists($d['foto_profil'])): ?>
                <img src="<?= htmlspecialchars($d['foto_profil']) ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($d['nama_lengkap'], 0, 1)) ?>
            <?php endif; ?>
            <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(30,58,138,0.7);color:white;font-size:0.55rem;text-align:center;padding:3px;font-weight:700;">DETAIL</div>
        </div>
        <div style="flex: 1;">
            <h1 class="academic-title" style="font-size: 2.2rem; margin-bottom: 5px; color: var(--text-main);"><?= htmlspecialchars($d['nama_lengkap']) ?></h1>
            <p style="font-size: 1.1rem; color: var(--text-muted); font-weight: 500;">
                <?= htmlspecialchars($d['unit_kerja'] ?? '-') ?> &bull; 
                <span style="background: var(--primary-soft); color: var(--primary); padding: 4px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 700;">
                    <?= htmlspecialchars($d['status_dosen'] ?? '-') ?>
                </span>
            </p>
        </div>
        <div style="z-index:1;">
            <a href="daftar_dosen.php" class="btn btn-outline" style="font-weight: 700;">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <div class="info-grid">
        <!-- Informasi Pribadi -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title"><i class="fas fa-id-card"></i> Identitas Personal</div>
            <div class="info-row"><div class="info-label">Nama Lengkap</div><div class="info-value"><?= htmlspecialchars($d['nama_lengkap'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">Gelar / Pendidikan</div><div class="info-value"><?= htmlspecialchars($d['riwayat_pendidikan'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">NIP</div><div class="info-value"><?= htmlspecialchars($d['nip'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">NIDN</div><div class="info-value"><?= htmlspecialchars($d['nidn'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">NUPTK</div><div class="info-value"><?= htmlspecialchars($d['nuptk'] ?? '-') ?></div></div>
            
            <div style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Tempat Lahir</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($d['ttl_tempat'] ?? '-') ?></div>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Tanggal Lahir</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= $d['ttl_tanggal'] ? date('d F Y', strtotime($d['ttl_tanggal'])) : '-' ?></div>
                </div>
            </div>

            <div class="info-row" style="margin-top: 15px;"><div class="info-label">Alamat</div><div class="info-value"><?= htmlspecialchars($d['alamat'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">Status Pernikahan</div><div class="info-value"><?= htmlspecialchars($d['status_pribadi'] ?? '-') ?></div></div>
            
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <?php if(!empty($d['dok_ktp'])): ?>
                    <a href="<?= $d['dok_ktp'] ?>" target="_blank" class="btn" style="flex: 1; background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.8rem; font-weight: 700; justify-content: center;">
                        <i class="fas fa-file-contract"></i> Lihat Dok. KTP
                    </a>
                <?php endif; ?>
                <?php if(!empty($d['dok_kk'])): ?>
                    <a href="<?= $d['dok_kk'] ?>" target="_blank" class="btn" style="flex: 1; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.8rem; font-weight: 700; justify-content: center;">
                        <i class="fas fa-users"></i> Lihat Dok. KK
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Kepegawaian -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title"><i class="fas fa-briefcase"></i> Status Kepegawaian</div>
            <div class="info-row">
                <div class="info-label">Status Dosen</div>
                <div class="info-value">
                    <?php 
                        $st = strtolower($d['status_dosen'] ?? '');
                        $bClass = 'badge-success';
                        if($st == 'tidak tetap') $bClass = 'badge-warning';
                        if($st == 'homebase') $bClass = 'badge-danger';
                    ?>
                    <span class="badge <?= $bClass ?>" style="font-size: 0.85rem; padding: 6px 16px;"><?= htmlspecialchars($d['status_dosen'] ?? '-') ?></span>
                </div>
            </div>
            <div class="info-row"><div class="info-label">Jabatan Dosen</div><div class="info-value"><?= htmlspecialchars($d['jenis_dosen'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">Jabatan Struktural</div><div class="info-value"><?= htmlspecialchars($d['jabatan_struktural'] ?? '-') ?></div></div>
            
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <div style="flex: 1; background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Homebase Prodi</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?></div>
                </div>
                <div style="flex: 1; background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Unit Kerja</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($d['unit_kerja'] ?? '-') ?></div>
                </div>
            </div>

            <div class="info-row" style="margin-top: 15px;"><div class="info-label">TMK / TMT</div><div class="info-value"><?= $d['tmk'] ? date('d F Y', strtotime($d['tmk'])) : '-' ?></div></div>
            
            <div class="info-row">
                <div class="info-label">Keaktifan</div>
                <div class="info-value">
                    <?php if(($d['status_keaktifan'] ?? 'Aktif') == 'Aktif'): ?>
                        <span style="color: #10b981; font-weight: 800;"><i class="fas fa-check-circle"></i> AKTIF</span>
                    <?php else: ?>
                        <span style="color: #ef4444; font-weight: 800;"><i class="fas fa-times-circle"></i> TIDAK AKTIF</span>
                        <div style="font-size: 0.85rem; color: #94a3b8; font-weight: 500; margin-top: 4px;">Alasan: <?= htmlspecialchars($d['keterangan_keaktifan'] ?? '-') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h4 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Riwayat Status Kepegawaian</h4>
                <?php if(count($status_riwayats) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach($status_riwayats as $idx => $sr): ?>
                            <div class="riwayat-card" onclick="showRiwayatModal('<?= htmlspecialchars(addslashes($sr['status_dosen'] ?? '-')) ?>', '<?= $sr['tmt'] ? date('d M Y', strtotime($sr['tmt'])) : '-' ?>', '<?= !empty($sr['tgl_berhenti']) ? date('d M Y', strtotime($sr['tgl_berhenti'])) : '-' ?>', '<?= !empty($sr['dokumen']) ? addslashes($sr['dokumen']) : '' ?>')" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: <?= $idx === 0 ? '#eff6ff' : '#f8fafc' ?>; border-radius: 12px; border-left: 4px solid <?= $idx === 0 ? 'var(--primary)' : '#cbd5e1' ?>; cursor: pointer; transition: all 0.2s;">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($sr['status_dosen'] ?? '-') ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Terhitung Mulai Bekerja: <?= $sr['tmt'] ? date('d/m/Y', strtotime($sr['tmt'])) : '-' ?><?= !empty($sr['tgl_berhenti']) ? ' &nbsp;|&nbsp; Berhenti: ' . date('d/m/Y', strtotime($sr['tgl_berhenti'])) : '' ?></div>
                                </div>
                                <div class="btn-icon" style="color: var(--primary); background: white; width: 32px; height: 32px; border-radius: 8px; box-shadow: var(--shadow-sm);"><i class="fas fa-eye"></i></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; color: #94a3b8; font-size: 0.85rem;">Belum ada riwayat status tercatat.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat Pangkat & Jabatan -->
        <div class="card" style="grid-column: 1 / -1; padding: 35px;">
            <div class="info-section-title"><i class="fas fa-layer-group"></i> Riwayat Kepangkatan & Jabatan Akademik</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <!-- Jabfung History -->
                <div>
                    <h4 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 20px;">Jabatan Fungsional</h4>
                    <?php if(count($jabfungs) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach($jabfungs as $jf): ?>
                            <div class="doc-card" style="border-left: 4px solid var(--primary);">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($jf['jabatan']) ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">TMT: <?= $jf['tmt'] ? date('d/m/Y', strtotime($jf['tmt'])) : '-' ?></div>
                                </div>
                                <?php if($jf['dokumen']): ?>
                                    <a href="<?= $jf['dokumen'] ?>" target="_blank" class="btn-icon" style="color: var(--primary); background: #f0f9ff;"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 20px; border-radius: 15px; border: 1px dashed #cbd5e1; text-align: center; color: #94a3b8; font-size: 0.8rem;">Tidak ada data Jabfung.</div>
                    <?php endif; ?>
                </div>

                <!-- LLDIKTI History -->
                <div>
                    <h4 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 20px;">Golongan LLDIKTI</h4>
                    <?php if(count($lldiktis) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach($lldiktis as $ld): ?>
                            <div class="doc-card" style="border-left: 4px solid #8b5cf6;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-main);">Golongan: <?= htmlspecialchars($ld['golongan']) ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">TMT: <?= $ld['tmt'] ? date('d/m/Y', strtotime($ld['tmt'])) : '-' ?></div>
                                </div>
                                <?php if($ld['dokumen']): ?>
                                    <a href="<?= $ld['dokumen'] ?>" target="_blank" class="btn-icon" style="color: #8b5cf6; background: #f5f3ff;"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 20px; border-radius: 15px; border: 1px dashed #cbd5e1; text-align: center; color: #94a3b8; font-size: 0.8rem;">Tidak ada data LLDIKTI.</div>
                    <?php endif; ?>
                </div>

                <!-- Yayasan History -->
                <div>
                    <h4 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 20px;">Golongan Yayasan</h4>
                    <?php if(count($yayasans) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach($yayasans as $yy): ?>
                            <div class="doc-card" style="border-left: 4px solid #10b981;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-main);">Golongan: <?= htmlspecialchars($yy['golongan']) ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">TMT: <?= $yy['tmt'] ? date('d/m/Y', strtotime($yy['tmt'])) : '-' ?></div>
                                </div>
                                <?php if($yy['dokumen']): ?>
                                    <a href="<?= $yy['dokumen'] ?>" target="_blank" class="btn-icon" style="color: #10b981; background: #ecfdf5;"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 20px; border-radius: 15px; border: 1px dashed #cbd5e1; text-align: center; color: #94a3b8; font-size: 0.8rem;">Tidak ada data Yayasan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Riwayat Pendidikan -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title"><i class="fas fa-graduation-cap"></i> Kualifikasi Akademik</div>
            <?php if(count($pendidikans) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach($pendidikans as $pend): ?>
                    <div class="doc-card" style="border-color: #cbd5e1;">
                        <div class="doc-icon"><i class="fas fa-university"></i></div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; color: var(--text-main); font-size: 1.1rem;"><?= htmlspecialchars($pend['jenjang']) ?></div>
                            <div style="font-weight: 600; color: #64748b;"><?= htmlspecialchars($pend['institusi']) ?></div>
                            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; margin-top: 5px;">Tahun Lulus: <?= htmlspecialchars($pend['tahun_lulus']) ?></div>
                        </div>
                        <?php if($pend['dokumen']): ?>
                            <a href="<?= $pend['dokumen'] ?>" target="_blank" class="btn" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 12px; font-size: 0.75rem;"><i class="fas fa-file-alt"></i> IJAZAH</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: #f8fafc; padding: 25px; border-radius: 20px; border: 1.5px solid #f1f5f9;">
                    <div style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Pendidikan Terakhir</div>
                    <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-main);"><?= htmlspecialchars($d['riwayat_pendidikan'] ?? '-') ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sertifikasi Dosen -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title"><i class="fas fa-certificate" style="color: #fbbf24;"></i> Sertifikasi Dosen</div>
            <?php if(!empty($d['no_serdos'])): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 25px; border-radius: 20px; display: flex; align-items: center; gap: 20px;">
                    <div style="width: 60px; height: 60px; background: white; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fbbf24; border: 1px solid #fde68a;">
                        <i class="fas fa-stamp"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 800; color: #92400e; text-transform: uppercase;">Nomor Sertifikat Pendidik</div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #92400e; letter-spacing: 1px;"><?= htmlspecialchars($d['no_serdos']) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding: 30px; border-radius: 20px; border: 1.5px dashed #cbd5e1; text-align: center; color: #94a3b8; font-weight: 600;">Data Sertifikasi Dosen belum tersedia.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dokumen Pendukung Lainnya -->
    <div class="card" style="margin-top: 24px;">
        <h3>Dokumen Pendukung Lainnya</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">

            <!-- Foto Profil (downloadable) -->
            <div class="doc-card">
                <span class="doc-label">Foto Profil</span>
                <?php if(!empty($d['foto_profil']) && file_exists($d['foto_profil'])): ?>
                    <a href="<?= htmlspecialchars($d['foto_profil']) ?>" download class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-download"></i> Download Foto
                    </a>
                <?php else: ?>
                    <span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span>
                <?php endif; ?>
            </div>

            <!-- Dokumen Status Dosen -->
            <div class="doc-card">
                <span class="doc-label">Dokumen Status Dosen</span>
                <?php if(!empty($d['dok_status_dosen'])): ?>
                    <a href="<?= $d['dok_status_dosen'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>

            <!-- Dokumen Terhitung Mulai Bertugas -->
            <div class="doc-card">
                <span class="doc-label">Dokumen Terhitung Mulai Bertugas</span>
                <?php if(!empty($d['dok_tmb'])): ?>
                    <a href="<?= $d['dok_tmb'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>

            <!-- Dokumen Berhenti Bertugas (renamed) -->
            <div class="doc-card">
                <span class="doc-label">Dokumen Berhenti Bertugas</span>
                <?php if(!empty($d['dok_berhenti_bertugas'])): ?>
                    <a href="<?= $d['dok_berhenti_bertugas'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php elseif(!empty($d['dok_tidak_kerja'])): ?>
                    <a href="<?= $d['dok_tidak_kerja'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>

            <!-- Dokumen Penugasan Struktural -->
            <div class="doc-card">
                <span class="doc-label">Dokumen Penugasan Struktural</span>
                <?php if(!empty($d['dok_penugasan_struktural'])): ?>
                    <a href="<?= $d['dok_penugasan_struktural'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>

            <!-- Dokumen Serdos -->
            <div class="doc-card">
                <span class="doc-label">Dokumen Serdos</span>
                <?php if(!empty($d['dok_serdos'])): ?>
                    <a href="<?= $d['dok_serdos'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
        <div class="card">
            <h3 style="color: var(--success);">Rewards</h3>
            <?php if($reward->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php while($r = $reward->fetch_assoc()): ?>
                    <div style="padding: 16px; background: #f0fdf4; border-left: 4px solid var(--success); border-radius: var(--radius-md);">
                        <div style="font-size: 0.75rem; color: var(--success); font-weight: 700; margin-bottom: 4px;"><?= $r['tanggal'] ? date('d/m/Y', strtotime($r['tanggal'])) : '-' ?></div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($r['deskripsi']) ?></div>
                        <?php if($r['file_upload']): ?>
                            <a href="<?= $r['file_upload'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 4px 10px; margin-top: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-file-alt"></i> Lihat Dokumen
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?><p style="color: var(--text-muted);">Belum ada reward dicatat.</p><?php endif; ?>
        </div>
        <div class="card">
            <h3 style="color: var(--danger);">Punishments</h3>
            <?php if($punishment->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php while($p = $punishment->fetch_assoc()): ?>
                    <div style="padding: 16px; background: #fef2f2; border-left: 4px solid var(--danger); border-radius: var(--radius-md);">
                        <div style="font-size: 0.75rem; color: var(--danger); font-weight: 700; margin-bottom: 4px;"><?= $p['tanggal'] ? date('d/m/Y', strtotime($p['tanggal'])) : '-' ?></div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($p['deskripsi']) ?></div>
                        <?php if($p['file_upload']): ?>
                            <a href="<?= $p['file_upload'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 4px 10px; margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; color: var(--danger); border-color: var(--danger);">
                                <i class="fas fa-file-alt"></i> Lihat Dokumen
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?><p style="color: var(--text-muted);">Belum ada punishment dicatat.</p><?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Profile Modal (opened by clicking avatar) -->
<div id="profileModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:9998; align-items:center; justify-content:center; padding:16px;">
    <div style="background:white; border-radius:20px; width:100%; max-width:600px; position:relative; max-height:90vh; overflow-y:auto;">
        <button onclick="document.getElementById('profileModal').style.display='none'" style="position:absolute; right:16px; top:16px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#94a3b8; z-index:1;"><i class="fas fa-times"></i></button>
        <!-- Header -->
        <div style="background:linear-gradient(135deg,#1e3a8a,#3b82f6); padding:30px; border-radius:20px 20px 0 0; display:flex; align-items:center; gap:20px;">
            <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,0.5);flex-shrink:0;background:#f8fafc;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#1e3a8a;">
                <?php if(!empty($d['foto_profil']) && file_exists($d['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($d['foto_profil']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <?= strtoupper(substr($d['nama_lengkap'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div style="color:white;">
                <div style="font-size:1.3rem;font-weight:800;font-family:'Outfit';"><?= htmlspecialchars($d['nama_lengkap']) ?></div>
                <div style="opacity:0.85;font-size:0.9rem;margin-top:4px;"><?= htmlspecialchars($d['unit_kerja'] ?? '-') ?></div>
                <?php
                    $stK = strtolower($d['status_dosen'] ?? '');
                    $bKlass = $stK=='tidak tetap' ? '#f59e0b' : ($stK=='homebase' ? '#ef4444' : '#10b981');
                ?>
                <span style="background:<?= $bKlass ?>;color:white;padding:3px 12px;border-radius:20px;font-size:0.75rem;font-weight:800;display:inline-block;margin-top:6px;"><?= htmlspecialchars($d['status_dosen'] ?? '-') ?></span>
            </div>
        </div>
        <!-- Body -->
        <div style="padding:24px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="background:#f8fafc;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;margin-bottom:4px;">NIP</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['nip'] ?? '-') ?></div>
                </div>
                <div style="background:#f8fafc;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;margin-bottom:4px;">NIDN</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['nidn'] ?? '-') ?></div>
                </div>
                <div style="background:#f8fafc;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;margin-bottom:4px;">Tempat, Tgl Lahir</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['ttl_tempat'] ?? '-') ?>, <?= $d['ttl_tanggal'] ? date('d/m/Y', strtotime($d['ttl_tanggal'])) : '-' ?></div>
                </div>
                <div style="background:#f8fafc;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;margin-bottom:4px;">Status Pernikahan</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['status_pribadi'] ?? '-') ?></div>
                </div>
                <div style="background:#f0f9ff;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#0369a1;text-transform:uppercase;margin-bottom:4px;">Homebase Prodi</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?></div>
                </div>
                <div style="background:#f0f9ff;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#0369a1;text-transform:uppercase;margin-bottom:4px;">Jabatan Dosen</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['jenis_dosen'] ?? '-') ?></div>
                </div>
                <div style="background:#f0fdf4;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:#15803d;text-transform:uppercase;margin-bottom:4px;">Jabatan Akademik</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($d['jabfung_akademik'] ?? '-') ?></div>
                </div>
                <div style="background:<?= ($d['status_keaktifan'] ?? 'Aktif') == 'Aktif' ? '#f0fdf4' : '#fff1f2' ?>;padding:14px;border-radius:12px;">
                    <div style="font-size:0.7rem;font-weight:800;color:<?= ($d['status_keaktifan'] ?? 'Aktif') == 'Aktif' ? '#15803d' : '#e11d48' ?>;text-transform:uppercase;margin-bottom:4px;">Keaktifan</div>
                    <div style="font-weight:800;color:<?= ($d['status_keaktifan'] ?? 'Aktif') == 'Aktif' ? '#10b981' : '#ef4444' ?>;"><?= ($d['status_keaktifan'] ?? 'Aktif') == 'Aktif' ? '✓ AKTIF' : '✕ TIDAK AKTIF' ?></div>
                    <?php if(($d['status_keaktifan'] ?? 'Aktif') != 'Aktif' && !empty($d['keterangan_keaktifan'])): ?>
                    <div style="font-size:0.8rem;color:#94a3b8;margin-top:3px;">Alasan: <?= htmlspecialchars($d['keterangan_keaktifan']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end;">
                <a href="form_edit_dosen.php?id=<?= $d['id'] ?>" class="btn btn-primary" style="font-weight:700;"><i class="fas fa-pen"></i> Edit Data</a>
                <button onclick="document.getElementById('profileModal').style.display='none'" class="btn btn-outline" style="font-weight:700;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div id="riwayatModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:30px; border-radius:15px; width:90%; max-width:500px; position:relative;">
        <button onclick="document.getElementById('riwayatModal').style.display='none'" style="position:absolute; right:20px; top:20px; background:none; border:none; font-size:1.5rem; cursor:pointer; color:#94a3b8;"><i class="fas fa-times"></i></button>
        <h3 style="margin-top:0; color:var(--primary); font-weight:800; border-bottom:1px solid #f1f5f9; padding-bottom:10px;"><i class="fas fa-history"></i> Detail Riwayat Status</h3>
        
        <div style="margin-top:20px;">
            <div style="margin-bottom:15px;">
                <label style="font-size:0.75rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">Status Dosen</label>
                <div id="riwayat_statusVal" style="font-size:1.1rem; font-weight:700; color:var(--text-main);"></div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="font-size:0.75rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">Terhitung Mulai Bekerja</label>
                    <div id="riwayat_tmtVal" style="font-weight:600; color:var(--text-main);"></div>
                </div>
                <div>
                    <label style="font-size:0.75rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">Tanggal Berhenti</label>
                    <div id="riwayat_tglBerhentiVal" style="font-weight:600; color:var(--text-main);"></div>
                </div>
            </div>
            
            <div style="margin-top:25px; padding-top:15px; border-top:1px dashed #cbd5e1;" id="riwayat_dokArea">
                <a id="riwayat_dokLink" href="#" target="_blank" class="btn btn-primary" style="width:100%; justify-content:center;">
                    <i class="fas fa-file-download"></i> Unduh / Lihat Dokumen
                </a>
            </div>
            <div id="riwayat_noDokArea" style="display:none; margin-top:25px; padding-top:15px; border-top:1px dashed #cbd5e1; text-align:center; color:#94a3b8; font-weight:600; font-size:0.9rem;">
                Tidak ada dokumen dilampirkan.
            </div>
        </div>
    </div>
</div>

<script>
function openProfileModal() {
    document.getElementById('profileModal').style.display = 'flex';
}
document.getElementById('profileModal').addEventListener('click', function(e) {
    if(e.target === this) this.style.display = 'none';
});

function showRiwayatModal(status, tmt, tglb, dok) {
    document.getElementById('riwayat_statusVal').innerText = status;
    document.getElementById('riwayat_tmtVal').innerText = tmt;
    document.getElementById('riwayat_tglBerhentiVal').innerText = tglb;
    
    if(dok && dok.trim() !== '') {
        document.getElementById('riwayat_dokArea').style.display = 'block';
        document.getElementById('riwayat_noDokArea').style.display = 'none';
        document.getElementById('riwayat_dokLink').href = dok;
    } else {
        document.getElementById('riwayat_dokArea').style.display = 'none';
        document.getElementById('riwayat_noDokArea').style.display = 'block';
    }
    
    document.getElementById('riwayatModal').style.display = 'flex';
}
</script>

<?php include 'components/file_viewer.php'; ?>

</body>
</html>

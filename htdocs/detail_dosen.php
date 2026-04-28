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

// Fetch basic data
$stmt = $conn->prepare("SELECT * FROM dosen WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$d) {
    echo "<script>alert('Data tidak ditemukan!');location='daftar_dosen.php';</script>";
    exit;
}

// Fetch related data
$reward = $conn->query("SELECT * FROM reward WHERE dosen_id = $id ORDER BY tanggal DESC");
$punishment = $conn->query("SELECT * FROM punishment WHERE dosen_id = $id ORDER BY tanggal DESC");
$jabfungs = $conn->query("SELECT * FROM jabfung_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$lldiktis = $conn->query("SELECT * FROM lldikti_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$yayasans = $conn->query("SELECT * FROM yayasan_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_dosen WHERE dosen_id = $id ORDER BY tahun_lulus DESC")->fetch_all(MYSQLI_ASSOC);
$status_riwayats = $conn->query("SELECT * FROM status_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
$serdoses = $conn->query("SELECT * FROM sertifikasi_dosen WHERE dosen_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);

$breadcrumbs = [
    ['label' => 'Daftar Dosen', 'url' => 'daftar_dosen.php'],
    ['label' => 'Profil Dosen', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Dosen | <?= htmlspecialchars($d['nama_lengkap']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        :root {
            --glass: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        .profile-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 30px;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .profile-header::after {
            content: ''; position: absolute; top: -50%; right: -10%; width: 400px; height: 400px;
            background: rgba(255,255,255,0.05); border-radius: 50%; z-index: 0;
        }
        .avatar-container {
            width: 160px; height: 160px; border-radius: 40px; overflow: hidden;
            border: 6px solid rgba(255,255,255,0.2); background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem; font-weight: 800; color: white; z-index: 1; position: relative;
        }
        .stat-pill {
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
            padding: 10px 20px; border-radius: 15px; backdrop-filter: blur(10px);
        }
        .info-card {
            background: white; border-radius: 24px; padding: 30px; height: 100%;
            border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .detail-row {
            display: flex; justify-content: space-between; padding: 12px 0;
            border-bottom: 1px solid #f8fafc;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #64748b; font-weight: 500; font-size: 0.9rem; }
        .detail-value { color: #1e293b; font-weight: 600; text-align: right; }
        .vault-item {
            background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px;
            padding: 15px; display: flex; align-items: center; gap: 15px;
            transition: all 0.3s; text-decoration: none; color: inherit;
        }
        .vault-item:hover {
            transform: translateY(-5px); border-color: var(--primary);
            background: white; box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .vault-icon {
            width: 45px; height: 45px; border-radius: 12px; background: white;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            color: var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .timeline-item { position: relative; padding-left: 30px; padding-bottom: 25px; border-left: 2px solid #e2e8f0; }
        .timeline-item::before {
            content: ''; position: absolute; left: -7px; top: 0; width: 12px; height: 12px;
            border-radius: 50%; background: var(--primary); border: 2px solid white; box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
        }
        .timeline-item.success::before { background: #10b981; box-shadow: 0 0 0 4px rgba(16,185,129,0.1); }
        .timeline-item.danger::before { background: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.1); }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center g-4">
                <div class="col-auto">
                    <div class="avatar-container shadow-lg">
                        <?php if(!empty($d['foto_profil']) && file_exists($d['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($d['foto_profil']) ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($d['nama_lengkap'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                        <span class="badge bg-primary rounded-pill px-3 py-2">Dosen Tetap</span>
                        <span class="badge bg-info rounded-pill px-3 py-2 text-dark fw-bold"><?= htmlspecialchars($d['nidn'] ?? 'N/A') ?></span>
                    </div>
                    <h1 class="display-5 fw-bold mb-1 text-white" style="font-family: 'Outfit', sans-serif;"><?= htmlspecialchars($d['nama_lengkap']) ?></h1>
                    <p class="fs-5 text-white mb-3" style="opacity: 0.9;"><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?> &bull; <?= htmlspecialchars($d['unit_kerja'] ?? '-') ?></p>
                    
                    <div class="d-flex flex-wrap gap-4 mt-4">
                        <div class="stat-pill">
                            <div class="small text-white text-uppercase fw-bold ls-1" style="opacity: 0.7;">Jabfung</div>
                            <div class="fw-bold text-white"><?= htmlspecialchars($d['jenis_dosen'] ?? 'Asisten Ahli') ?></div>
                        </div>
                        <div class="stat-pill">
                            <div class="small text-white text-uppercase fw-bold ls-1" style="opacity: 0.7;">Pendidikan</div>
                            <div class="fw-bold text-white"><?= htmlspecialchars($d['riwayat_pendidikan'] ?? 'S2') ?></div>
                        </div>
                        <div class="stat-pill">
                            <div class="small text-white text-uppercase fw-bold ls-1" style="opacity: 0.7;">Status</div>
                            <div class="fw-bold text-white"><i class="fas fa-circle text-success me-1 small"></i> Aktif</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto align-self-start">
                    <a href="form_edit_dosen.php?id=<?= $id ?>" class="btn btn-light rounded-pill px-4 shadow">
                        <i class="fas fa-edit me-2"></i>Edit Profil
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Personal & Kepegawaian -->
            <div class="col-lg-8">
                <div class="row g-4">
                    <!-- Personal Info -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                                <i class="fas fa-user-circle text-primary"></i> Data Pribadi
                            </h5>
                            <div class="detail-row"><span class="detail-label">NIP</span><span class="detail-value"><?= htmlspecialchars($d['nip'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">NIDN</span><span class="detail-value"><?= htmlspecialchars($d['nidn'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">NUPTK</span><span class="detail-value"><?= htmlspecialchars($d['nuptk'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Tempat Lahir</span><span class="detail-value"><?= htmlspecialchars($d['ttl_tempat'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Tgl Lahir</span><span class="detail-value"><?= $d['ttl_tanggal'] ? date('d M Y', strtotime($d['ttl_tanggal'])) : '-' ?></span></div>
                            <div class="detail-row"><span class="detail-label">Status Nikah</span><span class="detail-value"><?= htmlspecialchars($d['status_pribadi'] ?? '-') ?></span></div>
                        </div>
                    </div>

                    <!-- Work Info -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                                <i class="fas fa-briefcase text-primary"></i> Kepegawaian
                            </h5>
                            <div class="detail-row"><span class="detail-label">Status Dosen</span><span class="detail-value"><?= htmlspecialchars($d['status_dosen'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Jabatan Struktural</span><span class="detail-value"><?= htmlspecialchars($d['jabatan_struktural'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Homebase Prodi</span><span class="detail-value"><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Unit Kerja</span><span class="detail-value"><?= htmlspecialchars($d['unit_kerja'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">TMK Bertugas</span><span class="detail-value"><?= $d['tmk'] ? date('d M Y', strtotime($d['tmk'])) : '-' ?></span></div>
                        </div>
                    </div>

                    <!-- Qualifications -->
                    <div class="col-12">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                                <i class="fas fa-graduation-cap text-primary"></i> Riwayat Pendidikan
                            </h5>
                            <div class="row g-3">
                                <?php if(!empty($pendidikans)): foreach($pendidikans as $p): ?>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4 d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 fw-bold"><?= $p['jenjang'] ?></div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($p['institusi']) ?></div>
                                            <div class="small text-muted">Lulus Tahun: <?= $p['tahun_lulus'] ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                <div class="col-12 text-center py-4 text-muted">Belum ada riwayat pendidikan detail.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Rewards & Punishments -->
                    <div class="col-12">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4">Timeline Penghargaan & Sanksi</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="timeline ps-2">
                                        <?php if($reward->num_rows > 0): while($r = $reward->fetch_assoc()): ?>
                                        <div class="timeline-item success">
                                            <div class="small text-success fw-bold"><?= date('d M Y', strtotime($r['tanggal'])) ?></div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['deskripsi']) ?></div>
                                        </div>
                                        <?php endwhile; else: ?>
                                        <div class="text-muted small">Tidak ada catatan penghargaan.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 border-start">
                                    <div class="timeline ps-2">
                                        <?php if($punishment->num_rows > 0): while($p = $punishment->fetch_assoc()): ?>
                                        <div class="timeline-item danger">
                                            <div class="small text-danger fw-bold"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['deskripsi']) ?></div>
                                        </div>
                                        <?php endwhile; else: ?>
                                        <div class="text-muted small">Tidak ada catatan sanksi.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Documents & Serdos -->
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-4">
                    <!-- Serdos Card -->
                    <div class="info-card" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #451a03; border: none;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-white bg-opacity-25 p-3 rounded-4 fs-3 shadow-sm"><i class="fas fa-certificate"></i></div>
                            <div>
                                <h5 class="fw-bold mb-0">Sertifikasi Dosen</h5>
                                <p class="small mb-0 opacity-75">Professional Certification</p>
                            </div>
                        </div>
                        <?php if(!empty($serdoses)): $sd = $serdoses[0]; ?>
                            <div class="bg-white bg-opacity-20 p-3 rounded-4 mb-3 border border-white border-opacity-25">
                                <div class="small fw-bold opacity-75 text-uppercase">No. Sertifikat</div>
                                <div class="fs-5 fw-bold"><?= htmlspecialchars($sd['no_serdos']) ?></div>
                                <div class="small fw-bold mt-2 opacity-75">TMT: <?= date('d M Y', strtotime($sd['tmt'])) ?></div>
                            </div>
                            <?php if($sd['dokumen']): ?>
                                <a href="<?= $sd['dokumen'] ?>" target="_blank" class="btn btn-white w-100 rounded-pill fw-bold" style="background: white; color: #b45309; border: none;">Lihat Dokumen</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-3 opacity-75 fw-bold">Belum Sertifikasi</div>
                        <?php endif; ?>
                    </div>

                    <!-- Document Vault -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                            <i class="fas fa-vault text-primary"></i> Document Vault
                        </h5>
                        <div class="d-flex flex-column gap-3">
                            <?php 
                            $docs = [
                                ['label' => 'Kartu Tanda Penduduk', 'file' => $d['dok_ktp'] ?? '', 'icon' => 'fa-id-card'],
                                ['label' => 'Kartu Keluarga', 'file' => $d['dok_kk'] ?? '', 'icon' => 'fa-users'],
                                ['label' => 'SK Status Dosen', 'file' => $d['dok_status_dosen'] ?? '', 'icon' => 'fa-file-signature'],
                                ['label' => 'SK TMB', 'file' => $d['dok_tmb'] ?? '', 'icon' => 'fa-calendar-check'],
                                ['label' => 'SK Struktural', 'file' => $d['dok_penugasan_struktural'] ?? '', 'icon' => 'fa-sitemap'],
                            ];
                            foreach($docs as $doc): if(!empty($doc['file'])):
                            ?>
                            <a href="<?= $doc['file'] ?>" target="_blank" class="vault-item shadow-sm">
                                <div class="vault-icon"><i class="fas <?= $doc['icon'] ?>"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small fw-bold text-dark"><?= $doc['label'] ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Document Verified</div>
                                </div>
                                <i class="fas fa-chevron-right text-muted small"></i>
                            </a>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="info-card bg-light">
                        <h6 class="fw-bold mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary rounded-pill text-start"><i class="fas fa-print me-2"></i> Cetak Bio-Data</button>
                            <button class="btn btn-outline-secondary rounded-pill text-start"><i class="fas fa-share-alt me-2"></i> Bagikan Profil</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

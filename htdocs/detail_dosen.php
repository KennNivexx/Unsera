<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$id = $_GET['id'] ?? 0;
if (!$id) { header('Location: daftar_dosen.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM dosen WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$d) { echo "<script>alert('Data tidak ditemukan!');location='daftar_dosen.php';</script>"; exit; }

$reward        = $conn->query("SELECT * FROM reward WHERE dosen_id = $id ORDER BY tanggal DESC");
$punishment    = $conn->query("SELECT * FROM punishment WHERE dosen_id = $id ORDER BY tanggal DESC");
$jabfungs            = ($q = $conn->query("SELECT * FROM jabfung_dosen WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$lldiktis            = ($q = $conn->query("SELECT * FROM lldikti_dosen WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$yayasans            = ($q = $conn->query("SELECT * FROM yayasan_dosen WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$pendidikans         = ($q = $conn->query("SELECT * FROM pendidikan_dosen WHERE dosen_id = $id ORDER BY tahun_lulus DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$status_riwayats     = ($q = $conn->query("SELECT * FROM status_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$homebase_riwayats   = ($q = $conn->query("SELECT * FROM homebase_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$unit_kerja_riwayats = ($q = $conn->query("SELECT * FROM unit_kerja_dosen_riwayat WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];
$serdoses            = ($q = $conn->query("SELECT * FROM sertifikasi_dosen WHERE dosen_id = $id ORDER BY tmt DESC")) ? $q->fetch_all(MYSQLI_ASSOC) : [];


$breadcrumbs = [['label' => 'Daftar Dosen', 'url' => 'daftar_dosen.php'], ['label' => 'Profil Dosen', 'url' => '#']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Dosen | <?= htmlspecialchars($d['nama_lengkap']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .profile-header-card {
            background: white; border-radius: 16px;
            border-left: 6px solid #4f46e5;
            padding: 24px 28px; margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .avatar-box {
            width: 110px; border: 1px solid #e2e8f0; border-radius: 12px;
            overflow: hidden; flex-shrink: 0; background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .avatar-content {
            padding: 16px; font-size: 2.4rem; font-weight: 800;
            color: #1e293b; text-align: center; background: #f8fafc;
            min-height: 80px; display: flex; align-items: center; justify-content: center;
        }
        .avatar-content img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-label {
            background: #4f46e5; color: white; text-align: center;
            padding: 5px; font-size: 0.6rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
        }
        .info-card { background: white; border-radius: 16px; border: 1px solid #e8edf3; padding: 28px; height: 100%; }
        .sec-title {
            display: flex; align-items: center; gap: 10px;
            font-weight: 700; font-size: 1rem; color: #4338ca; margin-bottom: 22px;
        }
        .sec-title .ic {
            width: 32px; height: 32px; border-radius: 8px; background: #eef2ff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: #4338ca;
        }
        .sec-title.gold { color: #d97706; }
        .sec-title.gold .ic { background: #fef3c7; color: #d97706; }
        .fl { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 3px; }
        .fv { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
        .field-row { margin-bottom: 16px; }
        .mini-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
        .pill-green { background: #dcfce7; color: #16a34a; padding: 4px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; display: inline-block; }
        .pill-red   { background: #fee2e2; color: #dc2626; padding: 4px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; display: inline-block; }
        .riwayat-item {
            background: #f8fafc; border-left: 4px solid #4f46e5;
            border-radius: 0 10px 10px 0; padding: 11px 14px;
            margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;
        }
        .ri-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; }
        .ri-sub  { font-size: 0.72rem; color: #4f46e5; font-weight: 600; margin-top: 2px; }
        .jf-item {
            background: white; border: 1px solid #e8edf3; border-left: 4px solid #4f46e5;
            border-radius: 0 10px 10px 0; padding: 11px 14px; margin-bottom: 8px;
        }
        .jf-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; }
        .jf-tmt  { font-size: 0.72rem; color: #4f46e5; font-weight: 600; margin-top: 2px; }
        .empty-dash { border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 20px; text-align: center; color: #94a3b8; font-size: 0.83rem; }
        .edu-item { background: white; border: 1px solid #e8edf3; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
        .edu-icon { width: 42px; height: 42px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 1.1rem; flex-shrink: 0; }
        .dok-item .dok-label { font-size: 0.78rem; font-weight: 600; color: #4f46e5; margin-bottom: 2px; }
        .dok-item .dok-status { font-size: 0.73rem; color: #94a3b8; }
        .dok-item .dok-label.available { cursor: pointer; text-decoration: underline; }
        .reward-item { border-left: 4px solid #16a34a; background: #f0fdf4; border-radius: 0 8px 8px 0; padding: 10px 14px; margin-bottom: 8px; }
        .punish-item { border-left: 4px solid #dc2626; background: #fef2f2; border-radius: 0 8px 8px 0; padding: 10px 14px; margin-bottom: 8px; }
        .rp-date-g { color: #16a34a; font-size: 0.72rem; font-weight: 700; }
        .rp-date-r { color: #dc2626; font-size: 0.72rem; font-weight: 700; }
        .rp-desc   { color: #1e293b; font-weight: 700; font-size: 0.85rem; margin-top: 2px; }
        .rp-sec-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 14px; }
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<div class="main-content">
    <?php include 'components/navbar.php'; ?>
    <div class="container-fluid px-4 py-4">

        <!-- ── Header ── -->
        <div class="profile-header-card">
            <div class="d-flex align-items-center gap-4">
                <div class="avatar-box">
                    <div class="avatar-content">
                        <?php if(!empty($d['foto_profil']) && file_exists($d['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($d['foto_profil']) ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($d['nama_lengkap'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-label">DETAIL</div>
                </div>
                <div class="flex-grow-1">
                    <h3 class="fw-bold mb-1" style="font-family:'Outfit',sans-serif;"><?= htmlspecialchars($d['nama_lengkap']) ?></h3>
                    <div class="d-flex align-items-center gap-2 text-secondary" style="font-size:0.9rem;">
                        <span><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?></span>
                        <span>•</span>
                        <?php $aktifH = ($d['status_keaktifan'] ?? 'Aktif') === 'Aktif'; ?>
                        <span class="<?= $aktifH ? 'pill-green' : 'pill-red' ?>"><?= htmlspecialchars($d['status_dosen'] ?? 'Tetap') ?></span>
                    </div>
                </div>
                <a href="daftar_dosen.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" style="font-size:0.85rem;">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                </a>
            </div>
        </div>

        <!-- ── Row 1: Identitas Personal | Status Kepegawaian ── -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="sec-title"><div class="ic"><i class="fas fa-id-card"></i></div> Identitas Personal</div>
                    <div class="field-row"><div class="fl">NAMA LENGKAP</div><div class="fv"><?= htmlspecialchars($d['nama_lengkap']) ?></div></div>
                    <div class="field-row"><div class="fl">GELAR / PENDIDIKAN</div><div class="fv"><?= htmlspecialchars($d['riwayat_pendidikan'] ?? '-') ?></div></div>
                    <div class="field-row"><div class="fl">NIP</div><div class="fv"><?= htmlspecialchars($d['nip'] ?? '-') ?></div></div>
                    <div class="field-row"><div class="fl">NIDN</div><div class="fv"><?= htmlspecialchars($d['nidn'] ?? '-') ?></div></div>
                    <div class="field-row"><div class="fl">NUPTK</div><div class="fv"><?= htmlspecialchars($d['nuptk'] ?? '-') ?></div></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="mini-card"><div class="fl">TEMPAT LAHIR</div><div class="fv"><?= htmlspecialchars($d['ttl_tempat'] ?? '-') ?></div></div></div>
                        <div class="col-6"><div class="mini-card"><div class="fl">TANGGAL LAHIR</div><div class="fv"><?= !empty($d['ttl_tanggal']) ? date('d F Y', strtotime($d['ttl_tanggal'])) : '-' ?></div></div></div>
                    </div>
                    <div class="field-row"><div class="fl">ALAMAT</div><div class="fv"><?= htmlspecialchars($d['alamat'] ?? '-') ?></div></div>
                    <div class="field-row mb-0"><div class="fl">STATUS PERNIKAHAN</div><div class="fv"><?= htmlspecialchars($d['status_pribadi'] ?? '-') ?></div></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="sec-title"><div class="ic"><i class="fas fa-briefcase"></i></div> Status Kepegawaian</div>
                    <div class="field-row"><div class="fl">STATUS DOSEN</div><div class="fv"><span class="pill-green"><?= htmlspecialchars($d['status_dosen'] ?? '-') ?></span></div></div>
                    <div class="field-row"><div class="fl">JABATAN DOSEN</div><div class="fv"><?= htmlspecialchars($d['jenis_dosen'] ?? '-') ?></div></div>
                    <div class="field-row"><div class="fl">JABATAN STRUKTURAL</div><div class="fv"><?= htmlspecialchars($d['jabatan_struktural'] ?? '-') ?></div></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="mini-card">
                                <div class="fl">HOMEBASE PRODI</div>
                                <div class="fv" style="font-size:0.82rem;"><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?></div>
                                <hr class="my-1 opacity-25">
                                <div class="fl" style="font-size:0.6rem;">RIWAYAT HOMEBASE</div>
                                <?php foreach(array_slice($homebase_riwayats, 1) as $hr): ?>
                                    <div class="text-muted d-flex justify-content-between align-items-center" style="font-size:0.7rem;">
                                        <span>• <?= htmlspecialchars($hr['homebase_prodi']) ?> (<?= !empty($hr['tmt']) ? date('Y', strtotime($hr['tmt'])) : '-' ?>)</span>
                                        <?php if(!empty($hr['dokumen'])): ?>
                                        <a href="<?= htmlspecialchars($hr['dokumen']) ?>" target="_blank" class="ms-1" title="Lihat SK"><i class="fas fa-file-pdf"></i></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-card">
                                <div class="fl">UNIT KERJA</div>
                                <div class="fv" style="font-size:0.82rem;"><?= htmlspecialchars($d['unit_kerja'] ?? '-') ?></div>
                                <hr class="my-1 opacity-25">
                                <div class="fl" style="font-size:0.6rem;">RIWAYAT UNIT</div>
                                <?php foreach(array_slice($unit_kerja_riwayats, 1) as $ur): ?>
                                    <div class="text-muted d-flex justify-content-between align-items-center" style="font-size:0.7rem;">
                                        <span>• <?= htmlspecialchars($ur['unit_kerja']) ?> (<?= !empty($ur['tmt']) ? date('Y', strtotime($ur['tmt'])) : '-' ?>)</span>
                                        <?php if(!empty($ur['dokumen'])): ?>
                                        <a href="<?= htmlspecialchars($ur['dokumen']) ?>" target="_blank" class="ms-1" title="Lihat SK"><i class="fas fa-file-pdf"></i></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="field-row"><div class="fl">TMK / TMT</div><div class="fv"><?= !empty($d['tmk']) ? date('d/m/Y', strtotime($d['tmk'])) : '-' ?></div></div>
                    <div class="field-row">
                        <div class="fl">KEAKTIFAN</div>
                        <div class="fv">
                            <?php $aktif = ($d['status_keaktifan'] ?? 'Aktif') === 'Aktif'; ?>
                            <i class="fas fa-circle me-1" style="color:<?= $aktif ? '#16a34a' : '#dc2626' ?>;font-size:0.6rem;"></i>
                            <span style="color:<?= $aktif ? '#16a34a' : '#dc2626' ?>"><?= htmlspecialchars($d['status_keaktifan'] ?? 'Aktif') ?></span>
                        </div>
                    </div>
                    <div class="fl mb-2 mt-1">RIWAYAT STATUS KEPEGAWAIAN</div>
                    <?php if(!empty($status_riwayats)): foreach($status_riwayats as $sr): ?>
                    <div class="riwayat-item">
                        <div>
                            <div class="ri-name"><?= htmlspecialchars($sr['status_dosen']) ?></div>
                            <div class="ri-sub">Terhitung Mulai Bekerja: <?= !empty($sr['tmt']) ? date('d/m/Y', strtotime($sr['tmt'])) : '-' ?></div>
                        </div>
                        <?php if(!empty($sr['dokumen'])): ?>
                        <a href="<?= htmlspecialchars($sr['dokumen']) ?>" target="_blank" style="color:#4f46e5;"><i class="fas fa-eye"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="empty-dash">Belum ada riwayat status.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Row 2: Riwayat Kepangkatan (full width) ── -->
        <div class="info-card mb-4" style="height:auto;">
            <div class="sec-title"><div class="ic"><i class="fas fa-layer-group"></i></div> Riwayat Kepangkatan &amp; Jabatan Akademik</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="fl mb-3">JABATAN FUNGSIONAL</div>
                    <?php if(!empty($jabfungs)): foreach($jabfungs as $jf): ?>
                    <div class="jf-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="jf-name"><?= htmlspecialchars($jf['jabatan']) ?></div>
                                <div class="jf-tmt">TMT: <?= !empty($jf['tmt']) ? date('d/m/Y', strtotime($jf['tmt'])) : '-' ?></div>
                            </div>
                            <?php if(!empty($jf['dokumen'])): ?>
                            <a href="<?= htmlspecialchars($jf['dokumen']) ?>" target="_blank" class="text-primary small ms-2" title="Lihat Dokumen"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="empty-dash">Tidak ada data jabatan fungsional.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="fl mb-3">GOLONGAN LLDIKTI</div>
                    <?php if(!empty($lldiktis)): foreach($lldiktis as $ll): ?>
                    <div class="jf-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="jf-name"><?= htmlspecialchars($ll['golongan']) ?></div>
                                <div class="jf-tmt">TMT: <?= !empty($ll['tmt']) ? date('d/m/Y', strtotime($ll['tmt'])) : '-' ?></div>
                            </div>
                            <?php if(!empty($ll['dokumen'])): ?>
                            <a href="<?= htmlspecialchars($ll['dokumen']) ?>" target="_blank" class="text-primary small ms-2" title="Lihat Dokumen"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="empty-dash">Tidak ada data LLDIKTI.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="fl mb-3">GOLONGAN YAYASAN</div>
                    <?php if(!empty($yayasans)): foreach($yayasans as $yy): ?>
                    <div class="jf-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="jf-name"><?= htmlspecialchars($yy['golongan']) ?></div>
                                <div class="jf-tmt">TMT: <?= !empty($yy['tmt']) ? date('d/m/Y', strtotime($yy['tmt'])) : '-' ?></div>
                            </div>
                            <?php if(!empty($yy['dokumen'])): ?>
                            <a href="<?= htmlspecialchars($yy['dokumen']) ?>" target="_blank" class="text-primary small ms-2" title="Lihat Dokumen"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="empty-dash">Tidak ada data Yayasan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Row 3: Kualifikasi Akademik | Sertifikasi Dosen ── -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="sec-title"><div class="ic"><i class="fas fa-graduation-cap"></i></div> Kualifikasi Akademik</div>
                    <?php if(!empty($pendidikans)): foreach($pendidikans as $pend): ?>
                    <div class="edu-item">
                        <div class="edu-icon"><i class="fas fa-university"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($pend['jenjang']) ?></div>
                            <div class="text-secondary" style="font-size:0.8rem;"><?= htmlspecialchars($pend['institusi']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">Tahun Lulus: <?= htmlspecialchars($pend['tahun_lulus']) ?></div>
                        </div>
                        <?php if(!empty($pend['dokumen'])): ?>
                        <a href="<?= htmlspecialchars($pend['dokumen']) ?>" target="_blank" class="btn btn-sm btn-light border" title="Lihat Ijazah"><i class="fas fa-file-pdf text-danger"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="empty-dash">Belum ada data pendidikan.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="sec-title gold"><div class="ic"><i class="fas fa-certificate"></i></div> Sertifikasi Dosen</div>
                    <?php if(!empty($serdoses)): foreach($serdoses as $sd): ?>
                    <div class="edu-item">
                        <div class="edu-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-award"></i></div>
                        <div>
                            <div class="fw-bold" style="font-size:0.9rem;">No. <?= htmlspecialchars($sd['no_serdos']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">TMT: <?= !empty($sd['tmt']) ? date('d/m/Y', strtotime($sd['tmt'])) : '-' ?></div>
                            <?php if(!empty($sd['dokumen'])): ?><a href="<?= $sd['dokumen'] ?>" target="_blank" class="text-primary small">Lihat Dokumen</a><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="empty-dash">Data Sertifikasi Dosen belum tersedia.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Row 4: Dokumen Pendukung (full width) ── -->
        <div class="info-card mb-4" style="height:auto;">
            <div class="fw-bold mb-4" style="font-size:1rem;">Dokumen Pendukung Lainnya</div>
            <div class="d-flex flex-wrap gap-4">
                <?php
                $latestSerdos = $serdoses[0] ?? null;
                $dokDefs = [
                    ['label' => 'Scan KTP',                       'file' => $d['dok_ktp'] ?? ''],
                    ['label' => 'Scan KK',                        'file' => $d['dok_kk'] ?? ''],
                    ['label' => 'Foto Profil',                    'file' => $d['foto_profil'] ?? ''],
                    ['label' => 'Dokumen Status Dosen',           'file' => $d['dok_status_dosen'] ?? ''],
                    ['label' => 'Dokumen Terhitung Mulai Bertugas','file' => $d['dok_tmb'] ?? ''],
                    ['label' => 'Dokumen Berhenti Bertugas',      'file' => $d['dok_berhenti_bertugas'] ?? ''],
                    ['label' => 'Dokumen Penugasan Struktural',   'file' => $d['dok_penugasan_struktural'] ?? ''],
                    ['label' => 'Dokumen Serdos',                 'file' => $latestSerdos['dokumen'] ?? ''],
                ];
                foreach($dokDefs as $dok):
                    $hasFile = !empty($dok['file']);
                ?>
                <div class="dok-item" style="min-width:110px;">
                    <?php if($hasFile): ?>
                    <a href="<?= htmlspecialchars($dok['file']) ?>" target="_blank" class="dok-label available text-decoration-none d-block"><?= $dok['label'] ?></a>
                    <div class="dok-status text-success">Tersedia</div>
                    <?php else: ?>
                    <div class="dok-label"><?= $dok['label'] ?></div>
                    <div class="dok-status">Tidak Tersedia</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Row 5: Rewards | Punishments ── -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="info-card" style="height:auto;">
                    <div class="rp-sec-title text-success">Rewards</div>
                    <?php if($reward && $reward->num_rows > 0): while($r = $reward->fetch_assoc()): ?>
                    <div class="reward-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="rp-date-g">TMT: <?= !empty($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-' ?></div>
                            <div class="rp-desc"><?= htmlspecialchars($r['deskripsi']) ?></div>
                        </div>
                        <?php if(!empty($r['file_upload'])): ?>
                        <a href="<?= htmlspecialchars($r['file_upload']) ?>" target="_blank" class="text-success ms-2"><i class="fas fa-file-pdf"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="text-muted small">Belum ada reward dicatat.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card" style="height:auto;">
                    <div class="rp-sec-title text-danger">Punishments</div>
                    <?php if($punishment && $punishment->num_rows > 0): while($pn = $punishment->fetch_assoc()): ?>
                    <div class="punish-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="rp-date-r">TMT: <?= !empty($pn['tanggal']) ? date('d/m/Y', strtotime($pn['tanggal'])) : '-' ?></div>
                            <div class="rp-desc"><?= htmlspecialchars($pn['deskripsi']) ?></div>
                        </div>
                        <?php if(!empty($pn['file_upload'])): ?>
                        <a href="<?= htmlspecialchars($pn['file_upload']) ?>" target="_blank" class="text-danger ms-2"><i class="fas fa-file-pdf"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="text-muted small">Belum ada punishment dicatat.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

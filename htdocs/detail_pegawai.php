<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$id = $_GET['id'] ?? 0;
if (!$id) { header('Location: data_pegawai.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) { echo "<script>alert('Data tidak ditemukan!');location='data_pegawai.php';</script>"; exit; }

$rewards      = ($res = $conn->query("SELECT * FROM reward_pegawai WHERE pegawai_id = $id ORDER BY tanggal DESC")) ? $res : null;
$punishments  = ($res = $conn->query("SELECT * FROM punishment_pegawai WHERE pegawai_id = $id ORDER BY tanggal DESC")) ? $res : null;
$pendidikans  = ($res = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id ORDER BY tahun_lulus DESC")) ? $res->fetch_all(MYSQLI_ASSOC) : [];
$jabfungs     = ($res = $conn->query("SELECT * FROM jabfung_pegawai WHERE pegawai_id = $id ORDER BY tmt DESC")) ? $res->fetch_all(MYSQLI_ASSOC) : [];
$lldiktis     = ($res = $conn->query("SELECT * FROM lldikti_pegawai WHERE pegawai_id = $id ORDER BY tmt DESC")) ? $res->fetch_all(MYSQLI_ASSOC) : [];
$yayasans     = ($res = $conn->query("SELECT * FROM yayasan_pegawai WHERE pegawai_id = $id ORDER BY tmt DESC")) ? $res->fetch_all(MYSQLI_ASSOC) : [];
$status_riw   = ($res = $conn->query("SELECT * FROM status_pegawai_riwayat WHERE pegawai_id = $id ORDER BY tmt DESC, id DESC")) ? $res->fetch_all(MYSQLI_ASSOC) : [];
$unit_kerja_riw = ($res = $conn->query("SELECT * FROM unit_kerja_pegawai_riwayat WHERE pegawai_id = $id ORDER BY tmt DESC")) ? $res->fetch_all(MYSQLI_ASSOC) : [];

$breadcrumbs = [['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'], ['label' => 'Profil Pegawai', 'url' => '#']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pegawai | <?= htmlspecialchars($p['nama_lengkap']) ?></title>
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
        .sec-title { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1rem; color: #4338ca; margin-bottom: 22px; }
        .sec-title .ic { width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: #4338ca; }
        .fl { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 3px; }
        .fv { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
        .field-row { margin-bottom: 16px; }
        .mini-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
        .pill-green { background: #dcfce7; color: #16a34a; padding: 4px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; display: inline-block; }
        .pill-red   { background: #fee2e2; color: #dc2626; padding: 4px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; display: inline-block; }
        .riwayat-item { background: #f8fafc; border-left: 4px solid #4f46e5; border-radius: 0 10px 10px 0; padding: 11px 14px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; }
        .ri-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; }
        .ri-sub  { font-size: 0.72rem; color: #4f46e5; font-weight: 600; margin-top: 2px; }
        .jf-item { background: white; border: 1px solid #e8edf3; border-left: 4px solid #4f46e5; border-radius: 0 10px 10px 0; padding: 11px 14px; margin-bottom: 8px; }
        .jf-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; }
        .jf-tmt  { font-size: 0.72rem; color: #4f46e5; font-weight: 600; margin-top: 2px; }
        .empty-dash { border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 20px; text-align: center; color: #94a3b8; font-size: 0.83rem; }
        .edu-item { background: white; border: 1px solid #e8edf3; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
        .edu-icon { width: 42px; height: 42px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 1.1rem; flex-shrink: 0; }
        .dok-item .dok-label { font-size: 0.78rem; font-weight: 600; color: #4f46e5; margin-bottom: 2px; }
        .dok-item .dok-status { font-size: 0.73rem; color: #94a3b8; }
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
                        <?php if(!empty($p['foto_profil']) && file_exists($p['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($p['foto_profil']) ?>">
                        <?php else: ?>
                            <?= strtoupper(substr($p['nama_lengkap'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-label">DETAIL</div>
                </div>
                <div class="flex-grow-1">
                    <h3 class="fw-bold mb-1" style="font-family:'Outfit',sans-serif;"><?= htmlspecialchars($p['nama_lengkap']) ?></h3>
                    <div class="d-flex align-items-center gap-2 text-secondary" style="font-size:0.9rem;">
                        <span><?= htmlspecialchars($p['unit_kerja'] ?? '-') ?></span>
                        <span>•</span>
                        <span class="pill-green"><?= htmlspecialchars($p['status_pegawai'] ?? 'Tetap') ?></span>
                    </div>
                </div>
                <a href="data_pegawai.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" style="font-size:0.85rem;">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                </a>
            </div>
        </div>

        <!-- ── Row 1: Identitas Personal | Status Kepegawaian ── -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="sec-title"><div class="ic"><i class="fas fa-id-card"></i></div> Identitas Personal</div>
                    <div class="field-row"><div class="fl">NAMA LENGKAP</div><div class="fv"><?= htmlspecialchars($p['nama_lengkap']) ?></div></div>
                    <div class="field-row"><div class="fl">GELAR / PENDIDIKAN</div><div class="fv"><?= htmlspecialchars($p['riwayat_pendidikan'] ?? '-') ?></div></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="mini-card"><div class="fl">TEMPAT LAHIR</div><div class="fv"><?= htmlspecialchars($p['ttl_tempat'] ?? '-') ?></div></div></div>
                        <div class="col-6"><div class="mini-card"><div class="fl">TANGGAL LAHIR</div><div class="fv"><?= !empty($p['ttl_tanggal']) ? date('d F Y', strtotime($p['ttl_tanggal'])) : '-' ?></div></div></div>
                    </div>
                    <div class="field-row"><div class="fl">ALAMAT</div><div class="fv"><?= htmlspecialchars($p['alamat'] ?? '-') ?></div></div>
                    <div class="field-row mb-0"><div class="fl">STATUS PERNIKAHAN</div><div class="fv"><?= htmlspecialchars($p['status_pribadi'] ?? '-') ?></div></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="sec-title"><div class="ic"><i class="fas fa-briefcase"></i></div> Status Kepegawaian</div>
                    <div class="field-row"><div class="fl">STATUS PEGAWAI</div><div class="fv"><span class="pill-green"><?= htmlspecialchars($p['status_pegawai'] ?? '-') ?></span></div></div>
                    <div class="field-row">
                        <div class="fl">JABATAN</div>
                        <div class="fv"><?= htmlspecialchars($p['posisi_jabatan'] ?? '-') ?></div>
                        <?php if(count($jabfungs) > 1): ?>
                        <div class="mt-2 p-2 bg-light rounded" style="font-size:0.75rem;">
                            <div class="fl" style="font-size:0.6rem; margin-bottom:5px;">RIWAYAT JABATAN</div>
                            <?php foreach(array_slice($jabfungs, 1) as $jf_h): ?>
                                <div class="text-muted mb-1 d-flex justify-content-between align-items-center">
                                    <span>• <?= htmlspecialchars($jf_h['jabatan']) ?> (<?= !empty($jf_h['tmt']) ? date('Y', strtotime($jf_h['tmt'])) : '-' ?>)</span>
                                    <?php if(!empty($jf_h['dokumen'])): ?>
                                    <a href="<?= htmlspecialchars($jf_h['dokumen']) ?>" target="_blank" class="ms-2" title="Lihat SK"><i class="fas fa-file-pdf"></i></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="field-row">
                        <div class="fl">UNIT KERJA</div>
                        <div class="fv"><?= htmlspecialchars($p['unit_kerja'] ?? '-') ?></div>
                        <?php if(count($unit_kerja_riw) > 1): ?>
                        <div class="mt-2 p-2 bg-light rounded" style="font-size:0.75rem;">
                            <div class="fl" style="font-size:0.6rem; margin-bottom:5px;">RIWAYAT UNIT</div>
                            <?php foreach(array_slice($unit_kerja_riw, 1) as $ur): ?>
                                <div class="text-muted mb-1 d-flex justify-content-between align-items-center">
                                    <span>• <?= htmlspecialchars($ur['unit_kerja']) ?> (<?= !empty($ur['tmt']) ? date('Y', strtotime($ur['tmt'])) : '-' ?>)</span>
                                    <?php if(!empty($ur['dokumen'])): ?>
                                    <a href="<?= htmlspecialchars($ur['dokumen']) ?>" target="_blank" class="ms-2" title="Lihat SK"><i class="fas fa-file-pdf"></i></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="field-row"><div class="fl">TMK / TMT MULAI BEKERJA</div><div class="fv"><?= !empty($p['tmt_mulai_kerja']) ? date('d/m/Y', strtotime($p['tmt_mulai_kerja'])) : '-' ?></div></div>
                    <?php if(!empty($p['tmt_tidak_kerja'])): ?>
                    <div class="field-row"><div class="fl">TMT TIDAK BEKERJA</div><div class="fv text-danger"><?= date('d/m/Y', strtotime($p['tmt_tidak_kerja'])) ?></div></div>
                    <?php endif; ?>
                    <div class="field-row">
                        <div class="fl">KEAKTIFAN</div>
                        <div class="fv">
                            <?php $aktif = ($p['status_keaktifan'] ?? 'Aktif') === 'Aktif'; ?>
                            <i class="fas fa-circle me-1" style="color:<?= $aktif ? '#16a34a' : '#dc2626' ?>;font-size:0.6rem;"></i>
                            <span style="color:<?= $aktif ? '#16a34a' : '#dc2626' ?>"><?= htmlspecialchars($p['status_keaktifan'] ?? 'Aktif') ?></span>
                        </div>
                    </div>
                    <div class="fl mb-2 mt-1">RIWAYAT STATUS PEGAWAI</div>
                    <?php if(!empty($status_riw)): foreach($status_riw as $sr): ?>
                    <div class="riwayat-item">
                        <div>
                            <div class="ri-name"><?= htmlspecialchars($sr['status_pegawai'] ?? '-') ?></div>
                            <div class="ri-sub">Terhitung Mulai Bekerja: <?= !empty($sr['tmt_mulai_kerja']) ? date('d/m/Y', strtotime($sr['tmt_mulai_kerja'])) : (!empty($sr['tmt']) ? date('d/m/Y', strtotime($sr['tmt'])) : '-') ?></div>
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
            <div class="sec-title"><div class="ic"><i class="fas fa-layer-group"></i></div> Riwayat Kepangkatan &amp; Golongan</div>
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
                    <div class="empty-dash">Tidak ada data golongan yayasan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Row 3: Kualifikasi Akademik (full width) ── -->
        <div class="info-card mb-4" style="height:auto;">
            <div class="sec-title"><div class="ic"><i class="fas fa-graduation-cap"></i></div> Kualifikasi Akademik</div>
            <?php if(!empty($pendidikans)): ?>
            <div class="row g-3">
                <?php foreach($pendidikans as $pend): ?>
                <div class="col-md-4">
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
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-dash">Belum ada data pendidikan.</div>
            <?php endif; ?>
        </div>

        <!-- ── Row 4: Dokumen Pendukung (full width) ── -->
        <div class="info-card mb-4" style="height:auto;">
            <div class="fw-bold mb-4" style="font-size:1rem;">Dokumen Pendukung Lainnya</div>
            <div class="d-flex flex-wrap gap-4">
                <?php
                $latestStatus = $status_riw[0] ?? null;
                $latestYayasan = $yayasans[0] ?? null;
                $dokDefs = [
                    ['label' => 'Foto Profil',           'file' => $p['foto_profil'] ?? ''],
                    ['label' => 'Dokumen KTP',           'file' => $p['dok_ktp'] ?? ''],
                    ['label' => 'Dokumen KK',            'file' => $p['dok_kk'] ?? ''],
                    ['label' => 'Dokumen SK Status',     'file' => $latestStatus['dokumen'] ?? ''],
                    ['label' => 'Dokumen Pemberhentian', 'file' => $p['dok_tmtk'] ?? ''],
                    ['label' => 'Dokumen Golongan',      'file' => $latestYayasan['dokumen'] ?? ''],
                ];
                foreach($dokDefs as $dok):
                    $hasFile = !empty($dok['file']);
                ?>
                <div class="dok-item" style="min-width:110px;">
                    <?php if($hasFile): ?>
                    <a href="<?= htmlspecialchars($dok['file']) ?>" target="_blank" class="dok-label text-decoration-none d-block" style="cursor:pointer;"><?= $dok['label'] ?></a>
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
                    <?php if($rewards && $rewards->num_rows > 0): while($r = $rewards->fetch_assoc()): ?>
                    <div class="reward-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="rp-date-g">TMT: <?= !empty($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-' ?></div>
                            <div class="rp-desc"><?= htmlspecialchars($r['keterangan']) ?></div>
                        </div>
                        <?php if(!empty($r['dokumen'])): ?>
                        <a href="<?= htmlspecialchars($r['dokumen']) ?>" target="_blank" class="text-success ms-2"><i class="fas fa-file-pdf"></i></a>
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
                    <?php if($punishments && $punishments->num_rows > 0): while($pn = $punishments->fetch_assoc()): ?>
                    <div class="punish-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="rp-date-r">TMT: <?= !empty($pn['tanggal']) ? date('d/m/Y', strtotime($pn['tanggal'])) : '-' ?></div>
                            <div class="rp-desc"><?= htmlspecialchars($pn['keterangan']) ?></div>
                        </div>
                        <?php if(!empty($pn['dokumen'])): ?>
                        <a href="<?= htmlspecialchars($pn['dokumen']) ?>" target="_blank" class="text-danger ms-2"><i class="fas fa-file-pdf"></i></a>
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

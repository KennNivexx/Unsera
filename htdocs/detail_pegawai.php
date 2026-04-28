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

// Fetch Profile
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pegawai = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pegawai) {
    header('Location: data_pegawai.php');
    exit;
}

// Fetch related data
$rewards = $conn->query("SELECT * FROM reward_pegawai WHERE pegawai_id = $id ORDER BY tanggal DESC");
$punishments = $conn->query("SELECT * FROM punishment_pegawai WHERE pegawai_id = $id ORDER BY tanggal DESC");
$pendidikans = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id ORDER BY tahun_lulus DESC")->fetch_all(MYSQLI_ASSOC);
$status_riwayats = $conn->query("SELECT * FROM status_pegawai_riwayat WHERE pegawai_id = $id ORDER BY tmt_mulai_kerja DESC, id DESC")->fetch_all(MYSQLI_ASSOC);

$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'],
    ['label' => 'Profil Pegawai', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pegawai | <?= htmlspecialchars($pegawai['nama_lengkap']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 30px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .avatar-container {
            width: 150px; height: 150px; border-radius: 35px; overflow: hidden;
            border: 5px solid rgba(255,255,255,0.2); background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem; font-weight: 800; color: white;
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
        .detail-label { color: #64748b; font-weight: 500; font-size: 0.85rem; }
        .detail-value { color: #1e293b; font-weight: 600; text-align: right; }
        .vault-item {
            background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px;
            padding: 15px; display: flex; align-items: center; gap: 15px;
            transition: all 0.3s; text-decoration: none; color: inherit; margin-bottom: 12px;
        }
        .vault-item:hover { transform: translateY(-5px); border-color: var(--primary); background: white; }
        .vault-icon { width: 40px; height: 40px; border-radius: 10px; background: white; display: flex; align-items: center; justify-content: center; color: var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .timeline-item { position: relative; padding-left: 30px; padding-bottom: 25px; border-left: 2px solid #e2e8f0; }
        .timeline-item::before { content: ''; position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); border: 2px solid white; }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="profile-header">
            <div class="row align-items-center g-4">
                <div class="col-auto">
                    <div class="avatar-container shadow-lg">
                        <?php if(!empty($pegawai['foto_profil']) && file_exists($pegawai['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($pegawai['foto_profil']) ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($pegawai['nama_lengkap'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                        <span class="badge bg-primary rounded-pill px-3 py-2"><?= htmlspecialchars($pegawai['status_pegawai'] ?? 'Tetap') ?></span>
                        <span class="badge bg-light text-dark rounded-pill px-3 py-2 fw-bold">ID: <?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <h1 class="display-5 fw-bold mb-1 text-white" style="font-family: 'Outfit', sans-serif;"><?= htmlspecialchars($pegawai['nama_lengkap']) ?></h1>
                    <p class="fs-5 text-white mb-0" style="opacity: 0.9;"><?= htmlspecialchars($pegawai['posisi_jabatan'] ?? '-') ?> &bull; <?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?></p>
                </div>
                <div class="col-auto align-self-start">
                    <a href="form_edit_pegawai.php?id=<?= $id ?>" class="btn btn-light rounded-pill px-4 shadow">
                        <i class="fas fa-edit me-2"></i>Edit Data
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left -->
            <div class="col-lg-8">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2"><i class="fas fa-user-circle text-primary"></i> Data Pribadi</h5>
                            <div class="detail-row"><span class="detail-label">Tempat Lahir</span><span class="detail-value"><?= htmlspecialchars($pegawai['ttl_tempat'] ?: ($pegawai['ttl'] ?? '-')) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Tgl Lahir</span><span class="detail-value"><?= $pegawai['ttl_tanggal'] ? date('d M Y', strtotime($pegawai['ttl_tanggal'])) : '-' ?></span></div>
                            <div class="detail-row"><span class="detail-label">Status Nikah</span><span class="detail-value"><?= htmlspecialchars($pegawai['status_pribadi'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value small w-50"><?= htmlspecialchars($pegawai['alamat'] ?? '-') ?></span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2"><i class="fas fa-briefcase text-primary"></i> Karir & Jabatan</h5>
                            <div class="detail-row"><span class="detail-label">Jabatan</span><span class="detail-value"><?= htmlspecialchars($pegawai['posisi_jabatan'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Unit Kerja</span><span class="detail-value"><?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Mulai Kerja</span><span class="detail-value"><?= $pegawai['tmt_mulai_kerja'] ? date('d M Y', strtotime($pegawai['tmt_mulai_kerja'])) : '-' ?></span></div>
                            <?php if($pegawai['tmt_tidak_kerja']): ?>
                                <div class="detail-row text-danger"><span class="detail-label text-danger">TMTK (Berhenti)</span><span class="detail-value"><?= date('d M Y', strtotime($pegawai['tmt_tidak_kerja'])) ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2"><i class="fas fa-graduation-cap text-primary"></i> Pendidikan</h5>
                            <div class="row g-3">
                                <?php if(!empty($pendidikans)): foreach($pendidikans as $p): ?>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4 d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 fw-bold"><?= $p['jenjang'] ?></div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($p['institusi']) ?></div>
                                            <div class="small text-muted">Lulus: <?= $p['tahun_lulus'] ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                <div class="col-12 text-center py-3 text-muted">Data pendidikan tidak ditemukan.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Rewards Timeline -->
                    <div class="col-12">
                        <div class="info-card">
                            <h5 class="fw-bold mb-4">Penghargaan & Sanksi</h5>
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h6 class="text-success fw-bold mb-3">Rewards</h6>
                                    <?php if($rewards->num_rows > 0): while($r = $rewards->fetch_assoc()): ?>
                                        <div class="timeline-item">
                                            <div class="small text-success fw-bold"><?= date('d M Y', strtotime($r['tanggal'])) ?></div>
                                            <div class="fw-bold small"><?= htmlspecialchars($r['keterangan']) ?></div>
                                        </div>
                                    <?php endwhile; else: ?><p class="small text-muted">Belum ada data.</p><?php endif; ?>
                                </div>
                                <div class="col-md-6 ps-4">
                                    <h6 class="text-danger fw-bold mb-3">Sanksi</h6>
                                    <?php if($punishments->num_rows > 0): while($p = $punishments->fetch_assoc()): ?>
                                        <div class="timeline-item">
                                            <div class="small text-danger fw-bold"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
                                            <div class="fw-bold small"><?= htmlspecialchars($p['keterangan']) ?></div>
                                        </div>
                                    <?php endwhile; else: ?><p class="small text-muted">Tidak ada data.</p><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right -->
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-4">
                    <!-- Documents -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-4">Document Storage</h5>
                        <?php 
                        $docs = [
                            ['label' => 'KTP Pegawai', 'file' => $pegawai['dok_ktp'], 'icon' => 'fa-id-card'],
                            ['label' => 'Kartu Keluarga', 'file' => $pegawai['dok_kk'], 'icon' => 'fa-users'],
                            ['label' => 'SK Status Pegawai', 'file' => $pegawai['dok_status_pegawai'], 'icon' => 'fa-file-signature'],
                            ['label' => 'SK Pemberhentian', 'file' => $pegawai['dok_tmtk'], 'icon' => 'fa-file-excel'],
                        ];
                        foreach($docs as $doc): if(!empty($doc['file'])):
                        ?>
                        <a href="<?= $doc['file'] ?>" target="_blank" class="vault-item">
                            <div class="vault-icon"><i class="fas <?= $doc['icon'] ?>"></i></div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold text-dark"><?= $doc['label'] ?></div>
                                <div class="text-muted small">Verified File</div>
                            </div>
                            <i class="fas fa-external-link-alt small text-muted"></i>
                        </a>
                        <?php endif; endforeach; ?>
                    </div>

                    <!-- Career Path -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-4">Riwayat Penugasan</h5>
                        <?php if(!empty($status_riwayats)): foreach($status_riwayats as $sr): ?>
                            <div class="p-3 border rounded-4 mb-2">
                                <div class="fw-bold small"><?= htmlspecialchars($sr['status_pegawai']) ?></div>
                                <div class="small text-muted">TMT: <?= date('d/m/Y', strtotime($sr['tmt_mulai_kerja'])) ?></div>
                                <?php if(!empty($sr['dokumen'])): ?>
                                    <a href="<?= $sr['dokumen'] ?>" target="_blank" class="text-primary small text-decoration-none d-block mt-1"><i class="fas fa-file-download me-1"></i> Lihat Dokumen</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; else: ?><div class="text-muted small">Tidak ada data riwayat.</div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

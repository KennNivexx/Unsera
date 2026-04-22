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

// Fetch Rewards & Punishments
$rewards = $conn->query("SELECT * FROM reward_pegawai WHERE pegawai_id = $id ORDER BY tanggal DESC");
$punishments = $conn->query("SELECT * FROM punishment_pegawai WHERE pegawai_id = $id ORDER BY tanggal DESC");
$yayasans = $conn->query("SELECT * FROM yayasan_pegawai WHERE pegawai_id = $id ORDER BY tmt DESC")->fetch_all(MYSQLI_ASSOC);
$pendidikans = $conn->query("SELECT * FROM pendidikan_pegawai WHERE pegawai_id = $id ORDER BY tahun_lulus DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch status riwayat - use table if it exists
$status_riwayats = [];
$res_rw = $conn->query("SELECT * FROM status_pegawai_riwayat WHERE pegawai_id = $id ORDER BY tmt DESC, id DESC");
if ($res_rw) {
    $status_riwayats = $res_rw->fetch_all(MYSQLI_ASSOC);
}

$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => 'data_pegawai.php'],
    ['label' => 'Detail Pegawai', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pegawai | <?= htmlspecialchars($pegawai['nama_lengkap']) ?> | UNSERA</title>
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

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Detail Pegawai</h1>
            <p>Detail informasi personal dan kepegawaian.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="data_pegawai.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="profile-card">
        <div class="profile-avatar">
            <?php if(!empty($pegawai['foto_profil']) && file_exists($pegawai['foto_profil'])): ?>
                <img src="<?= htmlspecialchars($pegawai['foto_profil']) ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($pegawai['nama_lengkap'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div style="flex: 1;">
            <h1 class="academic-title" style="font-size: 2.2rem; margin-bottom: 5px; color: var(--text-main);"><?= htmlspecialchars($pegawai['nama_lengkap']) ?></h1>
            <p style="font-size: 1.1rem; color: var(--text-muted); font-weight: 500;">
                <i class="fas fa-briefcase" style="margin-right: 5px;"></i> <?= htmlspecialchars($pegawai['posisi_jabatan'] ?? '-') ?> &bull; <?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?>
                <br>
                <span style="background: var(--primary-soft); color: var(--primary); padding: 4px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; display: inline-block; margin-top: 10px;">
                    <?= htmlspecialchars($pegawai['status_pegawai'] ?? $pegawai['jenis_pegawai'] ?? 'STAFF') ?>
                </span>
            </p>
        </div>
    </div>

    <div class="info-grid">
    <div class="info-grid">
        <!-- Informasi Personal -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title"><i class="fas fa-id-card"></i> Identitas Pegawai</div>
            <div class="info-row"><div class="info-label">Nama Lengkap</div><div class="info-value"><?= htmlspecialchars($pegawai['nama_lengkap'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">Alamat</div><div class="info-value"><?= htmlspecialchars($pegawai['alamat'] ?? '-') ?></div></div>
            
            <div style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Tempat Lahir</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($pegawai['ttl_tempat'] ?: ($pegawai['ttl'] ?? '-')) ?></div>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Tanggal Lahir</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= ($pegawai['ttl_tanggal'] ?? null) ? date('d F Y', strtotime($pegawai['ttl_tanggal'])) : '-' ?></div>
                </div>
            </div>

            <div class="info-row" style="margin-top: 15px;"><div class="info-label">Status Pernikahan</div><div class="info-value"><?= htmlspecialchars($pegawai['status_pribadi'] ?? '-') ?></div></div>
            
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <?php if(!empty($pegawai['dok_ktp'])): ?>
                    <a href="<?= htmlspecialchars($pegawai['dok_ktp']) ?>" target="_blank" class="btn" style="flex: 1; background: #eff6ff; color: var(--primary); border: 1px solid #dbeafe; font-size: 0.8rem; font-weight: 700; justify-content: center;">
                        <i class="fas fa-id-card"></i> Dokumen KTP
                    </a>
                <?php endif; ?>
                <?php if(!empty($pegawai['dok_kk'])): ?>
                    <a href="<?= htmlspecialchars($pegawai['dok_kk']) ?>" target="_blank" class="btn" style="flex: 1; background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; font-size: 0.8rem; font-weight: 700; justify-content: center;">
                        <i class="fas fa-users"></i> Dokumen KK
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informasi Kepegawaian -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title"><i class="fas fa-briefcase"></i> Status Kepegawaian</div>
            <div class="info-row">
                <div class="info-label">Status Saat Ini</div>
                <div class="info-value">
                    <span class="badge badge-primary" style="font-size: 0.85rem; padding: 6px 16px;">
                        <?= htmlspecialchars($pegawai['status_pegawai'] ?? $pegawai['jenis_pegawai'] ?? '-') ?>
                    </span>
                </div>
            </div>
            <div class="info-row"><div class="info-label">Jabatan</div><div class="info-value"><?= htmlspecialchars($pegawai['posisi_jabatan'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">Unit Kerja</div><div class="info-value"><?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?></div></div>
            <div class="info-row"><div class="info-label">TMK (Mulai Kerja)</div><div class="info-value"><?= $pegawai['tmt_mulai_kerja'] ? date('d F Y', strtotime($pegawai['tmt_mulai_kerja'])) : '-' ?></div></div>
            
            <?php if(!empty($pegawai['tmt_tidak_kerja'])): ?>
            <div class="info-row" style="background: #fff1f2; margin: 10px -15px; padding: 15px; border-radius: 12px; border: 1px solid #fecaca;">
                <div class="info-label" style="color: #b91c1c;">TMTK (Berhenti)</div>
                <div class="info-value" style="color: #b91c1c;">
                    <?= date('d F Y', strtotime($pegawai['tmt_tidak_kerja'])) ?>
                    <div style="font-size: 0.8rem; font-weight: 500; margin-top: 4px;">Alasan: <?= htmlspecialchars($pegawai['ket_tidak_kerja'] ?? '-') ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top: 30px;">
                <h4 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Riwayat Penugasan & Status</h4>
                <?php if(count($status_riwayats) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach($status_riwayats as $idx => $sr): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: <?= $idx === 0 ? '#f0f9ff' : '#f8fafc' ?>; border-radius: 12px; border-left: 4px solid <?= $idx === 0 ? 'var(--primary)' : '#e2e8f0' ?>;">
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($sr['status_pegawai'] ?? '-') ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Terhitung: <?= isset($sr['tmt']) ? date('d/m/Y', strtotime($sr['tmt'])) : '-' ?></div>
                                </div>
                                <?php if(!empty($sr['dokumen'])): ?>
                                    <a href="<?= $sr['dokumen'] ?>" target="_blank" class="btn-icon" style="color: var(--primary); background: white; width: 32px; height: 32px; border-radius: 8px; box-shadow: var(--shadow-sm);"><i class="fas fa-file-pdf"></i></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; color: #94a3b8; font-size: 0.85rem;">Belum ada riwayat status tercatat.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kualifikasi Akademik -->
    <div class="card" style="margin-top:24px; padding: 35px;">
        <div class="info-section-title"><i class="fas fa-graduation-cap"></i> Kualifikasi Akademik</div>
        <?php if(count($pendidikans) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach($pendidikans as $pend): ?>
                <div class="doc-card" style="border-color: #cbd5e1;">
                    <div class="doc-icon"><i class="fas fa-university"></i></div>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; color: var(--text-main); font-size: 1.1rem;"><?= htmlspecialchars($pend['jenjang']) ?></div>
                        <div style="font-weight: 600; color: #64748b;"><?= htmlspecialchars($pend['institusi']) ?></div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; margin-top: 5px;">Lulus: <?= htmlspecialchars($pend['tahun_lulus']) ?></div>
                    </div>
                    <?php if($pend['dokumen']): ?>
                        <a href="<?= $pend['dokumen'] ?>" target="_blank" class="btn" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 12px; font-size: 0.75rem;"><i class="fas fa-file-alt"></i> IJAZAH</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: #f1f5f9; padding: 25px; border-radius: 20px; border: 1.5px solid #e2e8f0;">
                <div style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Pendidikan Terakhir</div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-main); margin-top: 5px;"><?= htmlspecialchars($pegawai['riwayat_pendidikan'] ?? '-') ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dokumen Pendukung -->
    <div class="card" style="margin-bottom: 24px;">
        <h3>Dokumen Pendukung</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">

            <!-- Foto Profil (downloadable) -->
            <div class="doc-card">
                <span class="doc-label">Foto Profil</span>
                <?php if(!empty($pegawai['foto_profil']) && file_exists($pegawai['foto_profil'])): ?>
                    <a href="<?= htmlspecialchars($pegawai['foto_profil']) ?>" download class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-download"></i> Download Foto
                    </a>
                <?php else: ?>
                    <span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span>
                <?php endif; ?>
            </div>

            <!-- Dokumen Status Pegawai -->
            <div class="doc-card">
                <span class="doc-label">Dokumen Status Pegawai</span>
                <?php if(!empty($pegawai['dok_status_pegawai'])): ?>
                    <a href="<?= htmlspecialchars($pegawai['dok_status_pegawai']) ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>

            <!-- Dokumen SK Pemberhentian -->
            <?php if(!empty($pegawai['tmt_tidak_kerja'])): ?>
            <div class="doc-card">
                <span class="doc-label">Dokumen SK Pemberhentian</span>
                <?php if(!empty($pegawai['dok_tmtk'])): ?>
                    <a href="dokumen/<?= $pegawai['dok_tmtk'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Timeline Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px; margin-top: 30px; margin-bottom: 50px;">
        <!-- Rewards -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title" style="color: var(--success); border-bottom-color: #dcfce7;"><i class="fas fa-medal"></i> Penghargaan & Prestasi</div>
            <?php if($rewards && $rewards->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php while($r = $rewards->fetch_assoc()): ?>
                    <div style="padding: 20px; background: #f0fdf4; border-radius: 15px; border-left: 5px solid #22c55e;">
                        <div style="font-size: 0.75rem; color: #16a34a; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;"><?= date('d M Y', strtotime($r['tanggal'])) ?></div>
                        <div style="font-weight: 700; color: #166534; font-size: 1.05rem;"><?= htmlspecialchars($r['keterangan']) ?></div>
                        <?php if(!empty($r['dokumen'])): ?>
                            <a href="dokumen/<?= $r['dokumen'] ?>" target="_blank" class="btn" style="margin-top: 15px; background: white; color: #16a34a; border: 1px solid #bbf7d0; font-size: 0.75rem;"><i class="fas fa-file-alt"></i> Lihat Bukti</a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; border: 2px dashed #f1f5f9; border-radius: 20px; color: #94a3b8; font-weight: 600;">Belum ada catatan penghargaan.</div>
            <?php endif; ?>
        </div>

        <!-- Punishments -->
        <div class="card" style="padding: 35px;">
            <div class="info-section-title" style="color: var(--danger); border-bottom-color: #fee2e2;"><i class="fas fa-gavel"></i> Sanksi & Kedisiplinan</div>
            <?php if($punishments && $punishments->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php while($p = $punishments->fetch_assoc()): ?>
                    <div style="padding: 20px; background: #fef2f2; border-radius: 15px; border-left: 5px solid #ef4444;">
                        <div style="font-size: 0.75rem; color: #dc2626; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
                        <div style="font-weight: 700; color: #991b1b; font-size: 1.05rem;"><?= htmlspecialchars($p['keterangan']) ?></div>
                        <?php if(!empty($p['dokumen'])): ?>
                            <a href="dokumen/<?= $p['dokumen'] ?>" target="_blank" class="btn" style="margin-top: 15px; background: white; color: #dc2626; border: 1px solid #fecaca; font-size: 0.75rem;"><i class="fas fa-file-alt"></i> Lihat Bukti</a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; border: 2px dashed #f1f5f9; border-radius: 20px; color: #94a3b8; font-weight: 600;">Bersih dari catatan sanksi.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'components/file_viewer.php'; ?>

</body>
</html>

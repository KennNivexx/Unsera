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
            gap: 30px;
            padding: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .profile-card::after {
            content: '';
            position: absolute;
            right: -50px;
            bottom: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: 800;
            box-shadow: var(--shadow-lg);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }
        .info-item {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-item:last-child { border-bottom: none; }
        .info-label {
            width: 180px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
        }
        .info-value {
            flex: 1;
            color: var(--text-main);
            font-weight: 600;
        }
        .doc-card {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 20px;
            background: #f8fafc;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .doc-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .doc-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
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
        <div class="profile-avatar">
            <?php if(!empty($d['foto_profil']) && file_exists($d['foto_profil'])): ?>
                <img src="<?= htmlspecialchars($d['foto_profil']) ?>" alt="Foto" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($d['nama_lengkap'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div style="flex: 1;">
            <h1 style="font-size: 2.2rem; margin-bottom: 5px;"><?= htmlspecialchars($d['nama_lengkap']) ?></h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">
                <?= htmlspecialchars($d['unit_kerja'] ?? '-') ?> &bull; 
                <span style="background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                    <?= htmlspecialchars($d['status_dosen'] ?? '-') ?>
                </span>
            </p>
        </div>
    </div>

    <div class="info-grid">
        <div class="card">
            <h3>Informasi Pribadi</h3>
            <div class="info-item"><div class="info-label">Alamat</div><div class="info-value"><?= htmlspecialchars($d['alamat'] ?? '-') ?></div></div>
            <div class="info-item"><div class="info-label">Tempat Lahir</div><div class="info-value"><?= htmlspecialchars($d['ttl_tempat'] ?? '-') ?></div></div>
            <div class="info-item"><div class="info-label">Tanggal Lahir</div><div class="info-value"><?= $d['ttl_tanggal'] ? date('d F Y', strtotime($d['ttl_tanggal'])) : '-' ?></div></div>
            <div class="info-item"><div class="info-label">Status Pernikahan</div><div class="info-value"><?= htmlspecialchars($d['status_pribadi'] ?? '-') ?></div></div>
            <div class="info-item">
                <div class="info-label">Dokumen KTP</div>
                <div class="info-value">
                    <?php if(!empty($d['dok_ktp'])): ?>
                        <a href="<?= $d['dok_ktp'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-id-card"></i> Lihat KTP
                        </a>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">Belum diunggah</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Dokumen KK</div>
                <div class="info-value">
                    <?php if(!empty($d['dok_kk'])): ?>
                        <a href="<?= $d['dok_kk'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-users"></i> Lihat KK
                        </a>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">Belum diunggah</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Status Kepegawaian</h3>
            <div class="info-item"><div class="info-label">Jenis Dosen</div><div class="info-value"><?= htmlspecialchars($d['jenis_dosen'] ?? '-') ?></div></div>
            <div class="info-item"><div class="info-label">Jabatan Struktural</div><div class="info-value"><?= htmlspecialchars($d['jabatan_struktural'] ?? '-') ?></div></div>
            <div class="info-item"><div class="info-label">Mulai Kerja (TMK)</div><div class="info-value"><?= $d['tmk'] ? date('d F Y', strtotime($d['tmk'])) : '-' ?></div></div>
            <div class="info-item"><div class="info-label">Homebase Prodi</div><div class="info-value"><?= htmlspecialchars($d['homebase_prodi'] ?? '-') ?></div></div>
        </div>

        <div class="card" style="grid-column: 1 / -1;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                <!-- Jabfung History -->
                <div>
                    <h3><i class="fas fa-award"></i> Riwayat Jabfung</h3>
                    <?php if(count($jabfungs) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach($jabfungs as $jf): ?>
                            <div style="padding: 16px; background: #eff6ff; border-left: 4px solid var(--primary); border-radius: var(--radius-md);">
                                <div style="font-weight: 600;"><?= htmlspecialchars($jf['jabatan']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">TMT: <?= $jf['tmt'] ? date('d/m/Y', strtotime($jf['tmt'])) : '-' ?></div>
                                <?php if($jf['dokumen']): ?>
                                    <a href="<?= $jf['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 10px;"><i class="fas fa-file-pdf"></i> Lihat SK</a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); font-size:0.9rem;">Belum ada riwayat Jabfung.</p>
                    <?php endif; ?>
                </div>

                <!-- LLDIKTI History -->
                <div>
                    <h3><i class="fas fa-university"></i> Riwayat LLDIKTI</h3>
                    <?php if(count($lldiktis) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach($lldiktis as $ld): ?>
                            <div style="padding: 16px; background: #eff6ff; border-left: 4px solid var(--primary); border-radius: var(--radius-md);">
                                <div style="font-weight: 600;">Golongan: <?= htmlspecialchars($ld['golongan']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">TMT: <?= $ld['tmt'] ? date('d/m/Y', strtotime($ld['tmt'])) : '-' ?></div>
                                <?php if($ld['dokumen']): ?>
                                    <a href="<?= $ld['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 10px;"><i class="fas fa-file-pdf"></i> Lihat SK</a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); font-size:0.9rem;">Belum ada riwayat LLDIKTI.</p>
                    <?php endif; ?>
                </div>

                <!-- Yayasan History -->
                <div>
                    <h3><i class="fas fa-building"></i> Riwayat Yayasan</h3>
                    <?php if(count($yayasans) > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach($yayasans as $yy): ?>
                            <div style="padding: 16px; background: #eff6ff; border-left: 4px solid var(--primary); border-radius: var(--radius-md);">
                                <div style="font-weight: 600;">Golongan: <?= htmlspecialchars($yy['golongan']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">TMT: <?= $yy['tmt'] ? date('d/m/Y', strtotime($yy['tmt'])) : '-' ?></div>
                                <?php if($yy['dokumen']): ?>
                                    <a href="<?= $yy['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 10px;"><i class="fas fa-file-pdf"></i> Lihat SK</a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); font-size:0.9rem;">Belum ada riwayat Yayasan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <h3><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</h3>
            <?php if(count($pendidikans) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                    <?php foreach($pendidikans as $pend): ?>
                    <div style="padding: 16px; background: #eff6ff; border-left: 4px solid var(--primary); border-radius: var(--radius-md);">
                        <div style="font-weight: 600;"><?= htmlspecialchars($pend['jenjang']) ?> - <?= htmlspecialchars($pend['institusi']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">Tahun Lulus: <?= htmlspecialchars($pend['tahun_lulus']) ?></div>
                        <?php if($pend['dokumen']): ?>
                            <a href="<?= $pend['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 10px;"><i class="fas fa-file-pdf"></i> Lihat Ijazah</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="padding: 16px; background: #eff6ff; border-left: 4px solid var(--primary); border-radius: var(--radius-md);">
                    <div style="font-weight: 600;">Pendidikan Terakhir</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;"><?= htmlspecialchars($d['riwayat_pendidikan'] ?? '-') ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Sertifikasi Dosen</h3>
            <div class="info-item"><div class="info-label">No. Serdos</div><div class="info-value"><?= htmlspecialchars($d['no_serdos'] ?? '-') ?></div></div>
        </div>
    </div>

    <div class="card">
        <h3>Dokumen Pendukung Lainnya</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="doc-card">
                <span class="doc-label">Dokumen Berhenti</span>
                <?php if($d['dok_tidak_kerja']): ?>
                    <a href="<?= $d['dok_tidak_kerja'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>
            <div class="doc-card">
                <span class="doc-label">Dokumen Serdos</span>
                <?php if($d['dok_serdos']): ?>
                    <a href="<?= $d['dok_serdos'] ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fas fa-file-pdf"></i> Lihat File</a>
                <?php else: ?><span style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tidak Tersedia</span><?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card">
            <h3 style="color: var(--success); --primary: var(--success);">Rewards</h3>
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
            <h3 style="color: var(--danger); --primary: var(--danger);">Punishments</h3>
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

<?php include 'components/file_viewer.php'; ?>

<script>
document.querySelectorAll('.submenu-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        this.classList.toggle('open');
        this.nextElementSibling.classList.toggle('show');
    });
});
</script>

</body>
</html>



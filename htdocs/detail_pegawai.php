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
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1>Profil Staf</h1>
            <p>Detail informasi kepegawaian dan riwayat profesional.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="data_pegawai.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Main Profile Card -->
    <div class="profile-card">
        <div class="profile-avatar">
            <?php if(!empty($pegawai['foto_profil']) && file_exists($pegawai['foto_profil'])): ?>
                <img src="<?= htmlspecialchars($pegawai['foto_profil']) ?>" alt="Foto" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($pegawai['nama_lengkap'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div style="flex: 1;">
            <h1 style="font-size: 2.2rem; margin-bottom: 5px;"><?= htmlspecialchars($pegawai['nama_lengkap']) ?></h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">
                <i class="fas fa-briefcase" style="margin-right: 5px;"></i> <?= htmlspecialchars($pegawai['posisi_jabatan'] ?? '-') ?> &bull; <?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?>
                <br>
                <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-top: 10px;">
                    <?= htmlspecialchars($pegawai['jenis_pegawai'] ?? 'STAFF') ?>
                </span>
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 30px;">
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <!-- Informasi Dasar -->
            <div style="background: #f8fafc; padding: 25px; border-radius: var(--radius-md); border: 1px solid #e2e8f0;">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-id-card"></i> Informasi Personal
                </h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px 0; color: var(--text-muted); font-size: 0.9rem;">Tempat/Tgl Lahir</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($pegawai['ttl'] ?? '-') ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px 0; color: var(--text-muted); font-size: 0.9rem;">Status Pernikahan</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($pegawai['status_pribadi'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; color: var(--text-muted); font-size: 0.9rem;">Alamat</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 600; color: var(--text-main); font-size: 0.85rem; line-height: 1.4; max-width: 200px;"><?= htmlspecialchars($pegawai['alamat'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>

            <!-- Karir -->
            <div style="background: #f8fafc; padding: 25px; border-radius: var(--radius-md); border: 1px solid #e2e8f0;">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-briefcase"></i> Karir
                </h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 12px 0; color: var(--text-muted); font-size: 0.9rem;">TMT Mulai Kerja</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 600; color: var(--text-main);"><?= $pegawai['tmt_mulai_kerja'] ? date('d M Y', strtotime($pegawai['tmt_mulai_kerja'])) : '-' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Employment Info -->
        <div class="card" style="margin-top: 25px; margin-bottom: 25px;">
            <h3><i class="fas fa-briefcase"></i> Informasi Kepegawaian</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div class="info-item"><div class="info-label">Unit Kerja</div><div class="info-value"><?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?></div></div>
            </div>
        </div>

        <!-- Pendidikan Info -->
        <div class="card" style="margin-bottom: 25px;">
            <h3><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</h3>
            <?php if(count($pendidikans) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
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
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;"><?= htmlspecialchars($pegawai['riwayat_pendidikan'] ?? '-') ?></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if(!empty($pegawai['tmt_tidak_kerja'])): ?>
        <!-- Area Pemberhentian (Jika Ada) -->
        <div style="margin-top: 30px; background: #fff5f5; border: 1px solid #feb2b2; border-radius: var(--radius-md); padding: 25px;">
            <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1rem; color: var(--danger); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Informasi Pemberhentian
            </h3>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-size: 0.85rem; color: #7f1d1d; margin-bottom: 5px;">Alasan Berhenti:</div>
                    <div style="font-weight: 600; color: #7f1d1d;"><?= htmlspecialchars($pegawai['ket_tidak_kerja'] ?? 'Tidak disebutkan') ?></div>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-size: 0.85rem; color: #7f1d1d; margin-bottom: 5px;">Tanggal Berhenti:</div>
                    <div style="font-weight: 600; color: #7f1d1d;"><?= date('d M Y', strtotime($pegawai['tmt_tidak_kerja'])) ?></div>
                </div>
                <?php if(!empty($pegawai['dok_tmtk'])): ?>
                <div>
                    <a href="dokumen/<?= $pegawai['dok_tmtk'] ?>" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Lihat SK Pemberhentian
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Riwayat Reward & Punishment -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; margin-bottom: 50px;">
        <!-- Rewards -->
        <div class="card">
            <h3 style="margin-top: 0; margin-bottom: 25px; color: var(--success); --primary: var(--success); display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                <i class="fas fa-medal"></i> Penghargaan & Prestasi
            </h3>
            <div style="position: relative; padding-left: 20px; border-left: 2px solid #f1f5f9;">
                <?php if($rewards && $rewards->num_rows > 0): while($r = $rewards->fetch_assoc()): ?>
                <div style="margin-bottom: 30px; position: relative;">
                    <div style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--success); border: 2px solid #fff;"></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-bottom: 5px;"><?= date('d M Y', strtotime($r['tanggal'])) ?></div>
                    <div style="font-weight: 600; color: var(--text-main); margin-bottom: 10px; line-height: 1.5;"><?= htmlspecialchars($r['keterangan']) ?></div>
                    <?php if($r['dokumen']): ?>
                        <a href="dokumen/<?= $r['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 6px 12px;">
                            <i class="fas fa-file-alt"></i> Lihat Dokumen
                        </a>
                    <?php endif; ?>
                </div>
                <?php endwhile; else: ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-muted); font-style: italic;">Belum ada data penghargaan.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Punishments -->
        <div class="card">
            <h3 style="margin-top: 0; margin-bottom: 25px; color: var(--danger); --primary: var(--danger); display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                <i class="fas fa-gavel"></i> Sanksi & Catatan Kedisiplinan
            </h3>
            <div style="position: relative; padding-left: 20px; border-left: 2px solid #f1f5f9;">
                <?php if($punishments && $punishments->num_rows > 0): while($p = $punishments->fetch_assoc()): ?>
                <div style="margin-bottom: 30px; position: relative;">
                    <div style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--danger); border: 2px solid #fff;"></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-bottom: 5px;"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
                    <div style="font-weight: 600; color: var(--text-main); margin-bottom: 10px; line-height: 1.5;"><?= htmlspecialchars($p['keterangan']) ?></div>
                    <?php if($p['dokumen']): ?>
                        <a href="dokumen/<?= $p['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 6px 12px; color: var(--danger); border-color: var(--danger);">
                            <i class="fas fa-file-alt"></i> Lihat Dokumen
                        </a>
                    <?php endif; ?>
                </div>
                <?php endwhile; else: ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-muted); font-style: italic;">Belum ada data sanksi/pelanggaran.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'components/file_viewer.php'; ?>

</body>
</html>

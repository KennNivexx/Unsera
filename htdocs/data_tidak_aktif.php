<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? 'dosen'; // 'dosen' or 'pegawai'

if ($type == 'dosen') {
    $title = "Dosen Tidak Aktif";
    $query = "SELECT id, nama_lengkap, nip, nidn, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja, foto_profil 
              FROM dosen WHERE status_keaktifan = 'Tidak Aktif' OR status_dosen = 'Tidak Aktif' ORDER BY tgl_mulai_tidak_bekerja DESC";
} else {
    $title = "Pegawai Tidak Aktif";
    $query = "SELECT id, nama_lengkap, posisi_jabatan, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja as tmtk, tmt_tidak_kerja, foto_profil 
              FROM pegawai WHERE status_keaktifan = 'Tidak Aktif' OR tmt_tidak_kerja IS NOT NULL ORDER BY tmt_tidak_kerja DESC";
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .view-switcher {
            display: flex; gap: 15px; margin-bottom: 30px; background: #f1f5f9; padding: 6px; border-radius: 12px; width: fit-content;
        }
        .view-btn {
            padding: 10px 24px; border-radius: 8px; text-decoration: none; color: #64748b; font-weight: 600; transition: all 0.2s;
        }
        .view-btn.active {
            background: white; color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section">
        <h1><i class="fas fa-user-slash" style="margin-right:12px; color:var(--danger);"></i>Data Tenaga Tidak Aktif</h1>
        <p>Arsip data dosen dan pegawai yang sudah tidak aktif atau berhenti bekerja di UNSERA.</p>
    </div>

    <div class="view-switcher">
        <a href="?type=dosen" class="view-btn <?= $type == 'dosen' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher" style="margin-right:8px;"></i> Dosen</a>
        <a href="?type=pegawai" class="view-btn <?= $type == 'pegawai' ? 'active' : '' ?>"><i class="fas fa-users-cog" style="margin-right:8px;"></i> Pegawai</a>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Identitas</th>
                        <th>Nama Lengkap</th>
                        <th><?= $type == 'dosen' ? 'NIP / NIDN' : 'Jabatan & Unit' ?></th>
                        <th>Tanggal Berhenti</th>
                        <th>Status / Alasan</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $initials = strtoupper(substr($row['nama_lengkap'], 0, 1));
                        ?>
                        <tr>
                            <td>
                                <div class="name-cell">
                                    <?php if(!empty($row['foto_profil'])): ?>
                                        <img src="<?= htmlspecialchars($row['foto_profil']) ?>" alt="Foto" style="width: 45px; height: 45px; border-radius: 12px; object-fit: cover; border: 2px solid #f1f5f9;">
                                    <?php else: ?>
                                        <div style="width: 45px; height: 45px; background: #f1f5f9; color: #94a3b8; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: 'Outfit';"><?= $initials ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-main); font-family: 'Outfit'; font-size: 1.05rem;">
                                    <?= htmlspecialchars($row['nama_lengkap']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">ID: <?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            </td>
                            <td>
                                <?php if($type == 'dosen'): ?>
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($row['nip'] ?: '-') ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">NIDN: <?= htmlspecialchars($row['nidn'] ?: '-') ?></div>
                                <?php else: ?>
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($row['posisi_jabatan'] ?: '-') ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;"><?= htmlspecialchars($row['unit_kerja'] ?? '-') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                                    <i class="far fa-calendar-times" style="color: #ef4444;"></i>
                                    <?php 
                                        $date = ($type == 'dosen' ? $row['tgl_mulai_tidak_bekerja'] : ($row['tmt_tidak_kerja'] ?: $row['tmtk']));
                                        echo $date ? date('d M Y', strtotime($date)) : '-';
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: #fff1f2; color: #e11d48; font-weight: 800; border: 1px solid #fee2e2; padding: 6px 14px; font-size: 0.75rem;">
                                    <?= htmlspecialchars($row['keterangan_keaktifan'] ?: 'PEMBERHENTIAN') ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?= $type=='dosen' ? 'detail_dosen.php' : 'detail_pegawai.php' ?>?id=<?= $row['id'] ?>" class="btn" style="padding: 8px 16px; font-size: 0.75rem; font-weight: 700; background: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-main); border-radius: 10px;">
                                    <i class="fas fa-file-invoice"></i> Lihat Arsip
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state" style="padding: 80px 0;">
                                    <div style="width: 100px; height: 100px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                                        <i class="fas fa-folder-open" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                    </div>
                                    <h4 style="font-family: 'Outfit'; font-size: 1.5rem; color: var(--text-main); font-weight: 800; letter-spacing: -0.5px;">Arsip Masih Kosong</h4>
                                    <p style="color: var(--text-muted); max-width: 400px; margin: 10px auto 0; font-size: 1rem;">Tidak ditemukan data <?= $type ?> dalam daftar pemberhentian atau tidak aktif.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

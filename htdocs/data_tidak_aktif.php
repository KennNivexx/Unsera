<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? 'dosen'; // 'dosen' or 'pegawai'

if ($type == 'dosen') {
    $title = "Arsip Dosen";
    $query = "SELECT id, nama_lengkap, nip, nidn, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja, foto_profil 
              FROM dosen WHERE status_keaktifan = 'Tidak Aktif' OR status_dosen = 'Tidak Aktif' ORDER BY tgl_mulai_tidak_bekerja DESC";
} else {
    $title = "Arsip Pegawai";
    $query = "SELECT id, nama_lengkap, posisi_jabatan, unit_kerja, status_keaktifan, keterangan_keaktifan, tgl_mulai_tidak_bekerja as tmtk, tmt_tidak_kerja, foto_profil 
              FROM pegawai WHERE status_keaktifan = 'Tidak Aktif' OR tmt_tidak_kerja IS NOT NULL ORDER BY tmt_tidak_kerja DESC";
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | UNSERA Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .archive-switcher {
            background: white; border-radius: 50px; padding: 6px;
            display: inline-flex; border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }
        .archive-btn {
            padding: 10px 30px; border-radius: 50px; text-decoration: none;
            color: #64748b; font-weight: 700; font-size: 0.9rem;
            transition: all 0.3s;
        }
        .archive-btn.active {
            background: var(--primary); color: white;
            box-shadow: 0 4px 12px rgba(37,99,235,0.2);
        }
        .avatar-box {
            width: 45px; height: 45px; border-radius: 12px; overflow: hidden;
            background: #f1f5f9; display: flex; align-items: center; justify-content: center;
        }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">Arsip Tenaga Tidak Aktif</h2>
                <p class="text-muted">Manajemen data historis tenaga pendidik dan kependidikan yang sudah tidak aktif.</p>
            </div>
            <div class="archive-switcher">
                <a href="?type=dosen" class="archive-btn <?= $type == 'dosen' ? 'active' : '' ?>">Dosen</a>
                <a href="?type=pegawai" class="archive-btn <?= $type == 'pegawai' ? 'active' : '' ?>">Pegawai</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Personel</th>
                            <th>Identitas</th>
                            <th>Tanggal Berhenti</th>
                            <th>Status Keaktifan</th>
                            <th class="text-end pe-4">Arsip</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                            $initials = strtoupper(substr($row['nama_lengkap'], 0, 1));
                            $date = ($type == 'dosen' ? $row['tgl_mulai_tidak_bekerja'] : ($row['tmt_tidak_kerja'] ?: ($row['tmtk'] ?? null)));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-box">
                                        <?php if($row['foto_profil']): ?>
                                            <img src="<?= htmlspecialchars($row['foto_profil']) ?>">
                                        <?php else: ?>
                                            <span class="fw-bold text-muted"><?= $initials ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <div class="small text-muted">ID: <?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if($type == 'dosen'): ?>
                                    <div class="fw-bold text-secondary"><?= htmlspecialchars($row['nip'] ?: '-') ?></div>
                                    <div class="small text-muted">NIDN: <?= htmlspecialchars($row['nidn'] ?: '-') ?></div>
                                <?php else: ?>
                                    <div class="fw-bold text-secondary"><?= htmlspecialchars($row['posisi_jabatan'] ?: '-') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($row['unit_kerja'] ?? '-') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><div class="fw-bold text-danger"><?= !empty($date) ? date('d M Y', strtotime($date)) : '-' ?></div></td>
                            <td>
                                <span class="badge rounded-pill" style="background: #fff1f2; color: #e11d48; border: 1px solid #fee2e2; padding: 6px 14px;">
                                    <?= htmlspecialchars($row['keterangan_keaktifan'] ?: 'NON-AKTIF') ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= $type=='dosen' ? 'detail_dosen.php' : 'detail_pegawai.php' ?>?id=<?= $row['id'] ?>" class="btn btn-sm btn-light rounded-pill border px-3">
                                    <i class="fas fa-folder-open me-1"></i> Lihat Data
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data arsip <?= $type ?> ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

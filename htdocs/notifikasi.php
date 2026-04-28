<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all activities (combined dosen and pegawai)
$dosen = $conn->query("SELECT id, nama_lengkap, 'Dosen' as tipe FROM dosen ORDER BY id DESC LIMIT 50");
$pegawai = $conn->query("SELECT id, nama_lengkap, 'Pegawai' as tipe FROM pegawai ORDER BY id DESC LIMIT 50");

$all = [];
while($r = $dosen->fetch_assoc()) $all[] = $r;
while($r = $pegawai->fetch_assoc()) $all[] = $r;

// Sort by ID DESC (rough approximation of chronological order)
usort($all, function($a, $b) {
    return $b['id'] - $a['id'];
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Notifikasi | UNSERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notif-card {
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            border-radius: 16px;
        }
        .notif-card:hover {
            background-color: #f8fafc;
            transform: translateX(5px);
            border-color: var(--primary);
        }
        .icon-circle {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="bg-light">

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="mb-4">
            <h2 class="fw-bold text-dark mb-1">Pusat Notifikasi</h2>
            <p class="text-secondary">Daftar aktivitas terbaru di sistem kepegawaian UNSERA.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Aktivitas Terbaru</h5>
                            <span class="badge bg-primary rounded-pill"><?= count($all) ?> Entri</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($all)): ?>
                            <div class="p-5 text-center">
                                <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                                <p class="text-muted">Belum ada aktivitas yang tercatat.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($all as $item): 
                                    $link = ($item['tipe'] == 'Dosen') ? "detail_dosen.php?id=".$item['id'] : "detail_pegawai.php?id=".$item['id'];
                                    $color = ($item['tipe'] == 'Dosen') ? 'primary' : 'success';
                                    $icon = ($item['tipe'] == 'Dosen') ? 'fa-user-graduate' : 'fa-user-tie';
                                ?>
                                <a href="<?= $link ?>" class="list-group-item list-group-item-action p-4 notif-card border-bottom">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="icon-circle bg-<?= $color ?>-subtle text-<?= $color ?> rounded-circle">
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between mb-1">
                                                <h6 class="mb-0 fw-bold"><?= $item['tipe'] ?> Baru Terdaftar</h6>
                                                <small class="text-muted">Baru saja</small>
                                            </div>
                                            <p class="text-secondary small mb-0">
                                                <strong><?= htmlspecialchars($item['nama_lengkap']) ?></strong> telah berhasil ditambahkan ke database <?= strtolower($item['tipe']) ?> UNSERA.
                                            </p>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-chevron-right"></i>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light py-3 text-center rounded-bottom-4">
                        <small class="text-muted italic">Menampilkan maksimal 100 aktivitas terbaru.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

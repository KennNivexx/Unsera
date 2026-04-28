<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    // Delete rewards
    $stmt = $conn->prepare("DELETE FROM reward WHERE dosen_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Delete punishments
    $stmt = $conn->prepare("DELETE FROM punishment WHERE dosen_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Delete dosen record and its uploaded files
    $stmt = $conn->prepare("SELECT dok_jabfung, dok_gol_lldikti, dok_gol_yayasan, dok_tidak_kerja, dok_serdos FROM dosen WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $files = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($files) {
        foreach ($files as $file) {
            if ($file && file_exists($file)) {
                @unlink($file);
            }
        }
    }

    $stmt = $conn->prepare("DELETE FROM dosen WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Data dosen berhasil dihapus!');location='hapus_dosen.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data.');location='hapus_dosen.php';</script>";
    }
    $stmt->close();
} else {
    // Tampilan Antarmuka Hapus Dosen
    $data = $conn->query("SELECT * FROM dosen ORDER BY id DESC");
    
    $breadcrumbs = [
        ['label' => 'Data Dosen', 'url' => 'daftar_dosen.php'],
        ['label' => 'Hapus Data Dosen', 'url' => '#']
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Data Dosen | UNSERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section">
        <h1>Manajemen Hapus Dosen</h1>
        <p>Pilih data dosen yang ingin Anda hapus secara permanen dari sistem.</p>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th width="80">No</th>
                    <th>Nama Lengkap</th>
                    <th>Alamat / Domisili</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = $data->fetch_assoc()): 
                ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td>
                        <div style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($row['alamat']) ?></td>
                    <td class="text-center">
                        <a href="hapus_dosen.php?id=<?= $row['id'] ?>" class="btn-icon" style="color: var(--danger); font-size: 1.2rem;" title="Hapus Data" onclick="return confirm('Apakah Anda yakin ingin menghapus data <?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>?')">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
} 
?>

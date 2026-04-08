<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$data = $conn->query("SELECT * FROM dosen ORDER BY id DESC");

$breadcrumbs = [
    ['label' => 'Data Dosen', 'url' => 'daftar_dosen.php'],
    ['label' => 'Edit Data Dosen', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Dosen | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-badge {
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section">
        <h1>Manajemen Edit Dosen</h1>
        <p>Pilih data dosen yang ingin Anda perbarui informasinya secara detail.</p>
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
                        <a href="form_edit_dosen.php?id=<?= $row['id'] ?>" class="btn-icon" style="color: var(--primary); font-size: 1.2rem;" title="Edit Data">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    // 1. Delete associated files and records from reward_pegawai
    $rewards = $conn->query("SELECT dokumen FROM reward_pegawai WHERE pegawai_id = $id");
    if ($rewards) {
        while ($row = $rewards->fetch_assoc()) {
            if ($row['dokumen'] && file_exists("dokumen/" . $row['dokumen'])) {
                @unlink("dokumen/" . $row['dokumen']);
            }
        }
    }
    $conn->query("DELETE FROM reward_pegawai WHERE pegawai_id = $id");

    // 2. Delete associated files and records from punishment_pegawai
    $punishments = $conn->query("SELECT dokumen FROM punishment_pegawai WHERE pegawai_id = $id");
    if ($punishments) {
        while ($row = $punishments->fetch_assoc()) {
            if ($row['dokumen'] && file_exists("dokumen/" . $row['dokumen'])) {
                @unlink("dokumen/" . $row['dokumen']);
            }
        }
    }
    $conn->query("DELETE FROM punishment_pegawai WHERE pegawai_id = $id");

    // 3. Delete TMTK document from pegawai table
    $pegawai_stmt = $conn->prepare("SELECT dok_tmtk FROM pegawai WHERE id = ?");
    $pegawai_stmt->bind_param("i", $id);
    $pegawai_stmt->execute();
    $pegawai_data = $pegawai_stmt->get_result()->fetch_assoc();
    $pegawai_stmt->close();

    if ($pegawai_data && $pegawai_data['dok_tmtk']) {
        if (file_exists("dokumen/" . $pegawai_data['dok_tmtk'])) {
            @unlink("dokumen/" . $pegawai_data['dok_tmtk']);
        }
    }

    // 4. Delete the main pegawai record
    $stmt = $conn->prepare("DELETE FROM pegawai WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header('Location: hapus_pegawai.php?status=deleted');
    } else {
        echo "<script>alert('Gagal menghapus data.'); location='hapus_pegawai.php';</script>";
    }
    $stmt->close();
} else {
    // Tampilan Antarmuka Hapus Pegawai
    if(isset($_GET['status']) && $_GET['status'] == 'deleted') {
        echo "<script>alert('Data pegawai berhasil dihapus!');location='hapus_pegawai.php';</script>";
    }

    $data = $conn->query("SELECT * FROM pegawai ORDER BY id DESC");
    
    $breadcrumbs = [
        ['label' => 'Data Pegawai', 'url' => 'data_pegawai.php'],
        ['label' => 'Hapus Data Pegawai', 'url' => '#']
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Data Pegawai | UNSERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        :root {
            --unsera-blue: #2563eb;
            --unsera-grey: #f8fafc;
            --unsera-dark: #1e293b;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .page-header { background: white; padding: 2rem; border-radius: 0 0 24px 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .table-card { background: white; border-radius: 24px; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; padding: 1.5rem; }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section">
        <h1>Manajemen Hapus Pegawai</h1>
        <p>Pilih data pegawai yang ingin Anda hapus secara permanen dari sistem.</p>
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
                        <a href="hapus_pegawai.php?id=<?= $row['id'] ?>" class="btn-icon" style="color: var(--danger); font-size: 1.2rem;" title="Hapus Data" onclick="return confirm('Apakah Anda yakin ingin menghapus data <?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>?')">
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

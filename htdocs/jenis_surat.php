<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah' && !empty(trim($_POST['nama_jenis']))) {
        $nama = trim($_POST['nama_jenis']);
        $stmt = $conn->prepare("INSERT INTO jenis_surat (nama_jenis) VALUES (?)");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Jenis surat berhasil ditambahkan!');location='jenis_surat.php';</script>";
        exit;
    }
    if ($_POST['action'] == 'hapus' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM jenis_surat WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Jenis surat berhasil dihapus!');location='jenis_surat.php';</script>";
        exit;
    }
    if ($_POST['action'] == 'edit' && !empty($_POST['id']) && !empty(trim($_POST['nama_jenis']))) {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_jenis']);
        $stmt = $conn->prepare("UPDATE jenis_surat SET nama_jenis = ? WHERE id = ?");
        $stmt->bind_param("si", $nama, $id);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Jenis surat berhasil diperbarui!');location='jenis_surat.php';</script>";
        exit;
    }
}

// Fetch all jenis surat
$result = $conn->query("SELECT js.*, (SELECT COUNT(*) FROM data_surat WHERE jenis_id = js.id) as total_surat FROM jenis_surat js ORDER BY js.id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Surat | UNSERA Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .category-card {
            background: white; border-radius: 24px; border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%; display: flex; flex-direction: column;
            overflow: hidden;
        }
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            border-color: var(--primary);
        }
        .category-icon {
            width: 60px; height: 60px; border-radius: 16px;
            background: rgba(37,99,235,0.1); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 20px;
        }
        .add-category-btn {
            border: 2px dashed #cbd5e1; border-radius: 24px;
            height: 100%; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            transition: all 0.3s; cursor: pointer; color: #64748b;
            min-height: 200px;
        }
        .add-category-btn:hover {
            border-color: var(--primary); background: rgba(37,99,235,0.02);
            color: var(--primary);
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">Kategori Arsip Surat</h2>
                <p class="text-muted">Kelola pengelompokan dokumen dan surat menyurat universitas.</p>
            </div>
            <div class="d-flex align-items-center gap-2 text-muted small">
                <span class="spinner-grow spinner-grow-sm text-success"></span>
                <span>Auto-Sync Active</span>
            </div>
        </div>

        <div class="row g-4" id="categoryGrid">
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-3">
                <div class="category-card p-4">
                    <div class="category-icon"><i class="fas fa-folder-open"></i></div>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($row['nama_jenis']) ?></h5>
                    <p class="text-muted small mb-4"><?= $row['total_surat'] ?> Dokumen Tersimpan</p>
                    
                    <div class="mt-auto d-flex gap-2">
                        <a href="data_surat.php?jenis_id=<?= $row['id'] ?>" class="btn btn-primary flex-grow-1 rounded-pill small fw-bold">
                            BUKA ARSIP
                        </a>
                        <button class="btn btn-light rounded-circle" onclick="editCategory(<?= $row['id'] ?>, '<?= addslashes($row['nama_jenis']) ?>')">
                            <i class="fas fa-pen small"></i>
                        </button>
                        <button class="btn btn-light rounded-circle text-danger" onclick="deleteCategory(<?= $row['id'] ?>)">
                            <i class="fas fa-trash small"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
            <div class="col-md-4 col-lg-3">
                <div class="add-category-btn" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus-circle fs-2 mb-2"></i>
                    <span class="fw-bold">Tambah Kategori</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kategori Surat</label>
                        <input type="text" name="nama_jenis" class="form-control rounded-3" placeholder="Contoh: SK Rektor, Nota Dinas, dll" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Buat Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_cat_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kategori Surat</label>
                        <input type="text" name="nama_jenis" id="edit_cat_nama" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id" id="delete_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editCategory(id, nama) {
        document.getElementById('edit_cat_id').value = id;
        document.getElementById('edit_cat_nama').value = nama;
        new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
    }
    function deleteCategory(id) {
        if(confirm('Hapus kategori ini? Semua arsip di dalamnya akan terhapus secara permanen.')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>
</body>
</html>

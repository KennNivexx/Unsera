<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'tambah' && !empty(trim($_POST['nama_jabatan']))) {
        $jabatan = trim($_POST['nama_jabatan']);
        $parent  = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $pejabat = trim($_POST['nama_pejabat'] ?? '');
        $stmt = $conn->prepare("INSERT INTO struktur_organisasi (nama_jabatan, parent_id, nama_pejabat) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $jabatan, $parent, $pejabat);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Berhasil ditambahkan!');location='struktur_organisasi.php';</script>";
        exit;
    }
    if ($action == 'hapus' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        // Also delete children
        $conn->query("DELETE FROM struktur_organisasi WHERE parent_id = $id");
        $stmt = $conn->prepare("DELETE FROM struktur_organisasi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Berhasil dihapus!');location='struktur_organisasi.php';</script>";
        exit;
    }
    if ($action == 'edit' && !empty($_POST['id']) && !empty(trim($_POST['nama_jabatan']))) {
        $id      = (int)$_POST['id'];
        $jabatan = trim($_POST['nama_jabatan']);
        $pejabat = trim($_POST['nama_pejabat'] ?? '');
        $stmt = $conn->prepare("UPDATE struktur_organisasi SET nama_jabatan = ?, nama_pejabat = ? WHERE id = ?");
        $stmt->bind_param("ssi", $jabatan, $pejabat, $id);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Berhasil diperbarui!');location='struktur_organisasi.php';</script>";
        exit;
    }
    // Update pejabat name inline (AJAX)
    if ($action == 'update_pejabat' && !empty($_POST['id'])) {
        $id      = (int)$_POST['id'];
        $pejabat = trim($_POST['nama_pejabat'] ?? '');
        $stmt = $conn->prepare("UPDATE struktur_organisasi SET nama_pejabat = ? WHERE id = ?");
        $stmt->bind_param("si", $pejabat, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
    // Add sub jabatan via AJAX
    if ($action == 'tambah_sub' && !empty($_POST['parent_id']) && !empty(trim($_POST['nama_jabatan']))) {
        $parent  = (int)$_POST['parent_id'];
        $jabatan = trim($_POST['nama_jabatan']);
        $stmt = $conn->prepare("INSERT INTO struktur_organisasi (nama_jabatan, parent_id) VALUES (?, ?)");
        $stmt->bind_param("si", $jabatan, $parent);
        $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $newId, 'nama_jabatan' => $jabatan]);
        exit;
    }
}

// Fetch all struktur (root = parent_id IS NULL)
$roots = [];
$rootsResult = $conn->query("SELECT * FROM struktur_organisasi WHERE parent_id IS NULL ORDER BY id ASC");
if ($rootsResult) {
    while ($r = $rootsResult->fetch_assoc()) {
        $roots[] = $r;
    }
} else {
    die("Database error: Sedang melakukan sinkronisasi tabel database. Silakan muat ulang (Refresh) halaman ini dalam beberapa saat. Error: " . $conn->error);
}

$allChildren = [];
$childResult = $conn->query("SELECT * FROM struktur_organisasi WHERE parent_id IS NOT NULL ORDER BY id ASC");
if ($childResult) {
    while ($row = $childResult->fetch_assoc()) {
        $allChildren[$row['parent_id']][] = $row;
    }
}

$breadcrumbs = [['label' => 'Struktur Organisasi', 'url' => '#']];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struktur Organisasi | UNSERA Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .org-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            padding: 10px 0;
        }
        .org-card {
            background: white; border-radius: 20px; border: 1px solid #f1f5f9;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02); overflow: hidden;
            transition: all 0.3s ease; display: flex; flex-direction: column;
        }
        .org-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }
        
        .org-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 25px; color: white; position: relative;
        }
        .org-header h3 { font-family: 'Outfit', sans-serif; font-weight: 700; margin: 0; font-size: 1.25rem; color: white; }
        .org-header p { opacity: 0.9; font-size: 0.9rem; margin: 5px 0 0 0; color: white; }
        
        .sub-list { padding: 20px; flex-grow: 1; }
        .sub-item {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 15px; margin-bottom: 12px; display: flex; align-items: center;
            gap: 15px; transition: all 0.2s;
        }
        .sub-item:hover { border-color: var(--primary); background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .sub-icon { width: 35px; height: 35px; border-radius: 8px; background: white; display: flex; align-items: center; justify-content: center; color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        .add-card {
            border: 2px dashed #cbd5e1; background: #f8fafc; display: flex;
            align-items: center; justify-content: center; flex-direction: column;
            border-radius: 20px; min-height: 200px; cursor: pointer; transition: all 0.2s;
        }
        .add-card:hover { border-color: var(--primary); background: #f0f9ff; }
        .add-card i { font-size: 2rem; color: #94a3b8; margin-bottom: 10px; }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="row g-4 mb-4 align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">Struktur Organisasi</h2>
                <p class="text-muted mb-0">Manajemen hierarki jabatan dan penugasan pejabat UNSERA.</p>
            </div>
            <div class="col-lg-6">
                <div class="d-flex gap-3 justify-content-lg-end">
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="orgSearch" class="form-control border-start-0" placeholder="Cari unit atau nama..." onkeyup="filterOrg()">
                    </div>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addRootModal">
                        <i class="fas fa-plus me-2"></i>Tambah Unit
                    </button>
                </div>
            </div>
        </div>

        <div class="org-container">
            <?php foreach ($roots as $row): ?>
                <div class="org-card">
                    <div class="org-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3><?= htmlspecialchars($row['nama_jabatan']) ?></h3>
                                <p class="mb-0 text-white-50 small"><i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($row['nama_pejabat'] ?: 'Belum Terisi') ?></p>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link text-white p-0" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="editRoot(<?= $row['id'] ?>, '<?= addslashes($row['nama_jabatan']) ?>', '<?= addslashes($row['nama_pejabat']) ?>'); return false;"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteJabatan(<?= $row['id'] ?>); return false;"><i class="fas fa-trash me-2"></i>Hapus</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="sub-list">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small fw-bold text-uppercase text-muted ls-1">Sub-Jabatan / Bagian</span>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openAddSub(<?= $row['id'] ?>)">
                                <i class="fas fa-plus me-1"></i> Sub
                            </button>
                        </div>
                        
                        <?php 
                        $children = $allChildren[$row['id']] ?? [];
                        if (empty($children)): ?>
                            <div class="text-center py-4 text-muted small italic">Belum ada sub-jabatan.</div>
                        <?php else: foreach ($children as $child): ?>
                            <div class="sub-item">
                                <div class="sub-icon"><i class="fas fa-id-badge"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark mb-0" style="font-size:0.9rem;"><?= htmlspecialchars($child['nama_jabatan']) ?></div>
                                    <div class="text-muted small"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($child['nama_pejabat'] ?: 'Belum Terisi') ?></div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link btn-sm text-muted" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick="editSub(<?= $child['id'] ?>, '<?= addslashes($child['nama_jabatan']) ?>', '<?= addslashes($child['nama_pejabat']) ?>'); return false;">Edit</a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteJabatan(<?= $child['id'] ?>); return false;">Hapus</a></li>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="add-card" data-bs-toggle="modal" data-bs-target="#addRootModal">
                <i class="fas fa-plus-circle"></i>
                <span class="fw-bold">Unit Kerja Baru</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal Root -->
<div class="modal fade" id="addRootModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Unit Utama</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Jabatan Utama</label>
                        <input type="text" name="nama_jabatan" class="form-control" placeholder="e.g. Rektorat" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Nama</label>
                        <input type="text" name="nama_pejabat" class="form-control" placeholder="e.g. Dr. H. Furtasan Ali Yusuf, S.E., S.Kom., M.M.">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Root -->
<div class="modal fade" id="editRootModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Unit Utama</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_root_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Jabatan Utama</label>
                        <input type="text" name="nama_jabatan" id="edit_root_jabatan" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Nama</label>
                        <input type="text" name="nama_pejabat" id="edit_root_pejabat" class="form-control">
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

<!-- Modal Add Sub -->
<div class="modal fade" id="addSubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Sub-Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">
                    <input type="hidden" name="parent_id" id="add_sub_parent_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Posisi</label>
                        <input type="text" name="nama_jabatan" class="form-control" placeholder="e.g. Kepala Bagian" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Nama</label>
                        <input type="text" name="nama_pejabat" class="form-control" placeholder="Nama ...">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Tambah Sub</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Sub -->
<div class="modal fade" id="editSubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Sub-Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_sub_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Posisi</label>
                        <input type="text" name="nama_jabatan" id="edit_sub_jabatan" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Nama</label>
                        <input type="text" name="nama_pejabat" id="edit_sub_pejabat" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
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
    function editRoot(id, jab, pej) {
        document.getElementById('edit_root_id').value = id;
        document.getElementById('edit_root_jabatan').value = jab;
        document.getElementById('edit_root_pejabat').value = pej;
        const modal = new bootstrap.Modal(document.getElementById('editRootModal'));
        modal.show();
        return false;
    }
    function openAddSub(parentId) {
        document.getElementById('add_sub_parent_id').value = parentId;
        const modal = new bootstrap.Modal(document.getElementById('addSubModal'));
        modal.show();
        return false;
    }
    function editSub(id, jab, pej) {
        document.getElementById('edit_sub_id').value = id;
        document.getElementById('edit_sub_jabatan').value = jab;
        document.getElementById('edit_sub_pejabat').value = pej;
        const modal = new bootstrap.Modal(document.getElementById('editSubModal'));
        modal.show();
        return false;
    }
    function deleteJabatan(id) {
        if(confirm('Apakah Anda yakin ingin menghapus jabatan ini? Semua data bawahan juga akan ikut terhapus.')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
        return false;
    }
    function filterOrg() {
        const query = document.getElementById('orgSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.org-card');
        
        cards.forEach(card => {
            const text = card.innerText.toLowerCase();
            if (text.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>

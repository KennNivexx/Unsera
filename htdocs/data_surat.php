<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$jenis_id = isset($_GET['jenis_id']) ? (int)$_GET['jenis_id'] : 0;
if (!$jenis_id) {
    header('Location: jenis_surat.php');
    exit;
}

// Get jenis surat info
$stmt = $conn->prepare("SELECT * FROM jenis_surat WHERE id = ?");
$stmt->bind_param("i", $jenis_id);
$stmt->execute();
$jenis = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$jenis) {
    echo "<script>alert('Jenis surat tidak ditemukan!');location='jenis_surat.php';</script>";
    exit;
}

// Handle add surat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
    
    if ($_POST['action'] == 'tambah') {
        $no_surat = trim($_POST['no_surat']);
        $tanggal = $_POST['tanggal'];
        $keterangan = trim($_POST['keterangan']);
        $dokumen = '';
        $dokumen_name = '';
        $dokumen_type = '';
        
        if (!empty($_FILES['dokumen']['name'])) {
            $dokumen_name = $_FILES['dokumen']['name'];
            $dokumen_type = $_FILES['dokumen']['type'];
            $dokumen = 'uploads/' . time() . '_surat_' . basename($dokumen_name);
            move_uploaded_file($_FILES['dokumen']['tmp_name'], $dokumen);
        }
        
        $stmt = $conn->prepare("INSERT INTO data_surat (jenis_id, no_surat, tanggal, keterangan, dokumen, dokumen_name, dokumen_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $jenis_id, $no_surat, $tanggal, $keterangan, $dokumen, $dokumen_name, $dokumen_type);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Data surat berhasil ditambahkan!');location='data_surat.php?jenis_id=$jenis_id';</script>";
        exit;
    }
    
    if ($_POST['action'] == 'hapus' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM data_surat WHERE id = ? AND jenis_id = ?");
        $stmt->bind_param("ii", $id, $jenis_id);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Data surat berhasil dihapus!');location='data_surat.php?jenis_id=$jenis_id';</script>";
        exit;
    }
    
    if ($_POST['action'] == 'edit' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $no_surat = trim($_POST['no_surat']);
        $tanggal = $_POST['tanggal'];
        $keterangan = trim($_POST['keterangan']);
        
        if (!empty($_FILES['dokumen']['name'])) {
            $dokumen_name = $_FILES['dokumen']['name'];
            $dokumen_type = $_FILES['dokumen']['type'];
            $dokumen = 'uploads/' . time() . '_surat_' . basename($dokumen_name);
            move_uploaded_file($_FILES['dokumen']['tmp_name'], $dokumen);
            
            $stmt = $conn->prepare("UPDATE data_surat SET no_surat = ?, tanggal = ?, keterangan = ?, dokumen = ?, dokumen_name = ?, dokumen_type = ? WHERE id = ? AND jenis_id = ?");
            $stmt->bind_param("ssssssii", $no_surat, $tanggal, $keterangan, $dokumen, $dokumen_name, $dokumen_type, $id, $jenis_id);
        } else {
            $stmt = $conn->prepare("UPDATE data_surat SET no_surat = ?, tanggal = ?, keterangan = ? WHERE id = ? AND jenis_id = ?");
            $stmt->bind_param("sssii", $no_surat, $tanggal, $keterangan, $id, $jenis_id);
        }
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Data surat berhasil diperbarui!');location='data_surat.php?jenis_id=$jenis_id';</script>";
        exit;
    }
}

$stmt = $conn->prepare("SELECT * FROM data_surat WHERE jenis_id = ? ORDER BY tanggal DESC, id DESC");
$stmt->bind_param("i", $jenis_id);
$stmt->execute();
$surat_list = $stmt->get_result();
$stmt->close();

$breadcrumbs = [
    ['label' => 'Kategori Surat', 'url' => 'jenis_surat.php'],
    ['label' => $jenis['nama_jenis'], 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($jenis['nama_jenis']) ?> | UNSERA Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .surat-card {
            background: white; border-radius: 20px; border: 1px solid #f1f5f9;
            transition: all 0.3s;
        }
        .surat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .doc-preview {
            width: 45px; height: 45px; border-radius: 12px; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center; color: var(--primary);
            font-size: 1.2rem;
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
                <h2 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;"><?= htmlspecialchars($jenis['nama_jenis']) ?></h2>
                <p class="text-muted">Manajemen arsip dokumen dan surat menyurat bagian <?= htmlspecialchars($jenis['nama_jenis']) ?>.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSuratModal">
                <i class="fas fa-plus me-2"></i>Tambah Arsip Baru
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">No. Surat / Dokumen</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>File</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($surat_list->num_rows > 0): while($s = $surat_list->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="doc-preview"><i class="fas fa-file-invoice"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['no_surat']) ?></div>
                                        <div class="small text-muted">ID: <?= str_pad($s['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="fw-bold"><?= date('d M Y', strtotime($s['tanggal'])) ?></div></td>
                            <td><div class="small text-muted text-truncate" style="max-width: 250px;"><?= htmlspecialchars($s['keterangan'] ?: '-') ?></div></td>
                            <td>
                                <?php if($s['dokumen']): ?>
                                    <a href="<?= $s['dokumen'] ?>" target="_blank" class="btn btn-sm btn-light rounded-pill border">
                                        <i class="fas fa-external-link-alt me-1"></i> Lihat
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small italic">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary rounded-pill me-2" onclick="editSurat(<?= $s['id'] ?>, '<?= addslashes($s['no_surat']) ?>', '<?= $s['tanggal'] ?>', '<?= addslashes($s['keterangan']) ?>', '<?= addslashes($s['dokumen']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="deleteSurat(<?= $s['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data surat dalam kategori ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="addSuratModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Tambah Arsip Surat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3"><label class="form-label small fw-bold">Nomor Surat</label><input type="text" name="no_surat" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Tanggal Surat</label><input type="date" name="tanggal" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Keterangan</label><textarea name="keterangan" class="form-control" rows="3"></textarea></div>
                    <div class="mb-0"><label class="form-label small fw-bold">Unggah Dokumen (PDF/Gambar)</label><input type="file" name="dokumen" class="form-control"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Arsip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editSuratModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Edit Arsip Surat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_surat_id">
                    <div class="mb-3"><label class="form-label small fw-bold">Nomor Surat</label><input type="text" name="no_surat" id="edit_no_surat" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Tanggal Surat</label><input type="date" name="tanggal" id="edit_tanggal" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Keterangan</label><textarea name="keterangan" id="edit_keterangan" class="form-control" rows="3"></textarea></div>
                    <div class="mb-0"><label class="form-label small fw-bold">Ganti Dokumen (Opsional)</label><input type="file" name="dokumen" class="form-control"><small id="edit_dok_info" class="text-primary mt-1 d-block"></small></div>
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
    function editSurat(id, no, tgl, ket, dok) {
        document.getElementById('edit_surat_id').value = id;
        document.getElementById('edit_no_surat').value = no;
        document.getElementById('edit_tanggal').value = tgl;
        document.getElementById('edit_keterangan').value = ket;
        document.getElementById('edit_dok_info').textContent = dok ? 'File saat ini: ' + dok.split('/').pop() : 'Belum ada file';
        new bootstrap.Modal(document.getElementById('editSuratModal')).show();
    }
    function deleteSurat(id) {
        if(confirm('Hapus arsip surat ini?')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>
</body>
</html>

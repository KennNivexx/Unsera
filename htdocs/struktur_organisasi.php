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
        $pejabat = trim($_POST['nama_pejabat'] ?? '');
        $ket     = trim($_POST['keterangan'] ?? '');
        $parent  = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $stmt = $conn->prepare("INSERT INTO struktur_organisasi (nama_jabatan, nama_pejabat, keterangan, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $jabatan, $pejabat, $ket, $parent);
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
        $ket     = trim($_POST['keterangan'] ?? '');
        $stmt = $conn->prepare("UPDATE struktur_organisasi SET nama_jabatan = ?, nama_pejabat = ?, keterangan = ? WHERE id = ?");
        $stmt->bind_param("sssi", $jabatan, $pejabat, $ket, $id);
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
    // Menangani error jika kolom parent_id belum ada (migration belum di run)
    die("Database error: Pastikan Anda sudah menjalankan script migrate_struktur.php di server hosting Anda. Error: " . $conn->error);
}

// Fetch all children grouped by parent_id
$allChildren = [];
$childResult = $conn->query("SELECT * FROM struktur_organisasi WHERE parent_id IS NOT NULL ORDER BY id ASC");
if ($childResult) {
    while ($row = $childResult->fetch_assoc()) {
        $allChildren[$row['parent_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struktur Organisasi | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .org-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .org-card {
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: border-color 0.2s, box-shadow 0.2s;
            overflow: hidden;
            position: relative;
        }
        .org-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .org-card-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .org-card-header:hover {
            background: #f1f5f9;
        }
        .org-icon-box {
            width: 36px; height: 36px;
            background: white;
            color: var(--primary);
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .jabatan-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            font-family: 'Inter', sans-serif;
            flex: 1;
        }
        /* Sub-jabatan area */
        .sub-area {
            padding: 15px 20px;
            background: white;
        }
        .sub-list {
            display: flex;
            flex-direction: column;
            gap: 0;
            margin-top: 5px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .sub-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 12px;
        }
        .sub-item:last-child {
            border-bottom: none;
        }
        .sub-item:hover { background: #f8fafc; }
        .sub-item-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            flex-shrink: 0;
            min-width: 140px;
        }
        .sub-item-name {
            flex: 1;
            font-size: 0.85rem;
            color: #0f172a;
            border: 1px solid transparent;
            outline: none;
            background: transparent;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .sub-item-name:hover {
            border-color: #e2e8f0;
            background: white;
        }
        .sub-item-name:focus {
            background: white; 
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
        }
        .sub-item-name::placeholder { color: #94a3b8; }
        .btn-save-name {
            width: 26px; height: 26px;
            border-radius: 4px;
            border: none;
            background: var(--primary);
            color: white;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem;
            opacity: 0;
            transition: opacity 0.2s;
            flex-shrink: 0;
        }
        .sub-item-name:focus ~ .btn-save-name,
        .btn-save-name:focus { opacity: 1; }
        .sub-item:focus-within .btn-save-name { opacity: 1; }
        .btn-del-sub {
            width: 26px; height: 26px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.75rem;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .btn-del-sub:hover { background: #fef2f2; border-color: #fca5a5; }
        
        /* Add sub form */
        .add-sub-form {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
        }
        .add-sub-form.visible { display: flex; }
        .add-sub-form input {
            flex: 1;
            border: 1px solid #cbd5e1;
            outline: none;
            background: white;
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 4px;
            font-family: inherit;
        }
        .add-sub-form input:focus { border-color: var(--primary); }
        .add-sub-form input::placeholder { color: #94a3b8; }
        
        /* Card actions */
        .org-card-actions {
            padding: 12px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            background: #f8fafc;
        }
        .btn-action {
            flex: 1;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
        }
        .btn-action:hover { background: #f1f5f9; }
        .btn-action.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .btn-action.blue:hover { background: #eff6ff; color: var(--primary); border-color: #bfdbfe; }

        /* Add card */
        .btn-add-card {
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap: 10px; cursor: pointer; min-height: 120px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-add-card:hover { border-color: var(--primary); background: #f0f9ff; }
        .btn-add-icon {
            width: 40px; height: 40px;
            background: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            color: #64748b;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
        }
        .btn-add-card:hover .btn-add-icon { color: var(--primary); border-color: var(--primary-soft); }

        /* Modal */
        .modal-glass {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4); z-index: 1000;
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-glass.active { display: flex; animation: fadeIn 0.15s ease; }
        .modal-body {
            background: white; padding: 30px; border-radius: 8px; width: 100%; max-width: 500px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .modal-body input, .modal-body textarea {
            padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1;
            width: 100%; font-weight: 400; font-family: inherit; font-size: 0.9rem;
        }
        .modal-body input:focus, .modal-body textarea:focus {
            border-color: var(--primary); outline: none; box-shadow: 0 0 0 2px var(--primary-soft);
        }
        .modal-body textarea { resize: vertical; }
        
        .modal-body h3 {
            margin: 0; font-family: 'Inter', sans-serif; font-weight: 600; font-size: 1.25rem; color: #0f172a;
        }
        .modal-body label {
            font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 6px; display: block;
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section">
        <h1><i class="fas fa-sitemap" style="margin-right:12px; color:var(--primary);"></i>Struktur Organisasi UNSERA</h1>
        <p>Kelola daftar jabatan dan pejabat struktural. Klik nama jabatan untuk mengelola posisi di bawahnya.</p>
    </div>

    <div class="org-grid">
        <?php foreach ($roots as $row): ?>
        <?php $children = $allChildren[$row['id']] ?? []; ?>
        <div class="org-card">
            <div class="org-card-header" onclick="toggleSubArea(<?= $row['id'] ?>)" title="Klik untuk kelola sub-jabatan">
                <div class="org-icon-box"><i class="fas fa-sitemap"></i></div>
                <h3 class="jabatan-title"><?= htmlspecialchars($row['nama_jabatan']) ?></h3>
                <i class="fas fa-chevron-down" id="chevron-<?= $row['id'] ?>" style="color:#94a3b8; transition:transform 0.2s; font-size:0.9rem;"></i>
            </div>

            <!-- Sub-area (jabatan bawahan + nama ke samping) -->
            <div class="sub-area" id="sub-area-<?= $row['id'] ?>" style="display:none;">
                <div class="sub-list" id="sub-list-<?= $row['id'] ?>">
                    <?php foreach ($children as $child): ?>
                    <div class="sub-item" data-id="<?= $child['id'] ?>">
                        <span class="sub-item-label"><?= htmlspecialchars($child['nama_jabatan']) ?></span>
                        <input type="text" class="sub-item-name" 
                               value="<?= htmlspecialchars($child['nama_pejabat'] ?? '') ?>"
                               placeholder="Nama pejabat..."
                               onchange="savePejabat(<?= $child['id'] ?>, this.value)"
                               onkeydown="if(event.key==='Enter'){savePejabat(<?= $child['id'] ?>, this.value); this.blur();}">
                        <button class="btn-save-name" onclick="savePejabat(<?= $child['id'] ?>, this.previousElementSibling.value)">
                            <i class="fas fa-check"></i>
                        </button>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Hapus sub-jabatan ini?')">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="id" value="<?= $child['id'] ?>">
                            <button type="submit" class="btn-del-sub"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Form tambah sub-jabatan -->
                <div class="add-sub-form" id="add-sub-form-<?= $row['id'] ?>">
                    <i class="fas fa-plus" style="color:#93c5fd; font-size:0.85rem;"></i>
                    <input type="text" id="new-sub-<?= $row['id'] ?>" placeholder="Nama jabatan baru (e.g. Kepala Bagian)..."
                           onkeydown="if(event.key==='Enter'){addSubJabatan(<?= $row['id'] ?>);}">
                    <button type="button" onclick="addSubJabatan(<?= $row['id'] ?>)" 
                            style="background:var(--primary); color:white; border:none; border-radius:4px; padding:6px 12px; font-weight:600; cursor:pointer; font-size:0.8rem;">
                        Tambah
                    </button>
                </div>
                <button type="button" onclick="toggleAddSubForm(<?= $row['id'] ?>)"
                        style="margin-top:10px; width:100%; border:1px dashed #cbd5e1; background:white; color:#64748b; padding:8px; border-radius:4px; font-weight:600; cursor:pointer; font-size:0.8rem; display:flex; align-items:center; justify-content:center; gap:6px; transition:all 0.2s;"
                        onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';"
                        onmouseout="this.style.background='white'; this.style.borderColor='#cbd5e1';">
                    <i class="fas fa-plus"></i> Tambah Sub-Jabatan
                </button>
            </div>

            <!-- Actions -->
            <div class="org-card-actions">
                <button onclick="openEditModal(<?= $row['id'] ?>, '<?= addslashes($row['nama_jabatan']) ?>', '<?= addslashes($row['nama_pejabat'] ?? '') ?>', '<?= addslashes($row['keterangan'] ?? '') ?>')" 
                        class="btn-action blue">
                    <i class="fas fa-pen-nib"></i> Edit
                </button>
                <form method="POST" style="flex:1;" onsubmit="return confirm('Hapus jabatan dan semua sub-jabatannya?')">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn-action delete" style="width:100%;">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add New Root Jabatan Card -->
        <div class="btn-add-card" onclick="openAddModal()">
            <div class="btn-add-icon"><i class="fas fa-plus"></i></div>
            <span style="font-weight:600; color:#1e293b; font-family:'Inter', sans-serif; font-size:1rem;">Tambah Jabatan Baru</span>
            <span style="font-size:0.85rem; color:#64748b; font-weight:400;">Klik untuk menambahkan posisi jabatan</span>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-glass" id="addModal">
    <div class="modal-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3>Tambah Jabatan Baru</h3>
            <button onclick="closeModal('addModal')" style="background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label>Nama Jabatan</label>
                <input type="text" name="nama_jabatan" placeholder="e.g., Biro Kepegawaian" required>
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Nama Pejabat (Kepala Jabatan)</label>
                <input type="text" name="nama_pejabat" placeholder="Nama Lengkap & Gelar">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="3" placeholder="Deskripsi singkat..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeModal('addModal')" class="btn" style="background:#f1f5f9; color:#475569; font-weight:600; padding:8px 16px; border:1px solid #e2e8f0;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-weight:600; border-radius:4px;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-glass" id="editModal">
    <div class="modal-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3>Edit Jabatan</h3>
            <button onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Nama Jabatan</label>
                <input type="text" name="nama_jabatan" id="edit_jabatan" required>
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Nama Pejabat</label>
                <input type="text" name="nama_pejabat" id="edit_pejabat">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Keterangan</label>
                <textarea name="keterangan" id="edit_keterangan" rows="3"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeModal('editModal')" class="btn" style="background:#f1f5f9; color:#475569; font-weight:600; padding:8px 16px; border:1px solid #e2e8f0;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-weight:600; border-radius:4px;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle sub-area visibility
function toggleSubArea(id) {
    const area = document.getElementById('sub-area-' + id);
    const chevron = document.getElementById('chevron-' + id);
    if (area.style.display === 'none' || !area.style.display) {
        area.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        area.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}

function toggleAddSubForm(parentId) {
    const form = document.getElementById('add-sub-form-' + parentId);
    form.classList.toggle('visible');
    if (form.classList.contains('visible')) {
        document.getElementById('new-sub-' + parentId).focus();
    }
}

async function addSubJabatan(parentId) {
    const input = document.getElementById('new-sub-' + parentId);
    const name = input.value.trim();
    if (!name) { input.focus(); return; }

    const formData = new FormData();
    formData.append('action', 'tambah_sub');
    formData.append('parent_id', parentId);
    formData.append('nama_jabatan', name);

    const res = await fetch('struktur_organisasi.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
        const list = document.getElementById('sub-list-' + parentId);
        const item = document.createElement('div');
        item.className = 'sub-item';
        item.dataset.id = data.id;
        item.innerHTML = `
            <span class="sub-item-label">${escHtml(data.nama_jabatan)}</span>
            <input type="text" class="sub-item-name" value="" placeholder="Nama pejabat..."
                   onchange="savePejabat(${data.id}, this.value)"
                   onkeydown="if(event.key==='Enter'){savePejabat(${data.id}, this.value); this.blur();}">
            <button class="btn-save-name" onclick="savePejabat(${data.id}, this.previousElementSibling.value)">
                <i class="fas fa-check"></i>
            </button>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Hapus sub-jabatan ini?')">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" value="${data.id}">
                <button type="submit" class="btn-del-sub"><i class="fas fa-times"></i></button>
            </form>`;
        list.appendChild(item);
        input.value = '';
        document.getElementById('add-sub-form-' + parentId).classList.remove('visible');
    }
}

async function savePejabat(id, value) {
    const formData = new FormData();
    formData.append('action', 'update_pejabat');
    formData.append('id', id);
    formData.append('nama_pejabat', value);
    const res = await fetch('struktur_organisasi.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
        // Visual feedback
        const items = document.querySelectorAll(`.sub-item[data-id="${id}"] .sub-item-name`);
        items.forEach(el => {
            el.style.background = '#f0fdf4';
            setTimeout(() => { el.style.background = ''; }, 800);
        });
    }
}

function escHtml(text) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(text));
    return d.innerHTML;
}

function openAddModal() { document.getElementById('addModal').classList.add('active'); }
function openEditModal(id, jab, pej, ket) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_jabatan').value = jab;
    document.getElementById('edit_pejabat').value = pej;
    document.getElementById('edit_keterangan').value = ket;
    document.getElementById('editModal').classList.add('active');
}
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
window.onclick = function(e) { if(e.target.className === 'modal-glass active') e.target.classList.remove('active'); }
</script>
</body>
</html>

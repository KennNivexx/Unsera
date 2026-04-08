<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle add new jenis
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
    <title>Kelola Jenis Surat | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .jenis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        .jenis-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .jenis-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        .jenis-card .icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }
        .jenis-card h3 {
            font-size: 1.1rem;
            margin: 0;
            color: var(--text-main);
        }
        .jenis-card .badge {
            background: #f0f5ff;
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .jenis-card .actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }
        .jenis-card .actions a, .jenis-card .actions button {
            flex: 1;
            text-align: center;
        }
        .add-card {
            border: 2px dashed var(--border-color);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            min-height: 200px;
            transition: var(--transition);
        }
        .add-card:hover {
            border-color: var(--primary);
            background: #f0f5ff;
        }
        .add-card .add-icon {
            font-size: 2.5rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .add-card:hover .add-icon { color: var(--primary); }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 36px;
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow-lg);
        }
        .modal-box h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: var(--text-main);
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
            <h1><i class="fas fa-envelope-open-text" style="margin-right:10px;"></i>Kelola Jenis Surat</h1>
            <p>Tambah, edit, atau hapus jenis-jenis surat yang tersedia di sistem.</p>
        </div>
        <div style="display:flex; align-items:center; gap:8px; font-size:0.82rem; color:var(--text-muted); margin-top:6px;">
            <span style="width:9px;height:9px;background:#22c55e;border-radius:50%;box-shadow:0 0 0 0 rgba(34,197,94,0.5);animation:pulse-dot 2s infinite;display:inline-block;"></span>
            Live &bull; Update: <span id="last-updated">--:--:--</span>
        </div>
    </div>

    <div class="jenis-grid" id="jenis-grid">
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="jenis-card">
            <div class="icon-wrapper"><i class="fas fa-file-alt"></i></div>
            <h3><?= htmlspecialchars($row['nama_jenis']) ?></h3>
            <span class="badge"><i class="fas fa-folder"></i> <?= $row['total_surat'] ?> surat</span>
            <div class="actions">
                <a href="data_surat.php?jenis_id=<?= $row['id'] ?>" class="btn btn-primary" style="font-size: 0.85rem; padding: 8px 16px;">
                    <i class="fas fa-eye"></i> Lihat
                </a>
                <button type="button" onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_jenis'])) ?>')" class="btn btn-outline" style="font-size: 0.85rem; padding: 8px 16px;">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <form method="POST" style="flex:1; display:flex;" onsubmit="return confirm('Hapus jenis surat ini? Semua data surat terkait juga akan dihapus.')">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-outline" style="font-size: 0.85rem; padding: 8px 16px; color: var(--danger); border-color: var(--danger); width:100%;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
        <div class="jenis-card add-card" onclick="openAddModal()">
            <div class="add-icon">
                <i class="fas fa-plus-circle"></i>
                <span style="font-size: 0.95rem; font-weight: 600;">Tambah Jenis Surat</span>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <h3><i class="fas fa-plus-circle" style="color: var(--primary); margin-right:8px;"></i>Tambah Jenis Surat Baru</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label>Nama Jenis Surat</label>
                <input type="text" name="nama_jenis" placeholder="Contoh: Surat Perjanjian Kerja" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                <button type="button" onclick="closeModal('addModal')" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3><i class="fas fa-edit" style="color: var(--primary); margin-right:8px;"></i>Edit Jenis Surat</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Nama Jenis Surat</label>
                <input type="text" name="nama_jenis" id="edit_nama" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes pulse-dot { 0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.6); } 70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } }
#toast-container { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
.toast { background:#1e293b; color:white; padding:14px 20px; border-radius:12px; font-size:0.9rem; box-shadow:0 8px 30px rgba(0,0,0,0.25); display:flex; align-items:center; gap:12px; animation:slide-in 0.4s ease; max-width:320px; border-left:4px solid #3b82f6; }
.toast.success { border-color:#22c55e; }
@keyframes slide-in { from { transform:translateX(100px); opacity:0; } to { transform:translateX(0); opacity:1; } }
@keyframes slide-out { from { transform:translateX(0); opacity:1; } to { transform:translateX(100px); opacity:0; } }
</style>

<div id="toast-container"></div>

<script>
let prevCount = <?= $result->num_rows ?>;
function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'info-circle'}"></i><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='slide-out 0.4s ease forwards'; setTimeout(()=>t.remove(),400); }, 4000);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fetchJenisSurat() {
    fetch('api_realtime.php?action=jenis_surat_list')
        .then(r => r.json())
        .then(d => {
            const grid = document.getElementById('jenis-grid');
            if (!grid) return;
            
            const html = d.rows.map(r => `
                <div class="jenis-card">
                    <div class="icon-wrapper"><i class="fas fa-file-alt"></i></div>
                    <h3>${escHtml(r.nama_jenis)}</h3>
                    <span class="badge"><i class="fas fa-folder"></i> ${r.total_surat} surat</span>
                    <div class="actions">
                        <a href="data_surat.php?jenis_id=${r.id}" class="btn btn-primary" style="font-size: 0.85rem; padding: 8px 16px;">
                            <i class="fas fa-eye"></i> Lihat
                        </a>
                        <button type="button" onclick="openEditModal(${r.id}, '${escHtml(r.nama_jenis).replace(/'/g, "\\'")}')" class="btn btn-outline" style="font-size: 0.85rem; padding: 8px 16px;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="flex:1; display:flex;" onsubmit="return confirm('Hapus jenis surat ini? Semua data surat terkait juga akan dihapus.')">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="id" value="${r.id}">
                            <button type="submit" class="btn btn-outline" style="font-size: 0.85rem; padding: 8px 16px; color: var(--danger); border-color: var(--danger); width:100%;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            `).join('') + `
                <div class="jenis-card add-card" onclick="openAddModal()">
                    <div class="add-icon">
                        <i class="fas fa-plus-circle"></i>
                        <span style="font-size: 0.95rem; font-weight: 600;">Tambah Jenis Surat</span>
                    </div>
                </div>
            `;
            
            grid.innerHTML = html;
            document.getElementById('last-updated').textContent = d.timestamp;
            
            if (d.rows.length > prevCount) showToast(`Jenis surat baru ditambahkan. Total: ${d.rows.length}`, 'success');
            else if (d.rows.length < prevCount) showToast(`Data jenis surat diperbarui. Total: ${d.rows.length}`, 'info');
            
            prevCount = d.rows.length;
        });
}

setInterval(fetchJenisSurat, 5000);

function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}
function openEditModal(id, nama) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('editModal').classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
// Close modal on backdrop click
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>

</body>
</html>

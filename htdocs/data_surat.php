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
        
        // Check for new file upload
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

// Fetch surat data
$stmt = $conn->prepare("SELECT * FROM data_surat WHERE jenis_id = ? ORDER BY tanggal DESC, id DESC");
$stmt->bind_param("i", $jenis_id);
$stmt->execute();
$surat_list = $stmt->get_result();
$stmt->close();

$page_title = htmlspecialchars($jenis['nama_jenis']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .multi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
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
            max-width: 550px;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-box h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: var(--text-main);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            color: var(--text-main);
        }
        .surat-table {
            width: 100%;
            border-collapse: collapse;
        }
        .surat-table th {
            background: #f8fafc;
            padding: 14px 16px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        .surat-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            vertical-align: middle;
        }
        .surat-table tr:hover {
            background: #f8fafc;
        }
        .surat-table .no-surat {
            font-weight: 700;
            color: var(--primary);
        }
        .file-current {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <a href="jenis_surat.php" class="btn-icon" title="Kembali"><i class="fas fa-arrow-left"></i></a>
                <h1 style="margin:0;"><i class="fas fa-file-alt" style="margin-right:10px; color: var(--primary);"></i><?= $page_title ?></h1>
            </div>
            <p style="margin:0; margin-left: 48px;">Kelola data surat untuk jenis <strong><?= $page_title ?></strong>.</p>
        </div>
        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:8px; font-size:0.82rem; color:var(--text-muted);">
                <span style="width:9px;height:9px;background:#22c55e;border-radius:50%;box-shadow:0 0 0 0 rgba(34,197,94,0.5);animation:pulse-dot 2s infinite;display:inline-block;"></span>
                Live &bull; Update: <span id="last-updated">--:--:--</span>
            </div>
            <button type="button" onclick="openAddModal()" class="btn btn-primary" style="padding: 12px 24px;">
                <i class="fas fa-plus"></i> Tambah Surat
            </button>
        </div>
    </div>

        <div style="overflow-x: auto;">
            <table class="surat-table">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Dokumen</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="surat-tbody">
                    <?php if ($surat_list->num_rows > 0): ?>
                        <?php $no = 1; while($s = $surat_list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="no-surat"><?= htmlspecialchars($s['no_surat']) ?></td>
                            <td><?= $s['tanggal'] ? date('d/m/Y', strtotime($s['tanggal'])) : '-' ?></td>
                            <td><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>
                            <td>
                                <?php if(!empty($s['dokumen'])): ?>
                                    <a href="<?= $s['dokumen'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 12px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-file-pdf"></i> Lihat
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <button type="button" onclick="openEditModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['no_surat'])) ?>', '<?= $s['tanggal'] ?>', '<?= htmlspecialchars(addslashes($s['keterangan'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($s['dokumen'] ?? '')) ?>')" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 12px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Yakin ingin menghapus surat ini?')">
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 12px; color: var(--danger); border-color: var(--danger);">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-inbox" style="font-size:3rem;opacity:0.2;display:block;margin-bottom:10px;"></i> Belum Ada Data Surat.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <h3><i class="fas fa-plus-circle" style="color: var(--primary); margin-right:8px;"></i>Tambah Data Surat</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label>Nomor Surat</label>
                <input type="text" name="no_surat" placeholder="Contoh: 001/SK/UNSERA/III/2026" required>
            </div>
            <div class="form-group">
                <label>Tanggal Surat</label>
                <input type="date" name="tanggal" required>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="3" placeholder="Tuliskan keterangan atau isi ringkas surat..."></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-upload" style="margin-right:6px; color: var(--primary);"></i>Unggah Dokumen</label>
                <input type="file" name="dokumen" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <small style="color: var(--text-muted); font-size: 0.8rem;">Format: PDF, JPG, PNG, DOC, DOCX</small>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                <button type="button" onclick="closeModal('addModal')" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3><i class="fas fa-edit" style="color: var(--primary); margin-right:8px;"></i>Edit Data Surat</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Nomor Surat</label>
                <input type="text" name="no_surat" id="edit_no_surat" required>
            </div>
            <div class="form-group">
                <label>Tanggal Surat</label>
                <input type="date" name="tanggal" id="edit_tanggal" required>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" id="edit_keterangan" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-upload" style="margin-right:6px; color: var(--primary);"></i>Unggah Dokumen Baru (opsional)</label>
                <input type="file" name="dokumen" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <span class="file-current" id="edit_dokumen_info"></span>
                <small style="color: var(--text-muted); font-size: 0.8rem;">Kosongkan jika tidak ingin mengganti dokumen</small>
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
let prevCount = <?= $surat_list->num_rows ?>;
function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'info-circle'}"></i><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='slide-out 0.4s ease forwards'; setTimeout(()=>t.remove(),400); }, 4000);
}

function formatDateDisplay(dStr) {
    if (!dStr) return '-';
    const parts = dStr.split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return dStr;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fetchSurat() {
    fetch(`api_realtime.php?action=surat_list&jenis_id=<?= $jenis_id ?>`)
        .then(r => r.json())
        .then(d => {
            const tbody = document.getElementById('surat-tbody');
            if (!tbody) return;
            
            if (!d.rows || d.rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-inbox" style="font-size:3rem;opacity:0.2;display:block;margin-bottom:10px;"></i> Belum Ada Data Surat.</td></tr>`;
            } else {
                tbody.innerHTML = d.rows.map((r, i) => `
                    <tr>
                        <td>${i+1}</td>
                        <td class="no-surat">${escHtml(r.no_surat)}</td>
                        <td>${formatDateDisplay(r.tanggal)}</td>
                        <td>${escHtml(r.keterangan || '-')}</td>
                        <td>
                            ${r.dokumen ? `<a href="${escHtml(r.dokumen)}" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 12px; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-file-pdf"></i> Lihat</a>` : `<span style="color: var(--text-muted); font-size: 0.85rem;">-</span>`}
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" onclick="openEditModal(${r.id}, '${escHtml(r.no_surat).replace(/'/g, "\\'")}', '${escHtml(r.tanggal)}', '${escHtml(r.keterangan || '').replace(/'/g, "\\'")}', '${escHtml(r.dokumen || '').replace(/'/g, "\\'")}')" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 12px;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus surat ini?')">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="${r.id}">
                                    <button type="submit" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 12px; color: var(--danger); border-color: var(--danger);">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            document.getElementById('last-updated').textContent = d.timestamp;
            if (d.rows.length > prevCount) {
                showToast(`Data surat baru ditambahkan! Total: ${d.rows.length}`, 'success');
            } else if (d.rows.length < prevCount) {
                showToast(`Data surat diperbarui. Total: ${d.rows.length}`, 'info');
            }
            prevCount = d.rows.length;
        });
}

setInterval(fetchSurat, 5000);

function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}
function openEditModal(id, noSurat, tanggal, keterangan, dokumen) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_no_surat').value = noSurat;
    document.getElementById('edit_tanggal').value = tanggal;
    document.getElementById('edit_keterangan').value = keterangan;
    
    const dokInfo = document.getElementById('edit_dokumen_info');
    if (dokumen) {
        const filename = dokumen.split('/').pop();
        dokInfo.innerHTML = '<i class="fas fa-file"></i> File saat ini: ' + filename;
    } else {
        dokInfo.innerHTML = 'Belum ada dokumen';
    }
    
    document.getElementById('editModal').classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>

</body>
</html>

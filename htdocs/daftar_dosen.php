<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$jabfung = $_GET['jabfung'] ?? '';

$where_clauses = [];
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $where_clauses[] = "(nama_lengkap LIKE '%$search_safe%' OR status_dosen LIKE '%$search_safe%')";
}
if ($status) {
    $status_safe = $conn->real_escape_string($status);
    $where_clauses[] = "status_dosen = '$status_safe'";
}
if ($jabfung) {
    $jabfung_safe = $conn->real_escape_string($jabfung);
    $where_clauses[] = "jabfung_akademik = '$jabfung_safe'";
}

$where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$data = $conn->query("SELECT * FROM dosen $where ORDER BY id DESC");

// Stats for Header
$total_dosen = $conn->query("SELECT COUNT(*) as total FROM dosen")->fetch_assoc()['total'];
$total_tetap = $conn->query("SELECT COUNT(*) as total FROM dosen WHERE status_dosen='Tetap'")->fetch_assoc()['total'];
$total_aktif = $conn->query("SELECT COUNT(*) as total FROM dosen WHERE status_keaktifan='Aktif'")->fetch_assoc()['total'];

$breadcrumbs = [
    ['label' => 'Manajemen Dosen', 'url' => '#'],
    ['label' => 'Daftar Induk', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Dosen | Kepegawaian UNSERA</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --unsera-blue: #2563eb;
            --unsera-grey: #f8fafc;
            --unsera-dark: #1e293b;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .page-header { background: white; padding: 2rem; border-radius: 0 0 24px 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .stat-mini-card { background: white; padding: 1.25rem; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; transition: all 0.3s; }
        .stat-mini-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: var(--unsera-blue); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        
        .table-card { background: white; border-radius: 24px; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table thead th { background: #f8fafc; padding: 1.2rem 1rem; font-weight: 700; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; }
        .data-table tbody td { padding: 1.2rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; transition: all 0.2s; }
        .data-table tbody tr:hover td { background-color: #f8fafc; }
        
        .avatar-box { width: 42px; height: 42px; border-radius: 12px; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--unsera-blue); border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .filter-section { background: white; padding: 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; }
        .form-control, .form-select { border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0.6rem 1rem; }
        .form-control:focus { border-color: var(--unsera-blue); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        
        .badge-status { padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge-tetap { background: #dcfce7; color: #15803d; }
        .badge-tidak-tetap { background: #fef9c3; color: #854d0e; }
        .badge-homebase { background: #ffedd5; color: #9a3412; }
        
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; border: none; background: #f1f5f9; color: #475569; }
        .btn-action:hover { background: var(--unsera-blue); color: white; transform: scale(1.1); }
        
        #toast-container { position:fixed; bottom:24px; right:24px; z-index:9999; }
        .toast { background:#1e293b; color:white; padding:12px 20px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); margin-top:10px; display:flex; align-items:center; gap:12px; animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <!-- Header & Quick Stats -->
        <div class="row g-4 mb-4 align-items-center">
            <div class="col-lg-6">
                <h1 class="h2 fw-bold text-dark mb-1">Manajemen Akademisi</h1>
                <p class="text-muted mb-0">Total <span class="fw-bold text-primary"><?= $total_dosen ?></span> dosen terdaftar dalam sistem pangkalan data.</p>
            </div>
            <div class="col-lg-6">
                <div class="d-flex gap-3 justify-content-lg-end flex-wrap">
                    <div class="stat-mini-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="small text-muted fw-medium">Total Dosen</div>
                            <div class="h5 fw-bold mb-0"><?= $total_dosen ?></div>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-check"></i></div>
                        <div>
                            <div class="small text-muted fw-medium">Dosen Tetap</div>
                            <div class="h5 fw-bold mb-0"><?= $total_tetap ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section shadow-sm">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" id="searchFilter" class="form-control border-start-0" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau status..." onkeyup="fetchDosen()">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" id="statusFilter" class="form-select" onchange="fetchDosen()">
                        <option value="">Semua Status Pegawai</option>
                        <option value="Tetap" <?= $status == 'Tetap' ? 'selected' : '' ?>>Tetap</option>
                        <option value="Tidak Tetap" <?= $status == 'Tidak Tetap' ? 'selected' : '' ?>>Tidak Tetap</option>
                        <option value="Homebase" <?= $status == 'Homebase' ? 'selected' : '' ?>>Homebase</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="jabfung" id="jabfungFilter" class="form-select" onchange="fetchDosen()">
                        <option value="">Semua Jabfung</option>
                        <option value="Tenaga Pengajar" <?= $jabfung == 'Tenaga Pengajar' ? 'selected' : '' ?>>Tenaga Pengajar</option>
                        <option value="Asisten Ahli" <?= $jabfung == 'Asisten Ahli' ? 'selected' : '' ?>>Asisten Ahli</option>
                        <option value="Lektor" <?= $jabfung == 'Lektor' ? 'selected' : '' ?>>Lektor</option>
                        <option value="Lektor Kepala" <?= $jabfung == 'Lektor Kepala' ? 'selected' : '' ?>>Lektor Kepala</option>
                        <option value="Guru Besar" <?= $jabfung == 'Guru Besar' ? 'selected' : '' ?>>Guru Besar</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <a href="input_dosen.php" class="btn btn-primary rounded-pill fw-bold">
                        <i class="fas fa-plus me-2"></i>Tambah Baru
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="table-card shadow-sm">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-dark">Data Induk Dosen</h5>
                <div class="d-flex gap-2">
                    <a href="export_dosen.php" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="fas fa-file-excel me-1"></i> Export</a>
                    <span class="badge bg-light text-muted rounded-pill px-3 py-2" style="font-size: 0.7rem;">Last updated: <span id="last-updated"><?= date('H:i:s') ?></span></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 60px;">No</th>
                            <th>Profil Dosen</th>
                            <th>Homebase / Prodi</th>
                            <th>Status Kepegawaian</th>
                            <th>Jabfung</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($data->num_rows > 0) {
                            $no = 1;
                            while($row = $data->fetch_assoc()) {
                                $st = strtolower($row['status_dosen'] ?? '');
                                $badgeClass = 'badge-tetap';
                                if($st == 'tidak tetap') $badgeClass = 'badge-tidak-tetap';
                                if($st == 'homebase') $badgeClass = 'badge-homebase';
                                
                                $initials = strtoupper(substr($row['nama_lengkap'], 0, 1));
                        ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-box">
                                        <?php if(!empty($row['foto_profil'])): ?>
                                            <img src="<?= htmlspecialchars($row['foto_profil']) ?>" alt="Foto">
                                        <?php else: ?>
                                            <?= $initials ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <div class="small text-muted">NIDN: <?= htmlspecialchars($row['nidn'] ?? '-') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-medium small"><?= htmlspecialchars($row['homebase_prodi'] ?? '-') ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['unit_kerja'] ?? '-') ?></div>
                            </td>
                            <td>
                                <span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($row['status_dosen'] ?? '-') ?></span>
                            </td>
                            <td>
                                <div class="small fw-bold text-primary"><?= htmlspecialchars($row['jabfung_akademik'] ?? '-') ?></div>
                                <div class="text-muted small">TMT: <?= !empty($row['tmt_jabfung']) ? date('d/m/Y', strtotime($row['tmt_jabfung'])) : '-' ?></div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="detail_dosen.php?id=<?= $row['id'] ?>" class="btn-action" title="Detail Profil"><i class="fas fa-eye"></i></a>
                                    <a href="form_edit_dosen.php?id=<?= $row['id'] ?>" class="btn-action" title="Edit Data"><i class="fas fa-edit"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5'>
                                <div class='text-muted'>
                                    <i class='fas fa-search fa-3x mb-3 opacity-25'></i>
                                    <p class='mb-0 fw-bold'>Data Dosen Tidak Ditemukan</p>
                                    <small>Gunakan filter atau kata kunci lain.</small>
                                </div>
                            </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let prevCount = <?= $data->num_rows ?>;

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast';
    t.style.borderLeft = `5px solid ${type==='success'?'#22c55e':'#3b82f6'}`;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle text-success':'info-circle text-primary'}"></i><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(50px)'; setTimeout(()=>t.remove(),300); }, 4000);
}

function renderTable(rows) {
    const tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5">
            <div class="text-muted">
                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                <p class="mb-0 fw-bold">Data Dosen Tidak Ditemukan</p>
            </div>
        </td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map((r, i) => {
        const st = (r.status_dosen || '').toLowerCase();
        let badgeClass = 'badge-tetap';
        if(st === 'tidak tetap') badgeClass = 'badge-tidak-tetap';
        if(st === 'homebase') badgeClass = 'badge-homebase';
        
        const initials = r.nama_lengkap.charAt(0).toUpperCase();
        const tmtJab = r.tmt_jabfung ? formatDate(r.tmt_jabfung) : '-';
        
        return `
        <tr>
            <td class="ps-4 text-muted fw-bold">${i+1}</td>
            <td>
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-box">
                        ${r.foto_profil ? `<img src="${escHtml(r.foto_profil)}" alt="Foto">` : initials}
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-0">${escHtml(r.nama_lengkap)}</div>
                        <div class="small text-muted">NIDN: ${escHtml(r.nidn || '-')}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="text-dark fw-medium small">${escHtml(r.homebase_prodi || '-')}</div>
                <div class="text-muted" style="font-size: 0.75rem;">${escHtml(r.unit_kerja || '-')}</div>
            </td>
            <td><span class="badge-status ${badgeClass}">${escHtml(r.status_dosen || '-')}</span></td>
            <td>
                <div class="small fw-bold text-primary">${escHtml(r.jabfung_akademik || '-')}</div>
                <div class="text-muted small">TMT: ${tmtJab}</div>
            </td>
            <td class="text-center pe-4">
                <div class="d-flex justify-content-center gap-2">
                    <a href="detail_dosen.php?id=${r.id}" class="btn-action" title="Detail Profil"><i class="fas fa-eye"></i></a>
                    <a href="form_edit_dosen.php?id=${r.id}" class="btn-action" title="Edit Data"><i class="fas fa-edit"></i></a>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function formatDate(dateStr) {
    if(!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID');
}

function escHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fetchDosen() {
    const s = encodeURIComponent(document.getElementById('searchFilter')?.value ?? '');
    const st = encodeURIComponent(document.getElementById('statusFilter')?.value ?? '');
    const jb = encodeURIComponent(document.getElementById('jabfungFilter')?.value ?? '');
    fetch(`api_realtime.php?action=dosen_list&search=${s}&status=${st}&jabfung=${jb}`)
        .then(r => r.json())
        .then(d => {
            renderTable(d.rows);
            document.getElementById('last-updated').textContent = d.timestamp;
            if (d.rows.length > prevCount) {
                showToast(`Data baru terdeteksi! Total: ${d.rows.length}`, 'success');
            }
            prevCount = d.rows.length;
        }).catch(() => {});
}

setInterval(fetchDosen, 5000);
</script>
</body>
</html>
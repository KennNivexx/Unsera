<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$search = $_GET['search'] ?? '';
$where = "";
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $where = " WHERE nama_lengkap LIKE '%$search_safe%' OR posisi_jabatan LIKE '%$search_safe%' OR unit_kerja LIKE '%$search_safe%' ";
}

$data = $conn->query("SELECT * FROM pegawai $where ORDER BY id DESC");

$breadcrumbs = [
    ['label' => 'Daftar Pegawai', 'url' => '#']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pegawai | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
        }
        .data-table td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .data-table tr:hover { background-color: #f1f5f9; }
        .search-box { position: relative; max-width: 300px; }
        .search-box input { padding-left: 40px; font-size: 0.9rem; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .action-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .name-cell { display: flex; align-items: center; gap: 12px; }
        .avatar-circle {
            width: 36px;
            height: 36px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        .avatar-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid #bae6fd;
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
            <h1>Data Kepegawaian (Staff)</h1>
            <p>Manajemen data tenaga kependidikan dan staf administrasi Universitas Serang Raya.</p>
        </div>
        <div style="display:flex; align-items:center; gap:8px; font-size:0.82rem; color:var(--text-muted); margin-top:6px;">
            <span style="width:9px;height:9px;background:#22c55e;border-radius:50%;box-shadow:0 0 0 0 rgba(34,197,94,0.5);animation:pulse-dot 2s infinite;display:inline-block;"></span>
            Live &bull; Update: <span id="last-updated">--:--:--</span>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
            <form method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, jabatan, atau unit..." class="form-control">
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Lengkap</th>
                        <th>Jabatan / Unit Kerja</th>
                        <th>Status</th>
                        <th style="text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($data && $data->num_rows > 0) {
                        while($row = $data->fetch_assoc()) {
                            $badgeClass = (strtolower($row['jenis_pegawai'] ?? '') == 'tetap') ? 'badge-success' : 'badge-warning';
                            $initials = strtoupper(substr($row['nama_lengkap'], 0, 1));
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                        <div class="name-cell">
                                <?php if(!empty($row['foto_profil'])): ?>
                                    <img src="<?= htmlspecialchars($row['foto_profil']) ?>" alt="Foto" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-circle"><?= $initials ?></div>
                                <?php endif; ?>
                                <div>
                                    <a href="detail_pegawai.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: var(--primary); font-weight: 600;">
                                        <?= htmlspecialchars($row['nama_lengkap']) ?>
                                    </a>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">ID: <?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-main);"><?= htmlspecialchars($row['posisi_jabatan'] ?? '-') ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($row['unit_kerja'] ?? '-') ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['jenis_pegawai'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="detail_pegawai.php?id=<?= $row['id'] ?>" class="btn-icon" title="Detail" style="color: var(--primary);"><i class="fas fa-eye"></i> Detail</a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; padding: 40px; color: var(--text-muted);'>Belum ada data pegawai yang sesuai.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@keyframes pulse-dot {
    0%   { box-shadow: 0 0 0 0 rgba(34,197,94,0.6); }
    70%  { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}
#toast-container { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
.toast { background:#1e293b; color:white; padding:14px 20px; border-radius:12px; font-size:0.9rem; box-shadow:0 8px 30px rgba(0,0,0,0.25); display:flex; align-items:center; gap:12px; animation:slide-in 0.4s ease; max-width:320px; border-left:4px solid #3b82f6; }
.toast.success { border-color:#22c55e; }
@keyframes slide-in { from { transform:translateX(100px); opacity:0; } to { transform:translateX(0); opacity:1; } }
@keyframes slide-out { from { transform:translateX(0); opacity:1; } to { transform:translateX(100px); opacity:0; } }
</style>

<div id="toast-container"></div>

<script>
const searchVal = () => document.querySelector('input[name="search"]')?.value ?? '';
let prevCount = <?= $data ? $data->num_rows : 0 ?>;

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'info-circle'}"></i><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='slide-out 0.4s ease forwards'; setTimeout(()=>t.remove(),400); }, 4000);
}

function escHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderTable(rows) {
    const tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data pegawai yang sesuai.</td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map((r, i) => {
        const badge = (r.jenis_pegawai||'').toLowerCase() === 'tetap' ? 'badge-success' : 'badge-warning';
        const idStr = String(r.id).padStart(5, '0');
        return `<tr>
            <td>${i+1}</td>
            <td>
                <div class="name-cell">
                    ${r.foto_profil ? `<img src="${escHtml(r.foto_profil)}" alt="Foto" class="avatar-img">` : `<div class="avatar-circle">${(r.nama_lengkap||'?').charAt(0).toUpperCase()}</div>`}
                    <div>
                        <a href="detail_pegawai.php?id=${r.id}" style="text-decoration:none;color:var(--primary);font-weight:600;">${escHtml(r.nama_lengkap)}</a>
                        <div style="font-size:0.75rem;color:var(--text-muted);">ID: ${idStr}</div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-weight:500;color:var(--text-main);">${escHtml(r.posisi_jabatan||'-')}</div>
                <div style="font-size:0.8rem;color:var(--text-muted);">${escHtml(r.unit_kerja||'-')}</div>
            </td>
            <td><span class="badge ${badge}">${escHtml(r.jenis_pegawai||'N/A')}</span></td>
            <td><div class="action-btns"><a href="detail_pegawai.php?id=${r.id}" class="btn-icon" style="color:var(--primary);"><i class="fas fa-eye"></i> Detail</a></div></td>
        </tr>`;
    }).join('');
}

function fetchPegawai() {
    fetch(`api_realtime.php?action=pegawai_list&search=${encodeURIComponent(searchVal())}`)
        .then(r => r.json())
        .then(d => {
            renderTable(d.rows);
            document.getElementById('last-updated').textContent = d.timestamp;
            if (d.rows.length > prevCount) {
                showToast(`Pegawai baru ditambahkan! Total: ${d.rows.length}`, 'success');
            } else if (d.rows.length < prevCount) {
                showToast(`Data pegawai diperbarui. Total: ${d.rows.length}`, 'info');
            }
            prevCount = d.rows.length;
        }).catch(() => {});
}

fetchPegawai();
setInterval(fetchPegawai, 5000);
</script>

</body>
</html>

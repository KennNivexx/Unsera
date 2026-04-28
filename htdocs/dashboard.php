<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// Greeting
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';

// Initial server-side values (used for first render)
$totalDosen      = $conn->query("SELECT COUNT(*) as c FROM dosen")->fetch_assoc()['c'];
$dosenTetap      = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='tetap'")->fetch_assoc()['c'];
$dosenTidakTetap = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='tidak tetap'")->fetch_assoc()['c'];
$dosenHomebase   = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='homebase'")->fetch_assoc()['c'];
$totalPegawai      = $conn->query("SELECT COUNT(*) as c FROM pegawai")->fetch_assoc()['c'];
$pegawaiTetap      = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE LOWER(status_pegawai)='tetap' OR LOWER(jenis_pegawai)='tetap'")->fetch_assoc()['c'];
$pegawaiTidakTetap = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE LOWER(status_pegawai)='tidak tetap' OR LOWER(status_pegawai)='tdk tetap' OR LOWER(jenis_pegawai)='tidak tetap' OR LOWER(jenis_pegawai)='tdk tetap'")->fetch_assoc()['c'];


// Jabfung breakdown per jenis
$jabfungBreakdown = [];
$dosenRows = $conn->query("SELECT id, jabfung_akademik FROM dosen");
if ($dosenRows) {
    while($row = $dosenRows->fetch_assoc()) {
        $did = $row['id'];
        $j = trim($row['jabfung_akademik'] ?? '');
        $qj = $conn->query("SELECT jabatan FROM jabfung_dosen WHERE dosen_id=$did ORDER BY tmt DESC, id DESC LIMIT 1");
        if($qj && $rj = $qj->fetch_assoc()) {
            if (trim($rj['jabatan']) !== '') $j = trim($rj['jabatan']);
        }
        if(!empty($j) && $j !== '-') {
            $jabfungBreakdown[$j] = ($jabfungBreakdown[$j] ?? 0) + 1;
        }
    }
}
arsort($jabfungBreakdown);

$maxId = 0;
$maxYId = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | UNSERA</title>
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .stat-card-premium {
            background: white;
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            position: relative;
            transition: var(--transition);
        }
        .stat-card-premium:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card-premium .card-body {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }
        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #ecfdf5; color: #10b981; }
        .icon-purple { background: #f5f3ff; color: #7c3aed; }
        .icon-orange { background: #fff7ed; color: #f59e0b; }
        
        .stat-card-premium .label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .stat-card-premium .value {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
        }
        .stat-card-premium .trend {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .chart-container-premium {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            height: 100%;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        .chart-header h5 {
            margin: 0;
            font-weight: 700;
            color: var(--text-main);
            font-size: 1.1rem;
        }

        /* Stats updating animation */
        .value.updating {
            animation: pulse-update 0.4s ease;
        }
        @keyframes pulse-update {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        #toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
        }
        .custom-toast {
            background: white;
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow-lg);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slide-in 0.4s ease forwards;
        }
    </style>
</head>
<body class="bg-light">

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <!-- Header Welcome -->
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h2 class="fw-bold text-dark mb-1">Overview Dashboard</h2>
                <p class="text-secondary">Selamat datang kembali, <?= htmlspecialchars($adminNama) ?>. Berikut adalah ringkasan data kepegawaian Anda.</p>
            </div>
            <div class="col-auto">
                <div class="bg-white p-2 rounded-3 shadow-sm border d-flex align-items-center gap-3">
                    <div class="text-end">
                        <small class="text-uppercase fw-bold text-primary d-block" style="font-size: 0.65rem;">Sistem Online</small>
                        <small class="text-muted" style="font-size: 0.8rem;">Update: <span id="last-updated" class="fw-bold text-dark">--:--:--</span></small>
                    </div>
                    <div class="live-dot" style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);"></div>
                </div>
            </div>
        </div>

        <!-- Stat Cards Row -->
        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-premium">
                    <div class="card-body">
                        <div class="stat-card-icon icon-blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <div class="label">Total Dosen</div>
                            <div class="value" id="stat-totalDosen"><?= $totalDosen ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-premium">
                    <div class="card-body">
                        <div class="stat-card-icon icon-green">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="label">Total Pegawai</div>
                            <div class="value" id="stat-totalPegawai"><?= $totalPegawai ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-premium">
                    <div class="card-body">
                        <div class="stat-card-icon icon-purple">
                            <i class="fas fa-award"></i>
                        </div>
                        <div>
                            <div class="label">Dosen Tetap</div>
                            <div class="value" id="stat-dosenTetap"><?= $dosenTetap ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-premium">
                    <div class="card-body">
                        <div class="stat-card-icon icon-orange">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div>
                            <div class="label">Pegawai Tetap</div>
                            <div class="value" id="stat-pegawaiTetap"><?= $pegawaiTetap ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Left Chart -->
            <div class="col-lg-6">
                <div class="chart-container-premium shadow-sm">
                    <div class="chart-header">
                        <h5><i class="fas fa-chart-bar text-primary me-2"></i> Distribusi Status Dosen</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border" type="button"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="grafikDosen"></canvas>
                    </div>
                    <div class="row mt-4 text-center g-2">
                        <div class="col-4">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Tetap</small>
                            <span class="fw-bold" id="stat-dosenTetap-2"><?= $dosenTetap ?></span>
                        </div>
                        <div class="col-4 border-start">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Tdk Tetap</small>
                            <span class="fw-bold" id="stat-dosenTidakTetap"><?= $dosenTidakTetap ?></span>
                        </div>
                        <div class="col-4 border-start">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Homebase</small>
                            <span class="fw-bold" id="stat-dosenHomebase"><?= $dosenHomebase ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Chart -->
            <div class="col-lg-6">
                <div class="chart-container-premium shadow-sm">
                    <div class="chart-header">
                        <h5><i class="fas fa-chart-pie text-success me-2"></i> Distribusi Status Pegawai</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border" type="button"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="grafikPegawai"></canvas>
                    </div>
                    <div class="row mt-4 text-center g-2">
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Tetap</small>
                            <span class="fw-bold" id="stat-pegawaiTetap-2"><?= $pegawaiTetap ?></span>
                        </div>
                        <div class="col-6 border-start">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Tidak Tetap</small>
                            <span class="fw-bold" id="stat-pegawaiTidakTetap"><?= $pegawaiTidakTetap ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribution Details Row -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-container-premium">
                    <div class="chart-header">
                        <h5><i class="fas fa-graduation-cap text-info me-2"></i> Kualifikasi Pendidikan</h5>
                    </div>
                    <div id="edu-breakdown-mini" class="d-flex flex-column gap-2">
                        <div class="text-center py-4 text-muted">Memuat data kualifikasi...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container-premium">
                    <div class="chart-header">
                        <h5><i class="fas fa-briefcase text-warning me-2"></i> Jabatan Fungsional</h5>
                    </div>
                    <div id="jabfung-breakdown-mini" class="d-flex flex-column gap-2">
                        <div class="text-center py-4 text-muted">Memuat data jabatan...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Chart setup ──────────────────────────────────────────────
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';

// Chart 1: Dosen Distribution
const ctxDosen = document.getElementById('grafikDosen');
const myChartDosen = new Chart(ctxDosen, {
    type: 'bar',
    data: {
        labels: ['Tidak Tetap', 'Homebase', 'Tetap'],
        datasets: [{
            label: 'Jumlah Dosen',
            data: [<?= $dosenTidakTetap ?>, <?= $dosenHomebase ?>, <?= $dosenTetap ?>],
            backgroundColor: ['#f59e0b', '#ef4444', '#2563eb'],
            borderRadius: 8,
            barThickness: 40
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 12,
                cornerRadius: 8,
                titleFont: { size: 14, weight: 'bold' }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#e2e8f0' } },
            x: { grid: { display: false } }
        }
    }
});

// Chart 2: Pegawai Distribution
const ctxPegawai = document.getElementById('grafikPegawai');
const myChartPegawai = new Chart(ctxPegawai, {
    type: 'doughnut',
    data: {
        labels: ['Tidak Tetap', 'Tetap'],
        datasets: [{
            data: [<?= $pegawaiTidakTetap ?>, <?= $pegawaiTetap ?>],
            backgroundColor: ['#f59e0b', '#10b981'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
        }
    }
});

// ── Toast helper ─────────────────────────────────────────────
function showToast(msg, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `custom-toast`;
    if (type === 'success') toast.style.borderLeftColor = '#10b981';
    if (type === 'danger') toast.style.borderLeftColor = '#ef4444';
    
    const icon = type === 'success' ? 'fa-check-circle text-success' : 'fa-info-circle text-primary';
    toast.innerHTML = `<i class="fas ${icon} fa-lg"></i><div class="fw-semibold">${msg}</div>`;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.4s ease';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// ── Animate number change ──────────────────────────────────────
function animateValue(el, newVal) {
    if (!el) return;
    if (el.textContent == newVal) return false;
    el.classList.add('updating');
    setTimeout(() => {
        el.textContent = newVal;
        el.classList.remove('updating');
    }, 300);
    return true;
}

let prev = {
    totalDosen: <?= $totalDosen ?>,
    totalPegawai: <?= $totalPegawai ?>
};

// ── Main polling function ─────────────────────────────────────
function fetchStats() {
    fetch('api_realtime.php?action=stats')
        .then(r => r.json())
        .then(d => {
            // Update Text Values
            animateValue(document.getElementById('stat-totalDosen'), d.totalDosen);
            animateValue(document.getElementById('stat-totalPegawai'), d.totalPegawai);
            animateValue(document.getElementById('stat-dosenTetap'), d.dosenTetap);
            animateValue(document.getElementById('stat-dosenTetap-2'), d.dosenTetap);
            animateValue(document.getElementById('stat-pegawaiTetap'), d.pegawaiTetap);
            animateValue(document.getElementById('stat-pegawaiTetap-2'), d.pegawaiTetap);
            
            animateValue(document.getElementById('stat-dosenTidakTetap'), d.dosenTidakTetap);
            animateValue(document.getElementById('stat-dosenHomebase'), d.dosenHomebase);
            animateValue(document.getElementById('stat-pegawaiTidakTetap'), d.pegawaiTidakTetap);

            // Update Charts
            myChartDosen.data.datasets[0].data = [d.dosenTidakTetap, d.dosenHomebase, d.dosenTetap];
            myChartDosen.update();

            myChartPegawai.data.datasets[0].data = [d.pegawaiTidakTetap, d.pegawaiTetap];
            myChartPegawai.update();

            // Update Breakdown Lists
            if (d.jabfungBreakdown) {
                const colors = ['#2563eb', '#7c3aed', '#10b981', '#f59e0b', '#ef4444'];
                const listEl = document.getElementById('jabfung-breakdown-mini');
                listEl.innerHTML = Object.entries(d.jabfungBreakdown).map(([l, v], i) => `
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:10px; height:10px; border-radius:50%; background:${colors[i] || '#cbd5e1'}"></div>
                            <span class="fw-bold text-secondary">${l}</span>
                        </div>
                        <span class="fw-extrabold h5 mb-0 text-dark">${v}</span>
                    </div>
                `).join('');
            }

            if (d.eduDosen) {
                const eduColors = ['#10b981', '#2563eb', '#7c3aed', '#f59e0b'];
                const eduListEl = document.getElementById('edu-breakdown-mini');
                eduListEl.innerHTML = Object.entries(d.eduDosen).map(([l, v], i) => `
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:10px; height:10px; border-radius:50%; background:${eduColors[i] || '#cbd5e1'}"></div>
                            <span class="fw-bold text-secondary">${l}</span>
                        </div>
                        <span class="fw-extrabold h5 mb-0 text-dark">${v}</span>
                    </div>
                `).join('');
            }

            document.getElementById('last-updated').textContent = d.timestamp;

            // Toasts for changes
            if (d.totalDosen > prev.totalDosen) {
                showToast(`Data Dosen bertambah!`, 'success');
            }
            if (d.totalPegawai > prev.totalPegawai) {
                showToast(`Data Pegawai bertambah!`, 'success');
            }

            prev.totalDosen   = d.totalDosen;
            prev.totalPegawai = d.totalPegawai;
        })
        .catch(err => console.error('Realtime Error:', err));
}

// Initial fetch and interval
fetchStats();
setInterval(fetchStats, 5000);
</script>

</body>
</html>

</body>
</html>

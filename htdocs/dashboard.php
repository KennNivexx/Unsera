<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// Initial server-side values (used for first render)
$totalDosen      = $conn->query("SELECT COUNT(*) as c FROM dosen")->fetch_assoc()['c'];
$dosenTetap      = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='tetap'")->fetch_assoc()['c'];
$dosenTidakTetap = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='tidak tetap'")->fetch_assoc()['c'];
$dosenHomebase   = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE LOWER(status_dosen)='homebase'")->fetch_assoc()['c'];
$totalPegawai      = $conn->query("SELECT COUNT(*) as c FROM pegawai")->fetch_assoc()['c'];
$pegawaiTetap      = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE jenis_pegawai='tetap'")->fetch_assoc()['c'];
$pegawaiTidakTetap = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE jenis_pegawai='tidak tetap' OR jenis_pegawai='tdk tetap'")->fetch_assoc()['c'];

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
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px; }
        .bg-blue-light   { background: #e0f2fe; color: #0369a1; }
        .bg-green-light  { background: #dcfce7; color: #15803d; }
        .bg-purple-light { background: #f3e8ff; color: #7e22ce; }
        .bg-orange-light { background: #ffedd5; color: #c2410c; }
        /* Live indicator */
        .live-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .live-dot {
            width: 9px; height: 9px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(34,197,94,0.5);
            animation: pulse-dot 2s infinite;
            display: inline-block;
        }
        @keyframes pulse-dot {
            0%   { box-shadow: 0 0 0 0 rgba(34,197,94,0.6); }
            70%  { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
        }
        .stat-value { transition: opacity 0.3s ease, transform 0.3s ease; }
        .stat-value.updating { opacity: 0.3; transform: scale(0.9); }
        
        /* New Dash Layout Styles */
        .dash-header-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; margin-bottom: 24px; }
        .dash-top-card { display: flex; flex-direction: column; padding: 24px; background: white; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .dash-top-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .dash-top-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .dash-top-card .info h3 { margin: 0; font-size: 0.95rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .dash-top-card .info .value { font-size: 2.5rem; font-weight: 800; color: var(--text-color); margin-top: 5px; line-height: 1; }
        .dash-top-card .icon-box { width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }
        
        .badges-container { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
        .badge-item { font-size: 0.8rem; font-weight: 700; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 5px; }

        .dash-charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .card-title-box { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        .card-title-box h3 { margin: 0; font-size: 1.1rem; color: var(--text-color); font-weight: 700; }
        
        @media (max-width: 1024px) {
            .dash-third-row { grid-template-columns: 1fr !important; }
        }

        /* Jabfung badge list */
        .jabfung-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .jabfung-pill {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        /* Toast */
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            background: #1e293b;
            color: white;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 0.9rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slide-in 0.4s ease;
            max-width: 320px;
        }
        .toast.success { border-left: 4px solid #22c55e; }
        .toast.info    { border-left: 4px solid #3b82f6; }
        @keyframes slide-in {
            from { transform: translateX(100px); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }
        @keyframes slide-out {
            from { transform: translateX(0); opacity: 1; }
            to   { transform: translateX(100px); opacity: 0; }
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="header-section" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
            <h1>Overview Kepegawaian</h1>
            <p>Ringkasan statistik data dosen dan pegawai Universitas Serang Raya.</p>
        </div>
        <div class="live-bar">
            <span class="live-dot"></span>
            <span>Live &bull; Diperbarui: <span id="last-updated">--:--:--</span></span>
        </div>
    </div>

    <div class="dash-header-cards">
        <!-- Total Dosen Card -->
        <div class="dash-top-card">
            <div class="dash-top-card-header">
                <div class="info">
                    <h3>Total Dosen</h3>
                    <div class="value stat-value" id="stat-totalDosen"><?= $totalDosen ?></div>
                    <div class="badges-container">
                        <span class="badge-item" style="background:#fef3c7; color:#d97706;">Tidak Tetap: <span id="stat-dosenTidakTetap" style="margin-left:4px;"><?= $dosenTidakTetap ?></span></span>
                        <span class="badge-item" style="background:#fee2e2; color:#ef4444;">Homebase: <span id="stat-dosenHomebase" style="margin-left:4px;"><?= $dosenHomebase ?></span></span>
                        <span class="badge-item" style="background:#dcfce7; color:#15803d;">Tetap: <span id="stat-dosenTetap" style="margin-left:4px;"><?= $dosenTetap ?></span></span>
                    </div>
                </div>
                <div class="icon-box bg-blue-light"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
            <!-- Bar Chart Dosen dipindah kesini -->
            <div style="height: 220px; width: 100%; border-top: 1px dashed #e2e8f0; padding-top: 20px; margin-top: auto;">
                <canvas id="grafikDosen"></canvas>
            </div>
        </div>

        <!-- Total Pegawai Card -->
        <div class="dash-top-card">
            <div class="dash-top-card-header">
                <div class="info">
                    <h3>Total Pegawai</h3>
                    <div class="value stat-value" id="stat-totalPegawai"><?= $totalPegawai ?></div>
                    <div class="badges-container">
                        <span class="badge-item" style="background:#fef3c7; color:#d97706;">Tidak Tetap: <span id="stat-pegawaiTidakTetap" style="margin-left:4px;"><?= $pegawaiTidakTetap ?></span></span>
                        <span class="badge-item" style="background:#dcfce7; color:#15803d;">Tetap: <span id="stat-pegawaiTetap" style="margin-left:4px;"><?= $pegawaiTetap ?></span></span>
                    </div>
                </div>
                <div class="icon-box bg-green-light"><i class="fas fa-users-cog"></i></div>
            </div>
            <!-- Bar Chart Pegawai dipindah kesini -->
            <div style="height: 220px; width: 100%; border-top: 1px dashed #e2e8f0; padding-top: 20px; margin-top: auto;">
                <canvas id="grafikPegawai"></canvas>
            </div>
        </div>
    </div>

    <!-- Jabatan Akademik (Full Width & Menarik) -->
    <div class="card stat-card" style="margin-bottom: 24px;">
        <div class="card-title-box" style="margin-bottom: 15px;">
            <div class="stat-icon bg-purple-light" style="width:40px;height:40px;font-size:1.2rem;margin-bottom:0;"><i class="fas fa-user-graduate"></i></div>
            <h3>Rincian Jabatan Akademik Dosen</h3>
        </div>
        <div class="jabfung-list" id="jabfung-breakdown" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <?php if (!empty($jabfungBreakdown)): ?>
                <?php foreach ($jabfungBreakdown as $jab => $jml): ?>
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s;">
                        <i class="fas fa-award" style="color: var(--purple); font-size: 1.8rem; margin-bottom: 12px;"></i>
                        <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;"><?= htmlspecialchars($jab) ?></span>
                        <strong style="font-size: 1.4rem; color: var(--primary); font-weight: 800;"><?= $jml ?> <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);">Orang</span></strong>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; color:var(--text-muted); padding: 20px;">Belum ada data jabatan akademik.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Second Row: Pies -->
    <div class="dash-third-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
        <!-- Doughnut Pie Dosen -->
        <div class="card stat-card" style="margin-bottom:0;">
            <div class="card-title-box">
                <div class="stat-icon bg-blue-light" style="width:40px;height:40px;font-size:1.2rem;margin-bottom:0;"><i class="fas fa-chart-pie"></i></div>
                <h3>Proporsi Dosen</h3>
            </div>
            <div style="height: 250px; width: 100%; display: flex; justify-content: center;">
                <canvas id="grafikPieDosen"></canvas>
            </div>
        </div>

        <!-- Doughnut Pie Pegawai -->
        <div class="card stat-card" style="margin-bottom:0;">
            <div class="card-title-box">
                <div class="stat-icon bg-green-light" style="width:40px;height:40px;font-size:1.2rem;margin-bottom:0;"><i class="fas fa-chart-pie"></i></div>
                <h3>Proporsi Pegawai</h3>
            </div>
            <div style="height: 250px; width: 100%; display: flex; justify-content: center;">
                <canvas id="grafikPiePegawai"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<script>
// ── Chart setup ──────────────────────────────────────────────

// Chart 1: Diagram Batang Dosen
const ctxDosen = document.getElementById('grafikDosen');
const myChartDosen = new Chart(ctxDosen, {
    type: 'bar',
    data: {
        labels: ['Tidak Tetap', 'Homebase', 'Tetap'],
        datasets: [{
            label: 'Jumlah Dosen',
            data: [<?= $dosenTidakTetap ?>, <?= $dosenHomebase ?>, <?= $dosenTetap ?>],
            backgroundColor: [
                '#f59e0b', // warning
                '#ef4444', // danger
                '#22c55e'  // success
            ],
            borderRadius: 6,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 600, easing: 'easeInOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                padding: 12,
                cornerRadius: 8,
                displayColors: false
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { display: true, color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 14 } } },
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', weight: 'bold', size: 14 } } }
        }
    }
});

// Chart 2: Diagram Batang Karyawan/Pegawai
const ctxPegawai = document.getElementById('grafikPegawai');
const myChartPegawai = new Chart(ctxPegawai, {
    type: 'bar',
    data: {
        labels: ['Tidak Tetap', 'Tetap'],
        datasets: [{
            label: 'Jumlah Karyawan',
            data: [<?= $pegawaiTidakTetap ?>, <?= $pegawaiTetap ?>],
            backgroundColor: [
                '#f59e0b', // warning
                '#22c55e'  // success
            ],
            borderRadius: 6,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 600, easing: 'easeInOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                padding: 12,
                cornerRadius: 8,
                displayColors: false
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { display: true, color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 14 } } },
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', weight: 'bold', size: 14 } } }
        }
    }
});

const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { display: true, position: 'bottom', labels: { font: { family: 'Inter', size: 13 }, padding: 20 } },
        tooltip: {
            backgroundColor: '#1e293b',
            titleFont: { size: 14, weight: 'bold' },
            bodyFont: { size: 13 },
            padding: 12,
            cornerRadius: 8,
            displayColors: true
        }
    }
};

// Chart 3: Diagram Pie Karyawan
const ctxPiePegawai = document.getElementById('grafikPiePegawai');
const myPieChartPegawai = new Chart(ctxPiePegawai, {
    type: 'doughnut',
    data: {
        labels: ['Tidak Tetap', 'Tetap'],
        datasets: [{
            data: [<?= $pegawaiTidakTetap ?>, <?= $pegawaiTetap ?>],
            backgroundColor: ['#f59e0b', '#22c55e'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: pieOptions
});

// Chart 4: Diagram Pie Dosen
const ctxPieDosen = document.getElementById('grafikPieDosen');
const myPieChartDosen = new Chart(ctxPieDosen, {
    type: 'doughnut',
    data: {
        labels: ['Tidak Tetap', 'Homebase', 'Tetap'],
        datasets: [{
            data: [<?= $dosenTidakTetap ?>, <?= $dosenHomebase ?>, <?= $dosenTetap ?>],
            backgroundColor: ['#f59e0b', '#ef4444', '#22c55e'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: pieOptions
});

// ── Toast helper ─────────────────────────────────────────────
function showToast(msg, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
    toast.innerHTML = `<i class="fas ${icon}"></i><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slide-out 0.4s ease forwards';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// ── Animate number change ──────────────────────────────────────
function animateValue(el, newVal) {
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
            animateValue(document.getElementById('stat-totalDosen'), d.totalDosen);
            animateValue(document.getElementById('stat-dosenTetap'), d.dosenTetap);
            animateValue(document.getElementById('stat-dosenTidakTetap'), d.dosenTidakTetap);
            animateValue(document.getElementById('stat-dosenHomebase'), d.dosenHomebase);
            animateValue(document.getElementById('stat-totalPegawai'), d.totalPegawai);
            animateValue(document.getElementById('stat-pegawaiTetap'), d.pegawaiTetap);
            animateValue(document.getElementById('stat-pegawaiTidakTetap'), d.pegawaiTidakTetap);

            // Update jabfung breakdown
            if (d.jabfungBreakdown) {
                const el = document.getElementById('jabfung-breakdown');
                if (Object.keys(d.jabfungBreakdown).length > 0) {
                    el.innerHTML = Object.entries(d.jabfungBreakdown).map(([jab, jml]) =>
                        `<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                            <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-color);"><i class="fas fa-award" style="color: var(--purple, #7e22ce); margin-right: 8px;"></i> ${jab}</span>
                            <strong style="font-size: 1rem; color: var(--primary); background: var(--primary-soft); padding: 4px 12px; border-radius: 20px;">${jml} Orang</strong>
                        </div>`
                    ).join('');
                } else {
                    el.innerHTML = '<span style="color:var(--text-muted); font-size:1rem;">Belum ada data jabfung.</span>';
                }
            }

            // Update grafik
            myChartDosen.data.datasets[0].data = [d.dosenTidakTetap, d.dosenHomebase, d.dosenTetap];
            myChartDosen.update();
            myChartPegawai.data.datasets[0].data = [d.pegawaiTidakTetap, d.pegawaiTetap];
            myChartPegawai.update();
            
            myPieChartDosen.data.datasets[0].data = [d.dosenTidakTetap, d.dosenHomebase, d.dosenTetap];
            myPieChartDosen.update();
            myPieChartPegawai.data.datasets[0].data = [d.pegawaiTidakTetap, d.pegawaiTetap];
            myPieChartPegawai.update();

            document.getElementById('last-updated').textContent = d.timestamp;

            if (d.totalDosen > prev.totalDosen) {
                showToast(`Dosen baru ditambahkan! Total: ${d.totalDosen}`, 'success');
            } else if (d.totalDosen < prev.totalDosen) {
                showToast(`Data dosen diperbarui. Total: ${d.totalDosen}`, 'info');
            }
            if (d.totalPegawai > prev.totalPegawai) {
                showToast(`Pegawai baru ditambahkan! Total: ${d.totalPegawai}`, 'success');
            } else if (d.totalPegawai < prev.totalPegawai) {
                showToast(`Data pegawai diperbarui. Total: ${d.totalPegawai}`, 'info');
            }

            prev.totalDosen   = d.totalDosen;
            prev.totalPegawai = d.totalPegawai;
        })
        .catch(() => {});
}

fetchStats();
setInterval(fetchStats, 5000);
</script>

</body>
</html>

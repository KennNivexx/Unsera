<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// Greeting berdasarkan jenis kelamin
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$adminJK   = $_SESSION['admin_jk'] ?? 'Laki-laki';
$sapaan    = (strtolower($adminJK) === 'perempuan') ? 'Ibu' : 'Bapak';

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

    <!-- Welcome & Hero Section -->
    <div class="card header-card" style="background: #ffffff; padding: 24px; margin-bottom: 24px; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 class="academic-title" style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 8px;">Selamat Datang <?= htmlspecialchars($sapaan) ?> <?= htmlspecialchars($adminNama) ?></h1>

                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">Status Sistem</div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;">Terakhir diperbarui: <span id="last-updated">--:--:--</span></div>
                </div>
            </div>
            <div style="display: flex; gap: 30px; margin-top: 25px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 44px; height: 44px; background: #ebedef; color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;"><i class="fas fa-id-badge"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Dosen Aktif</div>
                        <div class="value stat-value" id="stat-totalDosen" style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);"><?= $totalDosen ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 44px; height: 44px; background: #ebedef; color: var(--success); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;"><i class="fas fa-users"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Pegawai Aktif</div>
                        <div class="value stat-value" id="stat-totalPegawai" style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);"><?= $totalPegawai ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Breakdown Section -->
    <div class="dash-charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 24px;">
        <!-- Dosen Distribution -->
        <div class="card">
            <div class="card-title-box" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 600;">Distribusi Dosen</h3>
            </div>
            <div style="height: 300px; width: 100%;">
                <canvas id="grafikDosen"></canvas>
            </div>
            <div class="badges-container" style="display: flex; justify-content: center; gap: 15px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tetap</div>
                    <div id="stat-dosenTetap" style="font-size: 1.2rem; font-weight: 800; color: #10b981;"><?= $dosenTetap ?></div>
                </div>
                <div style="text-align: center; border-left: 1px solid #e2e8f0; padding-left: 15px;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tidak Tetap</div>
                    <div id="stat-dosenTidakTetap" style="font-size: 1.2rem; font-weight: 800; color: #f59e0b;"><?= $dosenTidakTetap ?></div>
                </div>
                <div style="text-align: center; border-left: 1px solid #e2e8f0; padding-left: 15px;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Homebase</div>
                    <div id="stat-dosenHomebase" style="font-size: 1.2rem; font-weight: 800; color: #ef4444;"><?= $dosenHomebase ?></div>
                </div>
            </div>
        </div>

        <!-- Pegawai Distribution -->
        <div class="card">
            <div class="card-title-box" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 600;">Distribusi Pegawai</h3>
            </div>
            <div style="height: 300px; width: 100%;">
                <canvas id="grafikPegawai"></canvas>
            </div>
            <div class="badges-container" style="display: flex; justify-content: center; gap: 25px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tetap</div>
                    <div id="stat-pegawaiTetap" style="font-size: 1.2rem; font-weight: 800; color: #10b981;"><?= $pegawaiTetap ?></div>
                </div>
                <div style="text-align: center; border-left: 1px solid #e2e8f0; padding-left: 25px;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tidak Tetap</div>
                    <div id="stat-pegawaiTidakTetap" style="font-size: 1.2rem; font-weight: 800; color: #f59e0b;"><?= $pegawaiTidakTetap ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-third-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Jabatan Akademik (Achievements Style) -->
        <div class="card">
            <div class="card-title-box" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 600;">Jabatan Akademik Dosen</h3>
            </div>
            <div class="jabfung-list" id="jabfung-breakdown" style="display: grid; gap: 12px;">
                <?php if (!empty($jabfungBreakdown)): ?>
                    <?php foreach ($jabfungBreakdown as $jab => $jml): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;">
                            <div style="display:flex; align-items:center; gap: 15px;">
                                <div style="width: 10px; height: 10px; background: #7e22ce; border-radius: 50%;"></div>
                                <span style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($jab) ?></span>
                            </div>
                            <div style="background: #ede9fe; color: #7e22ce; padding: 4px 14px; border-radius: 30px; font-weight: 800; font-size: 1.1rem;"><?= $jml ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-certificate"></i>
                        <h4>Belum Ada Data</h4>
                        <p>Data jabatan akademik dosen belum tersedia dalam sistem.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Proporsi Dosen & Pegawai -->
        <div class="card">
            <div class="card-title-box" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 18px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 600;">Ringkasan Proporsi</h3>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="text-align: center;">
                    <div style="height: 180px; width: 100%;">
                        <canvas id="grafikPieDosen"></canvas>
                    </div>
                    <p style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Dosen</p>
                </div>
                <div style="text-align: center;">
                    <div style="height: 180px; width: 100%;">
                        <canvas id="grafikPiePegawai"></canvas>
                    </div>
                    <p style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Pegawai</p>
                </div>
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

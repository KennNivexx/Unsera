<?php
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// Greeting
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';

// Initial server-side values
$q_td = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif'");
$totalDosen = ($q_td) ? $q_td->fetch_assoc()['c'] : 0;

$q_dt = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif' AND LOWER(status_dosen)='tetap'");
$dosenTetap = ($q_dt) ? $q_dt->fetch_assoc()['c'] : 0;

$q_dtt = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif' AND LOWER(status_dosen)='tidak tetap'");
$dosenTidakTetap = ($q_dtt) ? $q_dtt->fetch_assoc()['c'] : 0;

$q_dh = $conn->query("SELECT COUNT(*) as c FROM dosen WHERE status_keaktifan != 'Tidak Aktif' AND LOWER(status_dosen)='homebase'");
$dosenHomebase = ($q_dh) ? $q_dh->fetch_assoc()['c'] : 0;

$q_tp = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE status_keaktifan != 'Tidak Aktif'");
$totalPegawai = ($q_tp) ? $q_tp->fetch_assoc()['c'] : 0;

$q_pt = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE status_keaktifan != 'Tidak Aktif' AND (LOWER(status_pegawai)='tetap' OR LOWER(jenis_pegawai)='tetap')");
$pegawaiTetap = ($q_pt) ? $q_pt->fetch_assoc()['c'] : 0;

$q_ptt = $conn->query("SELECT COUNT(*) as c FROM pegawai WHERE status_keaktifan != 'Tidak Aktif' AND (LOWER(status_pegawai)='tidak tetap' OR LOWER(status_pegawai)='tdk tetap' OR LOWER(jenis_pegawai)='tidak tetap' OR LOWER(jenis_pegawai)='tdk tetap')");
$pegawaiTidakTetap = ($q_ptt) ? $q_ptt->fetch_assoc()['c'] : 0;

// Jabfung breakdown
$jabfungBreakdown = [];
$dosenRows = $conn->query("SELECT id, jabfung_akademik FROM dosen WHERE status_keaktifan != 'Tidak Aktif'");
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

// Education breakdown for initial render
$eduDosenInit = [];
$resEduD = $conn->query("SELECT jenjang, COUNT(*) as count FROM pendidikan_dosen pd JOIN dosen d ON pd.dosen_id=d.id WHERE d.status_keaktifan != 'Tidak Aktif' GROUP BY jenjang ORDER BY count DESC");
if ($resEduD) {
    while($row = $resEduD->fetch_assoc()) {
        $j = strtoupper(trim($row['jenjang']));
        if ($j === '' || $j === '-') continue;
        $eduDosenInit[$j] = (int)$row['count'];
    }
}
$eduPegawaiInit = [];
$resEduP = $conn->query("SELECT jenjang, COUNT(*) as count FROM pendidikan_pegawai pp JOIN pegawai p ON pp.pegawai_id=p.id WHERE p.status_keaktifan != 'Tidak Aktif' GROUP BY jenjang ORDER BY count DESC");
if ($resEduP) {
    while($row = $resEduP->fetch_assoc()) {
        $j = strtoupper(trim($row['jenjang']));
        if ($j === '' || $j === '-') continue;
        $eduPegawaiInit[$j] = (int)$row['count'];
    }
}
// Merge edu
$combinedEduInit = [];
foreach (array_merge(array_keys($eduDosenInit), array_keys($eduPegawaiInit)) as $k) {
    $combinedEduInit[$k] = ($eduDosenInit[$k] ?? 0) + ($eduPegawaiInit[$k] ?? 0);
}
ksort($combinedEduInit);

// Prepare JSON for JS
$jabfungJsonLabels  = json_encode(array_keys($jabfungBreakdown));
$jabfungJsonValues  = json_encode(array_values($jabfungBreakdown));
$eduJsonLabels      = json_encode(array_keys($combinedEduInit));
$eduJsonValues      = json_encode(array_values($combinedEduInit));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | UNSERA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js - Use specific version for stability on InfinityFree -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --card-bg: #ffffff;
            --accent-color: #2563eb;
        }
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            padding: 24px;
            height: 100%;
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .card-title-premium {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stat-main-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            color: #2563eb;
            line-height: 1;
        }
        .stat-sub-item {
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
        }
        .stat-sub-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-sub-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
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
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slide-in 0.4s ease forwards;
        }
        @keyframes slide-in {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-light">

<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-0">Dashboard Admin</h2>
                <p class="text-secondary">Ringkasan data kepegawaian Universitas Serang Raya</p>
            </div>
            <div class="bg-white px-3 py-2 rounded-4 shadow-sm border">
                <small class="text-muted d-block" style="font-size: 0.7rem;">TERAKHIR DIPERBARUI</small>
                <span id="last-updated" class="fw-bold text-primary">--:--:--</span>
            </div>
        </div>

        <!-- Row 1: 3-Grid Stats -->
        <div class="row g-4 mb-4">
            <!-- Data Dosen -->
            <div class="col-xl-4 col-md-6">
                <div class="dashboard-card border-top border-primary border-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title-premium mb-0"><i class="fas fa-user-graduate text-primary"></i> Data Dosen</h5>
                        <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Total: <span id="stat-totalDosen"><?= $totalDosen ?></span></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">Tetap</div>
                                <div class="stat-sub-value" id="stat-dosenTetap"><?= $dosenTetap ?></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">Tdk Tetap</div>
                                <div class="stat-sub-value" id="stat-dosenTidakTetap"><?= $dosenTidakTetap ?></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">H.Base</div>
                                <div class="stat-sub-value" id="stat-dosenHomebase"><?= $dosenHomebase ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Pegawai -->
            <div class="col-xl-4 col-md-6">
                <div class="dashboard-card border-top border-success border-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title-premium mb-0"><i class="fas fa-users text-success"></i> Data Pegawai</h5>
                        <div class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Total: <span id="stat-totalPegawai"><?= $totalPegawai ?></span></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">Pegawai Tetap</div>
                                <div class="stat-sub-value text-success" id="stat-pegawaiTetap"><?= $pegawaiTetap ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">Tdk Tetap</div>
                                <div class="stat-sub-value text-success" id="stat-pegawaiTidakTetap"><?= $pegawaiTidakTetap ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Keaktifan -->
            <div class="col-xl-4 col-md-12">
                <div class="dashboard-card border-top border-warning border-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title-premium mb-0"><i class="fas fa-toggle-on text-warning"></i> Status Keaktifan</h5>
                        <div class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Dosen & Pegawai</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">Aktif</div>
                                <div class="stat-sub-value text-warning" id="stat-aktif"><?= ($conn->query("SELECT COUNT(*) as total FROM dosen WHERE status_keaktifan='Aktif'")->fetch_assoc()['total'] + $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_keaktifan='Aktif'")->fetch_assoc()['total']) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-sub-item text-center">
                                <div class="stat-sub-label">Tidak Aktif</div>
                                <div class="stat-sub-value text-warning" id="stat-tidakAktif"><?= ($conn->query("SELECT COUNT(*) as total FROM dosen WHERE status_keaktifan='Tidak Aktif'")->fetch_assoc()['total'] + $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_keaktifan='Tidak Aktif'")->fetch_assoc()['total']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Main Vertical Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="card-title-premium"><i class="fas fa-chart-bar text-primary"></i> Grafik Distribusi Kepegawaian</h5>
                    <div class="chart-wrapper">
                        <canvas id="grafikUtama"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Jabatan Fungsional -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <h5 class="card-title-premium"><i class="fas fa-briefcase text-warning"></i> Jabatan Fungsional (JAD)</h5>
                    <div id="jabfung-breakdown" class="d-flex flex-column gap-2">
                        <?php if(!empty($jabfungBreakdown)): foreach(array_slice($jabfungBreakdown, 0, 5) as $label => $val): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white">
                            <span class="small fw-bold text-secondary"><?= $label ?></span>
                            <span class="fw-bold text-dark"><?= $val ?></span>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center py-4 text-muted small">Belum ada data jabatan fungsional</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <h5 class="card-title-premium">Grafik Jabatan Fungsional</h5>
                    <div class="chart-wrapper">
                        <canvas id="grafikJabfung"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 4: Pendidikan -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <h5 class="card-title-premium"><i class="fas fa-graduation-cap text-info"></i> Riwayat Pendidikan</h5>
                    <div id="edu-breakdown" class="d-flex flex-column gap-2">
                        <?php if(!empty($combinedEduInit)): foreach($combinedEduInit as $label => $val): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white">
                            <span class="small fw-bold text-secondary"><?= $label ?></span>
                            <span class="fw-bold text-dark"><?= $val ?></span>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="text-center py-4 text-muted small">Belum ada data pendidikan</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <h5 class="card-title-premium">Grafik Kualifikasi Pendidikan</h5>
                    <div class="chart-wrapper">
                        <canvas id="grafikEdu"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library failed to load.');
        const wrappers = document.querySelectorAll('.chart-wrapper');
        wrappers.forEach(w => w.innerHTML = '<div class="alert alert-warning py-5 text-center">Grafik tidak dapat dimuat. Periksa koneksi internet.</div>');
        return;
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';

// Helper for shared chart options
const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
    },
    scales: {
        y: { beginAtZero: true, grid: { display: true, color: '#f1f5f9' }, border: { display: false } },
        x: { grid: { display: false }, border: { display: false } }
    }
};

const verticalBarOptions = {
    ...barOptions,
    indexAxis: 'x',
    scales: {
        y: { beginAtZero: true, grid: { display: true, color: '#f1f5f9' }, border: { display: false } },
        x: { 
            grid: { display: false }, 
            border: { display: false },
            ticks: {
                maxRotation: 45,
                minRotation: 45
            }
        }
    }
};

// 1. Grafik Utama (Vertical Bar)
const myChartUtama = new Chart(document.getElementById('grafikUtama'), {
    type: 'bar',
    data: {
        labels: ['Dosen Tetap', 'Dosen Tdk Tetap', 'Dosen Homebase', 'Pegawai Tetap', 'Pegawai Tdk Tetap'],
        datasets: [{
            label: 'Jumlah',
            data: [<?= $dosenTetap ?>, <?= $dosenTidakTetap ?>, <?= $dosenHomebase ?>, <?= $pegawaiTetap ?>, <?= $pegawaiTidakTetap ?>],
            backgroundColor: ['#2563eb', '#60a5fa', '#93c5fd', '#10b981', '#34d399'],
            borderRadius: 8,
            barThickness: 40
        }]
    },
    options: barOptions
});

// 2. Grafik JAD (Vertical Bar) - diisi langsung dari PHP
const myChartJabfung = new Chart(document.getElementById('grafikJabfung'), {
    type: 'bar',
    data: {
        labels: <?= $jabfungJsonLabels ?>,
        datasets: [{
            label: 'Jumlah',
            data: <?= $jabfungJsonValues ?>,
            backgroundColor: '#f59e0b',
            borderRadius: 6,
            barThickness: 30
        }]
    },
    options: verticalBarOptions
});

// 3. Grafik Edu (Vertical Bar) - diisi langsung dari PHP
const myChartEdu = new Chart(document.getElementById('grafikEdu'), {
    type: 'bar',
    data: {
        labels: <?= $eduJsonLabels ?>,
        datasets: [{
            label: 'Jumlah',
            data: <?= $eduJsonValues ?>,
            backgroundColor: '#0ea5e9',
            borderRadius: 6,
            barThickness: 30
        }]
    },
    options: verticalBarOptions
});

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

let prev = { totalDosen: <?= $totalDosen ?>, totalPegawai: <?= $totalPegawai ?> };

function fetchStats() {
    fetch('api_realtime.php?action=stats')
        .then(r => r.json())
        .then(d => {
            animateValue(document.getElementById('stat-totalDosen'), d.totalDosen);
            animateValue(document.getElementById('stat-totalPegawai'), d.totalPegawai);
            animateValue(document.getElementById('stat-dosenTetap'), d.dosenTetap);
            animateValue(document.getElementById('stat-dosenTidakTetap'), d.dosenTidakTetap);
            animateValue(document.getElementById('stat-dosenHomebase'), d.dosenHomebase);
            animateValue(document.getElementById('stat-pegawaiTetap'), d.pegawaiTetap);
            animateValue(document.getElementById('stat-pegawaiTidakTetap'), d.pegawaiTidakTetap);
            animateValue(document.getElementById('stat-aktif'), d.totalAktif);
            animateValue(document.getElementById('stat-tidakAktif'), d.totalTidakAktif);

            // Update Utama
            myChartUtama.data.datasets[0].data = [d.dosenTetap, d.dosenTidakTetap, d.dosenHomebase, d.pegawaiTetap, d.pegawaiTidakTetap];
            myChartUtama.update();

        // Refresh Jabfung chart if data tersedia
        if (d.jabfungBreakdown && Object.keys(d.jabfungBreakdown).length > 0) {
            const labels = Object.keys(d.jabfungBreakdown);
            const vals   = Object.values(d.jabfungBreakdown);
            myChartJabfung.data.labels = labels;
            myChartJabfung.data.datasets[0].data = vals;
            myChartJabfung.update('none');

            document.getElementById('jabfung-breakdown').innerHTML = labels.map((l, i) => `
                <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white">
                    <span class="small fw-bold text-secondary">${l}</span>
                    <span class="fw-bold text-dark">${vals[i]}</span>
                </div>
            `).join('');
        }

        // Refresh Edu chart
        if (d.eduDosen || d.eduPegawai) {
                let combinedEdu = {};
                // Sum up education from both categories
                const allEdu = {...(d.eduDosen || {}), ...(d.eduPegawai || {})};
                Object.keys(allEdu).forEach(key => {
                    combinedEdu[key] = (d.eduDosen?.[key] || 0) + (d.eduPegawai?.[key] || 0);
                });
                
                const labels = Object.keys(combinedEdu).sort();
                const vals = labels.map(l => combinedEdu[l]);
                
                myChartEdu.data.labels = labels;
                myChartEdu.data.datasets[0].data = vals;
                myChartEdu.update();

                document.getElementById('edu-breakdown').innerHTML = labels.map((l, i) => `
                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white">
                        <span class="small fw-bold text-secondary">${l}</span>
                        <span class="fw-bold text-dark">${vals[i]}</span>
                    </div>
                `).join('');
            }

            document.getElementById('last-updated').textContent = d.timestamp || '--:--:--';
            prev.totalDosen = d.totalDosen;
            prev.totalPegawai = d.totalPegawai;
        })
        .catch(err => {
            console.error('Fetch error:', err);
            document.getElementById('last-updated').textContent = 'Error';
        });
}

fetchStats();
    setInterval(fetchStats, 10000);
})();
</script>

</body>
</html>

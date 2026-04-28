<?php
// Top Navigation Bar Component - Modern & Premium
$titles = [
    'dashboard.php'     => 'Dashboard Overview',
    'daftar_dosen.php'  => 'Manajemen Dosen',
    'input_dosen.php'   => 'Tambah Data Dosen',
    'form_edit_dosen.php'=> 'Edit Data Dosen',
    'detail_dosen.php'  => 'Profil Lengkap Dosen',
    'data_pegawai.php'  => 'Manajemen Pegawai',
    'input_pegawai.php' => 'Tambah Data Pegawai',
    'form_edit_pegawai.php' => 'Edit Data Pegawai',
    'detail_pegawai.php'=> 'Profil Lengkap Pegawai',
    'jenis_surat.php'   => 'Kategori Surat',
    'data_surat.php'    => 'Arsip Surat Digital',
];
$cp = basename($_SERVER['PHP_SELF']);
$page_title_text = $titles[$cp] ?? 'Kepegawaian Portal';
$admin_nama = htmlspecialchars($_SESSION['admin_nama'] ?? 'Administrator');
$admin_initial = strtoupper(substr($admin_nama, 0, 1));
?>

<nav class="top-navbar px-4">
    <div class="navbar-left d-flex align-items-center">
        <button class="hamburger-btn me-3" id="sidebarToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div class="d-flex align-items-center gap-3">
            <img src="download.png" alt="UNSERA" class="d-lg-none" style="height: 30px;">
            <div class="d-none d-lg-block">
                <h5 class="fw-bold text-dark mb-0 fs-6"><?= $page_title_text ?></h5>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.75rem;">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Admin</a></li>
                        <li class="breadcrumb-item active text-primary fw-600" aria-current="page"><?= $page_title_text ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="navbar-right d-flex align-items-center gap-3">
        <!-- System Time -->
        <div class="d-none d-md-block text-end me-2">
            <div class="text-muted" style="font-size: 0.7rem; font-weight: 500;"><?= date('l, d F Y') ?></div>
            <div class="fw-bold" style="font-size: 0.8rem; color: #1e293b;" id="nav-clock">00:00:00</div>
        </div>

        <!-- Notifications -->
        <div class="dropdown">
            <div class="nav-icon-btn d-flex align-items-center justify-content-center position-relative" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px; border-radius: 12px; background: #f8fafc; border: 1px solid #f1f5f9; cursor: pointer;">
                <i class="fas fa-bell text-muted"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-top: 8px; margin-left: -8px;"></span>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-2 overflow-hidden" style="border-radius: 16px; min-width: 320px;">
                <li class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Notifikasi</h6>
                    <?php
                    // Fetch latest additions with IDs
                    $recentDosen = $conn->query("SELECT id, nama_lengkap, 'Dosen' as tipe FROM dosen ORDER BY id DESC LIMIT 5");
                    $recentPegawai = $conn->query("SELECT id, nama_lengkap, 'Pegawai' as tipe FROM pegawai ORDER BY id DESC LIMIT 5");
                    $notifs = [];
                    while($rd = $recentDosen->fetch_assoc()) $notifs[] = $rd;
                    while($rp = $recentPegawai->fetch_assoc()) $notifs[] = $rp;
                    ?>
                    <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size: 0.7rem;"><?= count($notifs) ?> Baru</span>
                </li>
                <div style="max-height: 350px; overflow-y: auto;">
                    <?php if(empty($notifs)): ?>
                        <li class="p-4 text-center text-muted small">Tidak ada notifikasi baru</li>
                    <?php else: foreach($notifs as $n): 
                        $link = ($n['tipe'] == 'Dosen') ? "detail_dosen.php?id=" . $n['id'] : "detail_pegawai.php?id=" . $n['id'];
                    ?>
                    <li>
                        <a class="dropdown-item p-3 border-bottom d-flex gap-3 align-items-start" href="<?= $link ?>">
                            <div class="<?= $n['tipe'] == 'Dosen' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' ?> p-2 rounded-3">
                                <i class="fas <?= $n['tipe'] == 'Dosen' ? 'fa-user-graduate' : 'fa-user-tie' ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold small"><?= $n['tipe'] ?> Baru Terdaftar</div>
                                <div class="text-muted small" style="white-space: normal;"><?= htmlspecialchars($n['nama_lengkap']) ?> telah ditambahkan.</div>
                                <div class="text-primary mt-1" style="font-size: 0.65rem;">Baru saja</div>
                            </div>
                        </a>
                    </li>
                    <?php endforeach; endif; ?>
                </div>
                <li class="bg-light"><a class="dropdown-item py-2 text-center small text-primary fw-bold" href="notifikasi.php">Lihat Semua</a></li>
            </ul>
        </div>

        <!-- User Profile -->
        <div class="dropdown">
            <div class="user-profile-modern d-flex align-items-center gap-3 ps-3 pe-1 py-1 rounded-pill cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                <div class="user-info-text d-none d-sm-block text-end">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem; line-height: 1;"><?= $admin_nama ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Administrator</div>
                </div>
                <div class="user-avatar-circle" style="width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                    <?= $admin_initial ?>
                </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2" style="border-radius: 12px; min-width: 200px;">
                <li class="px-3 py-2 d-sm-none border-bottom mb-2">
                    <div class="fw-bold text-dark small"><?= $admin_nama ?></div>
                    <div class="text-muted small">Administrator</div>
                </li>
                <li><a class="dropdown-item py-2 rounded-2 mb-1" href="profile.php"><i class="fas fa-user-circle me-2 opacity-50"></i> Profil Saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 rounded-2 text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
            </ul>
        </div>
    </div>
</nav>

<script>
function updateClock() {
    const now = new Date();
    const clock = document.getElementById('nav-clock');
    if (clock) {
        clock.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
    }
}
setInterval(updateClock, 1000);
updateClock();
</script>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('visible');
        btn.classList.toggle('open');
        document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
    }

    if (btn) btn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024 && sidebar && sidebar.classList.contains('mobile-open')) {
            toggleSidebar();
        }
    });
});
</script>

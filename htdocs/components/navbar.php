<?php
// Top Navigation Bar Component
$titles = [
    'dashboard.php'     => 'Dashboard',
    'daftar_dosen.php'  => 'Daftar Dosen',
    'input_dosen.php'   => 'Tambah Dosen',
    'form_edit_dosen.php'=> 'Edit Dosen',
    'detail_dosen.php'  => 'Detail Dosen',
    'edit_dosen.php'    => 'Edit Dosen',
    'data_pegawai.php'  => 'Daftar Pegawai',
    'input_pegawai.php' => 'Tambah Pegawai',
    'edit_pegawai.php'  => 'Edit Pegawai',
    'detail_pegawai.php'=> 'Detail Pegawai',
    'jenis_surat.php'   => 'Kelola Jenis Surat',
    'data_surat.php'    => 'Data Surat',
];
$cp = basename($_SERVER['PHP_SELF']);
$auto_title = $titles[$cp] ?? 'Halaman';
$page_title = $page_title ?? $auto_title;
$admin_nama = htmlspecialchars($_SESSION['admin_nama'] ?? 'Administrator');
$admin_initial = strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1));
?>

<!-- Dark overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="top-navbar">
    <div class="navbar-left">
        <!-- Hamburger — only visible on mobile via CSS -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Sidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <span class="page-title-nav"><?= $page_title ?></span>
    </div>


</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn     = document.getElementById('hamburgerBtn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!btn || !sidebar || !overlay) return;

    function openSidebar() {
        sidebar.classList.add('mobile-open');
        overlay.classList.add('visible');
        btn.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('visible');
        btn.classList.remove('open');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', function() {
        sidebar.classList.contains('mobile-open') ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('visible');
            btn.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
});
</script>

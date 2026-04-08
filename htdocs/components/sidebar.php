<?php
$current_page = basename($_SERVER['PHP_SELF']);
$dosen_pages  = ['input_dosen.php','daftar_dosen.php','form_edit_dosen.php','edit_dosen.php','detail_dosen.php','hapus_dosen.php'];
$pegawai_pages = ['input_pegawai.php','data_pegawai.php','detail_pegawai.php','edit_pegawai.php'];
$surat_pages = ['jenis_surat.php','data_surat.php','tambah_jenis_surat.php'];
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="download.png" alt="Logo UNSERA" class="sidebar-logo">
        <div class="brand-text">
            <h2>Kepegawaian</h2>
            <span>Universitas Serang Raya</span>
        </div>
    </div>

    <ul>
        <li class="sidebar-label">MENU UTAMA</li>

        <li>
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="#" onclick="toggleSubmenu('sub-dosen', this); return false;"
               class="submenu-toggle <?= in_array($current_page, $dosen_pages) ? 'open' : '' ?>">
                <i class="fas fa-chalkboard-teacher"></i> Data Dosen
            </a>
            <ul id="sub-dosen" class="submenu <?= in_array($current_page, $dosen_pages) ? 'show' : '' ?>">
                <li>
                    <a href="input_dosen.php" class="<?= $current_page == 'input_dosen.php' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle"></i> Tambah Dosen
                    </a>
                </li>
                <li>
                    <a href="edit_dosen.php" class="<?= in_array($current_page, ['edit_dosen.php', 'form_edit_dosen.php']) ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Edit Dosen
                    </a>
                </li>
                <li>
                    <a href="daftar_dosen.php" class="<?= in_array($current_page, ['daftar_dosen.php','detail_dosen.php']) ? 'active' : '' ?>">
                        <i class="fas fa-list"></i> Daftar Dosen
                    </a>
                </li>
                <li>
                    <a href="hapus_dosen.php" class="<?= $current_page == 'hapus_dosen.php' ? 'active' : '' ?>">
                        <i class="fas fa-trash-alt"></i> Hapus Dosen
                    </a>
                </li>
            </ul>
        </li>

        <li>
            <a href="#" onclick="toggleSubmenu('sub-pegawai', this); return false;"
               class="submenu-toggle <?= in_array($current_page, $pegawai_pages) ? 'open' : '' ?>">
                <i class="fas fa-users-cog"></i> Data Pegawai
            </a>
            <ul id="sub-pegawai" class="submenu <?= in_array($current_page, $pegawai_pages) ? 'show' : '' ?>">
                <li>
                    <a href="input_pegawai.php" class="<?= $current_page == 'input_pegawai.php' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle"></i> Tambah Pegawai
                    </a>
                </li>
                <li>
                    <a href="edit_pegawai.php" class="<?= in_array($current_page, ['edit_pegawai.php', 'form_edit_pegawai.php']) ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Edit Pegawai
                    </a>
                </li>
                <li>
                    <a href="data_pegawai.php" class="<?= in_array($current_page, ['data_pegawai.php','detail_pegawai.php']) ? 'active' : '' ?>">
                        <i class="fas fa-list"></i> Daftar Pegawai
                    </a>
                </li>
                <li>
                    <a href="hapus_pegawai.php" class="<?= $current_page == 'hapus_pegawai.php' ? 'active' : '' ?>">
                        <i class="fas fa-trash-alt"></i> Hapus Pegawai
                    </a>
                </li>
            </ul>
        </li>

        <li class="sidebar-label" style="margin-top: 10px;">MANAJEMEN SURAT</li>

        <li>
            <a href="#" onclick="toggleSubmenu('sub-surat', this); return false;"
               class="submenu-toggle <?= in_array($current_page, $surat_pages) ? 'open' : '' ?>">
                <i class="fas fa-envelope-open-text"></i> Jenis Surat
            </a>
            <ul id="sub-surat" class="submenu <?= in_array($current_page, $surat_pages) ? 'show' : '' ?>">
                <?php
                if(isset($conn)) {
                    $res_jenis = $conn->query("SELECT * FROM jenis_surat ORDER BY id ASC");
                    if($res_jenis && $res_jenis->num_rows > 0) {
                        while($js = $res_jenis->fetch_assoc()) {
                            $is_active = ($current_page == 'data_surat.php' && isset($_GET['jenis_id']) && $_GET['jenis_id'] == $js['id']) ? 'active' : '';
                            echo '<li><a href="data_surat.php?jenis_id='.$js['id'].'" class="'.$is_active.'"><i class="fas fa-file-alt"></i> '.htmlspecialchars($js['nama_jenis']).'</a></li>';
                        }
                    }
                }
                ?>
                <li>
                    <a href="jenis_surat.php" class="<?= $current_page == 'jenis_surat.php' ? 'active' : '' ?>" style="font-weight: 600;">
                        <i class="fas fa-cog"></i> Kelola Jenis
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
function toggleSubmenu(id, el) {
    const sub = document.getElementById(id);
    const isOpen = sub.classList.contains('show');
    document.querySelectorAll('.sidebar .submenu').forEach(s => s.classList.remove('show'));
    document.querySelectorAll('.sidebar .submenu-toggle').forEach(t => t.classList.remove('open'));
    if (!isOpen) {
        sub.classList.add('show');
        el.classList.add('open');
    }
}
</script>

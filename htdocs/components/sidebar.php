<?php
$current_page = basename($_SERVER['PHP_SELF']);
$dosen_pages  = ['input_dosen.php','daftar_dosen.php','form_edit_dosen.php','edit_dosen.php','detail_dosen.php','hapus_dosen.php'];
$pegawai_pages = ['input_pegawai.php','data_pegawai.php','detail_pegawai.php','edit_pegawai.php','form_edit_pegawai.php'];
$surat_pages = ['jenis_surat.php','data_surat.php','tambah_jenis_surat.php','struktur_organisasi.php'];
$inactive_pages = ['data_tidak_aktif.php'];
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="download.png" alt="Logo UNSERA" class="sidebar-logo">
        <div class="brand-text">
            <h2>Kepegawaian</h2>
            <span>UNSERA PORTAL</span>
        </div>
    </div>

    <ul>
        <li class="sidebar-label">MENU UTAMA</li>

        <li>
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-columns"></i> Dashboard
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
                        <i class="fas fa-plus"></i> Tambah Dosen
                    </a>
                </li>
                <li>
                    <a href="edit_dosen.php" class="<?= in_array($current_page, ['edit_dosen.php', 'form_edit_dosen.php']) ? 'active' : '' ?>">
                        <i class="fas fa-user-edit"></i> Edit Dosen
                    </a>
                </li>
                <li>
                    <a href="daftar_dosen.php" class="<?= in_array($current_page, ['daftar_dosen.php','detail_dosen.php']) ? 'active' : '' ?>">
                        <i class="fas fa-table"></i> Daftar Dosen
                    </a>
                </li>
                <li>
                    <a href="hapus_dosen.php" class="<?= $current_page == 'hapus_dosen.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-minus"></i> Hapus Dosen
                    </a>
                </li>
            </ul>
        </li>

        <li>
            <a href="#" onclick="toggleSubmenu('sub-pegawai', this); return false;"
               class="submenu-toggle <?= in_array($current_page, $pegawai_pages) ? 'open' : '' ?>">
                <i class="fas fa-user-tie"></i> Data Pegawai
            </a>
            <ul id="sub-pegawai" class="submenu <?= in_array($current_page, $pegawai_pages) ? 'show' : '' ?>">
                <li>
                    <a href="input_pegawai.php" class="<?= $current_page == 'input_pegawai.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-plus"></i> Tambah Pegawai
                    </a>
                </li>
                <li>
                    <a href="edit_pegawai.php" class="<?= in_array($current_page, ['edit_pegawai.php', 'form_edit_pegawai.php']) ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Edit Pegawai
                    </a>
                </li>
                <li>
                    <a href="data_pegawai.php" class="<?= in_array($current_page, ['data_pegawai.php','detail_pegawai.php']) ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Daftar Pegawai
                    </a>
                </li>
                <li>
                    <a href="hapus_pegawai.php" class="<?= $current_page == 'hapus_pegawai.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-times"></i> Hapus Pegawai
                    </a>
                </li>
            </ul>
        </li>

        <li>
            <a href="#" onclick="toggleSubmenu('sub-inactive', this); return false;"
               class="submenu-toggle <?= in_array($current_page, $inactive_pages) ? 'open' : '' ?>">
                <i class="fas fa-user-slash"></i> Data Tidak Aktif
            </a>
            <ul id="sub-inactive" class="submenu <?= in_array($current_page, $inactive_pages) ? 'show' : '' ?>">
                <li>
                    <a href="data_tidak_aktif.php?type=dosen" class="<?= ($current_page == 'data_tidak_aktif.php' && ($_GET['type']??'') == 'dosen') ? 'active' : '' ?>">
                        <i class="fas fa-user-lock"></i> Dosen
                    </a>
                </li>
                <li>
                    <a href="data_tidak_aktif.php?type=pegawai" class="<?= ($current_page == 'data_tidak_aktif.php' && ($_GET['type']??'') == 'pegawai') ? 'active' : '' ?>">
                        <i class="fas fa-user-lock"></i> Pegawai
                    </a>
                </li>
            </ul>
        </li>

        <li>
            <a href="struktur_organisasi.php" class="<?= $current_page == 'struktur_organisasi.php' ? 'active' : '' ?>">
                <i class="fas fa-sitemap"></i> Struktur Organisasi
            </a>
        </li>

        <li class="sidebar-label">PENGELOLAAN</li>

        <li>
            <a href="#" onclick="toggleSubmenu('sub-surat', this); return false;"
               class="submenu-toggle <?= in_array($current_page, $surat_pages) ? 'open' : '' ?>">
                <i class="fas fa-file-signature"></i> Jenis Surat
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
                    <a href="jenis_surat.php" class="<?= $current_page == 'jenis_surat.php' ? 'active' : '' ?>">
                        <i class="fas fa-sliders-h"></i> Kelola Jenis
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout Akun
        </a>
    </div>
</div>

<script>
function toggleSubmenu(id, el) {
    const sub = document.getElementById(id);
    const isOpen = sub.classList.contains('show');
    // Close all other submenus
    document.querySelectorAll('.sidebar .submenu').forEach(s => { if (s.id !== id) s.classList.remove('show'); });
    document.querySelectorAll('.sidebar .submenu-toggle').forEach(t => { if (t !== el) t.classList.remove('open'); });
    // Toggle current
    if (isOpen) {
        sub.classList.remove('show');
        el.classList.remove('open');
    } else {
        sub.classList.add('show');
        el.classList.add('open');
    }
}
</script>

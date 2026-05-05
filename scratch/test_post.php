<?php
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = [
    'id' => 1,
    'nama_lengkap' => 'Test Updated',
    'alamat' => 'Test',
    'ttl_tempat' => 'Test',
    'ttl_tanggal' => '1990-01-01',
    'status_pribadi' => 'Menikah',
    'jenis_dosen' => 'Dosen Tetap',
    'jabatan_struktural' => 'Kepala',
    'tmk' => '2020-01-01',
    'tmtk' => '2020-01-01',
    'ket_tidak_kerja' => '',
    'status_utama' => 'Aktif',
    'status_keaktifan' => '-',
    'tgl_mulai_tidak_bekerja' => null,
    'status_dosen' => ['Aktif'],
    'tmt_status' => ['2020-01-01'],
    'jabfung_akademik' => ['Asisten Ahli'],
    'tmt_jabfung' => ['2021-01-01']
];
// Mocking session
session_start();
$_SESSION['admin_id'] = 1;

require 'htdocs/form_edit_dosen.php';
echo "\nDONE\n";

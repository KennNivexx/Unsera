<?php
$sql = "UPDATE dosen SET 
    nama_lengkap = ?, alamat = ?, ttl_tempat = ?, ttl_tanggal = ?, 
    nip = ?, nidn = ?, nuptk = ?, status_dosen = ?, status_pribadi = ?, 
    dok_ktp = ?, dok_kk = ?, jenis_dosen = ?, jabatan_struktural = ?, 
    tmk = ?, tmtk = ?, ket_tidak_kerja = ?, dok_tidak_kerja = ?,
    jabfung_akademik = ?, tmt_jabfung = ?, dok_jabfung = ?,
    gol_lldikti = ?, tmt_gol_lldikti = ?, dok_gol_lldikti = ?,
    gol_yayasan = ?, tmt_gol_yayasan = ?, dok_gol_yayasan = ?,
    homebase_prodi = ?, unit_kerja = ?, no_serdos = ?, tmt_serdos = ?, dok_serdos = ?, 
    riwayat_pendidikan = ?, foto_profil = ?, status_keaktifan = ?, 
    keterangan_keaktifan = ?, tgl_mulai_tidak_bekerja = ?,
    dok_status_dosen = ?, dok_tmb = ?, dok_berhenti_bertugas = ?, dok_penugasan_struktural = ?
    WHERE id = ?";

echo "Question marks: " . substr_count($sql, "?") . "\n";

$types = "ssssssssssssssssssssssssssssssssssssssssi";
echo "Types length: " . strlen($types) . "\n";
?>

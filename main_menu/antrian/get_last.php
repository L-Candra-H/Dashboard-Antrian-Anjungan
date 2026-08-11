<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

date_default_timezone_set('Asia/Jakarta'); // pastikan timezone WIB
$today = date('Y-m-d');

include_once '../conf/conf.php';
include_once '../conf/helpers.php';
header('Content-Type: application/json');

/* --- Ambil pasien aktif dari antripoli (status=1) --- */
$sql_called = "SELECT a.no_rawat, r.no_reg, r.kd_dokter, r.kd_poli, ps.nm_pasien,
                      pl.nm_poli, d.nm_dokter, pj.png_jawab, a.status
               FROM antripoli a
               JOIN reg_periksa r ON a.no_rawat = r.no_rawat
               JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
               JOIN poliklinik pl ON a.kd_poli = pl.kd_poli
               JOIN dokter d ON a.kd_dokter = d.kd_dokter
               JOIN penjab pj ON r.kd_pj = pj.kd_pj
               WHERE a.status = '1'
               ORDER BY a.no_rawat DESC
               LIMIT 1";
$res_called = bukaquery2($sql_called);
$called = mysqli_fetch_assoc($res_called);

if ($called) {
    // Jalur resmi: nm_pasien dibersihkan
    $called['nm_pasien'] = cleanNamaPasien($called['nm_pasien']);

    // Update pasien aktif jadi status=2 (panggil)
    bukaquery2("UPDATE antripoli 
                SET status='2' 
                WHERE no_rawat='{$called['no_rawat']}'");

    // Pasien lain yang sebelumnya status=2 → ubah ke status=3
    bukaquery2("UPDATE antripoli 
                SET status='3'
                WHERE status='2' AND no_rawat <> '{$called['no_rawat']}'");
}

/* --- Ambil panggilan terakhir dari antripoli (status=2 atau 3) --- */
$sql_last = "SELECT a.no_rawat, r.no_reg, r.kd_dokter, r.kd_poli, ps.nm_pasien,
                    pl.nm_poli, d.nm_dokter, pj.png_jawab, a.status
             FROM antripoli a
             JOIN reg_periksa r ON a.no_rawat = r.no_rawat
             JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
             JOIN poliklinik pl ON a.kd_poli = pl.kd_poli
             JOIN dokter d ON a.kd_dokter = d.kd_dokter
             JOIN penjab pj ON r.kd_pj = pj.kd_pj
             WHERE a.status IN ('2','3')
             ORDER BY a.no_rawat DESC
             LIMIT 1";
$res_last = bukaquery2($sql_last);
$last = mysqli_fetch_assoc($res_last);

if ($last) {
    $last['nm_pasien_bersih'] = cleanNamaPasien($last['nm_pasien']);
    $last['nm_pasien'] = $last['nm_pasien_bersih']; // timpa agar aman
}


/* --- Ambil daftar pasien hari ini (Belum) --- */
$sql_pasien = "SELECT r.no_rawat, r.no_rkm_medis, r.no_reg, r.jam_reg,
                      r.kd_dokter, r.kd_poli, r.kd_pj, r.stts,
                      ps.nm_pasien, d.nm_dokter, p.nm_poli, pj.png_jawab,
                      a.status AS status_antripoli
               FROM reg_periksa r
               JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
               JOIN dokter d ON r.kd_dokter = d.kd_dokter
               JOIN poliklinik p ON r.kd_poli = p.kd_poli
               JOIN penjab pj ON r.kd_pj = pj.kd_pj
               LEFT JOIN antripoli a ON a.no_rawat = r.no_rawat
               WHERE r.tgl_registrasi = '$today'
                 AND r.stts = 'Belum'
               ORDER BY r.jam_reg ASC";
$res_pasien = bukaquery($sql_pasien);
$pasienHariIni = [];

while($row = mysqli_fetch_assoc($res_pasien)) {
    $row['nm_pasien_bersih'] = cleanNamaPasien($row['nm_pasien']);
    $pasienHariIni[] = $row;
}

/* --- Ambil daftar pasien bypass (status=2 dan 3, hari ini) --- */
$sql_pasien_bypass = "SELECT a.no_rawat, r.no_reg,r.kd_poli, ps.nm_pasien, d.nm_dokter, p.nm_poli, pj.png_jawab,
                             a.status AS status_antripoli
                      FROM antripoli a
                      JOIN reg_periksa r ON a.no_rawat = r.no_rawat
                      JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                      JOIN dokter d ON a.kd_dokter = d.kd_dokter
                      JOIN poliklinik p ON a.kd_poli = p.kd_poli
                      JOIN penjab pj ON r.kd_pj = pj.kd_pj
                      WHERE a.status IN ('2','3')
                        AND r.tgl_registrasi = '$today'
                      ORDER BY r.jam_reg ASC";
$res_pasien_bypass = bukaquery($sql_pasien_bypass);
$pasienBypass = [];

while($row = mysqli_fetch_assoc($res_pasien_bypass)) {
    $row['nm_pasien_bersih'] = cleanNamaPasien($row['nm_pasien']);
    $row['nm_pasien'] = $row['nm_pasien_bersih'];
    $pasienBypass[] = $row;
}

/* --- Output JSON lengkap --- */
echo json_encode([
    'antrian'     => $pasienHariIni,   // daftar resmi dari reg_periksa (Belum)
    'calledNow'   => $called ?: null,  // pasien aktif (nm_pasien sudah dibersihkan)
    'calledLast'  => $last ?: null,    // panggilan terakhir (nm_pasien sudah dibersihkan)
    'pasienHariIni' => $pasienHariIni,
    'bypass'      => $pasienBypass     // daftar bypass dari antripoli (status 2/3 hari ini, nm_pasien mentah + nm_pasien_bersih)
], JSON_UNESCAPED_UNICODE);

?>

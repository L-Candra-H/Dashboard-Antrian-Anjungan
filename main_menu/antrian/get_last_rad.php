<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

date_default_timezone_set('Asia/Jakarta'); // WIB
$today = date('Y-m-d');

include_once '../conf/conf.php';
header('Content-Type: application/json');

/* --- Ambil panggilan sampel terakhir --- */
$sql_sampel_rad = "SELECT pl.no_rawat, r.no_reg, r.kd_dokter, r.kd_poli,
                         ps.nm_pasien, d.nm_dokter, pl.tgl_sampel, pl.jam_sampel
                  FROM permintaan_radiologi pl
                  JOIN reg_periksa r ON pl.no_rawat = r.no_rawat
                  JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                  JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
                  WHERE pl.status='ralan' 
                    AND pl.tgl_permintaan='$today' 
                    AND pl.tgl_sampel='$today'
                  ORDER BY pl.jam_sampel DESC LIMIT 1";
$lastSampel = mysqli_fetch_assoc(bukaquery2($sql_sampel_rad));

/* --- Ambil panggilan hasil terakhir --- */
$sql_hasil_rad = "SELECT pl.no_rawat, r.no_reg, r.kd_dokter, r.kd_poli,
                        ps.nm_pasien, d.nm_dokter, pl.tgl_hasil, pl.jam_hasil
                 FROM permintaan_radiologi pl
                 JOIN reg_periksa r ON pl.no_rawat = r.no_rawat
                 JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                 JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
                 WHERE pl.status='ralan' 
                   AND pl.tgl_permintaan='$today' 
                   AND pl.tgl_hasil='$today'
                 ORDER BY pl.jam_hasil DESC LIMIT 1";
$lastHasil = mysqli_fetch_assoc(bukaquery2($sql_hasil_rad));

/* --- Cek status antrian --- */
$statusSampel = mysqli_fetch_assoc(bukaquery2("SELECT status FROM antriradiologi LIMIT 1"));
$statusHasil  = mysqli_fetch_assoc(bukaquery2("SELECT status FROM antriradiologi2 LIMIT 1"));

$calledSampelRAD = null;
$calledHasilRAD  = null;

/* --- Logika sampel --- */
if ($lastSampel) {
    if ($statusSampel && $statusSampel['status'] == '1') {
        $calledSampelRAD = $lastSampel;
        $calledSampelRAD['mode'] = 'active';
        $calledSampelRAD['timestamp'] = date('Y-m-d H:i:s');
        bukaquery2("UPDATE antriradiologi SET status='0'");
    } else {
        $calledSampelRAD = $lastSampel;
        $calledSampelRAD['mode'] = 'standby';
        $calledSampelRAD['timestamp'] = date('Y-m-d H:i:s');
    }
} else {
    $calledSampelRAD = null;
}

/* --- Logika hasil --- */
if ($lastHasil) {
    if ($statusHasil && $statusHasil['status'] == '1') {
        $calledHasilRAD = $lastHasil;
        $calledHasilRAD['mode'] = 'active';
        $calledHasilRAD['timestamp'] = date('Y-m-d H:i:s');
        bukaquery2("UPDATE antriradiologi2 SET status='0'");
    } else {
        $calledHasilRAD = $lastHasil;
        $calledHasilRAD['mode'] = 'standby';
        $calledHasilRAD['timestamp'] = date('Y-m-d H:i:s');
    }
} else {
    $calledHasilRAD = null;
}

/* --- Ambil daftar permintaan radiologi hari ini --- */
$sql_permintaan_rad = "SELECT r.no_reg, ps.nm_pasien, d.nm_dokter,
                             IF(pl.jam_permintaan='00:00:00','',pl.jam_permintaan) AS jam_permintaan,
                             IF(pl.jam_sampel='00:00:00','',pl.jam_sampel) AS jam_sampel,
                             IF(pl.jam_hasil='00:00:00','',pl.jam_hasil) AS jam_hasil
                      FROM permintaan_radiologi pl
                      JOIN reg_periksa r ON pl.no_rawat = r.no_rawat
                      JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                      JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
                      WHERE pl.status='ralan' AND pl.tgl_permintaan='$today'
                      ORDER BY pl.jam_permintaan DESC";
$res_permintaan_rad = bukaquery($sql_permintaan_rad);
$permintaanHariIniRAD = [];
while($row = mysqli_fetch_assoc($res_permintaan_rad)) {
    $permintaanHariIniRAD[] = $row;
}

/* --- Output JSON --- */
echo json_encode([
    'antrianrad'              => $permintaanHariIniRAD,
    'calledSampelRAD'      => $calledSampelRAD,
    'calledHasilRAD'       => $calledHasilRAD,
    'permintaanHariIniRAD' => $permintaanHariIniRAD
], JSON_UNESCAPED_UNICODE);
?>

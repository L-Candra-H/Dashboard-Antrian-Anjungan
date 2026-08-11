<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

date_default_timezone_set('Asia/Jakarta'); // WIB
$today = date('Y-m-d');

include_once '../conf/conf.php';
include_once '../conf/helpers.php'; // pastikan ada cleanNamaPasien()

function getDataMB($today) {
    /* --- Ambil panggilan sampel terakhir --- */
    $sql_sampel_mb = "SELECT pl.no_rawat, r.no_reg, r.kd_dokter, r.kd_poli,
                             ps.nm_pasien, d.nm_dokter, pl.tgl_sampel, pl.jam_sampel
                      FROM permintaan_labmb pl
                      JOIN reg_periksa r ON pl.no_rawat = r.no_rawat
                      JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                      JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
                      WHERE pl.status='ralan' 
                        AND DATE(pl.tgl_permintaan) = '$today'
                        AND DATE(pl.tgl_sampel) = '$today'
                      ORDER BY pl.jam_sampel DESC LIMIT 1";
    $lastSampel = mysqli_fetch_assoc(bukaquery2($sql_sampel_mb));

    /* --- Ambil panggilan hasil terakhir --- */
    $sql_hasil_mb = "SELECT pl.no_rawat, r.no_reg, r.kd_dokter, r.kd_poli,
                            ps.nm_pasien, d.nm_dokter, pl.tgl_hasil, pl.jam_hasil
                     FROM permintaan_labmb pl
                     JOIN reg_periksa r ON pl.no_rawat = r.no_rawat
                     JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                     JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
                     WHERE pl.status='ralan' 
                       AND DATE(pl.tgl_permintaan) = '$today'
                       AND DATE(pl.tgl_hasil) = '$today'
                     ORDER BY pl.jam_hasil DESC LIMIT 1";
    $lastHasil = mysqli_fetch_assoc(bukaquery2($sql_hasil_mb));

    /* --- Cek status antrian --- */
    $statusSampel = mysqli_fetch_assoc(bukaquery2("SELECT status FROM antrilabmb LIMIT 1"));
    $statusHasil  = mysqli_fetch_assoc(bukaquery2("SELECT status FROM antrilabmb2 LIMIT 1"));

    $calledSampelMB = null;
    $calledHasilMB  = null;

    /* --- Logika sampel --- */
    if ($lastSampel) {
        $lastSampel['nm_pasien_bersih'] = cleanNamaPasien($lastSampel['nm_pasien']);
        $lastSampel['nm_pasien'] = $lastSampel['nm_pasien_bersih'];

        if ($statusSampel && $statusSampel['status'] == '1') {
            $calledSampelMB = $lastSampel;
            $calledSampelMB['mode'] = 'active';
            $calledSampelMB['timestamp'] = date('Y-m-d H:i:s');
            bukaquery2("UPDATE antrilabmb SET status='0'");
        } else {
            $calledSampelMB = $lastSampel;
            $calledSampelMB['mode'] = 'standby';
            $calledSampelMB['timestamp'] = date('Y-m-d H:i:s');
        }
    }

    /* --- Logika hasil --- */
    if ($lastHasil) {
        $lastHasil['nm_pasien_bersih'] = cleanNamaPasien($lastHasil['nm_pasien']);
        $lastHasil['nm_pasien'] = $lastHasil['nm_pasien_bersih'];

        if ($statusHasil && $statusHasil['status'] == '1') {
            $calledHasilMB = $lastHasil;
            $calledHasilMB['mode'] = 'active';
            $calledHasilMB['timestamp'] = date('Y-m-d H:i:s');
            bukaquery2("UPDATE antrilabmb2 SET status='0'");
        } else {
            $calledHasilMB = $lastHasil;
            $calledHasilMB['mode'] = 'standby';
            $calledHasilMB['timestamp'] = date('Y-m-d H:i:s');
        }
    }

    /* --- Ambil daftar permintaan lab MB hari ini --- */
    $sql_permintaan_mb = "SELECT r.no_reg, ps.nm_pasien, d.nm_dokter,
                                 IF(pl.jam_permintaan='00:00:00','',pl.jam_permintaan) AS jam_permintaan,
                                 IF(pl.jam_sampel='00:00:00','',pl.jam_sampel) AS jam_sampel,
                                 IF(pl.jam_hasil='00:00:00','',pl.jam_hasil) AS jam_hasil
                          FROM permintaan_labmb pl
                          JOIN reg_periksa r ON pl.no_rawat = r.no_rawat
                          JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
                          JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
                          WHERE pl.status='ralan' AND DATE(pl.tgl_permintaan) = '$today'
                          ORDER BY pl.jam_permintaan DESC";
    $res_permintaan_mb = bukaquery($sql_permintaan_mb);
    $permintaanHariIniMB = [];
    while($row = mysqli_fetch_assoc($res_permintaan_mb)) {
        $row['nm_pasien_bersih'] = cleanNamaPasien($row['nm_pasien']);
        $row['nm_pasien'] = $row['nm_pasien_bersih'];
        $permintaanHariIniMB[] = $row;
    }

    return [
        'antrianmb'           => $permintaanHariIniMB,
        'calledSampelMB'      => $calledSampelMB,
        'calledHasilMB'       => $calledHasilMB,
        'permintaanHariIniMB' => $permintaanHariIniMB
    ];
}

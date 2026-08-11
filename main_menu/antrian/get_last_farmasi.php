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

header('Content-Type: application/json');

/* --- Non racikan --- */
$sql_nonracik = "SELECT rd.no_resep, ro.no_rawat, ps.nm_pasien,
                        rd.kode_brng, rd.jml, rd.aturan_pakai,
                        ro.tgl_peresepan, ro.jam_peresepan, ro.jam_penyerahan, d.nm_dokter
                 FROM resep_dokter rd
                 JOIN resep_obat ro ON rd.no_resep = ro.no_resep
                 JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
                 JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
                 JOIN dokter d ON ro.kd_dokter = d.kd_dokter
                 WHERE ro.status='ralan' AND ro.tgl_peresepan='$today'
                 ORDER BY ro.jam_peresepan DESC";
$res_nonracik = bukaquery2($sql_nonracik);
$nonracikan = [];
while ($row = mysqli_fetch_assoc($res_nonracik)) {
    $row['jam_validasi'] = $row['jam_peresepan'];
    $row['nm_pasien_bersih'] = cleanNamaPasien($row['nm_pasien']);
    $row['nm_pasien'] = $row['nm_pasien_bersih'];

    $no_resep = $row['no_resep'];
    if (!isset($nonracikan[$no_resep])) {
        $nonracikan[$no_resep] = [
            'no_resep' => $no_resep,
            'no_rawat' => $row['no_rawat'],
            'nm_pasien' => $row['nm_pasien'],
            'nm_dokter' => $row['nm_dokter'],
            'tgl_peresepan' => $row['tgl_peresepan'],
            'jam_peresepan' => $row['jam_peresepan'],
            'jam_penyerahan' => $row['jam_penyerahan'],
            'items' => []
        ];
    }
    $nonracikan[$no_resep]['items'][] = [
        'kode_brng' => $row['kode_brng'],
        'jml' => $row['jml'],
        'aturan_pakai' => $row['aturan_pakai']
    ];
}
$nonracikan = array_values($nonracikan); // ubah ke array numerik

/* --- Racikan --- */
$sql_racik = "SELECT rr.no_resep, ro.no_rawat, ps.nm_pasien,
                     rr.no_racik, rr.nama_racik, rr.kd_racik,
                     rr.jml_dr, rr.aturan_pakai, rr.keterangan,
                     ro.tgl_peresepan, ro.jam_peresepan, ro.jam_penyerahan, d.nm_dokter
              FROM resep_dokter_racikan rr
              JOIN resep_obat ro ON rr.no_resep = ro.no_resep
              JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
              JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
              JOIN dokter d ON ro.kd_dokter = d.kd_dokter
              WHERE ro.status='ralan' AND ro.tgl_peresepan='$today'
              ORDER BY ro.jam_peresepan DESC";
$res_racik = bukaquery2($sql_racik);
$racikan = [];
while ($row = mysqli_fetch_assoc($res_racik)) {
    $row['jam_validasi'] = $row['jam_peresepan'];
    $row['nm_pasien_bersih'] = cleanNamaPasien($row['nm_pasien']);
    $row['nm_pasien'] = $row['nm_pasien_bersih'];

    $no_resep = $row['no_resep'];
    if (!isset($racikan[$no_resep])) {
        $racikan[$no_resep] = [
            'no_resep' => $no_resep,
            'no_rawat' => $row['no_rawat'],
            'nm_pasien' => $row['nm_pasien'],
            'nm_dokter' => $row['nm_dokter'],
            'tgl_peresepan' => $row['tgl_peresepan'],
            'jam_peresepan' => $row['jam_peresepan'],
            'jam_penyerahan' => $row['jam_penyerahan'],
            'items' => []
        ];
    }
    $racikan[$no_resep]['items'][] = [
        'no_racik' => $row['no_racik'],
        'nama_racik' => $row['nama_racik'],
        'kd_racik' => $row['kd_racik'],
        'jml_dr' => $row['jml_dr'],
        'aturan_pakai' => $row['aturan_pakai'],
        'keterangan' => $row['keterangan']
    ];
}
$racikan = array_values($racikan); // ubah ke array numerik

/* --- Gabungan Resep --- */
$gabunganResep = array_merge($nonracikan, $racikan);

/* --- Validasi --- */
$sql_validasi = "SELECT a.no_resep, a.no_rawat, a.status,
                        ro.jam_peresepan AS jam_validasi,
                        ps.nm_pasien
                 FROM antriapotek2 a
                 JOIN resep_obat ro ON a.no_resep = ro.no_resep
                 JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
                 JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
                 WHERE a.status = 1";
$statusValidasi = mysqli_fetch_assoc(bukaquery2($sql_validasi));

if ($statusValidasi && $statusValidasi['status'] == 1) {
    bukaquery2("UPDATE antriapotek2 SET status='2' WHERE no_resep='".$statusValidasi['no_resep']."'");
    bukaquery2("UPDATE antriapotek2 SET status='3' WHERE status='2' AND no_resep <> '".$statusValidasi['no_resep']."'");
    $statusValidasi = mysqli_fetch_assoc(bukaquery2($sql_validasi));
}
if ($statusValidasi) {
    $statusValidasi['nm_pasien_bersih'] = cleanNamaPasien($statusValidasi['nm_pasien']);
    $statusValidasi['nm_pasien'] = $statusValidasi['nm_pasien_bersih'];
}

/* --- Penyerahan --- */
$sql_penyerahan = "SELECT a.no_resep, a.no_rawat, a.status,
                          ro.jam_penyerahan,
                          ps.nm_pasien
                   FROM antriapotek3 a
                   JOIN resep_obat ro ON a.no_resep = ro.no_resep
                   JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
                   JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
                   WHERE a.status = 1";
$statusPenyerahan = mysqli_fetch_assoc(bukaquery2($sql_penyerahan));

if ($statusPenyerahan && $statusPenyerahan['status'] == 1) {
    bukaquery2("UPDATE antriapotek3 SET status='2' WHERE no_resep='".$statusPenyerahan['no_resep']."'");
    bukaquery2("UPDATE antriapotek3 SET status='3' WHERE status='2' AND no_resep <> '".$statusPenyerahan['no_resep']."'");
    $statusPenyerahan = mysqli_fetch_assoc(bukaquery2($sql_penyerahan));
}
if ($statusPenyerahan) {
    $statusPenyerahan['nm_pasien_bersih'] = cleanNamaPasien($statusPenyerahan['nm_pasien']);
    $statusPenyerahan['nm_pasien'] = $statusPenyerahan['nm_pasien_bersih'];
}

/* --- Output --- */
$output = [
    'gabunganResep' => $gabunganResep,
    'nonracikan'    => $nonracikan,
    'racikan'       => $racikan,
    'calledValidasiFarmasi' => $statusValidasi ? [
        'no_resep'     => $statusValidasi['no_resep'],
        'no_rawat'     => $statusValidasi['no_rawat'],
        'status'       => $statusValidasi['status'],
        'jam_validasi' => $statusValidasi['jam_validasi'],
        'nm_pasien'    => $statusValidasi['nm_pasien'],
        'mode'         => ($statusValidasi['status'] == 1 ? 'active' : 'standby')
    ] : null,
    'calledPenyerahanFarmasi' => $statusPenyerahan ? [
        'no_resep'       => $statusPenyerahan['no_resep'],
        'no_rawat'       => $statusPenyerahan['no_rawat'],
        'status'         => $statusPenyerahan['status'],
        'jam_penyerahan' => $statusPenyerahan['jam_penyerahan'],
        'nm_pasien'      => $statusPenyerahan['nm_pasien'],
        'mode'           => ($statusPenyerahan['status'] == 1 ? 'active' : 'standby')
    ] : null
];

echo json_encode($output, JSON_UNESCAPED_UNICODE);
exit;
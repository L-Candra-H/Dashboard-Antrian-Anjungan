<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

include_once '../conf/conf.php';
header('Content-Type: application/json; charset=utf-8');

// Mapping hari Inggris → Indonesia
$mapHari = [
    'Sunday'    => 'AKHAD',
    'Monday'    => 'SENIN',
    'Tuesday'   => 'SELASA',
    'Wednesday' => 'RABU',
    'Thursday'  => 'KAMIS',
    'Friday'    => 'JUMAT',
    'Saturday'  => 'SABTU'
];

$hari_en = date('l');
$hari    = $mapHari[$hari_en] ?? $hari_en;

// Parameter poli (frontend kirim kd_poli)
$kd_poli = $_GET['kd_poli'] ?? '';
$wherePoli = $kd_poli ? "AND j.kd_poli='$kd_poli'" : "";

// Query jadwal dokter untuk hari ini, difilter poli
$sql = "SELECT d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai, j.jam_selesai,
               j.kd_dokter, j.kd_poli, j.kuota, pg.photo
        FROM jadwal j
        JOIN dokter d ON j.kd_dokter = d.kd_dokter
        JOIN poliklinik p ON j.kd_poli = p.kd_poli
        JOIN pegawai pg ON d.kd_dokter = pg.nik
        WHERE j.hari_kerja = '$hari' $wherePoli
        ORDER BY p.nm_poli, d.nm_dokter, j.jam_mulai";

$result = bukaquery($sql);

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $photo = $row['photo'];
    $baseFolder = basename(dirname(dirname(__DIR__)));
    $foto = !empty($photo) ? "/{$baseFolder}/webapps/penggajian/{$photo}" : '';

    // Hitung jumlah pasien terdaftar hari ini untuk dokter & poli ini
    $tglHariIni = date('Y-m-d');
    $sqlCount = "SELECT COUNT(*) AS terdaftar
                 FROM reg_periksa
                 WHERE kd_dokter='{$row['kd_dokter']}'
                   AND kd_poli='{$row['kd_poli']}'
                   AND tgl_registrasi='$tglHariIni'";
    $resCount = bukaquery($sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $terdaftar = (int)($rowCount['terdaftar'] ?? 0);

    $sisaKuota = max(0, (int)$row['kuota'] - $terdaftar);

    $data[] = [
        'nm_dokter'   => $row['nm_dokter'],
        'nm_poli'     => $row['nm_poli'],
        'mulai'       => $row['jam_mulai'],
        'selesai'     => $row['jam_selesai'],
        'kd_dokter'   => $row['kd_dokter'],
        'kd_poli'     => $row['kd_poli'],
        'kuota'       => (int)$row['kuota'],
        'terdaftar'   => $terdaftar,
        'sisa_kuota'  => $sisaKuota,
        'photo'       => $foto
    ];
}

// Pastikan UTF-8
array_walk_recursive($data, function (&$item) {
    if (is_string($item)) {
        $item = mb_convert_encoding($item, 'UTF-8', 'auto');
    }
});

// Output JSON
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>

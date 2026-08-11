<?php
// Aktifkan error reporting untuk debug
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

date_default_timezone_set('Asia/Jakarta'); // WIB
include_once '../conf/conf.php';
header('Content-Type: application/json; charset=utf-8');

// Parameter dari frontend
$kd_dokter    = $_GET['kd_dokter'] ?? '';
$kd_poli      = $_GET['kd_poli'] ?? '';
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
$kd_pj        = $_GET['kd_pj'] ?? ''; // Jenis Bayar

if (empty($kd_dokter) || empty($kd_poli) || empty($no_rkm_medis) || empty($kd_pj)) {
    echo json_encode(["error" => "Parameter tidak lengkap"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ambil tanggal & jam sekarang
$tgl_registrasi = date('Y-m-d');
$jam_reg        = date('H:i:s');

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

// Validasi kuota penuh
$sqlCount = "SELECT COUNT(*) AS terdaftar
             FROM reg_periksa
             WHERE kd_dokter='$kd_dokter'
               AND kd_poli='$kd_poli'
               AND tgl_registrasi='$tgl_registrasi'";
$resCount = bukaquery($sqlCount);
$rowCount = mysqli_fetch_assoc($resCount);
$terdaftar = (int)($rowCount['terdaftar'] ?? 0);

$sqlKuota = "SELECT kuota FROM jadwal
             WHERE kd_dokter='$kd_dokter'
               AND kd_poli='$kd_poli'
               AND hari_kerja='$hari' LIMIT 1";
$resKuota = bukaquery($sqlKuota);
$rowKuota = mysqli_fetch_assoc($resKuota);
$kuota = (int)($rowKuota['kuota'] ?? 0);

if ($kuota > 0 && $terdaftar >= $kuota) {
    echo json_encode(["error" => "Kuota penuh, tidak bisa registrasi"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi pasien agar tidak registrasi berulang
$sqlCheck = "SELECT COUNT(*) AS sudah
             FROM reg_periksa
             WHERE kd_dokter='$kd_dokter'
               AND kd_poli='$kd_poli'
               AND tgl_registrasi='$tgl_registrasi'
               AND no_rkm_medis='$no_rkm_medis'";
$resCheck = bukaquery($sqlCheck);
$rowCheck = mysqli_fetch_assoc($resCheck);
$sudah = (int)($rowCheck['sudah'] ?? 0);

if ($sudah > 0) {
    echo json_encode(["error" => "Pasien sudah terdaftar di poli ini hari ini"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Generate no_reg
$sql_reg = "SELECT MAX(no_reg) as max_reg 
            FROM reg_periksa 
            WHERE tgl_registrasi='$tgl_registrasi' AND kd_poli='$kd_poli'";
$res_reg = bukaquery($sql_reg);
$row_reg = mysqli_fetch_assoc($res_reg);
$no_reg  = str_pad((int)$row_reg['max_reg'] + 1, 3, '0', STR_PAD_LEFT);

// Generate no_rawat (6 digit sequence)
$sql_rawat = "SELECT MAX(no_rawat) as max_rawat 
              FROM reg_periksa 
              WHERE tgl_registrasi='$tgl_registrasi'";
$res_rawat = bukaquery($sql_rawat);
$row_rawat = mysqli_fetch_assoc($res_rawat);
$last_rawat = $row_rawat['max_rawat'] ?? '';
$seq = $last_rawat ? (int)substr($last_rawat, -6) + 1 : 1;
$no_rawat = date('Y/m/d/') . str_pad($seq, 6, '0', STR_PAD_LEFT);

// Ambil data pasien
$sqlPasien = "SELECT namakeluarga, alamatpj, keluarga, umur, tgl_lahir 
              FROM pasien WHERE no_rkm_medis='$no_rkm_medis' LIMIT 1";
$resPasien = bukaquery($sqlPasien);
$rowPasien = mysqli_fetch_assoc($resPasien);

$p_jawab    = $rowPasien['namakeluarga'] ?? '';
$almt_pj    = $rowPasien['alamatpj'] ?? '';
$hubunganpj = $rowPasien['keluarga'] ?? '';
$umurdaftar = (int)($rowPasien['umur'] ?? 0);

// Hitung sttsumur
$sttsumur = 'Th';
if (!empty($rowPasien['tgl_lahir'])) {
    $birth = new DateTime($rowPasien['tgl_lahir']);
    $today = new DateTime($tgl_registrasi);
    $diff  = $birth->diff($today);
    $days  = $diff->days;

    if ($days >= 365) {
        $sttsumur = 'Th';
        $umurdaftar = $diff->y;
    } elseif ($days >= 30) {
        $sttsumur = 'Bl';
        $umurdaftar = $diff->y * 12 + $diff->m;
    } else {
        $sttsumur = 'Hr';
        $umurdaftar = $days;
    }
}

// Ambil biaya registrasi dari poliklinik
$sqlPoli = "SELECT registrasi, registrasilama FROM poliklinik WHERE kd_poli='$kd_poli' LIMIT 1";
$resPoli = bukaquery($sqlPoli);
$rowPoli = mysqli_fetch_assoc($resPoli);

$stts_daftar = "Lama"; // default
$biaya_reg   = ($stts_daftar == "Lama") ? ($rowPoli['registrasilama'] ?? 0) : ($rowPoli['registrasi'] ?? 0);

// Status default
$status_bayar = "Belum Bayar";
$status_poli  = "Lama";

// Insert ke reg_periksa
$sql_insert = "INSERT INTO reg_periksa 
    (no_reg, no_rawat, tgl_registrasi, jam_reg, kd_dokter, no_rkm_medis, kd_poli,
     p_jawab, almt_pj, hubunganpj, biaya_reg, stts, stts_daftar, status_lanjut, kd_pj,
     umurdaftar, sttsumur, status_bayar, status_poli)
    VALUES 
    ('$no_reg', '$no_rawat', '$tgl_registrasi', '$jam_reg', '$kd_dokter', '$no_rkm_medis', '$kd_poli',
     '$p_jawab', '$almt_pj', '$hubunganpj', '$biaya_reg', 'Belum', '$stts_daftar', 'Ralan', '$kd_pj',
     '$umurdaftar', '$sttsumur', '$status_bayar', '$status_poli')";

if (bukaquery($sql_insert)) {
    // Ambil setting instansi
    $sql_setting = "SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1";
    $res_setting = bukaquery($sql_setting);
    $row_setting = mysqli_fetch_assoc($res_setting);

    // Ambil data untuk bukti
    $sql_info = "SELECT rp.no_reg, rp.no_rawat, rp.jam_reg,
                        p.nm_pasien, d.nm_dokter, pl.nm_poli,
                        pj.png_jawab AS nm_pj
                 FROM reg_periksa rp
                 JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                 JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                 JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
                 JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                 WHERE rp.no_rawat='$no_rawat' LIMIT 1";
    $res_info = bukaquery($sql_info);
    $row_info = mysqli_fetch_assoc($res_info);

    // Gabungkan dengan setting instansi
    $row_info['nama_instansi']   = $row_setting['nama_instansi'] ?? '';
    $row_info['alamat_instansi'] = $row_setting['alamat_instansi'] ?? '';
    $row_info['kabupaten']       = $row_setting['kabupaten'] ?? '';
    $row_info['nm_pj'] = $row_info['nm_pj'] ?? '';

    echo json_encode($row_info, JSON_UNESCAPED_UNICODE);
    exit;
} else {
    global $conn;
    echo json_encode([
        "error"  => "Insert gagal",
        "detail" => mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

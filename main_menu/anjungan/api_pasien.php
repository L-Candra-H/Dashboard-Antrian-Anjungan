<?php
include_once '../conf/conf.php';
include_once '../conf/helpers.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

// jangan definisikan convertToDbDate() lagi di sini
// langsung pakai fungsi dari helpers.php

$identitas = $_GET['identitas'] ?? '';
$tgl_lahir_input = $_GET['tgl_lahir'] ?? '';

if (!$identitas) { echo json_encode(["error"=>"Identitas kosong"]); exit; }
if (!$tgl_lahir_input) { echo json_encode(["error"=>"Tanggal lahir kosong"]); exit; }

$tgl_lahir = convertToDbDate($tgl_lahir_input);
if (!$tgl_lahir) { echo json_encode(["error"=>"Format tanggal lahir salah"]); exit; }

$conn = bukakoneksi();

$sql = "SELECT 
            p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.jk, p.tmp_lahir, p.tgl_lahir,
            p.umur, p.nm_ibu, p.alamat,
            kel.nm_kel AS nama_kelurahan, kec.nm_kec AS nama_kecamatan,
            kab.nm_kab AS nama_kabupaten, prop.nm_prop AS nama_propinsi,
            p.no_tlp, p.email, p.gol_darah, p.pekerjaan, p.stts_nikah, p.agama,
            p.tgl_daftar, p.pnd, p.keluarga, p.namakeluarga, p.kd_pj,
            pj.png_jawab AS nama_penjamin, p.no_peserta, p.perusahaan_pasien,
            pr.nama_perusahaan AS nama_perusahaan_pasien, pr.alamat AS alamat_perusahaan,
            pr.kota AS kota_perusahaan, pr.no_telp AS telp_perusahaan,
            p.suku_bangsa, sb.nama_suku_bangsa, p.bahasa_pasien, bp.nama_bahasa,
            p.cacat_fisik, cf.nama_cacat, p.nip
        FROM pasien p
        LEFT JOIN kelurahan kel ON p.kd_kel = kel.kd_kel
        LEFT JOIN kecamatan kec ON p.kd_kec = kec.kd_kec
        LEFT JOIN kabupaten kab ON p.kd_kab = kab.kd_kab
        LEFT JOIN propinsi prop ON p.kd_prop = prop.kd_prop
        LEFT JOIN perusahaan_pasien pr ON p.perusahaan_pasien = pr.kode_perusahaan
        LEFT JOIN penjab pj ON p.kd_pj = pj.kd_pj
        LEFT JOIN suku_bangsa sb ON p.suku_bangsa = sb.id
        LEFT JOIN bahasa_pasien bp ON p.bahasa_pasien = bp.id
        LEFT JOIN cacat_fisik cf ON p.cacat_fisik = cf.id
        WHERE (p.no_ktp = ? OR p.no_rkm_medis = ?) AND p.tgl_lahir = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(["error"=>"Prepare failed: ".$conn->error]); exit; }

$stmt->bind_param("sss", $identitas, $identitas, $tgl_lahir);
if (!$stmt->execute()) { echo json_encode(["error"=>"Execute failed: ".$stmt->error]); exit; }

$stmt->bind_result(
    $no_rkm_medis, $nm_pasien, $no_ktp, $jk, $tmp_lahir, $tgl_lahir_db,
    $umur, $nm_ibu, $alamat,
    $nama_kelurahan, $nama_kecamatan, $nama_kabupaten, $nama_propinsi,
    $no_tlp, $email, $gol_darah, $pekerjaan, $stts_nikah, $agama,
    $tgl_daftar, $pnd, $keluarga, $namakeluarga, $kd_pj,
    $nama_penjamin, $no_peserta, $perusahaan_pasien,
    $nama_perusahaan_pasien, $alamat_perusahaan, $kota_perusahaan, $telp_perusahaan,
    $suku_bangsa, $nama_suku_bangsa, $bahasa_pasien, $nama_bahasa,
    $cacat_fisik, $nama_cacat, $nip
);

if ($stmt->fetch()) {
    $row = [
        "no_rkm_medis" => $no_rkm_medis,
        "nm_pasien" => $nm_pasien,
        "no_ktp" => $no_ktp,
        "jk" => $jk,
        "tmp_lahir" => $tmp_lahir,
        "tgl_lahir" => $tgl_lahir_db,
        "umur" => $umur,
        "nm_ibu" => $nm_ibu,
        "alamat" => $alamat,
        "nama_kelurahan" => $nama_kelurahan,
        "nama_kecamatan" => $nama_kecamatan,
        "nama_kabupaten" => $nama_kabupaten,
        "nama_propinsi" => $nama_propinsi,
        "no_tlp" => $no_tlp,
        "email" => $email,
        "gol_darah" => $gol_darah,
        "pekerjaan" => $pekerjaan,
        "stts_nikah" => $stts_nikah,
        "agama" => $agama,
        "tgl_daftar" => $tgl_daftar,
        "pnd" => $pnd,
        "keluarga" => $keluarga,
        "namakeluarga" => $namakeluarga,
        "kd_pj" => $kd_pj,
        "nama_penjamin" => $nama_penjamin,
        "no_peserta" => $no_peserta,
        "perusahaan_pasien" => $perusahaan_pasien,
        "nama_perusahaan_pasien" => $nama_perusahaan_pasien,
        "alamat_perusahaan" => $alamat_perusahaan,
        "kota_perusahaan" => $kota_perusahaan,
        "telp_perusahaan" => $telp_perusahaan,
        "suku_bangsa" => $suku_bangsa,
        "nama_suku_bangsa" => $nama_suku_bangsa,
        "bahasa_pasien" => $bahasa_pasien,
        "nama_bahasa" => $nama_bahasa,
        "cacat_fisik" => $cacat_fisik,
        "nama_cacat" => $nama_cacat,
        "nip" => $nip
    ];
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error"=>"Pasien tidak ditemukan atau tanggal lahir salah"]);
}

$stmt->close();
$conn->close();
exit;

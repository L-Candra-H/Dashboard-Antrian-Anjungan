<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

header('Content-Type: application/json');

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Ambil nomor terakhir hari ini
$sql = "SELECT MAX(nomor) AS last_nomor 
        FROM antriloketcetak 
        WHERE tanggal = CURDATE()";
$result = bukaquery($sql);
$data = mysqli_fetch_assoc($result);

// Tentukan nomor berikutnya
$nextNomor = ($data && $data['last_nomor']) ? intval($data['last_nomor']) + 1 : 1;

// Format 3 digit
$nomor3digit = str_pad($nextNomor, 3, '0', STR_PAD_LEFT);

// Kembalikan JSON (tanpa insert)
echo json_encode([
    'status'  => 'ok',
    'nomor'   => $nomor3digit,
    'tanggal' => date('Y-m-d'),
    'jam'     => date('H:i:s')
]);

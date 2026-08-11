<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

include_once '../conf/conf.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

$nomor   = $_GET['nomor'] ?? '000';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$jam     = $_GET['jam'] ?? date('H:i:s');

$conn    = bukakoneksi();
$setting = $conn->query("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1")->fetch_assoc();

// Pastikan nomor 3 digit
$nomor3digit = str_pad($nomor, 3, '0', STR_PAD_LEFT);

// Insert ke DB saat CETAK
$sql = "INSERT INTO antriloketcetak (tanggal, jam, nomor) 
        VALUES ('$tanggal', '$jam', '$nomor3digit')";
$conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Antrean Loket - <?= htmlspecialchars($nomor3digit) ?></title>
  <link rel="stylesheet" href="anjungan.css">
</head>
<body class="cetak-body" onload="cetakDanKembali()">
  <div class="cetak-header">
    <h1><?= htmlspecialchars($setting['nama_instansi']) ?></h1>
    <p><?= htmlspecialchars($setting['alamat_instansi']) ?><br><?= htmlspecialchars($setting['kabupaten']) ?></p>
  </div>

  <div class="cetak-divider"></div>
  <div class="cetak-title">ANTREAN LOKET ADMISI</div>

  <div class="cetak-content">
    <div class="nomor-antrian"><?= htmlspecialchars($nomor3digit) ?></div>
    <table>
      <tr>
        <td class="cetak-label">Tanggal</td>
        <td class="cetak-colon">:</td>
        <td class="cetak-val"><?= date('d-m-Y', strtotime($tanggal)) ?></td>
      </tr>
      <tr>
        <td class="cetak-label">Jam</td>
        <td class="cetak-colon">:</td>
        <td class="cetak-val"><?= htmlspecialchars($jam) ?> WIB</td>
      </tr>
    </table>
  </div>

  <div class="cetak-footer">
    <div class="cetak-divider"></div>
    <p>Silakan duduk dan tunggu panggilan.<br>Terima kasih.</p>
  </div>

  <script>
    function cetakDanKembali() {
      window.print();
      setTimeout(function() {
        window.location.href = 'anjungan.php';
      }, 1500);
    }
  </script>
</body>
</html>

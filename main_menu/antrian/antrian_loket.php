<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// Ambil nomor terakhir yang selesai (pakai jam saja)
$sql = "SELECT nomor, jam
        FROM antriloketcetak
        WHERE tanggal=CURDATE() AND status='selesai'
        ORDER BY jam DESC LIMIT 1";
$result = bukaquery($sql);
$row = mysqli_fetch_assoc($result);

$nomorTerakhir = $row ? str_pad($row['nomor'], 3, '0', STR_PAD_LEFT) : null;
$jamTerakhir   = $row['jam'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Antrian Loket</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="antrian.css"> <!-- CSS khusus antrian -->
</head>
<body>
  <header class="header">
    <div class="logo"><?php include '../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
      <p><?= $setting['kontak'] ?> | <?= $setting['email'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <div class="center-grid">
      <div class="loket-card">
        <div class="loket-title">NOMOR ANTRIAN</div>
        <div class="loket-number" id="nomorBox">
          <?= $nomorTerakhir ? $nomorTerakhir : "Belum ada antrian" ?>
        </div>
        <div class="loket-info" id="jamBox">
          <?= $jamTerakhir ? $jamTerakhir : "-" ?>
        </div>
        <div class="loket-footer" id="footerBox">Ke LOKET PENDAFTARAN</div>
      </div>
    </div>
    <?php include '../assets/banner.php'; ?>
  </main>

  <script src="../assets/clock.js"></script>
  <script>
    // reload otomatis untuk standby
    setInterval(() => {
      location.reload();
    }, 5000);
  </script>
</body>
</html>

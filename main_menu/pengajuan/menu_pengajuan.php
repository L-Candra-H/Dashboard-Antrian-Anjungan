<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: login.php");
    exit;
}

$nama_user = $_SESSION['nama_pengaju']; // gunakan nama_pengaju sesuai login.php

include_once '../conf/conf.php';
include_once '../conf/conf_pengajuan.php';
$conn_pengajuan = bukakoneksi_pengajuan();

// ambil data instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Menu Pengajuan</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="pengajuan.css">
</head>
<body class="pengajuan">
  <!-- Header -->
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
    <h2 class="anjungan-title">PILIH MENU</h2>
    <div class="button-group">
      <a href="daftar_pengajuan.php" class="btn-exit">Hapus Nota Salah</a>
      <a href="daftar_penggunaan_ruang.php" class="btn-exit">Penggunaan Ruang</a>
      <a href="logout.php" class="btn-exit">Logout</a>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>
  <script src="../assets/clock.js"></script>
</body>
</html>

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
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="pengajuan.css"> <!-- CSS khusus pengajuan -->
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

    <!-- Menu Utama -->
    <div class="button-group">
      <a href="daftar_pengajuan.php" class="btn-main">Hapus Nota Salah</a>
      <a href="daftar_penggunaan_ruang.php" class="btn-main">Penggunaan Ruang Pertemuan</a>
    </div>

    <!-- Statistik RS -->
    <div class="button-group">
      <a href="statistik/bor.php" class="btn-stat">BOR</a>
      <a href="statistik/alos.php" class="btn-stat">ALOS</a>
      <a href="statistik/toi.php" class="btn-stat">TOI</a>
      <a href="statistik/bto.php" class="btn-stat">BTO</a>
      <a href="statistik/gdr.php" class="btn-stat">GDR</a>
      <a href="statistik/ndr.php" class="btn-stat">NDR</a>
      <a href="statistik/statistik_gabungan.php" class="btn-stat">REKAP STATISTIK</a>
    </div>

    <!-- Indikator Tambahan RS -->
    <div class="button-group indikator-tambahan">
      <a href="statistik/adc.php" class="btn-extra">ADC</a>
      <a href="statistik/bc.php" class="btn-extra">BC</a>
      <a href="statistik/bod.php" class="btn-extra">BOD</a>
      <a href="statistik/cfr.php" class="btn-extra">CFR</a>
      <a href="statistik/discharge.php" class="btn-extra">DISCHARGE RATE</a>
      <a href="statistik/readmission.php" class="btn-extra">READMISSION RATE</a>
      <a href="statistik/indikator_tambahan.php" class="btn-extra">INDIKATOR TAMBAHAN</a>
    </div>

    <!-- Logout -->
    <div class="button-group">
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>
  <script src="../assets/clock.js"></script>
</body>
</html>

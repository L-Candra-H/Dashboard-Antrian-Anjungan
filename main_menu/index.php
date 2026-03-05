<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/conf/conf.php';
include_once __DIR__ . '/conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Menu Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
  <header class="header">
    <div class="logo"><?php include __DIR__ . '/assets/logo_menu.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
      <p><?= $setting['kontak'] ?> | <?= $setting['email'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <h2>MENU UTAMA</h2>
    <div class="menu-grid">
      <!-- Kolom Dashboard -->
      <div class="menu-column">
        <h3>DASHBOARD</h3>
        <div class="menu-cards">
          <a href="kamar/kamar.php" target="_blank">
            <img src="assets/img/kamar.png" alt="Kamar">
            <span>Kamar Inap</span>
          </a>
          <a href="operasi/operasi.php" target="_blank">
            <img src="assets/img/operasi.png" alt="Operasi">
            <span>Operasi</span>
          </a>
          <a href="jadwal/jadwal.php" target="_blank">
            <img src="assets/img/jadwal.png" alt="Jadwal">
            <span>Dokter</span>
          </a>
          <a href="jadwal/harian.php" target="_blank">
            <img src="assets/img/harian.png" alt="Harian">
            <span>Harian Dokter</span>
          </a>
          <a href="gabungan/dokter_kamar.php" target="_blank">
            <img src="assets/img/dokter_kamar.png" alt="Gabungan">
            <span>Dokter + Kamar</span>
          </a>
          <a href="gabungan/dokter_kamar_harian.php" target="_blank">
            <img src="assets/img/dokter_kamar_harian.png" alt="Gabungan">
            <span>Harian Dokter + Kamar</span>
          </a>
        </div>
      </div>

      <!-- Kolom Antrian -->
      <div class="menu-column">
        <h3>ANTRIAN DAN ANJUNGAN</h3>
        <div class="menu-cards">
          <a href="antrian/antrian_poli.php" target="_blank">
            <img src="assets/img/antrian_poli.png" alt="Poli">
            <span>Poli</span>
          </a>
          <a href="antrian/antrian_farmasi.php" target="_blank">
            <img src="assets/img/antrian_farmasi.png" alt="Farmasi">
            <span>Farmasi</span>
          </a>
          <a href="antrian/antrian_lab_gabung.php" target="_blank">
            <img src="assets/img/lab_gabung.png" alt="Lab Gabung">
            <span>Laboratorium</span>
          </a>
          <a href="antrian/antrian_rad.php" target="_blank">
            <img src="assets/img/radiologi.png" alt="Radiologi">
            <span>Radiologi</span>
          </a>
          <a href="anjungan/anjungan.php" target="_blank">
            <img src="assets/img/anjungan.png" alt="Anjungan">
            <span>Anjungan Mandiri</span>
          </a>
          <a href="antrian/antrian_loket.php" target="_blank">
            <img src="assets/img/loket.png" alt="Antrian Loket">
            <span>Loket Admisi</span>
          </a>
        </div>

        <!-- Tombol bawah utama -->
        <div class="menu-actions">
          <!-- Tombol kiri: PANGGIL ANTRI LOKET -->
          <div class="action-left">
            <a href="antrian/antrian_loket_panggil.php" target="_blank">
              PANGGIL ANTRI LOKET
            </a>
          </div>

          <!-- Tombol tengah: PANGGIL ULANG FARMASI -->
          <div class="action-middle">
            <a href="antrian/antrian_farmasi_bypass.php" target="_blank">
              PANGGIL ULANG FARMASI
            </a>
          </div>

          <!-- Tombol kanan: PANGGIL ULANG POLI -->
          <div class="action-right">
            <a href="antrian/antrian_poli_bypass.php" target="_blank">
              PANGGIL ULANG POLI
            </a>
          </div>
        </div>

        <!-- Tambahan baris khusus API GET LAST -->
        <div class="menu-actions-full">
          <a href="antrian/api_get_last_all.php" target="_blank">
            API GET LAST
          </a>
        </div>

      </div>

      <!-- Kolom SIMRS -->
      <div class="menu-column">
        <h3>SIMRS KHANZA</h3>
        <div class="menu-cards">
          <a href="../edokter?nocache=<?= time() ?>" target="_blank" rel="noopener noreferrer">
            <img src="assets/img/edokter.png" alt="E Dokter">
            <span>E DOKTER</span>
          </a>
          <a href="../emcu?nocache=<?= time() ?>" target="_blank" rel="noopener noreferrer">
            <img src="assets/img/emcu.png" alt="E MCU">
            <span>E MCU</span>
          </a>
          <a href="../epasien?nocache=<?= time() ?>" target="_blank" rel="noopener noreferrer">
            <img src="assets/img/epasien.png" alt="E Pasien">
            <span>E PASIEN</span>
          </a>
          <?php
          $baseFolder = basename(dirname(__DIR__)); // ambil nama folder induk
          ?>
          <a href="https://<?= $_SERVER['HTTP_HOST'] ?>/<?= $baseFolder ?>/webapps/?nocache=<?= time() ?>" 
             target="_blank" rel="noopener noreferrer">
             <img src="assets/img/webapps.png" alt="Webapps">
             <span>KONFIRMASI & PERSETUJUAN</span>
          </a>
          <?php
          $baseFolder = basename(dirname(__DIR__)); // ambil nama folder induk
          ?>
          <a href="https://<?= $_SERVER['HTTP_HOST'] ?>/<?= $baseFolder ?>/webapps/presensi/?nocache=<?= time() ?>" 
             target="_blank" rel="noopener noreferrer">
             <img src="assets/img/presensi.png" alt="Presensi">
             <span>PRESENSI</span>
          </a>
          <a href="../kyc-library-php?nocache=<?= time() ?>" target="_blank" rel="noopener noreferrer">
            <img src="assets/img/kyc.png" alt="KYC">
            <span>KYC</span>
          </a>
        </div>
      </div>
    </div>

    <?php include __DIR__ . '/assets/banner.php'; ?>
  </main>

  <script src="assets/clock.js"></script>

  <script>
    // Tambahkan parameter nocache ke setiap link menu utama
    document.querySelectorAll('.menu-cards a').forEach(link => {
      link.addEventListener('click', e => {
        const nocache = "nocache=" + new Date().getTime();
        const url = new URL(link.href, window.location.origin);
        url.searchParams.set("nocache", nocache);
        window.open(url.toString(), '_blank', 'noopener,noreferrer');
        e.preventDefault();
      });
    });

    // Tambahkan parameter nocache ke setiap tombol bawah (menu-actions)
    document.querySelectorAll('.menu-actions a').forEach(link => {
      link.addEventListener('click', e => {
        const nocache = "nocache=" + new Date().getTime();
        const url = new URL(link.href, window.location.origin);
        url.searchParams.set("nocache", nocache);
        window.open(url.toString(), '_blank', 'noopener,noreferrer');
        e.preventDefault();
      });
    });
  </script>

</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// Ambil jadwal dokter penuh 1 minggu
$sql_jadwal = "SELECT d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai, j.jam_selesai
               FROM jadwal j
               JOIN dokter d ON j.kd_dokter = d.kd_dokter
               JOIN poliklinik p ON j.kd_poli = p.kd_poli
               ORDER BY d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai";
$res_jadwal = bukaquery($sql_jadwal);
$count_jadwal = mysqli_num_rows($res_jadwal);

// Ambil data ketersediaan kamar
$sql_kamar = "SELECT CONCAT(b.nm_bangsal, ' (', k.kelas, ')') AS nm_bangsal_kelas,
                     COUNT(k.kd_kamar) AS jumlah,
                     SUM(CASE WHEN k.status='ISI' THEN 1 ELSE 0 END) AS terisi,
                     SUM(CASE WHEN k.status='KOSONG' THEN 1 ELSE 0 END) AS kosong
              FROM kamar k
              JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
              WHERE k.statusdata = '1'
              GROUP BY nm_bangsal_kelas, k.kelas, b.nm_bangsal
              ORDER BY k.kelas ASC, b.nm_bangsal ASC";
$res_kamar = bukaquery($sql_kamar);
$count_kamar = mysqli_num_rows($res_kamar);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Dokter + Kamar</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="dokter_kamar.css">
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

  <main class="dashboard dual-grid">
    <!-- Panel Jadwal Dokter -->
    <div class="panel">
      <h2>JADWAL PRAKTEK DOKTER (1 MINGGU)</h2>
      <div class="tbody-container <?= ($count_jadwal > 7 ? 'scrollable' : '') ?>">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama Dokter</th>
              <th>Poli</th>
              <th>Hari</th>
              <th>Mulai</th>
              <th>Selesai</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($res_jadwal)): ?>
              <tr>
                <td><?= $row['nm_dokter'] ?></td>
                <td><?= $row['nm_poli'] ?></td>
                <td><?= $row['hari_kerja'] ?></td>
                <td><?= $row['jam_mulai'] ?></td>
                <td><?= $row['jam_selesai'] ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Panel Ketersediaan Kamar -->
    <div class="panel">
      <h2>KAMAR INAP</h2>
      <div class="tbody-container <?= ($count_kamar > 7 ? 'scrollable' : '') ?>">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama Bangsal (Kelas)</th>
              <th>Jumlah</th>
              <th>Bed Terisi</th>
              <th>Bed Kosong</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($res_kamar)): ?>
              <tr>
                <td><?= $row['nm_bangsal_kelas'] ?></td>
                <td><?= $row['jumlah'] ?></td>
                <td><?= $row['terisi'] ?></td>
                <td class="kosong"><?= $row['kosong'] ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>

  <!-- Refresh otomatis setiap 60 detik -->
  <script>
  setTimeout(function(){
     location.reload();
  }, 60000);
  </script>

  <!-- Auto scroll vertikal hanya jika scrollable -->
  <script>
    document.querySelectorAll('.tbody-container.scrollable').forEach(container => {
      let direction = 1; // 1 = turun, -1 = naik
      function autoScroll() {
        container.scrollTop += direction;
        if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
          direction = -1; // ganti arah ke atas
        } else if (container.scrollTop <= 0) {
          direction = 1; // ganti arah ke bawah
        }
      }
      setInterval(autoScroll, 50); // kecepatan scroll
    });
  </script>
</body>
</html>

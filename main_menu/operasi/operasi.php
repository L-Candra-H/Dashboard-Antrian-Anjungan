<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// Ambil data jadwal operasi
$sql = "SELECT bo.no_rawat, bo.kode_paket, bo.tanggal, bo.jam_mulai, bo.jam_selesai, bo.status,
               po.nm_perawatan,
               d.nm_dokter,
               ro.nm_ruang_ok,
               p.nm_pasien, p.jk, p.tgl_lahir
        FROM booking_operasi bo
        JOIN reg_periksa rp ON bo.no_rawat = rp.no_rawat
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        JOIN paket_operasi po ON bo.kode_paket = po.kode_paket
        JOIN dokter d ON bo.kd_dokter = d.kd_dokter
        JOIN ruang_ok ro ON bo.kd_ruang_ok = ro.kd_ruang_ok
        WHERE bo.status IN ('Menunggu','Proses Operasi')
        ORDER BY bo.tanggal, bo.jam_mulai";
$result = bukaquery($sql);
$count = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Jadwal Operasi</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="operasi.css">
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
    <h2>DASHBOARD JADWAL OPERASI</h2>

    <div class="grid-container <?= ($count > 6 ? 'scrollable' : '') ?>">
      <?php while($row = mysqli_fetch_assoc($result)): ?>
        <div class="operasi-card">
          <h3><?= sensorNama($row['nm_pasien']) ?> (<?= $row['jk'] ?>)</h3>
          <div class="kelas">No. Rawat: <?= $row['no_rawat'] ?></div>
          <div class="detail">
            <div class="label">Umur</div><div><?= hitungUmur($row['tgl_lahir']) ?> </div>
            <div class="label">Tanggal</div>
            <div><?= date("d/m/Y", strtotime($row['tanggal'])) ?></div>
            <div class="label">Jam</div><div><?= $row['jam_mulai'] ?> - <?= $row['jam_selesai'] ?></div>
            <div class="label">Operasi</div><div><?= $row['nm_perawatan'] ?></div>
            <div class="label">Operator</div><div><?= $row['nm_dokter'] ?></div>
            <div class="label">Ruang</div><div><?= $row['nm_ruang_ok'] ?></div>
            <div class="label">Status</div>
            <div>
              <span class="status <?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                <?= $row['status'] ?>
              </span>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <?php include '../assets/banner.php'; ?>
  </main>

  <script src="../assets/clock.js"></script>

  <script>
    setTimeout(function(){ location.reload(); }, 60000);
  </script>

  <script>
    document.querySelectorAll('.grid-container.scrollable').forEach(container => {
      let direction = 1;
      function autoScroll() {
        container.scrollTop += direction;
        if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
          direction = -1;
        } else if (container.scrollTop <= 0) {
          direction = 1;
        }
      }
      setInterval(autoScroll, 50);
    });
  </script>
</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();

include_once __DIR__ . '/../conf/conf.php';
include_once __DIR__ . '/../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

$hari_ini = date('l');
$mapHari = ['Sunday'=>'AKHAD','Monday'=>'SENIN','Tuesday'=>'SELASA','Wednesday'=>'RABU','Thursday'=>'KAMIS','Friday'=>'JUMAT','Saturday'=>'SABTU'];
$hari = $mapHari[$hari_ini] ?? strtoupper($hari_ini);

$sql = "SELECT DISTINCT d.kd_dokter,d.nm_dokter,pg.photo FROM jadwal j JOIN dokter d ON j.kd_dokter=d.kd_dokter LEFT JOIN pegawai pg ON d.kd_dokter=pg.nik WHERE j.hari_kerja='$hari' ORDER BY d.nm_dokter";
$res = bukaquery($sql);
$doctors = [];
while ($r = mysqli_fetch_assoc($res)) $doctors[$r['kd_dokter']] = $r;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Praktek Dokter - Gabungan</title>
  <link rel="stylesheet" href="praktek.css">
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <header class="header">
    <div class="logo"><?php include __DIR__ . '/../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
      <p><?= $setting['kontak'] ?> | <?= $setting['email'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <div class="panel">
      <h2>DAFTAR DOKTER PRAKTEK (<?= $hari ?>)</h2>
      <div class="table-container scrollable" id="doctorTableContainer">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama Dokter</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($doctors)): ?>
              <tr><td colspan="2">Belum ada dokter praktek hari ini.</td></tr>
            <?php else: foreach ($doctors as $kd => $doc): ?>
              <tr>
                <td><?= htmlspecialchars($doc['nm_dokter']) ?></td>
                <td><a class="link-button" href="dokter_praktek.php?kd=<?= urlencode($kd) ?>">Buka dashboard</a></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>
  <script>
    setTimeout(function(){ location.reload(); }, 60000);
  </script>
</body>
</html>

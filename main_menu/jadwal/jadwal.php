<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// Ambil data jadwal dokter
$sql = "SELECT d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai, j.jam_selesai, pg.photo
        FROM jadwal j
        JOIN dokter d ON j.kd_dokter = d.kd_dokter
        JOIN poliklinik p ON j.kd_poli = p.kd_poli
        JOIN pegawai pg ON d.kd_dokter = pg.nik
        ORDER BY d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai";
$result = bukaquery($sql);

// Susun data per dokter, lalu poli di bawahnya
$jadwal = [];
while($row = mysqli_fetch_assoc($result)) {
    $key = $row['nm_dokter'];
    if(!isset($jadwal[$key])) {
        $jadwal[$key] = [
            'nm_dokter' => $row['nm_dokter'],
            'photo'     => $row['photo'],
            'poli'      => []
        ];
    }
    $jadwal[$key]['poli'][$row['nm_poli']][] = [
        'hari'    => $row['hari_kerja'],
        'mulai'   => $row['jam_mulai'],
        'selesai' => $row['jam_selesai']
    ];
}

// Hitung jumlah card
$count = count($jadwal);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Jadwal Praktek Dokter</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="jadwal.css">
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
    <h2>DASHBOARD JADWAL PRAKTEK DOKTER</h2>

    <div class="grid <?= ($count > 5 ? 'scrollable' : '') ?>">
      <?php foreach($jadwal as $data): ?>
        <?php
          $namaDokter = $data['nm_dokter'];
          $photo      = $data['photo'];
          $baseFolder = basename(dirname(dirname(__DIR__)));
          $foto       = !empty($photo) ? "/{$baseFolder}/webapps/penggajian/{$photo}" : '';
        ?>
        <div class="card jadwal-card">
          <div class="card-body">
            <div class="foto-dokter">
              <?php if (!empty($foto)): ?>
                <img src="<?= $foto ?>" alt="Foto <?= $namaDokter ?>">
              <?php endif; ?>
            </div>
            <div class="info-dokter">
              <h3><?= $namaDokter ?></h3>
              <?php foreach($data['poli'] as $namaPoli => $jadwalPoli): ?>
                <div class="kelas"><strong><?= $namaPoli ?></strong></div>
                <ul class="jadwal-list">
                  <?php foreach($jadwalPoli as $j): ?>
                    <li><?= $j['hari'] ?> : <?= $j['mulai'] ?> - <?= $j['selesai'] ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php include '../assets/banner.php'; ?>
  </main>

  <script src="../assets/clock.js"></script>
  <script>
  setTimeout(function(){ location.reload(); }, 60000);
  document.querySelectorAll('.grid.scrollable').forEach(grid => {
    let direction = 1;
    function autoScroll() {
      grid.scrollTop += direction;
      if (grid.scrollTop + grid.clientHeight >= grid.scrollHeight) {
        direction = -1;
      } else if (grid.scrollTop <= 0) {
        direction = 1;
      }
    }
    setInterval(autoScroll, 50);
  });
  </script>
</body>
</html>

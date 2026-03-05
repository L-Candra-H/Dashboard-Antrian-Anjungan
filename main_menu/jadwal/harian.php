<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// Tentukan hari (default: hari ini)
$hari_ini = date('l');
$mapHari = [
    'Sunday'    => 'AKHAD',
    'Monday'    => 'SENIN',
    'Tuesday'   => 'SELASA',
    'Wednesday' => 'RABU',
    'Thursday'  => 'KAMIS',
    'Friday'    => 'JUMAT',
    'Saturday'  => 'SABTU'
];
$hari = $mapHari[$hari_ini];

// Ambil data jadwal dokter untuk hari ini
$sql = "SELECT d.nm_dokter, p.nm_poli, j.hari_kerja, j.jam_mulai, j.jam_selesai, pg.photo,
               j.kd_dokter, j.kd_poli, j.kuota
        FROM jadwal j
        JOIN dokter d ON j.kd_dokter = d.kd_dokter
        JOIN poliklinik p ON j.kd_poli = p.kd_poli
        JOIN pegawai pg ON d.kd_dokter = pg.nik
        WHERE j.hari_kerja = '$hari'
        ORDER BY d.nm_dokter, p.nm_poli, j.jam_mulai";
$result = bukaquery($sql);

// Kelompokkan berdasarkan nama dokter
$dokterData = [];
while($row = mysqli_fetch_assoc($result)) {
    $nama = $row['nm_dokter'];
    if (!isset($dokterData[$nama])) {
        $dokterData[$nama] = [
            'nm_dokter' => $row['nm_dokter'],
            'photo'     => $row['photo'],
            'jadwal'    => []
        ];
    }
    $dokterData[$nama]['jadwal'][] = [
        'poli'      => $row['nm_poli'],
        'mulai'     => $row['jam_mulai'],
        'selesai'   => $row['jam_selesai'],
        'kd_dokter' => $row['kd_dokter'],
        'kd_poli'   => $row['kd_poli'],
        'kuota'     => $row['kuota']
    ];
}

// Hitung jumlah card
$count = count($dokterData);

// === Tambahan: mode JSON ===
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');

    // pastikan semua string diubah ke UTF-8
    array_walk_recursive($dokterData, function (&$item) {
        if (is_string($item)) {
            $item = mb_convert_encoding($item, 'UTF-8', 'auto');
        }
    });

    echo json_encode(array_values($dokterData), JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Jadwal Harian Dokter</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="jadwal_harian.css">
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
    <h2>DASHBOARD JADWAL PRAKTEK DOKTER<br>HARI : <?= $hari ?></h2>

    <!-- Grid: scrollable hanya jika card >= 6 -->
    <div class="grid <?= ($count >= 6 ? 'scrollable' : '') ?>">
      <?php foreach($dokterData as $dokter): ?>
        <?php
        $photo = $dokter['photo'];
        $baseFolder = basename(dirname(dirname(__DIR__)));
        $foto = !empty($photo) ? "/{$baseFolder}/webapps/penggajian/{$photo}" : '';
        ?>
        <div class="card jadwal-card">
          <div class="card-body">
            <div class="foto-dokter">
              <?php if (!empty($foto)): ?>
                <img src="<?= $foto ?>" alt="Foto <?= $dokter['nm_dokter'] ?>">
              <?php endif; ?>
            </div>
            <div class="info-dokter">
              <h3><?= $dokter['nm_dokter'] ?></h3>
              <ul class="jadwal-list">
                <?php foreach($dokter['jadwal'] as $j): ?>
                  <li class="jadwal-time">
                    <span class="value"><?= $j['poli'] ?> : <?= $j['mulai'] ?> - <?= $j['selesai'] ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Banner ucapan default -->
    <?php include '../assets/banner.php'; ?>
  </main>

  <script src="../assets/clock.js"></script>

  <!-- Refresh otomatis setiap 60 detik -->
  <script>
  setTimeout(function(){
     location.reload();
  }, 60000);
  </script>

  <!-- Auto scroll vertikal hanya jika scrollable -->
  <script>
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

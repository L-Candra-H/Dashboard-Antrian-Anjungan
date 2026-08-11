<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

include_once '../conf/conf.php';
include_once '../conf/conf_pengajuan.php';
include_once '../conf/command.php';

$conn_pengajuan = bukakoneksi_pengajuan();

$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// Ambil daftar pengajuan sesuai aturan baku: harus Setuju dan AKTIF
$pengajuan_query = mysqli_query($conn_pengajuan, "
    SELECT ruang, nama_kegiatan, tgl_mulai, jam_mulai, tgl_selesai, jam_selesai
    FROM pengajuan_penggunaan_ruang
    WHERE tindak_lanjut='Setuju' AND status_dashboard='AKTIF'
    ORDER BY tgl_mulai ASC, jam_mulai ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Penggunaan Ruang</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="pengajuan.css"> <!-- CSS khusus pengajuan -->
</head>
<body class="pengajuan">
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
      <?php 
      if(mysqli_num_rows($pengajuan_query) > 0){
        while($p = mysqli_fetch_assoc($pengajuan_query)){
          $mulai   = date("d-m-Y", strtotime($p['tgl_mulai'])) . " " . $p['jam_mulai'];
          $selesai = date("d-m-Y", strtotime($p['tgl_selesai'])) . " " . $p['jam_selesai'];
          echo "<div class='ruang-card'>
                  <div class='ruang-room'>".strtoupper(htmlspecialchars($p['ruang']))."</div>
                  <div class='ruang-title'>".strtoupper(htmlspecialchars($p['nama_kegiatan']))."</div>
                  <div class='ruang-info'>"
                      .date("d-m-Y", strtotime($p['tgl_mulai']))." | ".$p['jam_mulai']
                      ." - "
                      .date("d-m-Y", strtotime($p['tgl_selesai']))." | ".$p['jam_selesai'].
                  "</div>
                </div>";

        }
      } else {
        echo "<div class='ruang-card'>
                <div class='ruang-title'>Belum ada kegiatan AKTIF</div>
              </div>";
      }
      ?>
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

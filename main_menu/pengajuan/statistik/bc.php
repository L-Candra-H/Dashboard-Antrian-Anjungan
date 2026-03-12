<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: ../login.php");
    exit;
}

include_once '../../conf/conf.php';
$conn_sik = bukakoneksi();

// ambil data instansi
$setting = mysqli_fetch_assoc(mysqli_query($conn_sik,
    "SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1"));

// jumlah TT (Bed Complement)
$qTTCount = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS total
    FROM kamar k
    JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE k.statusdata='1'
");
$totalTT = mysqli_fetch_assoc($qTTCount)['total'] ?? 0;

// --- Kesimpulan ---
if($totalTT >= 100){
  $kesimpulan = "BC menunjukkan jumlah tempat tidur tersedia. Jumlah TT cukup besar, sesuai standar pelayanan.";
} else {
  $kesimpulan = "BC menunjukkan jumlah tempat tidur tersedia. Jumlah TT masih terbatas, perlu evaluasi kapasitas.";
}

// --- Pagination setup ---
$limit = 6; // jumlah baris per halaman
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$total_pages = ceil($totalTT / $limit);

// query detail dengan LIMIT
$qTT = mysqli_query($conn_sik, "
    SELECT k.kd_kamar, b.nm_bangsal
    FROM kamar k
    JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE k.statusdata='1'
    ORDER BY k.kd_kamar ASC
    LIMIT $start, $limit
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>BC</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="statistik.css">
</head>
<body class="pengajuan">
  <header class="header">
    <div class="logo"><?php include '../../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
      <p><?= $setting['kontak'] ?> | <?= $setting['email'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <h2 class="anjungan-title">Indikator BC (Bed Complement)</h2>

    <!-- Tombol kembali -->
    <div class="button-group top-back">
      <a href="../menu_pengajuan.php" class="btn-back">Kembali</a>
    </div>

    <!-- Kotak indikator -->
    <div class="stat-box small">
      <div class="stat-item"><strong>BC</strong><br><?= $totalTT ?> TT</div>
      <div class="stat-item kesimpulan"><strong>Kesimpulan: </strong><?= $kesimpulan ?></div>
    </div>

    <!-- Tabel detail daftar kamar/bangsal -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr><th>Kode Kamar</th><th>Bangsal</th></tr>
        <?php while($d = mysqli_fetch_assoc($qTT)){ ?>
          <tr>
            <td><?= $d['kd_kamar'] ?></td>
            <td><?= $d['nm_bangsal'] ?></td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <!-- Pagination Prev/Next -->
    <div class="pagination">
      <?php if($page > 1): ?>
        <a href="?page=<?= $page-1 ?>" class="btn-back">Prev</a>
      <?php endif; ?>

      <?php if($page < $total_pages): ?>
        <a href="?page=<?= $page+1 ?>" class="btn-back">Next</a>
      <?php endif; ?>
    </div>
  </main>

  <?php include '../../assets/banner.php'; ?>
  <script src="../../assets/clock.js"></script>
</body>
</html>

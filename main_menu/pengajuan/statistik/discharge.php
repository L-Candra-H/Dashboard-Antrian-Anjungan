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

// default range
$tgl_awal  = $_GET['tgl_awal'] ?? date("Y-m-01");
$tgl_akhir = $_GET['tgl_akhir'] ?? date("Y-m-t");

// jumlah pasien masuk
$qMasuk = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS masuk 
    FROM kamar_inap 
    WHERE tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_masuk = mysqli_fetch_assoc($qMasuk)['masuk'] ?? 0;

// jumlah pasien keluar
$qKeluar = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS keluar 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_keluar = mysqli_fetch_assoc($qKeluar)['keluar'] ?? 0;

// --- Hitung Discharge Rate ---
$DischargeRate = $pasien_masuk > 0 ? (($pasien_keluar / $pasien_masuk) * 100) : 0;

// --- Kesimpulan ---
if($DischargeRate >= 80){
  $kesimpulan = "Discharge Rate tinggi (≥80%). Sebagian besar pasien keluar sesuai standar pelayanan.";
} else {
  $kesimpulan = "Discharge Rate rendah (<80%). Perlu evaluasi alur keluar pasien.";
}

// --- Pagination setup ---
$limit = 5; // jumlah baris per halaman
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// hitung total baris
$qCount = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS total
    FROM kamar_inap
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$total_rows = mysqli_fetch_assoc($qCount)['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);

// query detail dengan LIMIT
$qDetail = mysqli_query($conn_sik, "
    SELECT no_rawat, tgl_masuk, tgl_keluar, lama, stts_pulang
    FROM kamar_inap
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY tgl_keluar ASC
    LIMIT $start, $limit
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Discharge Rate</title>
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
    <h2 class="anjungan-title">Indikator Discharge Rate</h2>

    <!-- Tombol kembali -->
    <div class="button-group top-back">
      <a href="../menu_pengajuan.php" class="btn-back">Kembali</a>
    </div>

    <!-- Form filter range -->
    <form method="get" class="filter-form form-inline">
      <label>Dari tanggal:</label>
      <input type="date" name="tgl_awal" value="<?= date('Y-m-d', strtotime($tgl_awal)) ?>">
      <label>Sampai tanggal:</label>
      <input type="date" name="tgl_akhir" value="<?= date('Y-m-d', strtotime($tgl_akhir)) ?>">
      <button type="submit" class="btn-modern">Tampilkan</button>
    </form>

    <!-- Kotak indikator -->
    <div class="stat-box small">
      <div class="stat-item"><strong>Discharge Rate</strong><br><?= number_format($DischargeRate,2) ?> %</div>
      <div class="stat-item kesimpulan"><strong>Kesimpulan: </strong><?= $kesimpulan ?></div>
    </div>

    <!-- Tabel detail pasien keluar -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr><th>No Rawat</th><th>Tgl Masuk</th><th>Tgl Keluar</th><th>Lama Rawat</th><th>Status Pulang</th></tr>
        <?php while($d = mysqli_fetch_assoc($qDetail)){ ?>
          <tr>
            <td><?= $d['no_rawat'] ?></td>
            <td><?= date('d-m-Y', strtotime($d['tgl_masuk'])) ?></td>
            <td><?= date('d-m-Y', strtotime($d['tgl_keluar'])) ?></td>
            <td><?= $d['lama'] ?> hari</td>
            <td><?= $d['stts_pulang'] ?></td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <!-- Pagination Prev/Next -->
    <div class="pagination">
      <?php if($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">Prev</a>
      <?php endif; ?>

      <?php if($page < $total_pages): ?>
        <a href="?page=<?= $page+1 ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">Next</a>
      <?php endif; ?>
    </div>
  </main>

  <?php include '../../assets/banner.php'; ?>
  <script src="../../assets/clock.js"></script>
</body>
</html>

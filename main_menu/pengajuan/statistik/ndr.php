<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: ../login.php");
    exit;
}

include_once '../../conf/conf.php';
$conn_sik = bukakoneksi(); // koneksi ke DB SIK Utama

// ambil data instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// default range: bulan berjalan
$tgl_awal  = $_GET['tgl_awal'] ?? $_POST['tgl_awal'] ?? date("Y-m-01");
$tgl_akhir = $_GET['tgl_akhir'] ?? $_POST['tgl_akhir'] ?? date("Y-m-t");

// jumlah pasien keluar
$qKeluar = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS keluar 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_keluar = mysqli_fetch_assoc($qKeluar)['keluar'] ?? 0;

// jumlah pasien meninggal <48 jam (lama rawat <=2 hari)
$qMati = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS mati 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
      AND stts_pulang='Meninggal'
      AND lama <= 2
");
$pasien_mati = mysqli_fetch_assoc($qMati)['mati'] ?? 0;

// hitung NDR
$NDR = $pasien_keluar > 0 ? (($pasien_mati / $pasien_keluar) * 1000) : 0;

// pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// query detail pasien meninggal <48 jam
$qData = mysqli_query($conn_sik, "
    SELECT ki.no_rawat, b.nm_bangsal, ki.tgl_masuk, ki.jam_masuk,
           ki.tgl_keluar, ki.jam_keluar, ki.lama, ki.stts_pulang
    FROM kamar_inap ki
    JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
      AND ki.stts_pulang='Meninggal'
      AND ki.lama <= 2
    LIMIT $limit OFFSET $offset
");

$totalRows = mysqli_num_rows(mysqli_query($conn_sik, "
    SELECT ki.no_rawat
    FROM kamar_inap ki
    WHERE ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
      AND ki.stts_pulang='Meninggal'
      AND ki.lama <= 2
"));
$totalPages = ceil($totalRows / $limit);

// kesimpulan NDR (ideal <25 ‰)
if($NDR == 0){
    $kesimpulan = "Tidak ada pasien meninggal <48 jam pada periode ini.";
} elseif($NDR < 25){
    $kesimpulan = "NDR rendah (<25 ‰). Angka kematian dini masih terkendali.";
} else {
    $kesimpulan = "NDR tinggi (≥25 ‰). Angka kematian dini perlu evaluasi.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>NDR</title>
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
    <h2 class="anjungan-title">Indikator NDR (Net Death Rate)</h2>

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

    <!-- Hasil perhitungan -->
    <div class="stat-box small">
      <div class="stat-item"><strong>NDR</strong><br><?= number_format($NDR,2) ?> ‰</div>
      <div class="stat-item"><strong>Pasien Keluar</strong><br><?= $pasien_keluar ?></div>
      <div class="stat-item"><strong>Pasien Meninggal <48 jam</strong><br><?= $pasien_mati ?></div>
      <div class="stat-item kesimpulan"><strong>Kesimpulan: </strong><?= $kesimpulan ?></div>
    </div>

    <!-- Tabel detail -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr>
          <th>No Rawat</th><th>Bangsal</th><th>Tgl Masuk</th><th>Jam Masuk</th>
          <th>Tgl Keluar</th><th>Jam Keluar</th><th>Lama</th><th>Status Pulang</th>
        </tr>
        <?php while($d = mysqli_fetch_assoc($qData)){ ?>
          <tr>
            <td><?= $d['no_rawat'] ?></td>
            <td><?= $d['nm_bangsal'] ?></td>
            <td><?= date('d-m-Y', strtotime($d['tgl_masuk'])) ?></td>
            <td><?= $d['jam_masuk'] ?></td>
            <td><?= date('d-m-Y', strtotime($d['tgl_keluar'])) ?></td>
            <td><?= $d['jam_keluar'] ?></td>
            <td><?= $d['lama'] ?> hari</td>
            <td><?= $d['stts_pulang'] ?></td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <?php if($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">Prev</a>
      <?php endif; ?>
      <?php if($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">Next</a>
      <?php endif; ?>
    </div>
  </main>

  <?php include '../../assets/banner.php'; ?>
  <script src="../../assets/clock.js"></script>
</body>
</html>

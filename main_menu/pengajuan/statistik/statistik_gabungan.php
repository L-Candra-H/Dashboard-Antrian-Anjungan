<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: ../login.php");
    exit;
}

include_once '../../conf/conf.php';
$conn_sik = bukakoneksi();

// ambil data instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// default range
$tgl_awal  = $_GET['tgl_awal'] ?? $_POST['tgl_awal'] ?? date("Y-m-01");
$tgl_akhir = $_GET['tgl_akhir'] ?? $_POST['tgl_akhir'] ?? date("Y-m-t");

// jumlah TT
$tt = mysqli_num_rows(mysqli_query($conn_sik, "SELECT kd_kamar FROM kamar WHERE statusdata='1'"));

// jumlah hari perawatan
$qHari = mysqli_query($conn_sik, "
    SELECT SUM(lama) AS total_hari 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$hari_perawatan = mysqli_fetch_assoc($qHari)['total_hari'] ?? 0;

// jumlah pasien keluar
$qKeluar = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS keluar 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_keluar = mysqli_fetch_assoc($qKeluar)['keluar'] ?? 0;

// periode hari
$periode = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / 86400 + 1;

// jumlah pasien meninggal
$qMati = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS mati 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
      AND stts_pulang='Meninggal'
");
$pasien_mati = mysqli_fetch_assoc($qMati)['mati'] ?? 0;

// jumlah pasien meninggal <48 jam
$qMatiDini = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS mati 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
      AND stts_pulang='Meninggal'
      AND lama <= 2
");
$pasien_mati_dini = mysqli_fetch_assoc($qMatiDini)['mati'] ?? 0;

// --- Hitung indikator ---
$BOR = $tt > 0 ? (($hari_perawatan / ($tt * $periode)) * 100) : 0;
$ALOS = $pasien_keluar > 0 ? ($hari_perawatan / $pasien_keluar) : 0;
$TOI = $pasien_keluar > 0 ? ((($tt * $periode) - $hari_perawatan) / $pasien_keluar) : 0;
$BTO = $tt > 0 ? ($pasien_keluar / $tt) : 0;
$GDR = $pasien_keluar > 0 ? (($pasien_mati / $pasien_keluar) * 1000) : 0;
$NDR = $pasien_keluar > 0 ? (($pasien_mati_dini / $pasien_keluar) * 1000) : 0;

// --- Kesimpulan lengkap ---
if($BOR >= 60 && $BOR <= 85){
  $kesimpulanBOR = "BOR berada dalam rentang ideal (60–85%). Pemanfaatan tempat tidur optimal.";
} else {
  $kesimpulanBOR = "BOR berada di luar rentang ideal. Perlu evaluasi pemanfaatan tempat tidur.";
}

if($ALOS >= 3 && $ALOS <= 12){
  $kesimpulanALOS = "ALOS berada dalam rentang ideal (3–12 hari). Rata-rata lama rawat sesuai standar.";
} else {
  $kesimpulanALOS = "ALOS berada di luar rentang ideal. Perlu evaluasi efisiensi lama rawat.";
}

if($TOI >= 1 && $TOI <= 3){
  $kesimpulanTOI = "TOI berada dalam rentang ideal (1–3 hari). Tempat tidur tidak terlalu lama kosong.";
} else {
  $kesimpulanTOI = "TOI berada di luar rentang ideal. Perlu evaluasi kecepatan pergantian pasien.";
}

if($BTO >= 40 && $BTO <= 60){
  $kesimpulanBTO = "BTO berada dalam rentang ideal (40–60 kali). Pemanfaatan tempat tidur optimal.";
} else {
  $kesimpulanBTO = "BTO berada di luar rentang ideal. Perlu evaluasi frekuensi penggunaan tempat tidur.";
}

if($GDR < 45){
  $kesimpulanGDR = "GDR rendah (<45 ‰). Angka kematian umum masih terkendali.";
} else {
  $kesimpulanGDR = "GDR tinggi (≥45 ‰). Angka kematian umum perlu evaluasi.";
}

if($NDR < 25){
  $kesimpulanNDR = "NDR rendah (<25 ‰). Angka kematian dini masih terkendali.";
} else {
  $kesimpulanNDR = "NDR tinggi (≥25 ‰). Angka kematian dini perlu evaluasi.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Gabungan Indikator</title>
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
    <h2 class="anjungan-title">Rekap Statistik (BOR, ALOS, TOI, BTO, GDR, NDR)</h2>

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

    <!-- Tabel ringkasan indikator -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr>
          <th>Indikator</th><th>Nilai</th><th>Kesimpulan</th>
        </tr>
        <tr><td>BOR</td><td><?= number_format($BOR,2) ?> %</td><td><?= $kesimpulanBOR ?></td></tr>
        <tr><td>ALOS</td><td><?= number_format($ALOS,2) ?> hari</td><td><?= $kesimpulanALOS ?></td></tr>
        <tr><td>TOI</td><td><?= number_format($TOI,2) ?> hari</td><td><?= $kesimpulanTOI ?></td></tr>
        <tr><td>BTO</td><td><?= number_format($BTO,2) ?></td><td><?= $kesimpulanBTO ?></td></tr>
        <tr><td>GDR</td><td><?= number_format($GDR,2) ?> ‰</td><td><?= $kesimpulanGDR ?></td></tr>
        <tr><td>NDR</td><td><?= number_format($NDR,2) ?> ‰</td><td><?= $kesimpulanNDR ?></td></tr>
      </table>
    </div>
  </main>

  <?php include '../../assets/banner.php'; ?>
  <script src="../../assets/clock.js"></script>
</body>
</html>

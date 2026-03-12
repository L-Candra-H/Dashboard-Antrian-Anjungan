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

// jumlah TT (Bed Complement)
$BC = mysqli_num_rows(mysqli_query($conn_sik, "SELECT kd_kamar FROM kamar WHERE statusdata='1'"));

// jumlah hari perawatan (Bed Occupancy Day)
$qHari = mysqli_query($conn_sik, "
    SELECT SUM(lama) AS total_hari 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$BOD = mysqli_fetch_assoc($qHari)['total_hari'] ?? 0;

// periode hari
$periode = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / 86400 + 1;

// jumlah pasien keluar
$qKeluar = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS keluar 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_keluar = mysqli_fetch_assoc($qKeluar)['keluar'] ?? 0;

// jumlah pasien masuk
$qMasuk = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS masuk 
    FROM kamar_inap 
    WHERE tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_masuk = mysqli_fetch_assoc($qMasuk)['masuk'] ?? 0;

// jumlah pasien meninggal (untuk CFR umum)
$qMati = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS mati 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
      AND stts_pulang='Meninggal'
");
$pasien_mati = mysqli_fetch_assoc($qMati)['mati'] ?? 0;

// jumlah pasien readmisi
$qReadmisi = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS readmisi
    FROM (
        SELECT no_rawat, COUNT(*) AS jml
        FROM kamar_inap
        WHERE tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
        GROUP BY no_rawat
        HAVING jml > 1
    ) x
");
$pasien_readmisi = mysqli_fetch_assoc($qReadmisi)['readmisi'] ?? 0;

// --- Hitung indikator tambahan ---
$ADC = $periode > 0 ? ($BOD / $periode) : 0;
$DischargeRate = $pasien_masuk > 0 ? (($pasien_keluar / $pasien_masuk) * 100) : 0;
$CFR = $pasien_masuk > 0 ? (($pasien_mati / $pasien_masuk) * 100) : 0;
$ReadmissionRate = $pasien_keluar > 0 ? (($pasien_readmisi / $pasien_keluar) * 100) : 0;

// --- Kesimpulan sesuai standar masing-masing indikator ---
// ADC
if($ADC >= 100 && $ADC <= 200){
  $kesimpulanADC = "ADC berada dalam rentang ideal. Rata-rata pasien rawat inap per hari stabil.";
} else {
  $kesimpulanADC = "ADC berada di luar rentang ideal. Perlu evaluasi jumlah pasien rawat inap harian.";
}

// BC
if($BC >= 100){
  $kesimpulanBC = "BC menunjukkan jumlah tempat tidur tersedia. Jumlah TT cukup besar, sesuai standar pelayanan.";
} else {
  $kesimpulanBC = "BC menunjukkan jumlah tempat tidur tersedia. Jumlah TT masih terbatas, perlu evaluasi kapasitas.";
}

// BOD
if($BOD > 0){
  $kesimpulanBOD = "BOD menunjukkan jumlah hari tempat tidur terisi. Pemanfaatan TT tercatat.";
} else {
  $kesimpulanBOD = "BOD menunjukkan jumlah hari tempat tidur terisi. Tidak ada data perawatan pada periode ini.";
}

// CFR
if($CFR < 5){
  $kesimpulanCFR = "CFR rendah (<5%). Angka kematian kasus umum masih terkendali.";
} else {
  $kesimpulanCFR = "CFR tinggi (≥5%). Angka kematian kasus umum perlu evaluasi.";
}

// Discharge Rate
if($DischargeRate >= 80){
  $kesimpulanDischarge = "Discharge Rate tinggi (≥80%). Sebagian besar pasien keluar sesuai standar pelayanan.";
} else {
  $kesimpulanDischarge = "Discharge Rate rendah (<80%). Perlu evaluasi alur keluar pasien.";
}

// Readmission Rate
if($ReadmissionRate < 10){
  $kesimpulanReadmission = "Readmission Rate rendah (<10%). Mutu pelayanan rawat inap baik.";
} else {
  $kesimpulanReadmission = "Readmission Rate tinggi (≥10%). Perlu evaluasi mutu pelayanan dan tindak lanjut pasien.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Indikator Tambahan</title>
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
    <h2 class="anjungan-title">Indikator Tambahan (ADC, BC, BOD, CFR, Discharge, Readmission)</h2>

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

    <!-- Tabel ringkasan indikator tambahan -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr><th>Indikator</th><th>Nilai</th><th>Kesimpulan</th></tr>
        <tr><td>ADC</td><td><?= number_format($ADC,2) ?> pasien/hari</td><td><?= $kesimpulanADC ?></td></tr>
        <tr><td>BC</td><td><?= $BC ?> TT</td><td><?= $kesimpulanBC ?></td></tr>
        <tr><td>BOD</td><td><?= $BOD ?> hari</td><td><?= $kesimpulanBOD ?></td></tr>
        <tr><td>CFR</td><td><?= number_format($CFR,2) ?> %</td><td><?= $kesimpulanCFR ?></td></tr>
        <tr><td>Discharge Rate</td><td><?= number_format($DischargeRate,2) ?> %</td><td><?= $kesimpulanDischarge ?></td></tr>
        <tr><td>Readmission Rate</td><td><?= number_format($ReadmissionRate,2) ?> %</td><td><?= $kesimpulanReadmission ?></td></tr>
      </table>
    </div>
  </main>

  <?php include '../../assets/banner.php'; ?>
  <script src="../../assets/clock.js"></script>
</body>
</html>

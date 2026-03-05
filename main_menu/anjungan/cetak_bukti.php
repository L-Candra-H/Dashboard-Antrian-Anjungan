<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Ambil parameter no_rawat dari URL
$no_rawat = $_GET['no_rawat'] ?? '';

$conn = bukakoneksi();

// Ambil setting instansi
$setting = $conn->query("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1")->fetch_assoc();

// Ambil data registrasi + pasien + dokter + poli + penjamin
$sql = "SELECT rp.no_rawat, rp.no_reg, rp.tgl_registrasi, rp.jam_reg,
               p.no_rkm_medis, p.nm_pasien,
               pj.png_jawab,
               d.nm_dokter,
               pol.nm_poli
        FROM reg_periksa rp
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
        JOIN penjab pj ON rp.kd_pj = pj.kd_pj
        WHERE rp.no_rawat = '$no_rawat' LIMIT 1";

$data = $conn->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Registrasi - <?= htmlspecialchars($data['nm_pasien'] ?? 'Pasien') ?></title>
  <link rel="stylesheet" href="anjungan.css">
</head>
<body class="cetak-body" onload="cetakDanKembali()">
  
  <!-- Header instansi -->
  <div class="cetak-header">
    <h1><?= htmlspecialchars($setting['nama_instansi'] ?? '-') ?></h1>
    <p><?= htmlspecialchars($setting['alamat_instansi'] ?? '-') ?><br><?= htmlspecialchars($setting['kabupaten'] ?? '-') ?></p>
  </div>
  
  <div class="cetak-divider"></div>
  <div class="cetak-title">BUKTI REGISTRASI</div>
  
  <!-- Nomor antrian besar -->
  <div class="nomor-antrian">
    <?= htmlspecialchars($data['no_reg'] ?? '-') ?>
  </div>

  <!-- Data poli & dokter -->
  <div class="cetak-content">
    <table>
      <tr><td class="cetak-label">Poli</td><td class="cetak-colon">:</td><td class="cetak-val"><?= htmlspecialchars($data['nm_poli'] ?? '-') ?></td></tr>
      <tr><td class="cetak-label">Dokter</td><td class="cetak-colon">:</td><td class="cetak-val"><?= htmlspecialchars($data['nm_dokter'] ?? '-') ?></td></tr>
    </table>
  </div>

  <div class="cetak-divider"></div>

  <!-- Data pasien -->
  <div class="cetak-content">
    <table>
      <tr><td class="cetak-label">No. Rawat</td><td class="cetak-colon">:</td><td class="cetak-val" style="font-weight:normal; font-size:11px;"><?= htmlspecialchars($data['no_rawat'] ?? '-') ?></td></tr>
      <tr><td class="cetak-label">No. RM</td><td class="cetak-colon">:</td><td class="cetak-val"><?= htmlspecialchars($data['no_rkm_medis'] ?? '-') ?></td></tr>
      <tr><td class="cetak-label">Pasien</td><td class="cetak-colon">:</td><td class="cetak-val"><?= htmlspecialchars($data['nm_pasien'] ?? '-') ?></td></tr>
      <tr><td class="cetak-label">Penjamin</td><td class="cetak-colon">:</td><td class="cetak-val"><?= htmlspecialchars($data['png_jawab'] ?? '-') ?></td></tr>
    </table>
  </div>
  
  <div class="cetak-divider"></div>
  
  <!-- Tanggal & jam -->
  <div class="cetak-content">
    <table>
      <tr><td class="cetak-label">Tanggal</td><td class="cetak-colon">:</td><td class="cetak-val" style="font-weight:normal;"><?= date("d-m-Y", strtotime($data['tgl_registrasi'] ?? date("Y-m-d"))) ?></td></tr>
      <tr><td class="cetak-label">Jam</td><td class="cetak-colon">:</td><td class="cetak-val" style="font-weight:normal;"><?= htmlspecialchars($data['jam_reg'] ?? date("H:i")) ?> WIB</td></tr>
    </table>
  </div>
  
  <!-- Footer -->
  <div class="cetak-footer">
    <div class="cetak-divider"></div>
    <p>Mohon dibawa saat pemeriksaan.<br>Semoga lekas sembuh!</p>
  </div>

  <script>
    function cetakDanKembali() {
      // beri jeda agar data sudah render sebelum print
      setTimeout(function() {
        window.print();
        sessionStorage.clear();
        window.location.href = 'anjungan.php';
      }, 500);
    }
  </script>
</body>
</html>

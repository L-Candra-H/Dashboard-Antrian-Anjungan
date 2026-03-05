<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>API GET LAST</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/dashboard.css">
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
    <h2>API GET LAST - Dashboard Antrian</h2>
    <div class="api-grid">
      <div class="menu-column">
        <h3>Poli</h3>
        <pre id="poli"></pre>
      </div>
      <div class="menu-column">
        <h3>Farmasi</h3>
        <pre id="farmasi"></pre>
      </div>
      <div class="menu-column">
        <h3>Lab Gabungan (PK, PA, MB)</h3>
        <pre id="labgabung"></pre>
      </div>
      <div class="menu-column">
        <h3>Radiologi</h3>
        <pre id="radiologi"></pre>
      </div>
      <div class="menu-column">
        <h3>Loket</h3>
        <pre id="loket"></pre>
      </div>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>
  <script>
  async function loadPanel(id, url) {
    try {
      const res = await fetch(url);
      const data = await res.json();
      // tampilkan JSON mentah apa adanya
      document.getElementById(id).textContent = JSON.stringify(data, null, 2);
    } catch (e) {
      document.getElementById(id).textContent = "Error load data";
    }
  }

  function refreshAll() {
    loadPanel("poli", "get_last.php");
    loadPanel("farmasi", "get_last_farmasi.php");
    loadPanel("labgabung", "get_last_lab.php");   // ✅ endpoint gabungan
    loadPanel("radiologi", "get_last_rad.php");
    loadPanel("loket", "get_last_loket.php"); // ✅ tambahan untuk loket
  }

  setInterval(refreshAll, 5000);
  refreshAll();
  </script>
</body>
</html>

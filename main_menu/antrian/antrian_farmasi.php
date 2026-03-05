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
  <title>Dashboard Antrian Farmasi</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="antrian.css">
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

  <main class="dashboard two-column">
    <!-- Kolom kiri: DAFTAR RESEP HARI INI -->
    <div class="left-column">
      <h2>DAFTAR RESEP HARI INI</h2>
      <div class="farmasi-grid">
        <div class="panel">
          <h3>NON RACIKAN</h3>
          <div id="farmasiNonRacikan" class="table-container"><em>Memuat data...</em></div>
        </div>
        <div class="panel">
          <h3>RACIKAN</h3>
          <div id="farmasiRacikan" class="table-container"><em>Memuat data...</em></div>
        </div>
      </div>
    </div>

    <!-- Kolom kanan: panel panggilan farmasi -->
    <div class="right-column">
      <div class="panel detail-panel">
        <h2>VALIDASI</h2>
        <div class="called-box" id="validasiAktif"><em>Menunggu validasi resep...</em></div>
      </div>
      <div class="panel detail-panel">
        <h2>PENYERAHAN RESEP</h2>
        <div class="called-box last" id="penyerahanAktif"><em>Belum ada penyerahan resep...</em></div>
      </div>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>
  <script src="suara.js"></script>

  <script>
  function sensorNamaJS(nama) {
    if (!nama) return "";
    return nama[0] + "*".repeat(nama.length - 1);
  }

  function renderTable(targetId, resepList) {
    if (!resepList || resepList.length === 0) {
      document.getElementById(targetId).innerHTML = "<div><em>Belum ada data</em></div>";
      return;
    }
    if (!Array.isArray(resepList)) resepList = [resepList];

    let html = "<table class='data-table'><thead><tr>";
    html += "<th>No. Resep</th><th>Nama Pasien</th><th>Dokter</th>";
    html += "</tr></thead><tbody>";

    resepList.forEach(r => {
      html += `<tr>
                 <td>${r.no_resep}</td>
                 <td>${sensorNamaJS(r.nm_pasien)}</td>
                 <td>${r.nm_dokter || ""}</td>
               </tr>`;
    });

    html += "</tbody></table>";
    document.getElementById(targetId).innerHTML = html;
  }

  // Polling data dari get_last_farmasi.php
  setInterval(() => {
    fetch('get_last_farmasi.php')
      .then(res => res.json())
      .then(data => {
        renderTable("farmasiNonRacikan", data.nonracikan);
        renderTable("farmasiRacikan", data.racikan);

        if (data.calledValidasiFarmasi) {
          document.getElementById('validasiAktif').innerHTML =
            `<div class="big-code">${data.calledValidasiFarmasi.no_resep}</div>
             <div>${data.calledValidasiFarmasi.no_rawat}</div>
             <div>${sensorNamaJS(data.calledValidasiFarmasi.nm_pasien)}</div>
             <div>${data.calledValidasiFarmasi.jam_validasi}</div>`;
          playSuara(`Atas nama ${data.calledValidasiFarmasi.nm_pasien}, silahkan menuju loket apotik.`);
        }

        if (data.calledPenyerahanFarmasi) {
          document.getElementById('penyerahanAktif').innerHTML =
            `<div class="big-code">${data.calledPenyerahanFarmasi.no_resep}</div>
             <div>${data.calledPenyerahanFarmasi.no_rawat}</div>
             <div>${sensorNamaJS(data.calledPenyerahanFarmasi.nm_pasien)}</div>
             <div>${data.calledPenyerahanFarmasi.jam_penyerahan}</div>`;
          playSuara(`Atas nama ${data.calledPenyerahanFarmasi.nm_pasien}, silahkan mengambil obat di loket apotik.`);
        }
      })
      .catch(err => console.error("Error get_last_farmasi:", err));
  }, 3000);

  // Deteksi panggilan bypass dari antrian_farmasi_bypass.php
  setInterval(() => {
    let bypassData = localStorage.getItem("bypassFarmasiCall");
    if (bypassData) {
      let data = JSON.parse(bypassData);

      document.getElementById('validasiAktif').innerHTML =
        `<div class="big-code">${data.no_resep}</div>
         <div>${data.no_rawat}</div>
         <div>${sensorNamaJS(data.nm_pasien)}</div>
         <div>${data.jam_validasi || '-'}</div>`;

      document.getElementById('penyerahanAktif').innerHTML =
        `<div class="big-code">${data.no_resep}</div>
         <div>${data.no_rawat}</div>
         <div>${sensorNamaJS(data.nm_pasien)}</div>
         <div>${data.jam_penyerahan || '-'}</div>`;

      playSuara(`Resep nomor ${data.no_resep}, atas nama ${data.nm_pasien}, silahkan ke Farmasi.`);

      localStorage.removeItem("bypassFarmasiCall");
    }
  }, 2000);
  </script>
</body>
</html>

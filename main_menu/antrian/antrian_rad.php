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
  <title>Dashboard Antrian Radiologi</title>
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
    <!-- Kolom kiri: daftar permintaan radiologi -->
    <div class="left-column">
      <h2>DAFTAR PERMINTAAN RADIOLOGI HARI INI</h2>
      <div class="lab-grid"><em>Memuat data permintaan...</em></div>
    </div>

    <!-- Kolom kanan: panel panggilan -->
    <div class="right-column">
      <div class="panel detail-panel">
        <h2>PENGAMBILAN SAMPEL</h2>
        <div class="called-box" id="sampelStandby">
          <em>Menunggu panggilan sampel...</em>
        </div>
        <div class="called-box hidden" id="sampelAktif"></div>
      </div>

      <div class="panel detail-panel">
        <h2>HASIL RADIOLOGI</h2>
        <div class="called-box last" id="hasilStandby">
          <em>Menunggu panggilan hasil...</em>
        </div>
        <div class="called-box hidden" id="hasilAktif"></div>
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

  function renderLab(dataList) {
    let html = `<table class="data-table">
                  <thead>
                    <tr><th>No. Reg</th><th>Nama Pasien</th><th>Perujuk</th><th>Jam Permintaan</th><th>Ambil Sampel</th><th>Keluar Hasil</th></tr>
                  </thead><tbody>`;
    dataList.forEach(row => {
      html += `<tr>
                 <td>${row.no_reg}</td>
                 <td>${sensorNamaJS(row.nm_pasien)}</td>
                 <td>${row.nm_dokter}</td>
                 <td>${row.jam_permintaan}</td>
                 <td>${row.jam_sampel}</td>
                 <td>${row.jam_hasil}</td>
               </tr>`;
    });
    html += `</tbody></table>`;
    document.querySelector('.lab-grid').innerHTML = html;
  }

  setInterval(() => {
    fetch('get_last_rad.php')
      .then(res => res.json())
      .then(data => {
        if (data.permintaanHariIniRAD) {
          renderLab(data.permintaanHariIniRAD);
        }

        if (data.calledSampelRAD) {
          if (data.calledSampelRAD.mode === "active") {
            document.getElementById('sampelAktif').innerHTML =
              `<div class="big-code">${data.calledSampelRAD.kd_poli}${data.calledSampelRAD.no_reg}</div>
               <div>${data.calledSampelRAD.no_rawat}</div>
               <div>${sensorNamaJS(data.calledSampelRAD.nm_pasien)}</div>`;
            playSuara(`Atas nama ${data.calledSampelRAD.nm_pasien}, silahkan menuju ruang radiologi.`);
            setTimeout(() => { document.getElementById('sampelAktif').innerHTML = ""; }, 5000);
          }
          document.getElementById('sampelStandby').innerHTML =
            `<div class="big-code">${data.calledSampelRAD.kd_poli}${data.calledSampelRAD.no_reg}</div>
             <div>${data.calledSampelRAD.no_rawat}</div>
             <div>${sensorNamaJS(data.calledSampelRAD.nm_pasien)}</div>`;
        }

        if (data.calledHasilRAD) {
          if (data.calledHasilRAD.mode === "active") {
            document.getElementById('hasilAktif').innerHTML =
              `<div class="big-code">${data.calledHasilRAD.kd_poli}${data.calledHasilRAD.no_reg}</div>
               <div>${data.calledHasilRAD.no_rawat}</div>
               <div>${sensorNamaJS(data.calledHasilRAD.nm_pasien)}</div>`;
            playSuara(`Atas nama ${data.calledHasilRAD.nm_pasien}, silahkan mengambil hasil di ruang radiologi.`);
            setTimeout(() => { document.getElementById('hasilAktif').innerHTML = ""; }, 5000);
          }
          document.getElementById('hasilStandby').innerHTML =
            `<div class="big-code">${data.calledHasilRAD.kd_poli}${data.calledHasilRAD.no_reg}</div>
             <div>${data.calledHasilRAD.no_rawat}</div>
             <div>${sensorNamaJS(data.calledHasilRAD.nm_pasien)}</div>`;
        }
      })
      .catch(err => console.error('Error get_last_rad:', err));
  }, 3000);
  </script>
</body>
</html>

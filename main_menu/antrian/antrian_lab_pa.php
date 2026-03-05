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
  <title>Dashboard Antrian Lab PA</title>
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
    <!-- Kolom kiri: daftar permintaan lab PA -->
    <div class="left-column">
      <h2>DAFTAR PERMINTAAN LAB. PATOLOGI ANATOMI HARI INI</h2>
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
        <h2>HASIL LAB</h2>
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
    fetch('get_last_pa.php')
      .then(res => res.json())
      .then(data => {
        if (data.permintaanHariIniPA) {
          renderLab(data.permintaanHariIniPA);
        }

        if (data.calledSampelPA) {
          if (data.calledSampelPA.mode === "active") {
            document.getElementById('sampelAktif').innerHTML =
              `<div class="big-code">${data.calledSampelPA.kd_poli}${data.calledSampelPA.no_reg}</div>
               <div>${data.calledSampelPA.no_rawat}</div>
               <div>${sensorNamaJS(data.calledSampelPA.nm_pasien)}</div>`;
            playSuara(`Atas nama ${data.calledSampelPA.nm_pasien}, silahkan menuju ruang laboratorium.`);
            setTimeout(() => { document.getElementById('sampelAktif').innerHTML = ""; }, 5000);
          }
          document.getElementById('sampelStandby').innerHTML =
            `<div class="big-code">${data.calledSampelPA.kd_poli}${data.calledSampelPA.no_reg}</div>
             <div>${data.calledSampelPA.no_rawat}</div>
             <div>${sensorNamaJS(data.calledSampelPA.nm_pasien)}</div>`;
        }

        if (data.calledHasilPA) {
          if (data.calledHasilPA.mode === "active") {
            document.getElementById('hasilAktif').innerHTML =
              `<div class="big-code">${data.calledHasilPA.kd_poli}${data.calledHasilPA.no_reg}</div>
               <div>${data.calledHasilPA.no_rawat}</div>
               <div>${sensorNamaJS(data.calledHasilPA.nm_pasien)}</div>`;
            playSuara(`Atas nama ${data.calledHasilPA.nm_pasien}, silahkan mengambil hasil di ruang laboratorium.`);
            setTimeout(() => { document.getElementById('hasilAktif').innerHTML = ""; }, 5000);
          }
          document.getElementById('hasilStandby').innerHTML =
            `<div class="big-code">${data.calledHasilPA.kd_poli}${data.calledHasilPA.no_reg}</div>
             <div>${data.calledHasilPA.no_rawat}</div>
             <div>${sensorNamaJS(data.calledHasilPA.nm_pasien)}</div>`;
        }
      })
      .catch(err => console.error('Error get_last_pa:', err));
  }, 3000);
  </script>
</body>
</html>

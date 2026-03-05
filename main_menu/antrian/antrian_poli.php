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
  <title>Dashboard Antrian Poli</title>
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
    <!-- Kolom kiri: daftar pasien per POLI -->
    <div class="left-column">
      <h2>DAFTAR PASIEN HARI INI</h2>
      <div class="poli-grid"><em>Memuat data pasien...</em></div>
    </div>

    <!-- Kolom kanan: panel panggilan -->
    <div class="right-column">
      <!-- Panel PANGGIL SEKARANG (indikator singkat) -->
      <div class="panel detail-panel">
        <h2>PANGGIL ANTRIAN</h2>
        <div class="called-box" id="panggilSekarang">
          <em>Menunggu panggilan berikutnya...</em>
        </div>
      </div>

      <!-- Panel PANGGILAN TERAKHIR (detail lengkap) -->
      <div class="panel detail-panel">
        <h2>PANGGILAN TERAKHIR</h2>
        <div class="called-box last" id="calledLast">
          <em>Belum ada panggilan terakhir...</em>
        </div>
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

  function renderPasien(pasienList) {
    // Jika tidak ada pasien sama sekali
    if (!pasienList || pasienList.length === 0) {
      document.querySelector('.poli-grid').innerHTML =
        `<div class="poli-box"><em>Belum Ada Pasien Terdaftar</em></div>`;
      return;
    }

    let poliData = {};
    pasienList.forEach(row => {
      let key = row.nm_poli + '-' + row.kd_dokter;
      if (!poliData[key]) {
        poliData[key] = { nm_poli: row.nm_poli, nm_dokter: row.nm_dokter, pasien: [] };
      }
      poliData[key].pasien.push(row);
    });

    let html = '';
    for (let key in poliData) {
      let poli = poliData[key];
      html += `<div class="poli-box">
                 <h3>${poli.nm_poli}</h3>
                 <div class="poli-info">
                   <span>Dokter: ${poli.nm_dokter}</span>
                   <span>Jumlah pasien: ${poli.pasien.length}</span>
                 </div>
                 <div class="table-container">
                   <table>
                     <thead>
                       <tr><th>No. Reg</th><th>Nama Pasien</th><th>Status</th><th>Bayar</th></tr>
                     </thead>
                     <tbody>`;
      poli.pasien.forEach(p => {
        html += `<tr>
                   <td>${p.no_reg}</td>
                   <td>${sensorNamaJS(p.nm_pasien)}</td>
                   <td>${p.stts}</td>
                   <td>${p.png_jawab}</td>
                 </tr>`;
      });
      html += `</tbody></table></div></div>`;
    }

    document.querySelector('.poli-grid').innerHTML = html;
  }

  let lastCallId = null;

  setInterval(() => {
    fetch('get_last.php')
      .then(res => res.json())
      .then(data => {
        // Render daftar pasien
        if (data.pasienHariIni) {
          renderPasien(data.pasienHariIni);
        }

        // Panel panggilan
        if (data.calledNow) {
          let currentCallId = data.calledNow.no_rawat + "-" + data.calledNow.status;
          if (currentCallId !== lastCallId) {
            lastCallId = currentCallId;

            document.getElementById('panggilSekarang').innerHTML =
              `<div class="big-code">${data.calledNow.kd_poli}${data.calledNow.no_reg}</div>`;

            document.getElementById('calledLast').innerHTML =
              `<div class="big-code">${data.calledNow.kd_poli}${data.calledNow.no_reg}</div>
               <div>${data.calledNow.no_rawat}</div>
               <div>${sensorNamaJS(data.calledNow.nm_pasien)}</div>
               <div>${data.calledNow.nm_poli}</div>
               <div>${data.calledNow.nm_dokter}</div>
               <div class="penjab ${data.calledNow.png_jawab!='Umum'?'highlight':''}">
                 ${data.calledNow.png_jawab}
               </div>`;

            playSuara(`Atas nama ${data.calledNow.nm_pasien}, silahkan menuju ${data.calledNow.nm_poli}.`);
          }
        } else {
          document.getElementById('panggilSekarang').innerHTML = `<em>Menunggu panggilan berikutnya...</em>`;
        }

        if (!data.calledLast) {
          document.getElementById('calledLast').innerHTML = `<em>Belum ada panggilan terakhir...</em>`;
        }
      })
      .catch(err => console.error('Error get_last:', err));
  }, 3000);

  // Deteksi panggilan bypass dari antrian_poli_bypass.php
  setInterval(() => {
    let bypassData = localStorage.getItem("bypassCall");
    if (bypassData) {
      let data = JSON.parse(bypassData);

      // Ambil nama bersih
      let namaBersih = data.nm_pasien_bersih || data.nm_pasien;

      // Tampilkan di panel panggilan
      document.getElementById('panggilSekarang').innerHTML =
        `<div class="big-code">${data.kd_poli}${data.no_reg}</div>`;

      document.getElementById('calledLast').innerHTML =
        `<div class="big-code">${data.kd_poli}${data.no_reg}</div>
         <div>${data.no_rawat}</div>
         <div>${sensorNamaJS(namaBersih)}</div>
         <div>${data.nm_poli}</div>
         <div>${data.nm_dokter}</div>
         <div class="penjab ${data.png_jawab!='Umum'?'highlight':''}">
           ${data.png_jawab}
         </div>`;

      // Mainkan suara panggilan dengan nama bersih
      playSuara(`Atas nama ${namaBersih}, silahkan menuju ${data.nm_poli}.`);

      // Hapus bypass setelah dipanggil agar tidak berulang
      localStorage.removeItem("bypassCall");
    }
  }, 2000);

  </script>
</body>
</html>

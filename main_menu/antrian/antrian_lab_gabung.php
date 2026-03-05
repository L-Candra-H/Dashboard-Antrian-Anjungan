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
  <title>Dashboard Antrian Lab Gabungan</title>
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
    <!-- Kolom kiri: daftar permintaan gabungan -->
    <div class="left-column">
      <h2>DAFTAR PERMINTAAN LAB (MB, PA, PK) HARI INI</h2>
      <div class="lab-grid" id="labTable"><em>Memuat data permintaan...</em></div>
    </div>

    <!-- Kolom kanan: panel panggilan gabungan -->
    <div class="right-column">
      <div class="panel detail-panel">
        <h2>PENGAMBILAN SAMPEL</h2>
        <div class="called-box" id="sampelGabung"><em>Menunggu panggilan sampel...</em></div>
      </div>

      <div class="panel detail-panel">
        <h2>PENYERAHAN HASIL</h2>
        <div class="called-box last" id="hasilGabung"><em>Menunggu panggilan hasil...</em></div>
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

  function renderLab(allData) {
    let html = `<table class="data-table">
                  <thead>
                    <tr><th>No. Reg</th><th>Nama Pasien</th><th>Perujuk</th><th>Jenis Lab</th><th>Jam Permintaan</th><th>Ambil Sampel</th><th>Keluar Hasil</th></tr>
                  </thead><tbody>`;

    if (!allData || allData.length === 0) {
      html += `<tr><td colspan="7" style="text-align:center;"><em>Tidak ada permintaan hari ini</em></td></tr>`;
    } else {
      allData.forEach(row => {
        html += `<tr>
                   <td>${row.no_reg}</td>
                   <td>${sensorNamaJS(row.nm_pasien)}</td>
                   <td>${row.nm_dokter}</td>
                   <td>${row.jenis || ""}</td>
                   <td>${row.jam_permintaan}</td>
                   <td>${row.jam_sampel}</td>
                   <td>${row.jam_hasil}</td>
                 </tr>`;
      });
    }

    html += `</tbody></table>`;
    document.getElementById('labTable').innerHTML = html;
  }

  setInterval(() => {
    fetch('get_last_lab.php')
      .then(res => res.json())
      .then(data => {
        // Gunakan data.gabungan langsung dari backend
        let allData = [];
        if (data.gabungan && data.gabungan.length > 0) {
          allData = data.gabungan.map(r => {
            // tambahkan jenis lab jika belum ada
            if (!r.jenis) {
              if (data.mb.permintaanHariIniMB.some(x => x.no_reg === r.no_reg)) r.jenis = "MB";
              else if (data.pa.permintaanHariIniPA.some(x => x.no_reg === r.no_reg)) r.jenis = "PA";
              else if (data.pk.permintaanHariIniPK.some(x => x.no_reg === r.no_reg)) r.jenis = "PK";
            }
            return r;
          });
        }
        renderLab(allData);

        // Gabungan panggilan sampel
        let sampel = (data.mb && data.mb.calledSampelMB)
                  || (data.pa && data.pa.calledSampelPA)
                  || (data.pk && data.pk.calledSampelPK);

        if (sampel) {
          document.getElementById('sampelGabung').innerHTML =
            `<div class="big-code">${sampel.kd_poli}${sampel.no_reg}</div>
             <div>${sampel.no_rawat}</div>
             <div>${sensorNamaJS(sampel.nm_pasien)}</div>`;
          if (sampel.mode === "active") {
            playSuara(`Atas nama ${sampel.nm_pasien}, silahkan menuju ruang laboratorium.`);
          }
        } else {
          document.getElementById('sampelGabung').innerHTML = "<em>Menunggu panggilan sampel...</em>";
        }

        // Gabungan panggilan hasil
        let hasil = (data.mb && data.mb.calledHasilMB)
                 || (data.pa && data.pa.calledHasilPA)
                 || (data.pk && data.pk.calledHasilPK);

        if (hasil) {
          document.getElementById('hasilGabung').innerHTML =
            `<div class="big-code">${hasil.kd_poli}${hasil.no_reg}</div>
             <div>${hasil.no_rawat}</div>
             <div>${sensorNamaJS(hasil.nm_pasien)}</div>`;
          if (hasil.mode === "active") {
            playSuara(`Atas nama ${hasil.nm_pasien}, silahkan mengambil hasil di ruang laboratorium.`);
          }
        } else {
          document.getElementById('hasilGabung').innerHTML = "<em>Menunggu panggilan hasil...</em>";
        }
      })
      .catch(err => console.error('Error get_last_lab:', err));
  }, 3000);
  </script>
</body>
</html>

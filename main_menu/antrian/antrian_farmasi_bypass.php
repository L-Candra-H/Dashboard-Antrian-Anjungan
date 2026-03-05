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
  <title>Dashboard Antrian Farmasi Panggil Ulang</title>
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

  <main class="dashboard farmasi bypass">
    <h2>DAFTAR PANGGIL ULANG RESEP HARI INI</h2>
    <div class="poli-grid">
      <div class="panel" id="farmasiGabungan"><em>Memuat data...</em></div>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <!-- Notifikasi -->
  <div id="notif"></div>

  <script src="../assets/clock.js"></script>
  <script src="suara.js"></script>

  <script>
  // Fungsi renderTable gabungan
  function renderTable(targetId, resepList) {
    if (!resepList || resepList.length === 0) {
      document.getElementById(targetId).innerHTML =
        "<div><em>Belum ada data</em></div>";
      return;
    }

    let html = `<table class="full-table">
                  <thead>
                    <tr>
                      <th>No. Resep</th>
                      <th>No. Rawat</th>
                      <th>Nama Pasien</th>
                      <th>Dokter Peresep</th>
                      <th>Jam Validasi</th>
                      <th>Panggil Validasi</th>
                      <th>Jam Penyerahan</th>
                      <th>Panggil Penyerahan</th>
                    </tr>
                  </thead>
                  <tbody>`;

    resepList.forEach(r => {
      let namaBersih = r.nm_pasien_bersih || r.nm_pasien;

      html += `<tr>
                 <td>${r.no_resep}</td>
                 <td>${r.no_rawat}</td>
                 <td>${namaBersih}</td>
                 <td>${r.nm_dokter || ""}</td>
                 <td>${r.jam_peresepan || '-'}</td>`;

      // tombol panggil ulang validasi
      if (r.jam_peresepan && r.jam_validasi !== '-') {
        html += `<td><button class="btn-panggil" onclick='bypassPanggil("${namaBersih}", "validasi")'>PANGGIL ULANG</button></td>`;
      } else {
        html += `<td><button class="btn-panggil" disabled>PANGGIL ULANG</button></td>`;
      }

      html += `<td>${r.jam_penyerahan || '-'}</td>`;

      // tombol panggil ulang penyerahan
      if (r.jam_penyerahan && r.jam_penyerahan !== '-') {
        html += `<td><button class="btn-panggil" onclick='bypassPanggil("${namaBersih}", "penyerahan")'>PANGGIL ULANG</button></td>`;
      } else {
        html += `<td><button class="btn-panggil" disabled>PANGGIL ULANG</button></td>`;
      }

      html += `</tr>`;
    });

    html += `</tbody></table>`;
    document.getElementById(targetId).innerHTML = html;
  }

  function bypassPanggil(namaPasienBersih, mode) {
    localStorage.setItem("bypassFarmasiCall", namaPasienBersih);

    let notif = document.getElementById("notif");
    notif.innerText = "Pasien " + namaPasienBersih + " dipanggil ulang (" + mode + ").";
    notif.style.display = "block";
    setTimeout(() => { notif.style.display = "none"; }, 3000);

    if (typeof playSuara === "function") {
      if (mode === "validasi") {
        playSuara(`Atas nama ${namaPasienBersih} silahkan menuju loket Apotik`);
      } else if (mode === "penyerahan") {
        playSuara(`Atas nama ${namaPasienBersih} silahkan mengambil obat di loket Apotik`);
      }
    }
  }

  // Ambil data resep dari get_last_farmasi.php
  setInterval(() => {
    fetch('get_last_farmasi.php')
      .then(res => res.json())
      .then(data => {
        let gabungan = data.gabunganResep || [];

        // inject jam validasi
        if (data.calledValidasiFarmasi) {
          gabungan.forEach(r => {
            if (r.no_rawat === data.calledValidasiFarmasi.no_rawat) {
              r.jam_validasi = data.calledValidasiFarmasi.jam_validasi;
            }
          });
        }

        // inject jam penyerahan
        if (data.calledPenyerahanFarmasi) {
          gabungan.forEach(r => {
            if (r.no_rawat === data.calledPenyerahanFarmasi.no_rawat) {
              r.jam_penyerahan = data.calledPenyerahanFarmasi.jam_penyerahan;
            }
          });
        }

        if (gabungan.length > 0) {
          renderTable('farmasiGabungan', gabungan);
        } else {
          document.getElementById('farmasiGabungan').innerHTML = "<div><em>Belum ada data</em></div>";
        }
      })
      .catch(err => console.error('Error get_last_farmasi:', err));
  }, 3000);
  </script>
</body>
</html>

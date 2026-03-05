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
  <title>Dashboard Antrian Poli Panggil Ulang</title>
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

  <!-- Perhatikan: class dashboard bypass -->
  <main class="dashboard bypass">
    <h2>DAFTAR PANGGIL ULANG PASIEN POLI HARI INI</h2>
    <div class="poli-grid"><em>Memuat data pasien...</em></div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <!-- Notifikasi -->
  <div id="notif"></div>

  <script src="../assets/clock.js"></script>
  <script src="suara.js"></script>

  <script>
  function renderPasien(pasienList) {
  if (!pasienList || pasienList.length === 0) {
    document.querySelector('.poli-grid').innerHTML =
      `<div><em>Belum Ada Pasien Dipanggil Hari Ini</em></div>`;
    return;
  }

  let html = `<table class="full-table">
                <thead>
                  <tr>
                    <th>No. Reg</th>
                    <th>No. Rawat</th>
                    <th>Nama Pasien</th>
                    <th>Bayar</th>
                    <th>Poli</th>
                    <th>Dokter</th>
                    <th>PANGGIL ULANG</th>
                  </tr>
                </thead>
                <tbody>`;

  pasienList.forEach(p => {
    html += `<tr>
               <td>${p.no_reg}</td>
               <td>${p.no_rawat}</td>
               <td>${p.nm_pasien}</td> <!-- tampilkan mentah -->
               <td>${p.png_jawab || ""}</td>
               <td>${p.nm_poli}</td>
               <td>${p.nm_dokter}</td>
               <td><button class="btn-panggil" onclick='bypassPanggil(${JSON.stringify(p)})'>PANGGIL ULANG</button></td>
             </tr>`;
  });

  html += `</tbody></table>`;
  document.querySelector('.poli-grid').innerHTML = html;
}

function bypassPanggil(pasien) {
  let namaBersih = pasien.nm_pasien_bersih || pasien.nm_pasien;
  localStorage.setItem("bypassCall", JSON.stringify(pasien));

  let notif = document.getElementById("notif");
  notif.innerText = "Pasien " + namaBersih + " dipanggil (bypass).";
  notif.style.display = "block";
  setTimeout(() => { notif.style.display = "none"; }, 3000);

  if (typeof playSuaraPanggil === "function") {
    playSuaraPanggil(namaBersih);
  }
}

// Ambil data pasien dari get_last.php (pakai field bypass)
setInterval(() => {
  fetch('get_last.php')
    .then(res => res.json())
    .then(data => {
      if (data.bypass) {
        renderPasien(data.bypass);
      }
    })
    .catch(err => console.error('Error get_last:', err));
}, 3000);

  </script>
</body>
</html>

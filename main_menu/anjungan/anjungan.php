<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten FROM setting LIMIT 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Anjungan Pendaftaran Mandiri</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="anjungan.css">
</head>
<body>
  <header class="header">
    <div class="logo"><?php include '../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <!-- Judul utama -->
    <h2 id="anjunganTitle" class="anjungan-title">ANJUNGAN PENDAFTARAN MANDIRI</h2>

    <!-- Step 1: Form input pasien (2 kolom) -->
    <section id="formPasien" class="form-container">
      <!-- Kolom kiri: aturan -->
      <div class="aturan">
        <h3>Petunjuk</h3>
        <p>
          Anjungan Pendaftaran Mandiri ini khusus untuk <strong>pasien lama</strong> yang sudah memiliki 
          <strong>Nomor Rekam Medis (No. RM)</strong>.
        </p>
        <p>
          Jika Anda <strong>pasien baru</strong> dan belum memiliki No. RM, 
          silakan menuju ke Loket <strong>Pendaftaran / ADMISI</strong>.
        </p>

        <!-- Tambahan menu cetak antrian loket untuk pasien baru -->
        <p class="ambil-antrian">
          👉 Untuk <strong>pasien baru</strong> yang belum memiliki No. RM, silakan :  
          <button onclick="ambilAntrianLoket()" class="btn-ambil">AMBIL ANTRIAN LOKET</button>
        </p>
      </div>

      <!-- Kolom kanan: input -->
      <div class="form-fields">
        <div class="form-input">
          <label for="identitas">No. KTP / No. RM:</label>
          <input type="text" id="identitas" placeholder="Masukkan No. KTP atau RM" onkeyup="autoCariPasien(event)">
        </div>

        <div class="form-input">
          <label for="tgl_lahir">Tanggal Lahir (dd/mm/yyyy):</label>
          <input type="text" id="tgl_lahir" placeholder="dd/mm/yyyy" onkeyup="autoCariPasien(event)">
        </div>
      </div>

    </section>

    <!-- Step 2 + Step 3 + Step 4: Data pasien + Poli + Draft -->
    <div id="pasienPoli" class="hidden pasien-poli-layout">
      <section id="dataPasien"></section>
      <section id="jadwalPoli"></section>
    </div>

    <!-- Step 4: Draft Bukti Registrasi -->
    <section id="draftBukti" class="hidden">
      <div class="kop-flex">
        <div class="logo">
          <?php include '../assets/logo.php'; ?>
        </div>
        <div class="instansi">
          <h1 id="namaInstansi"></h1>
          <p id="alamatInstansi"></p>
        </div>
      </div>
      <hr>
      <div class="box">
        <h3>Draft Bukti Registrasi</h3>
        <table>
          <tr><td class="label">Nama Pasien</td><td class="colon">:</td><td id="nmPasien"></td></tr>
          <tr><td class="label">Poli</td><td class="colon">:</td><td id="nmPoli"></td></tr>
          <tr><td class="label">Dokter</td><td class="colon">:</td><td id="nmDokter"></td></tr>
          <tr><td class="label">Jenis Bayar</td><td class="colon">:</td><td id="nmBayar"></td></tr>
          <tr><td class="label">Jam Daftar</td><td class="colon">:</td><td id="jamDaftar"></td></tr>
        </table>
        <div style="text-align:center; margin-top:20px;">
          <button onclick="editForm()">⬅ Kembali</button>
          <button onclick="cetakDraft()">💾 Simpan & Cetak</button>
        </div>
      </div>
    </section>

    <!-- Section khusus Ambil Antrian Loket -->
    <section id="antrianLoket" class="hidden">
      <div class="box-modern">
        <h3 class="anjungan-title">NOMOR ANTRIAN LOKET</h3>
        <div id="nomorLoket" class="nomor-box"></div>
        <div class="action-buttons">
          <a id="btnCetakLoket" href="#" class="btn-ambil">CETAK >>></a>
          <button class="btn-back" onclick="kembaliKeForm()">⬅ Kembali ke Menu Awal</button>
        </div>
      </div>
    </section>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>
  <script src="anjungan.js"></script>
  <script>
    // fungsi auto cari pasien
    function autoCariPasien(event) {
      if (event.key === "Enter") {
        cariPasien();
      }
    }
  </script>
</body>
</html>

<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"]) || $_SESSION["hak_akses"] !== 'administrator') {
    header("Location: login_penggunaan_ruang.php");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

include_once '../conf/conf.php';
include_once '../conf/conf_pengajuan.php'; // DB pengajuan
include_once '../conf/command.php';

$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");
$conn_pengajuan = bukakoneksi_pengajuan();

// Tambah ruang
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_ruang'])){
    $nama_ruang = mysqli_real_escape_string($conn_pengajuan, $_POST['nama_ruang']);
    if($nama_ruang != ''){
        $sql = "INSERT INTO pengaturan_ruang (nama_ruang) VALUES ('$nama_ruang')";
        if(!mysqli_query($conn_pengajuan, $sql)){
            $_SESSION['error_ruang'] = "Gagal tambah ruang: ".mysqli_error($conn_pengajuan);
        } else {
            $_SESSION['success_ruang'] = "Ruang berhasil ditambahkan.";
        }
    }
    header("Location: pengaturan_ruang.php");
    exit;
}

// Hapus ruang
if(isset($_GET['hapus'])){
    $id = intval($_GET['hapus']);
    mysqli_query($conn_pengajuan, "DELETE FROM pengaturan_ruang WHERE id_ruang=$id");
    $_SESSION['success_ruang'] = "Ruang berhasil dihapus.";
    header("Location: pengaturan_ruang.php");
    exit;
}

// Ambil daftar ruang
$query = mysqli_query($conn_pengajuan, "SELECT * FROM pengaturan_ruang ORDER BY id_ruang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengaturan Ruang Pertemuan</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="pengajuan.css">
</head>
<body class="pengajuan">
  <!-- Header -->
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
    <h2 class="anjungan-title">PENGATURAN RUANG PERTEMUAN</h2>

    <!-- Pesan sukses / error -->
    <?php if(isset($_SESSION['error_ruang'])) { ?>
      <div class="error-msg"><?= $_SESSION['error_ruang']; unset($_SESSION['error_ruang']); ?></div>
    <?php } ?>
    <?php if(isset($_SESSION['success_ruang'])) { ?>
      <div class="success-msg"><?= $_SESSION['success_ruang']; unset($_SESSION['success_ruang']); ?></div>
    <?php } ?>

    <!-- Form tambah ruang -->
    <form method="post" action="" class="form-input">
      <input type="text" name="nama_ruang" placeholder="Nama Ruang" required>
      <button type="submit">Tambah Ruang</button>
    </form>

    <!-- Daftar ruang -->
    <div class="table-container-ruang">
      <table class="tabel-ruang">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Ruang</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $no = 1;
          if(mysqli_num_rows($query) > 0) { 
            while($row = mysqli_fetch_assoc($query)) { ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_ruang']) ?></td>
                <td>
                  <a href="pengaturan_ruang.php?hapus=<?= $row['id_ruang'] ?>" 
                     onclick="return confirm('Hapus ruang ini?')">Hapus</a>
                </td>
              </tr>
            <?php } 
          } else { ?>
            <tr><td colspan="3">Belum ada ruang</td></tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <div class="button-group">
      <a href="pengaturan_user_akses.php" class="btn-exit">Pengaturan User Akses</a>
      <a href="daftar_penggunaan_ruang.php" class="btn-exit">Daftar Penggunaan Ruang</a>
      <a href="menu_pengajuan.php" class="btn-exit">Kembali</a>
    </div>
  </main>

  <!-- Banner bawah -->
  <?php include '../assets/banner.php'; ?>

  <!-- Script jam -->
  <script src="../assets/clock.js"></script>
</body>
</html>

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

$setting        = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");
$conn_pengajuan = bukakoneksi_pengajuan();
$conn_main      = bukakoneksi(); // koneksi ke DB utama (tabel user & pegawai)

// Proses tambah / update hak akses
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_user'], $_POST['hak_akses'])){
    $id_user      = mysqli_real_escape_string($conn_pengajuan, $_POST['id_user']); // plain text
    $hak_akses    = mysqli_real_escape_string($conn_pengajuan, $_POST['hak_akses']);
    $nama_pegawai = mysqli_real_escape_string($conn_pengajuan, $_POST['nama_pegawai']);

    if($id_user != '' && $nama_pegawai != ''){
        $cek = mysqli_query($conn_pengajuan, "SELECT * FROM pengaturan_user_akses WHERE id_user='$id_user'");
        if(mysqli_num_rows($cek) > 0){
            mysqli_query($conn_pengajuan, "UPDATE pengaturan_user_akses 
                                           SET hak_akses='$hak_akses', nama_pegawai='$nama_pegawai' 
                                           WHERE id_user='$id_user'");
            $_SESSION['success_user'] = "Hak akses berhasil diperbarui.";
        } else {
            mysqli_query($conn_pengajuan, "INSERT INTO pengaturan_user_akses (id_user, nama_pegawai, hak_akses) 
                                           VALUES ('$id_user','$nama_pegawai','$hak_akses')");
            $_SESSION['success_user'] = "User akses berhasil ditambahkan.";
        }
    }
    header("Location: pengaturan_user_akses.php");
    exit;
}

// Ambil daftar user untuk dropdown (hasil dekripsi plain text)
$listUser = mysqli_query($conn_main, "
    SELECT CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) AS id_user,
           p.nama AS nama_pegawai
    FROM user u
    LEFT JOIN pegawai p ON CAST(AES_DECRYPT(u.id_user,'nur') AS CHAR) = p.nik
    ORDER BY id_user ASC
");

// Ambil daftar user akses
$qAkses = mysqli_query($conn_pengajuan, "SELECT * FROM pengaturan_user_akses ORDER BY nama_pegawai ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengaturan User Akses</title>
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
    <h2 class="anjungan-title">PENGATURAN USER AKSES</h2>

    <!-- Pesan sukses / error -->
    <?php if(isset($_SESSION['error_user'])) { ?>
      <div class="error-msg"><?= $_SESSION['error_user']; unset($_SESSION['error_user']); ?></div>
    <?php } ?>
    <?php if(isset($_SESSION['success_user'])) { ?>
      <div class="success-msg"><?= $_SESSION['success_user']; unset($_SESSION['success_user']); ?></div>
    <?php } ?>

    <!-- Form tambah user akses -->
    <h3>Tambah User Akses</h3>
    <form method="post" action="" class="form-inline">
      <label>Kode User :</label>
      <select name="id_user" id="id_user" onchange="showNamaPegawai(this)" required>
        <option value="">-- Pilih Kode --</option>
        <?php
        while($u = mysqli_fetch_assoc($listUser)){
            $nama = $u['nama_pegawai'] ?: $u['id_user'];
            $kode = $u['id_user']; // hasil dekripsi plain text
            // gunakan plain text sebagai value
            echo "<option value='".htmlspecialchars($kode)."' data-nama='".htmlspecialchars($nama)."'>".$kode." - ".$nama."</option>";
        }
        ?>
      </select>

      <label>Nama Pegawai :</label>
      <input type="text" name="nama_pegawai" id="namaPegawai" readonly>

      <label>Hak Akses :</label>
      <select name="hak_akses" required>
        <option value="pengaju">Pengaju</option>
        <option value="pemroses">Pemroses</option>
        <option value="administrator" hidden>Administrator</option>
      </select>

      <button type="submit" class="btn-modern">Tambah</button>
    </form>

    <!-- Daftar user akses -->
    <h3>Daftar User Akses</h3>
    <div class="table-container-ruang">
      <table class="tabel-ruang">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Pegawai</th>
            <th>Hak Akses</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $no=1;
          if(mysqli_num_rows($qAkses) > 0){
            while($row = mysqli_fetch_assoc($qAkses)){
              echo "<tr>
                      <td>".$no++."</td>
                      <td>".htmlspecialchars($row['nama_pegawai'])."</td>
                      <td>".htmlspecialchars($row['hak_akses'])."</td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='3'>Belum ada user akses</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <div class="button-group">
      <a href="pengaturan_ruang.php" class="btn-exit">Pengaturan Ruang</a>
      <a href="daftar_penggunaan_ruang.php" class="btn-exit">Daftar Penggunaan Ruang</a>
      <a href="menu_pengajuan.php" class="btn-exit">Kembali</a>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>
  <script src="../assets/clock.js"></script>

  <script>
  function showNamaPegawai(select){
    var nama = select.options[select.selectedIndex].getAttribute('data-nama');
    document.getElementById('namaPegawai').value = nama;
  }
  </script>
</body>
</html>

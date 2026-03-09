<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/command.php';

$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

session_start();

if (isset($_POST['BtnLogin'])) {
    $usere     = validTeks4($_POST['usere'],30);
    $passworde = validTeks4($_POST['passworde'],30);

    // cek admin
    $cekAdmin = getOne2("SELECT COUNT(*) FROM admin 
                         WHERE usere=AES_ENCRYPT('$usere','nur') 
                           AND passworde=AES_ENCRYPT('$passworde','windi')");
    // cek user
    $cekUser  = getOne2("SELECT COUNT(*) FROM user 
                         WHERE id_user=AES_ENCRYPT('$usere','nur') 
                           AND password=AES_ENCRYPT('$passworde','windi')");

    if ($cekAdmin > 0) {
        // jika admin login
        $_SESSION["ses_pengajuan_login"] = $usere;
        // ambil nama pegawai berdasarkan nik
        $_SESSION["nama_pengaju"]        = getOne2("SELECT nama FROM pegawai WHERE nik='$usere' LIMIT 1");
        $_SESSION["hapus_nota_salah"]    = true; // admin otomatis punya hak akses penuh
        header("Location: daftar_pengajuan.php");
        exit;
    } elseif ($cekUser > 0) {
        // ambil data user termasuk hak akses hapus_nota_salah
        $rowUser = fetch_assoc("SELECT hapus_nota_salah FROM user 
                                WHERE id_user=AES_ENCRYPT('$usere','nur') 
                                  AND password=AES_ENCRYPT('$passworde','windi') 
                                LIMIT 1");
        
        $_SESSION["ses_pengajuan_login"] = $usere;
        // ambil nama pegawai berdasarkan nik
        $_SESSION["nama_pengaju"]        = getOne2("SELECT nama FROM pegawai WHERE nik='$usere' LIMIT 1");
        // konversi string 'true'/'false' dari DB ke boolean PHP
        $_SESSION["hapus_nota_salah"]    = ($rowUser['hapus_nota_salah'] === 'true');

        header("Location: daftar_pengajuan.php");
        exit;
    } else {
        $error = "Login gagal! Username/Password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengajuan Hapus Nota Salah</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="pengajuan.css">
</head>
<body class="pengajuan">
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
    <h2 class="anjungan-title">PENGAJUAN HAPUS NOTA SALAH</h2>

    <!-- Form login -->
    <form class="login-form" method="post" action="">
      <input type="text" name="usere" placeholder="User Login" required autofocus>
      <input type="password" name="passworde" placeholder="Password" required>
      <div class="button-group">
        <button type="submit" name="BtnLogin">Login</button>
        <button type="reset">Batal</button>
      </div>
      <?php if(isset($error)) echo "<p style='color:red; margin-top:10px;'>$error</p>"; ?>
    </form>
  </main>

  <?php include '../assets/banner.php'; ?>
  <script src="../assets/clock.js"></script>
</body>
</html>

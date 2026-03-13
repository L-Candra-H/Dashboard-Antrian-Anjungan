<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';             // koneksi DB utama
include_once '../conf/conf_pengajuan.php';   // koneksi DB pengajuan
include_once '../conf/command.php';

$conn_pengajuan = bukakoneksi_pengajuan();

$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

session_start();

if (isset($_POST['BtnLogin'])) {
    $usere     = validTeks4($_POST['usere'],30);
    $passworde = validTeks4($_POST['passworde'],30);

    // cek admin (DB utama)
    $rowAdmin = fetch_assoc("SELECT usere FROM admin 
                             WHERE usere=AES_ENCRYPT('$usere','nur') 
                               AND passworde=AES_ENCRYPT('$passworde','windi') 
                             LIMIT 1");

    // cek user (DB utama)
    $rowUser  = fetch_assoc("SELECT id_user, hapus_nota_salah FROM user 
                             WHERE id_user=AES_ENCRYPT('$usere','nur') 
                               AND password=AES_ENCRYPT('$passworde','windi') 
                             LIMIT 1");

    if ($rowAdmin) {
        // jika admin login
        $_SESSION["ses_pengajuan_login"] = $rowAdmin['usere'];
        $_SESSION["nama_pengaju"]        = getOne2("SELECT nama FROM pegawai WHERE nik='$usere' LIMIT 1");
        $_SESSION["hak_akses"]           = "administrator";
        $_SESSION["hapus_nota_salah"]    = true; // admin selalu boleh
        header("Location: menu_pengajuan.php");
        exit;
    } elseif ($rowUser) {
        // jika user login, tentukan hak akses dari tabel pengaturan_user_akses
        $id_user_plain = $usere; // plain text nik
        $qAkses = mysqli_query($conn_pengajuan, "SELECT hak_akses, nama_pegawai 
                                                 FROM pengaturan_user_akses 
                                                 WHERE id_user='$id_user_plain' LIMIT 1");
        if($akses = mysqli_fetch_assoc($qAkses)){
            $_SESSION["hak_akses"]    = strtolower($akses['hak_akses']); // 'pengaju' atau 'pemroses'
            $_SESSION["nama_pengaju"] = $akses['nama_pegawai'];
        } else {
            // default kalau belum diatur
            $_SESSION["hak_akses"]    = "pengaju";
            $_SESSION["nama_pengaju"] = getOne2("SELECT nama FROM pegawai WHERE nik='$usere' LIMIT 1");
        }

        $_SESSION["ses_pengajuan_login"] = $rowUser['id_user'];
        $_SESSION["hapus_nota_salah"]    = (strtolower($rowUser['hapus_nota_salah']) === 'true');
        header("Location: menu_pengajuan.php");
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
  <title>PENGAJUAN</title>
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
    <h2 class="anjungan-title">PENGAJUAN DAN STATISTIK</h2>

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

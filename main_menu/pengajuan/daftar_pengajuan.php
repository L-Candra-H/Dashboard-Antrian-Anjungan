<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/conf_pengajuan.php'; // DB pengajuan
include_once '../conf/command.php';

$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");
$conn_pengajuan = bukakoneksi_pengajuan();
$conn_main      = bukakoneksi(); // koneksi ke DB utama (reg_periksa)

// Jika ada data POST dari form_pengajuan
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['no_rawat'])) {
    $no_rawat      = mysqli_real_escape_string($conn_pengajuan, $_POST['no_rawat']);
    $no_rkm_medis  = mysqli_real_escape_string($conn_pengajuan, $_POST['no_rkm_medis']);
    $nm_pasien     = mysqli_real_escape_string($conn_pengajuan, $_POST['nm_pasien']);
    $tgl_registrasi= mysqli_real_escape_string($conn_pengajuan, $_POST['tgl_registrasi']);
    $status_lanjut = mysqli_real_escape_string($conn_pengajuan, $_POST['status_lanjut']);
    $nm_dokter     = mysqli_real_escape_string($conn_pengajuan, $_POST['nm_dokter']);
    $nm_poli       = mysqli_real_escape_string($conn_pengajuan, $_POST['nm_poli']);
    $alasan        = mysqli_real_escape_string($conn_pengajuan, $_POST['alasan']);
    $yang_mengajukan = mysqli_real_escape_string($conn_pengajuan, $_SESSION['nama_pengaju']);

    // Cari no_reg dari reg_periksa berdasarkan no_rawat
    $no_reg = '';
    $getReg = mysqli_query($conn_main, "SELECT no_reg FROM reg_periksa WHERE no_rawat='$no_rawat' LIMIT 1");
    if($getReg && mysqli_num_rows($getReg) > 0){
        $rowReg = mysqli_fetch_assoc($getReg);
        $no_reg = $rowReg['no_reg'];
    }

    // Cek apakah no_rawat sudah ada (mencegah duplikat)
    $cek = mysqli_query($conn_pengajuan, "SELECT id_pengajuan FROM pengajuan_nota_salah WHERE no_rawat='$no_rawat'");
    if(mysqli_num_rows($cek) > 0){
        $_SESSION['error_pengajuan'] = "Pengajuan untuk No Rawat $no_rawat sudah ada, tidak bisa duplikat.";
    } else {
        $sql = "INSERT INTO pengajuan_nota_salah 
                (no_reg, no_rawat, no_rkm_medis, nm_pasien, tgl_registrasi, status_lanjut, nm_dokter, nm_poli, alasan, yang_mengajukan, created_at)
                VALUES ('$no_reg','$no_rawat','$no_rkm_medis','$nm_pasien','$tgl_registrasi',
                        '$status_lanjut','$nm_dokter','$nm_poli','$alasan','$yang_mengajukan',NOW())";

        if(!mysqli_query($conn_pengajuan, $sql)){
            die("Insert error: " . mysqli_error($conn_pengajuan));
        }
        $_SESSION['success_pengajuan'] = "Pengajuan berhasil disimpan.";
    }

    header("Location: daftar_pengajuan.php");
    exit;
}

// Jika ada update tindak lanjut
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pengajuan'], $_POST['status_tindak'])) {
    if(empty($_SESSION['hapus_nota_salah']) || $_SESSION['hapus_nota_salah'] != 1) {
        $_SESSION['error_pengajuan'] = "Anda tidak memiliki hak akses untuk tindak lanjut.";
        header("Location: daftar_pengajuan.php");
        exit;
    }

    $id_pengajuan = intval($_POST['id_pengajuan']);
    $status_tindak = mysqli_real_escape_string($conn_pengajuan, $_POST['status_tindak']);
    $diproses_oleh = mysqli_real_escape_string($conn_pengajuan, $_SESSION['nama_pengaju']);

    $sql_update = "UPDATE pengajuan_nota_salah 
                   SET tindak_lanjut='$status_tindak', diproses_oleh='$diproses_oleh' 
                   WHERE id_pengajuan=$id_pengajuan";
    if(mysqli_query($conn_pengajuan, $sql_update)){
        $_SESSION['success_pengajuan'] = "Tindak lanjut berhasil diubah menjadi $status_tindak oleh $diproses_oleh.";
    } else {
        $_SESSION['error_pengajuan'] = "Gagal update: " . mysqli_error($conn_pengajuan);
    }

    header("Location: daftar_pengajuan.php");
    exit;
}

// Ambil daftar pengajuan
$query = mysqli_query($conn_pengajuan, "SELECT id_pengajuan, no_reg, no_rawat, no_rkm_medis, nm_pasien,
                                               tgl_registrasi, status_lanjut, nm_dokter, nm_poli,
                                               alasan, yang_mengajukan, tindak_lanjut, diproses_oleh, created_at
                                        FROM pengajuan_nota_salah
                                        ORDER BY created_at DESC");
if(!$query){
    die("Query error: " . mysqli_error($conn_pengajuan));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Pengajuan Hapus Nota Salah</title>
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
    <h2 class="anjungan-title">DAFTAR PENGAJUAN HAPUS NOTA SALAH</h2>

    <!-- Pesan sukses / error -->
    <?php if(isset($_SESSION['error_pengajuan'])) { ?>
      <div class="error-msg"><?= $_SESSION['error_pengajuan']; unset($_SESSION['error_pengajuan']); ?></div>
    <?php } ?>
    <?php if(isset($_SESSION['success_pengajuan'])) { ?>
      <div class="success-msg"><?= $_SESSION['success_pengajuan']; unset($_SESSION['success_pengajuan']); ?></div>
    <?php } ?>

    <!-- Scroll hanya di tabel -->
    <div class="table-container">
      <table class="tabel-pengajuan">
        <thead>
          <tr>
            <th>ID</th>
            <th>No Reg</th>
            <th>No Rawat</th>
            <th>No RM</th>
            <th>Nama Pasien</th>
            <th>Tgl Registrasi</th>
            <th>Status Lanjut</th>
            <th>Dokter</th>
            <th>Poli</th>
            <th>Alasan</th>
            <th>Yang Mengajukan</th>
            <th>Tindak Lanjut</th>
            <th>Diproses Oleh</th>
            <th>Dibuat</th>
          </tr>
        </thead>
        <tbody>
          <?php if(mysqli_num_rows($query) > 0) { ?>
            <?php while($row = mysqli_fetch_assoc($query)) { ?>
              <tr>
                <td><?= $row['id_pengajuan'] ?></td>
                <td><?= $row['no_reg'] ?: '-' ?></td>
                <td><?= $row['no_rawat'] ?></td>
                <td><?= $row['no_rkm_medis'] ?></td>
                <td><?= $row['nm_pasien'] ?></td>
                <td><?= $row['tgl_registrasi'] ? date('d-m-Y', strtotime($row['tgl_registrasi'])) : '-' ?></td>
                <td><?= $row['status_lanjut'] ?></td>
                <td><?= $row['nm_dokter'] ?: '-' ?></td>
                <td><?= $row['nm_poli'] ?: '-' ?></td>
                <td><?= htmlspecialchars($row['alasan']) ?></td>
                <td><?= $row['yang_mengajukan'] ?></td>
                <td>
                  <?= htmlspecialchars($row['tindak_lanjut']) ?>
                  <?php if(!empty($_SESSION['hapus_nota_salah']) && $_SESSION['hapus_nota_salah'] == 1) { ?>
                    <?php if($row['tindak_lanjut'] == '' || $row['tindak_lanjut'] == 'Menunggu verifikasi') { ?>
                      <form method="post" action="daftar_pengajuan.php" style="display:inline; margin-left:4px;">
                        <input type="hidden" name="id_pengajuan" value="<?= $row['id_pengajuan'] ?>">
                        <button type="submit" name="status_tindak" value="Selesai" class="btn-selesai">Selesai</button>
                      </form>
                      <form method="post" action="daftar_pengajuan.php" style="display:inline; margin-left:4px;">
                        <input type="hidden" name="id_pengajuan" value="<?= $row['id_pengajuan'] ?>">
                        <button type="submit" name="status_tindak" value="Ditolak" class="btn-ditolak">Ditolak</button>
                      </form>
                    <?php } else { ?>
                      <button disabled>Selesai</button>
                      <button disabled>Ditolak</button>
                    <?php } ?>
                  <?php } else { ?>
                    <button disabled>Selesai</button>
                    <button disabled>Ditolak</button>
                  <?php } ?>
                </td>
                <td><?= $row['diproses_oleh'] ?: '-' ?></td>
                <td><?= $row['created_at'] ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-' ?></td>
              </tr>
            <?php } ?>
          <?php } else { ?>
            <tr><td colspan="14">Belum ada pengajuan</td></tr>
          <?php } ?>
        </tbody>
      </table>
    </div><!-- tutup table-container -->

    <div class="button-group">
      <a href="form_pengajuan.php" class="btn-exit">Tambah Pengajuan</a>
      <a href="logout.php" class="btn-exit">Logout</a>
    </div>
  </main>

  <!-- Banner bawah -->
  <?php include '../assets/banner.php'; ?>

  <!-- Script jam -->
  <script src="../assets/clock.js"></script>
</body>
</html>

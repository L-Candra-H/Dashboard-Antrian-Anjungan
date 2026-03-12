<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: ../login.php");
    exit;
}

include_once '../../conf/conf.php';
$conn_sik = bukakoneksi(); // koneksi ke DB SIK Utama

// ambil data instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

// default range: bulan berjalan
$tgl_awal  = $_GET['tgl_awal'] ?? $_POST['tgl_awal'] ?? date("Y-m-01");
$tgl_akhir = $_GET['tgl_akhir'] ?? $_POST['tgl_akhir'] ?? date("Y-m-t");

// jumlah TT
$tt = mysqli_num_rows(mysqli_query($conn_sik, "SELECT kd_kamar FROM kamar WHERE statusdata='1'"));

// jumlah hari perawatan sesuai range
$qHari = mysqli_query($conn_sik, "
    SELECT SUM(lama) AS total_hari 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$hari_perawatan = mysqli_fetch_assoc($qHari)['total_hari'] ?? 0;

// jumlah pasien keluar
$qKeluar = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS keluar 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_keluar = mysqli_fetch_assoc($qKeluar)['keluar'] ?? 0;

// periode hari
$periode = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / 86400 + 1;

// hitung TOI
$TOI = $pasien_keluar > 0 ? ((($tt * $periode) - $hari_perawatan) / $pasien_keluar) : 0;

// pagination
$limit = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// query data pasien keluar dengan JOIN ke bangsal
$qData = mysqli_query($conn_sik, "
    SELECT ki.no_rawat, b.nm_bangsal, ki.tgl_masuk, ki.tgl_keluar, ki.lama, ki.stts_pulang
    FROM kamar_inap ki
    JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
    LIMIT $limit OFFSET $offset
");

$totalRows = mysqli_num_rows(mysqli_query($conn_sik, "
    SELECT ki.no_rawat
    FROM kamar_inap ki
    WHERE ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
"));
$totalPages = ceil($totalRows / $limit);

// kesimpulan TOI (standar ideal 1–3 hari)
if($TOI == 0){
    $kesimpulan = "Tidak ada pasien keluar pada periode ini.";
} elseif($TOI < 1){
    $kesimpulan = "TOI terlalu rendah (<1 hari). Tempat tidur langsung terisi kembali.";
} elseif($TOI > 3){
    $kesimpulan = "TOI tinggi (>3 hari). Tempat tidur terlalu lama kosong, perlu evaluasi.";
} else {
    $kesimpulan = "TOI berada dalam rentang ideal (1–3 hari). Pemanfaatan tempat tidur optimal.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>TOI</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="statistik.css"> <!-- gunakan css statistik -->
</head>
<body class="pengajuan">
  <header class="header">
    <div class="logo"><?php include '../../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
      <p><?= $setting['kontak'] ?> | <?= $setting['email'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard">
    <h2 class="anjungan-title">Indikator TOI (Turn Over Interval)</h2>

    <!-- Tombol kembali -->
    <div class="button-group top-back">
      <a href="../menu_pengajuan.php" class="btn-back">Kembali</a>
    </div>

    <!-- Form filter range -->
    <form method="get" class="filter-form form-inline">
      <label>Dari tanggal:</label>
      <input type="date" name="tgl_awal" value="<?= date('Y-m-d', strtotime($tgl_awal)) ?>">
      <label>Sampai tanggal:</label>
      <input type="date" name="tgl_akhir" value="<?= date('Y-m-d', strtotime($tgl_akhir)) ?>">
      <button type="submit" class="btn-modern">Tampilkan</button>
    </form>

    <!-- Hasil perhitungan + kesimpulan sejajar -->
    <div class="stat-box small">
      <div class="stat-item"><strong>TOI</strong><br><?= number_format($TOI,2) ?> hari</div>
      <div class="stat-item"><strong>Jumlah TT</strong><br><?= $tt ?></div>
      <div class="stat-item"><strong>Hari Perawatan</strong><br><?= $hari_perawatan ?></div>
      <div class="stat-item"><strong>Pasien Keluar</strong><br><?= $pasien_keluar ?></div>
      <div class="stat-item"><strong>Periode</strong><br><?= $periode ?> hari</div>
      <div class="stat-item kesimpulan"><strong>Kesimpulan: </strong><?= $kesimpulan ?></div>
    </div>

    <!-- Tabel pasien keluar -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr>
          <th>No Rawat</th><th>Bangsal</th><th>Tgl Masuk</th><th>Tgl Keluar</th>
          <th>Lama</th><th>Status Pulang</th>
        </tr>
        <?php
        while($d = mysqli_fetch_assoc($qData)){
            echo "<tr>
                    <td>{$d['no_rawat']}</td>
                    <td>{$d['nm_bangsal']}</td>
                    <td>".date('d-m-Y', strtotime($d['tgl_masuk']))."</td>
                    <td>".date('d-m-Y', strtotime($d['tgl_keluar']))."</td>
                    <td>{$d['lama']}</td>
                    <td>{$d['stts_pulang']}</td>
                  </tr>";
        }
        ?>
      </table>
    </div>

    <!-- Pagination Prev/Next -->
    <div class="pagination">
      <?php if($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">Prev</a>
      <?php endif; ?>

      <?php if($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">Next</a>
      <?php endif; ?>
    </div>
  </main>

  <?php include '../../assets/banner.php'; ?>
  <script src="../../assets/clock.js"></script>
</body>
</html>

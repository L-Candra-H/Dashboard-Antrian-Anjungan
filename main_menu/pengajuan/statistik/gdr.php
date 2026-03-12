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

// jumlah pasien keluar
$qKeluar = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS keluar 
    FROM kamar_inap 
    WHERE tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_keluar = mysqli_fetch_assoc($qKeluar)['keluar'] ?? 0;

// jumlah pasien mati sesuai range
$qMati = mysqli_query($conn_sik, "
    SELECT COUNT(*) AS mati 
    FROM pasien_mati 
    WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pasien_mati = mysqli_fetch_assoc($qMati)['mati'] ?? 0;

// hitung GDR
$GDR = $pasien_keluar > 0 ? (($pasien_mati / $pasien_keluar) * 1000) : 0;

// pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// query data pasien mati
$qData = mysqli_query($conn_sik, "
    SELECT pm.tanggal, pm.jam, pm.no_rkm_medis, p.nm_pasien,
           pm.keterangan, pm.temp_meninggal, d.nm_dokter
    FROM pasien_mati pm
    LEFT JOIN pasien p ON pm.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN dokter d ON pm.kd_dokter = d.kd_dokter
    WHERE pm.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
    LIMIT $limit OFFSET $offset
");

$totalRows = mysqli_num_rows(mysqli_query($conn_sik, "
    SELECT pm.no_rkm_medis
    FROM pasien_mati pm
    WHERE pm.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
"));
$totalPages = ceil($totalRows / $limit);

// kesimpulan GDR (ideal < 45 ‰)
if($GDR == 0){
    $kesimpulan = "Tidak ada pasien mati pada periode ini.";
} elseif($GDR < 45){
    $kesimpulan = "GDR rendah (<45 ‰). Angka kematian umum masih terkendali.";
} else {
    $kesimpulan = "GDR tinggi (≥45 ‰). Angka kematian umum perlu evaluasi.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>GDR</title>
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
    <h2 class="anjungan-title">Indikator GDR (Gross Death Rate)</h2>

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
      <div class="stat-item"><strong>GDR</strong><br><?= number_format($GDR,2) ?> ‰</div>
      <div class="stat-item"><strong>Pasien Keluar</strong><br><?= $pasien_keluar ?></div>
      <div class="stat-item"><strong>Pasien Mati</strong><br><?= $pasien_mati ?></div>
      <div class="stat-item kesimpulan"><strong>Kesimpulan: </strong><?= $kesimpulan ?></div>
    </div>

    <!-- Tabel pasien mati -->
    <div class="table-container-stat">
      <table class="tabel-pengajuan">
        <tr>
          <th>Tanggal</th><th>Jam</th><th>No RM</th><th>Nama Pasien</th>
          <th>Keterangan</th><th>Tempat Meninggal</th><th>Dokter</th>
        </tr>
        <?php
        while($d = mysqli_fetch_assoc($qData)){
            echo "<tr>
                    <td>".date('d-m-Y', strtotime($d['tanggal']))."</td>
                    <td>{$d['jam']}</td>
                    <td>{$d['no_rkm_medis']}</td>
                    <td>{$d['nm_pasien']}</td>
                    <td>{$d['keterangan']}</td>
                    <td>{$d['temp_meninggal']}</td>
                    <td>{$d['nm_dokter']}</td>
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

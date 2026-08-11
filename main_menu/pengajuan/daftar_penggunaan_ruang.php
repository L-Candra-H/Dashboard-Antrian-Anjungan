<?php
session_start();
if(!isset($_SESSION["ses_pengajuan_login"])) {
    header("Location: login.php");
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

// ambil role dari session
$hakAkses   = $_SESSION['hak_akses'] ?? '';
$isAdmin    = ($hakAkses === 'administrator');
$isPemroses = ($hakAkses === 'pemroses');

// Ambil daftar ruang
$ruang_query = mysqli_query($conn_pengajuan, "SELECT id_ruang, nama_ruang FROM pengaturan_ruang ORDER BY nama_ruang ASC");

// Proses tambah pengajuan
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_kegiatan'])){
    $ruang         = mysqli_real_escape_string($conn_pengajuan, $_POST['ruang']);
    $nama_kegiatan = mysqli_real_escape_string($conn_pengajuan, $_POST['nama_kegiatan']);
    $tgl_mulai     = mysqli_real_escape_string($conn_pengajuan, $_POST['tgl_mulai']);
    $jam_mulai     = mysqli_real_escape_string($conn_pengajuan, $_POST['jam_mulai']);
    $tgl_selesai   = mysqli_real_escape_string($conn_pengajuan, $_POST['tgl_selesai']);
    $jam_selesai   = mysqli_real_escape_string($conn_pengajuan, $_POST['jam_selesai']);
    $pengaju       = $_SESSION['nama_pengaju'];
    $jam_diajukan  = date("Y-m-d H:i:s");

    $sql = "INSERT INTO pengajuan_penggunaan_ruang 
            (ruang, nama_kegiatan, tgl_mulai, jam_mulai, tgl_selesai, jam_selesai, pengaju, created_at, tindak_lanjut, alasan_tolak, diproses_oleh) 
            VALUES 
            ('$ruang','$nama_kegiatan','$tgl_mulai','$jam_mulai','$tgl_selesai','$jam_selesai','$pengaju','$jam_diajukan','Pengajuan','','')";
    mysqli_query($conn_pengajuan, $sql) or die(mysqli_error($conn_pengajuan));

    header("Location: daftar_penggunaan_ruang.php");
    exit;
}

// Proses tindak lanjut (Setuju/Tolak)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])){
    if($isAdmin || $isPemroses){
        $id   = mysqli_real_escape_string($conn_pengajuan, $_POST['id_pengajuan']);
        $aksi = $_POST['aksi'];
        $oleh = $_SESSION['nama_pengaju'];
        $jam  = date("Y-m-d H:i:s");

        if($aksi === 'Setuju'){
            $sql = "UPDATE pengajuan_penggunaan_ruang 
                    SET tindak_lanjut='Setuju', diproses_oleh='$oleh', jam_proses='$jam' 
                    WHERE id_pengajuan='$id'";
        } elseif($aksi === 'Tolak'){
            $alasan = mysqli_real_escape_string($conn_pengajuan, $_POST['alasan_tolak']);
            $sql = "UPDATE pengajuan_penggunaan_ruang 
                    SET tindak_lanjut='Tolak', alasan_tolak='$alasan', diproses_oleh='$oleh', jam_proses='$jam', status_dashboard='NONAKTIF' 
                    WHERE id_pengajuan='$id'";
        }
        mysqli_query($conn_pengajuan, $sql) or die(mysqli_error($conn_pengajuan));
        header("Location: daftar_penggunaan_ruang.php");
        exit;
    }
}

// Proses toggle status dashboard
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])){
    $id     = mysqli_real_escape_string($conn_pengajuan, $_POST['id_pengajuan']);
    $status = mysqli_real_escape_string($conn_pengajuan, $_POST['toggle_status']);

    // cek tindak lanjut dulu
    $cek = mysqli_query($conn_pengajuan, "SELECT tindak_lanjut FROM pengajuan_penggunaan_ruang WHERE id_pengajuan='$id'");
    $row = mysqli_fetch_assoc($cek);

    if($row['tindak_lanjut'] !== 'Tolak'){
        $sql = "UPDATE pengajuan_penggunaan_ruang SET status_dashboard='$status' WHERE id_pengajuan='$id'";
    } else {
        // paksa tetap NONAKTIF
        $sql = "UPDATE pengajuan_penggunaan_ruang SET status_dashboard='NONAKTIF' WHERE id_pengajuan='$id'";
    }
    mysqli_query($conn_pengajuan, $sql) or die(mysqli_error($conn_pengajuan));
    header("Location: daftar_penggunaan_ruang.php");
    exit;
}

// Ambil daftar pengajuan
$pengajuan_query = mysqli_query($conn_pengajuan, "SELECT * FROM pengajuan_penggunaan_ruang ORDER BY id_pengajuan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Penggunaan Ruang</title>
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
    <h2 id="judul-halaman" class="anjungan-title">DAFTAR PENGGUNAAN RUANG</h2>

    <div id="table-container" class="table-container">
      <table class="tabel-pengajuan">
        <tr>
          <th>ID</th><th>Ruang</th><th>Nama Kegiatan</th><th>Tanggal Mulai</th>
          <th>Jam Mulai</th><th>Tanggal Selesai</th><th>Jam Selesai</th>
          <th>Pengaju</th><th>Tindak Lanjut</th><th>Alasan Tolak</th>
          <th>Diproses Oleh</th><th>Jam Diajukan</th><th>Jam Proses</th>
          <th>Akses Dashboard</th>
        </tr>
        <?php while($p = mysqli_fetch_assoc($pengajuan_query)){  
          $status = $p['tindak_lanjut'] ?: 'Pengajuan'; ?>
        <tr>
          <td><?= $p['id_pengajuan'] ?></td>
          <td><?= htmlspecialchars($p['ruang']) ?></td>
          <td><?= htmlspecialchars($p['nama_kegiatan']) ?></td>
          <td><?= date("d-m-Y", strtotime($p['tgl_mulai'])) ?></td>
          <td><?= $p['jam_mulai'] ?></td>
          <td><?= date("d-m-Y", strtotime($p['tgl_selesai'])) ?></td>
          <td><?= $p['jam_selesai'] ?></td>
          <td><?= htmlspecialchars($p['pengaju']) ?></td>
          <td>
            <?php if($status === 'Pengajuan'){ ?>
              <?php if($isAdmin || $isPemroses){ ?>
                <?= htmlspecialchars($status) ?>
                <form method="post" style="display:inline; margin-left:4px;">
                  <input type="hidden" name="id_pengajuan" value="<?= $p['id_pengajuan'] ?>">
                  <button type="submit" name="aksi" value="Setuju" class="btn-selesai">Setuju</button>
                </form>
                <button type="button" onclick="document.getElementById('alasan-<?= $p['id_pengajuan'] ?>').style.display='block';">Tolak</button>
              <?php } else { ?>
                <?= htmlspecialchars($status) ?>
                <span style="color:gray; font-size:0.9em;">(Menunggu diproses)</span>
              <?php } ?>
            <?php } else { ?>
              <?= htmlspecialchars($status) ?>
            <?php } ?>
          </td>
          <td>
            <?= htmlspecialchars($p['alasan_tolak']) ?>
            <?php if($status === 'Pengajuan' && ($isAdmin || $isPemroses)){ ?>
              <div id="alasan-<?= $p['id_pengajuan'] ?>" style="display:none; margin-top:8px;">
                <form method="post" style="display:flex; flex-direction:column; gap:6px; align-items:flex-start;">
                  <input type="hidden" name="id_pengajuan" value="<?= $p['id_pengajuan'] ?>">
                  <input type="text" name="alasan_tolak" placeholder="Alasan Tolak" required style="width:100%;">
                  <button type="submit" name="aksi" value="Tolak" class="btn-ditolak">Simpan</button>
                </form>
              </div>
            <?php } ?>
          </td>
          <td><?= htmlspecialchars($p['diproses_oleh']) ?></td>
          <td><?= date("H:i:s", strtotime($p['created_at'])) ?></td>
          <td><?= !empty($p['jam_proses']) ? date("H:i:s", strtotime($p['jam_proses'])) : '-' ?></td>
          <td>
            <?php if($p['tindak_lanjut'] === 'Tolak'){ ?>
                <!-- Jika sudah ditolak, tampilkan teks NONAKTIF tanpa tombol -->
                <span class="btn-exit">NONAKTIF</span>
            <?php } else { ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="id_pengajuan" value="<?= $p['id_pengajuan'] ?>">
                  <?php if($p['status_dashboard'] === 'AKTIF'){ ?>
                      <button type="submit" name="toggle_status" value="NONAKTIF" class="btn-exit">NONAKTIF</button>
                  <?php } else { ?>
                      <button type="submit" name="toggle_status" value="AKTIF" class="btn-modern">AKTIF</button>
                  <?php } ?>
                </form>
            <?php } ?>
          </td>
        </tr>
        <?php } ?>
      </table>
    </div>

    <div id="button-container" class="button-group">
      <?php if($isAdmin){ ?>
        <a href="pengaturan_ruang.php" class="btn-exit">Pengaturan Ruang</a>
        <a href="pengaturan_user_akses.php" class="btn-exit">Pengaturan Hak Akses</a>
      <?php } ?>
      <a href="#" class="btn-exit" onclick="showFormPengajuan(); return false;">Tambah Pengajuan</a>
      <a href="menu_pengajuan.php" class="btn-exit">Kembali</a>
    </div>

    <!-- Form pengajuan -->
    <div id="form-pengajuan" style="display:none;">
      <form method="post" action="" class="pengajuan-form">
        <div class="field-row">
          <label>Ruang :</label>
          <select name="ruang" required>
            <option value="">-- Pilih Ruang --</option>
            <?php
            if(mysqli_num_rows($ruang_query) > 0){
                while($r = mysqli_fetch_assoc($ruang_query)){
                    echo "<option value='".htmlspecialchars($r['nama_ruang'])."'>".htmlspecialchars($r['nama_ruang'])."</option>";
                }
            }
            ?>
          </select>
        </div>

        <table class="tabel-pasien">
          <tr>
            <td class="label">Nama Kegiatan</td>
            <td class="value"><input type="text" name="nama_kegiatan" required></td>
          </tr>
          <tr>
            <td class="label">Mulai</td>
            <td class="value">
              <input type="date" name="tgl_mulai" required>
              <input type="time" name="jam_mulai" required>
            </td>
          </tr>
          <tr>
            <td class="label">Selesai</td>
            <td class="value">
              <input type="date" name="tgl_selesai" required>
              <input type="time" name="jam_selesai" required>
            </td>
          </tr>
        </table>

        <div class="pengaju-row">
          <span>Yang mengajukan : <strong><?= $_SESSION['nama_pengaju'] ?></strong></span>
          <div class="button-group">
            <button type="submit" class="btn-modern">Simpan</button>
            <button type="button" onclick="hideFormPengajuan();" class="btn-exit">Keluar</button>
          </div>
        </div>
      </form>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>
  <script src="../assets/clock.js"></script>

  <script>
  function showFormPengajuan(){
    document.getElementById('form-pengajuan').style.display = 'block';
    document.getElementById('table-container').style.display = 'none';
    document.getElementById('button-container').style.display = 'none';
    document.getElementById('judul-halaman').innerText = 'PENGAJUAN PENGGUNAAN RUANG';
  }
  function hideFormPengajuan(){
    document.getElementById('form-pengajuan').style.display = 'none';
    document.getElementById('table-container').style.display = 'block';
    document.getElementById('button-container').style.display = 'flex';
    document.getElementById('judul-halaman').innerText = 'DAFTAR PENGGUNAAN RUANG';
  }
  </script>
</body>
</html>

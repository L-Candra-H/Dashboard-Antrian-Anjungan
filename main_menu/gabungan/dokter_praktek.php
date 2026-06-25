<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();

include_once __DIR__ . '/../conf/conf.php';
include_once __DIR__ . '/../conf/helpers.php';

// Ambil setting instansi
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

function mask_name($name) {
    $parts = preg_split('/\s+/', trim($name));
    $masked = [];
    foreach ($parts as $word) {
        $len = mb_strlen($word);
        if ($len <= 1) { $masked[] = '*'; continue; }
        if ($len == 2) { $masked[] = mb_substr($word,0,1) . '*'; continue; }
        if ($len <= 4) { $masked[] = mb_substr($word,0,1) . str_repeat('*', $len-1); continue; }
        $masked[] = mb_substr($word,0,2) . str_repeat('*', min(3,$len-2));
    }
    return implode(' ', $masked);
}

$kd = isset($_GET['kd']) ? validTeks4($_GET['kd'],20) : '';
if (empty($kd)) { header('Location: praktek_index.php'); exit; }

$hari_ini = date('l');
$mapHari = ['Sunday'=>'AKHAD','Monday'=>'SENIN','Tuesday'=>'SELASA','Wednesday'=>'RABU','Thursday'=>'KAMIS','Friday'=>'JUMAT','Saturday'=>'SABTU'];
$hari = $mapHari[$hari_ini] ?? strtoupper($hari_ini);

$sql_doc = "SELECT d.kd_dokter,d.nm_dokter,pg.photo FROM dokter d LEFT JOIN pegawai pg ON d.kd_dokter=pg.nik WHERE d.kd_dokter='".$kd."' LIMIT 1";
$doc = fetch_assoc($sql_doc);

$sql_jad = "SELECT p.nm_poli,j.jam_mulai,j.jam_selesai FROM jadwal j JOIN poliklinik p ON j.kd_poli=p.kd_poli WHERE j.kd_dokter='".$kd."' AND j.hari_kerja='$hari' ORDER BY p.nm_poli,j.jam_mulai";
$res_jad = bukaquery($sql_jad);
$jadwals = [];
while ($r = mysqli_fetch_assoc($res_jad)) $jadwals[] = $r;

$sql_pas = "SELECT r.no_reg,p.nm_pasien,r.stts FROM reg_periksa r JOIN pasien p ON r.no_rkm_medis=p.no_rkm_medis WHERE r.kd_dokter='".$kd."' AND r.tgl_registrasi = CURRENT_DATE() ORDER BY CAST(r.no_reg AS SIGNED), r.no_reg";
$res_pas = bukaquery($sql_pas);
$pasiens = [];
while ($r = mysqli_fetch_assoc($res_pas)) $pasiens[] = $r;

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard <?= htmlspecialchars($doc['nm_dokter'] ?? 'Dokter') ?></title>
  <link rel="stylesheet" href="praktek.css">
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <header class="header">
    <div class="logo"><?php include __DIR__ . '/../assets/logo.php'; ?></div>
    <div class="instansi">
      <h1><?= $setting['nama_instansi'] ?></h1>
      <p><?= $setting['alamat_instansi'] ?> – <?= $setting['kabupaten'] ?></p>
      <p><?= $setting['kontak'] ?> | <?= $setting['email'] ?></p>
    </div>
    <div id="clock"></div>
    <div id="next-prayer"></div>
  </header>

  <main class="dashboard dual-grid">
    <div class="panel">
      <h2><?= htmlspecialchars($doc['nm_dokter'] ?? '-') ?></h2>
      <div class="panel-content">
        <div class="foto">
          <?php if (!empty(trim($doc['photo']))): ?>
            <img src="<?= '/'.basename(dirname(dirname(__DIR__))).'/webapps/penggajian/'.trim($doc['photo']) ?>" alt="<?= htmlspecialchars($doc['nm_dokter']) ?>">
          <?php else: ?>
            <div class="placeholder">No Photo</div>
          <?php endif; ?>
        </div>
        <h3>Jadwal Praktek (<?= $hari ?>)</h3>
        <div class="tbody-container <?= (count($jadwals) > 7 ? 'scrollable' : '') ?>">
          <table class="data-table">
            <thead>
              <tr><th>Poli</th><th>Mulai</th><th>Selesai</th></tr>
            </thead>
            <tbody>
              <?php if (empty($jadwals)): ?>
                <tr><td colspan="3">-</td></tr>
              <?php else: foreach ($jadwals as $j): ?>
                <tr><td><?= htmlspecialchars($j['nm_poli']) ?></td><td><?= $j['jam_mulai'] ?></td><td><?= $j['jam_selesai'] ?></td></tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Daftar Pasien Hari Ini</h2>
      <div class="tbody-container <?= (count($pasiens) > 7 ? 'scrollable' : '') ?>">
        <table class="data-table">
          <thead><tr><th>No. Reg</th><th>Nama Pasien</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($pasiens)): ?>
              <tr><td colspan="3">Belum ada pasien terdaftar hari ini.</td></tr>
            <?php else: foreach ($pasiens as $ps): ?>
              <tr>
                <td><?= htmlspecialchars($ps['no_reg']) ?></td>
                <td><?= htmlspecialchars(mask_name($ps['nm_pasien'])) ?></td>
                <td><?= htmlspecialchars($ps['stts'] ?? '-') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php include '../assets/banner.php'; ?>

  <script src="../assets/clock.js"></script>
  <script>
    setTimeout(function(){ location.reload(); }, 60000);
    document.querySelectorAll('.tbody-container.scrollable').forEach(container => {
      let direction = 1;
      function autoScroll() {
        container.scrollTop += direction;
        if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
          direction = -1;
        } else if (container.scrollTop <= 0) {
          direction = 1;
        }
      }
      setInterval(autoScroll, 50);
    });
  </script>
</body>
</html>

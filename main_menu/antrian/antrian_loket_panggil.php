<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../conf/conf.php';
include_once '../conf/helpers.php';

// --- Handle AJAX panggil nomor ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomor'])) {
    $nomor = $_POST['nomor'];
    $sql = "UPDATE antriloketcetak 
            SET status='selesai' 
            WHERE nomor='$nomor' AND tanggal=CURDATE()";
    bukaquery($sql);
    echo json_encode(['status'=>'ok','nomor'=>$nomor]);
    exit;
}

// --- Handle AJAX reload tabel ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reload'])) {
    $sql = "SELECT tanggal, nomor, jam, status,
                   TIMESTAMPDIFF(MINUTE, CONCAT(tanggal,' ',jam), NOW()) AS waktu_tunggu
            FROM antriloketcetak
            WHERE tanggal = CURDATE()
            ORDER BY jam DESC";
    $result = bukaquery($sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $waktu = max(0, $row['waktu_tunggu']) . " menit";
        echo "<tr>
                <td>".str_pad($row['nomor'],3,'0',STR_PAD_LEFT)."</td>
                <td>{$row['jam']}</td>
                <td>{$waktu}</td>
                <td>".strtoupper($row['status'])."</td>
                <td>";
        if ($row['status']=='menunggu') {
            echo "<button class='btn-panggil' onclick=\"panggilNomor('{$row['nomor']}')\">PANGGIL</button>";
        } else {
            echo "<button class='btn-panggil' disabled>Selesai</button>";
        }
        echo "</td></tr>";
    }
    exit;
}

// --- Ambil setting instansi ---
$setting = fetch_assoc("SELECT nama_instansi, alamat_instansi, kabupaten, kontak, email FROM setting LIMIT 1");

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Panggil Antrian Loket</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="antrian.css">
</head>
<body>
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
    <div class="grid-container">
      <!-- Panel kiri: daftar antrian -->
      <div class="left-panel">
        <h2>DAFTAR PANGGIL ANTRIAN LOKET HARI INI</h2>
        <table class="antrian-loket">
          <thead>
            <tr>
              <th>Nomor</th>
              <th>Jam</th>
              <th>Waktu Tunggu</th>
              <th>Status</th>
              <th>Panggil</th>
            </tr>
          </thead>
          <tbody id="daftarAntrian">
            <!-- isi tabel akan di-load via AJAX -->
          </tbody>
        </table>
      </div>

      <!-- Panel kanan: antrian yang dipanggil -->
      <div class="right-panel">
        <div class="loket-card small">
          <div class="loket-title">ANTRIAN YANG DIPANGGIL</div>
          <div class="loket-number" id="nomorDipanggil">-</div>
          <div class="loket-footer">
            <button class="btn-ulang" onclick="panggilUlang()">PANGGIL ULANG</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="../assets/clock.js"></script>
  <script>
    async function reloadTabel() {
      try {
        const res = await fetch('antrian_loket_panggil.php?reload=1');
        const html = await res.text();
        document.getElementById('daftarAntrian').innerHTML = html;
      } catch (err) {
        console.error('Gagal reload tabel:', err);
      }
    }

    async function panggilNomor(nomor) {
      document.getElementById('nomorDipanggil').textContent = nomor;
      panggilSuara(nomor);

      try {
        await fetch('antrian_loket_panggil.php', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: 'nomor='+encodeURIComponent(nomor)
        });
        reloadTabel(); // reload tabel agar tombol berubah jadi "Selesai"
      } catch (err) {
        console.error('Gagal update status:', err);
      }
    }

    async function panggilUlang() {
      const nomor = document.getElementById('nomorDipanggil').textContent;
      if (nomor && nomor !== '-') {
        panggilSuara(nomor);
      }
    }

    function panggilSuara(nomor) {
      const kalimat = "Nomor antrian " + nomor + " silahkan menuju ke Loket Pendaftaran";
      const utterance = new SpeechSynthesisUtterance(kalimat);
      utterance.lang = 'id-ID';
      utterance.rate = 1;
      utterance.pitch = 1;
      speechSynthesis.speak(utterance);
    }

    // pertama kali load tabel
    reloadTabel();
    // reload tabel tiap 5 detik
    setInterval(reloadTabel, 5000);
  </script>
</body>
</html>

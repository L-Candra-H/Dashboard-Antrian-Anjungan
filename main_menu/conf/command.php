<?php
// Membersihkan input teks dengan panjang maksimal
if (!function_exists('validTeks4')) {
    function validTeks4($str, $length){
        $str = substr(trim($str),0,$length);
        $conn = bukakoneksi(); // koneksi SIMRS dari conf.php
        return mysqli_real_escape_string($conn,$str);
    }
}

// Eksekusi query dan ambil satu nilai (satu kolom pertama)
function getOne2($sql){
    $hasil = bukaquery($sql); // bukaquery sudah ada di conf.php
    if($row = mysqli_fetch_array($hasil)){
        return $row[0];
    } else {
        return "";
    }
}
?>

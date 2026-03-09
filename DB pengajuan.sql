CREATE DATABASE pengajuan;
USE pengajuan;

CREATE TABLE pengajuan_nota_salah (
    id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,
    no_reg VARCHAR(20) NOT NULL,
    no_rawat VARCHAR(20) NOT NULL UNIQUE,   -- unik per kunjungan
    no_rkm_medis VARCHAR(20) NOT NULL,
    nm_pasien VARCHAR(100) NOT NULL,
    tgl_registrasi DATE NOT NULL,
    status_lanjut VARCHAR(20) NOT NULL,
    nm_dokter VARCHAR(100) NOT NULL,
    nm_poli VARCHAR(100) NOT NULL,
    alasan TEXT NOT NULL,
    yang_mengajukan VARCHAR(100) NOT NULL,   -- nama pegawai dari session login
    tindak_lanjut TEXT NOT NULL DEFAULT 'Menunggu verifikasi', -- default agar tidak kosong
    diproses_oleh VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

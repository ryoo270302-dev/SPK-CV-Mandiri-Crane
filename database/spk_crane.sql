-- =====================================================================
-- SPK Crane - AHP & TOPSIS
-- Skema basis data untuk CV Mandiri Crane
-- Import file ini melalui phpMyAdmin (XAMPP) atau:
--   mysql -u root -p < spk_crane.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS spk_crane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spk_crane;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Akun default. Hash dibuat dengan password_hash(..., PASSWORD_DEFAULT):
-- Username: admin  | Password: admin123
-- Username: viewer | Password: viewer123
INSERT INTO users (username, password_hash, nama, role) VALUES
('admin', '$2y$10$KM3TVpvf/CmJTWDGWbgE4elvVLaR84uiMh5BpUVCxZUsm4pTFn76.', 'Administrator', 'admin'),
('viewer', '$2y$10$4oiwNaE1U8ycJqYSN9UR4uSTeenJWbCw55tKUyniiujQEG/6O.bRO', 'Pengambil Keputusan', 'viewer');
-- Setelah instalasi, SEGERA login dan ganti password melalui menu
-- "Ubah Password" di pojok kanan atas aplikasi.

-- ---------------------------------------------------------------------
-- cranes (alternatif)
-- ---------------------------------------------------------------------
CREATE TABLE cranes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jenis VARCHAR(50) DEFAULT NULL,
    kapasitas_ton DECIMAL(10,2) DEFAULT NULL,
    tahun INT DEFAULT NULL,
    harga_sewa DECIMAL(15,2) DEFAULT NULL,
    jangkauan_m DECIMAL(10,2) DEFAULT NULL,
    keterangan TEXT,
    status ENUM('tersedia','tidak_tersedia') NOT NULL DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO cranes (kode, nama, jenis, kapasitas_ton, tahun, harga_sewa, jangkauan_m, keterangan, status) VALUES
('CR-01', 'Crane Kobelco 50T', 'Crawler Crane', 50, 2018, 3500000, 32, 'Kondisi baik, servis rutin', 'tersedia'),
('CR-02', 'Crane Tadano 25T', 'Truck Crane', 25, 2020, 2500000, 28, 'Unit relatif baru', 'tersedia'),
('CR-03', 'Crane XCMG 80T', 'Crawler Crane', 80, 2016, 5000000, 40, 'Kapasitas besar, mobilitas rendah', 'tersedia'),
('CR-04', 'Crane Zoomlion 35T', 'Truck Crane', 35, 2019, 2800000, 30, 'Perawatan baik', 'tersedia'),
('CR-05', 'Crane Kato 20T', 'Rough Terrain Crane', 20, 2021, 2200000, 24, 'Mudah dipindahkan, unit baru', 'tersedia');

-- ---------------------------------------------------------------------
-- criteria
-- ---------------------------------------------------------------------
CREATE TABLE criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('benefit','cost') NOT NULL,
    satuan VARCHAR(30) DEFAULT NULL,
    keterangan TEXT,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    urutan INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO criteria (kode, nama, tipe, satuan, keterangan, aktif, urutan) VALUES
('C1', 'Kapasitas angkat', 'benefit', 'ton', 'Kemampuan angkat maksimum crane', 1, 1),
('C2', 'Biaya sewa', 'cost', 'Rp/hari', 'Biaya sewa per satuan waktu', 1, 2),
('C3', 'Jangkauan kerja', 'benefit', 'meter', 'Panjang jangkauan kerja (boom)', 1, 3),
('C4', 'Mobilitas', 'benefit', 'skor 1-5', 'Kemudahan pemindahan unit antar lokasi', 1, 4),
('C5', 'Ketersediaan unit', 'benefit', 'skor 1-5', 'Ketersediaan unit saat dibutuhkan', 1, 5);

-- ---------------------------------------------------------------------
-- scores  (penilaian setiap crane terhadap setiap kriteria, skala 1-5)
-- ---------------------------------------------------------------------
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crane_id INT NOT NULL,
    criterion_id INT NOT NULL,
    nilai DECIMAL(10,4) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_crane_criterion (crane_id, criterion_id),
    FOREIGN KEY (crane_id) REFERENCES cranes(id) ON DELETE CASCADE,
    FOREIGN KEY (criterion_id) REFERENCES criteria(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- calc_sessions  (satu sesi = satu kali proses perhitungan AHP+TOPSIS)
-- ---------------------------------------------------------------------
CREATE TABLE calc_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_sesi VARCHAR(150) NOT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft','selesai') NOT NULL DEFAULT 'draft',
    created_by INT DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- pairwise_matrix (input perbandingan berpasangan antar kriteria - AHP)
-- ---------------------------------------------------------------------
CREATE TABLE pairwise_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    criterion_row INT NOT NULL,
    criterion_col INT NOT NULL,
    nilai DECIMAL(10,4) NOT NULL,
    FOREIGN KEY (session_id) REFERENCES calc_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (criterion_row) REFERENCES criteria(id) ON DELETE CASCADE,
    FOREIGN KEY (criterion_col) REFERENCES criteria(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ahp_results (bobot kriteria hasil AHP per sesi)
-- ---------------------------------------------------------------------
CREATE TABLE ahp_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    criterion_id INT NOT NULL,
    bobot DECIMAL(10,4) NOT NULL,
    lambda_max DECIMAL(10,4) NOT NULL,
    ci DECIMAL(10,4) NOT NULL,
    cr DECIMAL(10,4) NOT NULL,
    UNIQUE KEY uq_session_criterion (session_id, criterion_id),
    FOREIGN KEY (session_id) REFERENCES calc_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (criterion_id) REFERENCES criteria(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- topsis_results (nilai preferensi & peringkat per sesi)
-- ---------------------------------------------------------------------
CREATE TABLE topsis_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    crane_id INT NOT NULL,
    d_plus DECIMAL(10,4) NOT NULL,
    d_minus DECIMAL(10,4) NOT NULL,
    preferensi DECIMAL(10,4) NOT NULL,
    peringkat INT NOT NULL,
    UNIQUE KEY uq_session_crane (session_id, crane_id),
    FOREIGN KEY (session_id) REFERENCES calc_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (crane_id) REFERENCES cranes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ahp_ranking (peringkat alternatif menurut metode AHP murni, per sesi)
-- skor AHP alternatif = jumlah (bobot kriteria x nilai skor alternatif
-- ternormalisasi pada kriteria tsb)
-- ---------------------------------------------------------------------
CREATE TABLE ahp_ranking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    crane_id INT NOT NULL,
    skor DECIMAL(10,4) NOT NULL,
    peringkat INT NOT NULL,
    UNIQUE KEY uq_session_crane_ahp (session_id, crane_id),
    FOREIGN KEY (session_id) REFERENCES calc_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (crane_id) REFERENCES cranes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- comparisons (perbandingan peringkat AHP vs TOPSIS per sesi)
-- ---------------------------------------------------------------------
CREATE TABLE comparisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    crane_id INT NOT NULL,
    peringkat_ahp INT NOT NULL,
    peringkat_topsis INT NOT NULL,
    selisih INT NOT NULL,
    UNIQUE KEY uq_session_crane_cmp (session_id, crane_id),
    FOREIGN KEY (session_id) REFERENCES calc_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (crane_id) REFERENCES cranes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

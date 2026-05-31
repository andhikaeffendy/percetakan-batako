-- ============================================================
-- deploy_all.sql — TABEL + DATA untuk InfinityFree
-- ============================================================
-- Cara import:
--   1. Buka phpMyAdmin → pilih database if0_42059089_batako_maros
--   2. Klik tab SQL → Choose File → pilih file ini → Go
-- ============================================================

-- ========================================
-- BAGIAN 1: BUAT TABEL (7 tabel)
-- ========================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('pemilik','operator') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pekerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pekerja VARCHAR(100) NOT NULL,
    tarif_per_sak DECIMAL(10,0) DEFAULT 65000,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bahan_baku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_penggunaan DATE NOT NULL,
    jenis_bahan ENUM('Semen','Pasir') NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    satuan ENUM('Sak','m3') NOT NULL,
    keterangan TEXT,
    operator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS produksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_produksi DATE NOT NULL,
    ukuran_batako ENUM('standar','besar') NOT NULL,
    target_produksi INT NOT NULL DEFAULT 0,
    realisasi_produksi INT NOT NULL DEFAULT 0,
    jumlah_sak_semen DECIMAL(10,2) NOT NULL DEFAULT 0,
    pekerja_id INT NOT NULL,
    operator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pekerja_id) REFERENCES pekerja(id) ON DELETE RESTRICT,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_penjualan DATE NOT NULL,
    ukuran_batako ENUM('standar','besar') NOT NULL,
    jumlah_terjual INT NOT NULL,
    harga_satuan DECIMAL(10,0) NOT NULL,
    total_penjualan DECIMAL(12,0) NOT NULL,
    nama_pembeli VARCHAR(100),
    operator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pekerja_id INT NOT NULL,
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    total_sak_semen DECIMAL(10,2) NOT NULL DEFAULT 0,
    tarif_per_sak DECIMAL(10,0) NOT NULL DEFAULT 65000,
    total_gaji DECIMAL(12,0) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pekerja_id) REFERENCES pekerja(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_pengeluaran DATE NOT NULL,
    kategori ENUM('Bahan Baku','Listrik','Air','Transportasi','Perawatan','Lainnya') NOT NULL,
    jumlah DECIMAL(12,0) NOT NULL,
    keterangan TEXT,
    operator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ukuran_batako ENUM('standar','besar') NOT NULL,
    total_produksi INT NOT NULL DEFAULT 0,
    total_penjualan INT NOT NULL DEFAULT 0,
    stok_tersedia INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- BAGIAN 2: SEED DATA
-- ========================================

-- Users (bcrypt hash)
-- pemilik / admin123
-- operator / operator123
INSERT INTO users (name, username, email, password, role) VALUES
('Pemilik Batako', 'pemilik', 'pemilik@batakomaros.com', '$2y$12$BqWmU7bEpHG.kajb9LbeeufiyaDwSdAz9HWn1xtxkPTm.ONWybqBO', 'pemilik'),
('Operator 1', 'operator', 'operator@batakomaros.com', '$2y$12$PJAYl4WlxUOiGmLjw76L.uHbsqh5wBmd/LgbdBih/nEKurx.RNtW6', 'operator');

-- Pekerja
INSERT INTO pekerja (nama_pekerja, tarif_per_sak, status) VALUES
('Ahmad Fauzi', 65000, 'aktif'),
('Budi Santoso', 65000, 'aktif'),
('Herman', 70000, 'aktif'),
('Rudi Hartono', 65000, 'aktif'),
('Slamet Riyadi', 65000, 'nonaktif');

-- Stok awal
INSERT INTO stok (ukuran_batako, total_produksi, total_penjualan, stok_tersedia) VALUES
('standar', 0, 0, 0),
('besar', 0, 0, 0);

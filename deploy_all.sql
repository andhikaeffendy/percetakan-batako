-- ============================================================
-- deploy_all.sql — TABEL + DATA untuk InfinityFree
-- ============================================================
-- Cara import: phpMyAdmin → pilih database → SQL → Choose File → Go

-- ========================================
-- BAGIAN 1: BUAT TABEL (9 tabel)
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

CREATE TABLE IF NOT EXISTS kategori_pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bahan_baku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_penggunaan DATE NOT NULL,
    jenis_transaksi ENUM('pembelian','penggunaan') NOT NULL DEFAULT 'penggunaan',
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

CREATE TABLE IF NOT EXISTS pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_pengeluaran DATE NOT NULL,
    kategori_pengeluaran_id INT NOT NULL,
    nominal DECIMAL(12,0) NOT NULL DEFAULT 0,
    keterangan TEXT,
    operator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_pengeluaran_id) REFERENCES kategori_pengeluaran(id) ON DELETE RESTRICT,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pekerja_id INT NOT NULL,
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    total_sak_semen DECIMAL(10,2) NOT NULL DEFAULT 0,
    tarif_per_sak DECIMAL(10,0) NOT NULL DEFAULT 65000,
    panjar DECIMAL(12,0) NOT NULL DEFAULT 0,
    total_gaji DECIMAL(12,0) NOT NULL DEFAULT 0,
    operator_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pekerja_id) REFERENCES pekerja(id) ON DELETE RESTRICT,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ukuran_batako ENUM('standar','besar') NOT NULL,
    total_produksi INT NOT NULL DEFAULT 0,
    total_penjualan INT NOT NULL DEFAULT 0,
    stok_tersedia INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stok_bahan_baku (
    id_stok_bahan INT AUTO_INCREMENT PRIMARY KEY,
    jenis_bahan ENUM('Semen','Pasir') NOT NULL UNIQUE,
    jumlah DECIMAL(10,2) NOT NULL DEFAULT 0,
    satuan ENUM('Sak','m3') NOT NULL,
    status ENUM('Aman','Menipis','Habis') NOT NULL DEFAULT 'Habis',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stok_produk (
    id_stok_produk INT AUTO_INCREMENT PRIMARY KEY,
    ukuran_batako ENUM('standar','besar') NOT NULL UNIQUE,
    jumlah_stok INT NOT NULL DEFAULT 0,
    status ENUM('Aman','Menipis','Habis') NOT NULL DEFAULT 'Habis',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- BAGIAN 2: SEED DATA
-- ========================================

INSERT INTO users (name, username, email, password, role) VALUES
('Pemilik Batako', 'pemilik', 'pemilik@batakomaros.com', '$2y$12$BqWmU7bEpHG.kajb9LbeeufiyaDwSdAz9HWn1xtxkPTm.ONWybqBO', 'pemilik'),
('Operator 1', 'operator', 'operator@batakomaros.com', '$2y$12$PJAYl4WlxUOiGmLjw76L.uHbsqh5wBmd/LgbdBih/nEKurx.RNtW6', 'operator');

INSERT INTO pekerja (nama_pekerja, tarif_per_sak, status) VALUES
('Ahmad Fauzi', 65000, 'aktif'),
('Budi Santoso', 65000, 'aktif'),
('Herman', 70000, 'aktif'),
('Rudi Hartono', 65000, 'aktif'),
('Slamet Riyadi', 65000, 'nonaktif');

INSERT INTO kategori_pengeluaran (nama_kategori, status) VALUES
('Pembelian Semen', 'aktif'),
('Pembelian Pasir', 'aktif'),
('Solar Kendaraan', 'aktif'),
('Perbaikan Alat', 'aktif'),
('Biaya Transportasi', 'aktif'),
('Biaya Listrik', 'aktif'),
('Biaya Air', 'aktif'),
('Pengeluaran Lainnya', 'aktif');

INSERT INTO stok (ukuran_batako, total_produksi, total_penjualan, stok_tersedia) VALUES
('standar', 0, 0, 0),
('besar', 0, 0, 0);

INSERT INTO stok_bahan_baku (jenis_bahan, jumlah, satuan, status) VALUES
('Semen', 0, 'Sak', 'Habis'),
('Pasir', 0, 'm3', 'Habis');

INSERT INTO stok_produk (ukuran_batako, jumlah_stok, status) VALUES
('standar', 0, 'Habis'),
('besar', 0, 'Habis');

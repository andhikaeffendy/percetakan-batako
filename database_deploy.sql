-- ============================================================
-- database_deploy.sql — Untuk InfinityFree / Production
-- ============================================================
-- Cara pakai:
-- 1. Buka phpMyAdmin → pilih database Anda (if0_XXXXX_batako_maros)
-- 2. Klik tab SQL → Choose File → pilih file ini → Go
-- ============================================================
-- TIDAK pakai CREATE DATABASE atau USE karena database
-- sudah dibuat dari panel hosting.
-- ============================================================

-- 1. users
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

-- 2. pekerja
CREATE TABLE IF NOT EXISTS pekerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pekerja VARCHAR(100) NOT NULL,
    tarif_per_sak DECIMAL(10,0) DEFAULT 65000,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. bahan_baku
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

-- 4. produksi
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

-- 5. penjualan
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

-- 6. gaji
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

-- 7. stok
CREATE TABLE IF NOT EXISTS stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ukuran_batako ENUM('standar','besar') NOT NULL,
    total_produksi INT NOT NULL DEFAULT 0,
    total_penjualan INT NOT NULL DEFAULT 0,
    stok_tersedia INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

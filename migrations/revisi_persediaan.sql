-- ============================================================
-- MIGRASI REVISI DOSEN 10-17: Persediaan
-- Jalankan SEKALI pada database yang sudah ada (bukan fresh install).
-- Fresh install cukup memakai database.sql / deploy_all.sql yang sudah diperbarui.
-- Backup database sebelum menjalankan file ini.
-- ============================================================

-- 1. Kolom jenis_transaksi pada bahan_baku (default 'penggunaan' = perilaku lama)
ALTER TABLE bahan_baku
    ADD COLUMN jenis_transaksi ENUM('pembelian','penggunaan') NOT NULL DEFAULT 'penggunaan'
    AFTER tanggal_penggunaan;

-- 2. Tabel stok_bahan_baku
CREATE TABLE IF NOT EXISTS stok_bahan_baku (
    id_stok_bahan INT AUTO_INCREMENT PRIMARY KEY,
    jenis_bahan ENUM('Semen','Pasir') NOT NULL UNIQUE,
    jumlah DECIMAL(10,2) NOT NULL DEFAULT 0,
    satuan ENUM('Sak','m3') NOT NULL,
    status ENUM('Aman','Menipis','Habis') NOT NULL DEFAULT 'Habis',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel stok_produk
CREATE TABLE IF NOT EXISTS stok_produk (
    id_stok_produk INT AUTO_INCREMENT PRIMARY KEY,
    ukuran_batako ENUM('standar','besar') NOT NULL UNIQUE,
    jumlah_stok INT NOT NULL DEFAULT 0,
    status ENUM('Aman','Menipis','Habis') NOT NULL DEFAULT 'Habis',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Seed awal stok bahan baku (0) — jalankan sekali
INSERT INTO stok_bahan_baku (jenis_bahan, jumlah, satuan, status) VALUES
('Semen', 0, 'Sak', 'Habis'),
('Pasir', 0, 'm3', 'Habis')
ON DUPLICATE KEY UPDATE satuan = VALUES(satuan);

-- 5. Seed awal stok produk (0) — jalankan sekali
INSERT INTO stok_produk (ukuran_batako, jumlah_stok, status) VALUES
('standar', 0, 'Habis'),
('besar', 0, 'Habis')
ON DUPLICATE KEY UPDATE ukuran_batako = VALUES(ukuran_batako);

-- 6. Isi awal stok_produk dari data historis produksi/penjualan yang sudah ada.
--    Baris yang tidak punya data tetap bernilai 0.
INSERT INTO stok_produk (ukuran_batako, jumlah_stok, status)
SELECT ukuran_batako,
       COALESCE(SUM(realisasi_produksi), 0) - COALESCE((SELECT SUM(jumlah_terjual) FROM penjualan pj WHERE pj.ukuran_batako = pr.ukuran_batako), 0) AS saldo,
       'Habis'
FROM produksi pr
GROUP BY ukuran_batako
ON DUPLICATE KEY UPDATE jumlah_stok = VALUES(jumlah_stok);

-- 7. Tabel stok lama dibiarkan ada (LEGACY) agar tidak merusak query lama;
--    aplikasi baru membaca dari stok_bahan_baku dan stok_produk.

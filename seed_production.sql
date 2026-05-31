-- ============================================================
-- seed_production.sql — Seed data untuk production
-- Gunakan file ini jika tidak bisa menjalankan php seeder.php
-- Jalankan via phpMyAdmin atau MySQL CLI:
--   mysql -u USER -p DB_NAME < seed_production.sql
-- ============================================================

USE db_batako_maros;

-- 1. Users (password sudah di-hash bcrypt)
-- pemilik / admin123
-- operator / operator123
INSERT INTO users (name, username, email, password, role) VALUES
('Pemilik Batako', 'pemilik', 'pemilik@batakomaros.com', '$2y$12$PW.aY2ixJF3rKSL60nI9keyVH3igDLyVzUBdOSIARAG4qr1IbPaFO', 'pemilik'),
('Operator 1', 'operator', 'operator@batakomaros.com', '$2y$12$U0bJVUUyRsGc.RdYLFQE8OquqG4qo.zHaZzwZkbnRR4j72LzRLXQq', 'operator')
ON DUPLICATE KEY UPDATE id=id;

-- 2. Pekerja
INSERT INTO pekerja (nama_pekerja, tarif_per_sak, status) VALUES
('Ahmad Fauzi', 65000, 'aktif'),
('Budi Santoso', 65000, 'aktif'),
('Herman', 70000, 'aktif'),
('Rudi Hartono', 65000, 'aktif'),
('Slamet Riyadi', 65000, 'nonaktif')
ON DUPLICATE KEY UPDATE id=id;

-- 3. Stok awal
INSERT INTO stok (ukuran_batako, total_produksi, total_penjualan, stok_tersedia) VALUES
('standar', 0, 0, 0),
('besar', 0, 0, 0)
ON DUPLICATE KEY UPDATE id=id;

<?php
// seeder.php — Seed database dengan data awal
// Jalankan sekali: php seeder.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

$db = getDB();

// Nonaktifkan FK checks agar TRUNCATE bisa jalan
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

echo "=== Seeder: Percetakan Batako Maros ===\n\n";

// 1. Users
echo "Membuat users...\n";
$db->exec("TRUNCATE TABLE users");
$pemilikPass = password_hash('admin123', PASSWORD_DEFAULT);
$operatorPass = password_hash('operator123', PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['Pemilik Batako', 'pemilik', 'pemilik@batakomaros.com', $pemilikPass, 'pemilik']);
echo "  ✓ pemilik / admin123 — pemilik@batakomaros.com (role: pemilik)\n";
$stmt->execute(['Operator 1', 'operator', 'operator@batakomaros.com', $operatorPass, 'operator']);
echo "  ✓ operator / operator123 — operator@batakomaros.com (role: operator)\n";

// 2. Pekerja
echo "\nMembuat data pekerja...\n";
$db->exec("TRUNCATE TABLE pekerja");
$pekerjaList = [
    ['Ahmad Fauzi', 65000, 'aktif'],
    ['Budi Santoso', 65000, 'aktif'],
    ['Herman', 70000, 'aktif'],
    ['Rudi Hartono', 65000, 'aktif'],
    ['Slamet Riyadi', 65000, 'nonaktif'],
];
$stmt = $db->prepare("INSERT INTO pekerja (nama_pekerja, tarif_per_sak, status) VALUES (?, ?, ?)");
foreach ($pekerjaList as $p) {
    $stmt->execute($p);
    echo "  ✓ {$p[0]} (Rp" . number_format($p[1]) . "/sak, {$p[2]})\n";
}

// 3. Stok awal
echo "\nMembuat stok awal...\n";
$db->exec("TRUNCATE TABLE stok");
$stmt = $db->prepare("INSERT INTO stok (ukuran_batako, total_produksi, total_penjualan, stok_tersedia) VALUES (?, 0, 0, 0)");
$stmt->execute(['standar']);
$stmt->execute(['besar']);
echo "  ✓ Stok standar: 0\n";
echo "  ✓ Stok besar: 0\n";

// 4. Contoh data produksi (7 hari terakhir)
echo "\nMembuat contoh data produksi...\n";
$db->exec("TRUNCATE TABLE produksi");
$stmt = $db->prepare("INSERT INTO produksi (tanggal_produksi, ukuran_batako, target_produksi, realisasi_produksi, jumlah_sak_semen, pekerja_id, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$contohProduksi = [
    ['2025-05-25', 'standar', 500, 480, 15, 1, 2],
    ['2025-05-25', 'besar', 300, 290, 20, 2, 2],
    ['2025-05-26', 'standar', 500, 500, 16, 1, 2],
    ['2025-05-26', 'besar', 300, 280, 18, 3, 2],
    ['2025-05-27', 'standar', 500, 460, 14, 2, 2],
    ['2025-05-27', 'besar', 300, 300, 21, 1, 2],
    ['2025-05-28', 'standar', 500, 490, 15, 3, 2],
    ['2025-05-28', 'besar', 300, 270, 17, 2, 2],
    ['2025-05-29', 'standar', 500, 510, 16, 1, 2],
    ['2025-05-29', 'besar', 300, 285, 19, 3, 2],
    ['2025-05-30', 'standar', 500, 475, 15, 2, 2],
    ['2025-05-30', 'besar', 300, 295, 20, 1, 2],
    ['2025-05-31', 'standar', 500, 445, 14, 3, 2],
    ['2025-05-31', 'besar', 300, 260, 18, 2, 2],
];
foreach ($contohProduksi as $p) {
    $stmt->execute($p);
}
echo "  ✓ " . count($contohProduksi) . " data produksi\n";

// 5. Contoh data penjualan (7 hari terakhir)
echo "\nMembuat contoh data penjualan...\n";
$db->exec("TRUNCATE TABLE penjualan");
$stmt = $db->prepare("INSERT INTO penjualan (tanggal_penjualan, ukuran_batako, jumlah_terjual, harga_satuan, total_penjualan, nama_pembeli, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$contohPenjualan = [
    ['2025-05-25', 'standar', 200, 2500, 500000, 'Toko Maju', 2],
    ['2025-05-25', 'besar', 150, 3500, 525000, 'Pak RT 03', 2],
    ['2025-05-26', 'standar', 180, 2500, 450000, 'Proyek Masjid', 2],
    ['2025-05-26', 'besar', 120, 3500, 420000, 'Toko Jaya', 2],
    ['2025-05-27', 'standar', 250, 2500, 625000, 'Toko Maju', 2],
    ['2025-05-27', 'besar', 100, 3500, 350000, 'Pak RT 03', 2],
    ['2025-05-28', 'standar', 220, 2500, 550000, 'Proyek Masjid', 2],
    ['2025-05-28', 'besar', 90, 3500, 315000, 'Toko Jaya', 2],
    ['2025-05-29', 'standar', 190, 2500, 475000, 'Toko Maju', 2],
    ['2025-05-29', 'besar', 110, 3500, 385000, NULL, 2],
    ['2025-05-30', 'standar', 210, 2500, 525000, 'Proyek Masjid', 2],
    ['2025-05-30', 'besar', 130, 3500, 455000, 'Toko Jaya', 2],
    ['2025-05-31', 'standar', 160, 2500, 400000, 'Toko Maju', 2],
    ['2025-05-31', 'besar', 80, 3500, 280000, 'Pak RT 03', 2],
];
foreach ($contohPenjualan as $p) {
    $stmt->execute($p);
}
echo "  ✓ " . count($contohPenjualan) . " data penjualan\n";

// 6. Update stok dari produksi dan penjualan
echo "\nMemperbarui stok...\n";
updateStok($db);
$stok = getAllStok($db);
echo "  ✓ standar: stok={$stok['standar']}\n";
echo "  ✓ besar: stok={$stok['besar']}\n";

// 7. Contoh bahan baku
echo "\nMembuat contoh bahan baku...\n";
$db->exec("TRUNCATE TABLE bahan_baku");
$stmt = $db->prepare("INSERT INTO bahan_baku (tanggal_penggunaan, jenis_bahan, jumlah, satuan, keterangan, operator_id) VALUES (?, ?, ?, ?, ?, ?)");
$contohBahan = [
    ['2025-05-25', 'Semen', 35, 'Sak', 'Penggunaan harian', 2],
    ['2025-05-25', 'Pasir', 8, 'm3', 'Penggunaan harian', 2],
    ['2025-05-27', 'Semen', 35, 'Sak', 'Penggunaan harian', 2],
    ['2025-05-27', 'Pasir', 8, 'm3', 'Penggunaan harian', 2],
    ['2025-05-29', 'Semen', 35, 'Sak', 'Penggunaan harian', 2],
    ['2025-05-29', 'Pasir', 8, 'm3', 'Penggunaan harian', 2],
    ['2025-05-31', 'Semen', 32, 'Sak', 'Penggunaan harian', 2],
    ['2025-05-31', 'Pasir', 7, 'm3', 'Penggunaan harian', 2],
];
foreach ($contohBahan as $b) {
    $stmt->execute($b);
}
echo "  ✓ " . count($contohBahan) . " data bahan baku\n";

// Aktifkan kembali FK checks
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n=== Selesai! ===\n";
echo "\nLogin:\n";
echo "  Pemilik : username=pemilik / email=pemilik@batakomaros.com / password=admin123\n";
echo "  Operator: username=operator / email=operator@batakomaros.com / password=operator123\n";

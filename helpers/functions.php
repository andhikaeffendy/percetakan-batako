<?php
// helpers/functions.php
// Fungsi bisnis — updateStok, dll
require_once __DIR__ . '/../config/database.php';

/**
 * Perbarui tabel stok berdasarkan data produksi & penjualan
 */
function updateStok(PDO $db, ?string $ukuran = null): void {
    $ukuranList = $ukuran ? [$ukuran] : ['standar', 'besar'];
    foreach ($ukuranList as $u) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) FROM produksi WHERE ukuran_batako = ?");
        $stmt->execute([$u]);
        $prod = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) FROM penjualan WHERE ukuran_batako = ?");
        $stmt->execute([$u]);
        $jual = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("UPDATE stok SET total_produksi = ?, total_penjualan = ?, stok_tersedia = ? WHERE ukuran_batako = ?");
        $stmt->execute([$prod, $jual, $prod - $jual, $u]);
    }
}

/**
 * Ambil stok tersedia per ukuran
 */
function getStok(PDO $db, string $ukuran): int {
    $stmt = $db->prepare("SELECT stok_tersedia FROM stok WHERE ukuran_batako = ?");
    $stmt->execute([$ukuran]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

/**
 * Ambil semua stok
 */
function getAllStok(PDO $db): array {
    $stmt = $db->query("SELECT ukuran_batako, stok_tersedia FROM stok ORDER BY ukuran_batako");
    $result = ['standar' => 0, 'besar' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['ukuran_batako']] = (int) $row['stok_tersedia'];
    }
    return $result;
}

/**
 * Generate CSRF token
 */
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token
 */
function verifyCsrf(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

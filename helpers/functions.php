<?php
// helpers/functions.php
// Fungsi bisnis — updateStok, updateStokBahan, dll
require_once __DIR__ . '/../config/database.php';

// Ambang status persediaan (disetujui Andhika 2026-08-09)
const STOK_AMBANG_BATOKO = 100;   // pcs; <= ambang = Menipis, 0 = Habis
const STOK_AMBANG_SEMEN = 10;     // sak
const STOK_AMBANG_PASIR = 2;      // m3

/**
 * Tentukan status persediaan berdasarkan jumlah stok saat ini.
 */
function hitungStatusStok(float $jumlah, string $jenis): string {
    if ($jumlah <= 0) return 'Habis';
    if ($jenis === 'Semen') $ambang = STOK_AMBANG_SEMEN;
    elseif ($jenis === 'Pasir') $ambang = STOK_AMBANG_PASIR;
    else $ambang = STOK_AMBANG_BATOKO;
    return $jumlah <= $ambang ? 'Menipis' : 'Aman';
}

/**
 * Sinkronkan stok produk dari produksi & penjualan ke tabel stok_produk.
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

        $saldo = max(0, $prod - $jual);
        $status = hitungStatusStok($saldo, $u);

        $stmt = $db->prepare(
            "INSERT INTO stok_produk (ukuran_batako, jumlah_stok, status)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE jumlah_stok = VALUES(jumlah_stok), status = VALUES(status)"
        );
        $stmt->execute([$u, $saldo, $status]);

        // LEGACY: jaga tabel stok lama tetap sinkron agar laporan lama tidak kosong
        $stmt = $db->prepare("UPDATE stok SET total_produksi = ?, total_penjualan = ?, stok_tersedia = ? WHERE ukuran_batako = ?");
        $stmt->execute([$prod, $jual, $saldo, $u]);
    }
}

/**
 * Sinkronkan stok bahan baku dari pembelian & penggunaan ke tabel stok_bahan_baku.
 * Stok = SUM(pembelian) - SUM(penggunaan) per jenis bahan.
 */
function updateStokBahan(PDO $db, ?string $jenis = null): void {
    $jenisList = $jenis ? [$jenis] : ['Semen', 'Pasir'];
    foreach ($jenisList as $j) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM bahan_baku WHERE jenis_bahan = ? AND jenis_transaksi = 'pembelian'");
        $stmt->execute([$j]);
        $beli = (float) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM bahan_baku WHERE jenis_bahan = ? AND jenis_transaksi = 'penggunaan'");
        $stmt->execute([$j]);
        $pakai = (float) $stmt->fetchColumn();

        $satuan = ($j === 'Semen') ? 'Sak' : 'm3';
        $saldo = max(0, $beli - $pakai);
        $status = hitungStatusStok($saldo, $j);

        $stmt = $db->prepare(
            "INSERT INTO stok_bahan_baku (jenis_bahan, jumlah, satuan, status)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE jumlah = VALUES(jumlah), satuan = VALUES(satuan), status = VALUES(status)"
        );
        $stmt->execute([$j, $saldo, $satuan, $status]);
    }
}

/**
 * Sinkronkan seluruh persediaan (produk + bahan baku).
 */
function updateSemuaStok(PDO $db): void {
    updateStok($db);
    updateStokBahan($db);
}

/**
 * Ambil stok tersedia per ukuran
 */
function getStok(PDO $db, string $ukuran): int {
    $stmt = $db->prepare("SELECT jumlah_stok FROM stok_produk WHERE ukuran_batako = ?");
    $stmt->execute([$ukuran]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

/**
 * Ambil semua stok
 */
function getAllStok(PDO $db): array {
    $stmt = $db->query("SELECT ukuran_batako, jumlah_stok FROM stok_produk ORDER BY ukuran_batako");
    $result = ['standar' => 0, 'besar' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['ukuran_batako']] = (int) $row['jumlah_stok'];
    }
    return $result;
}

/**
 * Validasi pasangan jenis bahan + satuan.
 * Hanya Semen/Sak atau Pasir/m3 yang diterima.
 */
function validasiSatuanBahan(string $jenis, string $satuan): ?string {
    $aturan = ['Semen' => 'Sak', 'Pasir' => 'm3'];
    if (!isset($aturan[$jenis])) {
        return 'Jenis bahan tidak valid.';
    }
    if ($satuan !== $aturan[$jenis]) {
        return "Satuan untuk $jenis harus '{$aturan[$jenis]}', bukan '$satuan'.";
    }
    return null;
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

/**
 * Wajibkan token CSRF valid sebelum mutasi; redirect + flash bila gagal.
 */
function requireCsrf(string $url, string $pesan = 'Sesi berakhir atau token tidak valid. Silakan coba lagi.'): void {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        redirect($url, 'danger', $pesan);
    }
}

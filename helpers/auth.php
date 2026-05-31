<?php
// helpers/auth.php
// Fungsi autentikasi dan otorisasi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Require login, redirect ke login.php jika belum
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php?error=Silakan login terlebih dahulu');
        exit;
    }
}

/**
 * Require role tertentu
 */
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        if ($_SESSION['role'] === 'pemilik') {
            header('Location: /pemilik/dashboard.php');
        } else {
            header('Location: /operator/index.php');
        }
        exit;
    }
}

/**
 * Cek apakah user adalah pemilik
 */
function isPemilik(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'pemilik';
}

/**
 * Cek apakah user adalah operator
 */
function isOperator(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'operator';
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get flash message dan hapus
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect dengan flash message
 */
function redirect(string $url, string $type = '', string $message = ''): void {
    if ($type && $message) {
        setFlash($type, $message);
    }
    header("Location: $url");
    exit;
}

/**
 * Format angka ke Rupiah
 */
function formatRupiah($angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal(string $date): string {
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $parts = explode('-', $date);
    return $parts[2] . ' ' . $bulan[$parts[1]] . ' ' . $parts[0];
}

/**
 * Escape output untuk mencegah XSS
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

<?php
// config/database.php
// Koneksi database PDO - kredensial hanya dari .env (fail-fast bila kosong)

// Load .env jika ada
$envLoaded = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
            $envLoaded = !empty($_ENV['DB_HOST']);
        } catch (\Exception $e) {}
    }
}

// Credentials live ONLY in .env (see .env.example). No hardcoded fallbacks.
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
if (!$envLoaded) {
    http_response_code(500);
    die('Konfigurasi database tidak ditemukan. Salin .env.example ke .env dan isi kredensial.');
}
foreach ($required as $key) {
    if (!defined($key)) {
        $value = $_ENV[$key] ?? (getenv($key) ?: '');
        if ($value === '') {
            http_response_code(500);
            die('Variabel lingkungan ' . $key . ' belum diatur di .env.');
        }
        define($key, $value);
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}

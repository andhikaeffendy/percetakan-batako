<?php
// config/database.php
// Koneksi database PDO — InfinityFree ready

// Load .env jika ada
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        } catch (\Exception $e) {}
    }
}

// Define konstanta — .env dulu, fallback ke production
$envDefaults = [
    'DB_HOST' => 'sql105.infinityfree.com',
    'DB_NAME' => 'if0_42059089_batako_maros',
    'DB_USER' => 'if0_42059089',
    'DB_PASS' => 'Batako2026',
];
foreach ($envDefaults as $key => $default) {
    if (!defined($key)) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
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

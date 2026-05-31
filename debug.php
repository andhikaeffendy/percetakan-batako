<?php
// debug.php — Debug semua error
// Akses: http://percetakan-batako.page.gd/debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug Percetakan Batako</h3><hr>";

// 1. PHP version
echo "PHP: " . phpversion() . "<br>";

// 2. File paths
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "SCRIPT: " . __FILE__ . "<br>";

// 3. Test vendor
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ vendor/autoload.php: ADA<br>";
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo "❌ vendor/autoload.php: TIDAK ADA<br>";
}

// 4. Test .env  
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    echo "✅ .env: ADA (" . strlen($envContent) . " bytes)<br>";
    echo "<pre>" . htmlspecialchars($envContent) . "</pre>";
} else {
    echo "❌ .env: TIDAK ADA<br>";
}

// 5. Test database (hardcode)
echo "<hr><b>Test Database:</b><br>";
try {
    $pdo = new PDO("mysql:host=sql105.infinityfree.com;port=3306;dbname=if0_42059089_batako_maros;charset=utf8mb4", "if0_42059089", "Batako2026", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ Koneksi DB SUCCESS<br>";
    
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tabel: " . $stmt->rowCount() . "<br>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "  - {$row[0]}<br>";
    }
    
    $stmt = $pdo->query("SELECT * FROM users");
    echo "<br>Users: " . $stmt->rowCount() . "<br>";
    while ($row = $stmt->fetch()) {
        echo "  - {$row['username']} ({$row['role']})<br>";
    }
} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "<br>";
}

// 6. Test login.php include
echo "<hr><b>Test include files:</b><br>";
$files = ['helpers/auth.php', 'helpers/functions.php', 'config/database.php'];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        echo "✅ {$f}: ADA<br>";
    } else {
        echo "❌ {$f}: TIDAK ADA<br>";
    }
}

echo "<hr><b>Selesai</b>";

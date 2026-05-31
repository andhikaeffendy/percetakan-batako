<?php
// test_db.php — Test koneksi database
$host = 'sql105.infinityfree.com';
$port = 3306;
$dbname = 'if0_42059089_batako_maros';
$user = 'if0_42059089';
$pass = 'Batako2026';

echo "<h3>Test Koneksi Database</h3>";
echo "DSN: mysql:host={$host};port={$port};dbname={$dbname}<br>";

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ KONEKSI BERHASIL!<br>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    echo "Users: " . $stmt->fetch()['total'] . "<br>";
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tabel: " . $stmt->rowCount() . "<br>";
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

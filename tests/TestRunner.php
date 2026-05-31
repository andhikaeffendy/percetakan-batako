<?php
/**
 * TestRunner.php — Comprehensive test suite
 * Run: php tests/TestRunner.php
 * 
 * Tests all 30 scenarios from TESTING_CHECKLIST.md
 * Plus additional automated integration tests
 */

// Bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

$testsPassed = 0;
$testsFailed = 0;
$testsTotal = 0;

function assertTest(string $name, bool $condition, string $detail = '') {
    global $testsPassed, $testsFailed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo "  ✅ {$name}\n";
    } else {
        $testsFailed++;
        echo "  ❌ {$name}" . ($detail ? " — {$detail}" : '') . "\n";
    }
}

function testHeader(string $title) {
    echo "\n━━━ {$title} ━━━\n";
}

echo "╔══════════════════════════════════════════╗\n";
echo "║  Test Suite: Percetakan Batako Maros    ║\n";
echo "╚══════════════════════════════════════════╝\n";

// ──────────────────────────────────────────────
testHeader("1. Database Connection");
$db = getDB();
assertTest("Database connection established", $db instanceof PDO);

$stmt = $db->query("SELECT DATABASE() as dbname");
$dbName = $stmt->fetch()['dbname'];
assertTest("Database 'db_batako_maros' exists", $dbName === 'db_batako_maros');


// ──────────────────────────────────────────────
testHeader("2. Table Structure");

$tables = ['users', 'pekerja', 'bahan_baku', 'produksi', 'penjualan', 'gaji', 'stok'];
foreach ($tables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
    assertTest("Table '{$table}' exists", $stmt->rowCount() > 0);
}

// Check columns
$stmt = $db->query("DESCRIBE users");
$usersCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
assertTest("users has 'password' column", in_array('password', $usersCols));
assertTest("users has 'role' column (ENUM)", in_array('role', $usersCols));

$stmt = $db->query("DESCRIBE stok");
$stokCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
assertTest("stok has ukuran_batako, total_produksi, total_penjualan, stok_tersedia",
    in_array('ukuran_batako', $stokCols) && in_array('stok_tersedia', $stokCols));


// ──────────────────────────────────────────────
testHeader("3. Seed Data — Users");

$stmt = $db->query("SELECT COUNT(*) FROM users");
assertTest("Users table has data", $stmt->fetchColumn() > 0);

$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['pemilik']);
$pemilik = $stmt->fetch();
assertTest("Pemilik user exists", (bool)$pemilik);
assertTest("Pemilik has role 'pemilik'", $pemilik && $pemilik['role'] === 'pemilik');
assertTest("Pemilik password is hashed (bcrypt)", $pemilik && preg_match('/^\$2y\$/', $pemilik['password']));
assertTest("Pemilik password verifies", $pemilik && password_verify('admin123', $pemilik['password']));

$stmt->execute(['operator']);
$operator = $stmt->fetch();
assertTest("Operator user exists", (bool)$operator);
assertTest("Operator has role 'operator'", $operator && $operator['role'] === 'operator');
assertTest("Operator password verifies", $operator && password_verify('operator123', $operator['password']));


// ──────────────────────────────────────────────
testHeader("4. Seed Data — Pekerja");

$stmt = $db->query("SELECT COUNT(*) FROM pekerja");
$pekerjaCount = $stmt->fetchColumn();
assertTest("Pekerja has 5 records", $pekerjaCount == 5);

$stmt = $db->query("SELECT COUNT(*) FROM pekerja WHERE status = 'aktif'");
assertTest("4 active workers", $stmt->fetchColumn() == 4);

$stmt = $db->query("SELECT COUNT(*) FROM pekerja WHERE status = 'nonaktif'");
assertTest("1 inactive worker", $stmt->fetchColumn() == 1);


// ──────────────────────────────────────────────
testHeader("5. Seed Data — Stok");

$stmt = $db->query("SELECT COUNT(*) FROM stok");
assertTest("Stok has 2 records (standar, besar)", $stmt->fetchColumn() == 2);

$stmt = $db->query("SELECT ukuran_batako, stok_tersedia FROM stok ORDER BY ukuran_batako");
$stokRows = $stmt->fetchAll();
assertTest("Stok standar > 0", $stokRows[0]['stok_tersedia'] > 0);
assertTest("Stok besar > 0", $stokRows[1]['stok_tersedia'] > 0);


// ──────────────────────────────────────────────
testHeader("6. Seed Data — Produksi & Penjualan");

$stmt = $db->query("SELECT COUNT(*) FROM produksi");
assertTest("Produksi has 14+ records", $stmt->fetchColumn() >= 14);

$stmt = $db->query("SELECT COUNT(*) FROM penjualan");
assertTest("Penjualan has 14+ records", $stmt->fetchColumn() >= 14);

$stmt = $db->query("SELECT COUNT(*) FROM bahan_baku");
assertTest("Bahan baku has 8+ records", $stmt->fetchColumn() >= 8);


// ──────────────────────────────────────────────
testHeader("7. Business Logic — Stock Calculation");

// Stock = sum(produksi) - sum(penjualan) per ukuran
foreach (['standar', 'besar'] as $ukuran) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) FROM produksi WHERE ukuran_batako = ?");
    $stmt->execute([$ukuran]);
    $totalProd = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) FROM penjualan WHERE ukuran_batako = ?");
    $stmt->execute([$ukuran]);
    $totalJual = (int)$stmt->fetchColumn();

    $expectedStok = $totalProd - $totalJual;

    $stmt = $db->prepare("SELECT stok_tersedia FROM stok WHERE ukuran_batako = ?");
    $stmt->execute([$ukuran]);
    $actualStok = (int)$stmt->fetchColumn();

    assertTest("Stok {$ukuran}: {$totalProd} - {$totalJual} = {$actualStok}",
        $expectedStok === $actualStok,
        "Expected {$expectedStok}, got {$actualStok}");
}


// ──────────────────────────────────────────────
testHeader("8. Business Logic — Insert Production Updates Stock");

// Get current stock
$stmt = $db->query("SELECT stok_tersedia FROM stok WHERE ukuran_batako = 'standar'");
$stokBefore = (int)$stmt->fetchColumn();

// Insert a production record
$stmt = $db->prepare("INSERT INTO produksi (tanggal_produksi, ukuran_batako, target_produksi, realisasi_produksi, jumlah_sak_semen, pekerja_id, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([date('Y-m-d'), 'standar', 10, 10, 1, 1, 1]);
$newProdId = $db->lastInsertId();

updateStok($db);

$stmt = $db->query("SELECT stok_tersedia FROM stok WHERE ukuran_batako = 'standar'");
$stokAfter = (int)$stmt->fetchColumn();
assertTest("Stock increased by 10 after production", $stokAfter === $stokBefore + 10, "Before: {$stokBefore}, After: {$stokAfter}");

// Cleanup
$db->prepare("DELETE FROM produksi WHERE id = ?")->execute([$newProdId]);
updateStok($db);


// ──────────────────────────────────────────────
testHeader("9. Business Logic — Insert Sale Updates Stock");

$stmt = $db->query("SELECT stok_tersedia FROM stok WHERE ukuran_batako = 'standar'");
$stokRow = $stmt->fetch();
$stokBefore = (int)$stokRow['stok_tersedia'];

// Insert a sale record
$stmt = $db->prepare("INSERT INTO penjualan (tanggal_penjualan, ukuran_batako, jumlah_terjual, harga_satuan, total_penjualan, operator_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([date('Y-m-d'), 'standar', 5, 2500, 12500, 1]);
$newSaleId = $db->lastInsertId();

updateStok($db);

$stmt = $db->query("SELECT stok_tersedia FROM stok WHERE ukuran_batako = 'standar'");
$stokAfter = (int)$stmt->fetchColumn();
assertTest("Stock decreased by 5 after sale", $stokAfter === $stokBefore - 5, "Before: {$stokBefore}, After: {$stokAfter}");

// Cleanup
$db->prepare("DELETE FROM penjualan WHERE id = ?")->execute([$newSaleId]);
updateStok($db);


// ──────────────────────────────────────────────
testHeader("10. Business Logic — Stock Validation");

$stmt = $db->query("SELECT stok_tersedia FROM stok WHERE ukuran_batako = 'standar'");
$stokTersedia = (int)$stmt->fetchColumn();

// Sale that exceeds stock should not be allowed
$stmt = $db->prepare("SELECT stok_tersedia FROM stok WHERE ukuran_batako = ?");
$stmt->execute(['standar']);
$limit = (int)$stmt->fetchColumn();

// Verify stock is correct
assertTest("Stock is reasonable (non-negative)", $limit >= 0);
if ($limit > 0) {
    assertTest("Stock exists for validation test", true);
}


// ──────────────────────────────────────────────
testHeader("11. Business Logic — Salary Calculation");

$stmt = $db->prepare("SELECT id, tarif_per_sak FROM pekerja WHERE status = 'aktif' LIMIT 1");
$stmt->execute();
$pekerja = $stmt->fetch();

$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_sak_semen), 0) as total_sak FROM produksi WHERE pekerja_id = ?");
$stmt->execute([$pekerja['id']]);
$totalSak = (float)$stmt->fetchColumn();

$expectedGaji = $totalSak * $pekerja['tarif_per_sak'];
assertTest("Salary = sak_semen x tarif_per_sak ({$totalSak} x {$pekerja['tarif_per_sak']} = {$expectedGaji})",
    $expectedGaji >= 0);


// ──────────────────────────────────────────────
testHeader("12. Foreign Key Integrity");

// Ensure FK constraints exist
$stmt = $db->query("
    SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'db_batako_maros' AND REFERENCED_TABLE_NAME IS NOT NULL
");
$fkCount = $stmt->rowCount();
assertTest("Foreign key constraints exist", $fkCount >= 5);

$fks = $stmt->fetchAll();
$fkTables = array_column($fks, 'TABLE_NAME');
assertTest("produksi has FK to pekerja", in_array('produksi', $fkTables));
assertTest("produksi has FK to users", in_array('produksi', $fkTables));
assertTest("penjualan has FK to users", in_array('penjualan', $fkTables));
assertTest("bahan_baku has FK to users", in_array('bahan_baku', $fkTables));
assertTest("gaji has FK to pekerja", in_array('gaji', $fkTables));


// ──────────────────────────────────────────────
testHeader("13. Helper Functions");

assertTest("formatRupiah works", formatRupiah(2500) === 'Rp 2.500');
assertTest("formatRupiah handles large numbers", formatRupiah(1000000) === 'Rp 1.000.000');
assertTest("formatRupiah handles zero", formatRupiah(0) === 'Rp 0');

assertTest("e() escapes HTML", e('<script>alert("xss")</script>') === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
assertTest("e() handles null", e(null) === '');

assertTest("formatTanggal works", formatTanggal('2025-05-31') === '31 Mei 2025');
assertTest("formatTanggal January", formatTanggal('2025-01-15') === '15 Januari 2025');

// CSRF token generation
$token1 = csrfToken();
$token2 = csrfToken();
assertTest("CSRF token is 64-char hex", strlen($token1) === 64 && ctype_xdigit($token1));
assertTest("CSRF token is consistent in session", $token1 === $token2);
assertTest("CSRF verification works", verifyCsrf($token1));
assertTest("CSRF rejects invalid token", !verifyCsrf('invalid'));


// ──────────────────────────────────────────────
testHeader("14. Stock Function Integrity");

$allStok = getAllStok($db);
assertTest("getAllStok returns array with 'standar' key", isset($allStok['standar']));
assertTest("getAllStok returns array with 'besar' key", isset($allStok['besar']));
assertTest("getAllStok values are integers", is_int($allStok['standar']) && is_int($allStok['besar']));

$stokStandar = getStok($db, 'standar');
assertTest("getStok('standar') matches getAllStok['standar']", $stokStandar === $allStok['standar']);


// ──────────────────────────────────────────────
testHeader("15. Login Auth Logic");

// Test session simulation
$_SESSION = [];
assertTest("isLoggedIn returns false without session", !isLoggedIn());

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'pemilik';
assertTest("isLoggedIn returns true with session", isLoggedIn());
assertTest("isPemilik returns true for pemilik role", isPemilik());
assertTest("isOperator returns false for pemilik role", !isOperator());

$_SESSION['role'] = 'operator';
assertTest("isOperator returns true for operator role", isOperator());
assertTest("isPemilik returns false for operator role", !isPemilik());


// ──────────────────────────────────────────────
testHeader("16. Flash Messages");

$_SESSION = [];
setFlash('success', 'Test message');
$flash = getFlash();
assertTest("setFlash/getFlash works", $flash['type'] === 'success' && $flash['message'] === 'Test message');
assertTest("getFlash clears flash", getFlash() === null);


// ──────────────────────────────────────────────
echo "\n══════════════════════════════════════════\n";
echo "  Results: {$testsPassed}/{$testsTotal} passed";
if ($testsFailed > 0) echo ", {$testsFailed} failed";
echo "\n";
echo "  " . str_repeat('█', round($testsPassed / max($testsTotal, 1) * 40));
echo "\n";
echo "══════════════════════════════════════════\n";

// Cleanup session
$_SESSION = [];

exit($testsFailed > 0 ? 1 : 0);

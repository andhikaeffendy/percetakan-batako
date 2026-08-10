<?php
// CRUD flow regression matrix (read-only, no DB).
$base = __DIR__ . '/../pemilik/';
$map = [
    'bahan_baku' => 'Data bahan baku tidak ditemukan.',
    'produksi' => 'Data produksi tidak ditemukan.',
    'penjualan' => 'Data penjualan tidak ditemukan.',
    'pengeluaran' => 'Data pengeluaran tidak ditemukan.',
    'tenaga_kerja' => 'Data pekerja tidak ditemukan.',
];
$fail = [];
foreach ($map as $p => $needle) {
    $source = file_get_contents($base . $p . '.php');
    if (str_contains($source, 'data-bs-target="#modalForm"')) $fail[] = "$p: add action can retain stale modal id";
    if (!str_contains($source, "window.location.href='$p.php'")) $fail[] = "$p: add action does not reset to fresh route";
    if (!str_contains($source, 'ctype_digit') && !str_contains($source, '(int)$_GET')) $fail[] = "$p: id not strictly parsed";
    if (!str_contains($source, 'rowCount()')) $fail[] = "$p: no rowCount guard on mutations";
    if (!str_contains($source, $needle)) $fail[] = "$p: missing not-found branch";
    if (!str_contains($source, "name=\"csrf_token\"")) $fail[] = "$p: missing CSRF token field";
    if (!str_contains($source, "requireCsrf('/pemilik/$p.php')")) $fail[] = "$p: missing CSRF verification";
    if (!str_contains($source, "_method")) $fail[] = "$p: delete is not a POST form";
}
assert(empty($fail), implode('; ', $fail));
$tk = file_get_contents($base . 'tenaga_kerja.php');
assert(str_contains($tk, <<<'HTML'
<a href="?edit=<?= $row['id'] ?>" class="btn btn-light btn-sm" title="Edit pekerja">
HTML), 'tenaga_kerja edit link must navigate');
assert(!str_contains($tk, 'title="Edit pekerja" data-bs-toggle'), 'tenaga_kerja edit must not open modal');
assert(str_contains($tk, "in_array(\$_POST['status']"), 'tenaga_kerja status must be whitelisted');
assert(str_contains($tk, 'value="toggle"'), 'tenaga_kerja toggle must be a POST form');
$sales = file_get_contents($base . 'penjualan.php');
assert(str_contains($sales, <<<'PHP'
if ($old['ukuran_batako'] === $ukuran)
PHP), 'cross-size edit must not restore stock from a different size');
$updateSegment = substr($sales, strpos($sales, 'UPDATE penjualan SET'));
assert(strpos($updateSegment, 'updateStok($db);') > strpos($updateSegment, 'SELECT 1 FROM penjualan WHERE id = ?'), 'stock recalculation must follow update target guard');
$gaji = file_get_contents($base . 'gaji.php');
assert(str_contains($gaji, 'DELETE FROM gaji WHERE pekerja_id = ? AND periode_awal = ? AND periode_akhir = ?'), 'payroll retry must replace same worker-period');
$payrollLoop = substr($gaji, strpos($gaji, 'foreach ($hasil as $h)'));
assert(strpos($payrollLoop, '$deleteExisting->execute') < strpos($payrollLoop, '$stmt->execute'), 'payroll delete must precede insert');
assert(str_contains($gaji, "name=\"csrf_token\""), 'gaji missing CSRF token field');
$op = __DIR__ . '/../operator/';
foreach (['input_produksi.php' => 'Realisasi tidak boleh melebihi target.', 'input_penjualan.php' => 'Harga satuan harus angka positif.', 'input_pengeluaran.php' => 'Nominal harus angka positif.'] as $file => $needle) {
    $source = file_get_contents($op . $file);
    if (!str_contains($source, $needle)) $fail[] = "$file: missing validation";
    if (!str_contains($source, "name=\"csrf_token\"")) $fail[] = "$file: missing CSRF token field";
}
assert(empty($fail), implode('; ', $fail));
echo "PASS: owner CRUD regression matrix holds for all 5 pages.\n";
// --- CSRF token placement: every csrf_token input MUST be inside a <form> ---
// (an input before <form method="POST"> is never submitted; requireCsrf() then
//  rejects every legitimate submit)
function csrfOutsideForm(string $html, string $page): array {
    $fails = [];
    // crude but deterministic: any token line whose NEXT non-empty line is <form ...>
    // means the token sits BEFORE the form (outside it) -> never submitted
    $lines = preg_split('/\R/', $html);
    foreach ($lines as $i => $line) {
        if (!str_contains($line, 'name="csrf_token"')) continue;
        for ($j = $i + 1; $j < count($lines); $j++) {
            $t = trim($lines[$j]);
            if ($t === '') continue;
            if (str_starts_with($t, '<form')) $fails[] = $page . ': token before <form> (line ' . ($i + 1) . ')';
            break;
        }
    }
    return $fails;
}
$fail2 = [];
foreach ($map as $p => $_n) {
    $src = file_get_contents($base . $p . '.php');
    $fail2 = array_merge($fail2, csrfOutsideForm($src, $p));
}
foreach (['input_bahan_baku.php', 'input_produksi.php', 'input_penjualan.php', 'input_pengeluaran.php'] as $f) {
    $src = file_get_contents($op . $f);
    $fail2 = array_merge($fail2, csrfOutsideForm($src, 'operator/' . $f));
}
// gaji: token inside formGaji (token line must be AFTER the <form> line)
$gajiSrc = file_get_contents($base . 'gaji.php');
$fail2 = array_merge($fail2, csrfOutsideForm($gajiSrc, 'gaji'));
assert(empty($fail2), implode('; ', $fail2));
echo "PASS: all CSRF tokens are inside their <form>.\n";


<?php
// operator/input_penjualan.php — Input Penjualan (Operator)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Input Penjualan';
$today = date('Y-m-d');
$success = false;
$stokWarning = '';

// Get stok
$stmt = $db->query("SELECT ukuran_batako, stok_tersedia FROM stok");
$stokData = [];
foreach ($stmt->fetchAll() as $s) {
    $stokData[$s['ukuran_batako']] = $s['stok_tersedia'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal_penjualan'];
    $ukuran = $_POST['ukuran_batako'];
    $jumlah = $_POST['jumlah_terjual'];
    $harga = $_POST['harga_satuan'];
    $total = $jumlah * $harga;
    $pembeli = $_POST['nama_pembeli'] ?? null;
    $operatorId = $_SESSION['user_id'];

    // Cek stok
    $stokTersedia = $stokData[$ukuran] ?? 0;
    if ($jumlah > $stokTersedia) {
        $stokWarning = "⚠️ Stok tidak mencukupi! Stok {$ukuran} tersedia: " . number_format($stokTersedia) . " batako.";
    } else {
        $stmt = $db->prepare("INSERT INTO penjualan (tanggal_penjualan, ukuran_batako, jumlah_terjual, harga_satuan, total_penjualan, nama_pembeli, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $ukuran, $jumlah, $harga, $total, $pembeli, $operatorId]);

        updateStok($db);
        $success = true;
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-cart-check"></i> Form Input Penjualan</h5></div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> Transaksi penjualan berhasil disimpan! Stok otomatis berkurang.</div>
                <?php endif; ?>
                <?php if ($stokWarning): ?>
                <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> <?= $stokWarning ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal Penjualan</label>
                                <input type="date" name="tanggal_penjualan" class="form-control date-today" value="<?= $today ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Ukuran Batako</label>
                                <select name="ukuran_batako" class="form-select" required>
                                    <option value="">Pilih ukuran</option>
                                    <option value="standar">Standar (Stok: <?= number_format($stokData['standar'] ?? 0) ?>)</option>
                                    <option value="besar">Besar (Stok: <?= number_format($stokData['besar'] ?? 0) ?>)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jumlah Terjual</label>
                                <input type="number" name="jumlah_terjual" id="jumlah_terjual" class="form-control" min="1" placeholder="Contoh: 200" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Harga Satuan (Rp)</label>
                                <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" min="0" value="2500" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Total Penjualan (Otomatis)</label>
                                <input type="number" id="total_penjualan" class="form-control" readonly style="background:var(--background);font-weight:600;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Pembeli (Opsional)</label>
                                <input type="text" name="nama_pembeli" class="form-control" placeholder="Contoh: Toko Maju">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning btn-lg" style="color:white;">
                                <i class="bi bi-save"></i> Simpan Penjualan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5>📦 Stok Saat Ini</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <small style="color:var(--text-muted);">Batako Standar</small>
                    <div style="font-size:24px;font-weight:700;color:var(--primary);"><?= number_format($stokData['standar'] ?? 0) ?></div>
                </div>
                <div>
                    <small style="color:var(--text-muted);">Batako Besar</small>
                    <div style="font-size:24px;font-weight:700;color:var(--orange);"><?= number_format($stokData['besar'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5>📊 Ringkasan Hari Ini</h5></div>
            <div class="card-body">
                <?php
                $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as terjual, COALESCE(SUM(total_penjualan), 0) as pendapatan, COUNT(*) as transaksi FROM penjualan WHERE tanggal_penjualan = ?");
                $stmt->execute([$today]);
                $ringkasan = $stmt->fetch();
                ?>
                <div class="mb-3">
                    <small style="color:var(--text-muted);">Total Terjual</small>
                    <div style="font-size:18px;font-weight:700;"><?= number_format($ringkasan['terjual']) ?> pcs</div>
                </div>
                <div class="mb-3">
                    <small style="color:var(--text-muted);">Total Pendapatan</small>
                    <div style="font-size:18px;font-weight:700;color:var(--green);"><?= formatRupiah($ringkasan['pendapatan']) ?></div>
                </div>
                <div>
                    <small style="color:var(--text-muted);">Jumlah Transaksi</small>
                    <div style="font-size:18px;font-weight:700;"><?= number_format($ringkasan['transaksi']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

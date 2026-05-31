<?php
// operator/input_pengeluaran.php — Input Pengeluaran (Operator)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Input Pengeluaran';
$today = date('Y-m-d');
$success = false;

$kategoriList = $db->query("SELECT id, nama_kategori FROM kategori_pengeluaran WHERE status = 'aktif' ORDER BY nama_kategori")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal_pengeluaran'];
    $kategoriId = $_POST['kategori_pengeluaran_id'];
    $nominal = $_POST['nominal'];
    $keterangan = $_POST['keterangan'] ?? '';
    $operatorId = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO pengeluaran (tanggal_pengeluaran, kategori_pengeluaran_id, nominal, keterangan, operator_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$tanggal, $kategoriId, $nominal, $keterangan, $operatorId]);
    $success = true;
}

$stmt = $db->prepare("SELECT COALESCE(SUM(p.nominal), 0) as total, COUNT(*) as transaksi FROM pengeluaran p WHERE p.tanggal_pengeluaran = ?");
$stmt->execute([$today]);
$ringkasan = $stmt->fetch();

include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-cash-stack"></i> Form Input Pengeluaran</h5></div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> Data pengeluaran berhasil disimpan!</div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal_pengeluaran" class="form-control date-today" value="<?= $today ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select name="kategori_pengeluaran_id" class="form-select" required>
                                    <option value="">Pilih kategori</option>
                                    <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>"><?= e($k['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nominal (Rp)</label>
                                <input type="number" name="nominal" class="form-control" min="0" placeholder="Contoh: 500000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Beli semen 50 sak">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-save"></i> Simpan Pengeluaran
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5>💸 Ringkasan Hari Ini</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <small style="color:var(--text-muted);">Total Pengeluaran</small>
                    <div style="font-size:24px;font-weight:700;color:var(--red);"><?= formatRupiah($ringkasan['total']) ?></div>
                </div>
                <div>
                    <small style="color:var(--text-muted);">Jumlah Transaksi</small>
                    <div style="font-size:24px;font-weight:700;"><?= number_format($ringkasan['transaksi']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

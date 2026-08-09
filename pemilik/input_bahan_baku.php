<?php
// operator/input_bahan_baku.php — Input Bahan Baku (Operator)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Input Bahan Baku';
$today = date('Y-m-d');
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal_penggunaan'];
    $jenisTransaksi = ($_POST['aksi'] ?? 'penggunaan') === 'pembelian' ? 'pembelian' : 'penggunaan';
    $jenis = $_POST['jenis_bahan'];
    $jumlah = $_POST['jumlah'];
    $satuan = $_POST['satuan'];
    $keterangan = $_POST['keterangan'] ?? '';
    $operatorId = $_SESSION['user_id'];

    // Validasi backend: Semen wajib Sak, Pasir wajib m3
    $errSatuan = validasiSatuanBahan((string)$jenis, (string)$satuan);
    if ($errSatuan) {
        $error = $errSatuan;
    } else {
        $stmt = $db->prepare("INSERT INTO bahan_baku (tanggal_penggunaan, jenis_transaksi, jenis_bahan, jumlah, satuan, keterangan, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $jenisTransaksi, $jenis, $jumlah, $satuan, $keterangan, $operatorId]);

        updateStokBahan($db, $jenis);
        $success = true;
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-box-seam"></i> Form Input Bahan Baku</h5></div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> Transaksi bahan baku berhasil disimpan! Stok otomatis diperbarui.</div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jenis Transaksi</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success btn-lg flex-fill" name="aksi" value="pembelian">
                                        <i class="bi bi-plus-circle"></i> Simpan Pembelian
                                    </button>
                                    <button type="submit" class="btn btn-warning btn-lg flex-fill" style="color:white;" name="aksi" value="penggunaan">
                                        <i class="bi bi-dash-circle"></i> Simpan Penggunaan
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_penggunaan" class="form-control date-today" value="<?= $today ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jenis Bahan</label>
                                <select name="jenis_bahan" class="form-select" required>
                                    <option value="">Pilih jenis bahan</option>
                                    <option value="Semen">Semen</option>
                                    <option value="Pasir">Pasir</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jumlah</label>
                                <input type="number" name="jumlah" class="form-control" step="0.01" min="0" placeholder="Contoh: 35" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Satuan</label>
                                <select name="satuan" class="form-select" required>
                                    <option value="">Pilih satuan</option>
                                    <option value="Sak">Sak</option>
                                    <option value="m3">m³</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Keterangan (Opsional)</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Tambahkan catatan..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-info-circle"></i> Informasi</h5></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--text-muted);">Catat transaksi bahan baku harian.</p>
                <ul style="font-size:13px;color:var(--text-muted);padding-left:18px;">
                    <li><strong>Pembelian</strong>: bahan masuk (stok bertambah)</li>
                    <li><strong>Penggunaan</strong>: bahan dipakai (stok berkurang)</li>
                    <li>Semen dalam satuan <strong>Sak</strong>, Pasir dalam <strong>m³</strong></li>
                    <li>Operator tercatat otomatis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
// Auto-satuan: Semen -> Sak, Pasir -> m3 (disabled agar tidak salah input)
(function () {
    var jenis = document.querySelector('select[name="jenis_bahan"]');
    var satuan = document.querySelector('select[name="satuan"]');
    if (!jenis || !satuan) return;
    function setSatuan() {
        var v = jenis.value;
        if (v === 'Semen') { satuan.value = 'Sak'; satuan.disabled = false; }
        else if (v === 'Pasir') { satuan.value = 'm3'; satuan.disabled = false; }
        else { satuan.value = ''; satuan.disabled = true; }
    }
    jenis.addEventListener('change', setSatuan);
    setSatuan();
})();
</script>

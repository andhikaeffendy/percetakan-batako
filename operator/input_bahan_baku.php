<?php
// operator/input_bahan_baku.php — Input Bahan Baku (Operator)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Input Bahan Baku';
$today = date('Y-m-d');
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal_penggunaan'];
    $jenis = $_POST['jenis_bahan'];
    $jumlah = $_POST['jumlah'];
    $satuan = $_POST['satuan'];
    $keterangan = $_POST['keterangan'] ?? '';
    $operatorId = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO bahan_baku (tanggal_penggunaan, jenis_bahan, jumlah, satuan, keterangan, operator_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tanggal, $jenis, $jumlah, $satuan, $keterangan, $operatorId]);
    $success = true;
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-box-seam"></i> Form Input Bahan Baku</h5></div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> Data bahan baku berhasil disimpan!</div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal Penggunaan</label>
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
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Simpan Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5>📋 Informasi</h5></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--text-muted);">Gunakan form ini untuk mencatat penggunaan bahan baku harian.</p>
                <ul style="font-size:13px;color:var(--text-muted);padding-left:18px;">
                    <li>Semen dicatat dalam satuan <strong>Sak</strong></li>
                    <li>Pasir dicatat dalam satuan <strong>m³</strong></li>
                    <li>Operator tercatat otomatis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<?php
// operator/input_produksi.php — Input Produksi Harian (Operator)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Input Produksi Harian';
$today = date('Y-m-d');
$success = false;

// Get pekerja aktif
$pekerjaList = $db->query("SELECT id, nama_pekerja FROM pekerja WHERE status = 'aktif' ORDER BY nama_pekerja")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('/input_produksi.php');
    $tanggal = $_POST['tanggal_produksi'];
    $ukuran = $_POST['ukuran_batako'];
    $target = $_POST['target_produksi'];
    $realisasi = $_POST['realisasi_produksi'];
    $sakSemen = $_POST['jumlah_sak_semen'];
    $pekerjaId = $_POST['pekerja_id'];
    $operatorId = $_SESSION['user_id'];

    $error = null;
    if ($tanggal === '' || !strtotime($tanggal)) { $error = 'Tanggal tidak valid.'; }
    elseif (!in_array($ukuran, ['standar', 'besar'], true)) { $error = 'Ukuran batako tidak valid.'; }
    elseif (!is_numeric($target) || (float)$target < 0) { $error = 'Target produksi tidak valid.'; }
    elseif (!is_numeric($realisasi) || (float)$realisasi < 0) { $error = 'Realisasi produksi tidak valid.'; }
    elseif ((float)$realisasi > (float)$target) { $error = 'Realisasi tidak boleh melebihi target.'; }
    elseif (!is_numeric($sakSemen) || (float)$sakSemen < 0) { $error = 'Jumlah sak semen tidak valid.'; }
    elseif (!ctype_digit((string)$pekerjaId) || (int)$pekerjaId <= 0) { $error = 'Pekerja tidak valid.'; }
    else {
        $cek = $db->prepare("SELECT id FROM pekerja WHERE id = ? AND status = 'aktif'");
        $cek->execute([(int)$pekerjaId]);
        if (!$cek->fetchColumn()) { $error = 'Pekerja tidak ditemukan atau tidak aktif.'; }
    }
    if (!$error) {
    $stmt = $db->prepare("INSERT INTO produksi (tanggal_produksi, ukuran_batako, target_produksi, realisasi_produksi, jumlah_sak_semen, pekerja_id, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $ukuran, $target, $realisasi, $sakSemen, (int)$pekerjaId, $operatorId]);

        updateStok($db);
        $success = true;
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="page-toolbar">
    <div><h3>Input Produksi</h3><p>Catat target, realisasi, bahan yang digunakan, dan pekerja untuk hari ini.</p></div>
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card form-workspace">
            <div class="card-header"><h5><i class="bi bi-bricks"></i> Detail Produksi Harian</h5></div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> Data produksi harian berhasil disimpan! Stok otomatis diperbarui.</div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal Produksi</label>
                                <input type="date" name="tanggal_produksi" class="form-control date-today" value="<?= $today ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Ukuran Batako</label>
                                <select name="ukuran_batako" class="form-select" required>
                                    <option value="">Pilih ukuran</option>
                                    <option value="standar">Standar</option>
                                    <option value="besar">Besar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Target Produksi</label>
                                <input type="number" name="target_produksi" class="form-control" min="0" placeholder="Contoh: 500" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Realisasi Produksi</label>
                                <input type="number" name="realisasi_produksi" class="form-control" min="0" placeholder="Contoh: 480" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jumlah Sak Semen</label>
                                <input type="number" name="jumlah_sak_semen" class="form-control" step="0.01" min="0" placeholder="Contoh: 15" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Pekerja</label>
                                <select name="pekerja_id" class="form-select" required>
                                    <option value="">Pilih pekerja</option>
                                    <?php foreach ($pekerjaList as $pk): ?>
                                    <option value="<?= $pk['id'] ?>"><?= e($pk['nama_pekerja']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg w-100 w-md-auto">
                                <i class="bi bi-save"></i> Simpan Produksi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5><i class="bi bi-bars"></i> Ringkasan Cepat</h5></div>
            <div class="card-body">
                <?php
                $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as prod, COALESCE(SUM(target_produksi), 0) as target FROM produksi WHERE tanggal_produksi = ?");
                $stmt->execute([$today]);
                $ringkasan = $stmt->fetch();
                $persentase = $ringkasan['target'] > 0 ? round(($ringkasan['prod'] / $ringkasan['target']) * 100, 1) : 0;
                ?>
                <div class="mb-3">
                    <small style="color:var(--text-muted);">Target Hari Ini</small>
                    <div style="font-size:20px;font-weight:700;"><?= number_format($ringkasan['target']) ?></div>
                </div>
                <div class="mb-3">
                    <small style="color:var(--text-muted);">Realisasi Hari Ini</small>
                    <div style="font-size:20px;font-weight:700;color:var(--green);"><?= number_format($ringkasan['prod']) ?></div>
                </div>
                <div>
                    <small style="color:var(--text-muted);">Persentase</small>
                    <div style="font-size:20px;font-weight:700;color:var(--primary);"><?= $persentase ?>%</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-info-circle"></i> Informasi</h5></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--text-muted);">Input data produksi harian sesuai ukuran batako. Stok otomatis bertambah.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

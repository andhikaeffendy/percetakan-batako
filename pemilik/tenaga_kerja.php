<?php
// pemilik/tenaga_kerja.php — CRUD Data Tenaga Kerja (Pemilik)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Data Tenaga Kerja';

// Delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM pekerja WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    redirect('/pemilik/tenaga_kerja.php', 'success', 'Data pekerja berhasil dihapus.');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $stmt = $db->prepare("UPDATE pekerja SET status = CASE WHEN status='aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
    redirect('/pemilik/tenaga_kerja.php', 'success', 'Status pekerja berhasil diubah.');
}

// Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nama = $_POST['nama_pekerja'];
    $tarif = $_POST['tarif_per_sak'];
    $status = $_POST['status'] ?? 'aktif';

    if ($id) {
        $stmt = $db->prepare("UPDATE pekerja SET nama_pekerja=?, tarif_per_sak=?, status=? WHERE id=?");
        $stmt->execute([$nama, $tarif, $status, $id]);
        redirect('/pemilik/tenaga_kerja.php', 'success', 'Data pekerja berhasil diperbarui.');
    } else {
        $stmt = $db->prepare("INSERT INTO pekerja (nama_pekerja, tarif_per_sak, status) VALUES (?, ?, ?)");
        $stmt->execute([$nama, $tarif, $status]);
        redirect('/pemilik/tenaga_kerja.php', 'success', 'Data pekerja berhasil disimpan.');
    }
}

$pekerjaList = $db->query("SELECT * FROM pekerja ORDER BY status ASC, nama_pekerja ASC")->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM pekerja WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue">👷</div>
            <div class="stat-label">Total Pekerja</div>
            <div class="stat-value"><?= count($pekerjaList) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-icon green">✅</div>
        <div class="stat-label">Pekerja Aktif</div>
        <div class="stat-value"><?= count(array_filter($pekerjaList, fn($p) => $p['status'] === 'aktif')) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-people"></i> Data Tenaga Kerja</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalForm">
            <i class="bi bi-plus-lg"></i> Tambah Pekerja
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pekerja</th>
                        <th>Tarif per Sak</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pekerjaList)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data pekerja.</td></tr>
                    <?php else: ?>
                    <?php foreach ($pekerjaList as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($row['nama_pekerja']) ?></strong></td>
                        <td><?= formatRupiah($row['tarif_per_sak']) ?></td>
                        <td>
                            <a href="?toggle=<?= $row['id'] ?>" class="badge <?= $row['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>" style="text-decoration:none;">
                                <?= $row['status'] === 'aktif' ? '✅ Aktif' : '❌ Nonaktif' ?>
                            </a>
                        </td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-delete"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editItem['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editItem ? 'Edit' : 'Tambah' ?> Pekerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Pekerja</label>
                        <input type="text" name="nama_pekerja" class="form-control" value="<?= e($editItem['nama_pekerja'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarif per Sak Semen (Rp)</label>
                        <input type="number" name="tarif_per_sak" class="form-control" min="0" value="<?= e($editItem['tarif_per_sak'] ?? '65000') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="aktif" <?= ($editItem['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($editItem['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editItem): ?>
<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('modalForm')).show(); });</script>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

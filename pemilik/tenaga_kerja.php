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
$totalPekerja = count($pekerjaList);
$aktifPekerja = count(array_filter($pekerjaList, fn($p) => $p['status'] === 'aktif'));
$nonaktifPekerja = $totalPekerja - $aktifPekerja;

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM pekerja WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

function inisialNama(string $nama): string {
    $parts = preg_split('/\s+/', trim($nama));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(mb_substr($nama, 0, 2));
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- HERO -->
<div class="dash-hero">
    <div>
        <h3><i class="bi bi-people-fill" style="color:var(--accent);"></i> Data Tenaga Kerja</h3>
        <p>Kelola pekerja produksi, tarif per sak semen, dan status keaktifan</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="hero-date"><i class="bi bi-person-workspace"></i> <?= $aktifPekerja ?> aktif dari <?= $totalPekerja ?> pekerja</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalForm" data-mode="tambah">
            <i class="bi bi-plus-lg"></i> Tambah Pekerja
        </button>
    </div>
</div>

<!-- KPI ROW -->
<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon cobalt"><i class="bi bi-people"></i></div>
                <span class="kpi-trend trend-flat"><i class="bi bi-person-badge"></i> Total</span>
            </div>
            <div class="kpi-label">Total Pekerja</div>
            <div class="kpi-value"><?= number_format($totalPekerja) ?> <small style="font-size:13px;font-weight:600;color:var(--ink-muted);">orang</small></div>
            <div class="kpi-sub">Terdaftar di sistem</div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon green"><i class="bi bi-person-check"></i></div>
                <span class="kpi-trend trend-up"><i class="bi bi-arrow-up-right"></i> Aktif</span>
            </div>
            <div class="kpi-label">Pekerja Aktif</div>
            <div class="kpi-value"><?= number_format($aktifPekerja) ?> <small style="font-size:13px;font-weight:600;color:var(--ink-muted);">orang</small></div>
            <div class="kpi-sub">Siap menerima produksi</div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon red"><i class="bi bi-person-dash"></i></div>
                <span class="kpi-trend trend-down"><i class="bi bi-arrow-down-right"></i> Nonaktif</span>
            </div>
            <div class="kpi-label">Pekerja Nonaktif</div>
            <div class="kpi-value"><?= number_format($nonaktifPekerja) ?> <small style="font-size:13px;font-weight:600;color:var(--ink-muted);">orang</small></div>
            <div class="kpi-sub">Tidak menerima produksi</div>
        </div>
    </div>
</div>

<!-- DIRECTORY -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-person-lines-fill"></i> Daftar Pekerja</h5>
        <span style="font-size:11px;color:var(--ink-muted);"><?= number_format($totalPekerja) ?> pekerja</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pekerjaList)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-person-plus"></i></div>
                <h5>Belum ada pekerja</h5>
                <p>Tambahkan pekerja pertama untuk mulai mencatat produksi dan gaji.</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalForm" data-mode="tambah">
                    <i class="bi bi-plus-lg"></i> Tambah Pekerja
                </button>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Pekerja</th>
                        <th>Tarif per Sak</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pekerjaList as $i => $row): ?>
                    <tr>
                        <td style="color:var(--ink-muted);font-weight:600;"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="tk-avatar <?= $row['status'] === 'aktif' ? 'tk-avatar-aktif' : 'tk-avatar-nonaktif' ?>">
                                    <?= e(inisialNama($row['nama_pekerja'])) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:var(--ink);font-size:14px;"><?= e($row['nama_pekerja']) ?></div>
                                    <div style="font-size:11px;color:var(--ink-muted);">ID: <?= e($row['id']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700;color:var(--ink);"><?= formatRupiah($row['tarif_per_sak']) ?></div>
                            <div style="font-size:11px;color:var(--ink-muted);">per sak semen</div>
                        </td>
                        <td>
                            <a href="?toggle=<?= $row['id'] ?>" class="badge <?= $row['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>" style="text-decoration:none;" title="Klik untuk ubah status">
                                <i class="bi bi-<?= $row['status'] === 'aktif' ? 'check-circle' : 'x-circle' ?>"></i>
                                <?= $row['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-light btn-sm" title="Edit pekerja" data-bs-toggle="modal" data-bs-target="#modalForm" data-edit-id="<?= $row['id'] ?>">
                                <i class="bi bi-pencil"></i> <span class="d-none d-sm-inline">Edit</span>
                            </a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-delete" title="Hapus pekerja">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="formId" value="<?= $editItem['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill"></i> <span id="modalTitle"><?= $editItem ? 'Edit' : 'Tambah' ?> Pekerja</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Pekerja</label>
                        <input type="text" name="nama_pekerja" id="formNama" class="form-control" value="<?= e($editItem['nama_pekerja'] ?? '') ?>" placeholder="cth: Andi Maulana" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarif per Sak Semen (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:transparent;border:none;box-shadow:var(--neu-pressed-sm);border-radius:10px 0 0 10px;color:var(--ink-muted);font-weight:700;">Rp</span>
                            <input type="number" name="tarif_per_sak" id="formTarif" class="form-control" min="0" value="<?= e($editItem['tarif_per_sak'] ?? '65000') ?>" required>
                        </div>
                        <div style="font-size:11px;color:var(--ink-muted);margin-top:6px;">Tarif dibayarkan per sak semen yang diproduksi.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="formStatus" class="form-select">
                            <option value="aktif" <?= ($editItem['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif — menerima produksi</option>
                            <option value="nonaktif" <?= ($editItem['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif — tidak menerima produksi</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editItem): ?>
<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('modalForm')).show(); });</script>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

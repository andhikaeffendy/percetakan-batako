<?php
// pemilik/pengeluaran.php — CRUD Data Pengeluaran (Pemilik)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Data Pengeluaran';
$today = date('Y-m-d');

// Get kategori list
$kategoriList = $db->query("SELECT id, nama_kategori FROM kategori_pengeluaran WHERE status = 'aktif' ORDER BY nama_kategori")->fetchAll();

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM pengeluaran WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    redirect('/pemilik/pengeluaran.php', 'success', 'Data pengeluaran berhasil dihapus.');
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $tanggal = $_POST['tanggal_pengeluaran'];
    $kategoriId = $_POST['kategori_pengeluaran_id'];
    $nominal = $_POST['nominal'];
    $keterangan = $_POST['keterangan'] ?? '';
    $operatorId = $_SESSION['user_id'];

    if ($id) {
        $stmt = $db->prepare("UPDATE pengeluaran SET tanggal_pengeluaran=?, kategori_pengeluaran_id=?, nominal=?, keterangan=?, operator_id=? WHERE id=?");
        $stmt->execute([$tanggal, $kategoriId, $nominal, $keterangan, $operatorId, $id]);
        redirect('/pemilik/pengeluaran.php', 'success', 'Data pengeluaran berhasil diperbarui.');
    } else {
        $stmt = $db->prepare("INSERT INTO pengeluaran (tanggal_pengeluaran, kategori_pengeluaran_id, nominal, keterangan, operator_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $kategoriId, $nominal, $keterangan, $operatorId]);
        redirect('/pemilik/pengeluaran.php', 'success', 'Data pengeluaran berhasil disimpan.');
    }
}

// Filters
$filterTanggal = $_GET['tanggal'] ?? '';
$filterKategori = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
if ($filterTanggal) { $where .= " AND p.tanggal_pengeluaran = ?"; $params[] = $filterTanggal; }
if ($filterKategori) { $where .= " AND p.kategori_pengeluaran_id = ?"; $params[] = $filterKategori; }
if ($search) { $where .= " AND (k.nama_kategori LIKE ? OR p.keterangan LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM pengeluaran p LEFT JOIN kategori_pengeluaran k ON p.kategori_pengeluaran_id = k.id $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT p.*, k.nama_kategori, u.username as operator_nama FROM pengeluaran p LEFT JOIN kategori_pengeluaran k ON p.kategori_pengeluaran_id = k.id LEFT JOIN users u ON p.operator_id = u.id $where ORDER BY p.tanggal_pengeluaran DESC, p.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$dataList = $stmt->fetchAll();

// Edit
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM pengeluaran WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

// Summary
$stmt = $db->query("SELECT COALESCE(SUM(nominal), 0) as total FROM pengeluaran");
$summary = $stmt->fetch();

include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-wallet2"></i></div>
            <div class="stat-label">Total Pengeluaran</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($summary['total']) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value"><?= number_format($totalRows) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-tags"></i></div>
            <div class="stat-label">Kategori</div>
            <div class="stat-value" style="font-size:14px;"><?= count($kategoriList) ?> aktif</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-cash-stack"></i> Data Pengeluaran</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalForm">
            <i class="bi bi-plus-lg"></i> Tambah Pengeluaran
        </button>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($filterTanggal) ?>">
            </div>
            <div class="col-md-2">
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $filterKategori == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-light btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            <?php if ($filterTanggal || $filterKategori || $search): ?>
            <div class="col-md-2">
                <a href="pengeluaran.php" class="btn btn-outline-primary btn-sm w-100">Reset</a>
            </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr><th>No</th><th>Tanggal</th><th>Kategori</th><th>Nominal</th><th>Keterangan</th><th>Operator</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($dataList)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data pengeluaran.</td></tr>
                    <?php else: foreach ($dataList as $i => $row): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= formatTanggal($row['tanggal_pengeluaran']) ?></td>
                        <td><span class="badge badge-danger"><?= e($row['nama_kategori'] ?? '-') ?></span></td>
                        <td><strong style="color:var(--red);"><?= formatRupiah($row['nominal']) ?></strong></td>
                        <td><?= e($row['keterangan'] ?? '-') ?></td>
                        <td><?= e($row['operator_nama'] ?? '-') ?></td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-delete"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <span><?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?></span>
            <ul class="pagination">
                <?php $qp = http_build_query(array_filter(['tanggal' => $filterTanggal, 'kategori' => $filterKategori, 'search' => $search])); ?>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $qp ?>&page=<?= $page-1 ?>">«</a></li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= $qp ?>&page=<?= $p ?>"><?= $p ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $qp ?>&page=<?= $page+1 ?>">»</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editItem['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editItem ? 'Edit' : 'Tambah' ?> Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal_pengeluaran" class="form-control date-today" value="<?= e($editItem['tanggal_pengeluaran'] ?? $today) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="kategori_pengeluaran_id" class="form-select" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= ($editItem['kategori_pengeluaran_id'] ?? '') == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nominal (Rp)</label>
                        <input type="number" name="nominal" class="form-control" min="0" value="<?= e($editItem['nominal'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= e($editItem['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editItem): ?>
<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('modalForm')).show(); });</script>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

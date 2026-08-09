<?php
// pemilik/penjualan.php — CRUD Data Penjualan (Pemilik)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Data Penjualan';
$today = date('Y-m-d');

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM penjualan WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    updateStok($db);
    redirect('/pemilik/penjualan.php', 'success', 'Data penjualan berhasil dihapus.');
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $tanggal = $_POST['tanggal_penjualan'];
    $ukuran = $_POST['ukuran_batako'];
    $jumlah = $_POST['jumlah_terjual'];
    $harga = $_POST['harga_satuan'];
    $total = $jumlah * $harga;
    $pembeli = $_POST['nama_pembeli'] ?? null;
    $operatorId = $_SESSION['user_id'];

    // Cek stok (dari stok_produk — canonical)
    $stokTersedia = getStok($db, $ukuran);

    // Jika edit, tambahkan kembali jumlah lama ke stok
    if ($id) {
        $stmt = $db->prepare("SELECT jumlah_terjual, ukuran_batako FROM penjualan WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        $stokTersedia += $old['jumlah_terjual'];
    }

    if ($jumlah > $stokTersedia) {
        setFlash('warning', "Stok tidak mencukupi! Stok {$ukuran} tersedia: " . number_format($stokTersedia) . " batako.");
        redirect('/pemilik/penjualan.php');
    }

    if ($id) {
        $stmt = $db->prepare("UPDATE penjualan SET tanggal_penjualan=?, ukuran_batako=?, jumlah_terjual=?, harga_satuan=?, total_penjualan=?, nama_pembeli=?, operator_id=? WHERE id=?");
        $stmt->execute([$tanggal, $ukuran, $jumlah, $harga, $total, $pembeli, $operatorId, $id]);
        updateStok($db);
        redirect('/pemilik/penjualan.php', 'success', 'Data penjualan berhasil diperbarui.');
    } else {
        $stmt = $db->prepare("INSERT INTO penjualan (tanggal_penjualan, ukuran_batako, jumlah_terjual, harga_satuan, total_penjualan, nama_pembeli, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $ukuran, $jumlah, $harga, $total, $pembeli, $operatorId]);
        updateStok($db);
        redirect('/pemilik/penjualan.php', 'success', 'Data penjualan berhasil disimpan.');
    }
}



// Filters
$filterTanggal = $_GET['tanggal'] ?? '';
$filterUkuran = $_GET['ukuran'] ?? '';
$search = $_GET['search'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
if ($filterTanggal) { $where .= " AND pj.tanggal_penjualan = ?"; $params[] = $filterTanggal; }
if ($filterUkuran) { $where .= " AND pj.ukuran_batako = ?"; $params[] = $filterUkuran; }
if ($search) { $where .= " AND (pj.nama_pembeli LIKE ? OR pj.ukuran_batako LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM penjualan pj $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT pj.*, u.username as operator_nama FROM penjualan pj LEFT JOIN users u ON pj.operator_id = u.id $where ORDER BY pj.tanggal_penjualan DESC, pj.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$dataList = $stmt->fetchAll();

// Edit
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM penjualan WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

// Summary
$stmt = $db->query("SELECT COALESCE(SUM(total_penjualan), 0) as total_pendapatan, COALESCE(SUM(jumlah_terjual), 0) as total_terjual FROM penjualan");
$summary = $stmt->fetch();

include __DIR__ . '/../layouts/header.php';
?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($summary['total_pendapatan']) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div class="stat-label">Total Batako Terjual</div>
            <div class="stat-value"><?= number_format($summary['total_terjual']) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-bars"></i></div>
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value"><?= number_format($totalRows) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-cart-check"></i> Data Penjualan</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalForm">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($filterTanggal) ?>">
            </div>
            <div class="col-md-2">
                <select name="ukuran" class="form-select form-select-sm">
                    <option value="">Semua Ukuran</option>
                    <option value="standar" <?= $filterUkuran === 'standar' ? 'selected' : '' ?>>Standar</option>
                    <option value="besar" <?= $filterUkuran === 'besar' ? 'selected' : '' ?>>Besar</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari pembeli...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-light btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            <?php if ($filterTanggal || $filterUkuran || $search): ?>
            <div class="col-md-2">
                <a href="penjualan.php" class="btn btn-outline-primary btn-sm w-100">Reset</a>
            </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Ukuran Batako</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th>Pembeli</th>
                        <th>Operator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dataList)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data penjualan.</td></tr>
                    <?php else: ?>
                    <?php foreach ($dataList as $i => $row): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= formatTanggal($row['tanggal_penjualan']) ?></td>
                        <td><span class="badge badge-primary"><?= e(ucfirst($row['ukuran_batako'])) ?></span></td>
                        <td><?= number_format($row['jumlah_terjual']) ?></td>
                        <td><?= formatRupiah($row['harga_satuan']) ?></td>
                        <td><strong><?= formatRupiah($row['total_penjualan']) ?></strong></td>
                        <td><?= e($row['nama_pembeli'] ?? '-') ?></td>
                        <td><?= e($row['operator_nama'] ?? '-') ?></td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-delete" title="Hapus"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <span>Menampilkan <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> data</span>
            <ul class="pagination">
                <?php $qp = http_build_query(array_filter(['tanggal' => $filterTanggal, 'ukuran' => $filterUkuran, 'search' => $search])); ?>
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

<!-- Modal Form -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editItem['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editItem ? 'Edit' : 'Tambah' ?> Penjualan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Tanggal Penjualan</label>
                        <input type="date" name="tanggal_penjualan" class="form-control date-today" value="<?= e($editItem['tanggal_penjualan'] ?? $today) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ukuran Batako</label>
                        <select name="ukuran_batako" class="form-select" required>
                            <option value="">Pilih ukuran</option>
                            <option value="standar" <?= ($editItem['ukuran_batako'] ?? '') === 'standar' ? 'selected' : '' ?>>Standar</option>
                            <option value="besar" <?= ($editItem['ukuran_batako'] ?? '') === 'besar' ? 'selected' : '' ?>>Besar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Terjual</label>
                        <input type="number" name="jumlah_terjual" id="jumlah_terjual" class="form-control" min="1" value="<?= e($editItem['jumlah_terjual'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" min="0" value="<?= e($editItem['harga_satuan'] ?? '2500') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Penjualan (Rp) — Otomatis</label>
                        <input type="number" name="total_display" id="total_penjualan" class="form-control" readonly style="background:var(--background);font-weight:600;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Pembeli (Opsional)</label>
                        <input type="text" name="nama_pembeli" class="form-control" value="<?= e($editItem['nama_pembeli'] ?? '') ?>">
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

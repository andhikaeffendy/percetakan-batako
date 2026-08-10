<?php
// pemilik/bahan_baku.php — CRUD Bahan Baku (Pemilik)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Data Bahan Baku';
$today = date('Y-m-d');

// Handle Delete (POST)
$deleteId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_method'] ?? '') === 'delete') {
    requireCsrf('/pemilik/bahan_baku.php');
    $deleteId = (int)($_POST['id'] ?? 0);
}
if ($deleteId > 0) {
    $stmt = $db->prepare("DELETE FROM bahan_baku WHERE id = ?");
    $stmt->execute([$deleteId]);
    if ($stmt->rowCount() > 0) {
        updateStokBahan($db);
        redirect('/pemilik/bahan_baku.php', 'success', 'Data bahan baku berhasil dihapus.');
    }
    redirect('/pemilik/bahan_baku.php', 'warning', 'Data bahan baku tidak ditemukan.');
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('/bahan_baku.php');
    $rawId = trim((string)($_POST['id'] ?? ''));
    if ($rawId !== '' && !ctype_digit($rawId)) {
        redirect('/pemilik/bahan_baku.php', 'warning', 'Data tidak valid.');
    }
    $id = $rawId === '' ? 0 : (int)$rawId;
    $tanggal = $_POST['tanggal_penggunaan'];
    $jenisTransaksi = ($_POST['jenis_transaksi'] ?? 'penggunaan') === 'pembelian' ? 'pembelian' : 'penggunaan';
    $jenis = $_POST['jenis_bahan'];
    $jumlah = $_POST['jumlah'];
    $satuan = $_POST['satuan'];
    $keterangan = $_POST['keterangan'] ?? '';
    $operatorId = $_SESSION['user_id'];

    // Validasi backend: Semen wajib Sak, Pasir wajib m3
    $errSatuan = validasiSatuanBahan((string)$jenis, (string)$satuan);
    if ($errSatuan) {
        redirect('/pemilik/bahan_baku.php', 'error', $errSatuan);
    }

    if ($id) {
        $stmt = $db->prepare("UPDATE bahan_baku SET tanggal_penggunaan=?, jenis_transaksi=?, jenis_bahan=?, jumlah=?, satuan=?, keterangan=?, operator_id=? WHERE id=?");
        $stmt->execute([$tanggal, $jenisTransaksi, $jenis, $jumlah, $satuan, $keterangan, $operatorId, $id]);
        // Hitung ulang SEMUA jenis bahan (lama + baru) agar stok tidak stale saat edit lintas jenis
        updateStokBahan($db);
        if ($stmt->rowCount() === 0) {
            $chk = $db->prepare("SELECT 1 FROM bahan_baku WHERE id = ?");
            $chk->execute([$id]);
            if (!$chk->fetchColumn()) {
                redirect('/pemilik/bahan_baku.php', 'warning', 'Data bahan baku tidak ditemukan.');
            }
        }
        redirect('/pemilik/bahan_baku.php', 'success', 'Data bahan baku berhasil diperbarui.');
    } else {
        $stmt = $db->prepare("INSERT INTO bahan_baku (tanggal_penggunaan, jenis_transaksi, jenis_bahan, jumlah, satuan, keterangan, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $jenisTransaksi, $jenis, $jumlah, $satuan, $keterangan, $operatorId]);
        updateStokBahan($db, $jenis);
        redirect('/pemilik/bahan_baku.php', 'success', 'Data bahan baku berhasil disimpan.');
    }
}

// Filters
$filterTanggal = $_GET['tanggal'] ?? '';
$filterJenis = $_GET['jenis'] ?? '';
$filterTransaksi = $_GET['transaksi'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Query
$where = "WHERE 1=1";
$params = [];
if ($filterTanggal) { $where .= " AND b.tanggal_penggunaan = ?"; $params[] = $filterTanggal; }
if ($filterJenis) { $where .= " AND b.jenis_bahan = ?"; $params[] = $filterJenis; }
if ($filterTransaksi) { $where .= " AND b.jenis_transaksi = ?"; $params[] = $filterTransaksi; }
if ($search) { $where .= " AND (b.jenis_bahan LIKE ? OR b.keterangan LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM bahan_baku b $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT b.*, u.username as operator_nama FROM bahan_baku b LEFT JOIN users u ON b.operator_id = u.id $where ORDER BY b.tanggal_penggunaan DESC, b.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$dataList = $stmt->fetchAll();

// Get item for edit
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM bahan_baku WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch();
    if (!$editItem) {
        redirect('/pemilik/bahan_baku.php', 'warning', 'Data bahan baku tidak ditemukan.');
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-box-seam"></i> Data Bahan Baku</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href='bahan_baku.php'">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter -->
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($filterTanggal) ?>" placeholder="Filter Tanggal">
            </div>
            <div class="col-md-2">
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <option value="Semen" <?= $filterJenis === 'Semen' ? 'selected' : '' ?>>Semen</option>
                    <option value="Pasir" <?= $filterJenis === 'Pasir' ? 'selected' : '' ?>>Pasir</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="transaksi" class="form-select form-select-sm">
                    <option value="">Semua Transaksi</option>
                    <option value="pembelian" <?= $filterTransaksi === 'pembelian' ? 'selected' : '' ?>>Pembelian</option>
                    <option value="penggunaan" <?= $filterTransaksi === 'penggunaan' ? 'selected' : '' ?>>Penggunaan</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari...">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-light btn-sm w-100"><i class="bi bi-funnel"></i></button>
            </div>
            <?php if ($filterTanggal || $filterJenis || $filterTransaksi || $search): ?>
            <div class="col-md-1">
                <a href="bahan_baku.php" class="btn btn-outline-primary btn-sm w-100">Reset</a>
            </div>
            <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jenis Transaksi</th>
                        <th>Jenis Bahan</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Keterangan</th>
                        <th>Operator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dataList)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data bahan baku.</td></tr>
                    <?php else: ?>
                    <?php foreach ($dataList as $i => $row): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= formatTanggal($row['tanggal_penggunaan']) ?></td>
                        <td>
                            <?php if (($row['jenis_transaksi'] ?? 'penggunaan') === 'pembelian'): ?>
                            <span class="badge badge-success">Pembelian</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Penggunaan</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $row['jenis_bahan'] === 'Semen' ? 'badge-primary' : 'badge-warning' ?>"><?= e($row['jenis_bahan']) ?></span></td>
                        <td><?= number_format($row['jumlah'], 2) ?></td>
                        <td><?= e($row['satuan']) ?></td>
                        <td><?= e($row['keterangan'] ?? '-') ?></td>
                        <td><?= e($row['operator_nama'] ?? '-') ?></td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="_method" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm btn-delete" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <span>Menampilkan <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> data</span>
            <ul class="pagination">
                <?php $qp = http_build_query(array_filter(['tanggal' => $filterTanggal, 'jenis' => $filterJenis, 'transaksi' => $filterTransaksi, 'search' => $search])); ?>
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
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" id="editId" value="<?= $editItem['id'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editItem ? 'Edit' : 'Tambah' ?> Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Tanggal Transaksi</label>
                        <input type="date" name="tanggal_penggunaan" class="form-control date-today" value="<?= e($editItem['tanggal_penggunaan'] ?? $today) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Transaksi</label>
                        <select name="jenis_transaksi" class="form-select" required>
                            <option value="pembelian" <?= ($editItem['jenis_transaksi'] ?? '') === 'pembelian' ? 'selected' : '' ?>>Pembelian (stok masuk)</option>
                            <option value="penggunaan" <?= ($editItem['jenis_transaksi'] ?? 'penggunaan') === 'penggunaan' ? 'selected' : '' ?>>Penggunaan (stok keluar)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Bahan</label>
                        <select name="jenis_bahan" class="form-select" required>
                            <option value="">Pilih jenis bahan</option>
                            <option value="Semen" <?= ($editItem['jenis_bahan'] ?? '') === 'Semen' ? 'selected' : '' ?>>Semen</option>
                            <option value="Pasir" <?= ($editItem['jenis_bahan'] ?? '') === 'Pasir' ? 'selected' : '' ?>>Pasir</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" step="0.01" min="0" value="<?= e($editItem['jumlah'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <select name="satuan" class="form-select" required>
                            <option value="">Pilih satuan</option>
                            <option value="Sak" <?= ($editItem['satuan'] ?? '') === 'Sak' ? 'selected' : '' ?>>Sak</option>
                            <option value="m3" <?= ($editItem['satuan'] ?? '') === 'm3' ? 'selected' : '' ?>>m³</option>
                        </select>
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

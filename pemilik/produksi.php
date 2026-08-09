<?php
// pemilik/produksi.php — CRUD Data Produksi (Pemilik)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Data Produksi';
$today = date('Y-m-d');

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM produksi WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    updateStok($db);
    redirect('/pemilik/produksi.php', 'success', 'Data produksi berhasil dihapus.');
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $tanggal = $_POST['tanggal_produksi'];
    $ukuran = $_POST['ukuran_batako'];
    $target = $_POST['target_produksi'];
    $realisasi = $_POST['realisasi_produksi'];
    $sakSemen = $_POST['jumlah_sak_semen'];
    $pekerjaId = $_POST['pekerja_id'];
    $operatorId = $_SESSION['user_id'];

    if ($id) {
        $stmt = $db->prepare("UPDATE produksi SET tanggal_produksi=?, ukuran_batako=?, target_produksi=?, realisasi_produksi=?, jumlah_sak_semen=?, pekerja_id=?, operator_id=? WHERE id=?");
        $stmt->execute([$tanggal, $ukuran, $target, $realisasi, $sakSemen, $pekerjaId, $operatorId, $id]);
        updateStok($db);
        redirect('/pemilik/produksi.php', 'success', 'Data produksi berhasil diperbarui.');
    } else {
        $stmt = $db->prepare("INSERT INTO produksi (tanggal_produksi, ukuran_batako, target_produksi, realisasi_produksi, jumlah_sak_semen, pekerja_id, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $ukuran, $target, $realisasi, $sakSemen, $pekerjaId, $operatorId]);
        updateStok($db);
        redirect('/pemilik/produksi.php', 'success', 'Data produksi berhasil disimpan.');
    }
}

// Handle Export
if (isset($_GET['export'])) {
    // Build export query (no pagination)
    $exportWhere = "WHERE 1=1";
    $exportParams = [];
    $filterTanggalExp = $_GET['tanggal'] ?? '';
    $filterUkuranExp = $_GET['ukuran'] ?? '';
    $filterPekerjaExp = $_GET['pekerja'] ?? '';
    $searchExp = $_GET['search'] ?? '';
    if ($filterTanggalExp) { $exportWhere .= " AND p.tanggal_produksi = ?"; $exportParams[] = $filterTanggalExp; }
    if ($filterUkuranExp) { $exportWhere .= " AND p.ukuran_batako = ?"; $exportParams[] = $filterUkuranExp; }
    if ($filterPekerjaExp) { $exportWhere .= " AND p.pekerja_id = ?"; $exportParams[] = $filterPekerjaExp; }
    if ($searchExp) { $exportWhere .= " AND (p.ukuran_batako LIKE ? OR pk.nama_pekerja LIKE ?)"; $exportParams[] = "%$searchExp%"; $exportParams[] = "%$searchExp%"; }

    $stmt = $db->prepare("SELECT p.*, pk.nama_pekerja, u.username as operator_nama FROM produksi p LEFT JOIN pekerja pk ON p.pekerja_id = pk.id LEFT JOIN users u ON p.operator_id = u.id $exportWhere ORDER BY p.tanggal_produksi DESC, p.id DESC");
    $stmt->execute($exportParams);
    $exportData = $stmt->fetchAll();

    if ($_GET['export'] === 'excel') {
        // Export Excel menggunakan PhpSpreadsheet
        require_once __DIR__ . '/../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Produksi');

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Tanggal');
        $sheet->setCellValue('C1', 'Ukuran Batako');
        $sheet->setCellValue('D1', 'Target');
        $sheet->setCellValue('E1', 'Realisasi');
        $sheet->setCellValue('F1', 'Sak Semen');
        $sheet->setCellValue('G1', 'Pekerja');
        $sheet->setCellValue('H1', 'Operator');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($exportData as $i => $d) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $d['tanggal_produksi']);
            $sheet->setCellValue('C' . $row, ucfirst($d['ukuran_batako']));
            $sheet->setCellValue('D' . $row, $d['target_produksi']);
            $sheet->setCellValue('E' . $row, $d['realisasi_produksi']);
            $sheet->setCellValue('F' . $row, $d['jumlah_sak_semen']);
            $sheet->setCellValue('G' . $row, $d['nama_pekerja'] ?? '-');
            $sheet->setCellValue('H' . $row, $d['operator_nama'] ?? '-');
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="data_produksi_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    if ($_GET['export'] === 'pdf') {
        // Export PDF menggunakan Dompdf
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
        @ini_set('display_errors', '0');
        require_once __DIR__ . '/../vendor/autoload.php';
        $html = '<html><head><meta charset="utf-8"><style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h2 { text-align: center; margin-bottom: 5px; }
            p { text-align: center; color: #666; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #1E293B; color: white; padding: 8px; text-align: left; }
            td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
        </style></head><body>';
        $html .= '<h2>Data Produksi</h2>';
        $html .= '<p>Percetakan Batako Maros — Ambon, Maluku</p>';
        $html .= '<table><thead><tr>';
        $html .= '<th>No</th><th>Tanggal</th><th>Ukuran</th><th>Target</th><th>Realisasi</th><th>Sak Semen</th><th>Pekerja</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($exportData as $i => $d) {
            $html .= '<tr>';
            $html .= '<td>' . ($i + 1) . '</td>';
            $html .= '<td>' . $d['tanggal_produksi'] . '</td>';
            $html .= '<td>' . ucfirst($d['ukuran_batako']) . '</td>';
            $html .= '<td>' . number_format($d['target_produksi']) . '</td>';
            $html .= '<td>' . number_format($d['realisasi_produksi']) . '</td>';
            $html .= '<td>' . number_format($d['jumlah_sak_semen'], 2) . '</td>';
            $html .= '<td>' . ($d['nama_pekerja'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('data_produksi_' . date('Ymd') . '.pdf', ['Attachment' => true]);
        exit;
    }
}

// Filters
$filterTanggal = $_GET['tanggal'] ?? '';
$filterUkuran = $_GET['ukuran'] ?? '';
$filterPekerja = $_GET['pekerja'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Query
$where = "WHERE 1=1";
$params = [];
if ($filterTanggal) { $where .= " AND p.tanggal_produksi = ?"; $params[] = $filterTanggal; }
if ($filterUkuran) { $where .= " AND p.ukuran_batako = ?"; $params[] = $filterUkuran; }
if ($filterPekerja) { $where .= " AND p.pekerja_id = ?"; $params[] = $filterPekerja; }
if ($search) { $where .= " AND (p.ukuran_batako LIKE ? OR pk.nama_pekerja LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM produksi p LEFT JOIN pekerja pk ON p.pekerja_id = pk.id $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT p.*, pk.nama_pekerja, u.username as operator_nama FROM produksi p LEFT JOIN pekerja pk ON p.pekerja_id = pk.id LEFT JOIN users u ON p.operator_id = u.id $where ORDER BY p.tanggal_produksi DESC, p.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$dataList = $stmt->fetchAll();

// Pekerja list for filter & form
$pekerjaList = $db->query("SELECT id, nama_pekerja FROM pekerja WHERE status = 'aktif' ORDER BY nama_pekerja")->fetchAll();

// Edit item
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM produksi WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-building"></i> Data Produksi</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalForm">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </button>
            <a href="produksi.php?export=excel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
            <a href="produksi.php?export=pdf" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Cetak PDF</a>
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
            <div class="col-md-2">
                <select name="pekerja" class="form-select form-select-sm">
                    <option value="">Semua Pekerja</option>
                    <?php foreach ($pekerjaList as $pk): ?>
                    <option value="<?= $pk['id'] ?>" <?= $filterPekerja == $pk['id'] ? 'selected' : '' ?>><?= e($pk['nama_pekerja']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-light btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            <?php if ($filterTanggal || $filterUkuran || $filterPekerja || $search): ?>
            <div class="col-md-2">
                <a href="produksi.php" class="btn btn-outline-primary btn-sm w-100">Reset</a>
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
                        <th>Target</th>
                        <th>Realisasi</th>
                        <th>Sak Semen</th>
                        <th>Pekerja</th>
                        <th>Operator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dataList)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data produksi.</td></tr>
                    <?php else: ?>
                    <?php foreach ($dataList as $i => $row): ?>
                    <?php $selisih = $row['realisasi_produksi'] - $row['target_produksi']; ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= formatTanggal($row['tanggal_produksi']) ?></td>
                        <td><span class="badge badge-primary"><?= e(ucfirst($row['ukuran_batako'])) ?></span></td>
                        <td><?= number_format($row['target_produksi']) ?></td>
                        <td>
                            <?= number_format($row['realisasi_produksi']) ?>
                            <?php if ($selisih != 0): ?>
                            <span class="badge <?= $selisih >= 0 ? 'badge-success' : 'badge-danger' ?> ms-1"><?= $selisih >= 0 ? '+' : '' ?><?= number_format($selisih) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($row['jumlah_sak_semen'], 2) ?></td>
                        <td><?= e($row['nama_pekerja'] ?? '-') ?></td>
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
                <?php $qp = http_build_query(array_filter(['tanggal' => $filterTanggal, 'ukuran' => $filterUkuran, 'pekerja' => $filterPekerja, 'search' => $search])); ?>
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
                    <h5 class="modal-title"><?= $editItem ? 'Edit' : 'Tambah' ?> Produksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Tanggal Produksi</label>
                        <input type="date" name="tanggal_produksi" class="form-control date-today" value="<?= e($editItem['tanggal_produksi'] ?? $today) ?>" required>
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
                        <label class="form-label">Target Produksi</label>
                        <input type="number" name="target_produksi" class="form-control" min="0" value="<?= e($editItem['target_produksi'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Realisasi Produksi</label>
                        <input type="number" name="realisasi_produksi" class="form-control" min="0" value="<?= e($editItem['realisasi_produksi'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Sak Semen</label>
                        <input type="number" name="jumlah_sak_semen" class="form-control" step="0.01" min="0" value="<?= e($editItem['jumlah_sak_semen'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pekerja</label>
                        <select name="pekerja_id" class="form-select" required>
                            <option value="">Pilih pekerja</option>
                            <?php foreach ($pekerjaList as $pk): ?>
                            <option value="<?= $pk['id'] ?>" <?= ($editItem['pekerja_id'] ?? '') == $pk['id'] ? 'selected' : '' ?>><?= e($pk['nama_pekerja']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

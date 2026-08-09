<?php
// pemilik/persediaan.php — Halaman Persediaan (Pemilik) — Revisi Dosen 10-14, 17
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Persediaan';

// Sinkronkan ulang sebelum tampil agar selalu akurat dari transaksi
updateSemuaStok($db);

// Data stok bahan baku
$stmtBahan = $db->query("SELECT jenis_bahan, jumlah, satuan, status, updated_at FROM stok_bahan_baku ORDER BY FIELD(jenis_bahan,'Semen','Pasir')");
$stokBahanList = $stmtBahan->fetchAll();
$stokBahanMap = [];
foreach ($stokBahanList as $row) { $stokBahanMap[$row['jenis_bahan']] = $row; }

// Data stok produk (dengan total produksi & terjual)
$stmtProduk = $db->query("
    SELECT sp.ukuran_batako, sp.jumlah_stok, sp.status, sp.updated_at,
           COALESCE((SELECT SUM(realisasi_produksi) FROM produksi pr WHERE pr.ukuran_batako = sp.ukuran_batako), 0) AS total_produksi,
           COALESCE((SELECT SUM(jumlah_terjual) FROM penjualan pj WHERE pj.ukuran_batako = sp.ukuran_batako), 0) AS total_terjual
    FROM stok_produk sp
    ORDER BY FIELD(sp.ukuran_batako,'standar','besar')
");
$stokProdukList = $stmtProduk->fetchAll();
$stokProdukMap = [];
foreach ($stokProdukList as $row) { $stokProdukMap[$row['ukuran_batako']] = $row; }

$semen = $stokBahanMap['Semen'] ?? ['jumlah' => 0, 'satuan' => 'Sak', 'status' => 'Habis', 'updated_at' => null];
$pasir = $stokBahanMap['Pasir'] ?? ['jumlah' => 0, 'satuan' => 'm3', 'status' => 'Habis', 'updated_at' => null];
$stokStandar = $stokProdukMap['standar'] ?? ['jumlah_stok' => 0, 'status' => 'Habis', 'updated_at' => null, 'total_produksi' => 0, 'total_terjual' => 0];
$stokBesar = $stokProdukMap['besar'] ?? ['jumlah_stok' => 0, 'status' => 'Habis', 'updated_at' => null, 'total_produksi' => 0, 'total_terjual' => 0];

// Export
if (isset($_GET['export'])) {
    if ($_GET['export'] === 'excel') {
        require_once __DIR__ . '/../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok Bahan Baku');
        $sheet->setCellValue('A1', 'No'); $sheet->setCellValue('B1', 'Jenis Bahan'); $sheet->setCellValue('C1', 'Stok Saat Ini'); $sheet->setCellValue('D1', 'Satuan'); $sheet->setCellValue('E1', 'Status'); $sheet->setCellValue('F1', 'Terakhir Diperbarui');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $r = 2;
        foreach ($stokBahanList as $i => $d) {
            $sheet->setCellValue('A'.$r, $i+1); $sheet->setCellValue('B'.$r, $d['jenis_bahan']); $sheet->setCellValue('C'.$r, $d['jumlah']); $sheet->setCellValue('D'.$r, $d['satuan']); $sheet->setCellValue('E'.$r, $d['status']); $sheet->setCellValue('F'.$r, $d['updated_at'] ?? '-');
            $r++;
        }
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Stok Produk');
        $sheet2->setCellValue('A1', 'No'); $sheet2->setCellValue('B1', 'Ukuran Batako'); $sheet2->setCellValue('C1', 'Total Diproduksi'); $sheet2->setCellValue('D1', 'Total Terjual'); $sheet2->setCellValue('E1', 'Stok Akhir'); $sheet2->setCellValue('F1', 'Status'); $sheet2->setCellValue('G1', 'Terakhir Diperbarui');
        $sheet2->getStyle('A1:G1')->getFont()->setBold(true);
        $r = 2;
        foreach ($stokProdukList as $i => $d) {
            $sheet2->setCellValue('A'.$r, $i+1); $sheet2->setCellValue('B'.$r, ucfirst($d['ukuran_batako'])); $sheet2->setCellValue('C'.$r, $d['total_produksi']); $sheet2->setCellValue('D'.$r, $d['total_terjual']); $sheet2->setCellValue('E'.$r, $d['jumlah_stok']); $sheet2->setCellValue('F'.$r, $d['status']); $sheet2->setCellValue('G'.$r, $d['updated_at'] ?? '-');
            $r++;
        }
        foreach (range('A', 'G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); $sheet2->getColumnDimension($col)->setAutoSize(true); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="persediaan_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    if ($_GET['export'] === 'pdf') {
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
        $html .= '<h2>Laporan Persediaan</h2>';
        $html .= '<p>Percetakan Batako Maros — Ambon, Maluku</p>';
        $html .= '<h3>Stok Bahan Baku</h3>';
        $html .= '<table><thead><tr><th>No</th><th>Jenis Bahan</th><th>Stok Saat Ini</th><th>Satuan</th><th>Status</th></tr></thead><tbody>';
        foreach ($stokBahanList as $i => $d) {
            $html .= '<tr><td>' . ($i+1) . '</td><td>' . $d['jenis_bahan'] . '</td><td>' . number_format((float)$d['jumlah'], 2) . '</td><td>' . $d['satuan'] . '</td><td>' . $d['status'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<h3>Stok Produk</h3>';
        $html .= '<table><thead><tr><th>No</th><th>Ukuran Batako</th><th>Total Diproduksi</th><th>Total Terjual</th><th>Stok Akhir</th><th>Status</th></tr></thead><tbody>';
        foreach ($stokProdukList as $i => $d) {
            $html .= '<tr><td>' . ($i+1) . '</td><td>' . ucfirst($d['ukuran_batako']) . '</td><td>' . number_format($d['total_produksi']) . '</td><td>' . number_format($d['total_terjual']) . '</td><td>' . number_format($d['jumlah_stok']) . '</td><td>' . $d['status'] . '</td></tr>';
        }
        $html .= '</tbody></table></body></html>';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('persediaan_' . date('Ymd') . '.pdf', ['Attachment' => true]);
        exit;
    }
}

// Data grafik: 7 hari produksi vs penjualan per ukuran + stok akhir
$chartLabels = [];
$chartProdStandar = []; $chartProdBesar = [];
$chartJualStandar = []; $chartJualBesar = [];
$chartStokStandar = []; $chartStokBesar = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($tgl));
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi),0) FROM produksi WHERE tanggal_produksi = ? AND ukuran_batako = 'standar'");
    $stmt->execute([$tgl]); $chartProdStandar[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi),0) FROM produksi WHERE tanggal_produksi = ? AND ukuran_batako = 'besar'");
    $stmt->execute([$tgl]); $chartProdBesar[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual),0) FROM penjualan WHERE tanggal_penjualan = ? AND ukuran_batako = 'standar'");
    $stmt->execute([$tgl]); $chartJualStandar[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual),0) FROM penjualan WHERE tanggal_penjualan = ? AND ukuran_batako = 'besar'");
    $stmt->execute([$tgl]); $chartJualBesar[] = (int)$stmt->fetchColumn();
    // Stok akhir per tanggal = seluruh produksi sampai tanggal itu dikurangi seluruh penjualan sampai tanggal itu
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi),0) FROM produksi WHERE tanggal_produksi <= ? AND ukuran_batako = 'standar'");
    $stmt->execute([$tgl]); $prodSampai = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual),0) FROM penjualan WHERE tanggal_penjualan <= ? AND ukuran_batako = 'standar'");
    $stmt->execute([$tgl]); $jualSampai = (int)$stmt->fetchColumn();
    $chartStokStandar[] = max(0, $prodSampai - $jualSampai);
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi),0) FROM produksi WHERE tanggal_produksi <= ? AND ukuran_batako = 'besar'");
    $stmt->execute([$tgl]); $prodSampai = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual),0) FROM penjualan WHERE tanggal_penjualan <= ? AND ukuran_batako = 'besar'");
    $stmt->execute([$tgl]); $jualSampai = (int)$stmt->fetchColumn();
    $chartStokBesar[] = max(0, $prodSampai - $jualSampai);
}
$chartJSON = json_encode($chartLabels);
$chartProdS = json_encode($chartProdStandar); $chartProdB = json_encode($chartProdBesar);
$chartJualS = json_encode($chartJualStandar); $chartJualB = json_encode($chartJualBesar);
$chartStokS = json_encode($chartStokStandar); $chartStokB = json_encode($chartStokBesar);

include __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-boxes"></i> Persediaan</h4>
        <small class="text-muted">Pantau stok bahan baku dan produk jadi secara real-time</small>
    </div>
    <div class="d-flex gap-2">
        <a href="persediaan.php?export=excel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        <a href="persediaan.php?export=pdf" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Cetak PDF</a>
    </div>
</div>

<!-- Kartu Ringkasan -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-success"><i class="bi bi-box-seam"></i></div>
            <div class="stat-content">
                <span class="stat-label">Stok Semen</span>
                <span class="stat-value"><?= number_format((float)$semen['jumlah'], 2) ?> <small>Sak</small></span>
                <span class="stat-detail">
                    <span class="badge <?= $semen['status'] === 'Aman' ? 'badge-success' : ($semen['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($semen['status']) ?></span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-info"><i class="bi bi-box-seam"></i></div>
            <div class="stat-content">
                <span class="stat-label">Stok Pasir</span>
                <span class="stat-value"><?= number_format((float)$pasir['jumlah'], 2) ?> <small>m³</small></span>
                <span class="stat-detail">
                    <span class="badge <?= $pasir['status'] === 'Aman' ? 'badge-success' : ($pasir['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($pasir['status']) ?></span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-primary"><i class="bi bi-grid-3x3-gap"></i></div>
            <div class="stat-content">
                <span class="stat-label">Stok Batako Standar</span>
                <span class="stat-value"><?= number_format((int)$stokStandar['jumlah_stok']) ?> <small>pcs</small></span>
                <span class="stat-detail">
                    <span class="badge <?= $stokStandar['status'] === 'Aman' ? 'badge-success' : ($stokStandar['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($stokStandar['status']) ?></span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-warning"><i class="bi bi-grid-1x2"></i></div>
            <div class="stat-content">
                <span class="stat-label">Stok Batako Besar</span>
                <span class="stat-value"><?= number_format((int)$stokBesar['jumlah_stok']) ?> <small>pcs</small></span>
                <span class="stat-detail">
                    <span class="badge <?= $stokBesar['status'] === 'Aman' ? 'badge-success' : ($stokBesar['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($stokBesar['status']) ?></span>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Tabel Stok Bahan Baku -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-box-seam"></i> Stok Bahan Baku</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Jenis Bahan</th>
                                <th>Stok Saat Ini</th>
                                <th>Satuan</th>
                                <th>Status</th>
                                <th>Terakhir Diperbarui</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stokBahanList as $d): ?>
                            <tr>
                                <td><span class="badge <?= $d['jenis_bahan'] === 'Semen' ? 'badge-primary' : 'badge-warning' ?>"><?= e($d['jenis_bahan']) ?></span></td>
                                <td><?= number_format((float)$d['jumlah'], 2) ?></td>
                                <td><?= $d['satuan'] === 'm3' ? 'm³' : e($d['satuan']) ?></td>
                                <td>
                                    <span class="badge <?= $d['status'] === 'Aman' ? 'badge-success' : ($d['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($d['status']) ?></span>
                                </td>
                                <td class="text-muted small"><?= $d['updated_at'] ? date('d/m/Y H:i', strtotime($d['updated_at'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Tabel Stok Produk -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5><i class="bi bi-grid-3x3-gap"></i> Stok Produk</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Ukuran</th>
                                <th>Diproduksi</th>
                                <th>Terjual</th>
                                <th>Stok Akhir</th>
                                <th>Status</th>
                                <th>Terakhir Diperbarui</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stokProdukList as $d): ?>
                            <tr>
                                <td><span class="badge badge-primary"><?= e(ucfirst($d['ukuran_batako'])) ?></span></td>
                                <td><?= number_format((int)$d['total_produksi']) ?></td>
                                <td><?= number_format((int)$d['total_terjual']) ?></td>
                                <td><strong><?= number_format((int)$d['jumlah_stok']) ?></strong></td>
                                <td>
                                    <span class="badge <?= $d['status'] === 'Aman' ? 'badge-success' : ($d['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($d['status']) ?></span>
                                </td>
                                <td class="text-muted small"><?= $d['updated_at'] ? date('d/m/Y H:i', strtotime($d['updated_at'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-bar-chart"></i> Stok Bahan Baku</h5></div>
            <div class="card-body">
                <canvas id="chartBahan" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-bar-chart"></i> Produksi vs Penjualan (7 Hari)</h5></div>
            <div class="card-body">
                <canvas id="chartProduk" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Tema chart konsisten — Neo-Industrial palette
Chart.defaults.font.family = "'Inter', -apple-system, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#5A636E';
Chart.defaults.borderColor = 'rgba(39,49,58,0.08)';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(35,42,49,0.92)';
Chart.defaults.plugins.tooltip.titleColor = '#fff';
Chart.defaults.plugins.tooltip.bodyColor = '#E8E3DA';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;

// ————— Format angka konsisten: Rp utuh (tanpa desimal) —————
const fmtRp = (v) => 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
const fmtNum = (v) => Number(v).toLocaleString('id-ID');

// Axis ticks: hindari desimal & label miring saat padat
Chart.defaults.scales.linear.ticks = {
    callback(value) { return (Math.round(value * 10) / 10) % 1 === 0 ? value : ''; },
    maxTicksLimit: 6
};
Chart.defaults.scales.category.ticks = {
    autoSkip: true,
    maxRotation: 0,
    minRotation: 0,
    autoSkipPadding: 18
};

const stokBahanData = {
    Semen: <?= json_encode((float)$semen['jumlah']) ?>,
    Pasir: <?= json_encode((float)$pasir['jumlah']) ?>
};
new Chart(document.getElementById('chartBahan'), {
    type: 'bar',
    data: {
        labels: ['Semen (Sak)', 'Pasir (m³)'],
        datasets: [{
            label: 'Stok Saat Ini',
            data: [stokBahanData.Semen, stokBahanData.Pasir],
            backgroundColor: ['#C65A32', '#B67A12'],
            borderRadius: 8, maxBarThickness: 60
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Stok: ' + Number(ctx.parsed.y).toLocaleString('id-ID') + (ctx.dataIndex === 0 ? ' Sak' : ' m³') } }
        },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(39,49,58,0.06)' } } }
    }
});
new Chart(document.getElementById('chartProduk'), {
    type: 'line',
    data: {
        labels: <?= $chartJSON ?>,
        datasets: [
            { label: 'Produksi Standar', data: <?= $chartProdS ?>, borderColor: '#2F67C7', backgroundColor: 'rgba(47,103,199,0.06)', fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5, spanGaps: true },
            { label: 'Produksi Besar', data: <?= $chartProdB ?>, borderColor: '#24539F', backgroundColor: 'transparent', tension: 0.35, borderWidth: 2, borderDash: [6, 4], pointRadius: 0, pointHoverRadius: 5, spanGaps: true },
            { label: 'Penjualan Standar', data: <?= $chartJualS ?>, borderColor: '#C65A32', backgroundColor: 'transparent', tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, spanGaps: true },
            { label: 'Penjualan Besar', data: <?= $chartJualB ?>, borderColor: '#A64A28', backgroundColor: 'transparent', tension: 0.35, borderWidth: 2, borderDash: [6, 4], pointRadius: 0, pointHoverRadius: 5, spanGaps: true },
            { label: 'Stok Akhir Standar', data: <?= $chartStokS ?>, borderColor: '#287A5A', backgroundColor: 'rgba(40,122,90,0.05)', fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5, spanGaps: true },
            { label: 'Stok Akhir Besar', data: <?= $chartStokB ?>, borderColor: '#1E6247', backgroundColor: 'transparent', tension: 0.35, borderWidth: 2, borderDash: [3, 3], pointRadius: 0, pointHoverRadius: 5, spanGaps: true }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + Number(ctx.parsed.y).toLocaleString('id-ID') + ' pcs' } }
        },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(39,49,58,0.06)' } } },
        interaction: { mode: 'index', intersect: false }
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

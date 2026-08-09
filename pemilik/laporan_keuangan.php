<?php
// pemilik/laporan_keuangan.php — Laporan Keuangan
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Laporan Keuangan';

$periodeAwal = $_GET['periode_awal'] ?? date('Y-m-01');
$periodeAkhir = $_GET['periode_akhir'] ?? date('Y-m-d');
$ukuran = $_GET['ukuran'] ?? '';

$ukuranWhere = $ukuran ? 'AND ukuran_batako = ?' : '';
$ukuranParam = $ukuran ? [$ukuran] : [];

// Summary Penjualan
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(jumlah_terjual), 0) as total_terjual,
    COALESCE(SUM(total_penjualan), 0) as total_pendapatan,
    COUNT(*) as total_transaksi
    FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ? $ukuranWhere");
$stmt->execute(array_merge([$periodeAwal, $periodeAkhir], $ukuranParam));
$summary = $stmt->fetch();

// Pengeluaran
$stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) as total_pengeluaran FROM pengeluaran WHERE tanggal_pengeluaran BETWEEN ? AND ?");
$stmt->execute([$periodeAwal, $periodeAkhir]);
$totalPengeluaran = (int)$stmt->fetchColumn();

// Gaji
$stmt = $db->prepare("SELECT COALESCE(SUM(total_gaji), 0) FROM gaji WHERE periode_awal >= ? AND periode_akhir <= ?");
$stmt->execute([$periodeAwal, $periodeAkhir]);
$totalGaji = (int)$stmt->fetchColumn();

$labaBersih = (int)$summary['total_pendapatan'] - $totalPengeluaran - $totalGaji;

// Chart bulanan — dinamis mengikuti periode yang dipilih (revisi 16: hilangkan tahun hardcoded)
$labelsBulan = [];
$pendapatanBulan = [];
$pengeluaranBulan = [];
$labaBulan = [];

$start = new DateTime(substr($periodeAwal, 0, 7) . '-01');
$end = new DateTime(substr($periodeAkhir, 0, 7) . '-01');
$end->modify('first day of next month');
$interval = new DateInterval('P1M');
$period = new DatePeriod($start, $interval, $end);
foreach ($period as $dt) {
    $blnStart = $dt->format('Y-m-01');
    $blnEnd = $dt->format('Y-m-t');
    $labelsBulan[] = $dt->format('M Y');
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ? $ukuranWhere");
    $stmt->execute(array_merge([$blnStart, $blnEnd], $ukuranParam));
    $pendapatanBulan[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal_pengeluaran BETWEEN ? AND ?");
    $stmt->execute([$blnStart, $blnEnd]);
    $pengeluaranBulan[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_gaji), 0) FROM gaji WHERE periode_awal >= ? AND periode_akhir <= ?");
    $stmt->execute([$blnStart, $blnEnd]);
    $gajiBulan = (int)$stmt->fetchColumn();
    $labaBulan[] = $pendapatanBulan[count($pendapatanBulan) - 1] - $pengeluaranBulan[count($pengeluaranBulan) - 1] - $gajiBulan;
}

// Detail
$detail = [];
if (isset($_GET['tampilkan'])) {
    $stmt = $db->prepare("SELECT tanggal_penjualan, ukuran_batako, jumlah_terjual, harga_satuan, total_penjualan, nama_pembeli FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ? $ukuranWhere ORDER BY tanggal_penjualan DESC");
    $stmt->execute(array_merge([$periodeAwal, $periodeAkhir], $ukuranParam));
    $detail = $stmt->fetchAll();
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
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
        .summary { margin: 15px 0; }
        .summary td { text-align: center; font-weight: bold; padding: 10px; border: 1px solid #ddd; }
    </style></head><body>';
    $html .= '<h2>Laporan Keuangan</h2>';
    $html .= '<p>Percetakan Batako Maros — ' . formatTanggal($periodeAwal) . ' s/d ' . formatTanggal($periodeAkhir) . '</p>';
    
    $html .= '<table class="summary"><tr>';
    $html .= '<td>Pendapatan: ' . formatRupiah($summary['total_pendapatan']) . '</td>';
    $html .= '<td>Pengeluaran: ' . formatRupiah($totalPengeluaran) . '</td>';
    $html .= '<td>Laba Bersih: ' . formatRupiah($labaBersih) . '</td>';
    $html .= '</tr></table>';
    
    $html .= '<table><thead><tr><th>Bulan</th><th>Pendapatan</th><th>Pengeluaran</th><th>Keuntungan Bersih</th></tr></thead><tbody>';
    for ($m = 0; $m < count($labelsBulan); $m++) {
        $html .= '<tr><td>' . $labelsBulan[$m] . '</td><td>' . formatRupiah($pendapatanBulan[$m]) . '</td><td>' . formatRupiah($pengeluaranBulan[$m]) . '</td><td>' . formatRupiah($labaBulan[$m]) . '</td></tr>';
    }
    $html .= '</tbody></table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('laporan_keuangan_' . date('Ymd') . '.pdf', ['Attachment' => true]);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Ringkasan');
    $sheet->setCellValue('A1', 'Laporan Keuangan'); $sheet->setCellValue('A2', 'Periode'); $sheet->setCellValue('B2', formatTanggal($periodeAwal) . ' s/d ' . formatTanggal($periodeAkhir));
    $sheet->setCellValue('A4', 'Total Pendapatan'); $sheet->setCellValue('B4', $summary['total_pendapatan']);
    $sheet->setCellValue('A5', 'Total Pengeluaran'); $sheet->setCellValue('B5', $totalPengeluaran);
    $sheet->setCellValue('A6', 'Laba Bersih'); $sheet->setCellValue('B6', $labaBersih);
    $sheet->setCellValue('A7', 'Total Terjual'); $sheet->setCellValue('B7', $summary['total_terjual']);
    $sheet->getStyle('A4:A7')->getFont()->setBold(true);
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Per Bulan');
    $sheet2->setCellValue('A1', 'Bulan'); $sheet2->setCellValue('B1', 'Pendapatan'); $sheet2->setCellValue('C1', 'Pengeluaran'); $sheet2->setCellValue('D1', 'Keuntungan Bersih');
    $sheet2->getStyle('A1:D1')->getFont()->setBold(true);
    $r = 2;
    for ($m = 0; $m < count($labelsBulan); $m++) {
        $sheet2->setCellValue('A'.$r, $labelsBulan[$m]); $sheet2->setCellValue('B'.$r, $pendapatanBulan[$m]); $sheet2->setCellValue('C'.$r, $pengeluaranBulan[$m]); $sheet2->setCellValue('D'.$r, $labaBulan[$m]);
        $r++;
    }
    foreach (range('A', 'D') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); $sheet2->getColumnDimension($col)->setAutoSize(true); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="laporan_keuangan_' . date('Ymd') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="page-toolbar">
    <div><h3>Laporan Keuangan</h3><p>Analisis pendapatan, biaya operasional, dan laba bersih pada periode yang dipilih.</p></div>
</div>
<div class="card mb-4">
    <div class="card-header"><h5><i class="bi bi-sliders"></i> Periode &amp; Ekspor Laporan</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Periode Awal</label>
                <input type="date" name="periode_awal" class="form-control" value="<?= e($periodeAwal) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Periode Akhir</label>
                <input type="date" name="periode_akhir" class="form-control" value="<?= e($periodeAkhir) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ukuran Batako</label>
                <select name="ukuran" class="form-select">
                    <option value="">Semua</option>
                    <option value="standar" <?= $ukuran === 'standar' ? 'selected' : '' ?>>Standar</option>
                    <option value="besar" <?= $ukuran === 'besar' ? 'selected' : '' ?>>Besar</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" name="tampilkan" value="1" class="btn btn-primary w-100"><i class="bi bi-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2">
                <a href="laporan_keuangan.php?export=pdf&periode_awal=<?= $periodeAwal ?>&periode_akhir=<?= $periodeAkhir ?>&ukuran=<?= $ukuran ?>" class="btn btn-danger btn-sm w-100"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            </div>
            <div class="col-md-2">
                <a href="laporan_keuangan.php?export=excel&periode_awal=<?= $periodeAwal ?>&periode_akhir=<?= $periodeAkhir ?>&ukuran=<?= $ukuran ?>" class="btn btn-success btn-sm w-100"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($summary['total_pendapatan']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-wallet2"></i></div>
            <div class="stat-label">Total Pengeluaran</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($totalPengeluaran) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $labaBersih >= 0 ? 'green' : 'red' ?>">📉</div>
            <div class="stat-label">Laba Bersih</div>
            <div class="stat-value" style="font-size:18px;color:<?= $labaBersih >= 0 ? 'var(--green)' : 'var(--red)' ?>;"><?= formatRupiah($labaBersih) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div class="stat-label">Total Terjual</div>
            <div class="stat-value"><?= number_format($summary['total_terjual']) ?></div>
            <div class="stat-sub"><?= number_format($summary['total_transaksi']) ?> transaksi</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Pendapatan Bulanan</h5></div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPendapatan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5>Pengeluaran Bulanan</h5></div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPengeluaran"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5>Keuntungan Bersih Bulanan</h5></div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartLaba"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Detail Table -->
<?php if (!empty($detail)): ?>
<div class="card">
    <div class="card-header"><h5>Detail Transaksi</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead><tr><th>No</th><th>Tanggal</th><th>Ukuran</th><th>Jumlah</th><th>Harga</th><th>Total</th><th>Pembeli</th></tr></thead>
                <tbody>
                    <?php foreach ($detail as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= formatTanggal($d['tanggal_penjualan']) ?></td>
                        <td><span class="badge badge-primary"><?= ucfirst($d['ukuran_batako']) ?></span></td>
                        <td><?= number_format($d['jumlah_terjual']) ?></td>
                        <td><?= formatRupiah($d['harga_satuan']) ?></td>
                        <td><strong><?= formatRupiah($d['total_penjualan']) ?></strong></td>
                        <td><?= e($d['nama_pembeli'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

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

new Chart(document.getElementById('chartPendapatan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsBulan) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode($pendapatanBulan) ?>,
            borderColor: '#287A5A',
            backgroundColor: 'rgba(40,122,90,0.10)',
            fill: true, tension: 0.35, pointRadius: 0, pointHoverRadius: 6, borderWidth: 2.5, spanGaps: true,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + fmtRp(ctx.parsed.y) } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => fmtRp(v), maxTicksLimit: 6 }, grid: { color: 'rgba(39,49,58,0.06)' } } }
    }
});

new Chart(document.getElementById('chartPengeluaran'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsBulan) ?>,
        datasets: [{
            label: 'Pengeluaran (Rp)',
            data: <?= json_encode($pengeluaranBulan) ?>,
            backgroundColor: '#B84444',
            borderRadius: 6, borderSkipped: false, maxBarThickness: 34,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + fmtRp(ctx.parsed.y) } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => fmtRp(v), maxTicksLimit: 6 }, grid: { color: 'rgba(39,49,58,0.06)' } } }
    }
});

new Chart(document.getElementById('chartLaba'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsBulan) ?>,
        datasets: [{
            label: 'Keuntungan Bersih (Rp)',
            data: <?= json_encode($labaBulan) ?>,
            backgroundColor: <?= json_encode(array_map(fn($v) => $v >= 0 ? '#287A5A' : '#B84444', $labaBulan)) ?>,
            borderRadius: 6, borderSkipped: false, maxBarThickness: 34,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Keuntungan: ' + fmtRp(ctx.parsed.y) } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => fmtRp(v), maxTicksLimit: 6 }, grid: { color: 'rgba(39,49,58,0.06)' } } }
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

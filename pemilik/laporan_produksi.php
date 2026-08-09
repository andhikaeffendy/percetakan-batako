<?php
// pemilik/laporan_produksi.php — Laporan Produksi vs Penjualan
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Laporan Produksi vs Penjualan';

$periodeAwal = $_GET['periode_awal'] ?? date('Y-m-01');
$periodeAkhir = $_GET['periode_akhir'] ?? date('Y-m-d');
$ukuran = $_GET['ukuran'] ?? '';

// Summary
$ukuranParam = [];
$ukuranWhere = '';
if ($ukuran) {
    $ukuranWhere = 'AND ukuran_batako = ?';
    $ukuranParam = [$ukuran];
}
$stmt = $db->prepare("SELECT 
    COALESCE((SELECT SUM(realisasi_produksi) FROM produksi WHERE tanggal_produksi BETWEEN ? AND ? $ukuranWhere), 0) as total_produksi,
    COALESCE((SELECT SUM(jumlah_terjual) FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ? $ukuranWhere), 0) as total_penjualan");
$stmt->execute(array_merge([$periodeAwal, $periodeAkhir], $ukuranParam, [$periodeAwal, $periodeAkhir], $ukuranParam));
$summary = $stmt->fetch();

$selisih = $summary['total_produksi'] - $summary['total_penjualan'];
$deviasi = $summary['total_produksi'] > 0 ? round(($selisih / $summary['total_produksi']) * 100, 1) : 0;

// Chart data: daily
$labels = [];
$produksiData = [];
$penjualanData = [];
$current = strtotime($periodeAwal);
$end = strtotime($periodeAkhir);
while ($current <= $end) {
    $date = date('Y-m-d', $current);
    $labels[] = date('d/m', $current);
    
    if ($ukuran) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) FROM produksi WHERE tanggal_produksi = ? AND ukuran_batako = ?");
        $stmt->execute([$date, $ukuran]);
        $produksiData[] = (int)$stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) FROM penjualan WHERE tanggal_penjualan = ? AND ukuran_batako = ?");
        $stmt->execute([$date, $ukuran]);
        $penjualanData[] = (int)$stmt->fetchColumn();
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) FROM produksi WHERE tanggal_produksi = ?");
        $stmt->execute([$date]);
        $produksiData[] = (int)$stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) FROM penjualan WHERE tanggal_penjualan = ?");
        $stmt->execute([$date]);
        $penjualanData[] = (int)$stmt->fetchColumn();
    }
    
    $current = strtotime('+1 day', $current);
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
        tr:nth-child(even) { background: #f9f9f9; }
        .summary { margin: 15px 0; }
        .summary td { text-align: center; font-weight: bold; padding: 10px; border: 1px solid #ddd; }
    </style></head><body>';
    $html .= '<h2>Laporan Produksi vs Penjualan</h2>';
    $html .= '<p>Percetakan Batako Maros — ' . formatTanggal($periodeAwal) . ' s/d ' . formatTanggal($periodeAkhir) . '</p>';
    
    // Summary
    $html .= '<table class="summary"><tr>';
    $html .= '<td>Total Produksi: ' . number_format($summary['total_produksi']) . '</td>';
    $html .= '<td>Total Penjualan: ' . number_format($summary['total_penjualan']) . '</td>';
    $html .= '<td>Selisih: ' . number_format($selisih) . '</td>';
    $html .= '<td>Deviasi: ' . $deviasi . '%</td>';
    $html .= '</tr></table>';
    
    $html .= '<table><thead><tr><th>Tanggal</th><th>Produksi</th><th>Penjualan</th><th>Selisih</th></tr></thead><tbody>';
    for ($i = 0; $i < count($labels); $i++) {
        $html .= '<tr><td>' . $labels[$i] . '</td><td>' . number_format($produksiData[$i]) . '</td><td>' . number_format($penjualanData[$i]) . '</td><td>' . number_format($produksiData[$i] - $penjualanData[$i]) . '</td></tr>';
    }
    $html .= '</tbody></table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('laporan_produksi_' . date('Ymd') . '.pdf', ['Attachment' => true]);
    exit;
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="page-toolbar">
    <div><h3>Laporan Produksi</h3><p>Bandingkan produksi dan penjualan untuk memantau keseimbangan operasional.</p></div>
</div>
<div class="card mb-4">
    <div class="card-header"><h5><i class="bi bi-sliders"></i> Periode &amp; Ekspor Laporan</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Periode Awal</label>
                <input type="date" name="periode_awal" class="form-control" value="<?= e($periodeAwal) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Periode Akhir</label>
                <input type="date" name="periode_akhir" class="form-control" value="<?= e($periodeAkhir) ?>" required>
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
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2">
                <a href="laporan_produksi.php?export=pdf&periode_awal=<?= $periodeAwal ?>&periode_akhir=<?= $periodeAkhir ?>&ukuran=<?= $ukuran ?>" class="btn btn-danger btn-sm w-100"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-bricks"></i></div>
            <div class="stat-label">Total Produksi</div>
            <div class="stat-value"><?= number_format($summary['total_produksi']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">Total Penjualan</div>
            <div class="stat-value"><?= number_format($summary['total_penjualan']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $selisih >= 0 ? 'green' : 'red' ?>"><i class="bi bi-bars"></i></div>
            <div class="stat-label">Selisih</div>
            <div class="stat-value"><?= number_format($selisih) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $deviasi >= 0 ? 'green' : 'red' ?>">📉</div>
            <div class="stat-label">Deviasi</div>
            <div class="stat-value"><?= $deviasi ?>%</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header"><h5>Grafik Produksi vs Penjualan</h5></div>
    <div class="card-body">
        <div class="chart-container"><canvas id="chartLaporan"></canvas></div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header"><h5>Detail Laporan (<?= formatTanggal($periodeAwal) ?> — <?= formatTanggal($periodeAkhir) ?>)</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr><th>Tanggal</th><th>Produksi</th><th>Penjualan</th><th>Selisih</th></tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($labels); $i++): ?>
                    <tr>
                        <td><?= $labels[$i] ?></td>
                        <td><?= number_format($produksiData[$i]) ?></td>
                        <td><?= number_format($penjualanData[$i]) ?></td>
                        <td><span class="badge <?= $produksiData[$i] >= $penjualanData[$i] ? 'badge-success' : 'badge-danger' ?>"><?= number_format($produksiData[$i] - $penjualanData[$i]) ?></span></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
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

// ————— Format angka konsisten —————
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

new Chart(document.getElementById('chartLaporan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Produksi', data: <?= json_encode($produksiData) ?>, borderColor: '#2F67C7', backgroundColor: 'rgba(47,103,199,0.10)', fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, spanGaps: true },
            { label: 'Penjualan', data: <?= json_encode($penjualanData) ?>, borderColor: '#C65A32', backgroundColor: 'rgba(198,90,50,0.08)', fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, spanGaps: true }
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

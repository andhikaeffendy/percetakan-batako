<?php
// pemilik/laporan_keuangan.php — Laporan Keuangan
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
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

// Chart bulanan
$labelsBulan = [];
$pendapatanBulan = [];
for ($m = 1; $m <= 12; $m++) {
    $bln = str_pad($m, 2, '0', STR_PAD_LEFT);
    $labelsBulan[] = date('M', strtotime("2025-{$bln}-01"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ? $ukuranWhere");
    $stmt->execute(array_merge(["2025-{$bln}-01", "2025-{$bln}-31"], $ukuranParam));
    $pendapatanBulan[] = (int)$stmt->fetchColumn();
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
    
    $html .= '<table><thead><tr><th>Bulan</th><th>Pendapatan</th></tr></thead><tbody>';
    for ($m = 0; $m < 12; $m++) {
        $html .= '<tr><td>' . $labelsBulan[$m] . '</td><td>' . formatRupiah($pendapatanBulan[$m]) . '</td></tr>';
    }
    $html .= '</tbody></table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('laporan_keuangan_' . date('Ymd') . '.pdf', ['Attachment' => true]);
    exit;
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="card mb-4">
    <div class="card-header"><h5><i class="bi bi-funnel"></i> Filter Laporan</h5></div>
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
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange">💰</div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($summary['total_pendapatan']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon red">💸</div>
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
            <div class="stat-icon blue">📦</div>
            <div class="stat-label">Total Terjual</div>
            <div class="stat-value"><?= number_format($summary['total_terjual']) ?></div>
            <div class="stat-sub"><?= number_format($summary['total_transaksi']) ?> transaksi</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header"><h5>Pendapatan Bulanan (2025)</h5></div>
    <div class="card-body">
        <div class="chart-container" style="min-height:250px;"><canvas id="chartKeuangan"></canvas></div>
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
                        <td><span class="badge badge-warning"><?= ucfirst($d['ukuran_batako']) ?></span></td>
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
new Chart(document.getElementById('chartKeuangan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsBulan) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode($pendapatanBulan) ?>,
            borderColor: '#16A34A',
            backgroundColor: 'rgba(22,163,74,0.05)',
            fill: true, tension: 0.3, pointRadius: 3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } }
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

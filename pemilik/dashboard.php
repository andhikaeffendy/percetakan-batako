<?php
// pemilik/dashboard.php — Dashboard Pemilik
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Dashboard Pemilik';
$today = date('Y-m-d');

// KPI: Produksi Hari Ini
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN realisasi_produksi ELSE 0 END), 0) as prod_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN realisasi_produksi ELSE 0 END), 0) as prod_besar
    FROM produksi WHERE tanggal_produksi = ?");
$stmt->execute([$today]);
$prodToday = $stmt->fetch();
$produksiHariIni = $prodToday['prod_standar'] + $prodToday['prod_besar'];

// KPI: Penjualan Hari Ini
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN jumlah_terjual ELSE 0 END), 0) as jual_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN jumlah_terjual ELSE 0 END), 0) as jual_besar,
    COALESCE(SUM(total_penjualan), 0) as pendapatan_hari_ini
    FROM penjualan WHERE tanggal_penjualan = ?");
$stmt->execute([$today]);
$jualToday = $stmt->fetch();
$penjualanHariIni = $jualToday['jual_standar'] + $jualToday['jual_besar'];

// KPI: Stok
$stmt = $db->query("SELECT ukuran_batako, stok_tersedia FROM stok ORDER BY ukuran_batako");
$stokData = $stmt->fetchAll();
$stokStandar = 0; $stokBesar = 0;
foreach ($stokData as $s) {
    if ($s['ukuran_batako'] === 'standar') $stokStandar = $s['stok_tersedia'];
    else $stokBesar = $s['stok_tersedia'];
}
$totalStok = $stokStandar + $stokBesar;

// KPI: Total Produksi Minggu Ini
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total FROM produksi WHERE tanggal_produksi BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$produksiMingguIni = $stmt->fetch()['total'];

// KPI: Total Penjualan Minggu Ini
$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$penjualanMingguIni = $stmt->fetch()['total'];

// KPI: Deviasi
$deviasi = $produksiHariIni > 0 ? round((($produksiHariIni - $penjualanHariIni) / $produksiHariIni) * 100, 1) : 0;

// Chart: Produksi vs Penjualan 7 Hari Terakhir
$labels7 = [];
$produksi7 = [];
$penjualan7 = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels7[] = date('d/m', strtotime($date));
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total FROM produksi WHERE tanggal_produksi = ?");
    $stmt->execute([$date]);
    $produksi7[] = (int)$stmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as total FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]);
    $penjualan7[] = (int)$stmt->fetch()['total'];
}

// Chart: Pendapatan 30 Hari Terakhir
$labels30 = [];
$pendapatan30 = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels30[] = date('d/m', strtotime($date));
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]);
    $pendapatan30[] = (int)$stmt->fetch()['total'];
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue">🏭</div>
            <div class="stat-label">Produksi Hari Ini</div>
            <div class="stat-value"><?= number_format($produksiHariIni) ?></div>
            <div class="stat-sub">
                Standar: <?= number_format($prodToday['prod_standar']) ?> | 
                Besar: <?= number_format($prodToday['prod_besar']) ?>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon orange">💰</div>
            <div class="stat-label">Penjualan Hari Ini</div>
            <div class="stat-value"><?= number_format($penjualanHariIni) ?></div>
            <div class="stat-sub">
                Standar: <?= number_format($jualToday['jual_standar']) ?> | 
                Besar: <?= number_format($jualToday['jual_besar']) ?>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon green">📦</div>
            <div class="stat-label">Stok Tersedia</div>
            <div class="stat-value"><?= number_format($totalStok) ?></div>
            <div class="stat-sub">
                Standar: <?= number_format($stokStandar) ?> | 
                Besar: <?= number_format($stokBesar) ?>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon <?= $deviasi >= 0 ? 'green' : 'red' ?>">📊</div>
            <div class="stat-label">Deviasi Produksi vs Penjualan</div>
            <div class="stat-value"><?= $deviasi ?>%</div>
            <div class="stat-sub">
                <span class="stat-badge <?= $deviasi >= 0 ? 'badge-success' : 'badge-danger' ?>">
                    <?= $deviasi >= 0 ? 'Surplus' : 'Defisit' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5>Produksi vs Penjualan (7 Hari Terakhir)</h5></div>
            <div class="card-body">
                <div class="chart-container"><canvas id="chartProduksiPenjualan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5>Ringkasan Minggu Ini</h5></div>
            <div class="card-body">
                <div class="mb-4">
                    <div style="font-size:12px;color:var(--text-muted);">Total Produksi</div>
                    <div style="font-size:24px;font-weight:700;color:var(--primary);"><?= number_format($produksiMingguIni) ?></div>
                </div>
                <div class="mb-4">
                    <div style="font-size:12px;color:var(--text-muted);">Total Penjualan</div>
                    <div style="font-size:24px;font-weight:700;color:var(--orange);"><?= number_format($penjualanMingguIni) ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text-muted);">Pendapatan Hari Ini</div>
                    <div style="font-size:24px;font-weight:700;color:var(--green);"><?= formatRupiah($jualToday['pendapatan_hari_ini']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pendapatan 30 Hari Chart -->
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5>Pendapatan (30 Hari Terakhir)</h5></div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPendapatan"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Produksi vs Penjualan Chart
const ctx1 = document.getElementById('chartProduksiPenjualan').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels7) ?>,
        datasets: [{
            label: 'Produksi',
            data: <?= json_encode($produksi7) ?>,
            backgroundColor: '#2563EB',
            borderRadius: 6,
            borderSkipped: false,
        }, {
            label: 'Penjualan',
            data: <?= json_encode($penjualan7) ?>,
            backgroundColor: '#D97706',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Pendapatan Chart
const ctx2 = document.getElementById('chartPendapatan').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels30) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode($pendapatan30) ?>,
            borderColor: '#16A34A',
            backgroundColor: 'rgba(22,163,74,0.05)',
            fill: true,
            tension: 0.3,
            pointRadius: 2,
            pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: { 
                    callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

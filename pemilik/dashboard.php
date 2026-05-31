<?php
// pemilik/dashboard.php — Dashboard Pemilik (Analytics Dashboard)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Dashboard Pemilik';
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

// ──────────────────────────────────────────────
// KPI: PRODUKSI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN realisasi_produksi ELSE 0 END), 0) as prod_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN realisasi_produksi ELSE 0 END), 0) as prod_besar,
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN target_produksi ELSE 0 END), 0) as target_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN target_produksi ELSE 0 END), 0) as target_besar
    FROM produksi WHERE tanggal_produksi = ?");
$stmt->execute([$today]);
$prodToday = $stmt->fetch();
$produksiHariIni = $prodToday['prod_standar'] + $prodToday['prod_besar'];
$targetHariIni = $prodToday['target_standar'] + $prodToday['target_besar'];
$capaianHariIni = $targetHariIni > 0 ? round(($produksiHariIni / $targetHariIni) * 100, 1) : 0;

// ──────────────────────────────────────────────
// KPI: PENJUALAN
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN jumlah_terjual ELSE 0 END), 0) as jual_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN jumlah_terjual ELSE 0 END), 0) as jual_besar,
    COALESCE(SUM(total_penjualan), 0) as pendapatan_hari_ini
    FROM penjualan WHERE tanggal_penjualan = ?");
$stmt->execute([$today]);
$jualToday = $stmt->fetch();
$penjualanHariIni = $jualToday['jual_standar'] + $jualToday['jual_besar'];
$pendapatanHariIni = $jualToday['pendapatan_hari_ini'];

// ──────────────────────────────────────────────
// KPI: STOK
// ──────────────────────────────────────────────
$allStok = getAllStok($db);
$totalStok = $allStok['standar'] + $allStok['besar'];
$stokStandar = $allStok['standar'];
$stokBesar = $allStok['besar'];

// ──────────────────────────────────────────────
// KPI: MINGGU INI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total FROM produksi WHERE tanggal_produksi BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$produksiMingguIni = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$penjualanMingguIni = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$pendapatanMingguIni = (int)$stmt->fetch()['total'];

// ──────────────────────────────────────────────
// KPI: BULAN INI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total FROM produksi WHERE tanggal_produksi BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$produksiBulanIni = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$penjualanBulanIni = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$pendapatanBulanIni = (int)$stmt->fetch()['total'];

// ──────────────────────────────────────────────
// KPI: PEKERJA
// ──────────────────────────────────────────────
$stmt = $db->query("SELECT COUNT(*) as total FROM pekerja WHERE status = 'aktif'");
$pekerjaAktif = (int)$stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM pekerja WHERE status = 'nonaktif'");
$pekerjaNonaktif = (int)$stmt->fetch()['total'];

// ──────────────────────────────────────────────
// DEVIASI
// ──────────────────────────────────────────────
$deviasi = $produksiHariIni > 0 ? round((($produksiHariIni - $penjualanHariIni) / $produksiHariIni) * 100, 1) : 0;

// ──────────────────────────────────────────────
// CHART: 7 HARI
// ──────────────────────────────────────────────
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

// ──────────────────────────────────────────────
// CHART: PENDAPATAN 30 HARI
// ──────────────────────────────────────────────
$labels30 = [];
$pendapatan30 = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels30[] = date('d/m', strtotime($date));
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]);
    $pendapatan30[] = (int)$stmt->fetch()['total'];
}

// ──────────────────────────────────────────────
// CHART: PENDAPATAN BULANAN (TAHUN INI)
// ──────────────────────────────────────────────
$tahunIni = date('Y');
$labelsBulan = [];
$pendapatanBulanan = [];
for ($m = 1; $m <= 12; $m++) {
    $bln = str_pad($m, 2, '0', STR_PAD_LEFT);
    $labelsBulan[] = date('M', strtotime("{$tahunIni}-{$bln}-01"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
    $stmt->execute(["{$tahunIni}-{$bln}-01", "{$tahunIni}-{$bln}-31"]);
    $pendapatanBulanan[] = (int)$stmt->fetchColumn();
}

// ──────────────────────────────────────────────
// TOP PRODUKSI — PEKERJA TERBAIK BULAN INI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT pk.nama_pekerja, 
    COALESCE(SUM(p.realisasi_produksi), 0) as total_produksi,
    COALESCE(SUM(p.jumlah_sak_semen), 0) as total_sak
    FROM produksi p
    JOIN pekerja pk ON p.pekerja_id = pk.id
    WHERE p.tanggal_produksi BETWEEN ? AND ?
    GROUP BY p.pekerja_id, pk.nama_pekerja
    ORDER BY total_produksi DESC LIMIT 5");
$stmt->execute([$monthStart, $monthEnd]);
$topPekerja = $stmt->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>

<!-- ===== GREETING ===== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:700;margin-bottom:2px;">
            Dashboard Pemilik 
            <span style="font-size:16px;font-weight:400;color:var(--text-muted);">
                — <?= date('l, d F Y') ?>
            </span>
        </h4>
        <p style="color:var(--text-muted);margin:0;font-size:13px;">
            Pantau seluruh aktivitas operasional percetakan batako
        </p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge badge-success" style="font-size:12px;padding:6px 14px;">
            🟢 Sistem Aktif
        </span>
    </div>
</div>

<!-- ===== KPI CARDS ROW (6 METRICS) ===== -->
<div class="row g-3 mb-4">
    <!-- Produksi Hari Ini -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon blue" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">🏭</div>
                <?php if ($capaianHariIni > 0): ?>
                <span class="badge badge-<?= $capaianHariIni >= 100 ? 'success' : 'warning' ?>" style="font-size:10px;">
                    <?= $capaianHariIni ?>%
                </span>
                <?php endif; ?>
            </div>
            <div class="stat-label" style="font-size:11px;">Produksi Hari Ini</div>
            <div class="stat-value" style="font-size:20px;"><?= number_format($produksiHariIni) ?></div>
            <div class="stat-sub">S: <?= number_format($prodToday['prod_standar']) ?> | B: <?= number_format($prodToday['prod_besar']) ?></div>
        </div>
    </div>

    <!-- Penjualan Hari Ini -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon orange" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">💰</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Penjualan Hari Ini</div>
            <div class="stat-value" style="font-size:20px;"><?= number_format($penjualanHariIni) ?></div>
            <div class="stat-sub">S: <?= number_format($jualToday['jual_standar']) ?> | B: <?= number_format($jualToday['jual_besar']) ?></div>
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon green" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">💳</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Pendapatan Hari Ini</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($pendapatanHariIni) ?></div>
            <div class="stat-sub">Minggu ini: <?= formatRupiah($pendapatanMingguIni) ?></div>
        </div>
    </div>

    <!-- Stok Tersedia -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon blue" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">📦</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Stok Tersedia</div>
            <div class="stat-value" style="font-size:20px;"><?= number_format($totalStok) ?></div>
            <div class="stat-sub">Standar: <?= number_format($stokStandar) ?> | Besar: <?= number_format($stokBesar) ?></div>
        </div>
    </div>

    <!-- Deviasi -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon <?= $deviasi >= 0 ? 'green' : 'red' ?>" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">📊</div>
                <span class="badge badge-<?= $deviasi >= 0 ? 'success' : 'danger' ?>" style="font-size:10px;">
                    <?= $deviasi >= 0 ? 'Surplus' : 'Defisit' ?>
                </span>
            </div>
            <div class="stat-label" style="font-size:11px;">Deviasi Prod vs Jual</div>
            <div class="stat-value" style="font-size:20px;"><?= $deviasi ?>%</div>
            <div class="stat-sub">Produksi vs Penjualan</div>
        </div>
    </div>

    <!-- Pekerja Aktif -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon green" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">👷</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Tenaga Kerja</div>
            <div class="stat-value" style="font-size:20px;"><?= $pekerjaAktif ?></div>
            <div class="stat-sub">Aktif: <?= $pekerjaAktif ?> | Nonaktif: <?= $pekerjaNonaktif ?></div>
        </div>
    </div>
</div>

<!-- ===== CHARTS ROW ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart"></i> Produksi vs Penjualan (7 Hari Terakhir)</h5>
                <span style="font-size:12px;color:var(--text-muted);">Perbandingan harian</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:280px;"><canvas id="chartProduksiPenjualan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-trophy"></i> Top Pekerja Bulan Ini</h5>
                <span style="font-size:12px;color:var(--text-muted);">Produksi terbanyak</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topPekerja)): ?>
                    <div class="p-4 text-muted text-center">Belum ada data produksi bulan ini.</div>
                <?php else: ?>
                <table class="table-custom">
                    <thead>
                        <tr><th>#</th><th>Pekerja</th><th class="text-end">Produksi</th><th class="text-end">Sak Semen</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPekerja as $i => $p): ?>
                        <tr>
                            <td>
                                <?php if ($i === 0): ?><span style="font-size:16px;">🥇</span>
                                <?php elseif ($i === 1): ?><span style="font-size:16px;">🥈</span>
                                <?php elseif ($i === 2): ?><span style="font-size:16px;">🥉</span>
                                <?php else: ?><span style="color:var(--text-muted);"><?= $i + 1 ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= e($p['nama_pekerja']) ?></strong></td>
                            <td class="text-end"><?= number_format($p['total_produksi']) ?></td>
                            <td class="text-end"><?= number_format($p['total_sak'], 1) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== SECOND CHART ROW ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-graph-up"></i> Pendapatan (30 Hari Terakhir)</h5>
                <span style="font-size:12px;color:var(--text-muted);">Total: <?= formatRupiah(array_sum($pendapatan30)) ?></span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPendapatan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-calendar"></i> Pendapatan Bulanan (<?= $tahunIni ?>)</h5>
                <span style="font-size:12px;color:var(--text-muted);">Total: <?= formatRupiah(array_sum($pendapatanBulanan)) ?></span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPendapatanBulanan"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- ===== SUMMARY ROW ===== -->
<div class="row g-3">
    <div class="col-lg-3 col-md-6">
        <div class="card text-center">
            <div class="card-body py-4">
                <div style="font-size:36px;margin-bottom:8px;">🏭</div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Produksi Bulan Ini</div>
                <div style="font-size:28px;font-weight:700;color:var(--primary);"><?= number_format($produksiBulanIni) ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-center">
            <div class="card-body py-4">
                <div style="font-size:36px;margin-bottom:8px;">💰</div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Penjualan Bulan Ini</div>
                <div style="font-size:28px;font-weight:700;color:var(--orange);"><?= number_format($penjualanBulanIni) ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-center">
            <div class="card-body py-4">
                <div style="font-size:36px;margin-bottom:8px;">💳</div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Pendapatan Bulan Ini</div>
                <div style="font-size:22px;font-weight:700;color:var(--green);"><?= formatRupiah($pendapatanBulanIni) ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-center">
            <div class="card-body py-4">
                <div style="font-size:36px;margin-bottom:8px;">📦</div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Total Stok</div>
                <div style="font-size:28px;font-weight:700;color:#0EA5E9;"><?= number_format($totalStok) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// 1. Produksi vs Penjualan
new Chart(document.getElementById('chartProduksiPenjualan'), {
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
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 2. Pendapatan 30 Hari
new Chart(document.getElementById('chartPendapatan'), {
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

// 3. Pendapatan Bulanan
new Chart(document.getElementById('chartPendapatanBulanan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsBulan) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode($pendapatanBulanan) ?>,
            backgroundColor: '#3B82F6',
            borderRadius: 6,
            borderSkipped: false,
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

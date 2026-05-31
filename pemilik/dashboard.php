<?php
// pemilik/dashboard.php — Dashboard Pemilik
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

// KPI: PRODUKSI
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN realisasi_produksi ELSE 0 END), 0) as prod_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN realisasi_produksi ELSE 0 END), 0) as prod_besar
    FROM produksi WHERE tanggal_produksi = ?");
$stmt->execute([$today]);
$prodToday = $stmt->fetch();
$produksiHariIni = $prodToday['prod_standar'] + $prodToday['prod_besar'];

// KPI: PENJUALAN
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN ukuran_batako='standar' THEN jumlah_terjual ELSE 0 END), 0) as jual_standar,
    COALESCE(SUM(CASE WHEN ukuran_batako='besar' THEN jumlah_terjual ELSE 0 END), 0) as jual_besar,
    COALESCE(SUM(total_penjualan), 0) as pendapatan_hari_ini
    FROM penjualan WHERE tanggal_penjualan = ?");
$stmt->execute([$today]);
$jualToday = $stmt->fetch();
$penjualanHariIni = $jualToday['jual_standar'] + $jualToday['jual_besar'];
$pendapatanHariIni = $jualToday['pendapatan_hari_ini'];

// KPI: STOK
$allStok = getAllStok($db);
$totalStok = $allStok['standar'] + $allStok['besar'];

// KPI: MINGGU INI
$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total FROM produksi WHERE tanggal_produksi BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$produksiMingguIni = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$weekStart, $weekEnd]);
$penjualanMingguIni = (int)$stmt->fetch()['total'];

// KPI: BULAN INI
$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total FROM produksi WHERE tanggal_produksi BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$produksiBulanIni = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$pendapatanBulanIni = (int)$stmt->fetchColumn();

// KPI: PEKERJA
$stmt = $db->query("SELECT COUNT(*) as total FROM pekerja WHERE status = 'aktif'");
$pekerjaAktif = (int)$stmt->fetch()['total'];

// DEVIASI
$deviasi = $produksiHariIni > 0 ? round((($produksiHariIni - $penjualanHariIni) / $produksiHariIni) * 100, 1) : 0;

// CHART: 7 HARI
$labels7 = []; $produksi7 = []; $penjualan7 = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels7[] = date('d/m', strtotime($date));
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) FROM produksi WHERE tanggal_produksi = ?");
    $stmt->execute([$date]); $produksi7[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]); $penjualan7[] = (int)$stmt->fetchColumn();
}

// CHART: PENDAPATAN 30 HARI
$labels30 = []; $pendapatan30 = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels30[] = date('d/m', strtotime($date));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]); $pendapatan30[] = (int)$stmt->fetchColumn();
}

// TOP PRODUKSI — PEKERJA
$stmt = $db->prepare("SELECT pk.nama_pekerja, 
    COALESCE(SUM(p.realisasi_produksi), 0) as total_produksi,
    COALESCE(SUM(p.jumlah_sak_semen), 0) as total_sak
    FROM produksi p JOIN pekerja pk ON p.pekerja_id = pk.id
    WHERE p.tanggal_produksi BETWEEN ? AND ?
    GROUP BY p.pekerja_id, pk.nama_pekerja ORDER BY total_produksi DESC LIMIT 5");
$stmt->execute([$monthStart, $monthEnd]);
$topPekerja = $stmt->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>

<!-- ===== GREETING ===== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:700;margin-bottom:2px;">
            Dashboard Pemilik
            <span style="font-size:15px;font-weight:400;color:var(--text-muted);">— <?= date('l, d F Y') ?></span>
        </h4>
        <p style="color:var(--text-muted);margin:0;font-size:13px;">Pantau seluruh aktivitas operasional percetakan batako</p>
    </div>
    <span class="badge badge-success" style="font-size:12px;padding:6px 14px;">🟢 Sistem Aktif</span>
</div>

<!-- ===== KPI CARDS ROW (4 CARDS LIKE ANALISA DESIGN) ===== -->
<div class="row g-4 mb-5">
    <!-- CARD 1: PRODUKSI (BLUE) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--primary);"></div>
            <div class="card-body text-center" style="padding:28px 16px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(37,99,235,0.12);">
                    <span style="font-size:26px;">🏭</span>
                </div>
                <div style="font-size:34px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= number_format($produksiHariIni) ?>
                </div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">
                    PRODUKSI HARI INI
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    S: <?= number_format($prodToday['prod_standar']) ?> | B: <?= number_format($prodToday['prod_besar']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: PENJUALAN (ORANGE) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--orange);"></div>
            <div class="card-body text-center" style="padding:28px 16px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(217,119,6,0.12);">
                    <span style="font-size:26px;">💰</span>
                </div>
                <div style="font-size:34px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= number_format($penjualanHariIni) ?>
                </div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">
                    PENJUALAN HARI INI
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    S: <?= number_format($jualToday['jual_standar']) ?> | B: <?= number_format($jualToday['jual_besar']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 3: STOK (GREEN) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--green);"></div>
            <div class="card-body text-center" style="padding:28px 16px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(22,163,74,0.12);">
                    <span style="font-size:26px;">📦</span>
                </div>
                <div style="font-size:34px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= number_format($totalStok) ?>
                </div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">
                    STOK TERSEDIA
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    Standar: <?= number_format($allStok['standar']) ?> | Besar: <?= number_format($allStok['besar']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 4: DEVIASI (RED/GREEN) -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:<?= $deviasi >= 0 ? 'var(--green)' : 'var(--red)' ?>;"></div>
            <div class="card-body text-center" style="padding:28px 16px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:<?= $deviasi >= 0 ? 'rgba(22,163,74,0.12)' : 'rgba(220,38,38,0.12)' ?>;">
                    <span style="font-size:26px;">📊</span>
                </div>
                <div style="font-size:34px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= $deviasi ?>%
                </div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">
                    DEVIASI
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    <span style="color:<?= $deviasi >= 0 ? 'var(--green)' : 'var(--red)' ?>;font-weight:700;">
                        <?= $deviasi >= 0 ? 'Surplus' : 'Defisit' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHART + TOP PERFORMER ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5>Produksi vs Penjualan (7 Hari Terakhir)</h5>
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
                <h5>🏆 Top Pekerja Bulan Ini</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topPekerja)): ?>
                    <div class="p-4 text-muted text-center">Belum ada data produksi bulan ini.</div>
                <?php else: ?>
                <table class="table-custom">
                    <thead>
                        <tr><th>#</th><th>Pekerja</th><th class="text-end">Produksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPekerja as $i => $p): ?>
                        <tr>
                            <td><?php if ($i===0): ?>🥇<?php elseif ($i===1): ?>🥈<?php elseif ($i===2): ?>🥉<?php else: ?><span style="color:var(--text-muted);"><?= $i+1 ?></span><?php endif; ?></td>
                            <td><strong><?= e($p['nama_pekerja']) ?></strong></td>
                            <td class="text-end"><?= number_format($p['total_produksi']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== PENDAPATAN CHART + SUMMARY ===== -->
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5>Pendapatan (30 Hari Terakhir)</h5>
                <span style="font-size:12px;color:var(--text-muted);">Total: <?= formatRupiah(array_sum($pendapatan30)) ?></span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPendapatan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="row g-3">
            <div class="col-12">
                <div class="card text-center border-0 shadow-sm" style="border-radius:16px;">
                    <div style="height:4px;background:var(--primary);border-radius:16px 16px 0 0;"></div>
                    <div class="card-body py-4">
                        <div style="font-size:30px;margin-bottom:8px;">🏭</div>
                        <div style="font-size:26px;font-weight:800;color:var(--primary);"><?= number_format($produksiBulanIni) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">PRODUKSI BULAN INI</div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card text-center border-0 shadow-sm" style="border-radius:16px;">
                    <div style="height:4px;background:var(--orange);border-radius:16px 16px 0 0;"></div>
                    <div class="card-body py-4">
                        <div style="font-size:30px;margin-bottom:8px;">💰</div>
                        <div style="font-size:26px;font-weight:800;color:var(--orange);"><?= number_format($penjualanMingguIni) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">PENJUALAN MINGGU INI</div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card text-center border-0 shadow-sm" style="border-radius:16px;">
                    <div style="height:4px;background:var(--green);border-radius:16px 16px 0 0;"></div>
                    <div class="card-body py-4">
                        <div style="font-size:30px;margin-bottom:8px;">💳</div>
                        <div style="font-size:18px;font-weight:800;color:var(--green);"><?= formatRupiah($pendapatanBulanIni) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;">PENDAPATAN BULAN INI</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartProduksiPenjualan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels7) ?>,
        datasets: [{
            label: 'Produksi', data: <?= json_encode($produksi7) ?>,
            backgroundColor: '#2563EB', borderRadius: 6, borderSkipped: false,
        }, {
            label: 'Penjualan', data: <?= json_encode($penjualan7) ?>,
            backgroundColor: '#D97706', borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById('chartPendapatan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels30) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)', data: <?= json_encode($pendapatan30) ?>,
            borderColor: '#16A34A', backgroundColor: 'rgba(22,163,74,0.05)',
            fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 6,
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

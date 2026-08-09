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

// Sinkronkan persediaan sebelum ditampilkan
updateSemuaStok($db);

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

// KPI: STOK (dari stok_produk — canonical)
$allStok = getAllStok($db);
$totalStok = $allStok['standar'] + $allStok['besar'];
$stokStandarKPI = $allStok['standar'];
$stokBesarKPI = $allStok['besar'];

// KPI: STOK BAHAN BAKU (Semen & Pasir)
$stmtBahan = $db->query("SELECT jenis_bahan, jumlah, satuan, status FROM stok_bahan_baku");
$stokBahanMap = [];
foreach ($stmtBahan->fetchAll() as $row) { $stokBahanMap[$row['jenis_bahan']] = $row; }
$stokSemen = $stokBahanMap['Semen'] ?? ['jumlah' => 0, 'satuan' => 'Sak', 'status' => 'Habis'];
$stokPasir = $stokBahanMap['Pasir'] ?? ['jumlah' => 0, 'satuan' => 'm3', 'status' => 'Habis'];

// KPI: PENGELUARAN HARI INI (revisi: harian, bukan bulanan)
$stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal_pengeluaran = ?");
$stmt->execute([$today]);
$pengeluaranHariIni = (int)$stmt->fetchColumn();

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

// KPI: PENGELUARAN BULAN INI
$stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal_pengeluaran BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$pengeluaranBulanIni = (int)$stmt->fetchColumn();

// KPI: GAJI BULAN INI
$stmt = $db->prepare("SELECT COALESCE(SUM(total_gaji), 0) FROM gaji WHERE periode_awal >= ? AND periode_akhir <= ?");
$stmt->execute([$monthStart, $monthEnd]);
$gajiBulanIni = (int)$stmt->fetchColumn();

// KPI: LABA BERSIH BULAN INI
$labaBersih = $pendapatanBulanIni - $pengeluaranBulanIni - $gajiBulanIni;

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
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels30[] = date('d/m', strtotime($date));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]); $pendapatan30[] = (int)$stmt->fetchColumn();
}

// CHART: PENDAPATAN vs PENGELUARAN 7 HARI (revisi 15)
$labels7keu = []; $pendapatan7 = []; $pengeluaran7 = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels7keu[] = date('d/m', strtotime($date));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]); $pendapatan7[] = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal_pengeluaran = ?");
    $stmt->execute([$date]); $pengeluaran7[] = (int)$stmt->fetchColumn();
}

// CHART: LABA BERSIH BULANAN (revisi 15) — 6 bulan terakhir
$labelsBulan = []; $labaBulanan = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-{$i} months"));
    $blnStart = $bln . '-01';
    $blnEnd = date('Y-m-t', strtotime($bln . '-01'));
    $labelsBulan[] = date('M Y', strtotime($bln . '-01'));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
    $stmt->execute([$blnStart, $blnEnd]); $pendapatanBln = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran WHERE tanggal_pengeluaran BETWEEN ? AND ?");
    $stmt->execute([$blnStart, $blnEnd]); $pengeluaranBln = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_gaji), 0) FROM gaji WHERE periode_awal >= ? AND periode_akhir <= ?");
    $stmt->execute([$blnStart, $blnEnd]); $gajiBln = (int)$stmt->fetchColumn();
    $labaBulanan[] = $pendapatanBln - $pengeluaranBln - $gajiBln;
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

<!-- ===== HERO ===== -->
<div class="dash-hero">
    <div>
        <h3>Dashboard Pemilik</h3>
        <p>Pantau seluruh aktivitas operasional percetakan batako secara real-time</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="hero-date"><i class="bi bi-calendar3"></i> <?= date('l, d F Y') ?></span>
        <span class="badge badge-success" style="font-size:11px;padding:7px 14px;"><i class="bi bi-check-circle"></i> Sistem Aktif</span>
    </div>
</div>

<!-- ===== KPI CARDS ===== -->
<div class="row g-4 mb-4">
    <!-- KPI 1: PRODUKSI HARI INI -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon cobalt"><i class="bi bi-gear-wide-connected"></i></div>
                <span class="kpi-trend <?= $deviasi >= 0 ? 'trend-up' : 'trend-down' ?>"><i class="bi bi-arrow-<?= $deviasi >= 0 ? 'up' : 'down' ?>-right"></i> <?= $deviasi ?>% vs jual</span>
            </div>
            <div class="kpi-label">Produksi Hari Ini</div>
            <div class="kpi-value"><?= number_format($produksiHariIni) ?> <small style="font-size:13px;font-weight:600;color:var(--ink-muted);">pcs</small></div>
            <div class="kpi-sub">Standar <strong><?= number_format($prodToday['prod_standar']) ?></strong> &nbsp;·&nbsp; Besar <strong><?= number_format($prodToday['prod_besar']) ?></strong></div>
        </div>
    </div>
    <!-- KPI 2: PENJUALAN HARI INI -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon terracotta"><i class="bi bi-cash-coin"></i></div>
                <span class="kpi-trend trend-flat"><i class="bi bi-cart3"></i> <?= date('d M') ?></span>
            </div>
            <div class="kpi-label">Penjualan Hari Ini</div>
            <div class="kpi-value"><?= number_format($penjualanHariIni) ?> <small style="font-size:13px;font-weight:600;color:var(--ink-muted);">pcs</small></div>
            <div class="kpi-sub">Standar <strong><?= number_format($jualToday['jual_standar']) ?></strong> &nbsp;·&nbsp; Besar <strong><?= number_format($jualToday['jual_besar']) ?></strong></div>
        </div>
    </div>
    <!-- KPI 3: PENDAPATAN HARI INI -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon green"><i class="bi bi-graph-up-arrow"></i></div>
                <span class="kpi-trend trend-flat"><i class="bi bi-wallet2"></i> Kas</span>
            </div>
            <div class="kpi-label">Pendapatan Hari Ini</div>
            <div class="kpi-value" style="font-size:19px;"><?= formatRupiah($pendapatanHariIni) ?></div>
            <div class="kpi-sub">Pengeluaran hari ini <strong><?= formatRupiah($pengeluaranHariIni) ?></strong></div>
        </div>
    </div>
    <!-- KPI 4: STOK BATAKO -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-icon amber"><i class="bi bi-box-seam"></i></div>
                <span class="kpi-trend trend-flat"><i class="bi bi-boxes"></i> <?= count($allStok) ?> jenis</span>
            </div>
            <div class="kpi-label">Stok Batako</div>
            <div class="kpi-value"><?= number_format($totalStok) ?> <small style="font-size:13px;font-weight:600;color:var(--ink-muted);">pcs</small></div>
            <div class="kpi-sub">Standar <strong><?= number_format($stokStandarKPI) ?></strong> &nbsp;·&nbsp; Besar <strong><?= number_format($stokBesarKPI) ?></strong></div>
        </div>
    </div>
</div>

<!-- ===== STOK BAHAN BAKU (revisi 13) ===== -->
<?php
$semenPct = 0; $pasirPct = 0; $semenBar = 'bar-cobalt'; $pasirBar = 'bar-amber';
if ((float)$stokSemen['jumlah'] > 0) { $semenPct = min(100, round(((float)$stokSemen['jumlah']) / 30 * 100)); }
if ((float)$stokPasir['jumlah'] > 0) { $pasirPct = min(100, round(((float)$stokPasir['jumlah']) / 5 * 100)); }
if ((float)$stokSemen['jumlah'] <= 2) { $semenBar = 'bar-red'; } elseif ((float)$stokSemen['jumlah'] <= 10) { $semenBar = 'bar-amber'; }
if ((float)$stokPasir['jumlah'] <= 1) { $pasirBar = 'bar-red'; } elseif ((float)$stokPasir['jumlah'] <= 2) { $pasirBar = 'bar-amber'; }
?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div style="font-size:12px;color:var(--ink-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">Stok Semen</div>
                    <span class="badge <?= $stokSemen['status'] === 'Aman' ? 'badge-success' : ($stokSemen['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($stokSemen['status']) ?></span>
                </div>
                <div style="font-size:24px;font-weight:800;"><?= number_format((float)$stokSemen['jumlah'], 2) ?> <small style="font-size:12px;font-weight:400;color:var(--ink-muted);"><?= e($stokSemen['satuan'] === 'm3' ? 'm³' : e($stokSemen['satuan'])) ?></small></div>
                <div class="progress-neu"><div class="bar <?= $semenBar ?>" style="width:<?= $semenPct ?>%"></div></div>
                <div class="d-flex justify-content-between mt-1" style="font-size:11px;color:var(--ink-muted);">
                    <span>Ambang menipis: 10 Sak</span><span><?= $semenPct ?>% dari target 30 Sak</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div style="font-size:12px;color:var(--ink-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">Stok Pasir</div>
                    <span class="badge <?= $stokPasir['status'] === 'Aman' ? 'badge-success' : ($stokPasir['status'] === 'Menipis' ? 'badge-warning' : 'badge-danger') ?>"><?= e($stokPasir['status']) ?></span>
                </div>
                <div style="font-size:24px;font-weight:800;"><?= number_format((float)$stokPasir['jumlah'], 2) ?> <small style="font-size:12px;font-weight:400;color:var(--ink-muted);"><?= e($stokPasir['satuan'] === 'm3' ? 'm³' : e($stokPasir['satuan'])) ?></small></div>
                <div class="progress-neu"><div class="bar <?= $pasirBar ?>" style="width:<?= $pasirPct ?>%"></div></div>
                <div class="d-flex justify-content-between mt-1" style="font-size:11px;color:var(--ink-muted);">
                    <span>Ambang menipis: 2 m³</span><span><?= $pasirPct ?>% dari target 5 m³</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHART PRODUKSI vs PENJUALAN + TOP PERFORMER ===== -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart-fill"></i> Produksi vs Penjualan (7 Hari Terakhir)</h5>
                <span style="font-size:11px;color:var(--ink-muted);">Perbandingan harian</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:280px;"><canvas id="chartProduksiPenjualan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-trophy-fill" style="color:var(--warn);"></i> Top Pekerja Bulan Ini</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topPekerja)): ?>
                    <div class="p-4 text-muted text-center">Belum ada data produksi bulan ini.</div>
                <?php else: $maxP = max(array_column($topPekerja, 'total_produksi')) ?: 1; ?>
                <?php foreach ($topPekerja as $i => $p): ?>
                <div class="leaderboard-item">
                    <div class="rank <?= $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')) ?>"><?= $i + 1 ?></div>
                    <div style="flex:1;min-width:0;">
                        <div class="lb-name"><?= e($p['nama_pekerja']) ?></div>
                        <div class="lb-meta"><?= number_format($p['total_sak']) ?> sak semen</div>
                        <div class="progress-neu" style="height:6px;margin-top:6px;"><div class="bar bar-cobalt" style="width:<?= round($p['total_produksi'] / $maxP * 100) ?>%"></div></div>
                    </div>
                    <div class="lb-value"><?= number_format($p['total_produksi']) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== PENDAPATAN CHART + RINGKASAN FINANSIAL ===== -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-graph-up-arrow"></i> Pendapatan (30 Hari Terakhir)</h5>
                <span style="font-size:11px;color:var(--ink-muted);">Total: <strong><?= formatRupiah(array_sum($pendapatan30)) ?></strong></span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartPendapatan"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-cash-stack"></i> Ringkasan Bulan Ini</h5>
            </div>
            <div class="card-body">
                <div class="fin-stack">
                    <div class="fin-item">
                        <div class="fin-icon green"><i class="bi bi-box-seam"></i></div>
                        <div style="flex:1;"><div class="fin-label">Produksi</div><div class="fin-value"><?= number_format($produksiBulanIni) ?> pcs</div></div>
                    </div>
                    <div class="fin-item">
                        <div class="fin-icon terracotta"><i class="bi bi-cart3"></i></div>
                        <div style="flex:1;"><div class="fin-label">Penjualan Minggu Ini</div><div class="fin-value"><?= number_format($penjualanMingguIni) ?> pcs</div></div>
                    </div>
                    <div class="fin-item">
                        <div class="fin-icon cobalt"><i class="bi bi-wallet2"></i></div>
                        <div style="flex:1;"><div class="fin-label">Pendapatan Bulan Ini</div><div class="fin-value"><?= formatRupiah($pendapatanBulanIni) ?></div></div>
                    </div>
                    <div class="fin-item">
                        <div class="fin-icon amber"><i class="bi bi-receipt"></i></div>
                        <div style="flex:1;"><div class="fin-label">Pengeluaran Bulan Ini</div><div class="fin-value"><?= formatRupiah($pengeluaranBulanIni) ?></div></div>
                    </div>
                    <div class="fin-item">
                        <div class="fin-icon red"><i class="bi bi-person-gear"></i></div>
                        <div style="flex:1;"><div class="fin-label">Gaji Bulan Ini</div><div class="fin-value"><?= formatRupiah($gajiBulanIni) ?></div></div>
                    </div>
                    <div class="fin-item" style="background:rgba(40,122,90,0.10);border:1px solid rgba(40,122,90,0.25);">
                        <div class="fin-icon green" style="background:var(--good);color:#fff;box-shadow:none;"><i class="bi bi-piggy-bank"></i></div>
                        <div style="flex:1;"><div class="fin-label" style="color:var(--good-deep);">Laba Bersih Bulan Ini</div><div class="fin-value" style="color:var(--good-deep);"><?= formatRupiah($labaBersih) ?></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHART: PENDAPATAN vs PENGELUARAN 7 HARI + LABA BULANAN (revisi 15) ===== -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-arrow-left-right"></i> Pendapatan vs Pengeluaran (7 Hari Terakhir)</h5>
                <span style="font-size:11px;color:var(--ink-muted);">Perbandingan harian</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartKeuangan7"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-pie-chart-fill"></i> Keuntungan Bersih (6 Bulan Terakhir)</h5>
                <span style="font-size:11px;color:var(--ink-muted);">Pendapatan − Pengeluaran − Gaji</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="min-height:250px;"><canvas id="chartLabaBulanan"></canvas></div>
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

// Axis ticks: hindari desimal (Rp 0,9 dst) & label miring saat padat
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

new Chart(document.getElementById('chartProduksiPenjualan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels7) ?>,
        datasets: [{
            label: 'Produksi', data: <?= json_encode($produksi7) ?>,
            borderColor: '#2F67C7', backgroundColor: 'rgba(47,103,199,0.10)',
            fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, spanGaps: true,
        }, {
            label: 'Penjualan', data: <?= json_encode($penjualan7) ?>,
            borderColor: '#C65A32', backgroundColor: 'rgba(198,90,50,0.08)',
            fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, spanGaps: true,
        }]
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

new Chart(document.getElementById('chartPendapatan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels30) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)', data: <?= json_encode($pendapatan30) ?>,
            borderColor: '#287A5A', backgroundColor: 'rgba(40,122,90,0.10)',
            fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 6, borderWidth: 2.5,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => fmtRp(v), maxTicksLimit: 6 } } }
    }
});

new Chart(document.getElementById('chartKeuangan7'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels7keu) ?>,
        datasets: [{
            label: 'Pendapatan', data: <?= json_encode($pendapatan7) ?>,
            borderColor: '#287A5A', backgroundColor: 'rgba(40,122,90,0.08)',
            fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, spanGaps: true,
        }, {
            label: 'Pengeluaran', data: <?= json_encode($pengeluaran7) ?>,
            borderColor: '#B84444', backgroundColor: 'rgba(184,68,68,0.08)',
            fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, spanGaps: true,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + fmtRp(ctx.parsed.y) } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => fmtRp(v), maxTicksLimit: 6 }, grid: { color: 'rgba(39,49,58,0.06)' } } },
        interaction: { mode: 'index', intersect: false }
    }
});

new Chart(document.getElementById('chartLabaBulanan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsBulan) ?>,
        datasets: [{
            label: 'Keuntungan Bersih (Rp)', data: <?= json_encode($labaBulanan) ?>,
            backgroundColor: <?= json_encode(array_map(fn($v) => $v >= 0 ? '#287A5A' : '#B84444', $labaBulanan)) ?>,
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

<?php
// operator/index.php — Beranda Operator (Analytics Dashboard)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Beranda Operator';
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

// ──────────────────────────────────────────────
// KPI — PRODUKSI HARI INI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(realisasi_produksi), 0) as total_produksi,
    COALESCE(SUM(target_produksi), 0) as total_target
    FROM produksi WHERE tanggal_produksi = ?");
$stmt->execute([$today]);
$prodToday = $stmt->fetch();
$produksiHariIni = (int)$prodToday['total_produksi'];
$targetHariIni = (int)$prodToday['total_target'];
$capaianProd = $targetHariIni > 0 ? round(($produksiHariIni / $targetHariIni) * 100, 1) : 0;

// ──────────────────────────────────────────────
// KPI — PENJUALAN HARI INI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(jumlah_terjual), 0) as total_terjual,
    COALESCE(SUM(total_penjualan), 0) as pendapatan
    FROM penjualan WHERE tanggal_penjualan = ?");
$stmt->execute([$today]);
$jualToday = $stmt->fetch();
$penjualanHariIni = (int)$jualToday['total_terjual'];
$pendapatanHariIni = (int)$jualToday['pendapatan'];

// ──────────────────────────────────────────────
// KPI — STOK SAAT INI
// ──────────────────────────────────────────────
$allStok = getAllStok($db);
$totalStok = $allStok['standar'] + $allStok['besar'];

// ──────────────────────────────────────────────
// KPI — BAHAN BAKU HARI INI
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(CASE WHEN jenis_bahan='Semen' THEN jumlah ELSE 0 END), 0) as total_semen,
    COALESCE(SUM(CASE WHEN jenis_bahan='Pasir' THEN jumlah ELSE 0 END), 0) as total_pasir
    FROM bahan_baku WHERE tanggal_penggunaan = ?");
$stmt->execute([$today]);
$bahanToday = $stmt->fetch();

// ──────────────────────────────────────────────
// KPI — PEKERJA AKTIF
// ──────────────────────────────────────────────
$stmt = $db->query("SELECT COUNT(*) as total FROM pekerja WHERE status = 'aktif'");
$pekerjaAktif = (int)$stmt->fetch()['total'];

// ──────────────────────────────────────────────
// KPI — RINGKASAN BULAN INI
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
// CHART — 7 HARI TERAKHIR
// ──────────────────────────────────────────────
$labels7 = [];
$produksi7 = [];
$penjualan7 = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels7[] = date('d/m', strtotime($date));
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) FROM produksi WHERE tanggal_produksi = ?");
    $stmt->execute([$date]);
    $produksi7[] = (int)$stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) FROM penjualan WHERE tanggal_penjualan = ?");
    $stmt->execute([$date]);
    $penjualan7[] = (int)$stmt->fetchColumn();
}

// ──────────────────────────────────────────────
// RECENT DATA
// ──────────────────────────────────────────────
$recentProduksi = $db->query("SELECT p.*, pk.nama_pekerja FROM produksi p LEFT JOIN pekerja pk ON p.pekerja_id = pk.id ORDER BY p.id DESC LIMIT 5")->fetchAll();
$recentPenjualan = $db->query("SELECT * FROM penjualan ORDER BY id DESC LIMIT 5")->fetchAll();
$recentBahan = $db->query("SELECT * FROM bahan_baku ORDER BY id DESC LIMIT 5")->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>

<!-- ===== GREETING ===== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:700;margin-bottom:2px;">Selamat datang, <?= e($_SESSION['name']) ?>! 👋</h4>
        <p style="color:var(--text-muted);margin:0;font-size:13px;">
            <?= date('l, d F Y') ?> — Ringkasan operasional hari ini
        </p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge badge-success" style="font-size:12px;padding:6px 14px;">
            🟢 Operasional Berjalan
        </span>
    </div>
</div>

<!-- ===== KPI CARDS ROW ===== -->
<div class="row g-3 mb-4">
    <!-- Produksi Hari Ini -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon blue" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">🏭</div>
                <?php if ($capaianProd > 0): ?>
                <span class="badge badge-<?= $capaianProd >= 100 ? 'success' : 'warning' ?>" style="font-size:10px;">
                    <?= $capaianProd ?>%
                </span>
                <?php endif; ?>
            </div>
            <div class="stat-label" style="font-size:11px;">Produksi Hari Ini</div>
            <div class="stat-value" style="font-size:20px;"><?= number_format($produksiHariIni) ?></div>
            <div class="stat-sub">Target: <?= number_format($targetHariIni) ?> batako</div>
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
            <div class="stat-sub"><?= formatRupiah($pendapatanHariIni) ?></div>
        </div>
    </div>

    <!-- Pendapatan -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon green" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">💳</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Pendapatan Hari Ini</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($pendapatanHariIni) ?></div>
            <div class="stat-sub">Bulan ini: <?= formatRupiah($pendapatanBulanIni) ?></div>
        </div>
    </div>

    <!-- Stok -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon blue" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">📦</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Stok Tersedia</div>
            <div class="stat-value" style="font-size:20px;"><?= number_format($totalStok) ?></div>
            <div class="stat-sub">S: <?= number_format($allStok['standar']) ?> | B: <?= number_format($allStok['besar']) ?></div>
        </div>
    </div>

    <!-- Bulan Ini -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon orange" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">📊</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Bulan Ini</div>
            <div class="stat-value" style="font-size:16px;"><?= number_format($produksiBulanIni) ?></div>
            <div class="stat-sub">Prod: <?= number_format($produksiBulanIni) ?> | Jual: <?= number_format($penjualanBulanIni) ?></div>
        </div>
    </div>

    <!-- Pekerja Aktif -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon green" style="width:40px;height:40px;font-size:18px;margin-bottom:0;">👷</div>
            </div>
            <div class="stat-label" style="font-size:11px;">Pekerja Aktif</div>
            <div class="stat-value" style="font-size:20px;"><?= $pekerjaAktif ?></div>
            <div class="stat-sub">Tenaga kerja tersedia</div>
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
                <h5><i class="bi bi-pie-chart"></i> Ringkasan Cepat</h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-between" style="gap:16px;">
                <div class="d-flex justify-content-between align-items-center p-3" style="background:rgba(37,99,235,0.05);border-radius:10px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-muted);">Produksi Bulan Ini</div>
                        <div style="font-size:22px;font-weight:700;color:var(--primary);"><?= number_format($produksiBulanIni) ?></div>
                    </div>
                    <div style="font-size:32px;">🏭</div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3" style="background:rgba(217,119,6,0.05);border-radius:10px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-muted);">Penjualan Bulan Ini</div>
                        <div style="font-size:22px;font-weight:700;color:var(--orange);"><?= number_format($penjualanBulanIni) ?></div>
                    </div>
                    <div style="font-size:32px;">💰</div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3" style="background:rgba(22,163,74,0.05);border-radius:10px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-muted);">Pendapatan Bulan Ini</div>
                        <div style="font-size:18px;font-weight:700;color:var(--green);"><?= formatRupiah($pendapatanBulanIni) ?></div>
                    </div>
                    <div style="font-size:32px;">💳</div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3" style="background:rgba(14,165,233,0.05);border-radius:10px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-muted);">Stok Tersedia</div>
                        <div style="font-size:22px;font-weight:700;color:#0EA5E9;"><?= number_format($totalStok) ?></div>
                    </div>
                    <div style="font-size:32px;">📦</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== QUICK ACTION + TOP PERFORMERS ===== -->
<div class="row g-3 mb-4">
    <!-- Quick Actions -->
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="input_bahan_baku.php" class="quick-card" style="padding:20px 16px;">
                    <div class="quick-icon blue" style="width:48px;height:48px;font-size:22px;">📦</div>
                    <h5 style="font-size:14px;">Input Bahan Baku</h5>
                    <p style="font-size:11px;">Catat penggunaan semen &amp; pasir</p>
                </a>
            </div>
            <div class="col-md-4">
                <a href="input_produksi.php" class="quick-card" style="padding:20px 16px;">
                    <div class="quick-icon green" style="width:48px;height:48px;font-size:22px;">🏭</div>
                    <h5 style="font-size:14px;">Input Produksi</h5>
                    <p style="font-size:11px;">Target &amp; realisasi produksi</p>
                </a>
            </div>
            <div class="col-md-4">
                <a href="input_penjualan.php" class="quick-card" style="padding:20px 16px;">
                    <div class="quick-icon orange" style="width:48px;height:48px;font-size:22px;">💰</div>
                    <h5 style="font-size:14px;">Input Penjualan</h5>
                    <p style="font-size:11px;">Catat transaksi penjualan</p>
                </a>
            </div>
        </div>
    </div>
    <!-- Quick Stats -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-clock-history"></i> Aktivitas Terbaru</h5>
            </div>
            <div class="card-body p-0">
                <div style="padding:12px 16px;max-height:180px;overflow-y:auto;">
                    <?php
                    $allRecent = [];
                    foreach ($recentProduksi as $r) {
                        $allRecent[] = ['type' => 'produksi', 'text' => 'Produksi ' . ucfirst($r['ukuran_batako']) . ': ' . number_format($r['realisasi_produksi']) . ' batako', 'date' => $r['tanggal_produksi']];
                    }
                    foreach ($recentPenjualan as $r) {
                        $allRecent[] = ['type' => 'penjualan', 'text' => 'Penjualan ' . ucfirst($r['ukuran_batako']) . ': ' . number_format($r['jumlah_terjual']) . ' pcs', 'date' => $r['tanggal_penjualan']];
                    }
                    usort($allRecent, fn($a, $b) => strcmp($b['date'], $a['date']));
                    $allRecent = array_slice($allRecent, 0, 6);
                    ?>
                    <?php if (empty($allRecent)): ?>
                        <div class="text-muted text-center py-3">Belum ada aktivitas.</div>
                    <?php else: ?>
                        <?php foreach ($allRecent as $act): ?>
                        <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border);">
                            <span style="font-size:18px;"><?= $act['type'] === 'produksi' ? '🏭' : '💰' ?></span>
                            <div style="flex:1;">
                                <div style="font-size:12px;font-weight:500;"><?= e($act['text']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);"><?= formatTanggal($act['date']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== RECENT DATA TABLES ===== -->
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5>📦 Bahan Baku Terbaru</h5>
                <a href="input_bahan_baku.php" style="font-size:12px;color:var(--primary);text-decoration:none;">Lihat Semua →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentBahan)): ?>
                    <div class="p-3 text-muted text-center">Belum ada data.</div>
                <?php else: ?>
                <table class="table-custom">
                    <tbody>
                        <?php foreach ($recentBahan as $rb): ?>
                        <tr>
                            <td><span class="badge <?= $rb['jenis_bahan'] === 'Semen' ? 'badge-primary' : 'badge-warning' ?>"><?= e($rb['jenis_bahan']) ?></span></td>
                            <td><strong><?= number_format($rb['jumlah']) ?></strong> <?= e($rb['satuan']) ?></td>
                            <td class="text-end" style="font-size:11px;color:var(--text-muted);"><?= formatTanggal($rb['tanggal_penggunaan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5>🏭 Produksi Terbaru</h5>
                <a href="input_produksi.php" style="font-size:12px;color:var(--primary);text-decoration:none;">Lihat Semua →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentProduksi)): ?>
                    <div class="p-3 text-muted text-center">Belum ada data.</div>
                <?php else: ?>
                <table class="table-custom">
                    <tbody>
                        <?php foreach ($recentProduksi as $rp): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= ucfirst($rp['ukuran_batako']) ?></span></td>
                            <td><strong><?= number_format($rp['realisasi_produksi']) ?></strong>/<?= number_format($rp['target_produksi']) ?></td>
                            <td class="text-end" style="font-size:11px;color:var(--text-muted);"><?= formatTanggal($rp['tanggal_produksi']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5>💰 Penjualan Terbaru</h5>
                <a href="input_penjualan.php" style="font-size:12px;color:var(--primary);text-decoration:none;">Lihat Semua →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentPenjualan)): ?>
                    <div class="p-3 text-muted text-center">Belum ada data.</div>
                <?php else: ?>
                <table class="table-custom">
                    <tbody>
                        <?php foreach ($recentPenjualan as $rpj): ?>
                        <tr>
                            <td><span class="badge badge-warning"><?= ucfirst($rpj['ukuran_batako']) ?></span></td>
                            <td><strong><?= number_format($rpj['jumlah_terjual']) ?></strong> pcs</td>
                            <td class="text-end" style="font-size:12px;font-weight:600;color:var(--green);"><?= formatRupiah($rpj['total_penjualan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('chartProduksiPenjualan').getContext('2d');
new Chart(ctx, {
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
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

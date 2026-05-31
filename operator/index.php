<?php
// operator/index.php — Beranda Operator
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
// KPI CARD 1 — PRODUKSI HARI INI (BLUE)
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(realisasi_produksi), 0) as total_produksi,
    COALESCE(SUM(target_produksi), 0) as total_target
    FROM produksi WHERE tanggal_produksi = ?");
$stmt->execute([$today]);
$prodToday = $stmt->fetch();
$produksiHariIni = (int)$prodToday['total_produksi'];
$targetHariIni = (int)$prodToday['total_target'];

// ──────────────────────────────────────────────
// KPI CARD 2 — STOK TERSEDIA (GREEN)
// ──────────────────────────────────────────────
$allStok = getAllStok($db);
$totalStok = $allStok['standar'] + $allStok['besar'];

// ──────────────────────────────────────────────
// KPI CARD 3 — PENDAPATAN HARI INI (ORANGE)
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan = ?");
$stmt->execute([$today]);
$pendapatanHariIni = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan), 0) as total FROM penjualan WHERE tanggal_penjualan BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$pendapatanBulanIni = (int)$stmt->fetchColumn();

// ──────────────────────────────────────────────
// KPI CARD 4 — PENGELUARAN HARI INI (RED)
// ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT COALESCE(SUM(nominal), 0) as total FROM pengeluaran WHERE tanggal_pengeluaran = ?");
$stmt->execute([$today]);
$pengeluaranHariIni = (int)$stmt->fetchColumn();

// ──────────────────────────────────────────────
// RECENT DATA
// ──────────────────────────────────────────────
$recentBahan = $db->query("SELECT * FROM bahan_baku ORDER BY id DESC LIMIT 5")->fetchAll();
$recentProduksi = $db->query("SELECT p.*, pk.nama_pekerja FROM produksi p LEFT JOIN pekerja pk ON p.pekerja_id = pk.id ORDER BY p.id DESC LIMIT 5")->fetchAll();
$recentPenjualan = $db->query("SELECT * FROM penjualan ORDER BY id DESC LIMIT 5")->fetchAll();

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
    <span class="badge badge-success" style="font-size:12px;padding:6px 14px;">
        🟢 Operasional Berjalan
    </span>
</div>

<!-- ===== KPI CARDS — EXACT 3-COLUMN ANALISA DESIGN ===== -->
<div class="row g-4 mb-5">
    <!-- CARD 1: PRODUKSI (BLUE) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--primary);"></div>
            <div class="card-body text-center" style="padding:28px 20px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(37,99,235,0.12);">
                    <span style="font-size:26px;">🏭</span>
                </div>
                <div style="font-size:36px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= number_format($produksiHariIni) ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted);font-weight:500;letter-spacing:0.3px;">
                    PRODUKSI HARI INI
                </div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    Target: <?= number_format($targetHariIni) ?> batako
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: STOK TERSEDIA (GREEN) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--green);"></div>
            <div class="card-body text-center" style="padding:28px 20px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(22,163,74,0.12);">
                    <span style="font-size:26px;">📦</span>
                </div>
                <div style="font-size:36px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= number_format($totalStok) ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted);font-weight:500;letter-spacing:0.3px;">
                    STOK TERSEDIA
                </div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    Standar: <?= number_format($allStok['standar']) ?> | Besar: <?= number_format($allStok['besar']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 3: PENDAPATAN (ORANGE) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--orange);"></div>
            <div class="card-body text-center" style="padding:28px 20px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(217,119,6,0.12);">
                    <span style="font-size:26px;">💰</span>
                </div>
                <div style="font-size:28px;font-weight:800;color:var(--text);line-height:1.1;margin-bottom:6px;">
                    <?= formatRupiah($pendapatanHariIni) ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted);font-weight:500;letter-spacing:0.3px;">
                    PENDAPATAN HARI INI
                </div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    Bulan ini: <?= formatRupiah($pendapatanBulanIni) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 4: PENGELUARAN (RED) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
            <div style="height:6px;background:var(--red);"></div>
            <div class="card-body text-center" style="padding:28px 20px 24px;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;border-radius:16px;background:rgba(220,38,38,0.12);">
                    <span style="font-size:26px;">💸</span>
                </div>
                <div style="font-size:28px;font-weight:800;color:var(--red);line-height:1.1;margin-bottom:6px;">
                    <?= formatRupiah($pengeluaranHariIni) ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted);font-weight:500;letter-spacing:0.3px;">
                    PENGELUARAN HARI INI
                </div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;opacity:0.7;">
                    Operasional harian
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== QUICK ACTION CARDS ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="input_bahan_baku.php" class="quick-card" style="padding:24px 20px;">
            <div class="quick-icon blue" style="width:52px;height:52px;font-size:24px;margin-bottom:12px;">📦</div>
            <h5 style="font-size:15px;">Input Bahan Baku</h5>
            <p style="font-size:12px;">Catat penggunaan semen &amp; pasir harian</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="input_produksi.php" class="quick-card" style="padding:24px 20px;">
            <div class="quick-icon green" style="width:52px;height:52px;font-size:24px;margin-bottom:12px;">🏭</div>
            <h5 style="font-size:15px;">Input Produksi Harian</h5>
            <p style="font-size:12px;">Input target &amp; realisasi produksi batako</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="input_penjualan.php" class="quick-card" style="padding:24px 20px;">
            <div class="quick-icon orange" style="width:52px;height:52px;font-size:24px;margin-bottom:12px;">💰</div>
            <h5 style="font-size:15px;">Input Penjualan</h5>
            <p style="font-size:12px;">Catat transaksi penjualan batako</p>
        </a>
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

<?php include __DIR__ . '/../layouts/footer.php'; ?>

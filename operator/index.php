<?php
// operator/index.php — Beranda Operator
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Beranda Operator';
$today = date('Y-m-d');

// Ringkasan hari ini
$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah), 0) as total_bahan FROM bahan_baku WHERE tanggal_penggunaan = ?");
$stmt->execute([$today]);
$bahanHariIni = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi), 0) as total_produksi, COALESCE(SUM(target_produksi), 0) as total_target FROM produksi WHERE tanggal_produksi = ?");
$stmt->execute([$today]);
$prodToday = $stmt->fetch();

$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_terjual), 0) as total_terjual, COALESCE(SUM(total_penjualan), 0) as pendapatan FROM penjualan WHERE tanggal_penjualan = ?");
$stmt->execute([$today]);
$jualToday = $stmt->fetch();

// Recent activities
$recentBahan = $db->query("SELECT * FROM bahan_baku ORDER BY id DESC LIMIT 5")->fetchAll();
$recentProduksi = $db->query("SELECT p.*, pk.nama_pekerja FROM produksi p LEFT JOIN pekerja pk ON p.pekerja_id = pk.id ORDER BY p.id DESC LIMIT 5")->fetchAll();
$recentPenjualan = $db->query("SELECT * FROM penjualan ORDER BY id DESC LIMIT 5")->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>

<!-- Greeting -->
<div class="mb-4">
    <h4 style="font-weight:700;">Selamat datang, <?= e($_SESSION['name']) ?>! 👋</h4>
    <p style="color:var(--text-muted);">Beranda operator — input data harian produksi batako.</p>
</div>

<!-- Quick Action Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <a href="input_bahan_baku.php" class="quick-card">
            <div class="quick-icon blue">📦</div>
            <h5>Input Bahan Baku</h5>
            <p>Catat penggunaan semen &amp; pasir harian</p>
        </a>
    </div>
    <div class="col-lg-4 col-md-6">
        <a href="input_produksi.php" class="quick-card">
            <div class="quick-icon green">🏭</div>
            <h5>Input Produksi Harian</h5>
            <p>Input target &amp; realisasi produksi batako</p>
        </a>
    </div>
    <div class="col-lg-4 col-md-6">
        <a href="input_penjualan.php" class="quick-card">
            <div class="quick-icon orange">💰</div>
            <h5>Input Penjualan</h5>
            <p>Catat transaksi penjualan batako</p>
        </a>
    </div>
</div>

<!-- Ringkasan Hari Ini -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div class="stat-label">Bahan Baku Masuk Hari Ini</div>
            <div class="stat-value"><?= number_format($bahanHariIni, 2) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green">🏭</div>
            <div class="stat-label">Produksi Hari Ini</div>
            <div class="stat-value"><?= number_format($prodToday['total_produksi']) ?></div>
            <div class="stat-sub">Target: <?= number_format($prodToday['total_target']) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange">💰</div>
            <div class="stat-label">Penjualan Hari Ini</div>
            <div class="stat-value"><?= number_format($jualToday['total_terjual']) ?></div>
            <div class="stat-sub"><?= formatRupiah($jualToday['pendapatan']) ?></div>
        </div>
    </div>
</div>

<!-- Recent Data -->
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5>Bahan Baku Terbaru</h5></div>
            <div class="card-body p-0">
                <?php if (empty($recentBahan)): ?>
                    <div class="p-3 text-muted">Belum ada data.</div>
                <?php else: ?>
                <table class="table-custom">
                    <tbody>
                        <?php foreach ($recentBahan as $rb): ?>
                        <tr>
                            <td><span class="badge <?= $rb['jenis_bahan'] === 'Semen' ? 'badge-primary' : 'badge-warning' ?>"><?= e($rb['jenis_bahan']) ?></span></td>
                            <td><?= number_format($rb['jumlah']) ?> <?= e($rb['satuan']) ?></td>
                            <td class="text-end" style="font-size:12px;color:var(--text-muted);"><?= formatTanggal($rb['tanggal_penggunaan']) ?></td>
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
            <div class="card-header"><h5>Produksi Terbaru</h5></div>
            <div class="card-body p-0">
                <?php if (empty($recentProduksi)): ?>
                    <div class="p-3 text-muted">Belum ada data.</div>
                <?php else: ?>
                <table class="table-custom">
                    <tbody>
                        <?php foreach ($recentProduksi as $rp): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= ucfirst($rp['ukuran_batako']) ?></span></td>
                            <td><?= number_format($rp['realisasi_produksi']) ?>/<?= number_format($rp['target_produksi']) ?></td>
                            <td class="text-end" style="font-size:12px;color:var(--text-muted);"><?= formatTanggal($rp['tanggal_produksi']) ?></td>
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
            <div class="card-header"><h5>Penjualan Terbaru</h5></div>
            <div class="card-body p-0">
                <?php if (empty($recentPenjualan)): ?>
                    <div class="p-3 text-muted">Belum ada data.</div>
                <?php else: ?>
                <table class="table-custom">
                    <tbody>
                        <?php foreach ($recentPenjualan as $rpj): ?>
                        <tr>
                            <td><span class="badge badge-warning"><?= ucfirst($rpj['ukuran_batako']) ?></span></td>
                            <td><?= number_format($rpj['jumlah_terjual']) ?> pcs</td>
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

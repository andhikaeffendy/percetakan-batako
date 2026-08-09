<?php
// operator/index.php — Dashboard Operator
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
requireRole('operator');

$db = getDB();
$pageTitle = 'Beranda Operator';
$today = date('Y-m-d');

$stmt = $db->prepare("SELECT COALESCE(SUM(realisasi_produksi),0) FROM produksi WHERE tanggal_produksi=?");
$stmt->execute([$today]); $produksiHariIni = (int)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COALESCE(SUM(target_produksi),0) FROM produksi WHERE tanggal_produksi=?");
$stmt->execute([$today]); $targetHariIni = (int)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COALESCE(SUM(total_penjualan),0) FROM penjualan WHERE tanggal_penjualan=?");
$stmt->execute([$today]); $pendapatanHariIni = (float)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM pengeluaran WHERE tanggal_pengeluaran=?");
$stmt->execute([$today]); $pengeluaranHariIni = (float)$stmt->fetchColumn();
$allStok = getAllStok($db);
$totalStok = array_sum($allStok);
$recentBahan = $db->query("SELECT * FROM bahan_baku ORDER BY id DESC LIMIT 5")->fetchAll();
$recentProduksi = $db->query("SELECT * FROM produksi ORDER BY id DESC LIMIT 5")->fetchAll();
$recentPenjualan = $db->query("SELECT * FROM penjualan ORDER BY id DESC LIMIT 5")->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>
<div class="dash-hero operator-hero">
    <div>
        <span class="hero-kicker"><i class="bi bi-person-workspace"></i> Ruang kerja operator</span>
        <h3>Selamat datang, <?= e($_SESSION['name']) ?></h3>
        <p><?= date('l, d F Y') ?> · Catat operasional harian dengan data yang terhubung ke stok dan laporan.</p>
    </div>
    <div class="hero-status"><i class="bi bi-check2-circle"></i> Operasional aktif</div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-icon blue"><i class="bi bi-bricks"></i></div><div class="stat-label">Produksi hari ini</div><div class="stat-value"><?= number_format($produksiHariIni) ?></div><div class="stat-sub">Target: <?= number_format($targetHariIni) ?> batako</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-box-seam"></i></div><div class="stat-label">Stok tersedia</div><div class="stat-value"><?= number_format($totalStok) ?></div><div class="stat-sub">Standar <?= number_format($allStok['standar'] ?? 0) ?> · Besar <?= number_format($allStok['besar'] ?? 0) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-icon orange"><i class="bi bi-cash-coin"></i></div><div class="stat-label">Pendapatan hari ini</div><div class="stat-value stat-value-money"><?= formatRupiah($pendapatanHariIni) ?></div><div class="stat-sub">Dari transaksi penjualan hari ini</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-icon red"><i class="bi bi-receipt"></i></div><div class="stat-label">Pengeluaran hari ini</div><div class="stat-value stat-value-money"><?= formatRupiah($pengeluaranHariIni) ?></div><div class="stat-sub">Operasional dan kebutuhan harian</div></div></div>
</div>

<div class="section-heading"><div><span class="eyebrow">Aksi cepat</span><h4>Catat aktivitas operasional</h4><p>Pilih jenis transaksi yang ingin dicatat. Setiap input memengaruhi ringkasan terkait secara otomatis.</p></div></div>
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3"><a href="input_bahan_baku.php" class="quick-card"><div class="quick-icon blue"><i class="bi bi-box-seam"></i></div><h5>Bahan Baku</h5><p>Catat pembelian atau penggunaan Semen dan Pasir.</p><span class="quick-link">Buka formulir <i class="bi bi-arrow-right"></i></span></a></div>
    <div class="col-sm-6 col-xl-3"><a href="input_produksi.php" class="quick-card"><div class="quick-icon green"><i class="bi bi-bricks"></i></div><h5>Produksi Harian</h5><p>Input target, realisasi, bahan, dan pekerja.</p><span class="quick-link">Buka formulir <i class="bi bi-arrow-right"></i></span></a></div>
    <div class="col-sm-6 col-xl-3"><a href="input_penjualan.php" class="quick-card"><div class="quick-icon orange"><i class="bi bi-cart-check"></i></div><h5>Penjualan</h5><p>Catat penjualan dan cek stok produk tersedia.</p><span class="quick-link">Buka formulir <i class="bi bi-arrow-right"></i></span></a></div>
    <div class="col-sm-6 col-xl-3"><a href="input_pengeluaran.php" class="quick-card"><div class="quick-icon red"><i class="bi bi-wallet2"></i></div><h5>Pengeluaran</h5><p>Catat biaya operasional untuk laporan keuangan.</p><span class="quick-link">Buka formulir <i class="bi bi-arrow-right"></i></span></a></div>
</div>

<div class="section-heading"><div><span class="eyebrow">Aktivitas terakhir</span><h4>Transaksi terbaru</h4></div></div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card activity-card"><div class="card-header"><h5><i class="bi bi-box-seam"></i> Bahan Baku</h5><a href="input_bahan_baku.php">Input <i class="bi bi-arrow-up-right"></i></a></div><div class="card-body p-0"><?php if (!$recentBahan): ?><div class="empty-state compact"><div class="empty-icon"><i class="bi bi-inbox"></i></div><p>Belum ada bahan baku.</p></div><?php else: ?><div class="activity-list"><?php foreach ($recentBahan as $row): ?><div class="activity-row"><span class="badge <?= $row['jenis_bahan'] === 'Semen' ? 'badge-primary' : 'badge-warning' ?>"><?= e($row['jenis_bahan']) ?></span><strong><?= number_format($row['jumlah']) ?> <?= e($row['satuan']) ?></strong><small><?= formatTanggal($row['tanggal_penggunaan']) ?></small></div><?php endforeach; ?></div><?php endif; ?></div></div></div>
    <div class="col-lg-4"><div class="card activity-card"><div class="card-header"><h5><i class="bi bi-bricks"></i> Produksi</h5><a href="input_produksi.php">Input <i class="bi bi-arrow-up-right"></i></a></div><div class="card-body p-0"><?php if (!$recentProduksi): ?><div class="empty-state compact"><div class="empty-icon"><i class="bi bi-inbox"></i></div><p>Belum ada produksi.</p></div><?php else: ?><div class="activity-list"><?php foreach ($recentProduksi as $row): ?><div class="activity-row"><span class="badge badge-primary"><?= e(ucfirst($row['ukuran_batako'])) ?></span><strong><?= number_format($row['realisasi_produksi']) ?> <small>/ <?= number_format($row['target_produksi']) ?> pcs</small></strong><small><?= formatTanggal($row['tanggal_produksi']) ?></small></div><?php endforeach; ?></div><?php endif; ?></div></div></div>
    <div class="col-lg-4"><div class="card activity-card"><div class="card-header"><h5><i class="bi bi-cart-check"></i> Penjualan</h5><a href="input_penjualan.php">Input <i class="bi bi-arrow-up-right"></i></a></div><div class="card-body p-0"><?php if (!$recentPenjualan): ?><div class="empty-state compact"><div class="empty-icon"><i class="bi bi-inbox"></i></div><p>Belum ada penjualan.</p></div><?php else: ?><div class="activity-list"><?php foreach ($recentPenjualan as $row): ?><div class="activity-row"><span class="badge badge-warning"><?= e(ucfirst($row['ukuran_batako'])) ?></span><strong><?= number_format($row['jumlah_terjual']) ?> pcs</strong><small><?= formatRupiah($row['total_penjualan']) ?></small></div><?php endforeach; ?></div><?php endif; ?></div></div></div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
<!-- ponytail: activity rows use existing 5-item queries; add dedicated activity timeline only when cross-module audit history is required. -->

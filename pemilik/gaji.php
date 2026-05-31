<?php
// pemilik/gaji.php — Perhitungan Gaji Tenaga Kerja (Pemilik)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Perhitungan Gaji';

$pekerjaAktif = $db->query("SELECT * FROM pekerja WHERE status = 'aktif' ORDER BY nama_pekerja")->fetchAll();

// Filter
$periodeAwal = $_GET['periode_awal'] ?? date('Y-m-01');
$periodeAkhir = $_GET['periode_akhir'] ?? date('Y-m-d');

$hasil = [];
$totalSakKeseluruhan = 0;
$totalGajiKeseluruhan = 0;

if (isset($_GET['hitung'])) {
    foreach ($pekerjaAktif as $pk) {
        $stmt = $db->prepare("SELECT 
            COALESCE(SUM(CASE WHEN p.ukuran_batako='standar' THEN p.realisasi_produksi ELSE 0 END), 0) as prod_standar,
            COALESCE(SUM(CASE WHEN p.ukuran_batako='besar' THEN p.realisasi_produksi ELSE 0 END), 0) as prod_besar,
            COALESCE(SUM(p.jumlah_sak_semen), 0) as total_sak
            FROM produksi p WHERE p.pekerja_id = ? AND p.tanggal_produksi BETWEEN ? AND ?");
        $stmt->execute([$pk['id'], $periodeAwal, $periodeAkhir]);
        $data = $stmt->fetch();
        
        $totalGaji = $data['total_sak'] * $pk['tarif_per_sak'];
        $totalSakKeseluruhan += $data['total_sak'];
        $totalGajiKeseluruhan += $totalGaji;
        
        $hasil[] = [
            'nama' => $pk['nama_pekerja'],
            'tarif' => $pk['tarif_per_sak'],
            'prod_standar' => $data['prod_standar'],
            'prod_besar' => $data['prod_besar'],
            'total_sak' => $data['total_sak'],
            'total_gaji' => $totalGaji,
            'pekerja_id' => $pk['id'],
        ];
    }
}

// Simpan ke tabel gaji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    foreach ($hasil as $h) {
        $stmt = $db->prepare("INSERT INTO gaji (pekerja_id, periode_awal, periode_akhir, total_sak_semen, tarif_per_sak, total_gaji) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$h['pekerja_id'], $periodeAwal, $periodeAkhir, $h['total_sak'], $h['tarif'], $h['total_gaji']]);
    }
    redirect('/pemilik/gaji.php?periode_awal=' . $periodeAwal . '&periode_akhir=' . $periodeAkhir . '&hitung=1', 'success', 'Data gaji berhasil disimpan ke database.');
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="card mb-4">
    <div class="card-header"><h5><i class="bi bi-calculator"></i> Filter Periode Gaji</h5></div>
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
                <button type="submit" name="hitung" value="1" class="btn btn-primary w-100">
                    <i class="bi bi-calculator"></i> Hitung Gaji
                </button>
            </div>
            <?php if (!empty($hasil)): ?>
            <div class="col-md-2">
                <form method="POST" style="display:inline;">
                    <button type="submit" name="simpan" value="1" class="btn btn-success w-100">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (!empty($hasil)): ?>
<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">👷</div>
            <div class="stat-label">Total Pekerja</div>
            <div class="stat-value"><?= count($hasil) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange">🧱</div>
            <div class="stat-label">Total Sak Semen</div>
            <div class="stat-value"><?= number_format($totalSakKeseluruhan, 2) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green">💵</div>
            <div class="stat-label">Total Gaji</div>
            <div class="stat-value" style="font-size:18px;"><?= formatRupiah($totalGajiKeseluruhan) ?></div>
        </div>
    </div>
</div>

<!-- Tabel Produksi -->
<div class="card mb-4">
    <div class="card-header"><h5>Tabel Produksi Batako (<?= formatTanggal($periodeAwal) ?> — <?= formatTanggal($periodeAkhir) ?>)</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pekerja</th>
                        <th>Produksi Standar</th>
                        <th>Produksi Besar</th>
                        <th>Total Produksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hasil as $i => $h): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($h['nama']) ?></strong></td>
                        <td><?= number_format($h['prod_standar']) ?></td>
                        <td><?= number_format($h['prod_besar']) ?></td>
                        <td><strong><?= number_format($h['prod_standar'] + $h['prod_besar']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tabel Gaji -->
<div class="card">
    <div class="card-header"><h5>Tabel Perhitungan Gaji per Sak Semen</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pekerja</th>
                        <th>Total Sak Semen</th>
                        <th>Tarif per Sak</th>
                        <th>Total Gaji</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hasil as $i => $h): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($h['nama']) ?></strong></td>
                        <td><?= number_format($h['total_sak'], 2) ?></td>
                        <td><?= formatRupiah($h['tarif']) ?></td>
                        <td><strong style="color:var(--green);"><?= formatRupiah($h['total_gaji']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--background); font-weight:700;">
                        <td colspan="2" class="text-end">TOTAL</td>
                        <td><?= number_format($totalSakKeseluruhan, 2) ?></td>
                        <td></td>
                        <td style="color:var(--green);"><?= formatRupiah($totalGajiKeseluruhan) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif (isset($_GET['hitung'])): ?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Tidak ada data produksi pada periode yang dipilih.</div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

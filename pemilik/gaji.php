<?php
// pemilik/gaji.php — Perhitungan Gaji dengan Panjar
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Perhitungan Gaji';

$pekerjaAktif = $db->query("SELECT * FROM pekerja WHERE status = 'aktif' ORDER BY nama_pekerja")->fetchAll();

$periodeAwal = $_POST['periode_awal'] ?? $_GET['periode_awal'] ?? date('Y-m-01');
$periodeAkhir = $_POST['periode_akhir'] ?? $_GET['periode_akhir'] ?? date('Y-m-d');
$harusHitung = isset($_GET['hitung']) || $_SERVER['REQUEST_METHOD'] === 'POST';

$hasil = [];
$totalSakKeseluruhan = 0;
$totalGajiKeseluruhan = 0;
$totalPanjarKeseluruhan = 0;
$panjarInput = $_POST['panjar'] ?? [];

if ($harusHitung) {
    foreach ($pekerjaAktif as $pk) {
        $stmt = $db->prepare("SELECT 
            COALESCE(SUM(CASE WHEN p.ukuran_batako='standar' THEN p.realisasi_produksi ELSE 0 END), 0) as prod_standar,
            COALESCE(SUM(CASE WHEN p.ukuran_batako='besar' THEN p.realisasi_produksi ELSE 0 END), 0) as prod_besar,
            COALESCE(SUM(p.jumlah_sak_semen), 0) as total_sak
            FROM produksi p WHERE p.pekerja_id = ? AND p.tanggal_produksi BETWEEN ? AND ?");
        $stmt->execute([$pk['id'], $periodeAwal, $periodeAkhir]);
        $data = $stmt->fetch();
        
        $panjar = (int)($panjarInput[$pk['id']] ?? 0);
        $gajiKotor = $data['total_sak'] * $pk['tarif_per_sak'];
        $totalGaji = $gajiKotor - $panjar;
        
        $totalSakKeseluruhan += $data['total_sak'];
        $totalPanjarKeseluruhan += $panjar;
        $totalGajiKeseluruhan += $totalGaji;
        
        $hasil[] = [
            'nama' => $pk['nama_pekerja'],
            'tarif' => $pk['tarif_per_sak'],
            'prod_standar' => $data['prod_standar'],
            'prod_besar' => $data['prod_besar'],
            'total_sak' => $data['total_sak'],
            'panjar' => $panjar,
            'total_gaji' => $totalGaji,
            'pekerja_id' => $pk['id'],
        ];
    }
}

// Simpan ke tabel gaji. $hasil dihitung ulang dari POST di atas, bukan dari state GET yang hilang.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    requireCsrf('/pemilik/gaji.php');
    if (empty($hasil)) {
        redirect('/pemilik/gaji.php?periode_awal=' . urlencode($periodeAwal) . '&periode_akhir=' . urlencode($periodeAkhir) . '&hitung=1', 'warning', 'Tidak ada pekerja aktif untuk disimpan.');
    }

    $operatorId = $_SESSION['user_id'];
    $stmt = $db->prepare("INSERT INTO gaji (pekerja_id, periode_awal, periode_akhir, total_sak_semen, tarif_per_sak, panjar, total_gaji, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $deleteExisting = $db->prepare("DELETE FROM gaji WHERE pekerja_id = ? AND periode_awal = ? AND periode_akhir = ?");
    $db->beginTransaction();
    try {
        foreach ($hasil as $h) {
            // Re-save replaces the same worker's calculated period, preventing duplicates on retry.
            $deleteExisting->execute([$h['pekerja_id'], $periodeAwal, $periodeAkhir]);
            $stmt->execute([$h['pekerja_id'], $periodeAwal, $periodeAkhir, $h['total_sak'], $h['tarif'], $h['panjar'], $h['total_gaji'], $operatorId]);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    redirect('/pemilik/gaji.php?periode_awal=' . urlencode($periodeAwal) . '&periode_akhir=' . urlencode($periodeAkhir) . '&hitung=1', 'success', 'Data gaji berhasil disimpan.');
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="page-toolbar">
    <div><h3>Perhitungan Gaji</h3><p>Hitung kompensasi pekerja berdasarkan pemakaian semen pada periode yang dipilih.</p></div>
</div>
<div class="card mb-4">
    <div class="card-header"><h5><i class="bi bi-calendar-range"></i> Pilih Periode Perhitungan</h5></div>
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
                <button type="submit" name="simpan" value="1" form="formGaji" class="btn btn-success w-100">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (!empty($hasil)): ?>
<form method="POST" id="formGaji">
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
<input type="hidden" name="periode_awal" value="<?= e($periodeAwal) ?>">
<input type="hidden" name="periode_akhir" value="<?= e($periodeAkhir) ?>">
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people"></i></div><div class="stat-label">Total Pekerja</div><div class="stat-value"><?= count($hasil) ?></div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-icon orange"><i class="bi bi-bricks"></i></div><div class="stat-label">Total Sak Semen</div><div class="stat-value"><?= number_format($totalSakKeseluruhan, 2) ?></div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-wallet2"></i></div><div class="stat-label">Total Panjar</div><div class="stat-value" style="font-size:18px;"><?= formatRupiah($totalPanjarKeseluruhan) ?></div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-cash-stack"></i></div><div class="stat-label">Total Gaji Bersih</div><div class="stat-value" style="font-size:18px;"><?= formatRupiah($totalGajiKeseluruhan) ?></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5>Perhitungan Gaji (<?= formatTanggal($periodeAwal) ?> — <?= formatTanggal($periodeAkhir) ?>)</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr><th>No</th><th>Pekerja</th><th>Sak Semen</th><th>Tarif/Sak</th><th>Gaji Kotor</th><th>Panjar</th><th>Gaji Bersih</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($hasil as $i => $h): 
                        $gajiKotor = $h['total_sak'] * $h['tarif'];
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($h['nama']) ?></strong></td>
                        <td><?= number_format($h['total_sak'], 2) ?></td>
                        <td><?= formatRupiah($h['tarif']) ?></td>
                        <td><?= formatRupiah($gajiKotor) ?></td>
                        <td>
                            <input type="number" name="panjar[<?= $h['pekerja_id'] ?>]" value="<?= $h['panjar'] ?>" 
                                   class="form-control form-control-sm" style="width:130px;display:inline;" min="0" 
                                   onchange="this.form.submit()">
                        </td>
                        <td><strong style="color:var(--green);"><?= formatRupiah($h['total_gaji']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--background);font-weight:700;">
                        <td colspan="2">TOTAL</td>
                        <td><?= number_format($totalSakKeseluruhan, 2) ?></td>
                        <td></td>
                        <td></td>
                        <td><?= formatRupiah($totalPanjarKeseluruhan) ?></td>
                        <td style="color:var(--green);"><?= formatRupiah($totalGajiKeseluruhan) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <input type="hidden" name="hitung" value="1">
    </div>
</div>
</form>
<?php elseif (isset($_GET['hitung'])): ?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Tidak ada data produksi pada periode yang dipilih.</div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

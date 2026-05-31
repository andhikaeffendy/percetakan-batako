<?php
// pemilik/laporan_gaji.php — Laporan Gaji
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireRole('pemilik');

$db = getDB();
$pageTitle = 'Laporan Gaji';

$periodeAwal = $_GET['periode_awal'] ?? date('Y-m-01');
$periodeAkhir = $_GET['periode_akhir'] ?? date('Y-m-d');

// Get gaji data (bisa dari tabel gaji yang tersimpan atau fresh calculation)
$detail = [];
$totalGajiKeseluruhan = 0;
$totalSakKeseluruhan = 0;
$totalPanjarKeseluruhan = 0;

if (isset($_GET['tampilkan'])) {
    // Cek dulu tabel gaji
    $stmt = $db->prepare("SELECT g.*, p.nama_pekerja FROM gaji g JOIN pekerja p ON g.pekerja_id = p.id WHERE g.periode_awal = ? AND g.periode_akhir = ? ORDER BY p.nama_pekerja");
    $stmt->execute([$periodeAwal, $periodeAkhir]);
    $detail = $stmt->fetchAll();

    if (empty($detail)) {
        $pekerjaAktif = $db->query("SELECT * FROM pekerja WHERE status = 'aktif' ORDER BY nama_pekerja")->fetchAll();
        foreach ($pekerjaAktif as $pk) {
            $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_sak_semen), 0) as total_sak FROM produksi WHERE pekerja_id = ? AND tanggal_produksi BETWEEN ? AND ?");
            $stmt->execute([$pk['id'], $periodeAwal, $periodeAkhir]);
            $totalSak = $stmt->fetchColumn();
            $totalGaji = $totalSak * $pk['tarif_per_sak'];
            $totalSakKeseluruhan += $totalSak;
            $totalGajiKeseluruhan += $totalGaji;
            $detail[] = [
                'nama_pekerja' => $pk['nama_pekerja'],
                'total_sak_semen' => $totalSak,
                'tarif_per_sak' => $pk['tarif_per_sak'],
                'panjar' => 0,
                'total_gaji' => $totalGaji,
            ];
        }
    } else {
        foreach ($detail as $d) {
            $totalSakKeseluruhan += $d['total_sak_semen'];
            $totalPanjarKeseluruhan += ($d['panjar'] ?? 0);
            $totalGajiKeseluruhan += $d['total_gaji'];
        }
    }
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
    @ini_set('display_errors', '0');
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $html = '<html><head><meta charset="utf-8"><style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        p { text-align: center; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #1E293B; color: white; padding: 8px; text-align: left; }
        td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .summary { margin: 15px 0; }
        .summary td { text-align: center; font-weight: bold; padding: 10px; border: 1px solid #ddd; }
    </style></head><body>';
    $html .= '<h2>Laporan Gaji</h2>';
    $html .= '<p>Percetakan Batako Maros -- ' . formatTanggal($periodeAwal) . ' s/d ' . formatTanggal($periodeAkhir) . '</p>';
    
    $html .= '<table class="summary"><tr>';
    $html .= '<td>Total Pekerja: ' . count($detail) . '</td>';
    $html .= '<td>Total Sak Semen: ' . number_format($totalSakKeseluruhan, 2) . '</td>';
    $html .= '<td>Total Gaji: ' . formatRupiah($totalGajiKeseluruhan) . '</td>';
    $html .= '</tr></table>';
    
    $html .= '<table><thead><tr><th>No</th><th>Nama Pekerja</th><th>Total Sak</th><th>Tarif/Sak</th><th>Total Gaji</th></tr></thead><tbody>';
    foreach ($detail as $i => $d) {
        $html .= '<tr><td>' . ($i + 1) . '</td><td>' . $d['nama_pekerja'] . '</td><td>' . number_format($d['total_sak_semen'], 2) . '</td><td>' . formatRupiah($d['tarif_per_sak']) . '</td><td>' . formatRupiah($d['total_gaji']) . '</td></tr>';
    }
    $html .= '<tr style="font-weight:bold;"><td colspan="2">TOTAL</td><td>' . number_format($totalSakKeseluruhan, 2) . '</td><td></td><td>' . formatRupiah($totalGajiKeseluruhan) . '</td></tr>';
    $html .= '</tbody></table></body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('laporan_gaji_' . date('Ymd') . '.pdf', ['Attachment' => true]);
    exit;
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="card mb-4">
    <div class="card-header"><h5><i class="bi bi-funnel"></i> Filter Laporan Gaji</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Periode Awal</label>
                <input type="date" name="periode_awal" class="form-control" value="<?= e($periodeAwal) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Periode Akhir</label>
                <input type="date" name="periode_akhir" class="form-control" value="<?= e($periodeAkhir) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" name="tampilkan" value="1" class="btn btn-primary w-100"><i class="bi bi-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2">
                <a href="laporan_gaji.php?export=pdf&periode_awal=<?= $periodeAwal ?>&periode_akhir=<?= $periodeAkhir ?>" class="btn btn-danger btn-sm w-100"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($detail)): ?>
<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">👷</div>
            <div class="stat-label">Total Pekerja</div>
            <div class="stat-value"><?= count($detail) ?></div>
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

<div class="card">
    <div class="card-header"><h5>Detail Gaji (<?= formatTanggal($periodeAwal) ?> — <?= formatTanggal($periodeAkhir) ?>)</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr><th>No</th><th>Pekerja</th><th>Sak Semen</th><th>Tarif</th><th>Panjar</th><th>Gaji Bersih</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($detail as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($d['nama_pekerja']) ?></strong></td>
                        <td><?= number_format($d['total_sak_semen'], 2) ?></td>
                        <td><?= formatRupiah($d['tarif_per_sak']) ?></td>
                        <td><strong style="color:var(--green);"><?= formatRupiah($d['total_gaji']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--background);font-weight:700;">
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
<?php elseif (isset($_GET['tampilkan'])): ?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Tidak ada data gaji pada periode tersebut.</div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

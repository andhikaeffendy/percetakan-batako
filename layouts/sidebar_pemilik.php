<?php
// layouts/sidebar_pemilik.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🧱</div>
        <div class="brand-text">
            <h5>Percetakan Batako</h5>
            <span>Maros — Ambon</span>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-heading">Menu Pemilik</li>
        <li>
            <a href="/pemilik/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <span class="menu-icon">📊</span> Dashboard
            </a>
        </li>
        <li>
            <a href="/pemilik/bahan_baku.php" class="<?= $currentPage === 'bahan_baku.php' ? 'active' : '' ?>">
                <span class="menu-icon">📦</span> Bahan Baku
            </a>
        </li>
        <li>
            <a href="/pemilik/produksi.php" class="<?= $currentPage === 'produksi.php' ? 'active' : '' ?>">
                <span class="menu-icon">🏭</span> Data Produksi
            </a>
        </li>
        <li>
            <a href="/pemilik/penjualan.php" class="<?= $currentPage === 'penjualan.php' ? 'active' : '' ?>">
                <span class="menu-icon">💰</span> Data Penjualan
            </a>
        </li>
        <li>
            <a href="/pemilik/tenaga_kerja.php" class="<?= $currentPage === 'tenaga_kerja.php' ? 'active' : '' ?>">
                <span class="menu-icon">👷</span> Data Tenaga Kerja
            </a>
        </li>
        <li>
            <a href="/pemilik/gaji.php" class="<?= $currentPage === 'gaji.php' ? 'active' : '' ?>">
                <span class="menu-icon">💵</span> Perhitungan Gaji
            </a>
        </li>
        <li>
            <a href="/pemilik/pengeluaran.php" class="<?= $currentPage === 'pengeluaran.php' ? 'active' : '' ?>">
                <span class="menu-icon">💸</span> Data Pengeluaran
            </a>
        </li>
        <li class="sidebar-heading">Laporan</li>
        <li>
            <a href="/pemilik/laporan_produksi.php" class="<?= $currentPage === 'laporan_produksi.php' ? 'active' : '' ?>">
                <span class="menu-icon">📈</span> Produksi vs Penjualan
            </a>
        </li>
        <li>
            <a href="/pemilik/laporan_keuangan.php" class="<?= $currentPage === 'laporan_keuangan.php' ? 'active' : '' ?>">
                <span class="menu-icon">💳</span> Laporan Keuangan
            </a>
        </li>
        <li>
            <a href="/pemilik/laporan_gaji.php" class="<?= $currentPage === 'laporan_gaji.php' ? 'active' : '' ?>">
                <span class="menu-icon">📋</span> Laporan Gaji
            </a>
        </li>
        <li class="logout">
            <a href="/logout.php">
                <span class="menu-icon">🚪</span> Logout
            </a>
        </li>
    </ul>
</aside>

<?php
// layouts/sidebar_operator.php
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
        <li class="sidebar-heading">Menu Operator</li>
        <li>
            <a href="/operator/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <span class="menu-icon">🏠</span> Beranda
            </a>
        </li>
        <li>
            <a href="/operator/input_bahan_baku.php" class="<?= $currentPage === 'input_bahan_baku.php' ? 'active' : '' ?>">
                <span class="menu-icon">📦</span> Input Bahan Baku
            </a>
        </li>
        <li>
            <a href="/operator/input_produksi.php" class="<?= $currentPage === 'input_produksi.php' ? 'active' : '' ?>">
                <span class="menu-icon">🏭</span> Input Produksi Harian
            </a>
        </li>
        <li>
            <a href="/operator/input_penjualan.php" class="<?= $currentPage === 'input_penjualan.php' ? 'active' : '' ?>">
                <span class="menu-icon">💰</span> Input Penjualan
            </a>
        </li>
        <li class="logout">
            <a href="/logout.php">
                <span class="menu-icon">🚪</span> Logout
            </a>
        </li>
    </ul>
</aside>

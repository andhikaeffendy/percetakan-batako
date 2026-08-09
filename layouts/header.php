<?php
// layouts/header.php
// Header topbar — digunakan setelah sidebar
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Percetakan Batako Maros') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- MOBILE TOGGLE -->
<div class="sidebar-backdrop"></div>

<!-- SIDEBAR -->
<?php if (isPemilik()): ?>
    <?php include __DIR__ . '/sidebar_pemilik.php'; ?>
<?php elseif (isOperator()): ?>
    <?php include __DIR__ . '/sidebar_operator.php'; ?>
<?php endif; ?>

<!-- MAIN CONTENT -->
<div class="main-content">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="mobile-toggle"><i class="bi bi-list"></i></button>
            <h4 class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></h4>
        </div>
        <div class="user-info">
            <div class="text-end d-none d-sm-block">
                <div class="user-name"><?= e($_SESSION['name'] ?? 'User') ?></div>
                <div class="user-role"><?= e(strtoupper($_SESSION['role'] ?? '')) ?></div>
            </div>
            <div class="user-avatar">
                <?= e(strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1))) ?>
            </div>
        </div>
    </div>

    <!-- CONTENT WRAPPER -->
    <div class="content-wrapper">

    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'x-circle' : 'info-circle') ?>"></i>
        <?= e($flash['message']) ?>
    </div>
    <?php endif; ?>

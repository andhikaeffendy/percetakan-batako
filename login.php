<?php
// login.php — Halaman login
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';

// Jika sudah login, redirect
if (isLoggedIn()) {
    if (isPemilik()) header('Location: /pemilik/dashboard.php');
    else header('Location: /operator/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        // Support login dengan username ATAU email
        $stmt = $db->prepare("SELECT id, name, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'pemilik') {
                header('Location: /pemilik/dashboard.php');
            } else {
                header('Location: /operator/index.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Percetakan Batako Maros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="brand-icon">🧱</div>
            <h2>Percetakan Batako Maros</h2>
            <p>Sistem Informasi Operasional Produksi Batako</p>
        </div>
        <div class="login-body">
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger mb-3"><i class="bi bi-x-circle"></i> <?= e($_GET['error']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><i class="bi bi-x-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username atau Email</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username atau email" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-login mt-2">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>
        </div>
        <div class="login-footer">
            &copy; <?= date('Y') ?> Percetakan Batako Maros — Ambon, Maluku
        </div>
    </div>
</body>
</html>

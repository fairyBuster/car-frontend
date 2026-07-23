<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/admin_auth.php';

if (isAdminAuthenticated()) {
    header('Location: admin-panel.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!loginAdmin($username, $password)) {
        $error = 'Invalid admin username or password.';
    } else {
        header('Location: admin-panel.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body admin-login-body">
<main class="admin-login-shell">
    <section class="admin-login-card">
        <div class="admin-login-brand">
            <span class="admin-login-pill">Admin Panel</span>
            <h1>Aqua Admin Access</h1>
            <p>Sign in with the fixed administrator account to manage users and requests.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="admin-login-alert is-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="admin-login.php" class="admin-login-form" novalidate>
            <label class="admin-login-field" for="admin-username">
                <span>Username</span>
                <input id="admin-username" name="username" type="text" autocomplete="username" required>
            </label>

            <label class="admin-login-field" for="admin-password">
                <span>Password</span>
                <input id="admin-password" name="password" type="password" autocomplete="current-password" required>
            </label>

            <button type="submit" class="admin-login-submit">Log In</button>
        </form>
    </section>
</main>
</body>
</html>

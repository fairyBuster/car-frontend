<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/admin_auth.php';
require __DIR__ . '/includes/deposit.php';
require __DIR__ . '/includes/withdrawal.php';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    logoutAdmin();
    header('Location: admin-login.php');
    exit;
}

requireAdminAuthentication();
ensureDepositSchema($pdo);
ensureWithdrawalSchema($pdo);

function formatAdminRequestStatus(string $status): string
{
    switch ($status) {
        case 'paid':
        case 'approved':
            return 'Approved';
        case 'cancelled':
        case 'rejected':
            return 'Rejected';
        default:
            return 'Pending';
    }
}

$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$pendingDepositCount = (int)$pdo->query("SELECT COUNT(*) FROM deposit_orders WHERE status = 'pending'")->fetchColumn();
$pendingWithdrawalCount = (int)$pdo->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'")->fetchColumn();

$depositStmt = $pdo->query(
    "SELECT d.order_no, d.amount, d.status, u.email
     FROM deposit_orders d
     INNER JOIN users u ON u.id = d.user_id
     ORDER BY d.id DESC
     LIMIT 8"
);
$depositRequests = $depositStmt->fetchAll();

$withdrawalStmt = $pdo->query(
    "SELECT w.request_no, w.amount, w.status, u.email
     FROM withdrawal_requests w
     INNER JOIN users u ON u.id = w.user_id
     ORDER BY w.id DESC
     LIMIT 8"
);
$withdrawalRequests = $withdrawalStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body admin-panel-body">
<header class="dashboard-header admin-header">
    <div class="dashboard-brand" aria-label="Aqua admin brand">
        <span class="dashboard-brand-name">Aqua Admin</span>
    </div>

    <div class="dashboard-actions">
        <span class="admin-header-user"><?= e((string)($_SESSION['admin_username'] ?? 'admin')) ?></span>
        <a href="admin-panel.php?logout=1" class="admin-header-logout">Log Out</a>
    </div>
</header>

<main class="dashboard-main admin-main">
    <section class="admin-hero-card">
        <div class="admin-hero-copy">
            <span class="admin-hero-pill">Overview</span>
            <h1>Admin Dashboard</h1>
            <p>Track members, pending deposit requests, and pending withdrawal requests from one compact panel.</p>
        </div>

        <div class="admin-stat-grid">
            <a href="uyelist.php" class="admin-stat-card admin-stat-link">
                <span>Total Members</span>
                <strong><?= e((string)$userCount) ?></strong>
            </a>
            <a href="deposit-requests.php" class="admin-stat-card admin-stat-link">
                <span>Deposit Requests</span>
                <strong><?= e((string)$pendingDepositCount) ?></strong>
            </a>
            <a href="withdrawal-requests.php" class="admin-stat-card admin-stat-link">
                <span>Withdrawal Requests</span>
                <strong><?= e((string)$pendingWithdrawalCount) ?></strong>
            </a>
        </div>

        <div class="admin-hero-actions">
            <a href="wallet-settings.php" class="admin-shortcut-link">Add Wallet Address</a>
        </div>
    </section>

    <section class="admin-grid">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Deposit Requests</h2>
                    <p>Recent requests with pending count badge.</p>
                </div>
                <span class="admin-count-badge"><?= e((string)$pendingDepositCount) ?></span>
            </div>

            <div class="admin-request-list">
                <?php if (!$depositRequests): ?>
                    <p class="admin-empty-copy">No deposit requests yet.</p>
                <?php else: ?>
                    <?php foreach ($depositRequests as $request): ?>
                        <article class="admin-request-item">
                            <div>
                                <strong><?= e((string)$request['order_no']) ?></strong>
                                <span><?= e((string)$request['email']) ?></span>
                            </div>
                            <div class="admin-request-meta">
                                <span>$<?= e(number_format((float)$request['amount'], 2, '.', ',')) ?></span>
                                <span class="admin-status-badge is-<?= e((string)$request['status']) ?>"><?= e(formatAdminRequestStatus((string)$request['status'])) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Withdrawal Requests</h2>
                    <p>Recent requests with pending count badge.</p>
                </div>
                <span class="admin-count-badge"><?= e((string)$pendingWithdrawalCount) ?></span>
            </div>

            <div class="admin-request-list">
                <?php if (!$withdrawalRequests): ?>
                    <p class="admin-empty-copy">No withdrawal requests yet.</p>
                <?php else: ?>
                    <?php foreach ($withdrawalRequests as $request): ?>
                        <article class="admin-request-item">
                            <div>
                                <strong><?= e((string)$request['request_no']) ?></strong>
                                <span><?= e((string)$request['email']) ?></span>
                            </div>
                            <div class="admin-request-meta">
                                <span>$<?= e(number_format((float)$request['amount'], 2, '.', ',')) ?></span>
                                <span class="admin-status-badge is-<?= e((string)$request['status']) ?>"><?= e(formatAdminRequestStatus((string)$request['status'])) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>
</body>
</html>

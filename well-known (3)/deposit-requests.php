<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/admin_auth.php';
require __DIR__ . '/includes/deposit.php';

requireAdminAuthentication();
ensureDepositSchema($pdo);

$flash = null;
$errors = [];
$csrfToken = getAdminCsrfToken();

function formatAdminDepositStatus(string $status): string
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $action = trim((string)($_POST['action'] ?? ''));
    $orderNo = trim((string)($_POST['order_no'] ?? ''));

    if (!verifyAdminCsrfToken($token)) {
        $errors[] = 'Security check failed. Please refresh and try again.';
    } elseif ($orderNo === '') {
        $errors[] = 'Deposit order number is required.';
    } else {
        if ($action === 'approve_deposit') {
            $result = markDepositOrderAsPaid($pdo, $orderNo, 'admin-manual-approval');
        } elseif ($action === 'reject_deposit') {
            $result = markDepositOrderAsRejected($pdo, $orderNo, 'Deposit request rejected by admin.');
        } else {
            $result = [
                'success' => false,
                'message' => 'Invalid admin action.',
            ];
        }

        if (!empty($result['success'])) {
            $flash = (string)$result['message'];
        } else {
            $errors[] = (string)$result['message'];
        }
    }
}

$pendingDepositCount = (int)$pdo->query("SELECT COUNT(*) FROM deposit_orders WHERE status = 'pending'")->fetchColumn();
$approvedDepositCount = (int)$pdo->query("SELECT COUNT(*) FROM deposit_orders WHERE status = 'paid'")->fetchColumn();
$rejectedDepositCount = (int)$pdo->query("SELECT COUNT(*) FROM deposit_orders WHERE status = 'rejected'")->fetchColumn();

$requestStmt = $pdo->query(
    "SELECT d.order_no, d.amount, d.payment_label, d.network_label, d.status, d.note, d.created_at, u.id AS user_id, u.email
     FROM deposit_orders d
     INNER JOIN users u ON u.id = d.user_id
     ORDER BY
        CASE
            WHEN d.status = 'pending' THEN 0
            WHEN d.status = 'paid' THEN 1
            ELSE 2
        END,
        d.id DESC
     LIMIT 100"
);
$depositRequests = $requestStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Requests</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body admin-panel-body">
<header class="dashboard-header admin-header">
    <div class="dashboard-brand" aria-label="Aqua admin brand">
        <span class="dashboard-brand-name">Aqua Admin</span>
    </div>

    <div class="dashboard-actions">
        <a href="admin-panel.php" class="admin-header-logout">Back</a>
        <a href="admin-panel.php?logout=1" class="admin-header-logout">Log Out</a>
    </div>
</header>

<main class="dashboard-main admin-main">
    <?php if ($errors): ?>
        <section class="admin-banner is-error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($flash !== null): ?>
        <section class="admin-banner is-success">
            <p><?= e($flash) ?></p>
        </section>
    <?php endif; ?>

    <section class="admin-card admin-request-page-card">
        <div class="admin-card-head">
            <div>
                <h2>Request List</h2>
            </div>
            <span class="admin-count-badge"><?= e((string)count($depositRequests)) ?></span>
        </div>

        <div class="admin-request-page-list">
            <?php if (!$depositRequests): ?>
                <p class="admin-empty-copy">No deposit requests found.</p>
            <?php else: ?>
                <?php foreach ($depositRequests as $request): ?>
                    <article class="admin-request-panel">
                        <div class="admin-request-panel-head">
                            <div class="admin-request-panel-copy">
                                <strong><?= e((string)$request['order_no']) ?></strong>
                                <span>User #<?= e((string)$request['user_id']) ?> - <?= e((string)$request['email']) ?></span>
                            </div>
                            <span class="admin-status-badge is-<?= e((string)$request['status']) ?>">
                                <?= e(formatAdminDepositStatus((string)$request['status'])) ?>
                            </span>
                        </div>

                        <div class="admin-request-inline-meta">
                            <span><strong>Amount:</strong> $<?= e(number_format((float)$request['amount'], 2, '.', ',')) ?></span>
                            <span><strong>Method:</strong> <?= e((string)$request['payment_label']) ?></span>
                            <span><strong>Network:</strong> <?= e((string)($request['network_label'] ?: '-')) ?></span>
                            <span><strong>Created:</strong> <?= e(date('d.m.Y H:i', strtotime((string)$request['created_at']))) ?></span>
                        </div>

                        <?php if (!empty($request['note'])): ?>
                            <p class="admin-request-note"><?= e((string)$request['note']) ?></p>
                        <?php endif; ?>

                        <?php if ((string)$request['status'] === 'pending'): ?>
                            <div class="admin-request-actions">
                                <form method="post" action="deposit-requests.php" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="approve_deposit">
                                    <input type="hidden" name="order_no" value="<?= e((string)$request['order_no']) ?>">
                                    <button type="submit" class="admin-action-btn is-positive">Approve Request</button>
                                </form>

                                <form method="post" action="deposit-requests.php" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="reject_deposit">
                                    <input type="hidden" name="order_no" value="<?= e((string)$request['order_no']) ?>">
                                    <button type="submit" class="admin-action-btn is-danger">Reject Request</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="admin-request-complete">
                                <?= e((string)$request['status'] === 'paid' ? 'This request has been approved and the balance was added.' : 'This request was rejected and no balance was added.') ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>

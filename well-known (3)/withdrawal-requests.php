<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/admin_auth.php';
require __DIR__ . '/includes/withdrawal.php';

requireAdminAuthentication();
ensureWithdrawalSchema($pdo);

$flash = null;
$errors = [];
$csrfToken = getAdminCsrfToken();

function formatAdminWithdrawalStatus(string $status): string
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
    $requestNo = trim((string)($_POST['request_no'] ?? ''));

    if (!verifyAdminCsrfToken($token)) {
        $errors[] = 'Security check failed. Please refresh and try again.';
    } elseif ($requestNo === '') {
        $errors[] = 'Withdrawal request number is required.';
    } else {
        if ($action === 'approve_withdrawal') {
            $result = markWithdrawalRequestAsApproved($pdo, $requestNo, 'Withdrawal request approved by admin.');
        } elseif ($action === 'reject_withdrawal') {
            $result = markWithdrawalRequestAsRejected($pdo, $requestNo, 'Withdrawal request rejected by admin.');
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

$requestStmt = $pdo->query(
    "SELECT w.request_no, w.wallet_address, w.amount, w.status, w.note, w.created_at, u.id AS user_id, u.email
     FROM withdrawal_requests w
     INNER JOIN users u ON u.id = w.user_id
     ORDER BY
        CASE
            WHEN w.status = 'pending' THEN 0
            WHEN w.status = 'approved' THEN 1
            ELSE 2
        END,
        w.id DESC
     LIMIT 100"
);
$withdrawalRequests = $requestStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Requests</title>
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
            <span class="admin-count-badge"><?= e((string)count($withdrawalRequests)) ?></span>
        </div>

        <div class="admin-request-page-list">
            <?php if (!$withdrawalRequests): ?>
                <p class="admin-empty-copy">No withdrawal requests found.</p>
            <?php else: ?>
                <?php foreach ($withdrawalRequests as $request): ?>
                    <article class="admin-request-panel">
                        <div class="admin-request-panel-head">
                            <div class="admin-request-panel-copy">
                                <strong><?= e((string)$request['request_no']) ?></strong>
                                <span>User #<?= e((string)$request['user_id']) ?> - <?= e((string)$request['email']) ?></span>
                            </div>
                            <span class="admin-status-badge is-<?= e((string)$request['status']) ?>">
                                <?= e(formatAdminWithdrawalStatus((string)$request['status'])) ?>
                            </span>
                        </div>

                        <div class="admin-request-inline-meta">
                            <span><strong>Amount:</strong> $<?= e(number_format((float)$request['amount'], 2, '.', ',')) ?></span>
                            <span><strong>Wallet:</strong> <?= e((string)$request['wallet_address']) ?></span>
                            <span><strong>Created:</strong> <?= e(date('d.m.Y H:i', strtotime((string)$request['created_at']))) ?></span>
                        </div>

                        <?php if (!empty($request['note'])): ?>
                            <p class="admin-request-note"><?= e((string)$request['note']) ?></p>
                        <?php endif; ?>

                        <?php if ((string)$request['status'] === 'pending'): ?>
                            <div class="admin-request-actions">
                                <form method="post" action="withdrawal-requests.php" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="approve_withdrawal">
                                    <input type="hidden" name="request_no" value="<?= e((string)$request['request_no']) ?>">
                                    <button type="submit" class="admin-action-btn is-positive">Approve Request</button>
                                </form>

                                <form method="post" action="withdrawal-requests.php" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="reject_withdrawal">
                                    <input type="hidden" name="request_no" value="<?= e((string)$request['request_no']) ?>">
                                    <button type="submit" class="admin-action-btn is-danger">Reject Request</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="admin-request-complete">
                                <?= e((string)$request['status'] === 'approved' ? 'This request has been approved and the user balance was reduced.' : 'This request was rejected and the user balance was not changed.') ?>
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

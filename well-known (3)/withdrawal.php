<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';
require __DIR__ . '/includes/withdrawal.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$marketNotificationData = getFishMarketAvailability($pdo, $userId);
$withdrawalData = getWithdrawalDashboardData($pdo, $userId);
$errors = [];
$flash = null;
$amountInput = '';
$walletAddressInput = '';

function formatWithdrawalStatus(string $status): string
{
    switch ($status) {
        case 'approved':
            return 'Approved';
        case 'rejected':
            return 'Rejected';
        default:
            return 'Pending';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amountInput = trim((string)($_POST['amount'] ?? ''));
    $walletAddressInput = trim((string)($_POST['wallet_address'] ?? ''));
    $normalizedAmount = str_replace(',', '.', $amountInput);
    $amount = is_numeric($normalizedAmount) ? (float)$normalizedAmount : 0.0;

    if ($amount <= 0) {
        $errors[] = 'Please enter a valid withdrawal amount.';
    }

    if ($walletAddressInput === '') {
        $errors[] = 'Please enter the wallet address for your withdrawal.';
    }

    if ($amount > $withdrawalData['withdrawable_balance']) {
        $errors[] = 'The withdrawal amount cannot exceed your withdrawable balance.';
    }

    if (!$withdrawalData['passes_feed_rule']) {
        $errors[] = 'You must have used a total of 5 food units before requesting a withdrawal.';
    }

    if (!$errors) {
        try {
            $request = createWithdrawalRequest($pdo, $userId, $amount, $walletAddressInput);
            $flash = [
                'message' => 'Your withdrawal request has been placed in pending review.',
                'request_no' => $request['request_no'],
                'amount' => number_format((float)$request['amount'], 2),
            ];
            $amountInput = '';
            $walletAddressInput = '';
            $withdrawalData = getWithdrawalDashboardData($pdo, $userId);
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            $errors[] = 'The withdrawal request could not be created. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body withdrawal-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-withdrawal" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-withdrawal" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-withdrawal)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-withdrawal)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-withdrawal)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-withdrawal)" stroke-width="4.4" stroke-linecap="round"></path>
        </svg>
        <span class="dashboard-brand-name">Aqua</span>
    </div>

    <div class="dashboard-actions">
        <a href="dashboard.php" class="dashboard-icon-btn" aria-label="Return to home">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14.5 5.5L8 12l6.5 6.5"></path>
                <path d="M9 12h10"></path>
            </svg>
        </a>
        <a href="profile.php" class="dashboard-icon-btn" aria-label="Profile">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="3"></circle>
                <path d="M6.5 18.5c0-3 2.4-5 5.5-5s5.5 2 5.5 5"></path>
            </svg>
        </a>
    </div>
</header>

<?php require __DIR__ . '/includes/market_notification_partial.php'; ?>

<main class="dashboard-main withdrawal-main">
    <section class="withdrawal-panel" aria-label="Withdrawal summary">
        <div class="withdrawal-summary-grid">
            <article class="withdrawal-summary-card">
                <span>Main Balance</span>
                <strong>$<?= e(number_format($withdrawalData['display_balance'], 2)) ?></strong>
            </article>
            <article class="withdrawal-summary-card is-strong">
                <span>Withdrawable Balance</span>
                <strong>$<?= e(number_format($withdrawalData['withdrawable_balance'], 2)) ?></strong>
            </article>
        </div>

        <div class="withdrawal-progress-card">
            <span>5 food usage status</span>
            <strong><?= e((string)$withdrawalData['total_feed_used']) ?> / <?= e((string)$withdrawalData['required_feed_usage']) ?></strong>
            <small>
                <?= $withdrawalData['passes_feed_rule']
                    ? 'The food requirement for withdrawal has been completed.'
                    : e((string)$withdrawalData['remaining_feed_usage']) . ' more food must be used.' ?>
            </small>
        </div>
    </section>

    <?php if ($errors): ?>
        <section class="deposit-alert is-error" aria-label="Withdrawal warning">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($flash): ?>
        <section class="deposit-alert is-success is-autohide" aria-label="Withdrawal info" id="withdrawal-pending-alert">
            <p><?= e((string)$flash['message']) ?></p>
            <p>Request No: <strong><?= e((string)$flash['request_no']) ?></strong></p>
            <p>Amount: <strong>$<?= e((string)$flash['amount']) ?></strong></p>
        </section>
    <?php endif; ?>

    <section class="withdrawal-form-panel" aria-label="Withdrawal form">
        <form method="post" action="withdrawal.php" class="deposit-form" novalidate>
            <label class="deposit-field" for="withdrawal-amount">
                <span class="deposit-field-label">Withdrawal Amount</span>
                <input
                    id="withdrawal-amount"
                    class="deposit-input"
                    type="number"
                    name="amount"
                    min="4"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="Example: 4"
                    value="<?= e($amountInput) ?>"
                    required
                >
            </label>

            <label class="deposit-field" for="withdrawal-wallet-address">
                <span class="deposit-field-label">Wallet Address</span>
                <input
                    id="withdrawal-wallet-address"
                    class="deposit-input"
                    type="text"
                    name="wallet_address"
                    placeholder="Paste the wallet address you want to withdraw to"
                    required
                    value="<?= e($walletAddressInput) ?>"
                >
            </label>

            <button type="submit" class="deposit-submit-btn">Submit Withdrawal Request</button>
        </form>
    </section>

    <section class="deposit-history" aria-label="Withdrawal requests">
        <div class="deposit-section-head">
            <div>
                <h2>Withdrawal History</h2>
                <p>Submitted requests and their statuses</p>
            </div>
        </div>

        <?php if (!$withdrawalData['requests']): ?>
            <article class="deposit-history-empty">
                <p>There is no withdrawal request created yet.</p>
            </article>
        <?php else: ?>
            <div class="deposit-history-list">
                <?php foreach ($withdrawalData['requests'] as $request): ?>
                    <article class="deposit-history-item">
                        <div class="deposit-history-top">
                            <div>
                                <strong><?= e((string)$request['request_no']) ?></strong>
                                <span><?= e((string)$request['wallet_address']) ?></span>
                            </div>
                            <span class="deposit-status is-<?= e((string)$request['status']) ?>"><?= e(formatWithdrawalStatus((string)$request['status'])) ?></span>
                        </div>

                        <div class="deposit-history-bottom">
                            <span>$<?= e(number_format((float)$request['amount'], 2)) ?></span>
                            <span><?= e((string)$request['created_at']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="dashboard-footer" aria-label="Birincil alt gezinme">
    <nav class="dashboard-footer-bar" aria-label="Birincil">
        <a class="dashboard-footer-item" href="dashboard.php">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4 10.5L12 4l8 6.5"></path>
                    <path d="M6.5 9.5v9h11v-9"></path>
                    <path d="M10 18.5v-4h4v4"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">Home</span>
        </a>

        <a class="dashboard-footer-item" href="aquarium.php">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4.5 8.5h15l-1.5 10h-12z"></path>
                    <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                    <path d="M9.5 12.5h5"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">Aquarium</span>
        </a>

        <a class="dashboard-footer-item" href="invite.php">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="9" cy="8" r="2.5"></circle>
                    <path d="M4.5 18.5c0-2.5 2-4.5 4.5-4.5"></path>
                    <path d="M14 10.5h5"></path>
                    <path d="M16.5 8v5"></path>
                    <path d="M12 18.5c1.3-1.8 3.2-2.8 5.5-2.8"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">Invite</span>
        </a>

        <a class="dashboard-footer-item" href="profile.php">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3"></circle>
                    <path d="M6.5 18.5c0-3 2.4-5 5.5-5s5.5 2 5.5 5"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">Profile</span>
        </a>
    </nav>
</footer>
<script>
(() => {
    const pendingAlert = document.getElementById('withdrawal-pending-alert');
    if (!pendingAlert) {
        return;
    }

    window.setTimeout(() => {
        pendingAlert.classList.add('is-hidden');
        window.setTimeout(() => {
            pendingAlert.remove();
        }, 360);
    }, 2600);
})();
</script>
</body>
</html>

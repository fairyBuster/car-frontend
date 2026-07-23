<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';
require __DIR__ . '/includes/deposit.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function renderDepositMethodIcon(string $iconKey): string
{
    switch ($iconKey) {
        case 'wallet':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 7.5h13a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z"></path><path d="M16 13h4"></path><circle cx="16.5" cy="13" r="1"></circle><path d="M6 7.5V6a1.5 1.5 0 0 1 1.5-1.5H17"></path></svg>';
        default:
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.9 5.8H20l-4.9 3.5 1.8 5.7L12 14.5 7.1 18l1.8-5.7L4 8.8h6.1z"></path></svg>';
    }
}

function formatDepositStatus(string $status): string
{
    switch ($status) {
        case 'paid':
            return 'Approved';
        case 'cancelled':
            return 'Cancelled';
        default:
            return 'Pending';
    }
}

$userId = (int)$_SESSION['user_id'];
$marketNotificationData = getFishMarketAvailability($pdo, $userId);

if (!isset($_SESSION['deposit_csrf_token'])) {
    $_SESSION['deposit_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$selectedMethod = 'usdt';
$selectedNetwork = 'trc20';
$amountInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amountInput = trim((string)($_POST['amount'] ?? ''));
    $selectedMethod = (string)($_POST['payment_method'] ?? 'usdt');
    $selectedNetwork = strtolower(trim((string)($_POST['network_key'] ?? 'trc20')));
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals((string)$_SESSION['deposit_csrf_token'], $csrfToken)) {
        $errors[] = 'Security verification failed. Please refresh the page and try again.';
    }

    $normalizedAmount = str_replace(',', '.', $amountInput);
    $amount = is_numeric($normalizedAmount) ? (float)$normalizedAmount : 0.0;

    if (!$errors) {
        try {
            $createdOrder = createDepositOrder($pdo, $userId, $amount, $selectedMethod, $selectedNetwork);
            $_SESSION['deposit_flash'] = [
                'type' => 'success',
                'message' => 'Your deposit request is being reviewed.',
                'order_no' => $createdOrder['order_no'],
                'amount' => number_format((float)$createdOrder['amount'], 2),
                'payment_label' => $createdOrder['payment_label'],
                'network_label' => $createdOrder['network_label'],
            ];
            header('Location: deposit.php?order=' . urlencode($createdOrder['order_no']));
            exit;
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            $errors[] = 'The deposit request could not be created. Please try again.';
        }
    }
}

$flash = null;
if (isset($_SESSION['deposit_flash']) && is_array($_SESSION['deposit_flash'])) {
    $flash = $_SESSION['deposit_flash'];
    unset($_SESSION['deposit_flash']);
}

$depositData = getDepositDashboardData($pdo, $userId);
$walletsByKey = [];
foreach ($depositData['wallets'] as $wallet) {
    $walletsByKey[(string)$wallet['network_key']] = $wallet;
}

if (!isset($walletsByKey[$selectedNetwork])) {
    $selectedNetwork = isset($walletsByKey['trc20']) ? 'trc20' : (string)array_key_first($walletsByKey);
}

$selectedWallet = $walletsByKey[$selectedNetwork] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body deposit-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-deposit" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-deposit" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-deposit" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-deposit)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-deposit)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-deposit)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-deposit)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-deposit)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-deposit)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-deposit)" stroke-width="4.8" stroke-linecap="round"></path>
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

<main class="dashboard-main deposit-main">
    <?php if ($errors): ?>
        <section class="deposit-alert is-error" aria-label="Error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($flash): ?>
        <section class="deposit-alert is-success is-autohide" aria-label="Successful action" id="deposit-pending-alert">
            <p><?= e((string)$flash['message']) ?></p>
            <p>Order No: <strong><?= e((string)$flash['order_no']) ?></strong></p>
            <p>Amount: <strong>$<?= e((string)$flash['amount']) ?></strong> · Method: <strong><?= e((string)$flash['payment_label']) ?></strong> · Network: <strong><?= e((string)($flash['network_label'] ?? '')) ?></strong></p>
        </section>
    <?php endif; ?>

    <section class="deposit-panel" aria-label="Deposit form">
        <form method="post" action="deposit.php" class="deposit-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['deposit_csrf_token']) ?>">
            <input type="hidden" name="payment_method" value="usdt">
            <input type="hidden" name="network_key" id="deposit-network-key" value="<?= e($selectedNetwork) ?>">

            <label class="deposit-field" for="deposit-amount">
                <span class="deposit-field-label">Deposit Amount</span>
                <input
                    id="deposit-amount"
                    class="deposit-input"
                    type="number"
                    name="amount"
                    min="10"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="Example: 10"
                    value="<?= e($amountInput) ?>"
                    required
                >
            </label>

            <section class="deposit-usdt-card" aria-label="USDT payment card">
                <div class="deposit-usdt-head">
                    <span class="deposit-method-icon is-fixed" aria-hidden="true"><?= renderDepositMethodIcon('wallet') ?></span>
                    <div class="deposit-method-copy">
                        <strong>USDT</strong>
                        <small>Only TRC20 and BEB20 networks are supported</small>
                    </div>
                </div>

                <div class="deposit-network-switch" role="tablist" aria-label="Network selection">
                    <?php foreach ($depositData['wallets'] as $wallet): ?>
                        <button
                            type="button"
                            class="deposit-network-btn <?= $selectedNetwork === $wallet['network_key'] ? 'is-active' : '' ?>"
                            data-network-key="<?= e((string)$wallet['network_key']) ?>"
                            data-wallet-address="<?= e((string)$wallet['wallet_address']) ?>"
                            data-wallet-qr="<?= e((string)$wallet['qr_url']) ?>"
                            data-wallet-label="<?= e((string)$wallet['network_label']) ?>"
                        >
                            <?= e((string)$wallet['network_label']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if ($selectedWallet): ?>
                    <div class="deposit-wallet-box">
                        <span class="deposit-wallet-label">Selected Network: <strong id="deposit-network-label"><?= e((string)$selectedWallet['network_label']) ?></strong></span>

                        <div class="deposit-wallet-address-row">
                            <div class="deposit-wallet-address" id="deposit-wallet-address"><?= e((string)$selectedWallet['wallet_address']) ?></div>
                            <button type="button" class="deposit-copy-btn" id="deposit-copy-btn">Copy</button>
                        </div>

                        <div class="deposit-wallet-qr-wrap">
                            <img
                                src="<?= e((string)$selectedWallet['qr_url']) ?>"
                                alt="<?= e((string)$selectedWallet['network_label']) ?> QR code"
                                class="deposit-wallet-qr"
                                id="deposit-wallet-qr"
                            >
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <div class="deposit-flow-note">
                <strong>Flow:</strong>
                <span>Enter the amount, choose the network, complete the transfer, and submit the request for review. Balance is not added until approval.</span>
            </div>

            <button type="submit" class="deposit-submit-btn">Submit Deposit Request</button>
        </form>
    </section>

    <section class="deposit-history" aria-label="Deposit orders">
        <div class="deposit-section-head">
            <div>
                <h2>Deposit History</h2>
                <p>Created orders and payment status</p>
            </div>
        </div>

        <?php if (!$depositData['orders']): ?>
            <article class="deposit-history-empty">
                <p>There is no deposit order created yet.</p>
            </article>
        <?php else: ?>
            <div class="deposit-history-list">
                <?php foreach ($depositData['orders'] as $order): ?>
                    <article class="deposit-history-item">
                        <div class="deposit-history-top">
                            <div>
                                <strong><?= e((string)$order['order_no']) ?></strong>
                                <span><?= e((string)$order['payment_label']) ?> · <?= e((string)($order['network_label'] ?? '-')) ?></span>
                            </div>
                            <span class="deposit-status is-<?= e((string)$order['status']) ?>"><?= e(formatDepositStatus((string)$order['status'])) ?></span>
                        </div>

                        <div class="deposit-history-bottom">
                            <span>$<?= e(number_format((float)$order['amount'], 2)) ?></span>
                            <span><?= e((string)$order['created_at']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="dashboard-footer" aria-label="Primary footer navigation">
    <nav class="dashboard-footer-bar" aria-label="Primary">
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
    const networkButtons = Array.from(document.querySelectorAll('.deposit-network-btn'));
    const networkInput = document.getElementById('deposit-network-key');
    const addressNode = document.getElementById('deposit-wallet-address');
    const qrNode = document.getElementById('deposit-wallet-qr');
    const labelNode = document.getElementById('deposit-network-label');
    const copyButton = document.getElementById('deposit-copy-btn');

    if (!networkButtons.length || !networkInput || !addressNode || !qrNode || !labelNode || !copyButton) {
        return;
    }

    const setActiveNetwork = (button) => {
        networkButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');
        networkInput.value = button.dataset.networkKey || '';
        addressNode.textContent = button.dataset.walletAddress || '';
        qrNode.src = button.dataset.walletQr || '';
        qrNode.alt = (button.dataset.walletLabel || '') + ' QR code';
        labelNode.textContent = button.dataset.walletLabel || '';
    };

    networkButtons.forEach((button) => {
        button.addEventListener('click', () => setActiveNetwork(button));
    });

    copyButton.addEventListener('click', async () => {
        const walletAddress = addressNode.textContent || '';
        if (!walletAddress) {
            return;
        }

        try {
            await navigator.clipboard.writeText(walletAddress);
            copyButton.textContent = 'Copied';
            window.setTimeout(() => {
                copyButton.textContent = 'Copy';
            }, 1500);
        } catch (error) {
            copyButton.textContent = 'Copy';
        }
    });

    const pendingAlert = document.getElementById('deposit-pending-alert');
    if (pendingAlert) {
        window.setTimeout(() => {
            pendingAlert.classList.add('is-hidden');
            window.setTimeout(() => {
                pendingAlert.remove();
            }, 360);
        }, 2600);
    }
})();
</script>
</body>
</html>





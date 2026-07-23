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

$networkOptions = [
    'trc20' => 'USDT TRC20',
    'beb20' => 'USDT BEB20',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $action = trim((string)($_POST['action'] ?? ''));

    if (!verifyAdminCsrfToken($token)) {
        $errors[] = 'Security check failed. Please refresh and try again.';
    } else {
        if ($action === 'save_wallet') {
            $walletId = (int)($_POST['wallet_id'] ?? 0);
            $networkKey = trim((string)($_POST['network_key'] ?? ''));
            $walletAddress = trim((string)($_POST['wallet_address'] ?? ''));
            $networkLabel = $networkOptions[$networkKey] ?? '';

            $result = upsertAdminDepositWallet(
                $pdo,
                $walletId > 0 ? $walletId : null,
                $networkKey,
                $networkLabel,
                $walletAddress,
                $qrPayload
            );
        } elseif ($action === 'delete_wallet') {
            $walletId = (int)($_POST['wallet_id'] ?? 0);
            $result = deleteAdminDepositWallet($pdo, $walletId);
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

$walletRows = getAdminDepositWalletRows($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Settings</title>
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

    <section class="admin-card admin-wallet-card">
        <div class="admin-card-head">
            <div>
                <h2>Wallet Addresses</h2>
                <p>Manage the deposit wallet addresses shown to users.</p>
            </div>
            <span class="admin-count-badge"><?= e((string)count($walletRows)) ?></span>
        </div>

        <div class="admin-wallet-list">
            <?php if (!$walletRows): ?>
                <p class="admin-empty-copy">No wallet addresses found.</p>
            <?php else: ?>
                <?php foreach ($walletRows as $wallet): ?>
                    <?php $saveFormId = 'wallet-save-' . (int)$wallet['id']; ?>
                    <article class="admin-wallet-item">
                        <form method="post" action="wallet-settings.php" class="admin-wallet-form" id="<?= e($saveFormId) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_wallet">
                            <input type="hidden" name="wallet_id" value="<?= e((string)$wallet['id']) ?>">

                            <label class="admin-action-field">
                                <span>Network</span>
                                <select name="network_key" class="admin-wallet-select" required>
                                    <?php foreach ($networkOptions as $networkKey => $networkLabel): ?>
                                        <option value="<?= e($networkKey) ?>" <?= (string)$wallet['network_key'] === $networkKey ? 'selected' : '' ?>>
                                            <?= e($networkLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="admin-action-field">
                                <span>Wallet Address</span>
                                <input type="text" name="wallet_address" value="<?= e((string)$wallet['wallet_address']) ?>" required>
                            </label>
                        </form>

                        <div class="admin-request-actions">
                            <button type="submit" form="<?= e($saveFormId) ?>" class="admin-action-btn is-positive">Save</button>
                            <form method="post" action="wallet-settings.php" class="admin-inline-form" onsubmit="return confirm('Delete this wallet address?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_wallet">
                                <input type="hidden" name="wallet_id" value="<?= e((string)$wallet['id']) ?>">
                                <button type="submit" class="admin-action-btn is-danger">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-card admin-wallet-card">
        <div class="admin-card-head">
            <div>
                <h2>Add Wallet Address</h2>
                <p>Add a new deposit address for USDT TRC20 or USDT BEB20.</p>
            </div>
        </div>

        <form method="post" action="wallet-settings.php" class="admin-wallet-form admin-wallet-form-add">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save_wallet">

            <label class="admin-action-field">
                <span>Network</span>
                <select name="network_key" class="admin-wallet-select" required>
                    <option value="">Choose</option>
                    <?php foreach ($networkOptions as $networkKey => $networkLabel): ?>
                        <option value="<?= e($networkKey) ?>"><?= e($networkLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="admin-action-field">
                <span>Wallet Address</span>
                <input type="text" name="wallet_address" placeholder="Paste wallet address" required>
            </label>

            <button type="submit" class="admin-action-btn is-positive">Add Wallet Address</button>
        </form>
    </section>
</main>
</body>
</html>


<?php
declare(strict_types=1);

require_once __DIR__ . '/referral_rewards.php';
require_once __DIR__ . '/schema_helpers.php';

function getDepositMethodCatalog(): array
{
    return [
        'usdt' => [
            'key' => 'usdt',
            'label' => 'USDT',
            'description' => 'Only TRC20 and BEB20 are supported',
            'icon' => 'wallet',
            'accent' => 'blue',
        ],
    ];
}

function ensureDepositSchema(PDO $pdo): void
{
    ensureReferralRewardSchema($pdo);

    if (!schemaTableExists($pdo, 'deposit_orders')) {
        $pdo->exec(
            'CREATE TABLE deposit_orders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                order_no VARCHAR(32) NOT NULL,
                amount DECIMAL(12, 2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                payment_label VARCHAR(100) NOT NULL,
                network_key VARCHAR(20) NULL,
                network_label VARCHAR(50) NULL,
                status VARCHAR(20) NOT NULL DEFAULT "pending",
                note VARCHAR(255) NULL,
                gateway_reference VARCHAR(120) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_deposit_order_no (order_no),
                KEY idx_deposit_orders_user (user_id),
                CONSTRAINT fk_deposit_orders_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!schemaColumnExists($pdo, 'deposit_orders', 'network_key')) {
        $pdo->exec(
            'ALTER TABLE deposit_orders
             ADD COLUMN network_key VARCHAR(20) NULL
             AFTER payment_label'
        );
    }

    if (!schemaColumnExists($pdo, 'deposit_orders', 'network_label')) {
        $pdo->exec(
            'ALTER TABLE deposit_orders
             ADD COLUMN network_label VARCHAR(50) NULL
             AFTER network_key'
        );
    }

    ensureDepositWalletSchema($pdo);
}

function ensureDepositWalletSchema(PDO $pdo): void
{
    if (!schemaTableExists($pdo, 'deposit_wallets')) {
        $pdo->exec(
            'CREATE TABLE deposit_wallets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                network_key VARCHAR(20) NOT NULL,
                network_label VARCHAR(50) NOT NULL,
                wallet_address VARCHAR(255) NOT NULL,
                qr_payload VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_deposit_wallet_network (network_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    $seedWallets = [
        [
            'network_key' => 'trc20',
            'network_label' => 'TRC20',
            'wallet_address' => 'deneme trc20',
            'qr_payload' => 'deneme trc20',
        ],
        [
            'network_key' => 'beb20',
            'network_label' => 'BEB20',
            'wallet_address' => 'deneme beb20',
            'qr_payload' => 'deneme beb20',
        ],
    ];

    $walletCount = (int)$pdo->query('SELECT COUNT(*) FROM deposit_wallets')->fetchColumn();
    if ($walletCount === 0) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO deposit_wallets (network_key, network_label, wallet_address, qr_payload, is_active)
             VALUES (:network_key, :network_label, :wallet_address, :qr_payload, 1)'
        );

        foreach ($seedWallets as $wallet) {
            $insertStmt->execute($wallet);
        }
    }
}

function getAdminDepositWalletRows(PDO $pdo): array
{
    ensureDepositSchema($pdo);

    $stmt = $pdo->query(
        'SELECT id, network_key, network_label, wallet_address, qr_payload, is_active, updated_at
         FROM deposit_wallets
         ORDER BY FIELD(network_key, "trc20", "beb20"), id ASC'
    );

    return $stmt->fetchAll() ?: [];
}

function upsertAdminDepositWallet(
    PDO $pdo,
    ?int $walletId,
    string $networkKey,
    string $networkLabel,
    string $walletAddress,
    ?string $qrPayload = null
): array {
    ensureDepositSchema($pdo);

    $networkKey = strtolower(trim($networkKey));
    $networkLabel = trim($networkLabel);
    $walletAddress = trim($walletAddress);
    $qrPayload = trim((string)$qrPayload);

    if (!in_array($networkKey, ['trc20', 'beb20'], true)) {
        return [
            'success' => false,
            'message' => 'Please choose a valid network.',
        ];
    }

    if ($walletAddress === '') {
        return [
            'success' => false,
            'message' => 'Wallet address is required.',
        ];
    }

    if ($networkLabel === '') {
        $networkLabel = strtoupper($networkKey);
    }

    if ($qrPayload === '') {
        $qrPayload = $walletAddress;
    }

    try {
        if ($walletId !== null && $walletId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE deposit_wallets
                 SET network_key = :network_key,
                     network_label = :network_label,
                     wallet_address = :wallet_address,
                     qr_payload = :qr_payload,
                     is_active = 1
                 WHERE id = :id'
            );
            $stmt->execute([
                'network_key' => $networkKey,
                'network_label' => $networkLabel,
                'wallet_address' => $walletAddress,
                'qr_payload' => $qrPayload,
                'id' => $walletId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO deposit_wallets (network_key, network_label, wallet_address, qr_payload, is_active)
                 VALUES (:network_key, :network_label, :wallet_address, :qr_payload, 1)'
            );
            $stmt->execute([
                'network_key' => $networkKey,
                'network_label' => $networkLabel,
                'wallet_address' => $walletAddress,
                'qr_payload' => $qrPayload,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Wallet saved successfully.',
        ];
    } catch (Throwable $throwable) {
        return [
            'success' => false,
            'message' => 'This network already exists. Edit the current row instead.',
        ];
    }
}

function deleteAdminDepositWallet(PDO $pdo, int $walletId): array
{
    ensureDepositSchema($pdo);

    if ($walletId <= 0) {
        return [
            'success' => false,
            'message' => 'A valid wallet is required.',
        ];
    }

    $stmt = $pdo->prepare('DELETE FROM deposit_wallets WHERE id = :id');
    $stmt->execute(['id' => $walletId]);

    return [
        'success' => true,
        'message' => 'Wallet deleted successfully.',
    ];
}

function getDepositWallets(PDO $pdo): array
{
    ensureDepositSchema($pdo);

    $stmt = $pdo->query(
        'SELECT network_key, network_label, wallet_address, qr_payload
         FROM deposit_wallets
         WHERE is_active = 1
         ORDER BY FIELD(network_key, "trc20", "beb20"), id ASC'
    );

    $wallets = [];
    foreach ($stmt->fetchAll() as $row) {
        $networkKey = (string)$row['network_key'];
        $payload = (string)($row['qr_payload'] ?? $row['wallet_address']);
        $wallets[$networkKey] = [
            'network_key' => $networkKey,
            'network_label' => (string)$row['network_label'],
            'wallet_address' => (string)$row['wallet_address'],
            'qr_payload' => $payload,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($payload),
        ];
    }

    return $wallets;
}

function generateDepositOrderNumber(PDO $pdo): string
{
    ensureDepositSchema($pdo);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $candidate = 'PAY' . date('YmdHis') . random_int(100, 999);
        $checkStmt = $pdo->prepare('SELECT id FROM deposit_orders WHERE order_no = :order_no LIMIT 1');
        $checkStmt->execute(['order_no' => $candidate]);

        if (!$checkStmt->fetch()) {
            return $candidate;
        }
    }

    throw new RuntimeException('Could not generate a unique deposit order number.');
}

function createDepositOrder(PDO $pdo, int $userId, float $amount, string $paymentMethod, string $networkKey): array
{
    ensureDepositSchema($pdo);
    $methods = getDepositMethodCatalog();
    $wallets = getDepositWallets($pdo);

    if (!isset($methods[$paymentMethod])) {
        throw new InvalidArgumentException('Please choose a valid payment method.');
    }

    if (!isset($wallets[$networkKey])) {
        throw new InvalidArgumentException('Please choose a valid network.');
    }

    if ($amount <= 0) {
        throw new InvalidArgumentException('Please enter a valid deposit amount.');
    }

    $userStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $userId]);

    if (!$userStmt->fetch()) {
        throw new RuntimeException('User not found.');
    }

    $amount = round($amount, 2);
    $orderNo = generateDepositOrderNumber($pdo);
    $method = $methods[$paymentMethod];
    $wallet = $wallets[$networkKey];

    $insertStmt = $pdo->prepare(
        'INSERT INTO deposit_orders (
            user_id, order_no, amount, payment_method, payment_label, network_key, network_label, status, note
         ) VALUES (
            :user_id, :order_no, :amount, :payment_method, :payment_label, :network_key, :network_label, :status, :note
         )'
    );

    $insertStmt->execute([
        'user_id' => $userId,
        'order_no' => $orderNo,
        'amount' => $amount,
        'payment_method' => $method['key'],
        'payment_label' => $method['label'],
        'network_key' => $wallet['network_key'],
        'network_label' => $wallet['network_label'],
        'status' => 'pending',
        'note' => 'Deposit request received and moved to pending review.',
    ]);

    return [
        'order_no' => $orderNo,
        'amount' => $amount,
        'payment_method' => $method['key'],
        'payment_label' => $method['label'],
        'network_key' => $wallet['network_key'],
        'network_label' => $wallet['network_label'],
        'status' => 'pending',
    ];
}

function getDepositDashboardData(PDO $pdo, int $userId): array
{
    ensureDepositSchema($pdo);

    $userStmt = $pdo->prepare(
        'SELECT balance, bonus_balance, referral_balance, invite_code
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $ordersStmt = $pdo->prepare(
        'SELECT order_no, amount, payment_method, payment_label, network_key, network_label, status, note, created_at
         FROM deposit_orders
         WHERE user_id = :user_id
         ORDER BY id DESC
         LIMIT 8'
    );
    $ordersStmt->execute(['user_id' => $userId]);

    return [
        'balance' => (float)$user['balance'],
        'bonus_balance' => isset($user['bonus_balance']) ? (float)$user['bonus_balance'] : 0.0,
        'referral_balance' => isset($user['referral_balance']) ? (float)$user['referral_balance'] : 0.0,
        'trade_balance' => (float)$user['balance'] + (isset($user['bonus_balance']) ? (float)$user['bonus_balance'] : 0.0),
        'user_balance' => (float)$user['balance'],
        'total_balance' => (float)$user['balance']
            + (isset($user['bonus_balance']) ? (float)$user['bonus_balance'] : 0.0)
            + (isset($user['referral_balance']) ? (float)$user['referral_balance'] : 0.0),
        'earned_balance' => (float)$user['balance'],
        'invite_code' => (string)$user['invite_code'],
        'methods' => array_values(getDepositMethodCatalog()),
        'wallets' => array_values(getDepositWallets($pdo)),
        'orders' => $ordersStmt->fetchAll() ?: [],
    ];
}

function markDepositOrderAsPaid(PDO $pdo, string $orderNo, ?string $gatewayReference = null): array
{
    ensureDepositSchema($pdo);

    try {
        $pdo->beginTransaction();

        $orderStmt = $pdo->prepare(
            'SELECT id, user_id, amount, payment_label, status
             FROM deposit_orders
             WHERE order_no = :order_no
             LIMIT 1
             FOR UPDATE'
        );
        $orderStmt->execute(['order_no' => $orderNo]);
        $order = $orderStmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Deposit order not found.',
            ];
        }

        if ((string)$order['status'] === 'paid') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'This deposit request is already approved.',
            ];
        }

        if ((string)$order['status'] !== 'pending') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Only pending deposit requests can be approved.',
            ];
        }

        $userStmt = $pdo->prepare(
            'SELECT balance
             FROM users
             WHERE id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $userStmt->execute(['user_id' => (int)$order['user_id']]);
        $user = $userStmt->fetch();

        if (!$user) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $amount = round((float)$order['amount'], 2);
        $newBalance = round(((float)$user['balance']) + $amount, 2);

        $pdo->prepare(
            'UPDATE users
             SET balance = :balance
             WHERE id = :user_id'
        )->execute([
            'balance' => $newBalance,
            'user_id' => (int)$order['user_id'],
        ]);

        $pdo->prepare(
            'UPDATE deposit_orders
             SET status = :status,
                 gateway_reference = :gateway_reference,
                 note = :note
             WHERE id = :id'
        )->execute([
            'status' => 'paid',
            'gateway_reference' => $gatewayReference,
            'note' => 'Payment approved and balance added.',
            'id' => (int)$order['id'],
        ]);

        $referralReward = applyReferralReward(
            $pdo,
            (int)$order['user_id'],
            $amount,
            'deposit',
            (string)$orderNo,
            '6% commission from the referred user deposit amount.'
        );

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Deposit request approved and balance added.',
            'new_balance' => $newBalance,
            'referral_reward' => $referralReward['reward_amount'],
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'An error occurred while approving the deposit request.',
        ];
    }
}

function markDepositOrderAsRejected(PDO $pdo, string $orderNo, string $note = 'Deposit request rejected.'): array
{
    ensureDepositSchema($pdo);

    try {
        $pdo->beginTransaction();

        $orderStmt = $pdo->prepare(
            'SELECT id, status
             FROM deposit_orders
             WHERE order_no = :order_no
             LIMIT 1
             FOR UPDATE'
        );
        $orderStmt->execute(['order_no' => $orderNo]);
        $order = $orderStmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Deposit order not found.',
            ];
        }

        if ((string)$order['status'] === 'rejected') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'This deposit request is already rejected.',
            ];
        }

        if ((string)$order['status'] !== 'pending') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Only pending deposit requests can be rejected.',
            ];
        }

        $pdo->prepare(
            'UPDATE deposit_orders
             SET status = :status,
                 note = :note
             WHERE id = :id'
        )->execute([
            'status' => 'rejected',
            'note' => $note,
            'id' => (int)$order['id'],
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Deposit request rejected.',
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'An error occurred while rejecting the deposit request.',
        ];
    }
}


<?php
declare(strict_types=1);

require_once __DIR__ . '/deposit.php';
require_once __DIR__ . '/schema_helpers.php';

function ensureWithdrawalSchema(PDO $pdo): void
{
    if (!schemaTableExists($pdo, 'withdrawal_requests')) {
        $pdo->exec(
            'CREATE TABLE withdrawal_requests (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                request_no VARCHAR(32) NOT NULL,
                wallet_address VARCHAR(255) NOT NULL,
                amount DECIMAL(12, 2) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT "pending",
                note VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_withdrawal_request_no (request_no),
                KEY idx_withdrawal_requests_user (user_id),
                CONSTRAINT fk_withdrawal_requests_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}

function generateWithdrawalRequestNumber(PDO $pdo): string
{
    ensureWithdrawalSchema($pdo);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $candidate = 'WDR' . date('YmdHis') . random_int(100, 999);
        $checkStmt = $pdo->prepare('SELECT id FROM withdrawal_requests WHERE request_no = :request_no LIMIT 1');
        $checkStmt->execute(['request_no' => $candidate]);

        if (!$checkStmt->fetch()) {
            return $candidate;
        }
    }

    throw new RuntimeException('Could not generate a unique withdrawal request number.');
}

function createWithdrawalRequest(PDO $pdo, int $userId, float $amount, string $walletAddress): array
{
    ensureWithdrawalSchema($pdo);
    $walletAddress = trim($walletAddress);

    if ($amount <= 0) {
        throw new InvalidArgumentException('Please enter a valid withdrawal amount.');
    }

    if ($walletAddress === '') {
        throw new InvalidArgumentException('Please enter the wallet address for your withdrawal.');
    }

    $requestNo = generateWithdrawalRequestNumber($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO withdrawal_requests (user_id, request_no, wallet_address, amount, status, note)
         VALUES (:user_id, :request_no, :wallet_address, :amount, :status, :note)'
    );

    $stmt->execute([
        'user_id' => $userId,
        'request_no' => $requestNo,
        'wallet_address' => $walletAddress,
        'amount' => round($amount, 2),
        'status' => 'pending',
        'note' => 'Withdrawal request moved to pending review.',
    ]);

    return [
        'request_no' => $requestNo,
        'wallet_address' => $walletAddress,
        'amount' => round($amount, 2),
        'status' => 'pending',
    ];
}

function getWithdrawalDashboardData(PDO $pdo, int $userId): array
{
    ensureDepositSchema($pdo);
    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);
    ensureWithdrawalSchema($pdo);

    $userStmt = $pdo->prepare(
        'SELECT balance, bonus_balance, referral_balance
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $normalBalance = (float)$user['balance'];
    $bonusBalance = isset($user['bonus_balance']) ? (float)$user['bonus_balance'] : 0.0;
    $referralBalance = isset($user['referral_balance']) ? (float)$user['referral_balance'] : 0.0;
    $displayBalance = round($normalBalance + $bonusBalance + $referralBalance, 2);
    $withdrawableBalance = max(0, round($normalBalance + $referralBalance, 2));

    $catchStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(total_catches), 0)
         FROM user_fish_catch_states
         WHERE user_id = :user_id'
    );
    $catchStmt->execute(['user_id' => $userId]);
    $totalFeedUsed = (int)$catchStmt->fetchColumn();

    $requiredFeedUsage = 5;
    $remainingFeedUsage = max(0, $requiredFeedUsage - $totalFeedUsed);
    $passesFeedRule = $totalFeedUsed >= $requiredFeedUsage;
    $canRequestWithdrawal = $withdrawableBalance > 0 && $passesFeedRule;

    $requestStmt = $pdo->prepare(
        'SELECT request_no, wallet_address, amount, status, created_at
         FROM withdrawal_requests
         WHERE user_id = :user_id
         ORDER BY id DESC
         LIMIT 8'
    );
    $requestStmt->execute(['user_id' => $userId]);

    return [
        'display_balance' => $displayBalance,
        'normal_balance' => $normalBalance,
        'bonus_balance' => $bonusBalance,
        'referral_balance' => $referralBalance,
        'withdrawable_balance' => $withdrawableBalance,
        'total_feed_used' => $totalFeedUsed,
        'required_feed_usage' => $requiredFeedUsage,
        'remaining_feed_usage' => $remainingFeedUsage,
        'passes_feed_rule' => $passesFeedRule,
        'can_request_withdrawal' => $canRequestWithdrawal,
        'requests' => $requestStmt->fetchAll() ?: [],
    ];
}

function markWithdrawalRequestAsApproved(PDO $pdo, string $requestNo, string $note = 'Withdrawal request approved.'): array
{
    ensureWithdrawalSchema($pdo);

    try {
        $pdo->beginTransaction();

        $requestStmt = $pdo->prepare(
            'SELECT id, user_id, amount, status
             FROM withdrawal_requests
             WHERE request_no = :request_no
             LIMIT 1
             FOR UPDATE'
        );
        $requestStmt->execute(['request_no' => $requestNo]);
        $request = $requestStmt->fetch();

        if (!$request) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Withdrawal request not found.',
            ];
        }

        if ((string)$request['status'] === 'approved') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'This withdrawal request is already approved.',
            ];
        }

        if ((string)$request['status'] !== 'pending') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Only pending withdrawal requests can be approved.',
            ];
        }

        $userStmt = $pdo->prepare(
            'SELECT balance, referral_balance
             FROM users
             WHERE id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $userStmt->execute(['user_id' => (int)$request['user_id']]);
        $user = $userStmt->fetch();

        if (!$user) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $amount = round((float)$request['amount'], 2);
        $mainBalance = round((float)$user['balance'], 2);
        $referralBalance = round((float)$user['referral_balance'], 2);
        $withdrawableBalance = round($mainBalance + $referralBalance, 2);

        if ($withdrawableBalance < $amount) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'User does not have enough withdrawable balance.',
            ];
        }

        $remainingAmount = $amount;
        $newReferralBalance = $referralBalance;
        $newMainBalance = $mainBalance;

        if ($newReferralBalance > 0) {
            $referralUsed = min($newReferralBalance, $remainingAmount);
            $newReferralBalance = round($newReferralBalance - $referralUsed, 2);
            $remainingAmount = round($remainingAmount - $referralUsed, 2);
        }

        if ($remainingAmount > 0) {
            $newMainBalance = round(max(0, $newMainBalance - $remainingAmount), 2);
        }

        $pdo->prepare(
            'UPDATE users
             SET balance = :balance,
                 referral_balance = :referral_balance
             WHERE id = :user_id'
        )->execute([
            'balance' => $newMainBalance,
            'referral_balance' => $newReferralBalance,
            'user_id' => (int)$request['user_id'],
        ]);

        $pdo->prepare(
            'UPDATE withdrawal_requests
             SET status = :status,
                 note = :note
             WHERE id = :id'
        )->execute([
            'status' => 'approved',
            'note' => $note,
            'id' => (int)$request['id'],
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Withdrawal request approved.',
            'new_balance' => $newMainBalance,
            'new_referral_balance' => $newReferralBalance,
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'An error occurred while approving the withdrawal request.',
        ];
    }
}

function markWithdrawalRequestAsRejected(PDO $pdo, string $requestNo, string $note = 'Withdrawal request rejected.'): array
{
    ensureWithdrawalSchema($pdo);

    try {
        $pdo->beginTransaction();

        $requestStmt = $pdo->prepare(
            'SELECT id, status
             FROM withdrawal_requests
             WHERE request_no = :request_no
             LIMIT 1
             FOR UPDATE'
        );
        $requestStmt->execute(['request_no' => $requestNo]);
        $request = $requestStmt->fetch();

        if (!$request) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Withdrawal request not found.',
            ];
        }

        if ((string)$request['status'] === 'rejected') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'This withdrawal request is already rejected.',
            ];
        }

        if ((string)$request['status'] !== 'pending') {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Only pending withdrawal requests can be rejected.',
            ];
        }

        $pdo->prepare(
            'UPDATE withdrawal_requests
             SET status = :status,
                 note = :note
             WHERE id = :id'
        )->execute([
            'status' => 'rejected',
            'note' => $note,
            'id' => (int)$request['id'],
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Withdrawal request rejected.',
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'An error occurred while rejecting the withdrawal request.',
        ];
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/schema_helpers.php';

function ensureReferralRewardSchema(PDO $pdo): void
{
    if (!schemaColumnExists($pdo, 'users', 'referral_balance')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN referral_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00
             AFTER bonus_balance'
        );
    }

    if (!schemaTableExists($pdo, 'referral_reward_logs')) {
        $pdo->exec(
            'CREATE TABLE referral_reward_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                beneficiary_user_id BIGINT UNSIGNED NOT NULL,
                source_user_id BIGINT UNSIGNED NOT NULL,
                source_type VARCHAR(32) NOT NULL,
                source_ref VARCHAR(100) NOT NULL,
                base_amount DECIMAL(12, 2) NOT NULL,
                reward_rate DECIMAL(5, 2) NOT NULL,
                reward_amount DECIMAL(12, 2) NOT NULL,
                description VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_referral_reward_source (beneficiary_user_id, source_type, source_ref),
                KEY idx_referral_reward_beneficiary (beneficiary_user_id),
                CONSTRAINT fk_referral_reward_beneficiary
                    FOREIGN KEY (beneficiary_user_id) REFERENCES users(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT fk_referral_reward_source_user
                    FOREIGN KEY (source_user_id) REFERENCES users(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}

function applyReferralReward(
    PDO $pdo,
    int $sourceUserId,
    float $baseAmount,
    string $sourceType,
    string $sourceRef,
    string $description
): array {
    ensureReferralRewardSchema($pdo);

    if ($baseAmount <= 0) {
        return [
            'applied' => false,
            'reward_amount' => 0.0,
            'beneficiary_user_id' => null,
            'new_referral_balance' => null,
        ];
    }

    $sourceUserStmt = $pdo->prepare(
        'SELECT invited_by_user_id
         FROM users
         WHERE id = :user_id
         LIMIT 1'
    );
    $sourceUserStmt->execute(['user_id' => $sourceUserId]);
    $sourceUser = $sourceUserStmt->fetch();

    if (!$sourceUser || empty($sourceUser['invited_by_user_id'])) {
        return [
            'applied' => false,
            'reward_amount' => 0.0,
            'beneficiary_user_id' => null,
            'new_referral_balance' => null,
        ];
    }

    $beneficiaryUserId = (int)$sourceUser['invited_by_user_id'];
    $rewardRate = 6.00;
    $rewardAmount = round($baseAmount * ($rewardRate / 100), 2);

    if ($rewardAmount <= 0) {
        return [
            'applied' => false,
            'reward_amount' => 0.0,
            'beneficiary_user_id' => $beneficiaryUserId,
            'new_referral_balance' => null,
        ];
    }

    $existingRewardStmt = $pdo->prepare(
        'SELECT id
         FROM referral_reward_logs
         WHERE beneficiary_user_id = :beneficiary_user_id
           AND source_type = :source_type
           AND source_ref = :source_ref
         LIMIT 1'
    );
    $existingRewardStmt->execute([
        'beneficiary_user_id' => $beneficiaryUserId,
        'source_type' => $sourceType,
        'source_ref' => $sourceRef,
    ]);

    if ($existingRewardStmt->fetch()) {
        $balanceStmt = $pdo->prepare(
            'SELECT referral_balance
             FROM users
             WHERE id = :user_id
             LIMIT 1'
        );
        $balanceStmt->execute(['user_id' => $beneficiaryUserId]);
        $beneficiary = $balanceStmt->fetch();

        return [
            'applied' => false,
            'reward_amount' => $rewardAmount,
            'beneficiary_user_id' => $beneficiaryUserId,
            'new_referral_balance' => $beneficiary ? (float)$beneficiary['referral_balance'] : null,
        ];
    }

    $beneficiaryStmt = $pdo->prepare(
        'SELECT referral_balance
         FROM users
         WHERE id = :user_id
         LIMIT 1
         FOR UPDATE'
    );
    $beneficiaryStmt->execute(['user_id' => $beneficiaryUserId]);
    $beneficiary = $beneficiaryStmt->fetch();

    if (!$beneficiary) {
        return [
            'applied' => false,
            'reward_amount' => 0.0,
            'beneficiary_user_id' => null,
            'new_referral_balance' => null,
        ];
    }

    $newReferralBalance = round(((float)$beneficiary['referral_balance']) + $rewardAmount, 2);

    $pdo->prepare(
        'UPDATE users
         SET referral_balance = :referral_balance
         WHERE id = :user_id'
    )->execute([
        'referral_balance' => $newReferralBalance,
        'user_id' => $beneficiaryUserId,
    ]);

    $pdo->prepare(
        'INSERT INTO referral_reward_logs (
            beneficiary_user_id,
            source_user_id,
            source_type,
            source_ref,
            base_amount,
            reward_rate,
            reward_amount,
            description
        ) VALUES (
            :beneficiary_user_id,
            :source_user_id,
            :source_type,
            :source_ref,
            :base_amount,
            :reward_rate,
            :reward_amount,
            :description
        )'
    )->execute([
        'beneficiary_user_id' => $beneficiaryUserId,
        'source_user_id' => $sourceUserId,
        'source_type' => $sourceType,
        'source_ref' => $sourceRef,
        'base_amount' => round($baseAmount, 2),
        'reward_rate' => $rewardRate,
        'reward_amount' => $rewardAmount,
        'description' => $description,
    ]);

    return [
        'applied' => true,
        'reward_amount' => $rewardAmount,
        'beneficiary_user_id' => $beneficiaryUserId,
        'new_referral_balance' => $newReferralBalance,
    ];
}

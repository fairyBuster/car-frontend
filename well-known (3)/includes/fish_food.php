<?php
declare(strict_types=1);

require_once __DIR__ . '/referral_rewards.php';
require_once __DIR__ . '/schema_helpers.php';

function getFishFoodCatalog(): array
{
    return [
        [
            'key' => 'guppy',
            'name' => 'Guppy',
            'image' => 'akvaryum/Guppy.webp',
            'image_alt' => 'Guppy fish',
            'tags' => ['Freshwater', 'Peaceful'],
            'level_key' => 'easy',
            'level_label' => 'Easy Level',
            'referral_requirement' => 1,
            'reward_days' => 2,
            'unlock_balance' => 10.00,
            'purchase_price' => 1.00,
            'signup_bonus' => true,
        ],
        [
            'key' => 'neon-tetra',
            'name' => 'Neon Tetra',
            'image' => 'akvaryum/neontera.jpg',
            'image_alt' => 'Neon Tetra fish',
            'tags' => ['Amazon', 'Schooling'],
            'level_key' => 'easy',
            'level_label' => 'Easy Level',
            'referral_requirement' => 0,
            'reward_days' => 2,
            'unlock_balance' => 30.00,
            'purchase_price' => 2.00,
            'signup_bonus' => false,
        ],
        [
            'key' => 'lepistes',
            'name' => 'Troud',
            'image' => 'akvaryum/troud.jpg',
            'image_alt' => 'Lepistes fish',
            'tags' => ['Freshwater', 'Hardy'],
            'level_key' => 'medium',
            'level_label' => 'Medium Level',
            'referral_requirement' => 0,
            'reward_days' => 3,
            'unlock_balance' => 80.00,
            'purchase_price' => 3.00,
            'signup_bonus' => false,
        ],
        [
            'key' => 'angelfish',
            'name' => 'Angelfish',
            'image' => 'akvaryum/melek-baligi.jpg',
            'image_alt' => 'Angelfish',
            'tags' => ['Freshwater', 'Semi-peaceful'],
            'level_key' => 'medium',
            'level_label' => 'Medium Level',
            'referral_requirement' => 0,
            'reward_days' => 3,
            'unlock_balance' => 150.00,
            'purchase_price' => 4.00,
            'signup_bonus' => false,
        ],
        [
            'key' => 'discus',
            'name' => 'Discus Fish',
            'image' => 'akvaryum/diskus.jpg',
            'image_alt' => 'Discus Fish',
            'tags' => ['Freshwater', 'Peaceful'],
            'level_key' => 'hard',
            'level_label' => 'Hard Level',
            'referral_requirement' => 2,
            'reward_days' => 3,
            'unlock_balance' => 250.00,
            'purchase_price' => 5.00,
            'signup_bonus' => false,
        ],
        [
            'key' => 'oscar',
            'name' => 'Oscar Fish',
            'image' => 'akvaryum/oscar-baligi.webp',
            'image_alt' => 'Oscar Fish',
            'tags' => ['Freshwater', 'Semi-aggressive'],
            'level_key' => 'hard',
            'level_label' => 'Hard Level',
            'referral_requirement' => 4,
            'reward_days' => 3,
            'unlock_balance' => 400.00,
            'purchase_price' => 5.00,
            'signup_bonus' => false,
        ],
    ];
}

function getFishMarketRates(): array
{
    return [
        'guppy' => 4.00,
        'neon-tetra' => 4.6,
        'lepistes' => 5.0,
        'angelfish' => 5.6,
        'discus' => 6.3,
        'oscar' => 7.00,
    ];
}

function ensureFishFoodSchema(PDO $pdo): void
{
    if (!schemaTableExists($pdo, 'user_fish_foods')) {
        $pdo->exec(
            'CREATE TABLE user_fish_foods (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                fish_key VARCHAR(50) NOT NULL,
                is_unlocked TINYINT(1) NOT NULL DEFAULT 0,
                unlock_source VARCHAR(32) NOT NULL DEFAULT "rule",
                bonus_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                claimed_open_bonus TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_user_fish_food (user_id, fish_key),
                CONSTRAINT fk_user_fish_foods_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_foods', 'is_unlocked')) {
        $pdo->exec(
            'ALTER TABLE user_fish_foods
             ADD COLUMN is_unlocked TINYINT(1) NOT NULL DEFAULT 0
             AFTER fish_key'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_foods', 'unlock_source')) {
        $pdo->exec(
            'ALTER TABLE user_fish_foods
             ADD COLUMN unlock_source VARCHAR(32) NOT NULL DEFAULT "rule"
             AFTER is_unlocked'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_foods', 'bonus_days')) {
        $pdo->exec(
            'ALTER TABLE user_fish_foods
             ADD COLUMN bonus_days SMALLINT UNSIGNED NOT NULL DEFAULT 0
             AFTER unlock_source'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_foods', 'claimed_open_bonus')) {
        $pdo->exec(
            'ALTER TABLE user_fish_foods
             ADD COLUMN claimed_open_bonus TINYINT(1) NOT NULL DEFAULT 0
             AFTER bonus_days'
        );
    }
}

function seedUserFishFoodAccess(PDO $pdo, int $userId): void
{
    ensureFishFoodSchema($pdo);

    $selectStmt = $pdo->prepare('SELECT fish_key FROM user_fish_foods WHERE user_id = :user_id');
    $selectStmt->execute(['user_id' => $userId]);
    $existingKeys = array_flip(array_map('strval', $selectStmt->fetchAll(PDO::FETCH_COLUMN)));

    $insertStmt = $pdo->prepare(
        'INSERT INTO user_fish_foods (user_id, fish_key, is_unlocked, unlock_source, bonus_days, claimed_open_bonus)
         VALUES (:user_id, :fish_key, :is_unlocked, :unlock_source, :bonus_days, :claimed_open_bonus)'
    );

    foreach (getFishFoodCatalog() as $fish) {
        if (isset($existingKeys[$fish['key']])) {
            continue;
        }

        $isSignupBonus = $fish['signup_bonus'] === true;

        $insertStmt->execute([
            'user_id' => $userId,
            'fish_key' => $fish['key'],
            'is_unlocked' => $isSignupBonus ? 1 : 0,
            'unlock_source' => $isSignupBonus ? 'signup_bonus' : 'rule',
            'bonus_days' => $isSignupBonus ? 5 : 0,
            'claimed_open_bonus' => $isSignupBonus ? 1 : 0,
        ]);
    }
}

function getFishFoodDashboardData(PDO $pdo, int $userId): array
{
    ensureFishFoodSchema($pdo);
    seedUserFishFoodAccess($pdo, $userId);

    $userStmt = $pdo->prepare('SELECT balance, bonus_balance, invite_code FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $balance = (float)$user['balance'];
    $bonusBalance = isset($user['bonus_balance']) ? (float)$user['bonus_balance'] : 0.0;
    $tradeBalance = $balance + $bonusBalance;
    $inviteCode = (string)$user['invite_code'];
    $approvedDepositTotal = getUserApprovedDepositTotal($pdo, $userId);

    $referralStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE invited_by_user_id = :user_id');
    $referralStmt->execute(['user_id' => $userId]);
    $referralCount = (int)$referralStmt->fetchColumn();

    $statusStmt = $pdo->prepare(
        'SELECT fish_key, is_unlocked, unlock_source, bonus_days, claimed_open_bonus
         FROM user_fish_foods
         WHERE user_id = :user_id'
    );
    $statusStmt->execute(['user_id' => $userId]);

    $statusByKey = [];
    foreach ($statusStmt->fetchAll() as $row) {
        $statusByKey[(string)$row['fish_key']] = $row;
    }

    $grantStmt = $pdo->prepare(
        'UPDATE user_fish_foods
         SET is_unlocked = 1,
             unlock_source = :unlock_source,
             bonus_days = bonus_days + 5,
             claimed_open_bonus = 1
         WHERE user_id = :user_id AND fish_key = :fish_key'
    );
    $relockStmt = $pdo->prepare(
        'UPDATE user_fish_foods
         SET is_unlocked = 0,
             unlock_source = "rule"
         WHERE user_id = :user_id AND fish_key = :fish_key'
    );

    $cards = [];

    foreach (getFishFoodCatalog() as $fish) {
        $status = $statusByKey[$fish['key']] ?? [
            'is_unlocked' => 0,
            'unlock_source' => 'rule',
            'bonus_days' => 0,
            'claimed_open_bonus' => 0,
        ];

        $meetsBalance = $fish['signup_bonus'] === true || $approvedDepositTotal >= (float)$fish['unlock_balance'];
        $meetsReferrals = $fish['signup_bonus'] === true || $referralCount >= (int)$fish['referral_requirement'];
        $eligibleToUnlock = $meetsBalance && $meetsReferrals;
        $currentUnlockSource = (string)$status['unlock_source'];
        $isPermanentUnlock = in_array($currentUnlockSource, ['signup_bonus', 'manual_purchase'], true);

        if ($eligibleToUnlock && (int)$status['claimed_open_bonus'] === 0) {
            $grantStmt->execute([
                'unlock_source' => $fish['signup_bonus'] ? 'signup_bonus' : 'rule_unlock',
                'user_id' => $userId,
                'fish_key' => $fish['key'],
            ]);

            $status['is_unlocked'] = 1;
            $status['unlock_source'] = $fish['signup_bonus'] ? 'signup_bonus' : 'rule_unlock';
            $status['bonus_days'] = (int)$status['bonus_days'] + 5;
            $status['claimed_open_bonus'] = 1;
        } elseif ($eligibleToUnlock) {
            $status['is_unlocked'] = 1;
        } elseif (!$isPermanentUnlock && (int)$status['is_unlocked'] === 1) {
            $relockStmt->execute([
                'user_id' => $userId,
                'fish_key' => $fish['key'],
            ]);
            $status['is_unlocked'] = 0;
            $status['unlock_source'] = 'rule';
        }

        $isUnlocked = (int)$status['is_unlocked'] === 1;
        $bonusDays = (int)$status['bonus_days'];

        $cards[] = [
            'key' => $fish['key'],
            'name' => $fish['name'],
            'image' => $fish['image'],
            'image_alt' => $fish['image_alt'],
            'tags' => $fish['tags'],
            'level_key' => $fish['level_key'],
            'level_label' => $fish['level_label'],
            'reward_days' => (int)$fish['reward_days'],
            'unlock_balance' => number_format((float)$fish['unlock_balance'], 0),
            'purchase_price' => number_format((float)$fish['purchase_price'], 2, '.', ''),
            'purchase_price_value' => (float)$fish['purchase_price'],
            'referral_requirement' => (int)$fish['referral_requirement'],
            'is_unlocked' => $isUnlocked,
            'bonus_days' => $bonusDays,
            'meets_balance' => $meetsBalance,
            'meets_referrals' => $meetsReferrals,
            'can_buy_with_balance' => $balance >= (float)$fish['purchase_price'],
            'signup_bonus' => $fish['signup_bonus'],
            'unlock_message' => $fish['signup_bonus']
                ? 'Unlocked with signup bonus.'
                : '$' . number_format((float)$fish['unlock_balance'], 0) . '+ balance and ' . (int)$fish['referral_requirement'] . ' referrals.',
        ];
    }

    return [
        'balance' => $balance,
        'bonus_balance' => $bonusBalance,
        'trade_balance' => $tradeBalance,
        'approved_deposit_total' => $approvedDepositTotal,
        'invite_code' => $inviteCode,
        'referral_count' => $referralCount,
        'cards' => $cards,
    ];
}

function purchaseFishFoodForUser(PDO $pdo, int $userId, string $fishKey): array
{
    $catalogByKey = [];
    foreach (getFishFoodCatalog() as $fish) {
        $catalogByKey[(string)$fish['key']] = $fish;
    }

    if (!isset($catalogByKey[$fishKey])) {
        throw new InvalidArgumentException('Invalid fish card selected.');
    }

    ensureFishFoodSchema($pdo);
    seedUserFishFoodAccess($pdo, $userId);

    $fish = $catalogByKey[$fishKey];
    $purchasePrice = round((float)$fish['purchase_price'], 2);

    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare(
            'SELECT balance
             FROM users
             WHERE id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $userStmt->execute(['user_id' => $userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            $pdo->rollBack();
            throw new RuntimeException('User not found.');
        }

        $currentBalance = round((float)$user['balance'], 2);
        if ($currentBalance < $purchasePrice) {
            $pdo->rollBack();
            throw new RuntimeException('There is not enough main balance to buy fish food.');
        }

        $foodStmt = $pdo->prepare(
            'SELECT is_unlocked, unlock_source, bonus_days
             FROM user_fish_foods
             WHERE user_id = :user_id AND fish_key = :fish_key
             LIMIT 1
             FOR UPDATE'
        );
        $foodStmt->execute([
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);
        $foodRow = $foodStmt->fetch();

        if (!$foodRow) {
            $pdo->rollBack();
            throw new RuntimeException('Fish food card not found.');
        }

        $newBalance = round($currentBalance - $purchasePrice, 2);
        $newBonusDays = (int)$foodRow['bonus_days'] + 5;
        $wasUnlocked = (int)$foodRow['is_unlocked'] === 1;
        $unlockSource = $wasUnlocked ? (string)$foodRow['unlock_source'] : 'manual_purchase';

        $pdo->prepare(
            'UPDATE users
             SET balance = :balance
             WHERE id = :user_id'
        )->execute([
            'balance' => $newBalance,
            'user_id' => $userId,
        ]);

        $pdo->prepare(
            'UPDATE user_fish_foods
             SET is_unlocked = 1,
                 unlock_source = :unlock_source,
                 bonus_days = :bonus_days
             WHERE user_id = :user_id AND fish_key = :fish_key'
        )->execute([
            'unlock_source' => $unlockSource,
            'bonus_days' => $newBonusDays,
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'fish_key' => $fishKey,
            'fish_name' => $fish['name'],
            'amount' => $purchasePrice,
            'remaining_balance' => $newBalance,
            'bonus_days' => $newBonusDays,
            'was_unlocked' => $wasUnlocked,
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($throwable instanceof InvalidArgumentException || $throwable instanceof RuntimeException) {
            throw $throwable;
        }

        throw new RuntimeException('An error occurred while purchasing fish food.', 0, $throwable);
    }
}

function ensureFishCatchSchema(PDO $pdo): void
{
    if (!schemaTableExists($pdo, 'user_fish_catch_states')) {
        $pdo->exec(
            'CREATE TABLE user_fish_catch_states (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                fish_key VARCHAR(50) NOT NULL,
                last_catch_at DATETIME NULL,
                last_sold_at DATETIME NULL,
                total_catches INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_user_fish_catch_state (user_id, fish_key),
                CONSTRAINT fk_user_fish_catch_states_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_catch_states', 'last_catch_at')) {
        $pdo->exec(
            'ALTER TABLE user_fish_catch_states
             ADD COLUMN last_catch_at DATETIME NULL
             AFTER fish_key'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_catch_states', 'last_sold_at')) {
        $pdo->exec(
            'ALTER TABLE user_fish_catch_states
             ADD COLUMN last_sold_at DATETIME NULL
             AFTER last_catch_at'
        );
    }

    if (!schemaColumnExists($pdo, 'user_fish_catch_states', 'total_catches')) {
        $pdo->exec(
            'ALTER TABLE user_fish_catch_states
             ADD COLUMN total_catches INT UNSIGNED NOT NULL DEFAULT 0
             AFTER last_sold_at'
        );
    }
}

function seedUserFishCatchStates(PDO $pdo, int $userId): void
{
    ensureFishCatchSchema($pdo);

    $selectStmt = $pdo->prepare('SELECT fish_key FROM user_fish_catch_states WHERE user_id = :user_id');
    $selectStmt->execute(['user_id' => $userId]);
    $existingKeys = array_flip(array_map('strval', $selectStmt->fetchAll(PDO::FETCH_COLUMN)));

    $insertStmt = $pdo->prepare(
        'INSERT INTO user_fish_catch_states (user_id, fish_key)
         VALUES (:user_id, :fish_key)'
    );

    foreach (getFishFoodCatalog() as $fish) {
        if (isset($existingKeys[$fish['key']])) {
            continue;
        }

        $insertStmt->execute([
            'user_id' => $userId,
            'fish_key' => $fish['key'],
        ]);
    }
}

function getUserApprovedDepositTotal(PDO $pdo, int $userId): float
{
    if (!schemaTableExists($pdo, 'deposit_orders')) {
        return 0.0;
    }

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(amount), 0)
         FROM deposit_orders
         WHERE user_id = :user_id
           AND status = :status'
    );
    $stmt->execute([
        'user_id' => $userId,
        'status' => 'paid',
    ]);

    return round((float)$stmt->fetchColumn(), 2);
}

function getUserFishingCooldownStatus(PDO $pdo, int $userId): array
{
    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);

    $stmt = $pdo->prepare(
        'SELECT MAX(last_catch_at) AS last_catch_at
         FROM user_fish_catch_states
         WHERE user_id = :user_id'
    );
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();

    $lastCatchAt = $row && !empty($row['last_catch_at']) ? (string)$row['last_catch_at'] : null;
    $cooldownSeconds = 0;
    $nextAvailableAt = null;

    if ($lastCatchAt !== null) {
        $lastCatchTimestamp = strtotime($lastCatchAt);
        if ($lastCatchTimestamp !== false) {
            $nextTimestamp = $lastCatchTimestamp + 86400;
            $cooldownSeconds = max(0, $nextTimestamp - time());
            $nextAvailableAt = date(DATE_ATOM, $nextTimestamp);
        }
    }

    return [
        'last_catch_at' => $lastCatchAt,
        'cooldown_seconds' => $cooldownSeconds,
        'next_available_at' => $nextAvailableAt,
    ];
}

function getFishMapDashboardData(PDO $pdo, int $userId): array
{
    $dashboardData = getFishFoodDashboardData($pdo, $userId);

    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);
    $globalCatchStatus = getUserFishingCooldownStatus($pdo, $userId);

    $catchStmt = $pdo->prepare(
        'SELECT fish_key, last_catch_at, total_catches
         FROM user_fish_catch_states
         WHERE user_id = :user_id'
    );
    $catchStmt->execute(['user_id' => $userId]);

    $catchByKey = [];
    foreach ($catchStmt->fetchAll() as $row) {
        $catchByKey[(string)$row['fish_key']] = $row;
    }

    $now = time();

    foreach ($dashboardData['cards'] as &$card) {
        $catchRow = $catchByKey[$card['key']] ?? [
            'last_catch_at' => null,
            'total_catches' => 0,
        ];

        $lastCatchAt = $catchRow['last_catch_at'] !== null ? (string)$catchRow['last_catch_at'] : null;
        $cooldownSeconds = (int)$globalCatchStatus['cooldown_seconds'];
        $nextAvailableAt = $globalCatchStatus['next_available_at'];

        $foodCount = (int)$card['bonus_days'];
        $isUnlocked = (bool)$card['is_unlocked'];
        $canCatch = $isUnlocked && $foodCount > 0 && $cooldownSeconds === 0;

        if (!$isUnlocked) {
            $buttonLabel = 'Locked';
        } elseif ($foodCount <= 0) {
            $buttonLabel = 'No Food';
        } elseif ($cooldownSeconds > 0) {
            $buttonLabel = 'Wait 24 Hours';
        } else {
            $buttonLabel = 'Catch Fish';
        }

        $card['food_count'] = $foodCount;
        $card['last_catch_at'] = $lastCatchAt;
        $card['total_catches'] = (int)$catchRow['total_catches'];
        $card['cooldown_seconds'] = $cooldownSeconds;
        $card['next_available_at'] = $nextAvailableAt;
        $card['can_catch'] = $canCatch;
        $card['catch_button_label'] = $buttonLabel;
    }
    unset($card);

    return $dashboardData;
}

function catchFishForUser(PDO $pdo, int $userId, string $fishKey): array
{
    $catalogByKey = [];
    foreach (getFishFoodCatalog() as $fish) {
        $catalogByKey[(string)$fish['key']] = $fish;
    }

    if (!isset($catalogByKey[$fishKey])) {
        return [
            'success' => false,
            'message' => 'Invalid fish selection.',
        ];
    }

    getFishFoodDashboardData($pdo, $userId);
    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);

    try {
        $pdo->beginTransaction();

        $foodStmt = $pdo->prepare(
            'SELECT is_unlocked, bonus_days
             FROM user_fish_foods
             WHERE user_id = :user_id AND fish_key = :fish_key
             FOR UPDATE'
        );
        $foodStmt->execute([
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);
        $foodRow = $foodStmt->fetch();

        if (!$foodRow) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Fish information not found.',
            ];
        }

        if ((int)$foodRow['is_unlocked'] !== 1) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'This fish is not unlocked yet.',
            ];
        }

        $remainingFood = (int)$foodRow['bonus_days'];
        if ($remainingFood <= 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'You have no food attempts left.',
                'remaining_food' => 0,
                'cooldown_seconds' => 0,
            ];
        }

        $pdo->prepare(
            'INSERT IGNORE INTO user_fish_catch_states (user_id, fish_key)
             VALUES (:user_id, :fish_key)'
        )->execute([
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);

        $catchStmt = $pdo->prepare(
            'SELECT last_catch_at, total_catches
             FROM user_fish_catch_states
             WHERE user_id = :user_id AND fish_key = :fish_key
             FOR UPDATE'
        );
        $catchStmt->execute([
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);
        $catchRow = $catchStmt->fetch();

        $now = time();
        $globalCatchRowsStmt = $pdo->prepare(
            'SELECT last_catch_at
             FROM user_fish_catch_states
             WHERE user_id = :user_id
             FOR UPDATE'
        );
        $globalCatchRowsStmt->execute(['user_id' => $userId]);

        $cooldownSeconds = 0;
        foreach ($globalCatchRowsStmt->fetchAll() as $globalCatchRow) {
            if (empty($globalCatchRow['last_catch_at'])) {
                continue;
            }

            $lastCatchTimestamp = strtotime((string)$globalCatchRow['last_catch_at']);
            if ($lastCatchTimestamp === false) {
                continue;
            }

            $cooldownSeconds = max($cooldownSeconds, max(0, ($lastCatchTimestamp + 86400) - $now));
        }

        if ($cooldownSeconds > 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'You must wait before fishing for this fish again.',
                'remaining_food' => $remainingFood,
                'cooldown_seconds' => $cooldownSeconds,
                'next_available_at' => date(DATE_ATOM, $now + $cooldownSeconds),
            ];
        }

        $updatedFood = $remainingFood - 1;
        $nowSql = date('Y-m-d H:i:s', $now);
        $nextAvailableAt = date(DATE_ATOM, $now + 86400);
        $marketAvailableAt = date(DATE_ATOM, $now + 3600);

        $pdo->prepare(
            'UPDATE user_fish_foods
             SET bonus_days = :bonus_days
             WHERE user_id = :user_id AND fish_key = :fish_key'
        )->execute([
            'bonus_days' => $updatedFood,
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);

        $pdo->prepare(
            'UPDATE user_fish_catch_states
             SET last_catch_at = :last_catch_at,
                 last_sold_at = NULL,
                 total_catches = total_catches + 1
             WHERE user_id = :user_id AND fish_key = :fish_key'
        )->execute([
            'last_catch_at' => $nowSql,
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => $catalogByKey[$fishKey]['name'] . ' caught.',
            'fish' => [
                'key' => $fishKey,
                'name' => $catalogByKey[$fishKey]['name'],
                'image' => $catalogByKey[$fishKey]['image'],
                'image_alt' => $catalogByKey[$fishKey]['image_alt'],
            ],
            'remaining_food' => $updatedFood,
            'cooldown_seconds' => 86400,
            'next_available_at' => $nextAvailableAt,
            'market_available_at' => $marketAvailableAt,
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'An error occurred while catching the fish.',
        ];
    }
}

function getAquariumCollection(PDO $pdo, int $userId): array
{
    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);
    $marketAvailability = getFishMarketAvailability($pdo, $userId);
    $marketByKey = [];
    foreach ($marketAvailability['items'] as $marketItem) {
        $marketByKey[(string)$marketItem['key']] = $marketItem;
    }

    $catalogByKey = [];
    foreach (getFishFoodCatalog() as $fish) {
        $catalogByKey[(string)$fish['key']] = $fish;
    }

    $stmt = $pdo->prepare(
        'SELECT fish_key, last_catch_at, last_sold_at, total_catches
         FROM user_fish_catch_states
         WHERE user_id = :user_id AND total_catches > 0
         ORDER BY updated_at DESC'
    );
    $stmt->execute(['user_id' => $userId]);

    $items = [];
    $totalCatches = 0;

    foreach ($stmt->fetchAll() as $row) {
        $fishKey = (string)$row['fish_key'];
        if (!isset($catalogByKey[$fishKey])) {
            continue;
        }

        $fish = $catalogByKey[$fishKey];
        $catchCount = (int)$row['total_catches'];
        $totalCatches += $catchCount;

        $items[] = [
            'key' => $fishKey,
            'name' => $fish['name'],
            'image' => $fish['image'],
            'image_alt' => $fish['image_alt'],
            'tags' => $fish['tags'],
            'level_label' => $fish['level_label'],
            'total_catches' => $catchCount,
            'last_catch_at' => $row['last_catch_at'] ? date('d.m.Y H:i', strtotime((string)$row['last_catch_at'])) : null,
            'last_sold_at' => $row['last_sold_at'] ? date('d.m.Y H:i', strtotime((string)$row['last_sold_at'])) : null,
            'market_available_at' => $marketByKey[$fishKey]['market_available_at'] ?? null,
            'market_countdown_seconds' => $marketByKey[$fishKey]['market_countdown_seconds'] ?? 0,
            'is_market_ready' => $marketByKey[$fishKey]['is_market_ready'] ?? false,
            'is_market_sold' => $marketByKey[$fishKey]['is_market_sold'] ?? false,
            'market_url' => $marketByKey[$fishKey]['market_url'] ?? ('fish-market.php?fish=' . rawurlencode($fishKey)),
        ];
    }

    return [
        'items' => $items,
        'fish_count' => count($items),
        'total_catches' => $totalCatches,
    ];
}

function getFishMarketAvailability(PDO $pdo, int $userId): array
{
    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);

    $catalogByKey = [];
    foreach (getFishFoodCatalog() as $fish) {
        $catalogByKey[(string)$fish['key']] = $fish;
    }

    $stmt = $pdo->prepare(
        'SELECT fish_key, last_catch_at, last_sold_at, total_catches
         FROM user_fish_catch_states
         WHERE user_id = :user_id AND total_catches > 0
         ORDER BY updated_at DESC'
    );
    $stmt->execute(['user_id' => $userId]);

    $items = [];
    $now = time();

    foreach ($stmt->fetchAll() as $row) {
        $fishKey = (string)$row['fish_key'];
        if (!isset($catalogByKey[$fishKey])) {
            continue;
        }

        $lastCatchAt = $row['last_catch_at'] ? strtotime((string)$row['last_catch_at']) : false;
        if ($lastCatchAt === false) {
            continue;
        }

        $lastSoldAt = $row['last_sold_at'] ? strtotime((string)$row['last_sold_at']) : false;
        $isSoldForCurrentCatch = $lastSoldAt !== false && $lastSoldAt >= $lastCatchAt;
        $marketAvailableAt = $lastCatchAt + 3600;
        $countdownSeconds = $isSoldForCurrentCatch ? 0 : max(0, $marketAvailableAt - $now);
        $isMarketReady = !$isSoldForCurrentCatch && $countdownSeconds === 0;

        $items[] = [
            'key' => $fishKey,
            'name' => $catalogByKey[$fishKey]['name'],
            'market_available_at' => date(DATE_ATOM, $marketAvailableAt),
            'market_countdown_seconds' => $countdownSeconds,
            'is_market_ready' => $isMarketReady,
            'is_market_sold' => $isSoldForCurrentCatch,
            'market_url' => 'fish-market.php?fish=' . rawurlencode($fishKey),
        ];
    }

    $readyItems = array_values(array_filter($items, static function (array $item): bool {
        return $item['is_market_ready'] === true;
    }));
    $upcomingItems = array_values(array_filter($items, static function (array $item): bool {
        return $item['is_market_ready'] === false && ($item['is_market_sold'] ?? false) === false;
    }));

    usort($upcomingItems, static function (array $left, array $right): int {
        return $left['market_countdown_seconds'] <=> $right['market_countdown_seconds'];
    });

    return [
        'items' => $items,
        'ready_items' => $readyItems,
        'upcoming_items' => $upcomingItems,
        'has_ready_items' => !empty($readyItems),
        'first_ready_item' => $readyItems[0] ?? null,
        'next_upcoming_item' => $upcomingItems[0] ?? null,
    ];
}

function ensureMarketFeedSchema(PDO $pdo): void
{
    if (!schemaTableExists($pdo, 'market_feed_logs')) {
        $pdo->exec(
            'CREATE TABLE market_feed_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                seller_label VARCHAR(80) NOT NULL,
                fish_key VARCHAR(50) NOT NULL,
                fish_name VARCHAR(100) NOT NULL,
                status_label VARCHAR(40) NOT NULL,
                note VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_market_feed_created (created_at),
                KEY idx_market_feed_user (user_id),
                CONSTRAINT fk_market_feed_logs_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}

function addMarketFeedLog(
    PDO $pdo,
    ?int $userId,
    string $sellerLabel,
    string $fishKey,
    string $fishName,
    string $statusLabel,
    string $note
): void {
    ensureMarketFeedSchema($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO market_feed_logs (
            user_id, seller_label, fish_key, fish_name, status_label, note
        ) VALUES (
            :user_id, :seller_label, :fish_key, :fish_name, :status_label, :note
        )'
    );

    $stmt->execute([
        'user_id' => $userId,
        'seller_label' => $sellerLabel,
        'fish_key' => $fishKey,
        'fish_name' => $fishName,
        'status_label' => $statusLabel,
        'note' => $note,
    ]);
}

function getSharedMarketFeed(PDO $pdo, int $viewerUserId, int $limit = 5): array
{
    ensureMarketFeedSchema($pdo);

    $stmt = $pdo->prepare(
        'SELECT user_id, seller_label, fish_key, fish_name, status_label, note, created_at
         FROM market_feed_logs
         ORDER BY id DESC
         LIMIT :limit_count'
    );
    $stmt->bindValue(':limit_count', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $sourceUserId = isset($row['user_id']) ? (int)$row['user_id'] : null;
        $sellerLabel = $sourceUserId === $viewerUserId ? 'You' : (string)$row['seller_label'];

        $items[] = [
            'userId' => $sourceUserId,
            'seller' => $sellerLabel,
            'fish' => (string)$row['fish_name'],
            'fishKey' => (string)$row['fish_key'],
            'status' => (string)$row['status_label'],
            'note' => (string)$row['note'],
            'createdAt' => strtotime((string)$row['created_at']) * 1000,
            'time' => 'Just now',
        ];
    }

    return $items;
}

function sellFishForUser(PDO $pdo, int $userId, string $fishKey): array
{
    $marketRates = getFishMarketRates();
    $catalogByKey = [];
    foreach (getFishFoodCatalog() as $fish) {
        $catalogByKey[(string)$fish['key']] = $fish;
    }

    if (!isset($marketRates[$fishKey])) {
        return [
            'success' => false,
            'message' => 'Invalid fish selection.',
        ];
    }

    ensureFishCatchSchema($pdo);
    seedUserFishCatchStates($pdo, $userId);

    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare(
            'SELECT balance, bonus_balance
             FROM users
             WHERE id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $userStmt->execute(['user_id' => $userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $balance = (float)$user['balance'];
        $bonusBalance = isset($user['bonus_balance']) ? (float)$user['bonus_balance'] : 0.0;
        $tradeBalance = $balance + $bonusBalance;

        if ($tradeBalance <= 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Market balance required.',
            ];
        }

        $catchStmt = $pdo->prepare(
            'SELECT last_catch_at, last_sold_at, total_catches
             FROM user_fish_catch_states
             WHERE user_id = :user_id AND fish_key = :fish_key
             LIMIT 1
             FOR UPDATE'
        );
        $catchStmt->execute([
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);
        $catchRow = $catchStmt->fetch();

        if (!$catchRow || empty($catchRow['last_catch_at']) || (int)$catchRow['total_catches'] <= 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'No active fish found for sale.',
            ];
        }

        $lastCatchAt = strtotime((string)$catchRow['last_catch_at']);
        $lastSoldAt = !empty($catchRow['last_sold_at']) ? strtotime((string)$catchRow['last_sold_at']) : false;
        $now = time();

        if ($lastCatchAt === false) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Fish sale information could not be read.',
            ];
        }

        if ($lastSoldAt !== false && $lastSoldAt >= $lastCatchAt) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'This catch has already been sold.',
            ];
        }

        $marketAvailableAt = $lastCatchAt + 3600;
        if ($now < $marketAvailableAt) {
            $remaining = $marketAvailableAt - $now;
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'The market is not active yet for this fish.',
                'market_countdown_seconds' => $remaining,
                'market_available_at' => date(DATE_ATOM, $marketAvailableAt),
            ];
        }

        $rate = (float)$marketRates[$fishKey];
        $profit = round($tradeBalance * ($rate / 100), 2);
        $newBalance = round($balance + $profit, 2);
        $newTradeBalance = round($newBalance + $bonusBalance, 2);
        $nowSql = date('Y-m-d H:i:s', $now);
        $saleSourceRef = $fishKey . ':' . $userId . ':' . $nowSql;
        $fishName = $catalogByKey[$fishKey]['name'] ?? $fishKey;

        $pdo->prepare(
            'UPDATE users
             SET balance = :balance
             WHERE id = :user_id'
        )->execute([
            'balance' => $newBalance,
            'user_id' => $userId,
        ]);

        $pdo->prepare(
            'UPDATE user_fish_catch_states
             SET last_sold_at = :last_sold_at
             WHERE user_id = :user_id AND fish_key = :fish_key'
        )->execute([
            'last_sold_at' => $nowSql,
            'user_id' => $userId,
            'fish_key' => $fishKey,
        ]);

        $referralReward = applyReferralReward(
            $pdo,
            $userId,
            $profit,
            'fish_sale',
            $saleSourceRef,
            '6% commission from the referred user fish sale profit.'
        );

        addMarketFeedLog(
            $pdo,
            $userId,
            'Aqua #' . $userId,
            $fishKey,
            $fishName,
            'Sold',
            '%' . number_format($rate, 2, '.', '') . ' return completed the sale.'
        );

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Sale completed.',
            'fish_name' => $fishName,
            'profit' => $profit,
            'new_balance' => $newBalance,
            'bonus_balance' => $bonusBalance,
            'trade_balance' => $newTradeBalance,
            'rate' => $rate,
            'sold_at' => date(DATE_ATOM, $now),
            'referral_reward' => $referralReward['reward_amount'],
            'feed_item' => [
                'userId' => $userId,
                'seller' => 'You',
                'fish' => $fishName,
                'fishKey' => $fishKey,
                'status' => 'Sold',
                'note' => '%' . number_format($rate, 2, '.', '') . ' return completed the sale.',
                'createdAt' => $now * 1000,
                'time' => 'Just now',
            ],
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'An error occurred during the sale.',
        ];
    }
}

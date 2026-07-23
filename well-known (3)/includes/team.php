<?php
declare(strict_types=1);

require_once __DIR__ . '/fish_food.php';

function maskTeamEmail(string $email): string
{
    $parts = explode('@', $email, 2);
    $name = $parts[0] ?? '';
    $domain = $parts[1] ?? '';

    if ($name === '' || $domain === '') {
        return 'Kullanici';
    }

    $visible = strlen($name) <= 2 ? substr($name, 0, 1) : substr($name, 0, 2);
    return $visible . str_repeat('*', max(2, strlen($name) - strlen($visible))) . '@' . $domain;
}

function getTeamDashboardData(PDO $pdo, int $userId): array
{
    ensureReferralRewardSchema($pdo);
    ensureFishCatchSchema($pdo);

    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.email,
            u.invite_code,
            u.balance,
            u.bonus_balance,
            u.referral_balance,
            u.created_at,
            COALESCE(c.total_catches, 0) AS total_catches,
            c.last_catch_at,
            c.last_sold_at,
            COALESCE(r.total_reward, 0) AS reward_to_you
         FROM users u
         LEFT JOIN (
            SELECT
                user_id,
                SUM(total_catches) AS total_catches,
                MAX(last_catch_at) AS last_catch_at,
                MAX(last_sold_at) AS last_sold_at
            FROM user_fish_catch_states
            GROUP BY user_id
         ) c ON c.user_id = u.id
         LEFT JOIN (
            SELECT
                source_user_id,
                SUM(reward_amount) AS total_reward
            FROM referral_reward_logs
            WHERE beneficiary_user_id = :beneficiary_user_id
            GROUP BY source_user_id
         ) r ON r.source_user_id = u.id
         WHERE u.invited_by_user_id = :owner_user_id
         ORDER BY u.created_at DESC'
    );
    $stmt->execute([
        'beneficiary_user_id' => $userId,
        'owner_user_id' => $userId,
    ]);

    $members = [];
    $soldCount = 0;
    $totalTeamEarnings = 0.0;
    $totalRewardToYou = 0.0;

    foreach ($stmt->fetchAll() as $row) {
        $earnedBalance = round(((float)$row['balance']) + ((float)$row['referral_balance']), 2);
        $rewardToYou = round((float)$row['reward_to_you'], 2);
        $hasSoldFish = !empty($row['last_sold_at']);

        if ($hasSoldFish) {
            $soldCount++;
        }

        $totalTeamEarnings += $earnedBalance;
        $totalRewardToYou += $rewardToYou;

        $members[] = [
            'id' => (int)$row['id'],
            'email' => (string)$row['email'],
            'masked_email' => maskTeamEmail((string)$row['email']),
            'invite_code' => (string)$row['invite_code'],
            'earned_balance' => number_format($earnedBalance, 2, '.', ''),
            'reward_to_you' => number_format($rewardToYou, 2, '.', ''),
            'total_catches' => (int)$row['total_catches'],
            'has_sold_fish' => $hasSoldFish,
            'sale_status_label' => $hasSoldFish ? 'Fish Satti' : 'No Sales',
            'last_sale_at' => !empty($row['last_sold_at']) ? date('d.m.Y H:i', strtotime((string)$row['last_sold_at'])) : null,
            'joined_at' => date('d.m.Y', strtotime((string)$row['created_at'])),
        ];
    }

    return [
        'members' => $members,
        'member_count' => count($members),
        'sold_count' => $soldCount,
        'total_team_earnings' => number_format($totalTeamEarnings, 2, '.', ''),
        'total_reward_to_you' => number_format($totalRewardToYou, 2, '.', ''),
    ];
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/team.php';

function buildInviteBaseUrl(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = trim(str_replace('\\', '/', dirname($scriptName)), '/');

    $baseUrl = $scheme . '://' . $host;
    if ($basePath !== '' && $basePath !== '.') {
        $baseUrl .= '/' . $basePath;
    }

    return rtrim($baseUrl, '/');
}

function getInviteDashboardData(PDO $pdo, int $userId): array
{
    ensureReferralRewardSchema($pdo);

    $userStmt = $pdo->prepare(
        'SELECT id, email, invite_code, referral_balance
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $teamData = getTeamDashboardData($pdo, $userId);
    $baseUrl = buildInviteBaseUrl();
    $inviteCode = (string)$user['invite_code'];
    $inviteUrl = $baseUrl . '/register.php?invite=' . rawurlencode($inviteCode);

    return [
        'user_id' => (int)$user['id'],
        'email' => (string)$user['email'],
        'invite_code' => $inviteCode,
        'invite_url' => $inviteUrl,
        'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($inviteUrl),
        'referral_balance' => number_format((float)$user['referral_balance'], 2, '.', ''),
        'member_count' => (int)$teamData['member_count'],
        'sold_count' => (int)$teamData['sold_count'],
        'total_reward_to_you' => $teamData['total_reward_to_you'],
        'total_team_earnings' => $teamData['total_team_earnings'],
    ];
}

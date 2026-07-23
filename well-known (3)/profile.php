<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';
require __DIR__ . '/includes/withdrawal.php';
require __DIR__ . '/includes/team.php';
require __DIR__ . '/includes/app_settings.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$marketNotificationData = getFishMarketAvailability($pdo, $userId);

$userStmt = $pdo->prepare(
    'SELECT email, invite_code, vip_level, balance, bonus_balance, referral_balance, created_at
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$aquariumData = getAquariumCollection($pdo, $userId);
$withdrawalData = getWithdrawalDashboardData($pdo, $userId);
$teamData = getTeamDashboardData($pdo, $userId);
$helpTelegramUrl = getAppSetting($pdo, 'help_telegram_url', 'https://t.me/aquavestsupport') ?? 'https://t.me/aquavestsupport';

$email = (string)$user['email'];
$inviteCode = (string)$user['invite_code'];
$vipLevel = (int)$user['vip_level'];
$balance = (float)$user['balance'];
$bonusBalance = (float)($user['bonus_balance'] ?? 0);
$referralBalance = (float)($user['referral_balance'] ?? 0);
$totalBalance = $balance + $bonusBalance + $referralBalance;
$joinedAt = date('d.m.Y', strtotime((string)$user['created_at']));
$displayName = strstr($email, '@', true) ?: $email;
$avatarLetter = strtoupper(substr($displayName, 0, 1));
$memberLabel = $vipLevel > 0 ? 'VIP ' . $vipLevel : 'Standard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body profile-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-profile" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-profile" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-profile" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-profile)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-profile)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-profile)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-profile)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-profile)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-profile)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-profile)" stroke-width="4.8" stroke-linecap="round"></path>
            <path d="M22 39C25 36 27 33.5 27.8 30" fill="none" stroke="#78c995" stroke-width="2.3" stroke-linecap="round"></path>
            <path d="M40.5 35.5C37 33.2 35.2 30.8 34.6 28" fill="none" stroke="#69bb89" stroke-width="2.1" stroke-linecap="round"></path>
            <circle cx="10.8" cy="21.2" r="1.9" fill="#9fe5d7" opacity="0.72"></circle>
            <circle cx="52.5" cy="15.8" r="1.7" fill="#b2eee3" opacity="0.62"></circle>
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
        <a href="logout.php" class="profile-logout-btn" aria-label="Log Out">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14 7V5.5A1.5 1.5 0 0 0 12.5 4h-5A1.5 1.5 0 0 0 6 5.5v13A1.5 1.5 0 0 0 7.5 20h5A1.5 1.5 0 0 0 14 18.5V17"></path>
                <path d="M10 12h10"></path>
                <path d="M17 8.5l3.5 3.5-3.5 3.5"></path>
            </svg>
            <span>Log Out</span>
        </a>
    </div>
</header>

<?php require __DIR__ . '/includes/market_notification_partial.php'; ?>

<main class="dashboard-main profile-main">
    <section class="profile-shell">
        <div class="profile-top-card">
            <div class="profile-top-head">
                <div class="profile-avatar"><?= e($avatarLetter) ?></div>

                <div class="profile-identity">
                    <div class="profile-badge-row">
                        <span class="profile-member-badge"><?= e($memberLabel) ?></span>
                        <span class="profile-id-badge">#<?= e((string)$userId) ?></span>
                    </div>
                    <h1><?= e($displayName) ?></h1>
                    <p><?= e($email) ?></p>
                </div>
            </div>

            <div class="profile-stats-row">
                <div class="profile-stat-card">
                    <strong>$<?= e(number_format($balance, 2, '.', '')) ?></strong>
                    <span>Fish Earnings</span>
                </div>
                <div class="profile-stat-card">
                    <strong>$<?= e(number_format($bonusBalance, 2, '.', '')) ?></strong>
                    <span>Bonus Balance</span>
                </div>
                <div class="profile-stat-card">
                    <strong>$<?= e(number_format($referralBalance, 2, '.', '')) ?></strong>
                    <span>Referral Earnings</span>
                </div>
            </div>

            <div class="profile-balance-bar">
                <div class="profile-balance-copy">
                    <span>Total Balance</span>
                    <strong>$<?= e(number_format($totalBalance, 2, '.', '')) ?></strong>
                </div>
                <div class="profile-balance-actions">
                    <a href="deposit.php" class="profile-balance-btn is-primary">Deposit</a>
                    <a href="withdrawal.php" class="profile-balance-btn is-secondary">Withdraw</a>
                </div>
            </div>
        </div>

        <section class="profile-module">
            <div class="profile-module-title">
                <span>Fund Management</span>
            </div>
            <div class="profile-module-grid is-five">
                <a href="deposit.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect><path d="M12 4v10"></path><path d="M8.5 10.5L12 14l3.5-3.5"></path></svg>
                    </span>
                    <span>Deposit</span>
                </a>
                <a href="withdrawal.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect><path d="M12 19V9"></path><path d="M8.5 12.5L12 9l3.5 3.5"></path></svg>
                    </span>
                    <span>Withdraw</span>
                </a>
                <a href="fish-market.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><path d="M4 8h16l-1.5 3H5.5z"></path><path d="M6 11v6h12v-6"></path><path d="M9 14c1.1-1 2.1-1 3.2 0 1.1 1 2.1 1 3.1 0"></path></svg>
                    </span>
                    <span>Fish Market</span>
                </a>
                <a href="aquarium.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><path d="M4.5 8.5h15l-1.5 10h-12z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path><path d="M9.5 12.5h5"></path></svg>
                    </span>
                    <span>Aquarium</span>
                </a>
                <a href="fish-food.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><path d="M5 14h14l-1.4 4H6.4z"></path><path d="M7.5 11.5c1.2-1 2.4-1 3.6 0 1.1 1 2.2 1 3.4 0"></path><circle cx="9" cy="8.1" r="1"></circle><circle cx="12.5" cy="6.8" r="1"></circle><circle cx="16" cy="8.2" r="1"></circle></svg>
                    </span>
                    <span>Fish Food</span>
                </a>
            </div>
        </section>

        <section class="profile-module">
            <div class="profile-module-title">
                <span>Account Status</span>
            </div>
            <div class="profile-summary-grid">
                <div class="profile-summary-card">
                    <strong><?= e($inviteCode) ?></strong>
                    <span>Your Invite Code</span>
                </div>
                <div class="profile-summary-card">
                    <strong><?= e((string)$teamData['member_count']) ?></strong>
                    <span>Team Members</span>
                </div>
                <div class="profile-summary-card">
                    <strong><?= e((string)$aquariumData['total_catches']) ?></strong>
                    <span>Total Catches</span>
                </div>
                <div class="profile-summary-card">
                    <strong>$<?= e(number_format((float)$withdrawalData['withdrawable_balance'], 2, '.', '')) ?></strong>
                    <span>Withdrawable</span>
                </div>
            </div>
        </section>

        <section class="profile-module">
            <div class="profile-module-title">
                <span>Security Service</span>
            </div>
            <div class="profile-module-grid is-four">
                <a href="security.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 3.5l7 3v5.2c0 4.1-2.8 7.8-7 8.8-4.2-1-7-4.7-7-8.8V6.5z"></path><path d="M9.5 12l1.7 1.7 3.3-3.7"></path></svg>
                    </span>
                    <span>Security</span>
                </a>
                <a href="company.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><path d="M5 19h14"></path><path d="M7.5 19V8.5"></path><path d="M12 19V5"></path><path d="M16.5 19v-7"></path><path d="M5 8.5h5"></path><path d="M12 5h4.5"></path></svg>
                    </span>
                    <span>Company</span>
                </a>
                <a href="team.php" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"></circle><circle cx="6.8" cy="10.2" r="2.1"></circle><circle cx="17.2" cy="10.2" r="2.1"></circle><path d="M8 18.5c0-2.1 1.8-3.9 4-3.9s4 1.8 4 3.9"></path><path d="M3.8 18.5c0-1.6 1.4-2.9 3-2.9"></path><path d="M20.2 18.5c0-1.6-1.4-2.9-3-2.9"></path></svg>
                    </span>
                    <span>Team</span>
                </a>
                <a href="<?= e($helpTelegramUrl) ?>" target="_blank" rel="noopener noreferrer" class="profile-module-item">
                    <span class="profile-module-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 5L3.8 11.6l5.2 2.1L18.2 7 11 14.6V19l3.2-3.1 4.4 2.9L21 5z"></path></svg>
                    </span>
                    <span>Support</span>
                </a>
            </div>
            <a href="logout.php" class="profile-safe-logout">Secure Log Out</a>
        </section>
    </section>
</main>

<footer class="dashboard-footer profile-footer" aria-label="Primary footer navigation">
    <div class="profile-footer-meta">
        <span>User ID</span>
        <strong>#<?= e((string)$userId) ?></strong>
    </div>

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

        <a class="dashboard-footer-item is-active" href="profile.php" aria-current="page">
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
</body>
</html>



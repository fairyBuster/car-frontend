<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';
require __DIR__ . '/includes/invite.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$marketNotificationData = getFishMarketAvailability($pdo, $userId);
$inviteData = getInviteDashboardData($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body invite-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-invite" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-invite" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-invite" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-invite)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-invite)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-invite)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-invite)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-invite)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-invite)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-invite)" stroke-width="4.8" stroke-linecap="round"></path>
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

<main class="dashboard-main invite-main">
    <section class="invite-hero">
        <div class="invite-copy">
            <span class="invite-pill">Invite Friends</span>
            <h1>Share Your Aqua Link</h1>
            <p>Friends can scan the QR code or use your invite code to register directly.</p>
        </div>

        <div class="invite-qr-card">
            <img src="<?= e($inviteData['qr_url']) ?>" alt="Invite QR code" class="invite-qr-image">
            <p class="invite-qr-note">Friends can scan this QR code and go straight to the sign-up page.</p>
        </div>
    </section>

    <section class="invite-panel">
        <div class="invite-code-card">
            <span>Your Invite Code</span>
            <strong><?= e($inviteData['invite_code']) ?></strong>
            <button type="button" class="invite-copy-btn" data-copy-value="<?= e($inviteData['invite_code']) ?>">Copy Code</button>
        </div>

        <div class="invite-link-card">
            <span>Your Invite Link</span>
            <input type="text" value="<?= e($inviteData['invite_url']) ?>" readonly>
            <button type="button" class="invite-copy-btn" data-copy-value="<?= e($inviteData['invite_url']) ?>">Copy Link</button>
        </div>
    </section>

    <section class="invite-summary">
        <article class="invite-summary-card">
            <span>Referral Balance</span>
            <strong>$<?= e($inviteData['referral_balance']) ?></strong>
        </article>
        <article class="invite-summary-card">
            <span>Total Invites</span>
            <strong><?= e((string)$inviteData['member_count']) ?></strong>
        </article>
        <article class="invite-summary-card">
            <span>Sold Fish</span>
            <strong><?= e((string)$inviteData['sold_count']) ?></strong>
        </article>
    </section>

    <section class="invite-award-card">
        <div class="invite-award-head">
            <div>
                <h2>My Invite Rewards</h2>
                <p>Track how much your invited users have earned and how much they earned for you.</p>
            </div>
            <a href="team.php" class="invite-secondary-link">View My Invites</a>
        </div>

        <div class="invite-award-grid">
            <article class="invite-award-box">
                <span>Earned for You</span>
                <strong>$<?= e($inviteData['total_reward_to_you']) ?></strong>
            </article>
            <article class="invite-award-box">
                <span>Team Earnings</span>
                <strong>$<?= e($inviteData['total_team_earnings']) ?></strong>
            </article>
        </div>
    </section>

    <section class="invite-rules-card">
        <h2>Invite Rules</h2>
        <div class="invite-rules-list">
            <p>1. Your invite QR and invite code stay valid and can be shared anytime.</p>
            <p>2. When your referred users deposit, you earn 6% referral commission.</p>
            <p>3. When your referred users sell fish, you also earn 6% referral commission.</p>
        </div>
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

        <a class="dashboard-footer-item is-active" href="invite.php" aria-current="page">
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
    const copyButtons = document.querySelectorAll('[data-copy-value]');
    if (!copyButtons.length) {
        return;
    }

    copyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy-value') || '';
            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                const previous = button.textContent;
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = previous;
                }, 1400);
            } catch (error) {
                window.prompt('Copy this value:', value);
            }
        });
    });
})();
</script>
</body>
</html>

<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$aquariumData = getAquariumCollection($pdo, (int)$_SESSION['user_id']);
$visibleAquariumItems = array_values(array_filter($aquariumData['items'], static function (array $item): bool {
    return empty($item['is_market_sold']);
}));
$marketNotificationData = getFishMarketAvailability($pdo, (int)$_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aquarium</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body aquarium-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-aquarium" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-aquarium" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-aquarium" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-aquarium)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-aquarium)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-aquarium)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-aquarium)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-aquarium)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-aquarium)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-aquarium)" stroke-width="4.8" stroke-linecap="round"></path>
            <path d="M22 39C25 36 27 33.5 27.8 30" fill="none" stroke="#78c995" stroke-width="2.3" stroke-linecap="round"></path>
            <path d="M40.5 35.5C37 33.2 35.2 30.8 34.6 28" fill="none" stroke="#69bb89" stroke-width="2.1" stroke-linecap="round"></path>
            <circle cx="10.8" cy="21.2" r="1.9" fill="#9fe5d7" opacity="0.72"></circle>
            <circle cx="52.5" cy="15.8" r="1.7" fill="#b2eee3" opacity="0.62"></circle>
        </svg>
        <span class="dashboard-brand-name">Aqua</span>
    </div>

    <div class="dashboard-actions">
        <button type="button" class="dashboard-icon-btn" aria-label="Support center">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 13v-2a8 8 0 1 1 16 0v2"></path>
                <rect x="2.5" y="12" width="4" height="7" rx="1.5"></rect>
                <rect x="17.5" y="12" width="4" height="7" rx="1.5"></rect>
                <path d="M18 19a3 3 0 0 1-3 3h-3"></path>
                <circle cx="11.8" cy="22" r="1"></circle>
            </svg>
        </button>
        <button type="button" class="dashboard-icon-btn" aria-label="Language">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M3 12h18"></path>
                <path d="M12 3a14.5 14.5 0 0 1 0 18"></path>
                <path d="M12 3a14.5 14.5 0 0 0 0 18"></path>
            </svg>
        </button>
    </div>
</header>

<?php require __DIR__ . '/includes/market_notification_partial.php'; ?>

<main class="dashboard-main aquarium-main">
    <?php if (!empty($visibleAquariumItems)): ?>
        <section class="aquarium-grid" aria-label="Fish in the aquarium">
            <?php foreach ($visibleAquariumItems as $item): ?>
                <article class="aquarium-card">
                    <img src="<?= e($item['image']) ?>" alt="<?= e($item['image_alt']) ?>" class="aquarium-card-image">

                    <div class="aquarium-card-body">
                        <div class="aquarium-card-tags" aria-hidden="true">
                            <?php foreach ($item['tags'] as $tag): ?>
                                <span class="aquarium-card-tag"><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <h2><?= e($item['name']) ?></h2>
                        <span class="aquarium-card-level"><?= e($item['level_label']) ?></span>

                        <div class="aquarium-card-meta">
                            <div class="aquarium-card-stat">
                                <span>Catches</span>
                                <strong><?= e((string)$item['total_catches']) ?> times</strong>
                            </div>
                            <div class="aquarium-card-stat">
                                <span>Last Catch</span>
                                <strong><?= e($item['last_catch_at'] ?? '-') ?></strong>
                            </div>
                        </div>

                        <div class="aquarium-market-action">
                            <p
                                class="aquarium-market-timer <?= !$item['is_market_ready'] ? 'is-active' : '' ?>"
                                data-market-countdown
                                data-market-available="<?= e((string)($item['market_available_at'] ?? '')) ?>"
                            >
                                <?= $item['is_market_ready']
                                    ? 'This fish is ready for market sale.'
                                    : 'Waiting 1 hour before market sale.' ?>
                            </p>

                            <a
                                href="<?= $item['is_market_ready'] ? e($item['market_url']) : '#' ?>"
                                class="aquarium-market-button <?= $item['is_market_ready'] ? '' : 'is-locked' ?>"
                                <?= $item['is_market_ready'] ? '' : 'aria-disabled="true"' ?>
                                data-market-button
                                data-market-url="<?= e($item['market_url']) ?>"
                                data-market-available="<?= e((string)($item['market_available_at'] ?? '')) ?>"
                            >
                                Sell in Market
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="aquarium-empty">
            <h2>Your Aquarium Is Empty for Now</h2>
            <p>You can start building your first collection by catching fish on the map page.</p>
            <a href="map.php" class="aquarium-empty-button">Go to Map</a>
        </section>
    <?php endif; ?>
</main>

<footer class="dashboard-footer" aria-label="Birincil alt gezinme">
    <nav class="dashboard-footer-bar" aria-label="Birincil">
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

        <a class="dashboard-footer-item is-active" href="aquarium.php" aria-current="page">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4 15.5c0-3.9 3.6-7 8-7s8 3.1 8 7"></path>
                    <path d="M4 15.5v1.5A2 2 0 0 0 6 19h12a2 2 0 0 0 2-2v-1.5"></path>
                    <path d="M8 12.5h8"></path>
                    <circle cx="9" cy="10" r="1"></circle>
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
        const countdownBlocks = document.querySelectorAll('[data-market-countdown]');

        function formatCountdown(seconds) {
            const safe = Math.max(0, Math.ceil(seconds));
            const minutes = String(Math.floor((safe % 3600) / 60)).padStart(2, '0');
            const hours = String(Math.floor(safe / 3600)).padStart(2, '0');
            const remainingSeconds = String(safe % 60).padStart(2, '0');
            return `${hours}:${minutes}:${remainingSeconds}`;
        }

        function updateMarketButtons() {
            const now = Date.now();

            countdownBlocks.forEach((timer) => {
                const marketAvailable = timer.dataset.marketAvailable;
                const button = timer.parentElement ? timer.parentElement.querySelector('[data-market-button]') : null;
                if (!marketAvailable || !button) {
                    return;
                }

                const targetTime = new Date(marketAvailable).getTime();
                if (Number.isNaN(targetTime)) {
                    return;
                }

                const remaining = Math.max(0, Math.ceil((targetTime - now) / 1000));

                if (remaining === 0) {
                    timer.textContent = 'This fish is ready for market sale.';
                    timer.classList.remove('is-active');
                    button.classList.remove('is-locked');
                    button.removeAttribute('aria-disabled');
                    button.setAttribute('href', button.dataset.marketUrl || 'fish-market.php');
                } else {
                    timer.textContent = `Market sale opens in ${formatCountdown(remaining)} later.`;
                    timer.classList.add('is-active');
                    button.classList.add('is-locked');
                    button.setAttribute('aria-disabled', 'true');
                    button.setAttribute('href', '#');
                }
            });
        }

        updateMarketButtons();
        window.setInterval(updateMarketButtons, 1000);
    })();
</script>
</body>
</html>

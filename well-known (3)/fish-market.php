<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sell_fish') {
    header('Content-Type: application/json; charset=UTF-8');

    $fishKey = isset($_POST['fish_key']) ? trim((string)$_POST['fish_key']) : '';
    echo json_encode(sellFishForUser($pdo, (int)$_SESSION['user_id'], $fishKey), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (string)($_GET['action'] ?? '') === 'market_feed') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'items' => getSharedMarketFeed($pdo, (int)$_SESSION['user_id'], 5),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function formatMarketCountdownText(int $seconds): string
{
    if ($seconds <= 0) {
        return 'The market is active. You can list your fish for auction now.';
    }

    $hours = (int)floor($seconds / 3600);
    $minutes = (int)floor(($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('Waiting %02d:%02d:%02d for the market to open.', $hours, $minutes, $remainingSeconds);
}

$userId = (int)$_SESSION['user_id'];
$marketNotificationData = getFishMarketAvailability($pdo, $userId);
$fishFoodData = getFishFoodDashboardData($pdo, $userId);
$aquariumData = getAquariumCollection($pdo, $userId);
$balance = (float)$fishFoodData['balance'];
$bonusBalance = (float)($fishFoodData['bonus_balance'] ?? 0);
$tradeBalance = (float)($fishFoodData['trade_balance'] ?? ($balance + $bonusBalance));
$hasTradeBalance = $tradeBalance > 0;

$marketRates = getFishMarketRates();

$ownedFish = [];
foreach ($aquariumData['items'] as $item) {
    $rate = $marketRates[$item['key']] ?? 2.00;
    $item['market_rate'] = $rate;
    $item['estimated_profit'] = $tradeBalance * ($rate / 100);
    $item['can_list_for_sale'] = $item['is_market_ready'] && $hasTradeBalance;
    $item['market_timer_text'] = formatMarketCountdownText((int)$item['market_countdown_seconds']);
    $item['market_status_label'] = $item['is_market_ready'] ? 'Ready for Sale' : 'Market Pending';
    if (!empty($item['is_market_sold'])) {
        $item['can_list_for_sale'] = false;
        $item['market_timer_text'] = 'This catch was sold. A new sale becomes available after the next catch.';
        $item['market_status_label'] = 'Sold';
    } elseif (!$hasTradeBalance) {
        $item['market_timer_text'] = 'Market balance required.';
        $item['market_status_label'] = 'Balance Required';
    }

    $ownedFish[] = $item;
}

$selectedFishKey = isset($_GET['fish']) ? trim((string)$_GET['fish']) : '';
$selectedFish = null;

foreach ($ownedFish as $ownedItem) {
    if ($ownedItem['key'] === $selectedFishKey) {
        if (empty($ownedItem['is_market_sold'])) {
            $selectedFish = $ownedItem;
        }
        break;
    }
}

$listedOwnedFish = array_values(array_filter($ownedFish, static function (array $item): bool {
    return empty($item['is_market_sold']);
}));

if ($selectedFish === null && !empty($listedOwnedFish)) {
    $selectedFish = $listedOwnedFish[0];
}

$marketFeed = [
    [
        'seller' => 'Aqua #204',
        'fish' => 'Neon Tetra',
        'status' => 'Sold',
        'note' => 'Closed in a short auction round with 2.70% return.',
        'time' => 'Just now',
    ],
    [
        'seller' => 'Aqua #118',
        'fish' => 'Angelfish',
        'status' => 'Collecting Bids',
        'note' => 'The market is busy. The final bid window has opened.',
        'time' => '1 min ago',
    ],
    [
        'seller' => 'Aqua #332',
        'fish' => 'Discus Fish',
        'status' => 'In Auction',
        'note' => 'Bids are accelerating for this high-level fish.',
        'time' => '3 min ago',
    ],
];

$marketFeedTemplates = [
    [
        'seller' => 'Aqua #204',
        'fish' => 'Neon Tetra',
        'status' => 'Sold',
        'note' => 'Closed in a short auction round with 2.70% return.',
    ],
    [
        'seller' => 'Aqua #118',
        'fish' => 'Angelfish',
        'status' => 'Collecting Bids',
        'note' => 'The market is busy. The final bid window has opened.',
    ],
    [
        'seller' => 'Aqua #332',
        'fish' => 'Discus Fish',
        'status' => 'In Auction',
        'note' => 'Bids are accelerating for this high-level fish.',
    ],
    [
        'seller' => 'Aqua #519',
        'fish' => 'Oscar Fish',
        'status' => 'Collecting Bids',
        'note' => 'Final bids are rising in the aggressive species category.',
    ],
    [
        'seller' => 'Aqua #087',
        'fish' => 'Guppy',
        'status' => 'Sold',
        'note' => 'Completed in a fast auction window with 4.00% return.',
    ],
    [
        'seller' => 'Aqua #261',
        'fish' => 'Lepistes',
        'status' => 'In Auction',
        'note' => 'New buyers are lining up for this mid-level collection fish.',
    ],
    [
        'seller' => 'Aqua #441',
        'fish' => 'Neon Tetra',
        'status' => 'Collecting Bids',
        'note' => 'Bid ranges are narrowing in the schooling fish session.',
    ],
];

$sharedMarketFeed = getSharedMarketFeed($pdo, $userId, 5);
$marketFeed = $sharedMarketFeed;
$marketFeedSignatures = [];
foreach ($marketFeed as $feedItem) {
    $marketFeedSignatures[] = ($feedItem['seller'] ?? '') . '|' . ($feedItem['fish'] ?? '') . '|' . ($feedItem['status'] ?? '');
}

if (count($marketFeed) < 5) {
    foreach ($marketFeedTemplates as $template) {
        $signature = ($template['seller'] ?? '') . '|' . ($template['fish'] ?? '') . '|' . ($template['status'] ?? '');
        if (in_array($signature, $marketFeedSignatures, true)) {
            continue;
        }

        $marketFeed[] = $template + ['time' => 'Just now'];
        $marketFeedSignatures[] = $signature;

        if (count($marketFeed) >= 5) {
            break;
        }
    }
}

$marketPageData = [
    'userId' => $userId,
    'selectedFish' => $selectedFish ? [
        'key' => $selectedFish['key'],
        'name' => $selectedFish['name'],
        'marketRate' => $selectedFish['market_rate'],
        'estimatedProfit' => round((float)$selectedFish['estimated_profit'], 2),
        'tradeBalance' => round($tradeBalance, 2),
        'marketAvailableAt' => $selectedFish['market_available_at'],
        'marketCountdownSeconds' => (int)$selectedFish['market_countdown_seconds'],
        'isMarketReady' => (bool)$selectedFish['is_market_ready'],
        'isSold' => (bool)($selectedFish['is_market_sold'] ?? false),
        'canSell' => (bool)$selectedFish['can_list_for_sale'],
        'hasTradeBalance' => $hasTradeBalance,
        'marketUrl' => $selectedFish['market_url'],
    ] : null,
    'marketFeed' => $sharedMarketFeed,
    'marketFeedTemplates' => $marketFeedTemplates,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fish Market</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body fish-market-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-market" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-market" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-market" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-market)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-market)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-market)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-market)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-market)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-market)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-market)" stroke-width="4.8" stroke-linecap="round"></path>
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

<main class="dashboard-main fish-market-main">
    <?php if ($selectedFish !== null): ?>
        <section class="fish-market-own-card" data-market-card>
            <div class="fish-market-own-media">
                <img src="<?= e($selectedFish['image']) ?>" alt="<?= e($selectedFish['image_alt']) ?>" class="fish-market-own-image">
            </div>

            <div class="fish-market-own-body">
                <div class="fish-market-own-tags" aria-hidden="true">
                    <?php foreach ($selectedFish['tags'] as $tag): ?>
                        <span class="fish-market-own-tag"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>

                <h1><?= e($selectedFish['name']) ?></h1>
                <span class="fish-market-own-status <?= !empty($selectedFish['is_market_sold']) || $selectedFish['can_list_for_sale'] ? 'is-ready' : 'is-waiting' ?>" data-market-status-label>
                    <?= e($selectedFish['market_status_label']) ?>
                </span>

                <div class="fish-market-own-meta">
                    <div class="fish-market-own-stat">
                        <span>Profit Rate</span>
                        <strong>%<?= e(number_format((float)$selectedFish['market_rate'], 2, '.', '')) ?></strong>
                    </div>
                    <div class="fish-market-own-stat">
                        <span>Market Balance</span>
                        <strong id="marketTradeBalanceValue">$<?= e(number_format($tradeBalance, 2, '.', ',')) ?></strong>
                    </div>
                    <div class="fish-market-own-stat">
                        <span>Estimated Profit</span>
                        <strong id="marketEstimatedProfitValue">$<?= e(number_format((float)$selectedFish['estimated_profit'], 2, '.', ',')) ?></strong>
                    </div>
                </div>

                <p
                    class="fish-market-own-timer <?= (!$selectedFish['can_list_for_sale'] && empty($selectedFish['is_market_sold'])) ? 'is-active' : '' ?>"
                    id="marketOwnTimer"
                    data-market-available="<?= e((string)$selectedFish['market_available_at']) ?>"
                >
                    <?= e($selectedFish['market_timer_text']) ?>
                </p>

                <button
                    type="button"
                    class="fish-market-sell-button <?= !empty($selectedFish['is_market_sold']) ? 'is-listed' : ($selectedFish['can_list_for_sale'] ? '' : 'is-locked') ?>"
                    id="marketSellButton"
                    <?= (!empty($selectedFish['is_market_sold']) || !$selectedFish['can_list_for_sale']) ? 'disabled' : '' ?>
                >
                    <?= $selectedFish['is_market_ready'] ? 'List for Sale' : 'Waiting for Market Open' ?>
                </button>

                <div class="fish-market-status-stream" id="marketStatusStream">
                    <?php if (!$hasTradeBalance): ?>
                        <p class="fish-market-status-line is-info">Market balance required.</p>
                    <?php endif; ?>
                    <p class="fish-market-status-line is-muted">The auction flow is ready. You can start the sale once the market becomes active.</p>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="aquarium-empty">
            <h2>You Do Not Have a Fish Ready for Market Yet</h2>
            <p>First catch a fish on the map page, then start the auction flow here.</p>
            <a href="map.php" class="aquarium-empty-button">Go to Map</a>
        </section>
    <?php endif; ?>

    <section class="fish-market-feed-shell">
        <div class="fish-market-feed-head">
            <h2>Live Market Feed</h2>
            <span>Caught fish currently listed for sale</span>
        </div>

        <div class="fish-market-feed" id="marketFeed">
            <?php foreach ($marketFeed as $feedItem): ?>
                <article class="fish-market-feed-item">
                    <div class="fish-market-feed-top">
                        <strong><?= e($feedItem['seller']) ?></strong>
                        <span><?= e($feedItem['time']) ?></span>
                    </div>
                    <h3><?= e($feedItem['fish']) ?></h3>
                    <span class="fish-market-feed-status"><?= e($feedItem['status']) ?></span>
                    <p><?= e($feedItem['note']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($listedOwnedFish)): ?>
        <section class="fish-market-owned-list" id="marketOwnedList">
            <div class="fish-market-feed-head">
                <h2>Your Fish</h2>
                <span>Queue status and expected return</span>
            </div>

            <div class="fish-market-owned-grid" id="marketOwnedGrid">
                <?php foreach ($listedOwnedFish as $ownedItem): ?>
                    <article class="fish-market-owned-row <?= $selectedFish !== null && $ownedItem['key'] === $selectedFish['key'] ? 'is-selected' : '' ?>" data-owned-fish-key="<?= e($ownedItem['key']) ?>">
                        <div class="fish-market-owned-copy">
                            <strong><?= e($ownedItem['name']) ?></strong>
                            <span>%<?= e(number_format((float)$ownedItem['market_rate'], 2, '.', '')) ?> rate | $<?= e(number_format((float)$ownedItem['estimated_profit'], 2, '.', ',')) ?> estimated</span>
                        </div>
                        <span class="fish-market-owned-badge <?= $ownedItem['can_list_for_sale'] ? 'is-ready' : 'is-waiting' ?>">
                            <?= $ownedItem['is_market_ready'] ? 'Ready' : 'Waiting' ?>
                        </span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
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
                    <path d="M4 15.5c0-3.9 3.6-7 8-7s8 3.1 8 7"></path>
                    <path d="M4 15.5v1.5A2 2 0 0 0 6 19h12a2 2 0 0 0 2-2v-1.5"></path>
                    <path d="M8 12.5h8"></path>
                    <circle cx="9" cy="10" r="1"></circle>
                </svg>
            </span>
            <span class="dashboard-footer-label">Aquarium</span>
        </a>

        <a class="dashboard-footer-item is-active" href="fish-market.php" aria-current="page">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4 8h16l-1.5 3H5.5z"></path>
                    <path d="M6 11v6h12v-6"></path>
                    <path d="M9 14c1.1-1 2.1-1 3.2 0 1.1 1 2.1 1 3.1 0"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">Market</span>
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

<script id="fishMarketPageData" type="application/json"><?= json_encode($marketPageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script>
    (() => {
        const payload = JSON.parse(document.getElementById('fishMarketPageData').textContent);
        const selectedFish = payload.selectedFish;
        const initialFeed = Array.isArray(payload.marketFeed) ? payload.marketFeed : [];
        const feedTemplates = Array.isArray(payload.marketFeedTemplates) ? payload.marketFeedTemplates : [];
        const sellButton = document.getElementById('marketSellButton');
        const timer = document.getElementById('marketOwnTimer');
        const statusLabel = document.querySelector('[data-market-status-label]');
        const statusStream = document.getElementById('marketStatusStream');
        const feed = document.getElementById('marketFeed');
        const ownedList = document.getElementById('marketOwnedList');
        const ownedGrid = document.getElementById('marketOwnedGrid');
        const tradeBalanceValue = document.getElementById('marketTradeBalanceValue');
        const estimatedProfitValue = document.getElementById('marketEstimatedProfitValue');
        let sharedFeedItems = [];
        let templateOffset = 0;

        if (!selectedFish || !sellButton || !timer || !statusLabel || !statusStream || !feed) {
            return;
        }

        function formatRelativeTime(timestamp) {
            const seconds = Math.max(0, Math.floor((Date.now() - timestamp) / 1000));
            if (seconds < 60) {
                return 'Just now';
            }

            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) {
                return `${minutes}  min ago`;
            }

            const hours = Math.floor(minutes / 60);
            return `${hours}  h ago`;
        }

        function normalizeFeedItem(item, fallbackMinutesAgo = 0) {
            const now = Date.now();
            return {
                userId: item.userId ?? null,
                seller: item.seller || 'Aqua',
                fish: item.fish || 'Fish',
                fishKey: item.fishKey || '',
                status: item.status || 'In Auction',
                note: item.note || 'Market feed is updating.',
                createdAt: typeof item.createdAt === 'number' ? item.createdAt : now - (fallbackMinutesAgo * 60000),
            };
        }

        function normalizeFeedItems(items) {
            return items.map((item, index) => normalizeFeedItem(item, index + 1));
        }

        function getFeedSignature(item) {
            return `${item.seller}|${item.fish}|${item.status}|${item.createdAt}`;
        }

        function createFeedMarkup(item, isUser = false) {
            const safeClass = isUser ? 'fish-market-feed-item is-user is-live' : 'fish-market-feed-item is-live';
            return `
                <article class="${safeClass}">
                    <div class="fish-market-feed-top">
                        <strong>${item.seller}</strong>
                        <span data-feed-time="${item.createdAt}">${formatRelativeTime(item.createdAt)}</span>
                    </div>
                    <h3>${item.fish}</h3>
                    <span class="fish-market-feed-status">${item.status}</span>
                    <p>${item.note}</p>
                </article>
            `;
        }

        function buildDisplayFeed() {
            const items = [];
            const signatures = new Set();

            sharedFeedItems.forEach((item) => {
                if (items.length >= 5) {
                    return;
                }

                const signature = getFeedSignature(item);
                if (signatures.has(signature)) {
                    return;
                }

                signatures.add(signature);
                items.push(item);
            });

            if (items.length >= 5 || feedTemplates.length === 0) {
                return items.slice(0, 5);
            }

            for (let index = 0; index < feedTemplates.length && items.length < 5; index += 1) {
                const template = feedTemplates[(templateOffset + index) % feedTemplates.length];
                const normalized = normalizeFeedItem(template, (index + 1) * 7);
                const signature = `${normalized.seller}|${normalized.fish}|${normalized.status}`;
                if (signatures.has(signature)) {
                    continue;
                }

                signatures.add(signature);
                items.push(normalized);
            }

            return items.slice(0, 5);
        }

        function renderFeed() {
            const items = buildDisplayFeed();
            feed.innerHTML = items.map((item) => createFeedMarkup(item, item.seller === 'You')).join('');
            updateFeedTimes();
        }

        function updateFeedTimes() {
            feed.querySelectorAll('[data-feed-time]').forEach((node) => {
                const timestamp = parseInt(node.getAttribute('data-feed-time') || '0', 10);
                if (!Number.isNaN(timestamp)) {
                    node.textContent = formatRelativeTime(timestamp);
                }
            });
        }

        function rotateTemplateFeed() {
            if (feedTemplates.length === 0) {
                return;
            }

            templateOffset = (templateOffset + 1) % feedTemplates.length;
            renderFeed();
        }

        function prependSharedFeedItem(item) {
            const nextItem = normalizeFeedItem(item);
            const remainingItems = sharedFeedItems.filter((existingItem) => getFeedSignature(existingItem) !== getFeedSignature(nextItem));
            sharedFeedItems = [nextItem, ...remainingItems].slice(0, 5);
            renderFeed();
        }

        function prependFeedItem(title, badge, note) {
            prependSharedFeedItem({
                seller: 'You',
                fish: title,
                status: badge,
                note,
                createdAt: Date.now(),
            });
        }

        function removeOwnedFishRow(fishKey) {
            if (!ownedGrid || !ownedList) {
                return;
            }

            const rows = Array.from(ownedGrid.querySelectorAll('[data-owned-fish-key]'));
            const targetRow = rows.find((row) => row.getAttribute('data-owned-fish-key') === fishKey);
            if (targetRow) {
                targetRow.remove();
            }

            if (ownedGrid.querySelector('[data-owned-fish-key]')) {
                return;
            }

            ownedList.style.display = 'none';
        }

        async function refreshSharedFeed() {
            try {
                const response = await fetch('fish-market.php?action=market_feed', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const result = await response.json();
                if (!result || !Array.isArray(result.items)) {
                    return;
                }

                sharedFeedItems = normalizeFeedItems(result.items);
                renderFeed();
            } catch (error) {
                // Bir sonraki yenilemede tekrar denenecek.
            }
        }

        function formatCountdown(seconds) {
            const safe = Math.max(0, Math.ceil(seconds));
            const hours = String(Math.floor(safe / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((safe % 3600) / 60)).padStart(2, '0');
            const remainingSeconds = String(safe % 60).padStart(2, '0');
            return `${hours}:${minutes}:${remainingSeconds}`;
        }

        function addStatusLine(text, tone = '') {
            const line = document.createElement('p');
            line.className = `fish-market-status-line ${tone}`.trim();
            line.textContent = text;
            statusStream.prepend(line);
        }

        function setStatusLines(lines) {
            statusStream.innerHTML = '';
            lines.forEach((item) => {
                const line = document.createElement('p');
                line.className = `fish-market-status-line ${item.tone || ''}`.trim();
                line.textContent = item.text;
                statusStream.append(line);
            });
        }

        function formatMoney(amount) {
            return `$${Number(amount || 0).toFixed(2)}`;
        }

        function updateCountdownState() {
            if (selectedFish.isSold) {
                timer.textContent = 'Sale completed.';
                timer.classList.remove('is-active');
                statusLabel.textContent = 'Sold';
                statusLabel.classList.remove('is-waiting');
                statusLabel.classList.add('is-ready');
                sellButton.textContent = 'Sale Completed';
                sellButton.disabled = true;
                sellButton.classList.remove('is-locked');
                sellButton.classList.add('is-listed');
                return;
            }

            if (!selectedFish.hasTradeBalance) {
                timer.textContent = 'Market balance required.';
                timer.classList.add('is-active');
                statusLabel.textContent = 'Balance Required';
                statusLabel.classList.remove('is-ready');
                statusLabel.classList.add('is-waiting');
                sellButton.textContent = 'Balance Required';
                sellButton.disabled = true;
                sellButton.classList.add('is-locked');
                sellButton.classList.remove('is-listed');
                return;
            }

            const target = new Date(selectedFish.marketAvailableAt).getTime();
            const remaining = Math.max(0, Math.ceil((target - Date.now()) / 1000));
            

            if (false) {
                timer.textContent = 'This fish has been listed. The sale continues in the market feed.';
                timer.classList.remove('is-active');
                statusLabel.textContent = 'In Auction';
                statusLabel.classList.remove('is-waiting');
                statusLabel.classList.add('is-ready');
                sellButton.textContent = 'Listed for Sale';
                sellButton.disabled = true;
                sellButton.classList.remove('is-locked');
                sellButton.classList.add('is-listed');
                return;
            }

            if (remaining === 0) {
                timer.textContent = 'The market is active. You can list your fish for auction now.';
                timer.classList.remove('is-active');
                statusLabel.textContent = 'Ready for Sale';
                statusLabel.classList.remove('is-waiting');
                statusLabel.classList.add('is-ready');
                sellButton.textContent = 'List for Sale';
                sellButton.disabled = false;
                sellButton.classList.remove('is-locked', 'is-listed');
            } else {
                timer.textContent = `Waiting ${formatCountdown(remaining)} for the market to open.`;
                timer.classList.add('is-active');
                statusLabel.textContent = 'Market Pending';
                statusLabel.classList.remove('is-ready');
                statusLabel.classList.add('is-waiting');
                sellButton.textContent = 'Waiting for Market Open';
                sellButton.disabled = true;
                sellButton.classList.add('is-locked');
                sellButton.classList.remove('is-listed');
            }
        }

        if (false) {
            addStatusLine(`${selectedFish.name} was already listed for auction.`, 'is-success');
            prependFeedItem(selectedFish.name, 'In Auction', `%${selectedFish.marketRate.toFixed(2)} was added to the user auction with the target return.`);
        }

        sharedFeedItems = normalizeFeedItems(initialFeed);
        renderFeed();

        sellButton.addEventListener('click', () => {
            if (sellButton.disabled) {
                return;
            }

            (async () => {
                sellButton.disabled = true;
                sellButton.textContent = 'Listing for Sale...';
                setStatusLines([
                    { text: 'Auction started.', tone: 'is-info' },
                ]);

                window.setTimeout(async () => {
                    try {
                        const response = await fetch('fish-market.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new URLSearchParams({
                                action: 'sell_fish',
                                fish_key: selectedFish.key,
                            }),
                        });

                        const saleResult = await response.json();

                        if (!saleResult.success) {
                            sellButton.disabled = false;
                            addStatusLine(saleResult.message || 'Sale could not be completed.', 'is-info');
                            updateCountdownState();
                            return;
                        }

                        selectedFish.isSold = true;
                        selectedFish.canSell = false;
                        selectedFish.tradeBalance = Number(saleResult.trade_balance || selectedFish.tradeBalance || 0);
                        selectedFish.estimatedProfit = selectedFish.tradeBalance * (selectedFish.marketRate / 100);
                        selectedFish.hasTradeBalance = selectedFish.tradeBalance > 0;

                        if (tradeBalanceValue) {
                            tradeBalanceValue.textContent = formatMoney(selectedFish.tradeBalance);
                        }

                        if (estimatedProfitValue) {
                            estimatedProfitValue.textContent = formatMoney(selectedFish.estimatedProfit);
                        }

                        setStatusLines([
                            { text: 'Auction started.', tone: 'is-info' },
                            { text: 'Sale completed.', tone: 'is-success' },
                        ]);
                        removeOwnedFishRow(selectedFish.key);
                        if (saleResult.feed_item) {
                            prependSharedFeedItem(saleResult.feed_item);
                        }
                        updateCountdownState();
                        window.setTimeout(refreshSharedFeed, 1200);
                        window.setTimeout(() => {
                            window.location.href = 'fish-market.php';
                        }, 1800);
                    } catch (error) {
                        sellButton.disabled = false;
                        addStatusLine('A connection error occurred during the sale.', 'is-info');
                        updateCountdownState();
                    }
                }, 2400);
            })();

            return;

            sellButton.disabled = true;
            sellButton.textContent = 'Listing for Sale...';

            addStatusLine('Auction process started.', 'is-info');

            window.setTimeout(() => {
                addStatusLine('Sending to the live bidding room.', 'is-info');
            }, 1100);

            window.setTimeout(() => {
                addStatusLine(`${selectedFish.name} listed for sale.`, 'is-success');
                prependFeedItem(selectedFish.name, 'In Auction', `%${selectedFish.marketRate.toFixed(2)} sale flow started with this profit rate.`);
                updateCountdownState();
            }, 2400);
        });

        updateCountdownState();
        window.setInterval(updateCountdownState, 1000);
        window.setInterval(updateFeedTimes, 30000);
        window.setInterval(refreshSharedFeed, 15000);
        window.setInterval(rotateTemplateFeed, 360000);
    })();
</script>
</body>
</html>



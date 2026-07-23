<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'catch_fish') {
    header('Content-Type: application/json; charset=UTF-8');

    $fishKey = isset($_POST['fish_key']) ? trim((string)$_POST['fish_key']) : '';
    echo json_encode(catchFishForUser($pdo, (int)$_SESSION['user_id'], $fishKey), JSON_UNESCAPED_UNICODE);
    exit;
}

function formatCountdownText(int $seconds): string
{
    if ($seconds <= 0) {
        return 'Ready to fish today.';
    }

    $hours = (int)floor($seconds / 3600);
    $minutes = (int)floor(($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('Wait %02d:%02d:%02d to fish again.', $hours, $minutes, $remainingSeconds);
}

$fishMapMeta = [
    'guppy' => [
        'habitat_image' => 'map/Guppy.png',
        'country' => 'Venezuela',
        'habitat' => 'slow-moving river',
        'info' => 'Usually lives in calm, planted freshwater environments.',
    ],
    'neon-tetra' => [
        'habitat_image' => 'map/Neon Tetra.png',
        'country' => 'Peru',
        'habitat' => 'Amazon River and tributaries',
        'info' => 'Lives in dark, soft waters known as blackwater.',
    ],
    'lepistes' => [
        'habitat_image' => 'map/Lepistes.png',
        'country' => 'Trinidad',
        'habitat' => 'Streams, ponds, and slow-moving waters',
        'info' => 'Common in warm, planted waters.',
    ],
    'angelfish' => [
        'habitat_image' => 'map/melekbaligi.png',
        'country' => 'Brazil',
        'habitat' => 'Amazon River and flooded forests',
        'info' => 'Lives in planted waters with roots and cover.',
    ],
    'discus' => [
        'habitat_image' => 'map/discus.png',
        'country' => 'Brazil',
        'habitat' => 'Amazon River (especially still areas)',
        'info' => 'Lives in very clean, warm, and soft water.',
    ],
    'oscar' => [
        'habitat_image' => 'map/Oscar.png',
        'country' => 'Colombia',
        'habitat' => 'Amazon and Orinoco rivers',
        'info' => 'Found in still, warm, planted waters.',
    ],
];

$fishMapData = getFishMapDashboardData($pdo, (int)$_SESSION['user_id']);
$marketNotificationData = getFishMarketAvailability($pdo, (int)$_SESSION['user_id']);
$mapCards = [];
$modalFishData = [];

foreach ($fishMapData['cards'] as $card) {
    if (!isset($fishMapMeta[$card['key']])) {
        continue;
    }

    $mergedCard = array_merge($card, $fishMapMeta[$card['key']]);
    $mergedCard['status_label'] = $mergedCard['is_unlocked'] ? 'Unlocked' : 'Locked';
    $mergedCard['unlock_rule_text'] = $mergedCard['signup_bonus']
        ? '$ 5-30 Balance'
        : 'Unlock: $' . $mergedCard['unlock_balance'] . '+ balance • ' . $mergedCard['referral_requirement'] . ' Free Refferal';
    $mergedCard['timer_text'] = !$mergedCard['is_unlocked']
        ? 'This fish is not unlocked yet.'
        : ($mergedCard['food_count'] <= 0
            ? 'You have no fish food left.'
            : formatCountdownText((int)$mergedCard['cooldown_seconds']));

    $mapCards[] = $mergedCard;

    $modalFishData[$mergedCard['key']] = [
        'key' => $mergedCard['key'],
        'name' => $mergedCard['name'],
        'tags' => $mergedCard['tags'],
        'country' => $mergedCard['country'],
        'habitat' => $mergedCard['habitat'],
        'info' => $mergedCard['info'],
        'foodCount' => (int)$mergedCard['food_count'],
        'isUnlocked' => (bool)$mergedCard['is_unlocked'],
        'canCatch' => (bool)$mergedCard['can_catch'],
        'cooldownSeconds' => (int)$mergedCard['cooldown_seconds'],
        'nextAvailableAt' => $mergedCard['next_available_at'],
        'buttonLabel' => $mergedCard['catch_button_label'],
        'catchImage' => $mergedCard['image'],
        'catchImageAlt' => $mergedCard['image_alt'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body map-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep)" stroke-width="4.8" stroke-linecap="round"></path>
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

<main class="dashboard-main map-main">
    <section class="map-grid" aria-label="Fish habitats">
        <?php foreach ($mapCards as $card): ?>
            <article class="map-card" id="<?= e('map-' . $card['key']) ?>" data-fish-card data-fish-key="<?= e($card['key']) ?>">
                <img src="<?= e($card['habitat_image']) ?>" alt="<?= e($card['name'] . ' habitat') ?>" class="map-card-image">

                <div class="map-card-body">
                    <div class="map-card-tags" aria-hidden="true">
                        <span class="map-card-tag"><?= e($card['name']) ?></span>
                        <span class="map-card-tag is-soft"><?= e($card['tags'][0] ?? 'Freshwater') ?></span>
                    </div>

                    <h2 class="map-card-title"><?= e($card['name']) ?></h2>

                    <span class="map-card-status <?= $card['is_unlocked'] ? 'is-open' : 'is-locked' ?>" data-status-label>
                        <?= e($card['status_label']) ?>
                    </span>

                    <p class="map-card-unlock-rule"><?= e($card['unlock_rule_text']) ?></p>

                    <div class="map-card-info">
                        <p><strong>Country:</strong> <?= e($card['country']) ?></p>
                        <p><strong>Habitat:</strong> <?= e($card['habitat']) ?></p>
                        <p><strong>Info:</strong> <?= e($card['info']) ?></p>
                    </div>

                    <div class="map-card-meta">
                        <div class="map-card-food">
                            <span>Food Count</span>
                            <strong data-food-count><?= e((string)$card['food_count']) ?></strong>
                        </div>
                        <p
                            class="map-card-timer <?= $card['cooldown_seconds'] > 0 ? 'is-active' : '' ?>"
                            data-countdown
                            data-next-available="<?= e((string)($card['next_available_at'] ?? '')) ?>"
                        >
                            <?= e($card['timer_text']) ?>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="map-card-button <?= !$card['is_unlocked'] ? 'is-locked' : ($card['can_catch'] ? 'is-open' : 'is-waiting') ?>"
                        data-open-catch-modal
                        data-fish-key="<?= e($card['key']) ?>"
                        <?= !$card['can_catch'] ? 'disabled' : '' ?>
                    >
                        <span data-button-label><?= e($card['catch_button_label']) ?></span>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<div class="map-modal" id="catchModal" hidden>
    <div class="map-modal-backdrop" data-close-catch-modal></div>
    <div class="map-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="catchModalTitle">
        <button type="button" class="map-modal-close" data-close-catch-modal aria-label="Close">×</button>

        <div class="map-modal-video-shell">
            <video
                id="catchModalVideo"
                class="map-modal-video"
                autoplay
                muted
                playsinline
                preload="auto"
            >
                <source src="map/ilkvideo.mp4" type="video/mp4">
            </video>
        </div>

        <div class="map-modal-content">
            <section class="map-modal-stage is-active" data-modal-stage="intro">
                <div class="map-modal-tags" id="catchModalTags"></div>
                <h2 class="map-modal-title" id="catchModalTitle">Fishing</h2>

                <div class="map-modal-food-panel">
                    <span>Remaining Food</span>
                    <strong id="catchModalFoodCount">0</strong>
                </div>

                <div class="map-modal-info">
                    <p><strong>Country:</strong> <span id="catchModalCountry"></span></p>
                    <p><strong>Habitat:</strong> <span id="catchModalHabitat"></span></p>
                    <p><strong>Info:</strong> <span id="catchModalInfo"></span></p>
                </div>

                <p class="map-modal-message" id="catchModalMessage">Start the fishing attempt when you are ready.</p>

                <button type="button" class="map-modal-action" id="catchModalAction">Catch Fish</button>
            </section>

            <section class="map-modal-stage" data-modal-stage="result">
                <img src="" alt="" class="map-modal-result-image" id="catchModalResultImage">
                <h3 class="map-modal-result-title" id="catchModalResultTitle">Success</h3>
                <p class="map-modal-result-text" id="catchModalResultText"></p>
                <button type="button" class="map-modal-action is-secondary" id="catchModalAquariumButton">Add to Aquarium</button>
            </section>
        </div>
    </div>
</div>

<footer class="dashboard-footer" aria-label="Primary footer navigation">
    <nav class="dashboard-footer-bar" aria-label="Primary">
        <a class="dashboard-footer-item is-active" href="dashboard.php" aria-current="page">
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

<script id="mapFishData" type="application/json"><?= json_encode($modalFishData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script>
    (() => {
        const fishState = JSON.parse(document.getElementById('mapFishData').textContent);
        const catchModal = document.getElementById('catchModal');
        const catchModalVideo = document.getElementById('catchModalVideo');
        const introStage = catchModal.querySelector('[data-modal-stage="intro"]');
        const resultStage = catchModal.querySelector('[data-modal-stage="result"]');
        const modalTags = document.getElementById('catchModalTags');
        const modalTitle = document.getElementById('catchModalTitle');
        const modalFoodCount = document.getElementById('catchModalFoodCount');
        const modalCountry = document.getElementById('catchModalCountry');
        const modalHabitat = document.getElementById('catchModalHabitat');
        const modalInfo = document.getElementById('catchModalInfo');
        const modalMessage = document.getElementById('catchModalMessage');
        const modalAction = document.getElementById('catchModalAction');
        const modalResultImage = document.getElementById('catchModalResultImage');
        const modalResultTitle = document.getElementById('catchModalResultTitle');
        const modalResultText = document.getElementById('catchModalResultText');
        const modalAquariumButton = document.getElementById('catchModalAquariumButton');

        const introVideoSrc = 'map/ilkvideo.mp4';
        const catchVideoSrc = 'map/baliktutmavideosu.mp4';

        let activeFishKey = null;
        let modalMode = 'intro';
        let pendingCatchResult = null;

        function formatCountdown(seconds) {
            const safeSeconds = Math.max(0, Math.ceil(seconds));
            const hours = String(Math.floor(safeSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((safeSeconds % 3600) / 60)).padStart(2, '0');
            const remainingSeconds = String(safeSeconds % 60).padStart(2, '0');

            return `${hours}:${minutes}:${remainingSeconds}`;
        }

        function refreshFishAvailability(key) {
            const state = fishState[key];
            if (!state) {
                return;
            }

            let cooldownSeconds = 0;
            if (state.nextAvailableAt) {
                const nextTimestamp = new Date(state.nextAvailableAt).getTime();
                if (!Number.isNaN(nextTimestamp)) {
                    cooldownSeconds = Math.max(0, Math.ceil((nextTimestamp - Date.now()) / 1000));
                }
            }

            state.cooldownSeconds = cooldownSeconds;
            state.canCatch = state.isUnlocked && state.foodCount > 0 && cooldownSeconds === 0;

            if (!state.isUnlocked) {
                state.buttonLabel = 'Locked';
                state.timerText = 'This fish is not unlocked yet.';
            } else if (state.foodCount <= 0) {
                state.buttonLabel = 'No Food';
                state.timerText = 'You have no fish food left.';
            } else if (cooldownSeconds > 0) {
                state.buttonLabel = 'Wait 24 Hours';
                state.timerText = `Wait ${formatCountdown(cooldownSeconds)} to fish again.`;
            } else {
                state.buttonLabel = 'Catch Fish';
                state.timerText = 'Ready to fish today.';
            }
        }

        function renderCardState(key) {
            const state = fishState[key];
            const card = document.querySelector(`[data-fish-card][data-fish-key="${key}"]`);
            if (!state || !card) {
                return;
            }

            const foodCount = card.querySelector('[data-food-count]');
            const countdown = card.querySelector('[data-countdown]');
            const button = card.querySelector('[data-open-catch-modal]');
            const buttonLabel = card.querySelector('[data-button-label]');

            if (foodCount) {
                foodCount.textContent = String(state.foodCount);
            }

            if (countdown) {
                countdown.textContent = state.timerText;
                countdown.classList.toggle('is-active', state.cooldownSeconds > 0);
                countdown.dataset.nextAvailable = state.nextAvailableAt || '';
            }

            if (button && buttonLabel) {
                button.disabled = !state.canCatch;
                button.classList.remove('is-open', 'is-locked', 'is-waiting');

                if (!state.isUnlocked) {
                    button.classList.add('is-locked');
                } else if (state.canCatch) {
                    button.classList.add('is-open');
                } else {
                    button.classList.add('is-waiting');
                }

                buttonLabel.textContent = state.buttonLabel;
            }
        }

        function renderModalState() {
            if (!activeFishKey || !fishState[activeFishKey]) {
                return;
            }

            const state = fishState[activeFishKey];
            modalFoodCount.textContent = String(state.foodCount);

            if (modalMode !== 'intro') {
                return;
            }

            modalAction.disabled = !state.canCatch;
            modalAction.textContent = state.canCatch ? 'Catch Fish' : state.buttonLabel;

            if (!state.isUnlocked) {
                modalMessage.textContent = 'This fish is not unlocked yet.';
            } else if (state.foodCount <= 0) {
                modalMessage.textContent = 'You have no fish food left. You need to earn or buy more fish food.';
            } else if (state.cooldownSeconds > 0) {
                modalMessage.textContent = `You need to wait ${formatCountdown(state.cooldownSeconds)} to fish again.`;
            } else {
                modalMessage.textContent = 'Start the fishing attempt when you are ready.';
            }
        }

        function switchVideo(source) {
            catchModalVideo.pause();
            catchModalVideo.setAttribute('src', source);
            catchModalVideo.load();
            const playPromise = catchModalVideo.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {});
            }
        }

        function openCatchModal(key) {
            if (!fishState[key]) {
                return;
            }

            activeFishKey = key;
            pendingCatchResult = null;
            modalMode = 'intro';

            const state = fishState[key];
            modalTags.innerHTML = '';

            (state.tags || []).forEach((tag) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'map-modal-tag';
                tagElement.textContent = tag;
                modalTags.appendChild(tagElement);
            });

            modalTitle.textContent = state.name;
            modalCountry.textContent = state.country;
            modalHabitat.textContent = state.habitat;
            modalInfo.textContent = state.info;

            introStage.classList.add('is-active');
            resultStage.classList.remove('is-active');
            modalResultImage.removeAttribute('src');
            modalResultImage.setAttribute('alt', '');
            switchVideo(introVideoSrc);
            renderModalState();

            catchModal.hidden = false;
            document.body.classList.add('is-modal-open');
        }

        function closeCatchModal() {
            catchModal.hidden = true;
            document.body.classList.remove('is-modal-open');
            catchModalVideo.pause();
            activeFishKey = null;
            pendingCatchResult = null;
            modalMode = 'intro';
        }

        function showCatchResult() {
            if (!pendingCatchResult || !activeFishKey || !fishState[activeFishKey]) {
                return;
            }

            const state = fishState[activeFishKey];
            modalMode = 'result';
            introStage.classList.remove('is-active');
            resultStage.classList.add('is-active');
            modalResultImage.src = pendingCatchResult.fish.image;
            modalResultImage.alt = pendingCatchResult.fish.image_alt;
            modalResultTitle.textContent = `Success, you caught ${pendingCatchResult.fish.name}`;
            modalResultText.textContent = `1 food was used. Remaining food: ${state.foodCount}. This card will be active again in 24 hours.`;
        }

        async function startCatch() {
            if (!activeFishKey || !fishState[activeFishKey]) {
                return;
            }

            const state = fishState[activeFishKey];
            if (!state.canCatch) {
                renderModalState();
                return;
            }

            modalAction.disabled = true;
            modalAction.textContent = 'Starting catch...';

            try {
                const response = await fetch('map.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        action: 'catch_fish',
                        fish_key: activeFishKey,
                    }),
                });

                const payload = await response.json();

                if (!payload.success) {
                    if (typeof payload.remaining_food === 'number') {
                        state.foodCount = payload.remaining_food;
                    }
                    if (payload.next_available_at) {
                        state.nextAvailableAt = payload.next_available_at;
                    }
                    refreshFishAvailability(activeFishKey);
                    renderCardState(activeFishKey);
                    modalMode = 'intro';
                    modalMessage.textContent = payload.message || 'The fish could not be caught.';
                    renderModalState();
                    return;
                }

                state.foodCount = payload.remaining_food;
                state.nextAvailableAt = payload.next_available_at;
                pendingCatchResult = payload;

                if (window.aquaMarketNotifier && payload.market_available_at) {
                    window.aquaMarketNotifier.addItem({
                        key: payload.fish.key,
                        name: payload.fish.name,
                        market_available_at: payload.market_available_at,
                    });
                }

                refreshFishAvailability(activeFishKey);
                renderCardState(activeFishKey);
                modalMode = 'catching';
                modalMessage.textContent = 'Searching for the fish. The result will appear when the video ends.';
                modalAction.disabled = true;
                modalAction.textContent = 'Fishing in Progress';
                switchVideo(catchVideoSrc);
            } catch (error) {
                modalMode = 'intro';
                modalMessage.textContent = 'A connection error occurred while catching the fish.';
                renderModalState();
            }
        }

        document.querySelectorAll('[data-open-catch-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const fishKey = button.dataset.fishKey;
                openCatchModal(fishKey);
            });
        });

        document.querySelectorAll('[data-close-catch-modal]').forEach((button) => {
            button.addEventListener('click', closeCatchModal);
        });

        modalAction.addEventListener('click', startCatch);
        modalAquariumButton.addEventListener('click', () => {
            window.location.href = 'aquarium.php';
        });

        catchModalVideo.addEventListener('ended', () => {
            if (modalMode === 'catching') {
                showCatchResult();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !catchModal.hidden) {
                closeCatchModal();
            }
        });

        Object.keys(fishState).forEach((key) => {
            refreshFishAvailability(key);
            renderCardState(key);
        });

        window.setInterval(() => {
            Object.keys(fishState).forEach((key) => {
                refreshFishAvailability(key);
                renderCardState(key);
            });

            if (!catchModal.hidden) {
                renderModalState();
            }
        }, 1000);
    })();
</script>
</body>
</html>

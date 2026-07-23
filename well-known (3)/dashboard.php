<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';
require __DIR__ . '/includes/app_settings.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$marketNotificationData = getFishMarketAvailability($pdo, (int)$_SESSION['user_id']);
$helpTelegramUrl = getAppSetting($pdo, 'help_telegram_url', 'https://t.me/aquavestsupport') ?? 'https://t.me/aquavestsupport';
$fishMarketRates = getFishMarketRates();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
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

<main class="dashboard-main">
    <section class="dashboard-card">
        <video class="dashboard-video" autoplay muted loop playsinline preload="metadata">
            <source src="slide.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </section>

    <section class="dashboard-menu-grid" aria-label="Dashboard menu">
        <a class="dashboard-menu-item" href="security.php">
            <span class="dashboard-menu-icon tone-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M12 3.5l7 3v5.2c0 4.1-2.8 7.8-7 8.8-4.2-1-7-4.7-7-8.8V6.5z"></path>
                    <path d="M9.5 12l1.7 1.7 3.3-3.7"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Security</span>
        </a>

        <a class="dashboard-menu-item" href="company.php">
            <span class="dashboard-menu-icon tone-green" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M5 19h14"></path>
                    <path d="M7.5 19V8.5"></path>
                    <path d="M12 19V5"></path>
                    <path d="M16.5 19v-7"></path>
                    <path d="M5 8.5h5"></path>
                    <path d="M12 5h4.5"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Company</span>
        </a>

        <a class="dashboard-menu-item" href="#">
            <span class="dashboard-menu-icon tone-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="5" y="4.5" width="14" height="15" rx="2"></rect>
                    <path d="M9 8.5h6"></path>
                    <path d="M12 15V9"></path>
                    <path d="M9.5 12.5L12 15l2.5-2.5"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Download</span>
        </a>

        <a class="dashboard-menu-item" href="invite.php">
            <span class="dashboard-menu-icon tone-green" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="8.5" cy="8" r="2.3"></circle>
                    <circle cx="15.5" cy="9.5" r="1.9"></circle>
                    <path d="M4.5 18.5c0-2.6 2.1-4.5 4.7-4.5 1.5 0 2.9.6 3.8 1.7"></path>
                    <path d="M13 16.5h6"></path>
                    <path d="M16 13.5v6"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Invite Friends</span>
        </a>

        <a class="dashboard-menu-item" href="deposit.php">
            <span class="dashboard-menu-icon tone-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect>
                    <path d="M12 4v10"></path>
                    <path d="M8.5 10.5L12 14l3.5-3.5"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Deposit</span>
        </a>

        <a class="dashboard-menu-item" href="withdrawal.php">
            <span class="dashboard-menu-icon tone-green" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect>
                    <path d="M12 19V9"></path>
                    <path d="M8.5 12.5L12 9l3.5 3.5"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Withdraw</span>
        </a>

        <a class="dashboard-menu-item" href="fish-food.php">
            <span class="dashboard-menu-icon tone-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M5 14h14l-1.4 4H6.4z"></path>
                    <path d="M7.5 11.5c1.2-1 2.4-1 3.6 0 1.1 1 2.2 1 3.4 0"></path>
                    <circle cx="9" cy="8.1" r="1"></circle>
                    <circle cx="12.5" cy="6.8" r="1"></circle>
                    <circle cx="16" cy="8.2" r="1"></circle>
                </svg>
            </span>
            <span class="dashboard-menu-text">Fish Food</span>
        </a>

        <button type="button" class="dashboard-menu-item dashboard-menu-button" data-open-help-modal>
            <span class="dashboard-menu-icon tone-green" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M21 5L3.8 11.6l5.2 2.1L18.2 7 11 14.6V19l3.2-3.1 4.4 2.9L21 5z"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Support</span>
        </button>

        <a class="dashboard-menu-item" href="fish-market.php">
            <span class="dashboard-menu-icon tone-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4 8h16l-1.5 3H5.5z"></path>
                    <path d="M6 11v6h12v-6"></path>
                    <path d="M9 14c1.1-1 2.1-1 3.2 0 1.1 1 2.1 1 3.1 0"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Fish Market</span>
        </a>

        <a class="dashboard-menu-item" href="map.php">
            <span class="dashboard-menu-icon tone-green" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4 6l6 2 4-2 6 2v10l-6-2-4 2-6-2z"></path>
                    <path d="M10 8v10M14 6v10"></path>
                    <circle cx="14" cy="11" r="1.4"></circle>
                </svg>
            </span>
            <span class="dashboard-menu-text">Map</span>
        </a>

        <a class="dashboard-menu-item" href="team.php">
            <span class="dashboard-menu-icon tone-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3"></circle>
                    <circle cx="6.8" cy="10.2" r="2.1"></circle>
                    <circle cx="17.2" cy="10.2" r="2.1"></circle>
                    <path d="M8 18.5c0-2.1 1.8-3.9 4-3.9s4 1.8 4 3.9"></path>
                    <path d="M3.8 18.5c0-1.6 1.4-2.9 3-2.9"></path>
                    <path d="M20.2 18.5c0-1.6-1.4-2.9-3-2.9"></path>
                </svg>
            </span>
            <span class="dashboard-menu-text">Team</span>
        </a>

        <a class="dashboard-menu-item" href="news.php">
            <span class="dashboard-menu-icon tone-green" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="4" y="5" width="16" height="14" rx="2"></rect>
                    <path d="M7 9h10M7 12h6M7 15h7"></path>
                    <circle cx="16.5" cy="13.5" r="1.8"></circle>
                </svg>
            </span>
            <span class="dashboard-menu-text">Sea News</span>
        </a>
    </section>

    <section class="dashboard-invite-card" aria-label="friend invitation banner">
        <div class="dashboard-invite-copy">Invite Your Friends, Earn Together Fast earning!</div>
        <img src="invite.png" alt="friend invitation" class="dashboard-invite-image">
    </section>

    <section class="dashboard-fish-list" aria-label="fish list">
        <article class="dashboard-fish-item">
            <a href="map.php#map-guppy" class="dashboard-fish-link">
                <div class="dashboard-fish-media">
                    <img src="akvaryum/Guppy.webp" alt="Guppy fish" class="dashboard-fish-image">
                </div>

                <div class="dashboard-fish-content">
                    <div class="dashboard-fish-labels" aria-hidden="true">
                        <span class="dashboard-fish-label is-primary">Freshwater</span>
                        <span class="dashboard-fish-label is-accent">Peaceful</span>
                    </div>

                    <h3 class="dashboard-fish-title">Guppy</h3>

                    <p class="dashboard-fish-unlock-rule">Open at sign-up.</p>
                    <p class="dashboard-fish-rate">Return <?= e(number_format((float)($fishMarketRates['guppy'] ?? 0), 2)) ?>%</p>

                    <div class="dashboard-fish-stats">
                        <div class="dashboard-fish-stat">
                            <div class="dashboard-fish-value">3-5 cm</div>
                            <div class="dashboard-fish-note">Average size</div>
                        </div>

                        <div class="dashboard-fish-stat is-soft">
                            <div class="dashboard-fish-value">Easy</div>
                            <div class="dashboard-fish-note">Care level</div>
                        </div>
                    </div>

                    <span class="dashboard-fish-button">Catch Fish</span>
                </div>
            </a>
        </article>

        <article class="dashboard-fish-item">
            <a href="map.php#map-neon-tetra" class="dashboard-fish-link">
                <div class="dashboard-fish-media">
                    <img src="akvaryum/neontera.jpg" alt="Neon Tetra fish" class="dashboard-fish-image">
                </div>

                <div class="dashboard-fish-content">
                    <div class="dashboard-fish-labels" aria-hidden="true">
                        <span class="dashboard-fish-label is-primary">Amazon</span>
                        <span class="dashboard-fish-label is-accent">Schooling</span>
                    </div>

                    <h3 class="dashboard-fish-title">Neon Tetra</h3>

                    <p class="dashboard-fish-unlock-rule">$30+ balance • </p>
                    <p class="dashboard-fish-rate">Return <?= e(number_format((float)($fishMarketRates['neon-tetra'] ?? 0), 2)) ?>%</p>

                    <div class="dashboard-fish-stats">
                        <div class="dashboard-fish-stat">
                            <div class="dashboard-fish-value">3-4 cm</div>
                            <div class="dashboard-fish-note">Average size</div>
                        </div>

                        <div class="dashboard-fish-stat is-soft">
                            <div class="dashboard-fish-value">Easy</div>
                            <div class="dashboard-fish-note">Care level</div>
                        </div>
                    </div>

                    <span class="dashboard-fish-button">Catch Fish</span>
                </div>
            </a>
        </article>

        <article class="dashboard-fish-item">
            <a href="map.php#map-lepistes" class="dashboard-fish-link">
                <div class="dashboard-fish-media">
                    <img src="akvaryum/troud.jpg" alt="Lepistes fish" class="dashboard-fish-image">
                </div>

                <div class="dashboard-fish-content">
                    <div class="dashboard-fish-labels" aria-hidden="true">
                        <span class="dashboard-fish-label is-primary">Freshwater</span>
                        <span class="dashboard-fish-label is-accent">Hardy</span>
                    </div>

                    <h3 class="dashboard-fish-title">Troud</h3>

                    <p class="dashboard-fish-unlock-rule">$80+ balance • </p>
                    <p class="dashboard-fish-rate">Return <?= e(number_format((float)($fishMarketRates['lepistes'] ?? 0), 2)) ?>%</p>

                    <div class="dashboard-fish-stats">
                        <div class="dashboard-fish-stat">
                            <div class="dashboard-fish-value">3-5 cm</div>
                            <div class="dashboard-fish-note">Average size</div>
                        </div>

                        <div class="dashboard-fish-stat is-soft">
                            <div class="dashboard-fish-value">Medium</div>
                            <div class="dashboard-fish-note">Care level</div>
                        </div>
                    </div>

                    <span class="dashboard-fish-button">Catch Fish</span>
                </div>
            </a>
        </article>

        <article class="dashboard-fish-item">
            <a href="map.php#map-angelfish" class="dashboard-fish-link">
                <div class="dashboard-fish-media">
                    <img src="akvaryum/melek-baligi.jpg" alt="Angelfish" class="dashboard-fish-image">
                </div>

                <div class="dashboard-fish-content">
                    <div class="dashboard-fish-labels" aria-hidden="true">
                        <span class="dashboard-fish-label is-primary">Freshwater</span>
                        <span class="dashboard-fish-label is-accent">Semi-peaceful</span>
                    </div>

                    <h3 class="dashboard-fish-title">Angelfish</h3>

                    <p class="dashboard-fish-unlock-rule">$150+ balance • </p>
                    <p class="dashboard-fish-rate">Return <?= e(number_format((float)($fishMarketRates['angelfish'] ?? 0), 2)) ?>%</p>

                    <div class="dashboard-fish-stats">
                        <div class="dashboard-fish-stat">
                            <div class="dashboard-fish-value">10–15 cm</div>
                            <div class="dashboard-fish-note">Average size</div>
                        </div>

                        <div class="dashboard-fish-stat is-soft">
                            <div class="dashboard-fish-value">Medium</div>
                            <div class="dashboard-fish-note">Care level</div>
                        </div>
                    </div>

                    <span class="dashboard-fish-button">Catch Fish</span>
                </div>
            </a>
        </article>

        <article class="dashboard-fish-item">
            <a href="map.php#map-discus" class="dashboard-fish-link">
                <div class="dashboard-fish-media">
                    <img src="akvaryum/diskus.jpg" alt="Discus Fish" class="dashboard-fish-image">
                </div>

                <div class="dashboard-fish-content">
                    <div class="dashboard-fish-labels" aria-hidden="true">
                        <span class="dashboard-fish-label is-primary">Freshwater</span>
                        <span class="dashboard-fish-label is-accent">Peaceful</span>
                    </div>

                    <h3 class="dashboard-fish-title">Discus Fish</h3>

                    <p class="dashboard-fish-unlock-rule">$250+ balance • 2 referrals</p>
                    <p class="dashboard-fish-rate">Return <?= e(number_format((float)($fishMarketRates['discus'] ?? 0), 2)) ?>%</p>

                    <div class="dashboard-fish-stats">
                        <div class="dashboard-fish-stat">
                            <div class="dashboard-fish-value">15–20 cm</div>
                            <div class="dashboard-fish-note">Average size</div>
                        </div>

                        <div class="dashboard-fish-stat is-soft">
                            <div class="dashboard-fish-value">Hard</div>
                            <div class="dashboard-fish-note">Care level</div>
                        </div>
                    </div>

                    <span class="dashboard-fish-button">Catch Fish</span>
                </div>
            </a>
        </article>

        <article class="dashboard-fish-item">
            <a href="map.php#map-oscar" class="dashboard-fish-link">
                <div class="dashboard-fish-media">
                    <img src="akvaryum/oscar-baligi.webp" alt="Oscar Fish" class="dashboard-fish-image">
                </div>

                <div class="dashboard-fish-content">
                    <div class="dashboard-fish-labels" aria-hidden="true">
                        <span class="dashboard-fish-label is-primary">Freshwater</span>
                        <span class="dashboard-fish-label is-accent">Semi-aggressive</span>
                    </div>

                    <h3 class="dashboard-fish-title">Oscar Fish</h3>

                    <p class="dashboard-fish-unlock-rule">$400+ balance • 4 referrals</p>
                    <p class="dashboard-fish-rate">Return <?= e(number_format((float)($fishMarketRates['oscar'] ?? 0), 2)) ?>%</p>

                    <div class="dashboard-fish-stats">
                        <div class="dashboard-fish-stat">
                            <div class="dashboard-fish-value">25–35 cm</div>
                            <div class="dashboard-fish-note">Average size</div>
                        </div>

                        <div class="dashboard-fish-stat is-soft">
                            <div class="dashboard-fish-value">Hard</div>
                            <div class="dashboard-fish-note">Care level</div>
                        </div>
                    </div>

                    <span class="dashboard-fish-button">Catch Fish</span>
                </div>
            </a>
        </article>
    </section>
</main>

<div class="help-modal" id="helpModal" hidden>
    <div class="help-modal-backdrop" data-close-help-modal></div>
    <div class="help-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle">
        <button type="button" class="help-modal-close" data-close-help-modal aria-label="Close">×</button>

        <div class="help-modal-content">
            <span class="help-modal-pill">Support</span>
            <h2 class="help-modal-title" id="helpModalTitle">7/24 Support Center</h2>
            <p class="help-modal-text">You can reach the support team via our Telegram channel.</p>
            <a href="<?= e($helpTelegramUrl) ?>" target="_blank" rel="noopener noreferrer" class="help-modal-action">
                Go to Telegram Channel
            </a>
        </div>
    </div>
</div>

<footer class="dashboard-footer" aria-label="Birincil alt gezinme">
    <nav class="dashboard-footer-bar" aria-label="Birincil">
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
<script>
    (() => {
        const helpModal = document.getElementById('helpModal');
        const openHelpButton = document.querySelector('[data-open-help-modal]');

        if (!helpModal || !openHelpButton) {
            return;
        }

        const openModal = () => {
            helpModal.hidden = false;
            document.body.classList.add('is-modal-open');
        };

        const closeModal = () => {
            helpModal.hidden = true;
            document.body.classList.remove('is-modal-open');
        };

        openHelpButton.addEventListener('click', openModal);

        helpModal.querySelectorAll('[data-close-help-modal]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !helpModal.hidden) {
                closeModal();
            }
        });
    })();
</script>
</body>
</html>

<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$marketNotificationData = getFishMarketAvailability($pdo, (int)$_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body company-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-company" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-company" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-company" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-company)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-company)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-company)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-company)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-company)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-company)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-company)" stroke-width="4.8" stroke-linecap="round"></path>
            <path d="M22 39C25 36 27 33.5 27.8 30" fill="none" stroke="#78c995" stroke-width="2.3" stroke-linecap="round"></path>
            <path d="M40.5 35.5C37 33.2 35.2 30.8 34.6 28" fill="none" stroke="#69bb89" stroke-width="2.1" stroke-linecap="round"></path>
            <circle cx="10.8" cy="21.2" r="1.9" fill="#9fe5d7" opacity="0.72"></circle>
            <circle cx="52.5" cy="15.8" r="1.7" fill="#b2eee3" opacity="0.62"></circle>
        </svg>
        <span class="dashboard-brand-name">Aqua</span>
    </div>

    <div class="dashboard-actions">
        <a href="dashboard.php" class="dashboard-icon-btn" aria-label="Back to dashboard">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14.5 5.5L8 12l6.5 6.5"></path>
                <path d="M9 12h10"></path>
            </svg>
        </a>
        <a href="logout.php" class="dashboard-icon-btn" aria-label="Sign out">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14 7V5.5A1.5 1.5 0 0 0 12.5 4h-5A1.5 1.5 0 0 0 6 5.5v13A1.5 1.5 0 0 0 7.5 20h5A1.5 1.5 0 0 0 14 18.5V17"></path>
                <path d="M10 12h10"></path>
                <path d="M17 8.5l3.5 3.5-3.5 3.5"></path>
            </svg>
        </a>
    </div>
</header>

<?php require __DIR__ . '/includes/market_notification_partial.php'; ?>

<main class="dashboard-main company-main">
    <section class="company-hero" aria-label="Company overview">
        <span class="company-kicker">Enterprise Introduction</span>
        <h1>Company</h1>
        <p>
            Aqua is built as a mobile-first platform around aquarium-themed products, simple account journeys,
            and a lightweight dashboard experience designed to stay readable and fast on smaller screens.
        </p>

        <div class="company-chip-row" aria-hidden="true">
            <span class="company-chip">Mobile-first</span>
            <span class="company-chip">Simple flows</span>
            <span class="company-chip">Readable layout</span>
        </div>
    </section>

    <section class="company-grid" aria-label="Company details">
        <article class="company-card">
            <div class="company-card-top">
                <span class="company-card-icon tone-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 18h14"></path>
                        <path d="M7 18V8"></path>
                        <path d="M12 18V5"></path>
                        <path d="M17 18v-9"></path>
                        <path d="M5 8h4"></path>
                        <path d="M12 5h4"></path>
                    </svg>
                </span>
                <h2>Who We Are</h2>
            </div>
            <p>
                Our presentation is centered on aquarium culture, fish discovery, and a clean digital experience
                that keeps navigation straightforward for everyday users.
            </p>
        </article>

        <article class="company-card">
            <div class="company-card-top">
                <span class="company-card-icon tone-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 18h16"></path>
                        <path d="M6.5 18V7.5L12 4l5.5 3.5V18"></path>
                        <path d="M9.5 11h5"></path>
                        <path d="M9.5 14h5"></path>
                    </svg>
                </span>
                <h2>Market Focus</h2>
            </div>
            <p>
                The concept is positioned around ornamental fish interest, aquarium hobby growth, and a user-facing
                interface that makes browsing and action steps easy to follow.
            </p>
        </article>

        <article class="company-card">
            <div class="company-card-top">
                <span class="company-card-icon tone-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4.5 7.5h15"></path>
                        <path d="M6.5 11.5h11"></path>
                        <path d="M8.5 15.5h7"></path>
                        <circle cx="12" cy="7.5" r="3.5"></circle>
                    </svg>
                </span>
                <h2>Operating Model</h2>
            </div>
            <p>
                The product structure combines digital onboarding, dashboard-based navigation, and clearly separated
                sections so users can move from discovery to action without heavy page complexity.
            </p>
        </article>
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

        <a class="dashboard-footer-item" href="#">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M4.5 8.5h15l-1.5 10h-12z"></path>
                    <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                    <path d="M9.5 12.5h5"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">Shop</span>
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

        <a class="dashboard-footer-item" href="#">
            <span class="dashboard-footer-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3"></circle>
                    <path d="M6.5 18.5c0-3 2.4-5 5.5-5s5.5 2 5.5 5"></path>
                </svg>
            </span>
            <span class="dashboard-footer-label">My</span>
        </a>
    </nav>
</footer>
</body>
</html>

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
    <title>Security</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body security-page-body">
<header class="dashboard-header">
    <div class="dashboard-brand" aria-label="Aqua brand">
        <svg class="dashboard-brand-logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="seaweed-main-security" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#a9e795"></stop>
                    <stop offset="58%" stop-color="#58b77b"></stop>
                    <stop offset="100%" stop-color="#216f54"></stop>
                </linearGradient>
                <linearGradient id="seaweed-deep-security" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b966a"></stop>
                    <stop offset="100%" stop-color="#184b3a"></stop>
                </linearGradient>
                <linearGradient id="seaweed-soft-security" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#b5eea3"></stop>
                    <stop offset="100%" stop-color="#3f9f70"></stop>
                </linearGradient>
            </defs>
            <ellipse cx="32" cy="55" rx="16.5" ry="4.6" fill="#2b694f" opacity="0.2"></ellipse>
            <path d="M13 53C10 44 11 34 15 26C18 19 20 12 18 6" fill="none" stroke="url(#seaweed-deep-security)" stroke-width="4.9" stroke-linecap="round"></path>
            <path d="M19.5 53C17 45 17 36 20.5 28C23.5 21 25 15 24 11" fill="none" stroke="url(#seaweed-main-security)" stroke-width="4.3" stroke-linecap="round"></path>
            <path d="M24.8 53C23 46 24 38 27 31C29.5 25 30.5 21 30 17" fill="none" stroke="url(#seaweed-soft-security)" stroke-width="3.7" stroke-linecap="round"></path>
            <path d="M30.8 54C28 44 29 33 33.5 23C37 15 37.5 9 35.5 4.8" fill="none" stroke="url(#seaweed-main-security)" stroke-width="5.6" stroke-linecap="round"></path>
            <path d="M37.5 53C35.5 44.5 36 35.5 39 27.5C42 20 43.5 14 42.8 10" fill="none" stroke="url(#seaweed-deep-security)" stroke-width="4.4" stroke-linecap="round"></path>
            <path d="M43 53C41.5 47 42 39.5 44.5 33.5C46.8 27.8 48 22.8 47.4 18.4" fill="none" stroke="url(#seaweed-soft-security)" stroke-width="3.5" stroke-linecap="round"></path>
            <path d="M49.2 53C51 44 50 34 46.5 25.5C43.2 17.2 42.5 11 44.4 6.8" fill="none" stroke="url(#seaweed-deep-security)" stroke-width="4.8" stroke-linecap="round"></path>
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

<main class="dashboard-main security-main">
    <section class="security-hero" aria-label="Security introduction">
        <span class="security-kicker">Privacy & Fund Safety</span>
        <h1>Security</h1>
        <p>
            We are committed to protecting personal data, securing account activity, and reducing operational risk
            through layered controls designed around confidentiality, integrity, lawful processing, and financial safety.
        </p>

        <div class="security-pill-row" aria-hidden="true">
            <span class="security-pill">Data minimization</span>
            <span class="security-pill">Controlled access</span>
            <span class="security-pill">Activity monitoring</span>
        </div>
    </section>

    <section class="security-grid" aria-label="Security details">
        <article class="security-card">
            <div class="security-card-top">
                <span class="security-card-icon tone-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3.5l7 3v5.2c0 4.1-2.8 7.8-7 8.8-4.2-1-7-4.7-7-8.8V6.5z"></path>
                        <path d="M9.5 12l1.7 1.7 3.3-3.7"></path>
                    </svg>
                </span>
                <h2>Personal Data Protection</h2>
            </div>

            <p>
                Personal data is handled in line with applicable data protection principles such as lawfulness,
                transparency, purpose limitation, data minimization, accuracy, storage limitation, and confidentiality.
            </p>

            <ul class="security-list">
                <li>Only the information required for account operation, verification, support, and transaction processing is collected.</li>
                <li>Access to personal data is restricted to authorized personnel and limited to legitimate business need.</li>
                <li>Administrative, technical, and procedural safeguards are used to reduce unauthorized access, misuse, loss, or disclosure.</li>
                <li>Retention periods are reviewed so data is not kept longer than necessary for legal, regulatory, or operational purposes.</li>
            </ul>
        </article>

        <article class="security-card">
            <div class="security-card-top">
                <span class="security-card-icon tone-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                        <path d="M8 10V7.5A4 4 0 0 1 12 3.5a4 4 0 0 1 4 4V10"></path>
                        <circle cx="12" cy="15" r="1.1"></circle>
                    </svg>
                </span>
                <h2>Account & Session Security</h2>
            </div>

            <p>
                Account safety depends on both platform-side controls and responsible user behavior. Our approach
                focuses on access control, session integrity, and secure account recovery workflows.
            </p>

            <ul class="security-list">
                <li>Session handling, login protection, and account checks are used to reduce unauthorized access attempts.</li>
                <li>Yousitive actions are reviewed through validation steps designed to prevent misuse and account takeover.</li>
                <li>Internal access is limited by role and monitored to support accountability and traceability.</li>
                <li>Users should also maintain strong passwords, protect their devices, and avoid sharing credentials.</li>
            </ul>
        </article>

        <article class="security-card">
            <div class="security-card-top">
                <span class="security-card-icon tone-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4.5 8.5h15l-1.5 10h-12z"></path>
                        <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                        <path d="M7.5 13.5h9"></path>
                    </svg>
                </span>
                <h2>Fund Safety Controls</h2>
            </div>

            <p>
                Fund protection is supported by layered operational controls intended to reduce fraud risk, processing
                errors, and unauthorized movement of value.
            </p>

            <ul class="security-list">
                <li>Critical payment and withdrawal activity is subject to review rules, verification checks, and transaction monitoring.</li>
                <li>Operational workflows are structured to reduce the risk of unauthorized fund instructions and suspicious activity.</li>
                <li>Records are maintained to support reconciliation, investigation, and dispute handling when needed.</li>
                <li>Risk controls are reviewed over time and updated as platform, legal, or operational requirements evolve.</li>
            </ul>
        </article>

        <article class="security-card">
            <div class="security-card-top">
                <span class="security-card-icon tone-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 18h12"></path>
                        <path d="M8 18v-4"></path>
                        <path d="M12 18v-7"></path>
                        <path d="M16 18v-10"></path>
                        <path d="M5 8.5l4-3 3 2 6-4"></path>
                    </svg>
                </span>
                <h2>Monitoring, Backup & Response</h2>
            </div>

            <p>
                Security is not a one-time setup. It depends on monitoring, review, backup discipline, and incident
                response preparation.
            </p>

            <ul class="security-list">
                <li>Operational events and suspicious patterns may be monitored to support detection and response.</li>
                <li>Backup and recovery procedures are used to improve resilience and continuity.</li>
                <li>System changes, permissions, and higher-risk actions should be reviewed through controlled processes.</li>
                <li>If an incident is identified, containment, review, and corrective action procedures help reduce impact.</li>
            </ul>
        </article>
    </section>

    <section class="security-commitment" aria-label="User rights">
        <h2>User Rights & Transparency</h2>
        <p>
            Subject to applicable law, users may request access to their personal data, ask for correction of inaccurate
            information, and request deletion or restriction where legally available. Some information may need to be
            retained for fraud prevention, financial record-keeping, legal obligations, or dispute resolution.
        </p>
        <p>
            No digital service can promise zero risk. Our goal is to reduce risk with practical safeguards, timely review,
            and continuous improvement while keeping privacy, account safety, and fund protection as core priorities.
        </p>
        <div class="security-note">This page is an overview of our security approach and should be read together with platform rules, account procedures, and applicable legal requirements.</div>
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

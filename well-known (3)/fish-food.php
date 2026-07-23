<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!isset($_SESSION['fish_food_csrf_token'])) {
    $_SESSION['fish_food_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'buy_fish_food') {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $fishKey = trim((string)($_POST['fish_key'] ?? ''));

    if (!hash_equals((string)$_SESSION['fish_food_csrf_token'], $csrfToken)) {
        $errors[] = 'Security dogrulamasi basarisiz oldu. Sayfayi yenileyip tekrar dene.';
    }

    if (!$errors) {
        try {
            $purchase = purchaseFishFoodForUser($pdo, $userId, $fishKey);
            $_SESSION['fish_food_flash'] = [
                'message' => $purchase['fish_name'] . '  received 5 days of fish food.',
                'amount' => number_format((float)$purchase['amount'], 2, '.', ''),
                'remaining_balance' => number_format((float)$purchase['remaining_balance'], 2, '.', ''),
                'bonus_days' => (int)$purchase['bonus_days'],
            ];
            header('Location: fish-food.php');
            exit;
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            $errors[] = 'Fish food could not be purchased. Please try again.';
        }
    }
}

$flash = null;
if (isset($_SESSION['fish_food_flash']) && is_array($_SESSION['fish_food_flash'])) {
    $flash = $_SESSION['fish_food_flash'];
    unset($_SESSION['fish_food_flash']);
}

$fishFoodData = getFishFoodDashboardData($pdo, $userId);
$marketNotificationData = getFishMarketAvailability($pdo, $userId);

$purchaseModalData = [];
foreach ($fishFoodData['cards'] as $card) {
    $purchaseModalData[$card['key']] = [
        'name' => $card['name'],
        'price' => $card['purchase_price'],
        'days' => 5,
        'balance' => number_format((float)$fishFoodData['balance'], 2, '.', ''),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fish Food</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body fish-food-page-body">
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
        <button type="button" class="dashboard-icon-btn" aria-label="Cagri merkezi">
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

<main class="dashboard-main fish-food-main">
    <?php if ($errors): ?>
        <section class="deposit-alert is-error" aria-label="Food error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($flash): ?>
        <section class="deposit-alert is-success is-autohide" aria-label="Food info" id="fish-food-alert">
            <p><?= e((string)$flash['message']) ?></p>
            <p>Amount: <strong>$<?= e((string)$flash['amount']) ?></strong> · Remaining balance: <strong>$<?= e((string)$flash['remaining_balance']) ?></strong></p>
            <p>Total food entitlement: <strong><?= e((string)$flash['bonus_days']) ?> days</strong></p>
        </section>
    <?php endif; ?>

    <section class="fish-food-grid" aria-label="Fish food cards">
        <?php foreach ($fishFoodData['cards'] as $card): ?>
            <article class="fish-food-card <?= $card['is_unlocked'] ? 'is-unlocked' : 'is-locked' ?>">
                <div class="fish-food-card-top">
                    <img src="<?= e($card['image']) ?>" alt="<?= e($card['image_alt']) ?>" class="fish-food-image">

                    <div class="fish-food-card-head">
                        <div class="fish-food-tags" aria-hidden="true">
                            <?php foreach ($card['tags'] as $tag): ?>
                                <span class="fish-food-tag"><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <h2><?= e($card['name']) ?></h2>
                        <span class="fish-food-level is-<?= e($card['level_key']) ?>"><?= e($card['level_label']) ?></span>
                        <span class="fish-food-status <?= $card['is_unlocked'] ? 'is-open' : 'is-locked' ?>">
                            <?= $card['is_unlocked'] ? 'Kart Acik' : 'Kart Locked' ?>
                        </span>
                    </div>
                </div>

                <p class="fish-food-offer">
                    <?= e((string)$card['referral_requirement']) ?> With reference
                    <strong><?= e((string)$card['reward_days']) ?> days of food</strong> earned.
                </p>

                <div class="fish-food-meta">
                    <span class="<?= $card['meets_balance'] ? 'is-ready' : 'is-pending' ?>">
                        Deposit requied : $<?= e($card['unlock_balance']) ?>+
                    </span>
                    <span class="<?= $card['meets_referrals'] ? 'is-ready' : 'is-pending' ?>">
                        Referral requirement: <?= e((string)$card['referral_requirement']) ?> users
                    </span>
                </div>

                <div class="fish-food-bonus-box">
                    <?php if ($card['bonus_days'] > 0): ?>
                        <strong><?= e((string)$card['bonus_days']) ?> days of foodn var.</strong>
                    <?php else: ?>
                        <strong>Gives 5 free days of food on first activation.</strong>
                    <?php endif; ?>
                    <span><?= e($card['unlock_message']) ?></span>
                </div>

                <div class="fish-food-actions">
                    <span class="fish-food-note">
                        <?= $card['is_unlocked']
                            ? 'Card is active. You can add 5 more days of food using your balance.'
                            : 'Meet the requirements or buy to unlock the card and add 5 days of food.' ?>
                    </span>
                    <div class="fish-food-purchase">
                        <span class="fish-food-price">$<?= e((string)$card['purchase_price']) ?> / 5 day</span>
                        <button
                            type="button"
                            class="fish-food-buy-button"
                            data-open-food-modal
                            data-fish-key="<?= e($card['key']) ?>"
                        >
                            Buy Fish Food
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<div class="fish-food-modal" id="fishFoodModal" hidden>
    <div class="fish-food-modal-backdrop" data-close-food-modal></div>
    <div class="fish-food-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="fishFoodModalTitle">
        <button type="button" class="fish-food-modal-close" data-close-food-modal aria-label="Close">×</button>

        <div class="fish-food-modal-content">
            <span class="fish-food-modal-pill">Fish Food buy</span>
            <h2 class="fish-food-modal-title" id="fishFoodModalTitle">Fish Food</h2>

            <div class="fish-food-modal-summary">
                <div class="fish-food-modal-box">
                    <span>Fish</span>
                    <strong id="fishFoodModalFishName">Guppy</strong>
                </div>
                <div class="fish-food-modal-box">
                    <span>Times</span>
                    <strong id="fishFoodModalDays">5 days of food</strong>
                </div>
                <div class="fish-food-modal-box">
                    <span>Amount</span>
                    <strong>$<span id="fishFoodModalPrice">1.00</span></strong>
                </div>
            </div>

            <p class="fish-food-modal-text">Payment is deducted only from the main balance. Bonus balance cannot be used for this purchase.</p>
            <p class="fish-food-modal-balance">Available balance: <strong>$<span id="fishFoodModalBalance"><?= e(number_format((float)$fishFoodData['balance'], 2, '.', '')) ?></span></strong></p>
            <p class="fish-food-modal-error" id="fishFoodModalError" hidden>There is not enough main balance for this food purchase.</p>

            <form method="post" action="fish-food.php" class="fish-food-modal-form" id="fishFoodModalForm">
                <input type="hidden" name="action" value="buy_fish_food">
                <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['fish_food_csrf_token']) ?>">
                <input type="hidden" name="fish_key" id="fishFoodModalFishKey" value="">
                <button type="submit" class="fish-food-modal-submit" id="fishFoodModalSubmit">Confirm</button>
            </form>
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

<script id="fishFoodPurchaseData" type="application/json"><?= json_encode($purchaseModalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script>
    (() => {
        const modal = document.getElementById('fishFoodModal');
        const modalFishName = document.getElementById('fishFoodModalFishName');
        const modalDays = document.getElementById('fishFoodModalDays');
        const modalPrice = document.getElementById('fishFoodModalPrice');
        const modalBalance = document.getElementById('fishFoodModalBalance');
        const modalFishKey = document.getElementById('fishFoodModalFishKey');
        const modalError = document.getElementById('fishFoodModalError');
        const modalSubmit = document.getElementById('fishFoodModalSubmit');
        const modalForm = document.getElementById('fishFoodModalForm');
        const purchaseDataNode = document.getElementById('fishFoodPurchaseData');
        const alertBox = document.getElementById('fish-food-alert');

        if (alertBox) {
            window.setTimeout(() => {
                alertBox.classList.add('is-hidden');
            }, 3200);
        }

        if (!modal || !purchaseDataNode) {
            return;
        }

        const purchaseData = JSON.parse(purchaseDataNode.textContent || '{}');

        const openModal = (fishKey) => {
            const state = purchaseData[fishKey];
            if (!state) {
                return;
            }

            const hasEnoughBalance = Number(state.balance) >= Number(state.price);

            modalFishName.textContent = state.name;
            modalDays.textContent = `${state.days} days of food`;
            modalPrice.textContent = state.price;
            modalBalance.textContent = state.balance;
            modalFishKey.value = fishKey;
            modalSubmit.disabled = !hasEnoughBalance;
            modalError.hidden = hasEnoughBalance;

            modal.hidden = false;
            document.body.classList.add('is-modal-open');
        };

        const closeModal = () => {
            modal.hidden = true;
            document.body.classList.remove('is-modal-open');
        };

        document.querySelectorAll('[data-open-food-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal(button.getAttribute('data-fish-key') || '');
            });
        });

        document.querySelectorAll('[data-close-food-modal]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });

        modalForm.addEventListener('submit', () => {
            modalSubmit.disabled = true;
            modalSubmit.textContent = 'Isleniyor...';
        });
    })();
</script>
</body>
</html>

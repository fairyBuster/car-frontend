<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/admin_auth.php';

requireAdminAuthentication();

$flash = null;
$errors = [];
$csrfToken = getAdminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!verifyAdminCsrfToken($token)) {
        $errors[] = 'Security check failed. Please refresh and try again.';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $amountValue = trim((string)($_POST['amount'] ?? ''));
        $amount = is_numeric(str_replace(',', '.', $amountValue)) ? (float)str_replace(',', '.', $amountValue) : 0.0;

        try {
            if ($action === 'add_balance' || $action === 'subtract_balance') {
                if ($userId <= 0 || $amount <= 0) {
                    throw new RuntimeException('User and amount are required.');
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare('SELECT bonus_balance FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();

                if (!$user) {
                    throw new RuntimeException('User not found.');
                }

                $currentBalance = (float)$user['bonus_balance'];
                $nextBalance = $action === 'add_balance'
                    ? $currentBalance + $amount
                    : max(0, $currentBalance - $amount);

                $updateStmt = $pdo->prepare('UPDATE users SET bonus_balance = :bonus_balance WHERE id = :id');
                $updateStmt->execute([
                    'bonus_balance' => round($nextBalance, 2),
                    'id' => $userId,
                ]);

                $pdo->commit();

                $flash = $action === 'add_balance'
                    ? 'Bonus balance added successfully.'
                    : 'Bonus balance removed successfully.';
            } elseif ($action === 'delete_user') {
                if ($userId <= 0) {
                    throw new RuntimeException('A valid user is required.');
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();

                if (!$user) {
                    throw new RuntimeException('User not found.');
                }

                $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $deleteStmt->execute(['id' => $userId]);

                $pdo->commit();
                $flash = 'User deleted successfully.';
            }
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $throwable instanceof RuntimeException ? $throwable->getMessage() : 'Admin action failed.';
        }
    }
}

// 🔍 ARAMA DESTEĞİ EKLENDİ
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

if ($search !== '') {
    // Arama yapılacaksa tüm tabloyu ara
    $stmt = $pdo->prepare("
        SELECT id, email, invite_code, vip_level, balance, bonus_balance, referral_balance, created_at
        FROM users
        WHERE email LIKE :search OR id LIKE :search OR invite_code LIKE :search
        ORDER BY id DESC
    ");
    $stmt->execute(['search' => "%$search%"]);
    $members = $stmt->fetchAll();
} else {
    // Arama yoksa tüm üyeleri çek (limit yok)
    $memberStmt = $pdo->query("
        SELECT id, email, invite_code, vip_level, balance, bonus_balance, referral_balance, created_at
        FROM users
        ORDER BY id DESC
    ");
    $members = $memberStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member List</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body admin-panel-body">
<header class="dashboard-header admin-header">
    <div class="dashboard-brand" aria-label="Aqua admin brand">
        <span class="dashboard-brand-name">Aqua Admin</span>
    </div>

    <div class="dashboard-actions">
        <a href="admin-panel.php" class="admin-header-logout">Back</a>
        <a href="admin-panel.php?logout=1" class="admin-header-logout">Log Out</a>
    </div>
</header>

<main class="dashboard-main admin-main">

    <!-- 🔍 ARAMA KUTUSU (EKLENDİ) -->
    <form method="GET" class="admin-search-box" style="margin-bottom:15px; display:flex; gap:10px;">
        <input 
            type="text" 
            name="search" 
            placeholder="Search by email, ID or invite code..." 
            value="<?= e(isset($_GET['search']) ? $_GET['search'] : '') ?>"
            style="flex:1; padding:10px; border-radius:8px; border:1px solid #ccc;"
        >
        <button type="submit" style="padding:10px 15px; border:none; border-radius:8px; cursor:pointer; background:linear-gradient(135deg, #8ffbf1, #4eddd2);">
            Search
        </button>
    </form>

    <?php if ($errors): ?>
        <section class="admin-banner is-error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($flash !== null): ?>
        <section class="admin-banner is-success">
            <p><?= e($flash) ?></p>
        </section>
    <?php endif; ?>

    <section class="admin-card admin-members-page-card">
        <div class="admin-card-head">
            <div>
                <h2>Member List</h2>
                <p>User ID, email, balances, and quick detail access.</p>
            </div>
            <span class="admin-count-badge"><?= e((string)count($members)) ?></span>
        </div>

        <div class="admin-member-list">
            <?php if (!$members): ?>
                <p class="admin-empty-copy">No members found.</p>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <?php
                    $memberPayload = [
                        'id' => (int)$member['id'],
                        'email' => (string)$member['email'],
                        'invite_code' => (string)$member['invite_code'],
                        'vip_level' => (int)$member['vip_level'],
                        'balance' => number_format((float)$member['balance'], 2, '.', ''),
                        'bonus_balance' => number_format((float)$member['bonus_balance'], 2, '.', ''),
                        'referral_balance' => number_format((float)$member['referral_balance'], 2, '.', ''),
                        'created_at' => date('d.m.Y H:i', strtotime((string)$member['created_at'])),
                    ];
                    ?>
                    <article class="admin-member-row">
                        <div class="admin-member-copy">
                            <strong>#<?= e((string)$member['id']) ?> - <?= e((string)$member['email']) ?></strong>
                            <span>Main: $<?= e(number_format((float)$member['balance'], 2, '.', ',')) ?> - Bonus: $<?= e(number_format((float)$member['bonus_balance'], 2, '.', ',')) ?></span>
                        </div>
                        <button
                            type="button"
                            class="admin-detail-btn"
                            data-open-admin-user
                            data-user='<?= e(json_encode($memberPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                        >
                            Details
                        </button>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<div class="admin-modal" id="adminUserModal" hidden>
    <div class="admin-modal-backdrop" data-close-admin-user></div>
    <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminUserModalTitle">
        <button type="button" class="admin-modal-close" data-close-admin-user aria-label="Close">&times;</button>

        <div class="admin-modal-head">
            <span class="admin-modal-pill">Member Detail</span>
            <h2 id="adminUserModalTitle">User Detail</h2>
            <p id="adminUserModalMeta"></p>
        </div>

        <div class="admin-modal-grid">
            <article class="admin-modal-info-card">
                <span>Main Balance</span>
                <strong id="adminUserBalance">$0.00</strong>
            </article>
            <article class="admin-modal-info-card">
                <span>Bonus Balance</span>
                <strong id="adminUserBonusBalance">$0.00</strong>
            </article>
            <article class="admin-modal-info-card">
                <span>Referral Balance</span>
                <strong id="adminUserReferralBalance">$0.00</strong>
            </article>
            <article class="admin-modal-info-card">
                <span>Invite Code</span>
                <strong id="adminUserInviteCode">-</strong>
            </article>
        </div>

        <div class="admin-modal-actions">
            <form method="post" action="uyelist.php" class="admin-action-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add_balance">
                <input type="hidden" name="user_id" id="adminAddUserId" value="">
                <label class="admin-action-field">
                    <span>Add Bonus Balance</span>
                    <input type="number" name="amount" min="0.01" step="0.01" placeholder="25.00" required>
                </label>
                <button type="submit" class="admin-action-btn is-positive">Add</button>
            </form>

            <form method="post" action="uyelist.php" class="admin-action-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="subtract_balance">
                <input type="hidden" name="user_id" id="adminSubtractUserId" value="">
                <label class="admin-action-field">
                    <span>Subtract Bonus Balance</span>
                    <input type="number" name="amount" min="0.01" step="0.01" placeholder="10.00" required>
                </label>
                <button type="submit" class="admin-action-btn is-warning">Subtract</button>
            </form>

            <form method="post" action="uyelist.php" class="admin-action-form is-danger" onsubmit="return confirm('Delete this user permanently?');">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="adminDeleteUserId" value="">
                <button type="submit" class="admin-action-btn is-danger">Delete User</button>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('adminUserModal');
    if (!modal) {
        return;
    }

    const title = document.getElementById('adminUserModalTitle');
    const meta = document.getElementById('adminUserModalMeta');
    const balance = document.getElementById('adminUserBalance');
    const bonusBalance = document.getElementById('adminUserBonusBalance');
    const referralBalance = document.getElementById('adminUserReferralBalance');
    const inviteCode = document.getElementById('adminUserInviteCode');
    const addUserId = document.getElementById('adminAddUserId');
    const subtractUserId = document.getElementById('adminSubtractUserId');
    const deleteUserId = document.getElementById('adminDeleteUserId');

    function openModal(payload) {
        title.textContent = `#${payload.id} - ${payload.email}`;
        meta.textContent = `Joined ${payload.created_at} - VIP ${payload.vip_level}`;
        balance.textContent = `$${payload.balance}`;
        bonusBalance.textContent = `$${payload.bonus_balance}`;
        referralBalance.textContent = `$${payload.referral_balance}`;
        inviteCode.textContent = payload.invite_code || '-';
        addUserId.value = String(payload.id);
        subtractUserId.value = String(payload.id);
        deleteUserId.value = String(payload.id);
        modal.hidden = false;
        document.body.classList.add('is-modal-open');
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('is-modal-open');
    }

    document.querySelectorAll('[data-open-admin-user]').forEach((button) => {
        button.addEventListener('click', () => {
            const raw = button.getAttribute('data-user');
            if (!raw) {
                return;
            }

            try {
                openModal(JSON.parse(raw));
            } catch (error) {
                // ignore malformed payloads
            }
        });
    });

    modal.querySelectorAll('[data-close-admin-user]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
</body>
</html>

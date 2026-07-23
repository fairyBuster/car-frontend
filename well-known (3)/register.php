<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fish_food.php';

function generateCaptchaCode(int $length = 4): string
{
    $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $max = strlen($alphabet) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }

    return $code;
}

function generateInviteCode(int $length = 7): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }

    return $code;
}

function generateUniqueInviteCode(PDO $pdo): string
{
    $attempts = 0;
    while ($attempts < 40) {
        $attempts++;
        $candidate = generateInviteCode(7);

        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE invite_code = :invite_code LIMIT 1');
        $checkStmt->execute(['invite_code' => $candidate]);

        if (!$checkStmt->fetch()) {
            return $candidate;
        }
    }

    throw new RuntimeException('Could not generate unique invite code.');
}

function ensureUserRegistrationSchema(PDO $pdo): void
{
    if (!schemaColumnExists($pdo, 'users', 'invited_by_user_id')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN invited_by_user_id BIGINT UNSIGNED NULL
             AFTER invite_code'
        );
    }

    if (!schemaColumnExists($pdo, 'users', 'vip_level')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN vip_level TINYINT UNSIGNED NOT NULL DEFAULT 0
             AFTER invited_by_user_id'
        );
    }

    if (!schemaColumnExists($pdo, 'users', 'balance')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00
             AFTER vip_level'
        );
    }

    if (!schemaColumnExists($pdo, 'users', 'bonus_balance')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN bonus_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00
             AFTER balance'
        );
    }

    if (!schemaColumnExists($pdo, 'users', 'referral_balance')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN referral_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00
             AFTER bonus_balance'
        );
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['captcha_code']) || !is_string($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = generateCaptchaCode();
}

$errors = [];
$email = '';
$prefilledInviteCode = strtoupper(trim((string)($_GET['invite'] ?? '')));

if ($prefilledInviteCode !== '' && !preg_match('/^[A-Z0-9]{7}$/', $prefilledInviteCode)) {
    $prefilledInviteCode = '';
}

$inviteCode = $prefilledInviteCode;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordRepeat = (string)($_POST['password_repeat'] ?? '');
    $postedInviteCode = strtoupper(trim((string)($_POST['invite_code'] ?? '')));
    $postedPrefilledInviteCode = strtoupper(trim((string)($_POST['invite_code_prefill'] ?? '')));
    $inviteCode = $postedInviteCode !== '' ? $postedInviteCode : $postedPrefilledInviteCode;
    $verificationCode = strtoupper(trim((string)($_POST['verification_code'] ?? '')));
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $inviterUserId = null;

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Security check failed. Please refresh and try again.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $passwordRepeat) {
        $errors[] = 'Passwords do not match.';
    }

    if ($inviteCode === '') {
        $errors[] = 'Invite code is required.';
    } elseif (!preg_match('/^[A-Z0-9]{7}$/', $inviteCode)) {
        $errors[] = 'Invite code must be 7 characters (letters and numbers).';
    }

    if ($verificationCode === '') {
        $errors[] = 'Verification code is required.';
    } elseif (!hash_equals((string)$_SESSION['captcha_code'], $verificationCode)) {
        $errors[] = 'Verification code is incorrect.';
    }

    if (!$errors) {
        try {
            $inviterStmt = $pdo->prepare('SELECT id FROM users WHERE invite_code = :invite_code LIMIT 1');
            $inviterStmt->execute(['invite_code' => $inviteCode]);
            $inviter = $inviterStmt->fetch();

            if (!$inviter) {
                $errors[] = 'Invite code not found. Please check and try again.';
            } else {
                $inviterUserId = (int)$inviter['id'];
            }

            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch()) {
                $errors[] = 'This email is already registered.';
            } elseif ($inviterUserId !== null) {
                ensureUserRegistrationSchema($pdo);
                ensureFishFoodSchema($pdo);
                ensureFishCatchSchema($pdo);
                $newInviteCode = generateUniqueInviteCode($pdo);
                $pdo->beginTransaction();

                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, invite_code, invited_by_user_id, vip_level, bonus_balance)
                     VALUES (:email, :password_hash, :invite_code, :invited_by_user_id, :vip_level, :bonus_balance)'
                );

                $insertStmt->execute([
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'invite_code' => $newInviteCode,
                    'invited_by_user_id' => $inviterUserId,
                    'vip_level' => 0,
                    'bonus_balance' => 5.00,
                ]);

                $newUserId = (int)$pdo->lastInsertId();
                seedUserFishFoodAccess($pdo, $newUserId);
                seedUserFishCatchStates($pdo, $newUserId);
                $pdo->commit();

                unset(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    $_SESSION['vip_level'],
                    $_SESSION['user_invite_code']
                );
                $_SESSION['registration_success'] = 'Registration successful. You can sign in now. Your invite code: ' . $newInviteCode;
                $_SESSION['captcha_code'] = generateCaptchaCode();
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[register] ' . $exception->getMessage());
            $errors[] = 'Registration failed. Please check database setup and try again.';
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Could not generate invite code. Please try again.';
        }
    }

    $_SESSION['captcha_code'] = generateCaptchaCode();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aquarium Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="register-shell">
    <header class="register-head">
        <h1>Sign Up</h1>
    </header>

    <section class="logo-area" aria-label="Brand logo area">
        <img src="logo.png" alt="Site logo" class="logo-image">
    </section>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php<?= $prefilledInviteCode !== '' ? '?invite=' . urlencode($prefilledInviteCode) : '' ?>" class="register-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="invite_code_prefill" value="<?= e($prefilledInviteCode) ?>">

        <div class="form-field">
            <input
                id="email"
                name="email"
                type="email"
                placeholder="Email"
                value="<?= e($email) ?>"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-field password-wrap">
            <input
                id="password"
                name="password"
                type="password"
                placeholder="Password"
                autocomplete="new-password"
                minlength="8"
                required
            >
            <button type="button" class="toggle-password" data-target="password" aria-label="Show or hide password">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                    <circle cx="12" cy="12" r="3.25"></circle>
                    <path class="eye-slash" d="M4 4l16 16"></path>
                </svg>
            </button>
        </div>

        <div class="form-field password-wrap">
            <input
                id="password_repeat"
                name="password_repeat"
                type="password"
                placeholder="Repeat password"
                autocomplete="new-password"
                minlength="8"
                required
            >
            <button type="button" class="toggle-password" data-target="password_repeat" aria-label="Show or hide repeat password">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                    <circle cx="12" cy="12" r="3.25"></circle>
                    <path class="eye-slash" d="M4 4l16 16"></path>
                </svg>
            </button>
        </div>

        <div class="form-field">
            <input
                id="invite_code"
                name="invite_code"
                type="text"
                placeholder="Invite code"
                value="<?= e($inviteCode) ?>"
                autocomplete="off"
                maxlength="7"
                required
            >
        </div>

        <div class="form-field captcha-field">
            <input
                id="verification_code"
                name="verification_code"
                type="text"
                placeholder="Verification code"
                autocomplete="off"
                maxlength="4"
                required
            >
            <img
                src="captcha.php?v=<?= time() ?>"
                alt="Verification code"
                class="captcha-image"
                id="captcha-image"
                title="Click to refresh"
            >
        </div>

        <button class="submit-button" type="submit">Sign Up</button>
    </form>

    <footer class="register-foot">
        <p>Already have an account?</p>
        <a href="login.php">Sign In</a>
    </footer>
</main>

<script>
    document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.dataset.target);
            if (!target) {
                return;
            }
            var isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            button.classList.toggle('is-visible', isPassword);
        });
    });

    var captchaImage = document.getElementById('captcha-image');
    if (captchaImage) {
        captchaImage.addEventListener('click', function () {
            captchaImage.src = 'captcha.php?v=' + Date.now();
        });
    }
</script>
</body>
</html>

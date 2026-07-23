<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$successMessage = '';
$email = '';

if (isset($_SESSION['registration_success']) && is_string($_SESSION['registration_success'])) {
    $successMessage = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $verificationCode = strtoupper(trim((string)($_POST['verification_code'] ?? '')));
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Security check failed. Please refresh and try again.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if ($verificationCode === '') {
        $errors[] = 'Verification code is required.';
    } elseif (!isset($_SESSION['captcha_code']) || !hash_equals((string)$_SESSION['captcha_code'], $verificationCode)) {
        $errors[] = 'Verification code is incorrect.';
    }

    if (!$errors) {
        try {
            $userStmt = $pdo->prepare(
                'SELECT id, email, password_hash, vip_level, invite_code FROM users WHERE email = :email LIMIT 1'
            );
            $userStmt->execute(['email' => $email]);
            $user = $userStmt->fetch();

            if (!$user || !password_verify($password, (string)$user['password_hash'])) {
                $errors[] = 'Email or password is incorrect.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_email'] = (string)$user['email'];
                $_SESSION['vip_level'] = (int)$user['vip_level'];
                $_SESSION['user_invite_code'] = (string)$user['invite_code'];
                unset($_SESSION['captcha_code']);

                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $exception) {
            $errors[] = 'Sign in failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="register-shell">
    <header class="register-head">
        <h1>Sign In</h1>
    </header>

    <section class="logo-area" aria-label="Brand logo area">
        <img src="logo.png" alt="Site logo" class="logo-image">
    </section>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php" class="register-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

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
                autocomplete="current-password"
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

        <button class="submit-button" type="submit">Sign In</button>
    </form>

    <footer class="register-foot">
        <p>No account yet?</p>
        <a href="register.php">Sign Up</a>
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

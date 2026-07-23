<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;

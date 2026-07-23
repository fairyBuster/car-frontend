<?php
declare(strict_types=1);

require_once __DIR__ . '/schema_helpers.php';

function ensureAppSettingsSchema(PDO $pdo): void
{
    if (!schemaTableExists($pdo, 'app_settings')) {
        $pdo->exec(
            'CREATE TABLE app_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_app_settings_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    $defaultHelpTelegramUrl = 'https://t.me/aquavestsupport';

    $seedStmt = $pdo->prepare(
        'INSERT IGNORE INTO app_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)'
    );

    $seedStmt->execute([
        'setting_key' => 'help_telegram_url',
        'setting_value' => $defaultHelpTelegramUrl,
    ]);

    $pdo->prepare(
        'UPDATE app_settings
         SET setting_value = :setting_value
         WHERE setting_key = :setting_key
           AND (setting_value IS NULL OR setting_value = "" OR setting_value = :legacy_value)'
    )->execute([
        'setting_value' => $defaultHelpTelegramUrl,
        'setting_key' => 'help_telegram_url',
        'legacy_value' => 'https://t.me/aquavestsupport',
    ]);
}

function getAppSetting(PDO $pdo, string $key, ?string $default = null): ?string
{
    ensureAppSettingsSchema($pdo);

    $stmt = $pdo->prepare(
        'SELECT setting_value
         FROM app_settings
         WHERE setting_key = :setting_key
         LIMIT 1'
    );
    $stmt->execute(['setting_key' => $key]);
    $value = $stmt->fetchColumn();

    if ($value === false || $value === null || trim((string)$value) === '') {
        return $default;
    }

    return (string)$value;
}

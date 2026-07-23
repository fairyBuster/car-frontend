-- Import this file into the gravuahe_fish database on your hosting panel.
-- Database: gravuahe_fish

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    invite_code CHAR(7) NOT NULL UNIQUE,
    invited_by_user_id BIGINT UNSIGNED NULL,
    vip_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
    aquarium_size TINYINT UNSIGNED NOT NULL DEFAULT 1,
    balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    bonus_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    referral_balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_invited_by_user_id (invited_by_user_id),
    CONSTRAINT fk_users_invited_by_user_id
        FOREIGN KEY (invited_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referral_reward_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    beneficiary_user_id BIGINT UNSIGNED NOT NULL,
    source_user_id BIGINT UNSIGNED NOT NULL,
    source_type VARCHAR(32) NOT NULL,
    source_ref VARCHAR(100) NOT NULL,
    base_amount DECIMAL(12, 2) NOT NULL,
    reward_rate DECIMAL(5, 2) NOT NULL,
    reward_amount DECIMAL(12, 2) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_referral_reward_source (beneficiary_user_id, source_type, source_ref),
    KEY idx_referral_reward_beneficiary (beneficiary_user_id),
    CONSTRAINT fk_referral_reward_beneficiary
        FOREIGN KEY (beneficiary_user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_referral_reward_source_user
        FOREIGN KEY (source_user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_fish_foods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    fish_key VARCHAR(50) NOT NULL,
    is_unlocked TINYINT(1) NOT NULL DEFAULT 0,
    unlock_source VARCHAR(32) NOT NULL DEFAULT 'rule',
    bonus_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    claimed_open_bonus TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_fish_food (user_id, fish_key),
    CONSTRAINT fk_user_fish_foods_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_fish_catch_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    fish_key VARCHAR(50) NOT NULL,
    last_catch_at DATETIME NULL,
    total_catches INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_fish_catch_state (user_id, fish_key),
    CONSTRAINT fk_user_fish_catch_states_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deposit_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_no VARCHAR(32) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_label VARCHAR(100) NOT NULL,
    network_key VARCHAR(20) NULL,
    network_label VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    note VARCHAR(255) NULL,
    gateway_reference VARCHAR(120) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_deposit_order_no (order_no),
    KEY idx_deposit_orders_user (user_id),
    CONSTRAINT fk_deposit_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deposit_wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    network_key VARCHAR(20) NOT NULL,
    network_label VARCHAR(50) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    qr_payload VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_deposit_wallet_network (network_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    request_no VARCHAR(32) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_withdrawal_request_no (request_no),
    KEY idx_withdrawal_requests_user (user_id),
    CONSTRAINT fk_withdrawal_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_app_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS market_feed_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    seller_label VARCHAR(80) NOT NULL,
    fish_key VARCHAR(50) NOT NULL,
    fish_name VARCHAR(100) NOT NULL,
    status_label VARCHAR(40) NOT NULL,
    note VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_market_feed_created (created_at),
    KEY idx_market_feed_user (user_id),
    CONSTRAINT fk_market_feed_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

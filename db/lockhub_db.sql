-- LockHub database schema.
--
-- You normally don't need to import this: LockHub creates the database and
-- tables automatically the first time it connects (see db_conn.php).
-- It's here for reference, or if you prefer to set things up in phpMyAdmin.

CREATE DATABASE IF NOT EXISTS lockhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lockhub_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,              -- bcrypt hash of the master password
    name VARCHAR(100) NOT NULL,
    vault_salt VARCHAR(64) NULL,                 -- salt for deriving the vault key (PBKDF2)
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_user_name (user_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS passwords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    website VARCHAR(255) NOT NULL,
    url VARCHAR(500) NULL,
    username VARCHAR(255) NOT NULL,
    password TEXT NOT NULL,                      -- AES-256-GCM ciphertext ("v2:" prefix)
    notes TEXT NULL,                             -- AES-256-GCM ciphertext
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_password (user_id, website(150), username(150)),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    old_password_hash VARCHAR(255) NOT NULL,
    change_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(255) NOT NULL,
    action_description TEXT,
    ip_address VARCHAR(45) NULL,
    action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lookup (user_name, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lh_meta (
    k VARCHAR(50) PRIMARY KEY,
    v VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

REPLACE INTO lh_meta (k, v) VALUES ('schema_version', '2');

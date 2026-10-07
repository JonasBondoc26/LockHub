<?php
// Opens the MySQL connection and makes sure the database and tables exist,
// so LockHub works on a fresh XAMPP install without importing anything.

const LOCKHUB_SCHEMA_VERSION = 2;

function lh_db_connect(array $config): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], '', $config['db_port']);
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '" . date('P') . "'");

    $db = $config['db_name'];
    try {
        $conn->select_db($db);
    } catch (mysqli_sql_exception $e) {
        $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db($db);
    }

    lh_ensure_schema($conn);
    return $conn;
}

function lh_ensure_schema(mysqli $conn): void
{
    try {
        $row = $conn->query("SELECT v FROM lh_meta WHERE k = 'schema_version'")->fetch_assoc();
        if ($row && (int) $row['v'] >= LOCKHUB_SCHEMA_VERSION) {
            return;
        }
    } catch (mysqli_sql_exception $e) {
        // lh_meta doesn't exist yet: fresh install or the original schema.
    }

    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(30) NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        vault_salt VARCHAR(64) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uniq_user_name (user_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS passwords (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        website VARCHAR(255) NOT NULL,
        url VARCHAR(500) NULL,
        username VARCHAR(255) NOT NULL,
        password TEXT NOT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_password (user_id, website(150), username(150)),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS password_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        old_password_hash VARCHAR(255) NOT NULL,
        change_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_type VARCHAR(255) NOT NULL,
        action_description TEXT,
        ip_address VARCHAR(45) NULL,
        action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_lookup (user_name, ip_address, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS lh_meta (
        k VARCHAR(50) PRIMARY KEY,
        v VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Upgrade databases created from the original db/test_db.sql.
    $add_columns = [
        'users'      => ['vault_salt'    => 'VARCHAR(64) NULL',
                         'created_at'    => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                         'last_login_at' => 'TIMESTAMP NULL DEFAULT NULL'],
        'passwords'  => ['url'           => 'VARCHAR(500) NULL',
                         'notes'         => 'TEXT NULL',
                         'created_at'    => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                         'updated_at'    => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
        'audit_logs' => ['ip_address'    => 'VARCHAR(45) NULL'],
    ];
    foreach ($add_columns as $table => $columns) {
        foreach ($columns as $column => $definition) {
            if ($conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->num_rows === 0) {
                $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        }
    }
    $conn->query("ALTER TABLE passwords MODIFY password TEXT NOT NULL");
    if ($conn->query("SHOW INDEX FROM users WHERE Key_name = 'uniq_user_name'")->num_rows === 0) {
        try {
            $conn->query("ALTER TABLE users ADD UNIQUE KEY uniq_user_name (user_name)");
        } catch (mysqli_sql_exception $e) {
            // Duplicate usernames already exist; leave the table as it is.
        }
    }

    $conn->query("REPLACE INTO lh_meta (k, v) VALUES ('schema_version', '" . LOCKHUB_SCHEMA_VERSION . "')");
}

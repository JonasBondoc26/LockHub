<?php
// LockHub configuration.
// Values can be overridden with environment variables (useful for testing),
// or, on a web host, by creating includes/config.local.php
// (see config.local.example.php).

$config = [
    // Show database error details on the "Can't reach the database" page.
    'debug' => true,

    'db_host' => getenv('LOCKHUB_DB_HOST') ?: 'localhost',
    'db_port' => (int) (getenv('LOCKHUB_DB_PORT') ?: 3306),
    'db_user' => getenv('LOCKHUB_DB_USER') ?: 'root',
    'db_pass' => getenv('LOCKHUB_DB_PASS') ?: '',
    'db_name' => getenv('LOCKHUB_DB_NAME') ?: 'lockhub_db',

    'timezone' => 'Asia/Manila',

    // Log users out after this many seconds without activity.
    'idle_timeout' => 15 * 60,

    // Failed logins allowed per username + IP before a temporary lockout.
    'max_login_attempts' => 5,
    'lockout_minutes'    => 15,

    // Key used by the original version of LockHub. Only needed to read
    // entries saved before vault keys were derived from the master password.
    'legacy_encryption_key' => '6f3e7c8a2e3d4b6f9f0a1d3c2b7e8f6a6d9c3e2a1b4c5d7e8f9a0b1c2d3e4f5',
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;

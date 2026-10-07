<?php
// Loaded at the top of every page: config, session, database and helpers.

$LH_CONFIG = require __DIR__ . '/config.php';
date_default_timezone_set($LH_CONFIG['timezone']);

require_once __DIR__ . '/../db_conn.php';
require_once __DIR__ . '/layout.php';

session_name('LOCKHUB');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                  || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Sign out after a period of inactivity.
if (isset($_SESSION['id'])) {
    $last = $_SESSION['last_activity'] ?? time();
    if (time() - $last > $LH_CONFIG['idle_timeout']) {
        lh_end_session();
        session_start();
        session_regenerate_id(true);
        flash('info', 'You were signed out after ' . intdiv($LH_CONFIG['idle_timeout'], 60) . ' minutes of inactivity.');
        redirect('login.php');
    }
    $_SESSION['last_activity'] = time();
}

/* ---------- General helpers ---------- */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit();
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = array_slice($_SESSION['flash'] ?? [], -3);
    unset($_SESSION['flash']);
    return $messages;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------- Database ---------- */

function db(): mysqli
{
    static $conn = null;
    global $LH_CONFIG;

    if ($conn === null) {
        try {
            $conn = lh_db_connect($LH_CONFIG);
        } catch (mysqli_sql_exception $e) {
            http_response_code(503);
            render_db_error($LH_CONFIG['debug'] ? $e->getMessage() : '');
            exit();
        }
    }
    return $conn;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// Rejects a POST whose form token doesn't match, then sends the user back.
function require_csrf(string $back): void
{
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        flash('error', 'Your session expired. Please try again.');
        redirect($back);
    }
}

/* ---------- Authentication ---------- */

function is_logged_in(): bool
{
    return isset($_SESSION['id'], $_SESSION['vault_key']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('info', 'Please log in to open your vault.');
        redirect('login.php');
    }
}

function vault_key(): string
{
    return base64_decode($_SESSION['vault_key']);
}

function derive_vault_key(string $master_password, string $salt_hex): string
{
    return hash_pbkdf2('sha256', $master_password, hex2bin($salt_hex), 150000, 32, true);
}

function start_user_session(array $user, string $vault_key): void
{
    session_regenerate_id(true);
    $_SESSION['id']            = (int) $user['id'];
    $_SESSION['user_name']     = $user['user_name'];
    $_SESSION['name']          = $user['name'];
    $_SESSION['vault_key']     = base64_encode($vault_key);
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf']);
}

function lh_end_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Encryption ---------- */

// Entries are encrypted with AES-256-GCM using a key derived from the
// user's master password, so the database alone can't reveal them.
function lh_encrypt(string $plain, string $key): string
{
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return 'v2:' . base64_encode($iv . $tag . $cipher);
}

function lh_decrypt(string $stored, string $key): ?string
{
    if (strncmp($stored, 'v2:', 3) === 0) {
        $data = base64_decode(substr($stored, 3), true);
        if ($data === false || strlen($data) < 28) {
            return null;
        }
        $plain = openssl_decrypt(substr($data, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA,
            substr($data, 0, 12), substr($data, 12, 16));
        return $plain === false ? null : $plain;
    }

    // Format used by the original LockHub (shared server key, AES-256-CBC).
    global $LH_CONFIG;
    $data = base64_decode($stored, true);
    if ($data === false || strlen($data) <= 16) {
        return null;
    }
    $plain = openssl_decrypt(substr($data, 16), 'AES-256-CBC', $LH_CONFIG['legacy_encryption_key'], 0, substr($data, 0, 16));
    return $plain === false ? null : $plain;
}

// Re-encrypts entries saved in the old format with the user's own key.
function upgrade_legacy_entries(mysqli $conn, int $user_id, string $key): void
{
    $stmt = $conn->prepare("SELECT id, password FROM passwords WHERE user_id = ? AND password NOT LIKE 'v2:%'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $update = $conn->prepare('UPDATE passwords SET password = ?, updated_at = updated_at WHERE id = ?');
    foreach ($rows as $row) {
        $plain = lh_decrypt($row['password'], $key);
        if ($plain !== null) {
            $encrypted = lh_encrypt($plain, $key);
            $update->bind_param('si', $encrypted, $row['id']);
            $update->execute();
        }
    }
}

/* ---------- Password strength (mirrors scoreStrength() in js/lockhub.js) ---------- */

const LH_STRENGTH_LABELS = ['Very weak', 'Weak', 'Fair', 'Strong', 'Very strong'];

function password_strength(string $p): int
{
    $common = ['password', '123456', '12345678', '123456789', 'qwerty', '111111', 'iloveyou', 'admin',
               'welcome', 'letmein', 'abc123', 'password1', 'qwerty123', 'monkey', 'dragon', 'lockhub'];
    $len = mb_strlen($p);
    if ($len === 0 || in_array(strtolower($p), $common, true) || preg_match('/^(.)\1*$/u', $p)) {
        return 0;
    }

    $classes = preg_match('/[a-z]/', $p) + preg_match('/[A-Z]/', $p)
             + preg_match('/\d/', $p) + preg_match('/[^a-zA-Z\d]/', $p);

    $score = 0;
    if ($len >= 8)  $score++;
    if ($len >= 12) $score++;
    if ($len >= 16) $score++;
    if ($classes >= 3) $score++;
    if ($classes <= 1 && $len < 20) $score = min($score, 1);

    return min($score, 4);
}

/* ---------- Activity log ---------- */

function audit(mysqli $conn, int $user_id, string $type, string $description = ''): void
{
    $ip = client_ip();
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $user_id, $type, $description, $ip);
    $stmt->execute();
}

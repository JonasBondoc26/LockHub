<?php
// Small JSON endpoint used by the vault page to fetch a single password on demand,
// so decrypted passwords never sit in the page's HTML.
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit();
}

if (!is_post()) {
    respond(405, ['error' => 'Use POST.']);
}
if (!is_logged_in()) {
    respond(401, ['error' => 'Your session ended. Please log in again.']);
}
if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    respond(403, ['error' => 'Your session expired. Reload the page and try again.']);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (($input['action'] ?? '') === 'reveal') {
    $id   = (int) ($input['id'] ?? 0);
    $uid  = (int) $_SESSION['id'];
    $stmt = db()->prepare('SELECT password FROM passwords WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        respond(404, ['error' => 'Entry not found.']);
    }
    $plain = lh_decrypt($row['password'], vault_key());
    if ($plain === null) {
        respond(500, ['error' => "This entry couldn't be decrypted."]);
    }
    respond(200, ['password' => $plain]);
}

respond(400, ['error' => 'Unknown action.']);

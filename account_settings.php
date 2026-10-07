<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$conn = db();
$uid  = (int) $_SESSION['id'];

function load_user(mysqli $conn, int $uid): array
{
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$user = load_user($conn, $uid);
$errors = [];

if (is_post()) {
    require_csrf('account_settings.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Enter your name (up to 100 characters).';
        } else {
            $stmt = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $uid);
            $stmt->execute();
            $_SESSION['name'] = $name;
            audit($conn, $uid, 'Profile updated', 'Changed display name.');
            flash('success', 'Your profile was updated.');
            redirect('account_settings.php');
        }
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors['current_password'] = 'Your current master password is incorrect.';
        } elseif (mb_strlen($new) < 8) {
            $errors['new_password'] = 'Use at least 8 characters.';
        } elseif (password_strength($new) < 2) {
            $errors['new_password'] = 'That password is too easy to guess.';
        } elseif ($new === $current) {
            $errors['new_password'] = 'Choose a password that is different from your current one.';
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = "The passwords don't match.";
        } else {
            $stmt = $conn->prepare('SELECT old_password_hash FROM password_history WHERE user_id = ?');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $old) {
                if (password_verify($new, $old['old_password_hash'])) {
                    $errors['new_password'] = "You've used this password before. Pick a new one.";
                    break;
                }
            }
        }

        if (!$errors) {
            // Re-encrypt every entry with a key derived from the new master password.
            $old_key  = vault_key();
            $new_salt = bin2hex(random_bytes(16));
            $new_key  = derive_vault_key($new, $new_salt);

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare('SELECT id, password, notes FROM passwords WHERE user_id = ?');
                $stmt->bind_param('i', $uid);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $update = $conn->prepare('UPDATE passwords SET password = ?, notes = ?, updated_at = updated_at WHERE id = ?');
                foreach ($rows as $row) {
                    $pw = lh_decrypt($row['password'], $old_key);
                    if ($pw === null) {
                        continue;
                    }
                    $notes = $row['notes'] === null ? null : lh_decrypt($row['notes'], $old_key);
                    $enc_pw = lh_encrypt($pw, $new_key);
                    $enc_notes = $notes === null ? null : lh_encrypt($notes, $new_key);
                    $update->bind_param('ssi', $enc_pw, $enc_notes, $row['id']);
                    $update->execute();
                }

                $stmt = $conn->prepare('INSERT INTO password_history (user_id, old_password_hash) VALUES (?, ?)');
                $stmt->bind_param('is', $uid, $user['password']);
                $stmt->execute();

                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET password = ?, vault_salt = ? WHERE id = ?');
                $stmt->bind_param('ssi', $hash, $new_salt, $uid);
                $stmt->execute();

                audit($conn, $uid, 'Master password changed', 'Vault re-encrypted with the new key.');
                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }

            $_SESSION['vault_key'] = base64_encode($new_key);
            session_regenerate_id(true);
            flash('success', 'Master password changed. Your vault was re-encrypted with the new key.');
            redirect('account_settings.php');
        }
    }

    if ($action === 'delete') {
        if (!password_verify($_POST['confirm_password'] ?? '', $user['password'])) {
            $errors['delete'] = 'Incorrect master password. Your account was not deleted.';
        } else {
            $conn->begin_transaction();
            foreach (['passwords', 'password_history', 'audit_logs'] as $table) {
                $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
                $stmt->bind_param('i', $uid);
                $stmt->execute();
            }
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $conn->commit();

            lh_end_session();
            session_start();
            session_regenerate_id(true);
            flash('success', 'Your account and all of its data were deleted.');
            redirect('index.php');
        }
    }
}

$stmt = $conn->prepare('SELECT COUNT(*) AS n FROM passwords WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$entry_count = (int) $stmt->get_result()->fetch_assoc()['n'];

$stmt = $conn->prepare('SELECT action_type, action_description, ip_address, action_timestamp
                        FROM audit_logs WHERE user_id = ? ORDER BY log_id DESC LIMIT 25');
$stmt->bind_param('i', $uid);
$stmt->execute();
$activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$activity_icons = [
    'Logged in' => 'fa-sign-in', 'Logged out' => 'fa-sign-out', 'Failed login' => 'fa-exclamation-triangle',
    'Account created' => 'fa-user-plus', 'Entry added' => 'fa-plus', 'Entry updated' => 'fa-pencil',
    'Entry deleted' => 'fa-trash-o', 'Master password changed' => 'fa-key', 'Profile updated' => 'fa-user',
];

function err(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<small class="field-error">' . e($errors[$field]) . '</small>' : '';
}

page_start('Settings', ['variant' => 'app', 'active' => 'settings']);
?>
<div class="container app-main">
  <div class="page-head">
    <div>
      <h1>Account settings</h1>
      <p class="muted">Manage your profile, master password and account activity.</p>
    </div>
  </div>

  <div class="settings-grid">
    <div class="settings-col">

      <section class="panel card">
        <h2><i class="fa fa-user"></i> Profile</h2>
        <dl class="facts">
          <div><dt>Username</dt><dd><?= e($user['user_name']) ?></dd></div>
          <div><dt>Member since</dt><dd><?= date('F j, Y', strtotime($user['created_at'])) ?></dd></div>
          <div><dt>Saved logins</dt><dd><?= $entry_count ?></dd></div>
        </dl>
        <form method="post" class="form-stack">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
          <label class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
            <span>Display name</span>
            <input type="text" name="name" value="<?= e(isset($errors['name']) ? ($_POST['name'] ?? '') : $user['name']) ?>" maxlength="100" required>
            <?= err($errors, 'name') ?>
          </label>
          <div><button class="btn btn-primary" type="submit">Save profile</button></div>
        </form>
      </section>

      <section class="panel card" id="password">
        <h2><i class="fa fa-key"></i> Change master password</h2>
        <p class="muted">Your vault will be re-encrypted with a key derived from the new password. You can't reuse an old one.</p>
        <form method="post" class="form-stack">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <label class="field<?= isset($errors['current_password']) ? ' has-error' : '' ?>">
            <span>Current master password</span>
            <input type="password" name="current_password" autocomplete="current-password" required>
            <?= err($errors, 'current_password') ?>
          </label>
          <label class="field<?= isset($errors['new_password']) ? ' has-error' : '' ?>">
            <span>New master password</span>
            <span class="input-group">
              <input type="password" name="new_password" autocomplete="new-password" required data-strength-input>
              <button type="button" class="icon-btn" data-toggle-visibility aria-label="Show password"><i class="fa fa-eye"></i></button>
            </span>
            <span class="strength" data-strength-meter><span class="strength-bar"><i></i></span><span class="strength-label"></span></span>
            <?= err($errors, 'new_password') ?>
          </label>
          <label class="field<?= isset($errors['confirm_password']) ? ' has-error' : '' ?>">
            <span>Confirm new master password</span>
            <input type="password" name="confirm_password" autocomplete="new-password" required>
            <?= err($errors, 'confirm_password') ?>
          </label>
          <div><button class="btn btn-primary" type="submit">Update master password</button></div>
        </form>
      </section>

      <section class="panel card card-danger" id="delete">
        <h2><i class="fa fa-exclamation-triangle"></i> Delete account</h2>
        <p class="muted">This permanently deletes your account, all <?= $entry_count ?> saved logins and your activity history. It can't be undone.</p>
        <form method="post" class="form-stack" data-confirm="Delete your LockHub account and everything in it? This cannot be undone.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <label class="field<?= isset($errors['delete']) ? ' has-error' : '' ?>">
            <span>Enter your master password to confirm</span>
            <input type="password" name="confirm_password" autocomplete="current-password" required>
            <?= err($errors, 'delete') ?>
          </label>
          <div><button class="btn btn-danger" type="submit"><i class="fa fa-trash-o"></i> Delete my account</button></div>
        </form>
      </section>
    </div>

    <section class="panel card activity">
      <h2><i class="fa fa-history"></i> Recent activity</h2>
      <p class="muted">The last 25 events on your account. If something looks unfamiliar, change your master password.</p>
      <?php if (!$activity): ?>
        <p class="muted">No activity yet.</p>
      <?php else: ?>
        <ol class="timeline">
          <?php foreach ($activity as $a): ?>
            <li class="<?= $a['action_type'] === 'Failed login' ? 'is-alert' : '' ?>">
              <span class="tl-icon"><i class="fa <?= $activity_icons[$a['action_type']] ?? 'fa-circle-o' ?>"></i></span>
              <div>
                <strong><?= e($a['action_type']) ?></strong>
                <?php if ($a['action_description']): ?><span><?= e($a['action_description']) ?></span><?php endif; ?>
                <small><?= date('M j, Y · g:i A', strtotime($a['action_timestamp'])) ?><?= $a['ip_address'] ? ' · ' . e($a['ip_address']) : '' ?></small>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php page_end(['variant' => 'app']); ?>

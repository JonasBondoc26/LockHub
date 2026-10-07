<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$error = '';
$uname = '';

if (is_post()) {
    require_csrf('login.php');

    $conn  = db();
    $uname = trim($_POST['uname'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $ip    = client_ip();

    $stmt = $conn->prepare('SELECT COUNT(*) AS n FROM login_attempts
                            WHERE user_name = ? AND ip_address = ? AND attempted_at > NOW() - INTERVAL ? MINUTE');
    $stmt->bind_param('ssi', $uname, $ip, $LH_CONFIG['lockout_minutes']);
    $stmt->execute();
    $attempts = (int) $stmt->get_result()->fetch_assoc()['n'];

    if ($uname === '' || $pass === '') {
        $error = 'Enter your username and master password.';
    } elseif ($attempts >= $LH_CONFIG['max_login_attempts']) {
        $error = "Too many failed attempts. Please wait {$LH_CONFIG['lockout_minutes']} minutes and try again.";
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE user_name = ?');
        $stmt->bind_param('s', $uname);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $ok = false;
        if ($user) {
            $ok = password_verify($pass, $user['password']);
            // Accounts created by the original LockHub hashed an HTML-escaped copy of the password.
            if (!$ok && password_verify(htmlspecialchars(stripslashes(trim($pass))), $user['password'])) {
                $ok = true;
                $user['password'] = '';  // forces the rehash below
            }
        }

        if ($ok) {
            $uid = (int) $user['id'];

            if (!password_verify($pass, $user['password']) || password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->bind_param('si', $hash, $uid);
                $stmt->execute();
            }

            if (empty($user['vault_salt'])) {
                $user['vault_salt'] = bin2hex(random_bytes(16));
                $stmt = $conn->prepare('UPDATE users SET vault_salt = ? WHERE id = ?');
                $stmt->bind_param('si', $user['vault_salt'], $uid);
                $stmt->execute();
            }

            $key = derive_vault_key($pass, $user['vault_salt']);
            upgrade_legacy_entries($conn, $uid, $key);

            $stmt = $conn->prepare('DELETE FROM login_attempts WHERE user_name = ? AND ip_address = ?');
            $stmt->bind_param('ss', $uname, $ip);
            $stmt->execute();

            $stmt = $conn->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $stmt->bind_param('i', $uid);
            $stmt->execute();

            audit($conn, $uid, 'Logged in');
            start_user_session($user, $key);
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('home.php');
        }

        $stmt = $conn->prepare('INSERT INTO login_attempts (user_name, ip_address) VALUES (?, ?)');
        $stmt->bind_param('ss', $uname, $ip);
        $stmt->execute();
        if ($user) {
            audit($conn, (int) $user['id'], 'Failed login', 'Wrong master password entered.');
        }
        $error = 'Incorrect username or password.';
    }
}

page_start('Log in', ['variant' => 'auth']);
?>
<section class="auth-wrap">
  <div class="auth-split">
    <?php include __DIR__ . '/includes/auth_aside.php'; ?>
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p class="muted">Log in with your master password to unlock your vault.</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> <?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" class="form-stack" novalidate>
        <?= csrf_field() ?>
        <label class="field">
          <span>Username</span>
          <input type="text" name="uname" value="<?= e($uname) ?>" autocomplete="username" required autofocus>
        </label>
        <label class="field">
          <span>Master password</span>
          <span class="input-group">
            <input type="password" name="password" autocomplete="current-password" required>
            <button type="button" class="icon-btn" data-toggle-visibility aria-label="Show password"><i class="fa fa-eye"></i></button>
          </span>
        </label>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Log in <i class="fa fa-arrow-right"></i></button>
      </form>
      <p class="auth-switch">New to LockHub? <a href="signup.php">Create a free account</a></p>
    </div>
  </div>
</section>
<?php page_end(['variant' => 'auth']); ?>

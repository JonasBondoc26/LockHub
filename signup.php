<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$errors = [];
$name = '';
$uname = '';

if (is_post()) {
    require_csrf('signup.php');

    $name     = trim($_POST['name'] ?? '');
    $uname    = trim($_POST['uname'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $re_pass  = $_POST['re_password'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errors['name'] = 'Enter your name (up to 100 characters).';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $uname)) {
        $errors['uname'] = 'Use 3–30 letters, numbers, dots, dashes or underscores.';
    }
    if (mb_strlen($pass) < 8) {
        $errors['password'] = 'Your master password needs at least 8 characters.';
    } elseif (password_strength($pass) < 2) {
        $errors['password'] = 'That password is too easy to guess. Make it longer or mix in numbers and symbols.';
    }
    if ($pass !== $re_pass) {
        $errors['re_password'] = "The passwords don't match.";
    }

    if (!$errors) {
        $conn = db();
        $stmt = $conn->prepare('SELECT id FROM users WHERE user_name = ?');
        $stmt->bind_param('s', $uname);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['uname'] = 'That username is taken. Try another one.';
        }
    }

    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $salt = bin2hex(random_bytes(16));
        $stmt = $conn->prepare('INSERT INTO users (user_name, password, name, vault_salt) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $uname, $hash, $name, $salt);
        $stmt->execute();
        $uid = $conn->insert_id;

        audit($conn, $uid, 'Account created');
        start_user_session(['id' => $uid, 'user_name' => $uname, 'name' => $name], derive_vault_key($pass, $salt));
        flash('success', "Your vault is ready, $name. Add your first password to get started.");
        redirect('home.php');
    }
}

function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<small class="field-error">' . e($errors[$field]) . '</small>' : '';
}

page_start('Create account', ['variant' => 'auth']);
?>
<section class="auth-wrap">
  <div class="auth-split">
    <?php include __DIR__ . '/includes/auth_aside.php'; ?>
    <div class="auth-card">
      <h1>Create your vault</h1>
      <p class="muted">It takes less than a minute. Pick a master password you'll remember — it's the only one you'll need.</p>

      <form method="post" class="form-stack" novalidate>
        <?= csrf_field() ?>
        <label class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <span>Your name</span>
          <input type="text" name="name" value="<?= e($name) ?>" maxlength="100" autocomplete="name" required autofocus>
          <?= field_error($errors, 'name') ?>
        </label>
        <label class="field<?= isset($errors['uname']) ? ' has-error' : '' ?>">
          <span>Username</span>
          <input type="text" name="uname" value="<?= e($uname) ?>" maxlength="30" autocomplete="username" required>
          <?= field_error($errors, 'uname') ?>
        </label>
        <label class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
          <span>Master password</span>
          <span class="input-group">
            <input type="password" name="password" autocomplete="new-password" required data-strength-input>
            <button type="button" class="icon-btn" data-toggle-visibility aria-label="Show password"><i class="fa fa-eye"></i></button>
          </span>
          <span class="strength" data-strength-meter><span class="strength-bar"><i></i></span><span class="strength-label">Use at least 8 characters</span></span>
          <?= field_error($errors, 'password') ?>
        </label>
        <label class="field<?= isset($errors['re_password']) ? ' has-error' : '' ?>">
          <span>Confirm master password</span>
          <input type="password" name="re_password" autocomplete="new-password" required>
          <?= field_error($errors, 're_password') ?>
        </label>
        <p class="hint"><i class="fa fa-info-circle"></i> Your master password encrypts your vault. LockHub can't recover it if you forget it.</p>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Create account <i class="fa fa-arrow-right"></i></button>
      </form>
      <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
    </div>
  </div>
</section>
<?php page_end(['variant' => 'auth']); ?>

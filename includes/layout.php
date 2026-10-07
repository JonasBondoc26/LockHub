<?php
// Shared page chrome. Pages call page_start() and page_end() around their content.

function lh_logo(string $class = 'logo-mark'): string
{
    return '<svg class="' . $class . '" viewBox="0 0 32 32" aria-hidden="true">'
         . '<rect width="32" height="32" rx="9" fill="url(#lhg)"/>'
         . '<defs><linearGradient id="lhg" x1="0" y1="0" x2="1" y2="1">'
         . '<stop offset="0" stop-color="#00bbf0"/><stop offset="1" stop-color="#5b4bff"/></linearGradient></defs>'
         . '<path d="M11 14v-3a5 5 0 0 1 10 0v3" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/>'
         . '<rect x="8.5" y="14" width="15" height="11" rx="3" fill="#fff"/>'
         . '<circle cx="16" cy="19" r="1.8" fill="#231a6f"/><rect x="15.1" y="19.5" width="1.8" height="3" rx=".9" fill="#231a6f"/>'
         . '</svg>';
}

function page_start(string $title, array $opts = []): void
{
    $variant = $opts['variant'] ?? 'public';   // public | app | auth
    $active  = $opts['active'] ?? '';
    $flashes = function_exists('take_flashes') ? take_flashes() : [];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="LockHub keeps your passwords encrypted, organised and one click away.">
  <title><?= e($title) ?> · LockHub</title>
  <link rel="icon" href="images/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
  <link href="css/font-awesome.min.css" rel="stylesheet">
  <link href="css/lockhub.css" rel="stylesheet">
</head>
<body class="variant-<?= e($variant) ?>" data-csrf="<?= e(function_exists('csrf_token') ? csrf_token() : '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php
    if ($variant === 'app') {
        render_app_header($active);
    } elseif ($variant === 'public') {
        render_public_header($active);
    }
    ?>
<div class="toasts" id="toasts" aria-live="polite">
<?php foreach ($flashes as $f): ?>
  <div class="toast toast-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
</div>
<main id="main">
<?php
}

function page_end(array $opts = []): void
{
    $variant = $opts['variant'] ?? 'public';
    ?>
</main>
<?php if ($variant === 'public'): ?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="brand brand-light" href="index.php"><?= lh_logo() ?><span>LockHub</span></a>
      <p class="muted-light">An encrypted password vault, built as a Dynamic Web Applications and Development finals project.</p>
    </div>
    <div>
      <h4>Product</h4>
      <a href="why.php">Features</a>
      <a href="generator.php">Password generator</a>
      <a href="signup.php">Create an account</a>
    </div>
    <div>
      <h4>Project</h4>
      <a href="about.php">About &amp; team</a>
      <a href="login.php">Log in</a>
      <a href="mailto:support@lockhub.com">support@lockhub.com</a>
    </div>
  </div>
  <div class="container footer-bottom">&copy; <?= date('Y') ?> LockHub. All rights reserved.</div>
</footer>
<?php elseif ($variant === 'app'): ?>
<footer class="app-footer container">
  <span><i class="fa fa-lock"></i> Entries are encrypted with AES-256 using a key derived from your master password.</span>
  <span>&copy; <?= date('Y') ?> LockHub</span>
</footer>
<?php endif; ?>
<script src="js/lockhub.js"></script>
</body>
</html>
<?php
}

function render_public_header(string $active): void
{
    $links = ['home' => ['index.php', 'Home'], 'features' => ['why.php', 'Features'],
              'generator' => ['generator.php', 'Generator'], 'about' => ['about.php', 'About']];
    ?>
<header class="site-header">
  <div class="container nav-row">
    <a class="brand" href="index.php"><?= lh_logo() ?><span>LockHub</span></a>
    <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
      <i class="fa fa-bars"></i>
    </button>
    <nav class="nav-links" data-nav>
      <?php foreach ($links as $key => [$href, $label]): ?>
        <a href="<?= $href ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
      <span class="nav-actions">
        <?php if (is_logged_in()): ?>
          <a class="btn btn-primary btn-sm" href="home.php"><i class="fa fa-unlock-alt"></i> Open vault</a>
        <?php else: ?>
          <a href="login.php"<?= $active === 'login' ? ' class="active"' : '' ?>>Log in</a>
          <a class="btn btn-primary btn-sm" href="signup.php">Get started</a>
        <?php endif; ?>
      </span>
    </nav>
  </div>
</header>
<?php
}

function render_app_header(string $active): void
{
    $links = ['vault' => ['home.php', 'fa-th-large', 'Vault'],
              'generator' => ['generator.php', 'fa-magic', 'Generator'],
              'settings' => ['account_settings.php', 'fa-cog', 'Settings']];
    $name = $_SESSION['name'] ?? '';
    ?>
<header class="app-header">
  <div class="container nav-row">
    <a class="brand brand-light" href="home.php"><?= lh_logo() ?><span>LockHub</span></a>
    <nav class="app-nav">
      <?php foreach ($links as $key => [$href, $icon, $label]): ?>
        <a href="<?= $href ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>>
          <i class="fa <?= $icon ?>"></i><span><?= $label ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="user-chip">
      <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
      <span class="user-name"><?= e($name) ?></span>
      <form action="logout.php" method="post">
        <?= csrf_field() ?>
        <button class="btn btn-ghost-light btn-sm" type="submit" title="Log out"><i class="fa fa-sign-out"></i><span>Log out</span></button>
      </form>
    </div>
  </div>
</header>
<?php
}

function render_db_error(string $detail): void
{
    page_start('Database unavailable', ['variant' => 'auth']);
    ?>
<section class="auth-wrap">
  <div class="auth-card">
    <a class="brand" href="index.php"><?= lh_logo() ?><span>LockHub</span></a>
    <h1>Can't reach the database</h1>
    <?php if ($detail !== ''): ?>
      <p class="muted">LockHub couldn't connect to MySQL. If you're running it with XAMPP, open the XAMPP Control Panel and start <strong>MySQL</strong>, then reload this page. The database and tables are created automatically.</p>
    <?php else: ?>
      <p class="muted">LockHub is temporarily unavailable. Please try again in a moment.</p>
    <?php endif; ?>
    <?php if ($detail !== ''): ?><pre class="error-detail"><?= e($detail) ?></pre><?php endif; ?>
    <a class="btn btn-primary btn-block" href="">Try again</a>
  </div>
</section>
<?php
    page_end(['variant' => 'auth']);
}

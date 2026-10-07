<?php
require __DIR__ . '/includes/bootstrap.php';
page_start('Your secure password manager', ['active' => 'home']);
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow eyebrow-light"><i class="fa fa-shield"></i> AES-256 encrypted vault</span>
      <h1>Every password you own, <span class="accent">locked in one place.</span></h1>
      <p class="lead">LockHub remembers your logins so you don't have to. Generate strong passwords, spot weak and reused ones, and unlock everything with a single master password.</p>
      <div class="hero-actions">
        <?php if (is_logged_in()): ?>
          <a class="btn btn-primary btn-lg" href="home.php"><i class="fa fa-unlock-alt"></i> Open my vault</a>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="signup.php">Create a free vault <i class="fa fa-arrow-right"></i></a>
        <?php endif; ?>
        <a class="btn btn-ghost-light btn-lg" href="generator.php"><i class="fa fa-magic"></i> Try the generator</a>
      </div>
    </div>

    <div class="hero-visual" aria-hidden="true">
      <div class="mock-window">
        <div class="mock-bar"><i></i><i></i><i></i><span>LockHub · My vault</span></div>
        <div class="mock-stats">
          <div><strong>24</strong><span>Logins</span></div>
          <div><strong>0</strong><span>Weak</span></div>
          <div class="mock-ring"><span>96%</span></div>
        </div>
        <ul class="mock-list">
          <li><span class="entry-avatar" style="--h: 210">G</span><div><strong>Gmail</strong><small>you@gmail.com</small></div><code>••••••••</code><span class="badge badge-good">Very strong</span></li>
          <li><span class="entry-avatar" style="--h: 330">I</span><div><strong>Instagram</strong><small>@your.handle</small></div><code>••••••••</code><span class="badge badge-good">Strong</span></li>
          <li><span class="entry-avatar" style="--h: 140">S</span><div><strong>Spotify</strong><small>you@gmail.com</small></div><code>••••••••</code><span class="badge badge-warn">Reused</span></li>
          <li><span class="entry-avatar" style="--h: 40">B</span><div><strong>BPI Online</strong><small>juan.dc</small></div><code>••••••••</code><span class="badge badge-good">Very strong</span></li>
        </ul>
      </div>
      <div class="float-card">
        <i class="fa fa-magic"></i>
        <div><small>Generated password</small><span class="mono">q7#Vt!mZ2p@Lx9eR</span></div>
      </div>
    </div>
  </div>
</section>

<section class="trust-strip">
  <div class="container trust-row">
    <span><i class="fa fa-lock"></i> AES-256-GCM encryption</span>
    <span><i class="fa fa-key"></i> PBKDF2 key derivation</span>
    <span><i class="fa fa-clock-o"></i> Auto-lock after inactivity</span>
    <span><i class="fa fa-ban"></i> Brute-force lockout</span>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Features</span>
      <h2>Everything you need to stay secure online</h2>
      <p>LockHub replaces sticky notes, spreadsheets and "the same password everywhere" with a vault that's simple to use.</p>
    </div>
    <div class="feature-grid">
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-lock"></i></span>
        <h3>Encrypted vault</h3>
        <p>Each password and note is encrypted with a key derived from your master password before it reaches the database.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-magic"></i></span>
        <h3>Password generator</h3>
        <p>Create random passwords or memorable passphrases in one click, then save them straight into your vault.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-heartbeat"></i></span>
        <h3>Vault health check</h3>
        <p>See at a glance which passwords are weak or reused, and get an overall health score for your vault.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-clone"></i></span>
        <h3>Copy in one click</h3>
        <p>Search your vault, then copy a username or password without ever showing it on screen.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-history"></i></span>
        <h3>Activity log</h3>
        <p>Every login, failed attempt and change is recorded so you can spot anything unusual.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-user-secret"></i></span>
        <h3>Built-in protection</h3>
        <p>Auto-lock, login attempt limits, CSRF protection and password-history checks guard your account.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">How it works</span>
      <h2>Up and running in three steps</h2>
    </div>
    <ol class="steps">
      <li>
        <span class="step-num">1</span>
        <h3>Create your vault</h3>
        <p>Sign up and choose one strong master password. It's the only one you'll need to remember.</p>
      </li>
      <li>
        <span class="step-num">2</span>
        <h3>Add your logins</h3>
        <p>Save existing accounts or generate brand new passwords for them right inside LockHub.</p>
      </li>
      <li>
        <span class="step-num">3</span>
        <h3>Log in anywhere</h3>
        <p>Search, copy and paste. Fix weak or reused passwords when your health check flags them.</p>
      </li>
    </ol>
  </div>
</section>

<section class="section">
  <div class="container security-grid">
    <div>
      <span class="eyebrow">Security</span>
      <h2>Your master password is the key to your vault</h2>
      <p>When you log in, LockHub runs your master password through 150,000 rounds of PBKDF2 to create an encryption key. That key encrypts each entry with AES-256-GCM, which also detects any tampering.</p>
      <p>The database only stores scrambled data, and your master password itself is saved as a one-way bcrypt hash.</p>
      <a class="text-link" href="why.php">Read more about how LockHub protects you <i class="fa fa-arrow-right"></i></a>
    </div>
    <div class="flow" aria-label="How encryption works">
      <div class="flow-step"><i class="fa fa-user"></i><div><strong>Master password</strong><small>Only you know it</small></div></div>
      <div class="flow-arrow"><i class="fa fa-long-arrow-down"></i> PBKDF2-SHA256 × 150,000</div>
      <div class="flow-step"><i class="fa fa-key"></i><div><strong>256-bit vault key</strong><small>Kept only while you're logged in</small></div></div>
      <div class="flow-arrow"><i class="fa fa-long-arrow-down"></i> AES-256-GCM</div>
      <div class="flow-step flow-step-end"><i class="fa fa-database"></i><div><strong>Encrypted entries</strong><small>Unreadable without your key</small></div></div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-inner">
    <div>
      <h2>Stop reusing passwords today.</h2>
      <p>Create your LockHub vault in under a minute.</p>
    </div>
    <a class="btn btn-light btn-lg" href="<?= is_logged_in() ? 'home.php' : 'signup.php' ?>"><?= is_logged_in() ? 'Open my vault' : 'Get started' ?> <i class="fa fa-arrow-right"></i></a>
  </div>
</section>
<?php page_end(); ?>

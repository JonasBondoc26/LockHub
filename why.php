<?php
require __DIR__ . '/includes/bootstrap.php';
page_start('Features', ['active' => 'features']);
?>
<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Why LockHub</span>
    <h1>Simple enough for everyday use. Serious about security.</h1>
    <p>Here's what LockHub does for you, and how it keeps your data safe behind the scenes.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="compare">
      <div class="compare-col compare-bad">
        <h3><i class="fa fa-times-circle"></i> Without a password manager</h3>
        <ul>
          <li>The same password on a dozen sites</li>
          <li>Passwords kept in notes apps or on paper</li>
          <li>"Forgot password?" every other week</li>
          <li>One data breach unlocks all your accounts</li>
        </ul>
      </div>
      <div class="compare-col compare-good">
        <h3><i class="fa fa-check-circle"></i> With LockHub</h3>
        <ul>
          <li>A unique, random password for every site</li>
          <li>All logins encrypted in one searchable vault</li>
          <li>Copy any password in a single click</li>
          <li>A breach on one site stays on that site</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Inside the vault</span>
      <h2>Features that make security effortless</h2>
    </div>
    <div class="feature-rows">
      <article class="feature-row">
        <span class="feature-icon"><i class="fa fa-th-large"></i></span>
        <div>
          <h3>A clean, searchable vault</h3>
          <p>Store a name, website, username, password and private notes for every account. Search instantly, filter by weak or reused passwords, and open the website straight from your vault.</p>
        </div>
      </article>
      <article class="feature-row">
        <span class="feature-icon"><i class="fa fa-magic"></i></span>
        <div>
          <h3>Password and passphrase generator</h3>
          <p>Pick the length and character types, avoid look-alike characters, or switch to easy-to-type passphrases. Randomness comes from your browser's cryptographic generator.</p>
        </div>
      </article>
      <article class="feature-row">
        <span class="feature-icon"><i class="fa fa-heartbeat"></i></span>
        <div>
          <h3>Vault health score</h3>
          <p>LockHub rates every password and detects ones you've used more than once, then rolls it all into a single score so you know what to fix first.</p>
        </div>
      </article>
      <article class="feature-row">
        <span class="feature-icon"><i class="fa fa-history"></i></span>
        <div>
          <h3>Account activity log</h3>
          <p>Logins, failed attempts, new entries, edits and deletions are all recorded with the time and IP address, so you'll notice if someone else gets in.</p>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Security model</span>
      <h2>How LockHub protects your data</h2>
    </div>
    <div class="feature-grid">
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-key"></i></span>
        <h3>Key from your password</h3>
        <p>Your vault key is derived from your master password with PBKDF2-SHA256 (150,000 rounds) and a unique salt. Without the password, the key can't be rebuilt.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-lock"></i></span>
        <h3>AES-256-GCM encryption</h3>
        <p>Passwords and notes are encrypted with a fresh random IV every time they're saved. GCM also detects if stored data was altered.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-eye-slash"></i></span>
        <h3>Revealed only on request</h3>
        <p>Passwords aren't written into the page. They're decrypted one at a time, only when you click show or copy.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-ban"></i></span>
        <h3>Brute-force lockout</h3>
        <p>After five wrong master passwords, logins for that account are paused for 15 minutes.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-clock-o"></i></span>
        <h3>Auto-lock</h3>
        <p>Walk away and forget to log out? Your vault locks itself after 15 minutes without activity.</p>
      </article>
      <article class="feature">
        <span class="feature-icon"><i class="fa fa-refresh"></i></span>
        <h3>No password reuse</h3>
        <p>When you change your master password, LockHub blocks old ones and re-encrypts your whole vault with the new key.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container narrow">
    <div class="section-head">
      <span class="eyebrow">FAQ</span>
      <h2>Common questions</h2>
    </div>
    <div class="faq">
      <details>
        <summary>What happens if I forget my master password?</summary>
        <p>Your vault is encrypted with a key that only your master password can recreate, so it can't be recovered. That's the trade-off that keeps your data private. Pick something long that you'll remember, like a passphrase.</p>
      </details>
      <details>
        <summary>Can LockHub see my passwords?</summary>
        <p>The database only stores encrypted data. Your vault key exists only while you're logged in, so the stored data on its own is unreadable.</p>
      </details>
      <details>
        <summary>Is the password generator really random?</summary>
        <p>Yes. It uses <span class="mono">crypto.getRandomValues()</span>, the same secure random source browsers use for encryption, and it runs entirely on your device.</p>
      </details>
      <details>
        <summary>Does LockHub cost anything?</summary>
        <p>No. LockHub is a free student project.</p>
      </details>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-inner">
    <div>
      <h2>Ready to lock it down?</h2>
      <p>Your first vault is one form away.</p>
    </div>
    <a class="btn btn-light btn-lg" href="<?= is_logged_in() ? 'home.php' : 'signup.php' ?>"><?= is_logged_in() ? 'Open my vault' : 'Create a free vault' ?> <i class="fa fa-arrow-right"></i></a>
  </div>
</section>
<?php page_end(); ?>

<?php
require __DIR__ . '/includes/bootstrap.php';

$variant = is_logged_in() ? 'app' : 'public';
page_start('Password generator', ['variant' => $variant, 'active' => 'generator']);
?>
<?php if ($variant === 'public'): ?>
<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Free tool</span>
    <h1>Strong password generator</h1>
    <p>Passwords are generated in your browser with a cryptographically secure random source. Nothing is sent to our server.</p>
  </div>
</section>
<?php endif; ?>

<div class="container <?= $variant === 'app' ? 'app-main' : 'section' ?>">
  <?php if ($variant === 'app'): ?>
    <div class="page-head">
      <div>
        <h1>Password generator</h1>
        <p class="muted">Generated in your browser with a secure random source. Copy it into a new vault entry.</p>
      </div>
    </div>
  <?php endif; ?>

  <div class="generator-layout">
    <section class="panel card generator" data-generator>
      <div class="tabs" role="tablist">
        <button type="button" class="tab active" data-mode="password" role="tab" aria-selected="true">Password</button>
        <button type="button" class="tab" data-mode="passphrase" role="tab" aria-selected="false">Passphrase</button>
      </div>

      <div class="gen-output">
        <output class="mono" data-gen-output aria-live="polite"></output>
        <div class="gen-output-actions">
          <button type="button" class="icon-btn" data-gen-refresh title="Generate another" aria-label="Generate another"><i class="fa fa-refresh"></i></button>
          <button type="button" class="btn btn-primary" data-gen-copy><i class="fa fa-clone"></i> Copy</button>
        </div>
      </div>
      <div class="strength strength-lg" data-gen-strength><span class="strength-bar"><i></i></span><span class="strength-label"></span></div>

      <div class="gen-options" data-options="password">
        <label class="range">
          <span>Length <strong data-gen-length-label>20</strong></span>
          <input type="range" min="8" max="64" value="20" data-gen-length>
        </label>
        <div class="toggles">
          <label class="toggle"><input type="checkbox" data-gen-set="upper" checked><span></span> Uppercase (A–Z)</label>
          <label class="toggle"><input type="checkbox" data-gen-set="lower" checked><span></span> Lowercase (a–z)</label>
          <label class="toggle"><input type="checkbox" data-gen-set="digits" checked><span></span> Numbers (0–9)</label>
          <label class="toggle"><input type="checkbox" data-gen-set="symbols" checked><span></span> Symbols (!@#$…)</label>
          <label class="toggle"><input type="checkbox" data-gen-ambiguous><span></span> Avoid look-alikes (0/O, 1/l/I)</label>
        </div>
      </div>

      <div class="gen-options" data-options="passphrase" hidden>
        <label class="range">
          <span>Words <strong data-gen-words-label>5</strong></span>
          <input type="range" min="3" max="10" value="5" data-gen-words>
        </label>
        <div class="toggles">
          <label class="toggle"><input type="checkbox" data-gen-capitalize checked><span></span> Capitalise words</label>
          <label class="toggle"><input type="checkbox" data-gen-number checked><span></span> Add a number</label>
        </div>
        <label class="field field-inline">
          <span>Separator</span>
          <select class="select" data-gen-separator>
            <option value="-">Dash ( - )</option>
            <option value=".">Dot ( . )</option>
            <option value="_">Underscore ( _ )</option>
            <option value=" ">Space</option>
          </select>
        </label>
      </div>

      <?php if ($variant === 'app'): ?>
        <a class="btn btn-soft btn-block" href="home.php" data-gen-save><i class="fa fa-plus"></i> Save as a new vault entry</a>
      <?php else: ?>
        <p class="hint"><i class="fa fa-lock"></i> Want to keep it safe? <a href="signup.php">Create a free LockHub vault</a>.</p>
      <?php endif; ?>
    </section>

    <aside class="panel card tips">
      <h2><i class="fa fa-lightbulb-o"></i> What makes a password strong?</h2>
      <ul class="tip-list">
        <li><strong>Length beats complexity.</strong> Every extra character multiplies the guesses an attacker needs. Aim for 16+.</li>
        <li><strong>Never reuse passwords.</strong> When one site leaks, attackers try the same login everywhere else.</li>
        <li><strong>Avoid personal details.</strong> Birthdays, pet names and favourite teams are the first things guessed.</li>
        <li><strong>Passphrases are easy to type.</strong> Five random words like <span class="mono">Orbit-Velvet-Canyon-Mango-Trail7</span> are strong and memorable.</li>
        <li><strong>Let the vault remember.</strong> You only need to memorise your master password.</li>
      </ul>
    </aside>
  </div>
</div>
<?php page_end(['variant' => $variant]); ?>

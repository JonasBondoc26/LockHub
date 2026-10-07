<?php
require __DIR__ . '/includes/bootstrap.php';

$team = [
    ['images/1.png', 'Anunciacion Adrian', 'Chief Security Officer'],
    ['images/2.png', 'Bondoc Jonas', 'Data Encryption Specialist'],
    ['images/3.png', 'Galang Raphael', 'UX/UI Design Lead'],
    ['images/4.png', 'Antonio Charles', 'Cloud Integration Expert'],
];

page_start('About', ['active' => 'about']);
?>
<section class="page-hero">
  <div class="container">
    <span class="eyebrow">About us</span>
    <h1>We built LockHub so good security doesn't feel like a chore.</h1>
    <p>Most people reuse passwords because remembering dozens of strong ones is impossible. LockHub remembers them for you.</p>
  </div>
</section>

<section class="section">
  <div class="container about-grid">
    <div>
      <span class="eyebrow">Our mission</span>
      <h2>Protect people's digital lives with tools anyone can use</h2>
      <p>LockHub is a web-based password manager. It stores your logins in an encrypted vault, helps you create strong new passwords, and shows you which accounts need attention.</p>
      <p>We designed it around one idea: the secure choice should also be the easy one. You remember a single master password, and LockHub handles the rest.</p>
    </div>
    <div class="value-list">
      <div class="value"><i class="fa fa-shield"></i><div><strong>Security first</strong><span>Modern encryption and safe defaults, not just a login screen.</span></div></div>
      <div class="value"><i class="fa fa-hand-pointer-o"></i><div><strong>Simple to use</strong><span>Search, copy, done. No manual needed.</span></div></div>
      <div class="value"><i class="fa fa-user-secret"></i><div><strong>Private by design</strong><span>Your vault key comes from your password, and only you have it.</span></div></div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">The team</span>
      <h2>Meet the people behind LockHub</h2>
    </div>
    <div class="team-grid">
      <?php foreach ($team as [$photo, $name, $role]): ?>
        <article class="member">
          <img src="<?= $photo ?>" alt="<?= e($name) ?>">
          <h3><?= e($name) ?></h3>
          <p><?= e($role) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Under the hood</span>
      <h2>Built with</h2>
    </div>
    <div class="stack">
      <span><i class="fa fa-code"></i> PHP 8</span>
      <span><i class="fa fa-database"></i> MySQL / MariaDB</span>
      <span><i class="fa fa-lock"></i> OpenSSL AES-256-GCM</span>
      <span><i class="fa fa-html5"></i> HTML5 &amp; CSS3</span>
      <span><i class="fa fa-file-code-o"></i> Vanilla JavaScript</span>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-inner">
    <div>
      <h2>Your security, our priority.</h2>
      <p>Join LockHub and keep every login safe.</p>
    </div>
    <a class="btn btn-light btn-lg" href="<?= is_logged_in() ? 'home.php' : 'signup.php' ?>"><?= is_logged_in() ? 'Open my vault' : 'Get started' ?> <i class="fa fa-arrow-right"></i></a>
  </div>
</section>
<?php page_end(); ?>

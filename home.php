<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$conn = db();
$uid  = (int) $_SESSION['id'];
$key  = vault_key();

/* ---------- Actions ---------- */

if (is_post()) {
    require_csrf('home.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $website  = trim($_POST['website'] ?? '');
        $url      = trim($_POST['url'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $notes    = trim($_POST['notes'] ?? '');

        if ($url !== '' && !preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $problem = null;
        if ($website === '' || mb_strlen($website) > 255) {
            $problem = 'Give the entry a name, like "Gmail" or "Facebook".';
        } elseif ($username === '' || mb_strlen($username) > 255) {
            $problem = 'Enter the username or email for this login.';
        } elseif ($password === '') {
            $problem = 'Enter the password for this login.';
        } elseif ($url !== '' && (mb_strlen($url) > 500 || !filter_var($url, FILTER_VALIDATE_URL))) {
            $problem = "That website address doesn't look right.";
        } elseif (mb_strlen($notes) > 2000) {
            $problem = 'Notes can be up to 2000 characters.';
        }

        if ($problem) {
            flash('error', $problem);
            redirect('home.php');
        }

        $enc_password = lh_encrypt($password, $key);
        $enc_notes    = $notes === '' ? null : lh_encrypt($notes, $key);
        $url          = $url === '' ? null : $url;

        try {
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE passwords SET website = ?, url = ?, username = ?, password = ?, notes = ?
                                        WHERE id = ? AND user_id = ?');
                $stmt->bind_param('sssssii', $website, $url, $username, $enc_password, $enc_notes, $id, $uid);
                $stmt->execute();
                // The password is re-encrypted with a fresh IV, so an owned row always changes.
                if ($stmt->affected_rows > 0) {
                    audit($conn, $uid, 'Entry updated', "Updated the login for $website.");
                    flash('success', "Saved changes to $website.");
                } else {
                    flash('error', "That entry doesn't exist anymore.");
                }
            } else {
                $stmt = $conn->prepare('INSERT INTO passwords (user_id, website, url, username, password, notes)
                                        VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('isssss', $uid, $website, $url, $username, $enc_password, $enc_notes);
                $stmt->execute();
                audit($conn, $uid, 'Entry added', "Added a login for $website.");
                flash('success', "$website was added to your vault.");
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() !== 1062) {
                throw $e;
            }
            flash('error', "You already have a $website login with the username $username.");
        }
        redirect('home.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT website FROM passwords WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $stmt = $conn->prepare('DELETE FROM passwords WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $uid);
            $stmt->execute();
            audit($conn, $uid, 'Entry deleted', "Deleted the login for {$row['website']}.");
            flash('success', "{$row['website']} was removed from your vault.");
        }
        redirect('home.php');
    }

    redirect('home.php');
}

/* ---------- Load the vault ---------- */

$stmt = $conn->prepare('SELECT id, website, url, username, password, notes, updated_at FROM passwords WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$entries = [];
$seen = [];
foreach ($rows as $row) {
    $plain = lh_decrypt($row['password'], $key);
    $row['locked']   = $plain === null;
    $row['strength'] = $plain === null ? 0 : password_strength($plain);
    $row['notes']    = $row['notes'] === null ? '' : (lh_decrypt($row['notes'], $key) ?? '');
    $row['fp']       = $plain === null ? null : hash_hmac('sha256', $plain, $key);
    if ($row['fp'] !== null) {
        $seen[$row['fp']] = ($seen[$row['fp']] ?? 0) + 1;
    }
    unset($row['password']);
    $entries[] = $row;
}
foreach ($entries as &$entry) {
    $entry['reused'] = $entry['fp'] !== null && $seen[$entry['fp']] > 1;
}
unset($entry);

$total   = count($entries);
$weak    = count(array_filter($entries, fn ($en) => $en['strength'] < 3));
$reused  = count(array_filter($entries, fn ($en) => $en['reused']));
$healthy = count(array_filter($entries, fn ($en) => $en['strength'] >= 3 && !$en['reused']));
$health  = $total ? (int) round($healthy / $total * 100) : 100;

function time_ago(string $ts): string
{
    $diff = time() - strtotime($ts);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return intdiv($diff, 60) . ' min ago';
    if ($diff < 86400)  return intdiv($diff, 3600) . ' h ago';
    if ($diff < 604800) return intdiv($diff, 86400) . ' d ago';
    return date('M j, Y', strtotime($ts));
}

function avatar_hue(string $name): int
{
    return crc32(strtolower($name)) % 360;
}

$strength_class = ['danger', 'danger', 'warn', 'good', 'good'];

page_start('My vault', ['variant' => 'app', 'active' => 'vault']);
?>
<div class="container app-main">

  <div class="page-head">
    <div>
      <h1>Hi, <?= e($_SESSION['name']) ?></h1>
      <p class="muted">Your vault is unlocked. It locks itself after <?= intdiv($LH_CONFIG['idle_timeout'], 60) ?> minutes of inactivity.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-entry><i class="fa fa-plus"></i> Add password</button>
  </div>

  <section class="stats">
    <div class="stat">
      <span class="stat-icon"><i class="fa fa-key"></i></span>
      <div><strong><?= $total ?></strong><span>Saved logins</span></div>
    </div>
    <div class="stat<?= $weak ? ' stat-warn' : '' ?>">
      <span class="stat-icon"><i class="fa fa-exclamation-triangle"></i></span>
      <div><strong><?= $weak ?></strong><span>Weak passwords</span></div>
    </div>
    <div class="stat<?= $reused ? ' stat-warn' : '' ?>">
      <span class="stat-icon"><i class="fa fa-clone"></i></span>
      <div><strong><?= $reused ?></strong><span>Reused passwords</span></div>
    </div>
    <div class="stat stat-health">
      <span class="ring" style="--p: <?= $health ?>"><span><?= $health ?>%</span></span>
      <div><strong>Vault health</strong><span><?= $health >= 80 ? 'Looking good' : ($health >= 50 ? 'Room to improve' : 'Needs attention') ?></span></div>
    </div>
  </section>

  <?php if ($total === 0): ?>
    <section class="empty">
      <img src="images/slider1.png" alt="">
      <h2>Your vault is empty</h2>
      <p class="muted">Save your first login and LockHub will keep it encrypted and one click away.</p>
      <button class="btn btn-primary btn-lg" type="button" data-open-entry><i class="fa fa-plus"></i> Add your first password</button>
    </section>
  <?php else: ?>
    <section class="panel">
      <div class="toolbar">
        <label class="search">
          <i class="fa fa-search"></i>
          <input type="search" placeholder="Search by name, website or username" data-search aria-label="Search vault">
        </label>
        <div class="chips" role="group" aria-label="Filter">
          <button type="button" class="chip active" data-filter="all">All <span><?= $total ?></span></button>
          <button type="button" class="chip" data-filter="weak">Weak <span><?= $weak ?></span></button>
          <button type="button" class="chip" data-filter="reused">Reused <span><?= $reused ?></span></button>
        </div>
        <select class="select" data-sort aria-label="Sort">
          <option value="recent">Recently updated</option>
          <option value="name">Name A–Z</option>
        </select>
      </div>

      <ul class="entries" data-entries>
        <?php foreach ($entries as $en):
            $host = $en['url'] ? parse_url($en['url'], PHP_URL_HOST) : '';
            ?>
          <li class="entry"
              data-id="<?= $en['id'] ?>"
              data-website="<?= e($en['website']) ?>"
              data-url="<?= e($en['url'] ?? '') ?>"
              data-username="<?= e($en['username']) ?>"
              data-notes="<?= e($en['notes']) ?>"
              data-weak="<?= $en['strength'] < 3 ? 1 : 0 ?>"
              data-reused="<?= $en['reused'] ? 1 : 0 ?>"
              data-updated="<?= strtotime($en['updated_at']) ?>">
            <span class="entry-avatar" style="--h: <?= avatar_hue($en['website']) ?>"><?= e(mb_strtoupper(mb_substr($en['website'], 0, 1))) ?></span>
            <div class="entry-main">
              <strong class="entry-name"><?= e($en['website']) ?></strong>
              <span class="entry-sub">
                <?= e($en['username']) ?>
                <button type="button" class="mini-btn" data-copy-text="<?= e($en['username']) ?>" title="Copy username" aria-label="Copy username"><i class="fa fa-clone"></i></button>
                <?php if ($host): ?><span class="dot">·</span><span class="entry-host"><?= e($host) ?></span><?php endif; ?>
              </span>
            </div>
            <div class="entry-secret">
              <code class="secret" data-secret>••••••••••</code>
              <button type="button" class="icon-btn" data-reveal title="Show password" aria-label="Show password"><i class="fa fa-eye"></i></button>
              <button type="button" class="icon-btn" data-copy-secret title="Copy password" aria-label="Copy password"><i class="fa fa-clone"></i></button>
            </div>
            <div class="entry-meta">
              <span class="badges">
              <?php if ($en['locked']): ?>
                <span class="badge badge-danger" title="This entry couldn't be decrypted">Unreadable</span>
              <?php else: ?>
                <span class="badge badge-<?= $strength_class[$en['strength']] ?>"><?= LH_STRENGTH_LABELS[$en['strength']] ?></span>
              <?php endif; ?>
              <?php if ($en['reused']): ?><span class="badge badge-warn">Reused</span><?php endif; ?>
              </span>
              <span class="entry-time"><?= time_ago($en['updated_at']) ?></span>
            </div>
            <div class="entry-actions">
              <?php if ($en['url']): ?>
                <a class="icon-btn" href="<?= e($en['url']) ?>" target="_blank" rel="noopener noreferrer" title="Open website" aria-label="Open website"><i class="fa fa-external-link"></i></a>
              <?php endif; ?>
              <button type="button" class="icon-btn" data-edit title="Edit" aria-label="Edit"><i class="fa fa-pencil"></i></button>
              <button type="button" class="icon-btn icon-btn-danger" data-delete title="Delete" aria-label="Delete"><i class="fa fa-trash-o"></i></button>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="no-results" data-no-results hidden>No logins match your search.</p>
    </section>
  <?php endif; ?>
</div>

<!-- Add / edit dialog -->
<dialog class="dialog" id="entry-dialog">
  <form method="post" class="form-stack" data-entry-form>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="">
    <div class="dialog-head">
      <h2 data-entry-title>Add password</h2>
      <button type="button" class="icon-btn" data-close aria-label="Close"><i class="fa fa-times"></i></button>
    </div>
    <label class="field">
      <span>Name</span>
      <input type="text" name="website" maxlength="255" placeholder="e.g. Gmail" required>
    </label>
    <label class="field">
      <span>Website address <em>(optional)</em></span>
      <input type="text" name="url" maxlength="500" placeholder="e.g. mail.google.com">
    </label>
    <label class="field">
      <span>Username or email</span>
      <input type="text" name="username" maxlength="255" autocomplete="off" required>
    </label>
    <label class="field">
      <span>Password</span>
      <span class="input-group">
        <input type="password" name="password" autocomplete="new-password" required data-strength-input>
        <button type="button" class="icon-btn" data-toggle-visibility aria-label="Show password"><i class="fa fa-eye"></i></button>
        <button type="button" class="btn btn-soft btn-sm" data-fill-generated title="Generate a strong password"><i class="fa fa-magic"></i> Generate</button>
      </span>
      <span class="strength" data-strength-meter><span class="strength-bar"><i></i></span><span class="strength-label"></span></span>
    </label>
    <label class="field">
      <span>Notes <em>(optional, encrypted)</em></span>
      <textarea name="notes" rows="3" maxlength="2000" placeholder="Security questions, recovery codes, PINs…"></textarea>
    </label>
    <div class="dialog-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Save</button>
    </div>
  </form>
</dialog>

<!-- Delete confirmation -->
<dialog class="dialog dialog-sm" id="delete-dialog">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="">
    <div class="dialog-icon danger"><i class="fa fa-trash-o"></i></div>
    <h2>Delete this login?</h2>
    <p class="muted">The saved password for <strong data-delete-name></strong> will be permanently removed.</p>
    <div class="dialog-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button type="submit" class="btn btn-danger">Delete</button>
    </div>
  </form>
</dialog>
<?php page_end(['variant' => 'app']); ?>

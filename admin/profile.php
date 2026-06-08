<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

admin_require_auth();

$adminId = Auth::adminId();
$error = '';

$stmt = $pdo->prepare(
    'SELECT id, username, email, display_name, role, created_at
     FROM admins WHERE id = ? LIMIT 1'
);
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    Auth::logout();
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($displayName === '') {
        $error = 'Display name is required.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $dup = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1');
        $dup->execute([$email, $adminId]);
        if ($dup->fetch()) {
            $error = 'That email is already used by another account.';
        } else {
            $pdo->prepare('UPDATE admins SET display_name = ?, email = ? WHERE id = ?')
                ->execute([$displayName, $email, $adminId]);
            $_SESSION['admin_name'] = $displayName;
            admin_log_activity($pdo, 'update', 'profile', $adminId, 'Profile updated');
            admin_flash_set('success', 'Profile saved.');
            redirect('profile.php');
        }
    }
}

$admin['display_name'] = trim((string) ($_POST['display_name'] ?? $admin['display_name']));
$admin['email'] = trim((string) ($_POST['email'] ?? $admin['email']));

$lastActivity = null;
try {
    $act = $pdo->prepare(
        'SELECT action, entity, detail, created_at
         FROM admin_activity WHERE admin_id = ? ORDER BY created_at DESC LIMIT 1'
    );
    $act->execute([$adminId]);
    $lastActivity = $act->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$appUrl = rtrim((string) (app_config('app_url') ?? ''), '/');
if ($appUrl === '') {
    $appUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . admin_base_path();
}
$adminLoginUrl = rtrim($appUrl, '/') . admin_base_path() . 'admin/login.php';
$publicSiteUrl = rtrim($appUrl, '/') . admin_base_path() . 'index.html';

$pageTitle = 'My profile';
$pageDescription = 'Your admin account details and sign-in information.';
$activeNav = 'profile';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-profile-layout">
  <div class="adm-card adm-glass adm-profile-card">
    <div class="adm-profile-head">
      <span class="adm-profile-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $admin['display_name'], 0, 1))) ?></span>
      <div>
        <h2><?= e((string) $admin['display_name']) ?></h2>
        <p class="adm-hint">Signed in as <strong><?= e((string) $admin['username']) ?></strong></p>
      </div>
    </div>

    <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>

    <form method="post" class="adm-settings-grid">
      <?= csrf_field() ?>
      <div class="adm-settings-section">
        <h2>Account details</h2>
        <p class="adm-hint">Update how your name appears in the admin panel. Username stays fixed for sign-in stability.</p>
        <div class="adm-field">
          <label for="display_name">Display name</label>
          <input type="text" name="display_name" id="display_name" value="<?= e((string) $admin['display_name']) ?>" required maxlength="150" />
        </div>
        <div class="adm-field">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" value="<?= e((string) ($admin['email'] ?? '')) ?>" required maxlength="254" autocomplete="email" />
        </div>
        <div class="adm-field">
          <label>Username</label>
          <input type="text" value="<?= e((string) $admin['username']) ?>" readonly class="adm-input-readonly" />
          <p class="adm-hint">Use your username or email to sign in.</p>
        </div>
        <button type="submit" class="adm-btn adm-btn-primary">Save profile</button>
      </div>
    </form>
  </div>

  <div class="adm-profile-side">
    <div class="adm-card adm-glass">
      <h2>Sign-in &amp; delivery</h2>
      <p class="adm-hint">Keep this handy after handover. Share only with trusted studio staff.</p>
      <dl class="adm-profile-dl">
        <div>
          <dt>Admin panel URL</dt>
          <dd class="adm-profile-copy-row">
            <code class="adm-profile-code" id="adm-login-url"><?= e($adminLoginUrl) ?></code>
            <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost adm-copy-text" data-target="adm-login-url">Copy</button>
          </dd>
        </div>
        <div>
          <dt>Username</dt>
          <dd class="adm-profile-copy-row">
            <code class="adm-profile-code" id="adm-username"><?= e((string) $admin['username']) ?></code>
            <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost adm-copy-text" data-target="adm-username">Copy</button>
          </dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd class="adm-profile-copy-row">
            <code class="adm-profile-code" id="adm-email"><?= e((string) ($admin['email'] ?? '—')) ?></code>
            <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost adm-copy-text" data-target="adm-email">Copy</button>
          </dd>
        </div>
        <div>
          <dt>Public website</dt>
          <dd><a href="<?= e($publicSiteUrl) ?>" target="_blank" rel="noopener"><?= e($publicSiteUrl) ?></a></dd>
        </div>
      </dl>
      <p class="adm-hint adm-profile-note">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        Change your password after first login. Use <a href="<?= e(admin_href('change-password.php')) ?>">Password</a> or <a href="<?= e(admin_href('forgot-password.php')) ?>">Forgot password</a> if needed.
      </p>
    </div>

    <div class="adm-card adm-glass">
      <h2>Account info</h2>
      <dl class="adm-profile-dl">
        <div>
          <dt>Role</dt>
          <dd><?= e(ucfirst((string) ($admin['role'] ?? 'admin'))) ?></dd>
        </div>
        <div>
          <dt>Member since</dt>
          <dd><?= e(date('j M Y', strtotime((string) $admin['created_at']))) ?></dd>
        </div>
        <div>
          <dt>Last activity</dt>
          <dd>
            <?php if ($lastActivity): ?>
              <?= e(date('j M Y, g:i A', strtotime((string) $lastActivity['created_at']))) ?>
              <span class="adm-hint">— <?= e((string) $lastActivity['action']) ?><?= ($lastActivity['detail'] ?? '') !== '' ? ': ' . e((string) $lastActivity['detail']) : '' ?></span>
            <?php else: ?>
              <span class="adm-hint">No activity logged yet</span>
            <?php endif; ?>
          </dd>
        </div>
      </dl>
      <a href="<?= e(admin_href('change-password.php')) ?>" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-key"></i> Change password</a>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.adm-copy-text').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var el = document.getElementById(btn.getAttribute('data-target'));
    if (!el) return;
    var text = el.textContent.trim();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        btn.textContent = 'Copied';
        setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
      });
    }
  });
});
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

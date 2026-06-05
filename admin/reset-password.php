<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::isAuthenticated()) {
    redirect('index.php');
}

$token = trim($_GET['token'] ?? '');
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $token = trim($_POST['token'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($token === '') {
        $error = 'Invalid reset link.';
    } else {
        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare(
            'SELECT id, admin_id FROM password_reset_tokens
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $error = 'This reset link has expired or was already used.';
        } else {
            Auth::updatePassword($pdo, (int) $row['admin_id'], $password);
            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
            $done = true;
        }
    }
}

$brand = admin_brand();
?>
<!DOCTYPE html>
<html lang="en" data-adm-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Reset password · <?= e($brand['short']) ?> Studio</title>
  <link rel="stylesheet" href="assets/admin.css?v=5" />
</head>
<body>
  <div class="adm-login-wrap">
    <div class="adm-login-card">
      <?php if ($done): ?>
        <h1>Password updated</h1>
        <p class="adm-alert adm-alert-success">You can now sign in with your new password.</p>
        <a href="login.php" class="adm-btn adm-btn-primary" style="display:block;text-align:center;">Sign in</a>
      <?php else: ?>
        <h1>Set new password</h1>
        <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
        <?php if ($token === ''): ?>
          <p class="adm-alert adm-alert-error">Missing reset token. Use the link from forgot password.</p>
          <p><a href="forgot-password.php" class="adm-btn adm-btn-ghost adm-btn-sm">Request a new link</a></p>
        <?php else: ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>" />
            <div class="adm-field"><label>New password</label><input type="password" name="password" required minlength="8" /></div>
            <div class="adm-field"><label>Confirm password</label><input type="password" name="confirm_password" required minlength="8" /></div>
            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Save password</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::isAuthenticated()) {
    redirect('index.php');
}

$message = '';
$error = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $email = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$email, $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->prepare('INSERT INTO password_reset_tokens (admin_id, token_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([(int) $admin['id'], $hash, $expires]);
        $base = rtrim((string) (app_config('app_url') ?? ''), '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $resetLink = rtrim($base, '/') . admin_base_path() . 'admin/reset-password.php?token=' . urlencode($token);
        $message = 'If that email is registered, use the reset link below (valid for 1 hour).';
    } else {
        $message = 'If that email is registered, use the reset link below (valid for 1 hour).';
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
  <title>Forgot password · <?= e($brand['short']) ?> Studio</title>
  <link rel="stylesheet" href="assets/admin.css?v=5" />
</head>
<body>
  <div class="adm-login-wrap">
    <div class="adm-login-card">
      <h1>Forgot password</h1>
      <p style="color:var(--adm-muted);">Enter your admin email. We will show a one-time reset link.</p>
      <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
      <?php if ($message): ?>
        <p class="adm-alert adm-alert-success"><?= e($message) ?></p>
        <?php if ($resetLink): ?>
          <p><a href="<?= e($resetLink) ?>" class="adm-btn adm-btn-primary" style="display:inline-flex;">Reset your password</a></p>
          <p class="adm-hint" style="word-break:break-all;"><?= e($resetLink) ?></p>
        <?php endif; ?>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="adm-field"><label>Email</label><input type="email" name="email" required /></div>
        <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Send reset link</button>
      </form>
      <p style="margin-top:1rem;text-align:center;"><a href="login.php" class="adm-btn adm-btn-ghost adm-btn-sm">Back to sign in</a></p>
    </div>
  </div>
</body>
</html>

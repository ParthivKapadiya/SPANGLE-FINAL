<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::isAuthenticated()) {
    redirect('index.php');
}

$error = '';
$notice = isset($_GET['signedout']) ? 'You have been signed out.' : '';
if (isset($_GET['timeout'])) {
    $error = 'Session expired. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (Auth::isLoginLocked()) {
        $error = 'Too many attempts. Try again later.';
    } elseif (Auth::login($pdo, $email, $password)) {
        redirect('index.php');
    } else {
        $error = 'Email or password is incorrect.';
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
  <title>Sign in · <?= e($brand['short']) ?> Studio</title>
  <link rel="stylesheet" href="assets/admin.css?v=5" />
</head>
<body>
  <div class="adm-login-wrap">
    <div class="adm-login-card">
      <p class="adm-login-eyebrow"><?= e($brand['name']) ?></p>
      <h1 style="margin:0 0 0.5rem;"><?= e($brand['short']) ?> Studio</h1>
      <p style="color:var(--adm-muted);margin:0 0 1.5rem;">Sign in to manage projects, inquiries, and homepage content.</p>
      <?php if ($notice): ?><p class="adm-alert adm-alert-success"><?= e($notice) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="adm-field">
          <label for="email">Email or username</label>
          <input type="text" id="email" name="email" required autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="admin" />
        </div>
        <div class="adm-field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required autocomplete="current-password" />
        </div>
        <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Sign in</button>
      </form>
      <p style="margin-top:1rem;text-align:center;"><a href="forgot-password.php" class="adm-btn adm-btn-ghost adm-btn-sm">Forgot password?</a></p>
    </div>
  </div>
</body>
</html>

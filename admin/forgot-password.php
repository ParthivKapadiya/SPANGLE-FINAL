<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/passwordResetMail.php';

if (Auth::isAuthenticated()) {
    redirect('index.php');
}

$message = '';
$error = '';
$resetLink = '';
$submittedEmail = '';
$submitted = false;
$emailSent = false;
$smtpConfigured = password_reset_smtp_available();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $submitted = true;
    $submittedEmail = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare(
        'SELECT id, email, display_name FROM admins WHERE email = ? OR username = ? LIMIT 1'
    );
    $stmt->execute([$submittedEmail, $submittedEmail]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->prepare('INSERT INTO password_reset_tokens (admin_id, token_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([(int) $admin['id'], $hash, $expires]);

        $resetLink = password_reset_build_url($pdo, $token);
        $emailSent = password_reset_send_email($pdo, $admin, $resetLink);

        if ($emailSent) {
            $message = password_reset_email_sent_message();
            $resetLink = '';
        } elseif (password_reset_should_show_link_on_screen(false, $smtpConfigured)) {
            $message = 'Your reset link is ready. It expires in 1 hour.';
            if ($smtpConfigured) {
                $error = 'Email could not be sent. Use the link below, or check config/mail.local.php.';
            }
        } else {
            $error = 'We could not send the reset email. Check SMTP settings in config/mail.local.php or contact your developer.';
            $resetLink = '';
            $message = password_reset_email_sent_message();
        }
    } else {
        $message = $smtpConfigured
            ? password_reset_email_sent_message()
            : 'If that account exists, check the email you entered and try again.';
    }
}

$brand = admin_brand();
$showLink = $resetLink !== '';
?>
<!DOCTYPE html>
<html lang="en" data-adm-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Forgot password · <?= e($brand['short']) ?> Studio</title>
  <link rel="stylesheet" href="assets/admin.css?v=16" />
</head>
<body>
  <div class="adm-login-wrap">
    <div class="adm-login-card">
      <h1>Forgot password</h1>

      <?php if ($showLink): ?>
        <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
        <p class="adm-alert adm-alert-success"><?= e($message) ?></p>
        <p class="adm-hint">Use the button below to set a new password. This link works once and expires in 1 hour.</p>
        <p style="margin:1rem 0;">
          <a href="<?= e($resetLink) ?>" class="adm-btn adm-btn-primary" style="display:inline-flex;width:100%;justify-content:center;">Reset your password</a>
        </p>
        <div class="adm-profile-copy-row" style="margin-bottom:1rem;">
          <code class="adm-profile-code" id="adm-reset-link" style="flex:1;"><?= e($resetLink) ?></code>
          <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost adm-copy-text" data-target="adm-reset-link">Copy link</button>
        </div>
        <p class="adm-hint adm-profile-note" style="margin-bottom:1rem;">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          SMTP is not configured, so the link is shown here for local testing. On live hosting with <code>config/mail.local.php</code>, it is sent by email instead.
        </p>
        <p style="text-align:center;">
          <a href="login.php" class="adm-btn adm-btn-ghost adm-btn-sm">Back to sign in</a>
        </p>
      <?php elseif ($submitted && ($message !== '' || $error !== '')): ?>
        <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
        <?php if ($message): ?><p class="adm-alert adm-alert-success"><?= e($message) ?></p><?php endif; ?>
        <?php if ($emailSent): ?>
          <p class="adm-hint">Open the email inbox for your admin account. The reset link expires in 1 hour.</p>
        <?php endif; ?>
        <p style="margin-top:1rem;text-align:center;">
          <a href="login.php" class="adm-btn adm-btn-ghost adm-btn-sm">Back to sign in</a>
          <?php if (!$emailSent && $error === ''): ?>
            · <a href="forgot-password.php" class="adm-btn adm-btn-ghost adm-btn-sm">Try again</a>
          <?php endif; ?>
        </p>
      <?php else: ?>
        <p style="color:var(--adm-muted);">
          Enter your admin email or username.
          <?= $smtpConfigured ? 'We will email a one-time reset link to the registered address.' : 'We will provide a one-time reset link.' ?>
        </p>
        <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="adm-field">
            <label for="email">Email or username</label>
            <input type="text" name="email" id="email" value="<?= e($submittedEmail) ?>" required autocomplete="username" />
          </div>
          <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">Send reset link</button>
        </form>
        <p style="margin-top:1rem;text-align:center;"><a href="login.php" class="adm-btn adm-btn-ghost adm-btn-sm">Back to sign in</a></p>
      <?php endif; ?>
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
          setTimeout(function () { btn.textContent = 'Copy link'; }, 1500);
        });
      }
    });
  });
  </script>
</body>
</html>

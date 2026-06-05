<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

admin_require_auth();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([Auth::adminId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            Auth::updatePassword($pdo, Auth::adminId(), $new);
            admin_flash_set('success', 'Password updated successfully.');
            redirect('change-password.php');
        }
    }
}

$pageTitle = 'Change password';
$pageDescription = 'Update your sign-in password.';
$activeNav = 'password';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-card" style="max-width:480px;">
  <?php if ($error): ?><p class="adm-alert adm-alert-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="adm-field"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password" /></div>
    <div class="adm-field"><label>New password</label><input type="password" name="new_password" required minlength="8" autocomplete="new-password" /></div>
    <div class="adm-field"><label>Confirm new password</label><input type="password" name="confirm_password" required minlength="8" autocomplete="new-password" /></div>
    <button type="submit" class="adm-btn adm-btn-primary">Update password</button>
  </form>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

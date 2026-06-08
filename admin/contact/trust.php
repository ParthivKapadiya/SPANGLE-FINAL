<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('trust');
extract(contact_admin_page_vars($section));

$keys = [];
for ($i = 1; $i <= 6; $i++) {
    $keys[] = 'contact_trust_' . $i . '_title';
    $keys[] = 'contact_trust_' . $i . '_text';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-trust', null, 'Contact trust section updated');
    contact_admin_sync_and_redirect('trust.php', 'Trust section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Six trust cards</h2>
    <?php for ($i = 1; $i <= 6; $i++): ?>
      <?php home_admin_render_field('contact_trust_' . $i . '_title', 'Card ' . $i . ' — title', $s); ?>
      <?php home_admin_render_field('contact_trust_' . $i . '_text', 'Card ' . $i . ' — text', $s, 'textarea'); ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save trust section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

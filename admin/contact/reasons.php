<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('reasons');
extract(contact_admin_page_vars($section));

$keys = ['contact_reasons'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-reasons', null, 'Contact reasons section updated');
    contact_admin_sync_and_redirect('reasons.php', 'Reasons section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Why clients reach out</h2>
    <?php home_admin_render_field('contact_reasons', 'Reasons (one per line)', $s, 'textarea'); ?>
    <p class="adm-hint">Each line becomes one card in the “Why clients contact us” grid.</p>
  </div>
  <?php home_admin_render_save('Save reasons section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

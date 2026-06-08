<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('whatsapp');
extract(contact_admin_page_vars($section));

$keys = ['contact_wa_lead'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-whatsapp', null, 'Contact WhatsApp section updated');
    contact_admin_sync_and_redirect('whatsapp.php', 'WhatsApp section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>WhatsApp section</h2>
    <?php home_admin_render_field('contact_wa_lead', 'Intro text', $s, 'textarea'); ?>
    <?php home_admin_card_link('settings.php', 'WhatsApp number', 'Set the WhatsApp link in Global settings.'); ?>
  </div>
  <?php home_admin_render_save('Save WhatsApp section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

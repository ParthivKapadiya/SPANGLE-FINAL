<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('contact');
extract(home_admin_page_vars($section));

$keys = ['contact_section_title', 'contact_section_lead'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-contact', null, 'Home contact section updated');
    home_admin_sync_and_redirect('contact.php', 'Contact section saved.');
}

$s = settings_get_many($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Contact block on home page</h2>
    <?php
    home_admin_render_field('contact_section_title', 'Section title', $s);
    home_admin_render_field('contact_section_lead', 'Intro paragraph', $s, 'textarea');
    ?>
    <?php home_admin_card_link('settings.php', 'Phone, email & address', 'Managed in Global settings.'); ?>
    <?php home_admin_card_link('contact-page.php', 'Full contact page', 'Edit the dedicated Contact page content.'); ?>
  </div>
  <?php home_admin_render_save('Save contact section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('services');
extract(home_admin_page_vars($section));

$keys = [
    'home_capabilities_eyebrow', 'home_capabilities_title', 'home_capabilities_intro',
    'home_link_services_text', 'home_link_services_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-services', null, 'Home services section updated');
    home_admin_sync_and_redirect('services.php', 'Services section saved.');
}

$s = settings_get_many($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('home_capabilities_eyebrow', 'Small label', $s);
    home_admin_render_field('home_capabilities_title', 'Title', $s);
    home_admin_render_field('home_capabilities_intro', 'Intro paragraph', $s, 'textarea');
    home_admin_render_link_row('home_link_services_text', 'home_link_services_url', $s);
    ?>
    <?php home_admin_card_link('services.php', 'Manage service cards', 'Add, edit, or reorder the service blocks shown here.'); ?>
  </div>
  <?php home_admin_render_save('Save services section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

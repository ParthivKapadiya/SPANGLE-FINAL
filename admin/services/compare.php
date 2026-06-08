<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('compare');
extract(services_admin_page_vars($section));

$keys = ['services_compare_eyebrow', 'services_compare_title', 'services_compare_intro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-compare', null, 'Services compare section updated');
    services_admin_sync_and_redirect('compare.php', 'Compare section saved.');
}

$s = services_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('services_compare_eyebrow', 'Small label', $s);
    home_admin_render_field('services_compare_title', 'Title', $s);
    home_admin_render_field('services_compare_intro', 'Intro paragraph', $s, 'textarea');
    ?>
    <?php home_admin_card_link('studio/compare.php', 'Edit compare bullets', 'Bullet lists are shared with Studio → The difference.'); ?>
  </div>
  <?php home_admin_render_save('Save compare section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

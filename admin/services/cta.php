<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('cta');
extract(services_admin_page_vars($section));

$keys = [
    'services_cta_eyebrow', 'services_cta_title', 'services_cta_sub', 'services_cta_lead',
    'services_cta_btn_text', 'services_cta_btn_url',
    'services_cta_btn2_text', 'services_cta_btn2_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-cta', null, 'Services CTA updated');
    services_admin_sync_and_redirect('cta.php', 'Call to action saved.');
}

$s = services_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Bottom invitation</h2>
    <?php
    home_admin_render_field('services_cta_eyebrow', 'Small label', $s);
    home_admin_render_field('services_cta_title', 'Headline', $s);
    home_admin_render_field('services_cta_sub', 'Disciplines line', $s);
    home_admin_render_field('services_cta_lead', 'Supporting text', $s, 'textarea');
    home_admin_render_link_row('services_cta_btn_text', 'services_cta_btn_url', $s, 'Primary button text', 'Primary button link');
    home_admin_render_link_row('services_cta_btn2_text', 'services_cta_btn2_url', $s, 'Secondary button text', 'Secondary button link');
    ?>
  </div>
  <?php home_admin_render_save('Save call to action'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';

admin_require_auth();

$section = studio_admin_require_section('cta');
extract(studio_admin_page_vars($section));

$keys = [
    'studio_cta_eyebrow', 'studio_cta_title', 'studio_cta_sub',
    'studio_cta_text', 'studio_cta_btn_text', 'studio_cta_btn_url',
    'studio_cta_btn2_text', 'studio_cta_btn2_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'studio-cta', null, 'Studio CTA updated');
    studio_admin_sync_and_redirect('cta.php', 'Call to action saved.');
}

$s = studio_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Bottom invitation</h2>
    <?php
    home_admin_render_field('studio_cta_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_cta_title', 'Headline', $s);
    home_admin_render_field('studio_cta_sub', 'Subtitle line', $s);
    home_admin_render_field('studio_cta_text', 'Supporting text', $s, 'textarea');
    home_admin_render_link_row('studio_cta_btn_text', 'studio_cta_btn_url', $s, 'Primary button text', 'Primary button link');
    home_admin_render_link_row('studio_cta_btn2_text', 'studio_cta_btn2_url', $s, 'Secondary button text', 'Secondary button link');
    ?>
  </div>
  <?php home_admin_render_save('Save call to action'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

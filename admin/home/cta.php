<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('cta');
extract(home_admin_page_vars($section));

$keys = [
    'home_cta_eyebrow', 'home_cta_title', 'home_cta_lead', 'home_cta_sub',
    'home_cta_btn_text', 'home_cta_btn_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-cta', null, 'Home CTA section updated');
    home_admin_sync_and_redirect('cta.php', 'Call to action saved.');
}

$s = settings_get_many($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Bottom invitation</h2>
    <?php
    home_admin_render_field('home_cta_eyebrow', 'Small label', $s);
    home_admin_render_field('home_cta_title', 'Main heading', $s);
    home_admin_render_field('home_cta_lead', 'Supporting text', $s, 'textarea');
    home_admin_render_field('home_cta_sub', 'Disciplines line', $s);
    home_admin_render_field('home_cta_btn_text', 'Button text', $s);
    home_admin_render_field('home_cta_btn_url', 'Button link', $s);
    ?>
  </div>
  <?php home_admin_render_save('Save call to action'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

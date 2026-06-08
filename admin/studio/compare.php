<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';

admin_require_auth();

$section = studio_admin_require_section('compare');
extract(studio_admin_page_vars($section));

$keys = [
    'studio_compare_eyebrow', 'studio_compare_title',
    'studio_compare_us_title', 'studio_compare_them_title',
];
for ($i = 1; $i <= 5; $i++) {
    $keys[] = 'studio_compare_us_' . $i;
    $keys[] = 'studio_compare_them_' . $i;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'studio-compare', null, 'Studio compare section updated');
    studio_admin_sync_and_redirect('compare.php', 'Compare section saved.');
}

$s = studio_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('studio_compare_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_compare_title', 'Main title', $s);
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Archevo Design column</h2>
    <?php home_admin_render_field('studio_compare_us_title', 'Column heading', $s); ?>
    <p class="adm-hint" style="margin-top:0.75rem;">Five bullet points (checkmarks on the website)</p>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <?php home_admin_render_field('studio_compare_us_' . $i, 'Point ' . $i, $s); ?>
    <?php endfor; ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Traditional approach column</h2>
    <?php home_admin_render_field('studio_compare_them_title', 'Column heading', $s); ?>
    <p class="adm-hint" style="margin-top:0.75rem;">Five bullet points (dashes on the website)</p>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <?php home_admin_render_field('studio_compare_them_' . $i, 'Point ' . $i, $s); ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save compare section', true); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

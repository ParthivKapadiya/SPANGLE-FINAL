<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();

$section = services_admin_require_section('impact');
extract(services_admin_page_vars($section));

$keys = ['services_impact_eyebrow', 'services_impact_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    services_admin_save_image_setting($pdo, $appConfig, 'services_impact_image', 'services_impact_image_file');
    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'services-impact', null, 'Services impact section updated');
    services_admin_sync_and_redirect('impact.php', 'Impact section saved.');
}

$s = services_admin_load_settings($pdo, array_merge($keys, ['services_impact_image']));
$stats = home_admin_hero_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('services_impact_eyebrow', 'Small label', $s);
    home_admin_render_field('services_impact_title', 'Title', $s);
    ?>
    <div class="adm-field">
      <label>Background image</label>
      <?php if (!empty($s['services_impact_image'])): ?>
        <p><img src="../../<?= e($s['services_impact_image']) ?>" alt="" style="max-width:320px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="services_impact_image_file" accept="image/*" />
      <p class="adm-hint">Optional — falls back to the services hero image if empty.</p>
    </div>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Statistics</h2>
    <p class="adm-hint">Shared with <a href="<?= e(admin_href('home/hero.php')) ?>">Home → Hero</a>. First four stats appear in this section.</p>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="Value" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="Label" />
      </div>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save impact section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

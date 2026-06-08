<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();

$section = studio_admin_require_section('hero');
extract(studio_admin_page_vars($section));

$keys = ['studio_kicker', 'studio_title', 'studio_lead'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    studio_admin_save_image_setting($pdo, $appConfig, 'studio_hero_image', 'studio_hero_image_file');
    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'studio-hero', null, 'Studio hero updated');
    studio_admin_sync_and_redirect('hero.php', 'Hero section saved.');
}

$s = studio_admin_load_settings($pdo, array_merge($keys, ['studio_hero_image']));
$stats = home_admin_hero_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Hero banner</h2>
    <?php
    home_admin_render_field('studio_kicker', 'Small label', $s);
    home_admin_render_field('studio_title', 'Main heading', $s);
    home_admin_render_field('studio_lead', 'Intro text', $s, 'textarea');
    ?>
    <div class="adm-field">
      <label>Background image</label>
      <?php if (!empty($s['studio_hero_image'])): ?>
        <p><img src="../../<?= e($s['studio_hero_image']) ?>" alt="" style="max-width:320px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="studio_hero_image_file" accept="image/*" />
    </div>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Credential statistics</h2>
    <p class="adm-hint">Four stats shown below the intro. These are shared with <a href="<?= e(admin_href('home/hero.php')) ?>">Home → Hero</a> — editing here updates both pages.</p>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="e.g. 150+" aria-label="Number" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="e.g. Projects Delivered" aria-label="Label" />
      </div>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save hero section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

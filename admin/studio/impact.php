<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';

admin_require_auth();

$section = studio_admin_require_section('impact');
extract(studio_admin_page_vars($section));

$keys = ['studio_impact_eyebrow', 'studio_impact_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'studio-impact', null, 'Studio impact section updated');
    studio_admin_sync_and_redirect('impact.php', 'Impact section saved.');
}

$s = studio_admin_load_settings($pdo, $keys);
$stats = home_admin_hero_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('studio_impact_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_impact_title', 'Title', $s);
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Statistics</h2>
    <p class="adm-hint">Shared with <a href="<?= e(admin_href('home/hero.php')) ?>">Home → Hero</a> — changes here update both pages.</p>
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

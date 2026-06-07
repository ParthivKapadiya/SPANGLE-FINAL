<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('impact');
extract(home_admin_page_vars($section));

$keys = ['home_impact_eyebrow', 'home_impact_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'home-impact', null, 'Home impact section updated');
    home_admin_sync_and_redirect('impact.php', 'Impact section saved.');
}

$s = settings_get_many($pdo, $keys);
$stats = home_admin_ensure_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('home_impact_eyebrow', 'Small label', $s);
    home_admin_render_field('home_impact_title', 'Main heading', $s);
    ?>
    <p class="adm-hint">Statistics below use the same values as the hero glass cards.</p>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Statistics</h2>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="Value" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="Label" />
      </div>
    <?php endforeach; ?>
    <p class="adm-hint"><a href="hero.php" class="adm-btn adm-btn-sm adm-btn-ghost">Also editable in Hero section</a></p>
  </div>
  <?php home_admin_render_save('Save impact section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

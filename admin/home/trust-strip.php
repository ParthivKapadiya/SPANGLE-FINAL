<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('trust-strip');
extract(home_admin_page_vars($section));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'home-trust-strip', null, 'Home trust strip updated');
    home_admin_sync_and_redirect('trust-strip.php', 'Trust strip saved.');
}

$stats = home_admin_ensure_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<div class="adm-card adm-settings-section adm-glass">
  <h2>About this section</h2>
  <p class="adm-hint">The trust strip is the scrolling bar of credentials directly below the hero. It uses the same statistics as the hero glass cards.</p>
  <p class="adm-hint"><a href="hero.php" class="adm-btn adm-btn-sm adm-btn-ghost">Edit hero statistics</a></p>
</div>

<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Statistics shown in trust strip</h2>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="Value" aria-label="Number" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="Label" aria-label="Label" />
      </div>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save trust strip'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

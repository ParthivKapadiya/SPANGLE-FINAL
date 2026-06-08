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

$stats = home_admin_trust_strip_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<div class="adm-card adm-settings-section adm-glass">
  <h2>About this section</h2>
  <p class="adm-hint">The trust strip is the scrolling bar of credentials directly below the hero. It shows six items — four statistics (shared with the hero glass panel) plus two credential labels.</p>
  <p class="adm-hint"><a href="hero.php" class="adm-btn adm-btn-sm adm-btn-ghost">Edit hero statistics</a></p>
</div>

<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Items shown in trust strip</h2>
    <p class="adm-hint">Items 1–4 are numeric statistics (also used in the hero). Items 5–6 are credential labels with icon only.</p>
    <?php foreach ($stats as $i => $stat): ?>
      <?php if (home_admin_is_trust_credential($i)): ?>
        <div class="adm-field adm-field-row">
          <input type="hidden" name="stats[<?= (int) $stat['id'] ?>][value]" value="" />
          <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="Credential label" aria-label="Item <?= $i + 1 ?> label" />
          <input type="text" name="stats[<?= (int) $stat['id'] ?>][icon]" value="<?= e($stat['stat_icon'] ?? '') ?>" placeholder="Icon class" aria-label="Item <?= $i + 1 ?> icon" title="Font Awesome class, e.g. fa-solid fa-landmark" />
        </div>
      <?php else: ?>
        <div class="adm-field adm-field-row adm-field-row--3">
          <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="Value" aria-label="Item <?= $i + 1 ?> value" />
          <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="Label" aria-label="Item <?= $i + 1 ?> label" />
          <input type="text" name="stats[<?= (int) $stat['id'] ?>][icon]" value="<?= e($stat['stat_icon'] ?? '') ?>" placeholder="Icon class" aria-label="Item <?= $i + 1 ?> icon" title="Font Awesome class, e.g. fa-solid fa-chart-line" />
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save trust strip'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

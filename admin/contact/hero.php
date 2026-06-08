<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();

$section = contact_admin_require_section('hero');
extract(contact_admin_page_vars($section));

$keys = ['contact_hero_kicker', 'contact_hero_title', 'contact_hero_lead'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    contact_admin_save_image_setting($pdo, $appConfig, 'contact_hero_image', 'contact_hero_image_file');
    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'contact-hero', null, 'Contact hero updated');
    contact_admin_sync_and_redirect('hero.php', 'Hero section saved.');
}

$s = contact_admin_load_settings($pdo, array_merge($keys, ['contact_hero_image']));
$stats = home_admin_hero_stats($pdo);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Hero banner</h2>
    <?php
    home_admin_render_field('contact_hero_kicker', 'Small label', $s);
    home_admin_render_field('contact_hero_title', 'Main heading', $s);
    home_admin_render_field('contact_hero_lead', 'Intro text', $s, 'textarea');
    ?>
    <div class="adm-field">
      <label>Background image</label>
      <?php if (!empty($s['contact_hero_image'])): ?>
        <p><img src="../../<?= e($s['contact_hero_image']) ?>" alt="" style="max-width:320px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="contact_hero_image_file" accept="image/*" />
    </div>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Credential statistics</h2>
    <p class="adm-hint">Four stats below the intro. Shared with <a href="<?= e(admin_href('home/hero.php')) ?>">Home → Hero</a>.</p>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="Value" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="Label" />
      </div>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save hero section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

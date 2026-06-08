<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();

$section = studio_admin_require_section('philosophy');
extract(studio_admin_page_vars($section));

$pillarDefaults = cms_studio_philosophy_pillar_defaults();
$keys = [
    'studio_philosophy_eyebrow', 'studio_philosophy_title', 'studio_pullquote',
];
for ($i = 1; $i <= 6; $i++) {
    $keys[] = 'studio_pillar_' . $i . '_title';
    $keys[] = 'studio_pillar_' . $i . '_text';
    $keys[] = 'studio_pillar_' . $i . '_icon';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    studio_admin_save_image_setting($pdo, $appConfig, 'studio_philosophy_image', 'studio_philosophy_image_file');
    admin_log_activity($pdo, 'save', 'studio-philosophy', null, 'Studio philosophy updated');
    studio_admin_sync_and_redirect('philosophy.php', 'Philosophy section saved.');
}

$s = studio_admin_load_settings($pdo, array_merge($keys, ['studio_philosophy_image']));

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Philosophy block</h2>
    <?php
    home_admin_render_field('studio_philosophy_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_philosophy_title', 'Title', $s);
    home_admin_render_field('studio_pullquote', 'Pull quote', $s, 'textarea');
    ?>
    <div class="adm-field">
      <label>Philosophy image</label>
      <?php if (!empty($s['studio_philosophy_image'])): ?>
        <p><img src="../../<?= e($s['studio_philosophy_image']) ?>" alt="" style="max-width:320px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="studio_philosophy_image_file" accept="image/*" />
    </div>
    <p class="adm-hint">Timeline paragraphs for milestones 2 and 5 are edited in <a href="<?= e(admin_href('studio/story.php')) ?>">Our story</a>. Value cards are managed in <a href="values.php">Our values</a>.</p>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Six pillar cards</h2>
    <p class="adm-hint">The grid below the philosophy image — title, description, and Font Awesome icon class for each card.</p>
    <?php foreach ($pillarDefaults as $i => $pillar): ?>
      <h3 style="margin:1rem 0 0.5rem;font-size:0.95rem;">Card <?= $i ?></h3>
      <div class="adm-field adm-field-row adm-field-row--3">
        <input type="text" name="studio_pillar_<?= $i ?>_title" id="studio_pillar_<?= $i ?>_title" value="<?= e($s['studio_pillar_' . $i . '_title'] ?? $pillar['title']) ?>" placeholder="Title" aria-label="Card <?= $i ?> title" />
        <textarea name="studio_pillar_<?= $i ?>_text" id="studio_pillar_<?= $i ?>_text" rows="2" placeholder="Description" aria-label="Card <?= $i ?> description"><?= e($s['studio_pillar_' . $i . '_text'] ?? $pillar['text']) ?></textarea>
        <input type="text" name="studio_pillar_<?= $i ?>_icon" id="studio_pillar_<?= $i ?>_icon" value="<?= e($s['studio_pillar_' . $i . '_icon'] ?? $pillar['icon']) ?>" placeholder="Icon class" aria-label="Card <?= $i ?> icon" title="e.g. fa-solid fa-brain" />
      </div>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save philosophy section', true); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

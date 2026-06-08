<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();

$section = studio_admin_require_section('story');
extract(studio_admin_page_vars($section));

$keys = [
    'studio_story_eyebrow', 'studio_story_title', 'studio_story_intro',
    'studio_timeline_1_year', 'studio_timeline_1_title', 'studio_timeline_1_text',
    'studio_timeline_2_year', 'studio_timeline_2_title', 'studio_timeline_2_text',
    'studio_timeline_3_year', 'studio_timeline_3_title', 'studio_timeline_3_text',
    'studio_timeline_4_year', 'studio_timeline_4_title', 'studio_timeline_4_text',
    'studio_timeline_5_year', 'studio_timeline_5_title', 'studio_timeline_5_text',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    studio_admin_save_image_setting($pdo, $appConfig, 'studio_story_image', 'studio_story_image_file');
    setting_set($pdo, 'studio_philosophy_lead_1', trim((string) ($_POST['studio_timeline_2_text'] ?? '')));
    setting_set($pdo, 'studio_philosophy_lead_2', trim((string) ($_POST['studio_timeline_5_text'] ?? '')));
    admin_log_activity($pdo, 'save', 'studio-story', null, 'Studio story updated');
    studio_admin_sync_and_redirect('story.php', 'Story section saved.');
}

$s = studio_admin_load_settings($pdo, array_merge($keys, ['studio_story_image']));

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('studio_story_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_story_title', 'Title', $s);
    home_admin_render_field('studio_story_intro', 'Intro paragraph', $s, 'textarea');
    ?>
    <div class="adm-field">
      <label>Story photo</label>
      <?php if (!empty($s['studio_story_image'])): ?>
        <p><img src="../../<?= e($s['studio_story_image']) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="studio_story_image_file" accept="image/*" />
    </div>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Timeline milestones</h2>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <h3 style="margin:1rem 0 0.5rem;font-size:0.95rem;">Milestone <?= $i ?></h3>
      <?php
      home_admin_render_field('studio_timeline_' . $i . '_year', 'Year / label', $s);
      home_admin_render_field('studio_timeline_' . $i . '_title', 'Heading', $s);
      home_admin_render_field('studio_timeline_' . $i . '_text', 'Description', $s, 'textarea');
      ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save story section', true); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

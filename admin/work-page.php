<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();

$pageKeys = [
    'work_kicker', 'work_title', 'work_lead',
    'work_featured_eyebrow', 'work_stats_eyebrow', 'work_stats_title',
    'work_categories_eyebrow', 'work_categories_title',
    'work_testimonials_eyebrow', 'work_testimonials_title',
    'work_timeline_eyebrow', 'work_timeline_title', 'work_timeline_intro',
    'work_trust_eyebrow', 'work_trust_title',
    'work_cta_final_eyebrow', 'work_cta_final_title', 'work_cta_final_sub',
    'work_cta_final_btn_text', 'work_cta_final_btn2_text',
    'work_cta_text', 'work_cta_btn_text', 'work_cta_btn_url',
];

$labels = [
    'work_kicker' => 'Hero — small label',
    'work_title' => 'Hero — main heading (use line breaks for two lines)',
    'work_lead' => 'Hero — intro text',
    'work_featured_eyebrow' => 'Featured showcase — eyebrow',
    'work_stats_eyebrow' => 'Statistics — eyebrow',
    'work_stats_title' => 'Statistics — heading',
    'work_categories_eyebrow' => 'Categories — eyebrow',
    'work_categories_title' => 'Categories — heading',
    'work_testimonials_eyebrow' => 'Testimonials — eyebrow',
    'work_testimonials_title' => 'Testimonials — heading',
    'work_timeline_eyebrow' => 'Timeline — eyebrow',
    'work_timeline_title' => 'Timeline — heading',
    'work_timeline_intro' => 'Timeline — intro',
    'work_trust_eyebrow' => 'Trust section — eyebrow',
    'work_trust_title' => 'Trust section — heading',
    'work_cta_final_eyebrow' => 'Final CTA — eyebrow',
    'work_cta_final_title' => 'Final CTA — heading',
    'work_cta_final_sub' => 'Final CTA — subheadline',
    'work_cta_final_btn_text' => 'Final CTA — primary button',
    'work_cta_final_btn2_text' => 'Final CTA — secondary button',
    'work_cta_text' => 'Final CTA — paragraph',
    'work_cta_btn_text' => 'Primary button — link label',
    'work_cta_btn_url' => 'Primary button — URL',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($pageKeys as $key) {
        if (array_key_exists($key, $_POST)) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }
    if (!empty($_FILES['work_hero_image_file']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['work_hero_image_file']);
        if ($up['ok']) {
            setting_set($pdo, 'work_hero_image', $up['path']);
            media_register($pdo, $up['path'], basename($up['path']));
        }
    }
    content_sync_site_json($pdo);
    admin_flash_set('success', 'Work page saved.');
    redirect('work-page.php');
}

$s = settings_get_many($pdo, array_merge($pageKeys, ['work_hero_image']));

$pageTitle = 'Work page';
$pageDescription = 'Hero, section headings, and final CTA for the portfolio page.';
$activeNav = 'work-page';
require __DIR__ . '/includes/layout.php';
?>

<p class="adm-hint adm-card" style="margin-bottom:1rem;">
  Preview <a href="../work.html" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost">work.html</a>.
  Portfolio projects, images, and case study content are under
  <a href="projects.php" class="adm-btn adm-btn-sm adm-btn-ghost">Projects</a>.
  Hero statistics use <a href="home.php" class="adm-btn adm-btn-sm adm-btn-ghost">Home page stats</a>.
  Testimonials are managed under <a href="testimonials.php" class="adm-btn adm-btn-sm adm-btn-ghost">Testimonials</a>.
</p>

<form method="post" enctype="multipart/form-data" class="adm-card">
  <?= csrf_field() ?>
  <h2>Hero</h2>
  <?php foreach (['work_kicker', 'work_title', 'work_lead'] as $key): ?>
    <div class="adm-field">
      <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
      <?php if ($key === 'work_lead' || $key === 'work_title'): ?>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="<?= $key === 'work_title' ? 2 : 3 ?>"><?= e($s[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="adm-field">
    <label>Hero fallback image</label>
    <?php if (!empty($s['work_hero_image'])): ?>
      <p><img src="../<?= e($s['work_hero_image']) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p>
    <?php endif; ?>
    <input type="file" name="work_hero_image_file" accept="image/*" />
    <p class="adm-hint">Slideshow uses featured project images when available.</p>
  </div>

  <h2>Sections</h2>
  <?php
  $sectionKeys = array_diff($pageKeys, ['work_kicker', 'work_title', 'work_lead', 'work_cta_text', 'work_cta_btn_text', 'work_cta_btn_url']);
  foreach ($sectionKeys as $key):
      ?>
    <div class="adm-field">
      <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
      <?php if (str_contains($key, 'intro') || str_contains($key, 'sub')): ?>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="3"><?= e($s[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <h2>Final CTA links</h2>
  <?php foreach (['work_cta_text', 'work_cta_btn_text', 'work_cta_btn_url'] as $key): ?>
    <div class="adm-field">
      <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
      <?php if ($key === 'work_cta_text'): ?>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save work page</button>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

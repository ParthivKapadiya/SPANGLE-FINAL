<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();
cms_sync_plain_services_fields($pdo);

$pageKeys = [
    'services_kicker', 'services_title', 'services_lead',
    'services_cta_eyebrow', 'services_cta_title', 'services_cta_lead',
    'services_cta_btn_text', 'services_cta_btn_url',
];

$labels = [
    'services_kicker' => 'Top banner — small label',
    'services_title' => 'Top banner — main heading',
    'services_lead' => 'Top banner — intro text',
    'services_cta_eyebrow' => 'Bottom section — small label',
    'services_cta_title' => 'Bottom section — heading',
    'services_cta_lead' => 'Bottom section — text',
    'services_cta_btn_text' => 'Bottom button — label',
    'services_cta_btn_url' => 'Bottom button — link',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($pageKeys as $key) {
        if (array_key_exists($key, $_POST)) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }
    if (!empty($_FILES['services_hero_image_file']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['services_hero_image_file']);
        if ($up['ok']) {
            setting_set($pdo, 'services_hero_image', $up['path']);
            media_register($pdo, $up['path'], basename($up['path']));
        }
    }
    cms_sync_plain_services_fields($pdo);
    content_sync_site_json($pdo);
    admin_flash_set('success', 'Services page saved.');
    redirect('services-page.php');
}

$s = settings_get_many($pdo, array_merge($pageKeys, ['services_hero_image']));

$pageTitle = 'Services page';
$pageDescription = 'Banner, bottom invitation, and links to each service block.';
$activeNav = 'services-page';
require __DIR__ . '/includes/layout.php';
?>

<p class="adm-hint adm-card" style="margin-bottom:1rem;">
  Preview <a href="../services.html" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost">services.html</a>.
  Each service block (title, text, image) is under
  <a href="services.php" class="adm-btn adm-btn-sm adm-btn-ghost">Service blocks</a>.
  The home page services section is under <a href="home.php" class="adm-btn adm-btn-sm adm-btn-ghost">Home page</a>.
</p>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="adm-card">
    <h2>Top banner</h2>
    <?php foreach (['services_kicker', 'services_title', 'services_lead'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <?php if ($key === 'services_lead'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="3"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label>Banner background image</label>
      <?php if (!empty($s['services_hero_image'])): ?>
        <p><img src="../<?= e($s['services_hero_image']) ?>" alt="" style="max-width:280px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="services_hero_image_file" accept="image/*" />
    </div>
  </div>

  <div class="adm-card">
    <h2>Bottom call-to-action</h2>
    <?php foreach (['services_cta_eyebrow', 'services_cta_title', 'services_cta_lead', 'services_cta_btn_text', 'services_cta_btn_url'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <?php if ($key === 'services_cta_lead'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save Services page</button>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-end.php'; ?>

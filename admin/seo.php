<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsCopyKeys.php';

admin_require_auth();

$seoKeys = array_filter(
    array_keys(cms_copy_setting_keys()),
    fn ($k) => str_starts_with($k, 'seo_')
);
$seoKeys[] = 'seo_description';
$seoKeys[] = 'seo_og_image';
$seoKeys = array_values(array_unique($seoKeys));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($seoKeys as $key) {
        if (isset($_POST[$key])) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }
    if (!empty($_FILES['seo_og_image_file']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['seo_og_image_file']);
        if ($up['ok']) {
            setting_set($pdo, 'seo_og_image', $up['path']);
            require_once __DIR__ . '/includes/media.php';
            media_register($pdo, $up['path'], basename($up['path']));
        }
    }
    content_sync_site_json($pdo);
    admin_flash_set('success', 'SEO settings saved.');
    redirect('seo.php');
}

cms_seed_copy_settings($pdo);
$s = settings_get_many($pdo, $seoKeys);

$pageTitle = 'SEO';
$pageDescription = 'Page titles and descriptions for search engines and social sharing.';
$activeNav = 'seo';
require __DIR__ . '/includes/layout.php';
?>
<form method="post" enctype="multipart/form-data" class="adm-card">
  <?= csrf_field() ?>
  <h2>Global</h2>
  <div class="adm-field">
    <label>Default meta description</label>
    <textarea name="seo_description"><?= e($s['seo_description'] ?? '') ?></textarea>
  </div>
  <div class="adm-field">
    <label>Default social share image (path)</label>
    <input type="text" name="seo_og_image" value="<?= e($s['seo_og_image'] ?? '') ?>" />
    <?php if (!empty($s['seo_og_image'])): ?><p><img src="../<?= e($s['seo_og_image']) ?>" alt="" style="max-width:200px;" /></p><?php endif; ?>
    <input type="file" name="seo_og_image_file" accept="image/*" />
  </div>
  <h2>Per page</h2>
  <?php foreach ($seoKeys as $key):
      if ($key === 'seo_description' || $key === 'seo_og_image') {
          continue;
      }
      $label = cms_copy_setting_keys()[$key] ?? $key;
      ?>
    <div class="adm-field">
      <label for="<?= e($key) ?>"><?= e($label) ?></label>
      <?php if (str_contains($key, 'description')): ?>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save SEO</button>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

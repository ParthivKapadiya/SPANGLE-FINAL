<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsNavigation.php';

admin_require_auth();

$textKeys = [
    'site_name' => 'Company name',
    'tagline' => 'Tagline (line under name)',
];

$logoKeys = [
    'site_logo_light' => [
        'label' => 'Light logo (white)',
        'hint' => 'Shown at the top of the page before you scroll — on the transparent header over the hero.',
    ],
    'site_logo_dark' => [
        'label' => 'Dark logo (black)',
        'hint' => 'Shown after you scroll — on the solid white header bar.',
    ],
];

$navKeys = [];
foreach (cms_nav_item_definitions() as $def) {
    $navKeys[] = $def['setting_label'];
    $navKeys[] = $def['setting_href'];
}

$allKeys = array_merge(array_keys($textKeys), array_keys($logoKeys), $navKeys);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($textKeys as $key => $label) {
        if (!isset($_POST[$key])) {
            continue;
        }
        setting_set($pdo, $key, trim((string) $_POST[$key]));
    }
    if (isset($_POST['site_name'])) {
        setting_set($pdo, 'brand_name', trim((string) $_POST['site_name']));
    }
    if (isset($_POST['tagline'])) {
        setting_set($pdo, 'brand_line', trim((string) $_POST['tagline']));
    }
    foreach ($logoKeys as $key => $meta) {
        if (!empty($_FILES[$key]['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES[$key]);
            if ($up['ok']) {
                setting_set($pdo, $key, $up['path']);
                require_once __DIR__ . '/includes/media.php';
                media_register($pdo, $up['path'], basename($up['path']));
            }
        } elseif (isset($_POST[$key])) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }
    foreach (cms_nav_item_definitions() as $def) {
        if (isset($_POST[$def['setting_label']])) {
            setting_set($pdo, $def['setting_label'], trim((string) $_POST[$def['setting_label']]));
        }
        if (isset($_POST[$def['setting_href']])) {
            setting_set($pdo, $def['setting_href'], trim((string) $_POST[$def['setting_href']]));
        }
    }
    content_sync_site_json($pdo);
    admin_log_activity($pdo, 'save', 'header', null, 'Header updated');
    admin_flash_set('success', 'Header saved. Refresh the website to see changes.');
    redirect('header.php');
}

$s = settings_get_many($pdo, $allKeys);

$pageTitle = 'Header';
$pageDescription = 'Logo, company name, tagline, and navigation menu — everything in the site header.';
$activeNav = 'header';
require __DIR__ . '/includes/layout.php';
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Logos</h2>
    <p class="adm-hint">Upload two versions of your logo. The header switches automatically when visitors scroll.</p>
    <?php foreach ($logoKeys as $key => $meta): ?>
      <div class="adm-field">
        <label><?= e($meta['label']) ?></label>
        <p class="adm-hint"><?= e($meta['hint']) ?></p>
        <?php if (!empty($s[$key])): ?>
          <p><img src="../<?= e($s[$key]) ?>" alt="" style="max-height:56px;border-radius:6px;background:#1a1a1a;padding:8px;" /></p>
        <?php endif; ?>
        <input type="hidden" name="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <input type="file" name="<?= e($key) ?>" accept="image/*" />
        <?php if (!empty($s[$key])): ?><p class="adm-hint"><?= e($s[$key]) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Company name &amp; tagline</h2>
    <p class="adm-hint">Text shown next to the logo in the header on every page.</p>
    <?php foreach ($textKeys as $key => $label): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($label) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Navigation menu</h2>
    <p class="adm-hint">Page names and links in the header. The same labels appear in the footer menu.</p>
    <?php foreach (cms_nav_item_definitions() as $id => $def): ?>
      <div class="adm-field adm-field-row">
        <div>
          <label for="<?= e($def['setting_label']) ?>"><?= e($def['label']) ?> — menu label</label>
          <input type="text" name="<?= e($def['setting_label']) ?>" id="<?= e($def['setting_label']) ?>" value="<?= e($s[$def['setting_label']] ?? $def['label']) ?>" />
        </div>
        <div>
          <label for="<?= e($def['setting_href']) ?>">Page link</label>
          <input type="text" name="<?= e($def['setting_href']) ?>" id="<?= e($def['setting_href']) ?>" value="<?= e($s[$def['setting_href']] ?? $def['href']) ?>" />
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card adm-glass">
    <div class="adm-actions">
      <button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save header</button>
      <a href="../index.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview website</a>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

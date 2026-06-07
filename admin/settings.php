<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

cms_sync_plain_home_fields($pdo);

admin_require_auth();

$fieldGroups = [
    'brand' => [
        'title' => 'Site details',
        'hint' => 'General company info and live URL. Logo, name, tagline, and menu are managed in the Header module.',
        'fields' => [
            'site_description' => 'Company description',
            'public_base' => 'Live website URL (for images & links)',
        ],
    ],
    'contact' => [
        'title' => 'Contact details',
        'hint' => 'Phone, email, and address used across the site and schema markup.',
        'fields' => [
            'contact_phone_display' => 'Phone (display)',
            'contact_phone_e164' => 'Phone (for links, e.g. +91…)',
            'contact_email' => 'Email',
            'contact_address' => 'Address',
            'enquiry_notify_email' => 'Inquiry notification email',
        ],
    ],
    'conversion' => [
        'title' => 'WhatsApp & consultation',
        'hint' => 'Controls the floating WhatsApp button and consultation modal prefill message.',
        'fields' => [
            'whatsapp_digits' => 'WhatsApp number (digits only)',
            'whatsapp_prefill' => 'WhatsApp default message',
        ],
    ],
    'social' => [
        'title' => 'Social links',
        'fields' => [
            'social_instagram' => 'Instagram URL',
            'social_facebook' => 'Facebook URL',
            'social_linkedin' => 'LinkedIn URL',
            'social_youtube' => 'YouTube URL',
        ],
    ],
    'maps' => [
        'title' => 'Google Maps',
        'fields' => [
            'map_embed_url' => 'Google Map embed URL',
            'map_title' => 'Map section title',
        ],
    ],
];

$logoFields = [
    'site_favicon' => 'Favicon path',
];

$allKeys = [];
foreach ($fieldGroups as $group) {
    foreach ($group['fields'] as $key => $label) {
        $allKeys[$key] = $label;
    }
}
foreach ($logoFields as $key => $label) {
    $allKeys[$key] = $label;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($allKeys as $key => $label) {
        if (!isset($_POST[$key])) {
            continue;
        }
        $value = trim((string) $_POST[$key]);
        if ($key === 'public_base') {
            $value = sanitize_public_base_url($value);
        }
        setting_set($pdo, $key, $value);
    }
    admin_log_activity($pdo, 'save', 'settings', null, 'Global settings updated');
    foreach (['site_favicon'] as $imgKey) {
        if (!empty($_FILES[$imgKey]['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES[$imgKey]);
            if ($up['ok']) {
                setting_set($pdo, $imgKey, $up['path']);
                require_once __DIR__ . '/includes/media.php';
                media_register($pdo, $up['path'], basename($up['path']));
            }
        }
    }
    content_sync_site_json($pdo);
    admin_flash_set('success', 'Site settings saved. Changes appear on the website shortly.');
    redirect('settings.php');
}

$s = settings_get_many($pdo, array_keys($allKeys));

$brand = admin_brand();
if (trim((string) ($s['whatsapp_prefill'] ?? '')) === '') {
    $s['whatsapp_prefill'] = $brand['whatsapp_prefill'];
}

$pageTitle = 'Global settings';
$pageDescription = 'Contact details, WhatsApp, social links, maps, and favicon.';
$activeNav = 'settings';
require __DIR__ . '/includes/layout.php';
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>

  <?php foreach ($fieldGroups as $group): ?>
    <div class="adm-card adm-settings-section">
      <h2><?= e($group['title']) ?></h2>
      <?php if (!empty($group['hint'])): ?>
        <p class="adm-hint"><?= e($group['hint']) ?></p>
      <?php endif; ?>
      <?php foreach ($group['fields'] as $key => $label): ?>
        <div class="adm-field">
          <label for="<?= e($key) ?>"><?= e($label) ?> <?= admin_tooltip('Saved when you click Save at the bottom') ?></label>
          <?php if ($key === 'whatsapp_prefill' || $key === 'contact_address' || $key === 'site_description'): ?>
            <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="<?= $key === 'contact_address' ? 2 : ($key === 'site_description' ? 4 : 3) ?>"><?= e($s[$key] ?? '') ?></textarea>
          <?php else: ?>
            <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Header &amp; footer</h2>
    <p class="adm-hint">Logo, company name, tagline, and navigation menu are managed in the <a href="header.php">Header module</a>. Footer copy and copyright are in the <a href="footer.php">Footer module</a>.</p>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Favicon</h2>
    <p class="adm-hint">Small icon shown in the browser tab.</p>
    <?php foreach (['site_favicon' => 'Favicon'] as $key => $label): ?>
      <div class="adm-field">
        <label><?= e($label) ?></label>
        <?php if (!empty($s[$key])): ?><p><img src="../<?= e($s[$key]) ?>" alt="" style="max-height:32px;border-radius:4px;" /></p><?php endif; ?>
        <input type="hidden" name="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <input type="file" name="<?= e($key) ?>" accept="image/*" />
        <?php if (!empty($s[$key])): ?><p class="adm-hint"><?= e($s[$key]) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card adm-glass">
    <div class="adm-actions">
      <button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save all settings</button>
      <a href="../index.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost">Preview website</a>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

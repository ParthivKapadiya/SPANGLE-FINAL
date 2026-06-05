<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsNavigation.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

cms_sync_plain_home_fields($pdo);

admin_require_auth();

$fieldGroups = [
    'brand' => [
        'title' => 'Brand identity',
        'hint' => 'Company name and tagline shown in the header and browser title.',
        'fields' => [
            'site_name' => 'Company / studio name',
            'tagline' => 'Tagline (under logo)',
            'footer_copyright' => 'Copyright line',
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
    'site_logo' => 'Main logo path',
    'site_logo_light' => 'Logo for dark backgrounds',
    'site_logo_dark' => 'Logo for light backgrounds',
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
    $footer1 = trim((string) ($_POST['footer_blurb_1'] ?? ''));
    $footer2 = trim((string) ($_POST['footer_blurb_2'] ?? ''));
    setting_set($pdo, 'footer_blurb_1', $footer1);
    setting_set($pdo, 'footer_blurb_2', $footer2);
    setting_set($pdo, 'footer_blurb_html', cms_build_footer_blurb_html($footer1, $footer2));
    foreach (cms_nav_item_definitions() as $def) {
        $lk = $def['setting_label'];
        $hk = $def['setting_href'];
        if (isset($_POST[$lk])) {
            setting_set($pdo, $lk, trim((string) $_POST[$lk]));
        }
        if (isset($_POST[$hk])) {
            setting_set($pdo, $hk, trim((string) $_POST[$hk]));
        }
    }
    foreach (['site_logo', 'site_logo_light', 'site_logo_dark', 'site_favicon'] as $imgKey) {
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

$navKeys = [];
foreach (cms_nav_item_definitions() as $def) {
    $navKeys[] = $def['setting_label'];
    $navKeys[] = $def['setting_href'];
}
$s = settings_get_many($pdo, array_merge(array_keys($allKeys), $navKeys, ['footer_blurb_html', 'footer_blurb_1', 'footer_blurb_2']));
if (trim((string) ($s['footer_blurb_1'] ?? '')) === '' && trim((string) ($s['footer_blurb_html'] ?? '')) !== '') {
    $footer = cms_parse_footer_blurb_html((string) $s['footer_blurb_html']);
    $s['footer_blurb_1'] = $footer['paragraph1'];
    $s['footer_blurb_2'] = $footer['paragraph2'];
}

$brand = admin_brand();
if (trim((string) ($s['whatsapp_prefill'] ?? '')) === '') {
    $s['whatsapp_prefill'] = $brand['whatsapp_prefill'];
}

$pageTitle = 'Site settings';
$pageDescription = 'Logo, contact, WhatsApp, footer, menu, and social links — no code required.';
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
          <?php if ($key === 'whatsapp_prefill' || $key === 'contact_address'): ?>
            <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="<?= $key === 'contact_address' ? 2 : 3 ?>"><?= e($s[$key] ?? '') ?></textarea>
          <?php else: ?>
            <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="adm-card adm-settings-section">
    <h2>Footer description</h2>
    <p class="adm-hint">Two short lines shown under the logo in the site footer.</p>
    <div class="adm-field">
      <label for="footer_blurb_1">First line</label>
      <textarea name="footer_blurb_1" id="footer_blurb_1" rows="2"><?= e($s['footer_blurb_1'] ?? '') ?></textarea>
    </div>
    <div class="adm-field">
      <label for="footer_blurb_2">Second line (optional)</label>
      <textarea name="footer_blurb_2" id="footer_blurb_2" rows="2"><?= e($s['footer_blurb_2'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="adm-card adm-settings-section">
    <h2>Logo &amp; favicon</h2>
    <p class="adm-hint">Upload Archevo Design branding images. Paths update automatically after upload.</p>
    <?php foreach (['site_logo' => 'Main logo', 'site_logo_light' => 'Light logo (header)', 'site_logo_dark' => 'Dark logo', 'site_favicon' => 'Favicon'] as $key => $label): ?>
      <div class="adm-field">
        <label><?= e($label) ?></label>
        <?php if (!empty($s[$key])): ?><p><img src="../<?= e($s[$key]) ?>" alt="" style="max-height:48px;border-radius:6px;" /></p><?php endif; ?>
        <input type="hidden" name="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <input type="file" name="<?= e($key) ?>" accept="image/*" />
        <?php if (!empty($s[$key])): ?><p class="adm-hint"><?= e($s[$key]) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card adm-settings-section">
    <h2>Header menu</h2>
    <?php foreach (cms_nav_item_definitions() as $id => $def): ?>
      <div class="adm-field adm-field-row">
        <div>
          <label><?= e($def['label']) ?> label</label>
          <input type="text" name="<?= e($def['setting_label']) ?>" value="<?= e($s[$def['setting_label']] ?? $def['label']) ?>" />
        </div>
        <div>
          <label>Link</label>
          <input type="text" name="<?= e($def['setting_href']) ?>" value="<?= e($s[$def['setting_href']] ?? $def['href']) ?>" />
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card">
    <div class="adm-actions">
      <button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save all settings</button>
      <a href="../index.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost">Preview website</a>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

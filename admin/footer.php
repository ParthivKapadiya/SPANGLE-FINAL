<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsNavigation.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();

$keys = [
    'footer_blurb_1', 'footer_blurb_2', 'footer_copyright', 'footer_agency_credit',
    'social_instagram', 'social_facebook', 'social_youtube', 'social_linkedin',
    'whatsapp_digits',
];
$navKeys = [];
foreach (cms_nav_item_definitions() as $def) {
    $navKeys[] = $def['setting_label'];
    $navKeys[] = $def['setting_href'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach (array_merge($keys, $navKeys) as $key) {
        if (!isset($_POST[$key])) {
            continue;
        }
        setting_set($pdo, $key, trim((string) $_POST[$key]));
    }
    $footer1 = trim((string) ($_POST['footer_blurb_1'] ?? ''));
    $footer2 = trim((string) ($_POST['footer_blurb_2'] ?? ''));
    setting_set($pdo, 'footer_blurb_1', $footer1);
    setting_set($pdo, 'footer_blurb_2', $footer2);
    setting_set($pdo, 'footer_blurb_html', cms_build_footer_blurb_html($footer1, $footer2));
    content_sync_site_json($pdo);
    admin_log_activity($pdo, 'save', 'footer', null, 'Footer & navigation links updated');
    admin_flash_set('success', 'Footer saved. Changes appear on the website shortly.');
    redirect('footer.php');
}

$s = settings_get_many($pdo, array_merge($keys, $navKeys, ['footer_blurb_html']));
if (trim((string) ($s['footer_blurb_1'] ?? '')) === '' && trim((string) ($s['footer_blurb_html'] ?? '')) !== '') {
    $footer = cms_parse_footer_blurb_html((string) $s['footer_blurb_html']);
    $s['footer_blurb_1'] = $footer['paragraph1'];
    $s['footer_blurb_2'] = $footer['paragraph2'];
}

$pageTitle = 'Footer';
$pageDescription = 'Footer copy, copyright, navigation links, and social icons — no code required.';
$activeNav = 'footer';
require __DIR__ . '/includes/layout.php';
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Brand &amp; copyright</h2>
    <p class="adm-hint">Text shown under the logo and in the footer bar on every page.</p>
    <div class="adm-field">
      <label for="footer_blurb_1">Footer description — line 1</label>
      <textarea name="footer_blurb_1" id="footer_blurb_1" rows="2"><?= e($s['footer_blurb_1'] ?? '') ?></textarea>
    </div>
    <div class="adm-field">
      <label for="footer_blurb_2">Footer description — line 2 (optional)</label>
      <textarea name="footer_blurb_2" id="footer_blurb_2" rows="2"><?= e($s['footer_blurb_2'] ?? '') ?></textarea>
    </div>
    <div class="adm-field">
      <label for="footer_copyright">Copyright line</label>
      <input type="text" name="footer_copyright" id="footer_copyright" value="<?= e($s['footer_copyright'] ?? '') ?>" />
    </div>
    <div class="adm-field">
      <label for="footer_agency_credit">Agency credit (optional)</label>
      <input type="text" name="footer_agency_credit" id="footer_agency_credit" value="<?= e($s['footer_agency_credit'] ?? '') ?>" placeholder="Digital experience by …" />
      <p class="adm-hint">Leave blank to keep the default developer credit.</p>
    </div>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Navigate column</h2>
    <p class="adm-hint">Footer and header share the same menu labels and links.</p>
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
    <p class="adm-hint">Services column is populated automatically from your <a href="services.php">service blocks</a>.</p>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Social &amp; WhatsApp</h2>
    <div class="adm-field">
      <label for="social_instagram">Instagram</label>
      <input type="url" name="social_instagram" id="social_instagram" value="<?= e($s['social_instagram'] ?? '') ?>" />
    </div>
    <div class="adm-field">
      <label for="social_facebook">Facebook</label>
      <input type="url" name="social_facebook" id="social_facebook" value="<?= e($s['social_facebook'] ?? '') ?>" />
    </div>
    <div class="adm-field">
      <label for="social_linkedin">LinkedIn</label>
      <input type="url" name="social_linkedin" id="social_linkedin" value="<?= e($s['social_linkedin'] ?? '') ?>" />
    </div>
    <div class="adm-field">
      <label for="social_youtube">YouTube</label>
      <input type="url" name="social_youtube" id="social_youtube" value="<?= e($s['social_youtube'] ?? '') ?>" />
    </div>
    <div class="adm-field">
      <label for="whatsapp_digits">WhatsApp number (digits only)</label>
      <input type="text" name="whatsapp_digits" id="whatsapp_digits" value="<?= e($s['whatsapp_digits'] ?? '') ?>" />
    </div>
  </div>

  <div class="adm-card">
    <div class="adm-actions">
      <button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save footer</button>
      <a href="../index.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview website</a>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

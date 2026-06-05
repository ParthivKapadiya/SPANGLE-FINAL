<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsCopyKeys.php';

admin_require_auth();

$legalKeys = ['legal_privacy_html', 'legal_terms_html'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($legalKeys as $key) {
        if (isset($_POST[$key])) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }
    content_sync_site_json($pdo);
    admin_log_activity($pdo, 'save', 'legal', null, 'Privacy & terms updated');
    admin_flash_set('success', 'Legal pages saved.');
    redirect('legal.php');
}

cms_seed_copy_settings($pdo);
$s = settings_get_many($pdo, $legalKeys);

$pageTitle = 'Legal pages';
$pageDescription = 'Edit Privacy Policy and Terms of Use — content loads automatically on privacy.html and terms.html.';
$activeNav = 'legal';
require __DIR__ . '/includes/layout.php';
?>
<form method="post" class="adm-legal-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-glass">
    <h2>Privacy policy</h2>
    <p class="adm-hint">HTML allowed: paragraphs, headings, lists. Shown on <a href="../privacy.html" target="_blank" rel="noopener">privacy.html</a>.</p>
    <div class="adm-field">
      <textarea name="legal_privacy_html" rows="18" class="adm-code-area"><?= e($s['legal_privacy_html'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="adm-card adm-glass">
    <h2>Terms of use</h2>
    <p class="adm-hint">Shown on <a href="../terms.html" target="_blank" rel="noopener">terms.html</a>.</p>
    <div class="adm-field">
      <textarea name="legal_terms_html" rows="18" class="adm-code-area"><?= e($s['legal_terms_html'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="adm-card adm-legal-actions">
    <div class="adm-actions">
      <button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save legal pages</button>
      <a href="../privacy.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost">Preview privacy</a>
      <a href="../terms.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost">Preview terms</a>
    </div>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

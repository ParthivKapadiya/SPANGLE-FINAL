<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('form');
extract(contact_admin_page_vars($section));

$keys = ['contact_project_types', 'contact_budget_ranges'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-form', null, 'Contact form options updated');
    contact_admin_sync_and_redirect('form.php', 'Form options saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Multi-step enquiry form</h2>
    <p class="adm-hint">Form field labels (Name, Email, etc.) are in <a href="<?= e(admin_href('settings.php')) ?>">Global settings → Form labels</a>.</p>
    <?php home_admin_render_field('contact_project_types', 'Project types (one per line)', $s, 'textarea'); ?>
    <?php home_admin_render_field('contact_budget_ranges', 'Budget ranges (one per line)', $s, 'textarea'); ?>
    <p class="adm-hint">Each line becomes a selectable button in steps 2 and 3 of the enquiry form.</p>
  </div>
  <?php home_admin_render_save('Save form options'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('cta');
extract(contact_admin_page_vars($section));

$keys = [
    'contact_cta_title', 'contact_cta_sub',
    'contact_cta_btn_text', 'contact_cta_btn_url',
    'contact_cta_btn2_text', 'contact_cta_btn2_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-cta', null, 'Contact CTA updated');
    contact_admin_sync_and_redirect('cta.php', 'Call to action saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Bottom invitation</h2>
    <?php
    home_admin_render_field('contact_cta_title', 'Headline', $s);
    home_admin_render_field('contact_cta_sub', 'Disciplines line', $s);
    home_admin_render_link_row('contact_cta_btn_text', 'contact_cta_btn_url', $s, 'Primary button text', 'Primary button link');
    home_admin_render_link_row('contact_cta_btn2_text', 'contact_cta_btn2_url', $s, 'Secondary button text', 'Secondary button link');
    ?>
    <p class="adm-hint">Use <code>#cnt-enquiry-form</code> to scroll to the enquiry form on this page.</p>
  </div>
  <?php home_admin_render_save('Save call to action'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

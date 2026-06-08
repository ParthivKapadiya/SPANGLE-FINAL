<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();
cms_sync_plain_services_fields($pdo);

$section = services_admin_require_section('faq');
extract(services_admin_page_vars($section));

$keys = [
    'services_faq_eyebrow', 'services_faq_title',
    'services_faq_q1', 'services_faq_a1', 'services_faq_q2', 'services_faq_a2',
    'services_faq_q3', 'services_faq_a3', 'services_faq_q4', 'services_faq_a4',
    'services_faq_q5', 'services_faq_a5', 'services_faq_q6', 'services_faq_a6',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-faq', null, 'Services FAQ updated');
    services_admin_sync_and_redirect('faq.php', 'FAQ section saved.');
}

$s = services_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('services_faq_eyebrow', 'Small label', $s);
    home_admin_render_field('services_faq_title', 'Title', $s);
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Questions &amp; answers</h2>
    <?php for ($i = 1; $i <= 6; $i++): ?>
      <?php home_admin_render_field('services_faq_q' . $i, 'Question ' . $i, $s); ?>
      <?php home_admin_render_field('services_faq_a' . $i, 'Answer ' . $i, $s, 'textarea'); ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save FAQ section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('faq');
extract(contact_admin_page_vars($section));

$keys = [
    'contact_faq_q1', 'contact_faq_a1', 'contact_faq_q2', 'contact_faq_a2',
    'contact_faq_q3', 'contact_faq_a3', 'contact_faq_q4', 'contact_faq_a4',
    'contact_faq_q5', 'contact_faq_a5',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-faq', null, 'Contact FAQ updated');
    contact_admin_sync_and_redirect('faq.php', 'FAQ section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Questions &amp; answers</h2>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <?php home_admin_render_field('contact_faq_q' . $i, 'Question ' . $i, $s); ?>
      <?php home_admin_render_field('contact_faq_a' . $i, 'Answer ' . $i, $s, 'textarea'); ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save FAQ section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

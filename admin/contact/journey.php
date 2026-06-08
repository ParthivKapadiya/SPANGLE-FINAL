<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('journey');
extract(contact_admin_page_vars($section));

$keys = [
    'contact_intro_title', 'contact_intro_lead',
    'contact_step_1_title', 'contact_step_1_text',
    'contact_step_2_title', 'contact_step_2_text',
    'contact_step_3_title', 'contact_step_3_text',
    'contact_step_4_title', 'contact_step_4_text',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-journey', null, 'Contact journey section updated');
    contact_admin_sync_and_redirect('journey.php', 'Journey section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('contact_intro_title', 'Title', $s);
    home_admin_render_field('contact_intro_lead', 'Intro paragraph', $s, 'textarea');
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Four steps</h2>
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <?php home_admin_render_field('contact_step_' . $i . '_title', 'Step ' . $i . ' — title', $s); ?>
      <?php home_admin_render_field('contact_step_' . $i . '_text', 'Step ' . $i . ' — description', $s, 'textarea'); ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save journey section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

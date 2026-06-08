<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('visit');
extract(contact_admin_page_vars($section));

$keys = [
    'contact_page_title', 'contact_page_lead', 'contact_hours_html',
    'contact_visit_parking', 'contact_visit_appointment',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-visit', null, 'Contact visit section updated');
    contact_admin_sync_and_redirect('visit.php', 'Visit section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Visit the studio</h2>
    <?php
    home_admin_render_field('contact_page_title', 'Section title', $s);
    home_admin_render_field('contact_page_lead', 'Intro paragraph', $s, 'textarea');
    home_admin_render_field('contact_hours_html', 'Studio hours (HTML)', $s, 'textarea');
    home_admin_render_field('contact_visit_parking', 'Parking note', $s);
    home_admin_render_field('contact_visit_appointment', 'Appointment note', $s);
    ?>
    <?php home_admin_card_link('settings.php', 'Phone, email & address', 'Contact details and map location are in Global settings.'); ?>
  </div>
  <?php home_admin_render_save('Save visit section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

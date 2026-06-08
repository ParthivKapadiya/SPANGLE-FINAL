<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('testimonials');
extract(services_admin_page_vars($section));

$keys = ['services_testimonials_eyebrow', 'services_testimonials_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-testimonials', null, 'Services testimonials section updated');
    services_admin_sync_and_redirect('testimonials.php', 'Testimonials section saved.');
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
    home_admin_render_field('services_testimonials_eyebrow', 'Small label', $s);
    home_admin_render_field('services_testimonials_title', 'Title', $s);
    ?>
    <?php home_admin_card_link('testimonials.php', 'Manage client quotes', 'Edit testimonial text shown on the services page.'); ?>
  </div>
  <?php home_admin_render_save('Save testimonials section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('testimonials');
extract(home_admin_page_vars($section));

$keys = ['home_testimonials_eyebrow', 'home_testimonials_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-testimonials', null, 'Home testimonials section updated');
    home_admin_sync_and_redirect('testimonials.php', 'Testimonials section saved.');
}

$s = settings_get_many($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('home_testimonials_eyebrow', 'Small label', $s);
    home_admin_render_field('home_testimonials_title', 'Title', $s);
    ?>
    <?php home_admin_card_link('testimonials.php', 'Manage client quotes', 'Add or edit testimonial text shown in the scrolling marquee.'); ?>
  </div>
  <?php home_admin_render_save('Save testimonials section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

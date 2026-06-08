<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';

admin_require_auth();

$section = studio_admin_require_section('testimonials');
extract(studio_admin_page_vars($section));

$keys = [
    'studio_testimonials_eyebrow', 'studio_testimonials_title',
    'studio_trust_badge_1_value', 'studio_trust_badge_1_label', 'studio_trust_badge_1_icon',
    'studio_trust_badge_2_value', 'studio_trust_badge_2_label', 'studio_trust_badge_2_icon',
    'studio_trust_badge_3_value', 'studio_trust_badge_3_label', 'studio_trust_badge_3_icon',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'studio-testimonials', null, 'Studio testimonials section updated');
    studio_admin_sync_and_redirect('testimonials.php', 'Testimonials section saved.');
}

$s = studio_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('studio_testimonials_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_testimonials_title', 'Title', $s);
    ?>
    <?php home_admin_card_link('testimonials.php', 'Manage client quotes', 'Edit testimonial text shown in the scrolling marquee.'); ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Trust badges</h2>
    <p class="adm-hint">Three highlight stats shown below the testimonial carousel.</p>
    <?php for ($i = 1; $i <= 3; $i++): ?>
    <h3 style="margin-top:1.25rem;">Badge <?= $i ?></h3>
    <?php
    home_admin_render_field('studio_trust_badge_' . $i . '_value', 'Value', $s);
    home_admin_render_field('studio_trust_badge_' . $i . '_label', 'Label', $s);
    home_admin_render_field('studio_trust_badge_' . $i . '_icon', 'Icon class', $s);
    ?>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save testimonials section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

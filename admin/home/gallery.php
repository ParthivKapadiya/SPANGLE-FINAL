<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('gallery');
extract(home_admin_page_vars($section));

$keys = ['home_gallery_eyebrow', 'home_gallery_title', 'home_gallery_intro', 'home_gallery_limit'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-gallery', null, 'Home gallery section updated');
    home_admin_sync_and_redirect('gallery.php', 'Gallery section saved.');
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
    home_admin_render_field('home_gallery_eyebrow', 'Small label', $s);
    home_admin_render_field('home_gallery_title', 'Title', $s);
    home_admin_render_field('home_gallery_intro', 'Intro paragraph', $s, 'textarea');
    home_admin_render_field('home_gallery_limit', 'Max images on home (4–24)', $s, 'number');
    ?>
    <?php home_admin_card_link('gallery.php', 'Manage gallery photos', 'Upload images and choose which appear on the home page.'); ?>
  </div>
  <?php home_admin_render_save('Save gallery section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

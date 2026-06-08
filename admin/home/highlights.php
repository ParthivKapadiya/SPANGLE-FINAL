<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('highlights');
extract(home_admin_page_vars($section));

$keys = ['home_awards_eyebrow', 'home_awards_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-highlights', null, 'Home highlights section updated');
    home_admin_sync_and_redirect('highlights.php', 'Studio highlights saved.');
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
    home_admin_render_field('home_awards_eyebrow', 'Small label', $s);
    home_admin_render_field('home_awards_title', 'Title', $s);
    ?>
    <?php home_admin_card_link('studio/values.php', 'Manage highlight cards', 'Edit the four “Why clients work with us” trust points on the Studio page.'); ?>
  </div>
  <?php home_admin_render_save('Save studio highlights'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

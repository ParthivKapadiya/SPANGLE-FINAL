<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';

admin_require_auth();

$section = studio_admin_require_section('process');
extract(studio_admin_page_vars($section));

$keys = ['studio_process_eyebrow', 'studio_process_title', 'studio_process_intro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'studio-process', null, 'Studio process section updated');
    studio_admin_sync_and_redirect('process.php', 'Process section saved.');
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
    home_admin_render_field('studio_process_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_process_title', 'Title', $s);
    home_admin_render_field('studio_process_intro', 'Intro paragraph', $s, 'textarea');
    ?>
    <?php home_admin_card_link('process.php', 'Manage process steps', 'Steps shown here are pulled from your Process page content.'); ?>
  </div>
  <?php home_admin_render_save('Save process section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('cases');
extract(services_admin_page_vars($section));

$keys = [
    'services_cases_eyebrow', 'services_cases_title', 'services_cases_intro',
    'services_cases_link_text', 'services_cases_link_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-cases', null, 'Services cases section updated');
    services_admin_sync_and_redirect('cases.php', 'Case studies section saved.');
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
    home_admin_render_field('services_cases_eyebrow', 'Small label', $s);
    home_admin_render_field('services_cases_title', 'Title', $s);
    home_admin_render_field('services_cases_intro', 'Intro paragraph', $s, 'textarea');
    home_admin_render_link_row('services_cases_link_text', 'services_cases_link_url', $s, 'Link text', 'Link URL');
    ?>
    <?php home_admin_card_link('projects.php', 'Manage featured projects', 'Case study cards use projects marked as featured.'); ?>
  </div>
  <?php home_admin_render_save('Save case studies section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('process');
extract(services_admin_page_vars($section));

$keys = [
    'services_process_eyebrow', 'services_process_title', 'services_process_intro',
    'services_process_link_text', 'services_process_link_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-process', null, 'Services process section updated');
    services_admin_sync_and_redirect('process.php', 'Process section saved.');
}

$s = services_admin_load_settings($pdo, $keys);
$steps = services_admin_load_process_steps($pdo);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('services_process_eyebrow', 'Small label', $s);
    home_admin_render_field('services_process_title', 'Title', $s);
    home_admin_render_field('services_process_intro', 'Intro paragraph', $s, 'textarea');
    home_admin_render_link_row('services_process_link_text', 'services_process_link_url', $s, 'Link text', 'Link URL');
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Process steps (<?= count($steps) ?>)</h2>
    <p class="adm-hint">Each step becomes one box in the row on the services page. Order follows <strong>Order</strong> in each step. Use context <strong>Process page only</strong> or <strong>Home and Process page</strong> — both appear here.</p>
    <?php if ($steps): ?>
      <ol class="adm-hint" style="margin:0.75rem 0 1rem;padding-left:1.25rem;">
        <?php foreach ($steps as $i => $step): ?>
          <li style="margin-bottom:0.65rem;">
            <strong><?= sprintf('%02d', $i + 1) ?> — <?= e($step['title']) ?></strong>
            <br />
            <span><?= e($step['description'] ?? '') ?></span>
            <br />
            <a href="<?= e(admin_href('process.php?action=edit_step&id=' . (int) $step['id'] . '&return=services/process.php')) ?>" class="adm-btn adm-btn-sm adm-btn-ghost" style="margin-top:0.35rem;">Edit this step</a>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p class="adm-hint">No steps yet — add your first step below.</p>
    <?php endif; ?>
    <p class="adm-hint">
      <a href="<?= e(admin_href('process.php?action=new_step&return=services/process.php')) ?>" class="adm-btn adm-btn-sm adm-btn-primary">Add process step</a>
      <a href="<?= e(admin_href('process.php')) ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Manage all steps</a>
    </p>
  </div>
  <?php home_admin_render_save('Save section heading'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

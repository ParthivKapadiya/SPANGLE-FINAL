<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('ecosystem');
extract(services_admin_page_vars($section));

$keys = ['services_ecosystem_eyebrow', 'services_ecosystem_title', 'services_ecosystem_intro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-ecosystem', null, 'Services ecosystem section updated');
    services_admin_sync_and_redirect('ecosystem.php', 'Service ecosystem saved.');
}

$s = services_admin_load_settings($pdo, $keys);
$blocks = services_admin_load_blocks($pdo);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <p class="adm-hint">Controls the eyebrow, title, and intro above the service cards grid.</p>
    <?php
    home_admin_render_field('services_ecosystem_eyebrow', 'Small label', $s);
    home_admin_render_field('services_ecosystem_title', 'Title', $s);
    home_admin_render_field('services_ecosystem_intro', 'Intro paragraph', $s, 'textarea');
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Service cards &amp; flow diagram</h2>
    <p class="adm-hint">Each block below becomes one card in the grid and one step in the flow diagram on the services page. Order follows <strong>Sort order</strong> in each block.</p>
    <?php if ($blocks): ?>
      <ol class="adm-hint" style="margin:0.75rem 0 1rem;padding-left:1.25rem;">
        <?php foreach ($blocks as $block): ?>
          <li style="margin-bottom:0.65rem;">
            <strong><?= e($block['number_label']) ?> — <?= e($block['title']) ?></strong>
            <?php if (empty($block['is_active'])): ?> <em>(hidden on site)</em><?php endif; ?>
            <?php if (!empty($block['show_on_home'])): ?> · <span>also on home</span><?php endif; ?>
            <br />
            <span><?= e($block['short_description'] ?? '') ?></span>
            <br />
            <a href="<?= e(admin_href('services.php?action=edit&id=' . (int) $block['id'])) ?>" class="adm-btn adm-btn-sm adm-btn-ghost" style="margin-top:0.35rem;">Edit this block</a>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p class="adm-hint">No service blocks yet — add your first card below.</p>
    <?php endif; ?>
    <p class="adm-hint">
      <a href="<?= e(admin_href('services.php?action=new')) ?>" class="adm-btn adm-btn-sm adm-btn-primary">Add service block</a>
      <a href="<?= e(admin_href('services.php')) ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Manage all blocks</a>
    </p>
  </div>
  <?php home_admin_render_save('Save section heading'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/servicesAdmin.php';

admin_require_auth();

$section = services_admin_require_section('blocks');
extract(services_admin_page_vars($section));

$keys = ['services_detail_link_text', 'services_detail_link_url'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    services_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'services-blocks', null, 'Services blocks settings updated');
    services_admin_sync_and_redirect('blocks.php', 'Service blocks settings saved.');
}

$s = services_admin_load_settings($pdo, $keys);
$rows = $pdo->query(
    'SELECT id, number_label, title, show_on_home, is_active FROM services ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__) . '/includes/layout.php';
services_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Detail block link</h2>
    <p class="adm-hint">Shown at the bottom of each service detail section on the services page.</p>
    <?php home_admin_render_link_row('services_detail_link_text', 'services_detail_link_url', $s, 'Link text', 'Link URL'); ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Service blocks</h2>
    <p class="adm-hint">Each block powers the ecosystem grid, detail sections, home cards, and footer links.</p>
    <?php if ($rows): ?>
      <ul class="adm-hint" style="margin:0.75rem 0 1rem;padding-left:1.25rem;">
        <?php foreach ($rows as $row): ?>
          <li style="margin-bottom:0.35rem;">
            <strong><?= e($row['number_label']) ?> — <?= e($row['title']) ?></strong>
            <?php if (empty($row['is_active'])): ?><em>(hidden)</em><?php endif; ?>
            <?php if (!empty($row['show_on_home'])): ?> · <span>on home</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="adm-hint">No service blocks yet.</p>
    <?php endif; ?>
    <?php home_admin_card_link('services.php', 'Edit service blocks', 'Add, reorder, and update titles, copy, and images.'); ?>
  </div>
  <?php home_admin_render_save('Save link settings'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

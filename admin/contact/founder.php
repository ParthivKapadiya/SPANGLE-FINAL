<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('founder');
extract(contact_admin_page_vars($section));

$keys = ['contact_founder_quote'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    contact_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'contact-founder', null, 'Contact founder section updated');
    contact_admin_sync_and_redirect('founder.php', 'Founder section saved.');
}

$s = contact_admin_load_settings($pdo, $keys);
$team = $pdo->query('SELECT name, role_title FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Founder quote</h2>
    <?php home_admin_render_field('contact_founder_quote', 'Quote under bio', $s, 'textarea'); ?>
    <p class="adm-hint">Name, role, photo, and bio come from the first active team member.</p>
    <?php if ($team): ?>
      <p class="adm-hint">Currently: <strong><?= e($team['name']) ?></strong> — <?= e($team['role_title']) ?></p>
    <?php endif; ?>
    <?php home_admin_card_link('team.php', 'Manage team profiles', 'Edit founder name, photo, and biography.'); ?>
  </div>
  <?php home_admin_render_save('Save founder section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

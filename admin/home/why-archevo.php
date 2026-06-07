<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('why-archevo');
extract(home_admin_page_vars($section));

$keys = ['home_why_eyebrow', 'home_why_title', 'home_why_intro'];
for ($i = 1; $i <= 6; $i++) {
    $keys[] = 'home_why_' . $i . '_title';
    $keys[] = 'home_why_' . $i . '_text';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-why', null, 'Home Why Archevo updated');
    home_admin_sync_and_redirect('why-archevo.php', 'Why Archevo section saved.');
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
    home_admin_render_field('home_why_eyebrow', 'Small label', $s);
    home_admin_render_field('home_why_title', 'Main heading', $s);
    home_admin_render_field('home_why_intro', 'Intro paragraph', $s, 'textarea');
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Six cards</h2>
    <?php for ($i = 1; $i <= 6; $i++): ?>
      <div class="adm-field">
        <label for="home_why_<?= $i ?>_title">Card <?= $i ?> — title</label>
        <input type="text" name="home_why_<?= $i ?>_title" id="home_why_<?= $i ?>_title" value="<?= e($s['home_why_' . $i . '_title'] ?? '') ?>" />
      </div>
      <div class="adm-field">
        <label for="home_why_<?= $i ?>_text">Card <?= $i ?> — description</label>
        <textarea name="home_why_<?= $i ?>_text" id="home_why_<?= $i ?>_text" rows="2"><?= e($s['home_why_' . $i . '_text'] ?? '') ?></textarea>
      </div>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save Why Archevo section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

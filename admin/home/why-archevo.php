<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();
cms_sync_plain_home_fields($pdo);

$section = home_admin_require_section('why-archevo');
extract(home_admin_page_vars($section));

$defaults = cms_home_why_defaults();
$keys = ['home_why_eyebrow', 'home_why_title', 'home_why_intro'];
for ($i = 1; $i <= 6; $i++) {
    $keys[] = 'home_why_' . $i . '_title';
    $keys[] = 'home_why_' . $i . '_text';
    $keys[] = 'home_why_' . $i . '_icon';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-why', null, 'Home Why Archevo updated');
    home_admin_sync_and_redirect('why-archevo.php', 'Why Archevo section saved.');
}

$s = settings_get_many($pdo, $keys);
foreach (['eyebrow' => 'home_why_eyebrow', 'title' => 'home_why_title', 'intro' => 'home_why_intro'] as $field => $key) {
    if (trim((string) ($s[$key] ?? '')) === '') {
        $s[$key] = $defaults[$field];
    }
}
foreach ($defaults['cards'] as $i => $card) {
    foreach (['title', 'text', 'icon'] as $field) {
        $key = 'home_why_' . $i . '_' . $field;
        if (trim((string) ($s[$key] ?? '')) === '') {
            $s[$key] = $card[$field];
        }
    }
}

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
    <p class="adm-hint">Shared on the <strong>Home</strong> and <strong>Studio</strong> pages. Each row is one card — title, description, and Font Awesome icon class (icons show on Home; Studio uses numbered cards with the same text).</p>
    <?php foreach ($defaults['cards'] as $i => $card): ?>
      <div class="adm-field adm-field-row adm-field-row--3">
        <input type="text" name="home_why_<?= $i ?>_title" id="home_why_<?= $i ?>_title" value="<?= e($s['home_why_' . $i . '_title'] ?? $card['title']) ?>" placeholder="Title" aria-label="Card <?= $i ?> title" />
        <textarea name="home_why_<?= $i ?>_text" id="home_why_<?= $i ?>_text" rows="2" placeholder="Description" aria-label="Card <?= $i ?> description"><?= e($s['home_why_' . $i . '_text'] ?? $card['text']) ?></textarea>
        <input type="text" name="home_why_<?= $i ?>_icon" id="home_why_<?= $i ?>_icon" value="<?= e($s['home_why_' . $i . '_icon'] ?? $card['icon']) ?>" placeholder="Icon class" aria-label="Card <?= $i ?> icon" title="e.g. fa-solid fa-layer-group" />
      </div>
    <?php endforeach; ?>
  </div>
  <?php home_admin_render_save('Save Why Archevo section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

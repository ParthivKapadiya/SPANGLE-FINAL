<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();

$section = studio_admin_require_section('why');
extract(studio_admin_page_vars($section));

$keys = ['studio_why_eyebrow', 'studio_why_title', 'studio_why_intro'];
$whyCards = cms_build_why_cards_from_settings(studio_admin_load_settings($pdo, array_merge(
    $keys,
    [
        'home_why_1_title', 'home_why_1_text', 'home_why_1_icon',
        'home_why_2_title', 'home_why_2_text', 'home_why_2_icon',
        'home_why_3_title', 'home_why_3_text', 'home_why_3_icon',
        'home_why_4_title', 'home_why_4_text', 'home_why_4_icon',
        'home_why_5_title', 'home_why_5_text', 'home_why_5_icon',
        'home_why_6_title', 'home_why_6_text', 'home_why_6_icon',
    ]
)));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    studio_admin_save_settings($pdo, $keys);
    admin_log_activity($pdo, 'save', 'studio-why', null, 'Studio why section updated');
    studio_admin_sync_and_redirect('why.php', 'Why Archevo section saved.');
}

$s = studio_admin_load_settings($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Studio page heading</h2>
    <p class="adm-hint">These three fields control only the Studio page intro above the six cards.</p>
    <?php
    home_admin_render_field('studio_why_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_why_title', 'Title', $s);
    home_admin_render_field('studio_why_intro', 'Intro paragraph', $s, 'textarea');
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Six cards (shared with Home)</h2>
    <p class="adm-hint">The same six cards appear on the Home and Studio pages. Edit them in one place only.</p>
    <ol class="adm-hint" style="margin:0.75rem 0 1rem;padding-left:1.25rem;">
      <?php foreach ($whyCards as $card): ?>
        <li style="margin-bottom:0.35rem;"><strong><?= e($card['title']) ?></strong> — <?= e($card['text']) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php home_admin_card_link('home/why-archevo.php', 'Edit the six cards', 'Opens Home → Why Archevo — changes apply to both pages.'); ?>
  </div>
  <?php home_admin_render_save('Save studio heading'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

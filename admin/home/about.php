<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();
cms_sync_plain_home_fields($pdo);

$section = home_admin_require_section('about');
extract(home_admin_page_vars($section));

$keys = [
    'home_about_eyebrow', 'home_about_title', 'home_about_image_alt', 'home_about_caption',
    'home_link_about_text', 'home_link_about_url',
    'home_pillar_1_title', 'home_pillar_1_text', 'home_pillar_2_title', 'home_pillar_2_text',
    'home_pillar_3_title', 'home_pillar_3_text', 'home_pillar_4_title', 'home_pillar_4_text',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    $aboutP1 = trim((string) ($_POST['home_about_paragraph_1'] ?? ''));
    $aboutP2 = trim((string) ($_POST['home_about_paragraph_2'] ?? ''));
    setting_set($pdo, 'home_about_paragraph_1', $aboutP1);
    setting_set($pdo, 'home_about_paragraph_2', $aboutP2);
    setting_set($pdo, 'home_about_lead_html', cms_build_about_lead_html($aboutP1, $aboutP2));
    if (!empty($_FILES['home_about_image']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['home_about_image']);
        if ($up['ok']) {
            setting_set($pdo, 'home_about_image', $up['path']);
            media_register($pdo, $up['path'], basename($up['path']));
        }
    }
    admin_log_activity($pdo, 'save', 'home-about', null, 'Home about section updated');
    home_admin_sync_and_redirect('about.php', 'About section saved.');
}

$s = settings_get_many($pdo, array_merge($keys, ['home_about_paragraph_1', 'home_about_paragraph_2', 'home_about_image', 'home_about_lead_html']));
if (trim((string) ($s['home_about_paragraph_1'] ?? '')) === '' && trim((string) ($s['home_about_lead_html'] ?? '')) !== '') {
    $about = cms_parse_about_lead_html((string) $s['home_about_lead_html']);
    $s['home_about_paragraph_1'] = $about['paragraph1'];
    $s['home_about_paragraph_2'] = $about['paragraph2'];
}

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Practice story</h2>
    <?php
    home_admin_render_field('home_about_eyebrow', 'Small label', $s);
    home_admin_render_field('home_about_title', 'Heading', $s);
    home_admin_render_field('home_about_paragraph_1', 'First paragraph', $s, 'textarea');
    home_admin_render_field('home_about_paragraph_2', 'Second paragraph (optional)', $s, 'textarea');
    ?>
    <div class="adm-field">
      <label>Section photo</label>
      <?php if (!empty($s['home_about_image'])): ?><p><img src="../../<?= e($s['home_about_image']) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p><?php endif; ?>
      <input type="file" name="home_about_image" accept="image/*" />
    </div>
    <?php
    home_admin_render_field('home_about_image_alt', 'Image description (accessibility)', $s);
    home_admin_render_field('home_about_caption', 'Image caption', $s);
    home_admin_render_link_row('home_link_about_text', 'home_link_about_url', $s);
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Four pillars</h2>
    <p class="adm-hint">Mission, Vision, Philosophy, and Execution cards beside the story.</p>
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="adm-field">
        <label for="home_pillar_<?= $i ?>_title">Pillar <?= $i ?> — title</label>
        <input type="text" name="home_pillar_<?= $i ?>_title" id="home_pillar_<?= $i ?>_title" value="<?= e($s['home_pillar_' . $i . '_title'] ?? '') ?>" />
      </div>
      <div class="adm-field">
        <label for="home_pillar_<?= $i ?>_text">Pillar <?= $i ?> — description</label>
        <textarea name="home_pillar_<?= $i ?>_text" id="home_pillar_<?= $i ?>_text" rows="2"><?= e($s['home_pillar_' . $i . '_text'] ?? '') ?></textarea>
      </div>
    <?php endfor; ?>
  </div>
  <?php home_admin_render_save('Save about section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();
cms_sync_plain_home_fields($pdo);

$section = home_admin_require_section('hero');
extract(home_admin_page_vars($section));

$keys = array_merge(
    [
        'home_hero_eyebrow', 'home_hero_lead',
        'home_hero_btn_primary_text', 'home_hero_btn_primary_url', 'home_hero_btn_secondary_text',
        'home_hero_btn_secondary_url', 'home_hero_scroll_text', 'home_hero_video_url',
        'home_hero_tag_1', 'home_hero_tag_2', 'home_hero_tag_3', 'home_hero_tag_4',
        'home_hero_avatar_1', 'home_hero_avatar_2', 'home_hero_avatar_3', 'home_hero_avatar_4', 'home_hero_avatar_5',
        'home_hero_social_text',
        'home_hero_preview_kicker', 'home_hero_preview_title', 'home_hero_preview_meta', 'home_hero_preview_url',
    ],
    cms_hero_headline_setting_keys()
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete_hero_slide') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        if ($slideId > 0) {
            $pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$slideId]);
            admin_flash_set('success', 'Hero image removed.');
        }
        redirect('hero.php');
    }

    if ($action === 'add_hero_slide' && !empty($_FILES['hero_slide_image']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['hero_slide_image']);
        if ($up['ok']) {
            $pdo->exec('UPDATE hero_slides SET sort_order = sort_order + 1');
            $pdo->prepare('INSERT INTO hero_slides (image_path, alt_text, sort_order, is_active) VALUES (?,?,?,1)')
                ->execute([$up['path'], cms_humanize_filename_alt($up['path']), 0]);
            media_register($pdo, $up['path'], basename($up['path']));
            admin_flash_set('success', 'Hero image added.');
        } else {
            admin_flash_set('error', $up['error'] ?? 'Upload failed.');
        }
        redirect('hero.php');
    }

    if ($action === 'update_hero_slide') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        if ($slideId > 0) {
            $alt = trim((string) ($_POST['hero_slide_description'] ?? ''));
            $sort = (int) ($_POST['sort_order'] ?? 0);
            $active = isset($_POST['is_active']) ? 1 : 0;
            if (!empty($_FILES['hero_slide_image']['name'])) {
                $up = Upload::image($appConfig, 'general', $_FILES['hero_slide_image']);
                if ($up['ok']) {
                    $pdo->prepare('UPDATE hero_slides SET image_path=?, alt_text=?, sort_order=?, is_active=? WHERE id=?')
                        ->execute([$up['path'], $alt, $sort, $active, $slideId]);
                    media_register($pdo, $up['path'], basename($up['path']));
                }
            } else {
                $pdo->prepare('UPDATE hero_slides SET alt_text=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$alt, $sort, $active, $slideId]);
            }
            admin_flash_set('success', 'Hero image updated.');
        }
        redirect('hero.php');
    }

    home_admin_save_text_fields($pdo, $keys);
    $headlines = cms_hero_headlines_from_settings($_POST);
    $firstHeadline = $headlines[0] ?? '';
    setting_set($pdo, 'home_hero_title_main', $firstHeadline);
    setting_set($pdo, 'home_hero_title_highlight', '');
    setting_set($pdo, 'home_hero_title_html', $firstHeadline !== '' ? cms_escape($firstHeadline) : '');

    home_admin_save_stats($pdo);
    admin_log_activity($pdo, 'save', 'home-hero', null, 'Home hero updated');
    home_admin_sync_and_redirect('hero.php', 'Hero section saved.');
}

$s = settings_get_many($pdo, array_merge($keys, ['home_hero_preview_image']));
$stats = home_admin_ensure_stats($pdo);
$heroSlides = $pdo->query('SELECT id, image_path, alt_text, sort_order, is_active FROM hero_slides ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<div class="adm-card adm-settings-section adm-glass">
  <h2>Rotating banner images</h2>
  <p class="adm-hint">Large photos behind the headline. New uploads appear first (order 0). Use order <strong>0</strong> for the main background image.</p>
  <div class="adm-media-grid adm-media-grid-large">
    <?php foreach ($heroSlides as $slide): ?>
      <div class="adm-media-item">
        <img src="../../<?= e($slide['image_path']) ?>" alt="" loading="lazy" />
        <form method="post" enctype="multipart/form-data" class="adm-field" style="margin-top:0.5rem;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_hero_slide" />
          <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>" />
          <label>Order</label>
          <input type="number" name="sort_order" value="<?= (int) $slide['sort_order'] ?>" style="width:5rem;" />
          <label><input type="checkbox" name="is_active" value="1"<?= !empty($slide['is_active']) ? ' checked' : '' ?> /> Show on website</label>
          <label>Replace image</label>
          <input type="file" name="hero_slide_image" accept="image/*" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary">Update</button>
        </form>
        <form method="post" data-confirm="Remove this banner image?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_hero_slide" />
          <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Remove</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <form method="post" enctype="multipart/form-data" class="adm-field">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_hero_slide" />
    <label>Add banner image</label>
    <input type="file" name="hero_slide_image" accept="image/*" required />
    <button type="submit" class="adm-btn adm-btn-primary">Add to banner</button>
  </form>
</div>

<form method="post" enctype="multipart/form-data" class="adm-settings-grid">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save" />

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Location line</h2>
    <p class="adm-hint">Small text above the rotating headline — e.g. Rajkot · Gujarat · Since 2010</p>
    <?php home_admin_render_field('home_hero_eyebrow', 'Location line', $s); ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Rotating headlines</h2>
    <p class="adm-hint">Five lines that cycle every 3 seconds in the hero. Leave a line blank to skip it.</p>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <?php home_admin_render_field('home_hero_headline_' . $i, 'Headline ' . $i, $s); ?>
    <?php endfor; ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Sub heading &amp; service tags</h2>
    <?php home_admin_render_field('home_hero_lead', 'Sub heading under title', $s, 'textarea'); ?>
    <p class="adm-hint" style="margin-top:1rem;">Four pill tags with checkmarks</p>
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <?php home_admin_render_field('home_hero_tag_' . $i, 'Tag ' . $i, $s); ?>
    <?php endfor; ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Client trust row</h2>
    <p class="adm-hint">Initials shown in overlapping circles, plus the trust line beside them.</p>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <?php home_admin_render_field('home_hero_avatar_' . $i, 'Circle ' . $i . ' text', $s); ?>
    <?php endfor; ?>
    <?php home_admin_render_field('home_hero_social_text', 'Trust line', $s); ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Call-to-action buttons</h2>
    <?php
    home_admin_render_field('home_hero_btn_primary_text', 'Primary button text', $s);
    home_admin_render_field('home_hero_btn_primary_url', 'Primary button link (optional — opens consultation if empty)', $s);
    home_admin_render_field('home_hero_btn_secondary_text', 'Secondary button text', $s);
    home_admin_render_field('home_hero_btn_secondary_url', 'Secondary button link', $s);
    home_admin_render_field('home_hero_scroll_text', 'Scroll hint text', $s);
    home_admin_render_field('home_hero_video_url', 'Background video URL (MP4, optional)', $s);
    ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Glass statistics panel</h2>
    <p class="adm-hint">Four stats in the top-right glass box. Use formats like <strong>150+</strong>, <strong>2M+</strong>, or <strong>98%</strong>.</p>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="e.g. 150+" aria-label="Number" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="e.g. Projects Delivered" aria-label="Label" />
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card adm-settings-section adm-glass">
    <h2>Featured project card</h2>
    <p class="adm-hint">The thumbnail follows the active rotating banner image. Override the label, title, meta, or link below — leave blank to auto-fill from the matched project.</p>
    <?php
    home_admin_render_field('home_hero_preview_kicker', 'Small label (e.g. Featured Project)', $s);
    home_admin_render_field('home_hero_preview_title', 'Project title (optional)', $s);
    home_admin_render_field('home_hero_preview_meta', 'Location · type line (optional)', $s);
    home_admin_render_field('home_hero_preview_url', 'Link URL (optional)', $s);
    ?>
  </div>

  <?php home_admin_render_save('Save hero section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';
require_once dirname(__DIR__) . '/includes/media.php';

admin_require_auth();

$section = studio_admin_require_section('culture');
extract(studio_admin_page_vars($section));

$MAX_SLOTS = 6;

$keys = [
    'studio_culture_eyebrow', 'studio_culture_title', 'studio_culture_intro',
];
for ($i = 1; $i <= $MAX_SLOTS; $i++) {
    $keys[] = 'studio_culture_caption_' . $i;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? 'save';

    if ($postAction === 'upload_strip_image') {
        $slot = (int) ($_POST['id'] ?? 0);
        if ($slot >= 1 && $slot <= $MAX_SLOTS && !empty($_FILES['strip_image']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['strip_image']);
            if ($up['ok']) {
                setting_set($pdo, 'studio_strip_image_' . $slot, $up['path']);
                media_register($pdo, $up['path'], basename($up['path']));
                content_sync_site_json($pdo);
                admin_flash_set('success', 'Culture image ' . $slot . ' saved.');
            } else {
                admin_flash_set('error', $up['error'] ?? 'Upload failed.');
            }
        } else {
            admin_flash_set('error', 'Choose an image file first.');
        }
        redirect('culture.php');
    }

    if ($postAction === 'remove_strip_image') {
        $slot = (int) ($_POST['id'] ?? 0);
        if ($slot >= 1 && $slot <= $MAX_SLOTS) {
            setting_set($pdo, 'studio_strip_image_' . $slot, '');
            setting_set($pdo, 'studio_culture_caption_' . $slot, '');
            content_sync_site_json($pdo);
            admin_flash_set('success', 'Image ' . $slot . ' removed.');
        }
        redirect('culture.php');
    }

    if ($postAction === 'save') {
        studio_admin_save_settings($pdo, $keys);
        admin_log_activity($pdo, 'save', 'studio-culture', null, 'Studio culture updated');
        studio_admin_sync_and_redirect('culture.php', 'Culture section saved.');
    }
}

$imageKeys = [];
for ($i = 1; $i <= $MAX_SLOTS; $i++) {
    $imageKeys[] = 'studio_strip_image_' . $i;
}
$s = studio_admin_load_settings($pdo, array_merge($keys, $imageKeys));

// Count filled slots and find next empty slot
$filledSlots = [];
for ($i = 1; $i <= $MAX_SLOTS; $i++) {
    if (!empty($s['studio_strip_image_' . $i])) {
        $filledSlots[] = $i;
    }
}
$nextEmptySlot = null;
for ($i = 1; $i <= $MAX_SLOTS; $i++) {
    if (empty($s['studio_strip_image_' . $i])) {
        $nextEmptySlot = $i;
        break;
    }
}

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid" style="margin-bottom:1.5rem;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save" />
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('studio_culture_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_culture_title', 'Title', $s);
    home_admin_render_field('studio_culture_intro', 'Intro paragraph', $s, 'textarea');
    ?>
  </div>
  <?php if (!empty($filledSlots)): ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Photo captions</h2>
    <p class="adm-hint">Edit captions for each uploaded photo. Save after changing.</p>
    <?php foreach ($filledSlots as $i): ?>
      <?php home_admin_render_field('studio_culture_caption_' . $i, 'Caption for image ' . $i, $s); ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php home_admin_render_save('Save culture section'); ?>
</form>

<div class="adm-card" style="margin-bottom:1.5rem;">
  <h2>Culture photos (<?= count($filledSlots) ?> / <?= $MAX_SLOTS ?> images)</h2>
  <p class="adm-hint">Upload up to <?= $MAX_SLOTS ?> photos. They appear as a grid on the Studio page. Add or remove images freely.</p>
  <div class="adm-service-blocks-grid">
    <?php foreach ($filledSlots as $i):
        $stripPath = $s['studio_strip_image_' . $i] ?? '';
    ?>
      <article class="adm-service-block-card">
        <div class="adm-service-block-thumb">
          <img src="../../<?= e($stripPath) ?>" alt="" loading="lazy" />
          <span class="adm-service-block-num"><?= $i ?></span>
        </div>
        <p class="adm-hint" style="margin:.5rem 0;"><?= e($s['studio_culture_caption_' . $i] ?? '') ?></p>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <form method="post" enctype="multipart/form-data" style="display:contents;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload_strip_image" />
            <input type="hidden" name="id" value="<?= $i ?>" />
            <input type="file" name="strip_image" accept="image/*" required style="font-size:.8rem;max-width:160px;" />
            <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary">Replace</button>
          </form>
          <form method="post" data-confirm="Remove image <?= $i ?>?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove_strip_image" />
            <input type="hidden" name="id" value="<?= $i ?>" />
            <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($nextEmptySlot !== null && count($filledSlots) < $MAX_SLOTS): ?>
<div class="adm-card">
  <h2>Add a photo (slot <?= $nextEmptySlot ?>)</h2>
  <p class="adm-hint">You have <?= count($filledSlots) ?> of <?= $MAX_SLOTS ?> slots filled. Upload another photo to add it to the grid.</p>
  <form method="post" enctype="multipart/form-data" class="adm-field" style="max-width:480px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload_strip_image" />
    <input type="hidden" name="id" value="<?= $nextEmptySlot ?>" />
    <label>Photo</label>
    <input type="file" name="strip_image" accept="image/*" required />
    <label>Caption (optional — you can also set it above after uploading)</label>
    <input type="text" name="studio_culture_caption_<?= $nextEmptySlot ?>" value="" placeholder="e.g. Material selection" />
    <button type="submit" class="adm-btn adm-btn-primary" style="margin-top:.75rem;"><i class="fa-solid fa-plus"></i> Add photo</button>
  </form>
</div>
<?php else: ?>
<div class="adm-card">
  <p class="adm-hint">All <?= $MAX_SLOTS ?> slots are filled. Remove a photo above to add a different one.</p>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

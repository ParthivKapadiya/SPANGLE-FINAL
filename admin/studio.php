<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once SPANGLE_ROOT . '/includes/cmsCopyKeys.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();

$action = $_GET['action'] ?? 'page';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$pageKeys = [
    'studio_kicker', 'studio_title', 'studio_lead',
    'studio_philosophy_eyebrow', 'studio_philosophy_title',
    'studio_philosophy_lead_1', 'studio_philosophy_lead_2',
    'studio_values_eyebrow', 'studio_values_title', 'studio_pullquote',
    'studio_cta_text', 'studio_cta_btn_text', 'studio_cta_btn_url',
];

$labels = [
    'studio_kicker' => 'Top banner — small label',
    'studio_title' => 'Top banner — main heading',
    'studio_lead' => 'Top banner — intro text',
    'studio_philosophy_eyebrow' => 'Philosophy — small label',
    'studio_philosophy_title' => 'Philosophy — heading',
    'studio_philosophy_lead_1' => 'Philosophy — first paragraph',
    'studio_philosophy_lead_2' => 'Philosophy — second paragraph',
    'studio_values_eyebrow' => 'Highlights — small label (home + studio page)',
    'studio_values_title' => 'Highlights — heading (home + studio page)',
    'studio_pullquote' => 'Pull quote',
    'studio_cta_text' => 'Bottom section — text',
    'studio_cta_btn_text' => 'Bottom button — label',
    'studio_cta_btn_url' => 'Bottom button — link',
];

$imageKeys = [
    'studio_hero_image' => 'Top banner background',
    'studio_philosophy_image' => 'Philosophy section image',
    'studio_strip_image_1' => 'Photo strip — image 1',
    'studio_strip_image_2' => 'Photo strip — image 2',
    'studio_strip_image_3' => 'Photo strip — image 3',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? 'save_page';

    if ($postAction === 'delete_highlight' && $id > 0) {
        $pdo->prepare('DELETE FROM awards WHERE id = ?')->execute([$id]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Highlight removed.');
        redirect('studio.php');
    }

    if ($postAction === 'upload_strip_image' && $id >= 1 && $id <= 3) {
        $settingKey = 'studio_strip_image_' . $id;
        if (!empty($_FILES['strip_image']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['strip_image']);
            if ($up['ok']) {
                setting_set($pdo, $settingKey, $up['path']);
                media_register($pdo, $up['path'], basename($up['path']));
                content_sync_site_json($pdo);
                admin_flash_set('success', 'Strip image ' . $id . ' saved.');
            } else {
                admin_flash_set('error', $up['error'] ?? 'Upload failed.');
            }
        } else {
            admin_flash_set('error', 'Choose an image file first.');
        }
        redirect('studio.php');
    }

    if ($postAction === 'save_highlight') {
        $icon = trim($_POST['icon_class'] ?? 'fas fa-star');
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            $pdo->prepare(
                'UPDATE awards SET icon_class=?, title=?, subtitle=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([$icon, $title, $subtitle, $sort, $active, $editId]);
        } else {
            $pdo->prepare(
                'INSERT INTO awards (icon_class, title, subtitle, sort_order, is_active) VALUES (?,?,?,?,?)'
            )->execute([$icon, $title, $subtitle, $sort, $active]);
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Highlight saved.');
        redirect('studio.php');
    }

    if ($postAction === 'save_page') {
        foreach ($pageKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                setting_set($pdo, $key, trim((string) $_POST[$key]));
            }
        }
        $eyebrow = trim((string) ($_POST['studio_values_eyebrow'] ?? ''));
        $title = trim((string) ($_POST['studio_values_title'] ?? ''));
        cms_sync_studio_highlight_headings($pdo, $eyebrow, $title);

        foreach ($imageKeys as $key => $label) {
            $fileKey = $key . '_file';
            if (!empty($_FILES[$fileKey]['name'])) {
                $up = Upload::image($appConfig, 'general', $_FILES[$fileKey]);
                if ($up['ok']) {
                    setting_set($pdo, $key, $up['path']);
                    media_register($pdo, $up['path'], basename($up['path']));
                }
            }
        }

        content_sync_site_json($pdo);
        admin_flash_set('success', 'Studio page saved.');
        redirect('studio.php');
    }
}

if ($action === 'edit_highlight' || $action === 'new_highlight') {
    $row = [
        'id' => 0, 'icon_class' => 'fas fa-star', 'title' => '', 'subtitle' => '',
        'sort_order' => 0, 'is_active' => 1,
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM awards WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit highlight' : 'Add highlight';
    $pageDescription = 'Shown on the home page (with icon) and as a card on studio.html.';
    $activeNav = 'studio';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_highlight" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field">
        <label>Icon (Font Awesome class)</label>
        <input type="text" name="icon_class" value="<?= e($row['icon_class']) ?>" placeholder="fas fa-handshake" />
        <p class="adm-hint">Used on the <strong>home page</strong> only. Example: <code>fas fa-map-location-dot</code></p>
      </div>
      <div class="adm-field"><label>Title</label><input type="text" name="title" value="<?= e($row['title']) ?>" required /></div>
      <div class="adm-field">
        <label>Description</label>
        <textarea name="subtitle" rows="3" required><?= e($row['subtitle'] ?? '') ?></textarea>
        <p class="adm-hint">Same text on home and Studio page cards.</p>
      </div>
      <div class="adm-field"><label>Order</label><input type="number" name="sort_order" value="<?= (int) $row['sort_order'] ?>" style="width:6rem;" /></div>
      <div class="adm-field">
        <label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible on website (home highlights + studio page cards)</label>
      </div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save</button>
        <a href="studio.php" class="adm-btn adm-btn-ghost">Back to Studio page</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$s = settings_get_many($pdo, array_merge($pageKeys, array_keys($imageKeys)));
$highlights = $pdo->query(
    'SELECT id, icon_class, title, subtitle, sort_order, is_active FROM awards ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Studio page';
$pageDescription = 'Edit studio.html and the matching “Why clients work with us” block on the home page.';
$activeNav = 'studio';
$mainClass = 'adm-main--wide';
require __DIR__ . '/includes/layout.php';
?>

<p class="adm-hint adm-card" style="margin-bottom:1rem;">
  Preview <a href="../studio.html" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost">studio.html</a>.
  Each <strong>highlight</strong> appears on the home page (with icon) and on the Studio page (value cards).
</p>

<form method="post" enctype="multipart/form-data" style="margin-bottom:1.5rem;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_page" />

  <div class="adm-card">
    <h2>Top banner</h2>
    <?php foreach (['studio_kicker', 'studio_title', 'studio_lead'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <?php if ($key === 'studio_lead'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="3"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label><?= e($imageKeys['studio_hero_image']) ?></label>
      <?php if (!empty($s['studio_hero_image'])): ?>
        <p><img src="../<?= e($s['studio_hero_image']) ?>" alt="" class="adm-service-preview" /></p>
      <?php endif; ?>
      <input type="file" name="studio_hero_image_file" accept="image/*" />
    </div>
  </div>

  <div class="adm-card">
    <h2>Philosophy section</h2>
    <?php foreach (['studio_philosophy_eyebrow', 'studio_philosophy_title'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
    <?php foreach (['studio_philosophy_lead_1', 'studio_philosophy_lead_2'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="4"><?= e($s[$key] ?? '') ?></textarea>
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label><?= e($imageKeys['studio_philosophy_image']) ?></label>
      <?php if (!empty($s['studio_philosophy_image'])): ?>
        <p><img src="../<?= e($s['studio_philosophy_image']) ?>" alt="" class="adm-service-preview" /></p>
      <?php endif; ?>
      <input type="file" name="studio_philosophy_image_file" accept="image/*" />
    </div>
  </div>

  <div class="adm-card">
    <h2>Highlights section headings</h2>
    <?php foreach (['studio_values_eyebrow', 'studio_values_title'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label for="studio_pullquote"><?= e($labels['studio_pullquote']) ?></label>
      <textarea name="studio_pullquote" id="studio_pullquote" rows="2"><?= e($s['studio_pullquote'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="adm-card">
    <h2>Bottom invitation</h2>
    <?php foreach (['studio_cta_text', 'studio_cta_btn_text', 'studio_cta_btn_url'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <?php if ($key === 'studio_cta_text'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save page text &amp; banner images</button>
  </div>
</form>

<h2 class="adm-section-heading">Photo strip (3 images)</h2>
<div class="adm-service-blocks-grid" style="margin-bottom:1.5rem;">
  <?php for ($i = 1; $i <= 3; $i++):
      $stripKey = 'studio_strip_image_' . $i;
      $stripPath = $s[$stripKey] ?? '';
      $hasImage = $stripPath !== '';
      ?>
    <article class="adm-service-block-card adm-card">
      <div class="adm-service-block-thumb<?= $hasImage ? '' : ' is-empty' ?>">
        <?php if ($hasImage): ?>
          <img src="../<?= e($stripPath) ?>" alt="" loading="lazy" />
        <?php else: ?>
          <span class="adm-service-block-placeholder">No image</span>
        <?php endif; ?>
        <span class="adm-service-block-num"><?= $i ?></span>
      </div>
      <h3 class="adm-service-block-title">Strip image <?= $i ?></h3>
      <form method="post" enctype="multipart/form-data" class="adm-service-block-upload">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_strip_image" />
        <input type="hidden" name="id" value="<?= $i ?>" />
        <input type="file" name="strip_image" accept="image/*" required />
        <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary"><?= $hasImage ? 'Replace image' : 'Upload image' ?></button>
      </form>
    </article>
  <?php endfor; ?>
</div>

<h2 class="adm-section-heading">Highlight blocks</h2>
<p class="adm-hint" style="margin-bottom:1rem;">Each block is live on the <strong>home page</strong> (icon + text) and <strong>studio.html</strong> (title + text cards).</p>
<div class="adm-actions" style="margin-bottom:1rem;">
  <a href="studio.php?action=new_highlight" class="adm-btn adm-btn-primary">Add highlight</a>
</div>
<div class="adm-service-blocks-grid">
  <?php foreach ($highlights as $row): ?>
    <article class="adm-service-block-card adm-card">
      <div class="adm-service-block-thumb is-empty" style="min-height:100px;">
        <i class="<?= e($row['icon_class']) ?>" style="font-size:2rem;color:var(--adm-muted);" aria-hidden="true"></i>
      </div>
      <h3 class="adm-service-block-title"><?= e($row['title']) ?></h3>
      <p class="adm-hint adm-service-block-meta" style="margin:0 0 0.5rem;line-height:1.4;"><?= e($row['subtitle'] ?? '') ?></p>
      <p class="adm-hint adm-service-block-meta">
        <?= !empty($row['is_active']) ? 'Live on home + studio page' : 'Hidden on site' ?>
      </p>
      <div class="adm-row-actions adm-service-block-actions">
        <a href="studio.php?action=edit_highlight&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
        <form method="post" data-confirm="Delete this highlight?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_highlight" />
          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Delete</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/layout-end.php'; ?>

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();

$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);
    if ($postAction === 'bulk_update') {
        $ids = $_POST['gallery_ids'] ?? [];
        $bulkAction = (string) ($_POST['bulk_action'] ?? '');
        if (!is_array($ids) || $ids === []) {
            admin_flash_set('error', 'Select at least one image.');
            redirect('gallery.php');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            admin_flash_set('error', 'No valid images selected.');
            redirect('gallery.php');
        }
        $allowed = ['show_on_home', 'hide_from_home', 'activate', 'deactivate', 'delete'];
        if (!in_array($bulkAction, $allowed, true)) {
            admin_flash_set('error', 'Choose a bulk action.');
            redirect('gallery.php');
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $count = count($ids);
        switch ($bulkAction) {
            case 'show_on_home':
                $pdo->prepare("UPDATE gallery_items SET show_on_home = 1 WHERE id IN ($placeholders)")->execute($ids);
                $message = "Set $count image(s) to show on home page.";
                break;
            case 'hide_from_home':
                $pdo->prepare("UPDATE gallery_items SET show_on_home = 0 WHERE id IN ($placeholders)")->execute($ids);
                $message = "Removed $count image(s) from home page gallery.";
                break;
            case 'activate':
                $pdo->prepare("UPDATE gallery_items SET is_active = 1 WHERE id IN ($placeholders)")->execute($ids);
                $message = "Activated $count image(s).";
                break;
            case 'deactivate':
                $pdo->prepare("UPDATE gallery_items SET is_active = 0 WHERE id IN ($placeholders)")->execute($ids);
                $message = "Deactivated $count image(s).";
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM gallery_items WHERE id IN ($placeholders)")->execute($ids);
                $message = "Deleted $count image(s).";
                break;
            default:
                $message = 'Updated gallery.';
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', $message);
        $filterStatus = trim((string) ($_POST['filter_status'] ?? ''));
        redirect('gallery.php' . ($filterStatus !== '' && $filterStatus !== 'all' ? '?filter_status=' . rawurlencode($filterStatus) : ''));
    }
    if ($postAction === 'delete' && $postId > 0) {
        $pdo->prepare('DELETE FROM gallery_items WHERE id = ?')->execute([$postId]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Gallery image removed.');
        redirect('gallery.php');
    }
    if ($postAction === 'save') {
        $alt = trim($_POST['alt_text'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $onHome = isset($_POST['show_on_home']) ? 1 : 0;
        $imagePath = '';
        if (!empty($_FILES['image_path']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['image_path']);
            if ($up['ok']) {
                $imagePath = $up['path'];
                media_register($pdo, $up['path'], basename($up['path']));
            }
        }
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            if ($imagePath !== '') {
                $pdo->prepare(
                    'UPDATE gallery_items SET alt_text=?, caption=?, sort_order=?, is_active=?, show_on_home=?, image_path=? WHERE id=?'
                )->execute([$alt, $caption, $sort, $active, $onHome, $imagePath, $editId]);
            } else {
                $pdo->prepare(
                    'UPDATE gallery_items SET alt_text=?, caption=?, sort_order=?, is_active=?, show_on_home=? WHERE id=?'
                )->execute([$alt, $caption, $sort, $active, $onHome, $editId]);
            }
        } else {
            if ($imagePath === '') {
                admin_flash_set('error', 'Please upload an image.');
                redirect('gallery.php?action=new');
            }
            $pdo->prepare(
                'INSERT INTO gallery_items (image_path, alt_text, caption, sort_order, is_active, show_on_home) VALUES (?,?,?,?,?,?)'
            )->execute([$imagePath, $alt, $caption, $sort, $active, $onHome]);
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Gallery image saved.');
        redirect('gallery.php');
    }
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'image_path' => '', 'alt_text' => '', 'caption' => '', 'sort_order' => 0, 'is_active' => 1, 'show_on_home' => 1];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM gallery_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit gallery image' : 'Add gallery image';
    $activeNav = 'gallery';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field">
        <label>Image <?= admin_tooltip('Shown on the home page gallery') ?></label>
        <?php if (!empty($row['image_path'])): ?><p><img src="../<?= e($row['image_path']) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p><?php endif; ?>
        <input type="file" name="image_path" accept="image/*"<?= empty($row['id']) ? ' required' : '' ?> />
      </div>
      <div class="adm-field"><label>Description for accessibility</label><input type="text" name="alt_text" value="<?= e($row['alt_text'] ?? '') ?>" /></div>
      <div class="adm-field"><label>Caption (optional)</label><input type="text" name="caption" value="<?= e($row['caption'] ?? '') ?>" /></div>
      <div class="adm-field"><label>Display order</label><input type="number" name="sort_order" value="<?= (int) ($row['sort_order'] ?? 0) ?>" /></div>
      <div class="adm-field"><label><input type="checkbox" name="show_on_home" value="1"<?= !empty($row['show_on_home']) ? ' checked' : '' ?> /> Show on home page</label></div>
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save</button>
        <a href="gallery.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$filterStatus = trim((string) ($_GET['filter_status'] ?? ''));
if ($filterStatus === 'all') {
    $filterStatus = '';
}
$listSql = 'SELECT id, image_path, alt_text, caption, show_on_home, is_active, sort_order FROM gallery_items';
$listParams = [];
if ($filterStatus === 'on_home') {
    $listSql .= ' WHERE show_on_home = 1 AND is_active = 1';
} elseif ($filterStatus === 'hidden_from_home') {
    $listSql .= ' WHERE show_on_home = 0';
} elseif ($filterStatus === 'inactive') {
    $listSql .= ' WHERE is_active = 0';
}
$listSql .= ' ORDER BY sort_order ASC, id ASC';
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($listParams);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$galleryLimit = home_gallery_limit_clamp((int) (settings_get_many($pdo, ['home_gallery_limit'])['home_gallery_limit'] ?? 12));
$galleryLimitMax = home_gallery_limit_max();
$galleryLimitMin = home_gallery_limit_min();

$pageTitle = 'Home gallery';
$pageDescription = 'Images in the scrolling gallery on your home page. Select multiple images to update at once.';
$activeNav = 'gallery';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;">
  <a href="gallery.php?action=new" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Add image</a>
</div>

<form method="get" class="adm-card adm-search-bar" style="margin-bottom:1rem;">
  <label for="filter_status" class="adm-hint" style="margin:0;">Show</label>
  <select name="filter_status" id="filter_status" onchange="this.form.submit()">
    <option value="all"<?= $filterStatus === '' ? ' selected' : '' ?>>All images</option>
    <option value="on_home"<?= $filterStatus === 'on_home' ? ' selected' : '' ?>>On home page</option>
    <option value="hidden_from_home"<?= $filterStatus === 'hidden_from_home' ? ' selected' : '' ?>>Hidden from home</option>
    <option value="inactive"<?= $filterStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option>
  </select>
  <span class="adm-hint"><?= count($rows) ?> image<?= count($rows) === 1 ? '' : 's' ?><?= $filterStatus !== '' ? ' in this filter' : '' ?></span>
</form>

<form
  method="post"
  class="adm-card"
  id="adm-gallery-bulk-form"
  data-bulk-picker
  data-bulk-limit="<?= $galleryLimit ?>"
  data-gallery-total="<?= count($rows) ?>"
  data-confirm="Apply this action to the selected gallery images?"
>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="bulk_update" />
  <?php if ($filterStatus !== ''): ?>
    <input type="hidden" name="filter_status" value="<?= e($filterStatus) ?>" />
  <?php endif; ?>

  <div class="adm-gallery-limit-row">
    <div>
      <p class="adm-hint" style="margin:0 0 0.35rem;">Quick select for home gallery</p>
      <p class="adm-hint adm-gallery-limit-hint" id="adm-gallery-limit-hint" style="margin:0;">
        Tap a number (<?= $galleryLimitMin ?>–<?= $galleryLimitMax ?>) to auto-select that many images. Home page currently shows up to <?= $galleryLimit ?>.
      </p>
    </div>
    <input type="hidden" id="gallery_select_limit" value="<?= $galleryLimit ?>" />
    <div
      class="adm-limit-picker adm-limit-picker--gallery"
      data-target="gallery_select_limit"
      data-bulk-form="adm-gallery-bulk-form"
      role="group"
      aria-label="Number of gallery images to select"
    >
      <?php for ($n = $galleryLimitMin; $n <= $galleryLimitMax; $n++): ?>
        <button type="button" class="adm-limit-btn<?= $galleryLimit === $n ? ' is-active' : '' ?>" data-value="<?= $n ?>"><?= $n ?></button>
      <?php endfor; ?>
      <button type="button" class="adm-limit-btn adm-limit-btn--all" data-value="all">All</button>
    </div>
    <div class="adm-gallery-limit-custom">
      <label for="gallery_select_custom" class="adm-hint">Or enter exact count</label>
      <input
        type="number"
        id="gallery_select_custom"
        min="<?= $galleryLimitMin ?>"
        max="<?= $galleryLimitMax ?>"
        value="<?= $galleryLimit ?>"
        step="1"
      />
      <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost" id="gallery_select_custom_btn">Select</button>
    </div>
  </div>

  <div class="adm-toolbar adm-bulk-toolbar">
    <label class="adm-bulk-select-all-label">
      <input type="checkbox" data-bulk-select-all />
      Select all
    </label>
    <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost" data-bulk-clear>Clear</button>
    <span class="adm-bulk-count" data-bulk-count>0 selected</span>
    <span class="adm-bulk-toolbar-divider" aria-hidden="true"></span>
    <label class="adm-hint" for="bulk_action" style="margin:0;">Action</label>
    <select name="bulk_action" id="bulk_action" data-bulk-action>
      <option value="show_on_home">Show on home page</option>
      <option value="hide_from_home">Hide from home page</option>
      <option value="activate">Activate</option>
      <option value="deactivate">Deactivate</option>
      <option value="delete">Delete permanently</option>
    </select>
    <button type="submit" class="adm-btn adm-btn-primary" data-bulk-apply disabled>
      <i class="fa-solid fa-check"></i> Apply
    </button>
  </div>

  <?php if ($rows === []): ?>
    <p class="adm-hint" style="padding:1rem 0 0;">No gallery images found<?= $filterStatus !== '' ? ' for this filter' : '' ?>.</p>
  <?php else: ?>
    <div class="adm-project-picker-grid" data-bulk-grid>
      <?php foreach ($rows as $row):
          $onHome = !empty($row['show_on_home']);
          $active = !empty($row['is_active']);
          if (!$active) {
              $statusLabel = 'Inactive';
          } elseif ($onHome) {
              $statusLabel = 'On home';
          } else {
              $statusLabel = 'Hidden';
          }
          $label = trim((string) ($row['alt_text'] ?? '')) ?: trim((string) ($row['caption'] ?? '')) ?: 'No description';
          ?>
        <label class="adm-project-picker-item" data-bulk-item>
          <input type="checkbox" name="gallery_ids[]" value="<?= (int) $row['id'] ?>" class="adm-bulk-check" />
          <span class="adm-project-picker-thumb">
            <img src="../<?= e($row['image_path']) ?>" alt="<?= e($row['alt_text']) ?>" loading="lazy" />
            <span class="adm-badge adm-project-picker-badge"><?= e($statusLabel) ?></span>
          </span>
          <span class="adm-project-picker-meta">
            <strong><?= e($label) ?></strong>
            <?php if (!empty($row['caption']) && $row['caption'] !== $row['alt_text']): ?>
              <span><?= e($row['caption']) ?></span>
            <?php endif; ?>
          </span>
          <span class="adm-project-picker-actions">
            <a href="gallery.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost" onclick="event.preventDefault(); event.stopPropagation(); window.location=this.href;">Edit</a>
            <button
              type="button"
              class="adm-btn adm-btn-sm adm-btn-danger adm-gallery-delete-btn"
              data-gallery-id="<?= (int) $row['id'] ?>"
              data-gallery-label="<?= e($label) ?>"
              onclick="event.stopPropagation();"
            >Delete</button>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</form>
<form method="post" id="adm-gallery-delete-form" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete" />
  <input type="hidden" name="id" id="adm-gallery-delete-id" value="" />
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

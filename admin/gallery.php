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

$rows = $pdo->query(
    'SELECT id, image_path, alt_text, show_on_home, is_active, sort_order FROM gallery_items ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Home gallery';
$pageDescription = 'Images in the scrolling gallery on your home page.';
$activeNav = 'gallery';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;">
  <a href="gallery.php?action=new" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Add image</a>
</div>
<div class="adm-media-grid adm-media-grid-large">
  <?php foreach ($rows as $row): ?>
    <div class="adm-media-item">
      <img src="../<?= e($row['image_path']) ?>" alt="<?= e($row['alt_text']) ?>" loading="lazy" />
      <p class="adm-media-path"><?= e($row['alt_text'] ?: 'No description') ?></p>
      <p class="adm-hint"><?= !empty($row['show_on_home']) ? 'On home' : 'Hidden from home' ?> · <?= !empty($row['is_active']) ? 'Active' : 'Off' ?></p>
      <div class="adm-actions">
        <a href="gallery.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
        <form method="post" data-confirm="Delete this gallery image?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

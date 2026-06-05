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
        $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$postId]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Service removed.');
        redirect('services.php');
    }
    if ($postAction === 'upload_image' && $postId > 0) {
        if (!empty($_FILES['image_path']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['image_path']);
            if ($up['ok']) {
                $pdo->prepare('UPDATE services SET image_path=? WHERE id=?')->execute([$up['path'], $postId]);
                media_register($pdo, $up['path'], basename($up['path']));
                content_sync_site_json($pdo);
                admin_flash_set('success', 'Image saved for this service block.');
            } else {
                admin_flash_set('error', $up['error'] ?? 'Upload failed.');
            }
        } else {
            admin_flash_set('error', 'Choose an image file first.');
        }
        redirect('services.php');
    }
    if ($postAction === 'save') {
        $data = [
            trim($_POST['number_label'] ?? '01'),
            trim($_POST['title'] ?? ''),
            trim($_POST['short_description'] ?? ''),
            trim($_POST['eyebrow'] ?? ''),
            trim($_POST['detail_title'] ?? ''),
            trim($_POST['detail_lead_1'] ?? ''),
            trim($_POST['detail_lead_2'] ?? ''),
            isset($_POST['show_on_home']) ? 1 : 0,
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
        ];
        $imagePath = null;
        if (!empty($_FILES['image_path']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['image_path']);
            if ($up['ok']) {
                $imagePath = $up['path'];
                media_register($pdo, $up['path'], basename($up['path']));
            }
        }
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            if ($imagePath) {
                $pdo->prepare(
                    'UPDATE services SET number_label=?, title=?, short_description=?, eyebrow=?, detail_title=?, detail_lead_1=?, detail_lead_2=?, show_on_home=?, sort_order=?, is_active=?, image_path=? WHERE id=?'
                )->execute(array_merge($data, [$imagePath, $editId]));
            } else {
                $pdo->prepare(
                    'UPDATE services SET number_label=?, title=?, short_description=?, eyebrow=?, detail_title=?, detail_lead_1=?, detail_lead_2=?, show_on_home=?, sort_order=?, is_active=? WHERE id=?'
                )->execute(array_merge($data, [$editId]));
            }
        } else {
            $pdo->prepare(
                'INSERT INTO services (number_label, title, short_description, eyebrow, detail_title, detail_lead_1, detail_lead_2, show_on_home, sort_order, is_active, image_path)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute(array_merge($data, [$imagePath ?? '']));
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Service saved.');
        redirect('services.php');
    }
}

if ($action === 'edit' || $action === 'new') {
    $row = [
        'id' => 0, 'number_label' => '01', 'title' => '', 'short_description' => '', 'eyebrow' => '',
        'detail_title' => '', 'detail_lead_1' => '', 'detail_lead_2' => '', 'image_path' => '',
        'show_on_home' => 1, 'sort_order' => 0, 'is_active' => 1,
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit service' : 'Add service';
    $activeNav = 'services';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field"><label>Number (e.g. 01)</label><input type="text" name="number_label" value="<?= e($row['number_label']) ?>" /></div>
      <div class="adm-field"><label>Title</label><input type="text" name="title" value="<?= e($row['title']) ?>" required /></div>
      <div class="adm-field adm-field--highlight">
        <label>Image (shown on Services page &amp; home card)</label>
        <?php if (!empty($row['image_path'])): ?>
          <p><img src="../<?= e($row['image_path']) ?>" alt="" class="adm-service-preview" /></p>
        <?php else: ?>
          <p class="adm-hint">No image yet — upload one below.</p>
        <?php endif; ?>
        <input type="file" name="image_path" accept="image/*" />
      </div>
      <div class="adm-field">
        <label>Short description (home page card)</label>
        <textarea name="short_description" rows="2"><?= e($row['short_description'] ?? '') ?></textarea>
      </div>
      <div class="adm-field">
        <label>Eyebrow on Services page</label>
        <input type="text" name="eyebrow" value="<?= e($row['eyebrow'] ?? '') ?>" placeholder="e.g. 01 — Architecture" />
      </div>
      <div class="adm-field"><label>Heading on Services page</label><input type="text" name="detail_title" value="<?= e($row['detail_title'] ?? '') ?>" /></div>
      <div class="adm-field"><label>First paragraph (Services page)</label><textarea name="detail_lead_1" rows="3"><?= e($row['detail_lead_1'] ?? '') ?></textarea></div>
      <div class="adm-field"><label>Second paragraph (Services page)</label><textarea name="detail_lead_2" rows="3"><?= e($row['detail_lead_2'] ?? '') ?></textarea></div>
      <input type="hidden" name="show_on_home" value="1" />
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible on website (home, services page, footer)</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save</button>
        <a href="services.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$rows = $pdo->query(
    'SELECT id, number_label, title, image_path, show_on_home, is_active FROM services ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Service blocks';
$pageDescription = 'Each block appears on the home page, services.html, and the site footer. Use Visible to hide one without deleting it.';
$activeNav = 'services';
$mainClass = 'adm-main--wide';
require __DIR__ . '/includes/layout.php';
?>
<p class="adm-hint adm-card" style="margin-bottom:1rem;">
  Page banner and bottom CTA are under <a href="services-page.php" class="adm-btn adm-btn-sm adm-btn-ghost">Services page</a>.
  Titles and service-page copy are in <strong>Edit text</strong> for each block.
</p>
<div class="adm-actions" style="margin-bottom:1rem;"><a href="services.php?action=new" class="adm-btn adm-btn-primary">Add service block</a></div>
<div class="adm-service-blocks-grid">
  <?php foreach ($rows as $row):
      $hasImage = !empty($row['image_path']);
      ?>
    <article class="adm-service-block-card adm-card">
      <div class="adm-service-block-thumb<?= $hasImage ? '' : ' is-empty' ?>">
        <?php if ($hasImage): ?>
          <img src="../<?= e($row['image_path']) ?>" alt="" loading="lazy" />
        <?php else: ?>
          <span class="adm-service-block-placeholder">No image</span>
        <?php endif; ?>
        <span class="adm-service-block-num"><?= e($row['number_label']) ?></span>
      </div>
      <h3 class="adm-service-block-title"><?= e($row['title']) ?></h3>
      <p class="adm-hint adm-service-block-meta">
        <?= !empty($row['is_active']) ? 'Live on site (home, services page, footer)' : 'Hidden on site' ?>
      </p>
      <form method="post" enctype="multipart/form-data" class="adm-service-block-upload">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_image" />
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
        <input type="file" name="image_path" accept="image/*" required />
        <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary"><?= $hasImage ? 'Replace image' : 'Upload image' ?></button>
      </form>
      <div class="adm-row-actions adm-service-block-actions">
        <a href="services.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit text</a>
        <form method="post" data-confirm="Delete this service?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Delete</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

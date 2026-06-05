<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

admin_require_auth();

$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);
    if ($postAction === 'delete' && $postId > 0) {
        $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$postId]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Testimonial removed.');
        redirect('testimonials.php');
    }
    if ($postAction === 'save') {
        $quote = trim($_POST['quote'] ?? '');
        $name = trim($_POST['author_name'] ?? '');
        $role = trim($_POST['author_role'] ?? '');
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $photo = null;
        if (!empty($_FILES['author_photo']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['author_photo']);
            if ($up['ok']) {
                $photo = $up['path'];
                require_once __DIR__ . '/includes/media.php';
                media_register($pdo, $up['path'], basename($up['path']));
            }
        }
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            if ($photo) {
                $pdo->prepare(
                    'UPDATE testimonials SET quote=?, author_name=?, author_role=?, rating=?, sort_order=?, is_active=?, author_photo=? WHERE id=?'
                )->execute([$quote, $name, $role, $rating, $sort, $active, $photo, $editId]);
            } else {
                $pdo->prepare(
                    'UPDATE testimonials SET quote=?, author_name=?, author_role=?, rating=?, sort_order=?, is_active=? WHERE id=?'
                )->execute([$quote, $name, $role, $rating, $sort, $active, $editId]);
            }
        } else {
            $pdo->prepare(
                'INSERT INTO testimonials (quote, author_name, author_role, rating, sort_order, is_active, author_photo) VALUES (?,?,?,?,?,?,?)'
            )->execute([$quote, $name, $role, $rating, $sort, $active, $photo ?? '']);
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Testimonial saved.');
        redirect('testimonials.php');
    }
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'quote' => '', 'author_name' => '', 'author_role' => '', 'rating' => 5, 'sort_order' => 0, 'is_active' => 1, 'author_photo' => ''];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit testimonial' : 'Add testimonial';
    $activeNav = 'testimonials';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field"><label>Review</label><textarea name="quote" rows="4" required><?= e($row['quote']) ?></textarea></div>
      <div class="adm-field"><label>Client name</label><input type="text" name="author_name" value="<?= e($row['author_name']) ?>" required /></div>
      <div class="adm-field"><label>Project type (e.g. Residential Villa)</label><input type="text" name="author_role" value="<?= e($row['author_role'] ?? '') ?>" placeholder="Residential · Commercial · Interior" /></div>
      <div class="adm-field"><label>Rating (1–5)</label><input type="number" name="rating" min="1" max="5" value="<?= (int) ($row['rating'] ?? 5) ?>" /></div>
      <div class="adm-field"><label>Client photo</label>
        <?php if (!empty($row['author_photo'])): ?><img src="../<?= e($row['author_photo']) ?>" alt="" style="max-width:80px;border-radius:50%;" /><?php endif; ?>
        <input type="file" name="author_photo" accept="image/*" /></div>
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Show on website</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save</button>
        <a href="testimonials.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$rows = $pdo->query('SELECT id, author_name, author_role, is_active FROM testimonials ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Testimonials';
$activeNav = 'testimonials';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;"><a href="testimonials.php?action=new" class="adm-btn adm-btn-primary">Add testimonial</a></div>
<div class="adm-card">
  <table class="adm-table">
    <thead><tr><th>Client</th><th>Role</th><th>Visible</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['author_name']) ?></td>
          <td><?= e($row['author_role'] ?? '') ?></td>
          <td><?= !empty($row['is_active']) ? 'Yes' : 'No' ?></td>
          <td>
            <div class="adm-row-actions">
              <a href="testimonials.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
              <form method="post" data-confirm="Delete this testimonial?">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete" /><input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

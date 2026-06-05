<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();

const JOURNAL_BODY_PARAGRAPHS = 8;

$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);
    if ($postAction === 'delete' && $postId > 0) {
        $pdo->prepare('DELETE FROM journal_posts WHERE id = ?')->execute([$postId]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Blog post removed.');
        redirect('journal.php');
    }
    if ($postAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: admin_slugify($title);
        $slug = admin_slugify($slug);
        $excerpt = trim($_POST['excerpt'] ?? '');
        $body = cms_build_paragraphs_html(cms_post_body_paragraphs($_POST, 'body_paragraph_', JOURNAL_BODY_PARAGRAPHS));
        $seoTitle = trim($_POST['seo_title'] ?? '');
        $seoDesc = trim($_POST['seo_description'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = null;
        if (!empty($_FILES['image_path']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['image_path']);
            if ($up['ok']) {
                $imagePath = $up['path'];
                require_once __DIR__ . '/includes/media.php';
                media_register($pdo, $up['path'], basename($up['path']));
            }
        }
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            if ($imagePath) {
                $pdo->prepare(
                    'UPDATE journal_posts SET slug=?, title=?, excerpt=?, body_html=?, seo_title=?, seo_description=?, sort_order=?, is_active=?, image_path=? WHERE id=?'
                )->execute([$slug, $title, $excerpt, $body, $seoTitle, $seoDesc, $sort, $active, $imagePath, $editId]);
            } else {
                $pdo->prepare(
                    'UPDATE journal_posts SET slug=?, title=?, excerpt=?, body_html=?, seo_title=?, seo_description=?, sort_order=?, is_active=? WHERE id=?'
                )->execute([$slug, $title, $excerpt, $body, $seoTitle, $seoDesc, $sort, $active, $editId]);
            }
        } else {
            $pdo->prepare(
                'INSERT INTO journal_posts (slug, title, excerpt, body_html, seo_title, seo_description, sort_order, is_active, image_path)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$slug, $title, $excerpt, $body, $seoTitle, $seoDesc, $sort, $active, $imagePath ?? '']);
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Blog post saved.');
        redirect('journal.php');
    }
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'body_html' => '', 'seo_title' => '', 'seo_description' => '', 'sort_order' => 0, 'is_active' => 1, 'image_path' => ''];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM journal_posts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit blog post' : 'Add blog post';
    $activeNav = 'journal';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field"><label>Title</label><input type="text" name="title" value="<?= e($row['title']) ?>" required /></div>
      <div class="adm-field"><label>URL slug</label><input type="text" name="slug" value="<?= e($row['slug']) ?>" /></div>
      <div class="adm-field"><label>Short summary</label><textarea name="excerpt" rows="2"><?= e($row['excerpt'] ?? '') ?></textarea></div>
      <?php $bodyParagraphs = cms_plain_paragraph_slots((string) ($row['body_html'] ?? ''), JOURNAL_BODY_PARAGRAPHS); ?>
      <div class="adm-field">
        <label>Article text</label>
        <p class="adm-hint">One box per paragraph. Leave empty boxes blank at the end.</p>
      </div>
      <?php foreach ($bodyParagraphs as $i => $para): $n = $i + 1; ?>
        <div class="adm-field">
          <label for="body_paragraph_<?= $n ?>">Paragraph <?= $n ?><?= $n > 1 ? ' (optional)' : '' ?></label>
          <textarea name="body_paragraph_<?= $n ?>" id="body_paragraph_<?= $n ?>" rows="5"><?= e($para) ?></textarea>
        </div>
      <?php endforeach; ?>
      <div class="adm-field"><label>Featured image</label>
        <?php if (!empty($row['image_path'])): ?><img src="../<?= e($row['image_path']) ?>" alt="" style="max-width:200px;" /><?php endif; ?>
        <input type="file" name="image_path" accept="image/*" /></div>
      <div class="adm-field"><label>SEO title</label><input type="text" name="seo_title" value="<?= e($row['seo_title'] ?? '') ?>" /></div>
      <div class="adm-field"><label>SEO description</label><textarea name="seo_description"><?= e($row['seo_description'] ?? '') ?></textarea></div>
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Published</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save post</button>
        <a href="journal.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$rows = $pdo->query('SELECT id, title, slug, is_active, created_at FROM journal_posts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Blog';
$activeNav = 'journal';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;"><a href="journal.php?action=new" class="adm-btn adm-btn-primary">Add blog post</a></div>
<div class="adm-card">
  <table class="adm-table">
    <thead><tr><th>Title</th><th>Slug</th><th>Published</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['title']) ?></td>
          <td><?= e($row['slug']) ?></td>
          <td><?= !empty($row['is_active']) ? 'Yes' : 'Draft' ?></td>
          <td>
            <div class="adm-row-actions">
              <a href="journal.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
              <form method="post" data-confirm="Delete this blog post?">
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

<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    if (($_POST['action'] ?? '') === 'delete') {
        $mid = (int) ($_POST['id'] ?? 0);
        if ($mid > 0) {
            media_delete_file($pdo, $mid);
            admin_flash_set('success', 'Image deleted.');
        }
        redirect('media.php');
    }
    if (!empty($_FILES['uploads']['name'][0])) {
        foreach ($_FILES['uploads']['name'] as $i => $name) {
            if (!$name) {
                continue;
            }
            $file = [
                'name' => $_FILES['uploads']['name'][$i],
                'type' => $_FILES['uploads']['type'][$i],
                'tmp_name' => $_FILES['uploads']['tmp_name'][$i],
                'error' => $_FILES['uploads']['error'][$i],
                'size' => $_FILES['uploads']['size'][$i],
            ];
            $up = Upload::image($appConfig, 'general', $file);
            if ($up['ok']) {
                media_register($pdo, $up['path'], $name, $file['type'] ?? null, (int) ($file['size'] ?? 0));
            }
        }
        admin_flash_set('success', 'Upload complete.');
        redirect('media.php');
    }
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT id, file_path, file_name, created_at FROM media_assets';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE file_name LIKE ? OR file_path LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like];
}
$sql .= ' ORDER BY created_at DESC LIMIT 120';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Media library';
$pageDescription = 'Upload and reuse images across your site.';
$activeNav = 'media';
require __DIR__ . '/includes/layout.php';
?>
<form method="post" enctype="multipart/form-data" class="adm-card">
  <?= csrf_field() ?>
  <div class="adm-field">
    <label>Upload images <?= admin_tooltip('JPG, PNG, or WEBP up to 5 MB each') ?></label>
    <input type="file" name="uploads[]" accept="image/*" multiple />
  </div>
  <button type="submit" class="adm-btn adm-btn-primary">Upload</button>
</form>
<form method="get" class="adm-card adm-search-bar">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search file name…" />
  <button type="submit" class="adm-btn adm-btn-ghost">Search</button>
</form>
<div class="adm-media-grid adm-media-grid-large">
  <?php foreach ($items as $item): ?>
    <div class="adm-media-item">
      <img src="../<?= e($item['file_path']) ?>" alt="<?= e($item['file_name']) ?>" loading="lazy" />
      <p class="adm-media-path" title="<?= e($item['file_path']) ?>"><?= e($item['file_path']) ?></p>
      <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost adm-copy-path" data-path="<?= e($item['file_path']) ?>">Copy path</button>
      <form method="post" data-confirm="Delete this image from the library?">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
        <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<script>
document.querySelectorAll('.adm-copy-path').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var path = btn.getAttribute('data-path') || '';
    if (navigator.clipboard) navigator.clipboard.writeText(path);
    btn.textContent = 'Copied!';
    setTimeout(function () { btn.textContent = 'Copy path'; }, 1500);
  });
});
</script>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

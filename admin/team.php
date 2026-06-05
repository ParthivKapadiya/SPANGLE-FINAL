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
        $pdo->prepare('DELETE FROM team_members WHERE id = ?')->execute([$postId]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Team member removed.');
        redirect('team.php');
    }
    if ($postAction === 'save') {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role_title'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $initials = trim($_POST['initials'] ?? '');
        $linkedin = trim($_POST['linkedin_url'] ?? '');
        $instagram = trim($_POST['instagram_url'] ?? '');
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
                    'UPDATE team_members SET name=?, role_title=?, bio=?, initials=?, linkedin_url=?, instagram_url=?, sort_order=?, is_active=?, image_path=? WHERE id=?'
                )->execute([$name, $role, $bio, $initials, $linkedin, $instagram, $sort, $active, $imagePath, $editId]);
            } else {
                $pdo->prepare(
                    'UPDATE team_members SET name=?, role_title=?, bio=?, initials=?, linkedin_url=?, instagram_url=?, sort_order=?, is_active=? WHERE id=?'
                )->execute([$name, $role, $bio, $initials, $linkedin, $instagram, $sort, $active, $editId]);
            }
        } else {
            $pdo->prepare(
                'INSERT INTO team_members (name, role_title, bio, initials, linkedin_url, instagram_url, sort_order, is_active, image_path)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$name, $role, $bio, $initials, $linkedin, $instagram, $sort, $active, $imagePath ?? '']);
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Team member saved.');
        redirect('team.php');
    }
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'name' => '', 'role_title' => '', 'bio' => '', 'initials' => '', 'linkedin_url' => '', 'instagram_url' => '', 'sort_order' => 0, 'is_active' => 1, 'image_path' => ''];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM team_members WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit team member' : 'Add team member';
    $activeNav = 'team';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field"><label>Name</label><input type="text" name="name" value="<?= e($row['name']) ?>" required /></div>
      <div class="adm-field"><label>Position</label><input type="text" name="role_title" value="<?= e($row['role_title']) ?>" required /></div>
      <div class="adm-field"><label>Bio</label><textarea name="bio" rows="4"><?= e($row['bio'] ?? '') ?></textarea></div>
      <div class="adm-field"><label>Initials (if no photo)</label><input type="text" name="initials" value="<?= e($row['initials'] ?? '') ?>" maxlength="4" /></div>
      <div class="adm-field"><label>Photo</label>
        <?php if (!empty($row['image_path'])): ?><img src="../<?= e($row['image_path']) ?>" alt="" style="max-width:120px;border-radius:50%;" /><?php endif; ?>
        <input type="file" name="image_path" accept="image/*" /></div>
      <div class="adm-field"><label>LinkedIn URL</label><input type="url" name="linkedin_url" value="<?= e($row['linkedin_url'] ?? '') ?>" /></div>
      <div class="adm-field"><label>Instagram URL</label><input type="url" name="instagram_url" value="<?= e($row['instagram_url'] ?? '') ?>" /></div>
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Show on website</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save</button>
        <a href="team.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$rows = $pdo->query('SELECT id, name, role_title, is_active FROM team_members ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Team';
$activeNav = 'team';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;"><a href="team.php?action=new" class="adm-btn adm-btn-primary">Add team member</a></div>
<div class="adm-card">
  <table class="adm-table">
    <thead><tr><th>Name</th><th>Role</th><th>Visible</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['name']) ?></td>
          <td><?= e($row['role_title']) ?></td>
          <td><?= !empty($row['is_active']) ? 'Yes' : 'No' ?></td>
            <td>
              <div class="adm-row-actions">
                <a href="team.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
                <form method="post" data-confirm="Remove this team member?">
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

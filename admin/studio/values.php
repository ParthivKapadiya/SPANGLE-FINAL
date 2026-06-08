<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/studioAdmin.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();

$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete_highlight' && $id > 0) {
        $pdo->prepare('DELETE FROM awards WHERE id = ?')->execute([$id]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Highlight removed.');
        redirect('values.php');
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
        redirect('values.php');
    }

    if ($postAction === 'save_headings') {
        $keys = ['studio_values_eyebrow', 'studio_values_title'];
        studio_admin_save_settings($pdo, $keys);
        $eyebrow = trim((string) ($_POST['studio_values_eyebrow'] ?? ''));
        $title = trim((string) ($_POST['studio_values_title'] ?? ''));
        cms_sync_studio_highlight_headings($pdo, $eyebrow, $title);
        admin_log_activity($pdo, 'save', 'studio-values', null, 'Studio values updated');
        studio_admin_sync_and_redirect('values.php', 'Values section saved.');
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
    $pageDescription = 'Shown on studio.html and the home page highlights section.';
    $activeNav = 'studio';
    require dirname(__DIR__) . '/includes/layout.php';
    studio_admin_render_back();
    ?>
    <form method="post" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_highlight" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field">
        <label>Icon (Font Awesome class)</label>
        <input type="text" name="icon_class" value="<?= e($row['icon_class']) ?>" placeholder="fas fa-handshake" />
        <p class="adm-hint">Used on the <strong>home page</strong> highlights row.</p>
      </div>
      <div class="adm-field"><label>Title</label><input type="text" name="title" value="<?= e($row['title']) ?>" required /></div>
      <div class="adm-field"><label>Description</label><textarea name="subtitle" rows="3" required><?= e($row['subtitle'] ?? '') ?></textarea></div>
      <div class="adm-field"><label>Order</label><input type="number" name="sort_order" value="<?= (int) $row['sort_order'] ?>" style="width:6rem;" /></div>
      <div class="adm-field">
        <label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible on website</label>
      </div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save</button>
        <a href="values.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require dirname(__DIR__) . '/includes/layout-end.php';
    exit;
}

$section = studio_admin_require_section('values');
extract(studio_admin_page_vars($section));
$s = studio_admin_load_settings($pdo, ['studio_values_eyebrow', 'studio_values_title']);
$highlights = $pdo->query(
    'SELECT id, icon_class, title, subtitle, sort_order, is_active FROM awards ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__) . '/includes/layout.php';
studio_admin_render_back();
?>
<form method="post" class="adm-settings-grid" style="margin-bottom:1.5rem;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_headings" />
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('studio_values_eyebrow', 'Small label', $s);
    home_admin_render_field('studio_values_title', 'Title', $s);
    ?>
    <?php home_admin_card_link('home/highlights.php', 'Home highlights heading', 'Syncs the matching heading on the home page.'); ?>
  </div>
  <?php home_admin_render_save('Save headings'); ?>
</form>

<div class="adm-card">
  <div class="adm-actions" style="margin-bottom:1rem;">
    <a href="values.php?action=new_highlight" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Add highlight</a>
  </div>
  <div class="adm-service-blocks-grid">
    <?php foreach ($highlights as $row): ?>
      <article class="adm-service-block-card adm-card">
        <div class="adm-service-block-thumb is-empty" style="min-height:100px;">
          <i class="<?= e($row['icon_class']) ?>" style="font-size:2rem;color:var(--adm-muted);" aria-hidden="true"></i>
        </div>
        <h3 class="adm-service-block-title"><?= e($row['title']) ?></h3>
        <p class="adm-hint" style="margin:0 0 0.5rem;line-height:1.4;"><?= e($row['subtitle'] ?? '') ?></p>
        <p class="adm-hint"><?= !empty($row['is_active']) ? 'Live on studio + home' : 'Hidden' ?></p>
        <div class="adm-row-actions">
          <a href="values.php?action=edit_highlight&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
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
</div>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

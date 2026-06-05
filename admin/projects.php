<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cms/ProjectRepository.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once SPANGLE_ROOT . '/includes/syncUploadLibrary.php';
require_once __DIR__ . '/includes/media.php';

const PROJECT_BODY_PARAGRAPHS = 6;

admin_require_auth();

$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? $id);
    if ($postAction === 'import_uploads') {
        $result = SyncUploadLibrary::syncOneProjectPerImage($pdo, true);
        admin_flash_set(
            'success',
            'Imported ' . $result['projects'] . ' images from uploads/ — each one is now a project on the Work page.'
        );
        redirect('projects.php');
    }
    if ($postAction === 'optimize_images') {
        require_once SPANGLE_ROOT . '/includes/ImageOptimizer.php';
        $stats = ImageOptimizer::optimizeDirectory(SPANGLE_ROOT . '/uploads', true);
        content_sync_site_json($pdo);
        $msg = ImageOptimizer::variantsEnabled()
            ? 'Optimized ' . $stats['processed'] . ' images (skipped ' . $stats['skipped'] . ', errors ' . $stats['errors'] . ').'
            : 'Resized ' . $stats['processed'] . ' originals (no small copies generated).';
        admin_flash_set('success', $msg);
        redirect('projects.php');
    }
    if ($postAction === 'delete' && $postId > 0) {
        $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$postId]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Project deleted.');
        redirect('projects.php');
    }
    if ($postAction === 'delete_gallery_image') {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $projectId = (int) ($_POST['project_id'] ?? 0);
        if ($imageId > 0) {
            $pdo->prepare('DELETE FROM project_images WHERE id = ? AND project_id = ?')->execute([$imageId, $projectId]);
            admin_flash_set('success', 'Gallery image removed.');
        }
        redirect('projects.php?action=edit&id=' . $projectId);
    }
    if ($postAction === 'duplicate' && $postId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$postId]);
        $src = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($src) {
            $slug = admin_slugify($src['title'] . '-copy-' . time());
            $ins = $pdo->prepare(
                'INSERT INTO projects (slug, title, location, category, project_type, summary, body_html,
                 hero_image, area_label, completion_year, services_provided, client_testimonial,
                 seo_title, seo_description, is_featured, is_active, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,999)'
            );
            $ins->execute([
                $slug, $src['title'] . ' (Copy)', $src['location'], $src['category'], $src['project_type'] ?? $src['category'],
                $src['summary'], $src['body_html'], $src['hero_image'], $src['area_label'], $src['completion_year'],
                $src['services_provided'], $src['client_testimonial'], $src['seo_title'], $src['seo_description'],
                $src['is_featured'],
            ]);
            admin_flash_set('success', 'Project duplicated.');
        }
        redirect('projects.php');
    }
    if ($postAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: admin_slugify($title);
        $slug = admin_slugify($slug);
        $projectType = ProjectRepository::normalizeType((string) ($_POST['project_type'] ?? 'residential'));
        $year = (int) ($_POST['completion_year'] ?? 0);
        $params = [
            $slug,
            $title,
            trim($_POST['location'] ?? ''),
            $projectType,
            $projectType,
            trim($_POST['summary'] ?? ''),
            cms_build_paragraphs_html(cms_post_body_paragraphs($_POST, 'body_paragraph_', PROJECT_BODY_PARAGRAPHS)),
            trim($_POST['area_label'] ?? ''),
            $year > 0 ? $year : null,
            trim($_POST['services_provided'] ?? ''),
            trim($_POST['client_testimonial'] ?? ''),
            trim($_POST['seo_title'] ?? ''),
            trim($_POST['seo_description'] ?? ''),
            isset($_POST['is_featured']) ? 1 : 0,
            isset($_POST['home_highlight']) ? 1 : 0,
            isset($_POST['is_active']) ? 1 : 0,
            (int) ($_POST['sort_order'] ?? 0),
        ];
        $editId = (int) ($_POST['id'] ?? 0);
        $heroPath = null;
        if (!empty($_FILES['hero_image']['name'])) {
            $up = Upload::image($appConfig, 'general', $_FILES['hero_image']);
            if ($up['ok']) {
                $heroPath = $up['path'];
                media_register($pdo, $up['path'], basename($up['path']));
            }
        }
        if ($editId > 0) {
            if ($heroPath !== null) {
                $params[] = $heroPath;
                $pdo->prepare(
                    'UPDATE projects SET slug=?, title=?, location=?, category=?, project_type=?, summary=?, body_html=?,
                     area_label=?, completion_year=?, services_provided=?, client_testimonial=?, seo_title=?, seo_description=?,
                     is_featured=?, home_highlight=?, is_active=?, sort_order=?, hero_image=? WHERE id=?'
                )->execute(array_merge($params, [$editId]));
            } else {
                $pdo->prepare(
                    'UPDATE projects SET slug=?, title=?, location=?, category=?, project_type=?, summary=?, body_html=?,
                     area_label=?, completion_year=?, services_provided=?, client_testimonial=?, seo_title=?, seo_description=?,
                     is_featured=?, home_highlight=?, is_active=?, sort_order=? WHERE id=?'
                )->execute(array_merge($params, [$editId]));
            }
            $projectId = $editId;
        } else {
            $hero = $heroPath ?? '';
            $insert = array_merge(
                array_slice($params, 0, 7),
                [$hero],
                array_slice($params, 7)
            );
            $pdo->prepare(
                'INSERT INTO projects (slug, title, location, category, project_type, summary, body_html, hero_image,
                 area_label, completion_year, services_provided, client_testimonial, seo_title, seo_description,
                 is_featured, home_highlight, is_active, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute($insert);
            $projectId = (int) $pdo->lastInsertId();
        }
        if ($projectId && !empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $i => $name) {
                if (!$name) continue;
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$i],
                    'type' => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error' => $_FILES['gallery_images']['error'][$i],
                    'size' => $_FILES['gallery_images']['size'][$i],
                ];
                $up = Upload::image($appConfig, 'general', $file);
                if ($up['ok']) {
                    media_register($pdo, $up['path'], basename($up['path']));
                    $pdo->prepare(
                        'INSERT INTO project_images (project_id, image_path, sort_order) VALUES (?,?,?)'
                    )->execute([$projectId, $up['path'], $i]);
                }
            }
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Project saved.');
        redirect('projects.php?action=edit&id=' . $projectId);
    }
}

if ($action === 'edit' || $action === 'new') {
    $row = [
        'id' => 0, 'title' => '', 'slug' => '', 'location' => '', 'project_type' => 'residential',
        'summary' => '', 'body_html' => '', 'hero_image' => '', 'area_label' => '', 'completion_year' => '',
        'services_provided' => '', 'client_testimonial' => '', 'seo_title' => '', 'seo_description' => '',
        'is_featured' => 0, 'home_highlight' => 0, 'is_active' => 1, 'sort_order' => 0,
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $gallery = $id > 0 ? ProjectRepository::galleryForProject($pdo, $id) : [];
    $pageTitle = $id > 0 ? 'Edit project' : 'Add project';
    $activeNav = 'projects';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <div class="adm-field"><label>Title <?= admin_tooltip('Project name shown on website') ?></label>
        <input type="text" name="title" value="<?= e($row['title']) ?>" required /></div>
      <div class="adm-field"><label>URL slug</label><input type="text" name="slug" value="<?= e($row['slug']) ?>" /></div>
      <div class="adm-field"><label>Category <?= admin_tooltip('Used on Work page filters — Villa and Office also match project titles') ?></label>
        <select name="project_type">
          <?php foreach (ProjectRepository::TYPES as $t): ?>
            <option value="<?= e($t) ?>"<?= ($row['project_type'] ?? '') === $t ? ' selected' : '' ?>><?= e(ucwords(str_replace('-', ' ', $t))) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="adm-hint">Work page chips include Villa and Office — titles containing “villa” or “office” are matched automatically.</p>
      </div>
      <div class="adm-field"><label>Location</label><input type="text" name="location" value="<?= e($row['location'] ?? '') ?>" /></div>
      <div class="adm-field"><label>Area (e.g. 2,400 sq ft)</label><input type="text" name="area_label" value="<?= e($row['area_label'] ?? '') ?>" /></div>
      <div class="adm-field"><label>Completion year</label><input type="number" name="completion_year" value="<?= e((string) ($row['completion_year'] ?? '')) ?>" /></div>
      <div class="adm-field"><label>Short summary</label><textarea name="summary" rows="2"><?= e($row['summary'] ?? '') ?></textarea></div>
      <?php $bodyParagraphs = cms_plain_paragraph_slots((string) ($row['body_html'] ?? ''), PROJECT_BODY_PARAGRAPHS); ?>
      <div class="adm-field">
        <label>Full description</label>
        <p class="adm-hint">Use separate boxes for each paragraph — no code or tags needed.</p>
      </div>
      <?php foreach ($bodyParagraphs as $i => $para): $n = $i + 1; ?>
        <div class="adm-field">
          <label for="body_paragraph_<?= $n ?>">Paragraph <?= $n ?><?= $n === 1 ? '' : ' (optional)' ?></label>
          <textarea name="body_paragraph_<?= $n ?>" id="body_paragraph_<?= $n ?>" rows="4"><?= e($para) ?></textarea>
        </div>
      <?php endforeach; ?>
      <div class="adm-field"><label>Services provided</label><textarea name="services_provided"><?= e($row['services_provided'] ?? '') ?></textarea></div>
      <div class="adm-field"><label>Client testimonial</label><textarea name="client_testimonial"><?= e($row['client_testimonial'] ?? '') ?></textarea></div>
      <div class="adm-field"><label>Cover image</label>
        <?php if (!empty($row['hero_image'])): ?><p><img src="../<?= e($row['hero_image']) ?>" alt="" style="max-width:200px;border-radius:8px;" /></p><?php endif; ?>
        <input type="file" name="hero_image" accept="image/*" /></div>
      <div class="adm-field"><label>Gallery images (add more)</label><input type="file" name="gallery_images[]" accept="image/*" multiple /></div>
      <?php if ($gallery): ?>
        <div class="adm-media-grid adm-media-grid-large">
          <?php foreach ($gallery as $g): ?>
            <div class="adm-media-item">
              <img src="<?= e($g['src']) ?>" alt="" />
              <form method="post" data-confirm="Remove this image from the project gallery?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_gallery_image" />
                <input type="hidden" name="image_id" value="<?= (int) ($g['id'] ?? 0) ?>" />
                <input type="hidden" name="project_id" value="<?= (int) $row['id'] ?>" />
                <button type="submit" class="adm-btn adm-btn-danger" style="width:100%;margin-top:0.35rem;">Remove</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="adm-field"><label>SEO title</label><input type="text" name="seo_title" value="<?= e($row['seo_title'] ?? '') ?>" /></div>
      <div class="adm-field"><label>SEO description</label><textarea name="seo_description"><?= e($row['seo_description'] ?? '') ?></textarea></div>
      <div class="adm-field">
        <label><input type="checkbox" name="home_highlight" value="1"<?= !empty($row['home_highlight']) ? ' checked' : '' ?> /> Show on home page (Featured commissions)</label>
        <p class="adm-hint">Appears in the project tiles section on the home page. You can also pick projects under <a href="home.php">Home page</a>.</p>
      </div>
      <div class="adm-field"><label><input type="checkbox" name="is_featured" value="1"<?= !empty($row['is_featured']) ? ' checked' : '' ?> /> Featured project (work page sort)</label></div>
      <div class="adm-field"><label>Display order</label><input type="number" name="sort_order" value="<?= (int) ($row['sort_order'] ?? 0) ?>" min="0" step="1" />
        <p class="adm-hint">Lower numbers appear first on the home page and in lists.</p></div>
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible on website</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save project</button>
        <a href="projects.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$rows = $pdo->query(
    'SELECT id, title, location, project_type, is_featured, home_highlight, is_active, created_at
     FROM projects ORDER BY created_at DESC'
)->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Projects';
$pageDescription = 'Add, edit, or remove portfolio projects.';
$activeNav = 'projects';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:0.5rem;">
  <a href="projects.php?action=new" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Add project</a>
  <form method="post" data-confirm="Replace all portfolio projects with one entry per image in uploads/? Retail samples are kept.">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="import_uploads" />
    <button type="submit" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-images"></i> Import all uploads to Work page</button>
  </form>
  <form method="post" data-confirm="Resize all images in uploads/ and create mobile-friendly versions? This may take a few minutes.">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="optimize_images" />
    <button type="submit" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-bolt"></i> Optimize all images</button>
  </form>
</div>
<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Title</th><th>Location</th><th>Type</th><th>Home</th><th>Featured</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['location'] ?? '') ?></td>
            <td><?= e($row['project_type'] ?? '') ?></td>
            <td><?= !empty($row['home_highlight']) ? 'Yes' : '—' ?></td>
            <td><?= !empty($row['is_featured']) ? 'Yes' : '—' ?></td>
            <td>
              <div class="adm-row-actions">
                <a href="projects.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
                <form method="post" data-confirm="Create a copy of this project?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="duplicate" />
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                  <button type="submit" class="adm-btn adm-btn-sm adm-btn-ghost">Duplicate</button>
                </form>
                <form method="post" data-confirm="Delete this project permanently?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                  <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

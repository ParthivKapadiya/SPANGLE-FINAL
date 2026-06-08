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
    if ($postAction === 'bulk_change_category') {
        $ids = $_POST['project_ids'] ?? [];
        $newType = ProjectRepository::normalizeType((string) ($_POST['project_type'] ?? ''));
        if (!is_array($ids) || $ids === []) {
            admin_flash_set('error', 'Select at least one project.');
            redirect('projects.php');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            admin_flash_set('error', 'No valid projects selected.');
            redirect('projects.php');
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare(
            "UPDATE projects SET project_type = ?, category = ? WHERE id IN ($placeholders)"
        )->execute(array_merge([$newType, $newType], $ids));
        content_sync_site_json($pdo);
        admin_flash_set(
            'success',
            'Updated category to ' . ProjectRepository::typeLabel($newType) . ' for ' . count($ids) . ' project(s).'
        );
        redirect('projects.php' . (isset($_POST['filter_type']) && $_POST['filter_type'] !== '' ? '?filter_type=' . rawurlencode((string) $_POST['filter_type']) : ''));
    }
    if ($postAction === 'duplicate' && $postId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$postId]);
        $src = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($src) {
            $slug = admin_unique_slug($pdo, $src['title'] . '-copy', 'projects');
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
        $editId = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: admin_slugify($title);
        $slug = admin_unique_slug($pdo, $slug, 'projects', $editId);
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
    $isNew = $id <= 0;
    if ($isNew) {
        $row['home_highlight'] = 1;
    }
    $pageTitle = $id > 0 ? 'Edit project' : 'Add project';
    $activeNav = 'projects';
    require __DIR__ . '/includes/layout.php';
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />

      <?php if ($isNew): ?>
        <p class="adm-form-intro"><strong>Quick upload:</strong> add a project name, choose a category, upload your cover photo, and save. Everything else is optional — expand the sections below only if you need them.</p>
      <?php else: ?>
        <p class="adm-form-intro">Update the essentials first. Optional case study, SEO, and display settings are grouped below.</p>
      <?php endif; ?>

      <div class="adm-form-section adm-glass">
        <h2>Essentials</h2>
        <p class="adm-hint">Only the project name is required. Add at least one photo so it looks good on the Work page.</p>
        <div class="adm-field">
          <label>Project name <?= admin_tooltip('Shown on the Work page and project detail page') ?></label>
          <input type="text" name="title" value="<?= e($row['title']) ?>" required placeholder="e.g. Patel Residence · Rajkot" />
        </div>
        <div class="adm-field-row-2">
          <div class="adm-field">
            <label>Category</label>
            <select name="project_type">
              <?php foreach (ProjectRepository::TYPES as $t): ?>
                <option value="<?= e($t) ?>"<?= ProjectRepository::normalizeType((string) ($row['project_type'] ?? '')) === $t ? ' selected' : '' ?>><?= e(ProjectRepository::typeLabel($t)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="adm-field">
            <label>Location</label>
            <input type="text" name="location" value="<?= e($row['location'] ?? '') ?>" placeholder="e.g. Rajkot, Gujarat" />
          </div>
        </div>
        <div class="adm-field">
          <label>Cover image <?= admin_tooltip('Main photo on Work page and project page') ?></label>
          <?php if (!empty($row['hero_image'])): ?><p><img src="../<?= e($row['hero_image']) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p><?php endif; ?>
          <input type="file" name="hero_image" accept="image/*" />
        </div>
        <div class="adm-field">
          <label>Gallery images (optional)</label>
          <input type="file" name="gallery_images[]" accept="image/*" multiple />
          <p class="adm-hint">Add extra photos for the project detail page. You can add more later when editing.</p>
        </div>
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
        <div class="adm-field">
          <label><input type="checkbox" name="home_highlight" value="1"<?= !empty($row['home_highlight']) ? ' checked' : '' ?> /> Show on home page</label>
        </div>
        <div class="adm-field">
          <label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible on website</label>
        </div>
      </div>

      <details class="adm-form-optional">
        <summary>More details (optional)</summary>
        <div class="adm-form-optional__body">
          <div class="adm-field"><label>URL slug</label><input type="text" name="slug" value="<?= e($row['slug']) ?>" placeholder="Auto-filled from project name if empty" /></div>
          <div class="adm-field-row-2">
            <div class="adm-field"><label>Area</label><input type="text" name="area_label" value="<?= e($row['area_label'] ?? '') ?>" placeholder="e.g. 2,400 sq ft" /></div>
            <div class="adm-field"><label>Completion year</label><input type="number" name="completion_year" value="<?= e((string) ($row['completion_year'] ?? '')) ?>" placeholder="e.g. 2024" /></div>
          </div>
          <div class="adm-field"><label>Short summary</label><textarea name="summary" rows="2" placeholder="One line for portfolio cards"><?= e($row['summary'] ?? '') ?></textarea></div>
        </div>
      </details>

      <?php $bodyParagraphs = cms_plain_paragraph_slots((string) ($row['body_html'] ?? ''), PROJECT_BODY_PARAGRAPHS); ?>
      <details class="adm-form-optional">
        <summary>Case study text (optional)</summary>
        <div class="adm-form-optional__body">
          <p class="adm-hint">For a full project write-up. Leave blank for a photo-only project.</p>
          <?php
          $bodyLabels = [
              1 => 'Challenge',
              2 => 'Approach / design process',
              3 => 'Result / outcome',
              4 => 'Materials',
              5 => 'Execution',
              6 => 'Additional detail',
          ];
          foreach ($bodyParagraphs as $i => $para):
              $n = $i + 1;
              ?>
            <div class="adm-field">
              <label for="body_paragraph_<?= $n ?>"><?= e($bodyLabels[$n] ?? ('Paragraph ' . $n)) ?></label>
              <textarea name="body_paragraph_<?= $n ?>" id="body_paragraph_<?= $n ?>" rows="3"><?= e($para) ?></textarea>
            </div>
          <?php endforeach; ?>
          <div class="adm-field"><label>Services provided</label><textarea name="services_provided" rows="2"><?= e($row['services_provided'] ?? '') ?></textarea></div>
          <div class="adm-field"><label>Client testimonial</label><textarea name="client_testimonial" rows="2"><?= e($row['client_testimonial'] ?? '') ?></textarea></div>
        </div>
      </details>

      <details class="adm-form-optional">
        <summary>SEO (optional)</summary>
        <div class="adm-form-optional__body">
          <div class="adm-field"><label>SEO title</label><input type="text" name="seo_title" value="<?= e($row['seo_title'] ?? '') ?>" /></div>
          <div class="adm-field"><label>SEO description</label><textarea name="seo_description" rows="2"><?= e($row['seo_description'] ?? '') ?></textarea></div>
        </div>
      </details>

      <details class="adm-form-optional">
        <summary>Display options</summary>
        <div class="adm-form-optional__body">
          <div class="adm-field"><label><input type="checkbox" name="is_featured" value="1"<?= !empty($row['is_featured']) ? ' checked' : '' ?> /> Featured on Work page</label></div>
          <div class="adm-field"><label>Display order</label><input type="number" name="sort_order" value="<?= (int) ($row['sort_order'] ?? 0) ?>" min="0" step="1" />
            <p class="adm-hint">Lower numbers appear first in lists.</p></div>
        </div>
      </details>

      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary"><?= $isNew ? 'Save project' : 'Save changes' ?></button>
        <a href="projects.php" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$filterType = trim((string) ($_GET['filter_type'] ?? ''));
if ($filterType === 'all') {
    $filterType = '';
}
$listSql = 'SELECT id, title, location, project_type, hero_image, is_featured, home_highlight, is_active, created_at FROM projects';
$listParams = [];
if ($filterType !== '') {
    $normalizedFilter = ProjectRepository::normalizeType($filterType);
    $listSql .= ' WHERE project_type = ? OR category = ?';
    $listParams = [$normalizedFilter, $normalizedFilter];
}
$listSql .= ' ORDER BY created_at DESC';
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($listParams);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Projects';
$pageDescription = 'Add, edit, or remove portfolio projects. Select multiple images to change category at once.';
$activeNav = 'projects';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-actions" style="margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:0.5rem;">
  <a href="work-page.php" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-layer-group"></i> Work page copy</a>
  <a href="projects.php?action=new" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Add project</a>
  <form method="post" data-confirm="Replace all portfolio projects with one entry per image in uploads/? Retail samples are kept.">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="import_uploads" />
    <button type="submit" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-images"></i> Import all uploads to Work page</button>
  </form>
</div>

<form method="get" class="adm-card adm-search-bar" style="margin-bottom:1rem;">
  <label for="filter_type" class="adm-hint" style="margin:0;">Show category</label>
  <select name="filter_type" id="filter_type" onchange="this.form.submit()">
    <option value="all"<?= $filterType === '' ? ' selected' : '' ?>>All categories</option>
    <?php foreach (ProjectRepository::TYPES as $t): ?>
      <option value="<?= e($t) ?>"<?= $filterType !== '' && ProjectRepository::normalizeType($filterType) === $t ? ' selected' : '' ?>><?= e(ProjectRepository::typeLabel($t)) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="adm-hint"><?= count($rows) ?> project<?= count($rows) === 1 ? '' : 's' ?><?= $filterType !== '' ? ' in this category' : '' ?></span>
</form>

<form method="post" class="adm-card" id="adm-projects-bulk-form" data-bulk-picker data-confirm="Change category for the selected projects?">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="bulk_change_category" />
  <?php if ($filterType !== ''): ?>
    <input type="hidden" name="filter_type" value="<?= e($filterType) ?>" />
  <?php endif; ?>

  <div class="adm-toolbar adm-bulk-toolbar">
    <label class="adm-bulk-select-all-label">
      <input type="checkbox" data-bulk-select-all />
      Select all
    </label>
    <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost" data-bulk-clear>Clear</button>
    <span class="adm-bulk-count" data-bulk-count>0 selected</span>
    <span class="adm-bulk-toolbar-divider" aria-hidden="true"></span>
    <label class="adm-hint" for="bulk_project_type" style="margin:0;">Change to</label>
    <select name="project_type" id="bulk_project_type">
      <?php foreach (ProjectRepository::TYPES as $t): ?>
        <option value="<?= e($t) ?>"><?= e(ProjectRepository::typeLabel($t)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="adm-btn adm-btn-primary" data-bulk-apply disabled>
      <i class="fa-solid fa-tags"></i> Update category
    </button>
  </div>

  <?php if ($rows === []): ?>
    <p class="adm-hint" style="padding:1rem 0 0;">No projects found<?= $filterType !== '' ? ' in this category' : '' ?>.</p>
  <?php else: ?>
    <div class="adm-project-picker-grid" data-bulk-grid>
      <?php foreach ($rows as $row):
          $type = ProjectRepository::normalizeType((string) ($row['project_type'] ?? ''));
          ?>
        <label class="adm-project-picker-item" data-bulk-item>
          <input type="checkbox" name="project_ids[]" value="<?= (int) $row['id'] ?>" class="adm-bulk-check" />
          <span class="adm-project-picker-thumb">
            <?php if (!empty($row['hero_image'])): ?>
              <img src="../<?= e($row['hero_image']) ?>" alt="" loading="lazy" />
            <?php else: ?>
              <span class="adm-project-picker-placeholder" aria-hidden="true">No image</span>
            <?php endif; ?>
            <span class="adm-badge adm-project-picker-badge"><?= e(ProjectRepository::typeLabel($type)) ?></span>
          </span>
          <span class="adm-project-picker-meta">
            <strong><?= e($row['title']) ?></strong>
            <?php if (!empty($row['location'])): ?>
              <span><?= e($row['location']) ?></span>
            <?php endif; ?>
          </span>
          <span class="adm-project-picker-actions">
            <a href="projects.php?action=edit&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost" onclick="event.preventDefault(); event.stopPropagation(); window.location=this.href;">Edit</a>
            <button
              type="button"
              class="adm-btn adm-btn-sm adm-btn-danger adm-project-delete-btn"
              data-project-id="<?= (int) $row['id'] ?>"
              data-project-title="<?= e($row['title']) ?>"
              onclick="event.stopPropagation();"
            >Delete</button>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</form>
<form method="post" id="adm-project-delete-form" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete" />
  <input type="hidden" name="id" id="adm-project-delete-id" value="" />
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

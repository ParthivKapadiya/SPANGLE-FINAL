<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once SPANGLE_ROOT . '/includes/cmsCopyKeys.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();

cms_sync_plain_home_fields($pdo);

$textKeys = [
    'home_hero_eyebrow' => 'Small label above main title',
    'home_hero_title_main' => 'Main headline (first part)',
    'home_hero_title_highlight' => 'Highlighted words in headline',
    'home_hero_lead' => 'Sub heading under title',
    'home_hero_btn_primary_text' => 'Primary button text',
    'home_hero_btn_primary_url' => 'Primary button link',
    'home_hero_btn_secondary_text' => 'Secondary button text',
    'home_hero_btn_secondary_url' => 'Secondary button link',
    'home_hero_scroll_text' => 'Scroll hint text',
    'home_hero_video_url' => 'Hero background video URL (MP4, optional)',
    'home_about_eyebrow' => 'About — small label',
    'home_about_title' => 'About — heading',
    'home_about_image_alt' => 'About image description',
    'home_about_caption' => 'About image caption',
    'home_capabilities_eyebrow' => 'Services block — label',
    'home_capabilities_title' => 'Services block — title',
    'home_capabilities_intro' => 'Services block — intro',
    'home_projects_eyebrow' => 'Projects block — label',
    'home_projects_title' => 'Projects block — title',
    'home_projects_intro' => 'Projects block — intro',
    'home_projects_limit' => 'Max featured projects on home (4–12)',
    'home_gallery_eyebrow' => 'Gallery block — label',
    'home_gallery_title' => 'Gallery block — title',
    'home_gallery_intro' => 'Gallery block — intro',
    'home_gallery_limit' => 'Max gallery images on home (4–24)',
    'home_process_eyebrow' => 'Process block — label',
    'home_process_title' => 'Process block — title',
    'home_process_intro' => 'Process block — intro',
    'home_testimonials_eyebrow' => 'Testimonials block — label',
    'home_testimonials_title' => 'Testimonials block — title',
    'home_awards_eyebrow' => 'Studio highlights — label',
    'home_awards_title' => 'Studio highlights — title',
    'home_cta_eyebrow' => 'Bottom call-to-action — label',
    'home_cta_title' => 'Bottom call-to-action — title',
    'home_cta_lead' => 'Bottom call-to-action — text',
    'home_cta_btn_text' => 'Bottom button text',
    'home_cta_btn_url' => 'Bottom button link',
    'contact_section_title' => 'Contact section — title',
    'contact_section_lead' => 'Contact section — intro',
    'home_link_about_text' => 'Link after About — text',
    'home_link_about_url' => 'Link after About — URL',
    'home_link_services_text' => 'Link after Services — text',
    'home_link_services_url' => 'Link after Services — URL',
    'home_link_work_text' => 'Link after Projects — text',
    'home_link_work_url' => 'Link after Projects — URL',
    'home_link_process_text' => 'Link after Process — text',
    'home_link_process_url' => 'Link after Process — URL',
];

$linkKeys = [
    'home_link_about_text', 'home_link_about_url',
    'home_link_services_text', 'home_link_services_url',
    'home_link_work_text', 'home_link_work_url',
    'home_link_process_text', 'home_link_process_url',
    'home_hero_btn_primary_text', 'home_hero_btn_primary_url',
    'home_hero_btn_secondary_text', 'home_hero_btn_secondary_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? 'save_home';

    if ($action === 'delete_hero_slide') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        if ($slideId > 0) {
            $pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$slideId]);
            admin_flash_set('success', 'Hero image removed.');
        }
        redirect('home.php');
    }

    if ($action === 'add_hero_slide' && !empty($_FILES['hero_slide_image']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['hero_slide_image']);
        if ($up['ok']) {
            $alt = trim((string) ($_POST['hero_slide_description'] ?? ''));
            if ($alt === '') {
                $alt = cms_humanize_filename_alt($up['path']);
            }
            $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM hero_slides')->fetchColumn();
            $pdo->prepare('INSERT INTO hero_slides (image_path, alt_text, sort_order, is_active) VALUES (?,?,?,1)')
                ->execute([$up['path'], $alt, $sort]);
            media_register($pdo, $up['path'], basename($up['path']));
            admin_flash_set('success', 'Hero image added.');
        } else {
            admin_flash_set('error', $up['error'] ?? 'Upload failed.');
        }
        redirect('home.php');
    }

    if ($action === 'update_hero_slide') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        if ($slideId > 0) {
            $alt = trim((string) ($_POST['hero_slide_description'] ?? ''));
            $sort = (int) ($_POST['sort_order'] ?? 0);
            $active = isset($_POST['is_active']) ? 1 : 0;
            if (!empty($_FILES['hero_slide_image']['name'])) {
                $up = Upload::image($appConfig, 'general', $_FILES['hero_slide_image']);
                if ($up['ok']) {
                    $pdo->prepare('UPDATE hero_slides SET image_path=?, alt_text=?, sort_order=?, is_active=? WHERE id=?')
                        ->execute([$up['path'], $alt, $sort, $active, $slideId]);
                    media_register($pdo, $up['path'], basename($up['path']));
                }
            } else {
                $pdo->prepare('UPDATE hero_slides SET alt_text=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$alt, $sort, $active, $slideId]);
            }
            admin_flash_set('success', 'Hero image updated.');
        }
        redirect('home.php');
    }

    foreach ($textKeys as $key => $label) {
        if (isset($_POST[$key])) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }

    $heroMain = trim((string) ($_POST['home_hero_title_main'] ?? ''));
    $heroHighlight = trim((string) ($_POST['home_hero_title_highlight'] ?? ''));
    setting_set($pdo, 'home_hero_title_html', cms_build_hero_title_html($heroMain, $heroHighlight));

    $aboutP1 = trim((string) ($_POST['home_about_paragraph_1'] ?? ''));
    $aboutP2 = trim((string) ($_POST['home_about_paragraph_2'] ?? ''));
    setting_set($pdo, 'home_about_paragraph_1', $aboutP1);
    setting_set($pdo, 'home_about_paragraph_2', $aboutP2);
    setting_set($pdo, 'home_about_lead_html', cms_build_about_lead_html($aboutP1, $aboutP2));

    if (!empty($_FILES['home_about_image']['name'])) {
        $up = Upload::image($appConfig, 'general', $_FILES['home_about_image']);
        if ($up['ok']) {
            setting_set($pdo, 'home_about_image', $up['path']);
            media_register($pdo, $up['path'], basename($up['path']));
        }
    }

    if (isset($_POST['stats']) && is_array($_POST['stats'])) {
        foreach ($_POST['stats'] as $statId => $pair) {
            $sid = (int) $statId;
            if ($sid <= 0) {
                continue;
            }
            $pdo->prepare('UPDATE home_stats SET stat_value = ?, stat_label = ? WHERE id = ?')
                ->execute([
                    trim((string) ($pair['value'] ?? '')),
                    trim((string) ($pair['label'] ?? '')),
                    $sid,
                ]);
        }
    }

    $pdo->exec('UPDATE projects SET home_highlight = 0');
    $featuredIds = $_POST['home_featured_ids'] ?? [];
    if (is_array($featuredIds) && $featuredIds !== []) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $featuredIds), static fn (int $id): bool => $id > 0)));
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE projects SET home_highlight = 1 WHERE id IN ($placeholders)")->execute($ids);
        }
    }
    if (isset($_POST['home_featured_sort']) && is_array($_POST['home_featured_sort'])) {
        $sortStmt = $pdo->prepare('UPDATE projects SET sort_order = ? WHERE id = ?');
        foreach ($_POST['home_featured_sort'] as $projectId => $sortVal) {
            $pid = (int) $projectId;
            if ($pid > 0) {
                $sortStmt->execute([(int) $sortVal, $pid]);
            }
        }
    }

    content_sync_site_json($pdo);
    admin_flash_set('success', 'Home page saved. Refresh your website to see changes.');
    redirect('home.php');
}

cms_seed_copy_settings($pdo);

$loadKeys = array_merge(
    array_keys($textKeys),
    ['home_about_paragraph_1', 'home_about_paragraph_2', 'home_about_image', 'home_hero_title_html', 'home_about_lead_html'],
    array_keys(cms_copy_setting_keys())
);
$s = settings_get_many($pdo, $loadKeys);

if (trim((string) ($s['home_hero_title_main'] ?? '')) === '' && trim((string) ($s['home_hero_title_html'] ?? '')) !== '') {
    $hero = cms_parse_hero_title_html((string) $s['home_hero_title_html']);
    $s['home_hero_title_main'] = $hero['main'];
    $s['home_hero_title_highlight'] = $hero['highlight'];
}
if (trim((string) ($s['home_about_paragraph_1'] ?? '')) === '' && trim((string) ($s['home_about_lead_html'] ?? '')) !== '') {
    $about = cms_parse_about_lead_html((string) $s['home_about_lead_html']);
    $s['home_about_paragraph_1'] = $about['paragraph1'];
    $s['home_about_paragraph_2'] = $about['paragraph2'];
}

$stats = $pdo->query('SELECT id, stat_value, stat_label FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
if (count($stats) < 4) {
    $defaults = [
        ['150+', 'Projects completed'],
        ['16+', 'Years in practice'],
        ['4', 'Directors & leads'],
        ['India', 'Pan-India delivery'],
    ];
    $ins = $pdo->prepare('INSERT INTO home_stats (stat_value, stat_label, sort_order) VALUES (?, ?, ?)');
    foreach ($defaults as $i => $d) {
        if ($i >= count($stats)) {
            $ins->execute([$d[0], $d[1], $i]);
        }
    }
    $stats = $pdo->query('SELECT id, stat_value, stat_label FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
}

$heroSlides = $pdo->query(
    'SELECT id, image_path, alt_text, sort_order, is_active FROM hero_slides ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$featuredProjectRows = $pdo->query(
    'SELECT id, title, location, hero_image, home_highlight, sort_order
     FROM projects WHERE is_active = 1
     ORDER BY home_highlight DESC, sort_order ASC, title ASC'
)->fetchAll(PDO::FETCH_ASSOC);
$featuredOnHomeCount = 0;
foreach ($featuredProjectRows as $fp) {
    if (!empty($fp['home_highlight'])) {
        $featuredOnHomeCount += 1;
    }
}

$pageTitle = 'Home page';
$pageDescription = 'Edit everything visitors see on your home page — text and images.';
$activeNav = 'home';
require __DIR__ . '/includes/layout.php';
?>

<p class="adm-hint adm-card" style="margin-bottom:1rem;">
  <strong>Tip:</strong> Service cards, project tiles, gallery photos, and testimonials are managed under
  <a href="services-page.php" class="adm-btn adm-btn-sm adm-btn-ghost">Services page</a>,
  <a href="services.php" class="adm-btn adm-btn-sm adm-btn-ghost">Service blocks</a>,
  <a href="projects.php" class="adm-btn adm-btn-sm adm-btn-ghost">Projects</a> (also pick <strong>Featured commissions</strong> below),
  <a href="gallery.php" class="adm-btn adm-btn-sm adm-btn-ghost">Home gallery</a>,
  <a href="testimonials.php" class="adm-btn adm-btn-sm adm-btn-ghost">Testimonials</a>,
  <a href="studio.php" class="adm-btn adm-btn-sm adm-btn-ghost">Studio page</a> (highlights), and
  <a href="process.php" class="adm-btn adm-btn-sm adm-btn-ghost">Process page</a>.
  Phone, email, and address are under <a href="settings.php" class="adm-btn adm-btn-sm adm-btn-ghost">Site settings</a>.
</p>

<div class="adm-card">
  <h2>Top banner — rotating images</h2>
  <p class="adm-hint">Large photos behind the headline. Keep 4–6 images. Lower order number shows first.</p>
  <div class="adm-media-grid adm-media-grid-large">
    <?php foreach ($heroSlides as $slide): ?>
      <div class="adm-media-item">
        <img src="../<?= e($slide['image_path']) ?>" alt="" loading="lazy" />
        <form method="post" enctype="multipart/form-data" class="adm-field" style="margin-top:0.5rem;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_hero_slide" />
          <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>" />
          <label>Order</label>
          <input type="number" name="sort_order" value="<?= (int) $slide['sort_order'] ?>" style="width:5rem;" />
          <label><input type="checkbox" name="is_active" value="1"<?= !empty($slide['is_active']) ? ' checked' : '' ?> /> Show on website</label>
          <label>Replace image</label>
          <input type="file" name="hero_slide_image" accept="image/*" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary">Update image</button>
        </form>
        <form method="post" data-confirm="Remove this banner image?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_hero_slide" />
          <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>" />
          <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Remove</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="adm-card adm-field">
    <h3>Add banner image</h3>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_hero_slide" />
      <label>Upload image</label>
      <input type="file" name="hero_slide_image" accept="image/*" required />
      <button type="submit" class="adm-btn adm-btn-primary">Add to banner</button>
    </form>
  </div>
</div>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_home" />

  <div class="adm-card">
    <h2>Top banner — text &amp; buttons</h2>
    <?php
    $heroFields = [
        'home_hero_eyebrow', 'home_hero_title_main', 'home_hero_title_highlight', 'home_hero_lead',
        'home_hero_video_url',
        'home_hero_btn_primary_text', 'home_hero_btn_primary_url', 'home_hero_btn_secondary_text',
        'home_hero_btn_secondary_url', 'home_hero_scroll_text',
    ];
    foreach ($heroFields as $key):
        ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($textKeys[$key] ?? $key) ?></label>
        <?php if ($key === 'home_hero_lead'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="3"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card">
    <h2>Statistics (hero glass cards + homepage bar)</h2>
    <p class="adm-hint">First four values power the floating glass cards in the hero and the stats bar below. Use formats like <strong>150+</strong>, <strong>2M+</strong>, or <strong>98%</strong>.</p>
    <?php foreach ($stats as $stat): ?>
      <div class="adm-field adm-field-row">
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][value]" value="<?= e($stat['stat_value']) ?>" placeholder="e.g. 150+" aria-label="Number" />
        <input type="text" name="stats[<?= (int) $stat['id'] ?>][label]" value="<?= e($stat['stat_label']) ?>" placeholder="e.g. Projects completed" aria-label="Label" />
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card">
    <h2>About section</h2>
    <?php foreach (['home_about_eyebrow', 'home_about_title'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($textKeys[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label for="home_about_paragraph_1">First paragraph</label>
      <textarea name="home_about_paragraph_1" id="home_about_paragraph_1" rows="4"><?= e($s['home_about_paragraph_1'] ?? '') ?></textarea>
    </div>
    <div class="adm-field">
      <label for="home_about_paragraph_2">Second paragraph (optional)</label>
      <textarea name="home_about_paragraph_2" id="home_about_paragraph_2" rows="4"><?= e($s['home_about_paragraph_2'] ?? '') ?></textarea>
    </div>
    <div class="adm-field">
      <label>About section photo</label>
      <?php $aboutImg = $s['home_about_image'] ?? setting_get($pdo, 'home_about_image'); ?>
      <?php if ($aboutImg): ?><p><img src="../<?= e($aboutImg) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p><?php endif; ?>
      <input type="file" name="home_about_image" accept="image/*" />
    </div>
    <?php foreach (['home_about_image_alt', 'home_about_caption'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($textKeys[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
    <div class="adm-field adm-field-row">
      <input type="text" name="home_link_about_text" value="<?= e($s['home_link_about_text'] ?? '') ?>" placeholder="Link text" />
      <input type="text" name="home_link_about_url" value="<?= e($s['home_link_about_url'] ?? '') ?>" placeholder="Link URL" />
    </div>
  </div>

  <div class="adm-card">
    <h2>Featured commissions (home page)</h2>
    <p class="adm-hint">Section title and intro appear above the project tiles on the home page. Tick which projects to show — lower order numbers appear first. Set a max count so tiles stay a readable size on the home page. If none are selected, the site shows the first four projects automatically.</p>
    <?php
    $featuredTextKeys = [
        'home_projects_eyebrow', 'home_projects_title', 'home_projects_intro', 'home_projects_limit',
        'home_link_work_text', 'home_link_work_url',
    ];
    foreach ($featuredTextKeys as $key):
        ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($textKeys[$key] ?? $key) ?></label>
        <?php if ($key === 'home_projects_limit'): ?>
          <input type="number" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e((string) ($s[$key] ?? '8')) ?>" min="4" max="12" step="1" />
        <?php elseif (str_contains($key, '_intro')): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <p class="adm-hint" style="margin-top:1rem;">
      <strong><?= (int) $featuredOnHomeCount ?></strong> project<?= $featuredOnHomeCount === 1 ? '' : 's' ?> selected for the home page.
      <a href="projects.php" class="adm-btn adm-btn-sm adm-btn-ghost">Manage all projects</a>
    </p>

    <?php if ($featuredProjectRows === []): ?>
      <p class="adm-hint">No active projects yet. <a href="projects.php?action=new">Add a project</a> or import from uploads.</p>
    <?php else: ?>
      <div class="adm-home-feature-list">
        <?php foreach ($featuredProjectRows as $proj): ?>
          <label class="adm-home-feature-item<?= !empty($proj['home_highlight']) ? ' is-selected' : '' ?>">
            <input type="checkbox" name="home_featured_ids[]" value="<?= (int) $proj['id'] ?>"<?= !empty($proj['home_highlight']) ? ' checked' : '' ?> />
            <span class="adm-home-feature-thumb">
              <?php if (!empty($proj['hero_image'])): ?>
                <img src="../<?= e($proj['hero_image']) ?>" alt="" loading="lazy" />
              <?php else: ?>
                <span class="adm-home-feature-placeholder" aria-hidden="true">No image</span>
              <?php endif; ?>
            </span>
            <span class="adm-home-feature-meta">
              <strong><?= e($proj['title']) ?></strong>
              <span><?= e($proj['location'] ?? '') ?></span>
            </span>
            <span class="adm-home-feature-sort">
              <span class="adm-home-feature-sort-label">Order</span>
              <input type="number" name="home_featured_sort[<?= (int) $proj['id'] ?>]" value="<?= (int) ($proj['sort_order'] ?? 0) ?>" min="0" step="1" aria-label="Sort order for <?= e($proj['title']) ?>" />
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php
  $sections = [
      'Services on home page' => ['home_capabilities_eyebrow', 'home_capabilities_title', 'home_capabilities_intro', 'home_link_services_text', 'home_link_services_url'],
      'Gallery on home page' => ['home_gallery_eyebrow', 'home_gallery_title', 'home_gallery_intro', 'home_gallery_limit'],
      'Process on home page' => ['home_process_eyebrow', 'home_process_title', 'home_process_intro', 'home_link_process_text', 'home_link_process_url'],
      'Testimonials' => ['home_testimonials_eyebrow', 'home_testimonials_title'],
      'Bottom invitation' => ['home_cta_eyebrow', 'home_cta_title', 'home_cta_lead', 'home_cta_btn_text', 'home_cta_btn_url'],
      'Contact on home page' => ['contact_section_title', 'contact_section_lead'],
  ];
  foreach ($sections as $heading => $keys):
      ?>
    <div class="adm-card">
      <h2><?= e($heading) ?></h2>
      <?php foreach ($keys as $key): ?>
        <div class="adm-field">
          <label for="<?= e($key) ?>"><?= e($textKeys[$key] ?? $key) ?></label>
          <?php if (str_contains($key, '_intro') || str_contains($key, '_lead')): ?>
            <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
          <?php else: ?>
            <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save entire home page</button>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-end.php'; ?>

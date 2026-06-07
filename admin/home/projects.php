<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('projects');
extract(home_admin_page_vars($section));

$keys = [
    'home_projects_eyebrow', 'home_projects_title', 'home_projects_intro', 'home_projects_limit',
    'home_link_work_text', 'home_link_work_url',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
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
    admin_log_activity($pdo, 'save', 'home-projects', null, 'Home projects section updated');
    home_admin_sync_and_redirect('projects.php', 'Projects section saved.');
}

$s = settings_get_many($pdo, $keys);
$featuredProjectRows = $pdo->query(
    'SELECT id, title, location, hero_image, home_highlight, sort_order
     FROM projects WHERE is_active = 1
     ORDER BY home_highlight DESC, sort_order ASC, title ASC'
)->fetchAll(PDO::FETCH_ASSOC);
$featuredOnHomeCount = count(array_filter($featuredProjectRows, static fn (array $row): bool => !empty($row['home_highlight'])));

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('home_projects_eyebrow', 'Small label', $s);
    home_admin_render_field('home_projects_title', 'Title', $s);
    home_admin_render_field('home_projects_intro', 'Intro paragraph', $s, 'textarea');
    home_admin_render_field('home_projects_limit', 'Max projects on home (4–12)', $s, 'number');
    home_admin_render_link_row('home_link_work_text', 'home_link_work_url', $s);
    ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Featured projects</h2>
    <p class="adm-hint"><strong><?= (int) $featuredOnHomeCount ?></strong> project<?= $featuredOnHomeCount === 1 ? '' : 's' ?> selected. Tick projects to show on the home page.</p>
    <?php home_admin_card_link('projects.php', 'Manage all projects'); ?>
    <?php if ($featuredProjectRows === []): ?>
      <p class="adm-hint">No active projects yet.</p>
    <?php else: ?>
      <div class="adm-home-feature-list">
        <?php foreach ($featuredProjectRows as $proj): ?>
          <label class="adm-home-feature-item<?= !empty($proj['home_highlight']) ? ' is-selected' : '' ?>">
            <input type="checkbox" name="home_featured_ids[]" value="<?= (int) $proj['id'] ?>"<?= !empty($proj['home_highlight']) ? ' checked' : '' ?> />
            <span class="adm-home-feature-thumb">
              <?php if (!empty($proj['hero_image'])): ?>
                <img src="../../<?= e($proj['hero_image']) ?>" alt="" loading="lazy" />
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
              <input type="number" name="home_featured_sort[<?= (int) $proj['id'] ?>]" value="<?= (int) ($proj['sort_order'] ?? 0) ?>" min="0" step="1" />
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php home_admin_render_save('Save projects section'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

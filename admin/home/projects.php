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
    $limit = max(4, min(12, (int) ($_POST['home_projects_limit'] ?? 8)));
    setting_set($pdo, 'home_projects_limit', (string) $limit);
    $pdo->exec('UPDATE projects SET home_highlight = 0');
    $featuredIds = $_POST['home_featured_ids'] ?? [];
    $ids = [];
    if (is_array($featuredIds) && $featuredIds !== []) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $featuredIds), static fn (int $id): bool => $id > 0)));
    }
    if (count($ids) < $limit) {
        $have = array_flip($ids);
        $rows = $pdo->query(
            'SELECT id FROM projects WHERE is_active = 1 ORDER BY sort_order ASC, title ASC, id ASC'
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $pid) {
            $pid = (int) $pid;
            if (count($ids) >= $limit) {
                break;
            }
            if (!isset($have[$pid])) {
                $ids[] = $pid;
            }
        }
    }
    $ids = array_slice($ids, 0, $limit);
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE projects SET home_highlight = 1 WHERE id IN ($placeholders)")->execute($ids);
    }
    if (isset($_POST['home_featured_sort']) && is_array($_POST['home_featured_sort'])) {
        $sortStmt = $pdo->prepare('UPDATE projects SET sort_order = ? WHERE id = ?');
        foreach ($_POST['home_featured_sort'] as $projectId => $sortVal) {
            $pid = (int) $projectId;
            if ($pid <= 0) {
                continue;
            }
            $sortStmt->execute([(int) $sortVal, $pid]);
        }
    }
    admin_log_activity($pdo, 'save', 'home-projects', null, 'Home projects section updated');
    home_admin_sync_and_redirect('projects.php', 'Projects section saved.');
}

$s = settings_get_many($pdo, $keys);
$s['home_projects_limit'] = (string) max(4, min(12, (int) ($s['home_projects_limit'] ?? 8)));
$featuredProjectRows = $pdo->query(
    'SELECT id, title, location, hero_image, home_highlight, sort_order
     FROM projects WHERE is_active = 1
     ORDER BY home_highlight DESC, sort_order ASC, title ASC'
)->fetchAll(PDO::FETCH_ASSOC);
$featuredOnHomeCount = count(array_filter($featuredProjectRows, static fn (array $row): bool => !empty($row['home_highlight'])));
$projectLimit = (int) $s['home_projects_limit'];
$orderReady = $featuredOnHomeCount === $projectLimit;

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid" id="adm-home-projects-form" data-project-limit="<?= $projectLimit ?>">
  <?= csrf_field() ?>
  <div class="adm-toolbar adm-save-toolbar">
    <p class="adm-hint adm-home-projects-status" id="adm-home-projects-status" style="margin:0;"></p>
    <button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save projects section</button>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Section heading</h2>
    <?php
    home_admin_render_field('home_projects_eyebrow', 'Small label', $s);
    home_admin_render_field('home_projects_title', 'Title', $s);
    home_admin_render_field('home_projects_intro', 'Intro paragraph', $s, 'textarea');
    ?>
    <div class="adm-field">
      <label>Step 1 — How many projects on the home page?</label>
      <input type="hidden" name="home_projects_limit" id="home_projects_limit" value="<?= $projectLimit ?>" />
      <div class="adm-limit-picker" data-target="home_projects_limit" data-projects-form="adm-home-projects-form" role="group" aria-label="Projects on home page">
        <?php for ($n = 4; $n <= 12; $n++): ?>
          <button type="button" class="adm-limit-btn<?= $projectLimit === $n ? ' is-active' : '' ?>" data-value="<?= $n ?>"><?= $n ?></button>
        <?php endfor; ?>
      </div>
    </div>
    <p class="adm-hint">Tap a number — that many projects are auto-selected below. Adjust checkboxes if needed, set order, then save.</p>
    <?php home_admin_render_link_row('home_link_work_text', 'home_link_work_url', $s); ?>
  </div>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Step 2 — Select projects</h2>
    <p class="adm-hint adm-home-projects-step-hint" id="adm-home-projects-step-hint">
      <?php if ($orderReady): ?>
        All <?= $projectLimit ?> projects selected — set display order below, then save.
      <?php else: ?>
        Select <?= $projectLimit ?> project<?= $projectLimit === 1 ? '' : 's' ?> (<?= (int) $featuredOnHomeCount ?> of <?= $projectLimit ?> chosen).
      <?php endif; ?>
    </p>
    <?php home_admin_card_link('projects.php', 'Manage all projects'); ?>
    <?php if ($featuredProjectRows === []): ?>
      <p class="adm-hint">No active projects yet.</p>
    <?php else: ?>
      <div class="adm-home-feature-list adm-home-feature-list--scroll<?= $orderReady ? ' is-order-ready' : '' ?>" id="adm-home-feature-list">
        <?php foreach ($featuredProjectRows as $i => $proj): ?>
          <?php $isSelected = !empty($proj['home_highlight']); ?>
          <label class="adm-home-feature-item<?= $isSelected ? ' is-selected' : '' ?>" data-project-item>
            <input type="checkbox" name="home_featured_ids[]" value="<?= (int) $proj['id'] ?>" class="adm-home-feature-check"<?= $isSelected ? ' checked' : '' ?> />
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
              <input
                type="number"
                name="home_featured_sort[<?= (int) $proj['id'] ?>]"
                class="adm-home-feature-sort-input"
                value="<?= (int) ($proj['sort_order'] ?? $i) ?>"
                min="0"
                step="1"
                <?= ($isSelected && $orderReady) ? '' : 'disabled' ?>
              />
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php home_admin_render_save('Save projects section', true); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

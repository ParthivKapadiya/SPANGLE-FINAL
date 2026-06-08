<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
require_once __DIR__ . '/includes/media.php';

admin_require_auth();
cms_sync_plain_process_fields($pdo);

$action = $_GET['action'] ?? 'page';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$pageKeys = [
    'process_kicker', 'process_title', 'process_lead',
    'process_split_eyebrow', 'process_split_title',
    'process_split_paragraph_1', 'process_split_paragraph_2',
    'process_timeline_eyebrow', 'process_timeline_title',
    'process_cta_eyebrow', 'process_cta_title', 'process_cta_sub', 'process_cta_text',
    'process_cta_btn_text', 'process_cta_btn_url', 'process_cta_btn2_text', 'process_cta_btn2_url',
    'process_faq_eyebrow', 'process_faq_title',
    'process_faq_q1', 'process_faq_a1', 'process_faq_q2', 'process_faq_a2',
    'process_faq_q3', 'process_faq_a3', 'process_faq_q4', 'process_faq_a4',
    'process_faq_q5', 'process_faq_a5',
];

$labels = [
    'process_kicker' => 'Top banner — small label',
    'process_title' => 'Top banner — main heading',
    'process_lead' => 'Top banner — intro text',
    'process_split_eyebrow' => 'Middle section — small label',
    'process_split_title' => 'Middle section — heading',
    'process_split_paragraph_1' => 'Middle section — first paragraph',
    'process_split_paragraph_2' => 'Middle section — second paragraph',
    'process_timeline_eyebrow' => 'Timeline — small label',
    'process_timeline_title' => 'Timeline — heading',
    'process_cta_eyebrow' => 'Bottom section — small label',
    'process_cta_title' => 'Bottom section — heading',
    'process_cta_sub' => 'Bottom section — subheadline',
    'process_cta_text' => 'Bottom section — text',
    'process_cta_btn_text' => 'Primary button — label',
    'process_cta_btn_url' => 'Primary button — link',
    'process_cta_btn2_text' => 'Secondary button — label',
    'process_cta_btn2_url' => 'Secondary button — link',
    'process_faq_eyebrow' => 'FAQ — small label',
    'process_faq_title' => 'FAQ — heading',
    'process_faq_q1' => 'FAQ 1 — question',
    'process_faq_a1' => 'FAQ 1 — answer',
    'process_faq_q2' => 'FAQ 2 — question',
    'process_faq_a2' => 'FAQ 2 — answer',
    'process_faq_q3' => 'FAQ 3 — question',
    'process_faq_a3' => 'FAQ 3 — answer',
    'process_faq_q4' => 'FAQ 4 — question',
    'process_faq_a4' => 'FAQ 4 — answer',
    'process_faq_q5' => 'FAQ 5 — question',
    'process_faq_a5' => 'FAQ 5 — answer',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postAction = $_POST['action'] ?? 'save_page';

    if ($postAction === 'delete_step' && $id > 0) {
        $pdo->prepare('DELETE FROM process_steps WHERE id = ?')->execute([$id]);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Timeline step removed.');
        redirect('process.php');
    }

    if ($postAction === 'save_step') {
        $label = trim($_POST['step_label'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $context = trim($_POST['context'] ?? 'page');
        if (!in_array($context, ['home', 'page', 'both'], true)) {
            $context = 'page';
        }
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            $pdo->prepare(
                'UPDATE process_steps SET step_label=?, title=?, description=?, context=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([$label, $title, $description, $context, $sort, $active, $editId]);
        } else {
            $pdo->prepare(
                'INSERT INTO process_steps (step_label, title, description, context, sort_order, is_active) VALUES (?,?,?,?,?,?)'
            )->execute([$label, $title, $description, $context, $sort, $active]);
        }
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Timeline step saved.');
        $return = trim((string) ($_POST['return'] ?? ''));
        redirect($return === 'services/process.php' ? $return : 'process.php');
    }

    if ($postAction === 'save_page') {
        foreach ($pageKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                setting_set($pdo, $key, trim((string) $_POST[$key]));
            }
        }
        $p1 = trim((string) ($_POST['process_split_paragraph_1'] ?? ''));
        $p2 = trim((string) ($_POST['process_split_paragraph_2'] ?? ''));
        setting_set($pdo, 'process_split_lead_html', cms_build_about_lead_html($p1, $p2));

        foreach (['process_hero_image', 'process_split_image'] as $imgKey) {
            $fileKey = $imgKey . '_file';
            if (!empty($_FILES[$fileKey]['name'])) {
                $up = Upload::image($appConfig, 'general', $_FILES[$fileKey]);
                if ($up['ok']) {
                    setting_set($pdo, $imgKey, $up['path']);
                    media_register($pdo, $up['path'], basename($up['path']));
                }
            }
        }

        cms_sync_plain_process_fields($pdo);
        content_sync_site_json($pdo);
        admin_flash_set('success', 'Process page saved.');
        redirect('process.php');
    }
}

if ($action === 'edit_step' || $action === 'new_step') {
    $row = [
        'id' => 0, 'step_label' => 'Phase 01', 'title' => '', 'description' => '',
        'context' => 'page', 'sort_order' => 0, 'is_active' => 1,
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM process_steps WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
    $pageTitle = $id > 0 ? 'Edit timeline step' : 'Add timeline step';
    $pageDescription = 'Shown on the Services page, Process page timeline, and Home page (when set to Home).';
    $returnTo = trim((string) ($_GET['return'] ?? ''));
    if ($returnTo !== 'services/process.php') {
        $returnTo = '';
    }
    $activeNav = 'process';
    require __DIR__ . '/includes/layout.php';
    if ($returnTo !== ''): ?>
      <p class="adm-hint adm-card" style="margin-bottom:1rem;">
        <a href="<?= e(admin_href($returnTo)) ?>" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to Services → Process</a>
      </p>
    <?php endif; ?>
    <form method="post" class="adm-card">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_step" />
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
      <?php if ($returnTo !== ''): ?>
        <input type="hidden" name="return" value="<?= e($returnTo) ?>" />
      <?php endif; ?>
      <div class="adm-field">
        <label>Step label</label>
        <input type="text" name="step_label" value="<?= e($row['step_label']) ?>" placeholder="e.g. Phase 01 or I" required />
        <p class="adm-hint">Short tag shown beside each step (Phase 01, II, Close-out, etc.).</p>
      </div>
      <div class="adm-field"><label>Title</label><input type="text" name="title" value="<?= e($row['title']) ?>" required /></div>
      <div class="adm-field"><label>Description</label><textarea name="description" rows="3"><?= e($row['description'] ?? '') ?></textarea></div>
      <div class="adm-field">
        <label>Show on</label>
        <select name="context">
          <option value="page"<?= ($row['context'] ?? '') === 'page' ? ' selected' : '' ?>>Services &amp; Process pages</option>
          <option value="home"<?= ($row['context'] ?? '') === 'home' ? ' selected' : '' ?>>Home page only</option>
          <option value="both"<?= ($row['context'] ?? '') === 'both' ? ' selected' : '' ?>>Home, Services &amp; Process</option>
        </select>
        <p class="adm-hint">Services and Process pages share the same steps. Home shows steps set to Home or Both.</p>
      </div>
      <div class="adm-field"><label>Order</label><input type="number" name="sort_order" value="<?= (int) $row['sort_order'] ?>" style="width:6rem;" /></div>
      <div class="adm-field"><label><input type="checkbox" name="is_active" value="1"<?= !empty($row['is_active']) ? ' checked' : '' ?> /> Visible on website</label></div>
      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">Save step</button>
        <a href="process.php<?= $returnTo !== '' ? '?return=' . rawurlencode($returnTo) : '' ?>" class="adm-btn adm-btn-ghost">Cancel</a>
      </div>
    </form>
    <?php
    require __DIR__ . '/includes/layout-end.php';
    exit;
}

$s = settings_get_many($pdo, array_merge($pageKeys, ['process_hero_image', 'process_split_image']));
$steps = $pdo->query(
    'SELECT id, step_label, title, context, sort_order, is_active FROM process_steps ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Process page';
$pageDescription = 'Edit all text and images on the Process page, plus timeline steps.';
$activeNav = 'process';
require __DIR__ . '/includes/layout.php';
?>

<p class="adm-hint adm-card" style="margin-bottom:1rem;">
  Changes appear on <a href="../process.html" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost">process.html</a>.
  The home page process block is edited under <a href="home.php" class="adm-btn adm-btn-sm adm-btn-ghost">Home page</a>.
</p>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_page" />

  <div class="adm-card">
    <h2>Top banner</h2>
    <?php foreach (['process_kicker', 'process_title', 'process_lead'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <?php if ($key === 'process_lead'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="3"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label>Banner background image</label>
      <?php if (!empty($s['process_hero_image'])): ?>
        <p><img src="../<?= e($s['process_hero_image']) ?>" alt="" style="max-width:280px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="process_hero_image_file" accept="image/*" />
    </div>
  </div>

  <div class="adm-card">
    <h2>Middle section (text + image)</h2>
    <?php foreach (['process_split_eyebrow', 'process_split_title'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
    <?php foreach (['process_split_paragraph_1', 'process_split_paragraph_2'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="4"><?= e($s[$key] ?? '') ?></textarea>
      </div>
    <?php endforeach; ?>
    <div class="adm-field">
      <label>Side image</label>
      <?php if (!empty($s['process_split_image'])): ?>
        <p><img src="../<?= e($s['process_split_image']) ?>" alt="" style="max-width:240px;border-radius:8px;" /></p>
      <?php endif; ?>
      <input type="file" name="process_split_image_file" accept="image/*" />
    </div>
  </div>

  <div class="adm-card">
    <h2>Timeline section heading</h2>
    <p class="adm-hint">Individual steps are listed below. Use “Add timeline step” to create or edit each phase.</p>
    <?php foreach (['process_timeline_eyebrow', 'process_timeline_title'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-card">
    <h2>FAQ section</h2>
    <?php foreach (['process_faq_eyebrow', 'process_faq_title'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      </div>
    <?php endforeach; ?>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <div class="adm-field">
        <label for="process_faq_q<?= $i ?>"><?= e($labels['process_faq_q' . $i]) ?></label>
        <input type="text" name="process_faq_q<?= $i ?>" id="process_faq_q<?= $i ?>" value="<?= e($s['process_faq_q' . $i] ?? '') ?>" />
      </div>
      <div class="adm-field">
        <label for="process_faq_a<?= $i ?>"><?= e($labels['process_faq_a' . $i]) ?></label>
        <textarea name="process_faq_a<?= $i ?>" id="process_faq_a<?= $i ?>" rows="2"><?= e($s['process_faq_a' . $i] ?? '') ?></textarea>
      </div>
    <?php endfor; ?>
  </div>

  <div class="adm-card">
    <h2>Bottom call-to-action</h2>
    <?php foreach (['process_cta_eyebrow', 'process_cta_title', 'process_cta_sub', 'process_cta_text', 'process_cta_btn_text', 'process_cta_btn_url', 'process_cta_btn2_text', 'process_cta_btn2_url'] as $key): ?>
      <div class="adm-field">
        <label for="<?= e($key) ?>"><?= e($labels[$key]) ?></label>
        <?php if ($key === 'process_cta_text'): ?>
          <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="2"><?= e($s[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save Process page</button>
  </div>
</form>

<div class="adm-card" style="margin-top:1.5rem;">
  <div class="adm-actions" style="margin-bottom:1rem;">
    <h2>Timeline steps</h2>
    <p class="adm-hint">These steps appear on the Services page, Process page, and Home page (depending on “Show on”). Edit them under <a href="services/process.php">Services page → Process preview</a> or below.</p>
    <a href="process.php?action=new_step" class="adm-btn adm-btn-primary adm-btn-sm">Add timeline step</a>
  </div>
  <?php if (!$steps): ?>
    <p class="adm-hint">No steps yet. Add at least one for the Process page timeline.</p>
  <?php else: ?>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead>
          <tr><th>Label</th><th>Title</th><th>Show on</th><th>Order</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($steps as $row): ?>
            <tr>
              <td><?= e($row['step_label']) ?></td>
              <td><?= e($row['title']) ?></td>
              <td><?= e($row['context']) ?></td>
              <td><?= (int) $row['sort_order'] ?></td>
              <td><?= !empty($row['is_active']) ? 'Live' : 'Hidden' ?></td>
              <td class="adm-table-actions">
                <a href="process.php?action=edit_step&id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Edit</a>
                <form method="post" data-confirm="Remove this step?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_step" />
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                  <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout-end.php'; ?>

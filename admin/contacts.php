<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

admin_require_auth();

$statuses = ['new', 'contacted', 'in_progress', 'closed'];

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $pdo->query(
        'SELECT id, created_at, name, phone, email, subject, budget_range, location, form_source, status, message
         FROM contact_messages ORDER BY created_at DESC'
    );
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="spangle-inquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Name', 'Mobile', 'Email', 'Project Type', 'Budget', 'Location', 'Source', 'Status', 'Message']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['created_at'],
            $row['name'],
            $row['phone'],
            $row['email'],
            $row['subject'],
            $row['budget_range'] ?? '',
            $row['location'] ?? '',
            $row['form_source'] ?? 'contact',
            $row['status'],
            $row['message'],
        ]);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $postId = (int) ($_POST['id'] ?? 0);
    $postAction = $_POST['action'] ?? 'update';
    if ($postAction === 'delete' && $postId > 0) {
        $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$postId]);
        admin_flash_set('success', 'Inquiry deleted.');
        redirect('contacts.php');
    }
    $status = (string) ($_POST['status'] ?? 'new');
    if (!in_array($status, $statuses, true)) {
        $status = 'new';
    }
    if ($postId > 0) {
        $pdo->prepare('UPDATE contact_messages SET status = ?, is_read = 1 WHERE id = ?')->execute([$status, $postId]);
        admin_flash_set('success', 'Inquiry updated.');
    }
    redirect('contacts.php' . ($postId ? '?id=' . $postId : ''));
}

$viewId = (int) ($_GET['id'] ?? 0);
$search = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = 'SELECT id, name, email, phone, subject, budget_range, location, form_source, status, created_at FROM contact_messages WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($filterStatus !== '' && in_array($filterStatus, $statuses, true)) {
    $sql .= ' AND status = ?';
    $params[] = $filterStatus;
}
$sql .= ' ORDER BY created_at DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$detail = null;
if ($viewId > 0) {
    $d = $pdo->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $d->execute([$viewId]);
    $detail = $d->fetch(PDO::FETCH_ASSOC);
    if ($detail && !(int) $detail['is_read']) {
        $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$viewId]);
    }
}

$pageTitle = 'Inquiries';
$pageDescription = 'Consultation and contact form submissions — filter, update status, or export.';
$activeNav = 'contacts';
$mainClass = 'adm-main--wide';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-toolbar">
  <div class="adm-actions" style="margin:0;">
    <a href="contacts.php?export=csv" class="adm-btn adm-btn-primary"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
    <a href="contacts.php?status=new" class="adm-btn adm-btn-ghost">New only</a>
    <a href="contacts.php" class="adm-btn adm-btn-ghost">All inquiries</a>
  </div>
  <p class="adm-hint" style="margin:0;">Consultation leads include project type, budget, and location.</p>
</div>
<form method="get" class="adm-card adm-search-bar">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, email, phone…" />
  <select name="status" aria-label="Filter by status">
    <option value="">All statuses</option>
    <?php foreach ($statuses as $st): ?>
      <option value="<?= e($st) ?>"<?= $filterStatus === $st ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="adm-btn adm-btn-ghost">Filter</button>
</form>
<div class="adm-split">
  <div class="adm-card adm-card-scroll">
    <table class="adm-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Name</th>
          <th>Mobile</th>
          <th>Project</th>
          <th>Budget</th>
          <th>Source</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr class="<?= $viewId === (int) $row['id'] ? 'is-selected' : '' ?>">
            <td><small><?= e(substr((string) $row['created_at'], 0, 10)) ?></small></td>
            <td>
              <a href="contacts.php?id=<?= (int) $row['id'] ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="adm-btn adm-btn-sm adm-btn-ghost"><?= e($row['name']) ?></a>
            </td>
            <td><?= e($row['phone'] ?: '—') ?></td>
            <td><?= e($row['subject'] ?: '—') ?></td>
            <td><?= e($row['budget_range'] ?? '—') ?></td>
            <td><?= admin_inquiry_source_badge($row['form_source'] ?? 'contact') ?></td>
            <td><?= admin_inquiry_status_badge((string) ($row['status'] ?? 'new')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="7">No inquiries yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($detail): ?>
    <div class="adm-card">
      <h2><?= e($detail['name']) ?></h2>
      <p><strong>Email:</strong><br><a href="mailto:<?= e($detail['email']) ?>" class="adm-contact-email"><?= e($detail['email']) ?></a></p>
      <?php if ($detail['phone']): ?><p><strong>Mobile:</strong> <a href="tel:<?= e(preg_replace('/\s+/', '', (string) $detail['phone'])) ?>"><?= e($detail['phone']) ?></a></p><?php endif; ?>
      <?php if (!empty($detail['subject'])): ?><p><strong>Project type:</strong> <?= e($detail['subject']) ?></p><?php endif; ?>
      <?php if (!empty($detail['budget_range'])): ?><p><strong>Budget:</strong> <?= e($detail['budget_range']) ?></p><?php endif; ?>
      <?php if (!empty($detail['location'])): ?><p><strong>Location:</strong> <?= e($detail['location']) ?></p><?php endif; ?>
      <p><strong>Source:</strong> <?= admin_inquiry_source_badge($detail['form_source'] ?? 'contact') ?></p>
      <p><strong>Status:</strong> <?= admin_inquiry_status_badge((string) ($detail['status'] ?? 'new')) ?></p>
      <p><strong>Date:</strong> <?= e($detail['created_at']) ?></p>
      <div class="adm-message-box"><?= nl2br(e($detail['message'])) ?></div>
      <form method="post" class="adm-actions">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="id" value="<?= (int) $detail['id'] ?>" />
        <label>Status</label>
        <select name="status">
          <?php foreach ($statuses as $st): ?>
            <option value="<?= e($st) ?>"<?= ($detail['status'] ?? '') === $st ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="adm-btn adm-btn-primary">Update status</button>
      </form>
      <form method="post" class="adm-actions" data-confirm="Delete this inquiry permanently?">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="id" value="<?= (int) $detail['id'] ?>" />
        <button type="submit" class="adm-btn adm-btn-danger">Delete inquiry</button>
      </form>
    </div>
  <?php else: ?>
    <div class="adm-card"><p class="adm-hint">Select an inquiry to read the full message.</p></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

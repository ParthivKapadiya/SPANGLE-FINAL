<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
admin_require_auth();

$stats = [
    'projects' => (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE is_active = 1')->fetchColumn(),
    'projects_draft' => (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE is_active = 0')->fetchColumn(),
    'gallery' => (int) $pdo->query('SELECT COUNT(*) FROM gallery_items WHERE is_active = 1')->fetchColumn(),
    'inquiries' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
    'testimonials' => (int) $pdo->query('SELECT COUNT(*) FROM testimonials WHERE is_active = 1')->fetchColumn(),
    'media' => (int) $pdo->query('SELECT COUNT(*) FROM media_assets')->fetchColumn(),
];

$leadStats = [
    'new' => (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'consultation' => (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE form_source = 'consultation'")->fetchColumn(),
    'in_progress' => (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'in_progress'")->fetchColumn(),
];

$recent = $pdo->query(
    'SELECT id, name, email, phone, subject, budget_range, form_source, status, created_at
     FROM contact_messages ORDER BY created_at DESC LIMIT 8'
)->fetchAll(PDO::FETCH_ASSOC);

$activity = admin_recent_activity($pdo, 10);
$storage = admin_uploads_usage($appConfig);
$storageMb = round($storage['bytes'] / 1048576, 1);
$storageCapMb = 512;
$storagePct = min(100, (int) round(($storageMb / $storageCapMb) * 100));

$gaId = trim((string) (settings_get_many($pdo, ['analytics_ga_id'])['analytics_ga_id'] ?? ''));

$statCards = [
    ['key' => 'projects', 'href' => 'projects.php', 'label' => 'Published projects', 'icon' => 'fa-folder-open'],
    ['key' => 'inquiries', 'href' => 'contacts.php', 'label' => 'Total inquiries', 'icon' => 'fa-inbox'],
    ['key' => 'media', 'href' => 'media.php', 'label' => 'Media assets', 'icon' => 'fa-images'],
];

$pageTitle = 'Dashboard';
$pageDescription = 'Content overview, leads, storage, and recent CMS activity.';
$activeNav = 'dashboard';
$mainClass = 'adm-main--wide';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-dash-hero adm-glass adm-animate-in">
  <div>
    <p class="adm-dash-eyebrow">Archevo Design CMS</p>
    <h2 class="adm-dash-title">Manage your entire website — no code required</h2>
    <p class="adm-hint">Every page, image, project, and inquiry is controlled from this panel. Changes sync to the live site automatically.</p>
  </div>
  <div class="adm-quick-actions adm-quick-actions--inline">
    <a href="projects.php?action=new" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Add project</a>
    <a href="contact-page.php" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-envelope"></i> Edit contact</a>
    <a href="home/index.php" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-house"></i> Edit home page</a>
    <a href="../index.html" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> View website</a>
  </div>
</div>

<div class="adm-dash-highlight adm-animate-in adm-animate-in--1">
  <div class="adm-card adm-glass adm-stat-tile">
    <i class="fa-solid fa-inbox adm-stat-icon"></i>
    <strong><?= $leadStats['new'] ?></strong>
    <span>New inquiries</span>
    <a href="contacts.php?status=new" class="adm-btn adm-btn-sm adm-btn-ghost">Review</a>
  </div>
  <div class="adm-card adm-glass adm-stat-tile">
    <i class="fa-solid fa-calendar-check adm-stat-icon"></i>
    <strong><?= $leadStats['consultation'] ?></strong>
    <span>Consultation leads</span>
  </div>
  <div class="adm-card adm-glass adm-stat-tile">
    <i class="fa-solid fa-spinner adm-stat-icon"></i>
    <strong><?= $leadStats['in_progress'] ?></strong>
    <span>In progress</span>
  </div>
  <div class="adm-card adm-glass adm-stat-tile">
    <i class="fa-solid fa-hard-drive adm-stat-icon"></i>
    <strong><?= e(admin_format_bytes($storage['bytes'])) ?></strong>
    <span><?= (int) $storage['files'] ?> files in uploads</span>
    <div class="adm-storage-bar" role="progressbar" aria-valuenow="<?= $storagePct ?>" aria-valuemin="0" aria-valuemax="100">
      <span style="width:<?= $storagePct ?>%"></span>
    </div>
  </div>
</div>

<div class="adm-grid adm-animate-in adm-animate-in--2">
  <?php foreach ($statCards as $card): ?>
    <a href="<?= e($card['href']) ?>" class="adm-card adm-glass adm-stat adm-stat-link">
      <i class="fa-solid <?= e($card['icon']) ?> adm-stat-icon"></i>
      <strong><?= $stats[$card['key']] ?></strong>
      <span><?= e($card['label']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="adm-dash-split adm-animate-in adm-animate-in--3">
  <div class="adm-card adm-glass">
    <div class="adm-toolbar">
      <h2 style="margin:0;">Recent inquiries</h2>
      <div class="adm-actions">
        <a href="contacts.php?export=csv" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-file-csv"></i> Export</a>
        <a href="contacts.php" class="adm-btn adm-btn-sm adm-btn-ghost">View all</a>
      </div>
    </div>
    <?php if (!$recent): ?>
      <p class="adm-hint">No messages yet. Contact and consultation form submissions appear here.</p>
    <?php else: ?>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Name</th>
              <th>Project</th>
              <th>Source</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $row): ?>
              <tr>
                <td><small><?= e(substr((string) $row['created_at'], 0, 10)) ?></small></td>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['subject'] ?: '—') ?></td>
                <td><?= admin_inquiry_source_badge($row['form_source'] ?? 'contact') ?></td>
                <td><?= admin_inquiry_status_badge((string) ($row['status'] ?? 'new')) ?></td>
                <td><a href="contacts.php?id=<?= (int) $row['id'] ?>" class="adm-btn adm-btn-sm adm-btn-ghost">Open</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="adm-card adm-glass">
    <div class="adm-toolbar">
      <h2 style="margin:0;">Recent activity</h2>
    </div>
    <?php if (!$activity): ?>
      <p class="adm-hint">CMS saves will appear here. Try editing the <a href="footer.php">footer</a> or <a href="home/index.php">home page</a>.</p>
    <?php else: ?>
      <ul class="adm-activity-feed">
        <?php foreach ($activity as $item): ?>
          <li>
            <span class="adm-activity-icon"><i class="fa-solid <?= e(admin_activity_icon((string) $item['action'])) ?>"></i></span>
            <div>
              <strong><?= e(ucfirst((string) $item['action'])) ?> <?= e((string) $item['entity']) ?></strong>
              <?php if (!empty($item['detail'])): ?><p><?= e((string) $item['detail']) ?></p><?php endif; ?>
              <small><?= e((string) $item['admin_name']) ?> · <?= e(substr((string) $item['created_at'], 0, 16)) ?></small>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="adm-analytics-note">
      <i class="fa-solid fa-chart-line"></i>
      <?php if ($gaId !== ''): ?>
        <span>Google Analytics connected (<code><?= e($gaId) ?></code>). View traffic in your GA dashboard.</span>
      <?php else: ?>
        <span>Add your Google Analytics ID in <a href="seo.php">SEO &amp; analytics</a> to track website visits.</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="adm-card adm-glass adm-animate-in adm-animate-in--4">
  <h2 style="margin-top:0;">Content at a glance</h2>
  <div class="adm-mini-stats">
    <span><strong><?= $stats['gallery'] ?></strong> gallery images</span>
    <span><strong><?= $stats['testimonials'] ?></strong> testimonials</span>
    <span><strong><?= $stats['projects_draft'] ?></strong> draft projects</span>
  </div>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

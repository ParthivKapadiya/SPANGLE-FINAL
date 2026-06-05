<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
admin_require_auth();

$stats = [
    'projects' => (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE is_active = 1')->fetchColumn(),
    'gallery' => (int) $pdo->query('SELECT COUNT(*) FROM gallery_items WHERE is_active = 1')->fetchColumn(),
    'inquiries' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
    'testimonials' => (int) $pdo->query('SELECT COUNT(*) FROM testimonials WHERE is_active = 1')->fetchColumn(),
    'journal' => (int) $pdo->query('SELECT COUNT(*) FROM journal_posts WHERE is_active = 1')->fetchColumn(),
    'team' => (int) $pdo->query('SELECT COUNT(*) FROM team_members WHERE is_active = 1')->fetchColumn(),
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

$statLinks = [
    'projects' => ['href' => 'projects.php', 'label' => 'Active projects'],
    'gallery' => ['href' => 'gallery.php', 'label' => 'Gallery images'],
    'inquiries' => ['href' => 'contacts.php', 'label' => 'All inquiries'],
    'testimonials' => ['href' => 'testimonials.php', 'label' => 'Testimonials'],
    'journal' => ['href' => 'journal.php', 'label' => 'Blog posts'],
    'team' => ['href' => 'team.php', 'label' => 'Team members'],
];

$pageTitle = 'Dashboard';
$pageDescription = 'Overview of content, leads, and consultation requests.';
$activeNav = 'dashboard';
$mainClass = 'adm-main--wide';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-quick-actions">
  <a href="contacts.php" class="adm-btn adm-btn-primary"><i class="fa-solid fa-inbox"></i> View inquiries</a>
  <a href="contacts.php?export=csv" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
  <a href="projects.php?action=new" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-plus"></i> New project</a>
  <a href="home.php" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-house"></i> Edit homepage</a>
  <a href="settings.php" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-gear"></i> Site settings</a>
</div>

<div class="adm-dash-highlight">
  <div class="adm-card">
    <strong><?= $leadStats['new'] ?></strong>
    <span>New inquiries</span>
    <div class="adm-actions" style="margin-top:0.75rem;">
      <a href="contacts.php?status=new" class="adm-btn adm-btn-sm adm-btn-ghost">Review</a>
    </div>
  </div>
  <div class="adm-card">
    <strong><?= $leadStats['consultation'] ?></strong>
    <span>Consultation modal leads</span>
    <p class="adm-hint" style="margin-top:0.5rem;">From “Book Free Consultation” on the website.</p>
  </div>
  <div class="adm-card">
    <strong><?= $leadStats['in_progress'] ?></strong>
    <span>In progress</span>
  </div>
</div>

<div class="adm-grid">
  <?php foreach ($statLinks as $key => $meta): ?>
    <a href="<?= e($meta['href']) ?>" class="adm-card adm-stat adm-stat-link">
      <strong><?= $stats[$key] ?></strong>
      <span><?= e($meta['label']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="adm-card">
  <div class="adm-toolbar">
    <h2 style="margin:0;">Recent inquiries</h2>
    <a href="contacts.php" class="adm-btn adm-btn-sm adm-btn-ghost">View all</a>
  </div>
  <?php if (!$recent): ?>
    <p class="adm-hint">No messages yet. Consultation and contact form submissions will appear here.</p>
  <?php else: ?>
    <div class="adm-table-wrap">
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
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $row): ?>
            <tr>
              <td><small><?= e(substr((string) $row['created_at'], 0, 10)) ?></small></td>
              <td><?= e($row['name']) ?></td>
              <td><?= e($row['phone'] ?: '—') ?></td>
              <td><?= e($row['subject'] ?: '—') ?></td>
              <td><?= e($row['budget_range'] ?? '—') ?></td>
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
<?php require __DIR__ . '/includes/layout-end.php'; ?>

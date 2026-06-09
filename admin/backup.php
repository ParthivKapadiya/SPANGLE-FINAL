<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/databaseExport.php';

admin_require_auth();

/** @var array<string, mixed> $configDb */
$configDb = (array) ($GLOBALS['configDb'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'download') {
        admin_log_activity($pdo, 'export', 'database', null, 'Database SQL backup downloaded');
        database_export_send_download($pdo, $configDb);
        exit;
    }

    if ($action === 'download_full') {
        if (!database_export_zip_available()) {
            admin_flash_set('error', 'ZIP extension is not available on this server. Use SQL backup and copy uploads/ manually.');
            redirect('backup.php');
        }

        admin_log_activity($pdo, 'export', 'site-backup', null, 'Full site backup downloaded (database + uploads)');
        if (!database_export_send_full_zip($pdo, $configDb, (array) $appConfig)) {
            admin_flash_set('error', 'Could not create backup ZIP. Try again or download SQL only.');
            redirect('backup.php');
        }
        exit;
    }
}

$stats = database_export_stats($pdo, $configDb);
$uploads = database_export_uploads_stats((array) $appConfig);
$tables = database_export_table_names($pdo);
$zipOk = database_export_zip_available();
$totalBytes = (int) $stats['size_bytes'] + (int) $uploads['bytes'];

$pageTitle = 'Site backup';
$pageDescription = 'Download database and images together for migration or safekeeping.';
$activeNav = 'backup';
require __DIR__ . '/includes/layout.php';
?>
<div class="adm-profile-layout">
  <div class="adm-card adm-glass">
    <h2>Full site backup (recommended)</h2>
    <p class="adm-hint">
      One <strong>.zip</strong> file with the complete database <em>and</em> all images from <code>uploads/</code>.
      Image paths in the database match the files inside the ZIP — restore both together when moving hosting.
    </p>

    <dl class="adm-profile-dl adm-backup-stats" style="margin:1rem 0;">
      <div>
        <dt>Database</dt>
        <dd>
          <span class="adm-backup-stat-line"><code class="adm-profile-code"><?= e($stats['database'] !== '' ? $stats['database'] : '—') ?></code></span>
          <span class="adm-backup-stat-line adm-hint"><?= (int) $stats['tables'] ?> tables · <?= number_format((int) $stats['rows']) ?> rows · <?= e(admin_format_bytes((int) $stats['size_bytes'])) ?></span>
        </dd>
      </div>
      <div>
        <dt>Images &amp; media</dt>
        <dd>
          <span class="adm-backup-stat-line"><?= number_format((int) $uploads['files']) ?> files · <?= e(admin_format_bytes((int) $uploads['bytes'])) ?></span>
          <span class="adm-backup-stat-line adm-hint">from <code>uploads/</code></span>
        </dd>
      </div>
      <div>
        <dt>Estimated total</dt>
        <dd>
          <span class="adm-backup-stat-line"><?= e(admin_format_bytes($totalBytes)) ?></span>
          <span class="adm-backup-stat-line adm-hint">database + files (ZIP may compress slightly)</span>
        </dd>
      </div>
    </dl>

    <?php if (!$zipOk): ?>
      <p class="adm-alert adm-alert-error">ZIP is not enabled on this server. Ask your host to enable the PHP Zip extension, or use SQL-only backup below.</p>
    <?php endif; ?>

    <div class="adm-backup-actions">
      <form method="post" class="adm-backup-action-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="download_full" />
        <button type="submit" class="adm-btn adm-btn-primary adm-btn-block"<?= $zipOk ? '' : ' disabled' ?>>
          <i class="fa-solid fa-file-zipper" aria-hidden="true"></i> Download full backup (ZIP)
        </button>
      </form>
      <form method="post" class="adm-backup-action-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="download" />
        <button type="submit" class="adm-btn adm-btn-ghost adm-btn-block">
          <i class="fa-solid fa-database" aria-hidden="true"></i> SQL only
        </button>
      </form>
    </div>

    <p class="adm-hint adm-profile-note" style="margin-top:1rem;">
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      Keep backups private — they include admin password hashes and all site content.
    </p>
  </div>

  <div class="adm-card adm-glass">
    <h2>What’s inside the ZIP</h2>
    <ul class="adm-hint" style="margin:0 0 1rem;padding-left:1.2rem;line-height:1.7;">
      <li><code>database/*.sql</code> — full MySQL dump</li>
      <li><code>uploads/</code> — all project, gallery, hero, and branding images</li>
      <li><code>README.txt</code> — restore instructions</li>
    </ul>

    <h2>Database tables</h2>
    <?php if ($tables === []): ?>
      <p class="adm-hint">No tables found.</p>
    <?php else: ?>
      <ul class="adm-backup-table-list">
        <?php foreach ($tables as $table): ?>
          <li><code><?= e($table) ?></code></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p class="adm-hint adm-profile-note" style="margin-top:1rem;">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      Images stay as files (not inside MySQL) — that keeps the site fast. The ZIP bundles database + files so you get everything in one download.
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

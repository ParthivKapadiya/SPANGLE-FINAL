<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';

$pdo = Database::connection($configDb);
cms_run_migrations($pdo);

$slug = preg_replace('/[^a-z0-9-]+/', '', strtolower(trim($_GET['slug'] ?? '')));
$stmt = $pdo->prepare('SELECT * FROM projects WHERE slug = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$slug]);
$project = $stmt->fetch();

if (!$project) {
    http_response_code(404);
    $publicPageTitle = 'Project not found | Archevo Design';
    require SPANGLE_ROOT . '/includes/public-header.php';
    echo '<main id="main" class="section" style="padding-top:6rem;"><div class="container narrow"><h1 class="section-title">Project not found</h1><p><a href="work.html">Back to work</a></p></div></main>';
    require SPANGLE_ROOT . '/includes/public-footer.php';
    exit;
}

$title = (string) $project['title'];
$body = (string) ($project['body_html'] ?? '');
$hero = public_media_path((string) ($project['hero_image'] ?? ''));
$location = (string) ($project['location'] ?? '');
$summary = (string) ($project['summary'] ?? '');

$publicPageTitle = $title . ' | Archevo Design';
$publicBodyClass = 'page-project-detail';

require SPANGLE_ROOT . '/includes/public-header.php';
?>
<main id="main" class="section section-tight fade-slide" style="padding-top: calc(var(--header-h) + 2rem);">
  <div class="container narrow prose-legal">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.html">Home</a><span aria-hidden="true"> /</span>
      <a href="work.html">Work</a><span aria-hidden="true"> /</span>
      <span aria-current="page"><?= htmlspecialchars($title) ?></span>
    </nav>
    <h1 class="section-title"><?= htmlspecialchars($title) ?></h1>
    <?php if ($location): ?>
      <p class="section-lead"><?= htmlspecialchars($location) ?></p>
    <?php endif; ?>
    <?php if ($hero): ?>
      <p style="margin:2rem 0;"><img src="<?= htmlspecialchars($hero) ?>" alt="" style="width:100%;border-radius:4px;" loading="lazy" /></p>
    <?php endif; ?>
    <?php if ($body): ?>
      <div class="article-body"><?= $body ?></div>
    <?php elseif ($summary): ?>
      <p class="section-lead"><?= htmlspecialchars($summary) ?></p>
    <?php endif; ?>
    <p style="margin-top:2rem;"><a href="work.html" class="text-link">← All projects</a></p>
  </div>
</main>
<?php require SPANGLE_ROOT . '/includes/public-footer.php'; ?>

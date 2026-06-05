<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';

$pdo = Database::connection($configDb);
cms_run_migrations($pdo);

$slug = preg_replace('/[^a-z0-9-]+/', '', strtolower(trim($_GET['slug'] ?? '')));
$stmt = $pdo->prepare('SELECT * FROM journal_posts WHERE slug = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $publicPageTitle = 'Article not found | SPANGLE Architecture & Interior Design Studio';
    require SPANGLE_ROOT . '/includes/public-header.php';
    echo '<main id="main" class="section" style="padding-top:6rem;"><div class="container narrow"><h1 class="section-title">Article not found</h1><p><a href="journal.html">Back to journal</a></p></div></main>';
    require SPANGLE_ROOT . '/includes/public-footer.php';
    exit;
}

$title = (string) $post['title'];
$body = (string) ($post['body_html'] ?? '');
$image = public_media_path((string) ($post['image_path'] ?? ''));
$category = trim((string) ($post['category'] ?? ''));
$readMins = $post['read_minutes'] !== null ? (int) $post['read_minutes'] : null;
$metaParts = [];
if ($category !== '') {
    $metaParts[] = htmlspecialchars($category);
}
if ($readMins) {
    $metaParts[] = $readMins . ' min read';
}

$seoTitle = trim((string) ($post['seo_title'] ?? ''));
$seoDesc = trim((string) ($post['seo_description'] ?? ''));
$publicPageTitle = $seoTitle !== '' ? $seoTitle : $title . ' | SPANGLE Architecture & Interior Design Studio Journal';
$publicMetaDescription = $seoDesc !== '' ? $seoDesc : (string) ($post['excerpt'] ?? '');
$publicBodyClass = 'page-journal-article';

require SPANGLE_ROOT . '/includes/public-header.php';
?>
<main id="main" class="section section-tight fade-slide" style="padding-top: calc(var(--header-h) + 2rem);">
  <div class="container narrow prose-legal">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.html">Home</a><span aria-hidden="true"> /</span>
      <a href="journal.html">Journal</a><span aria-hidden="true"> /</span>
      <span aria-current="page"><?= htmlspecialchars($title) ?></span>
    </nav>
    <?php if ($metaParts): ?>
      <p class="journal-meta"><?= implode(' · ', $metaParts) ?></p>
    <?php endif; ?>
    <h1 class="section-title"><?= htmlspecialchars($title) ?></h1>
    <?php if ($image): ?>
      <p style="margin:2rem 0;"><img src="<?= htmlspecialchars($image) ?>" alt="" style="width:100%;border-radius:4px;" loading="lazy" /></p>
    <?php endif; ?>
    <div class="article-body"><?= $body ?: '<p class="section-lead">' . htmlspecialchars((string) ($post['excerpt'] ?? '')) . '</p>' ?></div>
    <p style="margin-top:2rem;"><a href="journal.html" class="text-link">← Journal</a></p>
  </div>
</main>
<?php require SPANGLE_ROOT . '/includes/public-footer.php'; ?>

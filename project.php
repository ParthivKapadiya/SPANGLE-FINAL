<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
require_once SPANGLE_ROOT . '/includes/cms/ProjectRepository.php';

$pdo = Database::connection($configDb);
cms_run_migrations($pdo);

$slug = preg_replace('/[^a-z0-9-]+/', '', strtolower(trim($_GET['slug'] ?? '')));
$project = $slug !== '' ? ProjectRepository::findBySlug($pdo, $slug) : null;

if (!$project) {
    http_response_code(404);
    $publicPageTitle = 'Project not found | SPANGLE Architecture & Interior Design Studio';
    require SPANGLE_ROOT . '/includes/public-header.php';
    echo '<main id="main" class="section" style="padding-top:6rem;"><div class="container narrow"><h1 class="section-title">Project not found</h1><p><a href="work.html">Back to work</a></p></div></main>';
    require SPANGLE_ROOT . '/includes/public-footer.php';
    exit;
}

$publicPageTitle = ($project['seoTitle'] ?: $project['title']) . ' | SPANGLE Architecture & Interior Design Studio';
$publicMetaDescription = $project['seoDescription'] ?: ($project['summary'] ?? '');
$publicBodyClass = 'page-project-detail';

require SPANGLE_ROOT . '/includes/public-header.php';

$typeLabel = ucwords(str_replace('-', ' ', $project['projectType']));
?>
<main id="main">
  <section class="project-hero" aria-label="Project cover">
    <?php if ($project['heroImage']): ?>
      <img src="<?= e($project['heroImage']) ?>" alt="<?= e($project['title']) ?>" class="project-hero-img" />
    <?php endif; ?>
    <div class="project-hero-overlay">
      <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.html">Home</a><span aria-hidden="true"> /</span>
          <a href="work.html">Work</a><span aria-hidden="true"> /</span>
          <span aria-current="page"><?= e($project['title']) ?></span>
        </nav>
        <p class="page-kicker"><?= e($typeLabel) ?></p>
        <h1 class="project-hero-title"><?= e($project['title']) ?></h1>
        <?php if ($project['location']): ?>
          <p class="project-hero-meta"><?= e($project['location']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section section-tight">
    <div class="container project-detail-grid">
      <aside class="project-facts" aria-label="Project facts">
        <ul>
          <?php if ($project['location']): ?>
            <li><span>Location</span><strong><?= e($project['location']) ?></strong></li>
          <?php endif; ?>
          <?php if ($project['area']): ?>
            <li><span>Area</span><strong><?= e($project['area']) ?></strong></li>
          <?php endif; ?>
          <?php if ($project['year']): ?>
            <li><span>Completed</span><strong><?= e((string) $project['year']) ?></strong></li>
          <?php endif; ?>
          <li><span>Category</span><strong><?= e($typeLabel) ?></strong></li>
        </ul>
      </aside>
      <div class="project-body">
        <?php if ($project['summary']): ?>
          <p class="section-lead"><?= e($project['summary']) ?></p>
        <?php endif; ?>
        <?php if ($project['bodyHtml']): ?>
          <div class="article-body"><?= $project['bodyHtml'] ?></div>
        <?php endif; ?>
        <?php if ($project['servicesProvided']): ?>
          <h2>Services provided</h2>
          <p><?= nl2br(e($project['servicesProvided'])) ?></p>
        <?php endif; ?>
        <?php if ($project['clientTestimonial']): ?>
          <blockquote class="project-quote">
            <p><?= e($project['clientTestimonial']) ?></p>
          </blockquote>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (count($project['gallery']) > 1): ?>
    <section class="section section-tight project-gallery-section" aria-label="Project gallery">
      <div class="container">
        <div class="project-gallery-slider" id="project-gallery">
          <?php foreach ($project['gallery'] as $i => $img): ?>
            <figure class="project-gallery-slide<?= $i === 0 ? ' is-active' : '' ?>">
              <img src="<?= e($img['src']) ?>" alt="<?= e($img['caption'] ?: $project['title']) ?>" loading="lazy" />
              <?php if ($img['caption']): ?>
                <figcaption><?= e($img['caption']) ?></figcaption>
              <?php endif; ?>
            </figure>
          <?php endforeach; ?>
        </div>
        <div class="project-gallery-nav">
          <button type="button" class="btn btn-ghost" id="gallery-prev" aria-label="Previous image">←</button>
          <button type="button" class="btn btn-ghost" id="gallery-next" aria-label="Next image">→</button>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($project['related']): ?>
    <section class="section fade-slide" aria-labelledby="related-title">
      <div class="container">
        <h2 id="related-title" class="section-title">Related projects</h2>
        <div class="work-archive project-related-grid">
          <?php foreach ($project['related'] as $rel): ?>
            <a href="<?= e($rel['linkUrl']) ?>" class="work-card">
              <img src="<?= e($rel['heroImage']) ?>" alt="<?= e($rel['title']) ?>" loading="lazy" />
              <div class="work-card-body">
                <span><?= e(ucwords(str_replace('-', ' ', $rel['projectType']))) ?></span>
                <h3><?= e($rel['title']) ?></h3>
                <p><?= e($rel['location']) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>
<script src="js/project-detail.js" defer></script>
<?php require SPANGLE_ROOT . '/includes/public-footer.php'; ?>

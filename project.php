<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
require_once SPANGLE_ROOT . '/includes/cms/ProjectRepository.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

$pdo = Database::connection($configDb);
cms_run_migrations($pdo);

$slug = preg_replace('/[^a-z0-9-]+/', '', strtolower(trim($_GET['slug'] ?? '')));
$project = $slug !== '' ? ProjectRepository::findBySlug($pdo, $slug) : null;

if (!$project) {
    http_response_code(404);
    $publicPageTitle = 'Project not found | Archevo Design';
    require SPANGLE_ROOT . '/includes/public-header.php';
    echo '<main id="main" class="section" style="padding-top:6rem;"><div class="container narrow"><h1 class="section-title">Project not found</h1><p><a href="work.html">Back to work</a></p></div></main>';
    require SPANGLE_ROOT . '/includes/public-footer.php';
    exit;
}

$publicPageTitle = ($project['seoTitle'] ?: $project['title']) . ' | Archevo Design';
$publicMetaDescription = $project['seoDescription'] ?: ($project['summary'] ?? '');
$publicBodyClass = 'page-project-detail';

require SPANGLE_ROOT . '/includes/public-header.php';

$typeLabel = ucwords(str_replace('-', ' ', $project['projectType']));
$bodyHtml = (string) ($project['bodyHtml'] ?? '');
$bodyIsGalleryOnly = cms_body_is_gallery_markup($bodyHtml);
$paragraphs = cms_plain_paragraph_slots($bodyHtml, 6);
$challenge = $paragraphs[0] ?? '';
$approach = $paragraphs[1] ?? '';
$result = $paragraphs[2] ?? '';
$materials = $paragraphs[3] ?? '';
$execution = $paragraphs[4] ?? '';
$extra = $paragraphs[5] ?? '';
$scope = trim((string) ($project['servicesProvided'] ?? '')) ?: $typeLabel;
?>
<link rel="stylesheet" href="css/work-projects.css?v=10" />
<link rel="stylesheet" href="css/project-premium.css?v=2" />
<main id="main">
  <section class="prj-hero project-hero" aria-label="Project cover">
    <?php if ($project['heroImage']): ?>
      <img src="<?= e($project['heroImage']) ?>" alt="<?= e($project['title']) ?>" class="prj-hero__img project-hero-img" />
    <?php endif; ?>
    <div class="prj-hero__grid" aria-hidden="true"></div>
    <div class="prj-hero__overlay project-hero-overlay">
      <div class="prj-hero__inner container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="index.html">Home</a><span aria-hidden="true"> /</span>
          <a href="work.html">Work</a><span aria-hidden="true"> /</span>
          <span aria-current="page"><?= e($project['title']) ?></span>
        </nav>
        <p class="prj-hero__kicker page-kicker"><?= e($typeLabel) ?></p>
        <h1 class="prj-hero__title project-hero-title"><?= e($project['title']) ?></h1>
        <?php if ($project['location']): ?>
          <p class="prj-hero__meta project-hero-meta"><?= e($project['location']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="prj-section prj-section--white section section-tight">
    <div class="prj-container prj-layout project-detail-grid">
      <aside class="prj-facts project-facts prj-reveal" aria-label="Project facts">
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
          <li><span>Scope</span><strong><?= e($scope) ?></strong></li>
          <li><span>Category</span><strong><?= e($typeLabel) ?></strong></li>
        </ul>
      </aside>
      <div class="prj-body project-body prj-reveal">
        <?php if ($project['summary']): ?>
          <h2>Brief</h2>
          <p class="section-lead"><?= e($project['summary']) ?></p>
        <?php endif; ?>

        <?php if ($challenge): ?>
          <div class="prj-story-block">
            <h2>Challenge</h2>
            <p><?= nl2br(e($challenge)) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($approach): ?>
          <div class="prj-story-block">
            <h2>Design process</h2>
            <p><?= nl2br(e($approach)) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($materials || $project['servicesProvided']): ?>
          <div class="prj-story-block">
            <h2>Materials &amp; scope</h2>
            <?php if ($materials): ?>
              <p><?= nl2br(e($materials)) ?></p>
            <?php endif; ?>
            <?php if ($project['servicesProvided']): ?>
              <p><?= nl2br(e($project['servicesProvided'])) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($execution): ?>
          <div class="prj-story-block">
            <h2>Execution</h2>
            <p><?= nl2br(e($execution)) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($result): ?>
          <div class="prj-story-block">
            <h2>Results</h2>
            <p><?= nl2br(e($result)) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($extra): ?>
          <div class="prj-story-block">
            <p><?= nl2br(e($extra)) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($bodyHtml && !$challenge && !$approach && !$bodyIsGalleryOnly): ?>
          <div class="article-body"><?= $bodyHtml ?></div>
        <?php endif; ?>

        <?php if ($project['clientTestimonial']): ?>
          <blockquote class="prj-quote project-quote">
            <p>&ldquo;<?= e($project['clientTestimonial']) ?>&rdquo;</p>
          </blockquote>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (count($project['gallery']) > 0): ?>
    <section class="prj-section prj-gallery-section project-gallery-section" aria-label="Project gallery">
      <div class="prj-container">
        <h2 class="section-title" style="color:#f5f2ec;margin-bottom:2rem;">Gallery</h2>
        <div class="prj-gallery-grid" id="project-gallery">
          <?php foreach ($project['gallery'] as $img): ?>
            <figure class="project-gallery-slide is-active">
              <img src="<?= e($img['src']) ?>" alt="<?= e($img['caption'] ?: $project['title']) ?>" loading="lazy" decoding="async" />
              <?php if ($img['caption']): ?>
                <figcaption><?= e($img['caption']) ?></figcaption>
              <?php endif; ?>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($project['related']): ?>
    <section class="prj-section prj-section--white section fade-slide" aria-labelledby="related-title">
      <div class="prj-container">
        <h2 id="related-title" class="section-title prj-reveal">Related projects</h2>
        <div class="project-related-grid prj-reveal">
          <?php foreach ($project['related'] as $rel): ?>
            <a href="<?= e($rel['linkUrl']) ?>" class="work-card wrk-card">
              <div class="wrk-card__media">
                <img src="<?= e($rel['heroImage']) ?>" alt="<?= e($rel['title']) ?>" loading="lazy" decoding="async" />
                <span class="wrk-card__badge"><?= e(ucwords(str_replace('-', ' ', $rel['projectType']))) ?></span>
              </div>
              <div class="work-card-body wrk-card__body">
                <h3><?= e($rel['title']) ?></h3>
                <ul class="wrk-card__meta"><li><?= e($rel['location']) ?></li></ul>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="prj-cta prj-reveal" aria-labelledby="prj-cta-title">
    <div class="prj-container">
      <h2 id="prj-cta-title">Discuss a similar project</h2>
      <p>Share your site, scope, and timeline — we respond within two business days.</p>
      <a href="contact.html" class="btn btn-primary">Start your project</a>
      <a href="work.html" class="btn btn-ghost">Back to portfolio</a>
    </div>
  </section>
</main>
<script src="js/project-detail.js" defer></script>
<?php require SPANGLE_ROOT . '/includes/public-footer.php'; ?>

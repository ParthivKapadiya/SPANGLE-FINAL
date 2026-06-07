<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsHomeSections.php';

admin_require_auth();

$pageTitle = 'Home page';
$pageDescription = 'Edit all 14 sections on your homepage — hero, about, services, projects, and more.';
$activeNav = 'home';
require dirname(__DIR__) . '/includes/layout.php';
?>
<div class="adm-home-hub">
  <?php foreach (cms_home_section_definitions() as $section): ?>
    <a href="<?= e($section['href']) ?>" class="adm-home-hub-card">
      <span class="adm-home-hub-num"><?= (int) $section['num'] ?></span>
      <span class="adm-home-hub-icon"><i class="fa-solid <?= e($section['icon']) ?>" aria-hidden="true"></i></span>
      <strong><?= e($section['label']) ?></strong>
      <span><?= e($section['description']) ?></span>
    </a>
  <?php endforeach; ?>
</div>
<p class="adm-hint" style="margin-top:1rem;">
  <a href="<?= e(admin_href('../index.html')) ?>" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview home page</a>
</p>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

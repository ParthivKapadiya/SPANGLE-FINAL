<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $pageDescription */
/** @var string $activeNav */

$pageTitle = $pageTitle ?? 'Dashboard';
$pageDescription = $pageDescription ?? '';
$activeNav = $activeNav ?? '';
$mainClass = $mainClass ?? '';

$brand = admin_brand();
$newInquiries = admin_new_inquiry_count($pdo);

$navSections = [
    [
        'label' => 'Overview',
        'items' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'href' => 'index.php'],
        ],
    ],
    [
        'label' => 'Global',
        'items' => [
            ['id' => 'settings', 'label' => 'Global settings', 'icon' => 'fa-gear', 'href' => 'settings.php'],
            ['id' => 'seo', 'label' => 'SEO & analytics', 'icon' => 'fa-magnifying-glass', 'href' => 'seo.php'],
            ['id' => 'footer', 'label' => 'Footer', 'icon' => 'fa-shoe-prints', 'href' => 'footer.php'],
            ['id' => 'legal', 'label' => 'Legal pages', 'icon' => 'fa-scale-balanced', 'href' => 'legal.php'],
            ['id' => 'media', 'label' => 'Media library', 'icon' => 'fa-images', 'href' => 'media.php'],
        ],
    ],
    [
        'label' => 'Homepage',
        'items' => [
            ['id' => 'home', 'label' => 'Homepage', 'icon' => 'fa-house', 'href' => 'home.php'],
            ['id' => 'gallery', 'label' => 'Home gallery', 'icon' => 'fa-camera', 'href' => 'gallery.php'],
            ['id' => 'testimonials', 'label' => 'Testimonials', 'icon' => 'fa-quote-left', 'href' => 'testimonials.php'],
        ],
    ],
    [
        'label' => 'Pages',
        'items' => [
            ['id' => 'studio', 'label' => 'Studio', 'icon' => 'fa-building', 'href' => 'studio.php'],
            ['id' => 'services-page', 'label' => 'Services page', 'icon' => 'fa-briefcase', 'href' => 'services-page.php'],
            ['id' => 'services', 'label' => 'Service blocks', 'icon' => 'fa-list-check', 'href' => 'services.php'],
            ['id' => 'work-page', 'label' => 'Work page', 'icon' => 'fa-layer-group', 'href' => 'work-page.php'],
            ['id' => 'projects', 'label' => 'Projects', 'icon' => 'fa-folder-open', 'href' => 'projects.php'],
            ['id' => 'process', 'label' => 'Process', 'icon' => 'fa-diagram-project', 'href' => 'process.php'],
            ['id' => 'contact-page', 'label' => 'Contact', 'icon' => 'fa-envelope', 'href' => 'contact-page.php'],
        ],
    ],
    [
        'label' => 'Leads',
        'items' => [
            ['id' => 'contacts', 'label' => 'Inquiries', 'icon' => 'fa-inbox', 'href' => 'contacts.php', 'badge' => $newInquiries],
        ],
    ],
    [
        'label' => 'Account',
        'items' => [
            ['id' => 'password', 'label' => 'Password', 'icon' => 'fa-key', 'href' => 'change-password.php'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en" data-adm-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title><?= e($pageTitle) ?> · <?= e($brand['short']) ?> Studio</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="assets/admin.css?v=6" />
</head>
<body class="adm-app" id="adm-app">
  <div class="adm-sidebar-backdrop" id="adm-sidebar-backdrop" hidden aria-hidden="true"></div>
  <aside class="adm-sidebar" id="adm-sidebar">
    <div class="adm-brand">
      <strong><?= e($brand['short']) ?></strong>
      <span>Architecture studio CMS</span>
    </div>
    <div class="adm-nav-search">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" id="adm-nav-search" placeholder="Search modules…" autocomplete="off" aria-label="Search admin modules" />
    </div>
    <nav class="adm-nav" id="adm-nav" aria-label="Admin navigation">
      <?php foreach ($navSections as $section): ?>
        <p class="adm-nav-section"><?= e($section['label']) ?></p>
        <?php foreach ($section['items'] as $item): ?>
          <a href="<?= e($item['href']) ?>" class="adm-nav-link<?= $activeNav === $item['id'] ? ' is-active' : '' ?>" data-nav-label="<?= e(strtolower($item['label'] . ' ' . $section['label'])) ?>">
            <i class="fa-solid <?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
            <?php if (!empty($item['badge'])): ?>
              <span class="adm-nav-badge" aria-label="<?= (int) $item['badge'] ?> new inquiries"><?= (int) $item['badge'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="adm-sidebar-foot">
      <a href="../index.html" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View website</a>
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign out</a>
    </div>
  </aside>
  <div class="adm-shell">
    <header class="adm-topbar">
      <button type="button" class="adm-icon-btn adm-menu-toggle" id="adm-menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="adm-sidebar"><i class="fa-solid fa-bars"></i></button>
      <div>
        <h1><?= e($pageTitle) ?></h1>
        <?php if ($pageDescription): ?><p><?= e($pageDescription) ?></p><?php endif; ?>
      </div>
      <div class="adm-topbar-actions">
        <?php if ($newInquiries > 0): ?>
          <a href="contacts.php?status=new" class="adm-topbar-pill adm-topbar-pill--alert">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            <?= (int) $newInquiries ?> new
          </a>
        <?php endif; ?>
        <button type="button" class="adm-icon-btn" id="adm-theme-toggle" title="Toggle light/dark mode" aria-label="Toggle theme"><i class="fa-solid fa-sun"></i></button>
        <span class="adm-user"><?= e(Auth::adminName()) ?></span>
      </div>
    </header>
    <?php if ($flash = flash_get()): ?>
      <div class="adm-alert adm-alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <main class="adm-main<?= $mainClass !== '' ? ' ' . e($mainClass) : '' ?>">

<?php

declare(strict_types=1);

/** @var string $publicPageTitle */
/** @var string $publicBodyClass */
$publicPageTitle = $publicPageTitle ?? 'Archevo Design';
$publicBodyClass = trim('page-sub ' . ($publicBodyClass ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($publicPageTitle) ?></title>
  <meta name="description" content="" />
  <link rel="icon" href="archevo-logo-light.png" type="image/png" />
  <link rel="stylesheet" href="fonts/fonts.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="style.css" />
</head>
<body class="<?= htmlspecialchars($publicBodyClass) ?>">
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header is-solid" id="site-header" data-header role="banner">
    <div class="header-inner">
      <a href="index.html" class="brand brand--full" aria-label="Archevo Design home">
        <span class="brand-full">
          <img src="archevo-logo-light.png" alt="" width="168" height="100" class="brand-logo-full brand-logo-full--light" decoding="async" />
          <img src="archevo-logo-dark.png" alt="" width="148" height="88" class="brand-logo-full brand-logo-full--dark" decoding="async" />
        </span>
        <span class="brand-text">
          <span class="brand-name">ARCHEVO DESIGN</span>
          <span class="brand-line">Architecture &amp; Interiors</span>
        </span>
      </a>
      <nav class="nav-desktop" aria-label="Main navigation">
        <ul>
          <li><a href="studio.html">Studio</a></li>
          <li><a href="services.html">Services</a></li>
          <li><a href="work.html">Work</a></li>
          <li><a href="process.html">Process</a></li>
          <li><a href="journal.html">Journal</a></li>
          <li><a class="nav-cta" href="contact.html">Enquire</a></li>
        </ul>
      </nav>
      <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu"><span class="nav-toggle-bar" aria-hidden="true"></span><span class="nav-toggle-bar" aria-hidden="true"></span></button>
    </div>
    <div class="nav-drawer" id="nav-drawer" aria-hidden="true">
      <div class="nav-drawer-panel" role="dialog" aria-modal="true" aria-label="Menu">
        <button type="button" class="nav-drawer-close" id="nav-close" aria-label="Close menu">&times;</button>
        <ul class="nav-drawer-links">
          <li><a href="studio.html">Studio</a></li>
          <li><a href="services.html">Services</a></li>
          <li><a href="work.html">Work</a></li>
          <li><a href="process.html">Process</a></li>
          <li><a href="journal.html">Journal</a></li>
          <li><a href="contact.html">Enquire</a></li>
        </ul>
      </div>
    </div>
  </header>

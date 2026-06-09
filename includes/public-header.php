<?php

declare(strict_types=1);

/** @var string $publicPageTitle */
/** @var string $publicBodyClass */
/** @var string $publicMetaDescription */
$publicPageTitle = $publicPageTitle ?? 'Archevo Design';
$publicBodyClass = trim('page-sub ' . ($publicBodyClass ?? ''));
$publicMetaDescription = $publicMetaDescription ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($publicPageTitle) ?></title>
  <?php if ($publicMetaDescription !== ''): ?>
  <meta name="description" content="<?= htmlspecialchars($publicMetaDescription) ?>" />
  <?php endif; ?>
  <link rel="icon" href="uploads/branding/archevo-icon.png" type="image/png" />
  <link rel="apple-touch-icon" href="uploads/branding/archevo-icon.png" />
  <link rel="stylesheet" href="fonts/fonts.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="style.css?v=20" />
  <link rel="stylesheet" href="css/header-lux.css?v=30" />
</head>
<body class="<?= htmlspecialchars($publicBodyClass) ?>">
  <a class="skip-link" href="#main">Skip to content</a>
<?php require SPANGLE_ROOT . '/includes/partials/site-header-inner.html'; ?>

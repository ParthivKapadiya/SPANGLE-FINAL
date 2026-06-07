<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$pdo = Database::connection($configDb);
$base = rtrim((string) (setting_get($pdo, 'public_base', 'https://www.archevoinfra.com') ?: 'https://www.archevoinfra.com'), '/');
if ($base === '') {
    $base = 'https://www.archevoinfra.com';
}

$static = [
    ['loc' => '/', 'priority' => '1.0', 'freq' => 'weekly'],
    ['loc' => '/studio.html', 'priority' => '0.85', 'freq' => 'monthly'],
    ['loc' => '/services.html', 'priority' => '0.85', 'freq' => 'monthly'],
    ['loc' => '/work.html', 'priority' => '0.9', 'freq' => 'weekly'],
    ['loc' => '/process.html', 'priority' => '0.75', 'freq' => 'monthly'],
    ['loc' => '/contact.html', 'priority' => '0.9', 'freq' => 'monthly'],
    ['loc' => '/privacy.html', 'priority' => '0.3', 'freq' => 'yearly'],
    ['loc' => '/terms.html', 'priority' => '0.3', 'freq' => 'yearly'],
];

$projects = [];
try {
    $stmt = $pdo->query('SELECT slug FROM projects WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $projects[] = [
            'loc' => '/project.php?slug=' . rawurlencode((string) $row['slug']),
            'priority' => '0.8',
            'freq' => 'monthly',
        ];
    }
} catch (Throwable $e) {
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach (array_merge($static, $projects) as $url) {
    echo '  <url><loc>' . htmlspecialchars($base . $url['loc'], ENT_XML1) . '</loc>';
    if (!empty($url['lastmod'])) {
        echo '<lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1) . '</lastmod>';
    }
    echo '<changefreq>' . $url['freq'] . '</changefreq><priority>' . $url['priority'] . '</priority></url>' . "\n";
}

echo '</urlset>';

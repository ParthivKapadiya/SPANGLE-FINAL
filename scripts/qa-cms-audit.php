<?php

declare(strict_types=1);

/**
 * CMS QA audit — run: php scripts/qa-cms-audit.php
 */

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

/** @var array<string, mixed> $configDb */
$configDb = (array) ($GLOBALS['configDb'] ?? []);
$pdo = Database::connection($configDb);
require_once $root . '/includes/cmsMigrate.php';
cms_run_migrations($pdo);

$results = [];
$pass = 0;
$fail = 0;

function qa(string $module, string $test, bool $ok, string $detail = ''): void
{
    global $results, $pass, $fail;
    $results[] = [
        'module' => $module,
        'test' => $test,
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => $detail,
    ];
    if ($ok) {
        $pass++;
    } else {
        $fail++;
    }
}

// Database tables
$requiredTables = [
    'users' => 'admins',
    'settings' => 'site_settings',
    'projects' => 'projects',
    'project_images' => 'project_images',
    'services' => 'services',
    'journal_posts' => 'journal_posts',
    'inquiries' => 'contact_messages',
    'testimonials' => 'testimonials',
    'media_library' => 'media_assets',
    'process_steps' => 'process_steps',
    'admin_activity' => 'admin_activity',
];
foreach ($requiredTables as $label => $table) {
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        qa('Database', "Table: $label ($table)", true);
    } catch (Throwable $e) {
        qa('Database', "Table: $label ($table)", false, $e->getMessage());
    }
}

// Admin modules
$adminModules = [
    'Dashboard' => 'admin/index.php',
    'Global settings' => 'admin/settings.php',
    'SEO & analytics' => 'admin/seo.php',
    'Footer' => 'admin/footer.php',
    'Legal pages' => 'admin/legal.php',
    'Homepage' => 'admin/home.php',
    'Studio' => 'admin/studio.php',
    'Services page' => 'admin/services-page.php',
    'Service blocks' => 'admin/services.php',
    'Work page' => 'admin/work-page.php',
    'Projects' => 'admin/projects.php',
    'Process' => 'admin/process.php',
    'Journal' => 'admin/journal.php',
    'Contact' => 'admin/contact-page.php',
    'Team' => 'admin/team.php',
    'Inquiries' => 'admin/contacts.php',
    'Media library' => 'admin/media.php',
    'Testimonials' => 'admin/testimonials.php',
    'Gallery' => 'admin/gallery.php',
];
foreach ($adminModules as $name => $path) {
    qa('Admin modules', $name, is_file($root . '/' . $path));
}

// CSRF on admin forms (sample)
$csrfFiles = ['admin/footer.php', 'admin/legal.php', 'admin/settings.php', 'admin/projects.php'];
foreach ($csrfFiles as $f) {
    $content = (string) file_get_contents($root . '/' . $f);
    qa('Security', 'CSRF in ' . basename($f), str_contains($content, 'csrf_field()') || str_contains($content, 'csrf_verify()'));
}

// Auth protection
qa('Security', 'Auth::requireAdmin in bootstrap', str_contains(
    (string) file_get_contents($root . '/admin/includes/bootstrap.php'),
    'admin_require_auth'
));

// Content API
$apiPath = $root . '/api/site-data.js.php';
qa('Content binding', 'Site data API exists', is_file($apiPath));
if (is_file($apiPath)) {
    ob_start();
    try {
        include $apiPath;
        $out = ob_get_clean();
        qa('Content binding', 'API returns JS', str_contains($out, 'projects') && str_contains($out, 'branding'));
    } catch (Throwable $e) {
        ob_end_clean();
        qa('Content binding', 'API returns JS', false, $e->getMessage());
    }
}

// SiteContent class
qa('Content binding', 'SiteContent.php in includes/', is_file($root . '/includes/SiteContent.php'));

// Frontend pages
$pages = ['index.html', 'studio.html', 'services.html', 'work.html', 'process.html', 'journal.html', 'contact.html', 'privacy.html', 'terms.html'];
foreach ($pages as $page) {
    $html = (string) file_get_contents($root . '/' . $page);
    qa('Frontend', "$page exists & loads CMS bridge", is_file($root . '/' . $page) && (
        str_contains($html, 'content-bridge.js') || str_contains($html, 'page-content.js')
    ));
}

// Legal CMS keys seeded
$legal = settings_get_many($pdo, ['legal_privacy_html', 'legal_terms_html']);
qa('Legal', 'Privacy content in DB', trim((string) ($legal['legal_privacy_html'] ?? '')) !== '');
qa('Legal', 'Terms content in DB', trim((string) ($legal['legal_terms_html'] ?? '')) !== '');

// Projects CRUD columns
$projectCols = ['slug', 'body_html', 'seo_title', 'is_featured', 'project_type'];
foreach ($projectCols as $col) {
    try {
        $has = $pdo->query("SHOW COLUMNS FROM projects LIKE '$col'")->fetch();
        qa('Work / Portfolio', "Project column: $col", (bool) $has);
    } catch (Throwable $e) {
        qa('Work / Portfolio', "Project column: $col", false);
    }
}

// Journal
try {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM journal_posts')->fetchColumn();
    qa('Journal', 'Journal posts accessible', true, "$count posts");
} catch (Throwable $e) {
    qa('Journal', 'Journal posts accessible', false);
}

// Media upload dir
/** @var array<string, mixed> $appConfig */
$appConfig = app_config();
$uploadRoot = rtrim((string) ($appConfig['upload_root'] ?? $root . '/uploads'), '/');
qa('Media', 'Uploads directory writable', is_dir($uploadRoot) && is_writable($uploadRoot));

// Password hashing
qa('Security', 'password_hash used in Auth', str_contains(
    (string) file_get_contents($root . '/includes/Auth.php'),
    'password_hash'
));

// Responsive admin CSS
$css = (string) file_get_contents($root . '/admin/assets/admin.css');
qa('Admin UI', 'Dark/light theme CSS', str_contains($css, 'data-adm-theme'));
qa('Admin UI', 'Glassmorphism styles', str_contains($css, 'adm-glass'));
qa('Admin UI', 'Mobile breakpoint', str_contains($css, 'max-width: 900px'));
qa('Admin UI', 'Nav search styles', str_contains($css, 'adm-nav-search'));

// Dashboard features
$dash = (string) file_get_contents($root . '/admin/index.php');
qa('Dashboard', 'Storage usage', str_contains($dash, 'admin_uploads_usage'));
qa('Dashboard', 'Recent activity', str_contains($dash, 'admin_recent_activity'));
qa('Dashboard', 'Quick actions', str_contains($dash, 'Add project'));

// Footer manager
$footer = (string) file_get_contents($root . '/admin/footer.php');
qa('Footer', 'Footer blurb editable', str_contains($footer, 'footer_blurb_1'));
qa('Footer', 'Nav links editable', str_contains($footer, 'cms_nav_item_definitions'));

// Analytics binding
$bridge = (string) file_get_contents($root . '/js/content-bridge.js');
qa('SEO', 'GA injection in content-bridge', str_contains($bridge, 'applyAnalytics'));

echo "\n=== ARCHEVO CMS QA AUDIT ===\n";
echo 'Date: ' . date('Y-m-d H:i:s') . "\n";
echo "Total: " . count($results) . " | PASS: $pass | FAIL: $fail\n\n";

$byModule = [];
foreach ($results as $r) {
    $byModule[$r['module']][] = $r;
}
foreach ($byModule as $module => $tests) {
    echo "## $module\n";
    foreach ($tests as $t) {
        $icon = $t['status'] === 'PASS' ? '✓' : '✗';
        $line = "  $icon [{$t['status']}] {$t['test']}";
        if ($t['detail'] !== '') {
            $line .= " — {$t['detail']}";
        }
        echo $line . "\n";
    }
    echo "\n";
}

echo $fail === 0 ? "RESULT: ALL TESTS PASSED\n" : "RESULT: $fail TEST(S) FAILED — review above\n";
exit($fail > 0 ? 1 : 0);

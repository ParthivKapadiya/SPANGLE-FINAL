<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$base = 'http://localhost/SPANGLE_FINAL/admin';

function fetch(string $url, ?string $cookieFile = null, bool $post = false, array $postFields = []): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => true,
    ];
    if ($cookieFile) {
        $opts[CURLOPT_COOKIEJAR] = $cookieFile;
        $opts[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    if ($post) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $postFields;
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $location = '';
    if (preg_match('/^Location:\s*(.+)$/mi', is_string($raw) ? substr($raw, 0, $headerSize) : '', $m)) {
        $location = trim($m[1]);
    }

    return [$code, is_string($raw) ? substr($raw, $headerSize) : '', $location];
}

$navModules = [
    'index.php', 'header.php', 'settings.php', 'seo.php', 'footer.php', 'legal.php', 'media.php',
    'home/index.php', 'studio/index.php', 'services/index.php', 'services.php', 'work-page.php',
    'projects.php', 'process.php', 'contact/index.php', 'contacts.php', 'change-password.php',
    'login.php', 'gallery.php', 'testimonials.php', 'team.php',
];

$homeSections = [
    'home/hero.php', 'home/about.php', 'home/why-archevo.php', 'home/services.php',
    'home/projects.php', 'home/gallery.php', 'home/process.php', 'home/testimonials.php',
    'home/impact.php', 'home/cta.php', 'home/contact.php', 'home/trust-strip.php',
];

$studioSections = glob($root . '/admin/studio/*.php') ?: [];
$servicesSections = glob($root . '/admin/services/*.php') ?: [];
$contactSections = glob($root . '/admin/contact/*.php') ?: [];

echo "=== ADMIN FILE CHECK ===\n";
$missing = [];
foreach ($navModules as $m) {
    if (!is_file("$root/admin/$m")) {
        $missing[] = $m;
    }
}
echo empty($missing) ? "All nav modules exist\n" : "Missing: " . implode(', ', $missing) . "\n";

$orphanContact = [];
foreach ($contactSections as $f) {
    $name = basename($f);
    if (!in_array($name, ['hero.php', 'visit.php', 'index.php'], true)) {
        $orphanContact[] = $name;
    }
}
if ($orphanContact) {
    echo 'Orphan contact admin files (page removed): ' . implode(', ', $orphanContact) . "\n";
}

echo "\n=== AUTH GATE (no login) ===\n";
[$code, , $loc] = fetch("$base/index.php");
echo "Dashboard without login: $code -> $loc\n";
[$loginCode, $loginHtml] = fetch("$base/login.php");
echo "Login page: $loginCode " . (str_contains($loginHtml, 'Sign in') ? '(OK)' : '(BAD)') . "\n";

echo "\n=== LOGIN TEST ===\n";
$cookie = sys_get_temp_dir() . '/spangle_admin_audit_cookies.txt';
@unlink($cookie);
[$lc, $lh] = fetch("$base/login.php", $cookie);
preg_match('/name="_csrf" value="([^"]+)"/', $lh, $csrf) || preg_match('/name="csrf_token" value="([^"]+)"/', $lh, $csrf);
if (!isset($csrf[1]) && preg_match('/name="_csrf"[^>]*value="([^"]+)"/', $lh, $csrf)) {
    /* ok */
}
// csrf field name from helpers
preg_match('/name="([^"]+)"[^>]*value="([^"]+)"[^>]*>/', $lh, $tokenMatch);
$csrfName = '_csrf';
$csrfVal = '';
if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $lh, $m)) {
    $csrfVal = $m[1];
} elseif (preg_match('/value="([^"]+)"\s+name="_csrf"/', $lh, $m)) {
    $csrfVal = $m[1];
}

$credentials = [
    ['admin', 'admin123'],
    ['admin@spangle.local', 'admin123'],
];
$loggedIn = false;
foreach ($credentials as [$user, $pass]) {
  $fields = ['email' => $user, 'password' => $pass, '_csrf' => $csrfVal];
  [$pc, , $ploc] = fetch("$base/login.php", $cookie, true, $fields);
  if ($pc === 302 && str_contains($ploc, 'index.php')) {
    echo "Login OK as $user\n";
    $loggedIn = true;
    break;
  }
}
if (!$loggedIn) {
    echo "Login failed with default seed credentials (password may have been changed)\n";
}

if ($loggedIn) {
    echo "\n=== AUTHENTICATED PAGE LOADS ===\n";
    $fail = [];
    $allPages = array_merge($navModules, $homeSections);
    foreach (array_unique($allPages) as $page) {
        if ($page === 'login.php') {
            continue;
        }
        [$c, $body, $loc] = fetch("$base/$page", $cookie);
        $ok = ($c === 200 && (str_contains($body, 'adm-main') || str_contains($body, 'adm-settings-grid') || str_contains($body, 'adm-home-hub') || str_contains($body, 'adm-table')));
        if (!$ok && $c === 302) {
            $ok = false;
        }
        if (!$ok) {
            $fail[] = "$page ($c" . ($loc ? " -> $loc" : '') . ")";
        }
    }
    echo empty($fail) ? "All tested admin pages load (200)\n" : "Failed pages:\n  " . implode("\n  ", $fail) . "\n";

    echo "\n=== CSRF ON FORMS ===\n";
    $csrfMissing = [];
    foreach (['settings.php', 'projects.php', 'footer.php', 'seo.php', 'contacts.php'] as $p) {
        [$c, $body] = fetch("$base/$p", $cookie);
        if ($c === 200 && !str_contains($body, 'csrf') && !str_contains($body, '_csrf')) {
            $csrfMissing[] = $p;
        }
    }
    echo empty($csrfMissing) ? "Sample forms have CSRF\n" : 'Missing CSRF: ' . implode(', ', $csrfMissing) . "\n";

    echo "\n=== INQUIRIES MODULE ===\n";
    [$c, $body] = fetch("$base/contacts.php", $cookie);
    echo "contacts.php: $c";
    if (preg_match('/(\d+)\s+total/i', $body, $m) || preg_match('/contact_messages/', $body)) {
        echo " (inbox loads)\n";
    } else {
        echo "\n";
    }
}

@unlink($cookie);

echo "\n=== DB COUNTS ===\n";
require_once $root . '/includes/bootstrap.php';
/** @var array<string,mixed> $configDb */
$configDb = (array) ($GLOBALS['configDb'] ?? []);
$pdo = Database::connection($configDb);
$tables = ['projects' => 'projects', 'services' => 'services', 'contact_messages' => 'inquiries', 'testimonials' => 'testimonials', 'gallery_items' => 'gallery', 'process_steps' => 'process steps', 'media_assets' => 'media'];
foreach ($tables as $t => $label) {
    try {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$label: $n\n";
    } catch (Throwable $e) {
        echo "$label: error\n";
    }
}

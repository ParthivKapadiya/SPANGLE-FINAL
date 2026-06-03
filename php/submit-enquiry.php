<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html', true, 302);
    exit;
}

$gotcha = trim((string) ($_POST['_gotcha'] ?? ''));
if ($gotcha !== '') {
    header('Location: ../contact.html?enquiry=spam', true, 302);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$projectType = trim((string) ($_POST['project_type'] ?? ''));
$formSource = trim((string) ($_POST['form_source'] ?? 'unknown'));
if ($formSource === '') {
    $formSource = 'unknown';
}

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../contact.html?enquiry=invalid', true, 302);
    exit;
}

if (strlen($name) > 200 || strlen($email) > 254 || strlen($message) > 20000 || strlen($projectType) > 500) {
    header('Location: ../contact.html?enquiry=invalid', true, 302);
    exit;
}

$entry = [
    'id' => bin2hex(random_bytes(8)),
    'created_at' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'message' => $message,
    'project_type' => $projectType,
    'form_source' => $formSource,
    'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
];

if (!spangle_append_enquiry($entry)) {
    $path = spangle_enquiries_path();
    $dir = dirname($path);
    $perm = (is_dir($dir) && !is_writable($dir)) || (is_file($path) && !is_writable($path));
    header('Location: ../contact.html?enquiry=' . ($perm ? 'perm' : 'save'), true, 302);
    exit;
}

header('Location: ../thanks.html?sent=1', true, 302);
exit;

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
cms_run_migrations(Database::connection($configDb));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify_from_value($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Session expired. Refresh the page and try again.']);
    exit;
}

$gotcha = trim((string) ($_POST['_gotcha'] ?? ''));
if ($gotcha !== '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unable to submit.']);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$projectType = trim((string) ($_POST['project_type'] ?? ''));
$budgetRange = trim((string) ($_POST['budget_range'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || strlen($name) < 2 || $email === '' || $phone === '' || $message === ''
    || $projectType === '' || $budgetRange === '' || $location === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please complete all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid email address.']);
    exit;
}

$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid mobile number.']);
    exit;
}

if (strlen($name) > 200 || strlen($email) > 254 || strlen($phone) > 50
    || strlen($projectType) > 200 || strlen($budgetRange) > 100
    || strlen($location) > 200 || strlen($message) > 20000) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'One or more fields are too long.']);
    exit;
}

$fullMessage = "Project type: {$projectType}\nBudget: {$budgetRange}\nLocation: {$location}\n\n{$message}";

try {
    $pdo = Database::connection($configDb);
    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, phone, message, subject, form_source, budget_range, location, ip_address, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $email,
        $phone,
        $fullMessage,
        $projectType,
        'consultation',
        $budgetRange,
        $location,
        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'new',
    ]);

    require_once SPANGLE_ROOT . '/includes/enquiryMail.php';
    enquiry_send_notification($pdo, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'project_type' => $projectType,
        'budget_range' => $budgetRange,
        'location' => $location,
        'message' => $fullMessage,
        'form_source' => 'consultation',
        'submitted_at' => date('Y-m-d H:i:s'),
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[SPANGLE] Consultation save failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to save your request. Please try again or call us.']);
}

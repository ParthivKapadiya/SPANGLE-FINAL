<?php

declare(strict_types=1);

/**
 * Shared handler for public contact/enquiry form POSTs.
 *
 * @param array{thanks:string,invalid:string,spam:string,save:string} $redirects
 */
function process_contact_enquiry(array $configDb, array $redirects): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($redirects['invalid'] ?? '../index.html');
    }

    $gotcha = trim((string) ($_POST['_gotcha'] ?? ''));
    if ($gotcha !== '') {
        redirect($redirects['spam'] ?? '../contact.html?enquiry=spam');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $projectType = trim((string) ($_POST['project_type'] ?? ''));
    $budgetRange = trim((string) ($_POST['budget_range'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $formSource = trim((string) ($_POST['form_source'] ?? 'contact'));
    if ($formSource === '') {
        $formSource = 'contact';
    }

    if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect($redirects['invalid'] ?? '../contact.html?enquiry=invalid');
    }

    if (strlen($name) > 200 || strlen($email) > 254 || strlen($phone) > 50
        || strlen($projectType) > 200 || strlen($message) > 20000) {
        redirect($redirects['invalid'] ?? '../contact.html?enquiry=invalid');
    }

    try {
        $pdo = Database::connection($configDb);
        $subject = trim((string) ($_POST['subject'] ?? ''));
        if ($subject === '' && $projectType !== '') {
            $subject = $projectType;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, phone, message, subject, form_source, budget_range, location, ip_address, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $email,
            $phone,
            $message,
            $subject !== '' ? $subject : null,
            $formSource,
            $budgetRange !== '' ? $budgetRange : null,
            $location !== '' ? $location : null,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'new',
        ]);

        require_once SPANGLE_ROOT . '/includes/enquiryMail.php';
        $mailPayload = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'project_type' => $projectType,
            'message' => $message,
            'form_source' => $formSource,
            'submitted_at' => date('Y-m-d H:i:s'),
        ];
        if (!enquiry_send_notification($pdo, $mailPayload)) {
            error_log('[SPANGLE] Enquiry saved (id ' . $pdo->lastInsertId() . ') but email notification failed.');
        }

        redirect($redirects['thanks'] ?? '../thanks.html?sent=1');
    } catch (Throwable $e) {
        error_log('[SPANGLE] Contact enquiry save failed: ' . $e->getMessage());
        redirect($redirects['save'] ?? '../contact.html?enquiry=save');
    }
}

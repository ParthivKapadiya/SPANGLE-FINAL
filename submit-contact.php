<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/processContactEnquiry.php';

process_contact_enquiry($configDb, [
    'thanks' => 'thanks.html?sent=1',
    'invalid' => 'contact.html?enquiry=invalid',
    'spam' => 'contact.html?enquiry=spam',
    'save' => 'contact.html?enquiry=save',
]);

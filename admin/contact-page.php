<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

admin_require_auth();
redirect('contact/index.php');

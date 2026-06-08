<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/contactAdmin.php';

admin_require_auth();

$section = contact_admin_require_section('testimonials');
extract(contact_admin_page_vars($section));

require dirname(__DIR__) . '/includes/layout.php';
contact_admin_render_back();
?>
<div class="adm-card adm-settings-section adm-glass">
  <h2>Client quotes</h2>
  <p class="adm-hint">The contact page shows up to four testimonials from your library. There is no separate heading — quotes appear in a grid after the WhatsApp section.</p>
  <?php home_admin_card_link('testimonials.php', 'Manage client quotes', 'Add or edit testimonial text shown on the contact page.'); ?>
</div>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

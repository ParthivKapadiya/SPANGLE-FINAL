<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/homeAdmin.php';

admin_require_auth();

$section = home_admin_require_section('map');
extract(home_admin_page_vars($section));

$keys = ['map_embed_url', 'map_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    home_admin_save_text_fields($pdo, $keys);
    admin_log_activity($pdo, 'save', 'home-map', null, 'Home map updated');
    home_admin_sync_and_redirect('map.php', 'Map section saved.');
}

$s = settings_get_many($pdo, $keys);

require dirname(__DIR__) . '/includes/layout.php';
home_admin_render_back();
?>
<form method="post" class="adm-settings-grid">
  <?= csrf_field() ?>
  <div class="adm-card adm-settings-section adm-glass">
    <h2>Google Map embed</h2>
    <p class="adm-hint">Paste the embed URL from Google Maps (Share → Embed a map → copy the iframe src URL).</p>
    <?php
    home_admin_render_field('map_title', 'Map section title (accessibility)', $s);
    home_admin_render_field('map_embed_url', 'Google Map embed URL', $s);
    ?>
  </div>
  <?php home_admin_render_save('Save map'); ?>
</form>
<?php require dirname(__DIR__) . '/includes/layout-end.php'; ?>

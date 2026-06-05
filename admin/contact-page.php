<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';

admin_require_auth();
cms_sync_plain_contact_fields($pdo);

$pageKeys = [
    'contact_hero_kicker', 'contact_hero_title', 'contact_hero_lead', 'contact_hero_image',
    'contact_page_title', 'contact_page_lead', 'contact_hours_html',
    'contact_intro_title', 'contact_intro_lead',
    'contact_step_1_title', 'contact_step_1_text', 'contact_step_2_title', 'contact_step_2_text',
    'contact_step_3_title', 'contact_step_3_text', 'contact_step_4_title', 'contact_step_4_text',
    'contact_project_types', 'contact_budget_ranges', 'contact_reasons',
    'contact_trust_1_title', 'contact_trust_1_text', 'contact_trust_2_title', 'contact_trust_2_text',
    'contact_trust_3_title', 'contact_trust_3_text', 'contact_trust_4_title', 'contact_trust_4_text',
    'contact_trust_5_title', 'contact_trust_5_text', 'contact_trust_6_title', 'contact_trust_6_text',
    'contact_founder_quote', 'contact_visit_parking', 'contact_visit_appointment', 'contact_wa_lead',
    'contact_cta_title', 'contact_cta_sub', 'contact_cta_btn_text', 'contact_cta_btn_url',
    'contact_cta_btn2_text', 'contact_cta_btn2_url',
    'contact_faq_q1', 'contact_faq_a1', 'contact_faq_q2', 'contact_faq_a2',
    'contact_faq_q3', 'contact_faq_a3', 'contact_faq_q4', 'contact_faq_a4',
    'contact_faq_q5', 'contact_faq_a5',
];

$labels = [
    'contact_hero_kicker' => 'Hero — small label',
    'contact_hero_title' => 'Hero — main heading',
    'contact_hero_lead' => 'Hero — intro text',
    'contact_hero_image' => 'Hero — background image path',
    'contact_page_title' => 'Visit section — studio title',
    'contact_page_lead' => 'Visit section — intro',
    'contact_hours_html' => 'Studio hours (HTML)',
    'contact_intro_title' => 'Journey — heading',
    'contact_intro_lead' => 'Journey — intro',
    'contact_step_1_title' => 'Step 1 — title',
    'contact_step_1_text' => 'Step 1 — description',
    'contact_step_2_title' => 'Step 2 — title',
    'contact_step_2_text' => 'Step 2 — description',
    'contact_step_3_title' => 'Step 3 — title',
    'contact_step_3_text' => 'Step 3 — description',
    'contact_step_4_title' => 'Step 4 — title',
    'contact_step_4_text' => 'Step 4 — description',
    'contact_project_types' => 'Project types (one per line)',
    'contact_budget_ranges' => 'Budget ranges (one per line)',
    'contact_reasons' => 'Why clients contact (one per line)',
    'contact_trust_1_title' => 'Trust card 1 — title',
    'contact_trust_1_text' => 'Trust card 1 — text',
    'contact_trust_2_title' => 'Trust card 2 — title',
    'contact_trust_2_text' => 'Trust card 2 — text',
    'contact_trust_3_title' => 'Trust card 3 — title',
    'contact_trust_3_text' => 'Trust card 3 — text',
    'contact_trust_4_title' => 'Trust card 4 — title',
    'contact_trust_4_text' => 'Trust card 4 — text',
    'contact_trust_5_title' => 'Trust card 5 — title',
    'contact_trust_5_text' => 'Trust card 5 — text',
    'contact_trust_6_title' => 'Trust card 6 — title',
    'contact_trust_6_text' => 'Trust card 6 — text',
    'contact_founder_quote' => 'Founder quote',
    'contact_visit_parking' => 'Visit — parking note',
    'contact_visit_appointment' => 'Visit — appointment note',
    'contact_wa_lead' => 'WhatsApp section — intro',
    'contact_cta_title' => 'Final CTA — heading',
    'contact_cta_sub' => 'Final CTA — subheadline',
    'contact_cta_btn_text' => 'Primary CTA — label',
    'contact_cta_btn_url' => 'Primary CTA — link',
    'contact_cta_btn2_text' => 'Secondary CTA — label',
    'contact_cta_btn2_url' => 'Secondary CTA — link',
    'contact_faq_q1' => 'FAQ 1 — question',
    'contact_faq_a1' => 'FAQ 1 — answer',
    'contact_faq_q2' => 'FAQ 2 — question',
    'contact_faq_a2' => 'FAQ 2 — answer',
    'contact_faq_q3' => 'FAQ 3 — question',
    'contact_faq_a3' => 'FAQ 3 — answer',
    'contact_faq_q4' => 'FAQ 4 — question',
    'contact_faq_a4' => 'FAQ 4 — answer',
    'contact_faq_q5' => 'FAQ 5 — question',
    'contact_faq_a5' => 'FAQ 5 — answer',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    foreach ($pageKeys as $key) {
        if (array_key_exists($key, $_POST)) {
            setting_set($pdo, $key, trim((string) $_POST[$key]));
        }
    }
    cms_sync_plain_contact_fields($pdo);
    content_sync_site_json($pdo);
    admin_flash_set('success', 'Contact page settings saved.');
    redirect('contact-page.php');
}

$pageTitle = 'Contact page';
$activeNav = 'contact-page';
$s = settings_get_many($pdo, $pageKeys);
require __DIR__ . '/includes/layout.php';
?>
<form method="post" class="adm-card">
  <?= csrf_field() ?>
  <?php foreach ($pageKeys as $key): ?>
    <div class="adm-field">
      <label for="<?= e($key) ?>"><?= e($labels[$key] ?? $key) ?></label>
      <?php
      $multiline = preg_match('/_(text|lead|html|types|ranges|reasons|quote|parking|appointment|wa_lead|a\d)$/', $key);
      if ($multiline): ?>
        <textarea name="<?= e($key) ?>" id="<?= e($key) ?>" rows="<?= str_contains($key, 'hours_html') ? 6 : (str_contains($key, '_a') ? 3 : 2) ?>"><?= e($s[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="<?= e($key) ?>" id="<?= e($key) ?>" value="<?= e($s[$key] ?? '') ?>" />
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="adm-actions">
    <button type="submit" class="adm-btn adm-btn-primary">Save contact page</button>
  </div>
</form>
<?php require __DIR__ . '/includes/layout-end.php'; ?>

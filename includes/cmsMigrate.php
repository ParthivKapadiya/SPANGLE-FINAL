<?php

declare(strict_types=1);

function cms_run_migrations(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $tablesReady = false;
    try {
        $pdo->query('SELECT 1 FROM testimonials LIMIT 1');
        $tablesReady = true;
    } catch (Throwable $e) {
        $tablesReady = false;
    }

    $statements = [
        "CREATE TABLE IF NOT EXISTS testimonials (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          quote TEXT NOT NULL,
          author_name VARCHAR(200) NOT NULL,
          author_role VARCHAR(200) NULL,
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS team_members (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(200) NOT NULL,
          role_title VARCHAR(200) NOT NULL,
          bio TEXT NULL,
          image_path VARCHAR(500) NULL,
          initials VARCHAR(10) NULL,
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS process_steps (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          step_label VARCHAR(20) NOT NULL,
          title VARCHAR(200) NOT NULL,
          description TEXT NULL,
          context VARCHAR(20) NOT NULL DEFAULT 'both',
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS awards (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          icon_class VARCHAR(80) NOT NULL DEFAULT 'fas fa-trophy',
          title VARCHAR(200) NOT NULL,
          subtitle VARCHAR(300) NULL,
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS journal_posts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          slug VARCHAR(120) NOT NULL UNIQUE,
          title VARCHAR(300) NOT NULL,
          excerpt TEXT NULL,
          body_html MEDIUMTEXT NULL,
          image_path VARCHAR(500) NULL,
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    if (!$tablesReady) {
        foreach ($statements as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $ex) {
                // ignore
            }
        }
    }

    try {
        $has = $pdo->query("SHOW COLUMNS FROM projects LIKE 'body_html'")->fetch();
        if (!$has) {
            $pdo->exec('ALTER TABLE projects ADD COLUMN body_html MEDIUMTEXT NULL AFTER summary');
        }
    } catch (Throwable $ex) {
        // ignore
    }

    $journalCols = [
        'category' => "ALTER TABLE journal_posts ADD COLUMN category VARCHAR(120) NULL AFTER excerpt",
        'read_minutes' => "ALTER TABLE journal_posts ADD COLUMN read_minutes SMALLINT UNSIGNED NULL AFTER category",
    ];
    foreach ($journalCols as $col => $sql) {
        try {
            $hasCol = $pdo->query("SHOW COLUMNS FROM journal_posts LIKE '$col'")->fetch();
            if (!$hasCol) {
                $pdo->exec($sql);
            }
        } catch (Throwable $ex) {
            // ignore
        }
    }

    try {
        $has = $pdo->query("SHOW COLUMNS FROM gallery_items LIKE 'show_on_home'")->fetch();
        if (!$has) {
            $pdo->exec('ALTER TABLE gallery_items ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
        }
    } catch (Throwable $ex) {
        // ignore
    }

    cms_run_v3_migrations($pdo);
    cms_run_v4_migrations($pdo);
    cms_seed_missing_home_impact_settings($pdo);
    require_once SPANGLE_ROOT . '/includes/cmsStudioSections.php';
    cms_seed_studio_section_settings($pdo);
    cms_sync_studio_story_timeline_settings($pdo);
    cms_seed_services_process_steps($pdo);
    cms_fix_services_content_typos($pdo);

    require_once SPANGLE_ROOT . '/includes/cmsSeed.php';
    cms_seed_defaults($pdo);
    cms_seed_copy_settings($pdo);
}

function cms_run_v3_migrations(PDO $pdo): void
{
    cms_add_column_if_missing($pdo, 'admins', 'email', 'VARCHAR(254) NULL');
    cms_add_column_if_missing($pdo, 'admins', 'role', "VARCHAR(40) NOT NULL DEFAULT 'admin' AFTER display_name");

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          admin_id INT UNSIGNED NOT NULL,
          token_hash CHAR(64) NOT NULL,
          expires_at DATETIME NOT NULL,
          used_at DATETIME NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_reset_admin (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {
    }

    foreach ([
        'area_label' => 'VARCHAR(120) NULL',
        'completion_year' => 'SMALLINT UNSIGNED NULL',
        'project_type' => "VARCHAR(60) NOT NULL DEFAULT 'residential'",
        'services_provided' => 'TEXT NULL',
        'client_testimonial' => 'TEXT NULL',
        'seo_title' => 'VARCHAR(200) NULL',
        'seo_description' => 'VARCHAR(320) NULL',
        'is_featured' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ] as $col => $def) {
        cms_add_column_if_missing($pdo, 'projects', $col, $def);
    }

    try {
        $pdo->exec('UPDATE projects SET project_type = category WHERE project_type IS NULL OR project_type = ""');
        $pdo->exec("UPDATE projects SET project_type = 'commercial' WHERE category = 'retail'");
        $pdo->exec("UPDATE projects SET project_type = 'commercial', category = 'commercial' WHERE project_type IN ('retail','hospitality','office','mixed-use') OR category IN ('retail','hospitality','office','mixed-use')");
        $pdo->exec("UPDATE projects SET project_type = 'residential', category = 'residential' WHERE project_type IN ('renovation','villa') OR category IN ('renovation','villa')");
    } catch (Throwable $e) {
    }

    cms_remove_deprecated_project_categories($pdo);
    cms_migrate_projects_category_column($pdo);

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_images (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          project_id INT UNSIGNED NOT NULL,
          image_path VARCHAR(500) NOT NULL,
          caption VARCHAR(300) NULL,
          sort_order INT NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_project_images_project (project_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS media_assets (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          file_path VARCHAR(500) NOT NULL,
          file_name VARCHAR(255) NOT NULL,
          mime_type VARCHAR(120) NULL,
          file_size INT UNSIGNED NULL,
          alt_text VARCHAR(300) NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {
    }

    cms_add_column_if_missing($pdo, 'contact_messages', 'subject', 'VARCHAR(200) NULL');
    cms_add_column_if_missing($pdo, 'contact_messages', 'budget_range', 'VARCHAR(100) NULL');
    cms_add_column_if_missing($pdo, 'contact_messages', 'location', 'VARCHAR(200) NULL');
    cms_add_column_if_missing($pdo, 'contact_messages', 'status', "ENUM('new','contacted','in_progress','closed') NOT NULL DEFAULT 'new'");
    try {
        $col = $pdo->query("SHOW COLUMNS FROM contact_messages LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        $type = (string) ($col['Type'] ?? '');
        if ($type !== '' && stripos($type, 'in_progress') === false) {
            $pdo->exec("ALTER TABLE contact_messages MODIFY status ENUM('new','contacted','in_progress','closed') NOT NULL DEFAULT 'new'");
        }
    } catch (Throwable $e) {
    }
    cms_add_column_if_missing($pdo, 'testimonials', 'author_photo', 'VARCHAR(500) NULL');
    cms_add_column_if_missing($pdo, 'testimonials', 'rating', 'TINYINT UNSIGNED NULL DEFAULT 5');
    cms_add_column_if_missing($pdo, 'team_members', 'linkedin_url', 'VARCHAR(500) NULL');
    cms_add_column_if_missing($pdo, 'team_members', 'instagram_url', 'VARCHAR(500) NULL');
    cms_add_column_if_missing($pdo, 'journal_posts', 'seo_title', 'VARCHAR(200) NULL');
    cms_add_column_if_missing($pdo, 'journal_posts', 'seo_description', 'VARCHAR(320) NULL');
    cms_add_column_if_missing($pdo, 'home_stats', 'stat_icon', 'VARCHAR(80) NULL AFTER stat_label');

    try {
        $stmt = $pdo->query('SELECT id, username FROM admins WHERE email IS NULL OR email = ""');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uname = trim((string) $row['username']);
            $email = filter_var($uname, FILTER_VALIDATE_EMAIL)
                ? $uname
                : ($uname === 'admin' ? 'admin@spangle.local' : '');
            if ($email !== '') {
                $up = $pdo->prepare('UPDATE admins SET email = ? WHERE id = ?');
                $up->execute([$email, (int) $row['id']]);
            }
        }
    } catch (Throwable $e) {
    }
}

function cms_run_v4_migrations(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_activity (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          admin_id INT UNSIGNED NULL,
          action VARCHAR(80) NOT NULL,
          entity VARCHAR(80) NOT NULL DEFAULT '',
          entity_id INT UNSIGNED NULL,
          detail VARCHAR(500) NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_admin_activity_created (created_at DESC),
          INDEX idx_admin_activity_entity (entity, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {
    }

    cms_add_column_if_missing($pdo, 'media_assets', 'folder', "VARCHAR(60) NOT NULL DEFAULT 'general' AFTER file_name");
    cms_add_column_if_missing($pdo, 'journal_posts', 'status', "ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER is_active");
    cms_add_column_if_missing($pdo, 'projects', 'show_on_home', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured');

    foreach ([
        'nav_contact_label' => 'Contact',
        'nav_contact_href' => 'contact.html',
        'nav_enquire_label' => 'Enquire',
        'nav_enquire_href' => 'contact.html',
        'analytics_ga_id' => 'Google Analytics measurement ID',
        'analytics_gsc_meta' => 'Google Search Console verification meta content',
        'footer_agency_credit' => 'Footer agency credit line',
        'social_linkedin' => 'LinkedIn URL',
        'site_description' => 'Company description (schema & about)',
    ] as $key => $_label) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM site_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
                $ins->execute([$key, '']);
            }
        } catch (Throwable $e) {
        }
    }

    try {
        $pdo->exec("UPDATE site_settings SET setting_value = 'Contact' WHERE setting_key = 'nav_contact_label' AND setting_value IN ('', 'Enquire', 'ENQUIRE')");
        $pdo->exec("UPDATE site_settings SET setting_value = 'Enquire' WHERE setting_key = 'nav_enquire_label' AND (setting_value = '' OR setting_value IS NULL)");
        $pdo->exec("UPDATE site_settings SET setting_value = 'contact.html' WHERE setting_key = 'nav_enquire_href' AND (setting_value = '' OR setting_value IS NULL)");
        $pdo->exec("UPDATE site_settings SET setting_value = 'contact.html' WHERE setting_key = 'nav_contact_href' AND (setting_value = '' OR setting_value = 'journal.html')");
    } catch (Throwable $e) {
    }
}

function cms_add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    try {
        $has = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column))->fetch();
        if (!$has) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    } catch (Throwable $e) {
    }
}

function cms_seed_missing_home_impact_settings(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach ([
        'home_impact_eyebrow' => 'Impact',
        'home_impact_title' => 'Built at scale. Trusted at home.',
    ] as $key => $default) {
        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || trim((string) ($row['setting_value'] ?? '')) === '') {
                setting_set($pdo, $key, $default);
            }
        } catch (Throwable $e) {
        }
    }
}

function cms_migrate_projects_category_column(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $col = $pdo->query("SHOW COLUMNS FROM projects LIKE 'category'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            return;
        }
        $type = strtolower((string) ($col['Type'] ?? ''));
        if (!str_starts_with($type, 'enum')) {
            return;
        }

        $pdo->exec("ALTER TABLE projects MODIFY category VARCHAR(60) NOT NULL DEFAULT 'residential'");
        $pdo->exec(
            "UPDATE projects SET category = project_type
             WHERE project_type IS NOT NULL AND project_type != ''"
        );
        $pdo->exec(
            "UPDATE projects SET project_type = category
             WHERE project_type IS NULL OR project_type = ''"
        );
    } catch (Throwable $e) {
    }
}

function cms_remove_deprecated_project_categories(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $deprecatedLines = [
        'renovation',
        'hospitality',
        'office design',
        'villa design',
        'mixed use',
        'mixed-use',
    ];

    foreach (['contact_project_types', 'contact_reasons'] as $key) {
        try {
            $raw = trim((string) (settings_get_many($pdo, [$key])[$key] ?? ''));
            if ($raw === '') {
                continue;
            }
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: []), static function (string $line) use ($deprecatedLines): bool {
                return !in_array(strtolower($line), $deprecatedLines, true);
            }));
            setting_set($pdo, $key, implode("\n", $lines));
        } catch (Throwable $e) {
        }
    }
}

/** Ensure eight process steps exist for Services / Process / Studio previews. */
function cms_seed_services_process_steps(PDO $pdo): void
{
    try {
        $pdo->query('SELECT 1 FROM process_steps LIMIT 1');
    } catch (Throwable $e) {
        return;
    }

    $defaults = [
        ['I', 'Discovery', 'Site, aspirations, and feasibility — aligned before pencil hits paper.', 'both', 0],
        ['II', 'Design', 'Schematic through tender-ready drawings, models, and sample boards.', 'both', 1],
        ['III', 'Delivery', 'Site administration, RFIs, and vendor coordination until handover.', 'both', 2],
        ['IV', 'Close-out', 'Styling, documentation, and photography — space ready to live in.', 'both', 3],
        ['V', 'Approvals', 'Plan sanctioning, authority coordination, and compliance documentation.', 'page', 4],
        ['VI', 'Construction', 'Site administration, quality checks, and vendor coordination on site.', 'page', 5],
        ['VII', 'Interior execution', 'Joinery, finishes, FF&E, and styling aligned with the design intent.', 'page', 6],
        ['VIII', 'Handover', 'Snag resolution, documentation, and keys — space ready to occupy.', 'page', 7],
    ];

    $check = $pdo->prepare('SELECT id FROM process_steps WHERE title = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO process_steps (step_label, title, description, context, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)'
    );

    foreach ($defaults as $step) {
        $check->execute([$step[1]]);
        if ($check->fetch()) {
            continue;
        }
        $insert->execute([$step[0], $step[1], $step[2], $step[3], $step[4]]);
    }
}

/** One-time cleanup of known services-page copy typos in DB + resync site.json. */
function cms_fix_services_content_typos(PDO $pdo): void
{
    try {
        $flag = settings_get_many($pdo, ['cms_services_typo_fix_v1'])['cms_services_typo_fix_v1'] ?? '';
        if ($flag === 'done') {
            return;
        }
    } catch (Throwable $e) {
        return;
    }

    $stripOneKeys = [
        'services_kicker', 'services_title', 'services_lead',
        'services_ecosystem_eyebrow', 'services_ecosystem_title', 'services_ecosystem_intro',
        'services_process_eyebrow', 'services_process_title', 'services_process_intro',
        'services_process_link_text', 'services_compare_eyebrow', 'services_compare_title', 'services_compare_intro',
        'services_impact_eyebrow', 'services_impact_title',
        'services_cases_eyebrow', 'services_cases_title', 'services_cases_intro',
        'services_testimonials_eyebrow', 'services_testimonials_title',
        'services_cta_eyebrow', 'services_cta_title', 'services_cta_sub', 'services_cta_lead',
        'services_cta_btn_text', 'services_cta_btn2_text',
        'services_detail_link_text', 'services_cases_link_text',
        'services_faq_eyebrow', 'services_faq_title',
    ];

    foreach ($stripOneKeys as $key) {
        try {
            $val = trim((string) (settings_get_many($pdo, [$key])[$key] ?? ''));
            if ($val !== '' && preg_match('/[^\s]1$/', $val)) {
                setting_set($pdo, $key, substr($val, 0, -1));
            }
        } catch (Throwable $e) {
        }
    }

    try {
        $pdo->exec("UPDATE services SET number_label = '01' WHERE number_label = '011'");
        $textCols = ['title', 'short_description', 'eyebrow', 'detail_title', 'detail_lead_1', 'detail_lead_2'];
        $replacements = [
            'approvalss' => 'approvals',
            'Approvalss' => 'Approvals',
            'compliance..' => 'compliance.',
            'programme.s' => 'programme.',
            'rules.s' => 'rules.',
        ];
        foreach ($textCols as $col) {
            foreach ($replacements as $from => $to) {
                $pdo->exec(
                    "UPDATE services SET `$col` = REPLACE(`$col`, " . $pdo->quote($from) . ', ' . $pdo->quote($to) . ") WHERE `$col` LIKE " . $pdo->quote('%' . $from . '%')
                );
            }
        }
    } catch (Throwable $e) {
    }

    try {
        setting_set($pdo, 'cms_services_typo_fix_v1', 'done');
        content_sync_site_json($pdo);
    } catch (Throwable $e) {
    }
}

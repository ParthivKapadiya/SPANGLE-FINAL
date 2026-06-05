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
    } catch (Throwable $e) {
    }

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

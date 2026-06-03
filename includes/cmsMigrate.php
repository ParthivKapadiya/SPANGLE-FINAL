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

    require_once SPANGLE_ROOT . '/includes/cmsSeed.php';
    cms_seed_defaults($pdo);
    cms_seed_copy_settings($pdo);
}

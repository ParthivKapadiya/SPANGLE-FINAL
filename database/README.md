# SPANGLE — MySQL setup (XAMPP)

See **[LOCALHOST.md](../LOCALHOST.md)** in the project root for full steps.

Quick start:

1. Start **Apache** and **MySQL** in XAMPP.
2. Open `http://localhost/SPANGLE_FINAL/install.php` (or `/spangle/` if you renamed the folder).
3. Install with user **root** and password **empty** (Mac) or **root** (Windows).
4. Admin: `http://localhost/SPANGLE_FINAL/admin/login.php` — **admin** / **admin123**
5. Delete `install.php` on production only.

Config: `config/database.php` — optional override: `config/database.local.php`

Public site loads content from MySQL via `api/public-content.php`.

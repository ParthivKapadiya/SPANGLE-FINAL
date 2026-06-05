# Run SPANGLE on XAMPP (localhost)

## 1. Start XAMPP

1. Open **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.

## 2. Open the site in the browser

This project folder is `SPANGLE_FINAL` under htdocs:

| Page | URL |
|------|-----|
| Home | http://localhost/SPANGLE_FINAL/index.html |
| Installer | http://localhost/SPANGLE_FINAL/install.php |
| Admin login | http://localhost/SPANGLE_FINAL/admin/login.php |

Use **http://localhost/...** only — do not open files with `file://`.

Optional: rename the folder to `spangle` (no spaces) and use  
http://localhost/spangle/index.html

## 3. Install the database (first time)

1. Go to http://localhost/SPANGLE_FINAL/install.php
2. MySQL defaults for XAMPP:
   - Host: `127.0.0.1`
   - Port: `3306`
   - Database: `spangle_studio`
   - User: `root`
   - Password: leave **empty** on Mac; try **`root`** on Windows if empty fails
3. Click **Install now**
4. Sign in at admin: **admin** / **admin123** — change the password after login

If install says config is not writable, run in Terminal:

```bash
chmod -R 777 /Applications/XAMPP/xamppfiles/htdocs/SPANGLE_FINAL/config
chmod -R 777 /Applications/XAMPP/xamppfiles/htdocs/SPANGLE_FINAL/uploads
```

## 4. MySQL password override

If your MySQL root password is not empty:

```bash
cp config/database.local.example.php config/database.local.php
```

Edit `config/database.local.php` and set your password.

## 5. What works locally

- Public pages load content from MySQL via `api/public-content.php`
- Fallback: `content/site.json` if MySQL is off
- Contact form posts to `api/submit-contact.php`
- Admin CMS at `/admin/`
- Uploads saved under `uploads/`

On localhost, image and asset URLs automatically use  
`http://localhost/SPANGLE_FINAL` (not the live spangle.studio domain).

## 6. Troubleshooting

| Problem | Fix |
|---------|-----|
| Blank admin / API error | Start MySQL; re-run install.php |
| Access denied for user 'root' | Fix password in `config/database.php` or `database.local.php` |
| Images point to live site | Hard refresh (Cmd+Shift+R); ensure you use localhost URL |
| 403 on config/includes | Normal — those folders are blocked by `.htaccess` |
| Contact form fails | Complete install.php so `contact_messages` table exists |
| Form saves but **no email** | XAMPP cannot send mail by default. Copy `config/mail.local.example.php` to `config/mail.local.php`, add Gmail App Password, run `php scripts/test-enquiry-mail.php` |

## 7. Before going live

- Deploy files to hosting
- Run install (or import DB) on the server
- Set **public base URL** in Admin → Site branding to your live URL (e.g. `https://spangle.page.gd`)
- In Admin → **Contact information**, set **Enquiry notify email** (testing: `pkapadiya257@rku.ac.in`; production: studio inbox)
- Change the default admin password (`admin` / `admin123`) after first login
- **Admin URL is not shown in the public footer** — bookmark `https://your-domain.com/admin/login.php` for the studio owner
- Delete `install.php` on production (installer is also locked when `config/.installed` exists)
- Test the contact form: submission should appear in **Contact messages** and arrive by email

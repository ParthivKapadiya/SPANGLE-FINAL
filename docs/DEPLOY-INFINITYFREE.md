# Deploy to InfinityFree (GitHub → FTP)

Every push to `main` can upload the site automatically via GitHub Actions.

## 1. What you need from InfinityFree

In **Control Panel → FTP Accounts** (or main account FTP details), copy:

| Value | Example |
|-------|---------|
| FTP host | `ftpupload.net` |
| FTP username | `if0_12345678` or `epiz_...` |
| FTP password | (your FTP password) |
| Remote folder | Often `/htdocs/` for the main domain |

If you use an **addon domain**, the folder may look like:

`/yourdomain.com/htdocs/`

Upload one test file via File Manager first and note the exact path.

## 2. Add GitHub Secrets

Repo: **Settings → Secrets and variables → Actions → New repository secret**

| Secret name | Required | Value |
|-------------|----------|--------|
| `FTP_USERNAME` | Yes | InfinityFree FTP username |
| `FTP_PASSWORD` | Yes | InfinityFree FTP password |
| `FTP_SERVER_DIR` | Yes | e.g. `/htdocs/` (must start and end with `/`) |
| `FTP_SERVER` | Yes | Usually `ftpupload.net` |

## 3. First-time on the server (not done by Git)

Git deploy does **not** upload these (on purpose):

1. **`config/database.php`** — create on the server with InfinityFree MySQL host, database, user, password (copy from `config/database.local.example.php`).
2. **`uploads/` images** — upload once via FTP/File Manager (large files are not in GitHub).
3. Run **`install.php`** once in the browser, then **delete** `install.php`.
4. Optional: `config/mail.local.php` for SMTP enquiry emails.

## 4. Run deploy

- Push to `main`, or
- GitHub → **Actions** → **Deploy to InfinityFree** → **Run workflow**

## 5. After deploy

- Open `https://your-domain.infinityfreeapp.com/index.html`
- Test contact form and images
- Set public site URL in DB/settings when admin panel is rebuilt

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Workflow fails “login” | Check `FTP_USERNAME` / `FTP_PASSWORD` |
| Files in wrong folder | Fix `FTP_SERVER_DIR` (ask in InfinityFree forum if unsure) |
| 404 on pages | Confirm `deploy/htaccess.infinityfree` was applied (workflow does this) |
| No images | Upload `uploads/` manually |
| DB errors | Create `config/database.php` on server + run `install.php` |

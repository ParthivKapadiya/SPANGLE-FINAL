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
   Include **originals and optimized variants** (`*-640w.jpg`, `*-1280w.jpg`). If only full-size `.jpg` files are uploaded, the site may reference missing variants until you **Admin → Site settings → Save** on the server (rebuilds `content/site.json` from files that exist there).
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
| No images | Upload `uploads/` manually (originals + `*-640w`/`*-1280w` variants, or Admin Save on server to drop missing srcset entries) |
| Some images 404 but main file opens | Variant files missing; upload `*-640w.jpg` / `*-1280w.jpg` or re-save site settings on live admin |
| DB errors | Create `config/database.php` on server + run `install.php` |

### “I uploaded the whole `uploads/` folder but images still 404”

GitHub deploy **does not upload** your photos (`uploads/**` is excluded). Only `uploads/.htaccess` and `uploads/index.html` come from Git. All `.jpg` files must be uploaded **manually via FTP or File Manager**.

Common reasons a “full folder” upload still fails:

1. **Wrong directory** — Files must land in **`htdocs/uploads/`** for `archevoinfra.freepage.cc`.  
   On some InfinityFree accounts the site root is **`/yourdomain.freepage.cc/htdocs/`**, not the account’s top-level `/htdocs/`. Uploading to the wrong `htdocs` folder looks successful but the live site never sees the files.

2. **Nested `uploads/uploads/`** — Dragging the folder *into* an existing `uploads` folder creates `htdocs/uploads/uploads/054-….jpg`. The site requests `/uploads/054-….jpg` (one level) → 404.

3. **Upload did not finish** — ~426 files / ~109 MB. File Manager can time out; use **FTP** (FileZilla), wait until the queue is **100% complete**, then refresh File Manager and search for `054-KANTILAL`.

4. **Git deploy does not delete** excluded `uploads/` files (`dangerous-clean-slate: false`). If images vanish after deploy, check you are not using a different host folder or re-uploading to the wrong path.

**Verify on the server:** upload `check-uploads.php` to `htdocs/`, open  
`https://archevoinfra.freepage.cc/check-uploads.php`  
It prints how many images the server actually has and whether sample files exist. Delete the script when done.

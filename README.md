# GeoAtlas Bangladesh

> Understanding Bangladesh Through Maps.

Personal GIS portfolio. Stack: **HTML5 · CSS3 · Vanilla JS · PHP 8 · MySQL · PDO · Apache (XAMPP) · Leaflet.js**

## Current state: Public site and infrastructure

The public home and about pages are implemented alongside the PHP/PDO infrastructure. Project and category content on the home page requires a working MySQL database; the public shell remains available if the database is unavailable.

## First-time setup

### 1. Start XAMPP
- Start **Apache** and **MySQL** in the XAMPP Control Panel.
- Confirm `http://localhost/` loads.

### 2. Create the database & app user
Open `http://localhost/phpmyadmin` → **SQL** tab → run:

```sql
CREATE DATABASE geoatlas_bd
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'geoatlas_app'@'localhost'
  IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';

GRANT SELECT, INSERT, UPDATE, DELETE
  ON geoatlas_bd.*
  TO 'geoatlas_app'@'localhost';

FLUSH PRIVILEGES;
```

Use a strong password; put the **same** password in `.env` as `DB_PASS`.

### 3. Database schema
This checkout does not include `database/schema.sql` or the seed SQL files. Obtain the matching schema and data before importing anything. Until then, the public site renders, but the home page displays a warning and database-backed content is unavailable.

### 4. Seed categories & sample projects
Category and sample-project seed files are also not included. This step can be completed once those files and the matching schema are available.

### 5. Create the admin user (hashed password)
From the project root:

```bash
php database/seed_admin.php "admin@geoatlas.local" "ReplaceWithStrongPassword!" "Site Administrator"
```

The script hashes the password with **Argon2id** — the plaintext is never written to disk. It requires the matching `users` table to have been created first.

### 6. Create `.env`
```bash
cp .env.example .env
```
Then edit `.env`:
- Set `DB_PASS` to the password from Step 2
- Generate `APP_KEY`:
  ```bash
  php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
  ```
  Paste the output after `APP_KEY=`

`.env` is git-ignored. Never commit it.

### 7. Configure the virtual host (Option A)
Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and append:

```apache
<VirtualHost *:80>
    ServerName geoatlas.local
    DocumentRoot "C:/xampp/htdocs/geoatlasbd/public"
    <Directory "C:/xampp/htdocs/geoatlasbd/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Edit `C:\xampp\apache\conf\httpd.conf` — uncomment:
- `LoadModule rewrite_module modules/mod_rewrite.so`
- `LoadModule headers_module modules/mod_headers.so`
- `Include conf/extra/httpd-vhosts.conf`

Add to `C:\Windows\System32\drivers\etc\hosts` (as Administrator):
```
127.0.0.1   geoatlas.local
```

Restart Apache.

Set `APP_URL=http://geoatlas.local` in `.env`, restart Apache, and visit **http://geoatlas.local/**. Open the site through Apache; do not open PHP files directly from the file system or visit files under `app/Views/`.

For a local subfolder install instead of the virtual host, visit **http://localhost/geoatlasbd/**. Set `APP_URL=http://localhost/geoatlasbd/public` in `.env` for that setup. The project-root rules redirect this URL into `public/` and deny direct web access to application and configuration files.

### 8. Verify the DB connection
```bash
php database/verify_connection.php
```

The check reports the MySQL version, connection charset, expected tables, and row counts. If MySQL rejects the credentials, verify that `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `.env` match the account and database configured in MySQL. The database schema and seed SQL files must be present and imported before database-backed content can load.

## Directory layout

```
public/       → Apache docroot (only web-exposed folder)
app/          → PHP source (Models, Controllers, Services, Views)
config/       → config.php, database.php
database/     → schema.sql, seeds, verify_connection.php
storage/      → logs, cache (writable, web-blocked)
.env          → secrets (git-ignored)
```

## Security notes for Phase 2

- **Credentials** live only in `.env`; DB user is least-privilege (`SELECT, INSERT, UPDATE, DELETE`).
- **PDO** uses `ERRMODE_EXCEPTION`, real prepared statements, `utf8mb4`.
- **Security headers** set globally by `SecurityHeaders` middleware and `.htaccess`.
- **Uploads folder** has PHP execution disabled by `.htaccess`.
- **`storage/` and `database/`** are outside `public/` — not web-accessible.
- **Admin password** is Argon2id-hashed at insert time by `seed_admin.php`; no plaintext hash committed.
## Administrator setup (Phase 7)

Public registration is **disabled**. Administrators are created from the CLI only.

### Create the first administrator

From the project root:

```bash
php database/seed_admin.php "admin@geoatlas.local" "A-Strong-Passphrase-Here!" "Site Administrator"
```

- The script uses **Argon2id** via `password_hash()` — the plaintext password is never stored on disk.
- Minimum password length is 12 characters (enforced by the script).
- Safe to re-run: if the email exists, the hash and name are updated in place.
- The script is CLI-only. Visiting it in a browser returns 403.

### Sign in

Visit `http://geoatlas.local/admin/login` and sign in with the email and password you supplied.

### Lockout behaviour

- **Per account:** 5 failed attempts within a session → account locked 15 minutes.
  Lockout clears automatically; failures reset to zero after a successful login.
- **Per IP:** 10 failed attempts in 15 minutes from one IP → all login attempts
  from that IP are refused until the window passes. This is tracked in `audit_log`
  and does not require any cron job.

Both limits and window lengths are constants at the top of
`app/Services/Auth.php` (`MAX_FAILED`, `LOCK_MINUTES`, `MAX_FAILS_IP`, `IP_WINDOW_MIN`, `IDLE_MINUTES`).

### Reset a forgotten password

Run the same seed command with a **new** password:

```bash
php database/seed_admin.php "admin@geoatlas.local" "NewPassphrase!" "Site Administrator"
```

This updates the existing row (same email) with a fresh Argon2id hash and clears any lockout.

### Deactivate an administrator without deleting

```sql
UPDATE users SET is_active = 0 WHERE email = 'someone@example.com';
```

Inactive users cannot log in and active sessions are terminated on the next request.

### Session policy

- Session cookie: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS.
- Session ID rotates on login **and** on logout.
- Idle timeout: 30 minutes (adjust `IDLE_MINUTES` in `app/Services/Auth.php`).
- Session is bound to `User-Agent` (fingerprint). Switching browsers logs the old session out.

### What the audit log records

Every login attempt is written to `audit_log`:

| action | when |
|---|---|
| `login.success` | correct credentials |
| `login.fail` | wrong password, unknown email, inactive account, or locked account |
| `login.ip_locked` | IP was already over the failure threshold |
| `logout` | explicit sign-out |

Passwords are never logged. IP addresses are stored as SHA-256 hashes salted with `APP_KEY`.

### Verifying the seed file is safe

`database/seed_admin.php` refuses to run outside the CLI and rejects passwords shorter than 12 characters. It contains no hardcoded credentials. To confirm:

```bash
grep -n "password" database/seed_admin.php
```

You will only see `password_hash(...)`, `password_hash` column names, and the `$pass` variable passed in from CLI args.

## Next: Phase 3
Public site shell & design system (`main.css` palette + `layouts/public.php` + Homepage/About).
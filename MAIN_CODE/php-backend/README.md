# PHP Backend Connection Foundation

This folder contains the PHP 8 and MySQL connection test. It does not replace or modify the existing Node.js backend.

## Files

- `config/database.php` contains the reusable procedural MySQLi connection function `autoline_db_connect()`.
- `api/health.php` tests the connection and verifies that the existing `job_cards` table can be queried.
- `api/login.php` authenticates an active user and starts a PHP session.
- `api/logout.php` destroys the current PHP session.
- `auth/auth.php` contains reusable session and authentication helpers.
- `tools/create_user.php` creates users from the command line only.
- `tools/run_migration.php` runs the users-table migration from the command line only.
- `database/migrations/001_create_users.sql` creates the additive `users` table.

## Requirements

- PHP 8 with the `mysqli` extension enabled.
- WAMP Apache and MySQL services running.
- The existing `autoline_dms` database and six existing tables, including `job_cards`.
- The existing database and tables must remain unchanged.

## Database Configuration

The credentials are stored only in the ignored local file `config/database.local.php`. This file is required by `config/database.php` and is never sent in a health-check response. Do not copy its credentials into frontend JavaScript, HTML, logs, or a committed file.

The configured local values are:

- Host: `localhost`
- Username: `root`
- Database: `autoline_dms`
- Port: `3306`

The port is confirmed by the WAMP MySQL configuration at `C:\wamp64\bin\mysql\mysql8.0.31\my.ini`, where the `wampmysqld64` service uses `port=3306`. WAMP's separate MariaDB configuration uses port `3307` and is not used by this PHP connection.

If the local password or WAMP MySQL settings change, edit only `config/database.local.php`. The file is excluded by `php-backend/.gitignore` and is denied by `config/.htaccess` when Apache serves this directory.

## WAMP Setup

1. Place or map `MAIN_CODE` under the WAMP Apache document root. The requested project URL uses `http://localhost/abhi_code/MAIN_CODE/`.
2. Start **Apache** and the WAMP **MySQL** service, not the separate MariaDB service.
3. Confirm that the existing `autoline_dms` database and its six tables are present. Do not run or modify `backend/schema.sql` for this connection setup.
4. Confirm that `php-backend/config/database.local.php` exists with the local credentials and that PHP's `mysqli` extension is enabled.
5. Open the health-check URL below. A successful response verifies the connection and queries the existing `job_cards` table.

## Health Check URL

Open:

`http://localhost/abhi_code/MAIN_CODE/php-backend/api/health.php`

A successful response has HTTP status `200` and JSON with `success: true`. Connection or table verification failures return HTTP status `503` without exposing credentials or internal database details.

## Authentication Setup

The login page posts only the entered username and password to `php-backend/api/login.php`. The selected frontend role is not used for authentication; the role returned by the database is authoritative. Successful login regenerates the PHP session ID and stores only the user ID, username, and role in the session. Passwords and password hashes are never returned to the browser.

Run the migration once from the project directory using the WAMP PHP CLI. It only creates the new `users` table and does not alter existing job-card or billing tables:

```text
C:\wamp64\bin\php\php8.2.0\php.exe php-backend/tools/run_migration.php
```

The runner executes `database/migrations/001_create_users.sql` against the configured `autoline_dms` database. It is CLI-only and does not expose the migration through frontend code.

Create the initial accounts through the CLI-only provisioning script. Choose the passwords interactively; no default passwords are inserted:

```text
C:\wamp64\bin\php\php8.2.0\php.exe php-backend/tools/create_user.php
```

The script asks for the username, role (`ADMIN`, `MANAGER`, or `MECHANIC`), and password, then hashes the password with `password_hash()` and uses a prepared MySQLi statement. Run it once for the admin account and once for the manager account. It cannot be used through a browser.

The login endpoint is:

`http://localhost/abhi_code/MAIN_CODE/php-backend/api/login.php`

Open the existing login page here after creating an account:

`http://localhost/abhi_code/MAIN_CODE/FrontEnd/pages/LoginPage.html`

Enter the username and password created by `create_user.php`. The Role and Branch fields remain part of the existing form; the server uses the role stored in the `users` table, not the role selected in the browser.

The logout endpoint accepts POST requests at:

`http://localhost/abhi_code/MAIN_CODE/php-backend/api/logout.php`

For a protected PHP endpoint, include `auth/auth.php`, call `auth_start_session()`, and then call `require_authenticated_user()`. Unauthorized requests receive a generic JSON `401` response. The existing health endpoint remains public so it can be used for service checks.

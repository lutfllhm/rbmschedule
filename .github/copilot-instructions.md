**Purpose**: Short, focused guidance for AI coding agents working on RBM Schedule.

**Big Picture**:
- **Monolithic PHP app**: Plain PHP 7.4+ codebase (no framework). Entry points are page files in `pages/` and API endpoints in `api/`.
- **Real-time updates**: Server-Sent Events handled by `api/updates_stream.php`; clients also use AJAX polling (`api/check_updates.php`).
- **DB layer**: `config/database.php` exposes `getDBConnection()` / `executeQuery()` using MySQLi and prepared statements. Charset is `utf8mb4` and timezone is `Asia/Jakarta`.

**Key files to read first**:
- `config/autoload.php` — autoloading and helpers loading (`helpers/functions.php`).
- `config/database.php` — DB connection, `DEBUG_MODE`, helper functions.
- `api/updates_stream.php` — long-lived SSE loop (use `ignore_user_abort`, `set_time_limit(0)`).
- `api/schedule_ajax.php` — main schedule CRUD and operator flows (good example of role checks, CSRF, prepared statements, audit logging).
- `includes/auth.php`, `includes/csrf.php`, `includes/audit.php` — session, role logic, CSRF token API, and audit logging.
- `classes/Logger.php`, `classes/Response.php`, `classes/Validator.php` — logging, JSON response patterns, validation helpers.

**Project conventions & patterns (concrete)**:
- Use `require_once __DIR__ . '/../config/database.php'` at top of APIs to get DB and autoloader.
- Protect state-changing endpoints with session checks and CSRF tokens. Example: `api/schedule_ajax.php` verifies `requireLogin()` and `verifyCsrfToken($_POST['csrf_token'] ?? null)`.
- Role checks use `isAdmin()` / `isOperator()` defined in `includes/auth.php`. Follow these to gate actions.
- JSON APIs generally return `['success'=>bool, 'message'=>string, ...]`. The `Response` class exists but many endpoints echo JSON directly — mirror existing shape when adding endpoints.
- Persist audit trails by calling `logScheduleActivity($conn, $id, $action, $message, $changes)` from `includes/audit.php` after creates/updates/deletes.
- Always use prepared statements (see `executeQuery()` and per-endpoint `prepare` usage). Handle duplicate SPK checks as in `schedule_ajax.php`.

**Error handling & logging**:
- Use `Logger::info|warning|error` (initialized in `config/autoload.php`). Logs are in `logs/` (check `application.log` and `error.log`).
- For API errors prefer returning JSON with `success:false` and informative but not sensitive messages. In scripts that redirect (login), use query params like `?error=...`.

**Real-time specifics**:
- SSE endpoint `api/updates_stream.php` sends `event: schedule-update` and `event: heartbeat`; check client code in `assets/js/` for consumption.
- Keep the loop light (current pattern uses `sleep(2)` and a `heartbeatInterval` to avoid client disconnects).

**Developer workflows**:
- Local dev: run with XAMPP/Apache on Windows; import `database.sql` and update `config/database.php` credentials.
  ```powershell
  mysql -u root -p rbm_schedule < database.sql
  ```
- Toggle `DEBUG_MODE` in `config/database.php` for verbose exceptions (set `false` for production).
- Check logs at `logs/` for runtime errors. For SSE issues, use browser console + `logs/error.log`.

**How to add a new API endpoint (quick checklist)**:
1. Create `api/your_endpoint.php` and `require_once` `config/database.php`, `includes/auth.php`, `includes/csrf.php` as needed.
2. Use `requireLogin()` for protected routes and `isAdmin()`/`isOperator()` for role gating.
3. Validate CSRF with `verifyCsrfToken()` for POST actions.
4. Use prepared statements and mirror JSON shape `['success'=>..., 'message'=>..., 'data'=>...]`.
5. Log important actions via `Logger::info()` and `logScheduleActivity()` (for schedule-related changes).

**When in doubt**:
- Follow patterns in `api/schedule_ajax.php` and `api/updates_stream.php` rather than inventing new response shapes or session handling.
- Avoid changing global behaviors (session timeout, timezone) without discussion — they are set centrally in `includes/auth.php` and `config/database.php`.

If you want, I can open a draft PR with these instructions or iterate this file based on your priorities. What should I clarify or expand?

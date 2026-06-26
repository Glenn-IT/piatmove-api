# PiatMove — Security & Code Audit Report

**Audit date:** 2026-06-26  
**Fixed date:** 2026-06-26  
**Scope:** `piatmove-api/` (REST API) + `PiatMoveAdmin/` (Admin Panel)  
**Stack:** PHP 8, MySQL, no framework, custom JWT, PDO  

---

## Fix Status

| # | Issue | Status |
|---|---|---|
| C-1 | Hardcoded JWT secret | ✅ Fixed — reads from `JWT_SECRET` env var or `.env.secret` file; expiry reduced to 24h |
| C-2 | SQL injection in drivers filter | ✅ Fixed — conditional prepared statement |
| C-3 | SQL injection in bookings filter | ✅ Fixed — conditional prepared statement |
| C-4 | Session fixation on login | ✅ Fixed — `session_regenerate_id(true)` added |
| H-1 | No CSRF on admin forms | ✅ Fixed — `csrf_field()` / `csrf_verify()` in auth.php, wired to users + drivers forms |
| H-2 | Root DB credentials | ⚠️ Manual — create a dedicated MySQL user (see H-2 section below) |
| H-3 | Booking accept race condition | ✅ Fixed — single atomic UPDATE with rowCount check |
| H-4 | No brute-force protection | ⚠️ Manual — requires `login_attempts` table implementation |
| H-5 | No lat/lng validation | ✅ Fixed — range validation with FILTER_VALIDATE_FLOAT |
| M-1 | Wildcard CORS | ⚠️ Intentional for mobile API — document if keeping |
| M-2 | No try/catch on DB writes | ⚠️ Low risk on local; add before production deploy |
| M-3 | No minimum password length | ✅ Fixed — 8-char minimum enforced on registration |
| M-4 | Phone not validated | ✅ Fixed — regex format check on registration |
| M-5 | XSS in status_badge() | ✅ Fixed — htmlspecialchars on both class and content |
| M-6 | JWT no revocation | ✅ Partially fixed — expiry reduced to 24h |
| M-7 | No pagination | ⚠️ Add before production deploy |
| M-8 | No global exception handler | ✅ Fixed — set_exception_handler at top of index.php |
| L-1 | Dead $msg_type variable | ✅ Fixed — wired to alert class in users.php |
| L-5 | Logout doesn't clear cookie | ✅ Fixed — $_SESSION cleared + cookie expired |
| L-2, L-3, L-4, L-6 | Low priority items | ⚠️ Address before production deploy |

---

## Critical

### C-1 — Hardcoded JWT Secret
**File:** `piatmove-api/config/database.php`

```php
define('JWT_SECRET', 'piatmove-secret-change-in-production');
```

A known placeholder string committed to the codebase. Anyone with file access can forge any JWT for any user or admin, bypassing the entire authentication system.

**Fix:**
```php
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET'));
if (!JWT_SECRET) die(json_encode(['success'=>false,'message'=>'Server misconfigured']));
```
Load the secret from an environment variable or a `.env` file excluded from git.

---

### C-2 — SQL Injection in Drivers Filter
**File:** `PiatMoveAdmin/drivers.php`, lines 25 and 33

```php
$where = $filter !== 'all' ? "WHERE d.approval_status = '$filter'" : '';
$drivers = $db->query("SELECT ... $where ORDER BY ...")->fetchAll();
```

`$filter` is string-interpolated directly into SQL. The whitelist on line 23 is the only guard — removing it or copy-pasting this pattern elsewhere without the guard creates a live injection.

**Fix — conditional prepared statement:**
```php
if ($filter !== 'all') {
    $stmt = $db->prepare("SELECT ... WHERE d.approval_status = ? ORDER BY ...");
    $stmt->execute([$filter]);
} else {
    $stmt = $db->query("SELECT ... ORDER BY ...");
}
$drivers = $stmt->fetchAll();
```

---

### C-3 — SQL Injection in Bookings Filter
**File:** `PiatMoveAdmin/bookings.php`, lines 12 and 14

Identical pattern to C-2. `$filter` from `$_GET['status']` is interpolated directly into the query.

**Fix:** Same approach — use a conditional prepared statement.

---

### C-4 — Session Fixation on Admin Login
**File:** `PiatMoveAdmin/index.php`, login success block

`session_regenerate_id(true)` is never called after a successful login. An attacker who pre-sets a victim's session ID can hijack the authenticated session.

**Fix:**
```php
if ($admin && password_verify($pass, $admin['password'])) {
    session_regenerate_id(true); // <-- add this line
    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
```

---

## High

### H-1 — No CSRF Protection on Admin Forms
**Files:** `PiatMoveAdmin/users.php`, `PiatMoveAdmin/drivers.php`

Every destructive form (delete user, activate/deactivate, approve/reject driver) has no CSRF token. A logged-in admin visiting a malicious page can silently trigger these actions.

**Fix:**
```php
// On session start — generate token:
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In every form:
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

// At top of every POST handler:
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('CSRF validation failed');
}
```

---

### H-2 — Root Database Credentials / Empty Password
**Files:** `piatmove-api/config/database.php`, `PiatMoveAdmin/config.php`

```php
define('DB_USER', 'root');
define('DB_PASS', '');
```

Both apps connect as MySQL `root` with no password. `root` also has GRANT and FILE privileges — a single leaked DSN or SSRF gives full database control.

**Fix:** Create a dedicated user:
```sql
CREATE USER 'piatmove_app'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON piatmove.* TO 'piatmove_app'@'localhost';
FLUSH PRIVILEGES;
```
Store credentials in environment variables, not PHP files.

---

### H-3 — Race Condition on Booking Acceptance (TOCTOU)
**File:** `piatmove-api/routes/driver.php`, lines ~41–48

```php
// SELECT first, then UPDATE separately — two drivers can both pass the SELECT
$stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
if (!$stmt->fetch()) json_error('Not available', 404);

$db->prepare("UPDATE bookings SET status='accepted', driver_id=? WHERE id=?")->execute([$user['id'], $id]);
```

Two drivers can simultaneously pass the SELECT check and both update the row, with the second silently overwriting the first driver assignment.

**Fix — single atomic UPDATE, check affected rows:**
```php
$stmt = $db->prepare(
    "UPDATE bookings SET status='accepted', driver_id=? WHERE id=? AND status='pending'"
);
$stmt->execute([$user['id'], $id]);
if ($stmt->rowCount() === 0) {
    json_error('Booking not found or no longer available', 404);
}
json_success(null, 'Ride accepted');
```

---

### H-4 — No Brute-Force Protection on Login Endpoints
**Files:** `piatmove-api/routes/auth.php`, `piatmove-api/routes/admin.php`, `PiatMoveAdmin/index.php`

All three login endpoints accept unlimited credential attempts with no delay, lockout, or captcha. The admin JWT endpoint is especially sensitive.

**Minimum fix:** Track failed attempts by IP in a `login_attempts` table and lock out after 5 failures within 10 minutes. For the admin panel, consider IP allowlisting.

---

### H-5 — No Lat/Lng Validation on Driver Location Update
**File:** `piatmove-api/routes/driver.php`, lines ~81–88

Any non-null value is accepted for `lat`/`lng`. Garbage coordinates would break any map-rendering feature downstream.

**Fix:**
```php
$lat = filter_var($body['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($body['lng'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    json_error('Valid lat (-90 to 90) and lng (-180 to 180) are required');
}
```

---

## Medium

### M-1 — Wildcard CORS Header
**File:** `piatmove-api/index.php`

```php
header('Access-Control-Allow-Origin: *');
```

Any domain can make cross-origin requests. Low risk for a mobile-JWT API, but complicates future cookie-based auth and exposes unauthenticated endpoints to cross-origin abuse. Document this as intentional or restrict to known origins.

---

### M-2 — No try/catch Around DB Writes
**File:** `piatmove-api/routes/bookings.php` and others

Uncaught `PDOException` on INSERT failure outputs raw stack traces to API clients, leaking table names, column names, and file paths.

**Fix:**
```php
try {
    $stmt = $db->prepare('INSERT INTO bookings ...');
    $stmt->execute([...]);
    json_success(['booking_id' => (int)$db->lastInsertId()], 'Booking created', 201);
} catch (PDOException $e) {
    json_error('Could not create booking. Please try again.', 500);
}
```
Apply to all DB writes that currently lack error handling.

---

### M-3 — No Minimum Password Length
**File:** `piatmove-api/routes/auth.php`

Only checks `!$pass` (not empty). A 1-character password is accepted.

**Fix:**
```php
if (strlen($pass) < 8) {
    json_error('Password must be at least 8 characters');
}
```

---

### M-4 — Phone Number Not Validated
**File:** `piatmove-api/routes/auth.php`

Any non-empty string passes as a phone number.

**Fix:**
```php
if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $phone)) {
    json_error('Invalid phone number format');
}
```

---

### M-5 — XSS Risk in `status_badge()` Helper
**File:** `PiatMoveAdmin/dashboard.php`, also used in `bookings.php`

```php
return "<span class=\"badge badge-{$cls}\">" . ucfirst($status) . "</span>";
```

Neither `$status` nor `$cls` are escaped. Values come from the database — stored XSS if a status value ever contained HTML.

**Fix:**
```php
return "<span class=\"badge badge-" . htmlspecialchars($cls, ENT_QUOTES) . "\">"
     . htmlspecialchars(ucfirst($status), ENT_QUOTES)
     . "</span>";
```

---

### M-6 — JWT Has No Revocation Mechanism
**File:** `piatmove-api/middleware/auth.php`

Tokens are valid for 7 days with no way to invalidate them. A stolen token gives 7 days of access.

**Minimum fix:** Reduce expiry to 24 hours. For immediate revocation, maintain a `revoked_tokens` table checked on every request.

---

### M-7 — No Pagination on Admin List Queries
**Files:** `PiatMoveAdmin/users.php`, `drivers.php`, `bookings.php`

All pages call `fetchAll()` with no `LIMIT`. On a production DB with thousands of rows, this will cause memory exhaustion.

**Fix:** Add `LIMIT ? OFFSET ?` with page controls to all three pages.

---

### M-8 — No Global Exception Handler in the API
**File:** `piatmove-api/index.php`

Any uncaught exception produces a PHP HTML error page instead of JSON, breaking API clients and potentially leaking details.

**Fix (add to top of `index.php`):**
```php
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Internal server error']);
    exit;
});
```

---

## Low

### L-1 — Dead `$msg_type` Variable
**File:** `PiatMoveAdmin/users.php`, lines 8 and 60

`$msg_type = 'success'` is initialized but the alert class is hardcoded — the variable is never read.

**Fix:** Wire it up: `<div class="alert-ds alert-<?= htmlspecialchars($msg_type) ?>">` or remove the variable.

---

### L-2 — Duplicate `require_auth` / `require_admin` Logic
**File:** `piatmove-api/middleware/auth.php`

Both functions are near-identical — extract to a shared `_require_token(string $type)` helper.

---

### L-3 — No Admin Audit Trail
**File:** `piatmove-api/routes/admin.php`

`require_admin()` return value is discarded. No record of which admin approved a driver, deleted a user, or changed account status.

**Fix:** Capture the return value and log admin ID alongside each operation.

---

### L-4 — `BASE_URL` Uses HTTP
**File:** `PiatMoveAdmin/config.php`

```php
define('BASE_URL', 'http://localhost/PiatMoveAdmin');
```

Must be changed to HTTPS before any deployment. Also add `Secure` + `HttpOnly` session cookie flags.

---

### L-5 — Logout Doesn't Clear Session Cookie
**File:** `PiatMoveAdmin/logout.php`

`session_destroy()` alone doesn't clear `$_SESSION` or expire the browser cookie.

**Fix:**
```php
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
session_destroy();
```

---

### L-6 — No Environment Separation
**Files:** Both `config.php` files

All config is hardcoded. No dev/staging/production separation. Use a `.env` file (excluded from git) and `getenv()` / `$_ENV`.

---

## What's Done Well

| Item | Location |
|---|---|
| All API queries use PDO prepared statements — no interpolation | `routes/*.php` |
| JWT `hash_equals` for timing-safe comparison | `middleware/auth.php` |
| `PDO::ATTR_EMULATE_PREPARES => false` — true binary prepared statements | Both `config.php` |
| `password_hash` / `password_verify` used correctly | `routes/auth.php` |
| `filter_var($email, FILTER_VALIDATE_EMAIL)` on registration | `routes/auth.php` |
| `in_array($role, [...], true)` strict comparison | `routes/auth.php` |
| Role-based authorization enforced on all driver/passenger routes | `routes/driver.php`, `passenger.php` |
| Booking ownership verified before allowing passenger access | `routes/bookings.php` |
| Driver approval status checked before any ride actions | `routes/driver.php` |
| Correct HTTP status codes throughout (201, 401, 403, 404, 409) | `routes/*.php` |
| `.htaccess` correctly passes `Authorization` header through Apache | `.htaccess` |
| PDO singleton avoids redundant connections | `config/database.php` |
| `PDO::ERRMODE_EXCEPTION` set — failures throw, don't silently fail | Both `config.php` |
| `beginTransaction` / `commit` / `rollBack` used correctly in registration | `routes/auth.php` |
| `htmlspecialchars()` consistently applied to all table output | All admin pages |
| Session guard present at top of every protected admin page | `includes/auth.php` |
| Admin credentials in separate `admins` table, not mixed with users | `routes/admin.php` |
| `session_status()` check prevents double session start | `includes/auth.php` |
| Login redirects already-authenticated admins to dashboard | `PiatMoveAdmin/index.php` |

---

## Route Coverage

All 24 observable API endpoints are implemented. No dead route files exist.

| Endpoint | Status |
|---|---|
| POST /auth/register | ✅ |
| POST /auth/login | ✅ |
| POST /admin/login | ✅ |
| GET /admin/users | ✅ |
| GET /admin/drivers/pending | ✅ |
| GET /admin/bookings | ✅ |
| PUT /admin/driver/approve/{id} | ✅ |
| PUT /admin/driver/reject/{id} | ✅ |
| PUT /admin/user/activate/{id} | ✅ |
| PUT /admin/user/deactivate/{id} | ✅ |
| DELETE /admin/user/{id} | ✅ |
| POST /bookings | ✅ |
| GET /bookings | ✅ |
| GET /bookings/{id} | ✅ |
| POST /bookings/{id}/cancel | ✅ |
| GET /driver/requests | ✅ |
| POST /driver/accept/{id} | ✅ |
| POST /driver/reject/{id} | ✅ |
| POST /driver/start/{id} | ✅ |
| POST /driver/complete/{id} | ✅ |
| PUT /driver/location | ✅ |
| PUT /driver/status | ✅ |
| GET /passenger/history | ✅ |
| PUT /user/fcm-token | ✅ |

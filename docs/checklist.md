# PiatMove — Build Checklist

> Last updated: 2026-06-26

---

## Step 1 — Database

- [x] Create `piatmove` database in phpMyAdmin
- [x] Write SQL schema (`docs/database.sql`)
- [x] Run SQL in phpMyAdmin — all 5 tables created
  - [x] `users`
  - [x] `driver_info`
  - [x] `bookings`
  - [x] `fcm_tokens`
  - [x] `admins`

---

## Step 2 — PHP REST API (`piatmove-api/`)

### Foundation

- [x] `.htaccess` — URL rewriting to index.php
- [x] `config/database.php` — PDO connection + JWT config
- [x] `helpers/response.php` — `json_success()` / `json_error()` / `get_body()`
- [x] `middleware/auth.php` — JWT create/parse, `require_auth()`, `require_role()`, `require_admin()`
- [x] `index.php` — router entry point

### Routes

- [x] `routes/auth.php` — `POST /auth/register`, `POST /auth/login`
- [x] `routes/bookings.php` — `POST /bookings`, `GET /bookings`, `GET /bookings/{id}`, `POST /bookings/{id}/cancel`
- [x] `routes/driver.php` — accept, reject, start, complete, location, status
- [x] `routes/passenger.php` — `GET /passenger/history`
- [x] `routes/user.php` — `PUT /user/fcm-token`
- [x] `routes/admin.php` — all admin endpoints + `POST /admin/login`

### Testing

- [x] Test register passenger
- [x] Test register driver
- [x] Test login
- [x] Test booking create → accept → start → complete flow
- [x] Test passenger cancel
- [x] Test admin login + approve driver

---

## Step 3 — PHP Admin Panel (`PiatMoveAdmin/`)

### Foundation

- [x] `config.php` — DB credentials, base URL
- [x] `includes/db.php` — PDO connection
- [x] `includes/auth.php` — session guard
- [x] `includes/header.php` + `footer.php`
- [x] `logout.php`

### Pages

- [x] `index.php` — admin login page
- [x] `dashboard.php` — overview stats
- [x] `users.php` — list users, search, activate/deactivate/delete
- [x] `drivers.php` — approve / reject / revoke drivers, filter by status
- [x] `bookings.php` — all bookings with status filter tabs

### Assets

- [x] `assets/css/admin.css`
- [x] `assets/js/admin.js`

### Testing

- [x] Login at http://localhost/PiatMoveAdmin/
- [x] Dashboard loads with correct counts
- [x] Approve / reject driver from Drivers page
- [x] Activate / deactivate user from Users page
- [x] Filter bookings by status

---

## Legend

| Symbol | Meaning     |
| ------ | ----------- |
| `[x]`  | Done        |
| `[ ]`  | Not started |

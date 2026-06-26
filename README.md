# PiatMove API

PHP REST API backend for the PiatMove ride-booking app. Serves both the Android mobile apps and the web admin panel.

---

## Project Structure

```
xampp/htdocs/
├── piatmove-api/                   ← This repo — PHP REST API
│   ├── config/
│   │   └── database.php            ← DB credentials & PDO connection
│   ├── middleware/
│   │   └── auth.php                ← JWT verification middleware
│   ├── routes/
│   │   ├── auth.php                ← POST /auth/register, POST /auth/login
│   │   ├── bookings.php            ← Booking CRUD
│   │   ├── driver.php              ← Driver actions (accept, reject, start, complete, location)
│   │   ├── passenger.php           ← Passenger history & cancel
│   │   ├── user.php                ← FCM token update
│   │   └── admin.php               ← Admin-only endpoints
│   ├── helpers/
│   │   └── response.php            ← json_success() / json_error() helpers
│   ├── docs/
│   │   ├── Dev_Guide.md            ← Development plan and build order
│   │   ├── checklist.md            ← Progress tracker
│   │   └── database.sql            ← Full DB schema (run in phpMyAdmin)
│   └── index.php                   ← Router entry point
│
└── PiatMoveAdmin/                  ← Separate repo — PHP web admin panel
    ├── config.php
    ├── index.php                   ← Admin login
    ├── dashboard.php
    ├── users.php
    ├── drivers.php
    ├── bookings.php
    ├── includes/
    │   ├── auth.php                ← Session guard
    │   ├── db.php                  ← PDO connection
    │   ├── header.php
    │   └── footer.php
    └── assets/
        ├── css/admin.css
        └── js/admin.js
```

---

## API Endpoints

All responses follow the shape:
```json
{ "success": true, "data": {}, "message": "..." }
```

Auth uses **JWT Bearer tokens** in the `Authorization` header.

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | public | Register passenger or driver |
| POST | `/auth/login` | public | Login, returns JWT |
| POST | `/bookings` | passenger | Create a ride request |
| GET | `/bookings` | any | List bookings (role-filtered) |
| GET | `/bookings/{id}` | any | Get a single booking |
| GET | `/passenger/history` | passenger | Ride history |
| POST | `/bookings/{id}/cancel` | passenger | Cancel a ride |
| GET | `/driver/requests` | driver | See pending requests |
| POST | `/driver/accept/{id}` | driver | Accept a ride |
| POST | `/driver/reject/{id}` | driver | Reject a ride |
| POST | `/driver/start/{id}` | driver | Start a ride |
| POST | `/driver/complete/{id}` | driver | Complete a ride |
| PUT | `/driver/location` | driver | Update GPS coordinates |
| PUT | `/driver/status` | driver | Toggle online/offline |
| PUT | `/user/fcm-token` | any | Register FCM push token |
| GET | `/admin/users` | admin | List all users |
| GET | `/admin/drivers/pending` | admin | Pending driver approvals |
| GET | `/admin/bookings` | admin | All bookings |
| PUT | `/admin/driver/approve/{id}` | admin | Approve a driver |
| PUT | `/admin/driver/reject/{id}` | admin | Reject a driver |
| PUT | `/admin/user/activate/{id}` | admin | Activate a user |
| PUT | `/admin/user/deactivate/{id}` | admin | Deactivate a user |
| DELETE | `/admin/user/{id}` | admin | Delete a user |

---

## Database

5 tables — see `docs/database.sql` for the full schema.

| Table | Purpose |
|-------|---------|
| `users` | Passengers and drivers |
| `driver_info` | License, vehicle, approval status, GPS |
| `bookings` | Full ride lifecycle |
| `fcm_tokens` | Firebase push notification tokens |
| `admins` | Web admin panel accounts |

---

## Tech Stack

- **Backend:** PHP 8+ (no framework)
- **Database:** MySQL 8 via XAMPP
- **Auth:** JWT (Android apps) / Sessions (admin panel)
- **Server:** Apache via XAMPP (local dev)

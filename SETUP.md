# VinSync — Setup & Deployment Guide

## What This Project Does

VinSync pulls vehicle inventory from the Classic Arlington dealership API twice daily (6:00 AM and 6:00 PM) and stores it in a local database. The inventory page displays all vehicles with filtering, sorting, and pagination.

- New vehicles added to the API → automatically inserted
- Vehicles sold/removed from the API → automatically deleted
- Prices, mileage, images updated → automatically updated

---

## Requirements

- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- A server-level cron job (one line, set once)

---

## Local Setup (Laragon / Dev)

### 1. Install dependencies

```bash
composer install
```

### 2. Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure database in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vinsync_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Run first sync

```bash
php artisan sync:vehicles
```

This fetches all ~320 vehicles from the API and populates the database. Takes about 2–3 minutes.

### 6. Start local server

```bash
php artisan serve
```

Visit: `http://localhost:8000/inventory`

---

## Production Setup (Live Server)

### 1. Upload files

Upload all project files to your server (excluding `.env`, `node_modules`, `vendor`).

### 2. Install dependencies on server

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Configure `.env` on server

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SYNC_TOKEN=change-this-to-something-secret
```

### 4. Run migrations

```bash
php artisan migrate --force
```

### 5. Cache config and routes

```bash
php artisan config:cache
php artisan route:cache
```

### 6. Run first sync

```bash
php artisan sync:vehicles
```

### 7. Set up the cron job (one time only)

Add this single line to your server's crontab (`crontab -e`):

```
* * * * * cd /full/path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/full/path/to/your/project` with your actual project path (e.g. `/var/www/vinsync`).

That's it. Laravel handles the scheduling internally — syncs run at 6:00 AM and 6:00 PM daily.

To verify cron is working, check the logs after 6am/6pm:

```bash
tail -50 storage/logs/laravel.log
```

You should see entries like:

```
VehicleSync [new]: fetching page 1 of 27…
VehicleSync complete: fetched=640, inserted=0, updated=320, deleted=0
```

---

## Artisan Commands

| Command                                | Description                                                     |
| -------------------------------------- | --------------------------------------------------------------- |
| `php artisan sync:vehicles`            | Full sync — fetches all vehicles, updates DB, deletes sold ones |
| `php artisan sync:vehicles --limit=12` | Test sync — fetches first 12 vehicles only, no deletions        |

---

## Client Demo — Proving All Three Operations Work

If the database is already fully synced, hitting `/sync-now` will show `inserted=0, deleted=0` — which looks like nothing happened. Use the two-step demo flow below to show all three operations clearly.

### Step 1 — Prepare the demo state

Hit this URL first:

```
https://yourdomain.com/demo-setup?token=YOUR_SYNC_TOKEN
```

This deliberately breaks the database in three ways:

- Inserts 3 **fake VINs** that don't exist in the API
- **Deletes** 3 real vehicles from the database
- **Corrupts** the price of 3 real vehicles (sets them to $999,999)

Example response:

```json
{
    "status": "demo ready",
    "message": "Now hit /sync-now?token=... to see all three operations.",
    "setup": {
        "fake_vins_inserted": [
            "DEMO-FAKE-VIN-001",
            "DEMO-FAKE-VIN-002",
            "DEMO-FAKE-VIN-003"
        ],
        "real_vins_deleted": [
            "1HGCM82633A123456",
            "2T1BURHE0JC123456",
            "3VWFE21C04M123456"
        ],
        "prices_corrupted": [
            "5NPEB4AC8BH123456",
            "1N4AL3AP2JC123456",
            "JM1BK32F781123456"
        ]
    }
}
```

### Step 2 — Run the sync

```
https://yourdomain.com/sync-now?token=YOUR_SYNC_TOKEN
```

Example response:

```json
{
    "status": "ok",
    "time_seconds": 145.3,
    "vehicles_before": 320,
    "vehicles_after": 320,
    "inserted": 3,
    "updated": 3,
    "deleted": 3,
    "errors": []
}
```

### What to point out to the client

| Field                                | What it proves                                                                      |
| ------------------------------------ | ----------------------------------------------------------------------------------- |
| `inserted: 3`                        | The 3 deleted vehicles were re-added — **new cars from API get added**              |
| `updated: 3`                         | The 3 corrupted prices were corrected — **price/data changes from API get applied** |
| `deleted: 3`                         | The 3 fake VINs were removed — **sold cars get removed**                            |
| `vehicles_before` = `vehicles_after` | Total count is back to normal — database is in perfect sync                         |
| `errors: []`                         | Everything worked cleanly                                                           |

---

## How the Sync Works (CRUD)

| Scenario                         | What Happens                                     |
| -------------------------------- | ------------------------------------------------ |
| New car added to API             | Inserted into `vehicles` table                   |
| Car price/mileage changed in API | Existing row updated (`updateOrCreate` on VIN)   |
| Car sold / removed from API      | Row deleted (`whereNotIn` on all collected VINs) |

The VIN (Vehicle Identification Number) is the unique key. Every vehicle has a unique VIN, so it is used to match API records to database rows.

---

## Troubleshooting

**Sync fetches only 12 records (pagination broken)**
The API uses non-standard parameter names — `pt` is the page number and `pn` is the page size.
These names are backwards from what you'd expect and were found by reading the dealership's minified JS bundle.
If the API ever stops paginating, check `storage/logs/laravel.log` first:

```
VehicleSync [new]: fetching page 1…
VehicleSync [new]: fetching page 2 of 27…   ← if this never appears, pt/pn broke
```

The parameters are set in `VehicleSyncService::fetchPage()`. Do not rename or swap them.

**Images not showing**
Images are stored as JSON arrays in the `images` column. Check one record:

```sql
SELECT vin, images FROM vehicles LIMIT 1;
```

Should look like `["https://www.classicarlington.com/inventoryphotos/..."]`. If it's `[]`, re-run `php artisan sync:vehicles`.

**Sync route returns 403**
Wrong or missing token. Check `SYNC_TOKEN` in `.env` matches what you pass in the URL.

**Price sorting broken**
Sorting uses `ISNULL(COALESCE(sale_price, msrp))` which is MySQL-specific. Do not switch to PostgreSQL without updating the sort queries in `InventoryController.php`.

---

## File Structure (key files only)

```
app/
  Console/
    Kernel.php                  ← cron schedule defined here (runs at 6am & 6pm)
    Commands/
      SyncVehicles.php          ← artisan sync:vehicles command
  Http/Controllers/
    InventoryController.php     ← inventory listing page with filters
    SyncController.php          ← /sync-now endpoint for manual/demo triggers
  Models/
    Vehicle.php                 ← vehicle model with casts and filter scopes
  Services/
    VehicleSyncService.php      ← all API fetching, pagination, parsing, DB upsert

resources/views/inventory/
  index.blade.php               ← inventory listing UI

routes/
  web.php                       ← routes: /, /inventory, /sync-now, /demo-setup

database/migrations/
  2024_01_01_000001_create_vehicles_table.php
```

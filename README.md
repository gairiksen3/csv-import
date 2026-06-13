# CSV → Shopify Product Importer

A Laravel application that lets users upload a Shopify‑format product CSV, imports the
rows into a local MySQL database, and **syncs each product to a Shopify store via the
GraphQL Admin API**. Products are matched by **Variant SKU** — existing products are
updated, new ones are created. Every product shows a live sync status
(`pending` → `processing` → `successful` / `failed`), and any import or Shopify error is
visible in the UI.

---

## Features

- CSV upload with validation and background processing (queued jobs).
- Local DB import with **SKU‑based upsert** (create or update).
- Shopify create/update over the **GraphQL Admin API**, plus add‑to‑collection.
- Per‑product Shopify sync status column with **real‑time updates** (no page refresh).
- Error details in the product modal + a dedicated **Shopify API error log** with Retry.
- Import history with viewable row‑level error logs.
- **Comprehensive logging** of all import + Shopify sync events to a dedicated log channel.
- **In‑dashboard Log Viewer** (admin) with level filtering.
- **Error notification system** — failures notify the user via a topbar bell.
- Role‑based dashboard (admin sees all products; users see their own).

---

## Requirements

| Tool        | Version                              |
| ----------- | ------------------------------------ |
| PHP         | **8.2** or higher                    |
| Laravel     | **12.x** (installed via Composer)    |
| Composer    | 2.x                                  |
| MySQL       | 5.7+ / 8.x (MariaDB via XAMPP works) |
| Node.js     | 18+ (with npm) — for building assets |

Recommended local stack: **XAMPP** (Apache + MySQL) on Windows, or any PHP 8.2 + MySQL setup.

Required PHP extensions (enabled by default in XAMPP): `pdo_mysql`, `mbstring`, `openssl`,
`fileinfo`, `ctype`, `json`, `curl`.

---

## Composer / Laravel packages

These are installed automatically by `composer install` — listed here for reference.

**Runtime (`require`)**

- `laravel/framework` `^12.0` — the framework
- `laravel/tinker` `^2.10` — REPL / artisan tinker

**Development (`require-dev`)**

- `fakerphp/faker` — fake data for the seeder
- `laravel/pail` — live log viewer
- `laravel/pint` — code style fixer
- `laravel/sail` — Docker dev environment (optional)
- `mockery/mockery`, `phpunit/phpunit`, `nunomaduro/collision` — testing

**Front‑end (`npm`, dev dependencies)**

- `vite`, `laravel-vite-plugin`, `@tailwindcss/vite`, `tailwindcss`, `axios`, `concurrently`

> Note: the dashboard/product UI uses **Bootstrap 5 via CDN**, so the core import features
> work even without building front‑end assets. Vite/Tailwind are only needed for the default
> landing page assets.

---

## What is NOT in the repository

The following are git‑ignored (see `.gitignore`) and are created/installed during setup:

- `.env` — environment config (contains secrets). **Request the real file from the
  maintainer via email.** A blank‑value template is provided as `.env.example`.
- `/vendor` — Composer dependencies → restored by `composer install`.
- `/node_modules` — npm dependencies → restored by `npm install`.
- `/public/build`, `/public/hot` — compiled assets → created by `npm run build`.
- `*.log`, caches, IDE folders.

---

## Local setup — step by step

### 1. Clone the repository

```bash
git clone https://github.com/<your-username>/<your-repo>.git csv_import
cd csv_import
```

> On XAMPP, clone into `C:\xampp\htdocs\csv_import` (or wherever your web root is).

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

```bash
cp .env.example .env
```

Then open `.env` and fill in the values. **The maintainer will email you the real `.env`**
— you can use that directly. If setting up manually, you must provide:

- **App key** — generated in the next step (leave `APP_KEY=` blank for now).
- **Database** — `DB_DATABASE=csv_import`, `DB_USERNAME=root`, `DB_PASSWORD=` (default XAMPP).
- **Shopify** — `SHOPIFY_STORE_DOMAIN`, `SHOPIFY_ACCESS_TOKEN`, `SHOPIFY_COLLECTION_ID`
  (provided privately by the maintainer).

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Create the database

Create an empty MySQL database named `csv_import` (matching `DB_DATABASE`):

- Via phpMyAdmin (XAMPP): create a new database `csv_import`, or
- Via CLI:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS csv_import CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6. Run migrations (and seed default users)

```bash
php artisan migrate --seed
```

This creates all tables (users, products, csv_imports, jobs, failed_jobs, …) and seeds two
login accounts:

| Role  | Email               | Password   |
| ----- | ------------------- | ---------- |
| Admin | `admin@example.com` | `12345678` |
| User  | `test@example.com`  | (random — reset or register a new user) |

> You can also register a new user from the `/register` page (new users get the `user` role).

### 7. Install & build front‑end assets

```bash
npm install
npm run build
```

### 8. Run the application

You need **two processes**: the web server and the **queue worker** (the import + Shopify
sync run as background jobs, so the worker is required — without it, uploads stay stuck at
`pending`).

**Terminal 1 — web server:**

```bash
php artisan serve
```

App available at <http://127.0.0.1:8000>.
(If using XAMPP/Apache instead, point a vhost at the `public/` folder.)

**Terminal 2 — queue worker (required):**

```bash
php artisan queue:work --tries=3 --timeout=180
```

> Keep this running while importing. If it stops, restart it.
> After changing code, restart the worker (`php artisan queue:restart`) — workers hold code
> in memory.

#### One‑command alternative (server + queue + logs + vite)

```bash
composer dev
```

This runs `php artisan serve`, `php artisan queue:listen`, `php artisan pail` (logs) and
`npm run dev` together via `concurrently`.

---

## Using the app

1. Log in (e.g. `admin@example.com` / `12345678`).
2. Go to **Import CSV**, download the sample CSV (in `public/shopifyproduct/`) for the
   expected format, then upload a CSV.
3. The upload returns immediately; the queue worker parses rows into the DB and syncs each
   product to Shopify.
4. Open **Products** / **My Products** to watch the **Shopify** status column update
   **in real time** (`pending` → `processing` → `successful` / `failed`) — the page polls
   for status changes automatically and stops once everything is synced (no refresh needed).
   Click **View** on a product to see full details and any Shopify error.
5. **Import History** (on the upload page) shows past imports; click **View Errors** to see
   row‑level import errors.
6. **Shopify Errors** (sidebar) lists every product that failed to sync, with the exact
   GraphQL API error and a **Retry** button to re‑queue the sync.

Required CSV columns (Shopify format): `Handle, Title, Vendor, Product Type, Variant SKU,
Variant Price, Variant Inventory Qty` (plus the other optional Shopify columns).

---

## Logging, notifications & where to find the activity log

**Where to find the activity log:**

- **In the dashboard (recommended):** log in as an **admin** and open the **Logs** item in the
  left sidebar (`/dashboard/logs`). This Log Viewer shows every import and Shopify sync event,
  newest first, with **Info / Warning / Error** filter buttons, a **Refresh**, and a
  **Clear Log** button.
- **On disk:** the same events are written to `storage/logs/import.log` (a dedicated `import`
  log channel configured in `config/logging.php`). General framework errors stay in
  `storage/logs/laravel.log`.

**What gets logged** (channel: `import`):

- Import started / completed (with total, imported, failed counts)
- Each failed CSV row (row number + reason)
- Each Shopify sync result — success (with Shopify product id) or failure (with the GraphQL error)
- Shopify batch sync start/finish summary

**Error notification system:**

A 🔔 **bell** in the top bar shows unread notifications. Users are notified when:

- an import completes with failed rows, or fails entirely, or
- one or more products fail to sync to Shopify.

Click a notification to jump to the relevant page; use **Mark all read** to clear the count.
Notifications are stored in the `notifications` table (Laravel database notifications).

---

## Shopify configuration

Set in `.env` (read via `config/services.php`):

```env
SHOPIFY_STORE_DOMAIN=my-shop.myshopify.com   # domain only, no https:// or trailing slash
SHOPIFY_ACCESS_TOKEN=shpat_xxxxxxxxxxxxxxxx   # Admin API access token
SHOPIFY_COLLECTION_ID=464337174767            # numeric collection id
SHOPIFY_API_VERSION=2024-10
```

After editing `.env`, clear the config cache:

```bash
php artisan config:clear
```

If `SHOPIFY_STORE_DOMAIN` / `SHOPIFY_ACCESS_TOKEN` are missing, products import to the DB
but every Shopify sync is marked `failed` with a clear message.

---

## Useful commands

```bash
php artisan queue:work --tries=3 --timeout=180   # process background jobs
php artisan queue:failed                         # list jobs that failed after retries
php artisan queue:retry all                      # re-queue failed jobs
php artisan queue:restart                        # restart workers after deploying code
php artisan migrate:fresh --seed                 # rebuild DB from scratch (destroys data)
php artisan config:clear                         # refresh config after .env changes
```

---

## Troubleshooting

- **Imports stuck on `pending` / `processing`** → the queue worker isn’t running. Start
  `php artisan queue:work`.
- **All products show `failed`** → Shopify credentials missing/invalid in `.env`; check
  `SHOPIFY_STORE_DOMAIN` and `SHOPIFY_ACCESS_TOKEN`, then `php artisan config:clear`.
- **`419 / CSRF` or session errors** → run `php artisan key:generate` and ensure the DB is
  migrated (sessions use the `database` driver).
- **DB connection errors** → confirm MySQL is running and `csv_import` database exists.

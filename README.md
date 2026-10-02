# AssetKOM

A web-based asset management system built on Laravel 13 + SQLite (dev) / MySQL (production),
Livewire 3 + Volt + Tailwind CSS.

## Features

- **Asset registry** — assets with categories, locations, photos, JSON custom fields and
  auto-generated asset tags.
- **Assignments** — transactional check-out / check-in to people or locations, with full history.
- **Maintenance** — scheduled / in-progress / completed records with cost tracking; drives the
  asset status lifecycle.
- **Attachments** — polymorphic document uploads stored on a private disk with authorized downloads.
- **QR labels** — scannable asset QR codes, single and bulk printable label PDFs, auth-only
  scan-to-view.
- **Depreciation** — straight-line book value and yearly schedule.
- **Dashboard & reports** — estate overview, alerts, per-currency summaries and CSV exports.
- **Audit log** — activity trail on assets, assignments and maintenance.
- **Daily alerts** — queued email digests for warranty expiry, maintenance due and overdue
  assignments, with per-user preferences.
- **CSV import** — column mapping, dry-run validation and idempotent upserts by asset tag.

## Roles

| Capability | Admin | Manager | Staff |
|---|---|---|---|
| Manage users | ✅ | — | — |
| Categories / locations / settings | ✅ | ✅ | — |
| Create / edit assets | ✅ | ✅ | — |
| Delete assets | ✅ | — | — |
| Check-out / in, maintenance, attachments | ✅ | ✅ | ✅ |
| View assets | ✅ | ✅ | ✅ |
| Reports, exports, audit log, import | ✅ | ✅ | — |

## Requirements

- PHP 8.4, Composer
- Node 20+ / npm
- SQLite (development) or MySQL (production)

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

## Running

```sh
php artisan serve          # http://127.0.0.1:9090 (port via APP_PORT)
composer dev               # server + queue + logs + Vite
```

Demo accounts (password `password`):

| Email | Role |
|---|---|
| `admin@assetkom.test` | Administrator |
| `manager@assetkom.test` | Manager |
| `staff@assetkom.test` | Staff |

## Production notes

- Set `DB_CONNECTION=mysql` with connection credentials.
- **Create the first administrator explicitly** — public registration is disabled:
  `php artisan admin:create admin@yourorg.com` (prompts for a password).
- Demo seeders (users/catalog/assets/assignments/maintenance) only run in `local`/`testing`;
  production seeding applies settings only.
- All pages require a **verified** account; unverified users are redirected to verification.
- Configure SMTP: `MAIL_MAILER=smtp` plus host/port/username/password.
- Run a queue worker (`php artisan queue:work`) under a supervisor for alert emails.
- Schedule `php artisan schedule:run` every minute (daily alerts run at 08:00).

## Common commands

```sh
php artisan test           # test suite
./vendor/bin/pint          # format PHP
npm run build              # build frontend assets
php artisan alerts:send    # queue the daily alert digests now
```

## Conventions

- Run `./vendor/bin/pint` before committing PHP changes.
- Keep the SQLite database local; do not commit `database/*.sqlite` or `.env`.
- Commit and push to GitHub (`origin main`) at the end of each implementation phase.

See [`docs/PLAN.md`](docs/PLAN.md) for the phased roadmap and design decisions.

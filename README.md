# AssetKOM

> Every company starts with a spreadsheet. Then the spreadsheet starts lying to you.

## The story behind this project

AssetKOM was built **purely as an AI-assisted development experiment** — could an AI agent and a
human, working together, take a real business application from an empty folder to a finished,
tested, production-hardened system?

The answer, after fourteen implementation phases of planning, coding, reviewing, and hardening, is
yes. Every feature here — from the asset registry to QR labels, CSV imports, deprecation
schedules, and the daily alert digests — was designed and built with AI pair development
(Laravel Boost, Livewire Volt, and agent-guided testing), then reviewed by a human before
shipping. The repo itself is the proof of what that workflow can produce.

## Who this is for

Small and medium enterprises that have quietly started to **accumulate assets** — laptops,
monitors, printers, tools, vehicles, furniture, network gear, anything with a serial number — and
are still tracking them in Excel.

You know how it goes: one sheet for IT equipment, another for office furniture, a tab nobody
remembers exists, and nobody knows who has the projector. AssetKOM gives you one honest place to
track it all:

- You always know **who has what, and where it is** (transactional check-out / check-in with
  full history).
- **Scannable QR / barcode labels** mean physical inventory stops being a two-day nightmare.
- **Warranty and maintenance alerts** arrive in your inbox daily instead of after the damage.
- **Book value and depreciation** give finance real numbers, not guesses.
- **CSV import** means migrating off that spreadsheet takes an afternoon, not a quarter.

## Features

- **Asset registry** — assets with categories, locations, photos, JSON custom fields and
  auto-generated asset tags.
- **Assignments** — transactional check-out / check-in to people or locations, with full history.
- **Maintenance** — scheduled / in-progress / completed records with cost tracking; drives the
  asset status lifecycle.
- **Attachments** — polymorphic document uploads stored on a private disk with authorized downloads.
- **Labels** — printable 1D barcode + asset-tag labels (single and bulk PDFs), with optional
  QR codes, plus a scan/type **code lookup** to jump straight to an asset.
- **Depreciation** — straight-line (configurable method) book value and yearly schedule.
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

## Tech stack

Laravel 13 · PHP 8.4 · Livewire 3 + Volt · Tailwind CSS + daisyUI · Vite · SQLite (dev) / MySQL (prod) ·
PHPUnit test suite · dompdf, picqer/php-barcode-generator, endroid/qr-code, league/csv,
spatie/laravel-activitylog.

## Try it locally

**Requirements:** PHP 8.4 (with `pdo_sqlite`, `mbstring`, `gd`), Composer, Node 20+ / npm.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve          # http://localhost:9090 (port via APP_PORT)
```

Or run everything at once (server + queue + logs + Vite): `composer dev`.

Demo accounts (password `password`), seeded only in `local`/`testing`:

| Email | Role |
|---|---|
| `admin@assetkom.test` | Administrator |
| `manager@assetkom.test` | Manager |
| `nurul@assetkom.test` | Staff |

Additional staff accounts (`weiming@`, `arun@`, `siti@`, `daniel@`, `farah@assetkom.test`) come
with the demo dataset.

## Going live

Everything you need to run AssetKOM for a real organization:

### Server requirements

- PHP **8.4** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`, `curl`, `xml`
- **MySQL/MariaDB** (migrations are engine-portable; SQLite is for development only)
- **Composer** and **Node 20+** (Node is only needed to build frontend assets — run
  `npm ci && npm run build` at deploy time and ship the `public/build` output)
- A process supervisor (systemd/supervisor) for the **queue worker**
- `cron` access for the **scheduler**
- Write access for PHP to `storage/` and `bootstrap/cache/`

### Setup checklist

1. **Point a domain with HTTPS** in front of `public/` (nginx/Apache); set `APP_URL` accordingly.
2. Configure the environment for production:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://assets.yourorg.com
   DB_CONNECTION=mysql   (+ DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
   ```
   Then `php artisan config:cache route:cache view:cache` after each deploy.
3. `composer install --no-dev --optimize-autoloader` and `php artisan migrate --force`
   (production seeding applies settings only — no demo data).
4. **Create the first administrator explicitly** — public registration is disabled:
   `php artisan admin:create admin@yourorg.com` (prompts for a password). Invite further users
   from the admin's user management screen.
5. **Configure SMTP** so verification and alert emails work: `MAIL_MAILER=smtp` plus
   host/port/username/password. All pages require a **verified** account.
6. **Run a queue worker** under a supervisor (`php artisan queue:work`) — this delivers email
   verification and the daily alert digests.
7. **Add one cron line** (`* * * * * php /path/artisan schedule:run`) — daily alerts run at 08:00.
8. Set `APP_TIMEZONE`, `APP_LOCALE`, default currency and asset-tag prefix in **Settings**.
9. **Back up the database and `storage/app/private`** (attachments live on a private disk).

## Common commands

```sh
php artisan test           # test suite
./vendor/bin/pint          # format PHP
npm run build              # build frontend assets
php artisan alerts:send    # queue the daily alert digests now
```

## Requests, contributions & paid work

The learning experiment is done and the project is open source — use it however you like.
What happens next is up to the community:

- **Feature requests / bug reports:** [open a GitHub issue](../../issues). Tell us about your
  asset mess; the best features here started as "we wish it could…".
- **Pull requests:** welcome. Follow the existing conventions (run `php artisan test` and
  `./vendor/bin/pint` before submitting).
- **Need it adapted for your business?** Custom development, deployment, hosting setup,
  integrations and training can be arranged as **paid work** — reach out via a GitHub issue or
  the maintainer's profile and we'll scope it together.

## Conventions (for contributors)

- Run `./vendor/bin/pint` before committing PHP changes.
- Keep the SQLite database local; do not commit `database/*.sqlite` or `.env`.

## Disclaimer

AssetKOM is provided **as-is**, under the [MIT license](LICENSE), with no warranty of any kind.
It was built as a learning project — review it, test it, and run it responsibly.

## License

[MIT](LICENSE) — use it, fork it, sell it to your own clients. We're not responsible, and that's
the deal.

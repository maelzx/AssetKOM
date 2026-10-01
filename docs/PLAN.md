# AssetKOM — Implementation Plan

Web-based Asset Management system. Laravel 13 + SQLite (dev), Livewire + Blade + Tailwind,
single organization, role-based access (Admin / Manager / Staff).

> Reviewed by tech lead — v2. Changes: added permissions matrix, settings, state machine,
> notifications, CI, hardening; moved reporting earlier; split MVP vs v1.1.

## Decisions

- **Asset domain**: mixed / general purpose — configurable categories + custom fields.
- **UI**: Livewire (Volt) + Blade + Tailwind. Breeze Livewire for auth shell.
- **Roles**: fixed `role` enum on `users` (`admin`, `manager`, `staff`) enforced via Policies/Gates.
- **Tenancy**: single organization.
- **Depreciation**: straight-line (configurable via settings).
- **Import/Export**: CSV.
- **Dev DB**: SQLite. **Prod DB**: MySQL/MariaDB. Migrations must stay portable across
  both engines (no SQLite-only features, no engine-specific raw SQL).
- **Files**: stored on a **private disk**, served through authorized controllers
  (attachments may contain invoices / sensitive docs).
- **Currency**: multi-currency — **MYR (RM)** and **USD**. Each asset carries a currency;
  a base currency and FX rate for consolidated reporting live in settings.
- **Locale / timezone**: timezone `Asia/Kuala_Lumpur` (GMT+8), locale `en_MY`,
  MYR as default display currency.
- **MVP**: locked at Phase 5; QR, depreciation, notifications, import ship in v1.1.
- **QR scan page**: authentication required (no public asset data exposure).

## Resolved decisions

- Production database: MySQL/MariaDB.
- MVP line: Phase 5 (accepted).
- QR scan-to-view: auth-only.
- Currency/locale/timezone: multi-currency MYR+USD, Asia/Kuala_Lumpur, en_MY.

## Permission matrix (proposed)

| Capability | Admin | Manager | Staff |
|---|---|---|---|
| Manage users & roles | ✅ | ❌ | ❌ |
| Manage categories / locations / settings | ✅ | ✅ | ❌ |
| Create / edit / retire assets | ✅ | ✅ | ❌ |
| Delete assets | ✅ | ❌ | ❌ |
| Check-out / check-in assets | ✅ | ✅ | ✅ |
| Log maintenance | ✅ | ✅ | ✅ |
| Upload attachments | ✅ | ✅ | ✅ |
| View all assets | ✅ | ✅ | ✅ (read-only) |
| Reports & export | ✅ | ✅ | ❌ |
| View audit log | ✅ | ✅ | ❌ |

## Packages

- `livewire/livewire`, `livewire/volt`
- `laravel/breeze` (dev, livewire stack)
- `simplesoftwareio/simple-qrcode` (QR codes)
- `barryvdh/laravel-dompdf` (label + report PDFs)
- `league/csv` (import/export)
- `spatie/laravel-activitylog` (audit trail)
- Code128 barcode: `picqer/php-barcode-generator` (only if physical barcode labels required)

## Data Model

| Table | Purpose / key fields |
|---|---|
| `users` | existing + `role` enum, optional `department` |
| `settings` | key/value: org name, default currency, asset-tag prefix, base currency + FX rate, depreciation defaults, label template |
| `categories` | tree (`parent_id`), `name`, `slug`, `description` |
| `locations` | tree (`parent_id`), `name`, `code`, `address` |
| `assets` | `asset_tag` (unique), `name`, `description`, `category_id`, `location_id` (current location), `status`, `condition`, `serial_number`, `manufacturer`, `model`, `purchase_date`, `purchase_cost`, `currency` (MYR/USD), `salvage_value`, `useful_life_years`, `warranty_expiry`, `supplier`, `custom_fields` (JSON), `image_path`, `created_by`, soft deletes |
| `asset_assignments` | polymorphic `assignable` (User or Location), `assigned_by`, `assigned_at`, `expected_return_at`, `returned_at`, `condition_out`, `condition_in`, notes, status |
| `maintenances` | `asset_id`, `type` (preventive/corrective/inspection), `vendor`, `cost`, `scheduled_at`, `completed_at`, `status`, notes |
| `attachments` | polymorphic (`asset`/`maintenance`), private disk path, original name, mime, size, `uploaded_by` |
| `activity_log` | spatie/laravel-activitylog |

Notes:
- `assets.location_id` = current physical location. Assignment-to-location is a **history
  record** and must not be confused with it; check-out to a location updates `location_id`.
- **Asset tag** auto-generated from `settings.prefix` + sequence (overridable by admin).
- **Status state machine** is the single source of truth
  (`available → assigned → available`, `→ maintenance → available`, `→ retired`, `→ lost`),
  implemented once in an `AssetStatus` enum + transition service, reused by all features.
- **Concurrency**: check-out wraps in a DB transaction and enforces one active assignment
  per asset (unique index / lock) to prevent double check-out.

## Phases

### Phase 0 — Foundation & Guardrails (expanded) ✅
- [x] Install packages (Livewire, Volt, Breeze, QR, DOMPDF, CSV, activitylog)
- [x] Breeze Livewire auth + base layout / navigation
- [x] `role` enum on users + roles/permissions matrix + Policies/Gates + seeders
- [x] `settings` table + minimal settings screen (base currency, FX rate, tag prefix)
- [x] App config: timezone `Asia/Kuala_Lumpur`, locale `en_MY`, currency formatter (MYR/USD)
- [x] Asset status enum + transition service
- [x] Factories + demo seeders; private disk config
- [x] CI: GitHub Actions running `php artisan test` + `./vendor/bin/pint` (+ MySQL migration job)
- [x] Test harness + first feature tests

### Phase 1 — Asset Registry ✅
- [x] Category model/CRUD (nested)
- [x] Location model/CRUD (nested)
- [x] Asset model/CRUD with search, filters, pagination, indexes
- [x] Asset tag auto-generation
- [x] Image upload + custom fields (JSON)
- [x] Asset detail page with relationship panels

### Phase 2 — Assignments ✅
- [x] Transaction-safe check-out to user or location
- [x] Check-in with condition/notes
- [x] Assignment history timeline
- [x] Status transitions via state machine

### Phase 3 — Maintenance ✅
- [x] Log preventive/corrective/inspection records
- [x] Schedule + completion workflow, cost tracking
- [x] Status transitions to/from maintenance

### Phase 4 — Attachments ✅
- [x] Polymorphic uploads for assets and maintenances (private disk)
- [x] Authorized download / delete, mime whitelist + size limit

### Phase 5 — Dashboard & Reporting basics ✅
- [x] Dashboard stats (status / category / location, warranty & maintenance due counts)
- [x] CSV export of assets + assignment/maintenance history (asset currency preserved)
- [x] Audit log UI (activity feed)

> **MVP milestone reached here.**

### Phase 6 — QR Labels ✅
- [x] QR generation per asset
- [x] Printable label PDF (single + bulk)
- [x] Scan-to-view page (authentication required)

### Phase 7 — Depreciation ✅
- [x] Straight-line book value calculation
- [x] Depreciation display on asset detail + report

### Phase 8 — Notifications & Alerts (new) ✅
- [x] Warranty expiring soon, maintenance due, overdue assignments
- [x] Scheduler + queued mail; notification preferences

### Phase 9 — CSV Import (v1.1 candidate) ✅
- [x] Column mapping UI + dry-run + per-row error report
- [x] Idempotent by `asset_tag`

### Phase 10 — Hardening & QA
- [ ] Full permission matrix test coverage
- [ ] Responsive/mobile pass (scanning is phone-first)
- [ ] Performance: indexes, N+1 audit, pagination
- [ ] Security review: authorization on every action, rate limiting, public endpoint exposure
- [ ] `./vendor/bin/pint`, full suite green, docs updated

### Phase 11 — UI Polish & Design System
Decisions (locked): **daisyUI 5**, **modern spacious / card-based**, placeholder branding
(wordmark + neutral accent until real brand is supplied). Functionality-first until here.
- [x] Upgrade **Tailwind v3 → v4** (Breeze scaffolded v3; daisyUI 5 requires v4)
- [ ] Install daisyUI 5, define a light theme + dark mode toggle
- [ ] Replace the default Breeze app shell/nav with a branded spacious layout
- [ ] Re-skin assets list/detail, assignments, maintenance, auth screens to daisyUI components
- [ ] Placeholder logo/wordmark + accent color tokens

## Risks / watch items

- **Tailwind v3 → v4 (done)**: upgraded to v4.3 (Breeze pinned v3). Some renamed utilities
  (`shadow-sm`, `rounded-sm`, `focus:outline-none`) shift slightly; to be reconciled during
  the Phase 11 daisyUI reskin. Verify visuals when reskinning.
- **Scope**: "all features in v1" is large; MVP line at Phase 5 protects a usable release.
- **DB portability**: code runs on SQLite (dev) and MySQL (prod) — avoid engine-specific
  SQL; keep a MySQL smoke test in CI.
- **Multi-currency**: MYR + USD stored per asset; consolidated reports need the settings
  FX rate. Decide whether rates are manual or fetched, and how historical reports are valued.
- **Import**: high effort and error-prone — deliberately last.
- **Barcode vs QR**: `simple-qrcode` is QR-only; add Picqer if physical 1D barcodes needed.
- **Email/notifications**: requires working mail transport + queue worker in prod.

## Conventions

- Run `./vendor/bin/pint` before committing PHP changes.
- `php artisan test` for the suite; tests written per-phase, not deferred.
- Keep SQLite local; never commit `database/*.sqlite` or `.env`.

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

## Permission matrix (current)

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
- `endroid/qr-code` (QR codes — GD/PNG; replaced simple-qrcode which required Imagick)
- `barryvdh/laravel-dompdf` (label + report PDFs)
- `league/csv` (import/export)
- `spatie/laravel-activitylog` (audit trail)
- Code128 barcode: `picqer/php-barcode-generator` (only if physical barcode labels required)

## Data Model

| Table | Purpose / key fields |
|---|---|
| `users` | existing + `role` enum + `notify_*` preferences |
| `settings` | key/value: org name, default currency, asset-tag prefix, base currency + FX rate, depreciation defaults, label template |
| `categories` | tree (`parent_id`), `name`, `slug`, `description` |
| `locations` | tree (`parent_id`), `name`, `code`, `address` |
| `assets` | `asset_tag` (unique), `name`, `description`, `category_id`, `location_id` (current location), `status`, `condition`, `serial_number`, `manufacturer`, `model`, `purchase_date`, `purchase_cost`, `currency` (MYR/USD), `salvage_value`, `useful_life_years`, `warranty_expiry`, `supplier`, `custom_fields` (JSON), `image_path`, `created_by`, soft deletes |
| `asset_assignments` | polymorphic `assignable` (User or Location), `assigned_by`, `assigned_at`, `expected_return_at`, `returned_at`, `condition_out`, `condition_in`, notes, status |
| `maintenances` | `asset_id`, `type`, `title`, `description`, `vendor`, `cost`, `currency`, `scheduled_at`, `completed_at`, `performed_by`, `notes`, `created_by`, status |
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

### Phase 10 — Hardening & QA ✅
- [x] Full permission matrix test coverage ([PermissionMatrixTest])
- [x] Responsive/mobile pass (responsive grids, overflow-wrapped tables, mobile nav)
- [x] Performance: query indexes (warranty/status/scheduled), N+1 enforced in tests, pagination
- [x] Security review: authorization on every action, rate limits on export/label/scan/download,
      auth-only scan route and authorized attachment downloads, no unescaped output
- [x] `./vendor/bin/pint`, full suite green (158 tests), README updated

### Phase 11 — UI Polish & Design System ✅
Decisions (locked): **daisyUI 5**, **modern spacious / card-based**, placeholder branding
(wordmark + neutral accent until real brand is supplied). Functionality-first until here.
- [x] Upgrade **Tailwind v3 → v4** (Breeze scaffolded v3; daisyUI 5 requires v4)
- [x] Install daisyUI 5, light theme + dark mode toggle (persisted)
- [x] Replace the default Breeze app shell/nav with a branded spacious layout
- [x] Re-skin assets list/detail, assignments, maintenance, auth screens to daisyUI components
- [x] Placeholder logo/wordmark + accent color tokens (`AssetKOM` wordmark, primary accent)

### Phase 12 — Audit Follow-ups

#### Phase 12a — Security & correctness ✅
- [x] **Prod seeding**: demo seeders gated to `local`/`testing`; `admin:create` command for a
      secure first-admin bootstrap; README production setup documented.
- [x] **Registration posture**: public registration disabled; `verified` added to the
      attachment-download and label/scan routes; `User` implements `MustVerifyEmail`.
- [x] **Attachment authorization**: tested direct download as guest / unverified / staff /
      manager / admin.
- [x] **CSV import fixes**: zero values preserved; soft-deleted tags restored; in-file
      duplicate tags reported; unknown category/location reported (dry-run and import share
      one analysis pass so they agree).
- [x] **Maintenance conflict**: one in-progress record per asset enforced; asset returns to
      `available` only when no in-progress maintenance remains (locks + tests).
- [x] **Status lifecycle enforcement**: create/edit/import status changes routed through
      `AssetStatusTransition`; initial statuses constrained to available/retired/lost.
- [x] **Catalog deletion**: categories/locations with assets or children cannot be deleted.

#### Phase 12b — Features & scope decisions (open)
- [ ] Admin user-and-role management workflow (or documented CLI provisioning).
- [ ] Multi-currency reporting via base currency + FX rate (define rate direction / historic
      valuation) — or remove the unused conversion settings.
- [ ] Stocktake / reconciliation: build (count sessions, scan capture, discrepancy review) or
      record an explicit product-scope deferral.
- [ ] Retirement/loss lifecycle: add dates/reason/disposal/history, or record the limitation.

## Risks / watch items

- **Tailwind v3 → v4 + daisyUI (done)**: upgraded to v4.3 and reskinned to daisyUI 5.
  Theme is daisyUI `light` (default) + `dark` (prefers-color-scheme) with a persisted toggle;
  indigo utilities were mapped to `primary`. Swap the `primary` token + `AssetKOM` wordmark
  for real branding when available.
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

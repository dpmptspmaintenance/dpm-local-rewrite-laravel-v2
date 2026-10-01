# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A single-admin internal web app for DPMPTSP Kota Semarang HR data, running fully in Docker on local WSL. Laravel 13 (PHP 8.3), MySQL 8, Google OAuth2 login restricted to exactly one email address. No public registration, no password auth — Google is the only way in, and only one account is allowed through.

```
kepegawaian/
├── docker-compose.yml       # db (mysql:8.0) + app (php:8.3-cli, artisan serve)
├── .env                     # compose-level secrets (DB root/app passwords, host ports)
├── db/
│   ├── dump-kepegawaian-*.sql   # source data for the kepegawaian database
│   ├── dump-data_local-*.sql    # source data for the data_local database
│   └── init/00-init-databases.sh # creates both DBs + app user, imports both dumps
├── database.md               # full DB reference: all 7 tables, columns, FKs, data quirks
└── app/                      # the Laravel application (bind-mounted into the app container)
```

## Running it

```bash
docker compose up -d          # first run: mysql initializes, runs db/init/*.sh, imports both dumps
docker compose logs -f app    # tail the Laravel dev server
```

App: `http://localhost:${APP_FORWARD_PORT}` (default **8010** — 8000 was already taken by another local project when this was set up; check `.env` before assuming 8000).
DB: exposed on host port `${DB_FORWARD_PORT}` (default 33061) if you need to connect a GUI client.

**Important — the db init script only runs once, against an empty data directory.** If you change `DB_ROOT_PASSWORD`/`DB_USERNAME`/`DB_PASSWORD` in `.env` after the first `up`, it has no effect until the volume is wiped:
```bash
docker compose down
docker volume rm kepegawaian_db_data
docker compose up -d
```
(We hit exactly this once during setup — a stale `kepegawaian_db_data` volume from an unrelated earlier experiment was sitting in Docker under the same project name and silently shadowed the fresh init. If login/DB errors show up after changing credentials, check `docker volume inspect kepegawaian_db_data`'s `CreatedAt` before assuming the code is wrong.)

### PHP version note

`app/composer.json` targets PHP `^8.3` to match the `php:8.3-cli` runtime image, and `composer.lock` was resolved with `platform.php` pinned to `8.3.99` (see `app/composer.json`'s `config.platform`). If you run `composer update` from a host/container with a newer PHP (e.g. the plain `composer:2` image, which bundles PHP 8.5), it will happily lock packages that require PHP ≥8.4 and the app container will fatal on boot with "Composer detected issues in your platform". Always run composer commands through the pinned platform, e.g.:
```bash
docker run --rm -v "$(pwd)/app:/app" -w /app composer:2 update --with-all-dependencies
```

`maatwebsite/excel` (for the Kompetensi export) needed `--ignore-platform-req=ext-gd` when first required — the bare `composer:2` image used for dependency resolution doesn't carry `ext-gd`, even though the real `php:8.3-cli` runtime image does (it's built with `docker-php-ext-install gd`, see `app/Dockerfile`). Expect the same trap for any future package that requires a PHP extension: resolve with `docker run --rm -v "$(pwd)/app:/app" -w /app composer:2 require <pkg> --ignore-platform-req=ext-<name>`, then verify with `docker exec kepegawaian_app php -m | grep <name>` before trusting it.

### Local dev loop

`app/` is bind-mounted, so edits to PHP/Blade are live — the container's `docker-entrypoint.sh` just runs `php artisan serve`. No `vendor/` reinstall needed unless `composer.json` changes (the entrypoint runs `composer install` automatically if `vendor/` is missing, e.g. on a fresh clone that didn't carry it over).

## Google OAuth2 setup (required before login works)

1. Google Cloud Console → APIs & Services → Credentials → Create OAuth client ID (Web application).
2. Authorized redirect URI must exactly match `GOOGLE_REDIRECT_URI` in `app/.env` — currently `http://localhost:8010/auth/google/callback`.
3. Fill `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` in `app/.env`, then `docker compose restart app`.
4. Only the address in `ADMIN_ALLOWED_EMAIL` (`app/.env`) may log in — enforced in `App\Http\Controllers\Auth\AuthController::callback()`, rejected before any session is created. Currently set to `riyantonugroho.work@gmail.com`, which must already exist as a row in `data_local.users` (it does, in the imported dump) — the callback refuses to log in an email it can't match to an existing user row.

## Architecture

### Two database connections, one Laravel app

- Default connection `mysql` → **kepegawaian** database (`cuti`, `hari_libur`, `pegawai_profil`, `pegawai_anak`, `pegawai_kompetensi`, `pegawai_masuk`).
- Named connection `data_local` → **data_local** database (`users` only — the login/identity table). Configured in `app/config/database.php`. `App\Models\User` explicitly sets `protected $connection = 'data_local'`; every other model uses the default connection implicitly.

No Laravel migrations are used for either database — both are fully populated by importing the SQL dumps in `db/`. The default `users`/`cache`/`jobs` migrations that `laravel/laravel` scaffolds by default were deleted (see `app/database/migrations/`, empty) because the real `users` table already exists with a different, pre-existing schema, and this app has no need for the cache/jobs tables (`SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` are all file/sync in `app/.env`).

**`data_local.users` is shared with other internal apps** — `shared_pages` (JSON) lists app slugs like `rapat_kita`, `sikenut`, `data_kita`, `barang_kita` that aren't this app. `App\Filament\Resources\UserResource` therefore only edits existing rows (`canCreate()`/`canDelete()` return `false`), and `shared_pages` is a free-form `TagsInput` rather than a fixed checkbox list, so slugs this app doesn't know about survive a save.

### Auth

`App\Http\Controllers\Auth\AuthController` — Socialite `google` driver only, no local password auth. The allowed email lives in `config/admin.php` (`ADMIN_ALLOWED_EMAIL` env var), checked with `hash_equals` in the OAuth callback. On success it looks up the existing `data_local.users` row by email (does not create new ones), `Auth::login()`s it, and redirects to `/admin`.

The panel's stock login form is replaced by `App\Filament\Pages\Auth\GoogleLogin` (a `Filament\Auth\Pages\Login` subclass whose form is empty and whose only action links to `auth.google.redirect`). Because that class sits under the panel's `discoverPages()` path, it sets `protected static bool $isDiscovered = false` — otherwise it would also register itself as a nav page. `User` implements `FilamentUser` with `canAccessPanel(): true`: access is already gated in the OAuth callback, so any session that reaches the panel is legitimate.

### Admin UI — Filament panel

The entire admin UI is a Filament 5 panel (`App\Providers\Filament\AdminPanelProvider`, path `/admin`, registered in `bootstrap/providers.php`). There are no admin controllers or admin Blade views left; `routes/web.php` holds only the Google auth routes, `/logout`, and `/` → `/admin`. The panel auto-discovers `app/Filament/{Resources,Pages,Widgets}`.

Resources set an explicit `$slug` — without it Filament pluralizes the class name into URLs like `/admin/cutis` and `/admin/hari-liburs`.

- **CutiResource** (`/admin/cuti`) — full CRUD. Filters: `jenis` (from `Cuti::jenisOptions()`), date range. `no_surat`/`nip`/`nama` are nullable everywhere (see the import notes below).
- **PegawaiProfilResource** (`/admin/pegawai`) — read-only (`canCreate`/`canEdit`/`canDelete` all `false`); this data is sourced externally via import, not authored here. Detail page is an `infolist` plus `CutiRelationManager` / `AnakRelationManager` / `KompetensiRelationManager`.
- **PegawaiKompetensiResource** (`/admin/kompetensi`) — list + Excel export. Both the table's search/filters and `KompetensiExport` route through `PegawaiKompetensi::scopeFiltered()`, so they can't drift apart on what "filtered" means. `ListPegawaiKompetensi::exportFiltered()` translates the live table state back into the same `['q', 'tahun', 'jenis']` array the export takes.
- **KompetensiRekap** (`/admin/kompetensi-rekap`) — total JP per employee. Its grouped query (`GROUP BY nip, nama`) sets `defaultKeySort(false)` — Filament's automatic id-tiebreak sort would add `ORDER BY pegawai_kompetensi.id` and violate `ONLY_FULL_GROUP_BY`. Export (`KompetensiRekapSheet`) can be downloaded standalone as xlsx or PDF.
- **HariLiburResource** (`/admin/hari-libur`) — CRUD on one `ManageRecords` page (modals), year filter, `tanggal` unique.
- **UserResource** (`/admin/users`) — edit-only, see the shared-table note above.
- **PensiunMonitor** / **TunjanganAnakMonitor** — custom pages (stat cards + table). Their SQL mirrors `PegawaiProfil::statusPensiun()` and `PegawaiAnak::statusTunjangan()`/`batasUsiaTunjangan()` exactly, so the aggregate counts and the per-row badges can never disagree.
- **CutiTahunanRekap** (`/admin/cuti-tahunan-rekap`) — yearly cuti recap, pivoted per year: current year + two back (`CutiTahunanRekap::tahunList()`), one Terpakai/Sisa pair of columns per year. Table and export both route through `Cuti::scopeRekapTahunan()` (see Cuti model), so they can't drift. No tahun filter — the year list is fixed. The `sisa_hari_{y}` columns are `GREATEST(12 - SUM(...), 0)` aggregates, so the query `GROUP BY nip` only; the page must keep `defaultKeySort(false)` (Filament's id-tiebreak would add `ORDER BY cuti.id` and violate `ONLY_FULL_GROUP_BY`).
- **ImportCuti** / **ImportPegawai** — custom upload pages (hidden from nav via `$shouldRegisterNavigation = false`), reached by header actions on the Cuti and Pegawai lists. They call `CutiImportService` / `PegawaiImportService` unchanged and surface per-row notes in a section below the form.

Relation managers and pages that declare an `$icon` must type it `string | \BackedEnum | null` — Filament 5 widened the parent property, and a plain `?string` is a fatal type error at boot.

### Frontend

No hand-written CSS and no npm build — Filament ships its own compiled assets. `php artisan filament:assets` republishes them into `public/{css,js}/filament/` after a Filament upgrade. The only Blade left is four page views under `resources/views/filament/pages/`.

### Pegawai JSON import (`App\Services\PegawaiImportService`)

Imports the exact JSON shape scraped from `https://sisdm.semarangkota.go.id/pegawai/{id}` (one object or a JSON array of them, auto-detected in `App\Filament\Pages\ImportPegawai`). The NIP is read from `detail_profil.nip` (falling back to `data_tabel_utama.nip_tabel_utama`) — a record with a top-level `nip` but no `detail_profil` is rejected. Each employee record is treated as a **full current snapshot**, not a diff:

- `pegawai_profil` is upserted by `nip` (`updateOrCreate`).
- `pegawai_anak` / `pegawai_kompetensi` rows for that `nip` are **deleted and reinserted** from `riwayat_anak`/`riwayat_kompetensi` on every import — so a re-import after the source adds one new certificate replaces the whole list, it doesn't append.
- Dates arrive as `dd-mm-yyyy`; empty string / `"-"` become `NULL` (required — these columns are strict-mode `date`, which rejects `''`). `riwayat_kompetensi[].jenis` of `"-- Pilih --"` (the source system's unselected-dropdown placeholder) is also nulled.
- Each record imports in its own `mysql` connection transaction; one bad record doesn't abort the batch — errors collect per-record and flash back to the import page.

**Gotcha already hit once:** `PegawaiProfil` must NOT set `$guarded = ['nip']` (or any guard on the primary key). `updateOrCreate()`'s create path builds the new instance via mass-assignment `fill()`, which silently drops guarded attributes — including from trusted internal code, not just HTTP input — so a guarded `nip` means every *new* employee import fails with "Field 'nip' doesn't have a default value" while updates to existing NIPs work fine (they don't go through `fill()` for the key). Caught by testing the create path with a synthetic new NIP, not just re-importing an existing one.

### Cuti Excel import (`App\Services\CutiImportService`)

Reads sheet 1 with PhpSpreadsheet directly (`IOFactory::load()->getSheet(0)->toArray(null, false, false, false)` — raw values, so serial dates stay numeric). Note `Excel::toArray(null, $path)` is *not* valid in maatwebsite/excel v4; it requires an import object. Columns 1–11 must match the fixed header (column 0 is the ignored "No"): No Surat, Nip, Nama, Tanggal Mulai/Selesai Diajukan, Opd, Unit Kerja, Lokasi Kerja, Status, Keperluan, Jenis.

The import is a **positional upsert, not a replace** — rows are matched by `no_surat` and updated in place; rows whose `no_surat` isn't in the table are inserted; rows already in the table that don't appear in the file are left alone. Nothing is ever deleted.

Deliberately permissive, per repeated user requests — the source spreadsheet is authoritative and nothing may be silently dropped:

- `no_surat` / `nip` / `nama` may all be empty; they're stored `NULL`. The three columns were `ALTER`ed to be NULLable, and the `cuti_no_surat_unique` index was dropped (replaced by a plain index) because the source legitimately repeats a `no_surat`.
- A row is skipped **only** when all 11 mapped columns are blank. Don't reinstate the old "blank if no_surat+nip+nama are empty" check — those are exactly the columns allowed to be empty, so it silently ate rows carrying only dates and keperluan.
- Only an unparseable date throws (per-row, collected into `errors`). A reversed date range is stored as-is with a warning.
- Excel stores an 18-digit NIP as a float, losing digits past 15. `identifier()` recovers it by matching the 15-char prefix against `pegawai_profil.nip`; a prefix claimed by two NIPs is treated as ambiguous, and unrecoverable ones are stored as-is with one aggregated warning rather than skipped.

## Known data quirks (carried over from the source dumps)

- NIP values aren't validated at the source — expect `-`, `--`, empty strings, and placeholder `user_NN@dummy.local` emails for some `data_local.users` rows.
- `cuti.nip` is a plain column, not a declared FK (unlike `pegawai_anak`/`pegawai_kompetensi`, which do `FOREIGN KEY (nip) REFERENCES pegawai_profil(nip)`), so `Cuti::profil()` can return null even for rows with data.
- `cuti.durasi_hari` is `0` in every imported row — so the cuti-tahunan recap computes days as `DATEDIFF(tanggal_selesai_diajukan, tanggal_mulai_diajukan) + 1` (inclusive range). The recap's `Cuti::rekapTahunan()` only counts rows where both start and end fall in the same calendar year (a row spanning years contributes 0 to each).
- `pegawai_profil.nip` values may carry a 3-digit suffix that `data_local.users.nip` drops for the same person — don't assume exact string equality when reconciling the two by hand outside Eloquent relations (there is no cross-database FK, and none of the code here currently joins across connections).

### Cuti Tahunan recap logic (`Cuti::scopeRekapTahunan()`)

The yearly cuti recap lives on the `Cuti` model and is shared by the `CutiTahunanRekap` page and `CutiTahunanSheet` export:

- Counts `jenis = 'Cuti Tahunan'` only, skips blank/NULL NIP.
- Pivoted per year (`CutiTahunanRekap::tahunList()` = current year + two back): each year gets a `total_hari_{y}` (via `SUM(CASE WHEN YEAR(mulai) = y AND YEAR(selesai) = y THEN DATEDIFF(selesai, mulai) + 1 ELSE 0 END)`) and `sisa_hari_{y}` = `GREATEST(12 - total, 0)`. Kuota is `Cuti::KUOTA_TAHUNAN = 12`.
- Rows spanning a year boundary are deliberately dropped from both years (see known data quirks).
- `MIN(id) as id` is selected so the aggregated result has a stable record key; `GROUP BY nip` only.

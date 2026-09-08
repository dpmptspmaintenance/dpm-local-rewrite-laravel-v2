# Legacy PHP → Laravel conversion guide (data-kita)

App root: `/root/docker/rewrite-dpm-local/src` — Laravel app running in Docker container `dpmptsp-app`, nginx `dpmptsp-web` on http://localhost:8080, MySQL container `dpmptsp-db`, DB `dpmptsp_new`, root/root.

Legacy source root: `/root/docker/rewrite-dpm-local/old/dpm-local/www/dpmptsp-local/data-kita/`

DB schemas: `/root/docker/rewrite-dpm-local/db.md` (structure-only dumps, grep `CREATE TABLE`).

## Reference implementations (READ FIRST, mirror conventions exactly)

- Controller: `src/app/Http/Controllers/DataKita/PerizinanController.php` — FILTER_MAP with `raw:` prefix for YEAR()/MONTH() expressions, SORTABLE whitelist for ORDER BY, baseQuery() shared by ajaxData + export, bound params everywhere.
- Views: `src/resources/views/datakita/perizinan/{index,search,export}.blade.php`
- Navbar partial included at top of every page: `@include('datakita.partials.navbar')`
- Routes live in `src/routes/web.php` inside `Route::prefix('data-kita')->name('datakita.')->group(...)`.

## Hard rules

1. NEVER interpolate request input into SQL. Use bindings / query builder. Old code has SQLi holes — close them.
2. Sort column from client → whitelist map only (see SORTABLE const pattern).
3. Tables whose name starts with a digit (e.g. `2023_dp_proyek`) work fine with plain `DB::table('2023_dp_proyek as p')` — do NOT add manual backticks (Laravel wraps them).
4. Digit-starting table names are fine in Schema::create too.
5. Blade views: Bootstrap 5.3.3 CDN + jQuery 3.7.1 + DataTables 1.13.6 (dataTables.bootstrap5) unless legacy page used something else (ApexCharts/SheetJS — keep those CDN versions). Moderustic Google font. Match legacy HTML structure/labels/Indonesian text verbatim where practical.
6. jQuery AJAX: `$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } });` for POST.
7. Legacy `$("#tmpt_search").load("search.php")` pattern → keep as `.load(route('...search'))` returning a Blade partial view.
8. Excel export: legacy streams an HTML table with `application/vnd-ms-excel` header → port as `response()->streamDownload(fn() => print view(...)->render(), filename, ['Content-Type' => 'application/vnd-ms-excel'])` unless legacy used PhpSpreadsheet (that package IS installed — `phpoffice/phpspreadsheet` ^5.9).
9. Exports must re-apply the same filters as the grid.
10. CSRF: forms need `@csrf`; GET search forms don't.
11. Auth: everything behind `auth` middleware (already applied to the data-kita route group). Admin-only pages check `Auth::user()->role == 1`.
12. Legacy `$_SESSION['role']` → `Auth::user()->role`, `$_SESSION['username']` → `Auth::user()->nama`, `$_SESSION['user_id']` → `Auth::id()`.
13. If a page needs a table not yet migrated (check `src/database/migrations/`), add migration `2026_09_07_00XXXX_*.php` copying db.md schema EXACTLY. Coordinating numbers: 000010–000019 reserved for rekap batch, 000020–000029 statistik, 000030–000039 grafik-rekap, 000040–000059 overview, 000060–000079 lppd. If number taken, pick next free in your range.
14. Do NOT run migrations or artisan commands that mutate state except `route:list`/`migrate` — actually DO run `docker exec dpmptsp-app php artisan migrate --force` after adding migrations, and verify routes with `docker exec dpmptsp-app php artisan route:list --path=data-kita`.
15. Navbar: do NOT edit `datakita/partials/navbar.blade.php` — main agent wires links afterward.
16. Keep Indonesian UI text exactly as legacy.
17. If legacy references `../layout/header.php` / `footer.php`: those just bundle CSS/JS + navbar — replace with a plain `<!DOCTYPE html>` head containing the same CDNs + `@include('datakita.partials.navbar')` at body top. Read them at `old/.../data-kita/layout/`.
18. Legacy helper `function/fungsi.php` (`getPaginationdata`, `getRequestParams`) — replaced by baseQuery/FILTER_MAP pattern; do not port helpers.
19. If a legacy file is clearly dead (oldindex.php, tes.php, *_1.php, unreferenced), skip it and note it.
20. When done: report per module — routes added, views created, migrations created, deviations, skipped files.

## Route naming

URL = legacy folder/file name under /data-kita (e.g. legacy `rekap-sektor/index.php` → `/data-kita/rekap-sektor`, `statistik/statistik_kantor.php` → `/data-kita/statistik/statistik-kantor`). Name: `datakita.<slug>.<action>`.

## routes/web.php is SHARED — edit carefully

Append your routes at the end of the data-kita group; add controller imports in the existing use-block alphabetically. Do not restructure other routes.

# Progress — rewrite-dpm-local

Tracked per user instruction: update this file on every new progress.

## Current task: data-kita statistik + grafik-rekap + overview conversions, navbar wiring

Statistik, grafik-rekap, overview (OSS/SIMBG/MPPD) conversions DONE. Navbar groups wired.

Legacy source: `old/dpm-local/www/dpmptsp-local/data-kita/{statistik,grafik-rekap-*,overview}/`

### State (2026-09-07)

- `src/app/Http/Controllers/DataKita/StatistikController.php` — EXISTS, complete logic for all 4 modules (kantor, proyek, izin, simbg) + excel/word downloads + simbg kelurahan ajax.
- Views ALL PRESENT: `src/resources/views/datakita/statistik/` — kantor, kantor-download-excel, izin, izin-download-excel, proyek, proyek-download-excel, proyek-download-word, simbg, partials/simbg-table-rows.
- Routes: ADDED to `src/routes/web.php` — `Route::prefix('statistik')->name('statistik.')` group inside data-kita group, 9 routes (kantor/kantor-download-excel, proyek/proyek-download-excel/proyek-download-word, izin/izin-download-excel, simbg, simbg-kelurahan). Controller import added alphabetically.
- `php artisan migrate --force` RUN in `dpmptsp-app` container (pending migration 2026_09_07_000050_create_mppdig_tables DONE). Statistik tables (2023_dp_nib_kantor, 2023_dp_proyek, 2023_list_izin in 000006; simbg_monitoring in 000020) already migrated.
- `route:list --path=data-kita` verified: all statistik routes registered. Full route names matched blade refs. (Classifier was down — JSON route dump deferred; HTTP smoke test pending.)
- Migrations to verify: 2023_dp_nib_kantor, 2023_dp_proyek, 2023_list_izin, simbg_monitoring — all present.

### Grafik-rekap conversions (source table `2023_dp_proyek`)

User instruction: use `2023_dp_proyek` (NOT `rekap_dp_proyek` — that table empty/legacy agg). Rows are per-proyek; metric columns: investasi=`jumlah_investasi3`, TKI=`tki`. `bulan_pengambilan_data` is varchar('01'..'12') → always `CAST(... AS UNSIGNED)` in SELECT/order/group.

- DONE `GrafikRekapJumlahInvestasiController` — rewrite onto `2023_dp_proyek`, SUM(jumlah_investasi3) per tahun/month, legacy 4-filter grid (`uraian_skala_usaha/uraian_risiko_proyek/uraian_jenis_proyek/uraian_status_penanaman_modal`), grid col alias `jml_investasi`, export `Data_Rekap_Jumlah_Investasi.xls`.
- DONE `GrafikRekapJumlahTkiController` — same but `SUM(tki)`, alias `jml_tki`, export `Data_Rekap_Jumlah_TKI.xls`.
- Views rewritten legacy layout (4 dropdowns + 10-col grid mirroring old table.php): both modules `table.blade.php` + `export.blade.php`. `index.blade.php` already faithful (ApexCharts tahunan+bulanan), unchanged.
- Routes ADDED in web.php grafik-rekap-jumlah-investasi/`-tki`: index, table, bulanan, ajax (match get|post), export. Verified `route:list --path=grafik-rekap`. Controllers php -l clean. `view:cache` OK.
- Navbar `datakita/partials/navbar.blade.php` — now has submenu CSS/JS (ported legacy header/footer) + groups: Rekap », Statistik OSS », Grafik Rekap Proyek » (2 items wired), LPPD ». Under Data OSS-RBA.
- DB note: dpmptsp_new tables all EMPTY (0 rows) — real data not synced yet. Code mirrors schema only.

### Grafik-rekap-kbli + grafik-rekap-list-izin (DONE)

- `GrafikRekapKbliController` — rewritten onto `2023_dp_proyek` (user confirmed: kbli uses proyek since TKI+investasi only exist there). COUNT(DISTINCT kbli) per tahun/month; grouped grid `kbli, COALESCE(SUM(tki)), COALESCE(SUM(jumlah_investasi3))` w/ legacy 4-filter. Views table/export mirror legacy (KBLI/Jml TKI/Jml Investasi).
- `GrafikRekapListIzinController` — already read `2023_list_izin` (YEAR(day_of_tanggal_terbit_oss)); 3 views (index/table/export) were MISSING → created. Grid: nama_perusahaan/nib/status_respon/jenis_perizinan/status_penanaman_modal/kbli/bulan. Legacy list-izin export_excel was broken (copy-paste querying rekap_dp_proyek) — ported sensibly from grid.
- Routes: 10 more grafik-rekap routes added. Navbar Grafik Rekap Proyek » now has all 4 items.

### Overview conversions (DONE) — `datakita.overview.*` (36 routes total)

Controllers already existed (`OverviewOssController`, `OverviewSimbgController`, `OverviewMppdController`). Missing views created + routes + navbar wired.

- OSS (5 modules): views already present (index+export per dir) + routes existed. No change.
- SIMBG (4 modules): `simbg-resume-pertahun` + export existed. Created: rekap-perkecamatan-export, fungsi-per-kecamatan (+export), slf-pbg-pertahun (+export). Tables `simbg_monitoring`.
- MPPD (9 modules): created 18 views (9 page + 9 export). Tables `mppdig_permohonan_sip_semua` + `mppdig_faskes` (migration 000050). Faithful to legacy HTML/CSS/excel tables.
- Routes: added 26 routes (10 SIMBG/MPPD per module pattern; OSS 10 pre-existing). Verified `route:list --path=overview` = 36.
- Navbar: added top-level `Data SIMBG` + `MPP Digital` dropdowns (each with Overview » submenu), and OSS `Overview »` submenu under Data OSS-RBA. All 18 overview nav links (5 OSS + 4 SIMBG + 9 MPPD) wired.

### Data SIMBG modules (DONE) — `SimbgController`

Migration `2026_09_07_000080_create_simbg_support_tables` (RUN) adds: simbg_penyerahan_dokumen_pbg, simbg_data_tambahan, simbg_master_jenis_regist, simbg_master_jenis_konsultasi, simbg_master_fungsi_bangunan_gedung, datakita_validasi_pembayaran_retribusi_pbg. (simbg_monitoring already in 000020.)

- `monitoring-simbg` index + export: leftJoin simbg_monitoring m ↔ simbg_penyerahan_dokumen_pbg p, filters (tahun/bulan/input_antara/jenis_registrasi/jenis_konsultasi/fungsi_bg/status), SK-PBG-Terbit special-case uses p.tgl_dokumen_pbg for year/month.
- `monitoring-simbg/tambah-ubah`: select2 autocomplete (get-select-data) + get-data (populate form if exists) + proses-tambah-ubah (insert/update penyerahan + data_tambahan, transaction). Bound params everywhere (legacy had SQLi).
- `rekap-pbg` index + export: filter_berdasarkan whitelist (tgl_registrasi/tgl_dokumen_pbg/tgl_pengambilan_sk), YEAR/MONTH on chosen col.
- `validasi-pembayaran-retribusi-pbg` index + tambah + store (POST). NOTE legacy had broken table name mismatch (`datakita_validasi_pembayaran` vs `..._retribusi_pbg`); normalized to `datakita_validasi_pembayaran_retribusi_pbg`.
- `simbg-tracking` index: search by no_registrasi in simbg_monitoring.
- 12 routes registered; navbar Data SIMBG dropdown now full: Tambah/Ubah Pengambilan SK, Monitoring SIMBG, Rekap PBG, Validasi Pembayaran Retribusi PBG, Tracking SIMBG, Overview ».

### Daftar-file (DONE) — `DaftarFileController`

- Shared module `daftar-file/?klasifikasi=oss|simbg|nswi|mpp`. Migration `2026_09_08_000081_create_datakita_upload_file_table` (RUN) adds `datakita_upload_file`.
- index: list files by klasifikasi (is_aktif=1); upload: store to `public` disk `datakita-file/<klasifikasi>/<nama>`, insert row (nama_file/lokasi_file/klasifikasi/created_by/is_aktif). Deviation: DB `lokasi_file` = `/storage/...` (Laravel public disk) vs legacy `/dpmptsp-local/data-kita/file-...`.
- 2 routes + navbar Data SIMBG `Daftar File` link (klasifikasi=simbg).

### MPP Digital modules (DONE, excl. daftar-file) — `MppdigController`

Migration `2026_09_08_000082_create_mppdig_data_tables` (RUN) adds: mppdig_permohonan, mppdig_pemohon, mppdig_kendala (none were in db.md dump — schemas built from legacy query column usage). `mppdig_faskes` + `mppdig_permohonan_sip_semua` already in 000050.

- `mppdig-permohonan`: server-side DataTable (ajax POST) + export, filters status_permohonan/date-range(tgl_permohonan)/profesi[] (multi-select2), search nama/no_reg/nik. daterangepicker + moment + select2 + datatables CDN.
- `mppdig-pemohon`: index+export, filters nama(like)/gender(exact)/alamat(like) on mppdig_pemohon.
- `mppdig-kendala`: index+export, filters nama/kendala(like)/status(exact) on mppdig_kendala.
- `mppdig-faskes`: index+export, filters nama/alamat/kategori(like) on mppdig_faskes.
- `mppdig-grafik`: 3 ApexCharts (faskes by kategori, permohonan by status, permohonan by profesi) — read-only dashboard.
- `mppdig-tracking`: search mppdig_permohonan by no_reg, colored status pill (SK Diterbitkan=green/Dibatalkan=red/else=yellow).
- 11 routes registered; navbar MPP Digital dropdown now full: Permohonan, Pemohon, Kendala, Faskes, Grafik, Tracking, Overview » (9 items, excludes Daftar File per user request — already separately linked under Data SIMBG only, not MPP Digital, matching legacy since legacy MPP nav also had its own Daftar File item pointing klasifikasi=mpp — NOT yet added to MPP Digital navbar, only SIMBG's was requested/added).

### Import Data (DONE) — `ImportController` (admin-only, role==1)

User explicitly scoped: convert only `data-kita/import/` fully (all 8 forms), skip the OTHER Synchronize Data items (DP NIB Kantor/DP Proyek/List Izin/LKPM/NIB LKPM sync links — not converted, only Import Data linked).

- Legacy used `openspout/openspout` (NOT installed) for 6 of 8 forms + `phpoffice/phpspreadsheet` (already installed ^5.9) for SIMBG form. **Standardized all 8 forms on PhpSpreadsheet::IOFactory** to avoid adding a composer dependency — same header-validate/insert/batched-commit behavior per form, verified against each legacy script line-by-line.
- Dead/unreferenced legacy files SKIPPED (not linked from index.php or anywhere else): `cek_excel.php`, `cek_jumlah.php`, `insert_mppd.php`, `insert_simbg.php`, `import_permohonan_sip.php`, `validate_simbg_monitoring_scrape.php` (targets unmigrated `simbg_monitoring_scrape` table).
- 8 forms → `2023_dp_proyek`, `2023_dp_nib_kantor`, `2023_list_izin` (insert, batched commit/1000), `simbg_monitoring` (upsert by no_registrasi), `mppdig_permohonan_sip_semua` (insert for semua/nextgen-selesai/nextgen-ditolak, update-only for selesai). All existing migrated tables — no new migration needed.
- Preserved a **legacy bug verbatim**: `validate_list_izin.php` never mapped kecamatan/kelurahan (header cols 8/9) into the insert despite the column existing — kept as-is, not silently fixed, per minimal-scope.
- Preserved response-shape differences per legacy: dp_proyek/dp_kantor/list_izin/simbg/mppd-semua/mppd-selesai return plain HTML fragment (full-page nav on form submit, no layout — matches legacy `die()`/`echo`); mppd-nextgen-selesai/ditolak return raw JSON (matches legacy `exit(json_encode(...))` even though the form does a normal POST, not ajax).
- Admin gating: `abortIfNotAdmin()` (403 if `role != 1`) in every controller method; navbar link wrapped in `@if ((int) (Auth::user()->role ?? 0) === 1)` — new top-level "Synchronize Data" dropdown with only "Import Data" (legacy's other sync links intentionally excluded).
- 9 routes registered (index + 8 validate POSTs), verified via `route:list --path=import`. Controller `php -l` clean, `view:cache` OK.

### User Management (DONE) — `UserController` (top-level, not data-kita; admin-only role==1)

Legacy `dpmptsp-local/user/` (7 files) converted 1:1: index, get_users (ajax→html rows partial), add_user, add_gmail, toggle_status, update_access, batch_update_access.

- Routes at top-level `/user` prefix (sibling to `/dashboard`, `/rapatkita`, `/data-kita` — NOT under datakita.* namespace), named `user.*`. Dashboard (`dashboard.blade.php`) ALREADY had a role==1-gated "Add User" card linking `url('/user')` — no dashboard edit needed, just had to make the route real.
- Views: `resources/views/user/index.blade.php` (page+4 modals+JS, standalone — no shared app navbar, matches legacy which had none) + `user/partials/rows.blade.php` (ajax-loaded tbody rows, mirrors CONVERT_GUIDE's `.load()`-returns-partial pattern).
- `users` table already had all needed columns (`nama`,`role`,`bidang`,`is_aktif`,`email`,`password`,`shared_pages` json). Auth is Google-OAuth-only (`AuthController`) — `password` field is legacy-compat vestige only (`md5($email)`, auto-bcrypt-hashed by the model's `'hashed'` cast), never used to authenticate.
- Deviation: `status_jabatan`/`nip` are NOT NULL with no default in schema; legacy's INSERT omitted them (relied on non-strict MySQL mode). Explicitly insert `''` for both to avoid a hard failure under strict mode — same practical end-state as legacy.
- Deviation (cosmetic only): empty-state `<td colspan>` corrected from legacy's off-by-one `8` to the actual `9` columns.
- `updateAccess`/`batchUpdateAccess` write `shared_pages` (JSON column, cast as `array` on the model) — single-user path uses Eloquent `update()` (cast applies); batch path uses query-builder `update()` per id (cast doesn't apply there, so `json_encode()` called explicitly) — both land the same JSON shape.
- 7 routes verified via `route:list --path=user`. Controller `php -l` clean, `view:cache` OK.

### Grafik-rekap tahun/bulan field fix (2026-09-08)

User instruction: `GrafikRekapJumlahInvestasiController` + `GrafikRekapJumlahTkiController` gak boleh pakai `tahun_pengambilan_data`/`bulan_pengambilan_data` (varchar) buat filter/group — gunakan a date col di `2023_dp_proyek` via `YEAR(...)`/`MONTH(...)`. Applied to both controllers (index/bulanan/table/baseQuery/ajaxData/export + SORTABLE `bulan_pengambilan_data` raw expr). Output alias names (`tahun_pengambilan_data`, `bulan_pengambilan_data`, `jml_investasi`/`jml_tki`) kept identical — blade views untouched. `FILTER_MAP` no longer carries `tahun` (needs whereRaw, not plain where); handled separately in `baseQuery()`.
- First pass used `tanggal_proyek` → hit MySQL `only_full_group_by` error on `index()`: table still has a real (unused) column literally named `tahun_pengambilan_data`, so `groupBy('tahun_pengambilan_data')` bound to that real column instead of the `YEAR(...)` select alias, causing a functional-dependency mismatch. Fixed by grouping/ordering on `DB::raw('YEAR(...)')` directly instead of the alias string.
- Second pass: user changed date source again from `tanggal_proyek` → **`tanggal_terbit_oss`** (also a date col on `2023_dp_proyek`). Applied via global rename across both controller files; all `YEAR(...)`/`MONTH(...)`/`whereNotNull(...)`/`groupBy(DB::raw('YEAR(...)'))` now reference `tanggal_terbit_oss`.
- Not yet verified with `php -l` (php binary unavailable in this sandbox) — logic reviewed by inspection only.

### Remaining / notes

1. HTTP smoke-test pages when auto-mode classifier back up. DB `dpmptsp_new` tables empty (no sync yet) — Import Data itself is how real data would land, but not exercised yet.
2. Legacy MPP Digital nav also had its own `Daftar File` (klasifikasi=mpp) item — excluded per user request; not linked in MPP Digital dropdown.
3. Other nav groups/items (Data NSWI, Siimut, LKPM, other Synchronize Data sync links) not yet converted.
4. Report per-module summary when batch done.

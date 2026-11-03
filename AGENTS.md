# Repository & Agent Guidelines

Repository ini berisi aplikasi Laravel (`src/`) yang dijalankan menggunakan Docker (`docker-compose.yml`).

## 1. Lingkungan Docker & PHP Execution

Aplikasi berjalan di dalam kontainer Docker (`dpmptsp-app`), dan host **tidak memiliki binary PHP/Composer**.
Setiap perintah terkait PHP (`artisan`, `composer`, `tinker`, `php -l`, `phpunit/test`) **WAJIB** dijalankan melalui kontainer Docker dengan working directory `/var/www`:

```sh
docker exec -w /var/www dpmptsp-app <command>
```

Contoh:
```sh
docker exec -w /var/www dpmptsp-app php artisan optimize:clear
docker exec -w /var/www dpmptsp-app php artisan view:cache
docker exec -w /var/www dpmptsp-app php artisan test
```

### Menghindari Error `touch(): Utime failed: Operation not permitted`
Cache/compiled file di bawah `storage/framework/views/*`, `storage/framework/cache/*`, dan `bootstrap/cache/*` dapat memiliki mixed UIDs antara `www-data` dan `root`.

**Aturan:**
1. Jangan pernah menjalankan `php artisan ...`, `composer ...`, atau menulis langsung ke `storage/` atau `bootstrap/cache/` dari tool host. Selalu jalankan melalui `docker exec -w /var/www dpmptsp-app php artisan ...`.
2. **Selalu jalankan `optimize:clear` dan pastikan chown `www-data:www-data`** setelah membuat atau mengedit file kode, agar Blade compiler yang dijalankan oleh web server (Nginx/PHP-FPM) tidak mengalami benturan permission/UID `touch(): Utime failed`:
   ```sh
   docker exec -w /var/www dpmptsp-app php artisan optimize:clear
   docker exec -w /var/www dpmptsp-app chown -R www-data:www-data storage bootstrap/cache
   ```
3. Jangan `rm`/`touch` file di bawah `storage/framework/views`, `storage/framework/cache`, atau `bootstrap/cache` langsung dari host.

---

## 2. Migrasi Database — Keamanan Produksi

Saat `APP_ENV=production`, **JANGAN PERNAH menjalankan `php artisan migrate`**.

**Aturan:**
- Pada **local/development** (`APP_ENV=local`): `php artisan migrate --force` diizinkan untuk menerapkan migrasi baru:
  ```sh
  docker exec -w /var/www dpmptsp-app php artisan migrate --force
  ```
- Pada **production** (`APP_ENV=production`): Jangan jalankan `php artisan migrate`. Hasilkan raw SQL migrasi menggunakan `--pretend` dan berikan ke user untuk direview/dieksekusi manual:
  ```sh
  docker exec -w /var/www dpmptsp-app php artisan migrate --pretend --force
  ```

---

## 3. Data Uji Database — DILARANG Truncate Data Asli

Data nyata pengguna tersimpan di database (`kepegawaian`, `mysql`/`dpmptsp_new`). Sebagian tabel berisi data entri manual pengguna (DRH Satya Lancana, DUK, penghargaan manual, arsip dokumen, dll.).

**Aturan:**
- **JANGAN PERNAH** menjalankan `TRUNCATE`, `DELETE` tanpa `WHERE`, atau `->delete()` pada seluruh tabel.
- Hapus hanya baris data **yang dibuat sendiri selama pengujian**, targetkan berdasarkan identifier unik (ID hasil insert pengujian).
- Selalu bersihkan data uji di blok `tearDown()` / `finally`.
- Jika membutuhkan state kosong untuk pengujian, batasi query pada batch/record uji spesifik.
- Jangan pernah menghapus seluruh tabel untuk mendapatkan clean slate.

---

## 4. Modul Arsip Digital (`/arsip`) — Ownership & Hak Akses

Modul Arsip Digital menggunakan Filament Panel di `/arsip` dengan arsitektur penyimpanan hibrida (metadata di database lokal, berkas fisik di Google Drive / tautan).

### Konsep Ownership Akses
1. **Master Ownership** (`App\Models\Ownership`):
   - Dikelola di menu `/arsip/ownership` (`OwnershipResource`), akses dibatasi untuk Admin/Superadmin (`isArsipAdmin()`).
   - Admin dapat menambah, mengedit, dan menghapus unit ownership secara dinamis (contoh: Kepegawaian, Keuangan, IT, dll).
   - Pengguna (user) dapat dipetakan ke satu atau beberapa ownership melalui tabel pivot `ownership_user`.

2. **Aturan Hak Akses Dokumen**:
   - **Superadmin (`role === 1`) & Admin Arsip (`is_admin_arsip`)**: Memiliki akses penuh melihat, mengunggah, mengedit, menghapus, memverifikasi, dan mengubah ownership semua dokumen.
   - **Staf / Pengguna Biasa**:
     - Hanya dapat melihat dokumen yang:
       a) Diunggah oleh dirinya sendiri (`created_by === auth()->id()`), ATAU
       b) Dokumen yang berstatus `published` dan berada di bawah unit ownership yang diikutinya, ATAU
       c) Dokumen `published` yang bersifat **Publik** (`ownership_id` kosong/null).
     - Dokumen milik unit lain (misal dokumen ber-ownership Kepegawaian bagi user di luar Kepegawaian) **TIDAK BISA** dilihat atau diakses.

3. **Aturan Pengaturan Ownership saat Unggah**:
   - Pengguna biasa **hanya dapat memilih unit ownership saat proses unggah (`create`)**: pilihannya terbatas pada grup unit yang diikutinya atau **Publik** (`null`).
   - Pengguna biasa **tidak dapat mengubah ownership** setelah dokumen diunggah (field disabled pada halaman edit).
   - Admin / Superadmin bebas menentukan atau mengubah ownership kapan saja (pada form create, edit, maupun Dashboard Verifikasi / `ReviewQueue`).

### Konsep Berkas Campuran (file + tautan)
Satu dokumen bisa berisi berkas fisik (multiple upload) DAN tautan berjudul (multiple repeater URL+judul) sekaligus — radio pemisah "Unggah Berkas / Tempel Tautan" sudah dihapus. Tautan disimpan sebagai baris `document_files` (`google_web_view_link` + `link_title`, `storage_path` null; deteksi via `DocumentFile::isLink()`).
- `source_type`: `file` (hanya berkas), `url` (hanya tautan / dokumen url lama), `both` (`Document::SOURCE_BOTH`, campuran). Dihitung ulang otomatis oleh `DocumentService::tipeCampuran()` setiap tambah/hapus baris.
- Create: `createWithFilesAndLinks()` (satu transaksi; judul kosong → fallback nama file pertama / judul tautan pertama). Edit: `addFiles()` + `addLinks()`; hapus baris via `removeFile()` (fisik hapus disk, tautan langsung hapus DB).
- View: kartu berkas vs kartu tautan (badge TAUTAN); controller file redirect ke URL asli untuk baris tautan (tetap cek `canAccessDocument`).
- Migrasi: `2026_11_03_000001_add_link_title_to_document_files_table` (guarded `hasColumn`; APP_ENV=production → berikan SQL ke user, jangan `migrate`).

---

## 5. Modul Kepegawaian — Tool Notulen Maker (`/kepegawaian/notulen`) & Generator Word/PDF

Modul Notulen Maker berada di panel Kepegawaian (`/kepegawaian/notulen`) menggunakan `NotulenResource` dan `NotulenGeneratorService`. Berbeda dengan Surat Tugas, Notulen tidak diunggah ke Srikandi sehingga tidak memerlukan nomor naskah.

### 1. Struktur Form & Penandatangan
- **Atasan (Mengetahui) & Yang Melaporkan**: Dipilih dari `User` dengan relasi live select. NIP, nama, dan jabatan terisi otomatis serta dapat disesuaikan manual.
- **Hasil Acara**: Menggunakan RichEditor WYSIWYG yang mendukung format teks kaya, paragraf, dan daftar bernomor/peluru.

### 2. Aturan Penomoran Bagian Naskah (Sequential & Adaptive)
- Penomoran judul bagian naskah (**1. Dasar**, **2. Waktu dan Tempat Pelaksanaan**, **3. Narasumber :**, **4. Peserta :**, **5. Hasil Acara :**, **6. Penutup**) diatur secara sekuensial dan otomatis.
- **Penanganan Bagian Kosong**:
  - Jika **Dasar**, **Narasumber**, atau **Peserta** tidak memiliki data/isi, heading dan bloknya **wajib ditiadakan total** (tidak meninggalkan judul menggantung atau baris kosong sisa).
  - Penomoran bagian setelahnya **wajib otomatis melanjutkan secara urut** (misal: jika Narasumber dan Peserta kosong, Hasil Acara menjadi nomor 3, dan Penutup nomor 4).
- **Sub-Item Indentasi**:
  - Item di bawah Dasar berhuruf `a. `, `b. `, `c. ` dengan hanging indent.
  - Item di bawah Narasumber dan Peserta daftar bernomor `1. `, `2. ` dst dengan hanging indent.

### 3. Penanganan WYSIWYG HTML ke OpenXML Word
- Tag list HTML (`<ol>`, `<ul>`) dari RichEditor **wajib dinormalisasi** (`normalizeHtmlLists`) menjadi penomoran eksplisit (`1. `, `2. `, `a. ` dst) dengan hanging indent (`<w:ind w:left="720" w:hanging="360"/>`).
- Tag `<w:numPr>` bawaan parser HTML PHPWord **wajib dibersihkan** dari XML hasil konversi agar aplikasi Word maupun LibreOffice tidak salah memetakan `numId` menjadi unordered bullet list.
- **Prefix unik per level (WAJIB)**: `normalizeHtmlLists` memakai prefix ordered `1.` / `a.` / `1)` / `a)` (siklus `% 4`) dan unordered `•` / `◦` / `▪` / `‣` (siklus `% 4`) agar `detectListLevel` dapat menetapkan indent unik bertingkat: `w:ind w:left = 720 + (level * 360)` dengan hanging `360`. Jangan kembalikan ke prefix lama (`• `/`- `) karena akan membuat semua bullet rata di level yang sama.
- **Paragraf child Hasil Acara**: paragraf non-heading & non-list di bawah heading Hasil Acara diberi indent 1 tab (`<w:ind w:left="720"/>`). Sub-judul (`<h2>`/`<h3>` → `w:pStyle w:val="HeadingN"`) **tidak** diberi indent (rata kiri).
- **Paragraf kosong RichEditor**: paragraf kosong di akhir `hasil_acara` (mis. `</ol><p></p>`, `<p><br></p>`) **wajib dibuang** (`stripTrailingEmptyBlocks`) agar tidak menyisakan baris kosong berlebih.
- **Paragraf kosong berdampingan**: `collapseEmptyParagraphs` meringkas paragraf kosong top-level yang berurutan menjadi satu (spacer tunggal di dalam sel tabel TTD tetap utuh). PENTING: gunakan delimiter regex non-`/` (mis. `~`) karena pattern memuat `</w:pPr>`.
- **Penutup**: Teks penutup ("Demikian ... untuk menjadikan periksa") **TIDAK** diberi penomoran list.

### 4. Keamanan XML & Konversi PDF LibreOffice
- **XML Escaping Wajib**: Seluruh variabel yang dimasukkan ke `TemplateProcessor` (`setValue` dan `cloneBlock`) **wajib di-escape XML** (`htmlspecialchars($val, ENT_XML1 | ENT_QUOTES, 'UTF-8')`). Karakter mentah seperti `&` (misal pada nama jabatan "Potensi & Promosi") akan merusak validitas OpenXML dan membuat LibreOffice gagal memuat file (`Error: source file could not be loaded`).
- **Konversi PDF Native**: Konversi PDF wajib menggunakan LibreOffice headless (`soffice --headless`). Pastikan template `.docx` valid sehingga tidak jatuh ke fallback DomPDF yang merusak margin, Kop Surat, dan tabel.

### 5. Template `notulen.docx` — Aturan Layout Kop & TTD
- **Kop Surat**: Border bawah tabel kop berada di dalam `<w:tblBorders>` tabel. Gunakan **satu garis tebal saja**: `<w:bottom w:val="single" w:sz="24" w:space="0" w:color="auto"/>`. Jangan pakai `thinThickSmallGap` (menghasilkan 2 garis) dan jangan menambah paragraf `w:pBdr` tambahan (menghasilkan garis ketiga/kelebihan).
- **Spacer**: Paragraf kosong di template di-set `<w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="exact"/>` agar tidak mewarisi `w:after="160"` dari `<w:pPrDefault>` (penyebab jarak antar bagian melebar). Jangan sisipkan paragraf kosong di antara garis kop dan judul `NOTULEN`.
- **Tabel TTD**: 3 baris × 2 kolom — baris 1 label + jabatan, baris 2 ruang tanda tangan (`<w:trHeight w:val="1440" w:hRule="atLeast"/>`), baris 3 nama (bold) + NIP. Struktur sel kiri & kanan harus identik agar tinggi setiap baris sejajar.
- Bagian dalam file `.docx` (header/footer) **tidak ada**; kop surat berada di tabel pertama pada `word/document.xml`.

### 6. Cara Edit Template `.docx` (tanpa binary editor di host)
Host tidak memiliki `unzip`; gunakan Python (`zipfile`) untuk membaca/menulis ulang `word/document.xml` di dalam `.docx`. Selalu:
1. Backup template dulu (mis. `cp notulen.docx /tmp/...bak`).
2. Baca `word/document.xml`, lakukan replace pada string XML, tulis ulang ke zip baru dengan semua entry asli dipertahankan.
3. Validasi hasil: `zipfile.testzip()` → `None`, dan `xml.dom.minidom.parseString()` sukses.
4. Verifikasi tidak ada placeholder yang hilang (`grep` seluruh `${...}`).
5. Setelah generate, verifikasi visual: convert ke PDF via `soffice` lalu render dengan `pdftoppm` dan periksa band/garis.

Contoh cepat verifikasi layout kop (deteksi jumlah garis):
```sh
docker exec -w /var/www dpmptsp-app php -r '...generateDocx...'   # atau service
# lalu unzip word/document.xml via python, cari "thinThickSmallGap" harus 0 dan pBdr tepat 1
```

### 7. Editor Word di Browser (Notulen & Surat Tugas)

Editor dokumen ada di panel Tool: `/tool/notulen/{id}/word` dan `/tool/surat-tugas-word` (halaman Filament `EditWordNotulen` / `EditWordSuratTugas`, `shouldRegisterNavigation = false`).

- **Dua tab**: `Pratinjau PDF` (default) dan `Edit Kanvas`. Pratinjau = iframe route PDF (`kepegawaian.notulen.pdf` / `kepegawaian.surat-tugas.pdf`) yang dirender LibreOffice dari snapshot — **identik dengan Cetak/Unduh**. Kanvas SuperDoc dimuat lazy saat tab Edit diklik.
- **Bedanya kanvas**: SuperDoc punya mesin layout sendiri; ukuran halaman benar (dibaca dari `pgSz`), tetapi paginasi baris bisa beda dari Word/LibreOffice. Jangan jadikan kanvas acuan hasil akhir — acuan = Cetak/Unduh.
- **Generate Ulang** me-reload halaman (bukan `superdoc.replaceFile()`) karena `replaceFile()` tidak menerapkan ulang `pgSz`; tanpa reload kanvas bisa tetap A4 walau berkas baru folio. Simpan (save) me-refresh iframe pratinjau.
- **Cetak** = ekspor kanvas (edit belum disimpan ikut) → `POST /tool/{notulen,surat-tugas}-word/{id}/print` → PDF LibreOffice dibuka di tab baru (tekan `Ctrl+P`).
- Berkas: `NotulenWordController` / `SuratTugasWordController` (`show`, `store`, `print`, `regenerate`, `debug`), boot JS `public/vendor/superdoc/nw-boot.js` / `stw-boot.js` (global `__nwBooted` vs `__stwBooted`), snapshot service `NotulenDocumentService` / `SuratTugasDocumentService` (`materialize`, `regenerate`, `replaceSnapshot`, `renderPdf`).
- Snapshot notulen butuh kolom `document_*` (migrasi `2026_11_03_000002_add_document_snapshot_to_notulen_table`, guarded `hasColumn`; APP_ENV=production → berikan SQL ke user).
- Template `notulen.docx` = folio (`<w:pgSz w:w="12189" w:h="18709"/>`), sama dengan `surat-tugas.docx`.
- Berkas snapshot di disk `arsip` harus dimiliki `www-data`; bila pernah diedit sebagai root, `chown -R www-data:www-data storage/arsip` agar simpan/regenerate tidak 500 (`UnableToWriteFile`).

## 6. Modul Kepegawaian — Generator Sertifikat (`/kepegawaian/generator-sertifikat`)

Halaman Livewire tunggal untuk: unggah gambar template, isi data Excel, tarik field ke kanvas, pratinjau per baris, unduh PDF satuan/batch (ZIP per 25).

### 1. Berkas Utama
- `src/app/Filament/Kepegawaian/Pages/CertificateGenerator.php` — halaman editor + state + autosave + unduh.
- `src/app/Filament/Kepegawaian/Pages/ManageCertificates.php` — daftar "Template Sertifikat".
- `src/resources/views/filament/kepegawaian/pages/certificate-generator.blade.php` — kanvas Alpine + CSS + dialog Excel.
- `src/app/Services/Certificate/{CertificateExcelParser,CertificateRenderer,CertificateNamingService}.php`.
- `src/app/Models/Certificate/*` + migrasi `2026_10_27_000240_create_certificate_generator_tables.php`.
- Route background privat: `kepegawaian.certificate.background` (`CertificateBackgroundController`, token base64 + cek ownership).
- `CertificateGenerator::shouldRegisterNavigation()` = `false`; menu hanya "Template Sertifikat".
- Buka template via `?template={id}` memakai `#[\Livewire\Attributes\Url(as: 'template')] public ?int $openTemplate`. **Jangan kosongkan di `mount()`** — query string akan hilang dan refresh jadi mode create.

### 2. Data Excel Terpisah dari Form (WAJIB)
- Form template hanya berisi `title`, `file_name_pattern`, `background`. **Tidak ada** `FileUpload::make('excel')`.
- Data Excel dikelola lewat dialog: properti `showExcelDialog`, `excelUpload`, `pendingExcelHeaders/Keys/Rows/Name`, `datasetDirty`; method `openExcelDialog()`, `closeExcelDialog()`, `updatedExcelUpload()`, `applyExcelDialog()`, `clearExcelData()`.
- Dialog menampilkan **semua baris** (scroll, header sticky). Saat edit template, `openExcelDialog()` mengisi `pending*` dari `$this->rows/$this->headers/$this->keys` sehingga data lama langsung terlihat.
- Dataset ditulis ke DB lewat `replaceDatasetForTemplate()` (hapus dataset lama, insert ulang) — dipanggil dari `applyExcelDialog()` via `persistDatasetSilently()` dan dari `save()` (hanya bila `$isNew` atau `datasetDirty`).
- `save()` tidak lagi menerima `CertificateExcelParser`; validasi memakai `$this->headers/keys/rows`.
- **CSS dialog wajib kelas sendiri (`.cert-modal-*`)**, bukan utility Tailwind. Build CSS Filament yang terpasang tidak memuat semua utility (`fixed inset-0`, `z-50`, dll), akibatnya popup tampil sebagai blok biasa di bawah halaman.

### 3. Header (Label) vs Key (Slug) Dataset
- `CertificateExcelParser` mengembalikan `headers` (label asli, mis. `Nama Lengkap`) dan `keys` (slug, mis. `nama_lengkap`). Baris data memakai **keys**.
- Halaman menyimpan keduanya: `public array $headers` dan `public array $keys`. Chip kolom, `addField()`/`addFieldAt()`, dan `labelForKey()` **wajib memakai `$keys`** (label hanya untuk tampilan). Dulu chip memakai label sehingga field baru tidak menemukan data → teks kosong di mode *show*.
- `applyDataset()` menerima `(headers, keys, rows, rowCount)` dan memanggil `normalizeFieldKeysToDataset()` untuk memetakan field lama berkey label (`Nama`) ke key slug (`nama`).
- Saat memuat template lama, `keys` diturunkan dari `array_keys($rows[0])`; bila dataset kosong, dari `Str::slug($header, '_')`.

### 4. Renderer PDF (`CertificateRenderer`) — Jebakan
- **`cssFont()` wajib memakai kutip TUNGGAL** (`'DejaVu Sans', sans-serif`). Style disisipkan ke atribut `style="..."`; nama font berkutip ganda menutup atribut lebih awal sehingga `font-size` hilang dan teks tercetak 12pt meski setting 36pt. Style juga di-`htmlspecialchars()` sebelum disisipkan.
- `fitText()` mengecilkan font bertahap (min 50%) hanya bila estimasi baris × line-height melebihi tinggi field.
- Dompdf `chroot` harus mencakup `storage/app/private/certificate/tmp` **dan** `storage_path('app/private')`; kalau tidak, background tidak muncul. `tempDir()` di `storage/app/private/certificate/tmp`.
- Posisi/ukuran field dalam mm absolut; kanvas hanya skala zoom.

### 5. Simpan Otomatis & Unduh
- Autosave senyap: `persistFieldsSilently()` dipanggil dari `addFieldAt`, `updateFieldGeometry`, `updateFieldProperties`, `removeField` (tanpa notifikasi).
- **Sebelum unduh, `persistDraftBeforeDownload()` dipanggil** di awal `downloadSingle()` dan `downloadRange()`: menyimpan judul, pola nama berkas, dan seluruh field terkini, lalu template dibaca ulang dari DB. Tanpa ini PDF memakai setting lama.
- Batch dibatasi 25 PDF per klik (`downloadBatchRangeAction()` + `downloadRange()`); Nginx `fastcgi_read_timeout`/`fastcgi_send_timeout` = 180s.
- Modal aksi halaman butuh `<x-filament-actions::modals />` di akhir blade (layout page hanya merender otomatis untuk tabel).

### 6. Kanvas (Alpine `certCanvas`)
- `pxPerMm` **tetap** `96 / 25.4`. Jangan mengukur ulang dari lebar render (fungsi `measure()` sudah dihapus): lebar halaman sudah dikali zoom, jadi pengukuran ulang membuat zoom berlipat dan kanvas terasa "auto zoom" saat state berubah.
- Zoom hanya dari slider (default 70%).
- Drag di area kosong halaman = **pan/scroll** viewport (`startPan/movePan/endPan`, kelas `.is-panning`), bukan memindah field; klik tanpa geser tetap `select(null)`.
- Garis bantu tengah: dua garis putus-putus (`.cert-guide-v/h`) muncul saat drag/resize atau chip dragover; menyala hijau + snap tepat ke tengah bila selisih ≤ 2 mm. `dragInfo` menampilkan x/y/titik tengah (mm).
- `uid` field harus dibuat **sebelum** `x-for` (IIFE `initialFields`); `pendingGeometry` mencegah blink saat drag.
- `wire:ignore` **hanya** pada `.cert-viewport`; toolbar/bar pratinjau jangan di dalamnya.
- Layout: `.cert-editor-grid` = 3:1 pada ≥1280px. Kolom kanan (`.cert-editor-side`) **selalu dirender**; bila belum ada field terpilih tampilkan card kosong `.cert-props-empty` agar tidak "hilang-muncul".

### 7. Cara Uji (Livewire, tanpa PHPUnit Assert)
`PHPUnit\Framework\Assert` tidak tersedia di container, jadi hindari `assertOk()`/`fillForm()`.
```php
$t = Livewire::withQueryParams(['template' => 9])->test(CertificateGenerator::class);
$t->call('openExcelDialog');
$t->set('pendingExcelKeys', ['nama'])->call('applyExcelDialog');
```
- State form di-set via `$t->set('data.title', '...')`, bukan `fillForm()`.
- Uji action: `$t->mountAction('x'); $t->set('mountedActions.0.data.fields', [...]); $effects = $t->call('callMountedAction')->effects;` (download ada di `$effects['download']['content']`, base64).
- Render PDF diverifikasi dengan `pdftotext -bbox` (tersedia di container) — tinggi glyph harus sebanding `font-size`.
- Setelah menulis file: `rm -f storage/framework/views/*.php; chown -R www-data:www-data storage bootstrap/cache`, lalu jalankan artisan sebagai `www-data`.

## 7. Modul Kepegawaian — Unduh Pegawai (`/kepegawaian/pegawai`)

- Tombol header **Unduh Pegawai** di `PegawaiProfilResource\Pages\ListPegawaiProfil::getHeaderActions()` membuka modal berisi `CheckboxList` semua field (`PegawaiFieldsExport::LABELS`, 44 field), 3 kolom, `searchable()`, `bulkToggleable()` (centang/hapus-centang semua), default `nip, nama, jabatan`.
- Export: `src/app/Exports/Kepegawaian/PegawaiFieldsExport.php` (`FromQuery`). Urutan kolom mengikuti urutan `LABELS`, bukan urutan centang. Nama berkas `data-pegawai-YYYYmmdd-His.xlsx`.
- Data mengikuti pencarian/filter tabel aktif dan **semua baris** (tanpa paginasi): `getFilteredTableQuery()`.
- **Field virtual** `nama_kapital` memakai accessor `getNamaKapitalAttribute()` (kapital tiap kata, gelar/singkatan utuh). Field virtual tidak boleh masuk `SELECT`; gunakan `PegawaiFieldsExport::databaseColumns()` yang juga menambahkan kolom sumber `nama`.
- Guard sort: kolom sort tabel bisa berupa agregat (`kompetensi_count`, `anak_count`) yang bukan kolom DB — jatuhkan ke `nama` bila tidak ada di `LABELS`.
- Kolom `masa_kerja_*` bukan kolom DB (dihitung), jangan dipakai untuk `orderBy`/`select`.

## 8. Beranda (`resources/views/dashboard.blade.php`) — Modul Aktif
Saat ini hanya modul **Arsip Digital** (semua user), **Kepegawaian** (`role == 1 || is_admin_kepegawaian`), dan **Add User** (`role == 1`) yang ditampilkan. Modul lain (Rapat Kita, Barang Kita/Smart Stok, Data Kita, Sikenut, Botman, Bot Manager, Siperdafit) **di-hide dengan komentar Blade** `{{-- ... --}}` — kode tetap utuh, jangan dihapus. Untuk menampilkan kembali cukup hapus pembungkus komentarnya.

## 9. API Generator Surat Cuti — Chrome Extension (`/api/v1/generator-surat-cuti`)

Hasil API **harus identik** dengan halaman Generator Surat Cuti UI. Satu-satunya sumber kebenaran: `SuratCutiGeneratorService::susun()` (parse, duplikat, potong sisa, nomor). Halaman **dan** controller API sama-sama memanggilnya — jangan duplikasi aturan di controller/page lain.

### 1. Auth & Scope
- Tanpa Sanctum (sengaja, tanpa dependensi baru). Autentikasi via middleware `api.key` (`app/Http/Middleware/AuthenticateApiKey.php`): header `X-API-Key` (atau `Authorization: Bearer`), hash SHA-256 dicocokkan ke `kepegawaian.api_keys.key_hash`.
- Model `App\Models\ApiKey` (koneksi `kepegawaian`): `terbitkan()` mengembalikan `[model, plaintext]` — plaintext hanya ada sekali, jangan log/disimpan ulang.
- Kunci dibuat/dicabut di panel Kepegawaian → Master → **API Key** (`ApiKeys.php` + `api-keys.blade.php`; blade juga memuat dokumentasi endpoint untuk pembuat extension).
- Scope tunggal: `generator-cuti`. Rate limit `throttle:60,1`.
- CORS: `config/cors.php` mengizinkan `chrome-extension://*` (`allowed_origins_patterns`); tanpa cookie/credentials.

### 2. Endpoint (prefix `/api/v1`, lihat `routes/api.php`)
- `GET generator-surat-cuti/berikutnya?kode=800` → `{nomor_awal, tahun, import_hari_ini}`. Nomor awal = sekuens bulan terbaru + 1 di `cuti.no_surat` **dan** `riwayat_generator_cuti` (non-duplikat) — panggilan beruntun tidak dapat nomor kembar.
- `GET generator-surat-cuti/pegawai?q=` → lookup `nip, nama, status_pegawai, jabatan` (maks 25).
- `POST generator-surat-cuti` → terima `teks` (tempelan mentah) ATAU `rows[]` (`nama, jenis, hari, tanggal_mulai, tanggal_selesai?, pengajuan_at`); validasi → `susun()` → simpan batch riwayat (`sumber='extension'`) → respons 201 berisi `hasil` (nomor, sisa, flag duplikat), `duplikat`, `tak_dikenali`, `nomor_berikutnya`.
- Gate `Cuti::sudahImporHariIni()` berlaku juga di API (bisa dilepas per-request via `"abaikan_gate": true`). Cek duplikat silang riwayat opsional via `"cek_riwayat": true` (default mati).
- Maks 200 rincian/request. Respons error: 401 (kunci salah/dicabut), 403 (scope), 409 `belum_impor`, 422 (validasi), 429 (limit).

### 3. Batasan Keras
- **Jangan tulis ke tabel `cuti`** dari API — hanya `riwayat_generator_cuti` (kolom `sumber`, `sumber_label`, `teks_mentah`, `is_duplikat`). Verifikasi: hitung baris `cuti` sebelum/sesudah uji.
- Migrasi API: `2026_10_30_000003_create_api_keys_table.php`, `2026_10_30_000004_add_sumber_to_riwayat_generator_cuti.php` (keduanya guarded `hasTable`/`hasColumn`; APP_ENV=production → berikan SQL ke user, jangan `migrate`).
- UI Generator Surat Cuti memakai `simpanRiwayatBatch()` yang sama (sumber `manual`); duplikat tidak memotong sisa dua kali; baris duplikat tidak punya nomor.
- **Hitung hari kerja**: angka "N hari" tertulis DIABAIKAN — durasi dihitung ulang dari rentang tanggal (lewati Sabtu/Minggu + `hari_libur`). `parseRincian()` dan `dariRows()` memakai `hariKerja()` + `rentangTanggal()` yang sama. Rentang valid hanya 2 pola: `13 - 17 November 2026` dan `05 Oktober 2026`; di luar itu baris ditolak (teks → `null` → `tak_dikenali`, rows → `errors`). Rentang 0 hari kerja juga ditolak.
- **Nomor kronologis pengajuan**: `susun()` memipihkan semua rincian, mengurutkan kronologis berdasarkan `pengajuan_at` (stabil — pengajuan sama mempertahankan urutan tempel), lalu menampilkan + memberi nomor berurutan dari `nomor_awal`. No 1 = pengajuan terlama = nomor terkecil. Berlaku untuk API dan UI.

### 4. Mirror Google Sheets Eksternal (POST API + UI)
- Setiap `POST /api/v1/generator-surat-cuti` yang sukses: simpan batch riwayat dulu, lalu `GeneratorCutiSheetsMirror::syncAll()` menulis ulang **seluruh** isi tab dari `riwayat_generator_cuti` (rewrite full mirror, idempotent).
- Halaman UI Generator Surat Cuti juga ikut mirror: `GeneratorSuratCuti::simpanOtomatis()` (setiap Generate) dan `hapusRiwayat()` (setiap hapus batch) memanggil `syncAll()` yang sama — tapi gagal sync hanya notifikasi warning, nomor + riwayat DB tetap jalan (berbeda dengan API yang 500).
- Kredensial & spreadsheet **terpisah** dari RapatKita: `config/generatorcuti.php` (`GENERATORCUTI_SHEETS_ENABLED/CREDENTIALS_PATH/ID/TAB`, default tab `Riwayat`). Service: `src/app/Services/Kepegawaian/GeneratorCutiSheetsMirror.php`; kolom sheet = Batch, Waktu, Sumber, Sumber Label, Nama, NIP (lookup `pegawai_profils`), Jenis, Hari, Rentang, Pengajuan, Nomor Surat, Duplikat (YA/kosong), Dibuat Oleh, Sisa.
- Gagal sync = API balas **500** (`Gagal menulis ke Google Sheets: ...`, tetap sertakan `batch`); riwayat DB tetap tersimpan. `syncAll()` juga melempar bila mirror belum dikonfigurasi — jadi API 500 sampai env diisi.
- Setup: `php artisan generatorcuti:init-sheets` (`InitGeneratorCutiSheets.php`) — buat tab, sync awal, verifikasi share ke service account. Blade `api-keys` mendokumentasikan perilaku ini.


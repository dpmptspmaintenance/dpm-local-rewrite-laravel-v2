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

### 7. Beranda (`resources/views/dashboard.blade.php`) — Modul Aktif
Saat ini hanya modul **Arsip Digital** (semua user), **Kepegawaian** (`role == 1 || is_admin_kepegawaian`), dan **Add User** (`role == 1`) yang ditampilkan. Modul lain (Rapat Kita, Barang Kita/Smart Stok, Data Kita, Sikenut, Botman, Bot Manager, Siperdafit) **di-hide dengan komentar Blade** `{{-- ... --}}` — kode tetap utuh, jangan dihapus. Untuk menampilkan kembali cukup hapus pembungkus komentarnya.


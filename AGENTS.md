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

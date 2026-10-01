# Database

Dua database MySQL 8, satu aplikasi Laravel 13. Koneksi default `mysql` → **kepegawaian**, koneksi bernama `data_local` → **data_local**. Skema berasal dari SQL dump di `db/` (bukan migration Laravel — `app/database/migrations/` kosong).

| Koneksi | Database | Isi |
| --- | --- | --- |
| `mysql` (default) | `kepegawaian` | Data kepegawaian: cuti, hari libur, profil pegawai, anak, kompetensi, masuk |
| `data_local` | `data_local` | `users` saja — tabel login/identitas, **dipakai bersama** oleh aplikasi internal lain |

Konfigurasi koneksi: `app/config/database.php`. `App\Models\User` set `$connection = 'data_local'`; model lain pakai koneksi default.

---

## Database `kepegawaian`

### `pegawai_profil` — profil pegawai (read-only, sumber eksternal)

Primary key: `nip` (string, non-incrementing).

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `nip` | varchar(50) | PK |
| `nama` | varchar(255) | NOT NULL |
| `pangkat` | varchar(50) | |
| `golongan` | varchar(50) | |
| `jabatan` | varchar(255) | |
| `status_pegawai` | varchar(150) | mis. `PEGAWAI NEGERI SIPIL`, `... PERJANJIAN KERJA` |
| `gender` | varchar(30) | |
| `agama` | varchar(50) | |
| `tempat_lahir` | varchar(100) | |
| `tanggal_lahir` | date | |
| `usia_keterangan` | varchar(100) | teks bebas, bukan angka (mis. `57 tahun 10 bulan`) |
| `status_perkawinan` | varchar(50) | |
| `pendidikan` | varchar(255) | |
| `kelas_jabatan` | varchar(20) | |
| `capaian_bangkom` | varchar(50) | JP |
| `bup_but` | varchar(50) | usia pensiun (mis. `58 Tahun`) |
| `tmt_bup_but` | date | TMT pensiun — dipakai `PegawaiProfil::statusPensiun()` |
| `tmt_golongan` | date | |
| `kgb_selanjutnya` | date | |
| `nip_lama` | varchar(50) | |
| `alamat_ktp` | text | |
| `rt_ktp` / `rw_ktp` | varchar(10) | |
| `kelurahan_ktp` / `kecamatan_ktp` / `kota_ktp` / `provinsi_ktp` | varchar(100) | |
| `kode_pos_ktp` | varchar(10) | |
| `alamat_domisili` | text | |
| `rt_domisili` / `rw_domisili` | varchar(10) | |
| `kelurahan_domisili` / `kecamatan_domisili` / `kota_domisili` / `provinsi_domisili` | varchar(100) | |
| `kode_pos_domisili` | varchar(10) | |
| `jenis_domisili` | varchar(100) | |
| `sumber_url` | varchar(255) | URL SISDM sumber |
| `created_at` | timestamp | |

Model: `App\Models\PegawaiProfil`. Relasi: `anak()`, `kompetensi()`, `masuk()`, `cuti()`.

### `pegawai_anak` — data anak pegawai (KP4)

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | int unsigned | PK AUTO_INCREMENT |
| `nip` | varchar(50) | NOT NULL, FK → `pegawai_profil.nip` ON DELETE CASCADE |
| `no_urut` | int | |
| `nama_anak` | varchar(255) | |
| `gender_anak` | varchar(30) | |
| `tempat_lahir_anak` | varchar(100) | |
| `tanggal_lahir_anak` | date | dipakai hitung usia/batas tunjangan |
| `usia_anak` | varchar(100) | teks |
| `tingkat_pendidikan_anak` | varchar(100) | `Diploma III`/`S-1`/… → batas tunjangan 25 vs 21 |
| `tunjangan_anak` | varchar(50) | `Dapat`/`Tidak`/`....` |
| `hubungan_keluarga_anak` | varchar(255) | |

Model: `App\Models\PegawaiAnak`. FK resmi ke `pegawai_profil`.

### `pegawai_kompetensi` — riwayat kompetensi/sertifikat

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | int | PK AUTO_INCREMENT |
| `nip` | varchar(100) | NOT NULL, FK → `pegawai_profil.nip` ON DELETE CASCADE |
| `jenis` | varchar(100) | |
| `jumlah_jam` | int | JP (jam pelajaran) |
| `nama_kompetensi` | text | |
| `nomor_sertifikat` | varchar(150) | |
| `penyelenggara` | varchar(255) | |
| `tanggal_mulai` | date | |
| `tanggal_selesai` | date | |
| `tanggal_sertifikat` | date | dipakai filter tahun di rekap kompetensi |
| `created_at` | timestamp | |

Model: `App\Models\PegawaiKompetensi`. FK resmi ke `pegawai_profil`.

### `pegawai_masuk` — TMT masuk

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | int unsigned | PK AUTO_INCREMENT |
| `nip` | varchar(100) | NOT NULL, UNIQUE |
| `tanggal_masuk` | date | NOT NULL |
| `keterangan` | varchar(255) | |

Model: `App\Models\PegawaiMasuk`. Belum dipakai di UI mana pun.

### `cuti` — data cuti

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | PK AUTO_INCREMENT |
| `no_surat` | varchar(100) | NULLable (live) — sumber boleh kosong/berulang |
| `nip` | varchar(100) | NULLable (live), **bukan FK** (kolom polos) |
| `nama` | varchar(255) | NULLable (live) |
| `tanggal_mulai_diajukan` | date | NOT NULL |
| `tanggal_selesai_diajukan` | date | NOT NULL |
| `durasi_hari` | int | default 0, **semua baris 0** di sumber |
| `opd` | varchar(255) | |
| `unit_kerja` | varchar(255) | |
| `lokasi_kerja` | varchar(255) | |
| `status` | varchar(100) | |
| `keperluan` | text | |
| `jenis` | varchar(100) | `Cuti Tahunan` / `Cuti Sakit` / `Cuti Melahirkan` / `Cuti Karena Alasan Penting` |
| `created_at` | timestamp | |

Model: `App\Models\Cuti`. `profil()` = `belongsTo(PegawaiProfil, 'nip', 'nip')` bisa null (NIP nyasar).

### `hari_libur` — kalender libur

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | int unsigned | PK AUTO_INCREMENT |
| `tanggal` | date | NOT NULL, UNIQUE |
| `keterangan` | varchar(255) | |

Model: `App\Models\HariLibur`.

---

## Database `data_local`

### `users` — identitas/login (dipakai bersama)

Primary key: `id`. Unik: `email`, `google_id`.

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | PK AUTO_INCREMENT |
| `nama` | varchar(100) | NOT NULL |
| `status_jabatan` | varchar(100) | PNS / PPPK |
| `nip` | varchar(100) | bisa kosong; kadang tanpa sufiks 3 digit dibanding `pegawai_profil.nip` |
| `pangkat` | varchar(100) | |
| `role` | tinyint unsigned | default 6 |
| `bidang` | varchar(100) | |
| `is_aktif` | tinyint(1) | default 1 |
| `google_id` | varchar(255) | UNIQUE |
| `email` | varchar(255) | UNIQUE, NOT NULL |
| `name` | varchar(255) | nama display Google |
| `avatar` | varchar(255) | |
| `password` | varchar(255) | tidak dipakai (auth Google) |
| `email_verified_at` | timestamp | |
| `remember_token` | varchar(100) | |
| `created_at` / `updated_at` | timestamp | |
| `shared_pages` | json | daftar slug aplikasi lain (`rapat_kita`, `sikenut`, `data_kita`, …) |
| `is_bpp` | tinyint | default 0 |
| `is_admin_persediaan` | tinyint | default 0 |
| `is_admin_kepegawaian` | tinyint | default 0 |

Model: `App\Models\User` (`$connection = 'data_local'`). Email login wajib cocok dengan `ADMIN_ALLOWED_EMAIL` (`app/.env`).

---

## Relasi antar tabel

```
pegawai_profil (nip, PK)
 ├──< pegawai_anak        (nip FK, CASCADE)
 ├──< pegawai_kompetensi  (nip FK, CASCADE)
 ├──< pegawai_masuk       (nip UNIQUE, BUKAN FK)
 └──< cuti                (nip kolom polos, BUKAN FK — bisa null)

data_local.users  — tidak ada FK silang ke pegawai_profil.
                   Rekonsiliasi manual lewat NIP, tapi NIP bisa beda string
                   (pegawai_profil.nip kadang +3 digit sufiks).
```

## Quirk data (bawaan dari dump sumber)

- NIP tidak divalidasi sumber: ada `-`, `--`, kosong. `data_local.users` punya email placeholder `user_NN@dummy.local` dan `--`/`---`.
- `cuti.durasi_hari` semua 0 → perhitungan hari pakai `DATEDIFF(tanggal_selesai, tanggal_mulai) + 1`.
- `cuti.nip` bukan FK, jadi baris cuti bisa punya NIP yang tidak ada di `pegawai_profil`.
- `usia_keterangan`/`usia_anak` teks bebas, bukan angka — tidak dipakai untuk hitung.

## Perbedaan dump vs live DB

`db/dump-kepegawaian-*.sql` masih merekam `cuti` dengan `no_surat`/`nip`/`nama` `NOT NULL` dan `UNIQUE KEY cuti_no_surat_unique`. Live DB sudah di-`ALTER`:

- `no_surat` / `nip` / `nama` → NULLable
- `cuti_no_surat_unique` → di-drop (sumber sah mengulang `no_surat`)

Karena init DB hanya jalan sekali (volume kosong), `ALTER` ini **tidak ada di dump** — kalau volume di-wipe dan diimpor ulang, `ALTER` harus dijalankan ulang manual.

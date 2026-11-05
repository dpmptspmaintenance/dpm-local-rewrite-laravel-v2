<x-filament-panels::page>
    <div
        x-data="{ kunci: '', nama: '' }"
        x-on:api-key-dibuat.window="kunci = $event.detail.kunci; nama = $event.detail.nama; $el.scrollIntoView({behavior: 'smooth'})"
    >
        <template x-if="kunci !== ''">
            <div class="sc-keybox">
                <p class="sc-keybox-title">Kunci baru untuk "<span x-text="nama"></span>" — salin sekarang, tidak bisa dilihat lagi:</p>
                <div class="sc-keybox-row">
                    <code class="sc-key" x-text="kunci"></code>
                    <x-filament::button
                        type="button" size="sm" icon="heroicon-o-clipboard-document"
                        x-on:click="navigator.clipboard.writeText(kunci).catch(() => {})"
                    >
                        Salin
                    </x-filament::button>
                    <x-filament::button type="button" size="sm" color="gray" x-on:click="kunci = ''">
                        Tutup
                    </x-filament::button>
                </div>
            </div>
        </template>

        {{ $this->table }}

        <p class="sc-hint" style="margin-top:.75rem;">
            Dokumentasi lengkap semua endpoint + contoh JSON (termasuk CRUD Rekap Absen Bulanan):
            <a href="{{ \App\Filament\Kepegawaian\Pages\ApiDocs::getUrl() }}">buka halaman Dokumentasi API</a>.
        </p>
    </div>

    <div class="sc-doc">
        <h2 class="sc-doc-title">Dokumentasi API — Generator Surat Cuti</h2>
        <p class="sc-hint">Base URL: <code>{{ url('/api/v1') }}</code> · Autentikasi: header <code>X-API-Key: dpmk_...</code> (atau <code>Authorization: Bearer dpmk_...</code>) · Limit 60 request/menit per kunci · Hasil <strong>identik</strong> dengan halaman Generator Surat Cuti (nomor, sisa, flag duplikat) dan otomatis tersimpan di <code>riwayat_generator_cuti</code> dengan <code>sumber = extension</code> + mirror ke Google Sheets eksternal (<code>GENERATORCUTI_SHEETS_*</code>; gagal sync = <code>500</code>).</p>

        <h3 class="sc-doc-h">1. GET — nomor awal berikutnya</h3>
        <pre class="sc-pre">GET /api/v1/generator-surat-cuti/berikutnya?kode=800</pre>
        <p class="sc-hint">Respons: <code>{"ok":true,"kode":"800","nomor_awal":1225,"tahun":[2026,2025,2024],"import_hari_ini":true}</code></p>

        <h3 class="sc-doc-h">2. GET — cari pegawai (resolve nama → NIP)</h3>
        <pre class="sc-pre">GET /api/v1/generator-surat-cuti/pegawai?q=SANTI</pre>
        <p class="sc-hint">Respons: <code>{"ok":true,"jumlah":1,"data":[{"nip":"...","nama":"SANTI TRI WAHYUNI","status_pegawai":"...","jabatan":"..."}]}</code></p>

        <h3 class="sc-doc-h">3. POST — olah + simpan (utama)</h3>
        <pre class="sc-pre">POST /api/v1/generator-surat-cuti
Content-Type: application/json
X-API-Key: dpmk_...</pre>
        <p class="sc-hint">Dua bentuk body (salah satu):</p>
        <pre class="sc-pre">{"teks": "1. SANTI TRI WAHYUNI\n   - Cuti Sakit 2 hari (29 - 30 September 2026) pengajuan 05-10-2026 15:49:19",
 "kode": "800", "sumber_label": "ext-ecuti-v1"}</pre>
        <pre class="sc-pre">{"rows": [{"nama": "SANTI TRI WAHYUNI", "jenis": "Cuti Sakit", "hari": 2,
  "tanggal_mulai": "2026-09-29", "tanggal_selesai": "2026-09-30",
  "pengajuan_at": "2026-10-05 15:49:19"}],
 "kode": "800", "nomor_awal": 1182, "tahun": [2026,2025,2024],
 "sumber_label": "ext-ecuti-v1", "cek_riwayat": false, "abaikan_gate": false}</pre>
        <ul class="sc-list">
            <li><code>kode</code> opsional (default <code>800</code>).</li>
            <li><code>nomor_awal</code> opsional — kosong = lanjut otomatis dari nomor terakhir (cuti + riwayat).</li>
            <li><code>tahun</code> opsional — kolom sisa, default tahun berjalan + 2 sebelumnya, urut menurun.</li>
            <li><code>cek_riwayat</code> opsional (default <code>false</code>) — <code>true</code> = rincian yang nomornya sudah ada di riwayat ikut ditandai <code>duplikat</code>.</li>
            <li>Maksimal 200 rincian per request.</li>
            <li>Wajib impor cuti hari ini (gate sama seperti halaman), kecuali <code>"abaikan_gate": true</code>.</li>
        </ul>
        <p class="sc-hint">Respons (201):</p>
        <pre class="sc-pre">{"ok": true, "batch": "20261030-081500-32", "kode": "800",
 "tahun": [2026,2025,2024], "nomor_awal": 1182, "nomor_berikutnya": 1185,
 "jumlah": 3, "jumlah_bernomor": 2, "jumlah_duplikat": 1,
 "hasil": [{"no":1, "nama":"...", "nip":"...", "pengajuan":"...",
   "nomor":"1182/800/X/2026", "jenis":"Cuti Sakit", "hari":2,
   "sisa":{"2026":10,"2025":0,"2024":0}, "duplikat":false}],
 "duplikat": [{"nama":"...","pengajuan":"...","jenis":"...","mentah":"..."}],
 "tak_dikenali": [], "tersimpan": true}</pre>

        <h3 class="sc-doc-h">Kode error</h3>
        <ul class="sc-list">
            <li><code>401</code> — kunci tidak dikirim / salah / dicabut / kadaluarsa.</li>
            <li><code>403</code> — kunci tanpa scope yang dibutuhkan.</li>
            <li><code>409</code> + <code>{"kode_error":"belum_impor"}</code> — impor cuti hari ini belum dilakukan.</li>
            <li><code>422</code> — input tidak valid (pesan di <code>error</code>, rincian di <code>errors</code>).</li>
            <li><code>429</code> — limit terlampaui, coba lagi semenit kemudian.</li>
        </ul>

        <h3 class="sc-doc-h">Contoh curl</h3>
        <pre class="sc-pre">curl -X POST {{ url('/api/v1/generator-surat-cuti') }} \
  -H "Content-Type: application/json" \
  -H "X-API-Key: dpmk_GANTI_DENGAN_KUNCI" \
  -d '{"rows":[{"nama":"SANTI TRI WAHYUNI","jenis":"Cuti Sakit","hari":2,
       "tanggal_mulai":"2026-09-29","tanggal_selesai":"2026-09-30",
       "pengajuan_at":"2026-10-05 15:49:19"}]}'</pre>

        <h3 class="sc-doc-h">Catatan untuk extension</h3>
        <ul class="sc-list">
            <li>Aturan potong sisa sama seperti halaman: Tahunan selalu; Alasan Penting hanya PPPK; Sakit/Melahirkan/Besar tidak memotong.</li>
            <li>Tahun minus tampil 0 dan memotong tahun depannya; tahun kolom selain terakhir dibatasi 6.</li>
            <li>Duplikat (nama + tgl pengajuan sama) tidak diberi nomor dan tidak memotong sisa lagi.</li>
            <li>Hitung hari: angka tertulis <strong>diabaikan</strong> — durasi dihitung ulang sebagai hari kerja dari rentang (lewati Sabtu/Minggu + <code>hari_libur</code>). Cth: tertulis 5 hari (13–17 Nov 2026) → tercatat 3 hari. Rentang valid: <code>13 - 17 November 2026</code> atau <code>05 Oktober 2026</code>; di luar itu baris ditolak (teks → <code>tak_dikenali</code>, rows → <code>errors</code>).</li>
            <li>Tidak ada penulisan ke tabel <code>cuti</code> — hanya arsip <code>riwayat_generator_cuti</code>.</li>
        </ul>
    </div>

    <div class="sc-doc">
        <h2 class="sc-doc-title">Dokumentasi API — Rekap Absen Bulanan</h2>
        <p class="sc-hint">Base URL: <code>{{ url('/api/v1') }}</code> · Scope: <code>rekap-absen</code> · Autentikasi: header <code>X-API-Key: dpmk_...</code> · Data ditulis <strong>hanya lewat API</strong>; panel Kepegawaian baca saja (tanpa create/edit/hapus). Unggah ulang dengan <code>nip + tahun + bulan</code> yang sama akan <strong>mengganti</strong> seluruh rincian harian + data dukung periode itu (upsert).</p>

        <h3 class="sc-doc-h">1. POST — unggah / perbarui rekap</h3>
        <pre class="sc-pre">POST /api/v1/rekap-absen
Content-Type: application/json
X-API-Key: dpmk_...</pre>
        <pre class="sc-pre">{"nip": "199303252025061002",
 "nama": "RIYANTO NUGROHO, A.Md.Kom.",
 "jabatan": "Pranata Sumber Daya Manusia Aparatur Terampil",
 "opd": "Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu",
 "bulan": 10, "tahun": 2026,
 "sumber_label": "ext-rekap-absen-v1",
 "ringkasan": {"hari_kerja": 22, "hadir": 22, "tidak_hadir": 0,
               "konfirmasi": 3, "keterlambatan": "00:00:00"},
 "harian": [{"tanggal": "2026-10-01", "hari": "KAMIS",
   "jam_hadir": "2026-10-01 07:38:08", "hadir_selisih": "00:21:52",
   "jam_pulang": "2026-10-01 16:01:58", "pulang_selisih": "00:01:58",
   "status": "-"}],
 "data_dukung": [{"tanggal_mulai": "2026-10-09", "tanggal_selesai": "2026-10-09",
   "keterangan": "St Lion Pro", "jenis": "Konfirmasi",
   "token": "eyJpdiI6Inp0ZWRqaXhKNHNBWHF2ZUpuWWFVYlE9PSIs..."}]}</pre>
        <ul class="sc-list">
            <li><code>nip</code>, <code>tahun</code>, <code>bulan</code> wajib. <code>bulan</code> boleh angka <code>10</code> atau nama <code>"Oktober"</code>.</li>
            <li><code>nama</code>/<code>jabatan</code>/<code>opd</code> opsional — kosong = diambil dari <code>pegawai_profil</code> berdasarkan NIP.</li>
            <li><code>ringkasan</code> opsional; kunci alternatif yang diterima: <code>masuk</code>/<code>tidak_masuk</code>/<code>total_keterlambatan</code>.</li>
            <li><code>harian</code> maks 31 baris. <code>tanggal</code> terima <code>2026-10-01</code> atau <code>01-10-2026</code>; <code>jam_hadir</code>/<code>jam_pulang</code> butuh tanggal + jam (format <code>Y-m-d H:i:s</code> / <code>d-m-Y H:i:s</code>).</li>
            <li><code>data_dukung</code> maks 100 baris. <code>token</code> = nilai dari <code>lihat_dd('...')</code>; boleh juga kirim <code>url</code> penuh (token diambil otomatis). Tautan dibentuk sebagai <code>{{ \App\Models\Kepegawaian\RekapAbsenDataDukung::FILE_BASE }}&lt;token&gt;</code>.</li>
            <li>Batch: kirim <code>{"items": [ ...rekap1, ...rekap2 ]}</code> — maks 12 rekap per request.</li>
        </ul>
        <p class="sc-hint">Respons:</p>
        <pre class="sc-pre">{"ok": true, "jumlah": 1, "data": [{"id": 12, "nip": "...", "nama": "...",
 "bulan": 10, "tahun": 2026, "periode": "Oktober 2026", "hari_kerja": 22,
 "hadir": 22, "tidak_hadir": 0, "konfirmasi": 3, "keterlambatan": "00:00:00",
 "jumlah_harian": 22, "jumlah_data_dukung": 3, "aksi": "dibuat"}]}</pre>
        <p class="sc-hint"><code>aksi</code>: <code>dibuat</code> | <code>diperbarui</code>.</p>

        <h3 class="sc-doc-h">2. GET — daftar rekap</h3>
        <pre class="sc-pre">GET /api/v1/rekap-absen?nip=199303252025061002&tahun=2026&bulan=10</pre>
        <p class="sc-hint">Semua parameter opsional. Maks 200 baris, urut tahun/bulan terbaru. Respons sama seperti contoh di atas (tanpa <code>aksi</code>).</p>

        <h3 class="sc-doc-h">3. GET — detail (termasuk harian + data dukung)</h3>
        <pre class="sc-pre">GET /api/v1/rekap-absen/{id}</pre>
        <p class="sc-hint">Respons menambahkan <code>harian[]</code> (tanggal, hari, jam_hadir, hadir_selisih, jam_pulang, pulang_selisih, status) dan <code>data_dukung[]</code> (tanggal_mulai, tanggal_selesai, keterangan, jenis, tautan).</p>

        <h3 class="sc-doc-h">Kode error</h3>
        <ul class="sc-list">
            <li><code>401</code> — kunci tidak dikirim / salah / dicabut / kadaluarsa.</li>
            <li><code>403</code> — kunci tanpa scope <code>rekap-absen</code>.</li>
            <li><code>404</code> — id rekap tidak ditemukan.</li>
            <li><code>422</code> — payload tidak valid (rincian per item di <code>errors</code>).</li>
            <li><code>429</code> — limit 60 request/menit terlampaui.</li>
        </ul>

        <h3 class="sc-doc-h">Contoh curl</h3>
        <pre class="sc-pre">curl -X POST {{ url('/api/v1/rekap-absen') }} \
  -H "Content-Type: application/json" \
  -H "X-API-Key: dpmk_GANTI_DENGAN_KUNCI" \
  -d '{"nip":"199303252025061002","bulan":10,"tahun":2026,
       "ringkasan":{"hari_kerja":22,"hadir":22,"tidak_hadir":0,"konfirmasi":3,"keterlambatan":"00:00:00"},
       "harian":[{"tanggal":"2026-10-01","hari":"KAMIS",
         "jam_hadir":"2026-10-01 07:38:08","hadir_selisih":"00:21:52",
         "jam_pulang":"2026-10-01 16:01:58","pulang_selisih":"00:01:58","status":"-"}],
       "data_dukung":[{"tanggal_mulai":"2026-10-09","tanggal_selesai":"2026-10-09",
         "keterangan":"St Lion Pro","jenis":"Konfirmasi","token":"eyJpdiI6..."}]}'</pre>

        <h3 class="sc-doc-h">Catatan untuk extension</h3>
        <ul class="sc-list">
            <li>Scrape dari modal rekap bulanan (tabel <code>#rekap_bulanan</code>, <code>.rb-summary</code>, <code>#rekap_datadukung</code>) lalu POST seperti contoh.</li>
            <li>Kirim ulang periode yang sama bila data di web presensi berubah — rincian lama otomatis diganti, tidak menumpuk.</li>
            <li>Tidak ada endpoint hapus; header hanya bisa dibuat/diperbarui.</li>
            <li><code>status</code> "-" disimpan sebagai kosong; <code>KONFIRMASI</code> disimpan apa adanya.</li>
        </ul>
    </div>

    <x-filament-actions::modals />

    @assets
    <style>
        .sc-keybox { margin-bottom: 1rem; padding: 1rem 1.25rem; border-radius: .5rem; background: #ecfdf5; border: 1px solid #6ee7b7; }
        .dark .sc-keybox { background: #064e3b; border-color: #047857; }
        .sc-keybox-title { margin: 0 0 .5rem; font-size: .875rem; font-weight: 600; }
        .sc-keybox-row { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .sc-key { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; word-break: break-all; background: #fff; border: 1px solid #d1d5db; border-radius: .375rem; padding: .375rem .625rem; }
        .dark .sc-key { background: #111827; border-color: #374151; }
        .sc-doc { margin-top: 1.5rem; display: grid; gap: .5rem; }
        .sc-doc-title { margin: 0; font-size: 1.125rem; font-weight: 600; }
        .sc-doc-h { margin: .75rem 0 0; font-size: .95rem; font-weight: 600; }
        .sc-hint { font-size: .8rem; color: #6b7280; margin: .25rem 0 0; }
        .dark .sc-hint { color: #9ca3af; }
        .sc-hint code, .sc-list code, .sc-doc-h code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .78rem; background: rgba(107,114,128,.15); border-radius: .25rem; padding: 0 .3rem; }
        .sc-pre { margin: .35rem 0 0; padding: .6rem .8rem; border-radius: .5rem; background: #111827; color: #e5e7eb; font-size: .76rem; line-height: 1.5; overflow-x: auto; white-space: pre; }
        .sc-list { margin: .35rem 0 0; padding-left: 1.25rem; list-style: disc; font-size: .82rem; color: #4b5563; display: grid; gap: .15rem; }
        .dark .sc-list { color: #d1d5db; }
    </style>
    @endassets
</x-filament-panels::page>

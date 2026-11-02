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

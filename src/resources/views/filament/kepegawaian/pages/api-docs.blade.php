<x-filament-panels::page>
    <div class="apidoc">
        <div class="apidoc-toc">
            <span class="apidoc-toc-label">Isi halaman:</span>
            <a href="#umum">Autentikasi</a>
            <a href="#rekap-absen">Rekap Absen Bulanan</a>
            <a href="#rekap-create">Create</a>
            <a href="#rekap-read">Read</a>
            <a href="#rekap-update">Update</a>
            <a href="#rekap-schema">Skema JSON</a>
            <a href="#rekap-contoh">Contoh lengkap</a>
            <a href="#tpp">Data Dukung TPP</a>
            <a href="#cuti">Generator Surat Cuti</a>
            <a href="#error">Kode error</a>
        </div>

        <section id="umum" class="apidoc-section">
            <h2 class="apidoc-h2">1. Autentikasi &amp; aturan umum</h2>
            <table class="apidoc-table">
                <tbody>
                <tr><th>Base URL</th><td><code>{{ url('/api/v1') }}</code></td></tr>
                <tr><th>Header wajib</th><td><code>X-API-Key: dpmk_...</code> (alternatif: <code>Authorization: Bearer dpmk_...</code>)</td></tr>
                <tr><th>Content-Type</th><td><code>application/json</code></td></tr>
                <tr><th>Rate limit</th><td>60 request/menit per kunci (lebih dari itu <code>429</code>)</td></tr>
                <tr><th>Buat kunci</th><td>Panel Kepegawaian → Master → <strong>API Key</strong> → "Buat Kunci Baru". Plaintext hanya tampil sekali.</td></tr>
                <tr><th>Scope</th><td><code>generator-cuti</code> dan/atau <code>rekap-absen</code> — kunci hanya bisa memanggil endpoint sesuai scope-nya.</td></tr>
                </tbody>
            </table>
            <p class="apidoc-note">Kunci tanpa scope yang sesuai dibalas <code>403</code>. Kunci yang dicabut/kadaluarsa dibalas <code>401</code>.</p>
        </section>

        <section id="rekap-absen" class="apidoc-section">
            <h2 class="apidoc-h2">2. Rekap Absen Bulanan — ringkasan CRUD</h2>
            <p class="apidoc-p">Satu rekap = satu pegawai (<code>nip</code>) untuk satu periode (<code>bulan</code> + <code>tahun</code>). Data hanya bisa masuk lewat API; panel Kepegawaian bersifat baca saja.</p>
            <table class="apidoc-table">
                <thead>
                <tr><th>Operasi</th><th>Method &amp; path</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                <tr><td><strong>C</strong>reate</td><td><code>POST /api/v1/rekap-absen</code></td><td>Kirim rekap baru. Respons memuat <code>"aksi": "dibuat"</code>.</td></tr>
                <tr><td><strong>R</strong>ead</td><td><code>GET /api/v1/rekap-absen</code><br><code>GET /api/v1/rekap-absen/{id}</code></td><td>Daftar (maks 200) atau detail satu rekap termasuk rincian harian + data dukung.</td></tr>
                <tr><td><strong>U</strong>pdate</td><td><code>POST /api/v1/rekap-absen</code></td><td>Kirim ulang <code>nip + tahun + bulan</code> yang sama. Seluruh <code>harian</code> + <code>data_dukung</code> periode itu <strong>diganti</strong> (bukan ditambah). Respons <code>"aksi": "diperbarui"</code>.</td></tr>
                <tr><td><strong>D</strong>elete</td><td>—</td><td>Tidak ada endpoint hapus (sesuai keputusan modul). Data hanya bisa dibuat/diperbarui.</td></tr>
                </tbody>
            </table>
        </section>

        <section id="rekap-create" class="apidoc-section">
            <h2 class="apidoc-h3">2.1 Create — kirim rekap baru</h2>
            <pre class="apidoc-pre">POST /api/v1/rekap-absen
Content-Type: application/json
X-API-Key: dpmk_...</pre>
            <pre class="apidoc-pre">{
  "nip": "199303252025061002",
  "nama": "RIYANTO NUGROHO, A.Md.Kom.",
  "jabatan": "Pranata Sumber Daya Manusia Aparatur Terampil",
  "opd": "Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu",
  "bulan": 10,
  "tahun": 2026,
  "sumber_label": "ext-rekap-absen-v1",
  "ringkasan": {
    "hari_kerja": 22,
    "hadir": 22,
    "tidak_hadir": 0,
    "konfirmasi": 3,
    "keterlambatan": "00:00:00"
  },
  "harian": [
    {
      "tanggal": "2026-10-01",
      "hari": "KAMIS",
      "jam_hadir": "2026-10-01 07:38:08",
      "hadir_selisih": "00:21:52",
      "jam_pulang": "2026-10-01 16:01:58",
      "pulang_selisih": "00:01:58",
      "status": "-"
    },
    {
      "tanggal": "2026-10-09",
      "hari": "JUMAT",
      "jam_hadir": "2026-10-09 07:26:40",
      "hadir_selisih": "00:03:20",
      "jam_pulang": null,
      "pulang_selisih": null,
      "status": "KONFIRMASI"
    }
  ],
  "data_dukung": [
    {
      "tanggal_mulai": "2026-10-09",
      "tanggal_selesai": "2026-10-09",
      "keterangan": "St Lion Pro",
      "jenis": "Konfirmasi",
      "token": "eyJpdiI6Inp0ZWRqaXhKNHNBWHF2ZUpuWWFVYlE9PSIs..."
    }
  ]
}</pre>
            <p class="apidoc-p">Respons (200):</p>
            <pre class="apidoc-pre">{
  "ok": true,
  "jumlah": 1,
  "data": [
    {
      "id": 12,
      "nip": "199303252025061002",
      "nama": "RIYANTO NUGROHO, A.Md.Kom.",
      "jabatan": "Pranata Sumber Daya Manusia Aparatur Terampil",
      "opd": "Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu",
      "bulan": 10,
      "tahun": 2026,
      "periode": "Oktober 2026",
      "hari_kerja": 22,
      "hadir": 22,
      "tidak_hadir": 0,
      "konfirmasi": 3,
      "keterlambatan": "00:00:00",
      "jumlah_harian": 2,
      "jumlah_data_dukung": 1,
      "diunggah_at": "2026-11-04T08:36:45+07:00",
      "diunggah_oleh": "Admin Kepegawaian",
      "aksi": "dibuat"
    }
  ]
}</pre>
            <p class="apidoc-note">Batch: kirim <code>{"items": [ ...rekap1, ...rekap2 ]}</code> (maks 12 per request). Respons berisi satu entri <code>data[]</code> per item.</p>
        </section>

        <section id="rekap-read" class="apidoc-section">
            <h2 class="apidoc-h3">2.2 Read — daftar &amp; detail</h2>
            <pre class="apidoc-pre">GET /api/v1/rekap-absen?nip=199303252025061002&amp;tahun=2026&amp;bulan=10</pre>
            <p class="apidoc-p">Parameter opsional: <code>nip</code>, <code>tahun</code>, <code>bulan</code> (angka atau nama bulan). Hasil maks 200 baris, urut tahun/bulan terbaru lalu nama.</p>
            <pre class="apidoc-pre">{
  "ok": true,
  "jumlah": 1,
  "data": [
    {
      "id": 12,
      "nip": "199303252025061002",
      "nama": "RIYANTO NUGROHO, A.Md.Kom.",
      "bulan": 10,
      "tahun": 2026,
      "periode": "Oktober 2026",
      "hari_kerja": 22,
      "hadir": 22,
      "tidak_hadir": 0,
      "konfirmasi": 3,
      "keterlambatan": "00:00:00",
      "jumlah_harian": 22,
      "jumlah_data_dukung": 3,
      "diunggah_at": "2026-11-04T08:36:45+07:00",
      "diunggah_oleh": "Admin Kepegawaian"
    }
  ]
}</pre>
            <pre class="apidoc-pre">GET /api/v1/rekap-absen/12</pre>
            <pre class="apidoc-pre">{
  "ok": true,
  "data": {
    "id": 12,
    "nip": "199303252025061002",
    "nama": "RIYANTO NUGROHO, A.Md.Kom.",
    "periode": "Oktober 2026",
    "hari_kerja": 22,
    "hadir": 22,
    "tidak_hadir": 0,
    "konfirmasi": 3,
    "keterlambatan": "00:00:00",
    "harian": [
      {
        "tanggal": "2026-10-01",
        "hari": "KAMIS",
        "jam_hadir": "2026-10-01 07:38:08",
        "hadir_selisih": "00:21:52",
        "jam_pulang": "2026-10-01 16:01:58",
        "pulang_selisih": "00:01:58",
        "status": null
      }
    ],
    "data_dukung": [
      {
        "tanggal_mulai": "2026-10-09",
        "tanggal_selesai": "2026-10-09",
        "keterangan": "St Lion Pro",
        "jenis": "Konfirmasi",
        "tautan": "https://secure-sadewa.semarangkota.go.id/get-file/data-dukung/eyJpdiI6..."
      }
    ]
  }
}</pre>
        </section>

        <section id="rekap-update" class="apidoc-section">
            <h2 class="apidoc-h3">2.3 Update — kirim ulang periode yang sama</h2>
            <p class="apidoc-p">Tidak ada method khusus. Kirim <code>POST</code> dengan <code>nip + tahun + bulan</code> yang sudah ada. Isi lama dihapus lalu diganti isi baru dalam satu transaksi:</p>
            <pre class="apidoc-pre">{
  "nip": "199303252025061002",
  "bulan": 10,
  "tahun": 2026,
  "ringkasan": { "hari_kerja": 22, "hadir": 21, "tidak_hadir": 1, "konfirmasi": 3, "keterlambatan": "00:12:00" },
  "harian": [ /* daftar LENGKAP periode Oktober, bukan hanya yang berubah */ ],
  "data_dukung": [ /* daftar LENGKAP lampiran periode Oktober */ ]
}</pre>
            <pre class="apidoc-pre">{
  "ok": true,
  "jumlah": 1,
  "data": [
    { "id": 12, "periode": "Oktober 2026", "jumlah_harian": 21, "jumlah_data_dukung": 2, "aksi": "diperbarui" }
  ]
}</pre>
            <p class="apidoc-note">Karena bersifat <strong>replace</strong>, selalu kirim ulang seluruh baris periode tersebut. Baris yang tidak dikirim akan hilang.</p>
        </section>

        <section id="rekap-schema" class="apidoc-section">
            <h2 class="apidoc-h3">2.4 Skema field JSON</h2>
            <table class="apidoc-table">
                <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
                <tbody>
                <tr><td><code>nip</code></td><td>string (maks 30)</td><td>ya</td><td>NIP pegawai. Kunci utama bersama bulan+tahun.</td></tr>
                <tr><td><code>nama</code></td><td>string (maks 150)</td><td>tidak</td><td>Kosong → diambil dari <code>pegawai_profil</code> berdasarkan NIP.</td></tr>
                <tr><td><code>jabatan</code>, <code>opd</code></td><td>string (maks 255)</td><td>tidak</td><td>Kosong → diambil dari <code>pegawai_profil</code>.</td></tr>
                <tr><td><code>bulan</code></td><td>int 1-12 atau string</td><td>ya</td><td>Terima <code>10</code>, <code>"10"</code>, <code>"Oktober"</code>, atau <code>"Oktober 2026"</code>.</td></tr>
                <tr><td><code>tahun</code></td><td>int 2000-2100</td><td>ya</td><td>Contoh <code>2026</code>.</td></tr>
                <tr><td><code>sumber_label</code></td><td>string (maks 100)</td><td>tidak</td><td>Label asal unggahan, default nama kunci API.</td></tr>
                <tr><td><code>ringkasan</code></td><td>object</td><td>tidak</td><td>Angka ringkasan; boleh juga dikirim di level atas.</td></tr>
                <tr><td><code>harian</code></td><td>array (maks 31)</td><td>tidak</td><td>Rincian presensi harian.</td></tr>
                <tr><td><code>data_dukung</code></td><td>array (maks 100)</td><td>tidak</td><td>Lampiran konfirmasi.</td></tr>
                <tr><td><code>items</code></td><td>array (maks 12)</td><td>tidak</td><td>Bila dikirim, isi array dianggap daftar rekap dan field lain diabaikan.</td></tr>
                </tbody>
            </table>

            <p class="apidoc-p"><strong>Objek <code>ringkasan</code></strong> (semua opsional, default 0):</p>
            <table class="apidoc-table">
                <thead><tr><th>Field</th><th>Alias yang diterima</th></tr></thead>
                <tbody>
                <tr><td><code>hari_kerja</code></td><td><code>hariKerja</code></td></tr>
                <tr><td><code>hadir</code></td><td><code>jumlah_hadir</code>, <code>masuk</code>, <code>jumlah_masuk</code></td></tr>
                <tr><td><code>tidak_hadir</code></td><td><code>jumlah_tidak_hadir</code>, <code>tidak_masuk</code>, <code>jumlah_tidak_masuk</code></td></tr>
                <tr><td><code>konfirmasi</code></td><td><code>jumlah_konfirmasi</code></td></tr>
                <tr><td><code>keterlambatan</code></td><td><code>total_keterlambatan</code></td></tr>
                </tbody>
            </table>

            <p class="apidoc-p"><strong>Objek <code>harian[]</code>:</strong></p>
            <table class="apidoc-table">
                <thead><tr><th>Field</th><th>Format</th><th>Keterangan</th></tr></thead>
                <tbody>
                <tr><td><code>tanggal</code></td><td><code>2026-10-01</code> / <code>01-10-2026</code></td><td>Disimpan sebagai date.</td></tr>
                <tr><td><code>hari</code></td><td>teks, mis. <code>KAMIS</code></td><td>Maks 20 karakter.</td></tr>
                <tr><td><code>jam_hadir</code>, <code>jam_pulang</code></td><td><code>2026-10-01 07:38:08</code></td><td>Wajib memuat tanggal + jam. <code>-</code> / kosong → null.</td></tr>
                <tr><td><code>hadir_selisih</code>, <code>pulang_selisih</code></td><td>teks, mis. <code>00:21:52</code></td><td>Kolom "+/-", disimpan apa adanya (maks 20 karakter).</td></tr>
                <tr><td><code>status</code></td><td>teks, mis. <code>KONFIRMASI</code></td><td><code>-</code> disimpan sebagai null.</td></tr>
                </tbody>
            </table>

            <p class="apidoc-p"><strong>Objek <code>data_dukung[]</code>:</strong></p>
            <table class="apidoc-table">
                <thead><tr><th>Field</th><th>Keterangan</th></tr></thead>
                <tbody>
                <tr><td><code>tanggal_mulai</code>, <code>tanggal_selesai</code></td><td>Tanggal; format sama seperti <code>harian.tanggal</code>.</td></tr>
                <tr><td><code>keterangan</code></td><td>Mis. <code>St Lion Pro</code> / <code>Surat Tugas</code> (maks 255).</td></tr>
                <tr><td><code>jenis</code></td><td>Mis. <code>Konfirmasi</code> (maks 100).</td></tr>
                <tr><td><code>token</code></td><td>Nilai dari <code>lihat_dd('...')</code> di web presensi. Boleh juga kirim <code>url</code> penuh (token diambil otomatis).</td></tr>
                </tbody>
            </table>
            <p class="apidoc-note">Tautan lampiran dibentuk: <code>{{ \App\Models\Kepegawaian\RekapAbsenDataDukung::FILE_BASE }}&lt;token&gt;</code> dan ditampilkan sebagai tombol "Lihat" di panel.</p>
        </section>

        <section id="rekap-contoh" class="apidoc-section">
            <h2 class="apidoc-h3">2.5 Contoh curl &amp; fetch extension</h2>
            <pre class="apidoc-pre">curl -X POST {{ url('/api/v1/rekap-absen') }} \
  -H "Content-Type: application/json" \
  -H "X-API-Key: dpmk_GANTI_DENGAN_KUNCI" \
  -d '{
    "nip": "199303252025061002",
    "bulan": 10,
    "tahun": 2026,
    "ringkasan": {"hari_kerja": 22, "hadir": 22, "tidak_hadir": 0, "konfirmasi": 3, "keterlambatan": "00:00:00"},
    "harian": [
      {"tanggal": "2026-10-01", "hari": "KAMIS", "jam_hadir": "2026-10-01 07:38:08",
       "hadir_selisih": "00:21:52", "jam_pulang": "2026-10-01 16:01:58",
       "pulang_selisih": "00:01:58", "status": "-"}
    ],
    "data_dukung": [
      {"tanggal_mulai": "2026-10-09", "tanggal_selesai": "2026-10-09",
       "keterangan": "St Lion Pro", "jenis": "Konfirmasi", "token": "eyJpdiI6..."}
    ]
  }'</pre>

            <pre class="apidoc-pre">await fetch("{{ url('/api/v1/rekap-absen') }}", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-API-Key": "dpmk_..."
  },
  body: JSON.stringify({
    nip: "199303252025061002",
    bulan: 10,
    tahun: 2026,
    ringkasan: { hari_kerja: 22, hadir: 22, tidak_hadir: 0, konfirmasi: 3, keterlambatan: "00:00:00" },
    harian: [ /* hasil scrape #rekap_bulanan */ ],
    data_dukung: [ /* hasil scrape #rekap_datadukung */ ]
  })
});</pre>
            <p class="apidoc-note">Ambil data dari modal rekap bulanan: tabel <code>#rekap_bulanan</code>, ringkasan <code>.rb-summary</code>, lampiran <code>#rekap_datadukung</code> (token diambil dari argumen <code>lihat_dd('...')</code>).</p>
            <p class="apidoc-note">Data dukung TPP (API <code>/api/v1/data-dukung-tpp</code>) juga dianggap konfirmasi bila <code>file_pendukung_status = "ada"</code>. Hari yang tidak ada di <code>data_dukung</code> absen tetapi tercatat valid di TPP tidak dihitung sebagai "Belum Konfirmasi".</p>
        </section>

        <section id="tpp" class="apidoc-section">
            <h2 class="apidoc-h2">3. Data Dukung TPP</h2>
            <p class="apidoc-p">Sumber: <code>tpp.semarangkota.go.id/izin</code> (halaman "Data Pegawai Tidak Masuk Kerja"). Extension scrape tabel lalu POST. Baris dengan file pendukung <strong>belum diunggah</strong> tetap disimpan, tetapi <strong>tidak</strong> menutup konfirmasi di rekap absen.</p>
            <table class="apidoc-table">
                <thead><tr><th>Operasi</th><th>Method &amp; path</th><th>Keterangan</th></tr></thead>
                <tbody>
                <tr><td><strong>C</strong>reate/Update</td><td><code>POST /api/v1/data-dukung-tpp</code></td><td>Upsert per <code>id_tpp</code>; bila kosong memakai kunci <code>nip+tahun+bulan+keterangan+tanggal_mulai+jumlah_hari</code>.</td></tr>
                <tr><td><strong>R</strong>ead</td><td><code>GET /api/v1/data-dukung-tpp?nip=&amp;tahun=&amp;bulan=&amp;valid=</code></td><td>Daftar (maks 500) + daftar tanggal. <code>valid=1</code> hanya file sudah diunggah.</td></tr>
                <tr><td><strong>D</strong>elete</td><td>—</td><td>Tidak ada endpoint hapus.</td></tr>
                </tbody>
            </table>

            <pre class="apidoc-pre">POST /api/v1/data-dukung-tpp
Content-Type: application/json
X-API-Key: dpmk_...</pre>
            <pre class="apidoc-pre">{
  "sumber_label": "ext-tpp-v1",
  "items": [
    {
      "id_tpp": 1133089,
      "nip": "199308112025211037",
      "nama": "ADITYA NAFIUR RACHMAN, S.H.",
      "golongan": "IX",
      "eselon": "Pelaksana",
      "skpd": "DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU",
      "unit_kerja": "019001",
      "bulan": 10,
      "tahun": 2026,
      "keterangan": "Tugas Kedinasan (tgl 1 oktober 2026)",
      "jumlah_hari": 1,
      "file_pendukung_status": "ada",
      "file_pendukung_nama": "1 Oktober ST Verifikasi Lapangan TDG Kamis.pdf",
      "file_pendukung_url": "https://tpp.semarangkota.go.id/izin-kerja/1 Oktober ST Verifikasi Lapangan TDG Kamis.pdf"
    },
    {
      "id_tpp": 1133066,
      "nip": "199308112025211037",
      "nama": "ADITYA NAFIUR RACHMAN, S.H.",
      "bulan": 10,
      "tahun": 2026,
      "keterangan": "Presensi tidak terekam masuk / pulang < 3 kali tanpa Surat Keterangan (tgl 9 dan 12 oktober 2026)",
      "jumlah_hari": 2,
      "file_pendukung_status": "belum"
    }
  ]
}</pre>
            <ul class="apidoc-list">
                <li><code>file_pendukung_status</code>: <code>ada</code> | <code>belum</code>. Kosong → dianggap <code>ada</code> bila <code>file_pendukung_url</code>/<code>nama</code> terisi, selain itu <code>belum</code>.</li>
                <li><code>tanggal_list</code> opsional (array tanggal); bila tidak dikirim, tanggal diambil dari teks <code>keterangan</code> (pola <code>(tgl 9 dan 12 oktober 2026)</code> / <code>(tgl 28-30 oktober 2026)</code>).</li>
                <li><code>jumlah_hari</code> kosong → jumlah tanggal.</li>
                <li>Upsert per <code>id_tpp</code> (id baris di TPP); aman dikirim ulang saat data di TPP berubah.</li>
                <li>Maks 200 baris per request.</li>
            </ul>
            <pre class="apidoc-pre">{
  "ok": true,
  "jumlah": 2,
  "dibuat": 2,
  "diperbarui": 0,
  "data": [
    {
      "id": 12,
      "id_tpp": 1133089,
      "nip": "199308112025211037",
      "periode": "Oktober 2026",
      "jenis": "Tugas Kedinasan",
      "keterangan": "Tugas Kedinasan (tgl 1 oktober 2026)",
      "tanggal": ["2026-10-01"],
      "jumlah_hari": 1,
      "file_pendukung_status": "ada",
      "file_pendukung_nama": "1 Oktober ST Verifikasi Lapangan TDG Kamis.pdf",
      "file_pendukung_url": "https://tpp.semarangkota.go.id/izin-kerja/1 Oktober ST Verifikasi Lapangan TDG Kamis.pdf",
      "valid": true,
      "sumber": "api",
      "aksi": "dibuat"
    }
  ]
}</pre>

            <pre class="apidoc-pre">GET /api/v1/data-dukung-tpp?nip=199308112025211037&tahun=2026&bulan=10&valid=1</pre>
            <p class="apidoc-note">Hanya baris <code>valid: true</code> (<code>file_pendukung_status = "ada"</code>) yang dianggap konfirmasi pada kolom <strong>Belum Konfirmasi</strong> dan halaman Deteksi Data Dukung.</p>

            <pre class="apidoc-pre">curl -X POST {{ url('/api/v1/data-dukung-tpp') }} \
  -H "Content-Type: application/json" \
  -H "X-API-Key: dpmk_GANTI_DENGAN_KUNCI" \
  -d '{"items":[{"id_tpp":1133089,"nip":"199308112025211037","nama":"ADITYA NAFIUR RACHMAN, S.H.","bulan":10,"tahun":2026,"keterangan":"Tugas Kedinasan (tgl 1 oktober 2026)","jumlah_hari":1,"file_pendukung_status":"ada","file_pendukung_nama":"ST.pdf","file_pendukung_url":"https://tpp.semarangkota.go.id/izin-kerja/ST.pdf"}]}'</pre>
        </section>

        <section id="cuti" class="apidoc-section">
            <h2 class="apidoc-h2">4. Generator Surat Cuti (ringkas)</h2>
            <table class="apidoc-table">
                <tbody>
                <tr><th><code>GET /api/v1/generator-surat-cuti/berikutnya?kode=800</code></th><td>Nomor awal berikutnya + status impor hari ini.</td></tr>
                <tr><th><code>GET /api/v1/generator-surat-cuti/pegawai?q=SANTI</code></th><td>Cari pegawai (maks 25) untuk resolve nama → NIP.</td></tr>
                <tr><th><code>POST /api/v1/generator-surat-cuti</code></th><td>Olah + simpan rincian cuti (teks tempelan atau <code>rows</code>).</td></tr>
                </tbody>
            </table>
            <pre class="apidoc-pre">POST /api/v1/generator-surat-cuti
{
  "rows": [
    { "nama": "SANTI TRI WAHYUNI", "jenis": "Cuti Sakit", "hari": 2,
      "tanggal_mulai": "2026-09-29", "tanggal_selesai": "2026-09-30",
      "pengajuan_at": "2026-10-05 15:49:19" }
  ],
  "kode": "800",
  "sumber_label": "ext-ecuti-v1"
}</pre>
            <p class="apidoc-note">Detail aturan nomor/sisa/duplikat ada di blok "Dokumentasi API — Generator Surat Cuti" pada halaman <a href="{{ \App\Filament\Kepegawaian\Pages\ApiKeys::getUrl() }}">API Key</a>.</p>
        </section>

        <section id="error" class="apidoc-section">
            <h2 class="apidoc-h2">5. Kode error</h2>
            <table class="apidoc-table">
                <thead><tr><th>Kode</th><th>Arti</th><th>Contoh body</th></tr></thead>
                <tbody>
                <tr>
                    <td><code>401</code></td>
                    <td>Kunci tidak dikirim / salah / dicabut / kadaluarsa.</td>
                    <td><code>{"ok":false,"error":"API key tidak valid atau tidak dikirim (header X-API-Key)."}</code></td>
                </tr>
                <tr>
                    <td><code>403</code></td>
                    <td>Kunci tidak punya scope yang dibutuhkan.</td>
                    <td><code>{"ok":false,"error":"API key tidak punya akses \"rekap-absen\"."}</code></td>
                </tr>
                <tr>
                    <td><code>404</code></td>
                    <td>Id rekap tidak ditemukan.</td>
                    <td><code>{"ok":false,"error":"Rekap absen tidak ditemukan."}</code></td>
                </tr>
                <tr>
                    <td><code>422</code></td>
                    <td>Payload tidak valid.</td>
                    <td><code>{"ok":false,"error":"Sebagian payload tidak valid.","errors":{"0":["Bulan tidak dikenali..."]}}</code></td>
                </tr>
                <tr>
                    <td><code>429</code></td>
                    <td>Melebihi 60 request/menit.</td>
                    <td><code>{"message":"Too Many Requests"}</code></td>
                </tr>
                <tr>
                    <td><code>500</code></td>
                    <td>Gagal simpan (mis. tabel belum dimigrasi).</td>
                    <td><code>{"ok":false,"error":"Gagal menyimpan rekap absen: ..."}</code></td>
                </tr>
                </tbody>
            </table>
        </section>
    </div>

    @assets
    <style>
        .apidoc { display: grid; gap: 1.25rem; font-size: .875rem; }
        .apidoc-toc { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; padding: .6rem .85rem; border-radius: .5rem; background: rgba(107,114,128,.1); font-size: .78rem; }
        .apidoc-toc-label { font-weight: 600; }
        .apidoc-toc a { color: #2563eb; text-decoration: none; }
        .apidoc-toc a:hover { text-decoration: underline; }
        .dark .apidoc-toc a { color: #60a5fa; }
        .apidoc-section { display: grid; gap: .5rem; scroll-margin-top: 5rem; }
        .apidoc-h2 { margin: .25rem 0 0; font-size: 1.1rem; font-weight: 700; }
        .apidoc-h3 { margin: .25rem 0 0; font-size: .95rem; font-weight: 700; }
        .apidoc-p { margin: .25rem 0 0; color: #4b5563; }
        .dark .apidoc-p { color: #d1d5db; }
        .apidoc-note { margin: .25rem 0 0; font-size: .8rem; color: #6b7280; }
        .dark .apidoc-note { color: #9ca3af; }
        .apidoc-note code, .apidoc-p code, .apidoc-table code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .76rem; background: rgba(107,114,128,.15); border-radius: .25rem; padding: 0 .3rem; }
        .apidoc-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .apidoc-table th, .apidoc-table td { text-align: left; vertical-align: top; padding: .45rem .55rem; border: 1px solid rgba(107,114,128,.3); }
        .apidoc-table thead th { background: rgba(107,114,128,.12); font-weight: 600; }
        .apidoc-table tbody th { width: 34%; font-weight: 600; }
        .apidoc-pre { margin: .35rem 0 0; padding: .7rem .85rem; border-radius: .5rem; background: #111827; color: #e5e7eb; font-size: .75rem; line-height: 1.55; overflow-x: auto; white-space: pre; }
        .apidoc a { text-decoration: underline; }
    </style>
    @endassets
</x-filament-panels::page>

<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Models\Kepegawaian\Cuti;
use App\Models\Kepegawaian\SuratCutiRiwayat;
use App\Services\Kepegawaian\SuratCutiGeneratorService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Js;

/**
 * Generator Surat Cuti — menempelkan teks rekap pengajuan lalu memberi
 * nomor surat sekuensial per rincian cuti ("{seq}/{kode}/{ROMAN}/{tahun}",
 * bulan+tahun dari tanggal pengajuan) plus sisa cuti 2 tahun sebelumnya +
 * tahun berjalan sebagai referensi.
 *
 * TIDAK menulis/mengubah apa pun di tabel cuti (tidak menyentuh jatah,
 * sisa, maupun status) — hanya membuat nomornya saja. Hasil disalin
 * (click-to-copy TSV) untuk ditempel di dokumen/bahan impor, dan otomatis
 * tersimpan sebagai riwayat batch (tabel riwayat_generator_cuti,
 * murni arsip).
 */
class GeneratorSuratCuti extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Generator Surat Cuti';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Generator Surat Cuti';

    protected static string | \UnitEnum | null $navigationGroup = 'Cuti';

    protected static ?string $slug = 'generator-surat-cuti';

    protected string $view = 'filament.kepegawaian.pages.generator-surat-cuti';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Tempel rekap pengajuan cuti — tiap rincian diberi nomor surat berurutan, sisa tampil sudah dikurangi pengajuan ini. Tidak mengubah data cuti.';
    }

    /** Tempelan teks rekap pengajuan. */
    public string $teks = '';

    /** Kode pembentuk nomor, mis. "800" → "1182/800/X/2026". */
    public string $kode = '800';

    /** Sekuens awal (default = terbesar yang sudah dipakai + 1). */
    public int $nomorAwal = 1;

    /**
     * Tahun kolom sisa — tampil mulai dari yang terbesar
     * (2026, 2025, 2024). Cuti::rekapTahunan() mengurutkan sendiri secara
     * internal, jadi urutan di sini hanya untuk tampilan.
     *
     * @var list<int>
     */
    public array $tahun = [];

    /**
     * Hasil generate: satu baris per rincian cuti. Baris duplikat
     * (duplikat=true) tampil tanpa nomor tapi ikut tersimpan di riwayat.
     *
     * @var list<array{no: int, nama: string, pengajuan: string, nomor: ?string, bulan: int, tahun: int, hari: int, rentang: string, jenis: string, duplikat: bool, sisa: array<int, int|string>}>
     */
    public array $hasil = [];

    /** @var list<string> baris tak dikenali (tanpa nomor). */
    public array $takDikenali = [];

    /**
     * Rincian duplikat (nama + tgl pengajuan sama) — tetap tampil & masuk
     * riwayat dengan flag, tapi TIDAK diberi nomor (tidak diinput sebagai
     * surat baru).
     *
     * @var list<array{nama: string, pengajuan: string, jenis: string, mentah: string}>
     */
    public array $duplikat = [];

    /** Batch riwayat yang sudah tersimpan untuk hasil saat ini (null = belum). */
    public ?string $tersimpanBatch = null;

    /** Tabel riwayat tersedia (false bila SQL migrasi belum dijalankan). */
    public bool $riwayatTersedia = false;

    /**
     * Ringkasan batch riwayat (terbaru dulu).
     *
     * @var list<array{batch: string, waktu: string, oleh: string, jumlah: int, duplikat: int, nomor_dari: string, nomor_sampai: string, sumber: string}>
     */
    public array $riwayat = [];

    /** Batch yang sedang dilihat rincinya (null = tidak ada). */
    public ?string $riwayatDetailBatch = null;

    /**
     * Rincian baris satu batch riwayat.
     *
     * @var list<array{nama: string, jenis: string, hari: int, pengajuan: string, nomor: string, duplikat: bool, sisa: array}>
     */
    public array $riwayatDetail = [];

    public function mount(SuratCutiGeneratorService $service): void
    {
        $kini = (int) now()->year;

        $this->tahun = [$kini, $kini - 1, $kini - 2];
        $this->kode = SuratCutiGeneratorService::DEFAULT_KODE;
        $this->nomorAwal = $service->nextSequence($this->kode);
        $this->muatRiwayat();

        // Kembalikan draft (input + hasil terakhir) supaya refresh
        // tidak menghilangkan isian.
        $draft = session()->get('gsc.draft');

        if (is_array($draft)) {
            $this->teks = (string) ($draft['teks'] ?? '');
            $this->kode = (string) ($draft['kode'] ?? $this->kode);
            $this->nomorAwal = (int) ($draft['nomorAwal'] ?? $this->nomorAwal);
            $this->hasil = is_array($draft['hasil'] ?? null) ? $draft['hasil'] : [];
            $this->takDikenali = is_array($draft['takDikenali'] ?? null) ? $draft['takDikenali'] : [];
            $this->duplikat = is_array($draft['duplikat'] ?? null) ? $draft['duplikat'] : [];
            $this->tersimpanBatch = $draft['tersimpanBatch'] ?? null;
        }
    }

    /**
     * Simpan draft input + hasil ke session — dipanggil setiap ada
     * perubahan (debounced) dan setiap generate/hapus/simpan.
     */
    private function simpanDraft(): void
    {
        session()->put('gsc.draft', [
            'teks' => $this->teks,
            'kode' => $this->kode,
            'nomorAwal' => $this->nomorAwal,
            'hasil' => $this->hasil,
            'takDikenali' => $this->takDikenali,
            'duplikat' => $this->duplikat,
            'tersimpanBatch' => $this->tersimpanBatch,
        ]);
    }

    public function updatedTeks(): void
    {
        $this->simpanDraft();
    }

    public function updatedKode(): void
    {
        $this->simpanDraft();
    }

    public function updatedNomorAwal(): void
    {
        $this->simpanDraft();
    }

    /**
     * Cek langsung ke DB apakah tabel riwayat ada. Dipakai saat mau TULIS
     * (simpan/hapus) — jangan pakai $this->riwayatTersedia yang dihitung
     * saat mount, karena snapshot Livewire bisa basi (halaman dibuka sebelum
     * deploy/rename tabel, lalu aksi diklik tanpa refresh).
     */
    private function tabelRiwayatAda(): bool
    {
        try {
            return Schema::connection('kepegawaian')->hasTable('riwayat_generator_cuti');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Muat ringkasan batch riwayat. Aman dipanggil sebelum tabel migrasi
     * ada (halaman generate/copy tetap jalan, hanya riwayat yang mati).
     */
    private function muatRiwayat(): void
    {
        $this->riwayatTersedia = $this->tabelRiwayatAda();

        if (! $this->riwayatTersedia) {
            $this->riwayat = [];

            return;
        }

        $this->riwayat = SuratCutiRiwayat::query()
            ->selectRaw('batch, MIN(created_at) as waktu, MAX(created_by_nama) as oleh, COUNT(*) as jumlah, SUM(is_duplikat) as duplikat, MIN(CASE WHEN is_duplikat = 0 THEN nomor_surat END) as nomor_dari, MAX(CASE WHEN is_duplikat = 0 THEN nomor_surat END) as nomor_sampai, MAX(sumber) as sumber')
            ->groupBy('batch')
            ->orderByDesc('waktu')
            ->limit(20)
            ->get()
            ->map(fn ($r): array => [
                'batch' => (string) $r->batch,
                'waktu' => $r->waktu ? Carbon::parse($r->waktu)->format('d M Y H:i') : '—',
                'oleh' => (string) ($r->oleh ?: '—'),
                'jumlah' => (int) $r->jumlah,
                'duplikat' => (int) $r->duplikat,
                'nomor_dari' => (string) ($r->nomor_dari ?? '—'),
                'nomor_sampai' => (string) ($r->nomor_sampai ?? '—'),
                'sumber' => (string) ($r->sumber ?: 'manual'),
            ])
            ->all();

        // Kalau batch yang sedang dibuka rinciannya ikut terhapus/berubah,
        // tutup panel rincian supaya tidak menampilkan data basi.
        if ($this->riwayatDetailBatch !== null
            && ! collect($this->riwayat)->contains(fn (array $r): bool => $r['batch'] === $this->riwayatDetailBatch)) {
            $this->riwayatDetailBatch = null;
            $this->riwayatDetail = [];
        }
    }

    /**
     * Buka rincian satu batch riwayat (semua baris: nama, nomor, sisa).
     */
    public function lihatRiwayat(string $batch): void
    {
        if (! $this->tabelRiwayatAda()) {
            return;
        }

        $this->riwayatDetailBatch = $batch;
        $this->riwayatDetail = SuratCutiRiwayat::query()
            ->where('batch', $batch)
            ->orderBy('id')
            ->get()
            ->map(fn (SuratCutiRiwayat $r): array => [
                'nama' => (string) $r->nama,
                'jenis' => (string) ($r->jenis ?: '—'),
                'hari' => (int) $r->hari,
                'pengajuan' => (string) ($r->pengajuan ?: '—'),
                'nomor' => (string) $r->nomor_surat,
                'duplikat' => (bool) $r->is_duplikat,
                'sisa' => $r->sisa ?? [],
            ])
            ->all();
    }

    public function tutupRiwayat(): void
    {
        $this->riwayatDetailBatch = null;
        $this->riwayatDetail = [];
    }

    public function generate(SuratCutiGeneratorService $service): void
    {
        // Wajib impor cuti hari ini dulu supaya sisa yang tampil segar.
        if (! Cuti::sudahImporHariIni()) {
            $this->hasil = [];
            $this->takDikenali = [];
            $this->duplikat = [];

            Notification::make()
                ->title('Impor cuti hari ini dulu')
                ->body('Data cuti belum diimpor hari ini. Buka menu Kepegawaian → Impor Cuti, lalu kembali ke sini.')
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        $parsed = $service->parse($this->teks);
        $this->takDikenali = $parsed['tak_dikenali'];

        $totalRinci = 0;

        foreach ($parsed['orang'] as $o) {
            $totalRinci += count($o['rinci']);
        }

        if ($totalRinci === 0) {
            $this->hasil = [];
            $this->duplikat = [];
            $this->tersimpanBatch = null;

            Notification::make()
                ->title('Tidak ada rincian cuti dikenali')
                ->body('Pastikan formatnya "1. NAMA" lalu "- Cuti ... N hari (...) pengajuan ...".')
                ->danger()
                ->send();

            return;
        }

        // Aturan hasil (duplikat, potong sisa, penomoran) ada di service —
        // sama persis dengan yang dipakai API extension.
        $susunan = $service->susun(
            $parsed['orang'],
            $parsed['tak_dikenali'],
            $this->kode,
            $this->nomorAwal,
            $this->tahun,
        );

        $hasil = $susunan['hasil'];
        $this->duplikat = $susunan['duplikat'];
        $this->hasil = $hasil;
        $this->simpanDraft();

        $bernomor = array_values(array_filter($hasil, fn (array $h): bool => ! empty($h['nomor'])));
        $nDupe = count($hasil) - count($bernomor);

        // Otomatis tersimpan di riwayat_generator_cuti supaya tidak hilang.
        $batch = $this->simpanOtomatis($service);

        if ($bernomor !== []) {
            $this->nomorAwal = max(1, $susunan['nomor_berikutnya']);
            $this->simpanDraft();
        }

        $judul = count($bernomor).' nomor dibuat'.($nDupe > 0 ? " ({$nDupe} duplikat tanpa nomor)" : '');
        $badan = $bernomor !== []
            ? 'Nomor '.$bernomor[0]['nomor'].' s.d. '.$bernomor[count($bernomor) - 1]['nomor'].'. Klik Salin untuk menyalin semuanya.'
            : 'Semua baris duplikat — tidak ada nomor baru.';

        if ($batch !== null) {
            $badan .= ' Otomatis tersimpan di riwayat ('.$batch.').';
        }

        Notification::make()
            ->title($judul)
            ->body($badan)
            ->success()
            ->send();
    }

    /**
     * Simpan hasil saat ini sebagai batch riwayat (murni arsip — tidak
     * dipakai perhitungan cuti mana pun). Dipanggil otomatis setiap generate.
     * Logic tulis ada di service supaya sama dengan API extension.
     */
    private function simpanOtomatis(SuratCutiGeneratorService $service): ?string
    {
        if ($this->hasil === [] || ! $this->tabelRiwayatAda()) {
            $this->riwayatTersedia = false;

            return null;
        }

        try {
            $batch = $service->simpanRiwayatBatch(
                $this->hasil,
                SuratCutiRiwayat::SUMBER_MANUAL,
                null,
                $this->teks,
                Auth::user(),
            );
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Riwayat gagal tersimpan')
                ->body('Nomor tetap dibuat; riwayat tidak tercatat. Cek tabel riwayat_generator_cuti.')
                ->danger()
                ->send();

            return null;
        }

        if ($batch === null) {
            return null;
        }

        $this->tersimpanBatch = $batch;
        $this->muatRiwayat();
        $this->simpanDraft();

        return $batch;
    }

    /**
     * Hapus satu batch riwayat.
     */
    public function hapusRiwayat(string $batch): void
    {
        if (! $this->tabelRiwayatAda()) {
            $this->riwayatTersedia = false;

            return;
        }

        try {
            $jumlah = SuratCutiRiwayat::query()->where('batch', $batch)->delete();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal menghapus riwayat')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($this->tersimpanBatch === $batch) {
            $this->tersimpanBatch = null;
        }

        $this->muatRiwayat();
        $this->simpanDraft();

        Notification::make()
            ->title($jumlah.' baris riwayat dihapus')
            ->success()
            ->send();
    }

    /**
     * Salin seluruh hasil sebagai teks tab-separasi — tempel di Excel/Sheets.
     */
    public function salinHasil(): void
    {
        if ($this->hasil === []) {
            return;
        }

        $this->js(
            '(async function (teks) { try { await navigator.clipboard.writeText(teks); } catch (e) {'
            .' var ta = document.createElement("textarea"); ta.value = teks;'
            .' ta.style.position = "fixed"; ta.style.opacity = "0";'
            .' document.body.appendChild(ta); ta.select();'
            .' try { document.execCommand("copy"); } catch (err) {} ta.remove(); } })'
            .'('.Js::from($this->hasilTsv()).');',
        );

        Notification::make()
            ->title($this->jumlahTersalin().' baris disalin ke clipboard (tanpa duplikat)')
            ->body('Tempel (Ctrl+V) langsung di Excel atau Spreadsheet.')
            ->success()
            ->send();
    }

    private function jumlahTersalin(): int
    {
        return count(array_filter(
            $this->hasil,
            fn ($h): bool => is_array($h) && empty($h['duplikat']) && ! empty($h['nomor']),
        ));
    }

    /**
     * Hapus satu baris hasil lalu urutkan ulang nomor dari nomor awal.
     */
    public function hapusBaris(int $index, SuratCutiGeneratorService $service): void
    {
        if (! array_key_exists($index, $this->hasil)) {
            return;
        }

        array_splice($this->hasil, $index, 1);
        $this->penomoranUlang($service);
        $this->tersimpanBatch = null;
        $this->simpanDraft();

        Notification::make()
            ->title('Baris dihapus, nomor diurut ulang dari '.$this->nomorAwal)
            ->success()
            ->send();
    }

    private function penomoranUlang(SuratCutiGeneratorService $service): void
    {
        $seq = max(1, $this->nomorAwal);

        foreach ($this->hasil as $i => $h) {
            $this->hasil[$i]['no'] = $i + 1;

            if (! empty($h['duplikat'])) {
                $this->hasil[$i]['nomor'] = null;

                continue;
            }

            if (isset($h['bulan'], $h['tahun'])) {
                $this->hasil[$i]['nomor'] = $service->formatNomor($seq, $this->kode, (int) $h['bulan'], (int) $h['tahun']);
                $seq++;
            }
        }
    }

    /**
     * Salin satu baris: nomor surat + angka sisa tahun berjalan.
     * Baris duplikat tidak punya nomor — tidak disalin.
     */
    public function salinSatu(int $index): void
    {
        $h = $this->hasil[$index] ?? null;

        if (! is_array($h) || empty($h['nomor']) || ! empty($h['duplikat'])) {
            return;
        }

        $tahunBerjalan = $this->tahun !== [] ? max($this->tahun) : (int) now()->year;
        $sisa = $h['sisa'][$tahunBerjalan] ?? '—';
        $teks = $h['nomor']."\tSisa {$tahunBerjalan}: {$sisa}";

        $this->js(
            '(async function (teks) { try { await navigator.clipboard.writeText(teks); } catch (e) {'
            .' var ta = document.createElement("textarea"); ta.value = teks;'
            .' ta.style.position = "fixed"; ta.style.opacity = "0";'
            .' document.body.appendChild(ta); ta.select();'
            .' try { document.execCommand("copy"); } catch (err) {} ta.remove(); } })'
            .'('.Js::from($teks).');',
        );

        Notification::make()
            ->title('Disalin: nomor + sisa '.$tahunBerjalan)
            ->success()
            ->send();
    }

    private function hasilTsv(): string
    {
        $kepala = array_merge(
            ['No', 'Nama', 'Pengajuan', 'Nomor Surat'],
            array_map(fn (int $y): string => 'Sisa '.$y, $this->tahun),
        );

        $baris = [$kepala];

        // Baris duplikat tidak ikut tersalin (tidak punya nomor).
        foreach ($this->hasil as $h) {
            if (! empty($h['duplikat']) || empty($h['nomor'])) {
                continue;
            }

            $sel = [(string) $h['no'], $h['nama'], $h['pengajuan'], $h['nomor']];

            foreach ($this->tahun as $y) {
                $sel[] = (string) ($h['sisa'][$y] ?? '—');
            }

            $baris[] = array_map(
                fn (string $v): string => str_replace(["\t", "\r", "\n"], ' ', $v),
                $sel,
            );
        }

        return implode("\n", array_map(fn (array $r): string => implode("\t", $r), $baris));
    }
}

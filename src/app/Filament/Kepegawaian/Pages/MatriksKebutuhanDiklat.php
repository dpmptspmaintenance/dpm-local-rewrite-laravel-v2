<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\MatriksKebutuhanDiklatExport;
use App\Exports\Kepegawaian\MatriksKebutuhanSheet;
use App\Filament\Kepegawaian\Concerns\SelectsKompetensiTahun;
use App\Models\Kepegawaian\PegawaiKompetensi;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Matriks kebutuhan diklat seluruh jabatan: baris = jabatan, kolom = jenis
 * diklat (Seminar, Workshop/Lokakarya, ...), isi sel = jumlah event tahun itu.
 * Kolom jenis dibangun dari data (bukan daftar tetap) supaya jenis baru di
 * sumber otomatis muncul tanpa ubah kode.
 *
 * Digambar sebagai tabel HTML biasa, bukan Filament Table: kolomnya dinamis
 * dan selnya butuh pewarnaan intensitas, yang lebih jelas ditulis langsung.
 *
 * "Kebutuhan" = sebaran apa adanya; tidak ada target diklat di sumber data.
 */
class MatriksKebutuhanDiklat extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use SelectsKompetensiTahun;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Matriks Kebutuhan Diklat';

    protected static string | \UnitEnum | null $navigationGroup = 'Kompetensi';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Matriks Kebutuhan Diklat Seluruh Jabatan';

    protected string $view = 'filament.kepegawaian.pages.matriks-kebutuhan-diklat';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportMatriks(ExcelFormat::XLSX, 'xlsx')),
            Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportMatriks(ExcelFormat::DOMPDF, 'pdf')),
        ];
    }

    /**
     * Excel: 2 sheet — matriks (sumbernya buildMatriks() yang sama dipakai
     * tabel di layar) dan daftar mentah semua kompetensi tahun itu tanpa
     * filter jenis, jadi angka ringkas selalu bisa ditelusuri ke baris asli.
     *
     * PDF: matriks saja (1 sheet). DOMPDF merender 280-an baris rincian
     * mentah dari sheet 2 sampai menghabiskan memory_limit (dicoba, fatal
     * error) — dan PDF sepanjang itu juga tak enak dibaca/dicetak, jadi PDF
     * tetap ringkasan seperti sebelumnya.
     */
    protected function exportMatriks(string $writerType, string $extension): BinaryFileResponse
    {
        $tahun = $this->selectedTahun();
        $filename = "matriks-kebutuhan-diklat-{$tahun}-".now()->format('Ymd-His').".{$extension}";

        $export = $writerType === ExcelFormat::DOMPDF
            ? new MatriksKebutuhanSheet($tahun)
            : new MatriksKebutuhanDiklatExport($tahun);

        return Excel::download($export, $filename, $writerType);
    }

    /**
     * Matriks siap-render untuk tahun yang sedang dipilih di halaman.
     *
     * @return array{jenis: list<string>, baris: list<array<string, mixed>>, maks: int}
     */
    public function getMatriks(): array
    {
        return self::buildMatriks($this->selectedTahun());
    }

    /**
     * Matriks siap-render: daftar jenis (kolom), daftar baris per jabatan
     * dengan jumlah event per jenis, total per baris, dan nilai maksimum sel
     * (untuk skala intensitas warna). Static — dipakai halaman ini dan
     * MatriksKebutuhanSheet (export), supaya keduanya tak pernah beda angka.
     *
     * @return array{jenis: list<string>, baris: list<array<string, mixed>>, maks: int}
     */
    public static function buildMatriks(int $tahun): array
    {
        $rows = PegawaiKompetensi::rekapMatriksDiklat($tahun)->get();

        $jenis = $rows->pluck('jenis')->unique()->sort()->values()->all();

        $baris = $rows
            ->groupBy('jabatan')
            ->map(function (Collection $group, string $jabatan) use ($jenis): array {
                $perJenis = $group->keyBy('jenis');

                $sel = [];
                foreach ($jenis as $j) {
                    $row = $perJenis->get($j);
                    $sel[$j] = [
                        'event' => (int) ($row->jumlah_event ?? 0),
                        'jam' => (int) ($row->total_jam ?? 0),
                    ];
                }

                return [
                    'jabatan' => $jabatan,
                    'sel' => $sel,
                    'total_event' => $group->sum(fn ($r) => (int) $r->jumlah_event),
                    'total_jam' => $group->sum(fn ($r) => (int) $r->total_jam),
                ];
            })
            ->sortByDesc('total_event')
            ->values()
            ->all();

        $maks = 0;
        foreach ($baris as $b) {
            foreach ($b['sel'] as $s) {
                $maks = max($maks, $s['event']);
            }
        }

        return ['jenis' => $jenis, 'baris' => $baris, 'maks' => $maks];
    }

    /** Total per jenis (baris paling bawah matriks) untuk tahun yang sedang dipilih. */
    public function getTotalPerJenis(): array
    {
        return self::totalPerJenis($this->selectedTahun());
    }

    /** Static — dipakai halaman ini dan MatriksKebutuhanSheet (export). */
    public static function totalPerJenis(int $tahun): array
    {
        $rows = PegawaiKompetensi::rekapMatriksDiklat($tahun)->get();

        return $rows
            ->groupBy('jenis')
            ->map(fn (Collection $g) => [
                'event' => $g->sum(fn ($r) => (int) $r->jumlah_event),
                'jam' => $g->sum(fn ($r) => (int) $r->total_jam),
            ])
            ->all();
    }
}

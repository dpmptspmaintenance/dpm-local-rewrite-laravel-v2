<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages;

use App\Exports\Kepegawaian\PegawaiFieldsExport;
use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Filament\Kepegawaian\Widgets\PegawaiStatusOverview;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Js;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPegawaiProfil extends ListRecords
{
    protected static string $resource = PegawaiProfilResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PegawaiStatusOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPegawai')
                ->label('Unduh Pegawai')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Unduh Data Pegawai')
                ->modalDescription('Centang field yang ingin diunduh. Data mengikuti filter & pencarian tabel saat ini.')
                ->modalSubmitActionLabel('Unduh Excel')
                ->modalWidth('4xl')
                ->schema([
                    $this->pegawaiFieldsSchema('Field yang diunduh'),
                ])
                ->action(fn (array $data): BinaryFileResponse => $this->downloadPegawai($data['fields'] ?? [])),
            Action::make('salinPegawai')
                ->label('Salin Pegawai')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->modalHeading('Salin Data Pegawai')
                ->modalDescription('Centang field yang ingin disalin. Data mengikuti filter & pencarian tabel saat ini — disalin sebagai teks, tinggal tempel (Ctrl+V) di Excel/Spreadsheet. Tanpa mengunduh berkas.')
                ->modalSubmitActionLabel('Salin ke Clipboard')
                ->modalWidth('4xl')
                ->schema([
                    $this->pegawaiFieldsSchema('Field yang disalin'),
                ])
                ->action(function (array $data): void {
                    $this->salinPegawai($data['fields'] ?? []);
                }),
        ];
    }

    /**
     * Pilihan field untuk dialog unduh/salin — opsi, kolom, dan default sama.
     */
    private function pegawaiFieldsSchema(string $label): CheckboxList
    {
        return CheckboxList::make('fields')
            ->label($label)
            ->options(PegawaiFieldsExport::LABELS)
            ->columns(3)
            ->searchable()
            ->bulkToggleable()
            ->required()
            ->minItems(1)
            ->default(['nip', 'nama', 'jabatan']);
    }

    /**
     * Batasi ke kolom yang dikenal supaya input dialog tidak bisa dipakai
     * menambah kolom sembarang, dan urutkan sesuai LABELS agar konsisten.
     *
     * @param  array<int, string>  $fields
     * @return list<string>
     */
    private function selectedPegawaiFields(array $fields): array
    {
        $allowed = array_keys(PegawaiFieldsExport::LABELS);
        $selected = array_values(array_intersect($allowed, array_map('strval', $fields)));

        return $selected === [] ? ['nip', 'nama'] : $selected;
    }

    /**
     * Query export untuk SEMUA baris yang sedang tampil (mengikuti search +
     * filter tabel, tanpa paginasi).
     *
     * @param  list<string>  $selected
     */
    private function pegawaiExportQuery(array $selected): Builder
    {
        // Kolom sort tabel bisa berupa agregat (kompetensi_count/anak_count)
        // yang tidak ada di tabel; jatuhkan ke 'nama' bila bukan kolom asli.
        $sortColumn = $this->getTableSortColumn();

        if (! is_string($sortColumn) || ! array_key_exists($sortColumn, PegawaiFieldsExport::LABELS)) {
            $sortColumn = 'nama';
        }

        $sortDirection = strtolower((string) $this->getTableSortDirection()) === 'desc' ? 'desc' : 'asc';

        // Field virtual (mis. nama_kapital) tidak ada di DB; SELECT hanya
        // kolom asli + kolom sumber yang dibutuhkan accessor.
        return $this->getFilteredTableQuery()
            ->reorder($sortColumn, $sortDirection)
            ->select(PegawaiFieldsExport::databaseColumns($selected));
    }

    /**
     * Unduh Excel berisi kolom pilihan pengguna untuk SEMUA baris yang sedang
     * tampil (mengikuti search + filter tabel, tanpa paginasi).
     *
     * @param  array<int, string>  $fields
     */
    protected function downloadPegawai(array $fields): BinaryFileResponse
    {
        $selected = $this->selectedPegawaiFields($fields);
        $query = $this->pegawaiExportQuery($selected);

        $filename = 'data-pegawai-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new PegawaiFieldsExport($query, $selected), $filename);
    }

    /**
     * Salin data pegawai ke clipboard sebagai teks tab-separasi (header +
     * baris), siap ditempel langsung di Excel/Spreadsheet — tanpa berkas.
     *
     * @param  array<int, string>  $fields
     */
    protected function salinPegawai(array $fields): void
    {
        $selected = $this->selectedPegawaiFields($fields);
        $export = new PegawaiFieldsExport($this->pegawaiExportQuery($selected), $selected);

        $baris = [$export->headings()];

        foreach ($export->query()->get() as $row) {
            $baris[] = array_map(
                fn ($value): string => str_replace(["\t", "\r", "\n"], ' ', (string) $value),
                $export->map($row),
            );
        }

        $teks = implode("\n", array_map(fn (array $r): string => implode("\t", $r), $baris));
        $jumlah = count($baris) - 1;

        // Clipboard API (dengan fallback textarea+execCommand bila izin
        // clipboard ditolak browser). Teks di-escape aman via Js::from.
        $this->js(
            '(async function (teks) { try { await navigator.clipboard.writeText(teks); } catch (e) {'
            .' var ta = document.createElement("textarea"); ta.value = teks;'
            .' ta.style.position = "fixed"; ta.style.opacity = "0";'
            .' document.body.appendChild(ta); ta.select();'
            .' try { document.execCommand("copy"); } catch (err) {} ta.remove(); } })'
            .'('.Js::from($teks).');',
        );

        Notification::make()
            ->title($jumlah.' baris disalin ke clipboard')
            ->body('Tempel (Ctrl+V) langsung di Excel atau Spreadsheet.')
            ->success()
            ->send();
    }
}

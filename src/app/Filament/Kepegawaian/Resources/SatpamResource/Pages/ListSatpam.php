<?php

namespace App\Filament\Kepegawaian\Resources\SatpamResource\Pages;

use App\Exports\Kepegawaian\SatpamFieldsExport;
use App\Filament\Kepegawaian\Resources\SatpamResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListSatpam extends ListRecords
{
    protected static string $resource = SatpamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSatpam')
                ->label('Unduh Satpam')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Unduh Data Satpam')
                ->modalDescription('Centang field yang ingin diunduh. Data mengikuti filter & pencarian tabel saat ini.')
                ->modalSubmitActionLabel('Unduh Excel')
                ->modalWidth('4xl')
                ->schema([
                    CheckboxList::make('fields')
                        ->label('Field yang diunduh')
                        ->options(SatpamFieldsExport::LABELS)
                        ->columns(3)
                        ->searchable()
                        ->bulkToggleable()
                        ->required()
                        ->minItems(1)
                        ->default(['nama', 'nik', 'telepon', 'kelengkapan']),
                ])
                ->action(fn (array $data): BinaryFileResponse => $this->downloadSatpam($data['fields'] ?? [])),
            CreateAction::make()->label('Tambah Satpam'),
        ];
    }

    /**
     * Batasi ke kolom yang dikenal supaya input dialog tidak bisa dipakai
     * menambah kolom sembarang, dan urutkan sesuai LABELS agar konsisten.
     *
     * @param  array<int, string>  $fields
     * @return list<string>
     */
    private function selectedSatpamFields(array $fields): array
    {
        $allowed = array_keys(SatpamFieldsExport::LABELS);
        $selected = array_values(array_intersect($allowed, array_map('strval', $fields)));

        return $selected === [] ? ['nama', 'nik'] : $selected;
    }

    /**
     * @param  list<string>  $selected
     */
    private function satpamExportQuery(array $selected): Builder
    {
        $sortColumn = $this->getTableSortColumn();

        if (! is_string($sortColumn) || ! array_key_exists($sortColumn, SatpamFieldsExport::LABELS)) {
            $sortColumn = 'nama';
        }

        if (in_array($sortColumn, SatpamFieldsExport::VIRTUAL, true)) {
            $sortColumn = 'nama';
        }

        $sortDirection = strtolower((string) $this->getTableSortDirection()) === 'desc' ? 'desc' : 'asc';

        return $this->getFilteredTableQuery()
            ->reorder($sortColumn, $sortDirection)
            ->select(SatpamFieldsExport::databaseColumns($selected));
    }

    /**
     * @param  array<int, string>  $fields
     */
    protected function downloadSatpam(array $fields): BinaryFileResponse
    {
        $selected = $this->selectedSatpamFields($fields);
        $query = $this->satpamExportQuery($selected);

        $filename = 'data-satpam-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new SatpamFieldsExport($query, $selected), $filename);
    }
}

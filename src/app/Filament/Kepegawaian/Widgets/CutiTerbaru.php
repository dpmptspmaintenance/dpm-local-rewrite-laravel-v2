<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Filament\Kepegawaian\Resources\CutiResource;
use App\Models\Kepegawaian\Cuti;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CutiTerbaru extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cuti Terbaru')
            ->query(Cuti::query()->orderByDesc('id')->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn (Cuti $record): ?string => $record->no_surat)
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('tanggal_mulai_diajukan')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->placeholder('—'),
            ])
            ->recordUrl(fn (Cuti $record): string => CutiResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Belum ada data cuti');
    }
}

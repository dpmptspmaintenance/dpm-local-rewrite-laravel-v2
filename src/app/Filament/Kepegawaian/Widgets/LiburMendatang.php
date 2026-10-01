<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Models\Kepegawaian\HariLibur;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LiburMendatang extends TableWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Hari Libur Mendatang')
            ->query(
                HariLibur::query()
                    ->where('tanggal', '>=', now()->toDateString())
                    ->orderBy('tanggal')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn (HariLibur $record): string => $record->tanggal?->diffForHumans() ?? ''),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap(),
            ])
            ->emptyStateHeading('Tidak ada libur mendatang');
    }
}

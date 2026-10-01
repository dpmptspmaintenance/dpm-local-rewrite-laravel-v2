<?php

namespace App\Filament\Arsip\Widgets;

use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pending = Document::where('status', Document::STATUS_PENDING)->count();

        return [
            Stat::make('Menunggu Verifikasi', $pending)
                ->description('Perlu ditinjau admin')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Diterbitkan', Document::where('status', Document::STATUS_PUBLISHED)->count())
                ->description('Dokumen aktif')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Ditolak', Document::where('status', Document::STATUS_REJECTED)->count())
                ->description('Perlu diunggah ulang')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Dokumen', Document::count())
                ->description('Seluruh arsip tercatat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}

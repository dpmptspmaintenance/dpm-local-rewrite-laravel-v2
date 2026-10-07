<?php

namespace App\Filament\Arsip\Widgets;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $baseQuery = DocumentResource::getEloquentQuery();

        $pending = (clone $baseQuery)->where('status', Document::STATUS_PENDING)->count();
        $published = (clone $baseQuery)->where('status', Document::STATUS_PUBLISHED)->count();
        $rejected = (clone $baseQuery)->where('status', Document::STATUS_REJECTED)->count();
        $total = (clone $baseQuery)->count();

        return [
            Stat::make('Menunggu Verifikasi', $pending)
                ->description('Perlu ditinjau admin')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Diterbitkan', $published)
                ->description('Dokumen aktif')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Ditolak', $rejected)
                ->description('Perlu diunggah ulang')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Dokumen', $total)
                ->description('Arsip yang dapat diakses')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}

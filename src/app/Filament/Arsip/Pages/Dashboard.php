<?php

namespace App\Filament\Arsip\Pages;

use App\Filament\Arsip\Resources\DocumentResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

/**
 * Dashboard Arsip Digital: tombol pintas ke form unggah (halaman Create
 * Dokumen) + grid widget statistik. Form unggah utuh sengaja TIDAK ditanam
 * di sini (keputusan user: dashboard cukup tombol, biar dashboard ringan dan
 * form punya ruang penuh).
 */
class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make([
                    Action::make('unggah')
                        ->label('Unggah Dokumen (Berkas / Tautan URL)')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->url(DocumentResource::getUrl('create'))
                        ->color('primary'),
                    Action::make('daftar')
                        ->label('Lihat Semua Dokumen')
                        ->icon('heroicon-o-document-text')
                        ->url(DocumentResource::getUrl('index'))
                        ->color('gray'),
                ]),
                Grid::make($this->getColumns())
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }
}

<?php

namespace App\Filament\Arsip\Resources\DocumentResource\Pages;

use App\Filament\Arsip\Pages\ReviewQueue;
use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reviewQueue')
                ->label('Dashboard Verifikasi')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->url(ReviewQueue::getUrl())
                ->visible(fn (): bool => Auth::user()->isArsipAdmin()),
            CreateAction::make()
                ->label('Unggah Dokumen'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge(fn (): int => (clone $this->getResourceQuery())->count()),
            'pending' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Document::STATUS_PENDING))
                ->badge(fn (): int => (clone $this->getResourceQuery())->where('status', Document::STATUS_PENDING)->count()),
            'published' => Tab::make('Diterbitkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Document::STATUS_PUBLISHED)),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Document::STATUS_REJECTED)),
        ];
    }

    private function getResourceQuery(): Builder
    {
        return DocumentResource::getEloquentQuery();
    }
}

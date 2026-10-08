<?php

namespace App\Filament\Arsip\Resources\DocumentResource\Pages;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentFile;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (Document $record): bool => DocumentResource::canEdit($record)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Section::make('Detail')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('title')->label('Judul'),
                        TextEntry::make('source_type')
                            ->label('Sumber')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Document::SOURCES[$state] ?? $state)
                            ->color(fn (string $state): string => $state === Document::SOURCE_URL ? 'info' : 'gray'),
                        TextEntry::make('ownership.name')
                            ->label('Ownership')
                            ->badge()
                            ->placeholder('— (Umum / Semua Unit)')
                            ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray'),
                        TextEntry::make('category.name')->label('Kategori')->placeholder('—'),
                        TextEntry::make('tags.name')->label('Tag')->badge()->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Document::STATUSES[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                Document::STATUS_PENDING => 'warning',
                                Document::STATUS_PUBLISHED => 'success',
                                Document::STATUS_REJECTED => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('rejection_reason')->label('Alasan Ditolak')->visible(fn (Document $record): bool => filled($record->rejection_reason)),
                        TextEntry::make('source_url')
                            ->label('Tautan')
                            ->url(fn (Document $record): ?string => $record->isUrl() ? $record->source_url : null)
                            ->openUrlInNewTab()
                            ->visible(fn (Document $record): bool => $record->isUrl())
                            ->limit(60),
                        TextEntry::make('drive_folder_name')
                            ->label('Folder Penyimpanan')
                            ->placeholder('— (belum diorganisasi)')
                            ->visible(fn (Document $record): bool => ! $record->isUrl()),
                        TextEntry::make('creator.nama')->label('Diunggah Oleh')->placeholder('—'),
                        TextEntry::make('verifier.nama')->label('Diverifikasi Oleh')->placeholder('—'),
                        TextEntry::make('verified_at')->label('Tgl Verifikasi')->dateTime('d M Y H:i')->placeholder('—'),
                        TextEntry::make('created_at')->label('Tgl Unggah')->dateTime('d M Y H:i'),
                    ]),
                Section::make('Berkas')
                    ->columnSpan(2)
                    ->visible(fn (Document $record): bool => ! $record->isUrl())
                    ->schema([
                        RepeatableEntry::make('files')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('original_filename')
                                    ->label('Nama Berkas')
                                    ->url(fn (DocumentFile $record): string => $record->openUrl())
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-paper-clip'),
                                TextEntry::make('file_extension')
                                    ->label('Ekstensi')
                                    ->badge()
                                    ->placeholder('—'),
                                TextEntry::make('file_size')
                                    ->label('Ukuran')
                                    ->formatStateUsing(fn (?int $state): string => $state === null || $state === 0
                                        ? '—'
                                        : number_format($state / 1024, 1, ',', '.').' KB'),
                            ])
                            ->columns(3)
                            ->placeholder('Belum ada berkas fisik.'),
                    ]),
                Section::make('Tautan')
                    ->columnSpan(2)
                    ->visible(fn (Document $record): bool => $record->isUrl())
                    ->schema([
                        TextEntry::make('source_url')
                            ->hiddenLabel()
                            ->formatStateUsing(fn () => 'Dokumen ini berupa tautan. Buka tautannya di tab baru dari kolom "Tautan" di kiri.'),
                    ]),
            ]),
        ]);
    }
}

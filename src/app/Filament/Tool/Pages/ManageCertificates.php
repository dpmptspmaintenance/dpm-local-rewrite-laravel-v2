<?php

namespace App\Filament\Tool\Pages;

use App\Models\Certificate\CertificateTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Daftar template sertifikat milik pengguna (admin modul melihat semua).
 * Template disimpan lewat halaman Generator; di sini hanya kelola/unduh.
 */
class ManageCertificates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Template Sertifikat';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Template Sertifikat';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?string $slug = 'template-sertifikat';

    protected string $view = 'filament.tool.pages.manage-certificates';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Template yang pernah disimpan. Klik "Buka" untuk mengubah posisi field atau mengunduh ulang.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Template Baru')
                ->icon('heroicon-o-plus')
                ->url(CertificateGenerator::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CertificateTemplate::query()
                ->visibleTo(Auth::user())
                ->with(['latestDataset', 'creator'])
                ->withCount('fields'))
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('fields_count')
                    ->label('Field')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('latestDataset.row_count')
                    ->label('Baris Data')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('file_name_pattern')
                    ->label('Pola Nama Berkas')
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(fn (): bool => CertificateTemplate::userIsAdmin(Auth::user())),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (CertificateTemplate $record): string => CertificateGenerator::getUrl(['template' => $record->id])),
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (CertificateTemplate $record): void {
                        $this->duplicateTemplate($record);
                    }),
                DeleteAction::make()
                    ->using(function (CertificateTemplate $record): bool {
                        $this->deleteBackground($record);

                        return (bool) $record->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if (! $record instanceof CertificateTemplate || ! $record->isVisibleTo(Auth::user())) {
                                    continue;
                                }

                                $this->deleteBackground($record);
                                $record->delete();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada template')
            ->emptyStateDescription('Buat template pertama lewat tombol "Template Baru".');
    }

    private function duplicateTemplate(CertificateTemplate $record): void
    {
        $copy = $record->replicate();
        $copy->title = $record->title.' (salinan)';
        $copy->created_by = Auth::id();
        $copy->save();

        foreach ($record->fields as $field) {
            $copy->fields()->create($field->only([
                'key',
                'label',
                'x_mm',
                'y_mm',
                'w_mm',
                'h_mm',
                'font_family',
                'font_size_pt',
                'color',
                'bold',
                'italic',
                'align',
                'valign',
                'sort_order',
            ]));
        }

        foreach ($record->datasets as $dataset) {
            $newDataset = $dataset->replicate();
            $newDataset->template_id = $copy->id;
            $newDataset->created_by = Auth::id();
            $newDataset->save();

            foreach ($dataset->rows as $row) {
                $newDataset->rows()->create($row->only(['row_index', 'data']));
            }
        }

        Notification::make()
            ->title('Template diduplikat')
            ->body($copy->title)
            ->success()
            ->send();
    }

    private function deleteBackground(CertificateTemplate $record): void
    {
        if (filled($record->background_path)) {
            Storage::disk('local')->delete($record->background_path);
        }
    }
}

<?php

namespace App\Filament\Arsip\Pages;

use App\Models\Category;
use App\Models\Document;
use App\Models\Tag;
use App\Services\ArsipDigital\DocumentService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard Verifikasi — AGENTS.md § 2.A & § 5: layar kiri (iframe pratinjau
 * Google Drive), layar kanan (form judul/kategori/tag + Approve/Reject).
 * Daftar antrean (dokumen pending_review) ditampilkan di atas; memilih satu
 * baris memuat split screen di bawahnya.
 */
class ReviewQueue extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Dashboard Verifikasi';

    protected static string | \UnitEnum | null $navigationGroup = 'Arsip';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Dashboard Verifikasi';

    protected string $view = 'filament.arsip.pages.review-queue';

    public ?int $selectedId = null;

    public ?array $data = [];

    public ?string $rejectReason = null;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isArsipAdmin();
    }

    public function mount(): void
    {
        $first = $this->pendingQuery()->first();

        if ($first) {
            $this->select($first->id);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->pendingQuery())
            ->columns([
                TextColumn::make('title')->label('Judul')->wrap()->weight('medium'),
                TextColumn::make('files.original_filename')->label('Nama File')->limit(35)->listWithLineBreaks()->limitList(2)->toggleable(),
                TextColumn::make('creator.nama')->label('Diunggah Oleh')->placeholder('—'),
                TextColumn::make('created_at')->label('Tgl Unggah')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at')
            ->recordActions([
                \Filament\Actions\Action::make('review')
                    ->label('Tinjau')
                    ->icon('heroicon-o-eye')
                    ->action(fn (Document $record) => $this->select($record->id)),
            ])
            ->emptyStateHeading('Tidak ada dokumen menunggu verifikasi');
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;

        $document = $this->selectedDocument();

        if (! $document) {
            return;
        }

        $this->form->fill([
            'title' => $document->title,
            'category_id' => $document->category_id,
            'tag_names' => $document->tags()->pluck('name')->all(),
        ]);
    }

    public function selectedDocument(): ?Document
    {
        if (! $this->selectedId) {
            return null;
        }

        return Document::query()->find($this->selectedId);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            // Verifikasi WAJIB melengkapi tiga isian ini (keputusan user) —
            // beda dengan form upload staf yang semuanya opsional karena
            // staf tidak wajib tahu klasifikasi final.
            ->components([
                TextInput::make('title')
                    ->label('Judul Dokumen')
                    ->required()
                    ->maxLength(255),
                Select::make('category_id')
                    ->label('Kategori')
                    ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->placeholder('— Pilih kategori —'),
                TagsInput::make('tag_names')
                    ->label('Tag')
                    ->suggestions(fn (): array => Tag::query()->orderBy('name')->pluck('name')->all())
                    ->required()
                    ->placeholder('Tambah tag…'),
            ])
            ->statePath('data');
    }

    public function approve(DocumentService $service): void
    {
        $document = $this->selectedDocument();

        if (! $document) {
            return;
        }

        $state = $this->form->getState();

        $service->approve(
            $document,
            Auth::user(),
            $state['title'] ?? null,
            $state['category_id'] ?? null,
            $state['tag_names'] ?? [],
        );

        Notification::make()->title('Dokumen diterbitkan')->success()->send();

        $this->advanceAfterDecision();
    }

    public function moveFileAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('moveFile')
            ->label('Pindah Berkas')
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->modalHeading('Pindahkan Berkas ke Dokumen Lain')
            ->modalDescription('Berkas dokumen ini akan diserahkan ke dokumen target. Baris dokumen ini akan terhapus — metadata (judul/kategori/tag) ikut dokumen target.')
            ->visible(fn (): bool => ($doc = $this->selectedDocument()) !== null && ! $doc->isUrl() && $doc->files()->count() > 0)
            ->form([
                Select::make('target_id')
                    ->label('Dokumen Target')
                    ->options(fn (): array => Document::query()
                        ->where('id', '!=', $this->selectedId)
                        ->where('source_type', Document::SOURCE_FILE)
                        ->orderByDesc('created_at')
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(fn (Document $d): array => [
                            $d->id => sprintf('#%d — %s (%s)', $d->id, $d->title, Document::STATUSES[$d->status] ?? $d->status),
                        ])
                        ->all())
                    ->searchable()
                    ->required()
                    ->native(false)
                    ->placeholder('— Pilih dokumen target —'),
            ])
            ->action(function (array $data, DocumentService $service): void {
                $source = $this->selectedDocument();
                $target = Document::find($data['target_id'] ?? null);

                if (! $source || ! $target) {
                    Notification::make()->title('Dokumen sumber atau target tidak ditemukan')->danger()->send();

                    return;
                }

                try {
                    $service->moveFileTo($source, $target, Auth::user());

                    Notification::make()
                        ->title('Berkas berhasil dipindah')
                        ->body("Berhasil dipindah ke \"{$target->title}\".")
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Gagal memindah berkas')->body($e->getMessage())->danger()->persistent()->send();

                    return;
                }

                $this->advanceAfterDecision();
            });
    }

    public function reject(DocumentService $service): void
    {
        $document = $this->selectedDocument();

        if (! $document) {
            return;
        }

        $reason = trim((string) ($this->rejectReason ?? ''));

        if ($reason === '') {
            Notification::make()->title('Isi alasan penolakan terlebih dahulu')->danger()->send();

            return;
        }

        $service->reject($document, Auth::user(), $reason);

        Notification::make()->title('Dokumen ditolak')->warning()->send();

        $this->rejectReason = null;
        $this->advanceAfterDecision();
    }

    private function advanceAfterDecision(): void
    {
        $this->selectedId = null;
        $this->resetTable();

        $next = $this->pendingQuery()->first();

        if ($next) {
            $this->select($next->id);
        }
    }

    private function pendingQuery(): Builder
    {
        return Document::query()
            ->where('status', Document::STATUS_PENDING)
            ->orderBy('created_at');
    }
}

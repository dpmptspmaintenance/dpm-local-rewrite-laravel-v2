<?php

namespace App\Filament\Arsip\Pages;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Category;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Library Arsip — katalog dokumen terbit yang dikelompokkan per kategori.
 *
 * Dua tampilan dalam satu komponen:
 *   - /library                 → indeks: kartu tiap kategori + jumlah dokumen
 *   - /library/{kategori}      → isi kategori: tabel dokumen terbit
 *
 * Hanya dokumen berstatus Diterbitkan (published) yang tampil — dokumen
 * pending/rejected tetap di menu "Dokumen" milik pengunggah. Daftar dokumen
 * memakai DocumentResource::getEloquentQuery() supaya aturan hak akses
 * ownership (staf hanya melihat unit-nya sendiri atau dokumen Publik) sama
 * persis dengan menu Dokumen, tidak ditulis ulang di sini.
 */
class Library extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    /**
     * Slug semu untuk kartu dokumen tanpa kategori. Bila ada kategori asli
     * yang slug-nya sama, kartu "Tanpa Kategori" menang.
     */
    public const SLUG_TANPA_KATEGORI = 'tanpa-kategori';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Library';

    protected static string|\UnitEnum|null $navigationGroup = 'Arsip';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Library Arsip';

    protected static ?string $slug = 'library';

    protected string $view = 'filament.arsip.pages.library';

    /** Slug kategori aktif; null berarti halaman indeks (daftar kategori). */
    public ?string $categorySlug = null;

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    /**
     * Path rute memakai parameter opsional supaya indeks dan isi kategori
     * berbagi satu kelas + satu nama rute (tautan kategori bisa di-bookmark).
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/library/{category?}';
    }

    /**
     * Nama rute tanpa parameter (default menurunkannya dari slug) agar
     * route('filament.<panel>.pages.library') tetap bersih.
     */
    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'library';
    }

    public function mount(?string $category = null): void
    {
        $category = trim((string) $category);

        if ($category === '') {
            $this->categorySlug = null;

            return;
        }

        $this->categorySlug = $category;

        if ($this->isTanpaKategori()) {
            return;
        }

        // Kategori tak dikenal → 404, bukan halaman kosong yang menyesatkan.
        if (! Category::query()->where('slug', $category)->exists()) {
            abort(404);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->categoryTitle();
    }

    public function getSubheading(): ?string
    {
        if ($this->categorySlug === null) {
            return 'Seluruh dokumen terbit. Gunakan filter kategori di bawah untuk menyaring.';
        }

        return 'Dokumen terbit pada kategori ini.';
    }

    public function isTanpaKategori(): bool
    {
        return $this->categorySlug === self::SLUG_TANPA_KATEGORI;
    }

    public function categoryTitle(): string
    {
        if ($this->categorySlug === null) {
            return 'Library Arsip';
        }

        if ($this->isTanpaKategori()) {
            return 'Tanpa Kategori';
        }

        return (string) (Category::query()->where('slug', $this->categorySlug)->value('name')
            ?? 'Library Arsip');
    }

    /**
     * Kartu indeks: seluruh kategori master (termasuk yang masih kosong) plus
     * satu kartu "Tanpa Kategori" bila ada dokumen tanpa kategori.
     *
     * @return array<int, array{name:string,count:int,url:string,tanpa_kategori:bool,slug:?string}>
     */
    public function categoryCards(): array
    {
        $counts = $this->baseQuery()
            ->selectRaw('category_id, COUNT(*) as jumlah')
            ->groupBy('category_id')
            ->pluck('jumlah', 'category_id');

        $cards = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'count' => (int) ($counts[$category->id] ?? 0),
                'url' => static::getUrl(['category' => $category->slug]),
                'tanpa_kategori' => false,
                'slug' => $category->slug,
            ])
            ->all();

        $tanpaKategori = $this->baseQuery()->whereNull('category_id')->count();

        if ($tanpaKategori > 0) {
            $cards[] = [
                'name' => 'Tanpa Kategori',
                'count' => $tanpaKategori,
                'url' => static::getUrl(['category' => self::SLUG_TANPA_KATEGORI]),
                'tanpa_kategori' => true,
                'slug' => self::SLUG_TANPA_KATEGORI,
            ];
        }

        return $cards;
    }

    public function table(Table $table): Table
    {
        return $table
            // Closure, bukan Builder instance: query harus dievaluasi ulang
            // tiap request supaya kategori aktif selalu terpakai.
            ->query(fn (): Builder => $this->documentsQuery())
            // Tampilan kartu: jumlah kolom per baris menyesuaikan lebar layar
            // (1 di HP, naik bertahap sampai 4 di layar lebar). Filament
            // merender tiap baris sebagai .fi-ta-record bergaya kartu saat
            // contentGrid() diisi.
            ->contentGrid([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
                '2xl' => 4,
            ])
            // Seluruh kartu klikable menuju detail dokumen (hanya bila user
            // berhak melihat detail); aksi ikon tetap tersedia.
            ->recordUrl(fn (Document $record): ?string => static::canViewDocument($record)
                ? DocumentResource::getUrl('view', ['record' => $record])
                : null)
            ->columns([
                // Wajib dibungkus komponen Layout (Stack) supaya Filament
                // memakai jalur render "columns layout"; tanpa ini
                // contentGrid() diabaikan dan tabel tetap dirender sebagai
                // tabel biasa.
                Stack::make([
                    TextColumn::make('title')
                        ->label('Judul')
                        ->searchable()
                        ->sortable()
                        ->wrap()
                        ->weight('semibold')
                        ->size('lg'),
                    TextColumn::make('category.name')
                        ->label('Kategori')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—')
                        ->visible(fn (): bool => $this->categorySlug === null),
                    TextColumn::make('ownership.name')
                        ->label('Unit')
                        ->badge()
                        ->placeholder('Umum')
                        ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray'),
                    TextColumn::make('tags.name')
                        ->label('Tag')
                        ->badge()
                        ->placeholder('—'),
                    TextColumn::make('files_count')
                        ->label('Berkas')
                        ->counts('files')
                        ->badge()
                        ->color('gray'),
                    TextColumn::make('created_at')
                        ->label('Tgl Unggah')
                        ->dateTime('d M Y')
                        ->sortable()
                        ->color('gray'),
                ])->space(1),
            ])
            ->defaultSort('title')
            ->recordActions([
                Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Document $record): string => $record->openUrl())
                    ->openUrlInNewTab(),
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->url(fn (Document $record): string => DocumentResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (Document $record): bool => static::canViewDocument($record)),
            ])
            ->recordActionsAlignment('start')
            ->emptyStateHeading($this->categorySlug === null
                ? 'Belum ada dokumen terbit'
                : 'Belum ada dokumen di kategori ini')
            ->emptyStateDescription('Dokumen muncul di sini setelah diverifikasi dan diterbitkan.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali ke Library')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getUrl())
                ->visible(fn (): bool => $this->categorySlug !== null),
        ];
    }

    /**
     * Dokumen terbit yang boleh dilihat pengguna aktif. Hak akses ownership
     * diwarisi dari DocumentResource::getEloquentQuery() lalu dipersempit ke
     * status Diterbitkan.
     */
    protected function baseQuery(): Builder
    {
        return DocumentResource::getEloquentQuery()
            ->where('status', Document::STATUS_PUBLISHED);
    }

    protected function documentsQuery(): Builder
    {
        $query = $this->baseQuery();

        if ($this->categorySlug === null) {
            return $query;
        }

        if ($this->isTanpaKategori()) {
            return $query->whereNull('category_id');
        }

        return $query->where('category_id', $this->categoryId());
    }

    protected function categoryId(): ?int
    {
        if ($this->categorySlug === null || $this->isTanpaKategori()) {
            return null;
        }

        $id = Category::query()->where('slug', $this->categorySlug)->value('id');

        return $id === null ? null : (int) $id;
    }

    protected static function canViewDocument(Document $document): bool
    {
        $user = Auth::user();

        return $user !== null && $user->canAccessDocument($document);
    }
}

<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\SuratTugasDasarHukumSettingResource\Pages;
use App\Models\Kepegawaian\SuratTugasDasarHukumSetting;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Daftar dasar hukum BAKU yang otomatis disertakan di SEMUA Surat Tugas
 * (mis. Perda APBD tahun berjalan, Perwal Penjabaran APBD) — diedit di sini
 * tanpa perlu mengganti file template .docx tiap kali ada Perda/Perwal baru.
 * Lihat SuratTugasGeneratorService::dasarHukumLines() untuk cara baris ini
 * digabung dengan dasar hukum tambahan per-surat saat digenerate.
 */
class SuratTugasDasarHukumSettingResource extends Resource
{
    protected static ?string $model = SuratTugasDasarHukumSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Dasar Hukum Surat Tugas';

    protected static string | \UnitEnum | null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Dasar Hukum';

    protected static ?string $pluralModelLabel = 'Dasar Hukum Surat Tugas';

    protected static ?string $slug = 'dasar-hukum-surat-tugas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('teks')
                ->label('Teks Dasar Hukum')
                ->helperText('Mis. "Peraturan Daerah Kota Semarang Nomor 13 Tahun 2025 tentang Anggaran Pendapatan dan Belanja Daerah Tahun Anggaran 2026;"')
                ->rows(3)
                ->required()
                ->columnSpanFull(),
            TextInput::make('urutan')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->helperText('Angka lebih kecil tampil lebih dulu.')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('urutan')->label('Urutan')->sortable(),
                TextColumn::make('teks')->label('Teks Dasar Hukum')->wrap(),
            ])
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada dasar hukum baku')
            ->emptyStateDescription('Tambah dasar hukum yang berlaku untuk semua Surat Tugas, mis. Perda APBD tahun berjalan.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSuratTugasDasarHukumSetting::route('/'),
        ];
    }
}

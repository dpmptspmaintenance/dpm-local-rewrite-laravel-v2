<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\SuratTugasResource\Pages;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\Kepegawaian\SuratTugas;
use App\Services\Kepegawaian\SuratTugasGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Generator Surat Tugas dari template .docx (resources/templates/kepegawaian/
 * surat-tugas.docx) — bisa menugaskan banyak pegawai sekaligus (lampiran
 * daftar pegawai di-clone otomatis sebanyak pegawai yang dipilih).
 *
 * nomor_naskah/tanggal_naskah/ttd_pengirim TIDAK ada form-nya di sini —
 * placeholder itu dibiarkan literal di dokumen hasil, diisi nanti di
 * aplikasi Srikandi saat registrasi naskah dinas resmi (lihat
 * SuratTugasGeneratorService untuk detail keputusan ini).
 */
class SuratTugasResource extends Resource
{
    protected static ?string $model = SuratTugas::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Surat Tugas';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Surat Tugas';

    protected static ?string $pluralModelLabel = 'Surat Tugas';

    protected static ?string $slug = 'surat-tugas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kegiatan')
                ->columns(2)
                ->schema([
                    TextInput::make('judul')
                        ->label('Judul Kegiatan')
                        ->helperText('Mis. "Rapat Tata Kelola Tenaga Outsourcing pada OPD Kota Semarang"')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    DatePicker::make('hari_tanggal')
                        ->label('Hari, Tanggal')
                        ->native(false)
                        ->displayFormat('l, j F Y')
                        ->helperText('Disimpan sebagai teks "Senin, 5 Oktober 2026" di dokumen.')
                        ->required()
                        // Kolom DB-nya teks bebas (butuh gabungan nama hari +
                        // tanggal persis format naskah dinas, bukan tipe date
                        // murni). Konversi teks tersimpan -> "Y-m-d" untuk
                        // ditampilkan dilakukan lebih awal, di
                        // EditSuratTugas::mutateFormDataBeforeFill() — BUKAN
                        // di afterStateHydrated(), karena DatePicker Filament
                        // sendiri sudah mencoba meng-cast raw state dengan
                        // Carbon::parse() SEBELUM afterStateHydrated() sempat
                        // jalan; teks "Senin, 5 Oktober 2026" (prefix nama
                        // hari + bulan Indonesia) gagal di-parse Carbon biasa,
                        // sehingga state sudah keburu jadi null duluan — field
                        // tampak kosong di form Edit walau datanya ada di DB.
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                            ? null
                            : \Illuminate\Support\Carbon::parse($state)->locale('id')->translatedFormat('l, j F Y')),
                    TextInput::make('waktu')
                        ->label('Waktu')
                        ->helperText('Mis. "09.00 WIB s.d. selesai"')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('tempat')
                        ->label('Tempat')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('Tanggal Naskah')
                ->description('Opsional. Kalau diisi, tanggal ini dipakai di dokumen. Kalau dibiarkan kosong, placeholder "${tanggal_naskah}" tetap literal di dokumen hasil — diisi nanti di aplikasi Srikandi saat registrasi naskah dinas.')
                ->schema([
                    DatePicker::make('tanggal_naskah')
                        ->label('Tanggal Naskah')
                        ->native(false)
                        ->displayFormat('j F Y')
                        ->helperText('Disimpan sebagai "Semarang, 1 Oktober 2026" (format tempat terbit surat).')
                        // Nilai tersimpan berupa teks "Semarang, j F Y" —
                        // konversi ke "Y-m-d" untuk ditampilkan dilakukan di
                        // EditSuratTugas::mutateFormDataBeforeFill() (lihat
                        // catatan panjang pada field hari_tanggal di atas:
                        // afterStateHydrated() terlambat, DatePicker sudah
                        // keburu meng-null-kan teks yang gagal di-Carbon::parse()).
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                            ? null
                            : 'Semarang, '.\Illuminate\Support\Carbon::parse($state)->locale('id')->translatedFormat('j F Y')),
                ])
                ->collapsible(),

            Section::make('Dasar Hukum Tambahan')
                ->description('Dasar hukum baku (Perda APBD, dsb.) sudah otomatis disertakan — lihat Pengaturan Dasar Hukum. Isi di sini hanya bila surat ini butuh dasar hukum TAMBAHAN khusus, satu baris per dasar hukum.')
                ->schema([
                    Textarea::make('dasar_hukum_tambahan')
                        ->label('Dasar Hukum Tambahan (opsional)')
                        ->rows(4)
                        ->placeholder("Keputusan Kepala Dinas Nomor ... Tahun ... tentang ...;")
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('Pegawai yang Ditugaskan')
                ->schema([
                    Repeater::make('pegawai')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Select::make('nip')
                                ->label('Pegawai')
                                ->options(fn (): array => PegawaiProfil::query()
                                    ->orderBy('nama')
                                    ->get(['nip', 'nama'])
                                    ->mapWithKeys(fn (PegawaiProfil $p): array => [$p->nip => "{$p->nama} ({$p->nip})"])
                                    ->all())
                                ->searchable()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Select $component, ?string $state, callable $set): void {
                                    if (blank($state)) {
                                        return;
                                    }

                                    $p = PegawaiProfil::query()->find($state);

                                    if (! $p) {
                                        return;
                                    }

                                    $set('nama', $p->nama);
                                    $set('jabatan', $p->jabatan);
                                    $set('pangkat_golongan', trim(implode('/', array_filter([$p->pangkat, $p->golongan]))));
                                }),
                            TextInput::make('nama')->label('Nama (snapshot)')->required()->maxLength(255),
                            TextInput::make('jabatan')->label('Jabatan (snapshot)')->maxLength(255),
                            TextInput::make('pangkat_golongan')->label('Pangkat/Golongan (snapshot)')->maxLength(255),
                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah Pegawai')
                        ->reorderable()
                        ->orderColumn('urutan')
                        ->minItems(1)
                        ->required()
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kegiatan')
                ->columns(2)
                ->schema([
                    TextEntry::make('judul')->label('Judul Kegiatan')->columnSpanFull(),
                    TextEntry::make('hari_tanggal')->label('Hari, Tanggal'),
                    TextEntry::make('waktu'),
                    TextEntry::make('tempat')->columnSpanFull(),
                    TextEntry::make('tanggal_naskah')
                        ->label('Tanggal Naskah')
                        ->placeholder('${tanggal_naskah} (belum diisi, diisi di Srikandi)')
                        ->columnSpanFull(),
                    TextEntry::make('dasar_hukum_tambahan')
                        ->label('Dasar Hukum Tambahan')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
            Section::make('Pegawai yang Ditugaskan')
                ->schema([
                    RepeatableEntry::make('pegawai')
                        ->label('')
                        ->schema([
                            TextEntry::make('nama'),
                            TextEntry::make('nip'),
                            TextEntry::make('jabatan')->placeholder('—'),
                            TextEntry::make('pangkat_golongan')->placeholder('—'),
                        ])
                        ->columns(4),
                ]),
            Section::make('Dibuat')
                ->columns(2)
                ->schema([
                    TextEntry::make('dibuat_oleh_nama')->label('Dibuat Oleh')->placeholder('—'),
                    TextEntry::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')
                    ->label('Judul Kegiatan')
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('hari_tanggal')
                    ->label('Hari, Tanggal'),
                TextColumn::make('tempat')
                    ->wrap(),
                TextColumn::make('pegawai_count')
                    ->label('Jml Pegawai')
                    ->counts('pegawai')
                    ->badge(),
                TextColumn::make('dibuat_oleh_nama')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Tgl Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download_docx')
                    ->label('Word')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (SuratTugas $record): BinaryFileResponse {
                        $path = app(SuratTugasGeneratorService::class)->generateDocx($record);

                        $filename = \Illuminate\Support\Str::slug($record->judul).'.docx';

                        return response()
                            ->download($path, $filename, [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->deleteFileAfterSend(true);
                    }),
                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (SuratTugas $record): BinaryFileResponse {
                        $path = app(SuratTugasGeneratorService::class)->generatePdf($record);

                        $filename = \Illuminate\Support\Str::slug($record->judul).'.pdf';

                        return response()
                            ->download($path, $filename, ['Content-Type' => 'application/pdf'])
                            ->deleteFileAfterSend(true);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada Surat Tugas')
            ->emptyStateDescription('Klik "Buat Surat Tugas" untuk membuat yang pertama.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuratTugas::route('/'),
            'create' => Pages\CreateSuratTugas::route('/create'),
            'view' => Pages\ViewSuratTugas::route('/{record}'),
            'edit' => Pages\EditSuratTugas::route('/{record}/edit'),
        ];
    }
}

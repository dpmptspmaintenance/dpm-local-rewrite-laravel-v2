<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\Kepegawaian\SuratPengantar;
use App\Services\Kepegawaian\SuratPengantarGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Generator Surat Pengantar dari template .docx (resources/templates/
 * kepegawaian/surat-pengantar.docx) — satu tujuan + satu/lebih pegawai (blok
 * detail pegawai di-clone otomatis), plus paragraf pembuka/penutup, dasar
 * hukum, dan blok tanda tangan yang bisa diisi.
 *
 * tempat_tanggal dibiarkan literal bila kosong (diisi nanti di aplikasi
 * Srikandi saat registrasi naskah dinas).
 */
class SuratPengantarResource extends Resource
{
    protected static ?string $model = SuratPengantar::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Surat Pengantar';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Surat Pengantar';

    protected static ?string $pluralModelLabel = 'Surat Pengantar';

    protected static ?string $slug = 'surat-pengantar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Isi Surat')
                ->columns(2)
                ->schema([
                    TextInput::make('nomor_naskah')
                        ->label('Nomor Naskah')
                        ->helperText('Opsional. Kalau kosong, placeholder "${nomor_naskah}" dibiarkan literal (diisi di Srikandi).')
                        ->maxLength(255),
                    TextInput::make('perihal')
                        ->label('Perihal')
                        ->helperText('Mis. "Uji Kompetensi Pranata Komputer"')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('tujuan')
                        ->label('Ditujukan Kepada (Yth.)')
                        ->required()
                        ->maxLength(500),
                    TextInput::make('tujuan_sambungan')
                        ->label('Lanjutan Tujuan')
                        ->helperText('Baris lanjutan setelah "Yth.", mis. "dan Pelatihan Kota Semarang".')
                        ->maxLength(500),
                    TextInput::make('tujuan_kota')
                        ->label('Kota Tujuan (baris "di -" )')
                        ->helperText('Ditulis kapital berspasi, mis. "S E M A R A N G".')
                        ->maxLength(255),
                    TextInput::make('lampiran')
                        ->label('Lampiran')
                        ->default('-')
                        ->maxLength(255),
                    DatePicker::make('tempat_tanggal')
                        ->label('Tempat, Tanggal Naskah')
                        ->native(false)
                        ->displayFormat('j F Y')
                        ->helperText('Opsional. Disimpan sebagai "Semarang, j F Y". Kalau kosong, placeholder dibiarkan literal (diisi di Srikandi).')
                        ->suffixAction(
                            Action::make('clearTempatTanggal')
                                ->icon('heroicon-o-x-mark')
                                ->color('gray')
                                ->tooltip('Kosongkan tanggal naskah')
                                ->action(fn (Set $set) => $set('tempat_tanggal', null))
                        )
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                            ? null
                            : 'Semarang, '.Carbon::parse($state)->locale('id')->translatedFormat('j F Y')),
                ]),

            Section::make('Paragraf')
                ->description('Paragraf pembuka (sebelum daftar pegawai) dan penutup.')
                ->schema([
                    Textarea::make('paragraf_pembuka')
                        ->label('Paragraf Pembuka')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('paragraf_penutup')
                        ->label('Paragraf Penutup')
                        ->rows(3)
                        ->default('Demikian atas perhatian dan kerjasamanya kami sampaikan terima kasih.')
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make('Dasar Hukum Tambahan')
                ->description('Dasar hukum baku sudah otomatis disertakan. Isi di sini hanya bila surat ini butuh dasar hukum TAMBAHAN, satu baris per dasar hukum.')
                ->schema([
                    Textarea::make('dasar_hukum_tambahan')
                        ->label('Dasar Hukum Tambahan (opsional)')
                        ->rows(4)
                        ->placeholder('Surat Badan Pusat Statistik Nomor ... Tanggal ... Perihal ...')
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('Pegawai yang Diusulkan')
                ->description('Hanya boleh satu pegawai per surat.')
                ->columns(2)
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
                        ->afterStateUpdated(function (?string $state, callable $set): void {
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
                            $set('instansi', 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu Kota Semarang');
                        }),
                    TextInput::make('nama')->label('Nama')->required()->maxLength(255),
                    TextInput::make('jabatan')->label('Jabatan')->maxLength(255),
                    TextInput::make('pangkat_golongan')->label('Pangkat/Golongan')->maxLength(255),
                    TextInput::make('instansi')->label('Instansi')->maxLength(255)->columnSpanFull(),
                ]),

            Section::make('Tanda Tangan')
                ->description('Pilih penandatangan; baris jabatan & identitas terisi otomatis dan tetap bisa disesuaikan.')
                ->columns(2)
                ->schema([
                    Select::make('ttd_nip')
                        ->label('Penandatangan')
                        ->options(fn (): array => ['kepala-dinas' => 'Kepala Dinas (Drs. Puput Widhiatmoko H, MM)']
                            + PegawaiProfil::query()
                                ->orderBy('nama')
                                ->get(['nip', 'nama', 'jabatan'])
                                ->mapWithKeys(fn (PegawaiProfil $p): array => [$p->nip => "{$p->nama} — {$p->jabatan}"])
                                ->all())
                        ->searchable()
                        ->live()
                        ->default('kepala-dinas')
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            if ($state === 'kepala-dinas') {
                                $set('ttd_baris_1', 'Kepala Dinas Penanaman Modal dan');
                                $set('ttd_baris_2', 'Pelayanan Terpadu Satu Pintu');
                                $set('ttd_baris_3', 'Kota Semarang');
                                $set('ttd_jabatan', null);
                                $set('ttd_nama', 'Drs. Puput Widhiatmoko H, MM');
                                $set('ttd_nip_snapshot', '197404131993031002');

                                return;
                            }

                            $p = PegawaiProfil::query()->find($state);
                            if (! $p) {
                                return;
                            }

                            $set('ttd_baris_1', null);
                            $set('ttd_baris_2', null);
                            $set('ttd_baris_3', null);
                            $set('ttd_jabatan', $p->jabatan);
                            $set('ttd_nama', $p->nama);
                            $set('ttd_nip_snapshot', $p->nip);
                        }),
                    // NIP asli penandatangan disimpan di field ini (ttd_nip dipakai
                    // sebagai key Select); snapshot-nya dipindah saat create/edit.
                    TextInput::make('ttd_nip_snapshot')
                        ->label('NIP Penandatangan')
                        ->maxLength(100)
                        ->dehydrated(false),
                    TextInput::make('ttd_baris_1')->label('Baris Jabatan 1')->placeholder('An. Kepala Dinas ...')->maxLength(255),
                    TextInput::make('ttd_baris_2')->label('Baris Jabatan 2')->placeholder('Pelayanan Terpadu Satu Pintu')->maxLength(255),
                    TextInput::make('ttd_baris_3')->label('Baris Jabatan 3')->placeholder('Kota Semarang')->maxLength(255),
                    TextInput::make('ttd_jabatan')->label('Jabatan Penandatangan')->placeholder('Sekretaris')->maxLength(255),
                    TextInput::make('ttd_nama')->label('Nama Penandatangan')->placeholder('Soenarto, S. Kom, MM')->maxLength(255),
                ])
                ->collapsible(),

            Section::make('Tembusan')
                ->description('Opsional. Satu baris per tembusan, mis. "Sekda Kota Semarang (sebagai laporan);".')
                ->schema([
                    Textarea::make('tembusan_snapshot')
                        ->label('Tembusan')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Isi Surat')
                ->columns(2)
                ->schema([
                    TextEntry::make('nomor_naskah')->label('Nomor Naskah')->placeholder('${nomor_naskah} (belum diisi)'),
                    TextEntry::make('perihal')->label('Perihal')->columnSpanFull(),
                    TextEntry::make('tujuan')->label('Ditujukan Kepada'),
                    TextEntry::make('tujuan_sambungan')->label('Lanjutan Tujuan')->placeholder('—'),
                    TextEntry::make('tujuan_kota')->label('Kota Tujuan')->placeholder('—'),
                    TextEntry::make('lampiran')->label('Lampiran')->placeholder('—'),
                    TextEntry::make('tempat_tanggal')->label('Tempat, Tanggal')->placeholder('${tempat_tanggal} (belum diisi)'),
                    TextEntry::make('paragraf_pembuka')->label('Paragraf Pembuka')->columnSpanFull(),
                    TextEntry::make('paragraf_penutup')->label('Paragraf Penutup')->columnSpanFull(),
                    TextEntry::make('dasar_hukum_tambahan')->label('Dasar Hukum Tambahan')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Pegawai yang Diusulkan')
                ->schema([
                    RepeatableEntry::make('pegawai')
                        ->label('')
                        ->schema([
                            TextEntry::make('nama'),
                            TextEntry::make('nip'),
                            TextEntry::make('jabatan')->placeholder('—'),
                            TextEntry::make('pangkat_golongan')->placeholder('—'),
                            TextEntry::make('instansi')->placeholder('—'),
                        ])
                        ->columns(3),
                ]),
            Section::make('Tanda Tangan & Tembusan')
                ->columns(2)
                ->schema([
                    TextEntry::make('ttd_baris_1')->label('Baris 1')->placeholder('—'),
                    TextEntry::make('ttd_baris_2')->label('Baris 2')->placeholder('—'),
                    TextEntry::make('ttd_baris_3')->label('Baris 3')->placeholder('—'),
                    TextEntry::make('ttd_jabatan')->label('Jabatan')->placeholder('—'),
                    TextEntry::make('ttd_nama')->label('Nama')->placeholder('—'),
                    TextEntry::make('ttd_nip')->label('NIP')->placeholder('—'),
                    TextEntry::make('tembusan_snapshot')->label('Tembusan')->placeholder('—')->columnSpanFull(),
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
                TextColumn::make('perihal')
                    ->label('Perihal')
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('tujuan')
                    ->label('Ditujukan Kepada')
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
                    ->action(function (SuratPengantar $record): BinaryFileResponse {
                        $path = app(SuratPengantarGeneratorService::class)->generateDocx($record);

                        return response()
                            ->download($path, Str::slug($record->perihal).'.docx', [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->deleteFileAfterSend(true);
                    }),
                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (SuratPengantar $record): BinaryFileResponse {
                        $path = app(SuratPengantarGeneratorService::class)->generatePdf($record);

                        return response()
                            ->download($path, Str::slug($record->perihal).'.pdf', ['Content-Type' => 'application/pdf'])
                            ->deleteFileAfterSend(true);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada Surat Pengantar')
            ->emptyStateDescription('Klik "Buat Surat Pengantar" untuk membuat yang pertama.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuratPengantar::route('/'),
            'create' => Pages\CreateSuratPengantar::route('/create'),
            'view' => Pages\ViewSuratPengantar::route('/{record}'),
            'edit' => Pages\EditSuratPengantar::route('/{record}/edit'),
        ];
    }
}

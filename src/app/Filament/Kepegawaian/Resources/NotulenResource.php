<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\NotulenResource\Pages;
use App\Models\Kepegawaian\Notulen;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\User;
use App\Services\Kepegawaian\NotulenGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotulenResource extends Resource
{
    protected static ?string $model = Notulen::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Notulen';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Notulen';

    protected static ?string $pluralModelLabel = 'Notulen';

    protected static ?string $slug = 'notulen';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kegiatan / Rapat')
                ->columns(2)
                ->schema([
                    TextInput::make('judul')
                        ->label('Judul Kegiatan / Acara')
                        ->placeholder('Mis. "INVESTMENT DAY PENDAMPINGAN PENYAMPAIAN LAPORAN LKPM TAHUN 2025"')
                        ->helperText('Akan dicetak di bawah kata "NOTULEN" pada dokumen.')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    DatePicker::make('hari_tanggal')
                        ->label('Hari, Tanggal Pelaksanaan')
                        ->native(false)
                        ->displayFormat('l, j F Y')
                        ->helperText('Disimpan sebagai teks "Kamis, 3 Juli 2025" di dokumen.')
                        ->required()
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                            ? null
                            : \Illuminate\Support\Carbon::parse($state)->locale('id')->translatedFormat('l, j F Y')),

                    Select::make('waktu_mulai')
                        ->label('Waktu Mulai')
                        ->required()
                        ->options(self::opsiJam())
                        ->helperText('Jam mulai kegiatan (format 24 jam).'),

                    Select::make('waktu_selesai')
                        ->label('Waktu Selesai')
                        ->required()
                        ->default('selesai')
                        ->options(fn (): array => ['selesai' => 'Selesai'] + self::opsiJam())
                        ->helperText('Pilih "Selesai" atau jam berapa kegiatan berakhir.'),

                    Textarea::make('tempat')
                        ->label('Tempat Pelaksanaan')
                        ->placeholder("Ruang Osproom Lt.2 PT Kawasan Industri Wijayakusuma (KIW)\nJl. Pantura Semarang – Kendal Km.12, Kota Semarang")
                        ->helperText('Dapat diisi beberapa baris alamat/gedung.')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Dasar Hukum / Undangan')
                ->description('Opsional. Isi satu baris per dasar hukum atau nomor surat undangan.')
                ->schema([
                    Textarea::make('dasar')
                        ->label('Dasar Pelaksanaan (opsional)')
                        ->placeholder("Undangan nomor B/62/500.16.6/VI/2025\nPeraturan Daerah Nomor 8 Tahun 2024 tentang Anggaran...\nProgram Kerja DPMPTSP Kota Semarang")
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('Narasumber')
                ->description('Opsional. Isi satu baris per narasumber.')
                ->schema([
                    Textarea::make('narasumber')
                        ->label('Daftar Narasumber (opsional)')
                        ->placeholder("1. DPMPTSP KOTA SEMARANG Ibu DIAH SUPARTININGTIAS, SH, M.Kn\n2. DPMPTSP PROVINSI JAWA TENGAH Ibu NATALIA, S.E.\n3. Direktur PT. KIW Bapak AHMAD FAUZI NU, S.E., M.Business")
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('Peserta')
                ->columns(1)
                ->schema([
                    Textarea::make('peserta_deskripsi')
                        ->label('Deskripsi / Pengantar Peserta (opsional)')
                        ->placeholder('Mis. "Peserta acara dihadiri oleh perusahaan-perusahaan di wilayah industri Kota Semarang sebanyak 37 pelaku usaha."')
                        ->rows(2),

                    Textarea::make('peserta_daftar')
                        ->label('Daftar Peserta / Instansi (opsional)')
                        ->placeholder("PT. WINGTECH TECH INDONESIA\nPT. LIANHE TRADING INDONESIA\nPT. SHARP TECHNOLOGY")
                        ->helperText('Satu baris per peserta/perusahaan.')
                        ->rows(4),
                ])
                ->collapsible(),

            Section::make('Hasil Acara / Pembahasan')
                ->schema([
                    RichEditor::make('hasil_acara')
                        ->label('Isi Pembahasan / Hasil Acara')
                        ->placeholder('Uraian jalannya kegiatan, poin-poin pembahasan, catatan kesepakatan, dsb.')
                        ->helperText('Gunakan format tebal, miring, daftar poin/angka untuk menyusun risalah rapat.')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'blockquote',
                            'undo',
                            'redo',
                        ])
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make('Penutup & Tanggal Naskah')
                ->columns(2)
                ->schema([
                    TextInput::make('penutup')
                        ->label('Kalimat Penutup')
                        ->default('Demikian Notulen ini dibuat untuk menjadikan periksa.')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    DatePicker::make('tanggal_naskah')
                        ->label('Tanggal Notulen')
                        ->native(false)
                        ->displayFormat('j F Y')
                        ->default(now())
                        ->helperText('Disimpan sebagai "Semarang, 3 Juli 2025" di atas tanda tangan.')
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                            ? null
                            : 'Semarang, '.\Illuminate\Support\Carbon::parse($state)->locale('id')->translatedFormat('j F Y')),
                ]),

            Section::make('Penandatangan')
                ->description('Pilih pejabat/pegawai dari database Users. Nama, NIP, dan Jabatan akan terisi otomatis dan dapat disesuaikan jika diperlukan.')
                ->columns(2)
                ->schema([
                    // Kolom Kiri: Mengetahui (Atasan)
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Select::make('atasan_user_id')
                                ->label('Pilih Atasan (Mengetahui)')
                                ->options(fn (): array => User::query()
                                    ->orderBy('nama')
                                    ->get(['id', 'nama', 'name', 'nip'])
                                    ->mapWithKeys(fn (User $u): array => [$u->id => ($u->nama ?: $u->name).($u->nip ? " ({$u->nip})" : '')])
                                    ->all())
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (?int $state, Set $set): void {
                                    if (! $state) {
                                        return;
                                    }
                                    $u = User::query()->find($state);
                                    if (! $u) {
                                        return;
                                    }
                                    $set('atasan_nama', $u->nama ?: $u->name);
                                    $set('atasan_nip', $u->nip);
                                    $profil = $u->nip ? PegawaiProfil::query()->where('nip', $u->nip)->first() : null;
                                    $jabatan = $profil?->jabatan ?: ($u->status_jabatan ?: '');
                                    $set('atasan_jabatan', $jabatan ? "{$jabatan}\nDPMPTSP Kota Semarang" : 'DPMPTSP Kota Semarang');
                                }),

                            TextInput::make('atasan_jabatan')
                                ->label('Jabatan Atasan')
                                ->helperText('Mis. "Kabid. Penanaman Modal\nDPMPTSP Kota Semarang"')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('atasan_nama')
                                ->label('Nama Lengkap Atasan')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('atasan_nip')
                                ->label('NIP Atasan (opsional)')
                                ->maxLength(100),
                        ]),

                    // Kolom Kanan: Yang Melaporkan
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Select::make('pelapor_user_id')
                                ->label('Pilih Pegawai (Yang Melaporkan)')
                                ->options(fn (): array => User::query()
                                    ->orderBy('nama')
                                    ->get(['id', 'nama', 'name', 'nip'])
                                    ->mapWithKeys(fn (User $u): array => [$u->id => ($u->nama ?: $u->name).($u->nip ? " ({$u->nip})" : '')])
                                    ->all())
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (?int $state, Set $set): void {
                                    if (! $state) {
                                        return;
                                    }
                                    $u = User::query()->find($state);
                                    if (! $u) {
                                        return;
                                    }
                                    $set('pelapor_nama', $u->nama ?: $u->name);
                                    $set('pelapor_nip', $u->nip);
                                    $profil = $u->nip ? PegawaiProfil::query()->where('nip', $u->nip)->first() : null;
                                    $jabatan = $profil?->jabatan ?: ($u->status_jabatan ?: '');
                                    $set('pelapor_jabatan', $jabatan ? "{$jabatan}\nDPMPTSP Kota Semarang" : 'DPMPTSP Kota Semarang');
                                }),

                            TextInput::make('pelapor_jabatan')
                                ->label('Jabatan Yang Melaporkan')
                                ->helperText('Mis. "Subkoor Pengendalian PM\nDPMPTSP Kota Semarang"')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('pelapor_nama')
                                ->label('Nama Lengkap Pelapor')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('pelapor_nip')
                                ->label('NIP Pelapor (opsional)')
                                ->maxLength(100),
                        ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kegiatan / Rapat')
                ->columns(2)
                ->schema([
                    TextEntry::make('judul')->label('Judul Kegiatan')->columnSpanFull(),
                    TextEntry::make('hari_tanggal')->label('Hari, Tanggal'),
                    TextEntry::make('waktu')->label('Waktu'),
                    TextEntry::make('tempat')->label('Tempat')->columnSpanFull(),
                ]),

            Section::make('Dasar, Narasumber, dan Peserta')
                ->columns(2)
                ->schema([
                    TextEntry::make('dasar')->label('Dasar Pelaksanaan')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('narasumber')->label('Narasumber')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('peserta_deskripsi')->label('Deskripsi Peserta')->placeholder('—'),
                    TextEntry::make('peserta_daftar')->label('Daftar Peserta')->placeholder('—'),
                ]),

            Section::make('Hasil Acara / Pembahasan')
                ->schema([
                    TextEntry::make('hasil_acara')->label('Hasil Acara')->html()->columnSpanFull(),
                    TextEntry::make('penutup')->label('Penutup')->columnSpanFull(),
                    TextEntry::make('tanggal_naskah')->label('Tanggal Naskah'),
                ]),

            Section::make('Penandatangan')
                ->columns(2)
                ->schema([
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('atasan_jabatan')->label('Jabatan Atasan (Mengetahui)'),
                            TextEntry::make('atasan_nama')->label('Nama Atasan'),
                            TextEntry::make('atasan_nip')->label('NIP Atasan')->placeholder('—'),
                        ]),
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('pelapor_jabatan')->label('Jabatan Yang Melaporkan'),
                            TextEntry::make('pelapor_nama')->label('Nama Pelapor'),
                            TextEntry::make('pelapor_nip')->label('NIP Pelapor')->placeholder('—'),
                        ]),
                ]),

            Section::make('Informasi Pembuat')
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
                    ->label('Judul Kegiatan / Acara')
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),

                TextColumn::make('hari_tanggal')
                    ->label('Hari, Tanggal'),

                TextColumn::make('tempat')
                    ->label('Tempat')
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('pelapor_nama')
                    ->label('Yang Melaporkan')
                    ->searchable(),

                TextColumn::make('atasan_nama')
                    ->label('Mengetahui')
                    ->searchable(),

                TextColumn::make('dibuat_oleh_nama')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('download_docx')
                    ->label('Word')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->tooltip('Unduh berkas Word (.docx)')
                    ->action(function (Notulen $record): BinaryFileResponse {
                        $path = app(NotulenGeneratorService::class)->generateDocx($record);

                        return response()
                            ->download($path, Str::slug($record->judul).'.docx', [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->deleteFileAfterSend(true);
                    }),

                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->tooltip('Unduh berkas PDF (.pdf)')
                    ->action(function (Notulen $record): BinaryFileResponse {
                        $path = app(NotulenGeneratorService::class)->generatePdf($record);

                        return response()
                            ->download($path, Str::slug($record->judul).'.pdf', ['Content-Type' => 'application/pdf'])
                            ->deleteFileAfterSend(true);
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotulen::route('/'),
            'create' => Pages\CreateNotulen::route('/create'),
            'view' => Pages\ViewNotulen::route('/{record}'),
            'edit' => Pages\EditNotulen::route('/{record}/edit'),
        ];
    }

    public static function composeWaktu(?string $mulai, ?string $selesai): ?string
    {
        if (blank($mulai)) {
            return null;
        }

        $mulaiTeks = self::jamKeTitik($mulai);
        if (blank($selesai) || $selesai === 'selesai') {
            return "{$mulaiTeks} WIB – Selesai";
        }

        return "{$mulaiTeks} WIB – ".self::jamKeTitik($selesai).' WIB';
    }

    public static function parseWaktu(?string $waktu): array
    {
        $waktu = trim((string) $waktu);
        if ($waktu === '') {
            return [null, 'selesai'];
        }

        preg_match('/(\d{1,2})[.:](\d{2})/', $waktu, $m);
        $mulai = isset($m[1]) ? str_pad($m[1], 2, '0', STR_PAD_LEFT).'.'.$m[2] : null;

        if (stripos($waktu, 'selesai') !== false) {
            return [$mulai, 'selesai'];
        }

        preg_match_all('/(\d{1,2})[.:](\d{2})/', $waktu, $all);
        if (isset($all[1][1])) {
            return [$mulai, str_pad($all[1][1], 2, '0', STR_PAD_LEFT).'.'.$all[2][1]];
        }

        return [$mulai, 'selesai'];
    }

    private static function jamKeTitik(string $jam): string
    {
        return str_replace(':', '.', $jam);
    }

    public static function opsiJam(): array
    {
        $opsi = [];
        for ($h = 6; $h <= 22; $h++) {
            foreach ([0, 15, 30, 45] as $m) {
                $val = sprintf('%02d.%02d', $h, $m);
                $opsi[$val] = sprintf('%02d:%02d WIB', $h, $m);
            }
        }

        return $opsi;
    }
}

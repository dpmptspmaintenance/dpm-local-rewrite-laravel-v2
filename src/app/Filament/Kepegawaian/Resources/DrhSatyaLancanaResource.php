<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource\Pages;
use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\DrhSatyaLancanaDokumen;
use App\Models\Kepegawaian\PegawaiPenghargaan;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Services\Kepegawaian\DrhSatyaLancanaGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * DRH (Daftar Riwayat Hidup) usulan Tanda Kehormatan Satya Lancana Karya
 * Satya — generator Word/PDF dari template .docx (resources/templates/
 * kepegawaian/drh-satya-lancana.docx), pola sama SuratTugasResource.
 *
 * Beda dengan Surat Tugas: dokumen ini satu pegawai (bukan banyak), dan
 * sebagian field (nama/NIP/TTL/golongan/jabatan) di-snapshot dari
 * pegawai_profil saat dibuat — tapi staf tetap bisa mengoreksi tiap field.
 * Field yang tak ada di pegawai_profil (nomor/tanggal SK CPNS, nomor/tanggal
 * SK jabatan terakhir) diisi manual per-DRH.
 */
class DrhSatyaLancanaResource extends Resource
{
    protected static ?string $model = DrhSatyaLancana::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'DRH Satya Lancana';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'DRH Satya Lancana';

    protected static ?string $pluralModelLabel = 'DRH Satya Lancana';

    protected static ?string $slug = 'drh-satya-lancana';

    /**
     * Jenjang pendidikan ringkas (mis. "S-2 MAGISTER MANAJEMEN" → "S2") —
     * mengikuti gaya contoh DRH asli ("Pendidikan Terakhir: S2"). Ambil token
     * pertama lalu normalisasi "S-1"→"S1" dsb.; sisanya dikembalikan apa
     * adanya.
     */
    public static function jenjangPendidikan(?string $pendidikan): string
    {
        $text = trim((string) $pendidikan);

        if ($text === '') {
            return '-';
        }

        $first = explode(' ', $text)[0];

        return match (true) {
            (bool) preg_match('/^S-?([123])$/i', $first) => 'S'.preg_replace('/\D/', '', $first),
            (bool) preg_match('/^D-?(III|IV|II|I)$/i', $first) => 'D-'.strtoupper(preg_replace('/^D-?/i', '', $first)),
            default => strtoupper($first),
        };
    }

    /**
     * Ringkas riwayat penghargaan tercatat jadi satu teks multi-baris untuk
     * field "Tanda Kehormatan yang sudah dimiliki". Kosong → "-" (sama
     * seperti contoh DRH asli). Format: "Nama Penghargaan (Nomor SK, tanggal)".
     */
    public static function ringkasTandaKehormatan(string $nip): string
    {
        $rows = PegawaiPenghargaan::query()
            ->where('nip', $nip)
            ->orderByRaw('tanggal_sk_penghargaan IS NULL')
            ->orderBy('tanggal_sk_penghargaan')
            ->get();

        if ($rows->isEmpty()) {
            return '-';
        }

        $lines = [];

        foreach ($rows as $row) {
            $nama = $row->nama_penghargaan ?: $row->jenis_penghargaan;
            $detail = array_filter([
                $row->nomor_sk_penghargaan,
                $row->tanggal_sk_penghargaan?->format('d-m-Y'),
            ]);

            $lines[] = $detail === []
                ? $nama
                : $nama.' ('.implode(', ', $detail).')';
        }

        return implode("\n", $lines);
    }

    /**
     * Format tanggal Indonesia ("19 November 1981") dari nilai apa pun —
     * Carbon (kolom di-cast date) atau string nullable. Kosong/gagal → ''.
     */
    public static function formatTanggalIndo(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->locale('id')->translatedFormat('j F Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Data snapshot awal saat pegawai dipilih di form — dipakai
     * afterStateUpdated memanggil $set() untuk tiap field.
     *
     * @return array<string, string>
     */
    public static function fillFromProfil(string $nip): array
    {
        $p = PegawaiProfil::query()->find($nip);

        if (! $p) {
            return [];
        }

        return [
            'nip' => $nip,
            'nama' => (string) $p->nama,
            'nip_lama' => (string) ($p->nip_lama ?: ''),
            'pendidikan_terakhir' => self::jenjangPendidikan($p->pendidikan),
            'pangkat_golongan' => trim(implode(' ', array_filter([$p->pangkat, $p->golongan]))),
            'tmt_pangkat_golongan' => self::formatTanggalIndo($p->tmt_golongan),
            'jabatan_terakhir' => (string) ($p->jabatan ?? ''),
            'jenis_kelamin' => (string) ($p->gender ?? ''),
            'tempat_lahir' => (string) ($p->tempat_lahir ?? ''),
            'tanggal_lahir' => self::formatTanggalIndo($p->tanggal_lahir),
            'tanda_kehormatan_dimiliki' => self::ringkasTandaKehormatan($nip),
            ...self::skFromRiwayat($nip),
            'ttd_kiri_nama' => DrhSatyaLancana::TTD_KIRI_NAMA_DEFAULT,
            'ttd_kiri_nip' => DrhSatyaLancana::TTD_KIRI_NIP_DEFAULT,
        ];
    }

    /**
     * SK CPNS & SK Jabatan terakhir dari riwayat impor SISDM:
     *  - SK CPNS  ← pegawai_riwayat_cpns (nomor_sk/tanggal_sk/tmt_sk)
     *  - SK Jabatan ← pegawai_riwayat_jabatan baris dengan tmt_sk_jabatan
     *    PALING BARU (nomor_sk_jabatan/tanggal_sk_jabatan/tmt_sk_jabatan)
     * Semua tetap bisa dikoreksi manual di form (nilai hanya di-`$set` saat
     * kosong di afterStateUpdated). Tanggal dikonversi ke format Indonesia.
     *
     * @return array<string, string>
     */
    public static function skFromRiwayat(string $nip): array
    {
        $out = [];

        $cpns = \App\Models\Kepegawaian\PegawaiRiwayatCpns::query()->where('nip', $nip)->first();
        if ($cpns) {
            $out['sk_cpns_nomor'] = (string) ($cpns->nomor_sk ?? '');
            $out['sk_cpns_tanggal'] = self::formatTanggalIndo($cpns->tanggal_sk);
            $out['sk_cpns_tmt'] = self::formatTanggalIndo($cpns->tmt_sk);
        }

        $jabatan = \App\Models\Kepegawaian\PegawaiRiwayatJabatan::query()
            ->where('nip', $nip)
            ->orderByDesc('tmt_sk_jabatan')
            ->orderByDesc('no_urut')
            ->first();
        if ($jabatan) {
            $out['sk_jabatan_nomor'] = (string) ($jabatan->nomor_sk_jabatan ?? '');
            $out['sk_jabatan_tanggal'] = self::formatTanggalIndo($jabatan->tanggal_sk_jabatan);
            $out['sk_jabatan_tmt'] = self::formatTanggalIndo($jabatan->tmt_sk_jabatan);
        }

        return $out;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pegawai')
                ->description('Pilih pegawai — field identitas di bawah otomatis terisi dari data pegawai, tapi tetap bisa dikoreksi manual.')
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
                        ->required()
                        ->live()
                        ->disabledOn('edit')
                        ->afterStateUpdated(function (Select $component, ?string $state, callable $set): void {
                            if (blank($state)) {
                                return;
                            }

                            foreach (self::fillFromProfil($state) as $key => $value) {
                                if (filled($value)) {
                                    $set($key, $value);
                                }
                            }
                        }),
                    TextInput::make('nama')->label('Nama Lengkap')->required()->maxLength(255),
                    TextInput::make('nip_lama')->label('NIP Lama')->maxLength(100),
                    TextInput::make('pendidikan_terakhir')->label('Pendidikan Terakhir')->maxLength(255),
                    TextInput::make('pangkat_golongan')->label('Pangkat/Golongan Ruang Terakhir')->maxLength(255),
                    TextInput::make('tmt_pangkat_golongan')->label('TMT Pangkat/Golongan')->maxLength(255)->placeholder('Mis. 1 Juni 2024'),
                    TextInput::make('jabatan_terakhir')->label('Jabatan Terakhir')->maxLength(255),
                    TextInput::make('jenis_kelamin')->label('Jenis Kelamin')->maxLength(50),
                    TextInput::make('tempat_lahir')->label('Tempat Lahir')->maxLength(150),
                    TextInput::make('tanggal_lahir')->label('Tanggal Lahir')->maxLength(150)->placeholder('Mis. 19 November 1981'),
                ]),

            Section::make('Status Usulan')
                ->description('Tahap pengajuan DRH ini. Default "Draft" saat baru dibuat.')
                ->columns(1)
                ->schema([
                    ToggleButtons::make('status')
                        ->label('Status Usulan')
                        ->options(DrhSatyaLancana::STATUSES)
                        ->colors(DrhSatyaLancana::STATUS_COLORS)
                        ->inline()
                        ->default(DrhSatyaLancana::STATUS_DRAFT)
                        ->required(),
                ]),

            Section::make('SK CPNS & Jabatan')
                ->description('Tidak tersedia di data pegawai — diisi manual per-DRH.')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextInput::make('sk_cpns_nomor')->label('SK CPNS — Nomor')->maxLength(255),
                    TextInput::make('sk_cpns_tanggal')->label('SK CPNS — Tanggal')->maxLength(150)->placeholder('Mis. 12 Maret 2001'),
                    TextInput::make('sk_cpns_tmt')->label('SK CPNS — TMT')->maxLength(150)->placeholder('Mis. 1 Desember 2000'),
                    TextInput::make('sk_jabatan_nomor')->label('SK Jabatan — Nomor')->maxLength(255),
                    TextInput::make('sk_jabatan_tanggal')->label('SK Jabatan — Tanggal')->maxLength(150)->placeholder('Mis. 6 Maret 2024'),
                    TextInput::make('sk_jabatan_tmt')->label('SK Jabatan — TMT')->maxLength(150)->placeholder('Mis. 6 Maret 2024'),
                ]),

            Section::make('Pernyataan & Penetapan')
                ->columns(2)
                ->collapsible()
                ->schema([
                    Textarea::make('tanda_kehormatan_dimiliki')
                        ->label('Tanda Kehormatan yang sudah dimiliki')
                        ->helperText('Otomatis dari riwayat penghargaan pegawai (impor SISDM + tambahan manual). Bisa dikoreksi di sini.')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('hukuman_disiplin')
                        ->label('Hukuman Disiplin')
                        ->rows(2)
                        ->default(DrhSatyaLancana::HUKUMAN_DISIPLIN_DEFAULT)
                        ->columnSpanFull(),
                    Textarea::make('cltn')
                        ->label('CLTN')
                        ->rows(2)
                        ->default(DrhSatyaLancana::CLTN_DEFAULT)
                        ->columnSpanFull(),
                    TextInput::make('ditetapkan_di')->label('Ditetapkan di')->maxLength(150)->default('SEMARANG'),
                    DatePicker::make('tanggal_ditetapkan')
                        ->label('Tanggal Ditetapkan')
                        ->native(false)
                        ->displayFormat('j F Y')
                        ->default(now()->format('Y-m-d'))
                        ->helperText('Disimpan sebagai "30 September 2026" di dokumen. Default tanggal hari ini.')
                        // Nilai tersimpan berupa teks "j F Y" — konversi balik ke
                        // "Y-m-d" untuk ditampilkan dilakukan di
                        // EditDrhSatyaLancana::mutateFormDataBeforeFill() (pola
                        // sama Surat Tugas: DatePicker sudah meng-cast raw state
                        // sebelum afterStateHydrated sempat jalan).
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                            ? null
                            : \Illuminate\Support\Carbon::parse($state)->locale('id')->translatedFormat('j F Y')),
                    Select::make('atasan_nip')
                        ->label('Atasan Langsung (penandatangan kiri)')
                        ->options(fn (): array => PegawaiProfil::query()
                            ->orderBy('nama')
                            ->get(['nip', 'nama'])
                            ->mapWithKeys(fn (PegawaiProfil $p): array => [$p->nip => "{$p->nama} ({$p->nip})"])
                            ->all())
                        ->searchable()
                        ->live()
                        ->helperText('Pilih pegawai sebagai atasan (otomatis isi nama & NIP di bawah). Kalau atasan di luar daftar pegawai, kosongkan pilihan ini dan ketik nama/NIP manual.')
                        ->afterStateUpdated(function (Select $component, ?string $state, callable $set): void {
                            if (blank($state)) {
                                return;
                            }

                            $p = PegawaiProfil::query()->find($state);
                            if (! $p) {
                                return;
                            }

                            $set('ttd_kiri_nama', $p->nama);
                            $set('ttd_kiri_nip', $p->nip);
                            if (filled($p->jabatan)) {
                                $set('ttd_kiri_jabatan', $p->jabatan);
                            }
                        }),
                    Textarea::make('ttd_kiri_jabatan')
                        ->label('Jabatan Atasan (blok TTD kiri)')
                        ->helperText('Satu baris = satu baris di dokumen. Default: Kepala Dinas / Penanaman Modal Dan Pelayanan / Terpadu Satu Pintu.')
                        ->rows(3)
                        ->default(DrhSatyaLancana::TTD_KIRI_JABATAN_DEFAULT)
                        ->columnSpanFull(),
                    TextInput::make('ttd_kiri_nama')
                        ->label('Penandatangan Kiri — Nama')
                        ->helperText('Terisi otomatis saat pilih atasan. Bisa dikoreksi manual (mis. atasan luar daftar pegawai).')
                        ->maxLength(255)
                        ->default(DrhSatyaLancana::TTD_KIRI_NAMA_DEFAULT),
                    TextInput::make('ttd_kiri_nip')
                        ->label('Penandatangan Kiri — NIP')
                        ->maxLength(100)
                        ->default(DrhSatyaLancana::TTD_KIRI_NIP_DEFAULT),
                ]),

            Section::make('Berkas Lampiran (opsional)')
                ->description('Unggah berkas lampiran usulan. Semua opsional — setelah tersimpan bisa digabung jadi 1 PDF lewat tombol "Download Berkas Gabungan". PDF atau gambar (JPG/PNG).')
                ->columns(1)
                ->collapsible()
                ->schema([
                    ...collect(DrhSatyaLancanaDokumen::JENIS_LABELS)->map(
                        fn (string $label, string $jenis): FileUpload => FileUpload::make('berkas_'.$jenis)
                            ->label(strtoupper($jenis).'. '.$label)
                            ->storeFiles(false)
                            ->dehydrated(false)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(config('arsip.max_upload_kb'))
                            ->helperText(fn (?DrhSatyaLancana $record): string => $record
                                ? self::statusBerkas($record, $jenis)
                                : 'Belum diunggah.'),
                    )->all(),
                ]),
        ]);
    }

    /** Teks status berkas lampiran (centang sudah / belum) untuk helperText. */
    public static function statusBerkas(DrhSatyaLancana $drh, string $jenis): string
    {
        $doc = $drh->dokumen()->where('jenis', $jenis)->first();

        if (! $doc) {
            return '✗ BELUM diunggah.';
        }

        $service = app(\App\Services\Kepegawaian\DrhSatyaLancanaDocumentService::class);

        if (! is_file($service->absolutePath($doc))) {
            return '✗ BELUM diunggah (berkas fisik tidak ditemukan).';
        }

        return '✓ SUDAH diunggah: '.($doc->original_filename ?? basename((string) $doc->path));
    }

    /**
     * Aksi unduh satu berkas lampiran (SK CPNS, SK Jabatan, dll.) dari disk
     * lokal lewat suffixAction pada TextEntry. Selalu dikembalikan agar
     * Filament tak error; tombol hanya muncul (visible) bila berkas ada dan
     * fisiknya tersedia.
     */
    public static function unduhLampiranAction(DrhSatyaLancana $drh, string $jenis): Action
    {
        $service = app(\App\Services\Kepegawaian\DrhSatyaLancanaDocumentService::class);
        $doc = $drh->dokumen()->where('jenis', $jenis)->first();
        $exists = $doc !== null && is_file($service->absolutePath($doc));

        return Action::make('unduh_lampiran_'.$jenis)
            ->label('Unduh')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->tooltip('Unduh lampiran '.strtoupper($jenis))
            ->visible($exists)
            ->action(function () use ($service, $doc): BinaryFileResponse {
                $path = $service->absolutePath($doc);
                $filename = $doc->original_filename ?: (strtoupper($doc->jenis).'.'.pathinfo($path, PATHINFO_EXTENSION));

                return response()->download($path, $filename);
            });
    }

    /**
     * Simpan berkas lampiran dari raw state form (FileUpload `berkas_<jenis>`
     * dengan storeFiles(false) → object TemporaryUploadedFile). Dipakai
     * CreateDrhSatyaLancana & EditDrhSatyaLancana (afterCreate/afterSave).
     * Satu berkas per jenis; upload ulang mengganti.
     */
    public static function simpanBerkas(DrhSatyaLancana $drh, array $rawState): void
    {
        $service = app(\App\Services\Kepegawaian\DrhSatyaLancanaDocumentService::class);

        foreach (array_keys(DrhSatyaLancanaDokumen::JENIS_LABELS) as $jenis) {
            $file = $rawState['berkas_'.$jenis] ?? null;

            if (blank($file)) {
                continue;
            }

            // FileUpload multiple=false → object tunggal; toleran kalau array.
            if (is_array($file)) {
                $file = reset($file);
            }

            if (! $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                continue;
            }

            $service->store(
                $drh,
                $jenis,
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $file->getMimeType() ?: null,
            );
        }
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status Usulan')
                ->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => DrhSatyaLancana::STATUSES[$state] ?? $state)
                        ->color(fn (string $state): string => DrhSatyaLancana::STATUS_COLORS[$state] ?? 'gray'),
                ]),
            Section::make('Identitas')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama')->label('Nama Lengkap')->weight('bold'),
                    TextEntry::make('nip')->label('NIP'),
                    TextEntry::make('nip_lama')->label('NIP Lama')->placeholder('—'),
                    TextEntry::make('jenis_kelamin')->label('Jenis Kelamin')->placeholder('—'),
                    TextEntry::make('tempat_lahir')->label('Tempat Lahir')->placeholder('—'),
                    TextEntry::make('tanggal_lahir')->label('Tanggal Lahir')->placeholder('—'),
                    TextEntry::make('pendidikan_terakhir')->label('Pendidikan Terakhir')->placeholder('—'),
                    TextEntry::make('pangkat_golongan')->label('Pangkat/Golongan')->placeholder('—'),
                    TextEntry::make('jabatan_terakhir')->label('Jabatan Terakhir')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('SK CPNS & Jabatan')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('sk_cpns_nomor')->label('SK CPNS — Nomor')->placeholder('—'),
                    TextEntry::make('sk_cpns_tanggal')->label('SK CPNS — Tanggal')->placeholder('—'),
                    TextEntry::make('sk_cpns_tmt')->label('SK CPNS — TMT')->placeholder('—'),
                    TextEntry::make('sk_jabatan_nomor')->label('SK Jabatan — Nomor')->placeholder('—'),
                    TextEntry::make('sk_jabatan_tanggal')->label('SK Jabatan — Tanggal')->placeholder('—'),
                    TextEntry::make('sk_jabatan_tmt')->label('SK Jabatan — TMT')->placeholder('—'),
                ]),
            Section::make('Pernyataan')
                ->schema([
                    TextEntry::make('tanda_kehormatan_dimiliki')->label('Tanda Kehormatan yang sudah dimiliki')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('hukuman_disiplin')->label('Hukuman Disiplin')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('cltn')->label('CLTN')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('ditetapkan_di')->label('Ditetapkan di')->placeholder('—'),
                    TextEntry::make('tanggal_ditetapkan')->label('Tanggal Ditetapkan')->placeholder('—'),
                    TextEntry::make('ttd_kiri_nama')->label('Penandatangan Kiri — Nama')->placeholder('—'),
                    TextEntry::make('ttd_kiri_nip')->label('Penandatangan Kiri — NIP')->placeholder('—'),
                    TextEntry::make('ttd_kiri_jabatan')->label('Jabatan Atasan')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Berkas Lampiran')
                ->description('✓ = sudah diunggah, ✗ = belum. Semua opsional.')
                ->schema([
                    ...collect(DrhSatyaLancanaDokumen::JENIS_LABELS)->map(
                        fn (string $label, string $jenis): TextEntry => TextEntry::make('status_berkas_'.$jenis)
                            ->label(strtoupper($jenis).'. '.$label)
                            ->state(fn (DrhSatyaLancana $record): string => self::statusBerkas($record, $jenis))
                            ->suffixAction(
                                fn (DrhSatyaLancana $record): ?Action => self::unduhLampiranAction($record, $jenis),
                            )
                            ->columnSpanFull(),
                    )->all(),
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
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn (DrhSatyaLancana $record): ?string => $record->nip)
                    ->searchable(['nama', 'nip'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DrhSatyaLancana::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => DrhSatyaLancana::STATUS_COLORS[$state] ?? 'gray')
                    ->sortable(),
                TextColumn::make('jabatan_terakhir')->label('Jabatan')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('pangkat_golongan')->label('Pangkat/Gol.')->placeholder('—')->toggleable(),
                TextColumn::make('ditetapkan_di')->label('Ditetapkan di')->placeholder('—')->toggleable(),
                TextColumn::make('tanggal_ditetapkan')->label('Tanggal')->placeholder('—')->toggleable(),
                TextColumn::make('dibuat_oleh_nama')->label('Dibuat Oleh')->placeholder('—'),
                TextColumn::make('created_at')->label('Tgl Dibuat')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status Usulan')
                    ->options(DrhSatyaLancana::STATUSES)
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('ubah_status')
                    ->label('Status')
                    ->icon('heroicon-o-flag')
                    ->color('gray')
                    ->schema([
                        ToggleButtons::make('status')
                            ->label('Status Usulan')
                            ->options(DrhSatyaLancana::STATUSES)
                            ->colors(DrhSatyaLancana::STATUS_COLORS)
                            ->inline()
                            ->required(),
                    ])
                    ->fillForm(fn (DrhSatyaLancana $record): array => ['status' => $record->status])
                    ->action(fn (DrhSatyaLancana $record, array $data): mixed => $record->update(['status' => $data['status']]))
                    ->successNotificationTitle('Status usulan diperbarui'),
                Action::make('download_docx')
                    ->label('Word')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (DrhSatyaLancana $record): BinaryFileResponse {
                        $path = app(DrhSatyaLancanaGeneratorService::class)->generateDocx($record);
                        $filename = 'drh-satya-lancana-'.Str::slug($record->nama).'.docx';

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
                    ->action(function (DrhSatyaLancana $record): BinaryFileResponse {
                        $path = app(DrhSatyaLancanaGeneratorService::class)->generatePdf($record);
                        $filename = 'drh-satya-lancana-'.Str::slug($record->nama).'.pdf';

                        return response()
                            ->download($path, $filename, ['Content-Type' => 'application/pdf'])
                            ->deleteFileAfterSend(true);
                    }),
                Action::make('download_merge')
                    ->label('Berkas Gabungan')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function (DrhSatyaLancana $record): BinaryFileResponse {
                        $path = app(\App\Services\Kepegawaian\DrhSatyaLancanaDocumentService::class)->merge($record);
                        $filename = 'berkas-drh-'.Str::slug($record->nama).'.pdf';

                        return response()
                            ->download($path, $filename, ['Content-Type' => 'application/pdf'])
                            ->deleteFileAfterSend(true);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada DRH Satya Lancana')
            ->emptyStateDescription('Klik "Buat DRH" untuk membuat yang pertama.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrhSatyaLancana::route('/'),
            'create' => Pages\CreateDrhSatyaLancana::route('/create'),
            'view' => Pages\ViewDrhSatyaLancana::route('/{record}'),
            'edit' => Pages\EditDrhSatyaLancana::route('/{record}/edit'),
        ];
    }
}

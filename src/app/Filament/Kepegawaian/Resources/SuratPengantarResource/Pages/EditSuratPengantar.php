<?php

namespace App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditSuratPengantar extends EditRecord
{
    protected static string $resource = SuratPengantarResource::class;

    /**
     * Snapshot field pegawai tunggal yang di-unset dari data record utama
     * sebelum disimpan kembali sebagai baris surat_pengantar_pegawai.
     *
     * @var array<string, ?string>
     */
    private array $pegawaiSnapshot = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * tempat_tanggal tersimpan sebagai "Semarang, j F Y" — dikonversi ke
     * "Y-m-d" SEBELUM form di-fill; lihat catatan panjang di
     * EditSuratTugas::mutateFormDataBeforeFill() soal kenapa bukan
     * afterStateHydrated().
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Key Select Penandatangan = NIP; snapshot NIP tampil di field
        // ttd_nip_snapshot. Kepala Dinas dipetakan ke key khusus
        // 'kepala-dinas' (NIP-nya tidak ada di PegawaiProfil).
        $nipTersimpan = trim((string) ($data['ttd_nip'] ?? ''));
        $data['ttd_nip_snapshot'] = filled($nipTersimpan) ? $nipTersimpan : null;

        if ($nipTersimpan === '197404131993031002') {
            $data['ttd_nip'] = 'kepala-dinas';
        }

        // Isi field pegawai tunggal dari baris pegawai pertama.
        $pegawai = $this->getRecord()->pegawai()->first();
        $data['nip'] = $pegawai?->nip;
        $data['nama'] = $pegawai?->nama;
        $data['jabatan'] = $pegawai?->jabatan;
        $data['pangkat_golongan'] = $pegawai?->pangkat_golongan;
        $data['instansi'] = $pegawai?->instansi;

        if (filled($data['tempat_tanggal'] ?? null)) {
            $tanggalText = trim((string) str($data['tempat_tanggal'])->after(','));
            try {
                $data['tempat_tanggal'] = Carbon::createFromLocaleFormat('j F Y', 'id', $tanggalText)->format('Y-m-d');
            } catch (\Throwable $e) {
                $data['tempat_tanggal'] = null;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Key 'kepala-dinas' (Select) disimpan sebagai NIP asli Kepala Dinas.
        if (($data['ttd_nip'] ?? null) === 'kepala-dinas') {
            $data['ttd_nip'] = '197404131993031002';
        }

        if (blank($data['ttd_nip'] ?? null) && filled($data['ttd_nip_snapshot'] ?? null)) {
            $data['ttd_nip'] = $data['ttd_nip_snapshot'];
        }
        unset($data['ttd_nip_snapshot']);

        // Field pegawai tunggal bukan kolom surat_pengantar — lepas dari
        // record utama, simpan ke baris surat_pengantar_pegawai.
        foreach (['nip', 'nama', 'jabatan', 'pangkat_golongan', 'instansi'] as $field) {
            $this->pegawaiSnapshot[$field] = $data[$field] ?? null;
            unset($data[$field]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Pegawai hanya satu: hapus baris lama lalu buat ulang dari snapshot.
        $record->pegawai()->delete();
        $record->pegawai()->create([
            ...$this->pegawaiSnapshot,
            'urutan' => 0,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Tool\Resources\SuratTugasResource\Pages;

use App\Filament\Tool\Resources\SuratTugasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditSuratTugas extends EditRecord
{
    protected static string $resource = SuratTugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * hari_tanggal/tanggal_naskah tersimpan sebagai teks naskah dinas
     * ("Senin, 5 Oktober 2026" / "Semarang, 1 Oktober 2026"), bukan tanggal
     * murni — dikonversi ke "Y-m-d" di sini SEBELUM form di-fill, supaya
     * DatePicker menampilkan tanggal yang benar.
     *
     * Ini TIDAK BISA dipindah ke afterStateHydrated() pada field-nya sendiri:
     * DatePicker Filament sudah mencoba meng-cast raw state dengan
     * Carbon::parse() lebih dulu (lewat DateTimeStateCast bawaan) SEBELUM
     * afterStateHydrated() sempat jalan — teks "Senin, 5 Oktober 2026" gagal
     * di-parse Carbon biasa (prefix nama hari + nama bulan Indonesia),
     * sehingga state sudah terlanjur jadi null duluan saat hook kita jalan.
     * Dikonversi lebih awal di sini, raw state yang sampai ke DatePicker
     * sudah berupa "Y-m-d" yang valid buat Carbon::parse() bawaan.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pecah teks `waktu` lama ("09.00 WIB s.d. selesai") jadi 2 field input.
        [$mulai, $selesai] = SuratTugasResource::parseWaktu($data['waktu'] ?? null);
        $data['waktu_mulai'] = $mulai;
        // "Selesai" dipisah ke checkbox supaya TimePicker hanya menerima jam.
        $data['selesai_tanpa_jam'] = $selesai === 'selesai';
        $data['waktu_selesai'] = $selesai === 'selesai' ? null : $selesai;

        if (filled($data['hari_tanggal'] ?? null)) {
            $data['hari_tanggal'] = $this->parseIndonesianDate($data['hari_tanggal'], 'l, j F Y');
        }

        if (filled($data['tanggal_naskah'] ?? null)) {
            // Buang prefix "Semarang, " sebelum parse, format aslinya "j F Y".
            $tanggalText = trim(str($data['tanggal_naskah'])->after(',')->toString());
            $data['tanggal_naskah'] = $this->parseIndonesianDate($tanggalText, 'j F Y');
        }

        return $data;
    }

    private function parseIndonesianDate(string $text, string $format): ?string
    {
        try {
            return Carbon::createFromLocaleFormat($format, 'id', $text)->format('Y-m-d');
        } catch (\Throwable $e) {
            // Nilai lama tak sesuai format yang diharapkan (mis. diisi
            // manual lewat tinker) — biarkan kosong daripada error di form.
            return null;
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Gabung waktu_mulai + waktu_selesai jadi teks `waktu` untuk template.
        // Centang "Selesai" berarti tidak ada jam akhir (waktu_selesai kosong).
        $data['waktu'] = SuratTugasResource::composeWaktu(
            $data['waktu_mulai'] ?? null,
            ($data['selesai_tanpa_jam'] ?? false) ? 'selesai' : ($data['waktu_selesai'] ?? null),
        );
        unset($data['waktu_mulai'], $data['waktu_selesai'], $data['selesai_tanpa_jam']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

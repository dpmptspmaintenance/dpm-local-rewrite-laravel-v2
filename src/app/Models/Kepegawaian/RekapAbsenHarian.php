<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Satu baris presensi harian di dalam rekap bulanan.
 * `hadir_selisih`/`pulang_selisih` = kolom "+/-" dari aplikasi presensi.
 */
class RekapAbsenHarian extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'rekap_absen_harian';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'tanggal' => 'date',
            'jam_hadir' => 'datetime',
            'jam_pulang' => 'datetime',
        ];
    }

    public function rekapAbsen(): BelongsTo
    {
        return $this->belongsTo(RekapAbsen::class, 'rekap_absen_id');
    }

    /**
     * Data dukung TPP (tpp.semarangkota.go.id/izin) yang memuat tanggal
     * presensi ini, bila ada.
     */
    public function tppTerkait(): ?RekapAbsenTpp
    {
        $rekap = $this->rekapAbsen;
        $tanggal = $this->tanggal?->toDateString();

        if ($rekap === null || $tanggal === null) {
            return null;
        }

        return RekapAbsenTpp::query()
            ->where('nip', $rekap->nip)
            ->where('tahun', $rekap->tahun)
            ->where('bulan', $rekap->bulan)
            ->where('file_pendukung_status', RekapAbsenTpp::FILE_ADA)
            ->whereHas('tanggal', fn ($query) => $query->whereDate('tanggal', $tanggal))
            ->first();
    }

    /**
     * Lampiran untuk baris ini: data dukung absen dulu, jika tidak ada pakai
     * file pendukung TPP yang sudah diunggah.
     */
    public function tautanLampiran(): ?string
    {
        $token = $this->dukunganTerkait()->first()?->token;

        if (filled($token)) {
            return RekapAbsenDataDukung::FILE_BASE.$token;
        }

        return $this->tppTerkait()?->tautanFile();
    }

    /**
     * Data dukung pada rekap yang sama yang rentang tanggalnya memuat
     * tanggal presensi ini.
     *
     * @return Collection<int, RekapAbsenDataDukung>
     */
    public function dukunganTerkait(): Collection
    {
        $tanggal = $this->tanggal?->toDateString();

        if ($tanggal === null) {
            return collect();
        }

        return ($this->rekapAbsen?->dataDukung ?? collect())
            ->filter(function (RekapAbsenDataDukung $dukung) use ($tanggal): bool {
                $mulai = $dukung->tanggal_mulai?->toDateString();
                $selesai = $dukung->tanggal_selesai?->toDateString() ?? $mulai;

                return $mulai !== null && $tanggal >= $mulai && $tanggal <= $selesai;
            })
            ->values();
    }
}

<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu baris "Data Pegawai Tidak Masuk Kerja" dari TPP
 * (tpp.semarangkota.go.id/izin), masuk lewat API extension.
 *
 * Terpisah dari data dukung absen. Hanya baris dengan file pendukung sudah
 * diunggah (`file_pendukung_status = 'ada'`) yang menutup baris KONFIRMASI
 * pada rekap absen bulanan yang sama (nip+tahun+bulan).
 */
class RekapAbsenTpp extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'rekap_absen_tpp';

    public const FILE_ADA = 'ada';

    public const FILE_BELUM = 'belum';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'id_tpp' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'jumlah_hari' => 'integer',
            'bulan' => 'integer',
            'tahun' => 'integer',
            'imported_at' => 'datetime',
        ];
    }

    public function tanggal(): HasMany
    {
        return $this->hasMany(RekapAbsenTppTanggal::class, 'rekap_absen_tpp_id')->orderBy('tanggal');
    }

    /**
     * Hanya baris valid (file pendukung sudah diunggah) yang dihitung
     * sebagai konfirmasi.
     */
    public function valid(): bool
    {
        return $this->file_pendukung_status === self::FILE_ADA;
    }

    public function statusFileTeks(): string
    {
        return $this->valid() ? 'Ada' : 'Belum ada';
    }

    public function tanggalTeks(): string
    {
        $daftar = $this->relationLoaded('tanggal')
            ? $this->tanggal
            : $this->tanggal()->get();

        if ($daftar->isEmpty()) {
            return '—';
        }

        return $daftar
            ->map(fn (RekapAbsenTppTanggal $row): string => $row->tanggal?->format('d M Y') ?? '-')
            ->implode(', ');
    }

    public function tautanFile(): ?string
    {
        $url = trim((string) $this->file_pendukung_url);

        return $url !== '' ? $url : null;
    }
}

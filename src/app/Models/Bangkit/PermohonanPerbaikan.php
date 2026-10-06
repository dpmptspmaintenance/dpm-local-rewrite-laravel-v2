<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permohonan perbaikan barang (legacy `sekre_bangkit_data_permohonan_perbaikan`).
 * Alur 3 tahap verifikasi: Bendahara (b_barang) → Kasubag Umpeg (umpeg)
 * → Sekretaris Dinas (sekdin). Nilai: 0 belum, 1 disetujui, 2 ditolak,
 * null/dll = ditunggu.
 */
class PermohonanPerbaikan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_data_permohonan_perbaikan';

    protected $primaryKey = 'Id';

    // Legacy: hanya ada created_at (tak ada modified_at / updated_at).
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_barang',
        'kode_barang_register',
        'uraian_kerusakan',
        'tanggal_permohonan',
        'created_by',
        'modified_by',
        'is_aktif',
        'perbaikan_ke',
        'verifikasi_b_barang',
        'tanggal_verifikasi_b_barang',
        'penjelasan_b_barang',
        'verifikasi_umpeg',
        'tanggal_verifikasi_umpeg',
        'penjelasan_umpeg',
        'verifikasi_sekdin',
        'tanggal_verifikasi_sekdin',
        'penjelasan_sekdin',
        'pagu_anggaran',
        'tanggal_pengerjaan',
        'pengerjaan_oleh',
        'tanggal_selesai',
        'serah_terima_oleh',
        'biaya',
        'spj_tanggal',
        'uraian_perbaikan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_permohonan' => 'date',
            'tanggal_verifikasi_b_barang' => 'date',
            'tanggal_verifikasi_umpeg' => 'date',
            'tanggal_verifikasi_sekdin' => 'date',
            'tanggal_pengerjaan' => 'date',
            'tanggal_selesai' => 'date',
            'spj_tanggal' => 'date',
            'pagu_anggaran' => 'float',
            'biaya' => 'float',
            'is_aktif' => 'boolean',
            'created_at' => 'datetime',
            'modified_at' => 'datetime',
        ];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'Id');
    }

    /** Label status verifikasi 0/1/2/null. */
    public static function labelVerifikasi($nilai): string
    {
        return match ((int) $nilai) {
            1 => 'Disetujui',
            2 => 'Ditolak',
            0 => 'Belum Diverifikasi',
            default => 'Ditunggu',
        };
    }

    public static function warnaVerifikasi($nilai): string
    {
        return match ((int) $nilai) {
            1 => 'success',
            2 => 'danger',
            0 => 'secondary',
            default => 'primary',
        };
    }

    /** Sudah divalidasi ketiga level. */
    public function terverifikasiPenuh(): bool
    {
        return (int) $this->verifikasi_b_barang === 1
            && (int) $this->verifikasi_umpeg === 1
            && (int) $this->verifikasi_sekdin === 1;
    }
}

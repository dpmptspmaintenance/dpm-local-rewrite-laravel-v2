<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Inventaris barang dinas (legacy `sekre_bangkit_data_barang`).
 * Kolom dijaga verbatim: PK `Id`, `Jenis` huruf besar, `is_aktif`.
 */
class Barang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_data_barang';

    protected $primaryKey = 'Id';

    // Legacy: tidak ada kolom updated_at; pakai created_at / modified_at.
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';

    protected $fillable = [
        'kode_barang',
        'register',
        'kode_barang_register',
        'nama_barang',
        'merk_type',
        'Jenis',
        'bahan',
        'keadaan_barang',
        'lokasi',
        'pemegang',
        'tahun_pembelian',
        'harga',
        'keterangan',
        'link_foto',
        'created_by',
        'modified_by',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'float',
            'is_aktif' => 'boolean',
            'created_at' => 'datetime',
            'modified_at' => 'datetime',
        ];
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(MasterJenisBarang::class, 'Jenis', 'Id');
    }

    public function bahanBarang(): BelongsTo
    {
        return $this->belongsTo(MasterBahanBarang::class, 'bahan', 'Id');
    }

    public function keadaan(): BelongsTo
    {
        return $this->belongsTo(MasterKeadaanBarang::class, 'keadaan_barang', 'Id');
    }

    public function lokasiBarang(): BelongsTo
    {
        return $this->belongsTo(MasterLokasi::class, 'lokasi', 'Id');
    }

    public function pemegangPegawai(): BelongsTo
    {
        return $this->belongsTo(PegawaiKekuatan::class, 'pemegang', 'Id');
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(PermohonanPerbaikan::class, 'id_barang', 'Id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(HistoryBarang::class, 'id_barang', 'Id');
    }
}

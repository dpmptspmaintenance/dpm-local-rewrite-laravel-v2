<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Riwayat DRH (Daftar Riwayat Hidup) usulan Satya Lancana Karya Satya yang
 * pernah digenerate — satu baris per dokumen, isi identitas + field manual
 * per-DRH. Pola sama SuratTugas (riwayat disimpan, bukan stateless).
 *
 * Nama/nip/golongan/jabatan di-snapshot dari pegawai_profil SAAT dibuat,
 * bukan live-join — supaya DRH lama tak berubah kalau data pegawai
 * di-update belakangan.
 */
class DrhSatyaLancana extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'drh_satya_lancana';

    protected $guarded = [];

    /** Teks baku default untuk field hukuman disiplin (bisa diedit per-DRH). */
    public const HUKUMAN_DISIPLIN_DEFAULT = 'Tidak pernah mendapatkan hukuman disiplin tingkat sedang/berat selama masa kerja yang dijalani';

    /** Teks baku default untuk field CLTN (bisa diedit per-DRH). */
    public const CLTN_DEFAULT = 'Tidak pernah mengambil Cuti di Luar Tanggungan Negara (CLTN) selama masa kerja yang dijalani.';

    /** Penandatangan kiri default (Kepala Dinas) — bisa diubah per-DRH. */
    public const TTD_KIRI_NAMA_DEFAULT = 'Drs. Puput Widhiatmoko Hadinugroho, MM';

    public const TTD_KIRI_NIP_DEFAULT = '197404131993031002';

    /** Teks jabatan blok TTD kiri (multi-baris) — bisa diubah per-DRH. */
    public const TTD_KIRI_JABATAN_DEFAULT = "Kepala Dinas\nPenanaman Modal Dan Pelayanan\nTerpadu Satu Pintu";

    /** Label status alur usulan DRH. */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_DIUSULKAN = 'diusulkan';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_SUKSES = 'sukses';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_DIUSULKAN => 'Diusulkan',
        self::STATUS_DITOLAK => 'Ditolak',
        self::STATUS_SUKSES => 'Sukses',
    ];

    /** Kelas warna badge per status (dipakai kolom tabel & form). */
    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_DIUSULKAN => 'info',
        self::STATUS_DITOLAK => 'danger',
        self::STATUS_SUKSES => 'success',
    ];

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DrhSatyaLancanaDokumen::class, 'drh_satya_lancana_id');
    }

    /**
     * Hapus folder berkas lampiran fisik saat DRH dihapus (baris DB dokumen
     * ikut cascade, tapi file di disk harus dibersihkan manual).
     */
    protected static function booted(): void
    {
        static::deleting(function (self $drh): void {
            $dir = storage_path('app/drh-satya-lancana/'.$drh->getKey());

            foreach (glob($dir.'/*') ?: [] as $file) {
                @unlink($file);
            }

            if (is_dir($dir)) {
                @rmdir($dir);
            }
        });
    }
}

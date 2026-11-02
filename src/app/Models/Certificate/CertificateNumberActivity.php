<?php

namespace App\Models\Certificate;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu kegiatan beserta blok penomoran sertifikatnya (kode + tahun + nomor
 * awal). Nomor surat tiap baris disimpan di CertificateNumberRow sebagai
 * snapshot.
 */
class CertificateNumberActivity extends Model
{
    protected $table = 'certificate_number_activities';

    protected $fillable = [
        'kegiatan',
        'tempat',
        'tanggal',
        'kode',
        'tahun',
        'nomor_awal',
        'nomor_akhir',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahun' => 'integer',
            'nomor_awal' => 'integer',
            'nomor_akhir' => 'integer',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(CertificateNumberRow::class, 'activity_id')
            ->orderBy('urutan')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if (self::userIsAdmin($user)) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return self::userIsAdmin($user) || (int) $this->created_by === (int) $user->id;
    }

    public static function userIsAdmin(?User $user): bool
    {
        return $user !== null && ((int) $user->role === 1 || (bool) $user->is_admin_kepegawaian);
    }

    /** Label rentang nomor untuk tabel, mis. "1–3" / "90–92". */
    public function nomorRangeLabel(): string
    {
        if ($this->nomor_akhir === null) {
            return (string) $this->nomor_awal;
        }

        return $this->nomor_awal === $this->nomor_akhir
            ? (string) $this->nomor_awal
            : $this->nomor_awal.'–'.$this->nomor_akhir;
    }
}

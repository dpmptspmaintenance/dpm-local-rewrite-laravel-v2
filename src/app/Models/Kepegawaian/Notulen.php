<?php

namespace App\Models\Kepegawaian;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Notulen Kegiatan / Rapat di Modul Kepegawaian.
 * Terletak di koneksi 'kepegawaian'.
 */
class Notulen extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'notulen';

    protected $guarded = [];

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_user_id');
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_user_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}

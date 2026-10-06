<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu batch impor DUK (satu upload PDF). Dipakai supaya data DUK tersimpan
 * per upload, bukan snapshot terbaru yang menimpa — halaman DUK bisa memilih
 * batch mana yang ditampilkan (default terbaru).
 */
class DukImpor extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'duk_impor';

    protected $guarded = [];

    protected $casts = [
        'diimpor_pada' => 'datetime',
    ];

    public function baris(): HasMany
    {
        return $this->hasMany(Duk::class, 'duk_impor_id');
    }

    /** Label dropdown "kapan upload" (mis. "06 Okt 2026 14:30 — 34 baris"). */
    public function label(): string
    {
        $waktu = $this->diimpor_pada?->format('d M Y H:i') ?? '—';
        $ket = $this->periode ?: $this->original_filename;

        return $waktu.' — '.$this->jumlah_baris.' baris'.($ket ? ' ('.$ket.')' : '');
    }
}

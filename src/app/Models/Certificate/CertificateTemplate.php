<?php

namespace App\Models\Certificate;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CertificateTemplate extends Model
{
    protected $table = 'certificate_templates';

    protected $fillable = [
        'created_by',
        'title',
        'background_path',
        'bg_width_px',
        'bg_height_px',
        'page_width_mm',
        'page_height_mm',
        'file_name_pattern',
    ];

    protected function casts(): array
    {
        return [
            'bg_width_px' => 'integer',
            'bg_height_px' => 'integer',
            'page_width_mm' => 'float',
            'page_height_mm' => 'float',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CertificateField::class, 'template_id')->orderBy('sort_order')->orderBy('id');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(CertificateDataset::class, 'template_id');
    }

    public function latestDataset(): HasOne
    {
        return $this->hasOne(CertificateDataset::class, 'template_id')->latestOfMany();
    }

    public function generations(): HasMany
    {
        return $this->hasMany(CertificateGeneration::class, 'template_id');
    }

    /**
     * Template hanya terlihat oleh pembuatnya, kecuali admin modul
     * (role 1 atau flag admin kepegawaian) yang melihat semua.
     */
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

    public function backgroundAbsolutePath(): string
    {
        return storage_path('app/private/'.ltrim($this->background_path, '/'));
    }

    public static function userIsAdmin(?User $user): bool
    {
        return $user !== null && ((int) $user->role === 1 || (bool) $user->is_admin_kepegawaian);
    }
}

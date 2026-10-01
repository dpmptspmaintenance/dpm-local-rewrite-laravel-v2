<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if (blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tag');
    }

    /**
     * Cari-atau-buat tag secara case-insensitive lewat slug ("Arsip" dan
     * "arsip" jadi tag yang sama — lihat AGENTS.md § 5, Standardisasi Tag).
     * Nama yang ditampilkan mengikuti versi PERTAMA yang dibuat.
     */
    public static function resolve(string $name): self
    {
        $name = trim($name);
        $slug = Str::slug($name);

        return static::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name],
        );
    }
}

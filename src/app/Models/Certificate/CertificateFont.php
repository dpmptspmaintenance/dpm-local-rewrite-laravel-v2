<?php

namespace App\Models\Certificate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Satu keluarga font yang boleh dipakai field sertifikat.
 *
 * `css_family` adalah nama yang disimpan di certificate_fields.font_family
 * dan dipakai sebagai font-family di HTML/PDF. Untuk font non-sistem,
 * berkas TTF didaftarkan ke Dompdf lewat @font-face (lihat
 * CertificateRenderer) dan disajikan ke browser lewat route preview.
 */
class CertificateFont extends Model
{
    protected $table = 'certificate_fonts';

    public const DISK = 'local';

    public const DIRECTORY = 'certificate/fonts';

    /** Prefix path untuk font yang dibundel ikut repo (resources/fonts/certificate). */
    public const BUNDLED_PREFIX = 'bundled:';

    public const CATEGORIES = [
        'sans' => 'Sans-serif',
        'serif' => 'Serif',
        'mono' => 'Monospace',
        'display' => 'Display',
    ];

    protected $fillable = [
        'name',
        'css_family',
        'category',
        'regular_path',
        'bold_path',
        'italic_path',
        'bold_italic_path',
        'line_height_factor',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'line_height_factor' => 'float',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Hapus berkas TTF saat record font dihapus, supaya storage tidak
        // menyimpan font yatim yang tak bisa dipakai lagi. Cache metrik
        // Dompdf (ufm/ttf hasil registerFont) juga ikut dibersihkan.
        static::deleting(function (self $font): void {
            $font->purgeDompdfCache();

            foreach (['regular_path', 'bold_path', 'italic_path', 'bold_italic_path'] as $column) {
                $path = trim((string) $font->{$column});

                if ($path !== '' && Storage::disk(self::DISK)->exists($path)) {
                    Storage::disk(self::DISK)->delete($path);
                }
            }
        });

        // Ganti nama keluarga = kunci cache Dompdf berubah; buang kunci lama.
        static::updating(function (self $font): void {
            if (! $font->isDirty('css_family')) {
                return;
            }

            $font->purgeDompdfCache((string) $font->getOriginal('css_family'));
        });
    }

    /**
     * Hapus entri + berkas cache font buatan Dompdf untuk nama keluarga ini.
     * registerFont() menulis `<prefix>_<hash>.ttf/.ufm` ke fontDir dan
     * mencatatnya di installed-fonts.json.
     */
    public function purgeDompdfCache(?string $family = null): void
    {
        $family = $family ?? (string) $this->css_family;
        $key = mb_strtolower(trim(preg_replace('/[\'"\\\\;{}<>]/u', '', $family) ?? ''));

        if ($key === '') {
            return;
        }

        $dir = Storage::disk(self::DISK)->path(self::DIRECTORY);
        $cacheFile = $dir.'/installed-fonts.json';

        if (! is_file($cacheFile)) {
            return;
        }

        $data = json_decode((string) file_get_contents($cacheFile), true);

        if (! is_array($data) || ! isset($data[$key]) || ! is_array($data[$key])) {
            return;
        }

        foreach ($data[$key] as $variantPath) {
            if (! is_string($variantPath) || $variantPath === '') {
                continue;
            }

            $base = $dir.'/'.basename($variantPath);

            foreach (['', '.ttf', '.ufm', '.ufm.json'] as $suffix) {
                if (is_file($base.$suffix)) {
                    @unlink($base.$suffix);
                }
            }
        }

        unset($data[$key]);

        @file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Field sertifikat yang memakai keluarga font ini (relasi lewat nama
     * keluarga, bukan FK, karena font_family disimpan sebagai string CSS).
     */
    public function fields(): HasMany
    {
        return $this->hasMany(CertificateField::class, 'font_family', 'css_family');
    }

    /**
     * Faktor pitch baris internal Dompdf untuk font ini (diukur dari render
     * nyata). Dipakai renderer: line-height efektif = 1.15 / faktor, supaya
     * jarak baris PDF sama dengan browser (1.15em).
     */
    public function lineHeightFactor(): float
    {
        $factor = (float) ($this->line_height_factor ?: 0);

        return $factor > 0.5 ? $factor : 1.264;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param  Builder<self>  $query */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('sort_order', $direction)->orderBy('name');
    }

    /**
     * Semua slot varian (selalu 4), dengan path NULL bila belum diunggah.
     * Renderer memakai berkas Regular sebagai pengganti varian yang kosong.
     *
     * @return array<string, array{weight: int, style: string, path: string|null}>
     */
    public function variants(): array
    {
        return [
            'regular' => $this->variantData('regular_path', 400, 'normal'),
            'bold' => $this->variantData('bold_path', 700, 'normal'),
            'italic' => $this->variantData('italic_path', 400, 'italic'),
            'bold_italic' => $this->variantData('bold_italic_path', 700, 'italic'),
        ];
    }

    /**
     * @return array{weight: int, style: string, path: string|null}
     */
    private function variantData(string $column, int $weight, string $style): array
    {
        $path = trim((string) $this->{$column});

        return ['weight' => $weight, 'style' => $style, 'path' => $path === '' ? null : $path];
    }

    /** Varian yang benar-benar punya berkas sendiri (untuk label tabel). */
    public function uploadedVariants(): array
    {
        return array_filter($this->variants(), fn (array $variant): bool => $variant['path'] !== null);
    }

    /**
     * Path absolut berkas varian font.
     *
     * Path diawali "bundled:" berarti berkas ikut repo di
     * resources/fonts/certificate/ (dijamin ada di semua environment dan
     * di-commit ke git). Path lain = unggahan admin di disk privat.
     */
    public function absolutePath(string $variant): ?string
    {
        $path = $this->variants()[$variant]['path'] ?? null;

        if ($path === null) {
            return null;
        }

        $absolute = str_starts_with($path, self::BUNDLED_PREFIX)
            ? resource_path('fonts/certificate/'.substr($path, strlen(self::BUNDLED_PREFIX)))
            : Storage::disk(self::DISK)->path($path);

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Font sistem memakai keluarga yang sudah ada di Dompdf/AFM sehingga
     * tidak butuh @font-face. Selain itu wajib punya TTF regular.
     */
    /**
     * Label varian untuk kolom tabel Master Font.
     */
    public function variantSummary(): string
    {
        if ($this->is_system && $this->absolutePath('regular') !== null) {
            return 'Bundel aplikasi (embedded)';
        }

        if ($this->is_system) {
            return 'Bawaan Dompdf (tidak embedded)';
        }

        $labels = [
            'regular' => 'Regular',
            'bold' => 'Bold',
            'italic' => 'Italic',
            'bold_italic' => 'Bold Italic',
        ];

        $available = [];

        foreach ($this->uploadedVariants() as $key => $variant) {
            $available[] = $labels[$key] ?? $key;
        }

        return $available === [] ? '—' : implode(', ', $available);
    }

    /**
     * Font dianggap ter-embed bila punya berkas TTF sendiri. Font AFM
     * bawaan Dompdf (Helvetica/Times/Courier lama) TIDAK ter-embed sehingga
     * pembaca PDF bisa mengganti fontnya (mis. jadi Cambria / Microsoft Sans
     * Serif). Karena itu font sistem sekarang juga diberi berkas TTF
     * (Liberation untuk Times/Helvetica/Courier, DejaVu untuk DejaVu).
     */
    public function isEmbedded(): bool
    {
        return $this->absolutePath('regular') !== null;
    }

    /**
     * Font layak dipakai bila punya berkas TTF, atau berupa font sistem lama
     * tanpa berkas (masih dirender Dompdf lewat font bawaan — tidak embedded).
     */
    public function isUsable(): bool
    {
        return $this->is_system || $this->absolutePath('regular') !== null;
    }

    /**
     * Nama aman untuk disisipkan ke atribut style="..." dan @font-face.
     * Buang karakter yang bisa memutus deklarasi CSS.
     */
    public function safeFamily(): string
    {
        $family = preg_replace('/[\'"\\\\;{}<>]/u', '', (string) $this->css_family) ?? '';

        return trim($family);
    }

    public function cssStack(): string
    {
        $family = $this->safeFamily();

        if ($family === '') {
            return "'DejaVu Sans', sans-serif";
        }

        $fallback = match ($this->category) {
            'serif' => 'serif',
            'mono' => 'monospace',
            default => 'sans-serif',
        };

        return "'".$family."', ".$fallback;
    }

    /**
     * Daftar nama keluarga aktif — dipakai untuk validasi properti field.
     *
     * @return list<string>
     */
    public static function activeFamilies(): array
    {
        return self::query()
            ->active()
            ->ordered()
            ->pluck('css_family')
            ->map(fn (mixed $family): string => (string) $family)
            ->all();
    }

    /**
     * Font aktif yang benar-benar bisa dirender (sistem atau TTF ada).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function usable(): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->active()
            ->ordered()
            ->get()
            ->filter(fn (self $font): bool => $font->isUsable())
            ->values();
    }

    public static function forFamily(?string $family): ?self
    {
        $family = trim((string) $family);

        if ($family === '') {
            return null;
        }

        return self::query()->where('css_family', $family)->first();
    }
}

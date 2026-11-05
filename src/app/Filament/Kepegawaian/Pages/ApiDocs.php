<?php

namespace App\Filament\Kepegawaian\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

/**
 * Dokumentasi API eksternal (Chrome extension): autentikasi, scope, dan
 * contoh JSON semua endpoint — termasuk CRUD Rekap Absen Bulanan
 * (create = POST baru, read = GET list/detail, update = POST ulang periode
 * yang sama; tanpa delete).
 */
class ApiDocs extends Page
{
    protected static ?string $navigationLabel = 'Dokumentasi API';

    protected static string|\UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 91;

    protected static ?string $title = 'Dokumentasi API';

    protected static ?string $slug = 'api-docs';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected string $view = 'filament.kepegawaian.pages.api-docs';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null && ((int) $user->role === 1 || (bool) $user->is_admin_kepegawaian);
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Referensi endpoint, bentuk JSON, dan contoh pemakaian untuk Chrome extension.';
    }
}

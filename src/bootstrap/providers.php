<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\KepegawaianPanelProvider::class,
    App\Providers\Filament\UserPanelProvider::class,
    // ArsipPanelProvider dinonaktifkan: Arsip Digital kini digabung sebagai
    // group "Arsip" di panel Kepegawaian (lihat KepegawaianPanelProvider).
    // Aplikasi/sumbernya tetap ada bila perlu diaktifkan kembali.
];

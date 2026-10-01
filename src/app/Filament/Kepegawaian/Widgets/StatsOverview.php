<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Models\Kepegawaian\Cuti;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $cutiBulanIni = Cuti::whereBetween('tanggal_mulai_diajukan', [
            now()->startOfMonth(), now()->endOfMonth(),
        ])->count();

        return [
            Stat::make('Total Pegawai', PegawaiProfil::count())
                ->description('Data profil terimpor')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Cuti', Cuti::count())
                ->description('Seluruh pengajuan tercatat')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Cuti Bulan Ini', $cutiBulanIni)
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($cutiBulanIni > 0 ? 'warning' : 'gray'),

            Stat::make('Akun Terdaftar', User::count())
                ->description('Pengguna aplikasi')
                ->descriptionIcon('heroicon-m-identification')
                ->color('success'),
        ];
    }
}

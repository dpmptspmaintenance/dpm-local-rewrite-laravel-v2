<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Resume jumlah pegawai per status kepegawaian (PNS / PPPK / PPPK Paruh
 * Waktu) di atas tabel daftar pegawai. Label panjang status dari SISDM
 * diringkas agar kartu tetap ringkas.
 *
 * Tiap kartu bisa diklik: mengarah ke daftar pegawai dengan filter
 * status_pegawai sudah terpasang (lewat query param `filters`, sama dengan
 * `#[Url(as: 'filters')]` di ListRecords). Kartu "Total Pegawai" mengarah ke
 * daftar tanpa filter (reset).
 */
class PegawaiStatusOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static bool $isDiscovered = false;

    /**
     * URL daftar pegawai dengan filter status_pegawai terpasang. Null status
     * berarti tanpa filter (semua pegawai).
     */
    private function urlFilterStatus(?string $status): string
    {
        $url = PegawaiProfilResource::getUrl('index');

        if ($status === null) {
            return $url;
        }

        return $url.'?'.http_build_query([
            'filters' => [
                'status_pegawai' => ['value' => $status],
            ],
        ]);
    }

    protected function getStats(): array
    {
        $jumlah = PegawaiProfil::query()
            ->selectRaw('status_pegawai, COUNT(*) as jumlah')
            ->groupBy('status_pegawai')
            ->pluck('jumlah', 'status_pegawai');

        $pns = (int) $jumlah->get(PegawaiProfil::STATUS_PNS, 0);
        $pppk = (int) $jumlah->get(PegawaiProfil::STATUS_PPPK, 0);
        $pppkParuhWaktu = (int) $jumlah->get(PegawaiProfil::STATUS_PPPK_PARUH_WAKTU, 0);

        $lainnya = (int) $jumlah->except([
            PegawaiProfil::STATUS_PNS,
            PegawaiProfil::STATUS_PPPK,
            PegawaiProfil::STATUS_PPPK_PARUH_WAKTU,
        ])->sum();

        $stats = [
            Stat::make('Total Pegawai', PegawaiProfil::count())
                ->description('Seluruh status kepegawaian')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url($this->urlFilterStatus(null)),

            Stat::make('PNS', $pns)
                ->description('Pegawai Negeri Sipil')
                ->descriptionIcon('heroicon-m-identification')
                ->color('success')
                ->url($this->urlFilterStatus(PegawaiProfil::STATUS_PNS)),

            Stat::make('PPPK', $pppk)
                ->description('Perjanjian Kerja')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info')
                ->url($this->urlFilterStatus(PegawaiProfil::STATUS_PPPK)),

            Stat::make('PPPK Paruh Waktu', $pppkParuhWaktu)
                ->description('Perjanjian Kerja Paruh Waktu')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray')
                ->url($this->urlFilterStatus(PegawaiProfil::STATUS_PPPK_PARUH_WAKTU)),
        ];

        if ($lainnya > 0) {
            $stats[] = Stat::make('Status Lain', $lainnya)
                ->description('Status di luar PNS/PPPK')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('warning');
        }

        return $stats;
    }
}

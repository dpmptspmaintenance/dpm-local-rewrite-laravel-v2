<?php

namespace App\Filament\Tool\Widgets;

use App\Models\Certificate\CertificateNumberActivity;
use App\Models\Certificate\CertificateTemplate;
use App\Models\Kepegawaian\Notulen;
use App\Models\Kepegawaian\SuratPengantar;
use App\Models\Kepegawaian\SuratTugas;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Surat Tugas', SuratTugas::query()->count())
                ->description('Naskah tersimpan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Surat Pengantar', SuratPengantar::query()->count())
                ->description('Naskah tersimpan')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),

            Stat::make('Notulen', Notulen::query()->count())
                ->description('Rapat tercatat')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success'),

            Stat::make('Template Sertifikat', CertificateTemplate::query()->count())
                ->description(CertificateNumberActivity::query()->count().' kegiatan nomor')
                ->descriptionIcon('heroicon-m-identification')
                ->color('warning'),
        ];
    }
}

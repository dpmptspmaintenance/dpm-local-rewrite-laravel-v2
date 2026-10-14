<?php

namespace App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource\Pages;

use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource;
use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Services\Kepegawaian\DrhSatyaLancanaGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewDrhSatyaLancana extends ViewRecord
{
    protected static string $resource = DrhSatyaLancanaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('download_docx')
                ->label('Download Word')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function (): BinaryFileResponse {
                    /** @var DrhSatyaLancana $record */
                    $record = $this->getRecord();
                    $path = app(DrhSatyaLancanaGeneratorService::class)->generateDocx($record);

                    return response()
                        ->download($path, 'drh-satya-lancana-'.\App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource::namaFile($record->nama).'.docx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->deleteFileAfterSend(true);
                }),
            Action::make('download_pdf')
                ->label('Lihat PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                // Dibuka inline di tab baru (bukan diunduh) lewat route pratinjau.
                ->url(fn (): string => route('kepegawaian.drh-satya-lancana.pdf', $this->getRecord()))
                ->openUrlInNewTab(),
            Action::make('download_merge')
                ->label('Berkas Gabungan')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                // Dibuka inline di tab baru (bukan diunduh) lewat route pratinjau.
                ->url(fn (): string => route('kepegawaian.drh-satya-lancana.gabungan', $this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}

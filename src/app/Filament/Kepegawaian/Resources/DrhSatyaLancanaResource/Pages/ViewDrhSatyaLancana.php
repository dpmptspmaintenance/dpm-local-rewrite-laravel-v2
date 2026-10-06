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
                        ->download($path, 'drh-satya-lancana-'.Str::slug($record->nama).'.docx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->deleteFileAfterSend(true);
                }),
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (): BinaryFileResponse {
                    /** @var DrhSatyaLancana $record */
                    $record = $this->getRecord();
                    $path = app(DrhSatyaLancanaGeneratorService::class)->generatePdf($record);

                    return response()
                        ->download($path, 'drh-satya-lancana-'.Str::slug($record->nama).'.pdf', ['Content-Type' => 'application/pdf'])
                        ->deleteFileAfterSend(true);
                }),
            Action::make('download_merge')
                ->label('Download Berkas Gabungan')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->action(function (): BinaryFileResponse {
                    /** @var DrhSatyaLancana $record */
                    $record = $this->getRecord();
                    $path = app(\App\Services\Kepegawaian\DrhSatyaLancanaDocumentService::class)->merge($record);

                    return response()
                        ->download($path, 'berkas-drh-'.Str::slug($record->nama).'.pdf', ['Content-Type' => 'application/pdf'])
                        ->deleteFileAfterSend(true);
                }),
            DeleteAction::make(),
        ];
    }
}

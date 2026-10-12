<?php

namespace App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource;
use App\Models\Kepegawaian\SuratPengantar;
use App\Services\Kepegawaian\SuratPengantarGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewSuratPengantar extends ViewRecord
{
    protected static string $resource = SuratPengantarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('download_docx')
                ->label('Download Word')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function (): BinaryFileResponse {
                    /** @var SuratPengantar $record */
                    $record = $this->getRecord();
                    $path = app(SuratPengantarGeneratorService::class)->generateDocx($record);

                    return response()
                        ->download($path, Str::slug($record->perihal).'.docx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->deleteFileAfterSend(true);
                }),
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (): BinaryFileResponse {
                    /** @var SuratPengantar $record */
                    $record = $this->getRecord();
                    $path = app(SuratPengantarGeneratorService::class)->generatePdf($record);

                    return response()
                        ->download($path, Str::slug($record->perihal).'.pdf', ['Content-Type' => 'application/pdf'])
                        ->deleteFileAfterSend(true);
                }),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Kepegawaian\Resources\SuratTugasResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratTugasResource;
use App\Models\Kepegawaian\SuratTugas;
use App\Services\Kepegawaian\SuratTugasGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewSuratTugas extends ViewRecord
{
    protected static string $resource = SuratTugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('download_docx')
                ->label('Download Word')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function (): BinaryFileResponse {
                    /** @var SuratTugas $record */
                    $record = $this->getRecord();
                    $path = app(SuratTugasGeneratorService::class)->generateDocx($record);

                    return response()
                        ->download($path, Str::slug($record->judul).'.docx', [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->deleteFileAfterSend(true);
                }),
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (): BinaryFileResponse {
                    /** @var SuratTugas $record */
                    $record = $this->getRecord();
                    $path = app(SuratTugasGeneratorService::class)->generatePdf($record);

                    return response()
                        ->download($path, Str::slug($record->judul).'.pdf', ['Content-Type' => 'application/pdf'])
                        ->deleteFileAfterSend(true);
                }),
            DeleteAction::make(),
        ];
    }
}

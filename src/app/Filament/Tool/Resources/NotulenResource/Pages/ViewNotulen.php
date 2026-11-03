<?php

namespace App\Filament\Tool\Resources\NotulenResource\Pages;

use App\Filament\Tool\Resources\NotulenResource;
use App\Models\Kepegawaian\Notulen;
use App\Services\Kepegawaian\NotulenDocumentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewNotulen extends ViewRecord
{
    protected static string $resource = NotulenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('edit_word')
                ->label('Edit Word')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => EditWordNotulen::getUrl(['record' => $this->getRecord()])),
            Action::make('download_docx')
                ->label('Download Word')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function (): BinaryFileResponse {
                    /** @var Notulen $record */
                    $record = $this->getRecord();
                    $documents = app(NotulenDocumentService::class);
                    $path = $documents->materialize($record);

                    return response()
                        ->download($path, $documents->downloadName($record, 'docx'), [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ]);
                }),
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                // Dibuka inline di tab baru (bukan diunduh) lewat route pratinjau.
                ->url(fn (): string => route('kepegawaian.notulen.pdf', $this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}

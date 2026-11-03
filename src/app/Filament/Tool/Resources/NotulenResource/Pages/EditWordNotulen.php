<?php

namespace App\Filament\Tool\Resources\NotulenResource\Pages;

use App\Filament\Tool\Resources\NotulenResource;
use App\Models\Kepegawaian\Notulen;
use App\Services\Kepegawaian\NotulenDocumentService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

/**
 * Editor Word di browser untuk satu Notulen. Dibuka dari tombol
 * "Edit Word" di daftar notulen (tidak ada di navigasi utama).
 *
 * Dokumen diambil dari snapshot .docx notulen; setelah disimpan, PDF dan
 * unduhan Word memakai versi terbaru.
 */
class EditWordNotulen extends Page
{
    use InteractsWithRecord;

    protected static string $resource = NotulenResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.tool.pages.edit-word-notulen';

    public function mount(int|string $record, NotulenDocumentService $documents): void
    {
        $this->record = $this->resolveRecord($record);

        // Pastikan snapshot .docx ada (generate dari data bila belum pernah).
        $documents->materialize($this->getRecord());
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Notulen $notulen */
        $notulen = $this->getRecord();

        return 'Edit Word: '.Str::limit((string) $notulen->judul, 60);
    }

    public function getBreadcrumb(): string
    {
        return 'Edit Word';
    }

    public function getRecord(): Notulen
    {
        $record = $this->record;

        abort_unless($record instanceof Notulen, 404);

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditorConfigProperty(): array
    {
        /** @var Notulen $notulen */
        $notulen = $this->getRecord();

        return [
            'docxUrl' => route('tool.notulen-word.docx', $notulen),
            'saveUrl' => route('tool.notulen-word.save', $notulen),
            'regenerateUrl' => route('tool.notulen-word.regenerate', $notulen),
            'printUrl' => route('tool.notulen-word.print', $notulen),
            'downloadUrl' => route('kepegawaian.notulen.word', $notulen),
            'pdfUrl' => route('kepegawaian.notulen.pdf', $notulen),
            'fileName' => $this->downloadNameFor($notulen),
            'userName' => (string) (auth()->user()?->getFilamentName() ?? 'Pengguna'),
            'userEmail' => (string) (auth()->user()?->email ?? ''),
            'assetsBase' => asset('vendor/superdoc/assets'),
            'debugUrl' => route('tool.notulen-word.debug'),
            'bootVersion' => 'v5',
        ];
    }

    private function downloadNameFor(Notulen $notulen): string
    {
        return Str::slug((string) $notulen->judul) ?: 'notulen-'.$notulen->getKey();
    }
}

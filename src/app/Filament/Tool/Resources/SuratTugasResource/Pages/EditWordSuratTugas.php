<?php

namespace App\Filament\Tool\Resources\SuratTugasResource\Pages;

use App\Filament\Tool\Resources\SuratTugasResource;
use App\Models\Kepegawaian\SuratTugas;
use App\Services\Kepegawaian\SuratTugasDocumentService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

/**
 * Editor Word di browser untuk satu Surat Tugas. Dibuka dari tombol
 * "Edit Word" di daftar surat (tidak ada di navigasi utama).
 *
 * Dokumen diambil dari snapshot .docx surat; setelah disimpan, PDF dan
 * unduhan Word memakai versi terbaru.
 */
class EditWordSuratTugas extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SuratTugasResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.tool.pages.edit-word-surat-tugas';

    public function mount(int|string $record, SuratTugasDocumentService $documents): void
    {
        $this->record = $this->resolveRecord($record);

        // Pastikan snapshot .docx ada (generate dari data bila belum pernah).
        $documents->materialize($this->getRecord());
    }

    public function getTitle(): string|Htmlable
    {
        /** @var SuratTugas $surat */
        $surat = $this->getRecord();

        return 'Edit Word: '.Str::limit((string) $surat->judul, 60);
    }

    public function getBreadcrumb(): string
    {
        return 'Edit Word';
    }

    public function getRecord(): SuratTugas
    {
        $record = $this->record;

        abort_unless($record instanceof SuratTugas, 404);

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditorConfigProperty(): array
    {
        /** @var SuratTugas $surat */
        $surat = $this->getRecord();

        return [
            'docxUrl' => route('tool.surat-tugas-word.docx', $surat),
            'saveUrl' => route('tool.surat-tugas-word.save', $surat),
            'regenerateUrl' => route('tool.surat-tugas-word.regenerate', $surat),
            'printUrl' => route('tool.surat-tugas-word.print', $surat),
            'downloadUrl' => route('kepegawaian.surat-tugas.word', $surat),
            'pdfUrl' => route('kepegawaian.surat-tugas.pdf', $surat),
            'fileName' => $this->downloadNameFor($surat),
            'userName' => (string) (auth()->user()?->getFilamentName() ?? 'Pengguna'),
            'userEmail' => (string) (auth()->user()?->email ?? ''),
            'assetsBase' => asset('vendor/superdoc/assets'),
            'debugUrl' => route('tool.surat-tugas-word.debug'),
            'bootVersion' => 'v7',
        ];
    }

    private function downloadNameFor(SuratTugas $surat): string
    {
        return Str::slug((string) $surat->judul) ?: 'surat-tugas-'.$surat->getKey();
    }
}

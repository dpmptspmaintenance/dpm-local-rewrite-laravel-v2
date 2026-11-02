<?php

namespace Tests\Feature;

use App\Filament\Arsip\Resources\DocumentResource\Pages\ViewDocument;
use App\Models\Document;
use App\Models\User;
use App\Services\ArsipDigital\DocumentService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArsipDocumentZipDownloadTest extends TestCase
{
    /** @var list<int> */
    private array $cleanDocumentIds = [];

    /** @var list<int> */
    private array $cleanUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'dpmptsp_new',
        ]);
        Filament::setCurrentPanel('kepegawaian');

        Storage::fake('arsip');
        Storage::disk('arsip')->makeDirectory('');
    }

    protected function tearDown(): void
    {
        if (! empty($this->cleanDocumentIds)) {
            Document::query()->whereIn('id', $this->cleanDocumentIds)->delete();
        }

        if (! empty($this->cleanUserIds)) {
            User::query()->whereIn('id', $this->cleanUserIds)->delete();
        }

        parent::tearDown();
    }

    public function test_zip_download_only_contains_selected_files(): void
    {
        $user = $this->makeSuperadmin();
        $this->actingAs($user);

        $document = $this->makeDocumentWithFiles($user, ['laporan-satu.pdf', 'laporan-dua.pdf']);
        $files = $document->files()->orderBy('id')->get();

        $component = Livewire::test(ViewDocument::class, ['record' => $document->getKey()])
            ->callAction('downloadZip', data: ['file_ids' => [$files[0]->id]]);

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download);
        $this->assertSame('application/zip', $download['contentType']);
        $this->assertStringEndsWith('.zip', $download['name']);

        $this->assertSame([$files[0]->original_filename], $this->zipEntries(base64_decode($download['content'], true)));
    }

    public function test_zip_download_dedupes_identical_filenames(): void
    {
        $user = $this->makeSuperadmin();
        $this->actingAs($user);

        $document = $this->makeDocumentWithFiles($user, ['sama.pdf', 'sama.pdf']);

        $component = Livewire::test(ViewDocument::class, ['record' => $document->getKey()])
            ->callAction('downloadZip', data: ['file_ids' => $document->files()->pluck('id')->all()]);

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download);

        $this->assertSame(['sama.pdf', 'sama (1).pdf'], $this->zipEntries(base64_decode($download['content'], true)));
    }

    public function test_zip_action_hidden_for_url_documents(): void
    {
        $user = $this->makeSuperadmin();
        $this->actingAs($user);

        $document = Document::create([
            'title' => 'Dokumen Tautan Uji ZIP',
            'source_type' => Document::SOURCE_URL,
            'source_url' => 'https://example.com/berkas',
            'status' => Document::STATUS_PUBLISHED,
            'created_by' => $user->id,
        ]);
        $this->cleanDocumentIds[] = $document->id;

        Livewire::test(ViewDocument::class, ['record' => $document->getKey()])
            ->assertActionHidden('downloadZip');
    }

    /**
     * @param  list<string>  $filenames
     */
    private function makeDocumentWithFiles(User $user, array $filenames): Document
    {
        $meta = [];

        foreach ($filenames as $filename) {
            $tmp = tempnam(sys_get_temp_dir(), 'arsip_zip_').'.pdf';
            file_put_contents($tmp, '%PDF-1.4 '.$filename);

            $meta[] = [
                'path' => $tmp,
                'filename' => $filename,
                'mime' => 'application/pdf',
                'extension' => 'pdf',
                'size' => filesize($tmp),
            ];
        }

        $document = app(DocumentService::class)->createWithFiles(
            $meta,
            $user,
            null,
            'Uji Unduh ZIP',
            [],
            null,
            Document::STATUS_PUBLISHED,
        );

        $this->cleanDocumentIds[] = $document->id;

        foreach ($meta as $item) {
            @unlink($item['path']);
        }

        return $document->fresh();
    }

    /**
     * @return list<string>
     */
    private function zipEntries(string $binary): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'arsip_zip_check_');
        file_put_contents($tmp, $binary);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);

        $entries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }

        $zip->close();
        @unlink($tmp);

        return $entries;
    }

    private function makeSuperadmin(): User
    {
        $user = User::create([
            'nama' => 'Uji Unduh ZIP '.uniqid(),
            'email' => 'uji-zip-'.uniqid().'@test.local',
            'role' => 1,
            'nip' => '19900101'.random_int(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $user->id;

        return $user;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\ArsipDigital\DocumentService;
use App\Services\ArsipDigital\LocalArsipStorage;
use App\Services\Kepegawaian\PegawaiArsipService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArsipLocalStorageTest extends TestCase
{
    private array $cleanDocumentIds = [];
    private array $cleanUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'dpmptsp_new',
        ]);
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

    public function test_upload_writes_file_to_local_disk_and_records_path(): void
    {
        $user = $this->makeUser('Uji Arsip Lokal');

        $tmp = tempnam(sys_get_temp_dir(), 'arsip_').'.pdf';
        file_put_contents($tmp, '%PDF-1.4 test');

        $service = app(DocumentService::class);
        $document = $service->upload(
            [
                'path' => $tmp,
                'filename' => 'berkas-uji.pdf',
                'mime' => 'application/pdf',
                'extension' => 'pdf',
                'size' => filesize($tmp),
            ],
            $user,
            null,
            'Uji Berkas Lokal',
        );
        $this->cleanDocumentIds[] = $document->id;

        $file = $document->files()->firstOrFail();
        $this->assertNotNull($file->storage_path);
        $this->assertNull($file->google_file_id);
        Storage::disk('arsip')->assertExists($file->storage_path);

        // Berkas mentah berada di "{tahun}/etc/".
        $year = now()->year;
        $this->assertStringStartsWith($year.'/etc/', $file->storage_path);

        @unlink($tmp);
    }

    public function test_organize_on_publish_moves_file_into_document_folder(): void
    {
        $user = $this->makeUser('Uji Organize');

        $tmp = tempnam(sys_get_temp_dir(), 'arsip_').'.pdf';
        file_put_contents($tmp, '%PDF-1.4 organize');

        $service = app(DocumentService::class);
        $document = $service->upload(
            [
                'path' => $tmp,
                'filename' => 'organize.pdf',
                'mime' => 'application/pdf',
                'extension' => 'pdf',
                'size' => filesize($tmp),
            ],
            $user,
            null,
            'Uji Organize Lokal',
        );
        $this->cleanDocumentIds[] = $document->id;

        $file = $document->files()->firstOrFail();
        $original = $file->storage_path;

        $service->organizeOnPublish($document->fresh());
        $document->refresh();
        $file->refresh();

        $this->assertNotNull($document->drive_folder_id);
        $year = now()->year;
        $this->assertStringStartsWith($year.'/', $document->drive_folder_id);
        $this->assertStringStartsWith($document->drive_folder_id.'/', $file->storage_path);
        Storage::disk('arsip')->assertExists($file->storage_path);
        Storage::disk('arsip')->assertMissing($original);

        // Folder tahun tetap setelah dokumen dihapus.
        $folderPath = $document->drive_folder_id;
        $yearPath = $year;
        $service->delete($document->fresh());

        Storage::disk('arsip')->assertMissing($file->storage_path);
        $this->assertFalse(Storage::disk('arsip')->directoryExists($folderPath));
        $this->assertTrue(Storage::disk('arsip')->directoryExists($yearPath));

        @unlink($tmp);
    }

    public function test_pegawai_arsip_service_uses_kepegawaian_subfolder(): void
    {
        $storage = app(LocalArsipStorage::class);
        $folder = $storage->findOrCreateFolder('Kepegawaian', '');
        $this->assertSame('Kepegawaian', $folder);
        $this->assertTrue(Storage::disk('arsip')->directoryExists('Kepegawaian'));

        $this->assertInstanceOf(PegawaiArsipService::class, app(PegawaiArsipService::class));
    }

    private function makeUser(string $prefix): User
    {
        $user = User::create([
            'nama' => $prefix.' '.uniqid(),
            'email' => strtolower(str_replace(' ', '-', $prefix)).'-'.uniqid().'@test.local',
            'role' => 1,
            'nip' => '19900101'.random_int(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $user->id;

        return $user;
    }
}

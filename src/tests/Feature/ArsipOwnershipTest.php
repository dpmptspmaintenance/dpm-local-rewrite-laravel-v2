<?php

namespace Tests\Feature;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Ownership;
use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

class ArsipOwnershipTest extends TestCase
{
    private array $cleanOwnershipIds = [];
    private array $cleanUserIds = [];
    private array $cleanDocumentIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'dpmptsp_new',
        ]);
        Filament::setCurrentPanel('arsip');
    }

    protected function tearDown(): void
    {
        if (! empty($this->cleanDocumentIds)) {
            Document::query()->whereIn('id', $this->cleanDocumentIds)->delete();
        }
        if (! empty($this->cleanOwnershipIds)) {
            Ownership::query()->whereIn('id', $this->cleanOwnershipIds)->delete();
        }
        if (! empty($this->cleanUserIds)) {
            User::query()->whereIn('id', $this->cleanUserIds)->delete();
        }
        parent::tearDown();
    }

    public function test_ownership_access_scoping(): void
    {
        // 1. Create 2 ownerships: Kepegawaian & Keuangan
        $ownerKepegawaian = Ownership::create([
            'name' => 'Unit Kepegawaian Test '.uniqid(),
            'description' => 'Khusus berkas kepegawaian',
        ]);
        $this->cleanOwnershipIds[] = $ownerKepegawaian->id;

        $ownerKeuangan = Ownership::create([
            'name' => 'Unit Keuangan Test '.uniqid(),
            'description' => 'Khusus berkas keuangan',
        ]);
        $this->cleanOwnershipIds[] = $ownerKeuangan->id;

        // 2. Create users
        // Superadmin (role 1)
        $superadmin = User::create([
            'nama' => 'Superadmin Test '.uniqid(),
            'email' => 'superadmin_test_'.uniqid().'@test.local',
            'role' => 1,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $superadmin->id;

        // User under Kepegawaian
        $userKepegawaian = User::create([
            'nama' => 'Staf Kepegawaian Test '.uniqid(),
            'email' => 'staf_kepegawaian_'.uniqid().'@test.local',
            'role' => 6,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $userKepegawaian->id;
        $userKepegawaian->ownerships()->attach($ownerKepegawaian->id);

        // User under Keuangan
        $userKeuangan = User::create([
            'nama' => 'Staf Keuangan Test '.uniqid(),
            'email' => 'staf_keuangan_'.uniqid().'@test.local',
            'role' => 6,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $userKeuangan->id;
        $userKeuangan->ownerships()->attach($ownerKeuangan->id);

        // User outside any unit
        $userBiasa = User::create([
            'nama' => 'User Non-Unit Test '.uniqid(),
            'email' => 'user_biasa_'.uniqid().'@test.local',
            'role' => 6,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $userBiasa->id;

        // 3. Create Documents
        // Document 1: Kepegawaian (published)
        $docKepegawaian = Document::create([
            'title' => 'Dokumen Kepegawaian Test',
            'source_type' => Document::SOURCE_URL,
            'source_url' => 'https://example.com/kepegawaian',
            'status' => Document::STATUS_PUBLISHED,
            'ownership_id' => $ownerKepegawaian->id,
            'created_by' => $superadmin->id,
        ]);
        $this->cleanDocumentIds[] = $docKepegawaian->id;

        // Document 2: Keuangan (published)
        $docKeuangan = Document::create([
            'title' => 'Dokumen Keuangan Test',
            'source_type' => Document::SOURCE_URL,
            'source_url' => 'https://example.com/keuangan',
            'status' => Document::STATUS_PUBLISHED,
            'ownership_id' => $ownerKeuangan->id,
            'created_by' => $superadmin->id,
        ]);
        $this->cleanDocumentIds[] = $docKeuangan->id;

        // Document 3: Publik (published)
        $docPublik = Document::create([
            'title' => 'Dokumen Publik Test',
            'source_type' => Document::SOURCE_URL,
            'source_url' => 'https://example.com/publik',
            'status' => Document::STATUS_PUBLISHED,
            'ownership_id' => null,
            'created_by' => $superadmin->id,
        ]);
        $this->cleanDocumentIds[] = $docPublik->id;

        // 4. Test Superadmin Access
        $this->actingAs($superadmin);
        $superadminDocs = DocumentResource::getEloquentQuery()
            ->whereIn('id', [$docKepegawaian->id, $docKeuangan->id, $docPublik->id])
            ->pluck('id')->all();
        $this->assertCount(3, $superadminDocs);
        $this->assertTrue($superadmin->canAccessDocument($docKepegawaian));
        $this->assertTrue($superadmin->canAccessDocument($docKeuangan));
        $this->assertTrue($superadmin->canAccessDocument($docPublik));

        // 5. Test Kepegawaian User Access
        $this->actingAs($userKepegawaian);
        $kepegawaianDocs = DocumentResource::getEloquentQuery()
            ->whereIn('id', [$docKepegawaian->id, $docKeuangan->id, $docPublik->id])
            ->pluck('id')->all();
        $this->assertContains($docKepegawaian->id, $kepegawaianDocs);
        $this->assertContains($docPublik->id, $kepegawaianDocs);
        $this->assertNotContains($docKeuangan->id, $kepegawaianDocs); // Keuangan must NOT be visible!
        $this->assertTrue($userKepegawaian->canAccessDocument($docKepegawaian));
        $this->assertTrue($userKepegawaian->canAccessDocument($docPublik));
        $this->assertFalse($userKepegawaian->canAccessDocument($docKeuangan));

        // 6. Test Keuangan User Access
        $this->actingAs($userKeuangan);
        $keuanganDocs = DocumentResource::getEloquentQuery()
            ->whereIn('id', [$docKepegawaian->id, $docKeuangan->id, $docPublik->id])
            ->pluck('id')->all();
        $this->assertContains($docKeuangan->id, $keuanganDocs);
        $this->assertContains($docPublik->id, $keuanganDocs);
        $this->assertNotContains($docKepegawaian->id, $keuanganDocs); // Kepegawaian must NOT be visible!
        $this->assertTrue($userKeuangan->canAccessDocument($docKeuangan));
        $this->assertTrue($userKeuangan->canAccessDocument($docPublik));
        $this->assertFalse($userKeuangan->canAccessDocument($docKepegawaian));

        // 7. Test User Without Unit Access
        $this->actingAs($userBiasa);
        $biasaDocs = DocumentResource::getEloquentQuery()
            ->whereIn('id', [$docKepegawaian->id, $docKeuangan->id, $docPublik->id])
            ->pluck('id')->all();
        $this->assertNotContains($docKepegawaian->id, $biasaDocs);
        $this->assertNotContains($docKeuangan->id, $biasaDocs);
        $this->assertContains($docPublik->id, $biasaDocs); // only public
        $this->assertFalse($userBiasa->canAccessDocument($docKepegawaian));
        $this->assertFalse($userBiasa->canAccessDocument($docKeuangan));
        $this->assertTrue($userBiasa->canAccessDocument($docPublik));
    }

    public function test_ownership_upload_dropdown_scoping(): void
    {
        $ownerKepegawaian = Ownership::create(['name' => 'Unit Kepegawaian '.uniqid()]);
        $this->cleanOwnershipIds[] = $ownerKepegawaian->id;

        $ownerKeuangan = Ownership::create(['name' => 'Unit Keuangan '.uniqid()]);
        $this->cleanOwnershipIds[] = $ownerKeuangan->id;

        $userKepegawaian = User::create([
            'nama' => 'Staf Kepegawaian '.uniqid(),
            'email' => 'kepegawaian_'.uniqid().'@test.local',
            'role' => 6,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $userKepegawaian->id;
        $userKepegawaian->ownerships()->attach($ownerKepegawaian->id);

        $superadmin = User::create([
            'nama' => 'Superadmin '.uniqid(),
            'email' => 'superadmin_'.uniqid().'@test.local',
            'role' => 1,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $superadmin->id;

        // When userKepegawaian views schema, modifyQueryUsing scopes to only their ownership
        $this->actingAs($userKepegawaian);
        $query = Ownership::query();
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && ! $user->isArsipAdmin()) {
            $query->whereIn('id', $user->ownerships()->pluck('ownerships.id'));
        }
        $optionsUser = $query->pluck('id')->all();
        $this->assertContains($ownerKepegawaian->id, $optionsUser);
        $this->assertNotContains($ownerKeuangan->id, $optionsUser);

        // When superadmin views schema, all ownerships are available
        $this->actingAs($superadmin);
        $queryAdmin = Ownership::query();
        $adminUser = \Illuminate\Support\Facades\Auth::user();
        if ($adminUser && ! $adminUser->isArsipAdmin()) {
            $queryAdmin->whereIn('id', $adminUser->ownerships()->pluck('ownerships.id'));
        }
        $optionsAdmin = $queryAdmin->pluck('id')->all();
        $this->assertContains($ownerKepegawaian->id, $optionsAdmin);
        $this->assertContains($ownerKeuangan->id, $optionsAdmin);
    }

    public function test_http_endpoints_and_panel_authorization(): void
    {
        $superadmin = User::create([
            'nama' => 'Superadmin '.uniqid(),
            'email' => 'superadmin_http_'.uniqid().'@test.local',
            'role' => 1,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $superadmin->id;

        $userBiasa = User::create([
            'nama' => 'Staf Biasa '.uniqid(),
            'email' => 'staf_http_'.uniqid().'@test.local',
            'role' => 6,
            'nip' => '19900101'.rand(100000, 999999),
            'status_jabatan' => 'Staff',
            'is_aktif' => true,
        ]);
        $this->cleanUserIds[] = $userBiasa->id;

        // Superadmin: can access /arsip/ownership
        $responseAdmin = $this->actingAs($superadmin)->get('/arsip/ownership');
        $responseAdmin->assertSuccessful();

        // Non-admin: CANNOT access /arsip/ownership (403 Forbidden)
        $responseNonAdmin = $this->actingAs($userBiasa)->get('/arsip/ownership');
        $responseNonAdmin->assertForbidden();

        // Non-admin: CAN access /arsip/dokumen and /arsip/dokumen/create
        $responseDokumen = $this->actingAs($userBiasa)->get('/arsip/dokumen');
        $responseDokumen->assertSuccessful();

        $responseCreate = $this->actingAs($userBiasa)->get('/arsip/dokumen/create');
        $responseCreate->assertSuccessful();
    }
}

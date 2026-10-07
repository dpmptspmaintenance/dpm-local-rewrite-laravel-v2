<?php

namespace Tests\Feature;

use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource\Pages\CreateDrhSatyaLancana;
use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class DrhAtasanTest extends TestCase
{
    private ?int $id = null;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'dpmptsp_new',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::purge('kepegawaian');
        Filament::setCurrentPanel('kepegawaian');
    }

    protected function tearDown(): void
    {
        if ($this->id) {
            DrhSatyaLancana::query()->whereKey($this->id)->delete();
        }
        parent::tearDown();
    }

    public function test_pilih_atasan_auto_isi_dan_bisa_edit_manual(): void
    {
        $admin = User::query()->where('role', 1)->firstOrFail();
        $this->actingAs($admin);

        // Pilih atasan = pegawai nyata -> auto isi nama/nip dari profil.
        Livewire::test(CreateDrhSatyaLancana::class)
            ->fillForm(['nip' => '198111192000122001', 'nama' => 'RISTA AMELIA ADHI'])
            ->set('data.atasan_nip', '197201041992031004')
            ->assertFormSet(fn (array $state): bool => $state['ttd_kiri_nip'] === '197201041992031004')
            ->fillForm(['ttd_kiri_nama' => 'Pejabat Luar Daftar, MM', 'ttd_kiri_nip' => '999999999999999999'])
            ->call('create')
            ->assertHasNoFormErrors();

        $rec = DrhSatyaLancana::query()->where('nip', '198111192000122001')->latest('id')->first();
        $this->assertNotNull($rec);
        $this->id = $rec->id;
        $this->assertSame('Pejabat Luar Daftar, MM', $rec->ttd_kiri_nama);
        $this->assertSame('999999999999999999', $rec->ttd_kiri_nip);
    }
}

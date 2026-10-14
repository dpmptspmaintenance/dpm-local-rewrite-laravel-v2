<?php

namespace Tests\Feature;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages\ListPegawaiProfil;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PegawaiStatusFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'dpmptsp_new',
        ]);
        DB::purge('mysql');
        DB::purge('kepegawaian');
        Filament::setCurrentPanel('kepegawaian');
    }

    public function test_status_filter_from_query_string_limits_table(): void
    {
        $admin = User::query()->where('role', 1)->firstOrFail();
        $this->actingAs($admin);

        $expected = PegawaiProfil::query()
            ->where('status_pegawai', PegawaiProfil::STATUS_PNS)
            ->count();

        $this->assertGreaterThan(0, $expected);

        Livewire::withQueryParams([
            'filters' => [
                'status_pegawai' => ['value' => PegawaiProfil::STATUS_PNS],
            ],
        ])
            ->test(ListPegawaiProfil::class)
            ->assertCanSeeTableRecords(
                PegawaiProfil::query()
                    ->where('status_pegawai', PegawaiProfil::STATUS_PNS)
                    ->limit(5)
                    ->get()
            )
            ->assertCountTableRecords($expected)
            ->assertTableFilterExists('status_pegawai');
    }
}

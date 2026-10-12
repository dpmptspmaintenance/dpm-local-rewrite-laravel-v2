<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Models\Kepegawaian\PegawaiProfil;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Alert di beranda Kepegawaian: jadwal yang akan jatuh tempo segera —
 * KGB & (perkiraan) kenaikan pangkat 3 bulan mendatang, plus pensiun (BUP)
 * 1 tahun mendatang. Hanya PNS.
 *
 * Kenaikan pangkat reguler diperkirakan 4 tahun dari TMT golongan terakhir
 * (pegawai_profil.tmt_golongan) — data SISDM tak menyimpan "KP selanjutnya",
 * jadi ini estimasi, bukan tanggal terkonfirmasi. Baris yang estimasinya
 * sudah lewat ikut ditandai "terlambat" agar tak terlewat.
 */
class AlertMendatang extends Widget
{
    protected string $view = 'filament.kepegawaian.widgets.alert-mendatang';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    /** Jendela hari ke depan untuk KGB & KP. */
    private const HARI_KGB_KP = 90;

    /** Tahun kenaikan pangkat reguler dihitung dari TMT golongan terakhir. */
    private const TAHUN_KP_REGULER = 4;

    /**
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function getAlerts(): array
    {
        $pns = PegawaiProfil::query()
            ->where('status_pegawai', PegawaiProfil::STATUS_PNS)
            ->get(['nip', 'nama', 'jabatan', 'golongan', 'tmt_golongan', 'kgb_selanjutnya', 'tmt_bup_but']);

        $batasKgbKp = now()->addDays(self::HARI_KGB_KP);
        $batasPensiun = now()->addYear();

        $kgb = collect();
        $kp = collect();
        $pensiun = collect();

        foreach ($pns as $p) {
            if ($p->kgb_selanjutnya) {
                $tgl = Carbon::parse($p->kgb_selanjutnya);
                if ($tgl->lte($batasKgbKp) && $tgl->gte(now()->startOfDay())) {
                    $kgb->push($this->baris($p, $tgl));
                }
            }

            if ($p->tmt_golongan) {
                $est = Carbon::parse($p->tmt_golongan)->addYears(self::TAHUN_KP_REGULER);
                // Hanya estimasi dalam jendela ±3 bulan: yang akan datang
                // ATAU baru saja terlewat. Est yang jauh di masa lalu
                // (tmt_golongan basi / pegawai sudah di puncak) sengaja
                // dibuang supaya tak jadi alert lama yang bising.
                if ($est->lte($batasKgbKp) && $est->gte(now()->subDays(self::HARI_KGB_KP)->startOfDay())) {
                    $kp->push(array_merge($this->baris($p, $est), [
                        'terlambat' => $est->lt(now()->startOfDay()),
                    ]));
                }
            }

            if ($p->tmt_bup_but) {
                $tgl = Carbon::parse($p->tmt_bup_but);
                if ($tgl->lte($batasPensiun) && $tgl->gte(now()->startOfDay())) {
                    $pensiun->push($this->baris($p, $tgl));
                }
            }
        }

        return [
            'kgb' => $kgb->sortBy('tanggal')->values(),
            'kp' => $kp->sortBy('tanggal')->values(),
            'pensiun' => $pensiun->sortBy('tanggal')->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(PegawaiProfil $p, Carbon $tanggal): array
    {
        return [
            'nip' => $p->nip,
            'nama' => $p->nama ?? $p->nip,
            'jabatan' => $p->jabatan,
            'golongan' => $p->golongan,
            'tanggal' => $tanggal,
            'terlambat' => false,
        ];
    }

    /** Jumlah total alert (dipakai untuk badge tajuk). */
    public function total(): int
    {
        return collect($this->getAlerts())->sum(fn (Collection $c): int => $c->count());
    }
}

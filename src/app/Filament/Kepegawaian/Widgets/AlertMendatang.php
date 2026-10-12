<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Models\Kepegawaian\PegawaiProfil;
use App\Services\Kepegawaian\BatasKenaikanPangkat;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Alert di beranda Kepegawaian: jadwal yang akan jatuh tempo segera —
 * KGB 3 bulan mendatang, (perkiraan) kenaikan pangkat 6 bulan mendatang,
 * plus pensiun (BUP/BUT) 1 tahun mendatang.
 *
 * KGB & kenaikan pangkat hanya untuk PNS (PPPK tak punya KGB/pangkat).
 * Pensiun berlaku untuk SEMUA status ASN: PNS, PPPK, dan PPPK paruh waktu.
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

    /** Jendela hari ke depan untuk KGB. */
    private const HARI_KGB = 90;

    /** Jendela hari ke depan untuk kenaikan pangkat. */
    private const HARI_KP = 180;

    /** Tahun kenaikan pangkat reguler dihitung dari TMT golongan terakhir. */
    private const TAHUN_KP_REGULER = 4;

    /** Batas awal baris "terlambat" (tolak est yang jauh di masa lalu). */
    private const HARI_KP_TERLAMBAT = 90;

    /**
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function getAlerts(): array
    {
        $pns = PegawaiProfil::query()
            ->where('status_pegawai', PegawaiProfil::STATUS_PNS)
            ->with('riwayatJabatanTerakhir')
            ->get(['nip', 'nama', 'jabatan', 'golongan', 'pendidikan', 'status_pegawai', 'tmt_golongan', 'kgb_selanjutnya', 'tmt_bup_but']);

        $semuaAsn = PegawaiProfil::query()
            ->whereIn('status_pegawai', [PegawaiProfil::STATUS_PNS, ...PegawaiProfil::STATUS_NON_PNS])
            ->get(['nip', 'nama', 'jabatan', 'golongan', 'status_pegawai', 'tmt_bup_but']);

        $batasKgb = now()->addDays(self::HARI_KGB);
        $batasKp = now()->addDays(self::HARI_KP);
        $batasPensiun = now()->addYear();

        $kgb = collect();
        $kp = collect();
        $pensiun = collect();

        foreach ($pns as $p) {
            if ($p->kgb_selanjutnya) {
                $tgl = Carbon::parse($p->kgb_selanjutnya);
                if ($tgl->lte($batasKgb) && $tgl->gte(now()->startOfDay())) {
                    $kgb->push($this->baris($p, $tgl));
                }
            }

            // KP diabaikan bila golongan sudah menyentuh/melewati batas
            // maksimal menurut pendidikan + jalur karier (BatasKenaikanPangkat) —
            // tak ada lagi kenaikan pangkat yang mungkin, jadi tak perlu diingatkan.
            // Jalur (eselon/fungsional/pelaksana) dari jenis jabatan + nama jabatan terakhir.
            $jalur = BatasKenaikanPangkat::jalurDariJabatan(
                $p->riwayatJabatanTerakhir?->jenis_jabatan,
                $p->riwayatJabatanTerakhir?->jabatan_baru ?? $p->jabatan,
            );
            $batas = BatasKenaikanPangkat::evaluasi($p->pendidikan, $p->golongan, $jalur);

            if ($p->tmt_golongan && ! in_array($batas['status'], ['maks', 'lewat'], true)) {
                $est = Carbon::parse($p->tmt_golongan)->addYears(self::TAHUN_KP_REGULER);
                // Hanya estimasi dalam jendela ±: yang akan datang (<=6 bulan)
                // ATAU baru saja terlewat (<=90 hari). Est yang jauh di masa
                // lalu (tmt_golongan basi / pegawai sudah di puncak) sengaja
                // dibuang supaya tak jadi alert lama yang bising.
                if ($est->lte($batasKp) && $est->gte(now()->subDays(self::HARI_KP_TERLAMBAT)->startOfDay())) {
                    $kp->push(array_merge($this->baris($p, $est), [
                        'terlambat' => $est->lt(now()->startOfDay()),
                    ]));
                }
            }
        }

        // Pensiun/BUP berlaku untuk semua ASN (PNS + PPPK + PPPK paruh waktu).
        foreach ($semuaAsn as $p) {
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
            'status_pegawai' => $p->status_pegawai,
            'status_short' => $this->statusShort($p->status_pegawai),
            'tanggal' => $tanggal,
            'terlambat' => false,
        ];
    }

    /** Label singkat status pegawai untuk badge di daftar alert. */
    private function statusShort(?string $status): ?string
    {
        return match ($status) {
            PegawaiProfil::STATUS_PNS => 'PNS',
            PegawaiProfil::STATUS_PPPK => 'PPPK',
            PegawaiProfil::STATUS_PPPK_PARUH_WAKTU => 'PPPK PW',
            default => $status,
        };
    }

    /** Jumlah total alert (dipakai untuk badge tajuk). */
    public function total(): int
    {
        return collect($this->getAlerts())->sum(fn (Collection $c): int => $c->count());
    }
}

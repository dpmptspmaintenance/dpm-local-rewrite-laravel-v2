<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rekap absensi bulanan per pegawai (satu baris = satu pegawai + satu bulan).
 * Ditulis hanya lewat API `/api/v1/rekap-absen` (scope `rekap-absen`);
 * unggahan ulang dengan nip+tahun+bulan sama mengganti isinya.
 */
class RekapAbsen extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'rekap_absen';

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public const BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'hari_kerja' => 'integer',
            'jumlah_hadir' => 'integer',
            'jumlah_tidak_hadir' => 'integer',
            'jumlah_konfirmasi' => 'integer',
            'diunggah_at' => 'datetime',
        ];
    }

    public function harian(): HasMany
    {
        return $this->hasMany(RekapAbsenHarian::class, 'rekap_absen_id')->orderBy('urutan');
    }

    public function dataDukung(): HasMany
    {
        return $this->hasMany(RekapAbsenDataDukung::class, 'rekap_absen_id')->orderBy('urutan');
    }

    /**
     * Data dukung TPP untuk pegawai + periode rekap ini (relasi komposit
     * nip+tahun+bulan, bukan FK).
     */
    public function tpp(): HasMany
    {
        return $this->hasMany(RekapAbsenTpp::class, 'nip', 'nip')
            ->where('rekap_absen_tpp.tahun', $this->tahun)
            ->where('rekap_absen_tpp.bulan', $this->bulan);
    }

    /**
     * Baris harian berstatus konfirmasi yang tanggalnya TIDAK tercakup
     * rentang data dukung mana pun. Dipakai kolom "Belum Konfirmasi".
     */
    public function harianKonfirmasiTanpaDukung(): HasMany
    {
        $dukungTable = (new RekapAbsenDataDukung)->getTable();
        $rekapTable = (new RekapAbsen)->getTable();
        $tppTable = (new RekapAbsenTpp)->getTable();
        $tppTanggalTable = (new RekapAbsenTppTanggal)->getTable();

        return $this->harian()
            ->whereNotNull('rekap_absen_harian.status')
            ->whereNotExists(function ($query) use ($dukungTable): void {
                $query->selectRaw('1')
                    ->from($dukungTable.' as dd')
                    ->whereColumn('dd.rekap_absen_id', 'rekap_absen_harian.rekap_absen_id')
                    ->whereNotNull('dd.tanggal_mulai')
                    ->whereNotNull('dd.tanggal_selesai')
                    ->whereColumn('rekap_absen_harian.tanggal', '>=', 'dd.tanggal_mulai')
                    ->whereColumn('rekap_absen_harian.tanggal', '<=', 'dd.tanggal_selesai');
            })
            ->whereNotExists(function ($query) use ($rekapTable, $tppTable, $tppTanggalTable): void {
                $query->selectRaw('1')
                    ->from($tppTanggalTable.' as tt')
                    ->join($tppTable.' as tpp', 'tpp.id', '=', 'tt.rekap_absen_tpp_id')
                    ->join($rekapTable.' as r', function ($join): void {
                        $join->on('r.nip', '=', 'tpp.nip')
                            ->on('r.tahun', '=', 'tpp.tahun')
                            ->on('r.bulan', '=', 'tpp.bulan');
                    })
                    ->whereColumn('r.id', 'rekap_absen_harian.rekap_absen_id')
                    ->whereColumn('tt.tanggal', 'rekap_absen_harian.tanggal')
                    ->where('tpp.file_pendukung_status', RekapAbsenTpp::FILE_ADA);
            });
    }

    public function labelPeriode(): string
    {
        return (self::BULAN[$this->bulan] ?? 'Bulan '.$this->bulan).' '.$this->tahun;
    }

    /**
     * Terima angka (10), teks angka ("10"), nama bulan ("Oktober"), atau
     * gabungan ("Oktober 2026"). Null bila tidak dikenali.
     */
    public static function bulanAngka(mixed $nilai): ?int
    {
        if (is_int($nilai) || is_float($nilai)) {
            $angka = (int) $nilai;

            return $angka >= 1 && $angka <= 12 ? $angka : null;
        }

        if (! is_string($nilai)) {
            return null;
        }

        $teks = strtolower(trim($nilai));

        if ($teks === '') {
            return null;
        }

        if (ctype_digit($teks)) {
            $angka = (int) $teks;

            return $angka >= 1 && $angka <= 12 ? $angka : null;
        }

        // "Oktober 2026" / "okt" / "Oktober" → ambil kata pertama.
        $kata = preg_split('/[\s,\/]+/', $teks)[0] ?? $teks;

        foreach (self::BULAN as $angka => $nama) {
            $namaKecil = strtolower($nama);

            if ($kata === $namaKecil || (mb_strlen($kata) >= 3 && str_starts_with($namaKecil, $kata))) {
                return $angka;
            }
        }

        return null;
    }
}

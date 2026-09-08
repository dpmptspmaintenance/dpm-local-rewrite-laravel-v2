<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewOssController extends Controller
{
    private const TABLE = '2023_dp_proyek';

    private const MONTHS = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];

    // Klasifikasi KBLI 1-huruf berdasarkan rentang 2 digit kode KBLI (statis, bukan input user).
    private const CASE_KATEGORI_KBLI = <<<'SQL'
        CASE
            WHEN LEFT(kbli, 2) BETWEEN '01' AND '03' THEN 'A. Pertanian, Kehutanan dan Perikanan'
            WHEN LEFT(kbli, 2) BETWEEN '05' AND '09' THEN 'B. Pertambangan dan Penggalian'
            WHEN LEFT(kbli, 2) BETWEEN '10' AND '33' THEN 'C. Industri Pengolahan'
            WHEN LEFT(kbli, 2) = '35' THEN 'D. Pengadaan Listrik, Gas, Uap/Air Panas'
            WHEN LEFT(kbli, 2) BETWEEN '36' AND '39' THEN 'E. Treatment Air, Limbah, dan Sampah'
            WHEN LEFT(kbli, 2) BETWEEN '41' AND '43' THEN 'F. Konstruksi'
            WHEN LEFT(kbli, 2) BETWEEN '45' AND '47' THEN 'G. Perdagangan Besar dan Eceran'
            WHEN LEFT(kbli, 2) BETWEEN '49' AND '53' THEN 'H. Pengangkutan dan Pergudangan'
            WHEN LEFT(kbli, 2) BETWEEN '55' AND '56' THEN 'I. Penyediaan Akomodasi dan Makan Minum'
            WHEN LEFT(kbli, 2) BETWEEN '58' AND '63' THEN 'J. Informasi dan Komunikasi'
            WHEN LEFT(kbli, 2) BETWEEN '64' AND '66' THEN 'K. Aktivitas Keuangan dan Asuransi'
            WHEN LEFT(kbli, 2) = '68' THEN 'L. Real Estat'
            WHEN LEFT(kbli, 2) BETWEEN '69' AND '75' THEN 'M. Aktivitas Profesional, Ilmiah dan Teknis'
            WHEN LEFT(kbli, 2) BETWEEN '77' AND '82' THEN 'N. Aktivitas Penyewaan dan Sewa Guna'
            WHEN LEFT(kbli, 2) = '84' THEN 'O. Administrasi Pemerintahan'
            WHEN LEFT(kbli, 2) = '85' THEN 'P. Pendidikan'
            WHEN LEFT(kbli, 2) BETWEEN '86' AND '88' THEN 'Q. Aktivitas Kesehatan Manusia'
            WHEN LEFT(kbli, 2) BETWEEN '90' AND '93' THEN 'R. Kesenian, Hiburan dan Rekreasi'
            WHEN LEFT(kbli, 2) BETWEEN '94' AND '96' THEN 'S. Aktivitas Jasa Lainnya'
            WHEN LEFT(kbli, 2) BETWEEN '97' AND '98' THEN 'T. Aktivitas Rumah Tangga'
            WHEN LEFT(kbli, 2) = '99' THEN 'U. Aktivitas Badan Internasional'
            ELSE 'X. Tidak Terdefinisi'
        END
        SQL;

    private const LIST_KLASIFIKASI = [
        'A. Pertanian, Kehutanan dan Perikanan',
        'B. Pertambangan dan Penggalian',
        'C. Industri Pengolahan',
        'D. Pengadaan Listrik, Gas, Uap/Air Panas',
        'E. Treatment Air, Limbah, dan Sampah',
        'F. Konstruksi',
        'G. Perdagangan Besar dan Eceran',
        'H. Pengangkutan dan Pergudangan',
        'I. Penyediaan Akomodasi dan Makan Minum',
        'J. Informasi dan Komunikasi',
        'K. Aktivitas Keuangan dan Asuransi',
        'L. Real Estat',
        'M. Aktivitas Profesional, Ilmiah dan Teknis',
        'N. Aktivitas Penyewaan dan Sewa Guna',
        'O. Administrasi Pemerintahan',
        'P. Pendidikan',
        'Q. Aktivitas Kesehatan Manusia',
        'R. Kesenian, Hiburan dan Rekreasi',
        'S. Aktivitas Jasa Lainnya',
        'T. Aktivitas Rumah Tangga',
        'U. Aktivitas Badan Internasional',
        'X. Tidak Terdefinisi',
    ];

    // Mapping kode KBLI 2 digit -> judul resmi BPS.
    private const KBLI_MAP = [
        '01' => 'Pertanian Tanaman', '02' => 'Kehutanan dan Penebangan Kayu', '03' => 'Perikanan',
        '05' => 'Pertambangan Batubara dan Lignit', '06' => 'Pertambangan Minyak Bumi dan Gas Alam',
        '07' => 'Pertambangan Bijih Logam', '08' => 'Pertambangan dan Penggalian Lainnya',
        '09' => 'Aktivitas Penunjang Pertambangan', '10' => 'Industri Makanan', '11' => 'Industri Minuman',
        '12' => 'Industri Pengolahan Tembakau', '13' => 'Industri Tekstil', '14' => 'Industri Pakaian Jadi',
        '15' => 'Industri Kulit, Barang dari Kulit dan Alas Kaki', '16' => 'Industri Kayu, Gabus dan Anyaman',
        '17' => 'Industri Kertas dan Barang dari Kertas',
        '18' => 'Industri Percetakan dan Reproduksi Media Rekaman',
        '19' => 'Industri Produk dari Batu Bara dan Pengilangan Minyak Bumi',
        '20' => 'Industri Bahan Kimia dan Barang dari Bahan Kimia',
        '21' => 'Industri Farmasi, Produk Obat Kimia dan Obat Tradisional',
        '22' => 'Industri Karet, Barang dari Karet dan Plastik', '23' => 'Industri Barang Galian Bukan Logam',
        '24' => 'Industri Logam Dasar', '25' => 'Industri Barang dari Logam, Bukan Mesin dan Peralatannya',
        '26' => 'Industri Komputer, Barang Elektronik dan Optik', '27' => 'Industri Peralatan Listrik',
        '28' => 'Industri Mesin dan Perlengkapan',
        '29' => 'Industri Kendaraan Bermotor, Trailer dan Semi Trailer',
        '30' => 'Industri Alat Angkutan Lainnya', '31' => 'Industri Furnitur',
        '32' => 'Industri Pengolahan Lainnya', '33' => 'Reparasi dan Pemasangan Mesin dan Peralatan',
        '35' => 'Pengadaan Listrik, Gas, Uap/Air Panas dan Udara Dingin', '36' => 'Pengelolaan Air',
        '37' => 'Pengelolaan Air Limbah', '38' => 'Pengelolaan dan Pembuangan Sampah',
        '39' => 'Aktivitas Remediasi dan Pengelolaan Limbah Lainnya', '41' => 'Konstruksi Gedung',
        '42' => 'Konstruksi Bangunan Sipil', '43' => 'Konstruksi Khusus',
        '45' => 'Perdagangan, Reparasi dan Perawatan Mobil dan Sepeda Motor',
        '46' => 'Perdagangan Besar, Bukan Mobil dan Sepeda Motor',
        '47' => 'Perdagangan Eceran, Bukan Mobil dan Sepeda Motor',
        '49' => 'Angkutan Darat dan Angkutan Melalui Saluran Pipa', '50' => 'Angkutan Air',
        '51' => 'Angkutan Udara', '52' => 'Pergudangan dan Jasa Penunjang Angkutan',
        '53' => 'Aktivitas Pos dan Kurir', '55' => 'Penyediaan Akomodasi',
        '56' => 'Penyediaan Makanan dan Minuman', '58' => 'Penerbitan',
        '59' => 'Produksi Gambar Bergerak, Video dan Program Televisi',
        '60' => 'Penyiaran dan Pemrograman', '61' => 'Telekomunikasi',
        '62' => 'Aktivitas Pemrograman, Konsultasi Komputer dan Kegiatan ITES',
        '63' => 'Aktivitas Jasa Informasi',
        '64' => 'Aktivitas Jasa Keuangan, Bukan Asuransi dan Dana Pensiun',
        '65' => 'Asuransi, Reasuransi dan Dana Pensiun', '66' => 'Aktivitas Penunjang Jasa Keuangan',
        '68' => 'Real Estat', '69' => 'Aktivitas Hukum dan Akuntansi',
        '70' => 'Aktivitas Kantor Pusat dan Konsultasi Manajemen',
        '71' => 'Aktivitas Arsitektur dan Keinsinyuran',
        '72' => 'Penelitian dan Pengembangan Ilmu Pengetahuan', '73' => 'Periklanan dan Riset Pasar',
        '74' => 'Aktivitas Profesional, Ilmiah dan Teknis Lainnya', '75' => 'Aktivitas Kesehatan Hewan',
        '77' => 'Aktivitas Penyewaan dan Sewa Guna Usaha Tanpa Hak Opsi',
        '78' => 'Aktivitas Ketenagakerjaan',
        '79' => 'Aktivitas Agen Perjalanan, Penyelenggara Tur dan Jasa Reservasi',
        '80' => 'Aktivitas Keamanan dan Penyelidikan', '81' => 'Aktivitas Jasa Bangunan dan Pertamanan',
        '82' => 'Aktivitas Administrasi Kantor dan Penunjang Usaha',
        '84' => 'Administrasi Pemerintahan, Pertahanan dan Jaminan Sosial', '85' => 'Pendidikan',
        '86' => 'Aktivitas Kesehatan Manusia', '87' => 'Aktivitas Perawatan dengan Penginapan',
        '88' => 'Aktivitas Sosial Tanpa Akomodasi', '90' => 'Aktivitas Hiburan, Kesenian dan Kreativitas',
        '91' => 'Aktivitas Perpustakaan, Arsip, Museum dan Kebudayaan',
        '92' => 'Aktivitas Perjudian dan Pertaruhan', '93' => 'Aktivitas Olahraga dan Rekreasi Lainnya',
        '94' => 'Aktivitas Keanggotaan Organisasi',
        '95' => 'Reparasi Komputer dan Barang Keperluan Pribadi dan Perlengkapan Rumah Tangga',
        '96' => 'Aktivitas Jasa Perorangan Lainnya', '97' => 'Aktivitas Rumah Tangga sebagai Pemberi Kerja',
        '98' => 'Aktivitas yang Menghasilkan Barang dan Jasa Oleh Rumah Tangga',
        '99' => 'Aktivitas Badan Internasional dan Badan Ekstra Internasional Lainnya',
    ];

    // Mapping kode KBLI 2 digit -> kode kategori 1 huruf (A-U).
    private const KBLI_KATEGORI_MAP = [
        '01' => 'A', '02' => 'A', '03' => 'A', '05' => 'B', '06' => 'B', '07' => 'B', '08' => 'B', '09' => 'B',
        '10' => 'C', '11' => 'C', '12' => 'C', '13' => 'C', '14' => 'C', '15' => 'C', '16' => 'C', '17' => 'C',
        '18' => 'C', '19' => 'C', '20' => 'C', '21' => 'C', '22' => 'C', '23' => 'C', '24' => 'C', '25' => 'C',
        '26' => 'C', '27' => 'C', '28' => 'C', '29' => 'C', '30' => 'C', '31' => 'C', '32' => 'C', '33' => 'C',
        '35' => 'D',
        '36' => 'E', '37' => 'E', '38' => 'E', '39' => 'E',
        '41' => 'F', '42' => 'F', '43' => 'F',
        '45' => 'G', '46' => 'G', '47' => 'G',
        '49' => 'H', '50' => 'H', '51' => 'H', '52' => 'H', '53' => 'H',
        '55' => 'I', '56' => 'I',
        '58' => 'J', '59' => 'J', '60' => 'J', '61' => 'J', '62' => 'J', '63' => 'J',
        '64' => 'K', '65' => 'K', '66' => 'K',
        '68' => 'L',
        '69' => 'M', '70' => 'M', '71' => 'M', '72' => 'M', '73' => 'M', '74' => 'M', '75' => 'M',
        '77' => 'N', '78' => 'N', '79' => 'N', '80' => 'N', '81' => 'N', '82' => 'N',
        '84' => 'O',
        '85' => 'P',
        '86' => 'Q', '87' => 'Q', '88' => 'Q',
        '90' => 'R', '91' => 'R', '92' => 'R', '93' => 'R',
        '94' => 'S', '95' => 'S', '96' => 'S',
        '97' => 'T', '98' => 'T',
        '99' => 'U',
    ];

    private const KATEGORI_NAMA_MAP = [
        'A' => 'Pertanian, Kehutanan dan Perikanan',
        'B' => 'Pertambangan dan Penggalian',
        'C' => 'Industri Pengolahan',
        'D' => 'Pengadaan Listrik, Gas, Uap/Air Panas dan Udara Dingin',
        'E' => 'Treatment Air, Limbah, dan Sampah',
        'F' => 'Konstruksi',
        'G' => 'Perdagangan Besar dan Eceran',
        'H' => 'Pengangkutan dan Pergudangan',
        'I' => 'Penyediaan Akomodasi dan Makan Minum',
        'J' => 'Informasi dan Komunikasi',
        'K' => 'Aktivitas Keuangan dan Asuransi',
        'L' => 'Real Estat',
        'M' => 'Aktivitas Profesional, Ilmiah dan Teknis',
        'N' => 'Aktivitas Penyewaan dan Sewa Guna Usaha',
        'O' => 'Administrasi Pemerintahan, Pertahanan dan Jaminan Sosial',
        'P' => 'Pendidikan',
        'Q' => 'Aktivitas Kesehatan Manusia dan Aktivitas Sosial',
        'R' => 'Kesenian, Hiburan dan Rekreasi',
        'S' => 'Aktivitas Jasa Lainnya',
        'T' => 'Aktivitas Rumah Tangga',
        'U' => 'Aktivitas Badan Internasional',
        'X' => 'Tidak Terdefinisi',
    ];

    // ---------------------------------------------------------------
    // 1. Overview OSS per Kecamatan
    // ---------------------------------------------------------------

    public function perKecamatan(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, true);

        $rows = $this->perKecamatanQuery($tahun, $bulan)->get();

        return view('datakita.overview.oss-per-kecamatan.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'months' => self::MONTHS,
            'grandProyek' => $rows->sum('jumlah_proyek'),
            'grandInvestasi' => $rows->sum('jumlah_investasi'),
        ]);
    }

    public function perKecamatanExport(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, false);

        $rows = $this->perKecamatanQuery($tahun, $bulan)->get();

        $filename = 'Overview_Investasi_'.$tahun.'_'.($bulan ?: 'Full_Tahun').'.xls';

        return response()->streamDownload(function () use ($rows, $tahun, $bulan) {
            echo view('datakita.overview.oss-per-kecamatan.export', [
                'rows' => $rows,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'months' => self::MONTHS,
                'grandProyek' => $rows->sum('jumlah_proyek'),
                'grandInvestasi' => $rows->sum('jumlah_investasi'),
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function perKecamatanQuery(string $tahun, string $bulan)
    {
        $query = DB::table(self::TABLE)
            ->selectRaw("COALESCE(NULLIF(TRIM(kecamatan_usaha), ''), 'Kecamatan Tidak Terdefinisi') as kecamatan_usaha, COUNT(id) as jumlah_proyek, SUM(jumlah_investasi3) as jumlah_investasi")
            ->whereRaw('YEAR(day_of_tanggal_pengajuan_proyek) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(day_of_tanggal_pengajuan_proyek) = ?', [$bulan]);
        }

        return $query
            ->groupByRaw("COALESCE(NULLIF(TRIM(kecamatan_usaha), ''), 'Kecamatan Tidak Terdefinisi')")
            ->orderBy('kecamatan_usaha');
    }

    // ---------------------------------------------------------------
    // 2. Overview OSS per Kecamatan & KBLI (klasifikasi 1 huruf)
    // ---------------------------------------------------------------

    public function perKecamatanKbli(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, true);
        $kecamatan = (string) $request->input('kecamatan', '');

        $rows = $this->perKecamatanKbliQuery($tahun, $bulan, $kecamatan)->get();

        return view('datakita.overview.oss-per-kecamatan-kbli.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'kecamatan' => $kecamatan,
            'months' => self::MONTHS,
            'kecamatanList' => $this->kecamatanList(),
            'grandProyek' => $rows->sum('jumlah_proyek'),
            'grandInvestasi' => $rows->sum('jumlah_investasi'),
        ]);
    }

    public function perKecamatanKbliExport(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, false);
        $kecamatan = (string) $request->input('kecamatan', '');

        $rows = $this->perKecamatanKbliQuery($tahun, $bulan, $kecamatan)->get();

        $filename = 'Data_Investasi_'.$tahun.($bulan ? '_Bln'.$bulan : '').($kecamatan !== '' ? '_'.preg_replace('/\s+/', '_', $kecamatan) : '_Semua_Kecamatan').'.xls';

        return response()->streamDownload(function () use ($rows, $tahun, $bulan, $kecamatan) {
            echo view('datakita.overview.oss-per-kecamatan-kbli.export', [
                'rows' => $rows,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'kecamatan' => $kecamatan,
                'months' => self::MONTHS,
                'grandProyek' => $rows->sum('jumlah_proyek'),
                'grandInvestasi' => $rows->sum('jumlah_investasi'),
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function perKecamatanKbliQuery(string $tahun, string $bulan, string $kecamatan)
    {
        $query = DB::table(self::TABLE)
            ->selectRaw(self::CASE_KATEGORI_KBLI.' as kategori_kbli, COUNT(id) as jumlah_proyek, SUM(jumlah_investasi3) as jumlah_investasi')
            ->whereRaw('YEAR(day_of_tanggal_pengajuan_proyek) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(day_of_tanggal_pengajuan_proyek) = ?', [$bulan]);
        }

        if ($kecamatan !== '') {
            $query->where('kecamatan_usaha', $kecamatan);
        }

        return $query->groupBy('kategori_kbli')->orderBy('kategori_kbli');
    }

    // ---------------------------------------------------------------
    // 3. Overview OSS per Kecamatan & KBLI Kelas 1 (2 digit)
    // ---------------------------------------------------------------

    public function perKecamatanKbliKelas1(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, true);
        $kecamatan = (string) $request->input('kecamatan', '');

        [$rows, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap] = $this->perKecamatanKbliKelas1Data($tahun, $bulan, $kecamatan);

        return view('datakita.overview.oss-per-kecamatan-kbli-kelas1.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'kecamatan' => $kecamatan,
            'months' => self::MONTHS,
            'kecamatanList' => $this->kecamatanList(),
            'grandProyek' => $grandProyek,
            'grandInvestasi' => $grandInvestasi,
            'rowspanMap' => $rowspanMap,
            'firstIdxMap' => $firstIdxMap,
        ]);
    }

    public function perKecamatanKbliKelas1Export(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, false);
        $kecamatan = (string) $request->input('kecamatan', '');

        [$rows, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap] = $this->perKecamatanKbliKelas1Data($tahun, $bulan, $kecamatan);

        $filename = 'Data_Investasi_KBLI2Digit_'.$tahun.($bulan ? '_Bln'.$bulan : '').($kecamatan !== '' ? '_'.preg_replace('/\s+/', '_', $kecamatan) : '_Semua_Kecamatan').'.xls';

        return response()->streamDownload(function () use ($rows, $tahun, $bulan, $kecamatan, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap) {
            echo view('datakita.overview.oss-per-kecamatan-kbli-kelas1.export', [
                'rows' => $rows,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'kecamatan' => $kecamatan,
                'months' => self::MONTHS,
                'grandProyek' => $grandProyek,
                'grandInvestasi' => $grandInvestasi,
                'rowspanMap' => $rowspanMap,
                'firstIdxMap' => $firstIdxMap,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function perKecamatanKbliKelas1Data(string $tahun, string $bulan, string $kecamatan): array
    {
        $query = DB::table(self::TABLE)
            ->selectRaw('LEFT(kbli, 2) as kode_kbli, COUNT(id) as jumlah_proyek, SUM(jumlah_investasi3) as jumlah_investasi')
            ->whereRaw('YEAR(day_of_tanggal_pengajuan_proyek) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(day_of_tanggal_pengajuan_proyek) = ?', [$bulan]);
        }

        if ($kecamatan !== '') {
            $query->where('kecamatan_usaha', $kecamatan);
        }

        $rows = $query->groupBy('kode_kbli')->orderBy('kode_kbli')->get();

        $grandProyek = 0;
        $grandInvestasi = 0;
        $rowspanMap = [];
        $firstIdxMap = [];

        $rows = $rows->values()->map(function ($row, $i) use (&$grandProyek, &$grandInvestasi, &$rowspanMap, &$firstIdxMap) {
            $kode2 = str_pad((string) $row->kode_kbli, 2, '0', STR_PAD_LEFT);
            $row->kode_kbli = $kode2;
            $row->judul_kbli = self::KBLI_MAP[$kode2] ?? 'Kode KBLI Tidak Terdefinisi';
            $row->kat_kode = self::KBLI_KATEGORI_MAP[$kode2] ?? 'X';
            $row->kat_nama = self::KATEGORI_NAMA_MAP[$row->kat_kode] ?? 'Tidak Terdefinisi';

            $grandProyek += $row->jumlah_proyek;
            $grandInvestasi += $row->jumlah_investasi;

            if (! isset($rowspanMap[$row->kat_kode])) {
                $rowspanMap[$row->kat_kode] = 0;
                $firstIdxMap[$row->kat_kode] = $i;
            }
            $rowspanMap[$row->kat_kode]++;

            return $row;
        });

        return [$rows, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap];
    }

    /** Format persentase dengan desimal adaptif (min 2, naik jika terlalu kecil), seperti fmt_persen() legacy. */
    public static function formatPersen(float $val): string
    {
        if ($val == 0) {
            return '0,00%';
        }
        for ($dec = 2; $dec <= 6; $dec++) {
            $formatted = number_format($val, $dec, ',', '.');
            $afterComma = str_replace('.', '', substr(strrchr($formatted, ','), 1));
            if ((int) $afterComma > 0) {
                return $formatted.'%';
            }
        }

        return rtrim(number_format($val, 6, ',', '.'), '0').'%';
    }

    // ---------------------------------------------------------------
    // 4. Overview OSS per Kecamatan & KBLI Kelas 2 (3 digit)
    // ---------------------------------------------------------------

    public function perKecamatanKbliKelas2(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, true);
        $kecamatan = (string) $request->input('kecamatan', '');

        [$rows, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap] = $this->perKecamatanKbliKelas2Data($tahun, $bulan, $kecamatan);

        return view('datakita.overview.oss-per-kecamatan-kbli-kelas2.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'kecamatan' => $kecamatan,
            'months' => self::MONTHS,
            'kecamatanList' => $this->kecamatanList(),
            'grandProyek' => $grandProyek,
            'grandInvestasi' => $grandInvestasi,
            'rowspanMap' => $rowspanMap,
            'firstIdxMap' => $firstIdxMap,
        ]);
    }

    public function perKecamatanKbliKelas2Export(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, false);
        $kecamatan = (string) $request->input('kecamatan', '');

        [$rows, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap] = $this->perKecamatanKbliKelas2Data($tahun, $bulan, $kecamatan);

        $filename = 'Investasi_KBLI_3Digit_'.$tahun.'_'.date('His').'.xls';

        return response()->streamDownload(function () use ($rows, $tahun, $bulan, $kecamatan, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap) {
            echo view('datakita.overview.oss-per-kecamatan-kbli-kelas2.export', [
                'rows' => $rows,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'kecamatan' => $kecamatan,
                'months' => self::MONTHS,
                'grandProyek' => $grandProyek,
                'grandInvestasi' => $grandInvestasi,
                'rowspanMap' => $rowspanMap,
                'firstIdxMap' => $firstIdxMap,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function perKecamatanKbliKelas2Data(string $tahun, string $bulan, string $kecamatan): array
    {
        $query = DB::table(self::TABLE)
            ->selectRaw('LEFT(kbli, 3) as kode_kbli, MAX(judul_kbli) as nama_kbli, COUNT(id) as jumlah_proyek, SUM(jumlah_investasi3) as jumlah_investasi')
            ->whereRaw('YEAR(day_of_tanggal_pengajuan_proyek) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(day_of_tanggal_pengajuan_proyek) = ?', [$bulan]);
        }

        if ($kecamatan !== '') {
            $query->where('kecamatan_usaha', $kecamatan);
        }

        $rows = $query->groupBy('kode_kbli')->orderBy('kode_kbli')->get();

        $grandProyek = 0;
        $grandInvestasi = 0;
        $rowspanMap = [];
        $firstIdxMap = [];

        $rows = $rows->values()->map(function ($row, $i) use (&$grandProyek, &$grandInvestasi, &$rowspanMap, &$firstIdxMap) {
            $kat = $this->kategoriDari3Digit((string) $row->kode_kbli);
            $row->kat_kode = $kat;
            $row->kat_nama = self::KATEGORI_NAMA_MAP[$kat] ?? 'Lainnya';

            $grandProyek += $row->jumlah_proyek;
            $grandInvestasi += $row->jumlah_investasi;

            if (! isset($rowspanMap[$kat])) {
                $rowspanMap[$kat] = 0;
                $firstIdxMap[$kat] = $i;
            }
            $rowspanMap[$kat]++;

            return $row;
        });

        return [$rows, $grandProyek, $grandInvestasi, $rowspanMap, $firstIdxMap];
    }

    /** Tentukan kode kategori (A-U) dari 2 digit pertama kode KBLI 3 digit, seperti get_kat_kode() legacy. */
    private function kategoriDari3Digit(string $kode3): string
    {
        $d2 = substr($kode3, 0, 2);
        if ($d2 <= '03') return 'A';
        if ($d2 <= '09') return 'B';
        if ($d2 <= '33') return 'C';
        if ($d2 == '35') return 'D';
        if ($d2 <= '39') return 'E';
        if ($d2 <= '43') return 'F';
        if ($d2 <= '47') return 'G';
        if ($d2 <= '53') return 'H';
        if ($d2 <= '56') return 'I';
        if ($d2 <= '63') return 'J';
        if ($d2 <= '66') return 'K';
        if ($d2 == '68') return 'L';
        if ($d2 <= '75') return 'M';
        if ($d2 <= '82') return 'N';
        if ($d2 == '84') return 'O';
        if ($d2 == '85') return 'P';
        if ($d2 <= '88') return 'Q';
        if ($d2 <= '93') return 'R';
        if ($d2 <= '96') return 'S';
        if ($d2 <= '98') return 'T';

        return 'U';
    }

    // ---------------------------------------------------------------
    // 5. Overview OSS per Klasifikasi KBLI
    // ---------------------------------------------------------------

    public function perKlasifikasi(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, true);
        $klasifikasi = (string) $request->input('klasifikasi', '');

        $totalSemuaInvestasi = $this->totalInvestasi($tahun, $bulan);
        $rows = $this->perKlasifikasiQuery($tahun, $bulan, $klasifikasi)->get();

        return view('datakita.overview.oss-per-klasifikasi.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'klasifikasi' => $klasifikasi,
            'months' => self::MONTHS,
            'listKlasifikasi' => self::LIST_KLASIFIKASI,
            'totalSemuaInvestasi' => $totalSemuaInvestasi,
            'grandProyek' => $rows->sum('jumlah_proyek'),
            'grandInvestasi' => $rows->sum('jumlah_investasi'),
        ]);
    }

    public function perKlasifikasiExport(Request $request)
    {
        [$tahun, $bulan] = $this->tahunBulan($request, false);
        $klasifikasi = (string) $request->input('klasifikasi', '');

        $totalSemuaInvestasi = $this->totalInvestasi($tahun, $bulan);
        $rows = $this->perKlasifikasiQuery($tahun, $bulan, $klasifikasi)->get();

        $filename = 'Investasi_Per_Klasifikasi_'.$tahun.'_'.($bulan ?: 'Full').'.xls';

        return response()->streamDownload(function () use ($rows, $tahun, $bulan, $klasifikasi, $totalSemuaInvestasi) {
            echo view('datakita.overview.oss-per-klasifikasi.export', [
                'rows' => $rows,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'klasifikasi' => $klasifikasi,
                'months' => self::MONTHS,
                'totalSemuaInvestasi' => $totalSemuaInvestasi,
                'grandProyek' => $rows->sum('jumlah_proyek'),
                'grandInvestasi' => $rows->sum('jumlah_investasi'),
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function perKlasifikasiQuery(string $tahun, string $bulan, string $klasifikasi)
    {
        $query = DB::table(self::TABLE)
            ->selectRaw(self::CASE_KATEGORI_KBLI.' as kategori_kbli, COUNT(id) as jumlah_proyek, SUM(jumlah_investasi3) as jumlah_investasi')
            ->whereRaw('YEAR(day_of_tanggal_pengajuan_proyek) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(day_of_tanggal_pengajuan_proyek) = ?', [$bulan]);
        }

        $query->groupBy('kategori_kbli');

        if ($klasifikasi !== '') {
            $query->havingRaw('kategori_kbli = ?', [$klasifikasi]);
        }

        return $query->orderBy('kategori_kbli');
    }

    private function totalInvestasi(string $tahun, string $bulan): float
    {
        $query = DB::table(self::TABLE)->whereRaw('YEAR(day_of_tanggal_pengajuan_proyek) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(day_of_tanggal_pengajuan_proyek) = ?', [$bulan]);
        }

        return (float) $query->sum('jumlah_investasi3');
    }

    // ---------------------------------------------------------------
    // Helpers bersama
    // ---------------------------------------------------------------

    /**
     * @return array{0: string, 1: string} [$tahun, $bulan]
     */
    private function tahunBulan(Request $request, bool $defaultCurrentMonth): array
    {
        $tahunInput = $request->input('tahun');
        $tahun = ($tahunInput !== null && $tahunInput !== '') ? $tahunInput : date('Y');

        if ($defaultCurrentMonth) {
            $bulan = $request->has('bulan') ? (string) $request->input('bulan') : date('m');
        } else {
            $bulan = (string) $request->input('bulan', '');
        }

        return [(string) $tahun, $bulan];
    }

    private function kecamatanList()
    {
        return DB::table(self::TABLE)
            ->select('kecamatan_usaha')
            ->distinct()
            ->whereNotNull('kecamatan_usaha')
            ->where('kecamatan_usaha', '!=', '')
            ->orderBy('kecamatan_usaha')
            ->pluck('kecamatan_usaha');
    }
}

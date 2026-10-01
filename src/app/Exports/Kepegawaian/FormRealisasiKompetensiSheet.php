<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\PegawaiKompetensi;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Form Realisasi Pengembangan Kompetensi (format OPD), satu sheet per tahun.
 *
 * Layout-nya bukan grid rata maatwebsite (judul multi-baris, header 2 tingkat,
 * kolom teks panjang ber-wrap), jadi seluruh isi ditulis manual di AfterSheet()
 * dan FromArray mengembalikan array kosong — generator grid maatwebsite tidak
 * dipakai sama sekali.
 *
 * Satu baris = satu ACARA (keputusan user setelah sempat per-peserta) —
 * peserta digabung per (nama_kompetensi, penyelenggara, tanggal_mulai,
 * tanggal_selesai), kolom PNS/PPPK/PPPK PW = jumlah peserta per status.
 * Peserta dihitung COUNT(DISTINCT nip) — NIP dipakai sebagai pengenal, BUKAN
 * nama, karena nama satu orang bisa beda ejaan/gelar antar baris (contoh
 * nyata: "SANTI TRI WAHYUNI" vs "SANTI,S.KOM"); penamaan acara tetap aman
 * karena group-by tak menyertakan nama peserta. Semua peserta dihitung tak
 * peduli is_aktif (keputusan user); LEFT JOIN ke pegawai_profil supaya
 * baris kompetensi yang NIP-nya tak ada di profil tetap tampil sebagai
 * acara (statusnya tak terhitung di kolom mana pun — tak ada datanya).
 *
 * Beda dengan versi OPD resmi (per revisi user):
 *   - JUMLAH PESERTA dipecah EMPAT kolom: PNS | PPPK | PPPK PARUH WAKTU |
 *     TOTAL (form asli cuma PNS | PPPK | TOTAL, PPPK paruh waktu digabung
 *     ke situ) — per revisi user, PPPK PW punya kolom sendiri.
 *   - TEMPAT dan TANGGAL PELAKSANAAN dipecah jadi DUA KOLOM (G & H) — form
 *     asli menggabung keduanya dalam satu kolom "TEMPAT & TANGGAL".
 *   - Kolom SERTIFIKAT (YA/TIDAK) SELALU "YA" dan digeser ke kolom J.
 *   - Kolom NOMOR SERTIFIKAT baru ditambahkan tepat setelahnya (kolom K),
 *     berisi nomor sertifikat peserta baris itu.
 *   - Sisa kolom bergeser (JP → I, BENTUK → L, BIDANG → M, NAMA PESERTA → N,
 *     ANGGARAN → O).
 *
 * Tata letak:
 *   baris 1-3  judul form
 *   baris 4    nomor kolom (1..15 — "1    2" di-merge A4:B4 sesuai form asli)
 *   baris 5    header teks (JUMLAH PESERTA di-merge C5:F5)
 *   baris 6    sub-header peserta: PNS | PPPK | PPPK PARUH WAKTU | TOTAL
 *   baris 7+   data
 *
 * Kolom M = "BIDANG & SUBBID/SEKSI YANG MEMBIDANGI" berisi NAMA peserta
 * pertama + jabatannya (per permintaan user: tambah nama di samping jabatan).
 */
class FormRealisasiKompetensiSheet implements Export, FromArray, WithEvents, WithTitle
{
    /** Kolom terakhir (O) — ANGGARAN. */
    private const LAST_COLUMN = 'O';

    public function __construct(private readonly int $tahun) {}

    /** Isi ditulis di AfterSheet; maatwebsite tak perlu baris apa pun. */
    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Form Realisasi '.$this->tahun;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $ws = $event->sheet->getDelegate();
                $last = self::LAST_COLUMN;

                $ws->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $this->writeJudul($ws, $last);
                $this->writeHeader($ws);

                $r = 7;
                foreach ($this->rows() as $row) {
                    $this->writeBaris($ws, $r, $row);
                    $r++;
                }

                $lastRow = max(7, $r - 1);

                // Border header (nomor + teks + sub-header) dan seluruh data.
                $ws->getStyle('A4:'.$last.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $ws->getStyle('A4:'.$last.'4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $ws->getStyle('B7:B'.$lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('G7:G'.$lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('H7:H'.$lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('K7:K'.$lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('M7:M'.$lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('N7:N'.$lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('A7:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('C7:F'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('I7:I'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('J7:K'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);

                // Lebar kolom (FromArray tak dapat ShouldAutoSize bermakna).
                $ws->getColumnDimension('A')->setWidth(6);
                $ws->getColumnDimension('B')->setWidth(55);
                foreach (['C', 'D', 'E', 'F'] as $c) {
                    $ws->getColumnDimension($c)->setWidth(8);
                }
                $ws->getColumnDimension('G')->setWidth(30);
                $ws->getColumnDimension('H')->setWidth(22);
                $ws->getColumnDimension('I')->setWidth(10);
                $ws->getColumnDimension('J')->setWidth(12);
                $ws->getColumnDimension('K')->setWidth(30);
                $ws->getColumnDimension('L')->setWidth(14);
                $ws->getColumnDimension('M')->setWidth(30);
                $ws->getColumnDimension('N')->setWidth(28);
                $ws->getColumnDimension('O')->setWidth(15);
            },
        ];
    }

    private function writeJudul(Worksheet $ws, string $last): void
    {
        $judul = [
            'FORM REALISASI PENGEMBANGAN KOMPETENSI TAHUN '.$this->tahun,
            'KEGIATAN PENGEMBANGAN KOMPETENSI YANG DISELENGGARAKAN OLEH OPD',
            'Nama OPD : DPMPTSP Kota Semarang',
        ];

        foreach ($judul as $i => $teks) {
            $baris = $i + 1;
            $ws->mergeCells('A'.$baris.':'.$last.$baris);
            $ws->setCellValue('A'.$baris, $teks);
            $this->gaya($ws, ['A'.$baris]);
        }

        $ws->getStyle('A1')->getFont()->setSize(14);
        $ws->getRowDimension(1)->setRowHeight(24);
        $ws->getRowDimension(2)->setRowHeight(20);
        $ws->getRowDimension(3)->setRowHeight(20);
    }

    private function writeHeader(Worksheet $ws): void
    {
        // Baris 4: nomor kolom persis seperti form asli ("1    2" digabung di
        // A4:B4 — kolom NO dan NAMA dipisah di satu sel nomor saja).
        $ws->mergeCells('A4:B4');
        $ws->setCellValue('A4', '1    2');
        foreach (['C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9, 'J' => 10, 'K' => 11, 'L' => 12, 'M' => 13, 'N' => 14, 'O' => 15] as $c => $n) {
            $ws->setCellValue($c.'4', $n);
        }

        // Baris 5: header teks. C5 di-merge ke F5 (JUMLAH PESERTA di atas
        // empat sub-kolom); kolom lain merge vertikal ke baris 6.
        $ws->mergeCells('C5:F5');
        $ws->setCellValue('C5', 'JUMLAH PESERTA');

        $kolom = [
            'A' => 'NO',
            'B' => 'NAMA PELATIHAN/BIMTEK/ WORKSHOP/LOKAKARYA/ SEMINAR/ SOSIALISASI/KURSUS/ATAU KEGIATAN SEJENIS LAINNYA (pilih salah satu)',
            'G' => 'TEMPAT PELAKSANAAN',
            'H' => 'TANGGAL PELAKSANAAN',
            'I' => 'JUMLAH JAM PELAJARAN (JP)',
            'J' => 'SERTIFIKAT (YA/TIDAK)',
            'K' => 'NOMOR SERTIFIKAT',
            'L' => 'BENTUK SERTIFIKAT (FISIK / DIGITAL)',
            'M' => 'BIDANG & SUBBID/ SEKSI YANG MEMBIDANGI',
            'N' => 'NAMA PESERTA',
            'O' => 'ANGGARAN',
        ];

        foreach ($kolom as $c => $teks) {
            $ws->mergeCells($c.'5:'.$c.'6');
            $ws->setCellValue($c.'5', $teks);
        }

        // Baris 6: sub-header peserta (PPPK paruh waktu dipisah per revisi
        // user — form asli cuma PNS/PPPK/TOTAL).
        $ws->setCellValue('C6', 'PNS');
        $ws->setCellValue('D6', 'PPPK');
        $ws->setCellValue('E6', 'PPPK PARUH WAKTU');
        $ws->setCellValue('F6', 'TOTAL (3+4+5)');

        $this->gaya($ws, ['A4', 'C4', 'D4', 'E4', 'F4', 'G4', 'H4', 'I4', 'J4', 'K4', 'L4', 'M4', 'N4', 'O4']);
        $this->gaya($ws, ['A5', 'B5', 'C5', 'G5', 'H5', 'I5', 'J5', 'K5', 'L5', 'M5', 'N5', 'O5', 'C6', 'D6', 'E6', 'F6']);

        $ws->getRowDimension(4)->setRowHeight(16);
        $ws->getRowDimension(5)->setRowHeight(60);
        $ws->getRowDimension(6)->setRowHeight(18);
    }

    /**
     * Baris acara untuk satu tahun — peserta digabung per acara (keputusan
     * user). PNS/PPPK/PPPK PW = COUNT(DISTINCT nip) per status_pegawai
     * (NIP sebagai pengenal, bukan nama — gelar/ejaan nama boleh beda antar
     * baris); PPPK paruh waktu tetap kolom sendiri. Jabatan & nama peserta
     * di kolom M/N = peserta pertama (baris id terkecil) acara itu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        // GROUP_CONCAT untuk ambil "peserta pertama" (jabatan/nama) dan
        // gabungan nomor sertifikat; batas default 1024 char bisa terpotong
        // diam-diam utk acara 40+ peserta (Festival ASN 43 sertifikat),
        // jadi dinaikkan untuk sesi koneksi ini saja.
        PegawaiKompetensi::query()->getConnection()
            ->statement('SET SESSION group_concat_max_len = 1000000');

        $pns = 'PEGAWAI NEGERI SIPIL';
        $pppk = 'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA';
        $pppkPw = 'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA PARUH WAKTU';

        return PegawaiKompetensi::query()
            ->leftJoin('pegawai_profil', 'pegawai_profil.nip', '=', 'pegawai_kompetensi.nip')
            ->whereRaw('YEAR('.PegawaiKompetensi::TANGGAL_ACUAN.') = ?', [$this->tahun])
            ->groupBy(
                'pegawai_kompetensi.nama_kompetensi',
                'pegawai_kompetensi.penyelenggara',
                'pegawai_kompetensi.tanggal_mulai',
                'pegawai_kompetensi.tanggal_selesai',
            )
            ->select('pegawai_kompetensi.nama_kompetensi')
            ->addSelect('pegawai_kompetensi.penyelenggara')
            ->addSelect('pegawai_kompetensi.tanggal_mulai')
            ->addSelect('pegawai_kompetensi.tanggal_selesai')
            // JP acara — nilainya sama di tiap baris peserta, MAX cukup.
            ->selectRaw('MAX(pegawai_kompetensi.jumlah_jam) as total_jam')
            ->selectRaw("COUNT(DISTINCT CASE WHEN pegawai_profil.status_pegawai = '$pns' THEN pegawai_kompetensi.nip END) as pns")
            ->selectRaw("COUNT(DISTINCT CASE WHEN pegawai_profil.status_pegawai = '$pppk' THEN pegawai_kompetensi.nip END) as pppk")
            ->selectRaw("COUNT(DISTINCT CASE WHEN pegawai_profil.status_pegawai = '$pppkPw' THEN pegawai_kompetensi.nip END) as pppk_pw")
            ->selectRaw('GROUP_CONCAT(pegawai_kompetensi.nomor_sertifikat ORDER BY pegawai_kompetensi.id SEPARATOR ";") as sertifikat')
            // Peserta "pertama" = baris id terkecil; separator ';;' supaya
            // tak bentrok dengan ';' pemisah nomor sertifikat. NULLIF+TRIM
            // membuat nilai kosong jadi NULL — GROUP_CONCAT melewati NULL.
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(pegawai_profil.jabatan), "") ORDER BY pegawai_kompetensi.id SEPARATOR ";;"), ";;", 1) as bidang')
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(pegawai_profil.nama), "") ORDER BY pegawai_kompetensi.id SEPARATOR ";;"), ";;", 1) as nama_peserta')
            ->orderBy('pegawai_kompetensi.tanggal_mulai')
            ->orderBy('pegawai_kompetensi.nama_kompetensi')
            ->get()
            // ->map(fn ($row) => (array) $row) TIDAK bisa — (array) pada
            // Model melempar properti protected (nama key "..." internal),
            // bukan atribut. toArray() yang benar: hasilnya array asosiatif
            // nama-kolom → nilai, persis seperti yang dibaca writeBaris().
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function writeBaris(Worksheet $ws, int $r, array $row): void
    {
        $pns = (int) $row['pns'];
        $pppk = (int) $row['pppk'];
        $pppkPw = (int) $row['pppk_pw'];

        $ws->setCellValue('A'.$r, $r - 6);
        $ws->setCellValue('B'.$r, $row['nama_kompetensi'] ?? '—');
        $ws->setCellValue('C'.$r, $pns);
        $ws->setCellValue('D'.$r, $pppk);
        $ws->setCellValue('E'.$r, $pppkPw);
        $ws->setCellValue('F'.$r, $pns + $pppk + $pppkPw);
        // TEMPAT = penyelenggara (keputusan user — tak ada kolom lokasi).
        $tempat = trim((string) ($row['penyelenggara'] ?? ''));
        $ws->setCellValue('G'.$r, $tempat !== '' ? $tempat : '—');
        $ws->setCellValue('H'.$r, $this->tanggalPelaksanaan($row['tanggal_mulai'] ?? null, $row['tanggal_selesai'] ?? null));
        $ws->setCellValue('I'.$r, (int) $row['total_jam']);
        // Keputusan user (revisi): kolom YA/TIDAK SELALU "YA" — data sumber
        // memang tak punya baris tanpa sertifikat; nomornya sendiri dipindah
        // ke kolom K (NOMOR SERTIFIKAT) di sampingnya.
        $ws->setCellValue('J'.$r, 'YA');
        $ws->setCellValue('K'.$r, $this->labelSertifikat($row['sertifikat'] ?? null));
        // Keputusan user: bentuk sertifikat selalu DIGITAL (data sumber tak
        // punya kolom fisik/digital).
        $ws->setCellValue('L'.$r, 'DIGITAL');
        $ws->setCellValue('M'.$r, $row['bidang'] ?: '—');
        $ws->setCellValue('N'.$r, $row['nama_peserta'] ?: '—');
        // ANGGARAN (kolom O) sengaja dibiarkan kosong — tidak ada datanya di
        // sistem, diisi manual oleh OPD (keputusan user).
    }

    private function tanggalPelaksanaan(?string $mulai, ?string $selesai): string
    {
        if (! $mulai) {
            return '—';
        }

        $dari = date('d-m-Y', strtotime($mulai));
        $sampai = $selesai ? date('d-m-Y', strtotime($selesai)) : null;

        return ($sampai && $sampai !== $dari) ? $dari.' s.d. '.$sampai : $dari;
    }

    /**
     * Kolom NOMOR SERTIFIKAT per acara — nomor pertama (baris id terkecil),
     * diringkas "nomor pertama (+N nomor lainnya)" bila lebih dari satu.
     * Tak ada nomor sama sekali → "-" (keputusan user).
     */
    private function labelSertifikat(?string $gabungan): string
    {
        $nomor = array_values(array_filter(array_map(
            fn ($n) => trim((string) $n),
            explode(';', (string) $gabungan),
        )));

        if ($nomor === []) {
            return '-';
        }

        $lainnya = count($nomor) - 1;

        return $lainnya > 0
            ? $nomor[0].' (+'.($lainnya).' nomor lainnya)'
            : $nomor[0];
    }

    /**
     * @param  string[]  $cells
     */
    private function gaya(Worksheet $ws, array $cells): void
    {
        foreach ($cells as $cell) {
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
        }
    }
}

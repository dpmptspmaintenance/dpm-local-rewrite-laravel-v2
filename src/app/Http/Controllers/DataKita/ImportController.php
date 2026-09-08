<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import Data — bulk Excel/CSV upload → validate header → insert/update.
 *
 * Legacy used the `openspout/openspout` package (not installed in this app) for most
 * forms and `phpoffice/phpspreadsheet` (already installed, ^5.9) for the SIMBG form.
 * Standardized on PhpSpreadsheet::IOFactory for every form here to avoid adding a new
 * composer dependency — behavior (header validation, column mapping, batched commits)
 * mirrors the legacy scripts exactly per form.
 *
 * Admin-only (legacy: Synchronize Data navbar group, role == 1).
 */
class ImportController extends Controller
{
    private function abortIfNotAdmin(): void
    {
        abort_if((int) (Auth::user()->role ?? 0) !== 1, 403);
    }

    public function index()
    {
        $this->abortIfNotAdmin();

        $lastDpProyek = DB::table('2023_dp_proyek')->max('created_at');
        $lastDpKantor = DB::table('2023_dp_nib_kantor')->max('created_at');
        $lastListIzin = DB::table('2023_list_izin')->max('created_at');

        return view('datakita.import.index', [
            'lastDpProyek' => $lastDpProyek ? date('d-m-Y H:i:s', strtotime($lastDpProyek)) : 'Belum ada data',
            'lastDpKantor' => $lastDpKantor ? date('d-m-Y H:i:s', strtotime($lastDpKantor)) : 'Belum ada data',
            'lastListIzin' => $lastListIzin ? date('d-m-Y H:i:s', strtotime($lastListIzin)) : 'Belum ada data',
        ]);
    }

    // ==========================================
    // SHARED HELPERS (port of legacy fungsi.php)
    // ==========================================

    private function convertDynamicDate(?string $dateString, string $sourceFormat = 'd/m/Y', string $targetFormat = 'Y-m-d'): ?string
    {
        if ($dateString === null || trim($dateString) === '') {
            return null;
        }
        $trimmed = trim($dateString);
        $date = \DateTime::createFromFormat($sourceFormat, $trimmed);
        if ($date && $date->format($sourceFormat) === $trimmed) {
            return $date->format($targetFormat);
        }

        return null;
    }

    private function convertIdDateToFormat(?string $dateString, string $targetFormat = 'Y-m-d'): ?string
    {
        if ($dateString === null || trim($dateString) === '') {
            return null;
        }
        $bulanIndonesia = [
            'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March', 'April' => 'April',
            'Mei' => 'May', 'Juni' => 'June', 'Juli' => 'July', 'Agustus' => 'August',
            'September' => 'September', 'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December',
        ];
        $en = strtr(trim($dateString), $bulanIndonesia);
        $date = \DateTime::createFromFormat('d F Y', $en);

        return $date ? $date->format($targetFormat) : null;
    }

    private function parseIndoDateTimeSemua(?string $rawString): ?string
    {
        if (empty($rawString)) {
            return null;
        }
        $rawString = strtoupper(trim($rawString));
        $months = [
            'JANUARI' => 'January', 'FEBRUARI' => 'February', 'MARET' => 'March', 'APRIL' => 'April',
            'MEI' => 'May', 'JUNI' => 'June', 'JULI' => 'July', 'AGUSTUS' => 'August',
            'SEPTEMBER' => 'September', 'OKTOBER' => 'October', 'NOVEMBER' => 'November', 'DESEMBER' => 'December',
        ];
        $rawString = str_replace(array_keys($months), array_values($months), $rawString);
        $clean = str_replace(' - ', ' ', $rawString);
        $dt = \DateTime::createFromFormat('d F Y H:i', $clean);

        return $dt ? $dt->format('Y-m-d H:i:s') : null;
    }

    /**
     * Port of validate_list_izin.php's local formatOpenSpoutDate() — deliberately looser
     * than convertDynamicDate() (no strict round-trip check), matching legacy behavior.
     */
    private function formatDdMmYyDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (empty($value)) {
            return null;
        }
        $dt = \DateTime::createFromFormat('d/m/y', (string) $value);

        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function parseIndoDateToDatetimeNextgen($dateInput): ?string
    {
        if (empty($dateInput) || $dateInput === '-') {
            return null;
        }
        if ($dateInput instanceof \DateTimeInterface) {
            return $dateInput->format('Y-m-d 00:00:00');
        }
        $dateStr = trim((string) $dateInput);
        $bulanIndo = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
        ];
        $parts = preg_split('/\s+/', $dateStr);
        if (count($parts) >= 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $monthStr = strtolower(trim($parts[1]));
            $year = $parts[2];
            if (isset($bulanIndo[$monthStr])) {
                return "{$year}-{$bulanIndo[$monthStr]}-{$day} 00:00:00";
            }
        }
        $timestamp = strtotime($dateStr);

        return $timestamp ? date('Y-m-d 00:00:00', $timestamp) : null;
    }

    /**
     * Loads an uploaded spreadsheet (xlsx/csv/ods/xls) and returns rows as a 0-indexed
     * array of 0-indexed column arrays (row 0 = header row), mirroring the shape the
     * legacy OpenSpout-based scripts operated on.
     */
    private function loadRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    private function headerMismatchResponse(array $expected, array $actual, string $title = 'Hasil Validasi')
    {
        $rowsHtml = '';
        $max = max(count($expected), count($actual));
        for ($i = 0; $i < $max; $i++) {
            $e = $expected[$i] ?? '-';
            $a = $actual[$i] ?? '-';
            $s = ($e === $a) ? 'OK' : 'X';
            $rowsHtml .= "<tr><td>{$e}</td><td>{$a}</td><td>{$s}</td></tr>";
        }

        $html = "<h2>{$title}</h2>🛑 Validasi GAGAL!<br>"
            ."<table border='1'><tr><th>Exp</th><th>Act</th><th>Status</th></tr>{$rowsHtml}</table>";

        return response($html);
    }

    // ==========================================
    // 1. DP PROYEK (2023_dp_proyek)
    // ==========================================

    public function validateDpProyek(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file');
        if (! $file) {
            return response('❌ GAGAL: File gagal diupload.');
        }

        $expectedHeaders = [
            'No.', 'Id Proyek', 'Uraian_Jenis_Proyek', 'Nib', 'Nama Perusahaan', 'Tanggal Terbit Oss',
            'Uraian Status Penanaman Modal', 'Uraian Jenis Perusahaan', 'Uraian Risiko Proyek', 'nama_proyek',
            'Uraian Skala Usaha', 'Alamat Usaha', 'Kab Kota Usaha', 'kecamatan_usaha', 'kelurahan_usaha',
            'Day of Tanggal Pengajuan Proyek', 'Kbli', 'Judul Kbli', 'KL/Sektor Pembina', 'luas_tanah',
            'satuan_tanah', 'Jumlah Investasi', 'TKI',
        ];
        $expectedColumnCount = count($expectedHeaders);

        $rows = $this->loadRows($file->getRealPath());
        $actualHeaders = array_map(fn ($v) => trim((string) $v), array_slice($rows[0] ?? [], 0, $expectedColumnCount));

        if ($actualHeaders !== $expectedHeaders) {
            return $this->headerMismatchResponse($expectedHeaders, $actualHeaders, 'Hasil Validasi: DP Proyek');
        }

        $pdo = DB::connection()->getPdo();
        $insertSql = 'INSERT INTO `2023_dp_proyek` (
            `id_proyek`, `uraian_jenis_proyek`, `nib`, `nama_perusahaan`, `tanggal_terbit_oss`,
            `uraian_status_penanaman_modal`, `uraian_jenis_perusahaan`, `uraian_risiko_proyek`, `nama_proyek`,
            `uraian_skala_usaha`, `alamat_usaha`, `kab_kota_kantor_pusat`, `kecamatan_usaha`, `kelurahan_usaha`,
            `day_of_tanggal_pengajuan_proyek`, `kbli`, `judul_kbli`, `sektor_pembina`, `nama_user`, `email`,
            `nomor_telp`, `luas_tanah`, `satuan_tanah`, `jumlah_investasi3`, `tki`,
            `tahun_pengambilan_data`, `bulan_pengambilan_data`, `hari_pengambilan_data`, `tanggal_proyek`
        ) VALUES (
            :id_proyek, :uraian_jenis_proyek, :nib, :nama_perusahaan, :tanggal_terbit_oss,
            :uraian_status_penanaman_modal, :uraian_jenis_perusahaan, :uraian_risiko_proyek, :nama_proyek,
            :uraian_skala_usaha, :alamat_usaha, :kab_kota_kantor_pusat, :kecamatan_usaha, :kelurahan_usaha,
            :day_of_tanggal_pengajuan_proyek, :kbli, :judul_kbli, :sektor_pembina, :nama_user, :email,
            :nomor_telp, :luas_tanah, :satuan_tanah, :jumlah_investasi3, :tki,
            :tahun_pengambilan_data, :bulan_pengambilan_data, :hari_pengambilan_data, :tanggal_proyek
        )';
        $stmt = $pdo->prepare($insertSql);

        $importedCount = 0;
        $pdo->beginTransaction();

        try {
            for ($i = 1; $i < count($rows); $i++) {
                $cells = $rows[$i];
                if (empty(array_filter($cells))) {
                    continue;
                }

                $idProyek = isset($cells[1]) ? trim((string) $cells[1]) : '';
                $tglTerbitOss = $this->convertDynamicDate($cells[5] ?? null);
                $tglPengajuan = $this->convertIdDateToFormat($cells[15] ?? null);
                $luasTanahRaw = $cells[19] ?? '';
                $luasTanah = str_replace(['.', ','], ['', '.'], $luasTanahRaw == '' ? '0' : (string) $luasTanahRaw);

                $tanggalProyek = null;
                if (strlen($idProyek) >= 10) {
                    $tanggalProyek = substr($idProyek, 2, 4).'-'.substr($idProyek, 6, 2).'-'.substr($idProyek, 8, 2);
                }

                try {
                    $stmt->execute([
                        ':id_proyek' => $idProyek,
                        ':uraian_jenis_proyek' => $cells[2] ?? null,
                        ':nib' => $cells[3] ?? null,
                        ':nama_perusahaan' => $cells[4] ?? null,
                        ':tanggal_terbit_oss' => $tglTerbitOss,
                        ':uraian_status_penanaman_modal' => $cells[6] ?? null,
                        ':uraian_jenis_perusahaan' => $cells[7] ?? null,
                        ':uraian_risiko_proyek' => $cells[8] ?? null,
                        ':nama_proyek' => $cells[9] ?? null,
                        ':uraian_skala_usaha' => $cells[10] ?? null,
                        ':alamat_usaha' => $cells[11] ?? null,
                        ':kab_kota_kantor_pusat' => $cells[12] ?? null,
                        ':kecamatan_usaha' => $cells[13] ?? null,
                        ':kelurahan_usaha' => $cells[14] ?? null,
                        ':day_of_tanggal_pengajuan_proyek' => $tglPengajuan,
                        ':kbli' => $cells[16] ?? null,
                        ':judul_kbli' => $cells[17] ?? null,
                        ':sektor_pembina' => $cells[18] ?? null,
                        ':nama_user' => '',
                        ':email' => null,
                        ':nomor_telp' => null,
                        ':luas_tanah' => $luasTanah,
                        ':satuan_tanah' => $cells[20] ?? null,
                        ':jumlah_investasi3' => $cells[21] ?? null,
                        ':tki' => $cells[22] ?? 0,
                        ':tanggal_proyek' => $tanggalProyek,
                        ':tahun_pengambilan_data' => date('Y'),
                        ':bulan_pengambilan_data' => date('m'),
                        ':hari_pengambilan_data' => date('d'),
                    ]);
                    $importedCount++;

                    if ($importedCount % 1000 === 0) {
                        $pdo->commit();
                        $pdo->beginTransaction();
                    }
                } catch (\PDOException $e) {
                    // Silent skip per-row, matches legacy.
                }
            }

            $pdo->commit();

            return response("🎉 Validasi OK.<br>✅ Selesai. <b>{$importedCount}</b> data berhasil disimpan.");
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return response('❌ Error: '.$e->getMessage());
        }
    }

    // ==========================================
    // 2. DP NIB KANTOR (2023_dp_nib_kantor)
    // ==========================================

    public function validateDpKantor(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file');
        if (! $file) {
            return response('❌ GAGAL: File gagal diupload.');
        }

        $expectedHeaders = [
            'No.', 'nib', 'Day of tanggal_terbit_oss', 'nama_perusahaan', 'status_penanaman_modal',
            'uraian_jenis_perusahaan', 'uraian_skala_usaha', 'Alamat Perusahaan', 'kelurahan', 'kecamatan',
            'kab_kota', '',
        ];
        $expectedColumnCount = count($expectedHeaders);

        $rows = $this->loadRows($file->getRealPath());
        $actualHeaders = array_map(fn ($v) => trim((string) $v), array_slice($rows[0] ?? [], 0, $expectedColumnCount));

        if ($actualHeaders !== $expectedHeaders) {
            return $this->headerMismatchResponse($expectedHeaders, $actualHeaders, 'Hasil Validasi: DP NIB Kantor');
        }

        $pdo = DB::connection()->getPdo();
        $sql = 'INSERT INTO `2023_dp_nib_kantor` (
            `nib`, `day_of_tanggal_terbit_oss`, `nama_perusahaan`,
            `status_penanaman_modal`, `uraian_jenis_perusahaan`, `alamat_perusahaan`,
            `kab_kota`, `email`, `nomor_telp`, `flag`,
            `tahun_pengambilan_data`, `bulan_pengambilan_data`, `hari_pengambilan_data`,
            `kecamatan`, `kelurahan`, `uraian_skala_usaha`
        ) VALUES (
            :nib, :day_of_tanggal_terbit_oss, :nama_perusahaan,
            :status_penanaman_modal, :uraian_jenis_perusahaan, :alamat_perusahaan,
            :kab_kota, :email, :nomor_telp, :flag,
            :tahun_pengambilan_data, :bulan_pengambilan_data, :hari_pengambilan_data,
            :kecamatan, :kelurahan, :uraian_skala_usaha
        )';
        $stmt = $pdo->prepare($sql);

        $importedCount = 0;
        $pdo->beginTransaction();

        try {
            for ($i = 1; $i < count($rows); $i++) {
                $cells = $rows[$i];
                if (empty(array_filter($cells, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                try {
                    $stmt->execute([
                        ':nib' => $cells[1] ?? null,
                        ':day_of_tanggal_terbit_oss' => $this->convertDynamicDate($cells[2] ?? null, 'm/d/Y'),
                        ':nama_perusahaan' => $cells[3] ?? null,
                        ':status_penanaman_modal' => $cells[4] ?? null,
                        ':uraian_jenis_perusahaan' => $cells[5] ?? null,
                        ':alamat_perusahaan' => $cells[7] ?? null,
                        ':kab_kota' => $cells[10] ?? null,
                        ':email' => null,
                        ':nomor_telp' => null,
                        ':flag' => $cells[11] ?? 0,
                        ':tahun_pengambilan_data' => date('Y'),
                        ':bulan_pengambilan_data' => date('m'),
                        ':hari_pengambilan_data' => date('d'),
                        ':kecamatan' => $cells[9] ?? null,
                        ':kelurahan' => $cells[8] ?? null,
                        ':uraian_skala_usaha' => $cells[6] ?? null,
                    ]);
                    $importedCount++;

                    if ($importedCount % 1000 === 0) {
                        $pdo->commit();
                        $pdo->beginTransaction();
                    }
                } catch (\PDOException $e) {
                    // Silent skip per-row, matches legacy.
                }
            }

            $pdo->commit();

            return response("🎉 Validasi OK.<br>✅ <b>{$importedCount}</b> data berhasil disimpan.");
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return response('❌ Error: '.$e->getMessage());
        }
    }

    // ==========================================
    // 3. LIST IZIN (2023_list_izin)
    // ==========================================

    public function validateListIzin(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file');
        if (! $file) {
            return response('❌ GAGAL: File tidak ditemukan.');
        }

        $expectedHeaders = [
            'No.', 'Id Permohonan Izin', 'Nama Perusahaan', 'Nib', 'Day of Tanggal Terbit Oss',
            'Uraian Status Penanaman Modal', 'Propinsi', 'Kab Kota', 'kecamatan', 'kelurahan',
            'Id Proyek', 'Kd Resiko', 'Kbli', 'Day of Tgl Izin', 'Uraian Jenis Perizinan',
            'Nama Dokumen', 'Uraian Kewenangan', 'Status Perizinan', 'Kewenangan', 'kl_sektor',
        ];
        $expectedColumnCount = count($expectedHeaders);

        $rows = $this->loadRows($file->getRealPath());
        $actualHeaders = array_map(fn ($v) => trim((string) $v), array_slice($rows[0] ?? [], 0, $expectedColumnCount));

        if ($actualHeaders !== $expectedHeaders) {
            return response('🛑 Header Tidak Sesuai! Pastikan urutan: No, Id Permohonan, ..., kl_sektor.');
        }

        $pdo = DB::connection()->getPdo();
        // NOTE: legacy insert never mapped kecamatan/kelurahan (header cols 8/9) into the
        // table despite the column existing — preserved verbatim (pre-existing legacy gap).
        $sql = 'INSERT INTO `2023_list_izin` (
            `id_permohonan_izin`, `nama_perusahaan`, `nib`, `day_of_tanggal_terbit_oss`,
            `uraian_status_penanaman_modal`, `propinsi`, `kab_kota`, `id_proyek`,
            `kd_resiko`, `kbli`, `day_of_tanggal_izin`, `uraian_jenis_perizinan`,
            `nama_dokumen`, `uraian_kewenangan`, `uraian_status_respon`, `kewenangan`,
            `kl_sektor`, `tahun_pengambilan_data`, `bulan_pengambilan_data`,
            `hari_pengambilan_data`, `tanggal_permohonan`, `tanggal_proyek`
        ) VALUES (
            :id_permohonan_izin, :nama_perusahaan, :nib, :day_of_tanggal_terbit_oss,
            :uraian_status_penanaman_modal, :propinsi, :kab_kota, :id_proyek,
            :kd_resiko, :kbli, :day_of_tanggal_izin, :uraian_jenis_perizinan,
            :nama_dokumen, :uraian_kewenangan, :uraian_status_respon, :kewenangan,
            :kl_sektor, :tahun, :bulan, :hari, :tgl_perm, :tgl_proy
        )';
        $stmt = $pdo->prepare($sql);

        $extractDate = function ($id) {
            if (! $id || strlen((string) $id) < 10) {
                return null;
            }
            $s = (string) $id;

            return substr($s, 2, 4).'-'.substr($s, 6, 2).'-'.substr($s, 8, 2);
        };

        $importedCount = 0;
        $pdo->beginTransaction();

        try {
            for ($i = 1; $i < count($rows); $i++) {
                $cells = $rows[$i];
                if (empty(array_filter($cells))) {
                    continue;
                }

                $idPerm = $cells[1] ?? null;
                $idProyek = $cells[10] ?? null;

                $stmt->execute([
                    ':id_permohonan_izin' => $idPerm,
                    ':nama_perusahaan' => $cells[2] ?? null,
                    ':nib' => $cells[3] ?? null,
                    ':day_of_tanggal_terbit_oss' => $this->formatDdMmYyDate($cells[4] ?? null),
                    ':uraian_status_penanaman_modal' => $cells[5] ?? null,
                    ':propinsi' => $cells[6] ?? null,
                    ':kab_kota' => $cells[7] ?? null,
                    ':id_proyek' => $idProyek,
                    ':kd_resiko' => $cells[11] ?? null,
                    ':kbli' => $cells[12] ?? null,
                    ':day_of_tanggal_izin' => $this->formatDdMmYyDate($cells[13] ?? null),
                    ':uraian_jenis_perizinan' => $cells[14] ?? null,
                    ':nama_dokumen' => $cells[15] ?? null,
                    ':uraian_kewenangan' => $cells[16] ?? null,
                    ':uraian_status_respon' => $cells[17] ?? null,
                    ':kewenangan' => $cells[18] ?? null,
                    ':kl_sektor' => $cells[19] ?? null,
                    ':tahun' => date('Y'),
                    ':bulan' => date('m'),
                    ':hari' => date('d'),
                    ':tgl_perm' => $extractDate($idPerm),
                    ':tgl_proy' => $extractDate($idProyek),
                ]);

                if (++$importedCount % 1000 === 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }

            $pdo->commit();

            return response("🎉 Berhasil! <b>{$importedCount}</b> data tersimpan.");
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return response('❌ Error: '.$e->getMessage());
        }
    }

    // ==========================================
    // 4. SIMBG MONITORING (simbg_monitoring) — upsert by no_registrasi
    // ==========================================

    public function validateSimbgMonitoring(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file');
        if (! $file) {
            return response('❌ GAGAL: File gagal diupload atau tidak ditemukan.');
        }

        $expectedHeaders = [
            'No', 'Nama Pemilik', 'Jenis Permohonan', 'Nomor Dokumen', 'Nomor Registrasi', 'Tanggal',
            'Kota/Kabupaten Bangunan', 'Kecamatan Bangunan', 'Kelurahan Bangunan', 'Alamat', 'Status',
            'Status SLF', 'Fungsi', 'Tipe Konsultasi', 'Nama Bangunan', 'Jumlah Unit', 'Okupansi',
            'Luas Bangunan', 'Lantai', 'Luas Basement', 'Lapis Basement', 'Tinggi', 'Tipe Bangunan',
            'Fungsi Bangunan', 'Sub Fungsi Bangunan', 'Permanensi', 'Tipe Konsultasi',
        ];

        $rows = $this->loadRows($file->getRealPath());
        $actualHeaders = array_map(fn ($v) => trim((string) $v), $rows[0] ?? []);
        $lastIdx = count($expectedHeaders);
        $unexpected = array_filter(array_slice($actualHeaders, $lastIdx), fn ($v) => $v !== '');

        if (array_slice($actualHeaders, 0, $lastIdx) !== $expectedHeaders || ! empty($unexpected)) {
            return $this->headerMismatchResponse($expectedHeaders, array_slice($actualHeaders, 0, $lastIdx), 'Hasil Validasi Header: SIMBG Monitoring');
        }

        $pdo = DB::connection()->getPdo();
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM `simbg_monitoring` WHERE `no_registrasi` = :no_registrasi');
        $sqlInsert = "INSERT INTO `simbg_monitoring` (
            `nama_pemilik`, `jenis_permohonan`, `no_dokumen`, `no_registrasi`, `tgl_registrasi`,
            `kota_kab_bangunan`, `kecamatan_bangunan`, `kelurahan_bangunan`, `alamat`, `status`,
            `status_slf`, `fungsi`, `tipe_konsultasi_1`, `nama_bangunan`, `jumlah_unit`,
            `okupansi`, `luas_bangunan`, `jumlah_lantai`, `luas_basement`, `lapis_basement`,
            `tinggi_bangunan`, `tipe_bangunan`, `fungsi_bangunan`, `sub_fungsi_bangunan`,
            `permanensi`, `tipe_konsultasi_2`,
            `alamat_pemilik`, `no_kontak`, `e_mail`, `no_identitas`,
            `jenis_registrasi`, `is_sync`, `tgl_sk`, `tahun_pengambilan_data`, `bulan_pengambilan_data`
        ) VALUES (
            :nama_pemilik, :jenis_permohonan, :no_dokumen, :no_registrasi, :tgl_registrasi,
            :kota_kab_bangunan, :kecamatan_bangunan, :kelurahan_bangunan, :alamat, :status,
            :status_slf, :fungsi, :tipe_konsultasi_1, :nama_bangunan, :jumlah_unit,
            :okupansi, :luas_bangunan, :jumlah_lantai, :luas_basement, :lapis_basement,
            :tinggi_bangunan, :tipe_bangunan, :fungsi_bangunan, :sub_fungsi_bangunan,
            :permanensi, :tipe_konsultasi_2,
            '', '', '', '',
            :jenis_registrasi, '0', NULL, :tahun_pengambilan_data, :bulan_pengambilan_data
        )";
        $stmtInsert = $pdo->prepare($sqlInsert);

        $sqlUpdate = 'UPDATE `simbg_monitoring` SET
            `nama_pemilik` = :nama_pemilik, `jenis_permohonan` = :jenis_permohonan, `no_dokumen` = :no_dokumen,
            `tgl_registrasi` = :tgl_registrasi, `kota_kab_bangunan` = :kota_kab_bangunan,
            `kecamatan_bangunan` = :kecamatan_bangunan, `kelurahan_bangunan` = :kelurahan_bangunan,
            `alamat` = :alamat, `status` = :status, `status_slf` = :status_slf, `fungsi` = :fungsi,
            `tipe_konsultasi_1` = :tipe_konsultasi_1, `nama_bangunan` = :nama_bangunan,
            `jumlah_unit` = :jumlah_unit, `okupansi` = :okupansi, `luas_bangunan` = :luas_bangunan,
            `jumlah_lantai` = :jumlah_lantai, `luas_basement` = :luas_basement, `lapis_basement` = :lapis_basement,
            `tinggi_bangunan` = :tinggi_bangunan, `tipe_bangunan` = :tipe_bangunan,
            `fungsi_bangunan` = :fungsi_bangunan, `sub_fungsi_bangunan` = :sub_fungsi_bangunan,
            `permanensi` = :permanensi, `tipe_konsultasi_2` = :tipe_konsultasi_2,
            `jenis_registrasi` = :jenis_registrasi, `tahun_pengambilan_data` = :tahun_pengambilan_data,
            `bulan_pengambilan_data` = :bulan_pengambilan_data
            WHERE `no_registrasi` = :no_registrasi';
        $stmtUpdate = $pdo->prepare($sqlUpdate);

        $importedCount = 0;
        $updatedCount = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $cells = $rows[$i];
            $noRegistrasi = trim((string) ($cells[4] ?? ''));
            $namaPemilik = trim((string) ($cells[1] ?? ''));

            if ($namaPemilik === '' && $noRegistrasi === '') {
                continue;
            }

            $jenisPermohonan = trim((string) ($cells[2] ?? ''));
            $jenisRegistrasi = (str_contains($jenisPermohonan, 'Bangunan') || str_contains($jenisPermohonan, 'PBG')) ? 'PBG' : 'SLF';
            $getNum = fn ($idx) => is_numeric($v = trim((string) ($cells[$idx] ?? ''))) ? $v : 0;

            $params = [
                ':nama_pemilik' => $namaPemilik,
                ':jenis_permohonan' => $jenisPermohonan,
                ':no_dokumen' => trim((string) ($cells[3] ?? '')),
                ':no_registrasi' => $noRegistrasi,
                ':tgl_registrasi' => trim((string) ($cells[5] ?? '')),
                ':kota_kab_bangunan' => trim((string) ($cells[6] ?? '')),
                ':kecamatan_bangunan' => trim((string) ($cells[7] ?? '')),
                ':kelurahan_bangunan' => trim((string) ($cells[8] ?? '')),
                ':alamat' => trim((string) ($cells[9] ?? '')),
                ':status' => trim((string) ($cells[10] ?? '')),
                ':status_slf' => trim((string) ($cells[11] ?? '')),
                ':fungsi' => trim((string) ($cells[12] ?? '')),
                ':tipe_konsultasi_1' => trim((string) ($cells[13] ?? '')),
                ':nama_bangunan' => trim((string) ($cells[14] ?? '')),
                ':jumlah_unit' => $getNum(15),
                ':okupansi' => trim((string) ($cells[16] ?? '')),
                ':luas_bangunan' => $getNum(17),
                ':jumlah_lantai' => $getNum(18),
                ':luas_basement' => $getNum(19),
                ':lapis_basement' => $getNum(20),
                ':tinggi_bangunan' => $getNum(21),
                ':tipe_bangunan' => trim((string) ($cells[22] ?? '')),
                ':fungsi_bangunan' => trim((string) ($cells[23] ?? '')),
                ':sub_fungsi_bangunan' => trim((string) ($cells[24] ?? '')),
                ':permanensi' => trim((string) ($cells[25] ?? '')),
                ':tipe_konsultasi_2' => trim((string) ($cells[26] ?? '')),
                ':jenis_registrasi' => $jenisRegistrasi,
                ':tahun_pengambilan_data' => date('Y'),
                ':bulan_pengambilan_data' => date('m'),
            ];

            try {
                $stmtCheck->execute([':no_registrasi' => $noRegistrasi]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $stmtUpdate->execute($params);
                    $updatedCount++;
                } else {
                    $stmtInsert->execute($params);
                    $importedCount++;
                }
            } catch (\PDOException $e) {
                return response("❌ Error Baris {$i}: ".$e->getMessage());
            }
        }

        return response(
            '🎉 Validasi BERHASIL! Proses Selesai.<br>'
            ."✅ <b>{$importedCount}</b> data BARU ditambahkan.<br>"
            ."🔄 <b>{$updatedCount}</b> data LAMA diperbarui."
        );
    }

    // ==========================================
    // 5. MPPD SEMUA (mppdig_permohonan_sip_semua) — insert
    // ==========================================

    public function validateMppdSemua(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file_mppd_semua');
        if (! $file) {
            return response("<div class='alert alert-danger'>❌ Gagal Upload.</div>");
        }

        $expectedHeaders = [
            'btn href', 'min', 'min 2', 'min 3', 'min 4', 'tablescraper-selected-row',
            'tablescraper-selected-row 2', 'tablescraper-selected-row href', 'tablescraper-selected-row 3',
            'tablescraper-selected-row href 2', 'min 5', 'min 6',
        ];

        $rows = $this->loadRows($file->getRealPath());
        $actual = array_map(fn ($v) => strtolower(trim((string) $v)), array_slice($rows[0] ?? [], 0, count($expectedHeaders)));
        $expect = array_map('strtolower', $expectedHeaders);

        if ($actual !== $expect) {
            return response("<div class='alert alert-danger'>🛑 <b>Header Salah!</b><br>File: ".implode(', ', $actual).'</div>');
        }

        $pdo = DB::connection()->getPdo();
        $sql = 'INSERT INTO `mppdig_permohonan_sip_semua` (
            `nomor_registrasi`, `profesi`, `tempat_praktik`,
            `nama_lengkap`, `alamat`, `nomor_hp`, `email`,
            `waktu_input`, `status`
        ) VALUES (
            :nomor_registrasi, :profesi, :tempat_praktik,
            :nama_lengkap, :alamat, :nomor_hp, :email,
            :waktu_input, :status
        )';
        $stmt = $pdo->prepare($sql);

        $importedCount = 0;
        $pdo->beginTransaction();

        try {
            for ($i = 1; $i < count($rows); $i++) {
                $cells = $rows[$i];
                $noReg = trim((string) ($cells[1] ?? ''));
                if ($noReg === '') {
                    continue;
                }

                $alamat = trim(preg_replace('/\s+/', ' ', (string) ($cells[5] ?? '')));

                try {
                    $stmt->execute([
                        ':nomor_registrasi' => $noReg,
                        ':profesi' => trim((string) ($cells[2] ?? '')),
                        ':tempat_praktik' => trim((string) ($cells[3] ?? '')),
                        ':nama_lengkap' => trim((string) ($cells[4] ?? '')),
                        ':alamat' => $alamat,
                        ':nomor_hp' => trim((string) ($cells[6] ?? '')),
                        ':email' => trim((string) ($cells[8] ?? '')),
                        ':waktu_input' => $this->parseIndoDateTimeSemua($cells[10] ?? null),
                        ':status' => trim((string) ($cells[11] ?? '')),
                    ]);
                    $importedCount++;

                    if ($importedCount % 500 === 0) {
                        $pdo->commit();
                        $pdo->beginTransaction();
                    }
                } catch (\PDOException $e) {
                    // Silent skip, matches legacy.
                }
            }

            $pdo->commit();

            return response("<div class='alert alert-success'>✅ <b>SELESAI!</b> Berhasil import <b>{$importedCount}</b> data.</div>");
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return response("<div class='alert alert-danger'>Error: ".$e->getMessage().'</div>');
        }
    }

    // ==========================================
    // 6. MPPD SELESAI (mppdig_permohonan_sip_semua) — update no_sip only
    // ==========================================

    public function validateMppdSelesai(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file_mppd_selesai');
        if (! $file) {
            return response("<div class='alert alert-danger'>❌ Tidak ada file yang diupload.</div>");
        }

        $rows = $this->loadRows($file->getRealPath());
        $isValidHeader = str_contains(strtolower((string) ($rows[0][1] ?? '')), 'min');

        $pdo = DB::connection()->getPdo();
        $sql = 'UPDATE `mppdig_permohonan_sip_semua`
            SET `no_sip` = :no_sip
            WHERE `nomor_registrasi` = :no_reg
              AND `profesi` = :profesi
              AND REPLACE(`nama_lengkap`, "\'", \'\') = REPLACE(:nama, "\'", \'\')';
        $stmt = $pdo->prepare($sql);

        $updatedCount = 0;
        $pdo->beginTransaction();

        try {
            if ($isValidHeader) {
                for ($i = 1; $i < count($rows); $i++) {
                    $cells = $rows[$i];
                    $noReg = trim((string) ($cells[1] ?? ''));
                    $noSip = trim((string) ($cells[4] ?? ''));
                    if ($noReg === '' || $noSip === '') {
                        continue;
                    }

                    try {
                        $stmt->execute([
                            ':no_sip' => $noSip,
                            ':no_reg' => $noReg,
                            ':profesi' => trim((string) ($cells[2] ?? '')),
                            ':nama' => trim((string) ($cells[3] ?? '')),
                        ]);
                        if ($stmt->rowCount() > 0) {
                            $updatedCount++;
                        }

                        if ($updatedCount % 500 === 0 && $updatedCount > 0) {
                            $pdo->commit();
                            $pdo->beginTransaction();
                        }
                    } catch (\PDOException $e) {
                        // Silent skip, matches legacy.
                    }
                }
            }

            $pdo->commit();

            return response("<div class='alert alert-success'>✅ <b>SUKSES!</b> Data 'SA'IDA' aman. Total Update: <b>{$updatedCount}</b>.</div>");
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return response("<div class='alert alert-danger'>Error: ".$e->getMessage().'</div>');
        }
    }

    // ==========================================
    // 7. MPPD NEXT GEN SELESAI (mppdig_permohonan_sip_semua) — insert, JSON response
    // ==========================================

    public function validateMppdNextgenSelesai(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file_mppd_nextgen_selesai');
        if (! $file) {
            return response()->json(['status' => 'error', 'message' => 'File Excel gagal diunggah atau tidak ditemukan.']);
        }

        $expectedHeader = [
            'Tanggal Pengajuan', 'Jenis Permohonan', 'No Permohonan', 'Nomor Izin', 'Nama Pemohon',
            'Nama Profesi', 'Nama Fasyankes', 'Alamat Fasyankes', 'Tanggal Selesai', 'Nama Instansi',
            'Akhir Berlaku', 'Tipe Pengesahan',
        ];

        $rows = $this->loadRows($file->getRealPath());
        $actualHeader = array_map(fn ($v) => trim((string) $v), $rows[0] ?? []);

        foreach ($expectedHeader as $idx => $name) {
            if (! isset($actualHeader[$idx]) || $actualHeader[$idx] !== $name) {
                return response()->json(['status' => 'error', 'message' => 'Format Header Excel tidak sesuai dengan template.']);
            }
        }
        $headerMap = array_flip($expectedHeader);

        $pdo = DB::connection()->getPdo();
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM mppdig_permohonan_sip_semua WHERE nomor_registrasi = :no_reg');
        $insertStmt = $pdo->prepare('INSERT INTO mppdig_permohonan_sip_semua
            (nomor_registrasi, profesi, tempat_praktik, nama_lengkap, waktu_input, status, no_sip, waktu_selesai)
            VALUES
            (:nomor_registrasi, :profesi, :tempat_praktik, :nama_lengkap, :waktu_input, :status, :no_sip, :waktu_selesai)');

        $insertedCount = 0;
        $skippedProfesiCount = 0;
        $ignoredDuplicateCount = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $cells = $rows[$i];

            $tglPengajuan = $cells[$headerMap['Tanggal Pengajuan']] ?? '';
            $noPermohonan = trim((string) ($cells[$headerMap['No Permohonan']] ?? ''));
            $nomorIzin = trim((string) ($cells[$headerMap['Nomor Izin']] ?? ''));
            $namaPemohon = trim((string) ($cells[$headerMap['Nama Pemohon']] ?? ''));
            $namaProfesi = trim((string) ($cells[$headerMap['Nama Profesi']] ?? ''));
            $namaFasyankes = trim((string) ($cells[$headerMap['Nama Fasyankes']] ?? ''));
            $tglSelesai = $cells[$headerMap['Tanggal Selesai']] ?? '';

            if ($namaProfesi === '' || $namaProfesi === '-') {
                $skippedProfesiCount++;

                continue;
            }

            if ($noPermohonan !== '') {
                $checkStmt->execute([':no_reg' => $noPermohonan]);
                if ($checkStmt->fetchColumn() > 0) {
                    $ignoredDuplicateCount++;

                    continue;
                }
            }

            $insertStmt->execute([
                ':nomor_registrasi' => $noPermohonan,
                ':profesi' => $namaProfesi,
                ':tempat_praktik' => $namaFasyankes,
                ':nama_lengkap' => $namaPemohon,
                ':waktu_input' => $this->parseIndoDateToDatetimeNextgen($tglPengajuan),
                ':status' => 'SK DITERBITKAN',
                ':no_sip' => $nomorIzin,
                ':waktu_selesai' => $this->parseIndoDateToDatetimeNextgen($tglSelesai),
            ]);
            $insertedCount++;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Proses impor data selesai.',
            'inserted' => $insertedCount,
            'ignored_duplicate' => $ignoredDuplicateCount,
            'skipped_profesi' => $skippedProfesiCount,
        ]);
    }

    // ==========================================
    // 8. MPPD NEXT GEN DITOLAK (mppdig_permohonan_sip_semua) — insert, JSON response
    // ==========================================

    public function validateMppdNextgenDitolak(Request $request)
    {
        $this->abortIfNotAdmin();

        $file = $request->file('excel_file_mppd_nextgen_ditolak');
        if (! $file) {
            return response()->json(['status' => 'error', 'message' => 'File Excel gagal diunggah atau tidak ditemukan.']);
        }

        $expectedHeader = [
            'ID', 'Jenis Permohonan', 'No Permohonan', 'Nomor Izin', 'Nama Pemohon', 'Nama Profesi',
            'Nama Fasyankes', 'Alamat Fasyankes', 'Tanggal Pengajuan', 'Nama Instansi',
            'Tanggal Penolakan', 'Alasan Ditolak',
        ];

        $rows = $this->loadRows($file->getRealPath());
        $actualHeader = array_map(fn ($v) => trim((string) $v), $rows[0] ?? []);

        foreach ($expectedHeader as $idx => $name) {
            if (! isset($actualHeader[$idx]) || $actualHeader[$idx] !== $name) {
                return response()->json(['status' => 'error', 'message' => 'Format Header Excel tidak sesuai dengan template.']);
            }
        }
        $headerMap = array_flip($expectedHeader);

        $pdo = DB::connection()->getPdo();
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM mppdig_permohonan_sip_semua WHERE nomor_registrasi = :no_reg');
        $insertStmt = $pdo->prepare('INSERT INTO mppdig_permohonan_sip_semua
            (nomor_registrasi, profesi, tempat_praktik, nama_lengkap, waktu_input, status, waktu_selesai)
            VALUES
            (:nomor_registrasi, :profesi, :tempat_praktik, :nama_lengkap, :waktu_input, :status, :waktu_selesai)');

        $insertedCount = 0;
        $skippedProfesiCount = 0;
        $ignoredDuplicateCount = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $cells = $rows[$i];

            $noPermohonan = trim((string) ($cells[$headerMap['No Permohonan']] ?? ''));
            $namaPemohon = trim((string) ($cells[$headerMap['Nama Pemohon']] ?? ''));
            $namaProfesi = trim((string) ($cells[$headerMap['Nama Profesi']] ?? ''));
            $namaFasyankes = trim((string) ($cells[$headerMap['Nama Fasyankes']] ?? ''));
            $tglPengajuan = $cells[$headerMap['Tanggal Pengajuan']] ?? '';
            $tglPenolakan = $cells[$headerMap['Tanggal Penolakan']] ?? '';

            if ($namaProfesi === '' || $namaProfesi === '-') {
                $skippedProfesiCount++;

                continue;
            }

            if ($noPermohonan !== '') {
                $checkStmt->execute([':no_reg' => $noPermohonan]);
                if ($checkStmt->fetchColumn() > 0) {
                    $ignoredDuplicateCount++;

                    continue;
                }
            }

            $insertStmt->execute([
                ':nomor_registrasi' => $noPermohonan,
                ':profesi' => $namaProfesi,
                ':tempat_praktik' => $namaFasyankes,
                ':nama_lengkap' => $namaPemohon,
                ':waktu_input' => $this->parseIndoDateToDatetimeNextgen($tglPengajuan),
                ':status' => 'DITOLAK',
                ':waktu_selesai' => $this->parseIndoDateToDatetimeNextgen($tglPenolakan),
            ]);
            $insertedCount++;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Proses impor data penolakan selesai.',
            'inserted' => $insertedCount,
            'ignored_duplicate' => $ignoredDuplicateCount,
            'skipped_profesi' => $skippedProfesiCount,
        ]);
    }
}

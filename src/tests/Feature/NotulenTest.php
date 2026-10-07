<?php

namespace Tests\Feature;

use App\Filament\Kepegawaian\Resources\NotulenResource\Pages\CreateNotulen;
use App\Models\Kepegawaian\Notulen;
use App\Models\User;
use App\Services\Kepegawaian\NotulenGeneratorService;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class NotulenTest extends TestCase
{
    private ?int $notulenId = null;

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
        if ($this->notulenId) {
            Notulen::query()->whereKey($this->notulenId)->delete();
        }
        parent::tearDown();
    }

    public function test_notulen_resource_can_create_with_user_signatories(): void
    {
        $admin = User::query()->where('role', 1)->firstOrFail();
        $this->actingAs($admin);

        $users = User::query()->whereNotNull('nip')->where('nip', '!=', '')->take(2)->get();
        $atasanUser = $users->first() ?? $admin;
        $pelaporUser = $users->last() ?? $admin;

        Livewire::test(CreateNotulen::class)
            ->fillForm([
                'judul' => 'RAPAT EVALUASI KINERJA PELAYANAN TRIWULAN III TAHUN 2026',
                'hari_tanggal' => '2026-10-15',
                'waktu_mulai' => '09.00',
                'waktu_selesai' => 'selesai',
                'tempat' => "Ruang Rapat Lantai 2 DPMPTSP Kota Semarang\nJl. Jend. Urip Sumohardjo KM 17",
                'dasar' => "Undangan Nomor 005/123/X/2026\nProgram Kerja DPMPTSP Kota Semarang Tahun 2026",
                'narasumber' => "1. Kepala DPMPTSP Kota Semarang\n2. Koordinator Pelayanan Perizinan",
                'peserta_deskripsi' => 'Dihadiri oleh seluruh pejabat struktural dan fungsional.',
                'peserta_daftar' => "Bidang Penanaman Modal\nBidang Pelayanan Terpadu\nSubbag Umum dan Kepegawaian",
                'hasil_acara' => "Rapat dipimpin oleh Kepala Dinas membahas capaian realisasi investasi dan evaluasi indeks kepuasan masyarakat.\n\nDisepakati bahwa seluruh unit percepatan perizinan akan meningkatkan koordinasi lintas sektor pada triwulan berikutnya.",
                'penutup' => 'Demikian Notulen ini dibuat untuk dapat dipergunakan sebagaimana mestinya.',
                'tanggal_naskah' => '2026-10-15',
                'atasan_user_id' => $atasanUser->id,
                'atasan_nama' => $atasanUser->nama ?: $atasanUser->name,
                'atasan_nip' => $atasanUser->nip,
                'atasan_jabatan' => 'Kepala Bidang Penanaman Modal DPMPTSP Kota Semarang',
                'pelapor_user_id' => $pelaporUser->id,
                'pelapor_nama' => $pelaporUser->nama ?: $pelaporUser->name,
                'pelapor_nip' => $pelaporUser->nip,
                'pelapor_jabatan' => 'Subkoordinator Pengendalian DPMPTSP Kota Semarang',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $record = Notulen::query()->where('judul', 'RAPAT EVALUASI KINERJA PELAYANAN TRIWULAN III TAHUN 2026')->latest('id')->first();
        $this->assertNotNull($record);
        $this->notulenId = $record->id;

        $this->assertSame('09.00 WIB – Selesai', $record->waktu);
        $this->assertSame($atasanUser->id, $record->atasan_user_id);
        $this->assertSame($atasanUser->nama ?: $atasanUser->name, $record->atasan_nama);
        $this->assertSame($pelaporUser->id, $record->pelapor_user_id);
        $this->assertSame($pelaporUser->nama ?: $pelaporUser->name, $record->pelapor_nama);
        $this->assertSame($admin->id, $record->dibuat_oleh);
    }

    public function test_notulen_generator_docx_and_pdf(): void
    {
        $admin = User::query()->where('role', 1)->firstOrFail();

        $record = Notulen::create([
            'judul' => 'NOTULEN PENGUJIAN SISTEM DOKUMEN 2026',
            'hari_tanggal' => 'Kamis, 15 Oktober 2026',
            'waktu' => '09.00 WIB – Selesai',
            'tempat' => 'Ruang Rapat DPMPTSP',
            'dasar' => "Surat Undangan Nomor 005/123/2026\nPeraturan Daerah Nomor 8 Tahun 2024",
            'narasumber' => "1. Pembicara Pertama, S.Kom\n2. Pembicara Kedua, M.Si",
            'peserta_deskripsi' => 'Diikuti oleh 20 peserta rapat.',
            'peserta_daftar' => "Dinas Kominfo\nDPMPTSP Kota Semarang",
            'hasil_acara' => '<p>Paragraf pertama hasil pembahasan teknis dengan <strong>teks tebal</strong> dan <em>teks miring</em>.</p><ul><li>Poin evaluasi pertama</li><li>Poin evaluasi kedua</li></ul>',
            'penutup' => 'Demikian Notulen ini dibuat untuk menjadikan periksa.',
            'tanggal_naskah' => 'Semarang, 15 Oktober 2026',
            'atasan_user_id' => $admin->id,
            'atasan_nama' => $admin->nama ?: $admin->name,
            'atasan_nip' => $admin->nip ?: '198001012005011001',
            'atasan_jabatan' => 'Kepala Bidang DPMPTSP Kota Semarang',
            'pelapor_user_id' => $admin->id,
            'pelapor_nama' => 'Nama Penguji, S.T.',
            'pelapor_nip' => '198501012010011002',
            'pelapor_jabatan' => 'Penyusun Laporan',
            'dibuat_oleh' => $admin->id,
            'dibuat_oleh_nama' => $admin->nama ?: $admin->name,
        ]);
        $this->notulenId = $record->id;

        $service = app(NotulenGeneratorService::class);

        // Test DOCX Generation
        $docxPath = $service->generateDocx($record);
        $this->assertFileExists($docxPath);
        $this->assertGreaterThan(1000, filesize($docxPath));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($docxPath));
        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($docxPath);

        $this->assertStringContainsString('NOTULEN PENGUJIAN SISTEM DOKUMEN 2026', $docXml);
        $this->assertStringContainsString('Surat Undangan Nomor 005/123/2026', $docXml);
        $this->assertStringContainsString('Kamis, 15 Oktober 2026', $docXml);
        $this->assertStringContainsString('09.00 WIB – Selesai', $docXml);
        $this->assertStringContainsString('Pembicara Pertama, S.Kom', $docXml);
        $this->assertStringContainsString('Diikuti oleh 20 peserta rapat.', $docXml);
        $this->assertStringContainsString('Dinas Kominfo', $docXml);
        $this->assertStringContainsString('Paragraf pertama hasil pembahasan teknis dengan', $docXml);
        $this->assertStringContainsString('teks tebal', $docXml);
        $this->assertStringContainsString('Poin evaluasi pertama', $docXml);
        $this->assertStringContainsString('Demikian Notulen ini dibuat untuk menjadikan periksa.', $docXml);
        $this->assertStringContainsString('Semarang, 15 Oktober 2026', $docXml);
        $this->assertStringContainsString('Kepala Bidang DPMPTSP Kota Semarang', $docXml);
        $this->assertStringContainsString('Nama Penguji, S.T.', $docXml);

        // Test PDF Generation
        $pdfPath = $service->generatePdf($record);
        $this->assertFileExists($pdfPath);
        $this->assertGreaterThan(1000, filesize($pdfPath));
        @unlink($pdfPath);
    }

    public function test_notulen_omits_empty_dasar_narasumber_and_peserta(): void
    {
        $admin = User::query()->where('role', 1)->firstOrFail();

        $record = Notulen::create([
            'judul' => 'RAPAT KOORDINASI TANPA NARASUMBER DAN PESERTA',
            'hari_tanggal' => 'Jumat, 16 Oktober 2026',
            'waktu' => '08.00 WIB – Selesai',
            'tempat' => 'Ruang Rapat DPMPTSP',
            'dasar' => null,
            'narasumber' => null,
            'peserta_deskripsi' => null,
            'peserta_daftar' => null,
            'hasil_acara' => '<ol><li>Poin evaluasi pertama</li><li>Poin evaluasi kedua</li></ol>',
            'penutup' => 'Demikian Notulen ini dibuat.',
            'tanggal_naskah' => 'Semarang, 16 Oktober 2026',
            'atasan_user_id' => $admin->id,
            'atasan_nama' => $admin->nama ?: $admin->name,
            'atasan_nip' => $admin->nip ?: '198001012005011001',
            'atasan_jabatan' => 'Kepala Bidang DPMPTSP',
            'pelapor_user_id' => $admin->id,
            'pelapor_nama' => 'Nama Penguji, S.T.',
            'pelapor_nip' => '198501012010011002',
            'pelapor_jabatan' => 'Penyusun Laporan',
            'dibuat_oleh' => $admin->id,
            'dibuat_oleh_nama' => $admin->nama ?: $admin->name,
        ]);
        $this->notulenId = $record->id;

        $service = app(NotulenGeneratorService::class);
        $docxPath = $service->generateDocx($record);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($docxPath));
        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($docxPath);

        // Pastikan judul dan waktu tetap ada
        $this->assertStringContainsString('RAPAT KOORDINASI TANPA NARASUMBER DAN PESERTA', $docXml);
        $this->assertStringContainsString('Waktu dan Tempat Pelaksanaan', $docXml);

        // Pastikan heading Dasar, Narasumber, dan Peserta ditiadakan secara bersih
        $this->assertStringNotContainsString('Dasar</w:t>', $docXml);
        $this->assertStringNotContainsString('Narasumber :</w:t>', $docXml);
        $this->assertStringNotContainsString('Peserta :</w:t>', $docXml);

        // Pastikan numbered list dari WYSIWYG menjadi bernomor 1. dan 2.
        $this->assertStringContainsString('1. Poin evaluasi pertama', $docXml);
        $this->assertStringContainsString('2. Poin evaluasi kedua', $docXml);

        // Pastikan konversi PDF juga berhasil tanpa error
        $pdfPath = $service->generatePdf($record);
        $this->assertFileExists($pdfPath);
        $this->assertGreaterThan(1000, filesize($pdfPath));
        @unlink($pdfPath);
    }
}


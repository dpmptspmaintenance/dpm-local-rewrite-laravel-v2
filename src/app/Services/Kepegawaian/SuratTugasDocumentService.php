<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\SuratTugas;
use App\Services\ArsipDigital\LocalArsipStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Snapshot dokumen .docx per Surat Tugas — satu-satunya jalur tulis ke
 * kolom `document_docx_path` + `override_*`.
 *
 * Prinsip: dokumen yang sudah pernah dibuat TIDAK di-regenerate otomatis.
 * Ini yang membuat koreksi manual di halaman "Dokumen" tidak hilang saat
 * surat dibuka/download lagi. Regenerate hanya lewat aksi eksplisit
 * (tombol "Generate Ulang dari Data" atau simpan form Edit — lihat
 * EditSuratTugas).
 */
class SuratTugasDocumentService
{
    private const FOLDER = 'kepegawaian/surat-tugas';

    public function __construct(
        private readonly SuratTugasGeneratorService $generator,
        private readonly LocalArsipStorage $storage,
    ) {}

    /**
     * Path lokal dokumen .docx yang siap dipakai: pakai snapshot tersimpan
     * bila ada, selain itu generate + simpan sekali.
     */
    public function materialize(SuratTugas $suratTugas): string
    {
        $path = (string) ($suratTugas->document_docx_path ?? '');

        if ($path !== '' && $this->storage->exists($path)) {
            return $this->storage->absolutePath($path);
        }

        return $this->regenerate($suratTugas);
    }

    /**
     * Render ulang dokumen dari nilai efektif (data + override) lalu timpa
     * snapshot. Dipakai tombol "Generate Ulang" dan setelah form Edit data.
     */
    public function regenerate(SuratTugas $suratTugas, ?int $userId = null): string
    {
        $tmp = $this->generator->generateDocx($suratTugas);

        try {
            // Muat ulang relasi pegawai agar override terbaru ikut terbaca.
            $suratTugas->unsetRelation('pegawai');

            $filename = $this->filename($suratTugas);
            $folder = self::FOLDER.'/'.$suratTugas->getKey();

            $uploaded = $this->storage->uploadTo($tmp, $filename, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $folder);
            $baru = $uploaded['id'];
        } finally {
            @unlink($tmp);
        }

        $lama = (string) ($suratTugas->document_docx_path ?? '');

        $suratTugas->forceFill([
            'document_docx_path' => $baru,
            'document_generated_at' => now(),
            'document_generated_by' => $userId ?? auth()->id(),
        ])->save();

        if ($lama !== '' && $lama !== $baru) {
            $this->storage->delete($lama);
        }

        Log::info('[surat-tugas] dokumen diregenerasi', [
            'surat_tugas_id' => $suratTugas->getKey(),
            'path' => $baru,
        ]);

        return $this->storage->absolutePath($baru);
    }

    /**
     * Simpan koreksi manual: nilai teks per surat + koreksi per baris
     * pegawai, lalu render ulang snapshot dari nilai tersebut.
     *
     * @param  array{judul?: ?string, hari_tanggal?: ?string, waktu?: ?string, tempat?: ?string, tanggal_naskah?: ?string, dasar_hukum?: ?string, penutup?: ?string}  $nilai
     * @param  array<int, array{nama?: ?string, jabatan?: ?string, pangkat_golongan?: ?string, dihilangkan?: bool}>  $pegawai
     */
    public function saveEdits(SuratTugas $suratTugas, array $nilai, array $pegawai = [], ?int $userId = null): string
    {
        $override = [
            'override_judul' => $this->bersih($nilai['judul'] ?? null),
            'override_hari_tanggal' => $this->bersih($nilai['hari_tanggal'] ?? null),
            'override_waktu' => $this->bersih($nilai['waktu'] ?? null),
            'override_tempat' => $this->bersih($nilai['tempat'] ?? null),
            'override_tanggal_naskah' => $this->bersih($nilai['tanggal_naskah'] ?? null),
            'override_dasar_hukum' => $this->bersih($nilai['dasar_hukum'] ?? null),
            'override_penutup' => $this->bersih($nilai['penutup'] ?? null),
        ];

        DB::connection('kepegawaian')->transaction(function () use ($suratTugas, $override, $pegawai, $userId): void {
            $suratTugas->forceFill($override + [
                'document_edited_at' => now(),
                'document_edited_by' => $userId ?? auth()->id(),
            ])->save();

            foreach ($pegawai as $id => $data) {
                $row = $suratTugas->pegawai()->find((int) $id);

                if (! $row) {
                    continue;
                }

                $row->forceFill([
                    'override_nama' => $this->bersih($data['nama'] ?? null),
                    'override_jabatan' => $this->bersih($data['jabatan'] ?? null),
                    'override_pangkat_golongan' => $this->bersih($data['pangkat_golongan'] ?? null),
                    'override_dihilangkan' => (bool) ($data['dihilangkan'] ?? false),
                ])->save();
            }
        });

        return $this->regenerate($suratTugas->fresh(), $userId);
    }

    /**
     * Buang SEMUA koreksi manual (kembali ke data asli) lalu render ulang.
     */
    public function resetOverrides(SuratTugas $suratTugas, ?int $userId = null): string
    {
        DB::connection('kepegawaian')->transaction(function () use ($suratTugas): void {
            $suratTugas->forceFill([
                'override_judul' => null,
                'override_hari_tanggal' => null,
                'override_waktu' => null,
                'override_tempat' => null,
                'override_tanggal_naskah' => null,
                'override_dasar_hukum' => null,
                'override_penutup' => null,
                'document_edited_at' => null,
                'document_edited_by' => null,
            ])->save();

            foreach ($suratTugas->pegawai()->get() as $row) {
                $row->forceFill([
                    'override_nama' => null,
                    'override_jabatan' => null,
                    'override_pangkat_golongan' => null,
                    'override_dihilangkan' => false,
                ])->save();
            }
        });

        return $this->regenerate($suratTugas->fresh(), $userId);
    }

    /**
     * Timpa snapshot .docx dengan biner hasil edit di browser (SuperDoc).
     *
     * Tidak memakai regenerate(): isi dokumen datang dari editor, bukan dari
     * template + data surat, jadi override teks TIDAK diubah. Ditandai
     * `document_edited_at` supaya peringatan "Generate Ulang" tetap muncul.
     */
    public function replaceSnapshot(SuratTugas $suratTugas, string $sourcePath, ?int $userId = null): string
    {
        if (! is_file($sourcePath)) {
            throw new \RuntimeException('Berkas hasil edit tidak ditemukan.');
        }

        $filename = $this->filename($suratTugas);
        $folder = self::FOLDER.'/'.$suratTugas->getKey();

        $uploaded = $this->storage->uploadTo(
            $sourcePath,
            $filename,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $folder,
        );

        $lama = (string) ($suratTugas->document_docx_path ?? '');

        $suratTugas->forceFill([
            'document_docx_path' => $uploaded['id'],
            'document_generated_at' => $suratTugas->document_generated_at ?? now(),
            'document_edited_at' => now(),
            'document_edited_by' => $userId ?? auth()->id(),
        ])->save();

        if ($lama !== '' && $lama !== $uploaded['id']) {
            $this->storage->delete($lama);
        }

        Log::info('[surat-tugas] snapshot ditimpa dari editor browser', [
            'surat_tugas_id' => $suratTugas->getKey(),
            'path' => $uploaded['id'],
        ]);

        return $uploaded['id'];
    }

    /**
     * @return string Path file PDF sementara (pemanggil hapus setelah dipakai)
     */
    public function renderPdf(SuratTugas $suratTugas): string
    {
        // PDF SELALU dari snapshot .docx tersimpan, bukan render ulang data —
        // supaya hasil PDF identik dengan Word yang sudah dikoreksi.
        return $this->generator->generatePdf($suratTugas->fresh(), $this->materialize($suratTugas));
    }

    public function downloadName(SuratTugas $suratTugas, string $extension): string
    {
        $slug = \Illuminate\Support\Str::slug((string) $suratTugas->judul) ?: 'surat-tugas-'.$suratTugas->getKey();

        return $slug.'.'.$extension;
    }

    public function deleteSnapshot(SuratTugas $suratTugas): void
    {
        $path = (string) ($suratTugas->document_docx_path ?? '');

        if ($path === '') {
            return;
        }

        $this->storage->delete($path);
    }

    private function filename(SuratTugas $suratTugas): string
    {
        return $this->downloadName($suratTugas, 'docx');
    }

    private function bersih(mixed $value): ?string
    {
        $teks = trim((string) $value);

        return $teks === '' ? null : $teks;
    }
}

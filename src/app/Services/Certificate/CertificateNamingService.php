<?php

namespace App\Services\Certificate;

use App\Models\Certificate\CertificateDataset;
use App\Models\Certificate\CertificateTemplate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Nama berkas + pembungkus ZIP untuk unduhan sertifikat. Nama diambil dari
 * pola template (mis. "sertifikat anti korupsi <<nama>>") — placeholder
 * diganti nilai baris Excel, lalu dibersihkan dari karakter ilegal path.
 */
class CertificateNamingService
{
    public function fileNameFor(CertificateTemplate $template, array $row, int $rowIndex): string
    {
        $pattern = filled($template->file_name_pattern)
            ? (string) $template->file_name_pattern
            : 'sertifikat <<'.($template->fields->first()->key ?? 'nama').'>>';

        $name = preg_replace_callback(
            '/<<\s*([a-zA-Z0-9_\- ]+?)\s*>>/u',
            function (array $m) use ($row): string {
                $key = trim($m[1]);
                $key = Str::slug($key, '_');

                return (string) ($row[$key] ?? '');
            },
            $pattern,
        ) ?? $pattern;

        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]/u', '-', $name) ?? $name;
        $name = trim($name, " .\t");

        if ($name === '') {
            $name = 'sertifikat';
        }

        return Str::limit($name, 120, '');
    }

    /**
     * @param  list<array{name: string, binary: string}>  $outputs
     */
    public function zip(array $outputs, string $zipName): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cert-zip');

        $zip = new \ZipArchive;

        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat berkas ZIP.');
        }

        $used = [];

        foreach ($outputs as $output) {
            $name = $output['name'];
            $suffix = 2;

            while (in_array($name, $used, true)) {
                $name = Str::beforeLast($output['name'], '.pdf')." ({$suffix}).pdf";
                $suffix++;
            }

            $used[] = $name;
            $zip->addFromString($name, $output['binary']);
        }

        $zip->close();

        return response()
            ->download($tmp, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function pdfResponse(string $binary, string $name): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cert-pdf');
        file_put_contents($tmp, $binary);

        return response()
            ->download($tmp, $name, ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    public function zipName(CertificateTemplate $template, CertificateDataset $dataset): string
    {
        $base = Str::slug($template->title) ?: 'sertifikat';

        return $base.'-'.$dataset->row_count.'-'.now()->format('Ymd-His').'.zip';
    }
}

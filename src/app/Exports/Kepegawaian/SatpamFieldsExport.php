<?php

namespace App\Exports\Kepegawaian;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Unduh data satpam dengan kolom pilihan pengguna. Pola sama seperti
 * PegawaiFieldsExport: field dicentang di dialog, urutan kolom ikut LABELS.
 */
class SatpamFieldsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * Key = nama kolom DB / virtual, value = judul kolom Excel.
     */
    public const LABELS = [
        'nama' => 'Nama',
        'nik' => 'NIK',
        'telepon' => 'No. Telepon/HP',
        'alamat' => 'Alamat',
        'kelengkapan' => 'Kelengkapan Berkas',
        'jumlah_arsip' => 'Jumlah Arsip',
        'keterangan' => 'Keterangan',
        'created_at' => 'Ditambahkan',
    ];

    /**
     * Field turunan (bukan kolom DB) yang dihitung saat map().
     */
    public const VIRTUAL = ['kelengkapan', 'jumlah_arsip'];

    /**
     * Kolom DB yang perlu di-SELECT untuk daftar field terpilih.
     *
     * @param  list<string>  $fields
     * @return list<string>
     */
    public static function databaseColumns(array $fields): array
    {
        $columns = array_values(array_diff(array_map('strval', $fields), self::VIRTUAL));

        // Field virtual hitung lewat relasi arsip — butuh id selalu ikut.
        if (array_intersect(array_map('strval', $fields), self::VIRTUAL) !== [] && ! in_array('id', $columns, true)) {
            $columns[] = 'id';
        }

        return $columns;
    }

    /**
     * @param  list<string>  $fields  nama kolom terpilih (urut sesuai LABELS)
     */
    public function __construct(
        private readonly Builder $query,
        private readonly array $fields,
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return array_map(
            fn (string $field): string => self::LABELS[$field] ?? $field,
            $this->fields,
        );
    }

    public function map($row): array
    {
        $values = [];

        foreach ($this->fields as $field) {
            $values[] = match ($field) {
                'kelengkapan' => $this->kelengkapan($row),
                'jumlah_arsip' => $row->relationLoaded('arsipCount') ? $row->getAttribute('arsipCount') : $row->arsip()->count(),
                default => $this->nilaiKolom($row, $field),
            };
        }

        return $values;
    }

    private function kelengkapan($row): string
    {
        $khusus = 0;

        foreach (\App\Services\Kepegawaian\SatpamArsipService::JENIS_KHUSUS as $jenis) {
            if ($row->arsip()->where('kategori', $jenis)->exists()) {
                $khusus++;
            }
        }

        $lainnya = $row->arsip()->where('kategori', \App\Services\Kepegawaian\SatpamArsipService::JENIS_LAINNYA)->count();

        return $khusus.'/'.count(\App\Services\Kepegawaian\SatpamArsipService::JENIS_KHUSUS).' khusus + '.$lainnya.' lainnya';
    }

    private function nilaiKolom($row, string $field): mixed
    {
        $value = $row->getAttribute($field);

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y H:i');
        }

        return $value;
    }
}

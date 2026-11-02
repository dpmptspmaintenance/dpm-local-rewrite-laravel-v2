<?php

namespace App\Exports\Kepegawaian;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Unduh data pegawai dengan kolom pilihan pengguna. Hanya field yang
 * dicentang di dialog yang menjadi kolom Excel — urutan kolom mengikuti
 * urutan LABELS agar hasil selalu konsisten.
 */
class PegawaiFieldsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * Seluruh kolom tabel pegawai_profil beserta label Indonesianya.
     * Key = nama kolom DB, value = judul kolom Excel.
     */
    public const LABELS = [
        'nip' => 'NIP',
        'nama' => 'Nama',
        'nama_kapital' => 'Nama (Kapital Setiap Kata)',
        'nip_lama' => 'NIP Lama',
        'pangkat' => 'Pangkat',
        'golongan' => 'Golongan',
        'jabatan' => 'Jabatan',
        'status_pegawai' => 'Status Pegawai',
        'gender' => 'Jenis Kelamin',
        'agama' => 'Agama',
        'status_perkawinan' => 'Status Perkawinan',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'usia_keterangan' => 'Usia',
        'pendidikan' => 'Pendidikan',
        'kelas_jabatan' => 'Kelas Jabatan',
        'capaian_bangkom' => 'Capaian Bangkom',
        'bup_but' => 'BUP/BUT',
        'tmt_bup_but' => 'TMT BUP/BUT',
        'tmt_golongan' => 'TMT Golongan',
        'kgb_selanjutnya' => 'KGB Selanjutnya',
        'kgb_gaji_pokok' => 'KGB Gaji Pokok',
        'kgb_masa_kerja_saat' => 'KGB Masa Kerja Saat',
        'kgb_masa_kerja_terakhir' => 'KGB Masa Kerja Terakhir',
        'kgb_tmt_golongan_terakhir' => 'KGB TMT Golongan Terakhir',
        'alamat_ktp' => 'Alamat KTP',
        'rt_ktp' => 'RT KTP',
        'rw_ktp' => 'RW KTP',
        'kelurahan_ktp' => 'Kelurahan KTP',
        'kecamatan_ktp' => 'Kecamatan KTP',
        'kota_ktp' => 'Kota KTP',
        'provinsi_ktp' => 'Provinsi KTP',
        'kode_pos_ktp' => 'Kode Pos KTP',
        'alamat_domisili' => 'Alamat Domisili',
        'rt_domisili' => 'RT Domisili',
        'rw_domisili' => 'RW Domisili',
        'kelurahan_domisili' => 'Kelurahan Domisili',
        'kecamatan_domisili' => 'Kecamatan Domisili',
        'kota_domisili' => 'Kota Domisili',
        'provinsi_domisili' => 'Provinsi Domisili',
        'kode_pos_domisili' => 'Kode Pos Domisili',
        'jenis_domisili' => 'Jenis Domisili',
        'sumber_url' => 'Sumber',
        'created_at' => 'Diimpor',
    ];

    /**
     * Field turunan (bukan kolom DB) yang dihitung dari accessor model.
     * Tidak boleh ikut SELECT, dan butuh kolom sumbernya ikut dipilih.
     */
    public const VIRTUAL = ['nama_kapital'];

    /**
     * Kolom DB yang perlu di-SELECT untuk daftar field terpilih.
     *
     * @param  list<string>  $fields
     * @return list<string>
     */
    public static function databaseColumns(array $fields): array
    {
        $columns = array_values(array_diff($fields, self::VIRTUAL));

        // nama_kapital dihitung dari kolom nama.
        if (in_array('nama_kapital', $fields, true) && ! in_array('nama', $columns, true)) {
            $columns[] = 'nama';
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
            // nama_kapital memakai accessor getNamaKapitalAttribute().
            $value = $row->getAttribute($field);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('d-m-Y');
            }

            $values[] = $value;
        }

        return $values;
    }
}

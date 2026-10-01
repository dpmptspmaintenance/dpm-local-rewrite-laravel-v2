<?php

namespace App\Http\Controllers\RapatKita;

use App\Http\Controllers\Controller;
use App\Models\RapatKitaSchedule;
use App\Services\RapatKita\SheetsMirror;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $rapatSebelumnya = RapatKitaSchedule::aktif()
            ->select('id', 'title')
            ->orderByDesc('start_datetime')
            ->get();

        $schedules = RapatKitaSchedule::aktif()
            ->withExists('notulen')
            ->get()
            ->mapWithKeys(function (RapatKitaSchedule $row) {
                $row->has_notulen = $row->notulen_exists;
                $row->sdate = $row->start_datetime?->translatedFormat('d F Y H:i');
                $row->edate = $row->end_datetime?->translatedFormat('d F Y H:i');
                $row->cdate = $row->created_at?->translatedFormat('d F Y H:i');

                return [$row->id => $row];
            });

        return view('rapatkita.jadwal.index', [
            'rapatSebelumnya' => $rapatSebelumnya,
            'schedRes' => $schedules,
            'userBidang' => $user->bidang,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:rapat_kita_schedule_list,id'],
            'title' => ['required', 'string', 'max:255'],
            'dispo' => ['nullable', 'string', 'max:255'],
            'dihadiri' => ['required', 'string', 'max:100'],
            'pelaksana' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'pilih-lokasi' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'hari' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'konfirmasi' => ['nullable', 'in:iya,tidak'],
            'rapat_sebelumnya' => ['nullable', 'integer', 'exists:rapat_kita_schedule_list,id'],
        ]);

        $lokasi = ($validated['pilih-lokasi'] ?? '') === 'Masukan Lokasi Manual'
            ? ($validated['lokasi'] ?? '')
            : ($validated['pilih-lokasi'] ?? '');

        if ($lokasi === '') {
            return back()->withInput()->with('error', 'Lokasi wajib diisi.');
        }

        $start = Carbon::parse($validated['hari'].' '.$validated['jam_mulai']);
        $end = $start->copy()->addHours(2);

        $conflict = RapatKitaSchedule::aktif()
            ->where('lokasi', $lokasi)
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->when($validated['id'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();

        if ($conflict) {
            return back()->withInput()->with('error', "Lokasi '$lokasi' sudah terisi pada jam tersebut.");
        }

        $user = Auth::user();
        $data = [
            'title' => strip_tags($validated['title']),
            'lokasi' => $lokasi,
            'dispo' => strip_tags($validated['dispo'] ?? ''),
            'dihadiri' => $validated['dihadiri'],
            'pelaksana' => $validated['pelaksana'],
            'description' => strip_tags($validated['description'] ?? ''),
            'start_datetime' => $start,
            'end_datetime' => $end,
            'bidang_pembuat_jadwal' => $user->bidang,
            'id_rapat_sebelumnya' => ($validated['konfirmasi'] ?? '') === 'iya'
                ? ($validated['rapat_sebelumnya'] ?? null)
                : null,
            'is_aktif' => 1,
        ];

        if ($id = $validated['id'] ?? null) {
            $schedule = RapatKitaSchedule::aktif()->findOrFail($id);
            $this->authorizeBidang($schedule, $user->bidang);
            $schedule->update($data);
            $message = 'Jadwal diperbarui.';
        } else {
            RapatKitaSchedule::create($data + ['created_by' => $user->id]);
            $message = 'Jadwal ditambahkan.';
        }

        app(SheetsMirror::class)->syncAll();

        return redirect()->route('rapatkita.jadwal.index')->with('success', $message);
    }

    public function destroy(RapatKitaSchedule $schedule)
    {
        $this->authorizeBidang($schedule, Auth::user()->bidang);

        $schedule->update(['is_aktif' => 0]);

        app(SheetsMirror::class)->syncAll();

        return redirect()->route('rapatkita.jadwal.index')->with('success', 'Kegiatan berhasil dihapus.');
    }

    private function authorizeBidang(RapatKitaSchedule $schedule, ?string $bidang): void
    {
        if ($schedule->bidang_pembuat_jadwal !== $bidang && $bidang !== 'admin') {
            abort(403, 'Anda tidak berhak mengubah jadwal ini.');
        }
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'tanggal_awal' => ['required', 'date'],
            'tanggal_akhir' => ['required', 'date', 'after_or_equal:tanggal_awal'],
        ]);

        $schedules = RapatKitaSchedule::aktif()
            ->whereDate('start_datetime', '>=', $validated['tanggal_awal'])
            ->whereDate('start_datetime', '<=', $validated['tanggal_akhir'])
            ->orderBy('start_datetime')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator('Rapat Kita System')
            ->setTitle('Jadwal Rapat')
            ->setDescription("Daftar Jadwal Rapat dari {$validated['tanggal_awal']} sampai {$validated['tanggal_akhir']}");

        $headers = ['No', 'Judul Kegiatan', 'Lokasi', 'Disposisi', 'Dihadiri Oleh', 'Pelaksana', 'Deskripsi', 'Tanggal', 'Jam'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($schedules as $index => $schedule) {
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, $schedule->title);
            $sheet->setCellValue('C'.$row, $schedule->lokasi);
            $sheet->setCellValue('D'.$row, $schedule->dispo);
            $sheet->setCellValue('E'.$row, $schedule->dihadiri);
            $sheet->setCellValue('F'.$row, $schedule->pelaksana);
            $sheet->setCellValue('G'.$row, $schedule->description);
            $sheet->setCellValue('H'.$row, $schedule->start_datetime?->format('d/m/Y'));
            $sheet->setCellValue('I'.$row, $schedule->start_datetime?->format('H:i'));
            $row++;
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2A52BE']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        if ($row > 2) {
            $dataStyle = [
                'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFD3D3D3']]],
            ];
            $sheet->getStyle('A2:I'.($row - 1))->applyFromArray($dataStyle);

            for ($i = 2; $i <= $row - 1; $i++) {
                $fillColor = $i % 2 == 0 ? 'FFF0F8FF' : 'FFFFFFFF';
                $sheet->getStyle('A'.$i.':I'.$i)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($fillColor);
            }

            $sheet->getStyle('A2:A'.($row - 1))->getAlignment()->setHorizontal('center');
            $sheet->getStyle('H2:I'.($row - 1))->getAlignment()->setHorizontal('center');
        }

        foreach (['A' => 5, 'B' => 30, 'C' => 20, 'D' => 20, 'E' => 25, 'F' => 20, 'G' => 40, 'H' => 12, 'I' => 8] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->freezePane('A2');

        $filename = "Jadwal Rapat {$validated['tanggal_awal']} sampai {$validated['tanggal_akhir']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

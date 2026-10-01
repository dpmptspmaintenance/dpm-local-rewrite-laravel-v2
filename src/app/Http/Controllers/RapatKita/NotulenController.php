<?php

namespace App\Http\Controllers\RapatKita;

use App\Http\Controllers\Controller;
use App\Models\RapatKitaNotulen;
use App\Models\RapatKitaSchedule;
use App\Services\RapatKita\SheetsMirror;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotulenController extends Controller
{
    public function index()
    {
        $notulenList = RapatKitaNotulen::select('id', 'id_kegiatan', 'nama_kegiatan', 'tanggal')
            ->orderByDesc('tanggal')
            ->get();

        return view('rapatkita.notulen.daftar', ['notulenList' => $notulenList]);
    }

    public function create(RapatKitaSchedule $schedule)
    {
        return view('rapatkita.notulen.create', ['schedule' => $schedule]);
    }

    public function store(Request $request, RapatKitaSchedule $schedule)
    {
        $validated = $this->validateNotulen($request);

        $fotos = $this->uploadFotos($request);

        RapatKitaNotulen::create([
            ...$validated,
            ...$fotos,
            'id_kegiatan' => $schedule->id,
            'created_by' => Auth::user()->nama ?? Auth::user()->name,
            'user_id_pembuat_notulen' => Auth::id(),
        ]);

        app(SheetsMirror::class)->syncAll();

        return redirect()->route('rapatkita.jadwal.index')->with('success', 'Notulen ditambahkan.');
    }

    public function show(RapatKitaNotulen $notulen)
    {
        return view('rapatkita.notulen.show', ['notulen' => $notulen]);
    }

    public function edit(RapatKitaNotulen $notulen)
    {
        return view('rapatkita.notulen.edit', ['notulen' => $notulen]);
    }

    public function update(Request $request, RapatKitaNotulen $notulen)
    {
        $validated = $this->validateNotulen($request, namaKegiatanKey: 'nama_kegiatan');

        $fotos = $this->uploadFotos($request);

        $notulen->update([...$validated, ...$fotos]);

        app(SheetsMirror::class)->syncAll();

        return redirect()->route('rapatkita.notulen.index')->with('success', 'Data notulen berhasil diubah!');
    }

    private function validateNotulen(Request $request, string $namaKegiatanKey = 'title'): array
    {
        $rules = [
            'jam_mulai' => ['required', 'date_format:H:i'],
            'ketua' => ['required', 'string', 'max:255'],
            'sekretaris' => ['required', 'string', 'max:255'],
            'anggota' => ['nullable', 'string'],
            'susunan' => ['nullable', 'string'],
            'pembahasan' => ['nullable', 'string'],
            'hasil' => ['nullable', 'string'],
            'nama_pimpinan' => ['required', 'string', 'max:255'],
            'jabatan_pimpinan' => ['required', 'string', 'max:255'],
            'nama_notulis' => ['required', 'string', 'max:255'],
            'jabatan_notulis' => ['required', 'string', 'max:255'],
            'foto.*' => ['nullable', 'image', 'max:5120'],
            'foto_surat' => ['nullable', 'image', 'max:5120'],
        ];

        if ($namaKegiatanKey === 'title') {
            $rules['hari'] = ['required', 'date'];
            $rules['title'] = ['required', 'string', 'max:255'];
        } else {
            $rules['tanggal'] = ['required', 'date'];
            $rules['nama_kegiatan'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        return [
            'tanggal' => $validated['hari'] ?? $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'nama_kegiatan' => $validated['title'] ?? $validated['nama_kegiatan'],
            'ketua' => $validated['ketua'],
            'sekretaris' => $validated['sekretaris'],
            'anggota' => $validated['anggota'] ?? null,
            'susunan' => $validated['susunan'] ?? null,
            'pembahasan' => $validated['pembahasan'] ?? null,
            'hasil' => $validated['hasil'] ?? null,
            'nama_pimpinan' => $validated['nama_pimpinan'],
            'jabatan_pimpinan' => $validated['jabatan_pimpinan'],
            'nama_notulis' => $validated['nama_notulis'],
            'jabatan_notulis' => $validated['jabatan_notulis'],
        ];
    }

    private function uploadFotos(Request $request): array
    {
        $paths = [];

        foreach ([0 => 'foto_1', 1 => 'foto_2', 2 => 'foto_3'] as $index => $column) {
            $file = $request->file("foto.{$index}");
            if ($file) {
                $paths[$column] = $file->store('notulen/foto', 'public');
            }
        }

        if ($file = $request->file('foto_surat')) {
            $paths['foto_surat'] = $file->store('notulen/surat', 'public');
        }

        return $paths;
    }
}

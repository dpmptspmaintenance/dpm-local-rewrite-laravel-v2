<x-filament-panels::page>
    @php($m = $this->getMatriks())
    @php($totalJenis = $this->getTotalPerJenis())
    @php($maks = $m['maks'])

    <div class="mb-4 max-w-xs">
        {{ $this->form }}
    </div>

    @if (empty($m['baris']))
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tidak ada data kompetensi pada tahun ini.
            </p>
        </x-filament::section>
    @else
        <x-filament::section
            heading="Matriks Jabatan × Jenis Diklat"
            description="Angka di sel = jumlah event kompetensi tahun terpilih. Warna makin gelap = makin banyak. Angka murni sebaran — belum ada target diklat dari sumber data."
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="sticky start-0 z-10 border-b border-gray-200 bg-white px-3 py-2 text-start font-semibold text-gray-900 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                Jabatan
                            </th>
                            @foreach ($m['jenis'] as $jenis)
                                <th class="border-b border-gray-200 px-3 py-2 text-center align-bottom text-xs font-semibold text-gray-700 dark:border-white/10 dark:text-gray-300">
                                    <span class="inline-block max-w-[9rem] whitespace-normal">{{ $jenis }}</span>
                                </th>
                            @endforeach
                            <th class="border-b border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-900 dark:border-white/10 dark:text-white">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($m['baris'] as $baris)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <th class="sticky start-0 z-10 bg-white px-3 py-2 text-start font-normal text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                    {{ $baris['jabatan'] }}
                                </th>

                                @foreach ($m['jenis'] as $jenis)
                                    @php($sel = $baris['sel'][$jenis])
                                    @php($event = $sel['event'])
                                    {{-- Intensitas 5 tingkat dari nilai terbesar; 0 = kosong. --}}
                                    @php($tingkat = $maks > 0 && $event > 0 ? (int) ceil($event / $maks * 4) : 0)
                                    <td
                                        class="px-3 py-2 text-center tabular-nums"
                                        @if ($tingkat === 0)
                                            style="background: transparent"
                                        @elseif ($tingkat === 1)
                                            style="background: #dbeafe; color: #1e3a8a"
                                        @elseif ($tingkat === 2)
                                            style="background: #93c5fd; color: #1e3a8a"
                                        @elseif ($tingkat === 3)
                                            style="background: #3b82f6; color: #ffffff"
                                        @else
                                            style="background: #1d4ed8; color: #ffffff"
                                        @endif
                                        title="{{ $baris['jabatan'] }} — {{ $jenis }}: {{ $event }} event, {{ $sel['jam'] }} JP"
                                    >
                                        @if ($event > 0)
                                            {{ $event }}
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">·</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-3 py-2 text-center font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $baris['total_event'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-white/20">
                            <th class="sticky start-0 z-10 bg-white px-3 py-2 text-start font-semibold text-gray-900 dark:bg-gray-900 dark:text-white">
                                Total
                            </th>
                            @foreach ($m['jenis'] as $jenis)
                                <td class="px-3 py-2 text-center font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $totalJenis[$jenis]['event'] ?? 0 }}
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ collect($m['baris'])->sum('total_event') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span>Intensitas:</span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block h-3 w-6 rounded-sm" style="background: #dbeafe"></span> sedikit
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block h-3 w-6 rounded-sm" style="background: #93c5fd"></span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block h-3 w-6 rounded-sm" style="background: #3b82f6"></span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block h-3 w-6 rounded-sm" style="background: #1d4ed8"></span> banyak
                    (maks {{ $maks }} event)
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block h-3 w-6 rounded-sm border border-gray-200 dark:border-white/10"></span> tidak ada
                </span>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

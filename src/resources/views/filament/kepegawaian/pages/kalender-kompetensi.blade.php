<x-filament-panels::page>
    @php($jenisColors = \App\Filament\Kepegawaian\Pages\KalenderKompetensi::JENIS_COLORS)
    @php($barHeight = \App\Filament\Kepegawaian\Pages\KalenderKompetensi::BAR_HEIGHT)

    <style>
        .kal-week { position: relative; border-top: 1px solid; }
        .kal-cells { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .kal-cell { min-width: 0; }
        .kal-bar {
            position: absolute;
            height: {{ $barHeight }}px;
            line-height: {{ $barHeight }}px;
            border-radius: 3px;
            padding: 0 4px;
            font-size: 10px;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: default;
        }
        .kal-header { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .kal-overflow {
            position: absolute;
            left: 2px;
            right: 2px;
            font-size: 10px;
            line-height: 14px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: default;
        }
    </style>

    <div class="mb-4 max-w-xs">
        {{ $this->form }}
    </div>

    <x-filament::section class="mb-4">
        <x-slot name="heading">Legenda</x-slot>
        <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
            @foreach ($jenisColors as $jenis => $warna)
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-sm" style="background: {{ $warna }}"></span>
                    {{ $jenis }}
                </span>
            @endforeach
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm" style="background: {{ \App\Filament\Kepegawaian\Pages\KalenderKompetensi::DEFAULT_COLOR }}"></span>
                Lainnya
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm bg-red-100 ring-1 ring-red-300"></span>
                Hari Libur
            </span>
        </div>
    </x-filament::section>

    @php($headers = $this->dayHeaders())

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2 2xl:grid-cols-3">
        @foreach ($this->buildYear() as $bulan)
            <x-filament::section>
                <x-slot name="heading">{{ $bulan['label'] }}</x-slot>
                <x-slot name="description">{{ $bulan['jumlah_event'] }} event</x-slot>

                <div class="text-center">
                    <div class="kal-header pb-1 text-[11px] font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        @foreach ($headers as $header)
                            <div>{{ $header }}</div>
                        @endforeach
                    </div>

                    @foreach ($bulan['weeks'] as $pekan)
                        @php($laneCount = $pekan['lanes'])
                        @php($overflowExtra = $pekan['overflow'] ? 14 : 0)
                        @php($cellHeight = $laneCount * $barHeight + ($laneCount > 0 ? 4 : 0) + 22 + $overflowExtra)

                        <div class="kal-week border-gray-200 dark:border-white/10">
                            <div class="kal-cells">
                                @foreach ($pekan['cells'] as $cell)
                                    <div
                                        class="kal-cell border-e border-gray-100 p-1 text-start dark:border-white/5"
                                        style="min-height: {{ $cellHeight }}px"
                                    >
                                        <span @class([
                                            'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-xs font-semibold',
                                            'bg-primary-600 text-white' => $cell['is_today'],
                                            'text-gray-400 dark:text-gray-500' => ! $cell['is_today'] && $cell['is_weekend'],
                                            'text-gray-700 dark:text-gray-200' => ! $cell['is_today'] && ! $cell['is_weekend'],
                                        ])>{{ $cell['day'] }}</span>

                                        @if ($cell['libur'])
                                            <div class="mt-0.5 truncate text-[9px] font-medium text-red-600 dark:text-red-400" title="Libur: {{ $cell['libur'] }}">
                                                {{ $cell['libur'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Bar event: absolute, 1 kolom = 100/7 %. --}}
                            @foreach ($pekan['bars'] as $bar)
                                @php($left = $bar['col_start'] / 7 * 100)
                                @php($width = $bar['span'] / 7 * 100)
                                <div
                                    class="kal-bar"
                                    style="left: calc({{ $left }}% + 2px); width: calc({{ $width }}% - 4px); top: {{ 22 + $bar['lane'] * $barHeight }}px; background: {{ $bar['color'] }};"
                                    title="{{ $bar['tooltip'] }}"
                                >
                                    @if ($bar['continues_left'])&lsaquo; @endif{{ $bar['label'] }}@if ($bar['continues_right']) &rsaquo;@endif
                                </div>
                            @endforeach

                            @if ($pekan['overflow'])
                                @php($overflowTop = 22 + ($pekan['lanes']) * $barHeight)
                                <div
                                    class="kal-overflow"
                                    style="top: {{ $overflowTop }}px"
                                    title="{{ implode("\n", $pekan['overflow']) }}"
                                >+{{ count($pekan['overflow']) }} event lain (arahkan untuk lihat)</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>

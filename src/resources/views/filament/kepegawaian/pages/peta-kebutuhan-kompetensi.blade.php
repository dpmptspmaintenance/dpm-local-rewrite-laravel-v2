<x-filament-panels::page>
    @php($stats = $this->getStats())

    <div class="mb-4 max-w-xs">
        {{ $this->form }}
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total Pegawai', 'value' => $stats['total_pegawai'], 'color' => 'primary', 'icon' => 'heroicon-m-users', 'hint' => 'Seluruh profil terdata'],
            ['label' => 'Punya Kompetensi', 'value' => $stats['berkompetensi'], 'color' => 'success', 'icon' => 'heroicon-m-academic-cap', 'hint' => 'Ada event tahun ini'],
            ['label' => 'Belum Ada', 'value' => $stats['belum'], 'color' => 'danger', 'icon' => 'heroicon-m-x-circle', 'hint' => 'Tanpa event tahun ini'],
            ['label' => 'Total Event', 'value' => $stats['event'], 'color' => 'info', 'icon' => 'heroicon-m-calendar-days', 'hint' => 'Baris kompetensi'],
            ['label' => 'Total JP', 'value' => $stats['jam'], 'color' => 'warning', 'icon' => 'heroicon-m-clock', 'hint' => 'Akumulasi jam pelajaran'],
        ] as $stat)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stat['value']) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
                    </div>
                    <x-filament::icon
                        :icon="$stat['icon']"
                        @class([
                            'h-7 w-7 shrink-0',
                            'text-primary-500' => $stat['color'] === 'primary',
                            'text-success-500' => $stat['color'] === 'success',
                            'text-danger-500' => $stat['color'] === 'danger',
                            'text-info-500' => $stat['color'] === 'info',
                            'text-warning-500' => $stat['color'] === 'warning',
                        ])
                    />
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6">
        <x-filament::section
            heading="Sebaran per Jabatan"
            description="Diurutkan dari jabatan dengan pegawai berkompetensi terbanyak. Angka murni sebaran — belum ada target JP dari sumber data."
        >
            {{ $this->table }}
        </x-filament::section>

        @php($kosong = $this->jabatanKosong())

        @if ($kosong->isNotEmpty())
            <x-filament::section
                heading="Jabatan Tanpa Kompetensi Tahun Ini ({{ $kosong->count() }})"
                description="Jabatan yang ada di profil pegawai tapi belum punya satu pun event kompetensi pada tahun terpilih."
                collapsible
            >
                <div class="flex flex-wrap gap-2">
                    @foreach ($kosong as $jabatan)
                        <span class="inline-flex rounded-full bg-danger-50 px-2.5 py-1 text-xs font-medium text-danger-700 ring-1 ring-danger-200 dark:bg-danger-500/10 dark:text-danger-300 dark:ring-danger-500/30">
                            {{ $jabatan }}
                        </span>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    @php($stats = $this->getStats())

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([
            ['label' => 'Total PNS ≥ 10 Tahun', 'value' => $stats['total'], 'color' => 'primary', 'icon' => 'heroicon-m-users', 'hint' => 'PNS aktif, masa kerja \u2265 10 tahun'],
            ['label' => 'Perlu Diusulkan', 'value' => $stats['perlu_diusulkan'], 'color' => 'warning', 'icon' => 'heroicon-m-exclamation-triangle', 'hint' => 'Ada tingkat SLKS yang wajib diajukan'],
            ['label' => 'Lengkap', 'value' => $stats['lengkap'], 'color' => 'success', 'icon' => 'heroicon-m-check-badge', 'hint' => 'Sudah punya semua tingkat yang eligible'],
        ] as $stat)
            <x-filament::section class="fi-stat">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stat['value']) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
                    </div>
                    <x-filament::icon
                        :icon="$stat['icon']"
                        @class([
                            'h-8 w-8 shrink-0',
                            'text-primary-500' => $stat['color'] === 'primary',
                            'text-warning-500' => $stat['color'] === 'warning',
                            'text-success-500' => $stat['color'] === 'success',
                        ])
                    />
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section
        class="mt-6"
        description="Sesuai syarat pengusulan: SLKS harus diusulkan urut dari tingkat terendah yang belum pernah dimiliki — tidak boleh lompat (mis. masa kerja 23 tahun tanpa SLKS sama sekali wajib diusulkan 10 tahun dulu, bukan langsung 20 tahun). Bila tingkat lebih tinggi tercatat tapi yang lebih rendah kosong, tingkat bawah dianggap sudah terpenuhi (anggap belum diisi di SIMPATIK). Data 'dimiliki' berasal dari impor SISDM maupun tambahan manual (halaman Daftar Penghargaan) — pakai tambah manual bila SIMPATIK/SISDM staf belum diisi lengkap."
    >
        <x-slot name="heading">Checklist per Pegawai</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>

<x-filament-panels::page>
    @php
        $cards = $this->categoryCards();
        $activeSlug = $this->categorySlug;
    @endphp

    {{-- Filter kategori: chip daftar di atas grid kartu dokumen --}}
    @if ($cards !== [])
        <div class="mb-5 flex flex-wrap items-center gap-2">
            <a
                href="{{ static::getUrl() }}"
                class="rounded-full border px-3 py-1 text-sm font-medium transition {{ $activeSlug === null
                    ? 'border-primary-600 bg-primary-600 text-white shadow'
                    : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:text-primary-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:text-primary-400' }}"
            >
                Semua
            </a>

            @foreach ($cards as $card)
                <a
                    href="{{ $card['url'] }}"
                    class="rounded-full border px-3 py-1 text-sm font-medium transition {{ $activeSlug === $card['slug']
                        ? 'border-primary-600 bg-primary-600 text-white shadow'
                        : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:text-primary-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:text-primary-400' }}"
                >
                    {{ $card['name'] }}
                </a>
            @endforeach
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>

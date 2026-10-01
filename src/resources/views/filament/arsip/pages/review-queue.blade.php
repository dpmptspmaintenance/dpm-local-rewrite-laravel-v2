<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Antrean Verifikasi</x-slot>

        {{ $this->table }}
    </x-filament::section>

    @php
        $document = $this->selectedDocument();
    @endphp

    @if ($document)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Berkas</x-slot>

                {{-- Pratinjau iframe dihapus: verifikasi dilakukan dengan membuka
                     berkas di tab baru (sesuai permintaan), bukan embed di halaman ini. --}}
                <div class="grid gap-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Sumber</span>
                        <span class="font-medium text-gray-950 dark:text-white">
                            {{ $document->isUrl() ? 'Tautan' : 'Berkas ('.$document->files->count().')' }}
                        </span>
                    </div>
                    @if ($document->isUrl())
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400">Tautan</span>
                            <span class="font-medium text-gray-950 dark:text-white break-all">{{ \Illuminate\Support\Str::limit($document->source_url, 70) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400">Folder Drive</span>
                            <span class="font-medium text-gray-950 dark:text-white">
                                {{ $document->drive_folder_name ?? 'etc (penampung, belum disetujui)' }}
                            </span>
                        </div>
                    @endif
                </div>

                @if (! $document->isUrl())
                    @if ($document->files->isNotEmpty())
                        <ul class="mt-4 grid gap-1.5 text-sm">
                            @foreach ($document->files as $file)
                                <li class="flex items-center justify-between gap-3">
                                    <a href="{{ $file->openUrl() }}" target="_blank" class="text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $file->original_filename ?? $file->google_file_id }}
                                    </a>
                                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($file->file_extension ?? '—') }} · {{ number_format(($file->file_size ?? 0) / 1024, 1, ',', '.') }} KB
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Dokumen ini belum punya berkas fisik.</p>
                    @endif
                @endif

                <x-filament::button
                    tag="a"
                    href="{{ $document->openUrl() }}"
                    target="_blank"
                    color="gray"
                    icon="heroicon-o-arrow-top-right-on-square"
                    class="mt-4"
                >
                    {{ $document->isUrl() ? 'Buka di Tab Baru' : 'Buka Folder Drive' }}
                </x-filament::button>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Verifikasi Dokumen</x-slot>
                <x-slot name="description">
                    Diunggah oleh {{ $document->creator?->nama ?? '—' }} · {{ $document->created_at?->format('d M Y H:i') }}
                </x-slot>

                <form wire:submit.prevent class="grid gap-4">
                    {{ $this->form }}
                </form>

                <div class="mt-6 grid gap-3">
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-check-circle"
                        wire:click="approve"
                        wire:loading.attr="disabled"
                    >
                        Terbitkan (Approve)
                    </x-filament::button>

                    {{ $this->moveFile }}

                    <div class="rounded-lg border border-danger-200 p-4 dark:border-danger-400/20">
                        <x-filament::input.wrapper>
                            <textarea
                                wire:model="rejectReason"
                                rows="2"
                                placeholder="Alasan penolakan…"
                                class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                            ></textarea>
                        </x-filament::input.wrapper>

                        <x-filament::button
                            color="danger"
                            icon="heroicon-o-x-circle"
                            wire:click="reject"
                            wire:loading.attr="disabled"
                            class="mt-3"
                        >
                            Tolak (Reject)
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @else
        <x-filament::section>
            <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                Tidak ada dokumen yang sedang ditinjau. Pilih dokumen dari antrean di atas.
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

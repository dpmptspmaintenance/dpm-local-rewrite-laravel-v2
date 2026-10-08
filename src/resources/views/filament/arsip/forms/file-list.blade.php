@php
    /** @var \App\Models\Document $record */
@endphp

<div class="grid gap-2">
    @forelse ($record->files as $file)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 dark:border-white/10">
            <a href="{{ $file->openUrl() }}" target="_blank" class="flex items-center gap-2 text-sm text-primary-600 hover:underline dark:text-primary-400">
                <x-heroicon-o-paper-clip class="h-4 w-4 shrink-0" />
                <span>{{ $file->original_filename ?: basename((string) $file->storage_path) }}</span>
            </a>

            <button
                type="button"
                wire:click="removeDocumentFile('{{ $file->id }}')"
                wire:confirm="Hapus berkas ini dari dokumen? Berkas akan dihapus permanen dari penyimpanan."
                class="shrink-0 rounded-md p-1 text-danger-600 hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-500/10"
                title="Hapus berkas ini"
            >
                <x-heroicon-o-trash class="h-4 w-4" />
            </button>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Dokumen ini belum punya berkas fisik.</p>
    @endforelse
</div>

@php
    /** @var \App\Models\Document $record */
    $files = $record->files ?? collect();
@endphp

<div style="display:grid; gap:8px;">
    @forelse ($files as $file)
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid rgb(229 231 235); border-radius:6px; padding:4px 8px;">
            <a href="{{ $file->openUrl() }}" target="_blank" style="display:flex; align-items:center; gap:6px; min-width:0; font-size:12px; color:rgb(2 132 199); text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; flex-shrink:0;"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $file->original_filename ?: basename((string) $file->storage_path) }}</span>
            </a>

            <button
                type="button"
                wire:click="removeDocumentFile('{{ $file->id }}')"
                wire:confirm="Hapus berkas ini dari dokumen? Berkas akan dihapus permanen dari penyimpanan."
                style="flex-shrink:0; display:inline-flex; align-items:center; gap:4px; font-size:11px; padding:2px 8px; border-radius:6px; color:rgb(220 38 38); border:1px solid rgb(252 165 165); background:transparent; cursor:pointer;"
                onmouseover="this.style.background='rgb(254 226 226)'"
                onmouseout="this.style.background='transparent'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px; height:12px;"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Hapus
            </button>
        </div>
    @empty
        <p style="font-size:12px; color:rgb(107 114 128);">Dokumen ini belum punya berkas fisik.</p>
    @endforelse
</div>

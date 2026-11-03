@php
    /** @var \App\Models\Document $record */
    $files = $record->files ?? collect();
@endphp

<div style="display:grid; gap:8px;">
    @forelse ($files as $file)
        @php
            $isLink = $file->isLink();
            $label = $isLink
                ? ($file->link_title ?: $file->google_web_view_link)
                : ($file->original_filename ?: basename((string) $file->storage_path));
        @endphp
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid rgb(229 231 235); border-radius:6px; padding:4px 8px;">
            <a href="{{ $file->openUrl() }}" target="_blank" style="display:flex; align-items:center; gap:6px; min-width:0; font-size:12px; color:rgb(2 132 199); text-decoration:none;">
                @if ($isLink)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; flex-shrink:0;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; flex-shrink:0;"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                @endif
                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $label }}</span>
                @if ($isLink)
                    <span style="flex-shrink:0; font-size:10px; padding:0 6px; border-radius:9999px; background:rgb(224 242 254); color:rgb(12 74 110);">tautan</span>
                @endif
            </a>

            <button
                type="button"
                wire:click="removeDocumentFile('{{ $file->id }}')"
                wire:confirm="Hapus {{ $isLink ? 'tautan' : 'berkas' }} ini dari dokumen?{{ $isLink ? '' : ' Berkas akan dihapus permanen dari penyimpanan.' }}"
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

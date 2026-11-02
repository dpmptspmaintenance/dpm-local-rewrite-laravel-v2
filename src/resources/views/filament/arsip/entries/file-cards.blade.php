@php
    /** @var \App\Models\Document $record */
    $files = $record->files ?? collect();
@endphp

<div style="border:1px solid rgb(229 231 235); border-radius:10px; padding:10px; background:rgb(250 250 250);">
    <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px;">
        @forelse ($files as $file)
            @php
                $isImage = $file->isImage();
                $url = $file->openUrl();
                $label = $file->original_filename ?: basename((string) $file->storage_path);
                $type = strtoupper((string) $file->file_extension) ?: '—';
                $size = ($file->file_size ?? 0) > 0
                    ? number_format($file->file_size / 1024, 1, ',', '.').' KB'
                    : '—';
            @endphp

            <div style="min-width:0; border:1px solid rgb(229 231 235); border-radius:8px; padding:8px; background:#fff; display:flex; flex-direction:column; gap:8px;">
                @if ($isImage)
                    <a href="{{ $url }}" target="_blank" title="{{ $label }}" style="display:block; border-radius:6px; overflow:hidden; background:rgb(249 250 251);">
                        <img
                            src="{{ $url }}"
                            alt="{{ $label }}"
                            title="{{ $label }}"
                            style="display:block; width:100%; height:110px; object-fit:cover;"
                        >
                    </a>
                @else
                    <div title="{{ $label }}" style="display:flex; align-items:center; gap:8px; padding:10px 12px; min-height:110px; box-sizing:border-box; border-radius:6px; background:rgb(249 250 251); border:1px dashed rgb(209 213 219);">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px; flex-shrink:0; color:rgb(107 114 128);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        <span title="{{ $label }}" style="min-width:0; font-size:13px; word-break:break-word;">{{ $label }}</span>
                    </div>
                @endif

                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <a href="{{ $url }}" target="_blank" title="{{ $label }}" style="display:flex; align-items:center; gap:6px; min-width:0; font-size:12px; color:rgb(2 132 199); text-decoration:none;">
                        @if ($isImage)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; flex-shrink:0;"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        @endif
                        <span title="{{ $label }}" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $label }}</span>
                    </a>

                    <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                        <span title="{{ $label }}" style="font-size:11px; padding:1px 6px; border-radius:9999px; background:rgb(243 244 246); color:rgb(55 65 81); border:1px solid rgb(229 231 235);">{{ $type }}</span>
                        <span title="{{ $label }}" style="font-size:11px; color:rgb(107 114 128);">{{ $size }}</span>
                    </div>
                </div>
            </div>
        @empty
            <p style="font-size:12px; color:rgb(107 114 128); margin:0;">Dokumen ini belum punya berkas fisik.</p>
        @endforelse
    </div>
</div>

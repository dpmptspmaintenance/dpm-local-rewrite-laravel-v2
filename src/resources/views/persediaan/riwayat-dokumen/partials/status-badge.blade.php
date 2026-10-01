@php
    $statusMap = [
        'draft' => ['bg-secondary', '', 'Draft'],
        'menunggu' => ['bg-warning text-dark', 'bi-hourglass-split', 'Menunggu'],
        'disetujui' => ['bg-success', 'bi-check-circle', 'Disetujui'],
        'ditolak' => ['bg-danger', 'bi-x-circle', 'Ditolak'],
    ];
    [$cls, $icon, $label] = $statusMap[$status] ?? ['bg-secondary', '', ucfirst($status)];
@endphp
<span class="badge badge-status-lg {{ $cls }}">
    @if ($icon)
        <i class="bi {{ $icon }} me-1"></i>
    @endif
    {{ $label }}
</span>

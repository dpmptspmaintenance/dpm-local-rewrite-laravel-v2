@forelse ($users as $row)
    @php
        $isAktif = (int) $row->is_aktif;
        $statusBadge = $isAktif === 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Nonaktif</span>';
        $statusBtnText = $isAktif === 1 ? 'Nonaktifkan' : 'Aktifkan';
        $statusBtnClass = $isAktif === 1 ? 'btn-warning' : 'btn-success';
        $displayRole = $roles[$row->role] ?? $row->role;
        $sharedPagesJson = json_encode($row->shared_pages ?? []);
    @endphp
    <tr>
        <td class="text-center"><input type="checkbox" class="form-check-input user-checkbox" value="{{ $row->id }}"></td>
        <td>{{ $row->id }}</td>
        <td>{{ $row->nama }}</td>
        <td>{{ $isAktif === 1 ? 'Aktif' : 'Nonaktif' }}</td>
        <td>{{ $displayRole }}</td>
        <td>{{ $row->bidang }}</td>
        <td>
            @if (empty($row->email))
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addGmailModal" data-id="{{ $row->id }}"><i class="fas fa-plus"></i> Gmail</button>
            @else
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addGmailModal" data-id="{{ $row->id }}">{{ $row->email }}</button>
            @endif
        </td>
        <td>{!! $statusBadge !!}</td>
        <td>
            <button class="btn {{ $statusBtnClass }} btn-sm toggle-status-btn mb-1" data-id="{{ $row->id }}">{{ $statusBtnText }}</button>
            <button class="btn btn-dark btn-sm manage-access-btn mb-1" data-bs-toggle="modal" data-bs-target="#accessModal" data-id="{{ $row->id }}" data-pages="{{ $sharedPagesJson }}">
                <i class="fas fa-key"></i> Akses
            </button>
        </td>
    </tr>
@empty
    <tr><td colspan="9" class="text-center">Tidak ada data ditemukan.</td></tr>
@endforelse

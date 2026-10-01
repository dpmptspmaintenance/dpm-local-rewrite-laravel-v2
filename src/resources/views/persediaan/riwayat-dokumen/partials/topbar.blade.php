<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h4 class="fw-bold mb-0 {{ $activeTab === 'masuk' ? 'text-primary' : 'text-danger' }}">
        <i class="bi {{ $activeTab === 'masuk' ? 'bi-box-arrow-in-down' : 'bi-box-arrow-up' }} me-2"></i>{{ $activeTab === 'masuk' ? 'Riwayat BAST Masuk & Saldo Awal' : 'Riwayat Bon Pengeluaran' }}
    </h4>

    @if ($isAdmin)
        <form method="GET" class="d-flex align-items-center">
            <small class="me-2 text-muted fw-bold">Petugas Gudang:</small>
            <select name="filter_bpp" class="form-select form-select-sm w-auto rounded-3" onchange="this.form.submit()">
                <option value="0">-- Semua Petugas --</option>
                @foreach ($arrBpp as $b)
                    <option value="{{ $b->id }}" @selected($filterBpp == $b->id)>
                        {{ $b->nama }} ({{ (int) $b->is_admin_persediaan === 1 ? 'Admin' : 'BPP' }})
                    </option>
                @endforeach
            </select>
        </form>
    @endif
</div>

<script>
    const optMasuk = `<option value="">-- Pilih Barang Baru --</option>@foreach ($barangOptions as $b){{ '' }}<option value="{{ $b->id }}" data-harga="{{ number_format((float) $b->harga_satuan, 0, ',', '.') }}">{{ $b->nama_barang }}</option>@endforeach`;
    const optKeluar = `<option value="">-- Pilih Sisa Stok Gudang --</option>@foreach ($stokOptions as $s){{ '' }}<option value="{{ $s->id_barang }}" data-harga="{{ number_format((float) $s->harga_satuan, 0, ',', '.') }}">{{ $s->nama_barang }} (Sisa: {{ $s->sisa_stok }}) - Rp {{ number_format($s->harga_satuan, 0, ',', '.') }}</option>@endforeach`;

    function hitungTotalEdit(idHeader) {
        let container = document.getElementById('tbody_masuk_' + idHeader);
        if (!container) {
            container = document.getElementById('tbody_keluar_' + idHeader);
        }
        if (!container) return;

        let grandTotal = 0;
        let rows = container.querySelectorAll('tr');

        rows.forEach(row => {
            let qtyInput = row.querySelector('input[name="qty_detail[]"], input[name="new_qty[]"]');
            let hrgInput = row.querySelector('input[name="harga_detail[]"], input[name="new_harga[]"]');

            if (qtyInput && hrgInput) {
                let qty = parseInt(qtyInput.value) || 0;
                let hrg = parseFloat((hrgInput.value || '0').toString().replace(/\./g, '')) || 0;
                grandTotal += (qty * hrg);
            }
        });

        let targetText = document.getElementById('total_edit_text_' + idHeader);
        if (targetText) {
            targetText.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
        }
    }

    function addRowMasuk(idHeader) {
        let tbody = document.getElementById('tbody_masuk_' + idHeader);
        let tr = document.createElement('tr');
        tr.className = "bg-primary bg-opacity-10";
        tr.innerHTML = `
            <td class="text-start"><select class="form-select form-select-sm rounded-2" name="new_id_barang[]" onchange="let opt=this.options[this.selectedIndex]; this.closest('tr').querySelector('.n-hrg').value = opt.getAttribute('data-harga') || 0; hitungTotalEdit(${idHeader});" required>${optMasuk}</select></td>
            <td><input type="number" name="new_qty[]" class="form-control form-control-sm text-center py-0 rounded-2" value="1" min="1" oninput="hitungTotalEdit(${idHeader})" required></td>
            <td><input type="text" inputmode="numeric" name="new_harga[]" class="form-control form-control-sm text-end py-0 rounded-2 n-hrg text-dark fw-bold" oninput="hitungTotalEdit(${idHeader})" required></td>
            <td><button type="button" class="btn btn-sm text-danger border-0 p-0" onclick="this.closest('tr').remove(); hitungTotalEdit(${idHeader});"><i class="bi bi-x-circle fs-5"></i></button></td>
        `;
        tbody.appendChild(tr);
        hitungTotalEdit(idHeader);
    }

    function addRowKeluar(idHeader) {
        let tbody = document.getElementById('tbody_keluar_' + idHeader);
        let tr = document.createElement('tr');
        tr.className = "bg-danger bg-opacity-10";
        tr.innerHTML = `
            <td class="text-start"><select class="form-select form-select-sm rounded-2" name="new_id_barang[]" onchange="let opt=this.options[this.selectedIndex]; this.closest('tr').querySelector('.n-hrg').value = opt.getAttribute('data-harga') || 0; hitungTotalEdit(${idHeader});" required>${optKeluar}</select></td>
            <td><input type="number" name="new_qty[]" class="form-control form-control-sm text-center py-0 rounded-2" value="1" min="1" oninput="hitungTotalEdit(${idHeader})" required></td>
            <td><input type="text" inputmode="numeric" name="new_harga[]" class="form-control form-control-sm text-end py-0 rounded-2 n-hrg text-muted" readonly oninput="hitungTotalEdit(${idHeader})" required></td>
            <td><button type="button" class="btn btn-sm text-danger border-0 p-0" onclick="this.closest('tr').remove(); hitungTotalEdit(${idHeader});"><i class="bi bi-x-circle fs-5"></i></button></td>
        `;
        tbody.appendChild(tr);
        hitungTotalEdit(idHeader);
    }
</script>

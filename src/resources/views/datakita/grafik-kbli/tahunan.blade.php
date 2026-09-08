<div class="mx-2">
    <div class="card my-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2 class="card-title mb-0">Data KBLI</h2>
            <button id="exportExcel" class="btn btn-success btn-sm">📁 Export ke Excel</button>
        </div>
        <div class="card-body">
            <table id="tableData" class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:100px;">Tahun</th>
                        <th style="width:100px;">KBLI</th>
                        <th>Judul KBLI</th>
                        <th style="width:100px;">TKI</th>
                        <th style="width:180px;">Jumlah Investasi (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($dataPerTahun->isNotEmpty())
                        @php
                            $total = 0;
                            $tki = 0;
                        @endphp
                        @foreach ($dataPerTahun as $tahun => $rows)
                            @php
                                $total += $rows->sum('jumlah_investasi3');
                                $tki += $rows->sum('tki');
                            @endphp
                            @foreach ($rows as $index => $row)
                                <tr>
                                    @if ($index == 0)
                                        <td rowspan="{{ $rows->count() }}" class="align-middle text-center fw-bold bg-light">
                                            {{ $tahun }}
                                        </td>
                                    @endif
                                    <td>{{ $row->kbli }}</td>
                                    <td>{{ $row->judul_kbli }}</td>
                                    <td>{{ number_format($row->tki ?? 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($row->jumlah_investasi3 ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center text-muted">Tidak ada data ditemukan.</td>
                        </tr>
                    @endif

                    <tr class="table-secondary fw-bold">
                        <td colspan="3" class="text-end">Total Keseluruhan:</td>
                        <td>{{ number_format($tki ?? 0, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript" src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script>
    document.getElementById('exportExcel').addEventListener('click', function() {
        let table = document.getElementById('tableData');
        let wb = XLSX.utils.table_to_book(table, { sheet: "Data KBLI" });
        XLSX.writeFile(wb, "Data_KBLI.xlsx");
    });
</script>

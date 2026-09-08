<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Exporting Excel...</title>
    <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
</head>

<body>
    <p>Sedang memproses unduhan Excel, mohon tunggu...</p>

    <script>
        const tahunSelected = "{{ $selectedYear }}";
        const kategoriRisiko = @json($kategoriRisiko);
        const dataResiko = @json($dataResiko);
        const dataTotal = @json(array_values($dataTotal));
        const grandTotal = {{ (int) $grandTotal }};
        const statTotal = @json($statTotal);
        const statResiko = @json($statResiko);
        const jenisList = @json($jenisList);
        const dataJenis = @json($dataJenis);
        const dokumenList = @json($dokumenList);
        const dataDokumen = @json($dataDokumen);
        const responList = @json($responList);
        const dataRespon = @json($dataRespon);

        document.addEventListener("DOMContentLoaded", function() {
            const wb = XLSX.utils.book_new();
            const borderStyle = {
                top: { style: 'thin', color: { rgb: "000000" } },
                bottom: { style: 'thin', color: { rgb: "000000" } },
                left: { style: 'thin', color: { rgb: "000000" } },
                right: { style: 'thin', color: { rgb: "000000" } }
            };

            const cell = (r, c) => XLSX.utils.encode_cell({ r: r, c: c });
            const setNumFmt = (ws, r, c, numFmt) => {
                if (ws[cell(r, c)]) ws[cell(r, c)].s = { numFmt: numFmt };
            };

            const applyCommonStyles = (ws, headerMax, isRisk = false) => {
                const range = XLSX.utils.decode_range(ws['!ref']);
                for (let R = range.s.r; R <= range.e.r; ++R) {
                    for (let C = range.s.c; C <= range.e.c; ++C) {
                        const cell_ref = cell(R, C);
                        if (!ws[cell_ref]) continue;
                        if (!ws[cell_ref].s) ws[cell_ref].s = {};

                        if (R === 0) ws[cell_ref].s.font = { bold: true, sz: 12, name: 'Arial' };
                        if (R === 1) ws[cell_ref].s.font = { bold: true, sz: 13, name: 'Arial' };
                        if (R === 2) ws[cell_ref].s.font = { bold: true, sz: 11, name: 'Arial', color: { rgb: "475569" } };
                        if (R === 3) ws[cell_ref].s.font = { italic: true, sz: 9, name: 'Arial', color: { rgb: "64748B" } };

                        if (R >= 5) {
                            ws[cell_ref].s.border = borderStyle;
                            ws[cell_ref].s.font = { name: 'Arial', sz: 10 };
                            ws[cell_ref].s.alignment = { vertical: 'center' };

                            if (R <= headerMax) {
                                ws[cell_ref].s.fill = { fgColor: { rgb: "334155" } };
                                ws[cell_ref].s.font.bold = true;
                                ws[cell_ref].s.font.color = { rgb: "FFFFFF" };
                                ws[cell_ref].s.alignment.horizontal = 'center';
                            } else {
                                let totalRowIdx = range.e.r - (isRisk ? 0 : 3);
                                if (R >= totalRowIdx) {
                                    ws[cell_ref].s.fill = { fgColor: { rgb: "F1F5F9" } };
                                    ws[cell_ref].s.font.bold = true;
                                }
                                if (typeof ws[cell_ref].v === 'number') {
                                    ws[cell_ref].s.alignment.horizontal = 'right';
                                } else {
                                    ws[cell_ref].s.alignment.horizontal = (isRisk && C === 1) ? 'left' : 'center';
                                }
                            }
                        }
                    }
                }
            };

            // --- SHEET 1: RISIKO ---
            let aoa1 = [
                ["PEMERINTAH KOTA SEMARANG"],
                ["DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU (DPMPTSP)"],
                [`LAPORAN JUMLAH PERMOHONAN BERDASARKAN RISIKO - TAHUN ${tahunSelected}`],
                ["Sumber Data : OSS - RBA | Sumber File : List izin.xlsx"],
                [],
                ["NO", "TAHUN", "BULAN", "STATUS RISIKO PERMOHONAN", "", "", "", "", "", "", "", "", "", "JUMLAH", "% JUMLAH"],
                ["", "", "", "KOSONG", "% KOSONG", "MR", "% MR", "MT", "% MT", "R", "% R", "T", "% T", "", ""]
            ];

            for (let i = 1; i <= 12; i++) {
                let row = [i, parseInt(tahunSelected), i];
                kategoriRisiko.forEach(kr => {
                    let val = dataResiko[kr] ? (dataResiko[kr][i] || 0) : 0;
                    let totKr = dataResiko[kr] ? Object.values(dataResiko[kr]).reduce((a, b) => a + b, 0) : 0;
                    let pct = totKr > 0 ? (val / totKr) : 0;
                    row.push(val, pct);
                });
                let jmlBln = dataTotal[i - 1] || 0;
                let pctBln = grandTotal > 0 ? (jmlBln / grandTotal) : 0;
                row.push(jmlBln, pctBln);
                aoa1.push(row);
            }

            let jumRow = ["JUMLAH", "", ""];
            kategoriRisiko.forEach(kr => {
                let totKr = dataResiko[kr] ? Object.values(dataResiko[kr]).reduce((a, b) => a + b, 0) : 0;
                jumRow.push(totKr, "");
            });
            jumRow.push(grandTotal, "");
            aoa1.push(jumRow);

            let rataRow = ["RATA - RATA", "", ""];
            kategoriRisiko.forEach(kr => { rataRow.push(Math.round(statResiko[kr].avg), ""); });
            rataRow.push(Math.round(statTotal.avg), "");
            aoa1.push(rataRow);

            let maxRow = ["TERTINGGI", "", ""];
            kategoriRisiko.forEach(kr => { maxRow.push(statResiko[kr].max, ""); });
            maxRow.push(statTotal.max, "");
            aoa1.push(maxRow);

            let minRow = ["TERENDAH", "", ""];
            kategoriRisiko.forEach(kr => { minRow.push(statResiko[kr].min, ""); });
            minRow.push(statTotal.min, "");
            aoa1.push(minRow);

            const ws1 = XLSX.utils.aoa_to_sheet(aoa1);
            ws1['!merges'] = [
                { s: { r: 5, c: 0 }, e: { r: 6, c: 0 } },
                { s: { r: 5, c: 1 }, e: { r: 6, c: 1 } },
                { s: { r: 5, c: 2 }, e: { r: 6, c: 2 } },
                { s: { r: 5, c: 3 }, e: { r: 5, c: 12 } },
                { s: { r: 5, c: 13 }, e: { r: 6, c: 13 } },
                { s: { r: 5, c: 14 }, e: { r: 6, c: 14 } },
                { s: { r: 19, c: 0 }, e: { r: 19, c: 2 } },
                { s: { r: 20, c: 0 }, e: { r: 20, c: 2 } },
                { s: { r: 21, c: 0 }, e: { r: 21, c: 2 } },
                { s: { r: 22, c: 0 }, e: { r: 22, c: 2 } }
            ];
            for (let r = 7; r <= 22; r++) {
                for (let c = 3; c <= 14; c++) {
                    if (c % 2 === 1) setNumFmt(ws1, r, c, '#,##0');
                    else setNumFmt(ws1, r, c, '0.00%');
                }
            }
            ws1['!cols'] = [
                { wch: 6 }, { wch: 10 }, { wch: 10 },
                { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
                { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
                { wch: 12 }, { wch: 12 }
            ];
            applyCommonStyles(ws1, 6, false);
            XLSX.utils.book_append_sheet(wb, ws1, 'Risiko');

            // --- HELPER UNTUK SHEET DETAIL (JENIS / DOKUMEN / RESPON) ---
            function buildDetailSheet(sheetName, list, dataMap, titleText, uraianLabel) {
                let aoa = [
                    ["PEMERINTAH KOTA SEMARANG"],
                    ["DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU (DPMPTSP)"],
                    [`${titleText} - TAHUN ${tahunSelected}`],
                    ["Sumber Data : OSS - RBA | Sumber File : List izin.xlsx"],
                    [],
                    ["No", uraianLabel, "TAHUN : " + tahunSelected, "", "", "", "", "", "", "", "", "", "", "", "Jumlah", "% JUMLAH", "RATA - RATA", "TERTINGGI", "TERENDAH"],
                    ["", "", "BULAN", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""],
                    ["", "", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "", "", "", "", ""]
                ];

                list.forEach((item, idx) => {
                    let row = [idx + 1, item];
                    let arrBulanan = dataMap[item] || {};
                    let tot = 0;
                    let active = [];
                    for (let m = 1; m <= 12; m++) {
                        let val = arrBulanan[m] || 0;
                        row.push(val);
                        tot += val;
                        if (val > 0) active.push(val);
                    }
                    let pct = grandTotal > 0 ? (tot / grandTotal) : 0;
                    let avg = active.length > 0 ? Math.round(active.reduce((a, b) => a + b, 0) / active.length) : 0;
                    let max = active.length > 0 ? Math.max(...active) : 0;
                    let min = active.length > 0 ? Math.min(...active) : 0;
                    row.push(tot, pct, avg, max, min);
                    aoa.push(row);
                });

                let totalRow = ["JUMLAH TOTAL", ""];
                for (let m = 1; m <= 12; m++) totalRow.push(dataTotal[m - 1] || 0);
                totalRow.push(grandTotal, 1.0, Math.round(statTotal.avg), statTotal.max, statTotal.min);
                aoa.push(totalRow);

                const ws = XLSX.utils.aoa_to_sheet(aoa);
                const lastRow = aoa.length - 1;
                ws['!merges'] = [
                    { s: { r: 5, c: 0 }, e: { r: 7, c: 0 } },
                    { s: { r: 5, c: 1 }, e: { r: 7, c: 1 } },
                    { s: { r: 5, c: 2 }, e: { r: 5, c: 13 } },
                    { s: { r: 6, c: 2 }, e: { r: 6, c: 13 } },
                    { s: { r: 5, c: 14 }, e: { r: 7, c: 14 } },
                    { s: { r: 5, c: 15 }, e: { r: 7, c: 15 } },
                    { s: { r: 5, c: 16 }, e: { r: 7, c: 16 } },
                    { s: { r: 5, c: 17 }, e: { r: 7, c: 17 } },
                    { s: { r: 5, c: 18 }, e: { r: 7, c: 18 } },
                    { s: { r: lastRow, c: 0 }, e: { r: lastRow, c: 1 } }
                ];
                for (let r = 8; r <= lastRow; r++) {
                    for (let c = 2; c <= 14; c++) setNumFmt(ws, r, c, '#,##0');
                    setNumFmt(ws, r, 15, '0.00%');
                    for (let c = 16; c <= 18; c++) setNumFmt(ws, r, c, '#,##0');
                }
                ws['!cols'] = [{ wch: 5 }, { wch: 30 }];
                applyCommonStyles(ws, 7, true);
                XLSX.utils.book_append_sheet(wb, ws, sheetName);
            }

            buildDetailSheet('Jenis Perizinan', jenisList, dataJenis, 'LAPORAN JUMLAH JENIS PERIZINAN', 'Uraian Jenis Perizinan');
            buildDetailSheet('Nama Dokumen', dokumenList, dataDokumen, 'LAPORAN JUMLAH NAMA DOKUMEN', 'Nama Dokumen');
            buildDetailSheet('Status Respon', responList, dataRespon, 'LAPORAN JUMLAH STATUS RESPON', 'Uraian Status Respon');

            // Trigger Download File
            XLSX.writeFile(wb, `Laporan_Statistik_Izin_${tahunSelected}.xlsx`);

            // Kembalikan ke halaman dashboard utama secara otomatis
            setTimeout(() => {
                window.location.href = @json(route('datakita.statistik.izin')) + '?tahun=' + tahunSelected;
            }, 300);
        });
    </script>
</body>

</html>

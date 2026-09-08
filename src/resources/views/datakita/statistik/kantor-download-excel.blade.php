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
        const dataNIB = @json(array_values($dataNib));
        const totalNIB = {{ (int) $totalNib }};
        const dataPMA = @json(array_values($dataPma));
        const dataPMDN = @json(array_values($dataPmdn));
        const totalPMA = {{ (int) $totalPma }};
        const totalPMDN = {{ (int) $totalPmdn }};
        const statNIB = @json($statNib);
        const statPMA = @json($statPma);
        const statPMDN = @json($statPmdn);
        const jenisList = @json($jenisList);
        const dataJenis = @json($dataJenis);

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

            // --- SHEET 1: JUMLAH NIB ---
            let aoa1 = [
                ["PEMERINTAH KOTA SEMARANG"],
                ["DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU (DPMPTSP)"],
                [`LAPORAN STATISTIK JUMLAH NIB PER BULAN - TAHUN ${tahunSelected}`],
                ["Sumber Data : OSS - RBA | Sumber File : DP.NIB Kantor"],
                [],
                ["NO", "TAHUN", "BULAN", "JUMLAH PER BULAN", "% PER BULAN"]
            ];
            for (let i = 1; i <= 12; i++) {
                let jml = dataNIB[i - 1] || 0;
                let pct = totalNIB > 0 ? (jml / totalNIB) : 0;
                aoa1.push([i, parseInt(tahunSelected), i, jml, pct]);
            }
            aoa1.push(["JUMLAH", "", "", totalNIB, 1.0]);
            aoa1.push(["RATA - RATA", "", "", Math.round(statNIB.avg), ""]);
            aoa1.push(["TERTINGGI", "", "", statNIB.max, ""]);
            aoa1.push(["TERENDAH", "", "", statNIB.min, ""]);

            const ws1 = XLSX.utils.aoa_to_sheet(aoa1);
            ws1['!merges'] = [
                { s: { r: 18, c: 0 }, e: { r: 18, c: 2 } },
                { s: { r: 19, c: 0 }, e: { r: 19, c: 2 } },
                { s: { r: 20, c: 0 }, e: { r: 20, c: 2 } },
                { s: { r: 21, c: 0 }, e: { r: 21, c: 2 } }
            ];
            for (let r = 6; r <= 21; r++) {
                setNumFmt(ws1, r, 3, '#,##0');
                setNumFmt(ws1, r, 4, '0.00%');
            }
            ws1['!cols'] = [{ wch: 6 }, { wch: 10 }, { wch: 10 }, { wch: 22 }, { wch: 14 }];
            applyCommonStyles(ws1, 5, false);
            XLSX.utils.book_append_sheet(wb, ws1, 'Jumlah NIB');

            // --- SHEET 2: STATUS PENANAMAN MODAL ---
            let aoa2 = [
                ["PEMERINTAH KOTA SEMARANG"],
                ["DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU (DPMPTSP)"],
                [`LAPORAN STATUS PENANAMAN MODAL (PMA / PMDN) - TAHUN ${tahunSelected}`],
                ["Sumber Data : OSS - RBA | Sumber File : DP.NIB Kantor"],
                [],
                ["NO", "TAHUN", "BULAN", "STATUS PENANAMAN MODAL", "", "", "", "JUMLAH", "% JUMLAH"],
                ["", "", "", "PMA", "% PMA", "PMDN", "% PMDN", "", ""]
            ];
            for (let i = 1; i <= 12; i++) {
                let pma = dataPMA[i - 1] || 0;
                let pmdn = dataPMDN[i - 1] || 0;
                let totalBaris = pma + pmdn;
                let pctPMA = totalPMA > 0 ? (pma / totalPMA) : 0;
                let pctPMDN = totalPMDN > 0 ? (pmdn / totalPMDN) : 0;
                let pctTotal = totalNIB > 0 ? (totalBaris / totalNIB) : 0;
                aoa2.push([i, parseInt(tahunSelected), i, pma, pctPMA, pmdn, pctPMDN, totalBaris, pctTotal]);
            }
            aoa2.push(["JUMLAH", "", "", totalPMA, "", totalPMDN, "", totalPMA + totalPMDN, ""]);
            aoa2.push(["RATA - RATA", "", "", Math.round(statPMA.avg), "", Math.round(statPMDN.avg), "", Math.round(statNIB.avg), ""]);
            aoa2.push(["TERTINGGI", "", "", statPMA.max, "", statPMDN.max, "", statNIB.max, ""]);
            aoa2.push(["TERENDAH", "", "", statPMA.min, "", statPMDN.min, "", statNIB.min, ""]);

            const ws2 = XLSX.utils.aoa_to_sheet(aoa2);
            ws2['!merges'] = [
                { s: { r: 5, c: 0 }, e: { r: 6, c: 0 } },
                { s: { r: 5, c: 1 }, e: { r: 6, c: 1 } },
                { s: { r: 5, c: 2 }, e: { r: 6, c: 2 } },
                { s: { r: 5, c: 3 }, e: { r: 5, c: 6 } },
                { s: { r: 5, c: 7 }, e: { r: 6, c: 7 } },
                { s: { r: 5, c: 8 }, e: { r: 6, c: 8 } },
                { s: { r: 19, c: 0 }, e: { r: 19, c: 2 } },
                { s: { r: 20, c: 0 }, e: { r: 20, c: 2 } },
                { s: { r: 21, c: 0 }, e: { r: 21, c: 2 } },
                { s: { r: 22, c: 0 }, e: { r: 22, c: 2 } }
            ];
            for (let r = 7; r <= 22; r++) {
                setNumFmt(ws2, r, 3, '#,##0');
                setNumFmt(ws2, r, 4, '0.00%');
                setNumFmt(ws2, r, 5, '#,##0');
                setNumFmt(ws2, r, 6, '0.00%');
                setNumFmt(ws2, r, 7, '#,##0');
                setNumFmt(ws2, r, 8, '0.00%');
            }
            ws2['!cols'] = [{ wch: 6 }, { wch: 10 }, { wch: 10 }, { wch: 14 }, { wch: 10 }, { wch: 14 }, { wch: 10 }, { wch: 12 }, { wch: 12 }];
            applyCommonStyles(ws2, 6, false);
            XLSX.utils.book_append_sheet(wb, ws2, 'Status Penanaman Modal');

            // --- SHEET 3: JENIS PERUSAHAAN ---
            let aoa3 = [
                ["PEMERINTAH KOTA SEMARANG"],
                ["DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU (DPMPTSP)"],
                [`LAPORAN JUMLAH JENIS PERUSAHAAN - TAHUN ${tahunSelected}`],
                ["Sumber Data : OSS - RBA | Sumber File : DP.NIB Kantor"],
                [],
                ["No", "Uraian Jenis Perusahaan", "TAHUN : " + tahunSelected, "", "", "", "", "", "", "", "", "", "", "", "Jumlah", "% JUMLAH", "RATA - RATA", "TERTINGGI", "TERENDAH"],
                ["", "", "BULAN", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""],
                ["", "", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "", "", "", "", ""]
            ];
            jenisList.forEach((jp, idx) => {
                let rowArr = [idx + 1, jp];
                let arrBulanan = dataJenis[jp] || {};
                let totJp = 0;
                let activeVals = [];
                for (let m = 1; m <= 12; m++) {
                    let val = arrBulanan[m] || 0;
                    rowArr.push(val);
                    totJp += val;
                    if (val > 0) activeVals.push(val);
                }
                let pctJp = totalNIB > 0 ? (totJp / totalNIB) : 0;
                let avgJp = activeVals.length > 0 ? Math.round(activeVals.reduce((a, b) => a + b, 0) / activeVals.length) : 0;
                let maxJp = activeVals.length > 0 ? Math.max(...activeVals) : 0;
                let minJp = activeVals.length > 0 ? Math.min(...activeVals) : 0;
                rowArr.push(totJp, pctJp, avgJp, maxJp, minJp);
                aoa3.push(rowArr);
            });
            let totalRow3 = ["JUMLAH TOTAL", ""];
            for (let m = 1; m <= 12; m++) totalRow3.push(dataNIB[m - 1] || 0);
            totalRow3.push(totalNIB, 1.0, Math.round(statNIB.avg), statNIB.max, statNIB.min);
            aoa3.push(totalRow3);

            const ws3 = XLSX.utils.aoa_to_sheet(aoa3);
            ws3['!merges'] = [
                { s: { r: 5, c: 0 }, e: { r: 7, c: 0 } },
                { s: { r: 5, c: 1 }, e: { r: 7, c: 1 } },
                { s: { r: 5, c: 2 }, e: { r: 5, c: 13 } },
                { s: { r: 6, c: 2 }, e: { r: 6, c: 13 } },
                { s: { r: 5, c: 14 }, e: { r: 7, c: 14 } },
                { s: { r: 5, c: 15 }, e: { r: 7, c: 15 } },
                { s: { r: 5, c: 16 }, e: { r: 7, c: 16 } },
                { s: { r: 5, c: 17 }, e: { r: 7, c: 17 } },
                { s: { r: 5, c: 18 }, e: { r: 7, c: 18 } },
                { s: { r: aoa3.length - 1, c: 0 }, e: { r: aoa3.length - 1, c: 1 } }
            ];
            for (let r = 8; r < aoa3.length; r++) {
                for (let c = 2; c <= 14; c++) setNumFmt(ws3, r, c, '#,##0');
                setNumFmt(ws3, r, 15, '0.00%');
                for (let c = 16; c <= 18; c++) setNumFmt(ws3, r, c, '#,##0');
            }
            ws3['!cols'] = [{ wch: 5 }, { wch: 30 }];
            applyCommonStyles(ws3, 7, true);
            XLSX.utils.book_append_sheet(wb, ws3, 'Jenis Perusahaan');

            // Trigger Download File
            XLSX.writeFile(wb, `Laporan_Statistik_Kantor_${tahunSelected}.xlsx`);

            // Kembalikan ke halaman dashboard utama secara otomatis
            setTimeout(() => {
                window.location.href = @json(route('datakita.statistik.kantor')) + '?tahun=' + tahunSelected;
            }, 300);
        });
    </script>
</body>

</html>

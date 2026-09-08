<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataKita\DaftarFileController;
use App\Http\Controllers\DataKita\OssRbaController;
use App\Http\Controllers\DataKita\GrafikKbliController;
use App\Http\Controllers\DataKita\GrafikRekapJumlahInvestasiController;
use App\Http\Controllers\DataKita\GrafikRekapJumlahTkiController;
use App\Http\Controllers\DataKita\GrafikRekapKbliController;
use App\Http\Controllers\DataKita\GrafikRekapListIzinController;
use App\Http\Controllers\DataKita\ImportController;
use App\Http\Controllers\DataKita\MppdigController;
use App\Http\Controllers\DataKita\LppdController;
use App\Http\Controllers\DataKita\RealisasiInvestasiController;
use App\Http\Controllers\DataKita\OssRbaTrackingController;
use App\Http\Controllers\DataKita\OverviewOssController;
use App\Http\Controllers\DataKita\OverviewSimbgController;
use App\Http\Controllers\DataKita\OverviewMppdController;
use App\Http\Controllers\DataKita\PerizinanController;
use App\Http\Controllers\DataKita\PerusahaanController;
use App\Http\Controllers\DataKita\ProyekController;
use App\Http\Controllers\DataKita\Rekap10KbliTeratasController;
use App\Http\Controllers\DataKita\RekapIzinKbliController;
use App\Http\Controllers\DataKita\RekapJenisIzinController;
use App\Http\Controllers\DataKita\RekapKbliKecamatanController;
use App\Http\Controllers\DataKita\RekapNibController;
use App\Http\Controllers\DataKita\RekapSektorController;
use App\Http\Controllers\DataKita\SimbgController;
use App\Http\Controllers\DataKita\StatistikController;
use App\Http\Controllers\Persediaan\CetakController;
use App\Http\Controllers\Persediaan\LaporanController;
use App\Http\Controllers\Persediaan\LaporanPemakaianController;
use App\Http\Controllers\Persediaan\ManajemenKunciController;
use App\Http\Controllers\Persediaan\MasterBarangController;
use App\Http\Controllers\Persediaan\MasterRekeningController;
use App\Http\Controllers\Persediaan\MasterSatuanController;
use App\Http\Controllers\Persediaan\PersediaanController;
use App\Http\Controllers\Persediaan\RiwayatDokumenController;
use App\Http\Controllers\Persediaan\RiwayatOpnameController;
use App\Http\Controllers\Persediaan\StokOpnameController;
use App\Http\Controllers\Persediaan\TransaksiBastController;
use App\Http\Controllers\Persediaan\TransaksiBonController;
use App\Http\Controllers\RapatKita\JadwalController;
use App\Http\Controllers\RapatKita\NotulenController;
use App\Http\Controllers\SikenutController;
use App\Http\Controllers\SiperdafitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('rapatkita')->name('rapatkita.')->group(function () {
        Route::get('/', fn() => redirect()->route('rapatkita.jadwal.index'))->name('index');
        Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
        Route::post('/jadwal', [JadwalController::class, 'store'])->name('jadwal.store');
        Route::delete('/jadwal/{schedule}', [JadwalController::class, 'destroy'])->name('jadwal.destroy');
        Route::get('/jadwal-download', [JadwalController::class, 'download'])->name('jadwal.download');

        Route::get('/jadwal/{schedule}/notulen', [NotulenController::class, 'create'])->name('notulen.create');
        Route::post('/jadwal/{schedule}/notulen', [NotulenController::class, 'store'])->name('notulen.store');
        Route::get('/notulen', [NotulenController::class, 'index'])->name('notulen.index');
        Route::get('/notulen/{notulen}', [NotulenController::class, 'show'])->name('notulen.show');
        Route::get('/notulen/{notulen}/edit', [NotulenController::class, 'edit'])->name('notulen.edit');
        Route::put('/notulen/{notulen}', [NotulenController::class, 'update'])->name('notulen.update');
    });

    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/get-users', [UserController::class, 'getUsers'])->name('get-users');
        Route::post('/add-user', [UserController::class, 'addUser'])->name('add-user');
        Route::post('/add-gmail', [UserController::class, 'addGmail'])->name('add-gmail');
        Route::post('/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/update-access', [UserController::class, 'updateAccess'])->name('update-access');
        Route::post('/batch-update-access', [UserController::class, 'batchUpdateAccess'])->name('batch-update-access');
    });

    Route::prefix('sikenut')->name('sikenut.')->group(function () {
        Route::get('/', [SikenutController::class, 'index'])->name('index');
        Route::post('/data', [SikenutController::class, 'data'])->name('data');
        Route::post('/summary', [SikenutController::class, 'summary'])->name('summary');
        Route::post('/disposisi', [SikenutController::class, 'disposisi'])->name('disposisi');
        Route::post('/get-single-group', [SikenutController::class, 'getSingleGroup'])->name('get-single-group');
        Route::post('/store', [SikenutController::class, 'store'])->name('store');
        Route::post('/update-multi', [SikenutController::class, 'updateMulti'])->name('update-multi');
        Route::post('/destroy', [SikenutController::class, 'destroy'])->name('destroy');
        Route::post('/import', [SikenutController::class, 'import'])->name('import');
    });

    Route::prefix('siperdafit')->name('siperdafit.')->group(function () {
        Route::get('/', [SiperdafitController::class, 'index'])->name('index');
        Route::get('/list', [SiperdafitController::class, 'list'])->name('list');
        Route::post('/store', [SiperdafitController::class, 'store'])->name('store');
        Route::get('/{itRequest}/detail', [SiperdafitController::class, 'detail'])->name('detail');
        Route::post('/{itRequest}/update', [SiperdafitController::class, 'update'])->name('update');
        Route::post('/{itRequest}/delete', [SiperdafitController::class, 'destroy'])->name('delete');
        Route::post('/update-status', [SiperdafitController::class, 'updateStatus'])->name('update-status');
        Route::post('/update-priority', [SiperdafitController::class, 'updatePriority'])->name('update-priority');
    });

    Route::prefix('persediaan')->name('persediaan.')->group(function () {
        Route::get('/', [PersediaanController::class, 'index'])->name('index');

        Route::get('/master-rekening', [MasterRekeningController::class, 'index'])->name('master-rekening.index');
        Route::post('/master-rekening', [MasterRekeningController::class, 'store'])->name('master-rekening.store');
        Route::put('/master-rekening/{kode}', [MasterRekeningController::class, 'update'])->name('master-rekening.update');
        Route::post('/master-rekening/{kode}/toggle-status', [MasterRekeningController::class, 'toggleStatus'])->name('master-rekening.toggle-status');

        Route::get('/master-satuan', [MasterSatuanController::class, 'index'])->name('master-satuan.index');
        Route::post('/master-satuan', [MasterSatuanController::class, 'store'])->name('master-satuan.store');
        Route::put('/master-satuan/{id}', [MasterSatuanController::class, 'update'])->name('master-satuan.update');
        Route::post('/master-satuan/{id}/toggle-status', [MasterSatuanController::class, 'toggleStatus'])->name('master-satuan.toggle-status');

        Route::get('/master-barang', [MasterBarangController::class, 'index'])->name('master-barang.index');
        Route::post('/master-barang', [MasterBarangController::class, 'store'])->name('master-barang.store');
        Route::put('/master-barang/{id}', [MasterBarangController::class, 'update'])->name('master-barang.update');

        Route::get('/manajemen-kunci', [ManajemenKunciController::class, 'index'])->name('manajemen-kunci.index');
        Route::post('/manajemen-kunci/toggle', [ManajemenKunciController::class, 'toggle'])->name('manajemen-kunci.toggle');

        Route::get('/transaksi-bast', [TransaksiBastController::class, 'index'])->name('transaksi-bast.index');
        Route::post('/transaksi-bast', [TransaksiBastController::class, 'store'])->name('transaksi-bast.store');

        Route::get('/transaksi-bon', [TransaksiBonController::class, 'index'])->name('transaksi-bon.index');
        Route::post('/transaksi-bon', [TransaksiBonController::class, 'store'])->name('transaksi-bon.store');

        Route::get('/riwayat-dokumen', [RiwayatDokumenController::class, 'index'])->name('riwayat-dokumen.index');
        Route::post('/riwayat-dokumen/ajukan', [RiwayatDokumenController::class, 'ajukanKeAdmin'])->name('riwayat-dokumen.ajukan');
        Route::post('/riwayat-dokumen/hapus', [RiwayatDokumenController::class, 'hapusDokumen'])->name('riwayat-dokumen.hapus');
        Route::post('/riwayat-dokumen/approve', [RiwayatDokumenController::class, 'prosesApproval'])->name('riwayat-dokumen.approve');
        Route::post('/riwayat-dokumen/edit', [RiwayatDokumenController::class, 'editDokumen'])->name('riwayat-dokumen.edit');

        Route::get('/stok-opname', [StokOpnameController::class, 'index'])->name('stok-opname.index');
        Route::post('/stok-opname', [StokOpnameController::class, 'store'])->name('stok-opname.store');

        Route::get('/riwayat-opname', [RiwayatOpnameController::class, 'index'])->name('riwayat-opname.index');
        Route::post('/riwayat-opname/hapus', [RiwayatOpnameController::class, 'hapusOpname'])->name('riwayat-opname.hapus');
        Route::post('/riwayat-opname/finalkan', [RiwayatOpnameController::class, 'finalkanOpname'])->name('riwayat-opname.finalkan');

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::post('/laporan/data', [LaporanController::class, 'data'])->name('laporan.data');
        Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');

        Route::get('/laporan-pemakaian', [LaporanPemakaianController::class, 'index'])->name('laporan-pemakaian.index');

        Route::get('/cetak/bast/{id}', [CetakController::class, 'bast'])->name('cetak.bast');
        Route::get('/cetak/bon/{id}', [CetakController::class, 'bon'])->name('cetak.bon');
        Route::get('/cetak/baso/{id}', [CetakController::class, 'baso'])->name('cetak.baso');
        Route::get('/cetak/laporan', [CetakController::class, 'laporan'])->name('cetak.laporan');
    });

    Route::prefix('data-kita')->name('datakita.')->group(function () {
        Route::get('/', fn() => redirect()->route('datakita.perizinan.index'))->name('index');
        Route::get('/data-perizinan', [PerizinanController::class, 'index'])->name('perizinan.index');
        Route::get('/data-perizinan/search', fn() => view('datakita.perizinan.search'))->name('perizinan.search');
        Route::match(['get', 'post'], '/data-perizinan/ajax', [PerizinanController::class, 'ajaxData'])->name('perizinan.ajax');
        Route::get('/data-perizinan/export', [PerizinanController::class, 'export'])->name('perizinan.export');

        Route::get('/data-perusahaan', [PerusahaanController::class, 'index'])->name('perusahaan.index');
        Route::get('/data-perusahaan/search', fn() => view('datakita.perusahaan.search'))->name('perusahaan.search');
        Route::match(['get', 'post'], '/data-perusahaan/ajax', [PerusahaanController::class, 'ajaxData'])->name('perusahaan.ajax');
        Route::get('/data-perusahaan/export', [PerusahaanController::class, 'export'])->name('perusahaan.export');

        Route::get('/data-proyek', [ProyekController::class, 'index'])->name('proyek.index');
        Route::get('/data-proyek/search', fn() => view('datakita.proyek.search'))->name('proyek.search');
        Route::match(['get', 'post'], '/data-proyek/ajax', [ProyekController::class, 'ajaxData'])->name('proyek.ajax');
        Route::get('/data-proyek/export', [ProyekController::class, 'export'])->name('proyek.export');

        Route::get('/perizinan-oss-rba', [OssRbaController::class, 'index'])->name('ossrba.index');
        Route::get('/perizinan-oss-rba/search', fn() => view('datakita.oss-rba.search'))->name('ossrba.search');
        Route::match(['get', 'post'], '/perizinan-oss-rba/ajax', [OssRbaController::class, 'ajaxData'])->name('ossrba.ajax');
        Route::get('/perizinan-oss-rba/export', [OssRbaController::class, 'export'])->name('ossrba.export');

        Route::get('/oss-rba-tracking', [OssRbaTrackingController::class, 'index'])->name('ossrba-tracking.index');

        Route::get('/monitoring-simbg', [SimbgController::class, 'monitoringIndex'])->name('monitoring-simbg.index');
        Route::get('/monitoring-simbg/export', [SimbgController::class, 'monitoringExport'])->name('monitoring-simbg.export');
        Route::get('/monitoring-simbg/tambah-ubah', [SimbgController::class, 'monitoringTambahUbah'])->name('monitoring-simbg.tambah-ubah');
        Route::get('/monitoring-simbg/get-select-data', [SimbgController::class, 'monitoringGetSelectData'])->name('monitoring-simbg.get-select-data');
        Route::post('/monitoring-simbg/get-data', [SimbgController::class, 'monitoringGetData'])->name('monitoring-simbg.get-data');
        Route::post('/monitoring-simbg/proses-tambah-ubah', [SimbgController::class, 'monitoringProsesTambahUbah'])->name('monitoring-simbg.proses-tambah-ubah');

        Route::get('/rekap-pbg', [SimbgController::class, 'rekapPbgIndex'])->name('rekap-pbg.index');
        Route::get('/rekap-pbg/export', [SimbgController::class, 'rekapPbgExport'])->name('rekap-pbg.export');

        Route::get('/validasi-pembayaran-retribusi-pbg', [SimbgController::class, 'validasiIndex'])->name('validasi-pembayaran.index');
        Route::get('/validasi-pembayaran-retribusi-pbg/tambah', [SimbgController::class, 'validasiTambah'])->name('validasi-pembayaran.tambah');
        Route::post('/validasi-pembayaran-retribusi-pbg', [SimbgController::class, 'validasiStore'])->name('validasi-pembayaran.store');

        Route::get('/simbg-tracking', [SimbgController::class, 'trackingIndex'])->name('simbg-tracking.index');

        Route::get('/daftar-file', [DaftarFileController::class, 'index'])->name('daftar-file.index');
        Route::post('/daftar-file/upload', [DaftarFileController::class, 'upload'])->name('daftar-file.upload');

        Route::get('/mppdig-permohonan', [MppdigController::class, 'permohonanIndex'])->name('mppdig-permohonan.index');
        Route::match(['get', 'post'], '/mppdig-permohonan/ajax', [MppdigController::class, 'permohonanAjax'])->name('mppdig-permohonan.ajax');
        Route::get('/mppdig-permohonan/export', [MppdigController::class, 'permohonanExport'])->name('mppdig-permohonan.export');

        Route::get('/mppdig-pemohon', [MppdigController::class, 'pemohonIndex'])->name('mppdig-pemohon.index');
        Route::get('/mppdig-pemohon/export', [MppdigController::class, 'pemohonExport'])->name('mppdig-pemohon.export');

        Route::get('/mppdig-kendala', [MppdigController::class, 'kendalaIndex'])->name('mppdig-kendala.index');
        Route::get('/mppdig-kendala/export', [MppdigController::class, 'kendalaExport'])->name('mppdig-kendala.export');

        Route::get('/mppdig-faskes', [MppdigController::class, 'faskesIndex'])->name('mppdig-faskes.index');
        Route::get('/mppdig-faskes/export', [MppdigController::class, 'faskesExport'])->name('mppdig-faskes.export');

        Route::get('/mppdig-grafik', [MppdigController::class, 'grafikIndex'])->name('mppdig-grafik.index');

        Route::get('/mppdig-tracking', [MppdigController::class, 'trackingIndex'])->name('mppdig-tracking.index');

        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ImportController::class, 'index'])->name('index');
            Route::post('/validate-dp-proyek', [ImportController::class, 'validateDpProyek'])->name('validate-dp-proyek');
            Route::post('/validate-dp-kantor', [ImportController::class, 'validateDpKantor'])->name('validate-dp-kantor');
            Route::post('/validate-list-izin', [ImportController::class, 'validateListIzin'])->name('validate-list-izin');
            Route::post('/validate-simbg-monitoring', [ImportController::class, 'validateSimbgMonitoring'])->name('validate-simbg-monitoring');
            Route::post('/validate-mppd-semua', [ImportController::class, 'validateMppdSemua'])->name('validate-mppd-semua');
            Route::post('/validate-mppd-selesai', [ImportController::class, 'validateMppdSelesai'])->name('validate-mppd-selesai');
            Route::post('/validate-mppd-nextgen-selesai', [ImportController::class, 'validateMppdNextgenSelesai'])->name('validate-mppd-nextgen-selesai');
            Route::post('/validate-mppd-nextgen-ditolak', [ImportController::class, 'validateMppdNextgenDitolak'])->name('validate-mppd-nextgen-ditolak');
        });

        Route::get('/grafik-kbli', [GrafikKbliController::class, 'index'])->name('grafik-kbli.index');
        Route::get('/grafik-kbli/tahunan', [GrafikKbliController::class, 'tahunan'])->name('grafik-kbli.tahunan');

        Route::get('/grafik-rekap-jumlah-investasi', [GrafikRekapJumlahInvestasiController::class, 'index'])->name('grafik-rekap-jumlah-investasi.index');
        Route::get('/grafik-rekap-jumlah-investasi/table', [GrafikRekapJumlahInvestasiController::class, 'table'])->name('grafik-rekap-jumlah-investasi.table');
        Route::get('/grafik-rekap-jumlah-investasi/bulanan', [GrafikRekapJumlahInvestasiController::class, 'bulanan'])->name('grafik-rekap-jumlah-investasi.bulanan');
        Route::match(['get', 'post'], '/grafik-rekap-jumlah-investasi/ajax', [GrafikRekapJumlahInvestasiController::class, 'ajaxData'])->name('grafik-rekap-jumlah-investasi.ajax');
        Route::get('/grafik-rekap-jumlah-investasi/export', [GrafikRekapJumlahInvestasiController::class, 'export'])->name('grafik-rekap-jumlah-investasi.export');

        Route::get('/grafik-rekap-jumlah-tki', [GrafikRekapJumlahTkiController::class, 'index'])->name('grafik-rekap-jumlah-tki.index');
        Route::get('/grafik-rekap-jumlah-tki/table', [GrafikRekapJumlahTkiController::class, 'table'])->name('grafik-rekap-jumlah-tki.table');
        Route::get('/grafik-rekap-jumlah-tki/bulanan', [GrafikRekapJumlahTkiController::class, 'bulanan'])->name('grafik-rekap-jumlah-tki.bulanan');
        Route::match(['get', 'post'], '/grafik-rekap-jumlah-tki/ajax', [GrafikRekapJumlahTkiController::class, 'ajaxData'])->name('grafik-rekap-jumlah-tki.ajax');
        Route::get('/grafik-rekap-jumlah-tki/export', [GrafikRekapJumlahTkiController::class, 'export'])->name('grafik-rekap-jumlah-tki.export');

        Route::get('/grafik-rekap-kbli', [GrafikRekapKbliController::class, 'index'])->name('grafik-rekap-kbli.index');
        Route::get('/grafik-rekap-kbli/table', [GrafikRekapKbliController::class, 'table'])->name('grafik-rekap-kbli.table');
        Route::get('/grafik-rekap-kbli/bulanan', [GrafikRekapKbliController::class, 'bulanan'])->name('grafik-rekap-kbli.bulanan');
        Route::match(['get', 'post'], '/grafik-rekap-kbli/ajax', [GrafikRekapKbliController::class, 'ajaxData'])->name('grafik-rekap-kbli.ajax');
        Route::get('/grafik-rekap-kbli/export', [GrafikRekapKbliController::class, 'export'])->name('grafik-rekap-kbli.export');

        Route::get('/grafik-rekap-list-izin', [GrafikRekapListIzinController::class, 'index'])->name('grafik-rekap-list-izin.index');
        Route::get('/grafik-rekap-list-izin/table', [GrafikRekapListIzinController::class, 'table'])->name('grafik-rekap-list-izin.table');
        Route::get('/grafik-rekap-list-izin/bulanan', [GrafikRekapListIzinController::class, 'bulanan'])->name('grafik-rekap-list-izin.bulanan');
        Route::match(['get', 'post'], '/grafik-rekap-list-izin/ajax', [GrafikRekapListIzinController::class, 'ajaxData'])->name('grafik-rekap-list-izin.ajax');
        Route::get('/grafik-rekap-list-izin/export', [GrafikRekapListIzinController::class, 'export'])->name('grafik-rekap-list-izin.export');

        Route::get('/satu-data-realisasi-investasi', [RealisasiInvestasiController::class, 'index'])->name('realisasi-investasi.index');
        Route::match(['get', 'post'], '/satu-data-realisasi-investasi/data', [RealisasiInvestasiController::class, 'data'])->name('realisasi-investasi.data');
        Route::get('/satu-data-realisasi-investasi/export', [RealisasiInvestasiController::class, 'export'])->name('realisasi-investasi.export');

        Route::get('/rekap-10-kbli-teratas', [Rekap10KbliTeratasController::class, 'index'])->name('rekap-10-kbli-teratas.index');
        Route::get('/rekap-10-kbli-teratas/export', [Rekap10KbliTeratasController::class, 'export'])->name('rekap-10-kbli-teratas.export');

        Route::get('/rekap-sektor', [RekapSektorController::class, 'index'])->name('rekap-sektor.index');
        Route::get('/rekap-sektor/export', [RekapSektorController::class, 'export'])->name('rekap-sektor.export');

        Route::get('/rekap-nib', [RekapNibController::class, 'index'])->name('rekap-nib.index');
        Route::get('/rekap-nib/export', [RekapNibController::class, 'export'])->name('rekap-nib.export');

        Route::get('/rekap-kbli-kecamatan', [RekapKbliKecamatanController::class, 'index'])->name('rekap-kbli-kecamatan.index');
        Route::get('/rekap-kbli-kecamatan/export', [RekapKbliKecamatanController::class, 'export'])->name('rekap-kbli-kecamatan.export');

        Route::get('/rekap-izin-kbli', [RekapIzinKbliController::class, 'index'])->name('rekap-izin-kbli.index');
        Route::get('/rekap-izin-kbli/export', [RekapIzinKbliController::class, 'export'])->name('rekap-izin-kbli.export');

        Route::get('/rekap-jenis-izin', [RekapJenisIzinController::class, 'index'])->name('rekap-jenis-izin.index');
        Route::get('/rekap-jenis-izin/export', [RekapJenisIzinController::class, 'export'])->name('rekap-jenis-izin.export');

        Route::prefix('statistik')->name('statistik.')->group(function () {
            Route::get('/statistik-kantor', [StatistikController::class, 'kantor'])->name('kantor');
            Route::get('/statistik-kantor/download-excel', [StatistikController::class, 'kantorExcel'])->name('kantor-download-excel');

            Route::get('/statistik-proyek', [StatistikController::class, 'proyek'])->name('proyek');
            Route::get('/statistik-proyek/download-excel', [StatistikController::class, 'proyekExcel'])->name('proyek-download-excel');
            Route::get('/statistik-proyek/download-word', [StatistikController::class, 'proyekWord'])->name('proyek-download-word');

            Route::get('/statistik-izin', [StatistikController::class, 'izin'])->name('izin');
            Route::get('/statistik-izin/download-excel', [StatistikController::class, 'izinExcel'])->name('izin-download-excel');

            Route::get('/statistik-simbg', [StatistikController::class, 'simbg'])->name('simbg');
            Route::get('/statistik-simbg/kelurahan', [StatistikController::class, 'simbgKelurahanAjax'])->name('simbg-kelurahan');
        });

        Route::prefix('overview')->name('overview.')->group(function () {
            Route::get('/oss-per-kecamatan', [OverviewOssController::class, 'perKecamatan'])->name('oss-per-kecamatan.index');
            Route::get('/oss-per-kecamatan/export', [OverviewOssController::class, 'perKecamatanExport'])->name('oss-per-kecamatan.export');

            Route::get('/oss-per-kecamatan-kbli', [OverviewOssController::class, 'perKecamatanKbli'])->name('oss-per-kecamatan-kbli.index');
            Route::get('/oss-per-kecamatan-kbli/export', [OverviewOssController::class, 'perKecamatanKbliExport'])->name('oss-per-kecamatan-kbli.export');

            Route::get('/oss-per-kecamatan-kbli-kelas1', [OverviewOssController::class, 'perKecamatanKbliKelas1'])->name('oss-per-kecamatan-kbli-kelas1.index');
            Route::get('/oss-per-kecamatan-kbli-kelas1/export', [OverviewOssController::class, 'perKecamatanKbliKelas1Export'])->name('oss-per-kecamatan-kbli-kelas1.export');

            Route::get('/oss-per-kecamatan-kbli-kelas2', [OverviewOssController::class, 'perKecamatanKbliKelas2'])->name('oss-per-kecamatan-kbli-kelas2.index');
            Route::get('/oss-per-kecamatan-kbli-kelas2/export', [OverviewOssController::class, 'perKecamatanKbliKelas2Export'])->name('oss-per-kecamatan-kbli-kelas2.export');

            Route::get('/oss-per-klasifikasi', [OverviewOssController::class, 'perKlasifikasi'])->name('oss-per-klasifikasi.index');
            Route::get('/oss-per-klasifikasi/export', [OverviewOssController::class, 'perKlasifikasiExport'])->name('oss-per-klasifikasi.export');

            Route::get('/simbg-resume-pertahun', [OverviewSimbgController::class, 'resumePertahun'])->name('simbg-resume-pertahun');
            Route::get('/simbg-resume-pertahun/export', [OverviewSimbgController::class, 'exportResumePertahun'])->name('simbg-resume-pertahun.export');

            Route::get('/simbg-rekap-perkecamatan-pertahun', [OverviewSimbgController::class, 'rekapPerkecamatanPertahun'])->name('simbg-rekap-perkecamatan-pertahun');
            Route::get('/simbg-rekap-perkecamatan-pertahun/export', [OverviewSimbgController::class, 'exportRekapPerkecamatanPertahun'])->name('simbg-rekap-perkecamatan-pertahun.export');

            Route::get('/simbg-fungsi-per-kecamatan', [OverviewSimbgController::class, 'fungsiPerKecamatan'])->name('simbg-fungsi-per-kecamatan');
            Route::get('/simbg-fungsi-per-kecamatan/export', [OverviewSimbgController::class, 'exportFungsiPerKecamatan'])->name('simbg-fungsi-per-kecamatan-export');

            Route::get('/simbg-slf-pbg-pertahun', [OverviewSimbgController::class, 'slfPbgPertahun'])->name('simbg-slf-pbg-pertahun');
            Route::get('/simbg-slf-pbg-pertahun/export', [OverviewSimbgController::class, 'exportSlfPbgPertahun'])->name('simbg-slf-pbg-pertahun-export');

            Route::get('/mppd-sip-perkecamatan', [OverviewMppdController::class, 'sipPerkecamatan'])->name('mppd-sip-perkecamatan');
            Route::get('/mppd-sip-perkecamatan/export', [OverviewMppdController::class, 'exportSipPerkecamatan'])->name('mppd-sip-perkecamatan-export');

            Route::get('/mppd-sip-per-fasilitas', [OverviewMppdController::class, 'sipPerFasilitas'])->name('mppd-sip-per-fasilitas');
            Route::get('/mppd-sip-per-fasilitas/export', [OverviewMppdController::class, 'exportSipPerFasilitas'])->name('mppd-sip-per-fasilitas-export');

            Route::get('/mppd-resume-perbulan', [OverviewMppdController::class, 'resumePerbulan'])->name('mppd-resume-perbulan');
            Route::get('/mppd-resume-perbulan/export', [OverviewMppdController::class, 'exportResumePerbulan'])->name('mppd-resume-perbulan-export');

            Route::get('/mppd-profesi-pertahun', [OverviewMppdController::class, 'profesiPertahun'])->name('mppd-profesi-pertahun');
            Route::get('/mppd-profesi-pertahun/export', [OverviewMppdController::class, 'exportProfesiPertahun'])->name('mppd-profesi-pertahun-export');

            Route::get('/mppd-profesi-per-kecamatan', [OverviewMppdController::class, 'profesiPerKecamatan'])->name('mppd-profesi-per-kecamatan');
            Route::get('/mppd-profesi-per-kecamatan/export', [OverviewMppdController::class, 'exportProfesiPerKecamatan'])->name('mppd-profesi-per-kecamatan-export');

            Route::get('/mppd-permohonan-per-bulan', [OverviewMppdController::class, 'permohonanPerBulan'])->name('mppd-permohonan-per-bulan');
            Route::get('/mppd-permohonan-per-bulan/export', [OverviewMppdController::class, 'exportPermohonanPerBulan'])->name('mppd-permohonan-per-bulan-export');

            Route::get('/mppd-permohonan-per-bulan-horizontal', [OverviewMppdController::class, 'permohonanPerBulanHorizontal'])->name('mppd-permohonan-per-bulan-horizontal');
            Route::get('/mppd-permohonan-per-bulan-horizontal/export', [OverviewMppdController::class, 'exportPermohonanPerBulanHorizontal'])->name('mppd-permohonan-per-bulan-horizontal-export');

            Route::get('/mppd-frekuensi-per-individu', [OverviewMppdController::class, 'frekuensiPerIndividu'])->name('mppd-frekuensi-per-individu');
            Route::get('/mppd-frekuensi-per-individu/export', [OverviewMppdController::class, 'exportFrekuensiPerIndividu'])->name('mppd-frekuensi-per-individu-export');

            Route::get('/mppd-faskes-per-kecamatan', [OverviewMppdController::class, 'faskesPerKecamatan'])->name('mppd-faskes-per-kecamatan');
            Route::get('/mppd-faskes-per-kecamatan/export', [OverviewMppdController::class, 'exportFaskesPerKecamatan'])->name('mppd-faskes-per-kecamatan-export');
        });
        Route::get('/lppd/lppd-realisasi-investasi', [LppdController::class, 'realisasiInvestasi'])->name('lppd.realisasi-investasi.index');
        Route::get('/lppd/lppd-realisasi-investasi/export', [LppdController::class, 'exportRealisasiInvestasi'])->name('lppd.realisasi-investasi.export');

        Route::get('/lppd/lppd-realisasi-per-kecamatan', [LppdController::class, 'realisasiPerKecamatan'])->name('lppd.realisasi-per-kecamatan.index');
        Route::get('/lppd/lppd-realisasi-per-kecamatan/export', [LppdController::class, 'exportRealisasiPerKecamatan'])->name('lppd.realisasi-per-kecamatan.export');

        Route::get('/lppd/lppd-rincian-realisasi', [LppdController::class, 'rincianRealisasi'])->name('lppd.rincian-realisasi.index');
        Route::get('/lppd/lppd-rincian-realisasi/export', [LppdController::class, 'exportRincianRealisasi'])->name('lppd.rincian-realisasi.export');

        Route::get('/lppd/lppd-rekap-investasi', [LppdController::class, 'rekapInvestasi'])->name('lppd.rekap-investasi.index');
        Route::get('/lppd/lppd-rekap-investasi/export', [LppdController::class, 'exportRekapInvestasi'])->name('lppd.rekap-investasi.export');

        Route::get('/lppd/lppd-status-investasi', [LppdController::class, 'statusInvestasi'])->name('lppd.status-investasi.index');
        Route::get('/lppd/lppd-status-investasi/export', [LppdController::class, 'exportStatusInvestasi'])->name('lppd.status-investasi.export');

        Route::get('/lppd/target-investasi', [LppdController::class, 'targetInvestasi'])->name('lppd.target-investasi.index');
        Route::post('/lppd/target-investasi', [LppdController::class, 'storeTargetInvestasi'])->name('lppd.target-investasi.store');
        Route::get('/lppd/target-investasi/export', [LppdController::class, 'exportTargetInvestasi'])->name('lppd.target-investasi.export');
    });
});

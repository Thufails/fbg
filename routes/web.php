<?php

use App\Http\Controllers\akun\akunController;
use App\Http\Controllers\analisis\analisisController;
use App\Http\Controllers\detailAkun\detailAkunController;
use App\Http\Controllers\detailLaporan\detailLaporanController;
use App\Http\Controllers\detailRealisasi\detailRealisasiController;
use App\Http\Controllers\detailTarget\detailTargetController;
use App\Http\Controllers\entitas\entitasController;
use App\Http\Controllers\issue\issueController;
use App\Http\Controllers\komplain\komplainController;
use App\Http\Controllers\laporan\laporanController;
use App\Http\Controllers\laporan_bulanan\laporanBulananController;
use App\Http\Controllers\penjualan\penjualanController;
use App\Http\Controllers\produk\produkController;
use App\Http\Controllers\target\targetController;
use App\Http\Controllers\tugas\tugasController;
use App\Http\Controllers\realisasi\realisasiController;
use App\Http\Controllers\user\forgotPasswordController;
use App\Http\Controllers\user\userController;
use App\Http\Controllers\konversiTanggal\konversiTanggalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\companyProfile\companyProfileController;
use App\Http\Controllers\home\homeController;

Route::view('/', 'user.login');

// Route untuk guest (tidak perlu login)
Route::middleware('guest')->group(function () {
    Route::get('/user/login', [userController::class, 'login'])->name('login');
    Route::post('/user/login', [userController::class, 'doLogin'])->name('login.post');
    Route::get('/user/verifyAccount/{token}', [userController::class, 'verifyAccount'])->name('user.verifyAccount');
    Route::get('/user/forgotPassword', [forgotPasswordController::class, 'forgotPasswordForm'])->name('user.forgotPassword');
});

// Route untuk semua yang sudah login (admin, user, guest)
Route::middleware('auth')->group(function () {
    Route::get('/home', [homeController::class, 'index'])->name('home');

    Route::get('/company-profile', [companyProfileController::class, 'edit'])->name('profilPerusahaan.edit');
    Route::post('/company-profile', [companyProfileController::class, 'update'])->name('profilPerusahaan.update');
    
    Route::view('/dashboard', 'dashboard.view')->name('dashboard.view');
    Route::get('/user/logout', [userController::class, 'logout'])->name('logout');
    Route::get('/user/updateData', [userController::class, 'updateData'])->name('user.updateData');
    Route::post('/user/updateData', [userController::class, 'doUpdateData'])->name('user.updateData.post');

    // Route untuk admin saja
    Route::middleware('ceklevel:admin')->group(function () {
        Route::get('/akun', [akunController::class,'index'])->name('akun.view');
        Route::post('/akun', [akunController::class,'store'])->name('akun.store');
        Route::get('/akun/insert', [AkunController::class, 'create'])->name('akun.insert');
        Route::get('/akun/edit/{kd_akun}', [akunController::class, 'edit'])->name('akun.edit');
        Route::put('/akun/update/{kd_akun}', [akunController::class, 'update'])->name('akun.update');
        Route::delete('/akun/{kd_akun}', [akunController::class, 'destroy'])->name('akun.destroy');
        Route::get('/akun/{kd_akun}', [akunController::class, 'show'])->name('akun.detail');

        Route::get('/entitas', [entitasController::class,'index'])->name('entitas.view');
        Route::post('/entitas', [entitasController::class,'store'])->name('entitas.store');
        Route::get('/entitas/insert', [entitasController::class, 'create'])->name('entitas.insert');
        Route::get('/entitas/edit/{kd_entitas}', [entitasController::class, 'edit'])->name('entitas.edit');
        Route::put('/entitas/update/{kd_entitas}', [entitasController::class, 'update'])->name('entitas.update');
        Route::delete('/entitas/{kd_entitas}', [entitasController::class, 'destroy'])->name('entitas.destroy');

        Route::get('/produk', [produkController::class,'index'])->name('produk.view');
        Route::post('/produk', [produkController::class,'store'])->name('produk.store');
        Route::get('/produk/insert', [produkController::class, 'create'])->name('produk.insert');
        Route::get('/produk/edit/{kd_produk}', [produkController::class, 'edit'])->name('produk.edit');
        Route::put('/produk/update/{kd_produk}', [produkController::class, 'update'])->name('produk.update');
        Route::delete('/produk/{kd_produk}', [produkController::class, 'destroy'])->name('produk.destroy');
        
        Route::get('/user', [userController::class, 'index'])->name('user.view');
        Route::get('/user/insert', [userController::class, 'create'])->name('user.insert');
        Route::post('/user/store', [userController::class, 'store'])->name('user.store');    
        Route::delete('/user/{id}', [userController::class, 'destroy'])->name('user.destroy');   
        Route::get('/user/edit/{id}', [userController::class, 'edit'])->name('user.edit'); 
    });

    // Route untuk admin dan user (bukan guest)
    Route::middleware('ceklevel:admin,analis')->group(function () {
        Route::get('/analisis/search', [analisisController::class, 'search'])->name('analisis.search');
        Route::get('/analisis', [analisisController::class,'index'])->name('analisis.view');
        Route::post('/analisis', [analisisController::class,'store'])->name('analisis.store');
        Route::get('/analisis/insert', [analisisController::class, 'create'])->name('analisis.insert');
        Route::get('/analisis/edit/{kd_analisis}', [analisisController::class, 'edit'])->name('analisis.edit');
        Route::put('/analisis/update/{kd_analisis}', [analisisController::class, 'update'])->name('analisis.update');
        Route::delete('/analisis/{kd_analisis}', [analisisController::class, 'destroy'])->name('analisis.destroy');
        Route::get('/analisis/{kd_analisis}', [analisisController::class, 'show'])->name('analisis.detail');

        Route::get('/laporan_bulanan/{kd_laporan}', [laporanBulananController::class, 'index'])->name('laporan_bulanan.generate');
        Route::get('/laporan_bulanan/detail/{kd_laporan}', [laporanBulananController::class, 'showLaporanBulanan'])->name('laporan_bulanan.view');
        Route::get('/laporan_bulanan/preview/{kd_laporan}', [laporanBulananController::class, 'preview'])->name('laporan_bulanan.preview');
        Route::get('/laporan/{kd_laporan}/analisa-grafik/{title}', [laporanBulananController::class, 'analisaGrafik'])->name('laporan_bulanan.analisaGrafik');
    });

    // Route untuk semua level yang sudah login (admin, user, guest)
    Route::middleware('ceklevel:admin,user,guest')->group(function () {
        // ========== ROUTE SEARCH HARUS DITARUH DI ATAS ==========
        Route::get('/issue/search', [issueController::class, 'search'])->name('issue.search');
        Route::get('/komplain/search', [komplainController::class, 'search'])->name('komplain.search');
        Route::get('/tugas/search', [tugasController::class, 'search'])->name('tugas.search'); // ← DIPINDAHKAN KE SINI

        Route::get('/issue', [issueController::class,'index'])->name('issue.view');
        Route::post('/issue', [issueController::class,'store'])->name('issue.store');
        Route::get('/issue/insert', [issueController::class, 'create'])->name('issue.insert');
        Route::get('/issue/edit/{kd_issue}', [issueController::class, 'edit'])->name('issue.edit');
        Route::put('/issue/update/{kd_issue}', [issueController::class, 'update'])->name('issue.update');
        Route::delete('/issue/{kd_issue}', [issueController::class, 'destroy'])->name('issue.destroy');

        Route::get('/tugas', [tugasController::class,'index'])->name('tugas.view');
        Route::post('/tugas', [tugasController::class,'store'])->name('tugas.store');
        Route::get('/tugas/insert', [tugasController::class, 'create'])->name('tugas.insert');
        Route::get('/tugas/edit/{kd_tugas}', [tugasController::class, 'edit'])->name('tugas.edit');
        Route::put('/tugas/update/{kd_tugas}', [tugasController::class, 'update'])->name('tugas.update');
        Route::delete('/tugas/{kd_tugas}', [tugasController::class, 'destroy'])->name('tugas.destroy');

        Route::get('/komplain', [komplainController::class,'index'])->name('komplain.view');
        Route::post('/komplain', [komplainController::class,'store'])->name('komplain.store');
        Route::get('/komplain/insert', [komplainController::class, 'create'])->name('komplain.insert');
        Route::get('/komplain/edit/{kd_komplain}', [komplainController::class, 'edit'])->name('komplain.edit');
        Route::put('/komplain/update/{kd_komplain}', [komplainController::class, 'update'])->name('komplain.update');
        Route::delete('/komplain/{kd_komplain}', [komplainController::class, 'destroy'])->name('komplain.destroy');
        Route::get('/komplain/{kd_komplain}', [komplainController::class, 'show'])->name('komplain.detail');

        Route::get('/laporan', [laporanController::class,'index'])->name('laporan.view');
        Route::post('/laporan', [laporanController::class,'store'])->name('laporan.store');
        Route::get('/laporan/insert', [laporanController::class, 'create'])->name('laporan.insert');
        Route::get('/laporan/edit/{kd_laporan}', [laporanController::class, 'edit'])->name('laporan.edit');
        Route::put('/laporan/update/{kd_laporan}', [laporanController::class, 'update'])->name('laporan.update');
        Route::delete('/laporan/{kd_laporan}', [laporanController::class, 'destroy'])->name('laporan.destroy');
        Route::get('/laporan/{kd_laporan}', [laporanController::class, 'show'])->name('laporan.detail');
        Route::get('/laporan/json/{kd_laporan}', [laporanController::class, 'generateJsonReport'])->name('laporan.json');

        Route::get('/detailAkun', [detailAkunController::class,'index'])->name('detailAkun.view');
        Route::post('/detailAkun', [detailAkunController::class,'store'])->name('detailAkun.store');
        Route::get('/detailAkun/insert/{kd_akun}', [detailAkunController::class, 'create'])->name('detailAkun.insert');
        Route::get('/detailAkun/edit/{kd_detail_akun}', [detailAkunController::class, 'edit'])->name('detailAkun.edit');
        Route::put('/detailAkun/update/{kd_detail_akun}', [detailAkunController::class, 'update'])->name('detailAkun.update');
        Route::delete('/detailAkun/{kd_detail_akun}', [detailAkunController::class, 'destroy'])->name('detailAkun.destroy');

        Route::get('/target', [targetController::class,'index'])->name('target.view');
        Route::post('/target', [targetController::class,'store'])->name('target.store');
        Route::get('/target/insert', [targetController::class, 'create'])->name('target.insert');
        Route::get('/target/edit/{kd_target}', [targetController::class, 'edit'])->name('target.edit');
        Route::post('/target/bulk-destroy', [targetController::class, 'bulkDestroy'])->name('target.bulkDestroy');
        Route::post('/target/bulk-update', [targetController::class, 'bulkUpdate'])->name('target.bulkUpdate');
        Route::get('/target/detail', [targetController::class, 'detail'])->name('target.detail');

        // Rute untuk detailTarget
        Route::prefix('detailTarget')->group(function () {
            Route::get('/', [detailTargetController::class, 'index'])->name('detailTarget.view');
            Route::post('/', [detailTargetController::class, 'index'])->name('detailTarget.filter');
            Route::get('/insert', [detailTargetController::class, 'create'])->name('detailTarget.insert');
            Route::post('/store', [detailTargetController::class,'store'])->name('detailTarget.store');
            Route::delete('/bulk-destroy', [detailTargetController::class, 'bulkDestroy'])->name('detailTarget.bulkDestroy');
            Route::post('/bulk-update', [detailTargetController::class, 'bulkUpdate'])->name('detailTarget.bulkUpdate');
            Route::get('/select-target', [detailTargetController::class, 'selectTarget'])->name('detailTarget.select');
            Route::post('/add-to-cart', [detailTargetController::class, 'addToCart'])->name('detailTarget.addToCart');
            Route::get('/cart', [detailTargetController::class, 'showCart'])->name('detailTarget.show');
            Route::post('/cart/clear', [detailTargetController::class, 'clearCart'])->name('detailTarget.clear');
            Route::get('/cart/empty', [detailTargetController::class, 'emptyCart'])->name('detailTarget.empty');
            Route::delete('/cart/item/{id}', [detailTargetController::class, 'removeItem'])->name('detailTarget.item.remove');
        });

        // Realisasi Routes
        Route::prefix('realisasi')->group(function () {
            Route::get('/', [realisasiController::class, 'index'])->name('realisasi.view');
            Route::get('/select-target', [realisasiController::class, 'selectTarget'])->name('realisasi.select');
            Route::post('/add-to-cart', [realisasiController::class, 'addToCart'])->name('realisasi.addToCart');
            Route::get('/cart', [realisasiController::class, 'showCart'])->name('realisasi.cart');
            Route::post('/store', [realisasiController::class, 'store'])->name('realisasi.store');
            Route::get('/remove-from-cart/{index}', [realisasiController::class, 'removeFromCart'])->name('realisasi.removeFromCart');
            Route::get('/empty-cart', [realisasiController::class, 'emptyCart'])->name('realisasi.emptyCart');
            Route::delete('/bulk-destroy', [realisasiController::class, 'bulkDestroy'])->name('realisasi.bulkDestroy');
            // Route untuk edit realisasi
            Route::get('/realisasi/{id}/edit', [realisasiController::class, 'edit'])->name('realisasi.edit');
            Route::put('/realisasi/{id}', [realisasiController::class, 'update'])->name('realisasi.update');

            // routes/web.php
            Route::get('/realisasi/input-massal', [realisasiController::class, 'inputMassal'])->name('realisasi.inputMassal');
            Route::post('/realisasi/store-massal', [realisasiController::class, 'storeMassal'])->name('realisasi.storeMassal');
            Route::post('/realisasi/get-target-data', [realisasiController::class, 'getTargetData'])->name('realisasi.getTargetData');
            Route::get('/realisasi/results', [realisasiController::class, 'showResults'])->name('realisasi.results');
        });

        // Realisasi Routes
        Route::prefix('detailRealisasi')->group(function () {
            Route::get('/', [detailRealisasiController::class, 'index'])->name('detailRealisasi.view');
            Route::get('/select-target', [detailRealisasiController::class, 'selectTarget'])->name('detailRealisasi.select');
            Route::post('/add-to-cart', [detailRealisasiController::class, 'addToCart'])->name('detailRealisasi.addToCart');
            Route::get('/cart', [detailRealisasiController::class, 'showCart'])->name('detailRealisasi.cart');
            Route::post('/store', [detailRealisasiController::class, 'store'])->name('detailRealisasi.store');
            Route::get('/remove-from-cart/{index}', [detailRealisasiController::class, 'removeFromCart'])->name('detailRealisasi.removeFromCart');
            Route::get('/empty-cart', [detailRealisasiController::class, 'emptyCart'])->name('detailRealisasi.emptyCart');
            Route::delete('/bulk-destroy', [detailRealisasiController::class, 'bulkDestroy'])->name('detailRealisasi.bulkDestroy');
        });

        // Penjualan Routes
        Route::prefix('penjualan')->group(function () {
            Route::get('/', [penjualanController::class, 'index'])->name('penjualan.view');
            Route::get('/insert', [penjualanController::class, 'create'])->name('penjualan.insert');
            Route::post('/store', [penjualanController::class, 'store'])->name('penjualan.store');
            Route::get('/edit/{kd_penjualan}', [penjualanController::class, 'edit'])->name('penjualan.edit');
            Route::put('/update/{kd_penjualan}', [penjualanController::class, 'update'])->name('penjualan.update');
            Route::delete('/destroy/{kd_penjualan}', [penjualanController::class, 'destroy'])->name('penjualan.destroy');
            Route::delete('/bulk-destroy', [penjualanController::class, 'bulkDestroy'])->name('penjualan.bulkDestroy');
            Route::post('/add-to-cart', [penjualanController::class, 'addToCart'])->name('penjualan.addToCart');
            Route::get('/cart', [penjualanController::class, 'showCart'])->name('penjualan.cart');
            Route::delete('/cart/remove/{index}', [penjualanController::class, 'removeFromCart'])->name('penjualan.removeFromCart');
            Route::get('/cart/empty', [penjualanController::class, 'emptyCart'])->name('penjualan.emptyCart');
            Route::post('/cart/store', [penjualanController::class, 'storeCart'])->name('penjualan.storeCart');
        });

        // Route untuk halaman laporan laba rugi
        Route::get('/detailLaporan', [detailLaporanController::class, 'index'])->name('detailLaporan.view');
        Route::get('/detailLaporan/get-laporan-data', [detailLaporanController::class, 'getDataTableLaporan']);
        Route::post('/detailLaporan/get-chart-data', [detailLaporanController::class, 'getDataChart']);

        Route::post('/laporan/analyze', [laporanController::class, 'analyze'])->name('laporan.analyze');
        Route::post('/laporan/generate-conclusion', [laporanController::class, 'generateConclusion'])->name('laporan.generate.conclusion');
        Route::put('/laporan/hasil-analisis/{kd_hasil_analisis}', [laporanController::class, 'updateAnalysis'])->name('laporan.update.analysis');

        // Routes untuk prompt correction
        Route::get('/laporan/{kd_laporan}/prompt-form/{title}', [laporanController::class, 'showPromptForm'])->name('laporan.prompt.form');
        Route::post('/laporan/execute-analysis', [laporanController::class, 'executeAnalysis'])->name('laporan.execute.analysis');

        Route::get('/generate-laporan/{kd_laporan}', function ($kd_laporan) {
            $filePath = public_path('dash/final_report.php');
            
            if (file_exists($filePath)) {
                // Set parameter GET sebelum include file
                $_GET['kd_laporan'] = $kd_laporan;
                
                // Capture output buffer
                ob_start();
                include $filePath;
                $content = ob_get_clean();
                
                return response($content);
            }
            
            abort(404, 'File laporan tidak ditemukan');
        })->name('laporan.final_report');

        Route::get('/preview-laporan/{kd_laporan}', function ($kd_laporan) {
            $filePath = public_path('dash/preview_report.php');
            
            if (file_exists($filePath)) {
                // Set parameter GET sebelum include file
                $_GET['kd_laporan'] = $kd_laporan;
                
                // Capture output buffer
                ob_start();
                include $filePath;
                $content = ob_get_clean();
                
                return response($content);
            }
            
            abort(404, 'File laporan tidak ditemukan');
        })->name('laporan.preview_report');


        Route::get('/laporan/{kd_laporan}/view-pdf', [laporanController::class, 'viewPdf'])->name('laporan.view.pdf');
        Route::get('/laporan/{kd_laporan}/download-pdf', [laporanController::class, 'downloadPdf'])->name('laporan.download.pdf');

        // Routes untuk Konversi Tanggal
        Route::prefix('konversi-tanggal')->group(function () {
            Route::get('/', [konversiTanggalController::class, 'index'])->name('konversiTanggal.view');
            Route::get('/create', [konversiTanggalController::class, 'create'])->name('konversiTanggal.create');

            
            
            // Hanya POST method untuk preview
            Route::post('/preview', [konversiTanggalController::class, 'preview'])->name('konversiTanggal.preview');
            
            Route::post('/', [konversiTanggalController::class, 'store'])->name('konversiTanggal.store');
            Route::get('/{id}/edit', [konversiTanggalController::class, 'edit'])->name('konversiTanggal.edit');
            Route::put('/{id}', [konversiTanggalController::class, 'update'])->name('konversiTanggal.update');
            Route::delete('/{id}', [konversiTanggalController::class, 'destroy'])->name('konversiTanggal.destroy');
        });

        Route::get('/konversi-tanggal/tanggal-hijriah', function ($kd_laporan) {
            $filePath = public_path('dash/tahun_hijriah.html');
            
            if (file_exists($filePath)) {
                // Set parameter GET sebelum include file
                
                // Capture output buffer
                ob_start();
                include $filePath;
                $content = ob_get_clean();
                
                return response($content);
            }            
            abort(404, 'File laporan tidak ditemukan');
        })->name('konversi_tanggal.popup');

    });
});
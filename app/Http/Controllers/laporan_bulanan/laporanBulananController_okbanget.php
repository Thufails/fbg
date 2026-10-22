<?php

namespace App\Http\Controllers\laporan_bulanan;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Grafik;
use App\Models\HasilAnalisis;
use App\Models\Laporan;
use Illuminate\Support\Facades\File;
use DateTime;
use Illuminate\Support\Facades\Log;

class laporanBulananController extends Controller
{
    public function index($kd_laporan)
{
    // Inisialisasi semua variabel yang digunakan dalam compact()
    $reportData = [];
    $reportDataAnalysis = null;
    $dailyRealisasi = [];
    $dailyTarget = [];
    $summaryData = [];
    $monthlyDailyRealisasi = [];
    $monthlySummaryRealisasi = [];
    $yearlyMonthlyRealisasi = [];
    $entityDailyRealisasi = [];
    $dateLabels = [];
    $entitySummaryData = [];
    $entityPieData = [];
    $hargaPokokSummaryData = [];
    $monthlyHargaPokokRealisasi = [];
    $hargaPokokTableData = [];

    // Ambil data laporan dari database
    $laporan = DB::table('laporan')
        ->where('kd_laporan', $kd_laporan)
        ->first();
    
    if (!$laporan) {
        return view('laporan_bulanan.view', [
            'error' => 'Laporan dengan kode ' . $kd_laporan . ' tidak ditemukan.'
        ]);
    }
    
    // Ambil tanggal_awal dan tanggal_akhir dari database
    $startDate = $laporan->tanggal_awal;
    $endDate = $laporan->tanggal_akhir;
    
    // Validasi format tanggal
    if (!strtotime($startDate) || !strtotime($endDate)) {
        return view('laporan_bulanan.view', [
            'error' => 'Format tanggal pada laporan tidak valid. Tanggal awal: ' . $startDate . ', Tanggal akhir: ' . $endDate
        ]);
    }
    
    // Konversi ke format yang digunakan dalam kode (YYYYMMDD)
    $startDateFormatted = date('Ymd', strtotime($startDate));
    $endDateFormatted = date('Ymd', strtotime($endDate));
    
    // Extract tahun dan bulan dari endDate untuk keperluan processing
    $endYear = (int)date('Y', strtotime($endDate));
    $endMonth = (int)date('m', strtotime($endDate));
    
    // Untuk kompatibilitas dengan kode existing, kita tetap gunakan variabel lama
    $startDate = $startDateFormatted;
    $endDate = $endDateFormatted;

    // Data realisasi harian per bulan untuk grafik, diambil dari database
    $monthlyDailyRealisasi = [];
    $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    // Loop untuk mengambil data untuk 12 bulan penuh dalam endYear
    $monthlySummaryRealisasi = [];
    for ($i = 1; $i <= 12; $i++) {
        $month = $i;
        $year = $endYear;

        $dailyData = array_fill(0, 31, 0);
        $totalRealisasiBulan = 0;

        // Hanya ambil data dari database jika bulan <= endMonth
        if ($month <= $endMonth) {
            // Mengambil data realisasi harian dari database
            $realisasiData = DB::table('realisasi')
                ->select(DB::raw('DAY(realisasi.tanggal) as day'), DB::raw('SUM(realisasi.nilai) as total_realisasi'))
                ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
                ->where('target.kd_akun', 'like', '01%')
                ->whereYear('realisasi.tanggal', $year)
                ->whereMonth('realisasi.tanggal', $month)
                ->groupBy('day')
                ->get();
            
            foreach ($realisasiData as $item) {
                if ($item->day >= 1 && $item->day <= 31) {
                    $dailyData[$item->day - 1] += $item->total_realisasi;
                    $totalRealisasiBulan += $item->total_realisasi;
                }
            }
        }
        
        $monthlyDailyRealisasi[$monthNames[$month - 1]] = $dailyData;
        $monthlySummaryRealisasi[$monthNames[$month - 1]] = $totalRealisasiBulan;
    }

    // Data untuk grafik perbandingan antar tahun
    $yearlyMonthlyRealisasi = [];
    for ($year = $endYear; $year >= $endYear - 5; $year--) {
        $monthlyData = array_fill(0, 12, 0); // 12 bulan
        for ($month = 1; $month <= 12; $month++) {
            if ($year == $endYear && $month > $endMonth) {
                $monthlyData[$month - 1] = 0;
                continue;
            }
            
            $realisasiData = DB::table('realisasi')
                ->select(DB::raw('SUM(realisasi.nilai) as total_realisasi'))
                ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
                ->where('target.kd_akun', 'like', '01%')
                ->whereYear('realisasi.tanggal', $year)
                ->whereMonth('realisasi.tanggal', $month)
                ->first();
            
            $monthlyData[$month - 1] = $realisasiData->total_realisasi ?? 0;
        }
        $yearlyMonthlyRealisasi[$year] = $monthlyData;
    }

    // Data untuk grafik bar perbandingan target dan realisasi harga pokok produksi per bulan
    $monthlyHargaPokokData = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthlyHargaPokokData[$i] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }

    // Query untuk mendapatkan data target dan realisasi HPP per bulan
    $hppData = DB::table('realisasi')
        ->select(
            DB::raw('MONTH(realisasi.tanggal) as month'),
            DB::raw('SUM(realisasi.nilai) as total_realisasi'),
            DB::raw('SUM(target.nilai) as total_target')
        )
        ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->where(function ($query) {
            $query->Where('akun.nama_akun', 'like', '%harga pokok%')
                ->orWhere('akun.nama_akun', 'like', '%HPP%');
        })
        ->whereYear('realisasi.tanggal', $endYear)
        ->whereMonth('realisasi.tanggal', '<=', $endMonth)
        ->groupBy(DB::raw('MONTH(realisasi.tanggal)'))
        ->orderBy('month')
        ->get();

    // Isi data dari hasil query
    foreach ($hppData as $item) {
        if ($item->month >= 1 && $item->month <= 12) {
            $monthlyHargaPokokData[$item->month] = [
                'target' => $item->total_target ?? 0,
                'realisasi' => $item->total_realisasi ?? 0
            ];
        }
    }

    // Siapkan data untuk chart (array dengan 12 bulan)
    $monthlyHargaPokokTarget = [];
    $monthlyHargaPokokRealisasi = [];

    for ($i = 1; $i <= 12; $i++) {
        $monthlyHargaPokokTarget[] = $monthlyHargaPokokData[$i]['target'];
        $monthlyHargaPokokRealisasi[] = $monthlyHargaPokokData[$i]['realisasi'];
    }

    
    // Mengubah hasil query menjadi Collection Laravel dan mengindeksnya berdasarkan bulan
    $hppDataCollection = collect($hppData)->keyBy('month');

    // Menginisialisasi array final yang akan digunakan untuk chart
    $monthlyHargaPokokChartData = [];

    // Looping dari bulan 1 (Januari) hingga endMonth yang ditentukan
    for ($i = 1; $i <= $endMonth; $i++) {
        // Cari data untuk bulan saat ini dari collection yang sudah diindeks
        $item = $hppDataCollection->get($i);

        // Ambil nilai target dan realisasi. Jika data tidak ada ($item null), atur nilainya menjadi 0
        $targetValue = $item->total_target ?? 0;
        $realisasiValue = $item->total_realisasi ?? 0;

        // Asumsi Anda memiliki array nama bulan, seperti $monthNames
        $monthName = $monthNames[$i - 1];

        // Masukkan data ke dalam array final dengan nama bulan sebagai kunci
        $monthlyHargaPokokChartData[$monthName] = [
            'target' => $targetValue,
            'realisasi' => $realisasiValue
        ];
    }

    // Data untuk grafik bar perbandingan harga pokok antar entitas
    $hargaPokokSummaryData = [];
    $realisasiHPPperEntitas = DB::table('realisasi')
        ->select('entitas.nama_entitas', DB::raw('SUM(realisasi.nilai) as total_realisasi'), DB::raw('SUM(target.nilai) as total_target'))
        ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
        ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->where(function ($query) {
            $query->Where('akun.nama_akun', 'like', '%HPP%')
                ->orWhere('akun.nama_akun', 'like', '%harga pokok%');
        })
        ->whereBetween('realisasi.tanggal', [$startDate,$endDate])
        ->groupBy('entitas.nama_entitas')
        ->get();
        
    foreach ($realisasiHPPperEntitas as $item) {
        $entity = $item->nama_entitas ?? 'Lain-lain';
        $hargaPokokSummaryData[$entity] = [
            'target' => $item->total_target,
            'realisasi' => $item->total_realisasi
        ];
    }

    // Menyiapkan data untuk tabel perbandingan harga pokok
    $hargaPokokTableData = [];
    foreach ($hargaPokokSummaryData as $entity => $data) {
        $target = $data['target'];
        $realisasi = $data['realisasi'];
        
        $gap = $realisasi - $target;
        $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
        $keterangan = $gap >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        
        $hargaPokokTableData[] = [
            'cabang' => $entity,
            'target' => $target,
            'realisasi' => $realisasi,
            'percentage' => $percentage,
            'gap' => $gap,
            'keterangan' => $keterangan,
        ];
    }

    // Data untuk grafik pie perbandingan realisasi kd_akun 01 antar entitas
    $entityPieData = [];
    $sbsFilePath = storage_path('app/private/laporan_json/laporan_sbs_' . $startDate . '_to_' . $endDate . '.json');
    
    if (!File::exists($sbsFilePath)) {
        return view('laporan_bulanan.view', ['data' => [], 'error' => 'File laporan SBS untuk bulan ini tidak ditemukan.']);
    }
    $sbsJson = File::get($sbsFilePath);
    $sbsData = json_decode($sbsJson, true);
    
    foreach ($sbsData as $item) {
        if (substr($item['kd_akun'], 0, 2) == '01') {
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            if (!isset($entityPieData[$entity])) {
                $entityPieData[$entity] = 0;
            }
            $entityPieData[$entity] += $item['nilai_realisasi'];
        }
    }
    
    // Data untuk tabel dan grafik perbandingan realisasi antar entitas
    $entitySummaryData = [];
    foreach ($sbsData as $item) {
        if (substr($item['kd_akun'], 0, 2) == '01') {
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            if (!isset($entitySummaryData[$entity])) {
                $entitySummaryData[$entity] = [
                    'target' => 0,
                    'realisasi' => 0
                ];
            }
            $entitySummaryData[$entity]['target'] += $item['nilai_target'];
            $entitySummaryData[$entity]['realisasi'] += $item['nilai_realisasi'];
        }
    }

    foreach ($entitySummaryData as $entity => $data) {
        $target = $data['target'];
        $realisasi = $data['realisasi'];
        $gap = $realisasi - $target;
        $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
        $keterangan = $gap >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        
        $entitySummaryData[$entity]['gap'] = $gap;
        $entitySummaryData[$entity]['percentage'] = $percentage;
        $entitySummaryData[$entity]['keterangan'] = $keterangan;
    }

    // Data untuk grafik perbandingan realisasi antar entitas (grafik tren)
    $entityDailyRealisasi = [];
    $dateLabels = [];
    $currentDate = new DateTime(substr($startDate, 0, 4) . '-' . substr($startDate, 4, 2) . '-' . substr($startDate, 6, 2));
    $endDateObj = new DateTime(substr($endDate, 0, 4) . '-' . substr($endDate, 4, 2) . '-' . substr($endDate, 6, 2));
    
    while ($currentDate <= $endDateObj) {
        $dateLabels[] = $currentDate->format('d-M-Y');
        $currentDate->modify('+1 day');
    }

    foreach ($sbsData as $item) {
        if (substr($item['kd_akun'], 0, 2) == '01') {
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            $date = (new DateTime($item['tanggal']))->format('d-M-Y');
            
            if (!isset($entityDailyRealisasi[$entity])) {
                $entityDailyRealisasi[$entity] = array_fill_keys($dateLabels, 0);
            }
            if (isset($entityDailyRealisasi[$entity][$date])) {
                $entityDailyRealisasi[$entity][$date] += $item['nilai_realisasi'];
            }
        }
    }
    
    // Bagian ini untuk tabel dan grafik harian
    $dailyRealisasi = array_fill(0, 31, 0);
    $dailyTarget = array_fill(0, 31, 0);

    foreach ($sbsData as $item) {
        if (substr($item['kd_akun'], 0, 2) == '01') {
            $tanggal = (int)substr($item['tanggal'], -2);
            if ($tanggal >= 1 && $tanggal <= 31) {
                $dailyRealisasi[$tanggal - 1] += $item['nilai_realisasi'];
                $dailyTarget[$tanggal - 1] += $item['nilai_target'];
            }
        }
    }
    
    $calculatedData = [];
    foreach ($sbsData as $item) {
        $kdAkun = $item['kd_akun'];
        if (!isset($calculatedData[$kdAkun])) {
            $calculatedData[$kdAkun] = ['target' => 0, 'realisasi' => 0];
        }
        $calculatedData[$kdAkun]['target'] += $item['nilai_target'];
        $calculatedData[$kdAkun]['realisasi'] += $item['nilai_realisasi'];
    }

    $accounts = Akun::orderBy('urutan', 'asc')->get();
    
    $parentAccounts = $accounts->whereNull('induk');
    foreach ($parentAccounts as $parentAccount) {
        $children = $accounts->where('induk', $parentAccount->kd_akun);
        $totalTarget = isset($calculatedData[$parentAccount->kd_akun]) ? $calculatedData[$parentAccount->kd_akun]['target'] : 0;
        $totalRealisasi = isset($calculatedData[$parentAccount->kd_akun]) ? $calculatedData[$parentAccount->kd_akun]['realisasi'] : 0;
        foreach ($children as $child) {
            if (isset($calculatedData[$child->kd_akun])) {
                if ($child->operasi == '+') {
                    $totalTarget += $calculatedData[$child->kd_akun]['target'];
                    $totalRealisasi += $calculatedData[$child->kd_akun]['realisasi'];
                } elseif ($child->operasi == '-') {
                    $totalTarget -= $calculatedData[$child->kd_akun]['target'];
                    $totalRealisasi -= $calculatedData[$child->kd_akun]['realisasi'];
                }
            }
        }
        $calculatedData[$parentAccount->kd_akun] = [
            'target' => $totalTarget,
            'realisasi' => $totalRealisasi
        ];
    }

    //=============================================

    $summaryData = [];
    $totalPendapatanTarget = 0;
    $totalPendapatanRealisasi = 0;

    // Hitung total pendapatan dari file JSON (akun dengan kode 01)
    foreach ($sbsData as $item) {
        if (str_starts_with($item['kd_akun'], '01')) { // Akun pendapatan (kode 01)
            $totalPendapatanTarget += $item['nilai_target'];
            $totalPendapatanRealisasi += $item['nilai_realisasi'];
        }
    }

    // Data pendapatan harian
    $pendapatanHarian = [];
    $currentDate = new DateTime(substr($startDate, 0, 4) . '-' . substr($startDate, 4, 2) . '-' . substr($startDate, 6, 2));
    $endDateObj = new DateTime(substr($endDate, 0, 4) . '-' . substr($endDate, 4, 2) . '-' . substr($endDate, 6, 2));

    // Inisialisasi array untuk setiap hari
    while ($currentDate <= $endDateObj) {
        $dateKey = $currentDate->format('Y-m-d');
        $pendapatanHarian[$dateKey] = [
            'target' => 0,
            'realisasi' => 0,
            'tanggal' => $currentDate->format('d M Y')
        ];
        $currentDate->modify('+1 day');
    }

    // Isi data pendapatan harian dari JSON
    foreach ($sbsData as $item) {
        if (str_starts_with($item['kd_akun'], '01')) { // Akun pendapatan (kode 01)
            $itemDate = $item['tanggal'];
            if (isset($pendapatanHarian[$itemDate])) {
                $pendapatanHarian[$itemDate]['target'] += $item['nilai_target'];
                $pendapatanHarian[$itemDate]['realisasi'] += $item['nilai_realisasi'];
            }
        }
    }

    // Format data untuk summary (hanya pendapatan harian)
    foreach ($pendapatanHarian as $dateKey => $dataHarian) {
        $target = $dataHarian['target'];
        $realisasi = $dataHarian['realisasi'];
        $gap = $realisasi - $target;
        
        $percentageVsTarget = $target != 0 ? ($realisasi / $target) * 100 : 0;
        $percentageVsPendapatanTarget = $totalPendapatanTarget != 0 ? ($target / $totalPendapatanTarget) * 100 : 0;
        $percentageVsPendapatanRealisasi = $totalPendapatanRealisasi != 0 ? ($realisasi / $totalPendapatanRealisasi) * 100 : 0;
        
        $keterangan = $gap >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        
        $summaryData[] = [
            'nama_akun' => $dataHarian['tanggal'],
            'target' => $target,
            'realisasi' => $realisasi,
            'gap' => $gap,
            'percentage_vs_target' => $percentageVsTarget,
            'percentage_vs_pendapatan_target' => $percentageVsPendapatanTarget,
            'percentage_vs_pendapatan_realisasi' => $percentageVsPendapatanRealisasi,
            'keterangan' => $keterangan,
            'is_harian' => true,
            'tanggal' => $dateKey
        ];
    }

    // Tambahkan total pendapatan bulanan
    $gapTotal = $totalPendapatanRealisasi - $totalPendapatanTarget;
    $percentageVsTargetTotal = $totalPendapatanTarget != 0 ? ($totalPendapatanRealisasi / $totalPendapatanTarget) * 100 : 0;
    $keteranganTotal = $gapTotal >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';

    $summaryData[] = [
        'nama_akun' => '<strong>TOTAL PENDAPATAN BULANAN</strong>',
        'target' => $totalPendapatanTarget,
        'realisasi' => $totalPendapatanRealisasi,
        'gap' => $gapTotal,
        'percentage_vs_target' => $percentageVsTargetTotal,
        'percentage_vs_pendapatan_target' => 100, // 100% karena ini total
        'percentage_vs_pendapatan_realisasi' => 100, // 100% karena ini total
        'keterangan' => $keteranganTotal,
        'is_total' => true
    ];

    // Juga simpan data total untuk digunakan di view
    $summaryTotal = [
        'target' => $totalPendapatanTarget,
        'realisasi' => $totalPendapatanRealisasi,
        'gap' => $gapTotal
    ];
    //=============================================
    
    $calculatedData = [];
    foreach ($sbsData as $item) {
        $kdAkun = $item['kd_akun'];
        if (!isset($calculatedData[$kdAkun])) {
            $calculatedData[$kdAkun] = ['target' => 0, 'realisasi' => 0];
        }
        $calculatedData[$kdAkun]['target'] += $item['nilai_target'];
        $calculatedData[$kdAkun]['realisasi'] += $item['nilai_realisasi'];
    }

    $accounts = Akun::orderBy('urutan', 'asc')->get();
    
    // Hitung nilai untuk setiap akun induk dengan memperhitungkan anak-anaknya
    $parentAccounts = $accounts->whereNull('induk');
    foreach ($parentAccounts as $parentAccount) {
        $children = $accounts->where('induk', $parentAccount->kd_akun);
        $totalTarget = isset($calculatedData[$parentAccount->kd_akun]) ? $calculatedData[$parentAccount->kd_akun]['target'] : 0;
        $totalRealisasi = isset($calculatedData[$parentAccount->kd_akun]) ? $calculatedData[$parentAccount->kd_akun]['realisasi'] : 0;
        
        foreach ($children as $child) {
            if (isset($calculatedData[$child->kd_akun])) {
                if ($child->operasi == '+') {
                    $totalTarget += $calculatedData[$child->kd_akun]['target'];
                    $totalRealisasi += $calculatedData[$child->kd_akun]['realisasi'];
                } elseif ($child->operasi == '-') {
                    $totalTarget -= $calculatedData[$child->kd_akun]['target'];
                    $totalRealisasi -= $calculatedData[$child->kd_akun]['realisasi'];
                }
            }
        }
        
        // Simpan nilai yang sudah dihitung (mengganti nilai sebelumnya)
        $calculatedData[$parentAccount->kd_akun] = [
            'target' => $totalTarget,
            'realisasi' => $totalRealisasi
        ];
    }

    // HITUNG TOTAL PENDAPATAN untuk persentase
    $totalPendapatanTarget = 0;
    $totalPendapatanRealisasi = 0;
    
    foreach ($parentAccounts as $account) {
        if (str_starts_with($account->kd_akun, '01')) { // Akun pendapatan
            $totalPendapatanTarget += $calculatedData[$account->kd_akun]['target'] ?? 0;
            $totalPendapatanRealisasi += $calculatedData[$account->kd_akun]['realisasi'] ?? 0;
        }
    }
    
    Log::info("TOTAL PENDAPATAN TARGET: " . number_format($totalPendapatanTarget));
    Log::info("TOTAL PENDAPATAN REALISASI: " . number_format($totalPendapatanRealisasi));

    // TAMPILKAN SEMUA AKUN (INDUK DAN ANAK) DENGAN PERSENTASE
    $labaKotorTarget = 0;
    $labaKotorRealisasi = 0;
    $labaKotorInserted = false;
    
    $reportData = [];
    
    // Urutkan akun berdasarkan urutan
    $sortedAccounts = $accounts->sortBy('urutan');
    
    foreach ($sortedAccounts as $account) {
        $kdAkun = $account->kd_akun;
        $isInduk = is_null($account->induk);
        
        $target = $calculatedData[$kdAkun]['target'] ?? 0;
        $realisasi = $calculatedData[$kdAkun]['realisasi'] ?? 0;
        
        // Hitung persentase terhadap total pendapatan
        $persenTarget = $totalPendapatanTarget != 0 ? ($target / $totalPendapatanTarget) * 100 : 0;
        $persenRealisasi = $totalPendapatanRealisasi != 0 ? ($realisasi / $totalPendapatanRealisasi) * 100 : 0;
        
        // Cek apakah ini akun biaya/beban/ziswaf
        $isBiaya = str_contains(strtolower($account->nama_akun), 'biaya') || 
                   str_contains(strtolower($account->nama_akun), 'beban');
        
        $isZiswaf = str_contains(strtolower($account->nama_akun), 'ziswaf') ||
                    str_contains(strtolower($account->nama_akun), 'zakat') ||
                    str_contains(strtolower($account->nama_akun), 'infaq') ||
                    str_contains(strtolower($account->nama_akun), 'sedekah') ||
                    str_contains(strtolower($account->nama_akun), 'waqaf');
        
        $isBiayaAtauZiswaf = $isBiaya || $isZiswaf;
        
        // Untuk akun induk, format nama menjadi bold
        $namaAkun = $isInduk ? '<strong>' . $account->nama_akun . '</strong>' : $account->nama_akun;
        
        // Tentukan level indentasi
        $level = $isInduk ? 1 : 2;
        
        // Tambahkan Laba Kotor sebelum akun biaya/beban/ziswaf pertama (hanya untuk induk)
        if ($isBiayaAtauZiswaf && $isInduk && !$labaKotorInserted) {
            // Hitung persentase Laba Kotor
            $persenLabaKotorTarget = $totalPendapatanTarget != 0 ? ($labaKotorTarget / $totalPendapatanTarget) * 100 : 0;
            $persenLabaKotorRealisasi = $totalPendapatanRealisasi != 0 ? ($labaKotorRealisasi / $totalPendapatanRealisasi) * 100 : 0;
            
            $reportData[] = [
                'nama_akun' => '<strong>Laba Kotor</strong>',
                'target' => $labaKotorTarget,
                'realisasi' => $labaKotorRealisasi,
                'gap' => $labaKotorRealisasi - $labaKotorTarget,
                'percentage' => $labaKotorTarget != 0 ? ($labaKotorRealisasi / $labaKotorTarget) * 100 : 0,
                'keterangan' => ($labaKotorRealisasi - $labaKotorTarget) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
                'level' => 1,
                'is_induk' => true,
                'persen_target' => $persenLabaKotorTarget,
                'persen_realisasi' => $persenLabaKotorRealisasi
            ];
            $labaKotorInserted = true;
            
            Log::info("LABA KOTOR DITAMBAHKAN: Target = " . number_format($labaKotorTarget) . " (" . round($persenLabaKotorTarget, 2) . "%), Realisasi = " . number_format($labaKotorRealisasi) . " (" . round($persenLabaKotorRealisasi, 2) . "%)");
        }
        
        // Tambahkan akun ke report data
        $reportData[] = [
            'nama_akun' => $namaAkun,
            'target' => $target,
            'realisasi' => $realisasi,
            'gap' => $realisasi - $target,
            'percentage' => $target != 0 ? ($realisasi / $target) * 100 : 0,
            'keterangan' => ($realisasi - $target) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
            'level' => $level,
            'is_induk' => $isInduk,
            'kd_akun' => $kdAkun,
            'persen_target' => $persenTarget,
            'persen_realisasi' => $persenRealisasi
        ];
        
        // Akumulasi untuk Laba Kotor (hanya akun induk non-biaya sebelum biaya/beban/ziswaf pertama)
        if (!$isBiayaAtauZiswaf && !$labaKotorInserted && $isInduk) {
            if ($account->operasi == '+') {
                $labaKotorTarget += $target;
                $labaKotorRealisasi += $realisasi;
                Log::debug("AKUN PENDAPATAN: {$account->nama_akun} (+) Target: " . number_format($target) . ", Realisasi: " . number_format($realisasi));
            } elseif ($account->operasi == '-') {
                $labaKotorTarget -= $target;
                $labaKotorRealisasi -= $realisasi;
                Log::debug("AKUN BEBAN: {$account->nama_akun} (-) Target: " . number_format($target) . ", Realisasi: " . number_format($realisasi));
            }
            Log::debug("LABA KOTOR SEMENTARA: Target = " . number_format($labaKotorTarget) . ", Realisasi = " . number_format($labaKotorRealisasi));
        }
    }
    
    // Jika tidak ada akun biaya/beban/ziswaf, tetap tambahkan Laba Kotor di akhir
    if (!$labaKotorInserted) {
        // Hitung persentase Laba Kotor
        $persenLabaKotorTarget = $totalPendapatanTarget != 0 ? ($labaKotorTarget / $totalPendapatanTarget) * 100 : 0;
        $persenLabaKotorRealisasi = $totalPendapatanRealisasi != 0 ? ($labaKotorRealisasi / $totalPendapatanRealisasi) * 100 : 0;
        
        $reportData[] = [
            'nama_akun' => '<strong>Laba Kotor</strong>',
            'target' => $labaKotorTarget,
            'realisasi' => $labaKotorRealisasi,
            'gap' => $labaKotorRealisasi - $labaKotorTarget,
            'percentage' => $labaKotorTarget != 0 ? ($labaKotorRealisasi / $labaKotorTarget) * 100 : 0,
            'keterangan' => ($labaKotorRealisasi - $labaKotorTarget) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
            'level' => 1,
            'is_induk' => true,
            'persen_target' => $persenLabaKotorTarget,
            'persen_realisasi' => $persenLabaKotorRealisasi
        ];
        
        Log::info("LABA KOTOR DITAMBAHKAN (AKHIR): Target = " . number_format($labaKotorTarget) . " (" . round($persenLabaKotorTarget, 2) . "%), Realisasi = " . number_format($labaKotorRealisasi) . " (" . round($persenLabaKotorRealisasi, 2) . "%)");
    }

    // ================= PERHITUNGAN NPM DENGAN QUERY SQL LANGSUNG =================
    
    // **PERBAIKAN: Hitung NPM Total dengan query SQL langsung**
    $npmTotalQuery = "
        SELECT 
            'TOTAL SEMUA' AS keterangan,
            SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS total_target,
            SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) AS total_realisasi,
            SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) - 
            SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS selisih
        FROM target t
        LEFT JOIN realisasi r ON (t.kd_target = r.kd_target) 
        LEFT JOIN akun a ON (t.kd_akun = a.kd_akun) 
        LEFT JOIN entitas e ON (t.kd_entitas = e.kd_entitas)
        WHERE t.tanggal BETWEEN ? AND ?
    ";

    $npmTotal = DB::select($npmTotalQuery, [$laporan->tanggal_awal, $laporan->tanggal_akhir]);
    $npmTotal = $npmTotal[0] ?? null;

    Log::info("=== PERHITUNGAN NPM TOTAL DENGAN QUERY SQL ===");
    Log::info("NPM Total - Target: " . number_format($npmTotal->total_target ?? 0));
    Log::info("NPM Total - Realisasi: " . number_format($npmTotal->total_realisasi ?? 0));
    Log::info("NPM Total - Selisih: " . number_format($npmTotal->selisih ?? 0));

    // **PERBAIKAN: Hitung NPM Per Bulan dengan query SQL langsung**
    $npmBulananQuery = "
        SELECT 
            MONTH(t.tanggal) as bulan,  -- Gunakan fungsi MONTH untuk ekstrak bulan dari tanggal
            YEAR(t.tanggal) as tahun,
            SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS total_target,
            SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) AS total_realisasi,
            SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) - 
            SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS selisih
        FROM target t
        LEFT JOIN realisasi r ON (t.kd_target = r.kd_target) 
        LEFT JOIN akun a ON (t.kd_akun = a.kd_akun) 
        LEFT JOIN entitas e ON (t.kd_entitas = e.kd_entitas)
        WHERE t.tanggal BETWEEN ? AND ?
        GROUP BY YEAR(t.tanggal), MONTH(t.tanggal)
        ORDER BY YEAR(t.tanggal), MONTH(t.tanggal)
    ";
    
    $npmBulanan = DB::select($npmBulananQuery, [$laporan->tanggal_awal, $laporan->tanggal_akhir]);
    
    // Format data NPM Bulanan untuk chart
    $npmChartData = [];
    foreach ($npmBulanan as $data) {
        // **PERBAIKAN: Pastikan $data->bulan adalah integer**
        $bulanInt = (int)$data->bulan; // Konversi ke integer
        
        // Validasi range bulan (1-12)
        if ($bulanInt >= 1 && $bulanInt <= 12) {
            $monthName = $monthNames[$bulanInt - 1];
        } else {
            $monthName = "Bulan {$bulanInt}";
        }
        
        $npmChartData[$monthName] = [
            'target' => $data->total_target ?? 0,
            'realisasi' => $data->total_realisasi ?? 0
        ];
        
        Log::info("NPM Bulan {$monthName}: Target=" . number_format($data->total_target ?? 0) . ", Realisasi=" . number_format($data->total_realisasi ?? 0));
    }

    // **PERBAIKAN: Hitung NPM Per Entitas dengan query SQL langsung**
    $npmEntitasQuery = "
        SELECT 
            t.kd_entitas,
            e.nama_entitas,
            SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS total_target,
            SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) AS total_realisasi,
            SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) - 
            SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS selisih
        FROM target t
        LEFT JOIN realisasi r ON (t.kd_target = r.kd_target) 
        LEFT JOIN akun a ON (t.kd_akun = a.kd_akun) 
        LEFT JOIN entitas e ON (t.kd_entitas = e.kd_entitas)
        WHERE t.tanggal BETWEEN ? AND ?
        GROUP BY t.kd_entitas, e.nama_entitas
        ORDER BY t.kd_entitas
    ";

    $npmEntitasRaw = DB::select($npmEntitasQuery, [$laporan->tanggal_awal, $laporan->tanggal_akhir]);

    // Format data NPM Per Entitas
    $npmEntitasTableData = [];
    foreach ($npmEntitasRaw as $data) {
        $entityName = $data->nama_entitas ?? 'Lain-lain';
        $target = $data->total_target ?? 0;
        $realisasi = $data->total_realisasi ?? 0;
        $gap = $data->selisih ?? 0;
        
        $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
        $keterangan = $gap >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';

        $npmEntitasTableData[$entityName] = [
            'target' => $target,
            'realisasi' => $realisasi,
            'gap' => $gap,
            'percentage' => $percentage,
            'keterangan' => $keterangan
        ];
        
        Log::info("NPM Entitas {$entityName}: Target=" . number_format($target) . ", Realisasi=" . number_format($realisasi));
    }

    // **PERBAIKAN: Update NPM dalam reportData dengan nilai dari query SQL**
    $npmUpdated = false;
    foreach ($reportData as &$row) {
        $cleanNama = strip_tags($row['nama_akun']);
        $cleanNamaLower = strtolower($cleanNama);
        
        if (str_contains($cleanNamaLower, 'net profit') || 
            str_contains($cleanNamaLower, 'npm') || 
            str_contains($cleanNamaLower, 'laba bersih')) {
            
            // Gunakan nilai dari query SQL langsung
            $row['target'] = $npmTotal->total_target ?? 0;
            $row['realisasi'] = $npmTotal->total_realisasi ?? 0;
            $row['gap'] = $npmTotal->selisih ?? 0;
            $row['percentage'] = ($npmTotal->total_target ?? 0) != 0 ? 
                (($npmTotal->total_realisasi ?? 0) / ($npmTotal->total_target ?? 0)) * 100 : 0;
            $row['keterangan'] = ($npmTotal->selisih ?? 0) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
            
            // Update persentase terhadap total pendapatan
            $row['persen_target'] = $totalPendapatanTarget != 0 ? 
                (($npmTotal->total_target ?? 0) / $totalPendapatanTarget) * 100 : 0;
            $row['persen_realisasi'] = $totalPendapatanRealisasi != 0 ? 
                (($npmTotal->total_realisasi ?? 0) / $totalPendapatanRealisasi) * 100 : 0;
            
            $npmUpdated = true;
            
            Log::info("NPM DIUPDATE DENGAN QUERY SQL: Target=" . number_format($npmTotal->total_target ?? 0) . 
                     ", Realisasi=" . number_format($npmTotal->total_realisasi ?? 0));
            break;
        }
    }
    unset($row);

    // **PERBAIKAN: Jika NPM tidak ditemukan, tambahkan dengan data dari query SQL**
    if (!$npmUpdated && $npmTotal) {
        $persenNpmTarget = $totalPendapatanTarget != 0 ? 
            (($npmTotal->total_target ?? 0) / $totalPendapatanTarget) * 100 : 0;
        $persenNpmRealisasi = $totalPendapatanRealisasi != 0 ? 
            (($npmTotal->total_realisasi ?? 0) / $totalPendapatanRealisasi) * 100 : 0;

        $reportData[] = [
            'nama_akun' => '<strong>Net Profit Margin</strong>',
            'target' => $npmTotal->total_target ?? 0,
            'realisasi' => $npmTotal->total_realisasi ?? 0,
            'gap' => $npmTotal->selisih ?? 0,
            'percentage' => ($npmTotal->total_target ?? 0) != 0 ? 
                (($npmTotal->total_realisasi ?? 0) / ($npmTotal->total_target ?? 0)) * 100 : 0,
            'keterangan' => ($npmTotal->selisih ?? 0) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
            'level' => 1,
            'is_induk' => true,
            'persen_target' => $persenNpmTarget,
            'persen_realisasi' => $persenNpmRealisasi
        ];
        
        Log::info("NPM DITAMBAHKAN DENGAN QUERY SQL: Target=" . number_format($npmTotal->total_target ?? 0) . 
                 ", Realisasi=" . number_format($npmTotal->total_realisasi ?? 0));
    }

    // **PERBAIKAN: Validasi akhir NPM**
    Log::info("=== VALIDASI FINAL NPM DENGAN QUERY SQL ===");
    foreach ($reportData as $index => $item) {
        if (str_contains(strtolower(strip_tags($item['nama_akun'])), 'net profit')) {
            Log::info("NPM FINAL:");
            Log::info("  Target: " . number_format($item['target']));
            Log::info("  Realisasi: " . number_format($item['realisasi']));
            Log::info("  Gap: " . number_format($item['gap']));
            Log::info("  Persentase: " . round($item['percentage'], 2) . "%");
            Log::info("  Keterangan: " . $item['keterangan']);
            break;
        }
    }

    // --- Bagian baru untuk data grafik produk terjual ---
    $previousMonth = $endMonth - 1;
    $previousYear = $endYear;
    if ($previousMonth < 1) {
        $previousMonth = 12;
        $previousYear = $endYear - 1;
    }

    $monthlyProductSales = DB::table('realisasi')
        ->select(
            'entitas.nama_entitas',
            DB::raw('MONTH(realisasi.tanggal) as month'),
            DB::raw('SUM(realisasi.nilai) as total_sales')
        )
        ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
        ->where('target.kd_akun', 'like', '01%')
        ->whereYear('realisasi.tanggal', '>=', $previousYear)
        ->whereYear('realisasi.tanggal', '<=', $endYear)
        ->where(function($query) use ($endYear, $endMonth, $previousYear, $previousMonth) {
            $query->where(function($q) use ($endYear, $endMonth) {
                $q->whereYear('realisasi.tanggal', $endYear)
                ->whereMonth('realisasi.tanggal', $endMonth);
            })->orWhere(function($q) use ($previousYear, $previousMonth) {
                $q->whereYear('realisasi.tanggal', $previousYear)
                ->whereMonth('realisasi.tanggal', $previousMonth);
            });
        })
        ->groupBy('entitas.nama_entitas', 'month')
        ->get()
        ->groupBy('nama_entitas')
        ->map(function ($item) {
            return $item->keyBy('month');
        });
            
    $reportDataAnalysis = null;
    
    // --- Bagian untuk data grafik pie chart dan tabel produk terlaris ---
    $topProductsData = DB::table('penjualan')
        ->select('produk.nama_produk', DB::raw('SUM(penjualan.jumlah) as total_items'))
        ->join('produk', 'penjualan.kd_produk', '=', 'produk.kd_produk')
        ->where('penjualan.tanggal', '>=', $startDate)
        ->where('penjualan.tanggal', '<=', $endDate)
        ->groupBy('produk.nama_produk')
        ->orderByDesc('total_items')
        ->limit(10)
        ->get();
        
    $topProductsSalesData = DB::table('penjualan')
        ->select(
            'produk.nama_produk', 
            DB::raw('SUM(penjualan.jumlah) as total_items'), 
            DB::raw('SUM(penjualan.jumlah * penjualan.harga) as total_sales')
        )
        ->join('produk', 'penjualan.kd_produk', '=', 'produk.kd_produk')
        ->where('penjualan.tanggal', '>=', $startDate)
        ->where('penjualan.tanggal', '<=', $endDate)
        ->groupBy('produk.nama_produk')
        ->orderByDesc('total_items')
        ->limit(10)
        ->get();
        
    // --- Bagian untuk data grafik beban gaji per entitas ---
    $salaryData = [];
    $gajiAccounts = $accounts->filter(function($value) {
        return str_contains(strtolower($value->nama_akun), 'gaji');
    });

    foreach ($gajiAccounts as $gajiAccount) {
        foreach ($sbsData as $item) {
            if ($item['kd_akun'] == $gajiAccount->kd_akun) {
                $entity = $item['nama_entitas'] ?? 'Lain-lain';
                if (!isset($salaryData[$entity])) {
                    $salaryData[$entity] = [
                        'target' => 0,
                        'realisasi' => 0,
                    ];
                }
                $salaryData[$entity]['target'] += $item['nilai_target'];
                $salaryData[$entity]['realisasi'] += $item['nilai_realisasi'];
            }
        }
    }

    // Menghitung gap dan keterangan untuk data gaji per entitas
    $salaryTableData = [];
    foreach ($salaryData as $entityName => $data) {
        $target = $data['target'];
        $realisasi = $data['realisasi'];
        $gap = $realisasi - $target;
        $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
        $keterangan = $gap >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';

        $salaryTableData[] = [
            'entitas' => $entityName,
            'target' => $target,
            'realisasi' => $realisasi,
            'gap' => $gap,
            'percentage' => $percentage,
            'keterangan' => $keterangan,
        ];
    }
    
    // --- Bagian untuk data grafik beban gaji bulanan ---
    $monthlySalaryData = DB::table('target')
        ->select(
            DB::raw('MONTH(target.tanggal) as month'),
            DB::raw('SUM(target.nilai) as total_target')
        )
        ->join('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->whereYear('target.tanggal', $endYear)
        ->whereMonth('target.tanggal', '<=', $endMonth)
        ->where('akun.nama_akun', 'like', '%gaji%')
        ->groupBy('month')
        ->orderBy('month')
        ->get();
        
    $monthlySalaryRealisasi = DB::table('realisasi')
        ->select(
            DB::raw('MONTH(realisasi.tanggal) as month'),
            DB::raw('SUM(realisasi.nilai) as total_realisasi')
        )
        ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->join('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->whereYear('realisasi.tanggal', $endYear)
        ->whereMonth('realisasi.tanggal', '<=', $endMonth)
        ->where('akun.nama_akun', 'like', '%gaji%')
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    
    // Menggabungkan data target dan realisasi
    $monthlySalaryChartData = [];
    $tempTarget = $monthlySalaryData->keyBy('month');
    $tempRealisasi = $monthlySalaryRealisasi->keyBy('month');

    for ($i = 1; $i <= $endMonth; $i++) {
        $monthName = $monthNames[$i - 1];
        $targetValue = $tempTarget->get($i)->total_target ?? 0;
        $realisasiValue = $tempRealisasi->get($i)->total_realisasi ?? 0;
        
        $monthlySalaryChartData[$monthName] = [
            'target' => $targetValue,
            'realisasi' => $realisasiValue
        ];
    }
    
    // --- Bagian baru untuk data grafik biaya operasional per entitas ---
    $operationalCostData = [];

    foreach ($sbsData as $item) {
        if (isset($item['kd_akun']) && str_starts_with($item['kd_akun'], '05')) {
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            if (!isset($operationalCostData[$entity])) {
                $operationalCostData[$entity] = [
                    'target' => 0,
                    'realisasi' => 0,
                ];
            }
            $operationalCostData[$entity]['target'] += $item['nilai_target'];
            $operationalCostData[$entity]['realisasi'] += $item['nilai_realisasi'];
        }
    }
    
    // --- Bagian baru: Menambahkan perhitungan gap, persen gap, dan keterangan untuk biaya operasional ---
    foreach ($operationalCostData as $entity => $data) {
        $target = $data['target'];
        $realisasi = $data['realisasi'];
        $gap = $realisasi - $target;
        $percentage = $target != 0 ? (($realisasi - $target) / $target) * 100 : 0;
        $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        
        $operationalCostData[$entity]['gap'] = $gap;
        $operationalCostData[$entity]['percentage'] = $percentage;
        $operationalCostData[$entity]['keterangan'] = $keterangan;
    }

    // --- Data grafik biaya operasional bulanan ---
    $monthlyOperationalCostTarget = DB::table('target')
        ->select(
            DB::raw('MONTH(target.tanggal) as month'),
            DB::raw('SUM(target.nilai) as total_target')
        )
        ->join('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->whereYear('target.tanggal', $endYear)
        ->whereMonth('target.tanggal', '<=', $endMonth)
        ->where('akun.kd_akun', 'like', '05%')
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    $monthlyOperationalCostRealisasi = DB::table('realisasi')
        ->select(
            DB::raw('MONTH(realisasi.tanggal) as month'),
            DB::raw('SUM(realisasi.nilai) as total_realisasi')
        )
        ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->join('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->whereYear('realisasi.tanggal', $endYear)
        ->whereMonth('realisasi.tanggal', '<=', $endMonth)
        ->where('akun.kd_akun', 'like', '05%')
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    
    $monthlyOperationalCostChartData = [];
    $tempOperationalCostTarget = $monthlyOperationalCostTarget->keyBy('month');
    $tempOperationalCostRealisasi = $monthlyOperationalCostRealisasi->keyBy('month');

    for ($i = 1; $i <= $endMonth; $i++) {
        $monthName = $monthNames[$i - 1];
        $targetValue = $tempOperationalCostTarget->get($i)->total_target ?? 0;
        $realisasiValue = $tempOperationalCostRealisasi->get($i)->total_realisasi ?? 0;
        
        $monthlyOperationalCostChartData[$monthName] = [
            'target' => $targetValue,
            'realisasi' => $realisasiValue
        ];
    }

    // --- Bagian baru untuk data grafik biaya pemasaran per entitas ---
    $marketingCostData = [];

    foreach ($sbsData as $item) {
        if (isset($item['kd_akun']) && str_starts_with($item['kd_akun'], '06')) {
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            if (!isset($marketingCostData[$entity])) {
                $marketingCostData[$entity] = [
                    'target' => 0,
                    'realisasi' => 0,
                ];
            }
            $marketingCostData[$entity]['target'] += $item['nilai_target'];
            $marketingCostData[$entity]['realisasi'] += $item['nilai_realisasi'];
        }
    }

    foreach ($marketingCostData as $entity => $data) {
        $target = $data['target'];
        $realisasi = $data['realisasi'];
        $gap = $realisasi - $target;
        $percentage = $target != 0 ? (($realisasi - $target) / $target) * 100 : 0;
        $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        
        $marketingCostData[$entity]['gap'] = $gap;
        $marketingCostData[$entity]['percentage'] = $percentage;
        $marketingCostData[$entity]['keterangan'] = $keterangan;
    }

    // --- Bagian baru untuk data grafik target vs realisasi bulanan (keseluruhan) ---
    $monthlyOverallTarget = DB::table('target')
        ->select(
            DB::raw('MONTH(tanggal) as month'),
            DB::raw('SUM(nilai) as total_target')
        )
        ->whereYear('tanggal', $endYear)
        ->whereMonth('tanggal', '<=', $endMonth)
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    
    $monthlyOverallRealisasi = DB::table('realisasi')
        ->select(
            DB::raw('MONTH(realisasi.tanggal) as month'),
            DB::raw('SUM(realisasi.nilai) as total_realisasi')
        )
        ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->whereYear('realisasi.tanggal', $endYear)
        ->whereMonth('realisasi.tanggal', '<=', $endMonth)
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    
    $monthlyOverallChartData = [];
    $tempTarget = $monthlyOverallTarget->keyBy('month');
    $tempRealisasi = $monthlyOverallRealisasi->keyBy('month');
    
    for ($i = 1; $i <= $endMonth; $i++) {
        $monthName = $monthNames[$i - 1];
        $targetValue = $tempTarget->get($i)->total_target ?? 0;
        $realisasiValue = $tempRealisasi->get($i)->total_realisasi ?? 0;
        
        $monthlyOverallChartData[$monthName] = [
            'target' => $targetValue,
            'realisasi' => $realisasiValue
        ];
    }

    // --- Bagian baru untuk data grafik biaya lain-lain bulanan ---
    $monthlyOtherCostTarget = DB::table('target')
        ->select(
            DB::raw('MONTH(target.tanggal) as month'),
            DB::raw('SUM(target.nilai) as total_target')
        )
        ->join('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->whereYear('target.tanggal', $endYear)
        ->whereMonth('target.tanggal', '<=', $endMonth)
        ->where('akun.kd_akun', 'like', '07%')
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    $monthlyOtherCostRealisasi = DB::table('realisasi')
        ->select(
            DB::raw('MONTH(realisasi.tanggal) as month'),
            DB::raw('SUM(realisasi.nilai) as total_realisasi')
        )
        ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
        ->join('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        ->whereYear('realisasi.tanggal', $endYear)
        ->whereMonth('realisasi.tanggal', '<=', $endMonth)
        ->where('akun.kd_akun', 'like', '07%')
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    
    $monthlyOtherCostChartData = [];
    $tempOtherCostTarget = $monthlyOtherCostTarget->keyBy('month');
    $tempOtherCostRealisasi = $monthlyOtherCostRealisasi->keyBy('month');

    for ($i = 1; $i <= $endMonth; $i++) {
        $monthName = $monthNames[$i - 1];
        $targetValue = $tempOtherCostTarget->get($i)->total_target ?? 0;
        $realisasiValue = $tempOtherCostRealisasi->get($i)->total_realisasi ?? 0;
        
        $monthlyOtherCostChartData[$monthName] = [
            'target' => $targetValue,
            'realisasi' => $realisasiValue
        ];
    }

    $otherCostData = [];
    foreach ($sbsData as $item) {
        if (isset($item['kd_akun']) && str_starts_with($item['kd_akun'], '07')) {
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            if (!isset($otherCostData[$entity])) {
                $otherCostData[$entity] = [
                    'target' => 0,
                    'realisasi' => 0
                ];
            }
            $otherCostData[$entity]['target'] += $item['nilai_target'];
            $otherCostData[$entity]['realisasi'] += $item['nilai_realisasi'];
        }
    }

    // Kemudian buat otherCostTableData dari otherCostData:
    $otherCostTableData = [];
    foreach ($otherCostData as $entityName => $data) {
        $target = $data['target'];
        $realisasi = $data['realisasi'];
        $gap = $realisasi - $target;
        $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
        $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';

        $otherCostTableData[$entityName] = [
            'target' => $target,
            'realisasi' => $realisasi,
            'gap' => $gap,
            'percentage' => $percentage,
            'keterangan' => $keterangan,
        ];
    }

    // --- Bagian baru untuk mengakses data dari tabel terkait ---

    // 1. Mengakses data TUGAS berdasarkan periode laporan
    $tugas = DB::table('tugas')
        ->whereBetween('tanggal', [$laporan->tanggal_awal, $laporan->tanggal_akhir])
        ->orderBy('tanggal', 'asc')
        ->get()
        ->toArray();

    // 2. Mengakses data ISSUE berdasarkan periode laporan
    $issue = DB::table('issue')
        ->whereBetween('tanggal', [$laporan->tanggal_awal, $laporan->tanggal_akhir])
        ->orderBy('tanggal', 'desc')
        ->get()
        ->toArray();

    // 3. Mengakses data KOMPLAIN berdasarkan periode laporan
    $komplain = DB::table('komplain')
        ->whereBetween('tanggal', [$laporan->tanggal_awal, $laporan->tanggal_akhir])
        ->orderBy('tanggal', 'desc')
        ->get()
        ->toArray();

    // 4. Mengakses data ANALISIS berdasarkan periode laporan (jika ada tabel analisis)
    $analisis = DB::table('analisis')
        ->where('kd_laporan', $laporan->kd_laporan)
        ->orderBy('created_at', 'desc')
        ->get()
        ->toArray();

    // Jika tabel analisis tidak ada, gunakan data dari hasil_analisis
    if (empty($analisis)) {
        $analisis = DB::table('hasil_analisis')
            ->where('kd_laporan', $kd_laporan)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    // Simpan data ke dalam variabel array dengan nama yang diminta
    $tugasArray = $tugas;
    $issueArray = $issue;
    $komplainArray = $komplain;
    $analisisArray = $analisis;

    // Pre-fetch semua judul grafik sekaligus
    $grafikTitles = DB::table('grafik')
        ->whereIn('kd_grafik', range(1, 29))
        ->pluck('judul_grafik', 'kd_grafik');

    $analisisData = [
        [
            'kd_grafik' => 1,
            'title' => $grafikTitles[1] ?? 'Tidak Ditemukan',
            'data' => $reportData,
        ],
        [
            'kd_grafik' => 2,
            'title' => $grafikTitles[2] ?? 'Tidak Ditemukan',
            'data' => $monthlyDailyRealisasi,
        ],
        [
            'kd_grafik' => 3,
            'title' => $grafikTitles[3] ?? 'Tidak Ditemukan',
            'data' => $summaryData,
        ],
        [
            'kd_grafik' => 4,
            'title' => $grafikTitles[4] ?? 'Tidak Ditemukan',
            'data' => $monthlySummaryRealisasi,
        ],
        [
            'kd_grafik' => 5,
            'title' => $grafikTitles[5] ?? 'Tidak Ditemukan',
            'data' => $yearlyMonthlyRealisasi,
        ],
        [
            'kd_grafik' => 6,
            'title' => $grafikTitles[6] ?? 'Tidak Ditemukan',
            'data' => $entityDailyRealisasi,
        ],
        [
            'kd_grafik' => 7,
            'title' => $grafikTitles[7] ?? 'Tidak Ditemukan',
            'data' => $entitySummaryData,
        ],
        [
            'kd_grafik' => 9,
            'title' => $grafikTitles[9] ?? 'Tidak Ditemukan',
            'data' => $monthlyHargaPokokChartData,
        ],
        [
            'kd_grafik' => 10,
            'title' => $grafikTitles[10] ?? 'Tidak Ditemukan',
            'data' => $hargaPokokTableData,
        ],
        [
            'kd_grafik' => 12,
            'title' => $grafikTitles[12] ?? 'Tidak Ditemukan',
            'data' => $monthlyProductSales,
        ],
        [
            'kd_grafik' => 13,
            'title' => $grafikTitles[13] ?? 'Tidak Ditemukan',
            'data' => $topProductsSalesData,
        ],
        [
            'kd_grafik' => 14,
            'title' => $grafikTitles[14] ?? 'Tidak Ditemukan',
            'data' => $salaryData,
        ],
        [
            'kd_grafik' => 16,
            'title' => $grafikTitles[16] ?? 'Tidak Ditemukan',
            'data' => $monthlySalaryChartData,
        ],
        [
            'kd_grafik' => 17,
            'title' => $grafikTitles[17] ?? 'Tidak Ditemukan',
            'data' => $operationalCostData,
        ],
        [
            'kd_grafik' => 18,
            'title' => $grafikTitles[18] ?? 'Tidak Ditemukan',
            'data' => $monthlyOperationalCostChartData,
        ],
        [
            'kd_grafik' => 19,
            'title' => $grafikTitles[19] ?? 'Tidak Ditemukan',
            'data' => $marketingCostData,
        ],
        [
            'kd_grafik' => 20,
            'title' => $grafikTitles[20] ?? 'Tidak Ditemukan',
            'data' => $monthlyOverallChartData,
        ],
        [
            'kd_grafik' => 21,
            'title' => $grafikTitles[21] ?? 'Tidak Ditemukan',
            'data' => $monthlyOtherCostChartData,
        ],
        [
            'kd_grafik' => 22,
            'title' => $grafikTitles[22] ?? 'Tidak Ditemukan',
            'data' => $otherCostData,
        ],
        [
            'kd_grafik' => 24,
            'title' => $grafikTitles[24] ?? 'Tidak Ditemukan',
            'data' => $npmEntitasTableData,
        ],
        [
            'kd_grafik' => 25,
            'title' => $grafikTitles[25] ?? 'Tidak Ditemukan',
            'data' => $npmChartData,
        ],
        [
            'kd_grafik' => 26,
            'title' => $grafikTitles[26] ?? 'Tidak Ditemukan',
            'data' => $tugasArray,
        ],
        [
            'kd_grafik' => 27,
            'title' => $grafikTitles[27] ?? 'Tidak Ditemukan',
            'data' => $issueArray,
        ],
        [
            'kd_grafik' => 28,
            'title' => $grafikTitles[28] ?? 'Tidak Ditemukan',
            'data' => $komplainArray,
        ],
        [
            'kd_grafik' => 29,
            'title' => $grafikTitles[29] ?? 'Tidak Ditemukan',
            'data' => $analisisArray,
        ]                
    ];

    foreach ($analisisData as $data) {
        HasilAnalisis::updateOrCreate(
            [
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $data['kd_grafik'],
                'title' => $data['title']
            ],
            [
                'data' => $data['data'],
                'analisis' => null
            ]
        );
    }

    foreach ($analisisData as $data) {
        HasilAnalisis::updateOrCreate(
            [
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $data['kd_grafik'],
                'title'=>$data['title']
            ],
            [
                'data' => $data['data'],
                'analisis' => null
            ]
        );
    }

    return redirect()->route('laporan.view')->with('success','Data Laporan Telah tersimpan');
}

    public function showLaporanBulanan($kd_laporan)
    {
        try {
            Log::info("=== MEMULAI showLaporanBulanan ===");
            Log::info("Parameter kd_laporan: " . $kd_laporan);
            
            // Ambil data laporan dari database
            Log::debug("Mengambil data laporan dari database...");
            $laporan = DB::table('laporan')
                ->where('kd_laporan', $kd_laporan)
                ->first();
            
            if (!$laporan) {
                Log::warning("Laporan tidak ditemukan: " . $kd_laporan);
                return view('laporan_bulanan.view', [
                    'error' => 'Laporan dengan kode ' . $kd_laporan . ' tidak ditemukan.'
                ]);
            }
            
            Log::info("Laporan ditemukan: " . $laporan->kd_laporan);
            Log::info("Periode: " . $laporan->tanggal_awal . " hingga " . $laporan->tanggal_akhir);
            
            // Ambil semua data hasil analisis untuk laporan ini
            Log::debug("Mengambil data hasil_analisis dari database...");
            $hasilAnalisis = DB::table('hasil_analisis')
                ->where('kd_laporan', $kd_laporan)
                ->orderBy('created_at', 'asc')
                ->get();
            
            Log::info("Jumlah hasil analisis ditemukan: " . $hasilAnalisis->count());
            
            if ($hasilAnalisis->isEmpty()) {
                Log::warning("Tidak ada data hasil analisis untuk laporan: " . $kd_laporan);
                return view('laporan_bulanan.view', [
                    'error' => 'Tidak ada data hasil analisis untuk laporan ini.'
                ]);
            }
            
            // Inisialisasi semua variabel dengan nilai default array
            Log::debug("Menginisialisasi variabel...");
            $npmChartData = [];
            $reportData = [];
            $dailyRealisasi = [];
            $dailyTarget = [];
            $summaryData = [];
            $monthlyDailyRealisasi = [];
            $monthlySummaryRealisasi = [];
            $yearlyMonthlyRealisasi = [];
            $entityDailyRealisasi = [];
            $dateLabels = [];
            $entitySummaryData = [];
            $entityPieData = [];
            $hargaPokokSummaryData = [];
            $monthlyHargaPokokRealisasi = [];
            $hargaPokokTableData = [];
            $monthlyHargaPokokChartData = [];
            $monthlyProductSales = [];
            $topProductsData = [];
            $topProductsSalesData = [];
            $salaryData = [];
            $monthlySalaryChartData = [];
            $salaryTableData = [];
            $operationalCostData = [];
            $monthlyOperationalCostChartData = [];
            $marketingCostData = [];
            $monthlyOverallChartData = [];
            $monthlyOtherCostChartData = [];
            $otherCostData = [];
            $otherCostTableData = [];
            $npmEntitasTableData = [];
            $npmTargetValues = [];
            $npmRealisasiValues = [];
            $tugasArray = [];
            $issueArray = [];
            $komplainArray = [];
            $analisisArray = [];
            
            // Format tanggal untuk display
            Log::debug("Memformat tanggal untuk display...");
            $startDateDisplay = \Carbon\Carbon::parse($laporan->tanggal_awal)->format('d-m-Y');
            $endDateDisplay = \Carbon\Carbon::parse($laporan->tanggal_akhir)->format('d-m-Y');
            $periodeDisplay = $startDateDisplay . ' hingga ' . $endDateDisplay;
            
            // Variabel tambahan
            $endMonth = (int)date('m', strtotime($laporan->tanggal_akhir));
            $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $totalPendapatanTarget = 0;
            $totalPendapatanRealisasi = 0;
            
            Log::info("Memproses " . $hasilAnalisis->count() . " data hasil analisis...");
            
            // Proses setiap hasil analisis
            foreach ($hasilAnalisis as $index => $analisis) {
                Log::debug("=== MEMPROSES DATA ANALISIS [" . ($index + 1) . "/" . $hasilAnalisis->count() . "] ===");
                Log::debug("Title: " . $analisis->title);
                Log::debug("ID: " . $analisis->kd_hasil_analisis);
                
                $data = $analisis->data;
                $decodedData = [];
                
                // Log tipe data awal
                Log::debug("Tipe data awal: " . gettype($data));
                Log::debug("Nilai data (100 karakter pertama): " . substr(strval($data), 0, 100));
                
                try {
                    // PERBAIKAN: Fungsi helper untuk memastikan data selalu array
                    $decodedData = $this->ensureArrayData($data, $analisis->title);
                    
                    // Log hasil processing
                    Log::debug("Tipe data setelah processing: " . gettype($decodedData));
                    Log::debug("Jumlah elemen: " . (is_array($decodedData) ? count($decodedData) : 'N/A'));
                    
                    // PERBAIKAN: Pastikan decodedData selalu array sebelum assign
                    if (!is_array($decodedData)) {
                        Log::error("Data masih bukan array setelah processing untuk: " . $analisis->title);
                        $decodedData = [];
                    }
                    
                    // Assign data ke variabel yang sesuai berdasarkan title
                    Log::debug("Assign data ke variabel berdasarkan title...");
                    switch ($analisis->title) {
                        case 'Laporan Keuangan Target dan Realisasi':
                            $reportData = $decodedData;
                            Log::debug("reportData di-set, count: " . $reportData);
                            break;
                            
                        case 'Analisis Perbandingan Realisasi Omzet Antar Tahun':
                            $yearlyMonthlyRealisasi = $decodedData;
                            Log::debug("yearlyMonthlyRealisasi di-set, count: " . $yearlyMonthlyRealisasi);
                            break;
                            
                        case 'Analisis Pebandingan Target dan Realisasi Pendapatan':
                            $monthlyDailyRealisasi = $decodedData;
                            Log::debug("monthlyDailyRealisasi di-set, count: " . $monthlyDailyRealisasi);
                            break;
                            
                        case 'Laporan Pendapatan Harian':
                            $summaryData = $decodedData;
                            Log::debug("summaryData di-set, count: " . count($summaryData));
                            
                            // Hitung total pendapatan dari summary data
                            foreach ($decodedData as $item) {
                                if (isset($item['is_total']) && $item['is_total']) {
                                    $totalPendapatanTarget = $item['target'] ?? 0;
                                    $totalPendapatanRealisasi = $item['realisasi'] ?? 0;
                                    Log::debug("Total pendapatan - Target: " . $totalPendapatanTarget . ", Realisasi: " . $totalPendapatanRealisasi);
                                    break;
                                }
                            }
                            break;
                            
                        case 'Ringkasan Realisasi Bulanan':
                            $monthlySummaryRealisasi = $decodedData;
                            Log::debug("monthlySummaryRealisasi di-set, count: " . $monthlySummaryRealisasi);
                            break;
                            
                        case 'Perbandingan Realisasi Omzet Antar Tahun':
                            $yearlyMonthlyRealisasi = $decodedData;
                            Log::debug("yearlyMonthlyRealisasi di-set, count: " . $yearlyMonthlyRealisasi);
                            break;
                            
                        case 'Tren Realisasi Harian Antar Entitas':
                            $entityDailyRealisasi = $decodedData;
                            Log::debug("entityDailyRealisasi di-set, count: " . $entityDailyRealisasi);
                            break;
                            
                        case 'Perbandingan Target & Realisasi Pendapatan Antar Entitas':
                            $entitySummaryData = $decodedData;
                            Log::debug("entitySummaryData di-set, count: " . $entitySummaryData);
                            break;
                            
                        case 'Kontribusi Realisasi per Entitas':
                            $entityPieData = $decodedData;
                            Log::debug("entityPieData di-set, count: " . $entityPieData);
                            break;
                            
                        case 'Perbandingan Target dan Realisasi Harga Pokok Produksi per Entitas':
                            $hargaPokokSummaryData = $decodedData;
                            Log::debug("hargaPokokSummaryData di-set, count: " . $hargaPokokSummaryData);
                            break;
                            
                        case 'Detail Harga Pokok Produksi per Entitas':
                            $hargaPokokTableData = $decodedData;
                            Log::debug("hargaPokokTableData di-set, count: ". $hargaPokokTableData);
                            break;
                            
                        case 'Perbandingan Target dan Realisasi Harga Pokok Produksi per Bulan':
                            $monthlyHargaPokokChartData = $decodedData;
                            Log::debug("monthlyHargaPokokChartData di-set, count: " . $monthlyHargaPokokChartData);
                            break;
                            
                        case 'Perbandingan Produk Terjual per Entitas':
                            $monthlyProductSales = $decodedData;
                            Log::debug("monthlyProductSales di-set, count: " . $monthlyProductSales);
                            break;
                            
                        case 'Produk Terlaris':
                            $topProductsSalesData = $decodedData;
                            Log::debug("topProductsSalesData di-set, count: " . $topProductsSalesData);
                            break;
                            
                        case 'Beban Gaji per Entitas (Target vs Realisasi)':
                            $salaryData = $decodedData;
                            Log::debug("salaryData di-set, count: " . $salaryData);
                            break;
                            
                        case 'Perbandingan Beban Gaji per Entitas':
                            $salaryTableData = $decodedData;
                            Log::debug("salaryTableData di-set, count: " . $salaryTableData);
                            break;
                            
                        case 'Beban Gaji Bulanan (Target vs Realisasi)':
                            $monthlySalaryChartData = $decodedData;
                            Log::debug("monthlySalaryChartData di-set, count: " . $monthlySalaryChartData);
                            break;
                            
                        case 'Biaya Operasional per Entitas':
                            $operationalCostData = $decodedData;
                            Log::debug("operationalCostData di-set, count: " . $operationalCostData);
                            break;
                            
                        case 'Perbandingan Target dan Realisasi Biaya Operasional Per Bulan':
                            $monthlyOperationalCostChartData = $decodedData;
                            Log::debug("monthlyOperationalCostChartData di-set, count: " . $monthlyOperationalCostChartData);
                            break;
                            
                        case 'Perbandingan Beban Pemasaran per Entitas':
                            $marketingCostData = $decodedData;
                            Log::debug("marketingCostData di-set, count: " . $marketingCostData);
                            break;
                            
                        case 'Perbandingan Target dan Realisasi Bulanan':
                            $monthlyOverallChartData = $decodedData;
                            Log::debug("monthlyOverallChartData di-set, count: " . $monthlyOverallChartData);
                            break;
                            
                        case 'Beban Lain-lain Bulanan':
                            $monthlyOtherCostChartData = $decodedData;
                            Log::debug("monthlyOtherCostChartData di-set, count: " . $monthlyOtherCostChartData);
                            break;
                            
                        case 'Biaya Lain-lain per Entitas':
                            $otherCostData = $decodedData;
                            Log::debug("otherCostData di-set, count: " . $otherCostData);
                            break;
                            
                        case 'Tabel Biaya Lain-lain per Entitas':
                            $otherCostTableData = $decodedData;
                            Log::debug("otherCostTableData di-set, count: " . $otherCostTableData);
                            break;
                            
                        case 'Net Profit Margin per Entitas':
                            $npmEntitasTableData = $decodedData;
                            Log::debug("npmEntitasTableData di-set, count: " . $npmEntitasTableData);
                            break;
                            
                        case 'Grafik Target vs Realisasi NPM per Bulan':
                            $npmChartData = $decodedData;
                            Log::debug("npmChartData di-set, count: " . $npmChartData);
                            break;
                            
                        case 'Data Tugas':
                            $tugasArray = $decodedData;
                            Log::debug("tugasArray di-set, count: " . $tugasArray);
                            break;
                            
                        case 'Data Issue':
                            $issueArray = $decodedData;
                            Log::debug("issueArray di-set, count: " . $issueArray);
                            break;
                            
                        case 'Data Komplain':
                            $komplainArray = $decodedData;
                            Log::debug("komplainArray di-set, count: " . $komplainArray);
                            break;
                            
                        case 'Data Analisis':
                            $analisisArray = $decodedData;
                            Log::debug("analisisArray di-set, count: " . $analisisArray);
                            break;
                            
                        default:
                            Log::warning("Title tidak dikenali: " . $analisis->title);
                            break;
                    }
                    
                    Log::debug("Selesai memproses data analisis: " . $analisis->title);
                    
                } catch (\Exception $e) {
                    Log::error("ERROR saat memproses data analisis: " . $analisis->title);
                    Log::error("Pesan error: " . $e->getMessage());
                    Log::error("File: " . $e->getFile() . " Line: " . $e->getLine());
                    Log::error("Trace: " . $e->getTraceAsString());
                    
                    // Lanjutkan ke data berikutnya meskipun ada error
                    continue;
                }
            }
            
            Log::info("Selesai memproses semua data hasil analisis");
            
            // Untuk variabel yang perlu dihitung atau memiliki struktur khusus
            Log::debug("Membuat dateLabels berdasarkan periode laporan...");
            try {
                $startDateObj = new DateTime($laporan->tanggal_awal);
                $endDateObj = new DateTime($laporan->tanggal_akhir);
                $dateLabels = [];
                
                $currentDate = clone $startDateObj;
                while ($currentDate <= $endDateObj) {
                    $dateLabels[] = $currentDate->format('d-M-Y');
                    $currentDate->modify('+1 day');
                }
                
                Log::debug("dateLabels dibuat, count: " . count($dateLabels));
                
            } catch (\Exception $e) {
                Log::error("ERROR membuat dateLabels: " . $e->getMessage());
                $dateLabels = [];
            }
            
            // Buat data harian default jika tidak ada
            if (empty($dailyRealisasi) || !is_array($dailyRealisasi)) {
                Log::debug("Membuat default dailyRealisasi dan dailyTarget");
                $dailyRealisasi = array_fill(0, 31, 0);
                $dailyTarget = array_fill(0, 31, 0);
            }
            
            // Log informasi tentang variabel utama
            Log::info("=== INFORMASI VARIABEL UTAMA ===");
            Log::info("reportData count: " . $reportData);
            Log::info("summaryData count: " . $summaryData);
            Log::info("monthlyDailyRealisasi count: " . $monthlyDailyRealisasi);
            Log::info("yearlyMonthlyRealisasi count: " . $yearlyMonthlyRealisasi);
            Log::info("entitySummaryData count: " . $entitySummaryData);
            Log::info("hargaPokokTableData count: " . $hargaPokokTableData);
            Log::info("npmEntitasTableData count: " . $npmEntitasTableData);
            Log::info("npmChartData count: " . $npmChartData);
            
            Log::info("Berhasil memuat semua data, menampilkan view...");

            //dd($reportData);
            
            // Return view dengan semua variabel
            return view('laporan_bulanan.view', compact(
                'reportData', 'dailyRealisasi', 'dailyTarget', 'summaryData', 'monthlyDailyRealisasi',
                'monthlySummaryRealisasi', 'yearlyMonthlyRealisasi', 'entityDailyRealisasi',
                'dateLabels', 'entitySummaryData', 'entityPieData', 'hargaPokokSummaryData',
                'npmTargetValues', 'npmRealisasiValues','npmChartData',
                'startDateDisplay', 'endDateDisplay', 'periodeDisplay',
                'hargaPokokTableData', 'monthlyHargaPokokChartData',
                'endMonth', 'monthNames', 'monthlyProductSales', 'topProductsData', 'topProductsSalesData',
                'salaryData', 'monthlySalaryChartData', 'salaryTableData', 'operationalCostData', 'monthlyOperationalCostChartData',
                'marketingCostData', 'monthlyOverallChartData', 'monthlyOtherCostChartData', 'otherCostData', 'otherCostTableData', 'npmEntitasTableData',
                'totalPendapatanTarget', 'totalPendapatanRealisasi', 'kd_laporan',
                'tugasArray', 'issueArray', 'komplainArray', 'analisisArray'
            ));
            
        } catch (\Exception $e) {
            Log::error("=== ERROR KRITIS DALAM showLaporanBulanan ===");
            Log::error("Pesan error: " . $e->getMessage());
            Log::error("File: " . $e->getFile());
            Log::error("Line: " . $e->getLine());
            Log::error("Trace: " . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Gagal memuat laporan: ' . $e->getMessage())
                ->withInput();
        }
    }

    // PERBAIKAN: Tambahkan method helper di dalam controller
    /**
     * Memastikan data selalu berupa array
     */


    private function ensureArrayData($data, $title = '')
    {
        if (is_array($data)) {
            return $data;
        }
        
        if (is_string($data)) {
            try {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
                
                if (trim($data) === '' || $data === '[]' || $data === '{}') {
                    return [];
                }
                
                return [];
                
            } catch (\Exception $e) {
                return [];
            }
        }
        
        if ($data === null) {
            return [];
        }
        
        if (is_object($data)) {
            return (array)$data;
        }
        
        return [];
    }

    private function grafik01($kd_laporan, $kd_grafik)
    {
        // Decode title
        $kd_grafik = urldecode($kd_grafik);
        
        // Ambil data dari database
        $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
            ->where('kd_grafik', $kd_grafik)
            ->orderBy('kd_hasil_analisis', 'desc')
            ->first();

        $financialData = [];
        
        if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
            // Periksa apakah data sudah array atau masih JSON string
            if (is_array($hasilAnalisis->data)) {
                // Jika sudah array, gunakan langsung
                $financialData = $hasilAnalisis->data;
            } else {
                // Jika masih string, decode JSON
                $financialData = json_decode($hasilAnalisis->data, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Jika error decoding JSON, gunakan array kosong
                    $financialData = [];
                }
            }
        }
        
        // Jika data kosong, gunakan data contoh
        $usingSampleData = empty($financialData);
        if ($usingSampleData) {
            $financialData = $this->getSampleDataLaporan();
        }
        
        // Pisahkan data induk dan detail
        $mainAccounts = array_filter($financialData, function($item) {
            return isset($item['level']) && $item['level'] == 1;
        });
        
        $detailAccounts = array_filter($financialData, function($item) {
            return isset($item['level']) && $item['level'] == 2;
        });
        
        // Hitung total untuk summary
        $totalTarget = 0;
        $totalRealisasi = 0;
        $achieveCount = 0;
        $notAchieveCount = 0;
        
        foreach ($mainAccounts as $account) {
            if (strip_tags($account['nama_akun']) !== 'Net Profit Margin') {
                $totalTarget += (float)($account['target'] ?? 0);
                $totalRealisasi += (float)($account['realisasi'] ?? 0);
            }
            
            if (($account['keterangan'] ?? '') === 'ACHIEVE') {
                $achieveCount++;
            } else {
                $notAchieveCount++;
            }
        }
        
        $totalGap = $totalRealisasi - $totalTarget;
        $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
        
        // Kembalikan semua data sebagai array
        return [
            'financialData' => $financialData,
            'usingSampleData' => $usingSampleData,
            'mainAccounts' => $mainAccounts,
            'detailAccounts' => $detailAccounts,
            'totalTarget' => $totalTarget,
            'totalRealisasi' => $totalRealisasi,
            'totalGap' => $totalGap,
            'totalPercentage' => $totalPercentage,
            'achieveCount' => $achieveCount,
            'notAchieveCount' => $notAchieveCount
        ];
    }


    public function grafik02($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();
            
            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => array_fill(0, 31, 0),
                    "Februari" => array_fill(0, 28, 0),
                    "Maret" => array_fill(0, 31, 0),
                    "April" => array_fill(0, 30, 0),
                    "Mei" => array_fill(0, 31, 0),
                    "Juni" => array_fill(0, 30, 0),
                    "Juli" => array_fill(0, 31, 0),
                    "Agustus" => array_fill(0, 31, 0),
                    "September" => array_fill(0, 30, 0),
                    "Oktober" => array_fill(0, 31, 0),
                    "November" => array_fill(0, 30, 0),
                    "Desember" => array_fill(0, 31, 0)
                ];
            }
            
            // Hitung statistik bulanan
            $monthlyStats = [];
            $totalAll = 0;
            $monthsWithData = 0;
            $monthlyAverage = 0;
            
            foreach ($chartData as $month => $dailyData) {
                $nonZeroData = array_filter($dailyData, function($value) {
                    return $value > 0;
                });
                
                $monthTotal = array_sum($nonZeroData);
                $monthAvg = count($nonZeroData) > 0 ? $monthTotal / count($nonZeroData) : 0;
                
                $monthlyStats[$month] = [
                    'total' => $monthTotal,
                    'average' => $monthAvg,
                    'max' => count($nonZeroData) > 0 ? max($nonZeroData) : 0,
                    'min' => count($nonZeroData) > 0 ? min($nonZeroData) : 0,
                    'days_with_data' => count($nonZeroData)
                ];
                
                $totalAll += $monthTotal;
                if (count($nonZeroData) > 0) {
                    $monthsWithData++;
                }
            }
            
            $monthlyAverage = $monthsWithData > 0 ? $totalAll / $monthsWithData : 0;
            
            // Kembalikan array dengan key-value pair yang benar
            return [
                'chartData' => $chartData,
                'monthlyStats' => $monthlyStats,
                'totalAll' => $totalAll,
                'monthsWithData' => $monthsWithData,
                'monthlyAverage' => $monthlyAverage,
                'usingSampleData' => $usingSampleData,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            // Return array kosong dengan struktur yang diharapkan
            return [
                'chartData' => [
                    "Januari" => array_fill(0, 31, 0),
                    "Februari" => array_fill(0, 28, 0),
                    "Maret" => array_fill(0, 31, 0),
                    "April" => array_fill(0, 30, 0),
                    "Mei" => array_fill(0, 31, 0),
                    "Juni" => array_fill(0, 30, 0),
                    "Juli" => array_fill(0, 31, 0),
                    "Agustus" => array_fill(0, 31, 0),
                    "September" => array_fill(0, 30, 0),
                    "Oktober" => array_fill(0, 31, 0),
                    "November" => array_fill(0, 30, 0),
                    "Desember" => array_fill(0, 31, 0)
                ],
                'monthlyStats' => [],
                'totalAll' => 0,
                'monthsWithData' => 0,
                'monthlyAverage' => 0,
                'usingSampleData' => true,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik03($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $data = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $data = $hasilAnalisis->data;
                } else {
                    $data = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $data = [];
                        Log::warning("JSON decode error untuk grafik03: " . json_last_error_msg());
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($data)) {
                $usingSampleData = true;
                $data = [
                    [
                        "nama_akun" => "01 Jan 2025",
                        "target" => 104311130,
                        "realisasi" => 104232263,
                        "gap" => -78867,
                        "percentage_vs_target" => 99.92,
                        "keterangan" => "TIDAK ACHIEVE",
                        "is_harian" => true,
                        "tanggal" => "2025-01-01"
                    ],
                    [
                        "nama_akun" => "02 Jan 2025",
                        "target" => 86698599,
                        "realisasi" => 86236068,
                        "gap" => -462531,
                        "percentage_vs_target" => 99.47,
                        "keterangan" => "TIDAK ACHIEVE",
                        "is_harian" => true,
                        "tanggal" => "2025-01-02"
                    ],
                    [
                        "nama_akun" => "03 Jan 2025",
                        "target" => 80559610,
                        "realisasi" => 80285220,
                        "gap" => -274390,
                        "percentage_vs_target" => 99.66,
                        "keterangan" => "TIDAK ACHIEVE",
                        "is_harian" => true,
                        "tanggal" => "2025-01-03"
                    ],
                    [
                        "nama_akun" => "04 Jan 2025",
                        "target" => 168494350,
                        "realisasi" => 168799270,
                        "gap" => 304920,
                        "percentage_vs_target" => 100.18,
                        "keterangan" => "ACHIEVE",
                        "is_harian" => true,
                        "tanggal" => "2025-01-04"
                    ],
                    [
                        "nama_akun" => "05 Jan 2025",
                        "target" => 166819044,
                        "realisasi" => 166981619,
                        "gap" => 162575,
                        "percentage_vs_target" => 100.10,
                        "keterangan" => "ACHIEVE",
                        "is_harian" => true,
                        "tanggal" => "2025-01-05"
                    ],
                    [
                        "nama_akun" => "<strong>TOTAL PENDAPATAN BULANAN</strong>",
                        "target" => 35732912655,
                        "realisasi" => 35623770543,
                        "gap" => -109142112,
                        "percentage_vs_target" => 99.69,
                        "keterangan" => "TIDAK ACHIEVE",
                        "is_total" => true
                    ]
                ];
            }

            // Data untuk grafik
            $labels = [];
            $targetData = [];
            $realisasiData = [];
            $percentageData = [];
            $statusColors = [];

            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $totalAchieve = 0;
            $totalNotAchieve = 0;
            $totalData = []; // Inisialisasi variabel totalData

            foreach ($data as $item) {
                // Pastikan item memiliki struktur yang benar
                $item = array_merge([
                    'target' => 0,
                    'realisasi' => 0,
                    'gap' => 0,
                    'percentage_vs_target' => 0,
                    'keterangan' => 'TIDAK ACHIEVE',
                    'is_harian' => false,
                    'is_total' => false,
                    'tanggal' => date('Y-m-d')
                ], $item);

                // Periksa apakah key 'is_harian' ada dan bernilai true
                if (isset($item['is_harian']) && $item['is_harian'] === true) {
                    $labels[] = date('d M', strtotime($item['tanggal']));
                    $targetData[] = ($item['target'] ?? 0) / 1000000; // Convert to millions
                    $realisasiData[] = ($item['realisasi'] ?? 0) / 1000000; // Convert to millions
                    $percentageData[] = $item['percentage_vs_target'] ?? 0;
                    $statusColors[] = ($item['keterangan'] == 'ACHIEVE') ? '#28a745' : '#dc3545';
                    
                    $totalTarget += $item['target'] ?? 0;
                    $totalRealisasi += $item['realisasi'] ?? 0;
                    
                    if (($item['keterangan'] ?? '') == 'ACHIEVE') {
                        $totalAchieve++;
                    } else {
                        $totalNotAchieve++;
                    }
                }
                
                // Periksa apakah ini data total
                if (isset($item['is_total']) && $item['is_total'] === true) {
                    $totalData = $item;
                }
            }

            // Jika tidak ada data total yang ditemukan, hitung manual
            if (empty($totalData)) {
                $totalData = [
                    "target" => $totalTarget,
                    "realisasi" => $totalRealisasi,
                    "gap" => $totalRealisasi - $totalTarget,
                    "percentage_vs_target" => $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0,
                    "keterangan" => ($totalRealisasi >= $totalTarget) ? "ACHIEVE" : "TIDAK ACHIEVE"
                ];
            }

            // Pastikan totalData memiliki semua key yang diperlukan
            $totalData = array_merge([
                "target" => 0,
                "realisasi" => 0,
                "gap" => 0,
                "percentage_vs_target" => 0,
                "keterangan" => "TIDAK ACHIEVE"
            ], $totalData);

            $totalDays = $totalAchieve + $totalNotAchieve;
            $totalGap = $totalData['realisasi'] - $totalData['target'];
            $totalPercentage = $totalData['target'] > 0 ? ($totalData['realisasi'] / $totalData['target']) * 100 : 0;
            $achievementRate = $totalDays > 0 ? ($totalAchieve / $totalDays) * 100 : 0;

            return [
                'data' => $data,
                'labels' => $labels,
                'targetData' => $targetData,
                'realisasiData' => $realisasiData,
                'percentageData' => $percentageData,
                'statusColors' => $statusColors,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'totalAchieve' => $totalAchieve,
                'totalNotAchieve' => $totalNotAchieve,
                'achievementRate' => $achievementRate,
                'totalData' => $totalData,
                'usingSampleData' => $usingSampleData,
                'kd_laporan' => $kd_laporan
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik03: " . $e->getMessage());
            Log::error("File: " . $e->getFile() . " Line: " . $e->getLine());
        
            // Return data default yang aman
            return [
                'data' => [],
                'labels' => [],
                'targetData' => [],
                'realisasiData' => [],
                'percentageData' => [],
                'statusColors' => [],
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'totalAchieve' => 0,
                'totalNotAchieve' => 0,
                'achievementRate' => 0,
                'totalData' => [
                    "target" => 0,
                    "realisasi" => 0,
                    "gap" => 0,
                    "percentage_vs_target" => 0,
                    "keterangan" => "TIDAK ACHIEVE"
                ],
                'usingSampleData' => true,
                'kd_laporan' => $kd_laporan
            ];
        }
    }

    public function grafik10($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();
            
            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    [
                        "cabang" => "Bin Daud Maros",
                        "target" => "1241313146",
                        "realisasi" => "1240257274",
                        "percentage" => 99.91493911077939,
                        "gap" => -1055872,
                        "keterangan" => "TIDAK ACHIEVE"
                    ],
                    [
                        "cabang" => "Bin Daud Pettarani",
                        "target" => "1169938555",
                        "realisasi" => "1170007775",
                        "percentage" => 100.00591655003626,
                        "gap" => 69220,
                        "keterangan" => "ACHIEVE"
                    ],
                    [
                        "cabang" => "Satu Rasa",
                        "target" => "733963077",
                        "realisasi" => "734855682",
                        "percentage" => 100.12161442829637,
                        "gap" => 892605,
                        "keterangan" => "ACHIEVE"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $achieveCount = 0;
            $notAchieveCount = 0;
            
            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
                
                if ($data['keterangan'] === 'ACHIEVE') {
                    $achieveCount++;
                } else {
                    $notAchieveCount++;
                }
            }
            
            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // PERBAIKAN: Kembalikan array dengan key-value pair yang benar
            return [
                'chartData' => $chartData, // Key dengan value yang benar
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'notAchieveCount' => $notAchieveCount,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            // Return array kosong dengan struktur yang diharapkan
            return [
                'chartData' => [],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'notAchieveCount' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik11($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_string($hasilAnalisis->data)) {
                    $data = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $chartData = $data;
                    } else {
                        $chartData = [];
                        Log::warning("JSON decode error untuk grafik20: " . json_last_error_msg());
                    }
                } elseif (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = [];
                    Log::warning("Tipe data tidak dikenali untuk grafik20: " . gettype($hasilAnalisis->data));
                }
            }

            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => "3181910080",
                        "realisasi" => "3184519031"
                    ],
                    "Februari" => [
                        "target" => "2854757371", 
                        "realisasi" => "2853409350"
                    ],
                    "Maret" => [
                        "target" => "3145214778",
                        "realisasi" => "3145120731"
                    ],
                    "April" => [
                        "target" => "3093963561",
                        "realisasi" => "3090794366"
                    ],
                    "Mei" => [
                        "target" => "3163812227",
                        "realisasi" => "3161403377"
                    ],
                    "Juni" => [
                        "target" => "3304750304",
                        "realisasi" => "3302488526"
                    ],
                    "Juli" => [
                        "target" => "3283063594",
                        "realisasi" => "3284541298"
                    ],
                    "Agustus" => [
                        "target" => "3195258440",
                        "realisasi" => "3190296860"
                    ]
                ];
            }

            // Proses data untuk chart dan statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $totalGap = 0;
            $achieveCount = 0;

            foreach ($chartData as $bulan => $data) {
                $target = (float)$data['target'];
                $realisasi = (float)$data['realisasi'];
                $gap = $realisasi - $target;
                
                $totalTarget += $target;
                $totalRealisasi += $realisasi;
                $totalGap += $gap;
                
                // Update data dengan nilai yang dihitung
                $chartData[$bulan]['gap'] = $gap;
                $chartData[$bulan]['percentage'] = $target != 0 ? ($realisasi / $target) * 100 : 0;
                $chartData[$bulan]['keterangan'] = $gap >= 0 ? "ACHIEVE" : "TIDAK ACHIEVE";
                
                if ($chartData[$bulan]['keterangan'] === 'ACHIEVE') {
                    $achieveCount++;
                }
            }

            $totalPercentage = $totalTarget != 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            // Return data dalam format array
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik20: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => ["target" => "3181910080", "realisasi" => "3184519031"],
                    "Februari" => ["target" => "2854757371", "realisasi" => "2853409350"],
                    "Maret" => ["target" => "3145214778", "realisasi" => "3145120731"]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];
        }
    }


    public function grafik04($kd_laporan, $kd_grafik)
    {
        // Ambil data dari database
        try {
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
            ->where('kd_grafik', $kd_grafik)
            ->orderBy('kd_hasil_analisis', 'desc')
            ->first();

            //dd($hasilAnalisis);

            // --- Inisialisasi semua variabel di awal ---
            $chartData = [];
            $usingSampleData = false;
            $totalRealisasi = 0;
            $rataRata = 0;
            $bulanTertinggi = 'N/A';
            $nilaiTertinggi = 0;

            // --- Proses data dari database jika ada ---
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                $data = is_array($hasilAnalisis->data) ? $hasilAnalisis->data : json_decode($hasilAnalisis->data, true);
                $chartData = is_array($data) ? $data : [];
                
                // --- Perhitungan dilakukan di sini ---
                $totalRealisasi = array_sum($chartData);
                
                $bulanDenganData = array_filter($chartData, function($value) {
                    return $value > 0;
                });
                $rataRata = count($bulanDenganData) > 0 ? $totalRealisasi / count($bulanDenganData) : 0;
                
                if (!empty(array_filter($chartData))) {
                    $nilaiTertinggi = max($chartData);
                    $bulanTertinggi = array_keys($chartData, $nilaiTertinggi)[0];
                }
            } else {
                // --- Jika tidak ada data dari database, gunakan data sampel ---
                $usingSampleData = true;
                $chartData = [
                    "Januari" => 3743391623, "Februari" => 3311789946, "Maret" => 3963529904,
                    "April" => 0, "Mei" => 0, "Juni" => 0, "Juli" => 0, "Agustus" => 0,
                    "September" => 0, "Oktober" => 0, "November" => 0, "Desember" => 0
                ];
                
                // --- Perhitungan dilakukan di sini menggunakan data sampel ---
                $totalRealisasi = array_sum($chartData);
                
                $bulanDenganData = array_filter($chartData, function($value) {
                    return $value > 0;
                });
                $rataRata = count($bulanDenganData) > 0 ? $totalRealisasi / count($bulanDenganData) : 0;
                
                if (!empty(array_filter($chartData))) {
                    $nilaiTertinggi = max($chartData);
                    $bulanTertinggi = array_keys($chartData, $nilaiTertinggi)[0];
                }
            }

            return view('laporan_bulanan.ringkasanBulanan', compact(
                'chartData',
                'usingSampleData',
                'totalRealisasi',
                'rataRata',
                'bulanTertinggi',
                'nilaiTertinggi'
            ));
        } catch (\Exception $e) {
            // Return array kosong dengan struktur yang diharapkan
            return [
                'chartData' => [],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'rataRata' => 0,
                'bulanTertinggi' => 0,
                'nilaiTertinggi' => 0
            ];
        }
    }

    
    public function grafik05($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data default
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "2025" => ["3743391623", "3311789946", "3963529904", "3706668934", "3813427654", "4928015490", "5864270213", "6292676779", 0, 0, 0, 0],
                    "2024" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2023" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2022" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2021" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2020" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                ];
            }
            
            // Urutkan tahun dari yang paling lama ke yang terbaru
            uksort($chartData, function($a, $b) {
                return $a <=> $b;
            });
            
            // Hitung statistik untuk informasi tambahan
            $stats = $this->calculateOmzetStats($chartData);
            
            // Return data dalam format array untuk digunakan dalam analisaGrafik
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'stats' => $stats,
                'totalAll' => $stats['total_all'] ?? 0,
                'averageAll' => $stats['average_all'] ?? 0,
                'growthRate' => $stats['latest_growth_rate'] ?? 0,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik05: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "2025" => ["3743391623", "3311789946", "3963529904", "3706668934", "3813427654", "4928015490", "5864270213", "6292676779", 0, 0, 0, 0],
                    "2024" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2023" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2022" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2021" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    "2020" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                ],
                'usingSampleData' => true,
                'stats' => [],
                'totalAll' => 0,
                'averageAll' => 0,
                'growthRate' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }
    

    //////////////////////////////////

    public function grafik06($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $dailyData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $dailyData = $hasilAnalisis->data;
                } else {
                    $dailyData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $dailyData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($dailyData)) {
                $usingSampleData = true;
                $dailyData = [
                    "Bin Daud Maros" => [
                        "01-Jan-2025" => 0, "02-Jan-2025" => 0, "03-Jan-2025" => 0,
                        "20-Jul-2025" => 84124178, "21-Jul-2025" => 73055126, "22-Jul-2025" => 65947118,
                        "23-Jul-2025" => 92339468, "24-Jul-2025" => 87190340, "25-Jul-2025" => 94463617,
                        "26-Jul-2025" => 77284150, "27-Jul-2025" => 89616434, "28-Jul-2025" => 78019489,
                        "29-Jul-2025" => 74889269, "30-Jul-2025" => 81126106, "31-Jul-2025" => 80200629,
                        "01-Aug-2025" => 94086419, "02-Aug-2025" => 69576806, "03-Aug-2025" => 79492270,
                        "04-Aug-2025" => 83433909, "05-Aug-2025" => 69251009, "06-Aug-2025" => 94674291,
                        "07-Aug-2025" => 68769944, "08-Aug-2025" => 56989563, "09-Aug-2025" => 86678540,
                        "10-Aug-2025" => 78434357, "11-Aug-2025" => 88244757, "12-Aug-2025" => 96899368,
                        "13-Aug-2025" => 76373921, "14-Aug-2025" => 88328084, "15-Aug-2025" => 59951443,
                        "16-Aug-2025" => 91739254, "17-Aug-2025" => 74312089, "18-Aug-2025" => 74917641,
                        "19-Aug-2025" => 50355057, "20-Aug-2025" => 76725367, "21-Aug-2025" => 86144457,
                        "22-Aug-2025" => 98257771, "23-Aug-2025" => 88048386, "24-Aug-2025" => 71834141,
                        "25-Aug-2025" => 83792857, "26-Aug-2025" => 54568843, "27-Aug-2025" => 73575187,
                        "28-Aug-2025" => 50666442, "29-Aug-2025" => 84631690, "30-Aug-2025" => 83436838,
                        "31-Aug-2025" => 72536963
                    ],
                    "Bin Daud Pettarani" => [
                        "01-Jan-2025" => 83134804, "02-Jan-2025" => 61095591, "03-Jan-2025" => 58003064,
                        // Data lengkap lainnya...
                    ],
                    "Satu Rasa" => [
                        "01-Jan-2025" => 21097459, "02-Jan-2025" => 25140477, "03-Jan-2025" => 22282156,
                        // Data lengkap lainnya...
                    ]
                ];
            }
            
            // Hitung statistik
            $entities = array_keys($dailyData);
            $totalByEntity = [];
            $maxByEntity = [];
            $minByEntity = [];
            $avgByEntity = [];
            
            foreach ($dailyData as $entity => $data) {
                // Filter hanya nilai yang lebih dari 0
                $nonZeroData = array_filter($data, function($value) { 
                    return $value > 0; 
                });
                
                $totalByEntity[$entity] = array_sum($nonZeroData);
                $maxByEntity[$entity] = !empty($nonZeroData) ? max($nonZeroData) : 0;
                $minByEntity[$entity] = !empty($nonZeroData) ? min($nonZeroData) : 0;
                $avgByEntity[$entity] = !empty($nonZeroData) ? $totalByEntity[$entity] / count($nonZeroData) : 0;
            }
            
            // Ambil semua tanggal unik
            $allDates = [];
            foreach ($dailyData as $data) {
                $allDates = array_merge($allDates, array_keys($data));
            }
            $allDates = array_unique($allDates);
            sort($allDates);
            
            // Filter hanya tanggal dengan data (tidak semua 0)
            $datesWithData = [];
            foreach ($allDates as $date) {
                $hasData = false;
                foreach ($dailyData as $data) {
                    if (isset($data[$date]) && $data[$date] > 0) {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $datesWithData[] = $date;
                }
            }
            
            // Jika tidak ada tanggal dengan data, gunakan semua tanggal
            if (empty($datesWithData)) {
                $datesWithData = $allDates;
            }
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'dailyData' => $dailyData,
                'usingSampleData' => $usingSampleData,
                'entities' => $entities,
                'totalByEntity' => $totalByEntity,
                'maxByEntity' => $maxByEntity,
                'minByEntity' => $minByEntity,
                'avgByEntity' => $avgByEntity,
                'datesWithData' => $datesWithData,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik06: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'dailyData' => [
                    "Bin Daud Maros" => [
                        "01-Jan-2025" => 0, "02-Jan-2025" => 0, "03-Jan-2025" => 0,
                        "20-Jul-2025" => 84124178, "21-Jul-2025" => 73055126, "22-Jul-2025" => 65947118,
                        "23-Jul-2025" => 92339468, "24-Jul-2025" => 87190340, "25-Jul-2025" => 94463617,
                        "26-Jul-2025" => 77284150, "27-Jul-2025" => 89616434, "28-Jul-2025" => 78019489,
                        "29-Jul-2025" => 74889269, "30-Jul-2025" => 81126106, "31-Jul-2025" => 80200629
                    ]
                ],
                'usingSampleData' => true,
                'entities' => ["Bin Daud Maros"],
                'totalByEntity' => ["Bin Daud Maros" => 0],
                'maxByEntity' => ["Bin Daud Maros" => 0],
                'minByEntity' => ["Bin Daud Maros" => 0],
                'avgByEntity' => ["Bin Daud Maros" => 0],
                'datesWithData' => [],
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik09($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => "3181910080",
                        "realisasi" => "3184519031"
                    ],
                    "Februari" => [
                        "target" => "2854757371", 
                        "realisasi" => "2853409350"
                    ],
                    "Maret" => [
                        "target" => "3145214778",
                        "realisasi" => "3145120731"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $gapData = [];
            $percentageData = [];
            
            foreach ($chartData as $bulan => $data) {
                $target = (float)($data['target'] ?? 0);
                $realisasi = (float)($data['realisasi'] ?? 0);
                
                $totalTarget += $target;
                $totalRealisasi += $realisasi;
                
                $gap = $realisasi - $target;
                $gapData[$bulan] = $gap;
                
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                $percentageData[$bulan] = $percentage;
            }
            
            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'gapData' => $gapData,
                'percentageData' => $percentageData,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik07: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => ["target" => "3181910080", "realisasi" => "3184519031"],
                    "Februari" => ["target" => "2854757371", "realisasi" => "2853409350"],
                    "Maret" => ["target" => "3145214778", "realisasi" => "3145120731"]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'gapData' => [],
                'percentageData' => [],
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik07($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Bin Daud Pettarani" => [
                        "target" => 2473608731,
                        "realisasi" => 2474831888,
                        "gap" => 1223157,
                        "percentage" => 100.04944828115583,
                        "keterangan" => "ACHIEVE"
                    ],
                    "Satu Rasa" => [
                        "target" => 1489836768,
                        "realisasi" => 1488698016,
                        "gap" => -1138752,
                        "percentage" => 99.92356531772748,
                        "keterangan" => "TIDAK ACHIEVE"
                    ]
                ];
            }
            
            // Hitung total dan statistik
            $totalTarget = array_sum(array_column($chartData, 'target'));
            $totalRealisasi = array_sum(array_column($chartData, 'realisasi'));
            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            // Hitung jumlah entitas yang achieve
            $achieveCount = count(array_filter($chartData, function($item) {
                return $item['keterangan'] === 'ACHIEVE';
            }));

            $notAchieveCount = count($chartData) - $achieveCount;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'notAchieveCount' => $notAchieveCount,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik08: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Bin Daud Pettarani" => [
                        "target" => 2473608731,
                        "realisasi" => 2474831888,
                        "gap" => 1223157,
                        "percentage" => 100.04944828115583,
                        "keterangan" => "ACHIEVE"
                    ],
                    "Satu Rasa" => [
                        "target" => 1489836768,
                        "realisasi" => 1488698016,
                        "gap" => -1138752,
                        "percentage" => 99.92356531772748,
                        "keterangan" => "TIDAK ACHIEVE"
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'notAchieveCount' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik13($kd_laporan, $kd_grafik)
    {
        try {

            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }

            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    ["nama_produk" => "Air Zamzam 1 Liter", "total_items" => "35", "total_sales" => "1750000"],
                    ["nama_produk" => "Minyak Habbatussauda", "total_items" => "31", "total_sales" => "2945000"],
                    ["nama_produk" => "Parfum Mekkah", "total_items" => "24", "total_sales" => "2040000"],
                    ["nama_produk" => "Kurma Nabi Campuran", "total_items" => "21", "total_sales" => "2100000"],
                    ["nama_produk" => "Kurma Ajwa Madinah", "total_items" => "20", "total_sales" => "2850010"],
                    ["nama_produk" => "Parfum Madinah", "total_items" => "19", "total_sales" => "1710000"],
                    ["nama_produk" => "Kurma Sukari", "total_items" => "17", "total_sales" => "2040000"],
                    ["nama_produk" => "Minyak Argan", "total_items" => "16", "total_sales" => "2000000"],
                    ["nama_produk" => "Minyak Zaitun Palestina", "total_items" => "15", "total_sales" => "2625000"],
                    ["nama_produk" => "Kurma Mabroom", "total_items" => "14", "total_sales" => "1820000"]
                ];
            }

            // Hitung statistik
            $totalPenjualan = 0;
            $totalItem = 0;
            $maxPenjualan = 0;
            $minPenjualan = PHP_INT_MAX;

            foreach ($chartData as $item) {
                $totalPenjualan += (int)($item['total_sales'] ?? 0);
                $totalItem += (int)($item['total_items'] ?? 0);
                
                $penjualan = (int)($item['total_sales'] ?? 0);
                if ($penjualan > $maxPenjualan) {
                    $maxPenjualan = $penjualan;
                }
                
                if ($penjualan < $minPenjualan) {
                    $minPenjualan = $penjualan;
                }
            }

            $averagePenjualan = count($chartData) > 0 ? $totalPenjualan / count($chartData) : 0;
            $averageItems = count($chartData) > 0 ? $totalItem / count($chartData) : 0;

            // Siapkan data untuk grafik
            $productNames = [];
            $salesData = [];
            $itemsData = [];

            foreach ($chartData as $item) {
                $productNames[] = $item['nama_produk'] ?? 'Unknown Product';
                $salesData[] = (int)($item['total_sales'] ?? 0) / 1000000; // Convert to millions
                $itemsData[] = (int)($item['total_items'] ?? 0);
            }

            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'productNames' => $productNames,
                'salesData' => $salesData,
                'itemsData' => $itemsData,
                'totalPenjualan' => $totalPenjualan,
                'totalItem' => $totalItem,
                'maxPenjualan' => $maxPenjualan,
                'minPenjualan' => $minPenjualan,
                'averagePenjualan' => $averagePenjualan,
                'averageItems' => $averageItems,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik13: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    ["nama_produk" => "Air Zamzam 1 Liter", "total_items" => "35", "total_sales" => "1750000"],
                    ["nama_produk" => "Minyak Habbatussauda", "total_items" => "31", "total_sales" => "2945000"],
                    ["nama_produk" => "Parfum Mekkah", "total_items" => "24", "total_sales" => "2040000"]
                ],
                'usingSampleData' => true,
                'productNames' => [],
                'salesData' => [],
                'itemsData' => [],
                'totalPenjualan' => 0,
                'totalItem' => 0,
                'maxPenjualan' => 0,
                'minPenjualan' => 0,
                'averagePenjualan' => 0,
                'averageItems' => 0,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];
        }
    }


    public function grafik18($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => 706394,
                        "realisasi" => 706344
                    ],
                    "Februari" => [
                        "target" => 631712,
                        "realisasi" => 632036
                    ],
                    "Maret" => [
                        "target" => 734665,
                        "realisasi" => 735173
                    ],
                    "April" => [
                        "target" => 720000,
                        "realisasi" => 725000
                    ],
                    "Mei" => [
                        "target" => 680000,
                        "realisasi" => 675000
                    ],
                    "Juni" => [
                        "target" => 710000,
                        "realisasi" => 715000
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;

            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik05: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => [
                        "target" => 706394,
                        "realisasi" => 706344
                    ],
                    "Februari" => [
                        "target" => 631712,
                        "realisasi" => 632036
                    ],
                    "Maret" => [
                        "target" => 734665,
                        "realisasi" => 735173
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik14($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // PERBAIKAN: Cek tipe data sebelum melakukan decode
                if (is_string($hasilAnalisis->data)) {
                    // Jika data adalah string, coba decode sebagai JSON
                    $data = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $chartData = $data;
                    } else {
                        // Jika decode gagal, gunakan data sebagai array kosong
                        $chartData = [];
                        Log::warning("JSON decode error untuk grafik14: " . json_last_error_msg());
                    }
                } elseif (is_array($hasilAnalisis->data)) {
                    // Jika data sudah array, gunakan langsung
                    $chartData = $hasilAnalisis->data;
                } else {
                    // Untuk tipe data lainnya, set sebagai array kosong
                    $chartData = [];
                    Log::warning("Tipe data tidak dikenali untuk grafik14: " . gettype($hasilAnalisis->data));
                }
            }

            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Bin Daud Maros" => [
                        "target" => 1931296214,
                        "realisasi" => 1932048120
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 1948723623,
                        "realisasi" => 1948731087
                    ],
                    "Satu Rasa" => [
                        "target" => 1929482376,
                        "realisasi" => 1930894974
                    ]
                ];
            }

            // Proses data untuk chart dan statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $totalGap = 0;
            $achieveCount = 0;

            foreach ($chartData as $entitas => $data) {
                $gap = $data['realisasi'] - $data['target'];
                $percentage = $data['target'] != 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
                $keterangan = $data['realisasi'] >= $data['target'] ? "ACHIEVE" : "TIDAK ACHIEVE";
                
                if ($keterangan === "ACHIEVE") {
                    $achieveCount++;
                }
                
                $totalTarget += $data['target'];
                $totalRealisasi += $data['realisasi'];
                $totalGap += $gap;
                
                // Update data dengan nilai yang dihitung
                $chartData[$entitas]['gap'] = $gap;
                $chartData[$entitas]['percentage'] = $percentage;
                $chartData[$entitas]['keterangan'] = $keterangan;
            }

            $totalPercentage = $totalTarget != 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik14: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Bin Daud Maros" => [
                        "target" => 1931296214,
                        "realisasi" => 1932048120
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 1948723623,
                        "realisasi" => 1948731087
                    ],
                    "Satu Rasa" => [
                        "target" => 1929482376,
                        "realisasi" => 1930894974
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik15($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    [
                        "entitas" => "Bin Daud Maros",
                        "target" => 245149197,
                        "realisasi" => 245575618,
                        "gap" => 426421,
                        "percentage" => 100.17394346186661,
                        "keterangan" => "ACHIEVE"
                    ],
                    [
                        "entitas" => "Bin Daud Pettarani",
                        "target" => 247264525,
                        "realisasi" => 247403061,
                        "gap" => 138536,
                        "percentage" => 100.05602744671927,
                        "keterangan" => "ACHIEVE"
                    ],
                    [
                        "entitas" => "Satu Rasa",
                        "target" => 240589525,
                        "realisasi" => 240110084,
                        "gap" => -479441,
                        "percentage" => 99.80072241299783,
                        "keterangan" => "TIDAK ACHIEVE"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $achieveCount = 0;
            $notAchieveCount = 0;

            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
                
                if ($data['keterangan'] === 'ACHIEVE') {
                    $achieveCount++;
                } else {
                    $notAchieveCount++;
                }
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'notAchieveCount' => $notAchieveCount,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik15: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    [
                        "entitas" => "Bin Daud Maros",
                        "target" => 245149197,
                        "realisasi" => 245575618,
                        "gap" => 426421,
                        "percentage" => 100.17394346186661,
                        "keterangan" => "ACHIEVE"
                    ],
                    [
                        "entitas" => "Bin Daud Pettarani",
                        "target" => 247264525,
                        "realisasi" => 247403061,
                        "gap" => 138536,
                        "percentage" => 100.05602744671927,
                        "keterangan" => "ACHIEVE"
                    ],
                    [
                        "entitas" => "Satu Rasa",
                        "target" => 240589525,
                        "realisasi" => 240110084,
                        "gap" => -479441,
                        "percentage" => 99.80072241299783,
                        "keterangan" => "TIDAK ACHIEVE"
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'notAchieveCount' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }
    
    public function grafik16($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => "743951646",
                        "realisasi" => "744051637"
                    ],
                    "Februari" => [
                        "target" => "658699367", 
                        "realisasi" => "658975485"
                    ],
                    "Maret" => [
                        "target" => "733003247",
                        "realisasi" => "733088763"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $gapData = [];
            $percentageData = [];

            foreach ($chartData as $bulan => $data) {
                $target = (float)$data['target'];
                $realisasi = (float)$data['realisasi'];
                
                $totalTarget += $target;
                $totalRealisasi += $realisasi;
                
                $gap = $realisasi - $target;
                $gapData[$bulan] = $gap;
                
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                $percentageData[$bulan] = $percentage;
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            // Urutkan bulan sesuai urutan kalender
            $bulanOrder = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $sortedChartData = [];
            $sortedGapData = [];
            $sortedPercentageData = [];

            foreach ($bulanOrder as $bulan) {
                if (isset($chartData[$bulan])) {
                    $sortedChartData[$bulan] = $chartData[$bulan];
                    $sortedGapData[$bulan] = $gapData[$bulan];
                    $sortedPercentageData[$bulan] = $percentageData[$bulan];
                }
            }
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $sortedChartData,
                'gapData' => $sortedGapData,
                'percentageData' => $sortedPercentageData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik16: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => [
                        "target" => "743951646",
                        "realisasi" => "744051637"
                    ],
                    "Februari" => [
                        "target" => "658699367", 
                        "realisasi" => "658975485"
                    ],
                    "Maret" => [
                        "target" => "733003247",
                        "realisasi" => "733088763"
                    ]
                ],
                'gapData' => [],
                'percentageData' => [],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik19($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Bin Daud Maros" => [
                        "target" => 473920,
                        "realisasi" => 473815,
                        "gap" => -105,
                        "percentage" => -0.022155638082376774,
                        "keterangan" => "ACHIEVE"
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 485684,
                        "realisasi" => 486195,
                        "gap" => 511,
                        "percentage" => 0.10521244265818928,
                        "keterangan" => "TIDAK ACHIEVE"
                    ],
                    "Satu Rasa" => [
                        "target" => 470903,
                        "realisasi" => 470797,
                        "gap" => -106,
                        "percentage" => -0.022509943661433458,
                        "keterangan" => "ACHIEVE"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $achieveCount = 0;
            $notAchieveCount = 0;

            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
                
                if ($data['keterangan'] === 'ACHIEVE') {
                    $achieveCount++;
                } else {
                    $notAchieveCount++;
                }
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'notAchieveCount' => $notAchieveCount,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik19: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Bin Daud Maros" => [
                        "target" => 473920,
                        "realisasi" => 473815,
                        "gap" => -105,
                        "percentage" => -0.022155638082376774,
                        "keterangan" => "ACHIEVE"
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 485684,
                        "realisasi" => 486195,
                        "gap" => 511,
                        "percentage" => 0.10521244265818928,
                        "keterangan" => "TIDAK ACHIEVE"
                    ],
                    "Satu Rasa" => [
                        "target" => 470903,
                        "realisasi" => 470797,
                        "gap" => -106,
                        "percentage" => -0.022509943661433458,
                        "keterangan" => "ACHIEVE"
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'notAchieveCount' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }

    public function grafik17($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Bin Daud Maros" => [
                        "target" => 247644,
                        "realisasi" => 247431,
                        "gap" => -213,
                        "percentage" => -0.08601056355090372,
                        "keterangan" => "ACHIEVE"
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 242605,
                        "realisasi" => 243004,
                        "gap" => 399,
                        "percentage" => 0.16446487088064962,
                        "keterangan" => "TIDAK ACHIEVE"
                    ],
                    "Satu Rasa" => [
                        "target" => 244416,
                        "realisasi" => 244738,
                        "gap" => 322,
                        "percentage" => 0.1317426027755957,
                        "keterangan" => "TIDAK ACHIEVE"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;
            $achieveCount = 0;
            $notAchieveCount = 0;

            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
                
                if ($data['keterangan'] === 'ACHIEVE') {
                    $achieveCount++;
                } else {
                    $notAchieveCount++;
                }
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'achieveCount' => $achieveCount,
                'notAchieveCount' => $notAchieveCount,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik17: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Bin Daud Maros" => [
                        "target" => 247644,
                        "realisasi" => 247431,
                        "gap" => -213,
                        "percentage" => -0.08601056355090372,
                        "keterangan" => "ACHIEVE"
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 242605,
                        "realisasi" => 243004,
                        "gap" => 399,
                        "percentage" => 0.16446487088064962,
                        "keterangan" => "TIDAK ACHIEVE"
                    ],
                    "Satu Rasa" => [
                        "target" => 244416,
                        "realisasi" => 244738,
                        "gap" => 322,
                        "percentage" => 0.1317426027755957,
                        "keterangan" => "TIDAK ACHIEVE"
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'achieveCount' => 0,
                'notAchieveCount' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik20($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;
            
            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Periksa apakah data sudah array atau masih JSON string
                if (is_array($hasilAnalisis->data)) {
                    $chartData = $hasilAnalisis->data;
                } else {
                    $chartData = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $chartData = [];
                    }
                }
            }
            
            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => "8056446704",
                        "realisasi" => "8059428236"
                    ],
                    "Februari" => [
                        "target" => "7175577771",
                        "realisasi" => "7172552501"
                    ],
                    "Maret" => [
                        "target" => "8229749638",
                        "realisasi" => "8229887570"
                    ]
                ];
            }
            
            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;

            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            
            // Return data dalam format array untuk digunakan dalam view
            return [
                'chartData' => $chartData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'kd_laporan' => $kd_laporan
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in grafik20: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => [
                        "target" => "8056446704",
                        "realisasi" => "8059428236"
                    ],
                    "Februari" => [
                        "target" => "7175577771",
                        "realisasi" => "7172552501"
                    ],
                    "Maret" => [
                        "target" => "8229749638",
                        "realisasi" => "8229887570"
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik24($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database berdasarkan kd_laporan dan kd_grafik
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // PERBAIKAN: Cek tipe data sebelum melakukan decode
                if (is_string($hasilAnalisis->data)) {
                    // Jika data adalah string, coba decode sebagai JSON
                    $data = json_decode($hasilAnalisis->data, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $chartData = $data;
                    } else {
                        // Jika decode gagal, gunakan data sebagai array kosong
                        $chartData = [];
                        Log::warning("JSON decode error untuk grafik24: " . json_last_error_msg());
                    }
                } elseif (is_array($hasilAnalisis->data)) {
                    // Jika data sudah array, gunakan langsung
                    $chartData = $hasilAnalisis->data;
                } else {
                    // Untuk tipe data lainnya, set sebagai array kosong
                    $chartData = [];
                    Log::warning("Tipe data tidak dikenali untuk grafik24: " . gettype($hasilAnalisis->data));
                }
            }

            // Jika data kosong, gunakan data contoh
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => "8056446704",
                        "realisasi" => "8059428236"
                    ],
                    "Februari" => [
                        "target" => "7175577771",
                        "realisasi" => "7172552501"
                    ],
                    "Maret" => [
                        "target" => "8229749638",
                        "realisasi" => "8229887570"
                    ]
                ];
            }

            // Hitung statistik
            $totalTarget = 0;
            $totalRealisasi = 0;

            foreach ($chartData as $data) {
                $totalTarget += (float)$data['target'];
                $totalRealisasi += (float)$data['realisasi'];
            }

            $totalGap = $totalRealisasi - $totalTarget;
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            // Kembalikan data dalam format array
            return [
                'chartData' => $chartData, // PERBAIKAN: Pastikan key ini ada
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'totalPercentage' => $totalPercentage,
                'kd_laporan' => $kd_laporan
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik24: " . $e->getMessage());

            // Kembalikan array dengan data default jika terjadi error
            return [
                'chartData' => [ // PERBAIKAN: Pastikan key ini ada
                    "Januari" => [
                        "target" => "8056446704",
                        "realisasi" => "8059428236"
                    ],
                    "Februari" => [
                        "target" => "7175577771",
                        "realisasi" => "7172552501"
                    ],
                    "Maret" => [
                        "target" => "8229749638",
                        "realisasi" => "8229887570"
                    ]
                ],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'totalPercentage' => 0,
                'kd_laporan' => $kd_laporan
            ];
        }
    }


    public function grafik21($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Coba berbagai cara untuk mendapatkan data
                $rawData = $hasilAnalisis->data;
                
                // Jika sudah array, langsung gunakan
                if (is_array($rawData)) {
                    $chartData = $rawData;
                } 
                // Jika string, coba decode JSON
                elseif (is_string($rawData)) {
                    $decoded = json_decode($rawData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $chartData = $decoded;
                    }
                }
            }

            // Jika masih kosong, gunakan sample data
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => [
                        "target" => "36720508",
                        "realisasi" => "36735825"
                    ],
                    "Februari" => [
                        "target" => "34089482", 
                        "realisasi" => "34091052"
                    ],
                    "Maret" => [
                        "target" => "38540406",
                        "realisasi" => "38558716"
                    ],
                    "April" => [
                        "target" => "36648446",
                        "realisasi" => "36668220"
                    ],
                    "Mei" => [
                        "target" => "38683483",
                        "realisasi" => "38724806"
                    ],
                    "Juni" => [
                        "target" => "38624586",
                        "realisasi" => "38639874"
                    ],
                    "Juli" => [
                        "target" => "39507863",
                        "realisasi" => "39474005"
                    ],
                    "Agustus" => [
                        "target" => "38632939",
                        "realisasi" => "38679200"
                    ]
                ];
            }

            // Proses data untuk chart dan statistik
            // Data hanya memiliki target dan realisasi, jadi kita hitung manual
            $totalTarget = 0;
            $totalRealisasi = 0;
            $totalGap = 0;
            $bulanData = [];

            // Urutkan bulan sesuai urutan kalender
            $bulanOrder = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            foreach ($bulanOrder as $bulan) {
                if (isset($chartData[$bulan])) {
                    $data = $chartData[$bulan];
                    
                    // Pastikan format data konsisten
                    $target = is_array($data) ? (float)($data['target'] ?? 0) : 0;
                    $realisasi = is_array($data) ? (float)($data['realisasi'] ?? 0) : 0;
                    
                    // Hitung nilai yang tidak ada dalam data
                    $gap = $realisasi - $target;
                    $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
                    $keterangan = $gap >= 0 ? "ACHIEVE" : "TIDAK ACHIEVE";
                    
                    $totalTarget += $target;
                    $totalRealisasi += $realisasi;
                    $totalGap += $gap;
                    
                    $bulanData[] = [
                        'label' => $bulan,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'gap' => $gap,
                        'percentage' => $percentage,
                        'keterangan' => $keterangan
                    ];
                }
            }

            $grandPercentage = $totalTarget != 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            $bestMonth = '';
            $maxPercentage = -1;
            
            foreach ($bulanData as $data) {
                if ($data['percentage'] > $maxPercentage) {
                    $maxPercentage = $data['percentage'];
                    $bestMonth = $data['label'];
                }
            }

            // Kembalikan data dalam format array
            return [
                'chartData' => $chartData,
                'bulanData' => $bulanData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'grandPercentage' => $grandPercentage,
                'bestMonth' => $bestMonth,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];

        } catch (\Exception $e) {
            Log::error("Error in BiayaBulananController analisaGrafik: " . $e->getMessage());
            
            // Kembalikan array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => [
                        "target" => "36720508",
                        "realisasi" => "36735825"
                    ],
                    "Februari" => [
                        "target" => "34089482", 
                        "realisasi" => "34091052"
                    ],
                    "Maret" => [
                        "target" => "38540406",
                        "realisasi" => "38558716"
                    ]
                ],
                'bulanData' => [],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'grandPercentage' => 0,
                'bestMonth' => '',
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];
        }
    }


    public function grafik22($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Coba berbagai cara untuk mendapatkan data
                $rawData = $hasilAnalisis->data;
                
                // Jika sudah array, langsung gunakan
                if (is_array($rawData)) {
                    $chartData = $rawData;
                } 
                // Jika string, coba decode JSON
                elseif (is_string($rawData)) {
                    $decoded = json_decode($rawData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $chartData = $decoded;
                    }
                }
            }

            // Jika masih kosong, gunakan sample data
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Bin Daud Maros" => [
                        "target" => 100158946,
                        "realisasi" => 100198892
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 100270535,
                        "realisasi" => 100272298
                    ],
                    "Satu Rasa" => [
                        "target" => 101018232,
                        "realisasi" => 101100508
                    ]
                ];
            }

            // Proses data untuk chart dan statistik
            // Data hanya memiliki target dan realisasi, jadi kita hitung manual
            $totalTarget = 0;
            $totalRealisasi = 0;
            $totalGap = 0;
            $entitasData = [];

            foreach ($chartData as $entitas => $data) {
                // Pastikan format data konsisten
                $target = is_array($data) ? ($data['target'] ?? 0) : 0;
                $realisasi = is_array($data) ? ($data['realisasi'] ?? 0) : 0;
                
                // Hitung nilai yang tidak ada dalam data
                $gap = $realisasi - $target;
                $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
                $keterangan = $gap >= 0 ? "ACHIEVE" : "TIDAK ACHIEVE";
                
                $totalTarget += $target;
                $totalRealisasi += $realisasi;
                $totalGap += $gap;
                
                $entitasData[] = [
                    'label' => $entitas,
                    'target' => (float)$target,
                    'realisasi' => (float)$realisasi,
                    'gap' => (float)$gap,
                    'percentage' => (float)$percentage,
                    'keterangan' => $keterangan
                ];
            }

            $grandPercentage = $totalTarget != 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            $topAchiever = '';
            $maxPercentage = -1;
            
            foreach ($entitasData as $data) {
                if ($data['percentage'] > $maxPercentage) {
                    $maxPercentage = $data['percentage'];
                    $topAchiever = $data['label'];
                }
            }

            // Kembalikan data dalam format array
            return [
                'chartData' => $chartData,
                'entitasData' => $entitasData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'grandPercentage' => $grandPercentage,
                'topAchiever' => $topAchiever,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];

        } catch (\Exception $e) {
            Log::error("Error in BiayaLainLainController analisaGrafik: " . $e->getMessage());
            
            // Kembalikan array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Bin Daud Maros" => [
                        "target" => 100158946,
                        "realisasi" => 100198892
                    ],
                    "Bin Daud Pettarani" => [
                        "target" => 100270535,
                        "realisasi" => 100272298
                    ],
                    "Satu Rasa" => [
                        "target" => 101018232,
                        "realisasi" => 101100508
                    ]
                ],
                'entitasData' => [],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'grandPercentage' => 0,
                'topAchiever' => '',
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];
        }
    }


    public function grafik25($kd_laporan, $kd_grafik)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('kd_grafik', $kd_grafik)
                ->orderBy('kd_hasil_analisis', 'desc')
                ->first();

            $chartData = [];
            $usingSampleData = false;

            if ($hasilAnalisis && !empty($hasilAnalisis->data)) {
                // Coba berbagai cara untuk mendapatkan data
                $rawData = $hasilAnalisis->data;
                
                // Jika sudah array, langsung gunakan
                if (is_array($rawData)) {
                    $chartData = $rawData;
                } 
                // Jika string, coba decode JSON
                elseif (is_string($rawData)) {
                    $decoded = json_decode($rawData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $chartData = $decoded;
                    }
                }
            }

            // Jika masih kosong, gunakan sample data
            if (empty($chartData)) {
                $usingSampleData = true;
                $chartData = [
                    "Januari" => ["target" => -569070256, "realisasi" => -572644990],
                    "Februari" => ["target" => -547542605, "realisasi" => -548972609],
                    "Maret" => ["target" => -302858640, "realisasi" => -302827762],
                    // ... data lainnya
                ];
            }

            // Proses data untuk chart dan statistik
            $bulanList = array_keys($chartData);
            $totalTarget = 0;
            $totalRealisasi = 0;
            $totalGap = 0;
            $bulanData = [];

            foreach ($chartData as $bulan => $data) {
                // Pastikan format data konsisten
                $target = is_array($data) ? ($data['target'] ?? 0) : 0;
                $realisasi = is_array($data) ? ($data['realisasi'] ?? 0) : 0;
                
                $gap = $realisasi - $target;
                $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
                
                $totalTarget += $target;
                $totalRealisasi += $realisasi;
                $totalGap += $gap;
                
                $bulanData[] = [
                    'label' => $bulan,
                    'target' => (float)$target,
                    'realisasi' => (float)$realisasi,
                    'gap' => (float)$gap,
                    'percentage' => (float)$percentage
                ];
            }

            $grandPercentage = $totalTarget != 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
            $bestMonth = '';
            $maxPercentage = -1;
            foreach ($bulanData as $data) {
                if ($data['percentage'] > $maxPercentage) {
                    $maxPercentage = $data['percentage'];
                    $bestMonth = $data['label'];
                }
            }

            // Kembalikan data dalam format array
            return [
                'chartData' => $chartData,
                'bulanData' => $bulanData,
                'usingSampleData' => $usingSampleData,
                'totalTarget' => $totalTarget,
                'totalRealisasi' => $totalRealisasi,
                'totalGap' => $totalGap,
                'grandPercentage' => $grandPercentage,
                'bestMonth' => $bestMonth,
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik25: " . $e->getMessage());
            // Kembalikan array dengan data default jika terjadi error
            return [
                'chartData' => [
                    "Januari" => [
                        "target" => -569070256,
                        "realisasi" => -572644990
                    ],
                    "Februari" => [
                        "target" => -547542605,
                        "realisasi" => -548972609
                    ],
                    "Maret" => [
                        "target" => -302858640,
                        "realisasi" => -302827762
                    ]
                ],
                'bulanData' => [],
                'usingSampleData' => true,
                'totalTarget' => 0,
                'totalRealisasi' => 0,
                'totalGap' => 0,
                'grandPercentage' => 0,
                'bestMonth' => '',
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
            ];
        }
    }
    /**
     * Hitung statistik dari data chart omzet
     */
    private function calculateOmzetStats($chartData)
    {
        $stats = [
            'total_per_year' => [],
            'average_per_year' => [],
            'growth_rates' => [],
            'total_all' => 0,
            'average_all' => 0,
            'latest_growth_rate' => 0
        ];
        
        $previousYearTotal = null;
        $yearCount = 0;
        $totalSum = 0;
        
        foreach ($chartData as $year => $monthlyData) {
            // Filter hanya nilai yang bukan nol
            $nonZeroData = array_filter($monthlyData, function($value) {
                return $value > 0;
            });
            
            if (!empty($nonZeroData)) {
                $yearTotal = array_sum($nonZeroData);
                $yearAverage = $yearTotal / count($nonZeroData);
                
                $stats['total_per_year'][$year] = $yearTotal;
                $stats['average_per_year'][$year] = $yearAverage;
                
                $totalSum += $yearTotal;
                $yearCount++;
                
                // Hitung growth rate jika ada data tahun sebelumnya
                if ($previousYearTotal !== null && $previousYearTotal > 0) {
                    $growthRate = (($yearTotal - $previousYearTotal) / $previousYearTotal) * 100;
                    $stats['growth_rates'][$year] = $growthRate;
                    $stats['latest_growth_rate'] = $growthRate;
                }
                
                $previousYearTotal = $yearTotal;
            }
        }
        
        if ($yearCount > 0) {
            $stats['total_all'] = $totalSum;
            $stats['average_all'] = $totalSum / $yearCount;
        }
        
        return $stats;
    }
    
    /**
     * Helper functions untuk format angka - DIPERBAIKI MENJADI STATIC
     */
    public static function formatInJutaan($number)
    {
        return number_format($number / 1000000, 2, ',', '.');
    }
    
    public static function formatFullNumber($number)
    {
        return number_format($number, 0, ',', '.');
    }

    public function analisaGrafik($kd_laporan, $kd_grafik)
    {
        try {
            switch($kd_grafik){
                case 1:
                    // Dapatkan data dari method laporanKeuangan
                    $data = $this->grafik01($kd_laporan,  $kd_grafik);                    
                    // Tambahkan kd_laporan ke data
                    $data['kd_laporan'] = $kd_laporan;
                    $data['kd_grafik'] = $kd_grafik;

                    extract($data);                    
                    // return view('laporan_bulanan.laporanKeuangan', $data);
                    return view('laporan_bulanan.laporanKeuangan', compact(
                        'financialData',
                        'usingSampleData',
                        'mainAccounts',
                        'detailAccounts',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'notAchieveCount',
                        'kd_laporan',
                        'kd_grafik'
                    ));
                    break;
                
                case 2:
                    $data = $this->grafik02($kd_laporan, $kd_grafik);
                    return view('laporan_bulanan.analisisHarian', [
                        'chartData' => $data['chartData'] ?? [],
                        'monthlyStats' => $data['monthlyStats'] ?? [],
                        'totalAll' => $data['totalAll'] ?? 0,
                        'monthsWithData' => $data['monthsWithData'] ?? 0,
                        'monthlyAverage' => $data['monthlyAverage'] ?? 0,
                        'usingSampleData' => $data['usingSampleData'] ?? false,
                        'kd_laporan' => $data['kd_laporan'] ?? $kd_laporan
                    ]);
                    break;

                case 3:
                    // Laporan Pendapatan Harian
                    $data = $this->grafik03($kd_laporan, $kd_grafik);
                    
                    // Kirim data sebagai array assosiatif ke view
                    return view('laporan_bulanan.pendapatanHarian', [
                        'data' => $data['data'] ?? [],
                        'labels' => $data['labels'] ?? [],
                        'targetData' => $data['targetData'] ?? [],
                        'realisasiData' => $data['realisasiData'] ?? [],
                        'percentageData' => $data['percentageData'] ?? [],
                        'statusColors' => $data['statusColors'] ?? [],
                        'totalTarget' => $data['totalTarget'] ?? 0,
                        'totalRealisasi' => $data['totalRealisasi'] ?? 0,
                        'totalGap' => $data['totalGap'] ?? 0,
                        'totalPercentage' => $data['totalPercentage'] ?? 0,
                        'totalAchieve' => $data['totalAchieve'] ?? 0,
                        'totalNotAchieve' => $data['totalNotAchieve'] ?? 0,
                        'achievementRate' => $data['achievementRate'] ?? 0,
                        'totalData' => $data['totalData'] ?? [],
                        'usingSampleData' => $data['usingSampleData'] ?? false,
                        'kd_laporan' => $kd_laporan
                    ]);
                    break;


                case 4:
                    $data = $this->grafik04($kd_laporan, $kd_grafik);
                    return view('laporan_bulanan.ringkasanBulanan', [
                        'chartData' => $data['chartData'] ?? [],
                        'usingSampleData' => $data['usingSampleData'] ?? [],
                        'totalTarget' => $data['totalTarget'] ?? 0,
                        'totalRealisasi' => $data['totalRealisasi'] ?? 0,
                        'rataRata' => $data['rataRata'] ?? 0,
                        'bulanTertinggi' => $data['bulanTertinggi'] ?? 0,
                        'nilaiTertinggi' => $data['nilaiTertinggi'] ?? 0,
                        'kd_laporan' => $data['kd_laporan'] ?? $kd_laporan
                    ]);
                    break;

                case 5:
                    // Analisis Perbandingan Realisasi Omzet Antar Tahun
                    $data = $this->grafik05($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.omzetComparison', compact(
                        'chartData',
                        'usingSampleData',
                        'stats',
                        'totalAll',
                        'averageAll',
                        'growthRate',
                        'kd_laporan'
                    ));
                    break;

                case 6:
                    // Data Harian per Entitas
                    $data = $this->grafik06($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.dataHarianPerEntitas', compact(
                        'dailyData',
                        'usingSampleData',
                        'entities',
                        'totalByEntity',
                        'maxByEntity',
                        'minByEntity',
                        'avgByEntity',
                        'datesWithData',
                        'kd_laporan'
                    ));
                    break;
                case 7:
                    // Perbandingan Target & Realisasi Antar Entitas
                    $data = $this->grafik07($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.perbandinganTREntitas', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'notAchieveCount',
                        'kd_laporan'
                    ));
                    break;

                case 9:
                    // Perbandingan Target & Realisasi HPP per Entitas
                    $data = $this->grafik09($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.hppPerEntitas', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'gapData',
                        'percentageData',
                        'kd_laporan'
                    ));
                    break;
                    
                case 10:
                    $data = $this->grafik10($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.hargaPokokPerEntitas', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'notAchieveCount',
                        'kd_laporan'
                    ));

                case 11:
                    $data = $this->grafik11($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.HppPerBulanTR', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'kd_laporan',
                        'kd_grafik'
                    ));
                    break;
                
                case 13:
                    // Produk Terlaris
                    $data = $this->grafik13($kd_laporan, $kd_grafik);
                    
                    return view('laporan_bulanan.produkTerlaris', [
                        'chartData' => $data['chartData'],
                        'usingSampleData' => $data['usingSampleData'],
                        'productNames' => $data['productNames'],
                        'salesData' => $data['salesData'],
                        'itemsData' => $data['itemsData'],
                        'totalPenjualan' => $data['totalPenjualan'],
                        'totalItem' => $data['totalItem'],
                        'maxPenjualan' => $data['maxPenjualan'],
                        'minPenjualan' => $data['minPenjualan'],
                        'averagePenjualan' => $data['averagePenjualan'],
                        'averageItems' => $data['averageItems'],
                        'kd_laporan' => $kd_laporan,
                        'kd_grafik' => $kd_grafik
                    ]);
                    break;
                                
                case 15:
                    $data = $this->grafik15($kd_laporan, $kd_grafik);
        
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.bebanGajiPerEntitas', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'notAchieveCount',
                        'kd_laporan'
                    ));
                    break;

                case 16:
                    $data = $this->grafik16($kd_laporan, $kd_grafik);
        
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.bebanGajiBulanan', compact(
                        'chartData',
                        'gapData',
                        'percentageData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'kd_laporan'
                    ));

                case 14:
                    $data = $this->grafik14($kd_laporan, $kd_grafik);
                    
                    // PERBAIKAN: Gunakan data array langsung tanpa extract
                    return view('laporan_bulanan.bebanGajiPerEntitasTR', [
                        'chartData' => $data['chartData'] ?? [],
                        'usingSampleData' => $data['usingSampleData'] ?? false,
                        'totalTarget' => $data['totalTarget'] ?? 0,
                        'totalRealisasi' => $data['totalRealisasi'] ?? 0,
                        'totalGap' => $data['totalGap'] ?? 0,
                        'totalPercentage' => $data['totalPercentage'] ?? 0,
                        'achieveCount' => $data['achieveCount'] ?? 0,
                        'notAchieveCount' => $data['notAchieveCount'] ?? 0, // PERBAIKAN: Tambahkan ini
                        'kd_laporan' => $kd_laporan
                    ]);
                    break;

                case 17:
                    $data = $this->grafik17($kd_laporan, $kd_grafik);
        
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.biayaOperasionalPerEntitas', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'notAchieveCount',
                        'kd_laporan'
                    ));
                    break;

                case 18:
                    // Perbandingan Target dan Realisasi Biaya Operasional Per Bulan
                    $data = $this->grafik18($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.biayaOperasionalPerBulan', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'kd_laporan'
                    ));
                    break;
                
                case 19:
                    $data = $this->grafik19($kd_laporan, $kd_grafik);
        
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.bebanPemasaranPerEntitas', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'achieveCount',
                        'notAchieveCount',
                        'kd_laporan'
                    ));

                case 20:
                    $data = $this->grafik20($kd_laporan, $kd_grafik);
        
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.targetRealisasiPemasaranBulanan', compact(
                        'chartData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'totalPercentage',
                        'kd_laporan'
                    ));
                    break;

                case 21:
                    // Biaya Lain-lain Bulanan
                    $data = $this->grafik21($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.biayaLainLainBulanan', compact(
                        'chartData',
                        'bulanData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'grandPercentage',
                        'bestMonth',
                        'kd_laporan',
                        'kd_grafik'
                    ));
                    break;
                
                case 22:
                    // Biaya Lain-lain per Entitas
                    $data = $this->grafik22($kd_laporan, $kd_grafik);
                    
                    // Extract semua variabel dari array
                    extract($data);
                    
                    // Gunakan compact untuk mengirim semua variabel ke view
                    return view('laporan_bulanan.biayaLainLainPerEntitas', compact(
                        'chartData',
                        'entitasData',
                        'usingSampleData',
                        'totalTarget',
                        'totalRealisasi',
                        'totalGap',
                        'grandPercentage',
                        'topAchiever',
                        'kd_laporan',
                        'kd_grafik'
                    ));
                    break;
                
                case 24:
                    $data = $this->grafik24($kd_laporan, $kd_grafik);
                    
                    // PERBAIKAN: Gunakan data array langsung tanpa extract
                    return view('laporan_bulanan.targetRealisasiNPMPerEntitas', [
                        'chartData' => $data['chartData'] ?? [], // PERBAIKAN: Pastikan key ini ada
                        'usingSampleData' => $data['usingSampleData'] ?? false,
                        'totalTarget' => $data['totalTarget'] ?? 0,
                        'totalRealisasi' => $data['totalRealisasi'] ?? 0,
                        'totalGap' => $data['totalGap'] ?? 0,
                        'totalPercentage' => $data['totalPercentage'] ?? 0,
                        'kd_laporan' => $kd_laporan,
                        'kd_grafik' => $kd_grafik
                    ]);
                    break;

                case 25:
                    // Perbandingan Target dan Realisasi Bulanan NPM
                    $data = $this->grafik25($kd_laporan, $kd_grafik);
                    
                    // Tambahkan kd_laporan dan kd_grafik ke data array
                    $data['kd_laporan'] = $kd_laporan;
                    $data['kd_grafik'] = $kd_grafik;
                    
                    // Kirim seluruh array data ke view
                    return view('laporan_bulanan.targetRealisasiNPMPerBulan', $data);
                        
                default:
                    return redirect()->back()
                        ->with('error', 'Grafik tidak dikenali')
                        ->withInput();
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat laporan: ' . $e->getMessage())
                ->withInput();
        }
    }
}


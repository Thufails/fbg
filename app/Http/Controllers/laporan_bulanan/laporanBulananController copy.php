<?php

namespace App\Http\Controllers\laporan_bulanan;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Akun;
use Illuminate\Support\Facades\File;
use DateTime;
use Illuminate\Support\Facades\Log;

class laporanBulananController extends Controller
{
    public function index()
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

        // Tanggal untuk nama file. Asumsi untuk contoh ini, kita ingin melihat data tahun 2025 hingga bulan Maret.
        $endDate = '20250331';
        $endYear = (int)substr($endDate, 0, 4);
        $endMonth = (int)substr($endDate, 4, 2);
        $startDate = $endYear . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . '01';
        
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

        // // Data untuk grafik bar total realisasi harga pokok produksi per bulan
        // $monthlyHargaPokokRealisasi = array_fill(0, 12, 0);
        // $realisasiHPP = DB::table('realisasi')
        //     ->select(DB::raw('MONTH(realisasi.tanggal) as month'), DB::raw('SUM(realisasi.nilai) as total_realisasi'))
        //     ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
        //     ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
        //     ->where(function ($query) {
        //         $query->where('target.kd_akun', '=', '03')
        //             ->orWhere('akun.nama_akun', 'like', '%harga pokok%');
        //     })
        //     ->whereYear('realisasi.tanggal', $endYear)
        //     ->whereMonth('realisasi.tanggal', '<=', $endMonth)
        //     ->groupBy('month')
        //     ->orderBy('month')
        //     ->get();
            
        // foreach ($realisasiHPP as $item) {
        //     $monthlyHargaPokokRealisasi[$item->month - 1] = $item->total_realisasi;
        // }

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
                $query->where('target.kd_akun', '=', '03')
                    ->orWhere('akun.nama_akun', 'like', '%harga pokok%')
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

        // Siapkan data untuk chart (array dengan 12 bulan)
        // $monthlyHargaPokokTarget = [];
        // $monthlyHargaPokokRealisasi = [];

        // for ($i = 1; $i <= 12; $i++) {
        //     $monthlyHargaPokokTarget[] = $monthlyHargaPokokData[$i]['target'];
        //     $monthlyHargaPokokRealisasi[] = $monthlyHargaPokokData[$i]['realisasi'];
        // }

        // // Data untuk tabel summary harga pokok per bulan
        // $hargaPokokBulananTableData = [];
        // $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        //             'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // for ($i = 1; $i <= 12; $i++) {
        //     $target = $monthlyHargaPokokData[$i]['target'];
        //     $realisasi = $monthlyHargaPokokData[$i]['realisasi'];
        //     $gap = $realisasi - $target;
        //     $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
        //     $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; // Untuk biaya, lebih rendah lebih baik
            
        //     // Hanya tampilkan bulan yang memiliki data atau bulan yang sudah lewat
        //     if ($i <= $endMonth || $target > 0 || $realisasi > 0) {
        //         $hargaPokokBulananTableData[] = [
        //             'bulan' => $monthNames[$i - 1],
        //             'target' => $target,
        //             'realisasi' => $realisasi,
        //             'gap' => $gap,
        //             'percentage' => $percentage,
        //             'keterangan' => $keterangan
        //         ];
        //     }
        // }
        
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
                $query->where('target.kd_akun', '=', '03')
                    ->orWhere('akun.nama_akun', 'like', '%harga pokok%');
            })
            ->whereBetween('realisasi.tanggal', [$startDate,$endDate])
            ->groupBy('entitas.nama_entitas')
            ->get();

        //dd($realisasiHPPperEntitas);
            
        foreach ($realisasiHPPperEntitas as $item) {
            $entity = $item->nama_entitas ?? 'Lain-lain';
            $hargaPokokSummaryData[$entity] = [
                'target' => $item->total_target,
                'realisasi' => $item->total_realisasi
            ];
        }

        // // Tampilkan data dan hentikan eksekusi
        //dd($hargaPokokSummaryData);
        
        // // Menyiapkan data untuk tabel perbandingan harga pokok
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
        
        // PERBAIKAN 1: Hitung nilai untuk setiap akun induk dengan memperhitungkan anak-anaknya
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

        // PERBAIKAN 2: TAMPILKAN SEMUA AKUN (INDUK DAN ANAK) DENGAN PERSENTASE
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

        // PERBAIKAN 3: Hitung NPM dengan benar (Laba Kotor - Biaya/Beban - Ziswaf)
        // HANYA AKUN INDUK YANG DIPAKAI DALAM PERHITUNGAN NPM
        $npmTarget = $labaKotorTarget;
        $npmRealisasi = $labaKotorRealisasi;
        
        Log::info("NPM AWAL (LABA KOTOR): Target = " . number_format($npmTarget) . ", Realisasi = " . number_format($npmRealisasi));
        
        // Kumpulkan semua biaya/beban dan ziswaf (nilai sudah positif)
        $totalBiayaTarget = 0;
        $totalBiayaRealisasi = 0;
        $totalZiswafTarget = 0;
        $totalZiswafRealisasi = 0;
        
        // PERBAIKAN: Hanya gunakan akun induk untuk perhitungan NPM
        foreach ($parentAccounts as $account) {
            $isBiaya = str_contains(strtolower($account->nama_akun), 'biaya') || 
                       str_contains(strtolower($account->nama_akun), 'beban');
            
            $isZiswaf = str_contains(strtolower($account->nama_akun), 'ziswaf') ||
                        str_contains(strtolower($account->nama_akun), 'zakat') ||
                        str_contains(strtolower($account->nama_akun), 'infaq') ||
                        str_contains(strtolower($account->nama_akun), 'sedekah') ||
                        str_contains(strtolower($account->nama_akun), 'waqaf');
            
            // Kurangi biaya/beban dan ziswaf dari NPM (hanya akun induk, nilai sudah positif)
            if ($isBiaya || $isZiswaf) {
                $target = abs($calculatedData[$account->kd_akun]['target'] ?? 0); // PASTIKAN POSITIF
                $realisasi = abs($calculatedData[$account->kd_akun]['realisasi'] ?? 0); // PASTIKAN POSITIF
                
                $oldNpmTarget = $npmTarget;
                $oldNpmRealisasi = $npmRealisasi;
                
                $npmTarget -= $target;
                $npmRealisasi -= $realisasi;
                
                // Hitung total biaya dan ziswaf untuk logging
                if ($isBiaya) {
                    $totalBiayaTarget += $target;
                    $totalBiayaRealisasi += $realisasi;
                } else {
                    $totalZiswafTarget += $target;
                    $totalZiswafRealisasi += $realisasi;
                }
                
                // Log perubahan NPM untuk setiap akun biaya/ziswaf
                $jenisAkun = $isBiaya ? 'BIAYA' : 'ZISWAF';
                Log::info("PENGURANGAN NPM - {$jenisAkun} INDUK: {$account->nama_akun}");
                Log::info("  Target: " . number_format($oldNpmTarget) . " - " . number_format($target) . " = " . number_format($npmTarget));
                Log::info("  Realisasi: " . number_format($oldNpmRealisasi) . " - " . number_format($realisasi) . " = " . number_format($npmRealisasi));
                Log::info("  NPM SEMENTARA: Target = " . number_format($npmTarget) . ", Realisasi = " . number_format($npmRealisasi));
            }
        }

        // Hitung persentase NPM terhadap pendapatan
        $persenNpmTarget = $totalPendapatanTarget != 0 ? ($npmTarget / $totalPendapatanTarget) * 100 : 0;
        $persenNpmRealisasi = $totalPendapatanRealisasi != 0 ? ($npmRealisasi / $totalPendapatanRealisasi) * 100 : 0;

        // Log total biaya dan ziswaf
        Log::info("TOTAL BIAYA INDUK: Target = " . number_format($totalBiayaTarget) . " (" . round(($totalBiayaTarget / $totalPendapatanTarget) * 100, 2) . "%), Realisasi = " . number_format($totalBiayaRealisasi) . " (" . round(($totalBiayaRealisasi / $totalPendapatanRealisasi) * 100, 2) . "%)");
        Log::info("TOTAL ZISWAF INDUK: Target = " . number_format($totalZiswafTarget) . " (" . round(($totalZiswafTarget / $totalPendapatanTarget) * 100, 2) . "%), Realisasi = " . number_format($totalZiswafRealisasi) . " (" . round(($totalZiswafRealisasi / $totalPendapatanRealisasi) * 100, 2) . "%)");
        Log::info("TOTAL BEBAN INDUK (BIAYA + ZISWAF): Target = " . number_format($totalBiayaTarget + $totalZiswafTarget) . " (" . round((($totalBiayaTarget + $totalZiswafTarget) / $totalPendapatanTarget) * 100, 2) . "%), Realisasi = " . number_format($totalBiayaRealisasi + $totalZiswafRealisasi) . " (" . round((($totalBiayaRealisasi + $totalZiswafRealisasi) / $totalPendapatanRealisasi) * 100, 2) . "%)");

        // Log hasil akhir NPM
        Log::info("NPM FINAL: Target = " . number_format($npmTarget) . " (" . round($persenNpmTarget, 2) . "%), Realisasi = " . number_format($npmRealisasi) . " (" . round($persenNpmRealisasi, 2) . "%)");
        Log::info("GAP NPM: " . number_format($npmRealisasi - $npmTarget));
        Log::info("PERSENTASE NPM: " . ($npmTarget != 0 ? round(($npmRealisasi / $npmTarget) * 100, 2) : 0) . "%");

        // PERBAIKAN 4: Pastikan nilai beban positif dalam laporan
        foreach ($reportData as &$row) {
            // Hapus tag HTML untuk pengecekan
            $cleanNamaAkun = strip_tags($row['nama_akun']);
            
            $isBiayaReport = str_contains(strtolower($cleanNamaAkun), 'biaya') || 
                            str_contains(strtolower($cleanNamaAkun), 'beban');
            
            $isZiswafReport = str_contains(strtolower($cleanNamaAkun), 'ziswaf') ||
                             str_contains(strtolower($cleanNamaAkun), 'zakat') ||
                             str_contains(strtolower($cleanNamaAkun), 'infaq') ||
                             str_contains(strtolower($cleanNamaAkun), 'sedekah') ||
                             str_contains(strtolower($cleanNamaAkun), 'waqaf');
            
            // Pastikan nilai beban dan ziswaf positif dalam tampilan laporan
            if ($isBiayaReport || $isZiswafReport) {
                $row['target'] = abs($row['target']);
                $row['realisasi'] = abs($row['realisasi']);
                $row['gap'] = $row['realisasi'] - $row['target']; // Gap dihitung ulang
                $row['percentage'] = $row['target'] != 0 ? ($row['realisasi'] / $row['target']) * 100 : 0;
                $row['keterangan'] = ($row['realisasi'] - $row['target']) <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; // Untuk beban, realisasi <= target adalah ACHIEVE
                
                // Hitung ulang persentase setelah konversi ke positif
                $row['persen_target'] = $totalPendapatanTarget != 0 ? ($row['target'] / $totalPendapatanTarget) * 100 : 0;
                $row['persen_realisasi'] = $totalPendapatanRealisasi != 0 ? ($row['realisasi'] / $totalPendapatanRealisasi) * 100 : 0;
            }
        }
        unset($row); // Hapus reference

        // Tambahkan NPM
        $reportData[] = [
            'nama_akun' => '<strong>Net Profit Margin</strong>',
            'target' => $npmTarget,
            'realisasi' => $npmRealisasi,
            'gap' => $npmRealisasi - $npmTarget,
            'percentage' => $npmTarget != 0 ? ($npmRealisasi / $npmTarget) * 100 : 0,
            'keterangan' => ($npmRealisasi - $npmTarget) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
            'level' => 1,
            'is_induk' => true,
            'persen_target' => $persenNpmTarget,
            'persen_realisasi' => $persenNpmRealisasi
        ];

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
                
        // $reportDataAnalysis = $this->analyzeTableWithGemini(
        //     'Perbandingan Target dan Realisasi, NPM dan Laba Kotor',
        //     $reportData
        // );
        $reportDataAnalysis = null;
        
        // --- Bagian untuk data grafik pie chart dan tabel produk terlaris ---
        $topProductsData = DB::table('penjualan')
            ->select('produk.nama_produk', DB::raw('SUM(penjualan.jumlah) as total_items'))
            ->join('produk', 'penjualan.kd_produk', '=', 'produk.kd_produk')
            ->where('penjualan.tanggal', '>=', $startDate)
            ->where('penjualan.tanggal', '<=', $endDate)
            ->groupBy('produk.nama_produk')
            ->orderByDesc('total_items')
            ->limit(10) // Ambil 10 produk teratas untuk visualisasi
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
            ->limit(10) // Ambil 10 produk teratas untuk visualisasi
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
            // Kondisi seleksi yang diperbaiki: Langsung periksa kode akun (kd_akun) yang dimulai dengan '05'
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
            $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; // Target biaya adalah agar realisasi lebih rendah atau sama
            
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


        // --- Bagian baru untuk data grafik biaya lain-lain per entitas ---
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

        // Menghitung gap dan keterangan untuk data biaya lain-lain per entitas
        $otherCostTableData = [];
        foreach ($otherCostData as $entityName => $data) {
            $target = $data['target'];
            $realisasi = $data['realisasi'];
            $gap = $realisasi - $target;
            $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
            $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; // Untuk biaya, realisasi lebih rendah lebih baik

            $otherCostTableData[] = [
                'entitas' => $entityName,
                'target' => $target,
                'realisasi' => $realisasi,
                'gap' => $gap,
                'percentage' => $percentage,
                'keterangan' => $keterangan,
            ];
        }

        //NPM 
        // --- Bagian baru untuk data NPM per Entitas ---
        $npmPerEntitas = [];

        foreach ($sbsData as $item) {
            $kdAkun = $item['kd_akun'];
            $entity = $item['nama_entitas'] ?? 'Lain-lain';
            
            if (!isset($npmPerEntitas[$entity])) {
                $npmPerEntitas[$entity] = [
                    'target' => 0,
                    'realisasi' => 0,
                    'details' => []
                ];
            }
            
            // Simpan detail per akun untuk perhitungan NPM
            if (!isset($npmPerEntitas[$entity]['details'][$kdAkun])) {
                $npmPerEntitas[$entity]['details'][$kdAkun] = [
                    'target' => 0,
                    'realisasi' => 0
                ];
            }
            
            $npmPerEntitas[$entity]['details'][$kdAkun]['target'] += $item['nilai_target'];
            $npmPerEntitas[$entity]['details'][$kdAkun]['realisasi'] += $item['nilai_realisasi'];
        }

        // --- Perhitungan NPM per Entitas yang lebih detail ---
        $npmEntitasTableData = [];
        foreach ($npmPerEntitas as $entityName => $entityData) {
            $pendapatanTarget = 0;
            $pendapatanRealisasi = 0;
            $hppTarget = 0;
            $hppRealisasi = 0;
            $biayaTarget = 0;
            $biayaRealisasi = 0;
            
            // Hitung berdasarkan kategori akun
            foreach ($entityData['details'] as $kdAkun => $values) {
                $account = $accounts->firstWhere('kd_akun', $kdAkun);
                
                if ($account) {
                    // Pendapatan (akun 01)
                    if (str_starts_with($kdAkun, '01')) {
                        $pendapatanTarget += $values['target'];
                        $pendapatanRealisasi += $values['realisasi'];
                    }
                    // HPP (akun 03)
                    elseif (str_starts_with($kdAkun, '03')) {
                        $hppTarget += $values['target'];
                        $hppRealisasi += $values['realisasi'];
                    }
                    // Biaya (akun 05, 06, 07)
                    elseif (str_starts_with($kdAkun, '05') || str_starts_with($kdAkun, '06') || str_starts_with($kdAkun, '07')) {
                        $biayaTarget += $values['target'];
                        $biayaRealisasi += $values['realisasi'];
                    }
                }
            }
            
            // Hitung NPM: Pendapatan - HPP - Biaya
            $npmTarget = $pendapatanTarget - $hppTarget - $biayaTarget;
            $npmRealisasi = $pendapatanRealisasi - $hppRealisasi - $biayaRealisasi;
            
            $gap = $npmRealisasi - $npmTarget;
            $percentage = $npmTarget != 0 ? ($npmRealisasi / $npmTarget) * 100 : 0;
            $keterangan = $gap >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
            
            $npmEntitasTableData[] = [
                'entitas' => $entityName,
                'target' => $npmTarget,
                'realisasi' => $npmRealisasi,
                'gap' => $gap,
                'percentage' => $percentage,
                'keterangan' => $keterangan,
                'pendapatan_target' => $pendapatanTarget,
                'pendapatan_realisasi' => $pendapatanRealisasi,
                'hpp_target' => $hppTarget,
                'hpp_realisasi' => $hppRealisasi,
                'biaya_target' => $biayaTarget,
                'biaya_realisasi' => $biayaRealisasi,
            ];
        }

        return view('laporan_bulanan.view', compact(
            'reportDataAnalysis', 'reportData', 'dailyRealisasi', 'dailyTarget', 'summaryData', 'monthlyDailyRealisasi',
            'monthlySummaryRealisasi', 'yearlyMonthlyRealisasi', 'entityDailyRealisasi',
            'dateLabels', 'entitySummaryData', 'entityPieData', 'hargaPokokSummaryData',
        //    'monthlyHargaPokokRealisasi', 'hargaPokokTableData',
            'monthlyHargaPokokData','hargaPokokTableData',
            'endMonth', 'monthNames', 'monthlyProductSales', 'topProductsData', 'topProductsSalesData',
            'salaryData', 'monthlySalaryChartData', 'salaryTableData', 'operationalCostData', 'monthlyOperationalCostChartData',
            'marketingCostData', 'monthlyOverallChartData', 'monthlyOtherCostChartData', 'otherCostData', 'otherCostTableData','npmEntitasTableData',
            'startDate','endDate','totalPendapatanTarget', 'totalPendapatanRealisasi' // Tambahkan variabel ini untuk view
        ));
    }


    /**
     * Mengirim data tabel ke Gemini API untuk dianalisis.
     */
    private function analyzeTableWithGemini(string $tableTitle, array $tableData): string
    {
        // 1. Format data ke dalam string yang mudah dibaca oleh model
        if (empty($tableData)) {
            return 'Tidak ada data untuk dianalisis.';
        }

        // Handle case where array is not associative and has no keys
        if (!empty($tableData) && is_array($tableData[0])) {
            $headers = array_keys($tableData[0]);
            $dataString = "Berikut data tabel '{$tableTitle}':\n";
            $dataString .= implode(',', $headers) . "\n";
            foreach ($tableData as $row) {
                // Check if all values are scalars before imploding
                if (array_reduce($row, fn($carry, $item) => $carry && is_scalar($item), true)) {
                    $dataString .= implode(',', $row) . "\n";
                }
            }
        } else {
            $dataString = "Berikut data '{$tableTitle}':\n";
            foreach ($tableData as $value) {
                if (is_scalar($value)) {
                    $dataString .= $value . "\n";
                }
            }
        }

        $prompt = "Berdasarkan data tabel berikut, tolong berikan uraian dan analisis singkat: " . $dataString;

        // 2. Kirim permintaan ke Gemini API
        $apiKey = env('GEMINI_API_KEY'); // Sebaiknya gunakan .env file untuk API Key
        
        if (empty($apiKey)) {
            Log::error('GEMINI_API_KEY is not set in .env file.');
            return 'Maaf, kunci API tidak ditemukan.';
        }

        $client = new Client();

        try {
            $response = $client->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key={$apiKey}", [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $body = json_decode($response->getBody(), true);
            return $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Analisis tidak dapat dihasilkan.';

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return 'Maaf, terjadi kesalahan saat menghubungi layanan analisis. Silakan cek koneksi internet atau status API Anda.';
        }
    }
}
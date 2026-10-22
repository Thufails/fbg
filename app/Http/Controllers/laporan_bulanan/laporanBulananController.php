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
        $npmChartData = [];
        $tugasArray = [];
        $issueArray = [];
        $komplainArray = [];
        $analisisArray = [];
        $reportDataPerEntitas = [];

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
                    ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
                    ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                    ->where('target.kd_akun', 'like', '01%')
                    ->where('akun.aktif', '=', 'Y')
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
                    ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
                    ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                    ->where('target.kd_akun', 'like', '01%')
                    ->where('akun.aktif', '=', 'Y')
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

        // ========== PERHITUNGAN PERSENTASE YANG BENAR ==========
        
        // Hitung total target dan realisasi omzet untuk referensi persentase
        $totalOmzetTarget = 0;
        $totalOmzetRealisasi = 0;
        
        // Cari dan kumpulkan semua akun omzet/pendapatan (kode akun 01)
        foreach ($parentAccounts as $account) {
            if (str_starts_with($account->kd_akun, '01')) { // Akun pendapatan
                $totalOmzetTarget += $calculatedData[$account->kd_akun]['target'] ?? 0;
                $totalOmzetRealisasi += $calculatedData[$account->kd_akun]['realisasi'] ?? 0;
            }
        }

        Log::info("TOTAL OMZET UNTUK PERSENTASE - Target: " . number_format($totalOmzetTarget) . ", Realisasi: " . number_format($totalOmzetRealisasi));

        // ========== PERHITUNGAN OMZET BERSIH SEBELUM HPP ==========
        
        // Query untuk mendapatkan Omzet Bersih (Net Revenue) sebelum HPP
        $omzetBersihQuery = "
            SELECT 
                t.tanggal,
                t.kd_entitas,
                e.nama_entitas,
                SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS nilai_target,
                SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) AS nilai_realisasi,
                DAY(t.tanggal) as hari,
                MONTH(t.tanggal) as bulan,
                YEAR(t.tanggal) as tahun
            FROM target t
            LEFT JOIN realisasi r ON (t.kd_target = r.kd_target) 
            LEFT JOIN akun a ON (t.kd_akun = a.kd_akun) 
            LEFT JOIN entitas e ON (t.kd_entitas = e.kd_entitas)
            WHERE t.tanggal BETWEEN ? AND ?
            AND t.kd_akun IN (SELECT kd_akun FROM akun WHERE group_akun = 'OMZET')
            GROUP BY t.tanggal, t.kd_entitas, e.nama_entitas, hari, bulan, tahun
            ORDER BY t.tanggal, t.kd_entitas
        ";

        $omzetBersihData = DB::select($omzetBersihQuery, [$laporan->tanggal_awal, $laporan->tanggal_akhir]);

        // Hitung total Omzet Bersih
        $totalOmzetBersihTarget = 0;
        $totalOmzetBersihRealisasi = 0;
        
        foreach ($omzetBersihData as $item) {
            $totalOmzetBersihTarget += $item->nilai_target ?? 0;
            $totalOmzetBersihRealisasi += $item->nilai_realisasi ?? 0;
        }

        Log::info("OMZET BERSIH - Target: " . number_format($totalOmzetBersihTarget) . 
                ", Realisasi: " . number_format($totalOmzetBersihRealisasi));

        // ========== PERHITUNGAN OMZET BERSIH PER BULAN DAN PER ENTITAS ==========

        // Query untuk mendapatkan Omzet Bersih per bulan
        $omzetBersihBulananQuery = "
            SELECT 
                MONTH(t.tanggal) as bulan,
                YEAR(t.tanggal) as tahun,
                SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS nilai_target,
                SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) AS nilai_realisasi
            FROM target t
            LEFT JOIN realisasi r ON (t.kd_target = r.kd_target) 
            LEFT JOIN akun a ON (t.kd_akun = a.kd_akun) 
            WHERE t.tanggal BETWEEN ? AND ?
            AND t.kd_akun IN (SELECT kd_akun FROM akun WHERE group_akun = 'OMZET')
            GROUP BY YEAR(t.tanggal), MONTH(t.tanggal)
            ORDER BY YEAR(t.tanggal), MONTH(t.tanggal)
        ";

        $omzetBersihBulanan = DB::select($omzetBersihBulananQuery, [$laporan->tanggal_awal, $laporan->tanggal_akhir]);

        // Format data omzet bersih per bulan
        $omzetBersihPerBulan = [];
        foreach ($omzetBersihBulanan as $item) {
            $bulanInt = (int)$item->bulan;
            $monthName = $monthNames[$bulanInt - 1] ?? "Bulan {$bulanInt}";
            
            $omzetBersihPerBulan[$monthName] = [
                'target' => $item->nilai_target ?? 0,
                'realisasi' => $item->nilai_realisasi ?? 0
            ];
        }

        // Query untuk mendapatkan Omzet Bersih per entitas
        $omzetBersihEntitasQuery = "
            SELECT 
                t.kd_entitas,
                e.nama_entitas,
                SUM(CASE WHEN a.operasi = '+' THEN t.nilai ELSE -t.nilai END) AS nilai_target,
                SUM(CASE WHEN a.operasi = '+' THEN COALESCE(r.nilai, 0) ELSE -COALESCE(r.nilai, 0) END) AS nilai_realisasi
            FROM target t
            LEFT JOIN realisasi r ON (t.kd_target = r.kd_target) 
            LEFT JOIN akun a ON (t.kd_akun = a.kd_akun) 
            LEFT JOIN entitas e ON (t.kd_entitas = e.kd_entitas)
            WHERE t.tanggal BETWEEN ? AND ?
            AND t.kd_akun IN (SELECT kd_akun FROM akun WHERE group_akun = 'OMZET')
            GROUP BY t.kd_entitas, e.nama_entitas
            ORDER BY e.nama_entitas
        ";

        $omzetBersihEntitas = DB::select($omzetBersihEntitasQuery, [$laporan->tanggal_awal, $laporan->tanggal_akhir]);

        // Format data omzet bersih per entitas
        $omzetBersihPerEntitas = [];
        foreach ($omzetBersihEntitas as $item) {
            $entityName = $item->nama_entitas ?? 'Lain-lain';
            
            $omzetBersihPerEntitas[$entityName] = [
                'target' => $item->nilai_target ?? 0,
                'realisasi' => $item->nilai_realisasi ?? 0
            ];
        }

        // TAMPILKAN SEMUA AKUN (INDUK DAN ANAK) DENGAN PERSENTASE YANG DIPERBAIKI
        $labaKotorTarget = 0;
        $labaKotorRealisasi = 0;
        $labaKotorInserted = false;
        $omzetBersihInserted = false;
        
        $reportData = [];
        
        // Urutkan akun berdasarkan urutan
        $sortedAccounts = $accounts->sortBy('urutan');
        
        foreach ($sortedAccounts as $account) {
            $kdAkun = $account->kd_akun;
            $isInduk = is_null($account->induk);
            
            $target = $calculatedData[$kdAkun]['target'] ?? 0;
            $realisasi = $calculatedData[$kdAkun]['realisasi'] ?? 0;
            
            // PERBAIKAN: Hitung persentase sesuai aturan yang benar
            $isOmzet = str_starts_with($kdAkun, '01'); // Akun pendapatan (omzet)
            
            // PERHITUNGAN PERSENTASE TARGET (% vs Target Omzet Bersih)
            if($account->kd_akun == "01"){
                $persenTarget = 100;
                $persenRealisasi = ($realisasi/$target)*100;
            }
            else{
                $persenTarget = $totalOmzetBersihTarget != 0 ? ($target / $totalOmzetBersihTarget) * 100 : 0;
                $persenRealisasi = $totalOmzetBersihRealisasi != 0 ? ($realisasi / $totalOmzetBersihRealisasi) * 100 : 0;
            }
            
            
            // PERHITUNGAN PERSENTASE REALISASI (% vs Realisasi Omzet Bersih)
            //$persenRealisasi = $totalOmzetBersihRealisasi != 0 ? ($realisasi / $totalOmzetBersihRealisasi) * 100 : 0;
            
            // PERHITUNGAN PERSENTASE ACHIEVEMENT (Realisasi vs Target)
            $percentageAchievement = $target != 0 ? ($realisasi / $target) * 100 : 0;
            
            /////////////////////////////////
            if($account->kd_akun == "01" || $account->kd_akun == "99"){
                $keteranganStatus = ($realisasi - $target) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
            }
            else{
                if($account->operasi=="+"){
                    $keteranganStatus = ($persenRealisasi - $persenTarget) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                }   
                else{
                    $keteranganStatus = ($persenRealisasi - $persenTarget) >= 0 ? 'OVER BUDGET' : 'EFISIEN';
                } 
            }
            
            /////////////////////////////////
            
            // PERBAIKAN: Deteksi HPP/Biaya Pokok Penjualan secara spesifik
            $isHPP = str_contains(strtolower($account->nama_akun), 'hpp') || 
                    str_contains(strtolower($account->nama_akun), 'harga pokok') ||
                    str_contains(strtolower($account->nama_akun), 'biaya pokok') ||
                    str_contains(strtolower($account->nama_akun), 'cost of sales');
            
            $isBiayaLain = (str_contains(strtolower($account->nama_akun), 'biaya') || 
                        str_contains(strtolower($account->nama_akun), 'beban')) && !$isHPP;
            
            $isZiswaf = str_contains(strtolower($account->nama_akun), 'ziswaf') ||
                        str_contains(strtolower($account->nama_akun), 'zakat') ||
                        str_contains(strtolower($account->nama_akun), 'infaq') ||
                        str_contains(strtolower($account->nama_akun), 'sedekah') ||
                        str_contains(strtolower($account->nama_akun), 'waqaf');
            
            // Untuk akun induk, format nama menjadi bold
            $namaAkun = $isInduk ? '<strong>' . $account->nama_akun . '</strong>' : $account->nama_akun;
            
            // Tentukan level indentasi
            $level = $isInduk ? 1 : 2;
            
            // ========== PERBAIKAN: TAMBAHKAN OMZET BERSIH SEBELUM HPP ==========
            if ($isHPP && $isInduk && !$omzetBersihInserted) {
                // Hitung persentase Omzet Bersih
                $persenOmzetBersihTarget = $totalOmzetBersihTarget != 0 ? 
                    ($totalOmzetBersihTarget / $totalOmzetBersihTarget) * 100 : 0;
                $persenOmzetBersihRealisasi = $totalOmzetBersihRealisasi != 0 ? 
                    ($totalOmzetBersihRealisasi / $totalOmzetBersihRealisasi) * 100 : 0;
                $percentageOmzetBersihAchievement = $totalOmzetBersihTarget != 0 ? 
                    ($totalOmzetBersihRealisasi / $totalOmzetBersihTarget) * 100 : 0;
                
                $gapOmzetBersih = $totalOmzetBersihRealisasi - $totalOmzetBersihTarget;
                $keteranganOmzetBersih = $gapOmzetBersih >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                
                // Tambahkan entry Omzet Bersih dengan urutan 6
                $reportData[] = [
                    'nama_akun' => '<strong>Omzet Bersih</strong>',
                    'target' => $totalOmzetBersihTarget,
                    'persen_target' => $persenOmzetBersihTarget,
                    'realisasi' => $totalOmzetBersihRealisasi,
                    'persen_realisasi' => $persenOmzetBersihRealisasi,
                    'gap' => $gapOmzetBersih,
                    'percentage' => $percentageOmzetBersihAchievement,
                    'keterangan' => $keteranganOmzetBersih,
                    'level' => 1,
                    'is_induk' => true,
                    'kd_akun' => 'OMZETBERSIH',
                    'urutan' => 6 // Urutan khusus untuk Omzet Bersih
                ];
                
                $omzetBersihInserted = true;
                
                Log::info("OMZET BERSIH DITAMBAHKAN: Target = " . number_format($totalOmzetBersihTarget) . 
                        " (" . round($persenOmzetBersihTarget, 2) . "% vs Omzet Target), " .
                        "Realisasi = " . number_format($totalOmzetBersihRealisasi) . 
                        " (" . round($persenOmzetBersihRealisasi, 2) . "% vs Omzet Realisasi), " .
                        "Achievement = " . round($percentageOmzetBersihAchievement, 2) . "%");
            }
            
            // Tambahkan Laba Kotor sebelum akun biaya/beban/ziswaf pertama (hanya untuk induk)
            if ($isBiayaLain && $isInduk && !$labaKotorInserted) {
                // Hitung persentase Laba Kotor sesuai aturan baru
                $persenLabaKotorTarget = $totalOmzetBersihTarget != 0 ? ($labaKotorTarget / $totalOmzetBersihTarget) * 100 : 0;
                // PERBAIKAN: % realisasi laba kotor = (Realisasi Laba Kotor / Realisasi Omzet Bersih) * 100
                $persenLabaKotorRealisasi = $totalOmzetBersihRealisasi != 0 ? ($labaKotorRealisasi / $totalOmzetBersihRealisasi) * 100 : 0;
                $percentageLabaKotorAchievement = $labaKotorTarget != 0 ? ($labaKotorRealisasi / $labaKotorTarget) * 100 : 0;
                
                // $reportData[] = [
                //     'nama_akun' => '<strong>Laba Kotor</strong>',
                //     'target' => $labaKotorTarget,
                //     'realisasi' => $labaKotorRealisasi,
                //     'gap' => $labaKotorRealisasi - $labaKotorTarget,
                //     'percentage' => $percentageLabaKotorAchievement, // % Achievement
                //     'keterangan' => ($labaKotorRealisasi - $labaKotorTarget) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
                //     'level' => 1,
                //     'is_induk' => true,
                //     'persen_target' => $persenLabaKotorTarget,     // % vs Target Omzet
                //     'persen_realisasi' => $persenLabaKotorRealisasi, // % vs Realisasi Omzet
                //     'kd_akun' => 'LABAKOTOR' // Identifier khusus untuk Laba Kotor
                // ];
                $labaKotorInserted = true;
                
                Log::info("LABA KOTOR DITAMBAHKAN: Target = " . number_format($labaKotorTarget) . 
                        " (" . round($persenLabaKotorTarget, 2) . "% vs Omzet Target), " .
                        "Realisasi = " . number_format($labaKotorRealisasi) . 
                        " (" . round($persenLabaKotorRealisasi, 2) . "% vs Omzet Realisasi), " .
                        "Achievement = " . round($percentageLabaKotorAchievement, 2) . "%");
            }
            
            // Tambahkan akun ke report data dengan persentase yang sudah diperbaiki
            // PERBAIKAN URUTAN KOLOM: Target (Rp), Persen Target (%), Realisasi (Rp), Persen Realisasi (%), Gap (Rp), Keterangan
                    
            $reportData[] = [
                'nama_akun' => $namaAkun,
                'target' => $target,
                'persen_target' => $persenTarget,       // % vs Target Omzet Bersih
                'realisasi' => $realisasi,
                'persen_realisasi' => $persenRealisasi, // % vs Realisasi Omzet Bersih
                'gap' => $realisasi - $target,
                'percentage' => $percentageAchievement, // % Achievement (Realisasi vs Target)
                'keterangan' => $keteranganStatus,
                'level' => $level,
                'is_induk' => $isInduk,
                'kd_akun' => $kdAkun,
                'urutan' => $account->urutan // SIMPAN URUTAN DARI DATABASE
            ];
            
            Log::debug("AKUN: " . $account->nama_akun . 
                    " | Target: " . number_format($target) . 
                    " | Realisasi: " . number_format($realisasi) . 
                    " | % Target vs Omzet Bersih: " . round($persenTarget, 2) . 
                    " | % Realisasi vs Omzet Bersih: " . round($persenRealisasi, 2) . 
                    " | % Achievement: " . round($percentageAchievement, 2));
            
            // Akumulasi untuk Laba Kotor (hanya akun induk non-biaya sebelum biaya/beban/ziswaf pertama)
            if (!$isBiayaLain && !$isHPP && !$labaKotorInserted && $isInduk) {
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
        
        // Jika tidak ada akun HPP, tetap tambahkan Omzet Bersih di akhir (sebelum biaya pertama)
        if (!$omzetBersihInserted) {
            // Cari apakah ada biaya lain yang bukan HPP
            $foundBiayaLain = false;
            foreach ($sortedAccounts as $account) {
                $isInduk = is_null($account->induk);
                $isHPP = str_contains(strtolower($account->nama_akun), 'hpp') || 
                        str_contains(strtolower($account->nama_akun), 'harga pokok') ||
                        str_contains(strtolower($account->nama_akun), 'biaya pokok');
                
                $isBiayaLain = (str_contains(strtolower($account->nama_akun), 'biaya') || 
                            str_contains(strtolower($account->nama_akun), 'beban')) && !$isHPP;
                
                if (($isHPP || $isBiayaLain) && $isInduk && !$omzetBersihInserted) {
                    // Hitung persentase Omzet Bersih
                    $persenOmzetBersihTarget = $totalOmzetBersihTarget != 0 ? 
                        ($totalOmzetBersihTarget / $totalOmzetBersihTarget) * 100 : 0;
                    $persenOmzetBersihRealisasi = $totalOmzetBersihRealisasi != 0 ? 
                        ($totalOmzetBersihRealisasi / $totalOmzetBersihRealisasi) * 100 : 0;
                    $percentageOmzetBersihAchievement = $totalOmzetBersihTarget != 0 ? 
                        ($totalOmzetBersihRealisasi / $totalOmzetBersihTarget) * 100 : 0;
                    
                    $gapOmzetBersih = $totalOmzetBersihRealisasi - $totalOmzetBersihTarget;
                    $keteranganOmzetBersih = $gapOmzetBersih >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                    
                    $reportData[] = [
                        'nama_akun' => '<strong>Omzet Bersih</strong>',
                        'target' => $totalOmzetBersihTarget,
                        'persen_target' => $persenOmzetBersihTarget,
                        'realisasi' => $totalOmzetBersihRealisasi,
                        'persen_realisasi' => $persenOmzetBersihRealisasi,
                        'gap' => $gapOmzetBersih,
                        'percentage' => $percentageOmzetBersihAchievement,
                        'keterangan' => $keteranganOmzetBersih,
                        'level' => 1,
                        'is_induk' => true,
                        'kd_akun' => 'OMZETBERSIH',
                        'urutan' => 6
                    ];
                    
                    $omzetBersihInserted = true;
                    $foundBiayaLain = true;
                    
                    Log::info("OMZET BERSIH DITAMBAHKAN (SEBELUM BIAYA LAIN): Target = " . number_format($totalOmzetBersihTarget) . 
                            " (" . round($persenOmzetBersihTarget, 2) . "% vs Omzet Target), " .
                            "Realisasi = " . number_format($totalOmzetBersihRealisasi) . 
                            " (" . round($persenOmzetBersihRealisasi, 2) . "% vs Omzet Realisasi), " .
                            "Achievement = " . round($percentageOmzetBersihAchievement, 2) . "%");
                    break;
                }
            }
            
            // Jika tidak ditemukan biaya sama sekali, tambahkan di akhir
            if (!$foundBiayaLain && !$omzetBersihInserted) {
                // Hitung persentase Omzet Bersih
                $persenOmzetBersihTarget = $totalOmzetBersihTarget != 0 ? 
                    ($totalOmzetBersihTarget / $totalOmzetBersihTarget) * 100 : 0;
                $persenOmzetBersihRealisasi = $totalOmzetBersihRealisasi != 0 ? 
                    ($totalOmzetBersihRealisasi / $totalOmzetBersihRealisasi) * 100 : 0;
                $percentageOmzetBersihAchievement = $totalOmzetBersihTarget != 0 ? 
                    ($totalOmzetBersihRealisasi / $totalOmzetBersihTarget) * 100 : 0;
                
                $gapOmzetBersih = $totalOmzetBersihRealisasi - $totalOmzetBersihTarget;
                $keteranganOmzetBersih = $gapOmzetBersih >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                
                $reportData[] = [
                    'nama_akun' => '<strong>Omzet Bersih</strong>',
                    'target' => $totalOmzetBersihTarget,
                    'persen_target' => $persenOmzetBersihTarget,
                    'realisasi' => $totalOmzetBersihRealisasi,
                    'persen_realisasi' => $persenOmzetBersihRealisasi,
                    'gap' => $gapOmzetBersih,
                    'percentage' => $percentageOmzetBersihAchievement,
                    'keterangan' => $keteranganOmzetBersih,
                    'level' => 1,
                    'is_induk' => true,
                    'kd_akun' => 'OMZETBERSIH',
                    'urutan' => 6
                ];
                
                Log::info("OMZET BERSIH DITAMBAHKAN (AKHIR): Target = " . number_format($totalOmzetBersihTarget) . 
                        " (" . round($persenOmzetBersihTarget, 2) . "% vs Omzet Target), " .
                        "Realisasi = " . number_format($totalOmzetBersihRealisasi) . 
                        " (" . round($persenOmzetBersihRealisasi, 2) . "% vs Omzet Realisasi), " .
                        "Achievement = " . round($percentageOmzetBersihAchievement, 2) . "%");
            }
        }
        
        // Jika tidak ada akun biaya/beban/ziswaf, tetap tambahkan Laba Kotor di akhir
        if (!$labaKotorInserted) {
            // Hitung persentase Laba Kotor sesuai aturan baru
            $persenLabaKotorTarget = $totalOmzetBersihTarget != 0 ? ($labaKotorTarget / $totalOmzetBersihTarget) * 100 : 0;
            // PERBAIKAN: % realisasi laba kotor = (Realisasi Laba Kotor / Realisasi Omzet Bersih) * 100
            $persenLabaKotorRealisasi = $totalOmzetBersihRealisasi != 0 ? ($labaKotorRealisasi / $totalOmzetBersihRealisasi) * 100 : 0;
            $percentageLabaKotorAchievement = $labaKotorTarget != 0 ? ($labaKotorRealisasi / $labaKotorTarget) * 100 : 0;
            
            // $reportData[] = [
            //     'nama_akun' => '<strong>Laba Kotor</strong>',
            //     'target' => $labaKotorTarget,
            //     'persen_target' => $persenLabaKotorTarget,
            //     'realisasi' => $labaKotorRealisasi,
            //     'persen_realisasi' => $persenLabaKotorRealisasi,
            //     'gap' => $labaKotorRealisasi - $labaKotorTarget,
            //     'percentage' => $percentageLabaKotorAchievement,
            //     'keterangan' => ($labaKotorRealisasi - $labaKotorTarget) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
            //     'level' => 1,
            //     'is_induk' => true,
            //     'kd_akun' => 'LABAKOTOR'
            // ];
            
            Log::info("LABA KOTOR DITAMBAHKAN (AKHIR): Target = " . number_format($labaKotorTarget) . 
                    " (" . round($persenLabaKotorTarget, 2) . "% vs Omzet Target), " .
                    "Realisasi = " . number_format($labaKotorRealisasi) . 
                    " (" . round($persenLabaKotorRealisasi, 2) . "% vs Omzet Realisasi), " .
                    "Achievement = " . round($percentageLabaKotorAchievement, 2) . "%");
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
                MONTH(t.tanggal) as bulan,
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
            $bulanInt = (int)$data->bulan;
            
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

        // **PERBAIKAN: Update NPM dalam reportData dengan persentase yang benar**
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
                
                // PERBAIKAN: Update persentase terhadap total omzet bersih sesuai aturan baru
                $row['persen_target'] = $totalOmzetBersihTarget != 0 ? 
                    (($npmTotal->total_target ?? 0) / $totalOmzetBersihTarget) * 100 : 0;
                // PERBAIKAN: % realisasi NPM = (Realisasi NPM / Realisasi Omzet Bersih) * 100
                $row['persen_realisasi'] = $totalOmzetBersihRealisasi != 0 ? 
                    (($npmTotal->total_realisasi ?? 0) / $totalOmzetBersihRealisasi) * 100 : 0;
                
                $npmUpdated = true;
                
                Log::info("NPM DIUPDATE: % Target vs Omzet Bersih = " . round($row['persen_target'], 2) . 
                        "%, % Realisasi vs Omzet Bersih = " . round($row['persen_realisasi'], 2) . 
                        "%, % Achievement = " . round($row['percentage'], 2) . "%");
                break;
            }
        }
        unset($row);

        // **PERBAIKAN: Jika NPM tidak ditemukan, tambahkan dengan persentase yang benar**
        if (!$npmUpdated && $npmTotal) {
            // PERBAIKAN: Hitung persentase sesuai aturan baru
            $persenNpmTarget = $totalOmzetBersihTarget != 0 ? 
                (($npmTotal->total_target ?? 0) / $totalOmzetBersihTarget) * 100 : 0;
            // PERBAIKAN: % realisasi NPM = (Realisasi NPM / Realisasi Omzet Bersih) * 100
            $persenNpmRealisasi = $totalOmzetBersihRealisasi != 0 ? 
                (($npmTotal->total_realisasi ?? 0) / $totalOmzetBersihRealisasi) * 100 : 0;
            $percentageNpmAchievement = ($npmTotal->total_target ?? 0) != 0 ? 
                (($npmTotal->total_realisasi ?? 0) / ($npmTotal->total_target ?? 0)) * 100 : 0;

            $reportData[] = [
                'nama_akun' => '<strong>Net Profit Margin</strong>',
                'target' => $npmTotal->total_target ?? 0,
                'persen_target' => $persenNpmTarget,
                'realisasi' => $npmTotal->total_realisasi ?? 0,
                'persen_realisasi' => $persenNpmRealisasi,
                'gap' => $npmTotal->selisih ?? 0,
                'percentage' => $percentageNpmAchievement,
                'keterangan' => ($npmTotal->selisih ?? 0) >= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE',
                'level' => 1,
                'is_induk' => true,
                'kd_akun' => 'NPM',
                'urutan' => 99 // Urutan khusus untuk NPM
            ];
            
            Log::info("NPM DITAMBAHKAN: % Target vs Omzet Bersih = " . round($persenNpmTarget, 2) . 
                    "%, % Realisasi vs Omzet Bersih = " . round($persenNpmRealisasi, 2) . 
                    "%, % Achievement = " . round($percentageNpmAchievement, 2) . "%");
        }
        // ========== DATA PRODUK TERJUAL DAN BEBAN LAINNYA ==========
        
        // --- Bagian untuk data grafik produk terjual ---
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

        // --- PERBAIKAN: Data grafik beban gaji per entitas ---
        $salaryData = [];
        
        // Method 1: Query langsung dari database untuk data gaji
        $gajiQuery = DB::table('realisasi')
            ->select(
                'entitas.nama_entitas',
                DB::raw('SUM(target.nilai) as total_target'),
                DB::raw('SUM(realisasi.nilai) as total_realisasi')
            )
            ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
            ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
            ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
            ->where(function($query) {
                $query->where('akun.nama_akun', 'like', '%gaji%')
                      ->orWhere('akun.nama_akun', 'like', '%upah%')
                      ->orWhere('akun.nama_akun', 'like', '%salary%')
                      ->orWhere('akun.nama_akun', 'like', '%wage%')
                      ->orWhere('akun.nama_akun', 'like', '%honor%')
                      ->orWhere('akun.nama_akun', 'like', '%HR%')
                      ->orWhere('akun.kd_akun', 'like', '05%'); // Biasanya gaji di akun 05 (beban)
            })
            ->whereBetween('realisasi.tanggal', [$startDate, $endDate])
            ->groupBy('entitas.nama_entitas')
            ->get();
        
        // Method 2: Ambil dari SBS data sebagai backup
        foreach ($sbsData as $item) {
            if (isset($item['kd_akun']) && isset($item['nama_akun'])) {
                $isGaji = str_contains(strtolower($item['nama_akun']), 'gaji') ||
                         str_contains(strtolower($item['nama_akun']), 'upah') ||
                         str_contains(strtolower($item['nama_akun']), 'salary') ||
                         str_contains(strtolower($item['nama_akun']), 'wage') ||
                         str_contains(strtolower($item['nama_akun']), 'honor') ||
                         str_contains(strtolower($item['nama_akun']), 'hr') ||
                         str_starts_with($item['kd_akun'], '05');
                
                if ($isGaji) {
                    $entity = $item['nama_entitas'] ?? 'Lain-lain';
                    if (!isset($salaryData[$entity])) {
                        $salaryData[$entity] = [
                            'target' => 0,
                            'realisasi' => 0,
                        ];
                    }
                    $salaryData[$entity]['target'] += $item['nilai_target'] ?? 0;
                    $salaryData[$entity]['realisasi'] += $item['nilai_realisasi'] ?? 0;
                }
            }
        }
        
        // Jika data dari SBS kosong, gunakan data dari query
        if (empty($salaryData) && !$gajiQuery->isEmpty()) {
            foreach ($gajiQuery as $item) {
                $entity = $item->nama_entitas ?? 'Lain-lain';
                $salaryData[$entity] = [
                    'target' => $item->total_target ?? 0,
                    'realisasi' => $item->total_realisasi ?? 0
                ];
            }
        }
        
        // Log untuk debugging
        Log::info("Salary Data dari Query:", ['query_data' => $gajiQuery->toArray()]);
        Log::info("Salary Data Final:", ['final_data' => $salaryData]);

        // ========== MODIFIKASI PERHITUNGAN PERSENTASE BIAYA ==========

        // --- PERBAIKAN: Data tabel gaji per entitas dengan persentase vs omzet bersih per entitas ---
        $salaryTableData = [];
        foreach ($salaryData as $entityName => $data) {
            $target = $data['target'] ?? 0;
            $realisasi = $data['realisasi'] ?? 0;
            
            // Dapatkan omzet bersih untuk entitas ini
            $omzetEntitasTarget = $omzetBersihPerEntitas[$entityName]['target'] ?? $totalOmzetBersihTarget;
            $omzetEntitasRealisasi = $omzetBersihPerEntitas[$entityName]['realisasi'] ?? $totalOmzetBersihRealisasi;
            
            // Hitung persentase terhadap omzet bersih per entitas
            $persenTarget = $omzetEntitasTarget != 0 ? ($target / $omzetEntitasTarget) * 100 : 0;
            $persenRealisasi = $omzetEntitasRealisasi != 0 ? ($realisasi / $omzetEntitasRealisasi) * 100 : 0;
            
            $gap = $realisasi - $target;
            $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
            
            $keterangan = $realisasi <= $target ? 'ACHIEVE' : 'OVER BUDGET';

            $salaryTableData[] = [
                'entitas' => $entityName,
                'target' => $target,
                'persen_target' => $persenTarget,
                'realisasi' => $realisasi,
                'persen_realisasi' => $persenRealisasi,
                'gap' => $gap,
                'percentage' => $percentage,
                'keterangan' => $keterangan,
            ];
        }
        
        // Jika masih kosong, buat data dummy untuk testing
        if (empty($salaryTableData)) {
            Log::warning("Salary Table Data masih kosong, menggunakan data dummy");
            $salaryTableData = [];
            
            // Juga isi salaryData dengan data dummy
            foreach ($salaryTableData as $dummy) {
                $salaryData[$dummy['entitas']] = [
                    'target' => $dummy['target'],
                    'realisasi' => $dummy['realisasi']
                ];
            }
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
            ->orWhere('akun.nama_akun', 'like', '%HR%')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Menggabungkan data target dan realisasi
        $monthlySalaryChartData = [];
        $tempTarget = $monthlySalaryData->keyBy('month');
        $tempRealisasi = $monthlySalaryRealisasi->keyBy('month');

        // --- PERBAIKAN: Data grafik beban gaji bulanan dengan persentase vs omzet bersih bulanan ---
        $monthlySalaryChartDataEnhanced = [];
        for ($i = 1; $i <= $endMonth; $i++) {
            $monthName = $monthNames[$i - 1];
            $targetValue = $tempTarget->get($i)->total_target ?? 0;
            $realisasiValue = $tempRealisasi->get($i)->total_realisasi ?? 0;
            
            // Dapatkan omzet bersih untuk bulan ini
            $omzetBulanTarget = $omzetBersihPerBulan[$monthName]['target'] ?? 0;
            $omzetBulanRealisasi = $omzetBersihPerBulan[$monthName]['realisasi'] ?? 0;
            
            // Hitung persentase terhadap omzet bersih bulanan
            $persenTarget = $omzetBulanTarget != 0 ? ($targetValue / $omzetBulanTarget) * 100 : 0;
            $persenRealisasi = $omzetBulanRealisasi != 0 ? ($realisasiValue / $omzetBulanRealisasi) * 100 : 0;
            
            $monthlySalaryChartDataEnhanced[$monthName] = [
                'target' => $targetValue,
                'realisasi' => $realisasiValue,
                'persen_target' => $persenTarget,
                'persen_realisasi' => $persenRealisasi,
            ];
        }

        // Ganti data lama dengan data yang enhanced
        $monthlySalaryChartData = $monthlySalaryChartDataEnhanced;
        
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
        
        // --- PERBAIKAN: Data biaya operasional per entitas dengan persentase vs omzet bersih per entitas ---
        foreach ($operationalCostData as $entity => &$data) {
            $target = $data['target'];
            $realisasi = $data['realisasi'];
            
            // Dapatkan omzet bersih untuk entitas ini
            $omzetEntitasTarget = $omzetBersihPerEntitas[$entity]['target'] ?? $totalOmzetBersihTarget;
            $omzetEntitasRealisasi = $omzetBersihPerEntitas[$entity]['realisasi'] ?? $totalOmzetBersihRealisasi;
            
            // Hitung persentase terhadap omzet bersih per entitas
            $persenTarget = $omzetEntitasTarget != 0 ? ($target / $omzetEntitasTarget) * 100 : 0;
            $persenRealisasi = $omzetEntitasRealisasi != 0 ? ($realisasi / $omzetEntitasRealisasi) * 100 : 0;
            
            $gap = $realisasi - $target;
            $percentage = $target != 0 ? (($realisasi - $target) / $target) * 100 : 0;
            $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
            
            $data['gap'] = $gap;
            $data['percentage'] = $percentage;
            $data['keterangan'] = $keterangan;
            $data['persen_target'] = $persenTarget;
            $data['persen_realisasi'] = $persenRealisasi;
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
            ->where('akun.nama_akun', 'like', '%Operasional%')
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
            ->where('akun.nama_akun', 'like', '%Operasional%')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        $monthlyOperationalCostChartData = [];
        $tempOperationalCostTarget = $monthlyOperationalCostTarget->keyBy('month');
        $tempOperationalCostRealisasi = $monthlyOperationalCostRealisasi->keyBy('month');

        // --- PERBAIKAN: Data grafik biaya operasional bulanan dengan persentase vs omzet bersih bulanan ---
        $monthlyOperationalCostChartDataEnhanced = [];
        for ($i = 1; $i <= $endMonth; $i++) {
            $monthName = $monthNames[$i - 1];
            $targetValue = $tempOperationalCostTarget->get($i)->total_target ?? 0;
            $realisasiValue = $tempOperationalCostRealisasi->get($i)->total_realisasi ?? 0;
            
            // Dapatkan omzet bersih untuk bulan ini
            $omzetBulanTarget = $omzetBersihPerBulan[$monthName]['target'] ?? 0;
            $omzetBulanRealisasi = $omzetBersihPerBulan[$monthName]['realisasi'] ?? 0;
            
            // Hitung persentase terhadap omzet bersih bulanan
            $persenTarget = $omzetBulanTarget != 0 ? ($targetValue / $omzetBulanTarget) * 100 : 0;
            $persenRealisasi = $omzetBulanRealisasi != 0 ? ($realisasiValue / $omzetBulanRealisasi) * 100 : 0;
            
            $monthlyOperationalCostChartDataEnhanced[$monthName] = [
                'target' => $targetValue,
                'realisasi' => $realisasiValue,
                'persen_target' => $persenTarget,
                'persen_realisasi' => $persenRealisasi,
            ];
        }

        // Ganti data lama dengan data yang enhanced
        $monthlyOperationalCostChartData = $monthlyOperationalCostChartDataEnhanced;

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

        // --- PERBAIKAN: Data biaya pemasaran per entitas dengan persentase vs omzet bersih per entitas ---
        foreach ($marketingCostData as $entity => &$data) {
            $target = $data['target'];
            $realisasi = $data['realisasi'];
            
            // Dapatkan omzet bersih untuk entitas ini
            $omzetEntitasTarget = $omzetBersihPerEntitas[$entity]['target'] ?? $totalOmzetBersihTarget;
            $omzetEntitasRealisasi = $omzetBersihPerEntitas[$entity]['realisasi'] ?? $totalOmzetBersihRealisasi;
            
            // Hitung persentase terhadap omzet bersih per entitas
            $persenTarget = $omzetEntitasTarget != 0 ? ($target / $omzetEntitasTarget) * 100 : 0;
            $persenRealisasi = $omzetEntitasRealisasi != 0 ? ($realisasi / $omzetEntitasRealisasi) * 100 : 0;
            
            $gap = $realisasi - $target;
            $percentage = $target != 0 ? (($realisasi - $target) / $target) * 100 : 0;
            $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
            
            $data['gap'] = $gap;
            $data['percentage'] = $percentage;
            $data['keterangan'] = $keterangan;
            $data['persen_target'] = $persenTarget;
            $data['persen_realisasi'] = $persenRealisasi;
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
            ->where(function($query) {
                $query->where('akun.nama_akun', 'like', '%Biaya Lain%')
                      ->orWhere('akun.nama_akun', 'like', '%Lain-lain%')
                      ->orWhere('akun.kd_akun', 'like', '07%'); // Akun biaya lain-lain
            })
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
            ->where(function($query) {
                $query->where('akun.nama_akun', 'like', '%Biaya Lain%')
                      ->orWhere('akun.nama_akun', 'like', '%Lain-lain%')
                      ->orWhere('akun.kd_akun', 'like', '07%'); // Akun biaya lain-lain
            })
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        $monthlyOtherCostChartData = [];
        $tempOtherCostTarget = $monthlyOtherCostTarget->keyBy('month');
        $tempOtherCostRealisasi = $monthlyOtherCostRealisasi->keyBy('month');
        
        // --- PERBAIKAN: Data grafik biaya lain-lain bulanan dengan persentase vs omzet bersih bulanan ---
        $monthlyOtherCostChartDataEnhanced = [];
        for ($i = 1; $i <= $endMonth; $i++) {
            $monthName = $monthNames[$i - 1];
            $targetValue = $tempOtherCostTarget->get($i)->total_target ?? 0;
            $realisasiValue = $tempOtherCostRealisasi->get($i)->total_realisasi ?? 0;
            
            // Dapatkan omzet bersih untuk bulan ini
            $omzetBulanTarget = $omzetBersihPerBulan[$monthName]['target'] ?? 0;
            $omzetBulanRealisasi = $omzetBersihPerBulan[$monthName]['realisasi'] ?? 0;
            
            // Hitung persentase terhadap omzet bersih bulanan
            $persenTarget = $omzetBulanTarget != 0 ? ($targetValue / $omzetBulanTarget) * 100 : 0;
            $persenRealisasi = $omzetBulanRealisasi != 0 ? ($realisasiValue / $omzetBulanRealisasi) * 100 : 0;
            
            $monthlyOtherCostChartDataEnhanced[$monthName] = [
                'target' => $targetValue,
                'realisasi' => $realisasiValue,
                'persen_target' => $persenTarget,
                'persen_realisasi' => $persenRealisasi,
            ];
        }

        // Ganti data lama dengan data yang enhanced
        $monthlyOtherCostChartData = $monthlyOtherCostChartDataEnhanced;
        
        // --- PERBAIKAN: Data biaya lain-lain per entitas ---
        $otherCostData = [];
        
        // Method 1: Query langsung dari database
        $otherCostQuery = DB::table('realisasi')
            ->select(
                'entitas.nama_entitas',
                DB::raw('SUM(target.nilai) as total_target'),
                DB::raw('SUM(realisasi.nilai) as total_realisasi')
            )
            ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
            ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
            ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
            ->where(function($query) {
                $query->where('akun.nama_akun', 'like', '%Biaya Lain%')
                      ->orWhere('akun.nama_akun', 'like', '%Lain-lain%')
                      ->orWhere('akun.kd_akun', 'like', '07%');
            })
            ->whereBetween('realisasi.tanggal', [$startDate, $endDate])
            ->groupBy('entitas.nama_entitas')
            ->get();
        
        // Method 2: Juga ambil dari SBS data sebagai backup
        foreach ($sbsData as $item) {
            if (isset($item['kd_akun'])) {
                $isOtherCost = str_starts_with($item['kd_akun'], '07') || 
                              str_contains(strtolower($item['nama_akun'] ?? ''), 'biaya lain') ||
                              str_contains(strtolower($item['nama_akun'] ?? ''), 'lain-lain');
                
                if ($isOtherCost) {
                    $entity = $item['nama_entitas'] ?? 'Lain-lain';
                    if (!isset($otherCostData[$entity])) {
                        $otherCostData[$entity] = [
                            'target' => 0,
                            'realisasi' => 0
                        ];
                    }
                    $otherCostData[$entity]['target'] += $item['nilai_target'] ?? 0;
                    $otherCostData[$entity]['realisasi'] += $item['nilai_realisasi'] ?? 0;
                }
            }
        }
        
        // Jika data dari SBS kosong, gunakan data dari query
        if (empty($otherCostData) && !$otherCostQuery->isEmpty()) {
            foreach ($otherCostQuery as $item) {
                $entity = $item->nama_entitas ?? 'Lain-lain';
                $otherCostData[$entity] = [
                    'target' => $item->total_target ?? 0,
                    'realisasi' => $item->total_realisasi ?? 0
                ];
            }
        }
        
        // Log untuk debugging
        Log::info("Other Cost Data:", ['data' => $otherCostData]);
        
        // --- PERBAIKAN: Data tabel biaya lain-lain dengan persentase vs omzet bersih per entitas ---
        $otherCostTableData = [];
        foreach ($otherCostData as $entityName => $data) {
            $target = $data['target'] ?? 0;
            $realisasi = $data['realisasi'] ?? 0;
            
            // Dapatkan omzet bersih untuk entitas ini
            $omzetEntitasTarget = $omzetBersihPerEntitas[$entityName]['target'] ?? $totalOmzetBersihTarget;
            $omzetEntitasRealisasi = $omzetBersihPerEntitas[$entityName]['realisasi'] ?? $totalOmzetBersihRealisasi;
            
            // Hitung persentase terhadap omzet bersih per entitas
            $persenTarget = $omzetEntitasTarget != 0 ? ($target / $omzetEntitasTarget) * 100 : 0;
            $persenRealisasi = $omzetEntitasRealisasi != 0 ? ($realisasi / $omzetEntitasRealisasi) * 100 : 0;
            
            $gap = $realisasi - $target;
            $percentage = $target != 0 ? ($realisasi / $target) * 100 : 0;
            $keterangan = $gap <= 0 ? 'ACHIEVE' : 'TIDAK ACHIEVE';

            $otherCostTableData[] = [
                'entitas' => $entityName,
                'target' => $target,
                'persen_target' => $persenTarget,
                'realisasi' => $realisasi,
                'persen_realisasi' => $persenRealisasi,
                'gap' => $gap,
                'percentage' => $percentage,
                'keterangan' => $keterangan,
            ];
        }
        
        // Jika masih kosong, buat data dummy untuk testing
        if (empty($otherCostTableData)) {
            Log::warning("Other Cost Table Data masih kosong, menggunakan data dummy");
            $otherCostTableData = [];
        }

        // Pre-fetch semua judul grafik sekaligus
        $grafikTitles = DB::table('grafik')
            ->whereIn('title', range(1, 30))
            ->pluck('title', 'title');

        $analisisData = [
            [
                'title' => 1,
                'title' => $grafikTitles[1] ?? 'Tidak Ditemukan',
                'data' => $reportData,
            ],
            [
                'title' => 2,
                'title' => $grafikTitles[2] ?? 'Tidak Ditemukan',
                'data' => $monthlyDailyRealisasi,
            ],
            [
                'title' => 3,
                'title' => $grafikTitles[3] ?? 'Tidak Ditemukan',
                'data' => $summaryData,
            ],
            [
                'title' => 4,
                'title' => $grafikTitles[4] ?? 'Tidak Ditemukan',
                'data' => $monthlySummaryRealisasi,
            ],
            [
                'title' => 5,
                'title' => $grafikTitles[5] ?? 'Tidak Ditemukan',
                'data' => $yearlyMonthlyRealisasi,
            ],
            [
                'title' => 6,
                'title' => $grafikTitles[6] ?? 'Tidak Ditemukan',
                'data' => $entityDailyRealisasi,
            ],
            [
                'title' => 7,
                'title' => $grafikTitles[7] ?? 'Tidak Ditemukan',
                'data' => $entitySummaryData,
            ],
            [
                'title' => 9,
                'title' => $grafikTitles[9] ?? 'Tidak Ditemukan',
                'data' => $monthlyHargaPokokChartData,
            ],
            [
                'title' => 10,
                'title' => $grafikTitles[10] ?? 'Tidak Ditemukan',
                'data' => $hargaPokokTableData,
            ],
            [
                'title' => 12,
                'title' => $grafikTitles[12] ?? 'Tidak Ditemukan',
                'data' => $monthlyProductSales,
            ],
            [
                'title' => 13,
                'title' => $grafikTitles[13] ?? 'Tidak Ditemukan',
                'data' => $topProductsSalesData,
            ],
            [
                'title' => 14,
                'title' => $grafikTitles[14] ?? 'Tidak Ditemukan',
                'data' => $salaryTableData,
            ],
            [
                'title' => 16,
                'title' => $grafikTitles[16] ?? 'Tidak Ditemukan',
                'data' => $monthlySalaryChartData,
            ],
            [
                'title' => 17,
                'title' => $grafikTitles[17] ?? 'Tidak Ditemukan',
                'data' => $operationalCostData,
            ],
            [
                'title' => 18,
                'title' => $grafikTitles[18] ?? 'Tidak Ditemukan',
                'data' => $monthlyOperationalCostChartData,
            ],
            [
                'title' => 19,
                'title' => $grafikTitles[19] ?? 'Tidak Ditemukan',
                'data' => $marketingCostData,
            ],
            [
                'title' => 20,
                'title' => $grafikTitles[20] ?? 'Tidak Ditemukan',
                'data' => $monthlyOverallChartData,
            ],
            [
                'title' => 21,
                'title' => $grafikTitles[21] ?? 'Tidak Ditemukan',
                'data' => $monthlyOtherCostChartData,
            ],
            [
                'title' => 22,
                'title' => $grafikTitles[22] ?? 'Tidak Ditemukan',
                'data' => $otherCostTableData,
            ],
            [
                'title' => 24,
                'title' => $grafikTitles[24] ?? 'Tidak Ditemukan',
                'data' => $npmEntitasTableData,
            ],
            [
                'title' => 25,
                'title' => $grafikTitles[25] ?? 'Tidak Ditemukan',
                'data' => $npmChartData,
            ],
        ];

        foreach ($analisisData as $data) {
            try {
                // Sanitize and prepare data for storage
                $storageData = $this->prepareDataForStorage($data['data']);
                
                HasilAnalisis::updateOrCreate(
                    [
                        'kd_laporan' => $kd_laporan,
                        'title' => $data['title'],
                        'title' => $data['title']
                    ],
                    [
                        'data' => $storageData,
                        'analisis' => null
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Failed to save analysis data for title {$data['title']}: " . $e->getMessage());
                continue;
            }
        }

        return redirect()->route('laporan.view')->with('success','Data Laporan Telah tersimpan');
    }


    /**
     * Prepare data for storage by removing circular references and simplifying complex structures
     */
    private function prepareDataForStorage($data)
    {
        if (empty($data)) {
            return [];
        }
        
        // If it's already a JSON string, return as is
        if (is_string($data)) {
            return $data;
        }
        
        // Convert to array if it's an object
        if (is_object($data)) {
            $data = $this->objectToArray($data);
        }
        
        // Remove any potential circular references
        $data = $this->removeCircularReferences($data);
        
        // Limit depth and size for large datasets
        $data = $this->limitDataSize($data);
        
        return $data;
    }

    /**
     * Convert object to array safely
     */
    private function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }
        
        return array_map(function ($item) {
            if (is_object($item) || is_array($item)) {
                return $this->objectToArray($item);
            }
            return $item;
        }, (array) $object);
    }

    /**
     * Remove circular references from data
     */
    private function removeCircularReferences($data, $depth = 0, $maxDepth = 10)
    {
        if ($depth > $maxDepth) {
            return null;
        }
        
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = $this->removeCircularReferences($value, $depth + 1, $maxDepth);
                }
            }
        } elseif (is_object($data)) {
            // Convert objects to arrays to avoid circular references
            $data = $this->objectToArray($data);
            $data = $this->removeCircularReferences($data, $depth + 1, $maxDepth);
        }
        
        return $data;
    }

    /**
     * Limit data size to prevent storage issues
     */
    private function limitDataSize($data, $maxItems = 1000)
    {
        if (is_array($data)) {
            if (count($data) > $maxItems) {
                // If array is too large, take only first $maxItems elements
                $data = array_slice($data, 0, $maxItems);
            }
            
            foreach ($data as $key => &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = $this->limitDataSize($value, $maxItems);
                }
            }
        }
        
        return $data;
    }
    // ... (fungsi-fungsi lainnya tetap sama)
    // Karena keterbatasan panjang, saya tidak mengubah fungsi-fungsi lainnya

    // ========== FUNGSI TAMBAHAN YANG DIBUTUHKAN ==========

    /**
     * Mendapatkan akun omzet utama dari reportData
     */
    private function getOmzetAccount($reportData)
    {
        foreach ($reportData as $item) {
            $cleanNama = strip_tags($item['nama_akun']);
            $cleanNamaLower = strtolower($cleanNama);
            
            // Identifikasi akun omzet/pendapatan
            if (str_contains($cleanNamaLower, 'omzet') || 
                str_contains($cleanNamaLower, 'pendapatan') ||
                str_contains($cleanNamaLower, 'penjualan')) {
                return $item;
            }
        }
        
        // Jika tidak ditemukan, cari berdasarkan kode akun
        foreach ($reportData as $item) {
            if (isset($item['kd_akun']) && str_starts_with($item['kd_akun'], '01')) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * Mendapatkan total omzet dari semua akun pendapatan
     */
    private function getTotalOmzet($reportData)
    {
        $totalTarget = 0;
        $totalRealisasi = 0;
        
        foreach ($reportData as $item) {
            if (isset($item['kd_akun']) && str_starts_with($item['kd_akun'], '01')) {
                $totalTarget += $item['target'] ?? 0;
                $totalRealisasi += $item['realisasi'] ?? 0;
            }
        }
        
        return [
            'target' => $totalTarget,
            'realisasi' => $totalRealisasi
        ];
    }

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

    /**
     * Helper functions untuk format angka
     */
    public static function formatInJutaan($number)
    {
        return number_format($number / 1000000, 2, ',', '.');
    }
    
    public static function formatFullNumber($number)
    {
        return number_format($number, 0, ',', '.');
    }

    /**
     * Fungsi untuk menghitung statistik omzet
     */
    private function calculateOmzetStats($chartData)
    {
        $stats = [];
        $totalAll = 0;
        $countAll = 0;
        
        foreach ($chartData as $year => $monthlyData) {
            $yearTotal = array_sum($monthlyData);
            $yearAverage = count(array_filter($monthlyData)) > 0 ? $yearTotal / count(array_filter($monthlyData)) : 0;
            
            $stats[$year] = [
                'total' => $yearTotal,
                'average' => $yearAverage,
                'max' => max($monthlyData),
                'min' => min(array_filter($monthlyData)) ?: 0
            ];
            
            $totalAll += $yearTotal;
            $countAll += count(array_filter($monthlyData));
        }
        
        $stats['total_all'] = $totalAll;
        $stats['average_all'] = $countAll > 0 ? $totalAll / $countAll : 0;
        
        // Hitung growth rate
        $years = array_keys($chartData);
        sort($years);
        if (count($years) >= 2) {
            $latestYear = end($years);
            $previousYear = prev($years);
            
            $latestTotal = $stats[$latestYear]['total'] ?? 0;
            $previousTotal = $stats[$previousYear]['total'] ?? 0;
            
            $stats['latest_growth_rate'] = $previousTotal > 0 ? 
                (($latestTotal - $previousTotal) / $previousTotal) * 100 : 0;
        } else {
            $stats['latest_growth_rate'] = 0;
        }
        
        return $stats;
    }


    private function grafik01($kd_laporan, $title)
    {
        // Decode title
        $title = urldecode($title);
        
        // Ambil data dari database
        $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
            ->where('title', $title)
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


    public function grafik02($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik03($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $data = [];
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
            return [];
        }
    }

    public function grafik10($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik11($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
                'title' => $title
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik20: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [];
        }
    }


    public function grafik04($kd_laporan, $title)
    {
        // Ambil data dari database
        try {
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
            ->where('title', $title)
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
            return [];
        }
    }

    
    public function grafik05($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }
    

    //////////////////////////////////

    public function grafik06($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $dailyData = [];
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
            return [];
        }
    }


    public function grafik09($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik07($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik13($kd_laporan, $title)
    {
        try {

            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
                'title' => $title
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik13: " . $e->getMessage());
            
            // Return array dengan data default jika terjadi error
            return [];
        }
    }


    public function grafik18($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik14($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik15($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }
    
    public function grafik16($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik19($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }

    public function grafik17($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik20($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik24($kd_laporan, $title)
    {
        try {
            // Ambil data dari database berdasarkan kd_laporan dan title
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
            return [];
        }
    }


    public function grafik21($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
                'title' => $title
            ];

        } catch (\Exception $e) {
            Log::error("Error in BiayaBulananController analisaGrafik: " . $e->getMessage());
            
            // Kembalikan array dengan data default jika terjadi error
            return [];
        }
    }


    public function grafik22($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                $chartData = [];
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
                'title' => $title
            ];

        } catch (\Exception $e) {
            Log::error("Error in BiayaLainLainController analisaGrafik: " . $e->getMessage());
            
            // Kembalikan array dengan data default jika terjadi error
            return [];
        }
    }


    public function grafik25($kd_laporan, $title)
    {
        try {
            // Ambil data dari database
            $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                ->where('title', $title)
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
                'title' => $title
            ];

        } catch (\Exception $e) {
            Log::error("Error in grafik25: " . $e->getMessage());
            // Kembalikan array dengan data default jika terjadi error
            return [
            ];
        }
    }
    
}
<?php

namespace App\Http\Controllers\detailLaporan;

use App\Models\Akun;
use App\Models\Realisasi;
use App\Models\Target;
use App\Models\Entitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class detailLaporanController extends Controller
{
    /**
     * Menampilkan view utama untuk laporan.
     */
    /**
     * Menampilkan view utama untuk laporan.
     */
    public function index()
    {
        // Mengambil daftar tahun yang unik dari tabel 'target'
        $years = Target::select('tahun')->distinct()->orderBy('tahun', 'desc')->pluck('tahun');
        
        // Mengambil semua entitas
        $entitas = Entitas::orderBy('nama_entitas')->get();
        
        // Membuat daftar bulan dalam format nama
        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[] = Carbon::createFromDate(null, $i, null)->locale('id')->monthName;
        }

        return view('detailLaporan.view', compact('years', 'entitas', 'bulanList'));
    }

public function getDataTableLaporan(Request $request)
{
    // Mendapatkan filter dari request
    $selectedBulan = $request->input('bulan', []);
    $selectedTahun = $request->input('tahun', date('Y'));
    $selectedEntitas = $request->input('entitas');

    // Mapping nama bulan Indonesia ke angka bulan
    $bulanMap = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
    ];

    // Pastikan filter bulan berupa array angka
    $bulanAngka = [];
    if (!empty($selectedBulan)) {
        foreach ($selectedBulan as $bulan) {
            if (is_numeric($bulan)) {
                $bulanAngka[] = (int) $bulan;
            } else {
                // Cari key yang sesuai (case insensitive)
                $key = strtolower($bulan);
                if (isset($bulanMap[$key])) {
                    $bulanAngka[] = $bulanMap[$key];
                }
            }
        }
    } else {
        // Jika tidak ada bulan yang dipilih, ambil semua bulan (1-12)
        $bulanAngka = range(1, 12);
    }

    // Ambil semua akun dengan urutan yang benar
    $akuns = DB::table('akun')
        ->orderBy('urutan', 'asc')
        ->get();

    // Query untuk mengambil data per akun
    $dataLaporan = [];
    $totalOmzetTarget = 0;
    $totalOmzetRealisasi = 0;

    foreach ($akuns as $akun) {
        // QUERY 1: Target untuk akun ini
        $targetQuery = DB::table('target')
            ->select(DB::raw('COALESCE(SUM(nilai), 0) as total_target'))
            ->where('tahun', $selectedTahun)
            ->whereIn('bulan', $bulanAngka)
            ->where('kd_akun', $akun->kd_akun);
        
        if ($selectedEntitas) {
            $targetQuery->where('kd_entitas', $selectedEntitas);
        }
        
        $targetResult = $targetQuery->first();
        $totalTarget = $targetResult->total_target ?? 0;

        // QUERY 2: Realisasi untuk akun ini (melalui kd_target)
        $realisasiQuery = DB::table('realisasi')
            ->select(DB::raw('COALESCE(SUM(realisasi.nilai), 0) as total_realisasi'))
            ->whereIn('realisasi.kd_target', function($query) use ($selectedTahun, $bulanAngka, $akun, $selectedEntitas) {
                $query->select('kd_target')
                    ->from('target')
                    ->where('tahun', $selectedTahun)
                    ->whereIn('bulan', $bulanAngka)
                    ->where('kd_akun', $akun->kd_akun);
                    
                if ($selectedEntitas) {
                    $query->where('kd_entitas', $selectedEntitas);
                }
            });
        
        $realisasiResult = $realisasiQuery->first();
        $totalRealisasi = $realisasiResult->total_realisasi ?? 0;

        // Jika kedua query mengembalikan 0, coba approach alternatif
        if ($totalTarget == 0 && $totalRealisasi == 0) {
            // Coba query alternatif untuk realisasi
            $realisasiAltQuery = DB::table('realisasi')
                ->join('target', 'realisasi.kd_target', '=', 'target.kd_target')
                ->select(DB::raw('COALESCE(SUM(realisasi.nilai), 0) as total_realisasi'))
                ->where('target.tahun', $selectedTahun)
                ->whereIn('target.bulan', $bulanAngka)
                ->where('target.kd_akun', $akun->kd_akun);
            
            if ($selectedEntitas) {
                $realisasiAltQuery->where('target.kd_entitas', $selectedEntitas);
            }
            
            $realisasiAltResult = $realisasiAltQuery->first();
            $totalRealisasi = $realisasiAltResult->total_realisasi ?? 0;
        }

        // Sesuaikan dengan operasi (+/-)
        if ($akun->operasi === '-') {
            $totalTarget = -$totalTarget;
            $totalRealisasi = -$totalRealisasi;
        }

        // Simpan total omzet untuk perhitungan persentase (akun pertama diasumsikan sebagai omzet)
        if ($akun->urutan == 1) {
            $totalOmzetTarget = $totalTarget;
            $totalOmzetRealisasi = $totalRealisasi;
        }

        // Hitung persentase dan gap
        $gap = $totalRealisasi - $totalTarget;
        
        // Hitung persentase realisasi terhadap target
        $persentaseRealisasi = ($totalTarget != 0) ? round(($totalRealisasi / $totalTarget) * 100, 2) : 0;
        
        // Hitung persentase gap terhadap target
        $persentaseGap = ($totalTarget != 0) ? round(($gap / $totalTarget) * 100, 2) : 0;
        
        // Tentukan keterangan berdasarkan jenis akun
        $keterangan = $this->getKeterangan($persentaseRealisasi, $akun->operasi, $akun->urutan);

        $dataLaporan[] = [
            'bu_an' => $akun->nama_akun,
            'target' => $totalTarget,
            'target_persen' => 0, // Akan diisi nanti
            'realisasi' => $totalRealisasi,
            'realisasi_persen' => 0, // Akan diisi nanti
            'gap' => $gap,
            'gap_persen' => $persentaseGap,
            'keterangan' => $keterangan,
            'urutan' => $akun->urutan,
            'operasi' => $akun->operasi
        ];
    }

    // Hitung persentase untuk setiap akun berdasarkan total omzet
    if ($totalOmzetTarget != 0 || $totalOmzetRealisasi != 0) {
        foreach ($dataLaporan as &$item) {
            $item['target_persen'] = ($totalOmzetTarget != 0) ? round(($item['target'] / $totalOmzetTarget) * 100, 2) : 0;
            $item['realisasi_persen'] = ($totalOmzetRealisasi != 0) ? round(($item['realisasi'] / $totalOmzetRealisasi) * 100, 2) : 0;
        }
    }

    // Urutkan data berdasarkan urutan akun
    usort($dataLaporan, function($a, $b) {
        return $a['urutan'] <=> $b['urutan'];
    });

    // Untuk debugging, return juga informasi filter
    return response()->json([
        'data' => $dataLaporan,
        'debug' => [
            'filter' => [
                'tahun' => $selectedTahun,
                'bulan' => $bulanAngka,
                'entitas' => $selectedEntitas
            ],
            'total_akun' => count($akuns)
        ]
    ]);
}

    /**
     * Menentukan keterangan berdasarkan persentase, operasi, dan urutan akun.
     */
    private function getKeterangan($persentase, $operasi, $urutan)
    {
        // Akun pertama (urutan 1) diasumsikan sebagai omzet/pendapatan
        if ($urutan == 1) {
            return $persentase >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        }
        
        // Untuk akun dengan operasi '+' (pendapatan lain)
        if ($operasi === '+') {
            return $persentase >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
        }
        
        // Untuk akun dengan operasi '-' (biaya)
        return $persentase > 100 ? 'OVERBUDGET' : 'EFISIEN';
    }
}
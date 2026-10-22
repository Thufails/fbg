<?php

namespace App\Http\Controllers\target;

use App\Models\Target;
use App\Http\Controllers\Controller;
use App\Models\Akun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use function Laravel\Prompts\table;

class targetController extends Controller
{
    // Bulan dalam Bahasa Indonesia
    private $bulanIndonesia = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    // Hari dalam Bahasa Indonesia
    private $hariIndonesia = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
         try {
           // DB::enableQueryLog();   

            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $kd_akun = $request->input('kd_akun', null);
            $kd_entitas = $request->input('kd_entitas', null);

            // Jika kd_akun kosong, ambil dari tabel akun baris pertama
            if (empty($kd_akun)) {
                $firstAkun = DB::table('akun')
                    ->select('kd_akun','nama_akun')
                    ->orderBy('kd_akun')
                    ->first();                
                if ($firstAkun) {
                    $kd_akun = $firstAkun->kd_akun;
                    $nama_akun = $firstAkun->nama_akun;
                }
            }
            else{
                $firstAkun = DB::table('akun')
                    ->select('kd_akun','nama_akun')
                    ->where('kd_akun',$kd_akun)
                    ->first();                
                if ($firstAkun) {
                    $kd_akun = $firstAkun->kd_akun;
                    $nama_akun = $firstAkun->nama_akun;
                }
            }

            $query = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->leftJoin('konversi_tanggal', 'target.tanggal', '=', 'konversi_tanggal.tanggal') // Join dengan konversi_tanggal
                ->select(
                    'target.*',
                    'akun.nama_akun',
                    'entitas.nama_entitas',
                    DB::raw("CONCAT(konversi_tanggal.hari_h, ' ', konversi_tanggal.bulan_h, ' ', konversi_tanggal.tahun_h) as tanggal_hijriah")
                );

            if ($startDate && $endDate) {
                $query->whereBetween('target.tanggal', [$startDate, $endDate]);
            }
            else{
                $currentYear = date('Y');
                $startOfYear = $currentYear . '-01-01';
                $endOfYear = $currentYear . '-12-31';
                $query->whereBetween('target.tanggal', [$startOfYear, $endOfYear]);
            }

            if ($kd_akun) {
                $query->where('target.kd_akun', $kd_akun);
            }

            if ($kd_entitas) {
                $query->where('target.kd_entitas', $kd_entitas);
            }

            $perPage = 100;
            $target = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

           // dd($query->toSql(), $query->getBindings());

            // Get account list for filter dropdown
            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->where('aktif', '=', 'Y')
                ->orderBy('urutan','asc')
                ->get();

            // Get entitas list for filter dropdown
            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->orderBy('nama_entitas','asc')
                ->get();

            
            // HITUNG TOTAL TARGET
            $totalQuery = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas');

            // if ($startDate && $endDate) {
            //     $totalQuery->whereBetween('target.tanggal', [$startDate, $endDate]);
            // }

            if ($startDate && $endDate) {
                $totalQuery->whereBetween('target.tanggal', [$startDate, $endDate]);
            }
            else{
                $currentYear = date('Y');
                $startOfYear = $currentYear . '-01-01';
                $endOfYear = $currentYear . '-12-31';
                $totalQuery->whereBetween('target.tanggal', [$startOfYear, $endOfYear]);
            }

            if ($kd_akun) {
                $totalQuery->where('target.kd_akun', $kd_akun);
            }

            if ($kd_entitas) {
                $totalQuery->where('target.kd_entitas', $kd_entitas);
            }

            // Hitung total target
            $totalTarget = $totalQuery->sum('target.nilai');

           // dd($query);
           

            return view('target.view', compact(
                'target', 
                'startDate', 
                'endDate', 
                'kd_akun', 
                'nama_akun',
                'kd_entitas', 
                'akunList', 
                'entitasList',
                'totalTarget'
            ));
            
        } catch (\Exception $e) {
            return redirect()->route('target.view')
                ->with('error', 'Gagal mengambil data: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $akunList = DB::table('akun')->where('aktif', '=', 'Y')->orderBy('urutan', 'asc')->get();
            $entitasList = DB::table('entitas')->orderBy('nama_entitas', 'asc')->get();
            
            return view('target.insert', compact('akunList', 'entitasList'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       // dd($request);
        try {
            $validated = $request->validate([
                'kd_entitas' => 'required|string|max:5',
                'kd_akun' => 'required|string|max:15',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'nilai' => 'required|numeric',
                'jenis_periode' => 'required|in:harian,bulanan'
            ]);

            // Convert nilai to integer (remove any formatting)
            $validated['nilai'] = (int) str_replace(['.', ','], '', $validated['nilai']);

            $startDate = Carbon::parse($validated['tanggal_awal']);
            $endDate = Carbon::parse($validated['tanggal_akhir']);
            $nilai = $validated['nilai'];
            $jenisPeriode = $validated['jenis_periode'];

            // Hitung jumlah hari dalam periode
            $jumlahHari = $startDate->diffInDays($endDate) + 1;

            // Siapkan data untuk insert
            $dataToInsert = [];
            $currentDate = $startDate->copy();

            if ($jenisPeriode === 'harian') {
                // Harian - nilai sama setiap hari
                while ($currentDate <= $endDate) {
                    $dataToInsert[] = [
                        'kd_entitas' => $validated['kd_entitas'],
                        'kd_akun' => $validated['kd_akun'],
                        'tanggal' => $currentDate->format('Y-m-d'),
                        'nilai' => $nilai,
                        'hari' => $this->hariIndonesia[$currentDate->englishDayOfWeek] ?? $currentDate->englishDayOfWeek,
                        'bulan' => $this->bulanIndonesia[$currentDate->month] ?? $currentDate->monthName,
                        'tahun' => $currentDate->year,
                        'keterangan' => $request->keterangan,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $currentDate->addDay();
                }
            } else {
                // Bulanan - nilai dibagi rata per hari (dibulatkan)
                $nilaiPerHari = round($nilai / $jumlahHari);
                
                while ($currentDate <= $endDate) {
                    $dataToInsert[] = [
                        'kd_entitas' => $validated['kd_entitas'],
                        'kd_akun' => $validated['kd_akun'],
                        'tanggal' => $currentDate->format('Y-m-d'),
                        'nilai' => $nilaiPerHari,
                        'hari' => $this->hariIndonesia[$currentDate->englishDayOfWeek] ?? $currentDate->englishDayOfWeek,
                        'bulan' => $this->bulanIndonesia[$currentDate->month] ?? $currentDate->monthName,
                        'tahun' => $currentDate->year,
                        'keterangan' => $request->keterangan,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $currentDate->addDay();
                }
            }

            // Generate UUID untuk BigQuery dan insert data
            if (config('database.default') === 'bigquery') {
                $dataToInsert = array_map(function ($item) {
                    $item['kd_target'] = Str::uuid()->toString();
                    return $item;
                }, $dataToInsert);
                
                DB::table('target')->insert($dataToInsert);
            } else {
                DB::table('target')->insert($dataToInsert);
            }
            
            return redirect()->route('target.view')
            ->with('success', 'Target berhasil ditambahkan. Total: ' . count($dataToInsert) . ' record');
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan target: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($kd_target)
    {
        try {
            $query = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->leftJoin('konversi_tanggal', 'target.tanggal', '=', 'konversi_tanggal.tanggal')
                ->select(
                    'target.*',
                    'akun.nama_akun',
                    'entitas.nama_entitas',
                    DB::raw("CONCAT(konversi_tanggal.hari_h, ' ', konversi_tanggal.bulan_h, ' ', konversi_tanggal.tahun_h) as tanggal_hijriah")
                );

            if (config('database.default') === 'bigquery') {
                $target = $query->where('target.kd_target', $kd_target)->first();
            } else {
                $target = $query->where('target.kd_target', $kd_target)->first();
            }

            if (!$target) {
                throw new \Exception('Data target tidak ditemukan');
            }

            // Convert dates to Carbon instances for BigQuery
            if (config('database.default') === 'bigquery') {
                $target->created_at = Carbon::parse($target->created_at);
                $target->updated_at = Carbon::parse($target->updated_at);
                $target->tanggal = Carbon::parse($target->tanggal);
            }

            return view('target.detail', compact('target'));

        } catch (\Exception $e) {
            return redirect()
                ->route('target.view')
                ->with('error', 'Gagal menampilkan detail: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($kd_target)
    {
        try {
            $query = DB::table('target')
                ->leftJoin('konversi_tanggal', 'target.tanggal', '=', 'konversi_tanggal.tanggal')
                ->select(
                    'target.*',
                    DB::raw("CONCAT(konversi_tanggal.hari_h, ' ', konversi_tanggal.bulan_h, ' ', konversi_tanggal.tahun_h) as tanggal_hijriah")
                );

            if (config('database.default') === 'bigquery') {
                $target = $query->where('target.kd_target', $kd_target)->first();
            } else {
                $target = $query->where('target.kd_target', $kd_target)->first();
            }

            if (!$target) {
                return redirect()->route('target.view')
                    ->with('error', 'Target tidak ditemukan');
            }

            $akunList = DB::table('akun')->where('aktif', '=', 'Y')->orderBy('urutan', 'asc')->get();
            $entitasList = DB::table('entitas')->orderBy('nama_entitas', 'asc')->get();
            
            return view('target.edit', compact('target', 'akunList', 'entitasList'));

        } catch (\Exception $e) {
            return redirect()->route('target.view')
                ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_target)
    {
        try {
            $validated = $request->validate([
                'kd_entitas' => 'required|string|max:5',
                'kd_akun' => 'required|string|max:15',
                'tanggal' => 'required|date',
                'nilai' => 'required|integer',
            ]);

            $tanggal = Carbon::parse($request->tanggal);
            
            $updateData = [
                'kd_entitas' => $validated['kd_entitas'],
                'kd_akun' => $validated['kd_akun'],
                'tanggal' => $tanggal->format('Y-m-d'),
                'nilai' => $validated['nilai'],
                'hari' => $this->hariIndonesia[$tanggal->englishDayOfWeek] ?? $tanggal->englishDayOfWeek,
                'bulan' => $this->bulanIndonesia[$tanggal->month] ?? $tanggal->monthName,
                'tahun' => $tanggal->year,
                'keterangan' => $request->keterangan,
                'updated_at' => now()
            ];

            if (config('database.default') === 'bigquery') {
                $affected = DB::table('target')
                    ->where('kd_target', $kd_target)
                    ->update($updateData);
                    
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang diupdate');
                }
            } else {
                $affected = DB::table('target')
                    ->where('kd_target', $kd_target)
                    ->update($updateData);
            }

            return redirect()->route('target.view')
                ->with('success', 'Target berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah target: '.$e->getMessage())
                ->withInput();
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:target,kd_target'
        ]);

        try {
            $ids = $request->selected_ids;
            
            DB::beginTransaction();
                           
            // Kemudian hapus data realisasi yang terkait dengan target
            $deletedRealisasi = DB::table('realisasi')
                ->whereIn('kd_target', $ids)
                ->delete();
                
            // Terakhir hapus data target
            $deletedTarget = DB::table('target')
                ->whereIn('kd_target', $ids)
                ->delete();    
                
            DB::commit();

            return back()->with('success', "Berhasil menghapus : $deletedTarget data target. \n Data Realisasi : $deletedRealisasi");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus target: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'selected_ids' => 'required|json',
            'nilai' => 'required|numeric',
            'keterangan' => 'nullable|string'
        ]);
        
        $ids = json_decode($validated['selected_ids']);
        
        // Update each selected target
        DB::table('target')->whereIn('kd_target', $ids)
            ->update([
                'nilai' => $validated['nilai'],
                'keterangan' => $validated['keterangan'] ?? null
            ]);
        
        return redirect()->back()->with('success', 'Target berhasil diupdate');
    }

    public function detail()
    {
        // Gunakan Eloquent dengan eager loading dan join konversi_tanggal
        $targets = Target::with(['entitas', 'akun'])
            ->leftJoin('konversi_tanggal', 'target.tanggal', '=', 'konversi_tanggal.tanggal')
            ->select(
                'target.*',
                DB::raw("CONCAT(konversi_tanggal.hari_h, ' ', konversi_tanggal.bulan_h, ' ', konversi_tanggal.tahun_h) as tanggal_hijriah")
            )
            ->get();
            
        return view('target.detail', compact('targets'));
    }
}
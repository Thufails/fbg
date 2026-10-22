<?php

namespace App\Http\Controllers\realisasi;
use App\Models\Realisasi;
use App\Models\Target;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Add this line
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class realisasiController extends Controller
{
    /**
     * Display a listing of realisasi
     */
    // public function index(Request $request)
    // {
    //     try {
    //         $startDate = $request->input('start_date');
    //         $endDate = $request->input('end_date');
    //         $kd_akun = $request->input('kd_akun');
    //         $kd_entitas = $request->input('kd_entitas'); // Tambahkan ini

    //         $query = DB::table('realisasi')
    //             ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
    //             ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
    //             ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas') // Tambahkan join ke entitas
    //             ->select(
    //                 'realisasi.*',
    //                 'target.nilai as nilai_target',
    //                 'akun.nama_akun',
    //                 'akun.kd_akun',
    //                 'entitas.nama_entitas', // Tambahkan nama_entitas
    //                 'entitas.kd_entitas' // Tambahkan kd_entitas
    //             );

    //         if ($startDate && $endDate) {
    //             $query->whereBetween('realisasi.tanggal', [$startDate, $endDate]);
    //         }

    //         if ($kd_akun) {
    //             $query->where('target.kd_akun', $kd_akun);
    //         }

    //         if ($kd_entitas) { // Tambahkan filter entitas
    //             $query->where('target.kd_entitas', $kd_entitas);
    //         }

    //         $perPage = 20;
    //         $realisasi = $query->paginate($perPage);

    //         $akunList = DB::table('akun')
    //             ->select('kd_akun', 'nama_akun')
    //             ->orderBy('kd_akun')
    //             ->get();

    //         // Get entitas list for filter dropdown
    //         $entitasList = DB::table('entitas')
    //             ->select('kd_entitas', 'nama_entitas')
    //             ->orderBy('nama_entitas')
    //             ->get();
            
    //         return view('realisasi.view', compact('realisasi', 'akunList', 'entitasList', 'startDate', 'endDate', 'kd_akun', 'kd_entitas'));

    //     } catch (\Exception $e) {
    //         return back()->with('error', 'Gagal mengambil data: '.$e->getMessage());
    //     }
    // }

    /**
     * Show the form for editing the specified realization.
     */
    public function edit($id)
    {
        try {
            $realisasi = DB::table('realisasi')
                ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->select(
                    'realisasi.*',
                    'target.nilai as nilai_target',
                    'akun.nama_akun',
                    'akun.kd_akun',
                    'entitas.nama_entitas',
                    'entitas.kd_entitas'
                )
                ->where('realisasi.kd_realisasi', $id)
                ->first();

            if (!$realisasi) {
                return redirect()->route('realisasi.view')
                    ->with('error', 'Data realisasi tidak ditemukan.');
            }

            return view('realisasi.edit', compact('realisasi'));

        } catch (\Exception $e) {
            return redirect()->route('realisasi.view')
                ->with('error', 'Gagal memuat form edit: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified realization in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validasi input
            $request->validate([
                'nilai' => 'required|numeric|min:0',
                'keterangan' => 'nullable|string|max:500'
            ]);

            // Cek apakah realisasi exists
            $realisasi = DB::table('realisasi')->where('kd_realisasi', $id)->first();
            if (!$realisasi) {
                return redirect()->route('realisasi.view')
                    ->with('error', 'Data realisasi tidak ditemukan.');
            }

            // Update data
            $updateData = [
                'nilai' => $request->nilai,
                'keterangan' => $request->keterangan,
                'updated_at' => now()
            ];

            DB::table('realisasi')
                ->where('kd_realisasi', $id)
                ->update($updateData);

            DB::commit();

            return redirect()->route('realisasi.view')
                ->with('success', 'Realisasi berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('realisasi.view')
                ->with('error', 'Gagal memperbarui realisasi: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $kd_akun = $request->input('kd_akun', null);
            $kd_entitas = $request->input('kd_entitas', null); // Tambahkan ini

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

            $query = DB::table('realisasi')
                ->leftJoin('target', 'realisasi.kd_target', '=', 'target.kd_target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->select(
                    'realisasi.*',
                    'target.nilai as nilai_target',
                    'akun.nama_akun',
                    'akun.kd_akun',
                    'entitas.nama_entitas',
                    'entitas.kd_entitas'
                );

            if ($startDate && $endDate) {
                $query->whereBetween('realisasi.tanggal', [$startDate, $endDate]);
            }

            if ($kd_akun) {
                $query->where('target.kd_akun', $kd_akun);
            }

            if ($kd_entitas) {
                $query->where('target.kd_entitas', $kd_entitas);
            }

            $perPage = 100;
            $realisasi = $query->paginate($perPage);

            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->orderBy('kd_akun')
                ->get();

            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->get();
            
            // HITUNG TOTAL TARGET DAN REALISASI DENGAN JOIN YANG BENAR
            // Query untuk total TARGET (dasar perhitungan)
            $targetQuery = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas');

            // Query untuk total REALISASI (berdasarkan target yang ada)
            $realisasiQuery = DB::table('target')
                ->leftJoin('realisasi', 'target.kd_target', '=', 'realisasi.kd_target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas');

            // Terapkan filter yang sama untuk kedua query
            if ($startDate && $endDate) {
                $targetQuery->whereBetween('target.tanggal', [$startDate, $endDate]);
                $realisasiQuery->whereBetween('realisasi.tanggal', [$startDate, $endDate]);
            }

            if ($kd_akun) {
                $targetQuery->where('target.kd_akun', $kd_akun);
                $realisasiQuery->where('target.kd_akun', $kd_akun);
            }

            if ($kd_entitas) {
                $targetQuery->where('target.kd_entitas', $kd_entitas);
                $realisasiQuery->where('target.kd_entitas', $kd_entitas);
            }

            // Hitung total target
            $totalTarget = $targetQuery->sum('target.nilai');

            // Hitung total realisasi
            $totalRealisasi = $realisasiQuery->sum('realisasi.nilai');

            // Hitung persentase realisasi
            $persentaseRealisasi = $totalTarget > 0 
                ? round(($totalRealisasi / $totalTarget) * 100, 2) 
                : 0;

            return view('realisasi.view', compact(
                'realisasi', 
                'akunList', 
                'entitasList', 
                'startDate', 
                'endDate', 
                'kd_akun', 
                'nama_akun',
                'kd_entitas',
                'totalTarget',
                'totalRealisasi',
                'persentaseRealisasi'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengambil data: '.$e->getMessage());
        }
    }
    

    /**
     * Show form to select target for realization
     */
    public function selectTarget(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $kd_akun = $request->input('kd_akun');
            $kd_entitas = $request->input('kd_entitas'); // Tambahkan ini

            $query = DB::table('target')
            ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
            ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas') // Tambahkan join ke entitas
            ->select(
                'target.kd_target',
                'target.kd_akun',
                'target.kd_entitas', // Tambahkan kd_entitas
                'target.nilai',
                'target.tanggal',
                'akun.nama_akun',
                'entitas.nama_entitas' // Tambahkan nama_entitas
            );

            if ($startDate && $endDate) {
                $query->whereBetween('target.tanggal', [$startDate, $endDate]);
            }

            if ($kd_akun) {
                $query->where('target.kd_akun', $kd_akun);
            }

            if ($kd_entitas) { // Tambahkan filter entitas
                $query->where('target.kd_entitas', $kd_entitas);
            }

            $perPage = 20;
            $targets = $query->paginate($perPage);

            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->orderBy('kd_akun')
                ->get();

            // Get entitas list for filter dropdown
            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->get();

            return view('realisasi.select_target', compact('targets', 'startDate', 'endDate', 'kd_akun', 'kd_entitas', 'akunList', 'entitasList'));

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat daftar target: '.$e->getMessage());
        }
    }

    /**
     * Add selected targets to realization cart
     */
    public function addToCart(Request $request)
    {
        $selectedTargets = $request->input('selected_targets', []);
        
        if (empty($selectedTargets)) {
            return back()->with('error', 'Silakan pilih minimal satu target.');
        }

        $cart = session()->get('realisasi_cart', []);
        $existingAkun = $cart[0]['kd_akun'] ?? null;

        // Get all selected targets data
        $targets = DB::table('target')
            ->whereIn('kd_target', $selectedTargets)
            ->select('kd_target', 'kd_entitas','kd_akun', 'nilai', 'tanggal')
            ->get();

        // Check if all selected targets have the same kd_akun
        $uniqueAkun = $targets->pluck('kd_akun')->unique();
        if ($uniqueAkun->count() > 1) {
            return back()->with('error', 'Semua target yang dipilih harus memiliki Akun yang sama.');
        }

        $newAkun = $uniqueAkun->first();

        // Validasi konsistensi kd_akun dengan cart yang sudah ada
        if ($existingAkun && $existingAkun != $newAkun) {
            return back()->with('error', 
                'Target yang dipilih harus memiliki Akun yang sama dengan yang sudah ada di keranjang. '.
                'Akun di keranjang: '.$existingAkun.', Akun yang dipilih: '.$newAkun);
        }

        foreach ($targets as $target) {

            $item = [
                'kd_akun' => $target->kd_akun,
                'kd_target' => $target->kd_target,
                'tanggal' => $target->tanggal,
                'nilai_target' => $target->nilai,
                'nilai_realisasi' => $request->input('nilai_realisasi_'.$target->kd_target, 0),
                'keterangan' => $request->input('keterangan_'.$target->kd_target, ''),
                'added_at' => now() // Tambahkan timestamp
            ];

            // Cek duplikat (kd_target dan tanggal sama)
            $isDuplicate = collect($cart)->contains(function ($cartItem) use ($item) {
                return $cartItem['kd_target'] == $item['kd_target'] 
                    && $cartItem['tanggal'] == $item['tanggal'];
            });

            if (!$isDuplicate) {
                $cart[] = $item;
            }
        }

        session()->put('realisasi_cart', $cart);
        session()->put('kd_akun', $newAkun);
        
        $addedCount = count($targets) - (count($cart) - count(session()->get('realisasi_cart', [])));
        $message = 'Target berhasil ditambahkan ke keranjang.';
        
        if ($addedCount < count($targets)) {
            $message .= ' Beberapa item duplikat diabaikan.';
        }

        return redirect()->route('realisasi.cart')->with('success', $message);
    }

    /**
     * Show realization cart
     */
    public function showCart()
    {
        $cartItems = session()->get('realisasi_cart', []);
        
        // Validasi dan bersihkan data cart yang tidak lengkap
        $validCartItems = [];
        foreach ($cartItems as $item) {
            if (isset($item['kd_akun'], $item['kd_target'], $item['tanggal'])) {
                $validCartItems[] = $item;
            }
        }
        
        // Update session dengan data yang valid
        if (count($validCartItems) !== count($cartItems)) {
            session()->put('realisasi_cart', $validCartItems);
            $cartItems = $validCartItems;
        }
        
        $akunInfo = null;
        if (!empty($cartItems)) {
            $akunInfo = DB::table('akun')
                ->where('kd_akun', $cartItems[0]['kd_akun'])
                ->first();
        }

        return view('realisasi.cart', compact('cartItems', 'akunInfo'));
    }
    /**
     * Store realization from cart
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $realisasiData = $request->input('realisasi');
            $now = now();
            
            $bulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            
            $hari = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];

            $dataToInsert = [];
            
            foreach ($realisasiData as $item) {
                $date = Carbon::parse($item['tanggal']);
                $dayName = $hari[$date->format('l')];
                $monthName = $bulan[$date->month];
                $year = $date->year;

                $dataToInsert[] = [
               //     'kd_realisasi' => Str::uuid(), // Generate unique ID
                    'kd_target' => $item['kd_target'],
                    'tanggal' => $item['tanggal'],
                    'nilai' => $item['nilai'],
                    'hari' => $dayName,
                    'bulan' => $monthName,
                    'tahun' => $year,
                    'keterangan' => $item['keterangan'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            DB::table('realisasi')->insert($dataToInsert);
            session()->forget(['realisasi_cart', 'kd_akun']);
            DB::commit();

            return redirect()->route('realisasi.view')
                ->with('success', 'Realisasi berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan realisasi: ' . $e->getMessage());
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($index)
    {
        $cart = session()->get('realisasi_cart', []);

        if (isset($cart[$index])) {
            unset($cart[$index]);
            session()->put('realisasi_cart', array_values($cart));
        }

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Empty realization cart
     */
    public function emptyCart()
    {
        session()->forget(['realisasi_cart', 'kd_target']);
        return back()->with('success', 'Keranjang realisasi telah dikosongkan.');
    }

    public function bulkDestroy(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'selected_ids' => 'required|array',
                'selected_ids.*' => 'exists:realisasi,kd_realisasi' // Pastikan ID yang dipilih ada di tabel realisasi
            ]);

            DB::beginTransaction();
            
            // Hapus data realisasi berdasarkan ID yang dipilih
            $deletedCount = DB::table('realisasi')
                ->whereIn('kd_realisasi', $request->selected_ids)
                ->delete();
                
            DB::commit();

            // Cek apakah permintaan AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil menghapus $deletedCount realisasi.",
                    'count' => $deletedCount
                ]);
            }

            return back()->with('success', "Berhasil menghapus $deletedCount realisasi.");
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Cek apakah permintaan AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus realisasi: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Gagal menghapus realisasi: ' . $e->getMessage());
        }
    }
   
    /**
     * Generate result message based on processing results
     */
    private function generateResultMessage($results)
    {
        $successCount = count($results['success'] ?? []);
        $failedCount = count($results['failed'] ?? []);
        $skippedCount = count($results['skipped'] ?? []);

        $message = "Proses penyimpanan selesai. ";

        if ($successCount > 0) {
            $message .= "✅ {$successCount} realisasi berhasil disimpan. ";
        }

        if ($failedCount > 0) {
            $message .= "❌ {$failedCount} realisasi gagal disimpan. ";
        }

        if ($skippedCount > 0) {
            $message .= "⏭️ {$skippedCount} realisasi dilewati (sudah ada atau nilai kosong). ";
        }

        if ($successCount === 0 && $failedCount === 0 && $skippedCount === 0) {
            $message = "Tidak ada data realisasi yang diproses.";
        }

        return $message;
    }

    /**
     * Get target data for mass input (AJAX)
     */
    public function getTargetData(Request $request)
    {
        try {
            $request->validate([
                'kd_akun' => 'required|exists:akun,kd_akun',
                'kd_entitas' => 'required|exists:entitas,kd_entitas',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal'
            ]);

            $kd_akun = $request->kd_akun;
            $kd_entitas = $request->kd_entitas;
            $tanggal_awal = $request->tanggal_awal;
            $tanggal_akhir = $request->tanggal_akhir;

            // Get targets for the date range
            $targets = DB::table('target')
                ->where('kd_akun', $kd_akun)
                ->where('kd_entitas', $kd_entitas)
                ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir])
                ->orderBy('tanggal')
                ->get();

            $result = [];
            foreach ($targets as $target) {
                // Check if realization already exists
                $existingRealisasi = DB::table('realisasi')
                    ->where('kd_target', $target->kd_target)
                    ->where('tanggal', $target->tanggal)
                    ->first();

                $result[] = [
                    'kd_target' => $target->kd_target,
                    'tanggal' => $target->tanggal,
                    'tanggal_formatted' => Carbon::parse($target->tanggal)->format('d-m-Y'),
                    'nilai' => $target->nilai,
                    'nilai_formatted' => number_format($target->nilai, 0, ',', '.'),
                    'has_realisasi' => !is_null($existingRealisasi),
                    'realisasi_nilai' => $existingRealisasi->nilai ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Data target berhasil diambil'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data target: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show mass input form
     */
    public function inputMassal()
    {
        try {
            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->orderBy('kd_akun')
                ->get();

            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->get();

            return view('realisasi.input_massal', compact('akunList', 'entitasList'));

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat form input massal: ' . $e->getMessage());
        }
    }

    /**
     * Store mass realization dengan logika baru
     */
    public function storeMassal(Request $request)
    {
        Log::info('=== STORE MASSAL DIPANGGIL ===', $request->all());

        try {
            DB::beginTransaction();

            // Validasi input
            $validationRules = [
                'kd_akun' => 'required|exists:akun,kd_akun',
                'kd_entitas' => 'required|exists:entitas,kd_entitas',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'jenis_input' => 'required|in:bulanan,harian',
                'keterangan' => 'nullable|string|max:500'
            ];

            // Tambahkan validasi berdasarkan jenis input
            if ($request->jenis_input === 'bulanan') {
                $validationRules['nilai_total_bulanan'] = 'required|numeric|min:1';
            } else {
                $validationRules['nilai_per_hari'] = 'required|numeric|min:1';
            }

            $validated = $request->validate($validationRules);

            $kd_akun = $request->kd_akun;
            $kd_entitas = $request->kd_entitas;
            $tanggal_awal = $request->tanggal_awal;
            $tanggal_akhir = $request->tanggal_akhir;
            $jenisInput = $request->jenis_input;
            $keterangan = $request->keterangan ?? '';

            // Hitung jumlah hari
            $startDate = Carbon::parse($tanggal_awal);
            $endDate = Carbon::parse($tanggal_akhir);
            $jumlahHari = $startDate->diffInDays($endDate) + 1;

            Log::info('Jumlah hari dalam periode: ' . $jumlahHari);

            // Get targets for the date range
            $targets = DB::table('target')
                ->where('kd_akun', $kd_akun)
                ->where('kd_entitas', $kd_entitas)
                ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir])
                ->orderBy('tanggal')
                ->get();

            Log::info('Jumlah target ditemukan: ' . $targets->count());

            if ($targets->isEmpty()) {
                $errorMsg = 'Tidak ditemukan target untuk akun ' . $kd_akun . ' dan entitas ' . $kd_entitas . ' dalam rentang tanggal ' . $tanggal_awal . ' hingga ' . $tanggal_akhir;
                Log::warning($errorMsg);
                return back()->with('error', $errorMsg)->withInput();
            }

            $results = [
                'success' => [],
                'failed' => [],
                'skipped' => []
            ];

            // Konfigurasi bulan Indonesia
            $bulanNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            
            $hariNames = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];

            if ($jenisInput === 'bulanan') {
                $nilaiTotal = $request->nilai_total_bulanan;
                $nilaiPerHari = round($nilaiTotal / $jumlahHari);

                Log::info('Input Bulanan - Nilai Total: ' . $nilaiTotal . ', Nilai Per Hari: ' . $nilaiPerHari);

                foreach ($targets as $target) {
                    try {
                        // Check if realization already exists
                        $existingRealisasi = DB::table('realisasi')
                            ->where('kd_target', $target->kd_target)
                            ->where('tanggal', $target->tanggal)
                            ->first();

                        if ($existingRealisasi) {
                            $results['skipped'][] = [
                                'tanggal' => $target->tanggal,
                                'kd_target' => $target->kd_target,
                                'reason' => 'Realisasi sudah ada',
                                'existing_value' => $existingRealisasi->nilai
                            ];
                            continue;
                        }

                        // Prepare data
                        $date = Carbon::parse($target->tanggal);
                        $dayName = $hariNames[$date->format('l')] ?? 'Minggu';
                        $monthName = $bulanNames[$date->month] ?? 'Januari';

                        $realisasiData = [
                            'kd_target' => $target->kd_target,
                            'tanggal' => $target->tanggal,
                            'nilai' => $nilaiPerHari,
                            'hari' => $dayName,
                            'bulan' => $monthName,
                            'tahun' => $date->year,
                            'keterangan' => $keterangan . ' | Bagian dari realisasi bulanan: Rp ' . number_format($nilaiTotal, 0, ',', '.'),
                            'created_at' => now(),
                            'updated_at' => now()
                        ];

                        // Insert realization
                        $inserted = DB::table('realisasi')->insert($realisasiData);
                        
                        if ($inserted) {
                            $results['success'][] = [
                                'tanggal' => $target->tanggal,
                                'kd_target' => $target->kd_target,
                                'nilai' => $nilaiPerHari
                            ];
                        } else {
                            throw new \Exception('Gagal insert ke database');
                        }

                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'tanggal' => $target->tanggal,
                            'kd_target' => $target->kd_target,
                            'reason' => $e->getMessage()
                        ];
                    }
                }

            } else { // Input harian
                $nilaiPerHari = $request->nilai_per_hari;

                Log::info('Input Harian - Nilai Per Hari: ' . $nilaiPerHari);

                foreach ($targets as $target) {
                    try {
                        // Check if realization already exists
                        $existingRealisasi = DB::table('realisasi')
                            ->where('kd_target', $target->kd_target)
                            ->where('tanggal', $target->tanggal)
                            ->first();

                        if ($existingRealisasi) {
                            $results['skipped'][] = [
                                'tanggal' => $target->tanggal,
                                'kd_target' => $target->kd_target,
                                'reason' => 'Realisasi sudah ada',
                                'existing_value' => $existingRealisasi->nilai
                            ];
                            continue;
                        }

                        // Prepare data
                        $date = Carbon::parse($target->tanggal);
                        $dayName = $hariNames[$date->format('l')] ?? 'Minggu';
                        $monthName = $bulanNames[$date->month] ?? 'Januari';

                        $realisasiData = [
                            'kd_target' => $target->kd_target,
                            'tanggal' => $target->tanggal,
                            'nilai' => $nilaiPerHari,
                            'hari' => $dayName,
                            'bulan' => $monthName,
                            'tahun' => $date->year,
                            'keterangan' => $keterangan,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];

                        // Insert realization
                        $inserted = DB::table('realisasi')->insert($realisasiData);

                        if ($inserted) {
                            $results['success'][] = [
                                'tanggal' => $target->tanggal,
                                'kd_target' => $target->kd_target,
                                'nilai' => $nilaiPerHari
                            ];
                        } else {
                            throw new \Exception('Gagal insert realisasi harian ke database');
                        }

                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'tanggal' => $target->tanggal,
                            'kd_target' => $target->kd_target,
                            'reason' => $e->getMessage()
                        ];
                    }
                }
            }

            DB::commit();

            // Generate result message
            $message = $this->generateResultMessage($results);

            Log::info('Hasil penyimpanan massal:', [
                'success' => count($results['success']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped']),
                'message' => $message
            ]);

            return redirect()->route('realisasi.results')
                ->with('success', $message)
                ->with('results', $results);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== ERROR STORE MASSAL ===: ' . $e->getMessage());

            return back()->with('error', 'Gagal menyimpan realisasi massal: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show detailed results after saving
     */
    public function showResults(Request $request)
    {
        $results = session('results', []);
        
        if (empty($results)) {
            return redirect()->route('realisasi.view')
                ->with('info', 'Tidak ada hasil penyimpanan untuk ditampilkan.');
        }

        return view('realisasi.results', compact('results'));
    }

}
<?php

namespace App\Http\Controllers\detailRealisasi;

use App\Models\DetailRealisasi;
use App\Models\DetailTarget;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class detailRealisasiController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $kd_detail_akun = $request->input('kd_detail_akun');
            $kd_entitas = $request->input('kd_entitas'); // Tambahkan ini

            $query = DB::table('detail_realisasi')
                ->leftJoin('detail_target', 'detail_realisasi.kd_target_detail', '=', 'detail_target.kd_target_detail')
                ->leftJoin('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
                ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('target', function($join) {
                    $join->on('detail_target.tanggal', '=', 'target.tanggal')
                        ->on('akun.kd_akun', '=', 'target.kd_akun');
                })
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas') // Tambahkan join ke entitas
                ->select(
                    'detail_realisasi.*',
                    'detail_target.tanggal as target_tanggal',
                    'detail_akun.nama_detail_akun',
                    'akun.nama_akun',
                    'entitas.nama_entitas', // Tambahkan nama_entitas
                    'entitas.kd_entitas' // Tambahkan kd_entitas
                );

            // Date filtering
            if ($startDate && $endDate) {
                $query->whereBetween('detail_realisasi.tanggal', [
                    Carbon::parse($startDate)->format('Y-m-d'),
                    Carbon::parse($endDate)->format('Y-m-d')
                ]);
            }

            // Account filtering
            if ($kd_detail_akun) {
                $query->where('detail_target.kd_detail_akun', $kd_detail_akun);
            }

            // Entitas filtering
            if ($kd_entitas) { // Tambahkan filter entitas
                $query->where('target.kd_entitas', $kd_entitas);
            }

            // Pagination
            $perPage = 20;
            $detailRealisasi = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            // Format dates for display
            $detailRealisasi->getCollection()->transform(function ($item) {
                try {
                    $item->tanggal_formatted = Carbon::parse($item->tanggal)->format('d-m-Y');
                    $item->target_tanggal_formatted = isset($item->target_tanggal) 
                        ? Carbon::parse($item->target_tanggal)->format('d-m-Y') 
                        : null;
                } catch (\Exception $e) {
                    $item->tanggal_formatted = $item->tanggal;
                    $item->target_tanggal_formatted = $item->target_tanggal ?? null;
                }
                return $item;
            });

            // Get accounts for filter dropdown
            $targetDetailList = DB::table('detail_akun')
                ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                ->select('kd_detail_akun', 'nama_detail_akun', 'nama_akun')
                ->orderBy('kd_detail_akun')
                ->get();

            // Get entitas list for filter dropdown
            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->get();
            
            return view('detailRealisasi.view', compact(
                'detailRealisasi', 
                'targetDetailList', 
                'entitasList', // Tambahkan entitasList
                'startDate', 
                'endDate', 
                'kd_detail_akun',
                'kd_entitas' // Tambahkan kd_entitas
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengambil data: '.$e->getMessage());
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $targetDetailList = DetailTarget::all();
            return view('detailRealisasi.insert', compact('targetDetailList'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat form: ' . $e->getMessage());
        }
    }

    /**
     * Store realization data from cart to database
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $cartItems = session()->get('realisasi_cart', []);
            $realisasiData = $request->input('realisasi', []);
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
            
            foreach ($realisasiData as $index => $itemData) {
                if (!isset($cartItems[$index])) continue;
                
                $cartItem = $cartItems[$index];
                $date = Carbon::parse($cartItem['tanggal']);
                
                $dataToInsert[] = [
                    'kd_target_detail' => $cartItem['kd_target_detail'],
                    'tanggal' => $cartItem['tanggal'],
                    'nilai' => $itemData['nilai'] ?? 0,
                    'hari' => $hari[$date->format('l')],
                    'bulan' => $bulan[$date->month],
                    'tahun' => $date->year,
                    'keterangan' => $itemData['keterangan'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            // Insert data ke tabel detail_realisasi
            DB::table('detail_realisasi')->insert($dataToInsert);

            // Kosongkan keranjang setelah berhasil disimpan
            session()->forget(['realisasi_cart', 'kd_detail_akun']);
            
            DB::commit();

            return redirect()->route('detailRealisasi.view')
                ->with('success', 'Realisasi berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan realisasi: ' . $e->getMessage())
                ->withInput();
        }
    }
    /**
     * Bulk delete selected records.
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $request->validate([
                'selected_ids' => 'required|array',
                'selected_ids.*' => 'exists:detail_realisasi,kd_detail_realisasi'
            ]);

            DB::beginTransaction();
            
            $deletedCount = DetailRealisasi::whereIn('kd_detail_realisasi', $request->selected_ids)->delete();
                
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil menghapus $deletedCount detail realisasi.",
                    'count' => $deletedCount
                ]);
            }

            return back()->with('success', "Berhasil menghapus $deletedCount detail realisasi.");
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus detail realisasi: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Gagal menghapus detail realisasi: ' . $e->getMessage());
        }
    }

    public function selectTarget(Request $request)
{
    try {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $kd_detail_akun = $request->input('kd_detail_akun');
        $kd_entitas = $request->input('kd_entitas'); // Tambahkan ini

        // Validasi format tanggal
        if ($startDate) {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
        }
        
        if ($endDate) {
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
        }

        $cart = Session::get('cart', []);
        
        // Hanya memperbarui kd_target_detail session jika keranjang kosong
        if ($kd_detail_akun && (empty($cart) || count($cart) === 0)) {
            Session::put('kd_detail_akun', $kd_detail_akun);
        } elseif ($kd_detail_akun && !empty($cart)) {
            $firstItemKdDetailAkun = $cart[0]['kd_detail_akun'] ?? null;
            if ($firstItemKdDetailAkun && $firstItemKdDetailAkun != $kd_detail_akun) {
                return redirect()->back()->with('error', 
                    'Tidak dapat mengubah Detail Akun karena keranjang sudah berisi item dengan Detail Akun berbeda. '.
                    'Silakan kosongkan keranjang terlebih dahulu.');
            }
        }

        $query = DB::table('detail_target')
            ->join('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
            ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
            ->leftJoin('target', function($join) {
                $join->on('detail_target.tanggal', '=', 'target.tanggal')
                     ->on('akun.kd_akun', '=', 'target.kd_akun');
            })
            ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas') // Tambahkan join ke entitas
            ->select(
                'detail_target.kd_target_detail',
                'detail_target.tanggal',
                'detail_target.kd_detail_akun',
                'detail_target.nilai',
                'detail_target.hari',
                'detail_target.bulan',
                'detail_target.tahun',
                'detail_akun.nama_detail_akun',
                'akun.nama_akun',
                'entitas.nama_entitas', // Tambahkan nama_entitas
                'entitas.kd_entitas' // Tambahkan kd_entitas
            );

        // Filter berdasarkan tanggal
        if ($startDate && $endDate) {
            $query->whereBetween('detail_target.tanggal', [$startDate, $endDate]);
        }

        // Filter berdasarkan kode detail akun
        if ($kd_detail_akun) {
            $query->where('detail_target.kd_detail_akun', $kd_detail_akun);
        }

        // Filter berdasarkan entitas
        if ($kd_entitas) { // Tambahkan filter entitas
            $query->where('target.kd_entitas', $kd_entitas);
        }

        $perPage = 20;
        $targets = $query->paginate($perPage);

        // Ambil daftar target detail untuk dropdown filter
        $targetDetailList = DB::table('detail_akun')
            ->leftJoin('akun','detail_akun.kd_akun','=','akun.kd_akun')
            ->select('nama_akun','nama_detail_akun','kd_detail_akun')
            ->orderBy('kd_detail_akun')
            ->get();

        // Get entitas list for filter dropdown
        $entitasList = DB::table('entitas')
            ->select('kd_entitas', 'nama_entitas')
            ->orderBy('nama_entitas')
            ->get();

        return view('detailRealisasi.select_target', compact(
            'targets', 
            'startDate', 
            'endDate', 
            'kd_detail_akun', 
            'kd_entitas', // Tambahkan kd_entitas
            'targetDetailList',
            'entitasList' // Tambahkan entitasList
        ));

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Gagal memuat daftar target: ' . $e->getMessage());
    }
}

    public function addToCart(Request $request)
    {
        try {
            $selectedTargets = $request->input('selected_targets', []);
            
            if (empty($selectedTargets)) {
                return back()->with('error', 'Silakan pilih minimal satu target.');
            }

            $cart = session()->get('realisasi_cart', []);
            $existingKdDetailAkun = $cart[0]['kd_detail_akun'] ?? null;

            // Get all selected targets data
            $targets = DB::table('detail_target')
                ->join('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
                ->whereIn('detail_target.kd_target_detail', $selectedTargets)
                ->select(
                    'detail_target.kd_target_detail',
                    'detail_target.tanggal',
                    'detail_target.nilai as nilai_target',
                    'detail_akun.kd_detail_akun',
                    'detail_akun.nama_detail_akun'
                )
                ->get();

            // Check if all selected targets have the same kd_detail_akun
            $uniqueDetailAkun = $targets->pluck('kd_detail_akun')->unique();
            if ($uniqueDetailAkun->count() > 1) {
                return back()->with('error', 'Semua target yang dipilih harus memiliki Detail Akun yang sama.');
            }

            $kd_detail_akun = $uniqueDetailAkun->first();
            
            // Validate consistency with existing cart items
            if ($existingKdDetailAkun && $existingKdDetailAkun != $kd_detail_akun) {
                return back()->with('error', 
                    'Target yang dipilih harus memiliki Detail Akun yang sama dengan yang sudah ada di keranjang.');
            }

            foreach ($targets as $target) {
                $item = [
                    'kd_target_detail' => $target->kd_target_detail,
                    'tanggal' => $target->tanggal,
                    'kd_detail_akun' => $target->kd_detail_akun,
                    'nama_detail_akun' => $target->nama_detail_akun,
                    'nilai' => $request->input('nilai_'.$target->kd_target_detail, 0),
                    'keterangan' => $request->input('keterangan_'.$target->kd_target_detail, '')
                ];

                // Check for duplicate (same kd_target_detail and tanggal)
                $duplicateExists = collect($cart)->contains(function ($cartItem) use ($item) {
                    return $cartItem['kd_target_detail'] == $item['kd_target_detail'] 
                        && $cartItem['tanggal'] == $item['tanggal'];
                });

                if (!$duplicateExists) {
                    $cart[] = $item;
                }
            }

            session()->put('realisasi_cart', $cart);
            session()->put('kd_detail_akun', $kd_detail_akun);
            
            return redirect()->route('detailRealisasi.cart')
                ->with('success', 'Target berhasil ditambahkan ke keranjang.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan ke keranjang: ' . $e->getMessage());
        }
    }


    public function showCart()
    {
        $cartItems = session()->get('realisasi_cart', []);
        
        $akunInfo = null;
        if (!empty($cartItems)) {
            $akunInfo = DB::table('detail_akun')
                ->leftJoin('akun','detail_akun.kd_akun','=','akun.kd_akun')
                ->where('kd_detail_akun', $cartItems[0]['kd_detail_akun'])
                ->first();
        }

        return view('detailRealisasi.cart', compact('cartItems', 'akunInfo'));
    }

    public function removeFromCart($index)
    {
        $cart = session()->get('realisasi_cart', []);
        
        if (isset($cart[$index])) {
            unset($cart[$index]);
            session()->put('realisasi_cart', array_values($cart));
        }

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    public function emptyCart()
    {
        session()->forget(['realisasi_cart', 'kd_detail_akun']);
        return back()->with('success', 'Keranjang telah dikosongkan.');
    }

 

}

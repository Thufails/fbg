<?php

namespace App\Http\Controllers\detailTarget;

use App\Models\DetailTarget;
use App\Models\Target;
use App\Models\DetailAkun;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Add this line
use Illuminate\Support\Str;
use Carbon\Carbon;

class detailTargetController extends Controller
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

            $query = DB::table('detail_target')
                ->leftJoin('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
                ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('target', function($join) {
                    $join->on('detail_target.tanggal', '=', 'target.tanggal')
                        ->on('akun.kd_akun', '=', 'target.kd_akun');
                })
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas') // Tambahkan join ke entitas
                ->select(
                    'detail_target.*',
                    'detail_akun.nama_detail_akun',
                    'akun.nama_akun',
                    'akun.kd_akun',
                    'entitas.nama_entitas', // Tambahkan nama_entitas
                    'entitas.kd_entitas' // Tambahkan kd_entitas
                );

            // Date filtering
            if ($startDate && $endDate) {
                $query->whereBetween('detail_target.tanggal', [$startDate, $endDate]);
            }

            // Detail account filtering
            if ($kd_detail_akun) {
                $query->where('detail_target.kd_detail_akun', $kd_detail_akun);
            }

            // Entitas filtering
            if ($kd_entitas) { // Tambahkan filter entitas
                $query->where('target.kd_entitas', $kd_entitas);
            }

            // Pagination
            $perPage = 20;
            $detailTarget = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            // Get accounts for filter dropdown
            $detailAkunList = DB::table('detail_akun')
                ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                ->select('kd_detail_akun', 'nama_detail_akun','nama_akun')
                ->orderBy('kd_detail_akun')
                ->get();

            // Get entitas list for filter dropdown
            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->get();
            
            return view('detailTarget.view', compact('detailTarget', 'detailAkunList', 'entitasList', 'startDate', 'endDate', 'kd_detail_akun', 'kd_entitas'));

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
            $detailAkunList = DB::table('detail_akun')->get();
            $targetList = DB::table('target')->get();
            
            return view('detailTarget.insert', compact('detailAkunList', 'targetList'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat form: '.$e->getMessage());
        }
    }

    public function selectTarget(Request $request)
    {
        try {
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $kd_akun = $request->input('kd_akun', null);
            $kd_entitas = $request->input('kd_entitas', null); // Tambahkan ini

            $cart = Session::get('cart', []);
            
            // Only update kd_akun session if cart is empty
            if ($kd_akun && (empty($cart) || count($cart) === 0)) {
                Session::put('kd_akun', $kd_akun);
            } elseif ($kd_akun && !empty($cart)) {
                // If cart is not empty, only update if kd_akun matches existing items
                $firstItemKdAkun = $cart[0]['kd_akun'] ?? null;
                if ($firstItemKdAkun && $firstItemKdAkun != $kd_akun) {
                    return redirect()->back()->with('error', 
                        'Tidak dapat mengubah Akun karena keranjang sudah berisi item dengan akun berbeda. '
                        . 'Silakan kosongkan keranjang terlebih dahulu.');
                }
            }

            $query = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->select(
                    'target.*',
                    'akun.nama_akun',
                    'entitas.nama_entitas'
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
            $targets = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->orderBy('kd_akun')
                ->get();

            // Get entitas list for filter dropdown
            $entitasList = DB::table('entitas')
                ->select('kd_entitas', 'nama_entitas')
                ->orderBy('nama_entitas')
                ->get();

            return view('detailTarget.select_target', compact('targets', 'startDate', 'endDate', 'kd_akun', 'kd_entitas', 'akunList', 'entitasList'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat daftar target: '.$e->getMessage());
        }
    }


    public function addToCart(Request $request)
    {
        $requestKdAkun = $request->input('kd_akun');
        $sessionKdAkun = Session::get('kd_akun');
        $cart = Session::get('cart', []);
        
        // Validate kd_akun exists
        if (empty($requestKdAkun) && empty($sessionKdAkun)) {
            return redirect()->back()->with('error', 'Silakan pilih Akun terlebih dahulu.');
        }

        // Determine which kd_akun to use (request takes precedence)
        $kd_akun = $requestKdAkun ?: $sessionKdAkun;

        // If cart has items, verify kd_akun matches existing items
        if (!empty($cart)) {
            $firstItemKdAkun = $cart[0]['kd_akun'] ?? null;
            
            if ($firstItemKdAkun && $firstItemKdAkun != $kd_akun) {
                return redirect()->back()->with('error', 
                    'Tidak dapat menambahkan item: Akun berbeda dengan item yang sudah ada di keranjang.');
            }
        } else {
            // Only update session kd_akun when cart is empty
            Session::put('kd_akun', $kd_akun);
        }

        $tanggalList = $request->input('tanggal', []);
        if (empty($tanggalList)) {
            return redirect()->back()->with('error', 'Pilih minimal satu tanggal.');
        }

        foreach ($tanggalList as $tanggal) {
            $item = [
                'tanggal' => $tanggal,
                'kd_akun' => $kd_akun,
                'nilai' => $request->input('nilai_'.$tanggal)
            ];

            // Check for duplicates
            foreach ($cart as $cartItem) {
                if ($cartItem['tanggal'] === $item['tanggal'] && $cartItem['kd_akun'] === $item['kd_akun']) {
                    return redirect()->back()->with('error', 
                        'Item dengan tanggal ini sudah ada di keranjang.');
                }
            }

            $cart[] = $item;
        }

        Session::put('cart', $cart);
        return redirect()->back()->with('success', 'Item ditambahkan ke keranjang.');
    }
    
    public function removeFromCart($index)
    {
        $cart = session()->get('cart', []);

        // Hapus item dari keranjang
        if (isset($cart[$index])) {
            unset($cart[$index]);
            session()->put('cart', array_values($cart)); // Reindex array
        }

        return redirect()->route('detailTarget.show')->with('success', 'Item berhasil dihapus dari keranjang.');
    }


    public function clearCart()
    {
        // Hapus keranjang dari session
        session()->forget(['cart','kd_akun']);
        return redirect()->route('detailTarget.cart')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    public function emptyCart()
    {
        // Logika untuk mengosongkan keranjang
        session()->forget(['cart','kd_akun']);
        return redirect()->back()->with('success', 'Keranjang Anda telah dikosongkan.');
    }
    

    public function removeItem($id)
    {
        // Ambil item keranjang dari sesi
        $cart = session()->get('cart', []);

        // Periksa apakah item dengan ID tersebut ada
        if (isset($cart[$id])) {
            // Hapus item dari keranjang
            unset($cart[$id]);
            // Simpan kembali keranjang ke sesi
            session()->put('cart', $cart);
            return redirect()->back()->with('success', 'Item telah dihapus dari keranjang.');
        }

        // Jika item tidak ditemukan, kembalikan pesan error
        return redirect()->back()->with('error', 'Item tidak ditemukan di keranjang.');
    }

    public function showCart()
    {
        // Get cart items from session
        $cartItems = session()->get('cart', []);
        
        // Filter detail accounts based on session kd_akun
        $detailAkunList = DB::table('detail_akun')
            ->where('detail_akun.kd_akun', session('kd_akun'))
            ->select('detail_akun.kd_detail_akun', 'nama_detail_akun', 'detail_akun.kd_akun')
            ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
            ->orderBy('akun.kd_akun')
            ->get();

        return view('detailTarget.cart', compact('cartItems', 'detailAkunList'));
    }

    public function store(Request $request)
    {
        // Validate required fields
        $validated = $request->validate([
            'kd_detail_akun' => 'required|exists:detail_akun,kd_detail_akun',
            'nilai' => 'required|numeric|min:0',
            'cart_items' => 'required|json'
        ]);

        try {
            DB::beginTransaction();

            // Decode cart items
            $cartItems = json_decode($request->input('cart_items'), true);
            
            // Prepare data for bulk insert
            $detailTargets = [];
            $now = now();
            
            // Indonesian month and day names
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

            foreach ($cartItems as $item) {
                $date = Carbon::parse($item['tanggal']);
                $dayName = $hari[$date->format('l')];
                $monthName = $bulan[$date->month];
                $year = $date->year;

                $detailTargets[] = [
                  //  'kd_target_detail' => Str::uuid(), // Generate unique ID
                    'tanggal' => $item['tanggal'],
                    'kd_detail_akun' => $validated['kd_detail_akun'],
                    'nilai' => $validated['nilai'],
                    'hari' => $dayName,
                    'bulan' => $monthName,
                    'tahun' => $year,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            // BigQuery compatible insert
            if (config('database.default') === 'bigquery') {
                // For BigQuery, we need to insert one by one
                foreach ($detailTargets as $data) {
                    DB::table('detail_target')->insert($data);
                }
            } else {
                // For other databases, use bulk insert
                DB::table('detail_target')->insert($detailTargets);
            }

            // Clear cart and kd_akun session after successful save
            Session::forget(['cart', 'kd_akun']);

            DB::commit();

            return redirect()
                ->route('detailTarget.show')
                ->with('success', 'Detail target berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan detail target: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete selected records
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $request->validate([
                'selected_ids' => 'required|array',
                'selected_ids.*' => 'exists:detail_target,kd_target_detail'
            ]);

            DB::beginTransaction();

            $deletedRealisasiCount = DB::table('detail_realisasi')
                ->whereIn('kd_target_detail', $request->selected_ids)
                ->delete();
            
            $deletedCount = DB::table('detail_target')
                ->whereIn('kd_target_detail', $request->selected_ids)
                ->delete();
                
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil menghapus $deletedCount detail target. Data Detail Realisasi $deletedRealisasiCount",
                    'count' => $deletedCount
                ]);
            }

            return back()->with('success', "Berhasil menghapus $deletedCount detail target.");
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus detail target: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Gagal menghapus detail target: ' . $e->getMessage());
        }
    }

}
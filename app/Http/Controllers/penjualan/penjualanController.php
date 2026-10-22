<?php

namespace App\Http\Controllers\penjualan;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Entitas; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class penjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $kd_entitas = $request->input('kd_entitas'); // Tambahkan ini
            
            $query = Penjualan::with(['produk', 'entitas'])
                ->select(
                    'penjualan.*', 
                    'produk.nama_produk', 
                    'entitas.nama_entitas',
                    DB::raw("CONCAT(konversi_tanggal.hari_h, ' ', konversi_tanggal.bulan_h, ' ', konversi_tanggal.tahun_h) as tanggal_hijriah") // Tambahkan tanggal hijriah
                )
                ->join('produk', 'penjualan.kd_produk', '=', 'produk.kd_produk')
                ->leftJoin('entitas', 'penjualan.kd_entitas', '=', 'entitas.kd_entitas') // Tambahkan join ke entitas
                ->leftJoin('konversi_tanggal', 'penjualan.tanggal', '=', 'konversi_tanggal.tanggal'); // Join dengan konversi_tanggal
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('produk.nama_produk', 'like', '%'.$search.'%')
                      ->orWhere('penjualan.kd_produk', 'like', '%'.$search.'%')
                      ->orWhere('entitas.nama_entitas', 'like', '%'.$search.'%'); // Tambahkan pencarian entitas
                });
            }
            
            // if ($start_date) {
            //     $query->where('penjualan.tanggal', '>=', $start_date);
            // }
            
            // if ($end_date) {
            //     $query->where('penjualan.tanggal', '<=', $end_date);
            // }

            if ($start_date && $end_date) {
                $query->whereBetween('penjualan.tanggal', [$start_date, $end_date]);
            }
            else{
                $currentYear = date('Y');
                $startOfYear = $currentYear . '-01-01';
                $endOfYear = $currentYear . '-12-31';
                $query->whereBetween('penjualan.tanggal', [$startOfYear, $endOfYear]);
            }

            // Filter berdasarkan entitas
            if ($kd_entitas) {
                $query->where('penjualan.kd_entitas', $kd_entitas);
            }
            
            $penjualan = $query->paginate(10);

            // Get entitas list for filter
            $entitasList = Entitas::all();
            
            return view('penjualan.view', compact('penjualan', 'search', 'start_date', 'end_date', 'kd_entitas', 'entitasList'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data penjualan: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $produkList = Produk::all();
        $entitasList = Entitas::all(); // Tambahkan ini
        return view('penjualan.insert', compact('produkList', 'entitasList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'kd_produk' => 'required',
                'kd_entitas' => 'nullable|exists:entitas,kd_entitas', // Tambahkan validasi
                'harga' => 'required|integer|min:0',
                'jumlah' => 'required|integer|min:1'
            ]);

            $data = [
                'tanggal' => $validated['tanggal'],
                'kd_produk' => $validated['kd_produk'],
                'kd_entitas' => $validated['kd_entitas'], // Tambahkan ini
                'harga' => $validated['harga'],
                'jumlah' => $validated['jumlah'],
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table('penjualan')->insert($data);
            
            return redirect()->route('penjualan.view')
                ->with('success', 'Penjualan berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan penjualan: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_penjualan)
    {
        try {
            $penjualan = Penjualan::with(['produk', 'entitas'])
                ->select(
                    'penjualan.*',
                    DB::raw("CONCAT(konversi_tanggal.hari_h, ' ', konversi_tanggal.bulan_h, ' ', konversi_tanggal.tahun_h) as tanggal_hijriah") // Tambahkan tanggal hijriah
                )
                ->leftJoin('konversi_tanggal', 'penjualan.tanggal', '=', 'konversi_tanggal.tanggal')
                ->findOrFail($kd_penjualan);
                
            $produkList = Produk::all();
            $entitasList = Entitas::all(); // Tambahkan ini
            
            return view('penjualan.edit', compact('penjualan', 'produkList', 'entitasList'));
        } catch (\Exception $e) {
            return redirect()->route('penjualan.view')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $kd_penjualan)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'kd_produk' => 'required',
                'kd_entitas' => 'nullable|exists:entitas,kd_entitas', // Tambahkan validasi
                'harga' => 'required|integer|min:0',
                'jumlah' => 'required|integer|min:1'
            ]);

            $updateData = [
                'tanggal' => $validated['tanggal'],
                'kd_produk' => $validated['kd_produk'],
                'kd_entitas' => $validated['kd_entitas'], // Tambahkan ini
                'harga' => $validated['harga'],
                'jumlah' => $validated['jumlah'],
                'updated_at' => now()
            ];

            $affected = DB::table('penjualan')
                        ->where('kd_penjualan', $kd_penjualan)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('penjualan.view')
                ->with('success', 'Penjualan berhasil diperbarui');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui penjualan: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_penjualan)
    {
        try {
            $affected = DB::table('penjualan')
                        ->where('kd_penjualan', $kd_penjualan)
                        ->delete();
                        
            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang dihapus');
            }

            return redirect()->route('penjualan.view')
                ->with('success', 'Penjualan berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus penjualan: '.$e->getMessage());
        }
    }

    /**
     * Add item to cart
     */
    public function addToCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'kd_produk' => 'required',
                'kd_entitas' => 'nullable|exists:entitas,kd_entitas', // Tambahkan validasi
                'harga' => 'required|integer|min:0',
                'jumlah' => 'required|integer|min:1'
            ]);

            $produk = Produk::findOrFail($validated['kd_produk']);
            $entitas = $validated['kd_entitas'] ? Entitas::find($validated['kd_entitas']) : null;

            // Ambil tanggal hijriah dari tabel konversi_tanggal
            $tanggalHijriah = DB::table('konversi_tanggal')
                ->where('tanggal', $validated['tanggal'])
                ->select(DB::raw("CONCAT(hari_h, ' ', bulan_h, ' ', tahun_h) as tanggal_hijriah"))
                ->first();

            $cart = session()->get('penjualan.cart', []);

            $cartItem = [
                'tanggal' => $validated['tanggal'],
                'kd_produk' => $validated['kd_produk'],
                'kd_entitas' => $validated['kd_entitas'], // Tambahkan ini
                'nama_produk' => $produk->nama_produk,
                'nama_entitas' => $entitas ? $entitas->nama_entitas : '-', // Tambahkan ini
                'harga' => $validated['harga'],
                'jumlah' => $validated['jumlah'],
                'subtotal' => $validated['harga'] * $validated['jumlah'],
                'tanggal_hijriah' => $tanggalHijriah->tanggal_hijriah ?? null // Tambahkan tanggal hijriah ke cart
            ];

            $cart[] = $cartItem;
            session()->put('penjualan.cart', $cart);

            return redirect()->route('penjualan.cart')
                ->with('success', 'Item berhasil ditambahkan ke keranjang');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan ke keranjang: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show cart
     */
    public function showCart()
    {
        $cart = session()->get('penjualan.cart', []);
        $produkList = Produk::all();
        $entitasList = Entitas::all(); // Tambahkan ini
        
        return view('penjualan.cart', compact('cart', 'produkList', 'entitasList'));
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($index)
    {
        try {
            $cart = session()->get('penjualan.cart', []);

            if (isset($cart[$index])) {
                unset($cart[$index]);
                $cart = array_values($cart); // Reindex array
                session()->put('penjualan.cart', $cart);
                
                return redirect()->route('penjualan.cart')
                    ->with('success', 'Item berhasil dihapus dari keranjang');
            }

            throw new \Exception('Item tidak ditemukan di keranjang');
                
        } catch (\Exception $e) {
            return redirect()->route('penjualan.cart')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Empty cart
     */
    public function emptyCart()
    {
        session()->forget('penjualan.cart');
        
        return redirect()->route('penjualan.cart')
            ->with('success', 'Keranjang berhasil dikosongkan');
    }

    /**
     * Store cart items
     */
    public function storeCart(Request $request)
    {
        try {
            $cart = session()->get('penjualan.cart', []);

            if (empty($cart)) {
                throw new \Exception('Keranjang kosong');
            }

            DB::beginTransaction();

            foreach ($cart as $item) {
                Penjualan::create([
                    'tanggal' => $item['tanggal'],
                    'kd_produk' => $item['kd_produk'],
                    'kd_entitas' => $item['kd_entitas'], // Tambahkan ini
                    'harga' => $item['harga'],
                    'jumlah' => $item['jumlah'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            
            session()->forget('penjualan.cart');
            
            return redirect()->route('penjualan.view')
                ->with('success', 'Penjualan berhasil disimpan');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('penjualan.cart')
                ->with('error', 'Gagal menyimpan penjualan: '.$e->getMessage());
        }
    }

    /**
     * Bulk delete penjualan dengan penanganan error lebih detail
     */
    public function bulkDestroy(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'required|exists:penjualan,kd_penjualan'
        ]);

        try {
            DB::beginTransaction();

            $selectedIds = $validated['selected_ids'];
            $deletedCount = 0;

            // Hapus data satu per satu untuk logging yang lebih baik
            foreach ($selectedIds as $id) {
                $deleted = DB::table('penjualan')
                    ->where('kd_penjualan', $id)
                    ->delete();
                    
                if ($deleted) {
                    $deletedCount++;
                }
            }

            DB::commit();

            if ($deletedCount === 0) {
                throw new \Exception('Tidak ada data yang berhasil dihapus');
            }

            // Response untuk AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil menghapus $deletedCount dari " . count($selectedIds) . " penjualan.",
                    'deleted_count' => $deletedCount,
                    'total_selected' => count($selectedIds)
                ]);
            }

            return back()->with('success', 
                "Berhasil menghapus $deletedCount dari " . count($selectedIds) . " penjualan.");

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log error untuk debugging
            \Log::error('Bulk delete penjualan error: ' . $e->getMessage(), [
                'selected_ids' => $validated['selected_ids'] ?? [],
                'user_id' => auth()->id()
            ]);

            // Response untuk AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus penjualan: ' . $e->getMessage(),
                    'error_details' => config('app.debug') ? $e->getTraceAsString() : null
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus penjualan: ' . $e->getMessage());
        }
    }
}
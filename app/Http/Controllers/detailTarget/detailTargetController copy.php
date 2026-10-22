<?php

namespace App\Http\Controllers\detailTarget;

use App\Models\DetailTarget;
use App\Models\Target;
use App\Models\DetailAkun;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $kd_detail_akun = $request->input('kd_detail_akun', null);

            $query = DB::table('detail_target')
                ->leftJoin('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
                ->select(
                    'detail_target.*',
                    'detail_akun.nama_akun as nama_detail_akun'  // Fixed column name
                );

            if ($startDate && $endDate) {
                $query->whereBetween('detail_target.tanggal', [$startDate, $endDate]);
            }

            if ($kd_detail_akun) {
                $query->where('detail_target.kd_detail_akun', $kd_detail_akun);
            }

            $perPage = 10;
            $detailTarget = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            // Get detail akun list for filter dropdown
            $detailAkunList = DB::table('detail_akun')
                ->select('kd_detail_akun', 'nama_detail_akun')
                ->orderBy('kd_detail_akun')
                ->get();
            
            return view('detailTarget.view', compact('detailTarget'));

        } catch (\Exception $e) {
            return redirect()->route('detailTarget.view')
                ->with('error', 'Gagal mengambil data: '.$e->getMessage());
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
                session()->put('kd_akun', $request->input('kd_akun'));
            }

            $perPage = 20;
            $targets = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->orderBy('kd_akun')
                ->get();

            return view('detailTarget.select_target', compact('targets', 'startDate', 'endDate', 'kd_akun', 'akunList'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat daftar target: '.$e->getMessage());
        }
    }


    public function addToCart(Request $request)
    {
        // Check if kd_akun is provided either in request or session
        $requestKdAkun = $request->input('kd_akun');
        $sessionKdAkun = session('kd_akun');
        
        if (empty($requestKdAkun) && empty($sessionKdAkun)) {
            return redirect()->back()->with('error', 'Silakan pilih Akun terlebih dahulu sebelum menambahkan ke keranjang.');
        }

        // Determine which kd_akun to use (request takes precedence)
        $kd_akun = $requestKdAkun ?: $sessionKdAkun;

        $cart = session()->get('cart', []);
        
        // Check if cart is empty or doesn't exist
        if (empty($cart) || count($cart) === 0) {
            // Update session kd_akun with the new value
            session(['kd_akun' => $kd_akun]);
        } 
        // Check if cart already has items with different kd_akun
        else {
            $firstItemKdAkun = $cart[0]['kd_akun'] ?? null;
            
            if ($firstItemKdAkun && $firstItemKdAkun != $kd_akun) {
                return redirect()->back()->with('error', 
                    'Kode Akun berbeda dengan kode akun pada keranjang yang sudah ada. '
                    . 'Silakan kosongkan keranjang terlebih dahulu untuk mengubah akun.');
            }
        }

        $tanggalList = $request->input('tanggal', []);

        // Check if any dates were selected
        if (empty($tanggalList)) {
            return redirect()->back()->with('error', 'Pilih minimal satu tanggal untuk ditambahkan ke keranjang.');
        }

        foreach ($tanggalList as $tanggal) {
            $item = [
                'tanggal' => $tanggal,
                'kd_akun' => $kd_akun,
                'nilai' => $request->input('nilai_'.$tanggal) // Assuming you have nilai inputs
            ];

            // Check for duplicates (same tanggal and kd_akun)
            foreach ($cart as $cartItem) {
                if ($cartItem['tanggal'] === $item['tanggal'] && $cartItem['kd_akun'] === $item['kd_akun']) {
                    return redirect()->back()->with('error', 
                        'Item dengan tanggal ' . $item['tanggal'] . ' dan kode akun ini sudah ada di keranjang.');
                }
            }

            $cart[] = $item;
        }

        session()->put('cart', $cart);
        return redirect()->back()->with('success', 'Item telah ditambahkan ke keranjang.');
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
}
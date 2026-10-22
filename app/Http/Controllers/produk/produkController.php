<?php

namespace App\Http\Controllers\produk;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            
            // Gunakan query builder
            $query = DB::table('produk');
            
            // Tambahkan kondisi pencarian jika ada
            if ($search) {
                $query->where('nama_produk', 'like', '%'.$search.'%');
            }
            
            // Pagination dengan 10 item per halaman
            $produk = $query->paginate(10);
            
            return view('produk.view', compact('produk', 'search'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data produk: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('produk.insert');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kd_produk' => 'required|min:2|max:5',
                'nama_produk' => 'required|min:3|max:25'
            ]);

            $data = [
                'kd_produk' => $validated['kd_produk'],
                'nama_produk' => $validated['nama_produk'],
                'harga' => $request->input('harga', 0),
                'keterangan' => $request->input('keterangan', null),
                'created_at' => now(),
                'updated_at' => now()
            ];

            // For BigQuery, generate unique ID if needed
            if (config('database.default') === 'bigquery' && !isset($data['id'])) {
                $data['id'] = Str::uuid()->toString();
            }

            DB::table('produk')->insert($data);
            
            return redirect()->route('produk.view')
                ->with('success', 'Produk berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan produk: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_produk)
    {
        try {
            $produk = DB::table('produk')->where('kd_produk', $kd_produk)->first();
            
            if (!$produk) {
                throw new \Exception('Produk tidak ditemukan');
            }
            
            return view('produk.edit', compact('produk'));
        } catch (\Exception $e) {
            return redirect()->route('produk.view')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $kd_produk)
    {
        try {
            $validated = $request->validate([
                'kd_produk' => 'required|min:2|max:5',
                'nama_produk' => 'required|min:3|max:25'
            ]);

            $updateData = [
                'kd_produk' => $validated['kd_produk'],
                'nama_produk' => $validated['nama_produk'],
                'harga' => $request->input('harga', 0),
                'keterangan' => $request->input('keterangan', null),
                'updated_at' => now()
            ];

            $affected = DB::table('produk')
                        ->where('kd_produk', $kd_produk)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('produk.view')
                ->with('success', 'Produk berhasil diperbarui');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui produk: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_produk)
    {
        try {
            // For BigQuery, use soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('produk')
                    ->where('kd_produk', $kd_produk)
                    ->update(['deleted_at' => now()]);
            } else {
                $affected = DB::table('produk')
                            ->where('kd_produk', $kd_produk)
                            ->delete();
                            
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang dihapus');
                }
            }

            return redirect()->route('produk.view')
                ->with('success', 'Produk berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus produk: '.$e->getMessage());
        }
    }
}
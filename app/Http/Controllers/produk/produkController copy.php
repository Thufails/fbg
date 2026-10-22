<?php

namespace App\Http\Controllers\produk;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

class produkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $produk = Produk::all();
        //print_r($akun);
        return view('produk.view', compact('produk'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('produk.insert');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $produk_valid = $request->validate([
                'kd_produk'=>'required|min:2|max:5',
                'nama_produk'=>'required|min:3|max:25'
            ]);

            $data = [
                'kd_produk' => $produk_valid['kd_produk'],
                'nama_produk' => $produk_valid['nama_produk'],
                'harga' => $request->input('harga', 0), // null jika tidak diisi
                'keterangan' => $request->input('keterangan', null)
            ];

            Produk::create($data);
            return redirect()->route('produk.view')->with('success', 'Produk berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan produk: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_produk)
    {
        //
        $produk = Produk::findOrFail($kd_produk);
        return view('produk.edit', compact('produk'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $kd_produk)
    {
        //
         try {
            $produk_valid = $request->validate([
                'kd_produk'=>'required|min:2|max:5',
                'nama_produk'=>'required|min:3|max:25'
            ]);

            $produk = Produk::findOrFail($kd_produk);
            $produk->update([
                'kd_produk' => $produk_valid['kd_produk'],
                'nama_produk' => $produk_valid['nama_produk'],
                'harga' => $request->input('harga') ?: '0',
                'keterangan' => $request->input('keterangan', null)
            ]);
            return redirect()->route('produk.view')->with('success', 'Produk berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_produk)
    {
        //
        try {
            $produk = Produk::findOrFail($kd_produk);
            $produk->delete();
            return redirect()->route('produk.view')->with('success', 'Produk berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}

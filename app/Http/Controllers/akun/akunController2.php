<?php

namespace App\Http\Controllers\akun;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Akun;

class akunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $akun = Akun::all();
        //print_r($akun);
        return view('akun.view', compact('akun'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $akunList = Akun::all(); // ambil semua akun sebagai induk
        return view('akun.insert', compact('akunList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        //dd($request);
        try {
            $akun_valid = $request->validate([
                'kd_akun'=>'required|min:2|max:10',
                'nama_akun'=>'required|min:3|max:25',
                'urutan' => 'required|numeric|min:1',
                'operasi'=>'required|max:1'
            ]);

            $data = [
                'kd_akun' => $akun_valid['kd_akun'],
                'nama_akun' => $akun_valid['nama_akun'],
                'induk' => $request->input('induk', 0), // null jika tidak diisi
                'keterangan' => $request->input('keterangan', null),
                'urutan' => $akun_valid['urutan'],
                'operasi' => $akun_valid['operasi'],
            ];

            Akun::create($data);
            return redirect()->route('akun.view')->with('success', 'Akun berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan akun: '.$e->getMessage())->withInput();
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
    public function edit(string $kd_akun)
    {
        //
        $akun = Akun::findOrFail($kd_akun);
        $akunList = Akun::where('kd_akun', '!=', $kd_akun)->get(); // hindari memilih diri sendiri
        return view('akun.edit', compact('akun', 'akunList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $kd_akun)
    {
        //
         try {
            $akun_valid = $request->validate([
                'kd_akun' => 'required|min:2|max:10',
                'nama_akun' => 'required|min:3|max:25',
                'urutan' => 'required|numeric|min:1',
                'operasi' => 'required|max:1',
                'induk' => 'nullable',
            ]);

            $akun = Akun::findOrFail($kd_akun);
            $akun->update([
                'kd_akun' => $akun_valid['kd_akun'],
                'nama_akun' => $akun_valid['nama_akun'],
                'induk' => $request->input('induk') ?: '-',
                'keterangan' => $request->input('keterangan', null),
                'urutan' => $akun_valid['urutan'],
                'operasi' => $akun_valid['operasi'],
            ]);
            return redirect()->route('akun.view')->with('success', 'Akun berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui akun: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_akun)
    {
        //
        try {
            $akun = Akun::findOrFail($kd_akun);
            $akun->delete();
            return redirect()->route('akun.view')->with('success', 'Akun berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}

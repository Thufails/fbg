<?php

namespace App\Http\Controllers\entitas;

use App\Http\Controllers\Controller;
use App\Models\Entitas;
use Illuminate\Http\Request;

class entitasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $entitas = Entitas::all();
        //print_r($akun);
        return view('entitas.view', compact('entitas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $entitasList = Entitas::all(); // ambil semua akun sebagai induk
        return view('entitas.insert', compact('entitasList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $entitas_valid = $request->validate([
                'kd_entitas'=>'required|min:2|max:5',
                'nama_entitas'=>'required|min:3|max:25'
            ]);

            $data = [
                'kd_entitas' => $entitas_valid['kd_entitas'],
                'nama_entitas' => $entitas_valid['nama_entitas'],
                'induk' => $request->input('induk', 0), // null jika tidak diisi
                'keterangan' => $request->input('keterangan', null)
            ];

            Entitas::create($data);
            return redirect()->route('entitas.view')->with('success', 'Entitas berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan entitas: '.$e->getMessage())->withInput();
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
    public function edit(string $kd_entitas)
    {
        //
        $entitas = Entitas::findOrFail($kd_entitas);
        $entitasList = Entitas::where('kd_entitas', '!=', $kd_entitas)->get(); // hindari memilih diri sendiri
        return view('entitas.edit', compact('entitas', 'entitasList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $kd_entitas)
    {
        //
         try {
            $entitas_valid = $request->validate([
                'kd_entitas'=>'required|min:2|max:5',
                'nama_entitas'=>'required|min:3|max:25'
            ]);

            $entitas = Entitas::findOrFail($kd_entitas);
            $entitas->update([
                'kd_entitas' => $entitas_valid['kd_entitas'],
                'nama_entitas' => $entitas_valid['nama_entitas'],
                'induk' => $request->input('induk') ?: '-',
                'keterangan' => $request->input('keterangan', null)
            ]);
            return redirect()->route('entitas.view')->with('success', 'Entitas berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui entitas: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_entitas)
    {
        //
        try {
            $entitas = Entitas::findOrFail($kd_entitas);
            $entitas->delete();
            return redirect()->route('entitas.view')->with('success', 'Entitas berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus entitas: ' . $e->getMessage());
        }
    }
}

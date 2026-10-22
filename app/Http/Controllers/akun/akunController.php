<?php

namespace App\Http\Controllers\akun;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Akun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AkunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            
            $query = DB::table('akun')->orderBy('urutan');
            
            if ($search) {
                $query->where('nama_akun', 'like', '%' . $search . '%');
            }
            
            $akun = $query->paginate(10);
            
            return view('akun.view', compact('akun', 'search'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data akun: '.$e->getMessage());
        }
    }

    
    public function show($kd_akun)
    {
        try {
            $akun = DB::table('akun')
                ->where('kd_akun', $kd_akun)
                ->first();

            if (!$akun) {
                return redirect()->route('akun.view')
                    ->with('error', 'Akun dengan kode '.$kd_akun.' tidak ditemukan');
            }

            $detailAkun = DB::table('detail_akun')
                ->where('kd_akun', $kd_akun)
                ->get();

            return view('akun.detail', compact('akun', 'detailAkun'));

        } catch (\Exception $e) {
            return redirect()->route('akun.view')
                ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $akunList = DB::table('akun')->get();
            return view('akun.insert', compact('akunList'));
        } catch (\Exception $e) {
            return redirect()->route('akun.view')
                ->with('error', 'Gagal memuat form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kd_akun' => 'required|min:2|max:10',
                'nama_akun' => 'required|min:3|max:25',
                'urutan' => 'required|numeric|min:1',
                'operasi' => 'required|max:1'
            ]);

            $data = [
                'kd_akun' => $validated['kd_akun'],
                'nama_akun' => $validated['nama_akun'],
                'induk' => $request->input('induk', '-'),
                'keterangan' => $request->input('keterangan', null),
                'urutan' => $validated['urutan'],
                'operasi' => $validated['operasi']
            ];

            // Cek apakah kolom timestamps ada
            // if (Schema::hasColumn('akun', 'created_at')) {
            //     $data['created_at'] = now();
            //     $data['updated_at'] = now();
            // }

            // Gunakan insert langsung untuk menghindari Eloquent behavior
            DB::table('akun')->insert($data);
            
            return redirect()->route('akun.view')
                ->with('success', 'Akun berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan akun: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_akun)
    {
        try {
            $akun = DB::table('akun')->where('kd_akun', $kd_akun)->first();
            
            if (!$akun) {
                throw new \Exception('Akun tidak ditemukan');
            }
            
            $akunList = DB::table('akun')
                         ->where('kd_akun', '!=', $kd_akun)
                         ->get();
                         
            return view('akun.edit', compact('akun', 'akunList'));
        } catch (\Exception $e) {
            return redirect()->route('akun.view')
                ->with('error', 'Gagal memuat form edit: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $kd_akun)
    {
        try {
            $validated = $request->validate([
                'kd_akun' => 'required|min:2|max:10',
                'nama_akun' => 'required|min:3|max:25',
                'urutan' => 'required|numeric|min:1',
                'operasi' => 'required|max:1'
            ]);

            $updateData = [
                'kd_akun' => $validated['kd_akun'],
                'nama_akun' => $validated['nama_akun'],
                'induk' => $request->input('induk', '-'),
                'keterangan' => $request->input('keterangan', null),
                'urutan' => $validated['urutan'],
                'operasi' => $validated['operasi']
            ];

            $affected = DB::table('akun')
                        ->where('kd_akun', $kd_akun)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('akun.view')
                ->with('success', 'Akun berhasil diperbarui');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui akun: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
   public function destroy(string $kd_akun)
    {
        try {
            // Untuk BigQuery, gunakan soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('akun')
                    ->where('kd_akun', $kd_akun)
                    ->update(['deleted_at' => now()]);
                    
                // Soft delete related records in detail_akun
                DB::table('detail_akun')
                    ->where('kd_akun', $kd_akun)
                    ->update(['deleted_at' => now()]);
                    
            } else {
                // Mulai transaksi untuk operasi atomik
                DB::beginTransaction();
                
                try {
                    // Hapus dari tabel akun
                    $affectedAkun = DB::table('akun')
                        ->where('kd_akun', $kd_akun)
                        ->delete();
                        
                    if ($affectedAkun === 0) {
                        throw new \Exception('Tidak ada data akun yang dihapus');
                    }
                    
                    // Hapus related records dari detail_akun
                    $affectedDetail = DB::table('detail_akun')
                        ->where('kd_akun', $kd_akun)
                        ->delete();
                        
                    DB::commit();
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return redirect()->route('akun.view')
                ->with('success', 'Akun dan detail akun terkait berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }
}
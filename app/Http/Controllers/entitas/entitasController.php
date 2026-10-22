<?php

namespace App\Http\Controllers\entitas;

use App\Http\Controllers\Controller;
use App\Models\Entitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntitasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Gunakan query builder untuk kompatibilitas yang lebih baik
            $entitas = DB::table('entitas')->orderBy('kd_entitas')->get();
            
            return view('entitas.view', compact('entitas'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data entitas: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $entitasList = DB::table('entitas')->get();
            return view('entitas.insert', compact('entitasList'));
        } catch (\Exception $e) {
            return redirect()->route('entitas.view')
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
                'kd_entitas' => 'required|min:2|max:5',
                'nama_entitas' => 'required|min:3|max:25'
            ]);

            $data = [
                'kd_entitas' => $validated['kd_entitas'],
                'nama_entitas' => $validated['nama_entitas'],
                'induk' => $request->input('induk', '-'),
                'keterangan' => $request->input('keterangan', null)
            ];

            // Hanya tambahkan timestamps jika kolom ada
            // if (Schema::hasColumn('entitas', 'created_at')) {
            //     $data['created_at'] = now();
            //     $data['updated_at'] = now();
            // }

            // Gunakan insert langsung untuk menghindari Eloquent behavior
            DB::table('entitas')->insert($data);
            
            return redirect()->route('entitas.view')
                ->with('success', 'Entitas berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan entitas: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_entitas)
    {
        try {
            $entitas = DB::table('entitas')->where('kd_entitas', $kd_entitas)->first();
            
            if (!$entitas) {
                throw new \Exception('Entitas tidak ditemukan');
            }
            
            $entitasList = DB::table('entitas')
                         ->where('kd_entitas', '!=', $kd_entitas)
                         ->get();
                         
            return view('entitas.edit', compact('entitas', 'entitasList'));
        } catch (\Exception $e) {
            return redirect()->route('entitas.view')
                ->with('error', 'Gagal memuat form edit: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    
    public function update(Request $request, string $kd_entitas)
    {
        try {
            $validated = $request->validate([
                'kd_entitas' => 'required|min:2|max:5',
                'nama_entitas' => 'required|min:3|max:25'
            ]);

            // Cek apakah ada perubahan data
            $existingData = DB::table('entitas')
                            ->where('kd_entitas', $kd_entitas)
                            ->first();

            if (!$existingData) {
                throw new \Exception('Entitas tidak ditemukan');
            }

            $updateData = [
                'nama_entitas' => $validated['nama_entitas'],
                'induk' => $request->input('induk', '-'),
                'keterangan' => $request->input('keterangan', null)
            ];

            // Jika kode entitas berubah, tambahkan ke updateData
            if ($validated['kd_entitas'] !== $kd_entitas) {
                $updateData['kd_entitas'] = $validated['kd_entitas'];
            }

            // Hanya update jika ada perubahan
            $hasChanges = false;
            foreach ($updateData as $key => $value) {
                if ($existingData->$key != $value) {
                    $hasChanges = true;
                    break;
                }
            }

            if (!$hasChanges) {
                return redirect()->route('entitas.view')
                    ->with('info', 'Tidak ada perubahan data');
            }

            // Tambahkan updated_at jika kolom ada
            // if (Schema::hasColumn('entitas', 'updated_at')) {
            //     $updateData['updated_at'] = now();
            // }

            $affected = DB::table('entitas')
                        ->where('kd_entitas', $kd_entitas)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Gagal memperbarui entitas. Data mungkin sudah diubah oleh pengguna lain.');
            }

            return redirect()->route('entitas.view')
                ->with('success', 'Entitas berhasil diperbarui');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_entitas)
    {
        try {
            // Untuk BigQuery, gunakan soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('entitas')
                    ->where('kd_entitas', $kd_entitas)
                    ->update(['deleted_at' => now()]);
            } else {
                $affected = DB::table('entitas')
                            ->where('kd_entitas', $kd_entitas)
                            ->delete();
                            
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang dihapus');
                }
            }

            return redirect()->route('entitas.view')
                ->with('success', 'Entitas berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus entitas: '.$e->getMessage());
        }
    }
}
<?php

namespace App\Http\Controllers\tugas;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TugasController extends Controller
{
    // Bulan dalam Bahasa Indonesia
    private $bulanIndonesia = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Gunakan model untuk kompatibilitas yang lebih baik
            $query = Tugas::with('user') // Asumsi ada relasi user di model
                ->leftJoin('users', 'tugas.kd_user', '=', 'users.id')
                ->select('tugas.*', 'users.name');

            // Filter tanggal yang kompatibel dengan kedua database
            if ($startDate && $endDate) {
                $query->where('tugas.tanggal', '>=', $startDate)
                      ->where('tugas.tanggal', '<=', $endDate);
            }

            // Paginasi alternatif untuk BigQuery
            $perPage = 20;
            if (config('database.default') === 'bigquery') {
                $tugas = $query->simplePaginate($perPage);
            } else {
                $tugas = $query->paginate($perPage);
            }

            // Mapping status yang lebih efisien
            $statusMap = [
                0 => 'Plan',
                1 => 'Proses',
                2 => 'Done'
            ];

            $tugas->getCollection()->transform(function ($item) use ($statusMap) {
                $item->status = $statusMap[$item->status] ?? 'Unknown';
                return $item;
            });

            return view('tugas.view', compact('tugas', 'startDate', 'endDate'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tugas.insert');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date'
            ]);

            $tanggal = Carbon::parse($request->tanggal);
            
            $data = [
                'uraian' => $validated['uraian'],
                'tanggal' => $tanggal->format('Y-m-d'),
                'bulan' => $this->bulanIndonesia[$tanggal->month],
                'kd_user'=> Auth::user()->id,
                'tahun' => $tanggal->year,
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Untuk BigQuery, generate ID manual
            if (config('database.default') === 'bigquery') {
                $data['kd_tugas'] = Str::uuid()->toString();
            }

            Tugas::create($data);
            
            return redirect()->route('tugas.view')
                ->with('success', 'Tugas berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan tugas: '.$e->getMessage())
                ->withInput();
        }
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_tugas)
    {
        try {
            $tugas = Tugas::findOrFail($kd_tugas);
            return view('tugas.edit', compact('tugas'));
        } catch (\Exception $e) {
            return redirect()->route('tugas.view')
                ->with('error', 'Tugas tidak ditemukan');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_tugas)
    {
        try {
            $validated = $request->validate([
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date',
                'status' => 'required|numeric'
            ]);

            $tanggal = Carbon::parse($request->tanggal);
            
            $updateData = [
                'uraian' => $validated['uraian'],
                'tanggal' => $tanggal->format('Y-m-d'),
                'bulan' => $this->bulanIndonesia[$tanggal->month],
                'kd_user'=> Auth::user()->id,
                'tahun' => $tanggal->year,
                'status' => $validated['status'],
                'updated_at' => now()
            ];

            // Untuk BigQuery, gunakan query builder langsung
            if (config('database.default') === 'bigquery') {
                DB::table('tugas')
                    ->where('kd_tugas', $kd_tugas)
                    ->update($updateData);
            } else {
                $tugas = Tugas::findOrFail($kd_tugas);
                $tugas->update($updateData);
            }

            return redirect()->route('tugas.view')
                ->with('success', 'Tugas berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah tugas: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_tugas)
    {
        try {
            // Untuk BigQuery, gunakan soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('tugas')
                    ->where('kd_tugas', $kd_tugas)
                    ->update(['deleted_at' => now()]);
            } else {
                // Untuk MySQL, gunakan query builder langsung dengan primary key yang benar
                $tugas = DB::table('tugas')->where('kd_tugas', $kd_tugas)->first();
                
                if (!$tugas) {
                    throw new \Exception('Tugas tidak ditemukan');
                }

                DB::table('tugas')->where('kd_tugas', $kd_tugas)->delete();
            }

            return redirect()->route('tugas.view')
                ->with('success', 'Tugas berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus tugas: ' . $e->getMessage());
        }
    }


    /**
     * Search tugas for AJAX requests
     */
    public function search(Request $request)
    {
        $search = $request->get('search');
        
        $tugas = Tugas::select('kd_tugas', 'uraian', 'tanggal', 'status') // Pilih kolom, termasuk status
            ->when($search, function($query) use ($search) {
                return $query->where('uraian', 'like', '%' . $search . '%');
            })
            ->orderBy('tanggal', 'desc')
            ->limit(50)
            ->get();
        
        return response()->json([
            'tugas' => $tugas
        ]);
    }

    /**
     * Show the specified resource (untuk konsistensi)
     */
    public function show(string $kd_tugas)
    {
        // Method ini diperlukan untuk route dengan parameter
        try {
            $tugas = Tugas::findOrFail($kd_tugas);
            return view('tugas.detail', compact('tugas'));
        } catch (\Exception $e) {
            return redirect()->route('tugas.view')
                ->with('error', 'Tugas tidak ditemukan');
        }
    }

    /**
     * Get selected tugas for AJAX requests - SAMA SEPERTI ISSUE DAN KOMPLAIN
     */
    public function getSelected(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        
        $tugas = Tugas::whereIn('kd_tugas', $ids)->get();
        
        return response()->json([
            'tugas' => $tugas
        ]);
    }
}
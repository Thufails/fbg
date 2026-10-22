<?php

namespace App\Http\Controllers\analisis;

use App\Http\Controllers\Controller;
use App\Models\Analisis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class analisisController extends Controller
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
            
            $query = DB::table('analisis')
                ->leftJoin('users', 'analisis.kd_user', '=', 'users.id')
                ->select('analisis.*', 'users.name')
                ->orderBy('analisis.created_at', 'desc');

            // Filter by date range if provided
            if ($startDate && $endDate) {
                $query->whereBetween('analisis.created_at', [$startDate, $endDate]);
            }

            // Pagination
            $perPage = 20;
            $analisis = $query->paginate($perPage);

            return view('analisis.view', compact('analisis', 'startDate', 'endDate'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data analisis: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */

    /**
     * Display the specified resource.
     */
    public function show($kd_analisis)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $analisis = DB::table('analisis')
                    ->leftJoin('users', 'analisis.kd_user', '=', 'users.id')
                    ->leftJoin('laporan', 'analisis.kd_laporan', '=', 'laporan.kd_laporan')
                    ->select('analisis.*', 'users.name', 'laporan.keterangan as laporan_keterangan')
                    ->where('analisis.kd_analisis', $kd_analisis)
                    ->first();
            } else {
                $analisis = DB::table('analisis')
                    ->leftJoin('users', 'analisis.kd_user', '=', 'users.id')
                    ->leftJoin('laporan', 'analisis.kd_laporan', '=', 'laporan.kd_laporan')
                    ->select('analisis.*', 'users.name', 'laporan.keterangan as laporan_keterangan')
                    ->where('analisis.kd_analisis', $kd_analisis)
                    ->first();
            }

            if (!$analisis) {
                throw new \Exception('Analisis tidak ditemukan');
            }

            return view('analisis.detail', compact('analisis'));

        } catch (\Exception $e) {
            return redirect()->route('analisis.view')
                ->with('error', $e->getMessage());
        }
    }

    public function create()
    {
        // Get list of laporan for dropdown
        $laporanList = DB::table('laporan')->get();
        return view('analisis.insert', compact('laporanList'));
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
                'bulan' => $this->bulanIndonesia[$tanggal->month],
                'tahun' => $tanggal->year,
                'kd_user'=> Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Hapus kd_laporan jika tidak ada di tabel
            if ($request->has('kd_laporan')) {
                $data['kd_laporan'] = $request->kd_laporan;
            }

            DB::table('analisis')->insert($data);
            
            return redirect()->route('analisis.view')
                ->with('success', 'Analisis berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan analisis: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_analisis)
    {
        try {
            $analisis = DB::table('analisis')->where('kd_analisis', $kd_analisis)->first();
            
            if (!$analisis) {
                throw new \Exception('Analisis tidak ditemukan');
            }
            
            // Get list of laporan for dropdown
            $laporanList = DB::table('laporan')->get();
            
            return view('analisis.edit', compact('analisis', 'laporanList'));
        } catch (\Exception $e) {
            return redirect()->route('analisis.view')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_analisis)
    {
        try {
            $validated = $request->validate([
                'kd_laporan' => 'required|exists:laporan,kd_laporan',
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date'
            ]);

            $tanggal = Carbon::parse($request->tanggal);
            
            $updateData = [
                'kd_laporan' => $validated['kd_laporan'],
                'kd_user'=> Auth::user()->id,
                'uraian' => $validated['uraian'],
                'bulan' => $this->bulanIndonesia[$tanggal->month],
                'tahun' => $tanggal->year,
                'updated_at' => now()
            ];

            $affected = DB::table('analisis')
                        ->where('kd_analisis', $kd_analisis)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('analisis.view')
                ->with('success', 'Analisis berhasil diperbarui.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah analisis: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_analisis)
    {
        try {
            // For BigQuery, use soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('analisis')
                    ->where('kd_analisis', $kd_analisis)
                    ->update(['deleted_at' => now()]);
            } else {
                $affected = DB::table('analisis')
                            ->where('kd_analisis', $kd_analisis)
                            ->delete();
                            
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang dihapus');
                }
            }

            return redirect()->route('analisis.view')
                ->with('success', 'Analisis berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus analisis: '.$e->getMessage());
        }
    }
}
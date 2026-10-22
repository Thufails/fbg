<?php

namespace App\Http\Controllers\komplain;

use App\Http\Controllers\Controller;
use App\Models\Komplain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Add this line
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\FacadesLog;

class komplainController extends Controller
{
    // Bulan dalam Bahasa Indonesia (digunakan di beberapa method)
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
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = DB::table('komplain')
                ->leftJoin('users', 'komplain.kd_user', '=', 'users.id')
                ->select('komplain.*', 'users.name');

            if ($startDate && $endDate) {
                $query->whereBetween('komplain.tanggal', [$startDate, $endDate]);
            }

            $perPage = 20;
            $komplain = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            // Mapping status - safe method for both paginated and non-paginated results
            $statusMap = [0 => 'Plan', 1 => 'Proses', 2 => 'Done'];
            
            if (method_exists($komplain, 'getCollection')) {
                // For paginated results
                $komplain->getCollection()->transform(function ($item) use ($statusMap) {
                    $item->status = $statusMap[$item->status] ?? 'Unknown';
                    return $item;
                });
            } else {
                // For regular collections (if you change to get() instead of paginate)
                $komplain = $komplain->map(function ($item) use ($statusMap) {
                    $item->status = $statusMap[$item->status] ?? 'Unknown';
                    return $item;
                });
            }

            return view('komplain.view', compact('komplain', 'startDate', 'endDate'));

        } catch (\Exception $e) {
            return redirect()->route('komplain.view')
                ->with('error', 'Gagal mengambil data: '.$e->getMessage());
        }
    }        

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('komplain.insert');
    }


    public function show(string $kd_komplain)
    {
        try {
            // Get complaint data with user information
            if (config('database.default') === 'bigquery') {
                $komplain = DB::table('komplain')
                    ->leftJoin('users', 'komplain.kd_user', '=', 'users.id')
                    ->select('komplain.*', 'users.name')
                    ->where('komplain.kd_komplain', $kd_komplain)
                    ->first();
            } else {
                $komplain = Komplain::with('user')
                    ->where('kd_komplain', $kd_komplain)
                    ->first();
            }

            // Check if complaint exists
            if (!$komplain) {
                throw new \Exception('Data komplain tidak ditemukan');
            }

            // Map status code to text
            $statusMap = [
                0 => 'Plan',
                1 => 'Proses', 
                2 => 'Selesai'
            ];
            
            // Convert status code to text
            $komplain->status = $statusMap[$komplain->status] ?? 'Tidak diketahui';

            // Convert dates to Carbon instances for BigQuery
            if (config('database.default') === 'bigquery') {
                $komplain->created_at = Carbon::parse($komplain->created_at);
                $komplain->updated_at = Carbon::parse($komplain->updated_at);
                $komplain->tanggal = Carbon::parse($komplain->tanggal);
            }

            return view('komplain.detail', compact('komplain'));

        } catch (\Exception $e) {
            return redirect()
                ->route('komplain.view')
                ->with('error', 'Gagal menampilkan detail: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date',
                'sumber' => 'required|max:255',
                'kategori' => 'required|max:255', // Make sure this matches your form field name
                'prioritas' => 'required|max:255',
                'status' => 'required|numeric'
            ]);

            $tanggal = Carbon::parse($request->tanggal);
            
            $data = [
                'uraian' => $validated['uraian'],
                'kd_user'=> Auth::user()->id,
                'tanggal' => $tanggal->format('Y-m-d'),
                'bulan' => $this->bulanIndonesia[$tanggal->month],
                'tahun' => $tanggal->year,
                'sumber' => $validated['sumber'],
                'kategori' => $validated['kategori'], // Ensure this is included
                'prioritas' => $validated['prioritas'], // Ensure this is included
                'status' => $validated['status'],
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Generate unique ID for BigQuery
            if (config('database.default') === 'bigquery') {
                $data['kd_komplain'] = Str::uuid()->toString();
                
                // Use DB facade for BigQuery
                DB::table('komplain')->insert($data);
            } else {
                // For MySQL, use Eloquent
                Komplain::create($data);
            }
            
            return redirect()->route('komplain.view')
                ->with('success', 'Komplain berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan komplain: '.$e->getMessage())
                ->withInput();
        }
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_komplain)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $komplain = DB::table('komplain')
                            ->where('kd_komplain', $kd_komplain)
                            ->first();
            } else {
                $komplain = Komplain::where('kd_komplain', $kd_komplain)->first();
            }

            if (!$komplain) {
                return redirect()->route('komplain.view')
                            ->with('error', 'Komplain tidak ditemukan');
            }

            return view('komplain.edit', compact('komplain'));

        } catch (\Exception $e) {
            return redirect()->route('komplain.view')
                        ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    
    public function update(Request $request, $kd_komplain)
    {
        try {
            $validated = $request->validate([
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date',
                'sumber' => 'required|max:255',
                'kategori' => 'required|max:255',
                'prioritas' => 'required|max:255',
                'status' => 'required|numeric'
            ]);

            $tanggal = Carbon::parse($request->tanggal);
            
            $updateData = [
                'uraian' => $validated['uraian'],
                'kd_user'=> Auth::user()->id,
                'tanggal' => $tanggal->format('Y-m-d'),
                'bulan' => $this->bulanIndonesia[$tanggal->month],
                'tahun' => $tanggal->year,
                'sumber' => $validated['sumber'],
                'kategori' => $validated['kategori'],
                'prioritas' => $validated['prioritas'],
                'status' => $validated['status'],
                'updated_at' => now()
            ];

            if (config('database.default') === 'bigquery') {
                $affected = DB::table('komplain')
                    ->where('kd_komplain', $kd_komplain)
                    ->update($updateData);
                    
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang diupdate');
                }
            } else {
                $komplain = Komplain::where('kd_komplain', $kd_komplain)->first();
                
                if (!$komplain) {
                    throw new \Exception('Komplain tidak ditemukan');
                }
                
                $komplain->update($updateData);
            }

            return redirect()->route('komplain.view')
                ->with('success', 'Komplain berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah komplain: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_komplain)
    {
        try {
            // Untuk BigQuery, mungkin perlu soft delete
            if (config('database.default') === 'bigquery') {
                DB::table('komplain')
                    ->where('kd_komplain', $kd_komplain)
                    ->update(['deleted_at' => now()]);
            } else {
                $komplain = Komplain::findOrFail($kd_komplain);
                $komplain->delete();
            }

            return redirect()->route('komplain.view')
                ->with('success', 'Komplain berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus komplain: ' . $e->getMessage());
        }
    }

    /**
     * Search komplain for AJAX requests - DISAMAKAN DENGAN ISSUE
     */
    public function search(Request $request)
    {

        $search = $request->get('search');
        
        $komplains = Komplain::when($search, function($query) use ($search) {
                     return $query->where('uraian', 'like', '%' . $search . '%');
                })
                ->orderBy('tanggal', 'desc')
                ->limit(50)
                ->get();
        return response()->json([
            'komplains' => $komplains
        ]);
    }

    /**
     * Get selected komplains for AJAX requests - DISAMAKAN DENGAN ISSUE
     */
    public function getSelected(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        
        $komplains = Komplain::whereIn('kd_komplain', $ids)->get();
        
        return response()->json([
            'komplain' => $komplains
        ]);
    }
}
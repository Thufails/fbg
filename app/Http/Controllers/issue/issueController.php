<?php

namespace App\Http\Controllers\issue;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class issueController extends Controller
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
            
            $query = DB::table('issue')
                ->leftJoin('users', 'issue.kd_user', '=', 'users.id')
                ->select('issue.*', 'users.name')
                ->orderBy('issue.tanggal', 'desc');

            // Filter by date range if provided
            if ($startDate && $endDate) {
                $query->whereBetween('issue.tanggal', [$startDate, $endDate]);
            }

            // Pagination
            $perPage = 20;
            $issues = $query->paginate($perPage);

            // Mapping status
            $statusMap = [0 => 'Plan', 1 => 'Proses', 2 => 'Done'];
            
            $issues->getCollection()->transform(function ($issue) use ($statusMap) {
                $issue->status = $statusMap[$issue->status] ?? 'Unknown';
                return $issue;
            });

            return view('issue.view', compact('issues', 'startDate', 'endDate'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data issue: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('issue.insert');
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

            // For BigQuery, generate unique ID if needed
            if (config('database.default') === 'bigquery' && !isset($data['kd_issue'])) {
                $data['kd_issue'] = Str::uuid()->toString();
            }

            DB::table('issue')->insert($data);
            
            return redirect()->route('issue.view')
                ->with('success', 'Issue berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan issue: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kd_issue)
    {
        try {
            $issue = DB::table('issue')->where('kd_issue', $kd_issue)->first();
            
            if (!$issue) {
                throw new \Exception('Issue tidak ditemukan');
            }
            
            return view('issue.edit', compact('issue'));
        } catch (\Exception $e) {
            return redirect()->route('issue.view')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_issue)
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

            $affected = DB::table('issue')
                        ->where('kd_issue', $kd_issue)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('issue.view')
                ->with('success', 'Issue berhasil diperbarui.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah issue: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kd_issue)
    {
        try {
            // For BigQuery, use soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('issue')
                    ->where('kd_issue', $kd_issue)
                    ->update(['deleted_at' => now()]);
            } else {
                $affected = DB::table('issue')
                            ->where('kd_issue', $kd_issue)
                            ->delete();
                            
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang dihapus');
                }
            }

            return redirect()->route('issue.view')
                ->with('success', 'Issue berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus issue: '.$e->getMessage());
        }
    }


    public function search(Request $request)
    {
        $search = $request->get('search');
        
        $issues = Issue::when($search, function($query) use ($search) {
                    return $query->where('uraian', 'like', '%' . $search . '%');
                })
                ->orderBy('tanggal', 'desc')
                ->limit(50)
                ->get();
        
        return response()->json([
            'issues' => $issues
        ]);
    }

    public function getSelected(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        
        $issues = Issue::whereIn('kd_issue', $ids)->get();
        
        return response()->json([
            'issues' => $issues
        ]);
    }
}
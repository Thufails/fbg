<?php

namespace App\Http\Controllers\issue;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class issueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Melakukan join dengan tabel user
        $issues = DB::table('issue')
            ->leftJoin('users', 'issue.kd_user', '=', 'users.id') // Sesuaikan dengan nama kolom yang benar
            ->select('issue.*', 'users.name') // Ambil semua kolom dari issue dan username dari users
            ->get();

        // Mengubah status menjadi deskripsi
            foreach ($issues as $issue) {
                switch ($issue->status) {
                    case 0:
                        $issue->status = 'Plan';
                        break;
                    case 1:
                        $issue->status = 'Proses';
                        break;
                    case 2:
                        $issue->status = 'Done';
                        break;
                    default:
                        $issue->status = 'Unknown'; // Jika status tidak dikenali
                        break;
                }
            }

        return view('issue.view', compact('issues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('issue.insert');
    }

    /**
     * Store a newly created resource in storage.
     */
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

            // Konversi tanggal
            $tanggal = \Carbon\Carbon::parse($request->tanggal);
            
            // Nama bulan dalam bahasa Indonesia
            $bulanIndonesia = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            
            $data = [
                'uraian' => $validated['uraian'],
                'tanggal' => $tanggal->format('Y-m-d'), // Simpan format tanggal standar
                'bulan' => $bulanIndonesia[$tanggal->month], // Bulan dalam bahasa Indonesia
                'tahun' => $tanggal->year, // Tahun 4 digit
              //  'kd_user' => auth()->id(), // ID user yang sedang login
                'status' => 0 // Default status aktif
            ];

            Issue::create($data);
            
            return redirect()->route('issue.view')
                ->with('success', 'Issue berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan issue: '.$e->getMessage())
                ->withInput();
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
    public function edit(string $kd_issue)
    {
        try {
            $issue = Issue::findOrFail($kd_issue);
           
            return view('issue.edit', compact('issue'));
        } catch (\Exception $e) {
            return redirect()->route('issue.view')
                ->with('error', 'Issue tidak ditemukan');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_issue)
    {
        // Validasi input
        try{
            $validated = $request->validate([
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date'
            ]);

            // Konversi tanggal
            $tanggal = \Carbon\Carbon::parse($request->tanggal);
            
            // Nama bulan dalam bahasa Indonesia
            $bulanIndonesia = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            

            // Temukan issue berdasarkan kode
            $issue = Issue::findOrFail($kd_issue);

            $bulan = $bulanIndonesia[$tanggal->month]; // Bulan dalam bahasa Indonesia
            $tahun = $tanggal->year; // Tahun 4 digit
            $tanggal = $tanggal->format('Y-m-d'); // Simpan format tanggal standar


            // Perbarui data issue
            $issue->kd_issue = $request->kd_issue;
            $issue->tanggal = $tanggal;
            $issue->uraian = $request->uraian;
            $issue->bulan = $bulan;
            $issue->tahun = $tahun;
            $issue->status = $request->status;

            // Simpan perubahan
            $issue->save();

            // Redirect dengan pesan sukses
            return redirect()->route('issue.view')->with('success', 'Issue berhasil diperbarui.');
        }catch(\Exception $e){
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
        //
        try {
            $issue = Issue::findOrFail($kd_issue);
            $issue->delete();
            return redirect()->route('issue.view')->with('success', 'Issue berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus issue: ' . $e->getMessage());
        }
    }
}

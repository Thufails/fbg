<?php

namespace App\Http\Controllers\komplain;

use App\Http\Controllers\Controller;
use App\Models\Komplain; // Pastikan model ini ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class komplainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil parameter pencarian dari request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Melakukan join dengan tabel user
        $query = DB::table('komplain')
            ->leftJoin('users', 'komplain.kd_user', '=', 'users.id')
            ->select('komplain.*', 'users.name');

        // Filter berdasarkan rentang tanggal jika ada
        if ($startDate && $endDate) {
            $query->whereBetween('komplain.tanggal', [$startDate, $endDate]);
        }

        // Ambil data dengan paginasi
        $komplain = $query->paginate(20); // Ganti 10 dengan jumlah item per halaman yang diinginkan

        // Mengubah status menjadi deskripsi
        foreach ($komplain as $komplainItem) {
            switch ($komplainItem->status) {
                case 0:
                    $komplainItem->status = 'Plan';
                    break;
                case 1:
                    $komplainItem->status = 'Proses';
                    break;
                case 2:
                    $komplainItem->status = 'Done';
                    break;
                default:
                    $komplainItem->status = 'Unknown';
                    break;
            }
        }

        return view('komplain.view', compact('komplain', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('komplain.insert');
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
                'sumber' => 'required|max:255' // Validasi untuk kolom sumber
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
                'status' => 0, // Default status aktif
                'sumber' => $validated['sumber'] // Menyimpan sumber
            ];

            Komplain::create($data);
            
            return redirect()->route('komplain.view')
                ->with('success', 'Komplain berhasil ditambahkan');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan komplain: '.$e->getMessage())
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
    public function edit(string $kd_komplain)
    {
        try {
            $komplain = Komplain::findOrFail($kd_komplain);
           
            return view('komplain.edit', compact('komplain'));
        } catch (\Exception $e) {
            return redirect()->route('komplain.view')
                ->with('error', 'Komplain tidak ditemukan');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_komplain)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'uraian' => 'required|max:255',
                'tanggal' => 'required|date',
                'sumber' => 'required|max:255' // Validasi untuk kolom sumber
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
            
            // Temukan komplain berdasarkan kode
            $komplain = Komplain::findOrFail($kd_komplain);

            $bulan = $bulanIndonesia[$tanggal->month]; // Bulan dalam bahasa Indonesia
            $tahun = $tanggal->year; // Tahun 4 digit
            $tanggal = $tanggal->format('Y-m-d'); // Simpan format tanggal standar

            // Perbarui data komplain
            $komplain->kd_komplain = $request->kd_komplain;
            $komplain->tanggal = $tanggal;
            $komplain->uraian = $request->uraian;
            $komplain->bulan = $bulan;
            $komplain->tahun = $tahun;
            $komplain->status = $request->status;
            $komplain->sumber = $validated['sumber']; // Menyimpan sumber

            // Simpan perubahan
            $komplain->save();

            // Redirect dengan pesan sukses
            return redirect()->route('komplain.view')->with('success', 'Komplain berhasil diperbarui.');
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
            $komplain = Komplain::findOrFail($kd_komplain);
            $komplain->delete();
            return redirect()->route('komplain.view')->with('success', 'Komplain berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus komplain: ' . $e->getMessage());
        }
    }
}

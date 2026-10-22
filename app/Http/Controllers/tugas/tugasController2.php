<?php

namespace App\Http\Controllers\tugas;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TugasController extends Controller
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
        $query = DB::table('tugas')
            ->leftJoin('users', 'tugas.kd_user', '=', 'users.id')
            ->select('tugas.*', 'users.name');

        // Filter berdasarkan rentang tanggal jika ada
        if ($startDate && $endDate) {
            $query->whereBetween('tugas.tanggal', [$startDate, $endDate]);
        }

        // Ambil data dengan paginasi
        $tugas = $query->paginate(20); // Ganti 10 dengan jumlah item per halaman yang diinginkan

        // Mengubah status menjadi deskripsi
        foreach ($tugas as $tugasItem) {
            switch ($tugasItem->status) {
                case 0:
                    $tugasItem->status = 'Plan';
                    break;
                case 1:
                    $tugasItem->status = 'Proses';
                    break;
                case 2:
                    $tugasItem->status = 'Done';
                    break;
                default:
                    $tugasItem->status = 'Unknown';
                    break;
            }
        }

        return view('tugas.view', compact('tugas', 'startDate', 'endDate'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
                'status' => 0 // Default status aktif
            ];

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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
            

            // Temukan tugas berdasarkan kode
            $tugas = Tugas::findOrFail($kd_tugas);

            $bulan = $bulanIndonesia[$tanggal->month]; // Bulan dalam bahasa Indonesia
            $tahun = $tanggal->year; // Tahun 4 digit
            $tanggal = $tanggal->format('Y-m-d'); // Simpan format tanggal standar


            // Perbarui data tugas
            $tugas->kd_tugas = $request->kd_tugas;
            $tugas->tanggal = $tanggal;
            $tugas->uraian = $request->uraian;
            $tugas->bulan = $bulan;
            $tugas->tahun = $tahun;
            $tugas->status = $request->status;

            // Simpan perubahan
            $tugas->save();

            // Redirect dengan pesan sukses
            return redirect()->route('tugas.view')->with('success', 'Tugas berhasil diperbarui.');
        }catch(\Exception $e){
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
        //
        try {
            $tugas = Tugas::findOrFail($kd_tugas);
            $tugas->delete();
            return redirect()->route('tugas.view')->with('success', 'Tugas berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus tugas: ' . $e->getMessage());
        }
    }
}
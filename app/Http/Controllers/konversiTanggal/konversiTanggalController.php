<?php

namespace App\Http\Controllers\konversiTanggal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class konversiTanggalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            
            // Gunakan query builder
            $query = DB::table('konversi_tanggal');
            
            // Tambahkan kondisi pencarian jika ada
            if ($search) {
                $query->where('tanggal', 'like', '%'.$search.'%')
                      ->orWhere('tanggal_h', 'like', '%'.$search.'%')
                      ->orWhere('tahun_h', 'like', '%'.$search.'%');
            }
            
            // Pagination dengan 10 item per halaman
            $konversi_tanggal = $query->orderBy('tanggal', 'desc')->paginate(10);
            
            return view('konversiTanggal.view', compact('konversi_tanggal', 'search'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data konversi tanggal: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $hijriMonths = [
            '01' => 'Muharram',
            '02' => 'Safar',
            '03' => 'Rabiul Awal',
            '04' => 'Rabiul Akhir',
            '05' => 'Jumadil Awal',
            '06' => 'Jumadil Akhir',
            '07' => 'Rajab',
            '08' => 'Sya\'ban',
            '09' => 'Ramadhan',
            '10' => 'Syawal',
            '11' => 'Dzulqa\'dah',
            '12' => 'Dzulhijjah'
        ];
        
        $currentYear = date('Y');
        $hijriYears = range($currentYear - 10, $currentYear + 10);
        
        return view('konversiTanggal.insert', compact('hijriMonths', 'hijriYears'));
    }

    /**
     * Show the preview page (POST method only)
     */
    public function preview(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'bulan_h' => 'required',
                'tahun_h' => 'required|numeric|min:1300|max:1500'
            ]);

            // Validasi tambahan: maksimal 30 hari
            $startDate = strtotime($validated['tanggal_awal']);
            $endDate = strtotime($validated['tanggal_akhir']);
            $dayDiff = ($endDate - $startDate) / (60 * 60 * 24) + 1; // +1 untuk include tanggal awal
            
            if ($dayDiff > 30) {
                return redirect()->back()
                    ->with('error', 'Rentang tanggal tidak boleh lebih dari 30 hari. Anda memilih ' . $dayDiff . ' hari.')
                    ->withInput();
            }

            // Mapping angka bulan ke nama bulan
            $bulanHijriahMapping = [
                '01' => 'Muharram',
                '02' => 'Safar',
                '03' => 'Rabiul Awal',
                '04' => 'Rabiul Akhir',
                '05' => 'Jumadil Awal',
                '06' => 'Jumadil Akhir',
                '07' => 'Rajab',
                '08' => 'Sya\'ban',
                '09' => 'Ramadhan',
                '10' => 'Syawal',
                '11' => 'Dzulqa\'dah',
                '12' => 'Dzulhijjah'
            ];

            $previewData = [];
            $hariH = 1; // Mulai dari tanggal 1 Hijriah
            
            // Dapatkan nama bulan dari angka bulan
            $namaBulanH = $bulanHijriahMapping[$validated['bulan_h']] ?? $validated['bulan_h'];
            
            // Generate preview untuk setiap hari
            for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate = strtotime('+1 day', $currentDate)) {
                $tanggalMasehi = date('Y-m-d', $currentDate);
                
                $previewData[] = [
                    'tanggal_masehi' => date('Y-m-d', $currentDate),
                    'tanggal_masehi_formatted' => date('d-m-Y', $currentDate),
                    'hari_h' => str_pad($hariH, 2, '0', STR_PAD_LEFT),
                    'bulan_h' => $validated['bulan_h'], // Simpan angka bulan
                    'bulan_h_nama' => $namaBulanH, // Simpan nama bulan untuk display
                    'tahun_h' => $validated['tahun_h'],
                    'tanggal_hijriah' => str_pad($hariH, 2, '0', STR_PAD_LEFT) . '-' . $validated['bulan_h'] . '-' . $validated['tahun_h'] . ' H'
                ];
                
                $hariH++; // Increment tanggal Hijriah
            }

            // Simpan data input ke session untuk digunakan di store
            session()->flash('input_data', $validated);
            session()->flash('preview_data', $previewData);

            return view('konversiTanggal.preview', [
                'previewData' => $previewData,
                'inputData' => $validated,
                'totalHari' => count($previewData),
                'periode' => date('d-m-Y', $startDate) . ' hingga ' . date('d-m-Y', $endDate),
                'namaBulanH' => $namaBulanH
            ]);
                
        } catch (\Exception $e) {
            return redirect()->route('konversiTanggal.create')
                ->with('error', 'Gagal membuat preview: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     try {
    //         // Ambil data dari session (dari halaman preview)
    //         $inputData = session('input_data');
    //         $previewData = session('preview_data');

    //         // Jika tidak ada data di session, validasi dari request
    //         if (!$inputData) {
    //             $validated = $request->validate([
    //                 'tanggal_awal' => 'required|date',
    //                 'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
    //                 'bulan_h' => 'required',
    //                 'tahun_h' => 'required|numeric'
    //             ]);
    //             $inputData = $validated;
    //         }

    //         if (!$inputData) {
    //             throw new \Exception('Data input tidak ditemukan. Silakan isi form terlebih dahulu.');
    //         }

    //         $startDate = strtotime($inputData['tanggal_awal']);
    //         $endDate = strtotime($inputData['tanggal_akhir']);
            
    //         $dataToInsert = [];
    //         $dataToUpdate = [];
    //         $hariH = 1; // Mulai dari tanggal 1 Hijriah
            
    //         $insertedCount = 0;
    //         $updatedCount = 0;
            
    //         // Loop melalui setiap hari
    //         for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate = strtotime('+1 day', $currentDate)) {
    //             $tanggalMasehi = date('Y-m-d', $currentDate);
                
    //             $data = [
    //                 'tanggal' => $tanggalMasehi,
    //                 'tanggal_h' => str_pad($hariH, 2, '0', STR_PAD_LEFT) . ' ' . $inputData['bulan_h'] . ' ' . $inputData['tahun_h'] . ' H',
    //                 'hari_h' => str_pad($hariH, 2, '0', STR_PAD_LEFT),
    //                 'bulan_h' => $inputData['bulan_h'],
    //                 'tahun_h' => $inputData['tahun_h'],
    //                 'updated_at' => now()
    //             ];

    //             // For BigQuery, generate unique ID if needed
    //             if (config('database.default') === 'bigquery') {
    //                 if (!isset($data['id'])) {
    //                     $data['id'] = Str::uuid()->toString();
    //                 }
    //             }

    //             // Cek apakah tanggal sudah ada di database
    //             $existingData = DB::table('konversi_tanggal')
    //                 ->where('tanggal', $tanggalMasehi)
    //                 ->first();

    //             if ($existingData) {
    //                 // Jika sudah ada, update data
    //                 $dataToUpdate[] = [
    //                     'data' => $data,
    //                     'where' => ['tanggal' => $tanggalMasehi]
    //                 ];
    //                 $updatedCount++;
    //             } else {
    //                 // Jika belum ada, insert data baru
    //                 $data['created_at'] = now();
    //                 $dataToInsert[] = $data;
    //                 $insertedCount++;
    //             }
                
    //             $hariH++; // Increment tanggal Hijriah
    //         }

    //         // Mulai transaction
    //         DB::beginTransaction();

    //         try {
    //             // Insert data baru
    //             if (!empty($dataToInsert)) {
    //                 DB::table('konversi_tanggal')->insert($dataToInsert);
    //             }

    //             // Update data yang sudah ada
    //             if (!empty($dataToUpdate)) {
    //                 foreach ($dataToUpdate as $updateItem) {
    //                     DB::table('konversi_tanggal')
    //                         ->where($updateItem['where'])
    //                         ->update($updateItem['data']);
    //                 }
    //             }

    //             // Commit transaction
    //             DB::commit();

    //             // Hapus data session setelah berhasil disimpan
    //             session()->forget(['input_data', 'preview_data']);
                
    //             $message = 'Konversi tanggal berhasil diproses untuk periode ' . 
    //                       date('d-m-Y', strtotime($inputData['tanggal_awal'])) . ' hingga ' . 
    //                       date('d-m-Y', strtotime($inputData['tanggal_akhir'])) . 
    //                       ' (Total: ' . count($dataToInsert) . ' hari)';
                
    //             // Tambahkan info tentang insert dan update
    //             if ($insertedCount > 0 && $updatedCount > 0) {
    //                 $message .= ' - ' . $insertedCount . ' data baru ditambahkan, ' . $updatedCount . ' data diperbarui';
    //             } elseif ($insertedCount > 0) {
    //                 $message .= ' - ' . $insertedCount . ' data baru ditambahkan';
    //             } elseif ($updatedCount > 0) {
    //                 $message .= ' - ' . $updatedCount . ' data diperbarui';
    //             }
                
    //             return redirect()->route('konversiTanggal.view')
    //                 ->with('success', $message);
                    
    //         } catch (\Exception $e) {
    //             // Rollback transaction jika ada error
    //             DB::rollBack();
    //             throw $e;
    //         }
                
    //     } catch (\Exception $e) {
    //         return redirect()->route('konversiTanggal.create')
    //             ->with('error', 'Gagal menambahkan konversi tanggal: '.$e->getMessage())
    //             ->withInput();
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Ambil data dari session (dari halaman preview)
            $inputData = session('input_data');
            $previewData = session('preview_data');

            // Jika tidak ada data di session, validasi dari request
            if (!$inputData) {
                $validated = $request->validate([
                    'tanggal_awal' => 'required|date',
                    'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                    'bulan_h' => 'required',
                    'tahun_h' => 'required|numeric'
                ]);
                $inputData = $validated;
            }

            if (!$inputData) {
                throw new \Exception('Data input tidak ditemukan. Silakan isi form terlebih dahulu.');
            }

            // DEBUG: Lihat data yang masuk
            // Log::info('=== DEBUG DATA INPUT ===');
            // Log::info('Data input:', $inputData);

            // Mapping untuk konversi angka bulan ke nama bulan
            $bulanHijriahMapping = [
                '01' => 'Muharram',
                '02' => 'Safar', 
                '03' => 'Rabiul Awal',
                '04' => 'Rabiul Akhir',
                '05' => 'Jumadil Awal',
                '06' => 'Jumadil Akhir',
                '07' => 'Rajab',
                '08' => 'Sya\'ban',
                '09' => 'Ramadhan',
                '10' => 'Syawal',
                '11' => 'Dzulqa\'dah',
                '12' => 'Dzulhijjah'
            ];

            // Dapatkan nama bulan dari angka bulan
            $angkaBulan = $inputData['bulan_h']; // '02' dari form
            $namaBulan = $bulanHijriahMapping[$angkaBulan] ?? 'Bulan Tidak Dikenal';

            // DEBUG: Lihat hasil mapping
            // Log::info('=== DEBUG MAPPING RESULT ===');
            // Log::info('Angka bulan dari form: ' . $angkaBulan);
            // Log::info('Nama bulan hasil mapping: ' . $namaBulan);

            $startDate = strtotime($inputData['tanggal_awal']);
            $endDate = strtotime($inputData['tanggal_akhir']);
            
            $dataToInsert = [];
            $dataToUpdate = [];
            $hariH = 1; // Mulai dari tanggal 1 Hijriah
            
            $insertedCount = 0;
            $updatedCount = 0;
            
            // Daftar jumlah hari per bulan Hijriah
            $daysInHijriMonth = [
                '01' => 30, '02' => 29, '03' => 30, '04' => 29,
                '05' => 30, '06' => 29, '07' => 30, '08' => 29,
                '09' => 30, '10' => 29, '11' => 30, '12' => 29
            ];

            $maxDays = $daysInHijriMonth[$angkaBulan] ?? 30;
            
            // Loop melalui setiap hari
            for ($currentDate = $startDate; $currentDate <= $endDate && $hariH <= $maxDays; $currentDate = strtotime('+1 day', $currentDate)) {
                $tanggalMasehi = date('Y-m-d', $currentDate);
                
                // Format data untuk database
                $data = [
                    'tanggal' => $tanggalMasehi,
                    'tanggal_h' => $inputData['tahun_h'] . '-' . $angkaBulan . '-' . str_pad($hariH, 2, '0', STR_PAD_LEFT), // Format: 1447-02-01
                    'hari_h' => str_pad($hariH, 2, '0', STR_PAD_LEFT),
                    'bulan_h' => $namaBulan, // Nama bulan: 'Safar'
                    'tahun_h' => $inputData['tahun_h'],
                    'updated_at' => now()
                ];

                // DEBUG: Lihat data yang akan disimpan
                // Log::info('Data untuk row ' . $hariH . ':', $data);

                // For BigQuery, generate unique ID if needed
                if (config('database.default') === 'bigquery') {
                    if (!isset($data['id'])) {
                        $data['id'] = Str::uuid()->toString();
                    }
                }

                // Cek apakah tanggal sudah ada di database
                $existingData = DB::table('konversi_tanggal')
                    ->where('tanggal', $tanggalMasehi)
                    ->first();

                if ($existingData) {
                    $dataToUpdate[] = [
                        'data' => $data,
                        'where' => ['tanggal' => $tanggalMasehi]
                    ];
                    $updatedCount++;
                } else {
                    $data['created_at'] = now();
                    $dataToInsert[] = $data;
                    $insertedCount++;
                }
                
                $hariH++;
            }

            // Mulai transaction
            DB::beginTransaction();

            try {
                // Insert data baru
                if (!empty($dataToInsert)) {
                    DB::table('konversi_tanggal')->insert($dataToInsert);
                }

                // Update data yang sudah ada
                if (!empty($dataToUpdate)) {
                    foreach ($dataToUpdate as $updateItem) {
                        DB::table('konversi_tanggal')
                            ->where($updateItem['where'])
                            ->update($updateItem['data']);
                    }
                }

                DB::commit();

                session()->forget(['input_data', 'preview_data']);
                
                $message = 'Konversi tanggal berhasil diproses untuk periode ' . 
                        date('d-m-Y', $startDate) . ' hingga ' . 
                        date('d-m-Y', $endDate) . 
                        ' (Bulan ' . $namaBulan . ' ' . $inputData['tahun_h'] . ' H)';
                
                if ($insertedCount > 0 && $updatedCount > 0) {
                    $message .= ' - ' . $insertedCount . ' data baru ditambahkan, ' . $updatedCount . ' data diperbarui';
                } elseif ($insertedCount > 0) {
                    $message .= ' - ' . $insertedCount . ' data baru ditambahkan';
                } elseif ($updatedCount > 0) {
                    $message .= ' - ' . $updatedCount . ' data diperbarui';
                }
                
                return redirect()->route('konversiTanggal.view')
                    ->with('success', $message);
                    
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
                
        } catch (\Exception $e) {
            return redirect()->route('konversiTanggal.create')
                ->with('error', 'Gagal menambahkan konversi tanggal: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $konversi_tanggal = DB::table('konversi_tanggal')->where('id', $id)->first();
            
            if (!$konversi_tanggal) {
                throw new \Exception('Data konversi tanggal tidak ditemukan');
            }
            
            $hijriMonths = [
                'Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir',
                'Jumadil Awal', 'Jumadil Akhir', 'Rajab', 'Sya\'ban',
                'Ramadhan', 'Syawal', 'Dzulqa\'dah', 'Dzulhijjah'
            ];
            
            $currentYear = date('Y');
            $hijriYears = range($currentYear - 10, $currentYear + 10);
            
            return view('konversiTanggal.edit', compact('konversi_tanggal', 'hijriMonths', 'hijriYears'));
        } catch (\Exception $e) {
            return redirect()->route('konversiTanggal.view')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'bulan_h' => 'required',
                'tahun_h' => 'required|numeric'
            ]);

            // Untuk edit, kita tetap menggunakan increment sederhana
            $hariH = date('d', strtotime($validated['tanggal'])); // Ambil tanggal dari tanggal masehi

            $updateData = [
                'tanggal' => $validated['tanggal'],
                'tanggal_h' => str_pad($hariH, 2, '0', STR_PAD_LEFT) . ' ' . $validated['bulan_h'] . ' ' . $validated['tahun_h'] . ' H',
                'hari_h' => str_pad($hariH, 2, '0', STR_PAD_LEFT),
                'bulan_h' => $validated['bulan_h'],
                'tahun_h' => $validated['tahun_h'],
                'updated_at' => now()
            ];

            $affected = DB::table('konversi_tanggal')
                        ->where('id', $id)
                        ->update($updateData);

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('konversiTanggal.view')
                ->with('success', 'Konversi tanggal berhasil diperbarui');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui konversi tanggal: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // For BigQuery, use soft delete pattern
            if (config('database.default') === 'bigquery') {
                DB::table('konversi_tanggal')
                    ->where('id', $id)
                    ->update(['deleted_at' => now()]);
            } else {
                $affected = DB::table('konversi_tanggal')
                            ->where('id', $id)
                            ->delete();
                            
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang dihapus');
                }
            }

            return redirect()->route('konversiTanggal.view')
                ->with('success', 'Konversi tanggal berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus konversi tanggal: '.$e->getMessage());
        }
    }
}
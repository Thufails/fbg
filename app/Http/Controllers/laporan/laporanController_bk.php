<?php

namespace App\Http\Controllers\laporan;

use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use App\Models\Grafik;
use App\Models\HasilAnalisis;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class laporanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = 20;
            
            $query = Laporan::with('user')
                ->leftJoin('users', 'laporan.kd_user', '=', 'users.id')
                ->select('laporan.*', 'users.name')
                ->orderBy('laporan.tanggal', 'desc');

            // Filter by date range
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                $query->whereBetween('laporan.tanggal', [$startDate, $endDate]);
            }

            $laporan = $query->paginate($perPage);

            return view('laporan.view', compact('laporan'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data laporan: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('laporan.insert');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       // dd($request);
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'keterangan' => 'required'
            ]);
            
            $tanggal = Carbon::parse($request->tanggal);
            $tanggal_awal = Carbon::parse($request->tanggal_awal);
            $tanggal_akhir = Carbon::parse($request->tanggal_akhir);

            $data = [
                'tanggal' => $tanggal->format('Y-m-d'),
                'tanggal_awal' => $tanggal_awal->format('Y-m-d'),
                'tanggal_akhir' => $tanggal_akhir->format('Y-m-d'),
                'kd_user'=> Auth::user()->id,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
                'path'=>'',
                'path_penjualan'=>'',
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            //dd($data);

            // Handle BigQuery's UUID requirement
            if (config('database.default') === 'bigquery') {
                $data['kd_laporan'] = Str::uuid()->toString();
                DB::table('laporan')->insert($data);
            } else {
                //dd($data);
                Laporan::create($data);
            }

            return redirect()->route('laporan.view')->with('success', 'Laporan berhasil ditambahkan');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan laporan: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($kd_laporan)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $laporan = DB::table('laporan')
                    ->leftJoin('users', 'laporan.kd_user', '=', 'users.id')
                    ->select('laporan.*', 'users.name')
                    ->where('laporan.kd_laporan', $kd_laporan)
                    ->first();
            } else {
                $laporan = Laporan::with('user')->findOrFail($kd_laporan);
            }

            if (!$laporan) {
                throw new \Exception('Laporan tidak ditemukan');
            }

            // --- BACA DATA HASIL ANALISIS BERDASARKAN kd_laporan ---
            $hasilAnalisis = [];
            
            if (config('database.default') === 'bigquery') {
                // Untuk BigQuery
                $hasilAnalisis = DB::table('hasil_analisis')
                    ->where('kd_laporan', $kd_laporan)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            } else {
                // Untuk database biasa (MySQL, PostgreSQL, dll)
                $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }

            // Proses data untuk menentukan status data dan analisis
            $hasilAnalisis = $hasilAnalisis->map(function ($item) {
                // Decode data jika berupa string JSON
                $decodedData = [];
                if (!empty($item->data)) {
                    if (is_string($item->data)) {
                        try {
                            $decodedData = json_decode($item->data, true) ?? [];
                        } catch (\Exception $e) {
                            $decodedData = [];
                        }
                    } else {
                        $decodedData = (array)$item->data;
                    }
                }
                $item->decoded_data = $decodedData;

                // Cek status data
                $item->status_data = (!empty($decodedData) && $decodedData !== []) ? 'Ada' : 'Kosong';

                // Cek status analisis
                $item->status_analisis = (!empty($item->analisis) && strlen($item->analisis) > 50) ? 'Ada' : 'Kosong';

                // Tentukan jenis analisis (Manual atau AI)
                $item->jenis_analisis = 'Manual';
                if (!empty($item->analisis)) {
                    if (strpos($item->analisis, 'Analisis Data:') !== false || 
                        strpos($item->analisis, 'Analisis Manual:') === false) {
                        $item->jenis_analisis = 'AI';
                    }
                    
                    if (strpos($item->analisis, 'Analisis Manual:') !== false) {
                        $item->jenis_analisis = 'Manual';
                    }
                }
                
                return $item;
            });

            return view('laporan.detail', compact('laporan', 'hasilAnalisis','kd_laporan'));

        } catch (\Exception $e) {
            return redirect()->route('laporan.view')
                ->with('error', 'Laporan tidak ditemukan: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($kd_laporan)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $laporan = DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->first();
            } else {
                $laporan = Laporan::findOrFail($kd_laporan);
            }

            if (!$laporan) {
                throw new \Exception('Laporan tidak ditemukan');
            }

            return view('laporan.edit', compact('laporan'));

        } catch (\Exception $e) {
            return redirect()->route('laporan.view')
                ->with('error', 'Laporan tidak ditemukan');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $kd_laporan)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'keterangan' => 'required'
            ]);

            $updateData = [
                'tanggal' => $request->tanggal,
                'tanggal_awal' => $request->tanggal_awal,
                'tanggal_akhir' => $request->tanggal_akhir,
                'kd_user'=> Auth::user()->id,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
                'updated_at' => now()
            ];

            if (config('database.default') === 'bigquery') {
                DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->update($updateData);
            } else {
                $laporan = Laporan::findOrFail($kd_laporan);
                $laporan->update($updateData);
            }

            return redirect()->route('laporan.view')
                ->with('success', 'Laporan berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah laporan: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($kd_laporan)
    {
        try {
            if (config('database.default') === 'bigquery') {
                // For BigQuery, implement soft delete pattern
                DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->update(['deleted_at' => now()]);
            } else {
                // For standard databases
                $laporan = Laporan::findOrFail($kd_laporan);
                $laporan->delete();
                $hasilAnalisis = HasilAnalisis::findOrFail($kd_laporan);
                $hasilAnalisis->delete();
            }

            return redirect()->route('laporan.view')
                ->with('success', 'Laporan berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus laporan: ' . $e->getMessage());
        }
    }

    /**
     * Generate JSON report from penjualan data
     */
    public function generateJsonReport($kd_laporan)
    {
        try {
            // Get laporan data
            if (config('database.default') === 'bigquery') {
                $laporan = DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->first();
            } else {
                $laporan = Laporan::findOrFail($kd_laporan);
            }

            if (!$laporan) {
                throw new \Exception('Laporan tidak ditemukan');
            }

            // Get realisasi data with joins
            $realisasi = DB::table('target')
                ->leftJoin('realisasi','target.kd_target','=','realisasi.kd_target')
                ->leftJoin('akun','target.kd_akun','=','akun.kd_akun')
                ->leftJoin('entitas','target.kd_entitas','=','entitas.kd_entitas')
                ->select('target.tanggal','entitas.nama_entitas','target.kd_akun','akun.nama_akun','target.nilai as nilai_target','realisasi.nilai as nilai_realisasi','realisasi.hari','realisasi.bulan','realisasi.tahun')
                ->whereBetween('target.tanggal', [$laporan->tanggal_awal, $laporan->tanggal_akhir])
                ->get();
            
            // Get penjualan data
            $penjualan = DB::table('penjualan')
                ->whereBetween('tanggal', [$laporan->tanggal_awal, $laporan->tanggal_akhir])
                ->get();

            // Create directory if not exists
            $directory = 'laporan_json';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            // Generate unique filename with a timestamp to prevent overwriting previous reports.
            $startDate = Carbon::parse($laporan->tanggal_awal)->format('Ymd');
            $endDate = Carbon::parse($laporan->tanggal_akhir)->format('Ymd');
            
            // Save realisasi JSON file
            $filenameRealisasi = "laporan_sbs_{$startDate}_to_{$endDate}.json";
            $filePathRealisasi = $directory . '/' . $filenameRealisasi;
            Storage::put($filePathRealisasi, json_encode($realisasi, JSON_PRETTY_PRINT));

            // Save penjualan JSON file
            $filenamePenjualan = "laporan_penjualan_{$startDate}_to_{$endDate}.json";
            $filePathPenjualan = $directory . '/' . $filenamePenjualan;
            Storage::put($filePathPenjualan, json_encode($penjualan, JSON_PRETTY_PRINT));

            // Update laporan record with the new file paths
            if (config('database.default') === 'bigquery') {
                DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->update([
                        'path' => $filePathRealisasi, 
                        'path_penjualan' => $filePathPenjualan,
                        'updated_at' => now()
                    ]);
            } else {
                $laporan->path = $filePathRealisasi;
                $laporan->path_penjualan = $filePathPenjualan;
                $laporan->save();
            }

            return redirect()->route('laporan.view')
                ->with('success', 'Laporan JSON dan Laporan Penjualan telah berhasil dibuat!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }


    /**
     * Menganalisis data tabel menggunakan Gemini API.
     * @param string $prompt
     * @param string $promptSave
     * @param string $tableTitle
     * @param array $tableData
     * @return string
     */
    private function analyzeTableWithGemini(string $prompt, string $promptSave, string $tableTitle, array $tableData): string
    {
        //dd($prompt);
        // 1. Validasi dan Pengambilan API Key
        // Gunakan config() untuk akses ENV jika ada cache, atau pastikan .env terload.
        // Jika Anda menggunakan Laravel/Lumen, disarankan memakai config/services.php
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
    
        if (empty($apiKey)) {
            $errorMsg = 'GEMINI_API_KEY tidak diatur. Periksa file .env dan konfigurasi cache.';
            Log::error($errorMsg);
            return 'Maaf, kunci API tidak ditemukan. Silakan hubungi administrator.';
        }
    
        $client = new Client();
        
        // Perbaikan: Gunakan parameter 'key' sebagai bagian dari Guzzle 'query'
        // untuk pemisahan yang lebih bersih, meskipun di URL juga bisa.
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
        
        // Logging awal
        Log::info("Membuat permintaan ke Gemini API dengan model: gemini-2.0-flash");
    
        try {
            $response = $client->post($apiUrl, [
                'query' => [
                    'key' => $apiKey // API Key diletakkan di sini
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 2048,
                    ],
                    'safetySettings' => [
                        // Tetap pertahankan safety settings default yang Anda miliki
                        [ 'category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
                        [ 'category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ]
                    ]
                ],
                'timeout' => 60,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);
    
            $statusCode = $response->getStatusCode();
            Log::info("Gemini API HTTP Status: " . $statusCode);
            
            $body = json_decode($response->getBody(), true);
            
            // 2. Cek Kandidat Respons dan Penolakan
            if (isset($body['candidates'][0]['finishReason']) && $body['candidates'][0]['finishReason'] !== 'STOP') {
                 $reason = $body['candidates'][0]['finishReason'];
                 Log::warning("Gemini Response ditolak dengan alasan: " . $reason, $body);
                 return "Analisis gagal. Model menolak respons karena alasan: " . $reason;
            }
    
            if (!isset($body['candidates']) || empty($body['candidates'])) {
                Log::error('Tidak ada kandidat respons valid dari Gemini: ', $body);
                throw new \Exception('Tidak ada respons yang valid dari Gemini API. (No Candidates)');
            }
            
            $geminiResponseText = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
            if (!$geminiResponseText) {
                throw new \Exception('Analisis tidak dapat dihasilkan. Respons kosong.');
            }
    
            Log::info('Gemini Raw Response Length: ' . strlen($geminiResponseText));
            
            return $geminiResponseText;
    
        } catch (RequestException $e) {
            // 3. Penanganan Error Guzzle/Request yang Lebih Baik
            $errorMsg = 'Error koneksi/request ke Gemini API: ';
            
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = $response->getBody()->getContents();
                $errorDetails = json_decode($errorBody, true);
                $errorMessage = $errorDetails['error']['message'] ?? 'Detail error tidak tersedia.';
                
                Log::error("Gemini API Error {$statusCode}: " . $errorMessage . " | Raw: " . $errorBody);
                
                // Perbaikan Pesan Error Berdasarkan Status Code
                if ($statusCode === 401) {
                    $errorMsg .= 'Kunci API tidak valid atau telah dicabut. ';
                } elseif ($statusCode === 403) {
                    $errorMsg .= 'Akses Ditolak. Pastikan API Key Anda memiliki izin untuk Gemini API. ';
                } elseif ($statusCode === 400) {
                    $errorMsg .= 'Permintaan tidak valid. Periksa format JSON atau prompt. ';
                } elseif ($statusCode === 429) {
                    $errorMsg .= 'Kuota harian terlampaui atau batas request/menit tercapai. ';
                } else {
                    $errorMsg .= "Kesalahan Server/Lainnya ({$statusCode}): " . $errorMessage;
                }
            } else {
                // Error Koneksi Jaringan
                Log::error("Gemini API Connection Error: " . $e->getMessage());
                $errorMsg .= 'Koneksi gagal (timeout atau masalah jaringan). ';
            }
            
            $errorMsg .= 'Silakan coba lagi nanti.';
            return $this->generateManualAnalysis($tableTitle, $tableData);
            
        } catch (\Exception $e) {
            // Penanganan Error Umum (misalnya JSON parsing)
            Log::error("General Error during Gemini analysis: " . $e->getMessage());
            
            return $this->generateManualAnalysis($tableTitle, $tableData);
        }
    }

    /**
     * Simple manual analysis fallback
     */
    private function generateManualAnalysis(string $tableTitle, array $tableData): string
    {
        return "<h2>Analisis Manual: {$tableTitle}</h2>
                <p>Analisis tidak dapat dilakukan karena masalah koneksi dengan AI.</p>
                <p>Silakan periksa koneksi internet dan API key Gemini.</p>";
    }

    /**
     * Update the specified analysis in storage.
     */
    public function updateAnalysis(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'analisis' => 'required|string'
            ]);

            if (config('database.default') === 'bigquery') {
                DB::table('hasil_analisis')
                    ->where('id', $id)
                    ->update([
                        'analisis' => $request->analisis,
                        'updated_at' => now()
                    ]);
            } else {
                $hasilAnalisis = HasilAnalisis::findOrFail($id);
                $hasilAnalisis->update([
                    'analisis' => $request->analisis,
                    'updated_at' => now()
                ]);
            }

            return redirect()->back()
                ->with('success', 'Analisis berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error('Error updating analysis: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal mengupdate analisis: ' . $e->getMessage());
        }
    }

    /**
     * Show prompt correction form (GET method dengan parameter URL)
     */
    public function showPromptForm($kd_laporan, $kd_grafik)
    {
        try {
            // Get data dari parameter URL
           // $data = $request->input('data');

            // Validasi input
            if (empty($kd_laporan) || empty($kd_grafik)) {
                return redirect()->back()
                    ->with('error', 'Data kd_laporan dan kd_grafik harus diisi');
            }

            // Cek apakah laporan exists
            if (config('database.default') === 'bigquery') {
                $laporan = DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->first();
            } else {
                $laporan = Laporan::find($kd_laporan);
            }

            if (!$laporan) {
                return redirect()->route('laporan.view')
                    ->with('error', 'Laporan tidak ditemukan');
            }
            
            $data = DB::table('hasil_analisis')
                        ->select('data')
                        ->where('kd_laporan', $kd_laporan)
                        ->where('kd_grafik', $kd_grafik)
                        ->first();
                        
            //dd($data);

            // Decode data jika diperlukan
          //  $decodedData = $this->decodeData($data);
            
            // Jika data kosong, coba ambil dari hasil analisis yang sudah ada
            if (empty($decodedData)) {
                if (config('database.default') === 'bigquery') {
                    $existingAnalysis = DB::table('hasil_analisis')
                        ->where('kd_laporan', $kd_laporan)
                        ->where('kd_grafik', $kd_grafik)
                        ->first();
                } else {
                    $existingAnalysis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                        ->where('kd_grafik', $kd_grafik)
                        ->first();
                }
                
                if ($existingAnalysis && !empty($existingAnalysis->data)) {
                    $decodedData = $this->decodeData($existingAnalysis->data);
                }
            }

            if (empty($decodedData)) {
                return redirect()->back()
                    ->with('error', 'Data tidak boleh kosong');
            }

            $grafik = Grafik::where('kd_grafik', $kd_grafik)->first();
            
            if (!$grafik) {
                return redirect()->back()
                    ->with('error', 'Data grafik tidak ditemukan');
            }

            // CEK APAKAH SUDAH ADA PROMPT DI TABEL hasil_analisis
            $existingPrompt = null;
            if (config('database.default') === 'bigquery') {
                $existingAnalysis = DB::table('hasil_analisis')
                    ->where('kd_laporan', $kd_laporan)
                    ->where('kd_grafik', $kd_grafik)
                    ->whereNotNull('prompt')
                    ->first();
            } else {
                $existingAnalysis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                    ->where('kd_grafik', $kd_grafik)
                    ->whereNotNull('prompt')
                    ->first();
            }

            // Jika ada prompt yang sudah disimpan, gunakan itu
            if ($existingAnalysis && !empty($existingAnalysis->prompt)) {
                $initialPrompt = $existingAnalysis->prompt;
                $isExistingPrompt = true;
            } else {
                // Generate initial prompt baru jika tidak ada yang tersimpan
                $initialPrompt = $this->generateInitialPrompt($grafik->kd_grafik, $decodedData);
                $isExistingPrompt = false;
            }

            return view('laporan.prompt_form', compact(
                'kd_laporan', 
                'kd_grafik', 
                'decodedData',
                'grafik',
                'laporan',
                'initialPrompt',
                'isExistingPrompt'
            ));

        } catch (\Exception $e) {
            Log::error('Error dalam showPromptForm: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute analysis with corrected prompt - SIMPLIFIED VERSION
     */
//     public function executeAnalysis(Request $request)
//     {

        
//         $prompt_tambahan = "Buat analisis dengan format HTML yang VALID dan LENGKAP.

// **KETENTUAN STRUKTUR HTML YANG HARUS DIPATUHI:**
// 1. HARUS menggunakan format HTML yang bersih dan tidak usah menggunakan tag html untuk awal dokumen
// 2. JANGAN gunakan karakter escape \\/ pada tag HTML
// 3. Tag penutup harus menggunakan backslash contohnya </tag> BUKAN <\\/tag>
// 4. Pastikan semua tag dibuka dan ditutup dengan benar
// 5. Jangan ada teks 'html' di awal response
// 6. JANGAN ada respon dengan karakter \n
// 7. JANGAN ada respon dalam bentuk tabel data ataupun data json lagi

// **DATA UNTUK DIANALISIS:**";

//         try {
//             $kd_laporan = $request->input('kd_laporan');
//             $kd_grafik = $request->input('kd_grafik');
//             $data = $request->input('data');
//             $prompt = $prompt_tambahan . "\n\nData:\n" . $data . "\n\nInstruksi tambahan: " . $request->input('prompt');
//             $promptSave = $request->input('prompt');
            
//             // Validasi input
//             if (empty($kd_laporan) || empty($kd_grafik) || empty($prompt)) {
//                 return redirect()->back()
//                     ->with('error', 'Data kd_laporan, kd_grafik, dan prompt harus diisi');
//             }
    
//             // Cek apakah laporan exists
//             if (config('database.default') === 'bigquery') {
//                 $laporan = DB::table('laporan')
//                     ->where('kd_laporan', $kd_laporan)
//                     ->first();
//             } else {
//                 $laporan = Laporan::find($kd_laporan);
//             }
    
//             if (!$laporan) {
//                 return redirect()->route('laporan.view')
//                     ->with('error', 'Laporan tidak ditemukan');
//             }
    
//             // Decode data
//             $decodedData = $this->decodeData($data);
            
//             if (empty($decodedData)) {
//                 return redirect()->back()
//                     ->with('error', 'Data tidak boleh kosong');
//             }
    
//             $grafik = Grafik::where('kd_grafik', $kd_grafik)->first();
            
//             if (!$grafik) {
//                 return redirect()->back()
//                     ->with('error', 'Data grafik tidak ditemukan');
//             }
            
//             $prompt = $prompt_tambahan . "\n\nData:\n" . $data . "\n\nInstruksi tambahan: " . $grafik->prompt;
//             $promptSave = $request->input('prompt');
    
//             // Set session untuk menunjukkan bahwa proses analisis sedang berjalan
//             Session::flash('analyzing', true);
            
//             //dd($prompt);
    
//             // Lakukan analisis dengan Gemini - TANPA MODIFIKASI RESPONSE
//             $analysis = $this->analyzeTableWithGemini($prompt, $promptSave, $grafik->judul_grafik, $decodedData);
            
//             // **PERUBAHAN PENTING: Simpan analysis ASLI dari Gemini tanpa cleaning**
//             $cleanAnalysis = $analysis;
            
//             //dd($cleanAnalysis);
            
//             // Update atau create data di hasil_analisis
//             $hasilAnalisis = HasilAnalisis::updateOrCreate(
//                 [
//                     'kd_laporan' => $kd_laporan,
//                     'kd_grafik' => $kd_grafik
//                 ],
//                 [
//                     'data' => $decodedData,
//                     'analisis' => $analysis, // Simpan response ASLI dari Gemini
//                     'prompt' => $promptSave,
//                     'updated_at' => now()
//                 ]
//             );
    
//             return redirect()->route('laporan.detail', $kd_laporan)
//                 ->with('success', 'Analisis berhasil dilakukan')
//                 ->with('analyzed_title', $grafik->judul_grafik);
    
//         } catch (\Exception $e) {
//             Log::error('Error dalam executeAnalysis: ' . $e->getMessage());
//             Log::error('Stack trace: ' . $e->getTraceAsString());
            
//             return redirect()->back()
//                 ->with('error', 'Terjadi kesalahan server: ' . $e->getMessage());
//         }
//     }

    /**
     * Modify the existing analyze method to redirect to prompt form dengan data
     */
    public function analyze(Request $request)
    {
        try {
            $kd_laporan = $request->input('kd_laporan');
            $kd_grafik = $request->input('kd_grafik');

            // Validasi input
            if (empty($kd_laporan) || empty($kd_grafik)) {
                return redirect()->back()
                    ->with('error', 'Data kd_laporan dan kd_grafik harus diisi');
            }

            $dataHasilAnalisis = HasilAnalisis::where('kd_grafik', $kd_grafik)->where('kd_laporan',$kd_laporan)->first();
        
            if (!$dataHasilAnalisis) {
                return back()->with('error', 'Data tidak ditemukan');
            }
            
            // Handle data yang sudah berupa array atau JSON string
            $data = $dataHasilAnalisis->data;

            // Jika data adalah string JSON, decode
            if (is_string($data)) {
                $decodedData = json_decode($data, true) ?? [];
            } else {
                // Jika sudah array, gunakan langsung
                $decodedData = $data;
            }

            // Encode data untuk URL - pastikan berupa string JSON
            $encodedData = urlencode(json_encode($decodedData));

            // Cek apakah laporan exists
            if (config('database.default') === 'bigquery') {
                $laporan = DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->first();
            } else {
                $laporan = Laporan::find($kd_laporan);
            }

            if (!$laporan) {
                return redirect()->route('laporan.view')
                    ->with('error', 'Laporan tidak ditemukan');
            }

            return redirect()->route('laporan.prompt.form', [
                'kd_laporan' => $kd_laporan,
                'kd_grafik' => $kd_grafik
              //  'data' => $encodedData
            ]);

        } catch (\Exception $e) {
            Log::error('Error dalam analyze: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper method to decode data
     */
    private function decodeData($data)
    {
        if (is_string($data)) {
            $decodedData = json_decode($data, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (strpos($data, '{') === 0 && strpos($data, '}') !== false) {
                    $cleanedData = stripslashes($data);
                    $decodedData = json_decode($cleanedData, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return null;
                    }
                } else {
                    return null;
                }
            }
        } else {
            $decodedData = $data;
        }

        return $decodedData;
    }

    /**
     * Generate initial prompt
     */
    private function generateInitialPrompt(string $kd_grafik, array $tableData): string
    {
        $dataString = json_encode($tableData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $promptGrafik = Grafik::where('kd_grafik', $kd_grafik)->first();

        if (empty($promptGrafik->prompt)) {
            $prompt = "Anda adalah data analyst, tolong Berikan analisis data ".$promptGrafik->judul_grafik;
        } else {
            $prompt = $promptGrafik->prompt . ", Berikut data ". $promptGrafik->judul_grafik .":\n\n" . $dataString;
        }       
        return $prompt;
    }
    
    
     // Tambahkan method baru untuk generate kesimpulan
    public function generateConclusion(Request $request)
    {
        try {
            $kd_laporan = $request->input('kd_laporan');
            
            // Validasi input
            if (empty($kd_laporan)) {
                return redirect()->back()
                    ->with('error', 'Data kd_laporan harus diisi');
            }

            // Ambil semua hasil analisis untuk laporan ini
            if (config('database.default') === 'bigquery') {
                $hasilAnalisis = DB::table('hasil_analisis')
                    ->where('kd_laporan', $kd_laporan)
                    ->where('title', '!=', 'Kesimpulan dan Saran') // Exclude existing conclusion
                    ->get();
            } else {
                $hasilAnalisis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                    ->where('title', '!=', 'Kesimpulan dan Saran') // Exclude existing conclusion
                    ->get();
            }

            if ($hasilAnalisis->isEmpty()) {
                return redirect()->back()
                    ->with('error', 'Tidak ada data analisis untuk dibuatkan kesimpulan');
            }

            // Siapkan data untuk dikirim ke AI
            $allAnalysisData = [];
            foreach ($hasilAnalisis as $analysis) {
                // Decode data jika berupa string JSON
                $decodedData = [];
                if (!empty($analysis->data)) {
                    if (is_string($analysis->data)) {
                        try {
                            $decodedData = json_decode($analysis->data, true) ?? [];
                        } catch (\Exception $e) {
                            $decodedData = [];
                        }
                    } else {
                        $decodedData = (array)$analysis->data;
                    }
                }
                
                $allAnalysisData[] = [
                    'title' => $analysis->title,
                    'data' => $decodedData,
                    'analysis' => $analysis->analisis
                ];
            }

            // Lakukan analisis kesimpulan dengan Gemini
            $conclusion = $this->generateConclusionWithGemini($allAnalysisData);

            // Simpan hasil kesimpulan ke database
            $hasilKesimpulan = HasilAnalisis::updateOrCreate(
                [
                    'kd_laporan' => $kd_laporan,
                    'kd_grafik' => 99,
                    'title' => 'Kesimpulan dan Saran'
                ],
                [
                    'data' => json_encode(['sumber_analisis' => array_column($allAnalysisData, 'title')]),
                    'analisis' => $conclusion,
                    'updated_at' => now()
                ]
            );

            return redirect()->back()
                ->with('success', 'Kesimpulan berhasil dibuat')
                ->with('analyzed_title', 'Kesimpulan dan Saran');

        } catch (\Exception $e) {
            Log::error('Error dalam generateConclusion: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan server: ' . $e->getMessage());
        }
    }

    // Tambahkan method untuk generate kesimpulan dengan Gemini
    private function generateConclusionWithGemini(array $allAnalysisData): string
    {
        if (empty($allAnalysisData)) {
            return 'Tidak ada data analisis untuk dibuatkan kesimpulan.';
        }

        // Format data untuk prompt
        $analysisSummary = "Berikut adalah rangkuman dari beberapa analisis yang telah dilakukan:\n\n";
        
        foreach ($allAnalysisData as $index => $analysis) {
            $analysisSummary .= "ANALISIS " . ($index + 1) . ": " . $analysis['title'] . "\n";
            
            // Ringkasan data (jika ada)
            if (!empty($analysis['data'])) {
                $analysisSummary .= "Data: " . json_encode($analysis['data'], JSON_UNESCAPED_UNICODE) . "\n";
            }
            
            // Ringkasan analisis (jika ada)
            if (!empty($analysis['analysis'])) {
                $cleanAnalysis = strip_tags($analysis['analysis']);
                $analysisSummary .= "Hasil Analisis: " . Str::limit($cleanAnalysis, 500) . "\n";
            }
            
            $analysisSummary .= "----------------------------------------\n";
        }

        $prompt = "Berdasarkan kumpulan analisis berikut, buatlah sebuah KESIMPULAN UMUM dan SARAN yang komprehensif. \n";
        $prompt .= "Format respons harus dalam bentuk JSON dengan struktur berikut:\n";
        $prompt .= "{\n";
        $prompt .= "  \"judul\": \"Kesimpulan dan Saran\",\n";
        $prompt .= "  \"ringkasan_umum\": \"<ringkasan singkat>\",\n";
        $prompt .= "  \"poin_penting\": [\"<poin penting 1>\", \"<poin penting 2>\", ...],\n";
        $prompt .= "  \"kesimpulan\": \"<kesimpulan detail>\",\n";
        $prompt .= "  \"saran\": [\"<saran 1>\", \"<saran 2>\", \"<saran 3>\", ...],\n";
        $prompt .= "  \"rekomendasi_strategis\": [\"<rekomendasi 1>\", \"<rekomendasi 2>\", ...]\n";
        $prompt .= "}\n\n";
        $prompt .= "Jangan beri kalimat pengantar, penutup, atau karakter tambahan. Hanya kembalikan objek JSON saja.\n\n";
        $prompt .= $analysisSummary;

        Log::info('Conclusion Gemini Prompt: ' . $prompt);

        // Kirim permintaan ke Gemini API (menggunakan logic yang sama dengan analyzeTableWithGemini)
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            $errorMsg = 'GEMINI_API_KEY is not set in .env file.';
            Log::error($errorMsg);
            return $this->generateManualConclusion($allAnalysisData);
        }

        $client = new Client();
        $apiEndpoints = [
            'v1beta/models/gemini-pro:generateContent',
            'v1/models/gemini-pro:generateContent',
            'v1beta/models/gemini-1.5-pro:generateContent',
            'v1/models/gemini-1.5-pro:generateContent',
            'v1beta/models/gemini-1.0-pro:generateContent',
            'v1/models/gemini-1.0-pro:generateContent'
        ];

        $lastError = null;

        foreach ($apiEndpoints as $endpoint) {
            try {
                $apiUrl = "https://generativelanguage.googleapis.com/{$endpoint}?key={$apiKey}";
                Log::info("Trying Gemini API endpoint for conclusion: {$apiUrl}");

                $response = $client->post($apiUrl, [
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'topK' => 40,
                            'topP' => 0.95,
                            'maxOutputTokens' => 2048, // Lebih banyak token untuk kesimpulan
                        ]
                    ],
                    'timeout' => 60, // Timeout lebih lama untuk kesimpulan
                ]);

                $body = json_decode($response->getBody(), true);
                Log::info('Gemini Conclusion API Response: ', $body);

                $geminiResponseText = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$geminiResponseText) {
                    throw new \Exception('Kesimpulan tidak dapat dihasilkan. (Respons kosong dari API)');
                }

                Log::info('Gemini Conclusion Raw Response: ' . $geminiResponseText);

                // Bersihkan response
                $cleanJsonString = trim($geminiResponseText);
                if (strpos($cleanJsonString, '```json') === 0) {
                    $cleanJsonString = str_replace('```json', '', $cleanJsonString);
                }
                if (strpos($cleanJsonString, '```') !== false) {
                    $cleanJsonString = str_replace('```', '', $cleanJsonString);
                }

                $conclusionData = json_decode($cleanJsonString, true);

                if ($conclusionData && is_array($conclusionData)) {
                    // Format menjadi HTML
                    $htmlOutput = "<h2>{$conclusionData['judul']}</h2>";
                    
                    if (isset($conclusionData['ringkasan_umum'])) {
                        $htmlOutput .= "<div class='alert alert-info'><strong>Ringkasan Umum:</strong> {$conclusionData['ringkasan_umum']}</div>";
                    }
                    
                    if (isset($conclusionData['poin_penting']) && is_array($conclusionData['poin_penting'])) {
                        $htmlOutput .= "<h3>Poin Penting</h3><ul>";
                        foreach ($conclusionData['poin_penting'] as $poin) {
                            $htmlOutput .= "<li>{$poin}</li>";
                        }
                        $htmlOutput .= "</ul>";
                    }
                    
                    if (isset($conclusionData['kesimpulan'])) {
                        $htmlOutput .= "<h3>Kesimpulan</h3><div class='kesimpulan-content'>{$conclusionData['kesimpulan']}</div>";
                    }
                    
                    if (isset($conclusionData['saran']) && is_array($conclusionData['saran'])) {
                        $htmlOutput .= "<h3>Saran</h3><ul>";
                        foreach ($conclusionData['saran'] as $saran) {
                            $htmlOutput .= "<li>{$saran}</li>";
                        }
                        $htmlOutput .= "</ul>";
                    }
                    
                    if (isset($conclusionData['rekomendasi_strategis']) && is_array($conclusionData['rekomendasi_strategis'])) {
                        $htmlOutput .= "<h3>Rekomendasi Strategis</h3><ul>";
                        foreach ($conclusionData['rekomendasi_strategis'] as $rekomendasi) {
                            $htmlOutput .= "<li>{$rekomendasi}</li>";
                        }
                        $htmlOutput .= "</ul>";
                    }
                    
                    return $htmlOutput;
                }

                // Fallback: return raw response jika parsing gagal
                return "<h2>Kesimpulan dan Saran</h2><div class='analysis-content'>" . nl2br(e($geminiResponseText)) . "</div>";

            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $lastError = $e;
                
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $statusCode = $response->getStatusCode();
                    
                    // Jika error 404, continue ke endpoint berikutnya
                    if ($statusCode === 404) {
                        Log::info("Endpoint {$endpoint} not found, trying next one...");
                        continue;
                    }
                    
                    // Untuk error selain 404, break loop dan return error
                    break;
                } else {
                    Log::error("Gemini API Error for endpoint {$endpoint}: " . $e->getMessage());
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e;
                Log::error("General Error for endpoint {$endpoint}: " . $e->getMessage());
                break;
            }
        }

        // Jika semua endpoint gagal, return manual conclusion
        return $this->generateManualConclusion($allAnalysisData);
    }

    // Method untuk membuat kesimpulan manual
    private function generateManualConclusion(array $allAnalysisData): string
    {
        $output = "<h2>Kesimpulan dan Saran</h2>";
        $output .= "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Analisis Manual (AI tidak tersedia)</div>";
        
        $output .= "<h3>Ringkasan Analisis</h3>";
        $output .= "<p>Berdasarkan " . count($allAnalysisData) . " analisis yang telah dilakukan, berikut adalah kesimpulan umum:</p>";
        
        $output .= "<h3>Poin Penting</h3>";
        $output .= "<ul>";
        $output .= "<li>Total " . count($allAnalysisData) . " dataset dianalisis</li>";
        
        foreach ($allAnalysisData as $analysis) {
            $output .= "<li>" . Str::limit($analysis['title'], 30) . ": Data tersedia</li>";
        }
        $output .= "</ul>";
        
        $output .= "<h3>Saran Umum</h3>";
        $output .= "<ul>";
        $output .= "<li>Lakukan monitoring berkala terhadap semua metrik</li>";
        $output .= "<li>Bandingkan dengan target yang ditetapkan</li>";
        $output .= "<li>Identifikasi pola dan tren yang muncul</li>";
        $output .= "<li>Lakukan tindakan korektif jika diperlukan</li>";
        $output .= "</ul>";
        
        $output .= "<h3>Rekomendasi</h3>";
        $output .= "<ul>";
        $output .= "<li>Pastikan GEMINI_API_KEY diatur untuk analisis lebih mendalam</li>";
        $output .= "<li>Gunakan tools analitik untuk visualisasi data</li>";
        $output .= "<li>Jadwalkan review berkala</li>";
        $output .= "</ul>";
        
        return $output;
    }
    
    /**
     * Helper method to get analysis data from database
     */
    private function getAnalysisDataFromDatabase($kd_laporan, $kd_grafik)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $existingAnalysis = DB::table('hasil_analisis')
                    ->where('kd_laporan', $kd_laporan)
                    ->where('kd_grafik', $kd_grafik)
                    ->first();
            } else {
                $existingAnalysis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                    ->where('kd_grafik', $kd_grafik)
                    ->first();
            }

            if (!$existingAnalysis || empty($existingAnalysis->data)) {
                return null;
            }

            return $this->decodeData($existingAnalysis->data);

        } catch (\Exception $e) {
            Log::error('Error getting analysis data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper method to check if analysis data exists
     */
    private function checkAnalysisDataExists($kd_laporan, $kd_grafik)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $existingAnalysis = DB::table('hasil_analisis')
                    ->where('kd_laporan', $kd_laporan)
                    ->where('kd_grafik', $kd_grafik)
                    ->first();
            } else {
                $existingAnalysis = HasilAnalisis::where('kd_laporan', $kd_laporan)
                    ->where('kd_grafik', $kd_grafik)
                    ->first();
            }

            return $existingAnalysis && !empty($existingAnalysis->data);

        } catch (\Exception $e) {
            Log::error('Error checking analysis data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize data untuk mengurangi token usage
     */
    private function optimizeDataForAnalysis(array $data): array
    {
        $optimized = [];
        
        foreach ($data as $item) {
            // Hanya ambil field yang penting untuk analisis
            $optimizedItem = [
                'nama_akun' => $item['nama_akun'] ?? null,
                'target' => $item['target'] ?? 0,
                'realisasi' => $item['realisasi'] ?? 0,
                'gap' => $item['gap'] ?? 0,
                'percentage' => $item['percentage'] ?? 0,
                'keterangan' => $item['keterangan'] ?? '',
                'level' => $item['level'] ?? 1,
                'entity' => $item['entity'] ?? ''
            ];
            
            // Hapus HTML tags dari nama_akun untuk mengurangi token
            if (isset($optimizedItem['nama_akun'])) {
                $optimizedItem['nama_akun'] = strip_tags($optimizedItem['nama_akun']);
            }
            
            $optimized[] = $optimizedItem;
        }
        
        return $optimized;
    }

    /**
     * Buat prompt yang lebih efisien
     */
    private function createOptimizedPrompt(array $optimizedData, string $grafikPrompt, string $judulGrafik): string
    {
        $dataSummary = $this->createDataSummary($optimizedData);
        $dataSample = array_slice($optimizedData, 0, 10); // Ambil sample 10 data pertama
        
        $prompt = "Buat analisis untuk: {$judulGrafik}\n\n";
        $prompt .= "Ringkasan Data:\n{$dataSummary}\n\n";
        $prompt .= "Sample Data (10 pertama):\n" . json_encode($dataSample, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Instruksi Analisis: {$grafikPrompt}\n\n";
        $prompt .= "Format respons: HTML yang valid dan bersih. Fokus pada insight utama, tren, dan rekomendasi.\n";
        $prompt .= "Jangan ulangi semua data dalam analisis, cukup berikan insight yang meaningful.";
        
        return $prompt;
    }

    /**
     * Buat summary data untuk mengurangi token
     */
    private function createDataSummary(array $data): string
    {
        $totalItems = count($data);
        $entities = array_unique(array_column($data, 'entity'));
        $achieveCount = count(array_filter($data, fn($item) => ($item['keterangan'] ?? '') === 'ACHIEVE'));
        $notAchieveCount = count(array_filter($data, fn($item) => ($item['keterangan'] ?? '') === 'TIDAK ACHIEVE'));
        
        $summary = "Total Data: {$totalItems} item\n";
        $summary .= "Entitas: " . implode(', ', $entities) . "\n";
        $summary .= "Achieve: {$achieveCount} item\n";
        $summary .= "Tidak Achieve: {$notAchieveCount} item\n";
        
        // Hitung statistik numerik
        $percentages = array_column($data, 'percentage');
        $gaps = array_column($data, 'gap');
        
        if (!empty($percentages)) {
            $avgPercentage = array_sum($percentages) / count($percentages);
            $minPercentage = min($percentages);
            $maxPercentage = max($percentages);
            
            $summary .= sprintf("Persentase: Rata2 %.2f%%, Min %.2f%%, Max %.2f%%\n", 
                $avgPercentage, $minPercentage, $maxPercentage);
        }
        
        return $summary;
    }

    /**
     * Analisis dengan Gemini - Optimized version
     */
    private function analyzeWithGeminiOptimized(string $prompt, string $tableTitle): string
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        
        if (empty($apiKey)) {
            return $this->generateManualAnalysis($tableTitle, []);
        }

        $client = new Client();
        
        try {
            Log::info("Mengirim permintaan ke Gemini API (Optimized)");
            Log::info("Panjang prompt: " . strlen($prompt) . " karakter");

            $response = $client->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent", [
                'query' => ['key' => $apiKey],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 2048, // Batasi output tokens
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE']
                    ]
                ],
                'timeout' => 60,
            ]);

            $body = json_decode($response->getBody(), true);
            
            if (isset($body['candidates'][0]['finishReason']) && $body['candidates'][0]['finishReason'] !== 'STOP') {
                $reason = $body['candidates'][0]['finishReason'];
                Log::warning("Gemini Response ditolak: " . $reason);
                
                // Fallback untuk MAX_TOKENS
                if ($reason === 'MAX_TOKENS') {
                    return $this->generateFallbackAnalysis($tableTitle);
                }
                
                return "Analisis gagal. Model menolak respons karena alasan: " . $reason;
            }

            $geminiResponseText = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$geminiResponseText) {
                throw new \Exception('Analisis tidak dapat dihasilkan. Respons kosong.');
            }

            return $geminiResponseText;

        } catch (\Exception $e) {
            Log::error('Error dalam analyzeWithGeminiOptimized: ' . $e->getMessage());
            return $this->generateFallbackAnalysis($tableTitle);
        }
    }

    /**
     * Fallback analysis ketika token terbatas
     */
    private function generateFallbackAnalysis(string $tableTitle): string
    {
        return "<h2>Analisis: {$tableTitle}</h2>
                <div class='alert alert-warning'>
                    <strong>Catatan:</strong> Analisis dibatasi karena data yang terlalu besar.
                    Disarankan untuk menganalisis data per entitas atau periode yang lebih spesifik.
                </div>
                <h3>Rekomendasi:</h3>
                <ul>
                    <li>Pecah data menjadi bagian yang lebih kecil</li>
                    <li>Analisis per entitas secara terpisah</li>
                    <li>Fokus pada periode waktu tertentu</li>
                    <li>Gunakan filter untuk membatasi jumlah data</li>
                </ul>";
    }

    /**
     * Execute analysis with corrected prompt - OPTIMIZED VERSION
     */
    public function executeAnalysis(Request $request)
    {
        try {
            $kd_laporan = $request->input('kd_laporan');
            $kd_grafik = $request->input('kd_grafik');
            $promptSave = $request->input('prompt');
            
            // Validasi input
            if (empty($kd_laporan) || empty($kd_grafik) || empty($promptSave)) {
                return redirect()->back()
                    ->with('error', 'Data kd_laporan, kd_grafik, dan prompt harus diisi');
            }
        
            // Cek apakah laporan exists
            if (config('database.default') === 'bigquery') {
                $laporan = DB::table('laporan')
                    ->where('kd_laporan', $kd_laporan)
                    ->first();
            } else {
                $laporan = Laporan::find($kd_laporan);
            }
        
            if (!$laporan) {
                return redirect()->route('laporan.view')
                    ->with('error', 'Laporan tidak ditemukan');
            }
        
            // AMBIL DATA DARI DATABASE
            $decodedData = $this->getAnalysisDataFromDatabase($kd_laporan, $kd_grafik);
            
            if (empty($decodedData)) {
                return redirect()->back()
                    ->with('error', 'Data tidak ditemukan. Silakan generate data terlebih dahulu.');
            }
        
            $grafik = Grafik::where('kd_grafik', $kd_grafik)->first();
            
            if (!$grafik) {
                return redirect()->back()
                    ->with('error', 'Data grafik tidak ditemukan');
            }

            // **OPTIMASI DATA: Buat summary data yang lebih ringkas**
            $optimizedData = $this->optimizeDataForAnalysis($decodedData);
            
            // **OPTIMASI PROMPT: Gunakan prompt yang lebih efisien**
            $prompt = $this->createOptimizedPrompt($optimizedData, $grafik->prompt, $grafik->judul_grafik);
        
            // Set session untuk menunjukkan bahwa proses analisis sedang berjalan
            Session::flash('analyzing', true);
        
            // Lakukan analisis dengan Gemini
            $analysis = $this->analyzeWithGeminiOptimized($prompt, $grafik->judul_grafik);
            
            // Update atau create data di hasil_analisis
            $hasilAnalisis = HasilAnalisis::updateOrCreate(
                [
                    'kd_laporan' => $kd_laporan,
                    'kd_grafik' => $kd_grafik
                ],
                [
                    'data' => $decodedData,
                    'analisis' => $analysis,
                    'prompt' => $promptSave,
                    'updated_at' => now()
                ]
            );
        
            return redirect()->route('laporan.detail', $kd_laporan)
                ->with('success', 'Analisis berhasil dilakukan')
                ->with('analyzed_title', $grafik->judul_grafik);
        
        } catch (\Exception $e) {
            Log::error('Error dalam executeAnalysis: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan server: ' . $e->getMessage());
        }
    }

}
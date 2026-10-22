<?php

namespace App\Http\Controllers\laporan;

use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use App\Models\HasilAnalisis;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // This line was added
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

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
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'keterangan' => 'required'
            ]);

            $data = [
                'tanggal' => $request->tanggal,
                'tanggal_awal' => $request->tanggal_awal,
                'tanggal_akhir' => $request->tanggal_akhir,
                //'kd_user' => auth()->id(),
                'keterangan' => $request->keterangan,
                'status' => $request->status,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Handle BigQuery's UUID requirement
            if (config('database.default') === 'bigquery') {
                $data['kd_laporan'] = Str::uuid()->toString();
                DB::table('laporan')->insert($data);
            } else {
                Laporan::create($data);
            }

            return redirect()->route('laporan.view')
                ->with('success', 'Laporan berhasil ditambahkan');

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

                $item->status_analisis = 'Kosong';
                if (!empty($item->analisis) && strlen($item->analisis) > 50) {
                    $item->status_analisis = 'Ada';
                }
                else{
                    $item->status_analisis = 'Kosong';
                }

                // Tentukan jenis analisis (Manual atau AI)
                $item->jenis_analisis = 'Manual';
                if (!empty($item->analisis)) {
                    // Cek apakah analisis mengandung indikator bahwa ini adalah analisis AI
                    if (strpos($item->analisis, 'Analisis Data:') !== false || 
                        strpos($item->analisis, 'Analisis Manual:') === false) {
                        $item->jenis_analisis = 'AI';
                    }
                    
                    // Cek lebih spesifik untuk analisis AI
                    if (strpos($item->analisis, 'class="analysis-content"') !== false ||
                        strpos($item->analisis, '<h2>Analisis Data:') !== false) {
                        $item->jenis_analisis = 'AI';
                    }
                    
                    // Jika mengandung kata "Analisis Manual" maka tetap Manual
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

    public function analyze(Request $request)
    {
        try {
            $kd_laporan = $request->input('kd_laporan');
            $title = $request->input('title');
            $data = $request->input('data');

            // Validasi input
            if (empty($kd_laporan) || empty($title)) {
                return redirect()->back()
                    ->with('error', 'Data kd_laporan dan title harus diisi');
            }

            // Debug: Lihat tipe data yang diterima
            Log::info('Data received:', [
                'type' => gettype($data),
                'data' => $data
            ]);

            // Handle data yang mungkin sudah berupa array atau JSON string
            if (is_string($data)) {
                // Coba decode JSON string
                $decodedData = json_decode($data, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Jika bukan JSON, mungkin sudah berupa array yang di-stringify
                    // Coba parse manual untuk format khusus
                    if (strpos($data, '{') === 0 && strpos($data, '}') !== false) {
                        // Remove potential quotes and decode
                        $cleanedData = stripslashes($data);
                        $decodedData = json_decode($cleanedData, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return redirect()->back()
                                ->with('error', 'Format data tidak valid: ' . json_last_error_msg());
                        }
                    } else {
                        return redirect()->back()
                            ->with('error', 'Format data tidak valid: String bukan JSON');
                    }
                }
            } else {
                // Jika data sudah berupa array
                $decodedData = $data;
            }

            Log::info('Decoded data:', ['data' => $decodedData]);

            // Validasi data decoded
            if (empty($decodedData)) {
                return redirect()->back()
                    ->with('error', 'Data tidak boleh kosong');
            }

            // Set session untuk menunjukkan bahwa proses analisis sedang berjalan
            Session::flash('analyzing', true);

            // Lakukan analisis dengan Gemini
            $analysis = $this->analyzeTableWithGemini($title, $decodedData);

            // Update atau create data di hasil_analisis
            $hasilAnalisis = HasilAnalisis::updateOrCreate(
                [
                    'kd_laporan' => $kd_laporan,
                    'title' => $title
                ],
                [
                    'data' => $decodedData,
                    'analisis' => $analysis,
                    'updated_at' => now()
                ]
            );

            return redirect()->back()
                ->with('success', 'Analisis berhasil dilakukan')
                ->with('analyzed_title', $title);

        } catch (\Exception $e) {
            Log::error('Error dalam analyze: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan server: ' . $e->getMessage());
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
            // This also allows for a history of reports for the same period.
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

            // Perbaikan: Gunakan with('success') yang benar
            return redirect()->route('laporan.view')
                ->with('success', 'Laporan JSON dan Laporan Penjualan telah berhasil dibuat!');

        } catch (\Exception $e) {
            // Perbaikan: Gunakan with('error') yang benar
            return redirect()->back()
                ->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    private function analyzeTableWithGemini(string $tableTitle, array $tableData): string
    {
        // 1. Format data ke dalam string yang mudah dibaca oleh model
        if (empty($tableData)) {
            return 'Tidak ada data untuk dianalisis.';
        }

        Log::info('Gemini Analysis - Table Title: ' . $tableTitle);
        Log::info('Gemini Analysis - Table Data: ', $tableData);

        // Langsung konversi array menjadi string JSON untuk prompt
        $dataString = "Berikut data tabel '{$tableTitle}' dalam format JSON:\n";
        $dataString .= json_encode($tableData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "Berdasarkan data JSON berikut, berikan uraian dan analisis singkat dalam format JSON. Analisis harus memiliki struktur sebagai berikut: {\"judul\":\"<judul>\", \"sub_judul\":[{\"teks\":\"<sub judul>\", \"poin\":[\"<poin 1>\",\"<poin 2>\"]}, ...]}. Jangan beri kalimat pengantar, penutup, atau karakter tambahan. Hanya kembalikan objek JSON saja. Berikut datanya:\n\n" . $dataString;

        Log::info('Gemini Prompt: ' . $prompt);

        // 2. Kirim permintaan ke Gemini API
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            $errorMsg = 'GEMINI_API_KEY is not set in .env file.';
            Log::error($errorMsg);
            return 'Maaf, kunci API tidak ditemukan. Silakan hubungi administrator.';
        }

        Log::info('Gemini API Key found, making request...');

        $client = new Client();

        // Daftar endpoint yang akan dicoba secara berurutan
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
                Log::info("Trying Gemini API endpoint: {$apiUrl}");

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
                            'maxOutputTokens' => 1024,
                        ]
                    ],
                    'timeout' => 30,
                ]);

                $body = json_decode($response->getBody(), true);
                Log::info('Gemini API Response: ', $body);

                $geminiResponseText = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$geminiResponseText) {
                    throw new \Exception('Analisis tidak dapat dihasilkan. (Respons kosong dari API)');
                }

                Log::info('Gemini Raw Response: ' . $geminiResponseText);

                // Bersihkan response
                $cleanJsonString = trim($geminiResponseText);
                if (strpos($cleanJsonString, '```json') === 0) {
                    $cleanJsonString = str_replace('```json', '', $cleanJsonString);
                }
                if (strpos($cleanJsonString, '```') !== false) {
                    $cleanJsonString = str_replace('```', '', $cleanJsonString);
                }

                $analysisData = json_decode($cleanJsonString, true);

                if ($analysisData && is_array($analysisData)) {
                    if (isset($analysisData['judul'])) {
                        $htmlOutput = "<h2>{$analysisData['judul']}</h2>";
                        if (isset($analysisData['sub_judul']) && is_array($analysisData['sub_judul'])) {
                            foreach ($analysisData['sub_judul'] as $sub) {
                                $htmlOutput .= "<h3>{$sub['teks']}</h3>";
                                $htmlOutput .= "<ul>";
                                if (isset($sub['poin']) && is_array($sub['poin'])) {
                                    foreach ($sub['poin'] as $poin) {
                                        $htmlOutput .= "<li>{$poin}</li>";
                                    }
                                }
                                $htmlOutput .= "</ul>";
                            }
                        }
                        return $htmlOutput;
                    } else {
                        // Fallback: format as simple list
                        $htmlOutput = "<h2>Analisis Data: {$tableTitle}</h2>";
                        foreach ($analysisData as $key => $value) {
                            if (is_array($value)) {
                                $htmlOutput .= "<h3>{$key}</h3><ul>";
                                foreach ($value as $item) {
                                    $htmlOutput .= "<li>{$item}</li>";
                                }
                                $htmlOutput .= "</ul>";
                            } else {
                                $htmlOutput .= "<p><strong>{$key}:</strong> {$value}</p>";
                            }
                        }
                        return $htmlOutput;
                    }
                }

                // Fallback: return raw response jika parsing gagal
                return "<h3>Analisis Data: {$tableTitle}</h3><div class='analysis-content'>" . nl2br(e($geminiResponseText)) . "</div>";

            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $lastError = $e;
                
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $statusCode = $response->getStatusCode();
                    $body = $response->getBody()->getContents();
                    
                    Log::warning("Gemini API Error for endpoint {$endpoint}: Status {$statusCode}");
                    
                    // Jika error 404, continue ke endpoint berikutnya
                    if ($statusCode === 404) {
                        Log::info("Endpoint {$endpoint} not found, trying next one...");
                        continue;
                    }
                    
                    // Untuk error selain 404, break loop dan return error
                    Log::error("Gemini API Error: Status {$statusCode}, Body: {$body}");
                    break;
                } else {
                    Log::error("Gemini API Error for endpoint {$endpoint}: " . $e->getMessage());
                    // Untuk error koneksi, break loop
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e;
                Log::error("General Error for endpoint {$endpoint}: " . $e->getMessage());
                // Untuk error umum, break loop
                break;
            }
        }

        // Jika semua endpoint gagal, return error
        $errorMsg = 'Maaf, semua endpoint Gemini API gagal. ';
        
        if ($lastError instanceof \GuzzleHttp\Exception\RequestException && $lastError->hasResponse()) {
            $response = $lastError->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            if ($statusCode === 404) {
                $errorMsg .= 'Model Gemini tidak ditemukan di semua versi API. ';
            } elseif ($statusCode === 403) {
                $errorMsg .= 'Akses ditolak. Pastikan API key valid. ';
            } elseif ($statusCode === 400) {
                $errorMsg .= 'Permintaan tidak valid. ';
            }
            
            $errorMsg .= 'Silakan coba lagi nanti atau hubungi administrator.';
        } else {
            $errorMsg .= 'Silakan cek koneksi internet Anda.';
        }
        
        return $this->generateManualAnalysis($tableTitle, $tableData) . 
            "<div class='alert alert-warning mt-3'><i class='fas fa-exclamation-triangle'></i> {$errorMsg}</div>";
    }

    // Fallback analysis manual
    private function generateManualAnalysis(string $tableTitle, array $tableData): string
    {
        $output = "<h2>Analisis Manual: {$tableTitle}</h2>";
        
        // Basic statistical analysis
        if (!empty($tableData)) {
            $output .= "<h3>Statistik Dasar</h3>";
            $output .= "<ul>";
            
            // Handle different data format            
            $output .= "<li>Jumlah Data: " . count($tableData) . "</li>";
            $output .= "</ul>";
        }
        
        $output .= "<h3>Rekomendasi</h3>";
        $output .= "<ul>";
        $output .= "<li>Pastikan GEMINI_API_KEY diatur di file .env untuk analisis lebih mendalam</li>";
        $output .= "<li>Monitor tren data secara berkala</li>";
        $output .= "<li>Bandingkan dengan target yang ditetapkan</li>";
        $output .= "<li>Identifikasi pola musiman jika ada</li>";
        $output .= "</ul>";
        
        return $output;
    }

    private function fallbackAnalysis(string $tableTitle, array $tableData): string
    {
        $output = "<h2>Analisis: {$tableTitle}</h2>";
        
        if (isset($tableData['Januari'])) {
            // Analisis data bulanan
            $total = array_sum($tableData);
            $average = $total / count($tableData);
            $maxMonth = array_search(max($tableData), $tableData);
            $minMonth = array_search(min($tableData), $tableData);
            
            $output .= "<h3>Ringkasan Statistik</h3>";
            $output .= "<ul>";
            $output .= "<li>Total: " . number_format($total) . "</li>";
            $output .= "<li>Rata-rata: " . number_format($average) . "</li>";
            $output .= "<li>Bulan tertinggi: {$maxMonth} (" . number_format($tableData[$maxMonth]) . ")</li>";
            $output .= "<li>Bulan terendah: {$minMonth} (" . number_format($tableData[$minMonth]) . ")</li>";
            $output .= "</ul>";
        }
        
        $output .= "<h3>Rekomendasi</h3>";
        $output .= "<ul>";
        $output .= "<li>Lakukan evaluasi terhadap performa bulanan</li>";
        $output .= "<li>Identifikasi pola tren data</li>";
        $output .= "<li>Bandingkan dengan target yang ditetapkan</li>";
        $output .= "</ul>";
        
        return $output;
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

}
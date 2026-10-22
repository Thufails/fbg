<?php

namespace App\Http\Controllers\detailTarget;

use App\Models\DetailTarget;
use App\Models\Target;
use App\Models\DetailAkun;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class detailTargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // ... (bagian atas controller tetap sama)

    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $kd_detail_akun = $request->input('kd_detail_akun', null);

            $query = DB::table('detail_target')
                ->leftJoin('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
                ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                ->select(
                    'detail_target.*',
                    'detail_akun.nama_detail_akun',
                    'akun.nama_akun',
                );

            if ($startDate && $endDate) {
                $query->whereBetween('detail_target.tanggal', [$startDate, $endDate]);
            }

            if ($kd_detail_akun) {
                $query->where('detail_target.kd_detail_akun', $kd_detail_akun);
            }

            $perPage = 20;
            $detailTarget = $query->paginate($perPage);

            // Get detail akun list for filter dropdown
            $detailAkunList = DB::table('detail_akun')
                ->select('kd_detail_akun', 'nama_detail_akun')
                ->leftJoin('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                ->orderBy('kd_detail_akun')
                ->select(
                    'detail_akun.*',
                    'detail_akun.nama_detail_akun',
                    'akun.nama_akun',
                )
                ->get();
            
            return view('detailTarget.view', compact('detailTarget', 'detailAkunList'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Gagal mengambil data: '.$e->getMessage()], 500);
            }
            return redirect()->route('detailTarget.view')
                ->with('error', 'Gagal mengambil data: '.$e->getMessage());
        }
    }

    /**
     * Bulk delete selected records
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $request->validate([
                'selected_ids' => 'required|array',
                'selected_ids.*' => 'exists:detail_target,kd_target_detail'
            ]);

            DB::beginTransaction();
            
            $deletedCount = DB::table('detail_target')
                ->whereIn('kd_target_detail', $request->selected_ids)
                ->delete();
                
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil menghapus $deletedCount detail target.",
                    'count' => $deletedCount
                ]);
            }

            return back()->with('success', "Berhasil menghapus $deletedCount detail target.");
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus detail target: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Gagal menghapus detail target: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $detailAkunList = DB::table('detail_akun')->get();
            $targetList = DB::table('target')->get();
            
            return view('detailTarget.insert', compact('detailAkunList', 'targetList'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'kd_detail_akun' => 'required|string|max:20',
                'nilai' => 'required|numeric',
            ]);

            // Convert nilai to decimal (remove any formatting)
            $validated['nilai'] = (float) str_replace(['.', ','], '', $validated['nilai']);

            $dataToInsert = [
                'tanggal' => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
                'kd_detail_akun' => $validated['kd_detail_akun'],
                'nilai' => $validated['nilai'],
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Generate UUID for BigQuery
            if (config('database.default') === 'bigquery') {
                $dataToInsert['kd_target_detail'] = Str::uuid()->toString();
                DB::table('detail_target')->insert($dataToInsert);
            } else {
                // For MySQL, let the database auto-increment the ID
                DB::table('detail_target')->insert($dataToInsert);
            }
            
            return redirect()->route('detailTarget.view')
                ->with('success', 'Detail target berhasil ditambahkan');
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan detail target: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $query = DB::table('detail_target')
                ->leftJoin('detail_akun', 'detail_target.kd_detail_akun', '=', 'detail_akun.kd_detail_akun')
                ->where('detail_target.kd_target_detail', $id);

            $detailTarget = config('database.default') === 'bigquery' 
                ? $query->first()
                : $query->first();

            if (!$detailTarget) {
                throw new \Exception('Data detail target tidak ditemukan');
            }

            // Convert dates to Carbon instances for BigQuery
            if (config('database.default') === 'bigquery') {
                $detailTarget->created_at = Carbon::parse($detailTarget->created_at);
                $detailTarget->updated_at = Carbon::parse($detailTarget->updated_at);
                $detailTarget->tanggal = Carbon::parse($detailTarget->tanggal);
            }

            return view('detailTarget.show', compact('detailTarget'));

        } catch (\Exception $e) {
            return redirect()
                ->route('detailTarget.view')
                ->with('error', 'Gagal menampilkan detail: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $query = DB::table('detail_target')
                ->where('kd_target_detail', $id);

            $detailTarget = config('database.default') === 'bigquery' 
                ? $query->first()
                : $query->first();

            if (!$detailTarget) {
                return redirect()->route('detailTarget.view')
                    ->with('error', 'Detail target tidak ditemukan');
            }

            $detailAkunList = DB::table('detail_akun')->get();
            $targetList = DB::table('target')->get();
            
            return view('detailTarget.edit', compact('detailTarget', 'detailAkunList', 'targetList'));

        } catch (\Exception $e) {
            return redirect()->route('detailTarget.view')
                ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Bulk update selected records
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $request->validate([
                'selected_ids' => 'required|array',
                'selected_ids.*' => 'exists:detail_target,kd_target_detail',
                'nilai' => 'required|numeric'
            ]);

            DB::beginTransaction();

            $nilai = (float) str_replace(['.', ','], '', $request->nilai);
            $affected = DB::table('detail_target')
                ->whereIn('kd_target_detail', $request->selected_ids)
                ->update([
                    'nilai' => $nilai,
                    'updated_at' => now()
                ]);
                
            DB::commit();

            return redirect()->route('detailTarget.view')
                ->with('success', "Berhasil mengupdate $affected detail target");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('detailTarget.view')
                ->with('error', 'Gagal mengupdate detail target: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'kd_detail_akun' => 'required|string|max:20',
                'nilai' => 'required|numeric',
            ]);

            $updateData = [
                'tanggal' => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
                'kd_detail_akun' => $validated['kd_detail_akun'],
                'nilai' => (float) str_replace(['.', ','], '', $validated['nilai']),
                'updated_at' => now()
            ];

            if (config('database.default') === 'bigquery') {
                $affected = DB::table('detail_target')
                    ->where('kd_target_detail', $id)
                    ->update($updateData);
            } else {
                $affected = DB::table('detail_target')
                    ->where('kd_target_detail', $id)
                    ->update($updateData);
            }

            if ($affected === 0) {
                throw new \Exception('Tidak ada data yang diupdate');
            }

            return redirect()->route('detailTarget.view')
                ->with('success', 'Detail target berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah detail target: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $affected = DB::table('detail_target')
                    ->where('kd_target_detail', $id)
                    ->delete();
            } else {
                $affected = DB::table('detail_target')
                    ->where('kd_target_detail', $id)
                    ->delete();
            }

            if ($affected === 0) {
                throw new \Exception('Data tidak ditemukan');
            }

            return redirect()->route('detailTarget.view')
                ->with('success', 'Detail target berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()->route('detailTarget.view')
                ->with('error', 'Gagal menghapus detail target: '.$e->getMessage());
        }
    }

    // Add these methods to your controller

    /**
     * Show the target selection form
     */
    public function selectTarget(Request $request)
    {
        try {
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $kd_akun = $request->input('kd_akun', null);

            $query = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->select(
                    'target.*',
                    'akun.nama_akun',
                    'entitas.nama_entitas'
                );

            if ($startDate && $endDate) {
                $query->whereBetween('target.tanggal', [$startDate, $endDate]);
            }

            if ($kd_akun) {
                $query->where('target.kd_akun', $kd_akun);
            }

            $perPage = 20;
            $targets = config('database.default') === 'bigquery' 
                ? $query->simplePaginate($perPage)
                : $query->paginate($perPage);

            $akunList = DB::table('akun')
                ->select('kd_akun', 'nama_akun')
                ->orderBy('kd_akun')
                ->get();

            return view('detailTarget.select_target', compact('targets', 'startDate', 'endDate', 'kd_akun', 'akunList'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat daftar target: '.$e->getMessage());
        }
    }

    /**
     * Add target to temporary session
     */
    public function addToCart(Request $request)
    {
        try {
            $request->validate([
                'kd_target' => 'required|exists:target,kd_target',
                'tanggal' => 'required|array',
                'tanggal.*' => 'date'
            ]);

            $cart = session()->get('detail_target_cart', []);
            $target = DB::table('target')
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')                
                ->where('kd_target', $request->kd_target)
                ->select(
                    'target.*',
                    'akun.nama_akun',
                    'entitas.nama_entitas'
                )
                ->first();

            foreach ($request->tanggal as $tanggal) {
                $cartKey = $request->kd_target . '_' . $tanggal;
                
                if (!isset($cart[$cartKey])) {
                    $cart[$cartKey] = [
                        'kd_target' => $request->kd_target,
                        'tanggal' => $tanggal,
                        'kd_akun' => $target->kd_akun,
                        'nama_akun' => $target->nama_akun,
                        'kd_entitas' => $target->kd_entitas,
                        'nama_entitas' => $target->nama_entitas,
                        'details' => []
                    ];
                }
            }

            session()->put('detail_target_cart', $cart);

            return redirect()->route('detailTarget.createForTarget', $request->kd_target)
                ->with('success', 'Target dates added to cart');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan target: ' . $e->getMessage());
        }
    }

    public function createForTarget($targetId)
    {
        try {
            $cart = session()->get('detail_target_cart', []);
            $targetItems = array_filter($cart, function($item) use ($targetId) {
                return $item['kd_target'] == $targetId;
            });

            if (empty($targetItems)) {
                return redirect()->route('detailTarget.select')
                    ->with('warning', 'Silakan pilih tanggal target terlebih dahulu');
            }

            // Get all available dates for dropdown
            $availableDates = array_map(function($item) {
                return [
                    'date' => $item['tanggal'],
                    'formatted' => Carbon::parse($item['tanggal'])->format('d-m-Y')
                ];
            }, $targetItems);

            // Get first date as default selected
            $firstDate = reset($targetItems)['tanggal'];
            $selectedDateDetails = $targetItems[$targetId.'_'.$firstDate] ?? reset($targetItems);

            $target = DB::table('target')
                ->where('kd_target', $targetId)
                ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                ->first();

            $detailAkunList = DB::table('detail_akun')
                ->where('kd_akun', $target->kd_akun)
                ->get();

            return view('detailTarget.create_for_target', [
                'target' => $target,
                'availableDates' => $availableDates,
                'selectedDateDetails' => $selectedDateDetails,
                'detailAkunList' => $detailAkunList,
                'currentDate' => $firstDate
            ]);

        } catch (\Exception $e) {
            return redirect()->route('detailTarget.select')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Add detail to cart item
     */
    public function addDetailToCart(Request $request)
    {
        try {
            $request->validate([
                'kd_target' => 'required|exists:target,kd_target',
                'tanggal' => 'required|date',
                'kd_detail_akun' => 'required|exists:detail_akun,kd_detail_akun',
                'nilai' => 'required|numeric|min:0'
            ]);

            $cart = session()->get('detail_target_cart', []);
            $cartKey = $request->kd_target . '_' . $request->tanggal;

            if (!isset($cart[$cartKey])) {
                $target = DB::table('target')
                    ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                    ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                    ->where('kd_target', $request->kd_target)
                    ->first();
                
                $cart[$cartKey] = [
                    'kd_target' => $request->kd_target,
                    'tanggal' => $request->tanggal,
                    'kd_akun' => $target->kd_akun,
                    'nama_akun' => $target->nama_akun,
                    'kd_entitas' => $target->kd_entitas,
                    'nama_entitas' => $target->nama_entitas,
                    'details' => []
                ];
            }

            $detailAkun = DB::table('detail_akun')
                ->where('kd_detail_akun', $request->kd_detail_akun)
                ->first();

            // Add detail to the target date
            $cart[$cartKey]['details'][] = [
                'kd_detail_akun' => $request->kd_detail_akun,
                'nilai' => (float) $request->nilai,
                'temp_id' => Str::random(10),
                'nama_detail_akun' => $detailAkun->nama_detail_akun
            ];

            session()->put('detail_target_cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Detail berhasil ditambahkan',
                'detail' => end($cart[$cartKey]['details']) // Return the last added detail
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove detail from cart item
     */
    public function removeDetailFromCart(Request $request)
    {
        try {
            $request->validate([
                'kd_target' => 'required',
                'tanggal' => 'required|date',
                'temp_id' => 'required'
            ]);

            $cart = session()->get('detail_target_cart', []);
            $cartKey = $request->kd_target . '_' . $request->tanggal;

            if (!isset($cart[$cartKey])) {
                throw new \Exception('Target date not found in cart');
            }

            // Remove the detail
            $cart[$cartKey]['details'] = array_filter(
                $cart[$cartKey]['details'],
                function($detail) use ($request) {
                    return $detail['temp_id'] !== $request->temp_id;
                }
            );

            session()->put('detail_target_cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Detail removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store all details from cart
     */
    public function storeFromCart(Request $request)
    {
        try {
            $cart = session()->get('detail_target_cart', []);
            
            if (empty($cart)) {
                throw new \Exception('No items in cart');
            }

            DB::beginTransaction();

            $insertData = [];
            $now = now();

            foreach ($cart as $item) {
                foreach ($item['details'] as $detail) {
                    $data = [
                        'kd_target' => $item['kd_target'],
                        'tanggal' => $item['tanggal'],
                        'kd_detail_akun' => $detail['kd_detail_akun'],
                        'nilai' => $detail['nilai'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ];

                    if (config('database.default') === 'bigquery') {
                        $data['kd_target_detail'] = Str::uuid()->toString();
                    }

                    $insertData[] = $data;
                }
            }

            DB::table('detail_target')->insert($insertData);
            session()->forget('detail_target_cart');
            DB::commit();

            return redirect()->route('detailTarget.view')
                ->with('success', count($insertData) . ' detail target berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menyimpan detail target: ' . $e->getMessage());
        }
    }

    /**
     * Add detail to multiple cart items at once
     */
    public function addBulkDetails(Request $request)
    {
        try {
            $request->validate([
                'kd_target' => 'required|exists:target,kd_target',
                'tanggal' => 'required|array',
                'tanggal.*' => 'date',
                'kd_detail_akun' => 'required|exists:detail_akun,kd_detail_akun',
                'nilai' => 'required|numeric'
            ]);

            $cart = session()->get('detail_target_cart', []);
            $detailAkun = DB::table('detail_akun')
                ->where('kd_detail_akun', $request->kd_detail_akun)
                ->first();

            foreach ($request->tanggal as $tanggal) {
                $cartKey = $request->kd_target . '_' . $tanggal;
                
                if (isset($cart[$cartKey])) {
                    $cart[$cartKey]['details'][] = [
                        'kd_detail_akun' => $request->kd_detail_akun,
                        'nilai' => $request->nilai,
                        'temp_id' => Str::random(10),
                        'nama_detail_akun' => $detailAkun->nama_detail_akun // Add name for display
                    ];
                }
            }

            session()->put('detail_target_cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Detail berhasil ditambahkan ke multiple tanggal',
                'cart' => $cart
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // In your DetailTargetController

    /**
     * Add details to multiple dates at once
     */
    public function addMultiDateDetails(Request $request)
    {
        try {
            $request->validate([
                'kd_target' => 'required|exists:target,kd_target',
                'dates' => 'required|array',
                'dates.*' => 'date',
                'kd_detail_akun' => 'required|exists:detail_akun,kd_detail_akun',
                'nilai' => 'required|numeric|min:0'
            ]);

            $cart = session()->get('detail_target_cart', []);
            $detailAkun = DB::table('detail_akun')
                ->where('kd_detail_akun', $request->kd_detail_akun)
                ->first();

            foreach ($request->dates as $date) {
                $cartKey = $request->kd_target . '_' . $date;
                
                if (!isset($cart[$cartKey])) {
                    // Create new entry if date doesn't exist in cart
                    $target = DB::table('target')
                        ->leftJoin('akun', 'target.kd_akun', '=', 'akun.kd_akun')
                        ->leftJoin('entitas', 'target.kd_entitas', '=', 'entitas.kd_entitas')
                        ->where('kd_target', $request->kd_target)

                        ->first();
                    
                    $cart[$cartKey] = [
                        'kd_target' => $request->kd_target,
                        'tanggal' => $date,
                        'kd_akun' => $target->kd_akun,
                        'nama_akun' => $target->nama_akun,
                        'kd_entitas' => $target->kd_entitas,
                        'nama_entitas' => $target->nama_entitas,
                        'details' => []
                    ];
                }

                // Add the detail to this date
                $cart[$cartKey]['details'][] = [
                    'kd_detail_akun' => $request->kd_detail_akun,
                    'nilai' => $request->nilai,
                    'temp_id' => Str::random(10),
                    'nama_detail_akun' => $detailAkun->nama_detail_akun
                ];
            }

            session()->put('detail_target_cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Detail berhasil ditambahkan ke multiple tanggal',
                'cart' => $cart
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store bulk details directly to database
     */
    public function storeBulkDetails(Request $request)
    {
        try {
            $request->validate([
                'kd_target' => 'required|exists:target,kd_target',
                'kd_detail_akun' => 'required|exists:detail_akun,kd_detail_akun',
                'nilai' => 'required|numeric|min:0',
                'dates' => 'required|array',
                'dates.*' => 'date'
            ]);

            DB::beginTransaction();

            $insertData = [];
            $now = now();
            $nilai = (float) str_replace(['.', ','], '', $request->nilai);

            foreach ($request->dates as $date) {
                $data = [
                    'tanggal' => $date,
                    'kd_detail_akun' => $request->kd_detail_akun,
                    'nilai' => $nilai,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                if (config('database.default') === 'bigquery') {
                    $data['kd_target_detail'] = Str::uuid()->toString();
                }

                $insertData[] = $data;
            }

            DB::table('detail_target')->insert($insertData);
            DB::commit();

            return redirect()->route('detailTarget.view')
                ->with('success', count($insertData) . ' detail target berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menyimpan detail target: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function getDateDetails(Request $request)
    {
        try {
            $cart = session()->get('detail_target_cart', []);
            $cartKey = $request->kd_target . '_' . $request->date;
            $details = $cart[$cartKey]['details'] ?? [];

            // Get detail akun names
            $detailAkunIds = array_column($details, 'kd_detail_akun');
            $detailAkunNames = DB::table('detail_akun')
                ->whereIn('kd_detail_akun', $detailAkunIds)
                ->pluck('nama_detail_akun', 'kd_detail_akun')
                ->toArray();

            // Add names to details
            $detailsWithNames = array_map(function($detail) use ($detailAkunNames) {
                return [
                    'temp_id' => $detail['temp_id'],
                    'kd_detail_akun' => $detail['kd_detail_akun'],
                    'nama_detail_akun' => $detailAkunNames[$detail['kd_detail_akun']] ?? 'Unknown',
                    'nilai' => $detail['nilai']
                ];
            }, $details);

            return response()->json([
                'success' => true,
                'details' => $detailsWithNames
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }    

}
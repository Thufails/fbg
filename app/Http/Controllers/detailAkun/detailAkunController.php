<?php

namespace App\Http\Controllers\detailAkun;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class detailAkunController extends Controller
{
    public function create($kd_akun)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $akunList = DB::table('akun')->where('kd_akun', $kd_akun)->first();
            } else {
                $akunList = DB::table('akun')->where('kd_akun', $kd_akun)->first();
            }

            if (!$akunList) {
                return redirect()->back()->with('error', 'Akun tidak ditemukan');
            }

            $nama_akun = $akunList->nama_akun;

            return view('detailAkun.insert', compact('kd_akun', 'nama_akun'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat form: '.$e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kd_akun' => 'required',
                'nama_detail_akun' => 'required|min:3|max:25',
            ]);

            $data = [
                'kd_akun' => $validated['kd_akun'],
                'nama_detail_akun' => $validated['nama_detail_akun'],
                'keterangan' => $request->input('keterangan', null),
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (config('database.default') === 'bigquery') {
                $data['kd_detail_akun'] = Str::uuid()->toString();
                DB::table('detail_akun')->insert($data);
            } else {
                DB::table('detail_akun')->insert($data);
            }

            return redirect()->route('akun.detail', $validated['kd_akun'])
                ->with('success', 'Detail akun berhasil ditambahkan');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan detail akun: '.$e->getMessage())
                ->withInput();
        }
    }

    public function edit($kd_detail_akun)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $detailAkun = DB::table('detail_akun')
                    ->join('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                    ->select('detail_akun.*', 'akun.nama_akun')
                    ->where('detail_akun.kd_detail_akun', $kd_detail_akun)
                    ->first();
            } else {
                $detailAkun = DB::table('detail_akun')
                    ->join('akun', 'detail_akun.kd_akun', '=', 'akun.kd_akun')
                    ->select('detail_akun.*', 'akun.nama_akun')
                    ->where('detail_akun.kd_detail_akun', $kd_detail_akun)
                    ->first();
            }

            if (!$detailAkun) {
                return redirect()->back()->with('error', 'Detail akun tidak ditemukan');
            }

            // Convert dates to Carbon instances for BigQuery
            if (config('database.default') === 'bigquery') {
                $detailAkun->created_at = Carbon::parse($detailAkun->created_at);
                $detailAkun->updated_at = Carbon::parse($detailAkun->updated_at);
            }

            return view('detailAkun.edit', compact('detailAkun'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat form edit: '.$e->getMessage());
        }
    }

    public function update(Request $request, $kd_detail_akun)
    {
        try {
            $validated = $request->validate([
                'kd_detail_akun' => 'required|max:10',
                'nama_detail_akun' => 'required|min:3|max:25',
                'kd_akun' => 'required'
            ]);

            $updateData = [
                'kd_detail_akun' => $validated['kd_detail_akun'],
                'kd_akun' => $validated['kd_akun'],
                'nama_detail_akun' => $validated['nama_detail_akun'],
                'keterangan' => $request->input('keterangan', null),
                'updated_at' => now()
            ];

            if (config('database.default') === 'bigquery') {
                $affected = DB::table('detail_akun')
                    ->where('kd_detail_akun', $kd_detail_akun)
                    ->update($updateData);
                    
                if ($affected === 0) {
                    throw new \Exception('Tidak ada data yang diupdate');
                }
            } else {
                $affected = DB::table('detail_akun')
                    ->where('kd_detail_akun', $kd_detail_akun)
                    ->update($updateData);
            }

            return redirect()->route('akun.detail', $validated['kd_akun'])
                ->with('success', 'Detail akun berhasil diperbarui');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui detail akun: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy($kd_detail_akun)
    {
        try {
            if (config('database.default') === 'bigquery') {
                $detail = DB::table('detail_akun')->where('kd_detail_akun', $kd_detail_akun)->first();
                if (!$detail) {
                    throw new \Exception('Detail akun tidak ditemukan');
                }
                
                $kd_akun = $detail->kd_akun;
                DB::table('detail_akun')->where('kd_detail_akun', $kd_detail_akun)->delete();
            } else {
                $detail = DB::table('detail_akun')->where('kd_detail_akun', $kd_detail_akun)->first();
                if (!$detail) {
                    throw new \Exception('Detail akun tidak ditemukan');
                }
                
                $kd_akun = $detail->kd_akun;
                DB::table('detail_akun')->where('kd_detail_akun', $kd_detail_akun)->delete();
            }

            return redirect()->route('akun.detail', $kd_akun)
                ->with('success', 'Detail akun berhasil dihapus');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus detail akun: '.$e->getMessage());
        }
    }
}
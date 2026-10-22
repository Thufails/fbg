<?php

namespace App\Http\Controllers\companyProfile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class companyProfileController extends Controller
{
     /**
     * Display the edit form for company profile.
     */
    public function edit()
    {
        try {
            // Ambil data pertama atau buat baru jika belum ada
            $companyProfile = CompanyProfile::first();
            
            if (!$companyProfile) {
                $companyProfile = new CompanyProfile();
            }
            
            return view('profilPerusahaan.edit', compact('companyProfile'));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengambil data profile perusahaan: '.$e->getMessage());
        }
    }

    /**
     * Update the company profile in storage.
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_perusahaan' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'alamat' => 'nullable|string|max:500',
                'telelpon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Mulai transaction
            DB::beginTransaction();

            try {
                $companyProfile = CompanyProfile::first();

                if (!$companyProfile) {
                    $companyProfile = new CompanyProfile();
                }

                // Handle logo upload
                if ($request->hasFile('logo')) {
                    // Hapus logo lama jika ada
                    if ($companyProfile->logo) {
                        Storage::delete('public/' . $companyProfile->logo);
                    }
                    
                    $logoPath = $request->file('logo')->store('company-logos', 'public');
                    $validated['logo'] = $logoPath;
                }

                // Update atau create data
                if ($companyProfile->exists) {
                    $companyProfile->update($validated);
                    $message = 'Profile perusahaan berhasil diperbarui!';
                } else {
                    $companyProfile->create($validated);
                    $message = 'Profile perusahaan berhasil dibuat!';
                }

                // Commit transaction
                DB::commit();

                return redirect()->route('profilPerusahaan.edit')
                                 ->with('success', $message);
                                 
            } catch (\Exception $e) {
                // Rollback transaction jika ada error
                DB::rollBack();
                throw $e;
            }
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan profile perusahaan: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show company profile data in JSON format (optional API endpoint)
     */
    public function show()
    {
        try {
            $companyProfile = CompanyProfile::first();
            
            if (!$companyProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data profile perusahaan tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $companyProfile
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data profile perusahaan: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove company logo
     */
    public function removeLogo()
    {
        try {
            $companyProfile = CompanyProfile::first();

            if (!$companyProfile || !$companyProfile->logo) {
                throw new \Exception('Logo tidak ditemukan');
            }

            // Mulai transaction
            DB::beginTransaction();

            try {
                // Hapus file logo
                Storage::delete('public/' . $companyProfile->logo);
                
                // Update database
                $companyProfile->update(['logo' => null]);

                // Commit transaction
                DB::commit();

                return redirect()->route('profilPerusahaan.edit')
                                 ->with('success', 'Logo berhasil dihapus');
                                 
            } catch (\Exception $e) {
                // Rollback transaction jika ada error
                DB::rollBack();
                throw $e;
            }
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus logo: '.$e->getMessage());
        }
    }
}

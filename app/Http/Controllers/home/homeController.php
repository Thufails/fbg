<?php

namespace App\Http\Controllers\home;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;

class homeController extends Controller
{
    /**
     * Display home page with company profile.
     */
    public function index()
    {
        try {
            $companyProfile = CompanyProfile::first();
           // dd($companyProfile);
            // Jika tidak ada data company profile, buat object kosong
            if (!$companyProfile) {
                $companyProfile = (object) [
                    'nama_perusahaan' => null,
                    'deskripsi' => null,
                    'logo' => null,
                    'alamat' => null,
                    'telepon' => null,
                    'email' => null,
                    'website' => null
                ];
            }

           //dd($companyProfile);
            
            return view('home.view', compact('companyProfile'));
            
        } catch (\Exception $e) {
            // Fallback dengan object kosong jika ada error
            $companyProfile = (object) [
                'nama_perusahaan' => null,
                'deskripsi' => null,
                'logo' => null,
                'alamat' => null,
                'telepon' => null,
                'email' => null,
                'website' => null
            ];
           // dd($companyProfile);
            
            return view('home.view', compact('companyProfile'))
                ->with('error', 'Gagal memuat data profile perusahaan: '.$e->getMessage());
        }
    }

    /**
     * Display company profile data as JSON (for API)
     */
    public function getCompanyProfile()
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
}
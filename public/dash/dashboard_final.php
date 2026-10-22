<?php
require_once 'config.php';

$kd_laporan = isset($_GET['kd_laporan']) && !empty($_GET['kd_laporan']) ? $_GET['kd_laporan'] : null;

// Inisialisasi variabel tanggal
$tanggal_awal = null;
$tanggal_akhir = null;

// Jika ada kd_laporan, ambil tanggal dari database
if ($kd_laporan) {
    try {
        $sqlLaporan = "SELECT tanggal_awal, tanggal_akhir FROM laporan WHERE kd_laporan = ?";
        $stmtLaporan = $pdo->prepare($sqlLaporan);
        $stmtLaporan->execute([$kd_laporan]);
        $dataLaporan = $stmtLaporan->fetch(PDO::FETCH_ASSOC);
        
        if ($dataLaporan) {
            $tanggal_awal = $dataLaporan['tanggal_awal'];
            $tanggal_akhir = $dataLaporan['tanggal_akhir'];
        }
    } catch (PDOException $e) {
        // Tangani error jika perlu
        // echo "Error: " . $e->getMessage();
    }
}

// Jika tidak ada kd_laporan atau data tidak ditemukan, coba ambil dari URL parameter
if (empty($tanggal_awal) && isset($_GET['tanggal_awal'])) {
    $tanggal_awal = $_GET['tanggal_awal'];
}

if (empty($tanggal_akhir) && isset($_GET['tanggal_akhir'])) {
    $tanggal_akhir = $_GET['tanggal_akhir'];
}

// Format tanggal jika perlu
if ($tanggal_awal) {
    $tanggal_awal = date('Y-m-d', strtotime($tanggal_awal));
}

if ($tanggal_akhir) {
    $tanggal_akhir = date('Y-m-d', strtotime($tanggal_akhir));
}


// Form filter tanggal
echo '
<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="kd_laporan" value="'.htmlspecialchars($kd_laporan).'">
                
                <div class="col-md-4">
                    <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                    <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" 
                           value="'.htmlspecialchars($tanggal_awal).'">
                </div>
                
                <div class="col-md-4">
                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" 
                           value="'.htmlspecialchars($tanggal_akhir).'">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter Data</button>
                        <a href="?kd_laporan='.htmlspecialchars($kd_laporan).'" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>';

//////////////////////////////////////////////////////////////
// Inisialisasi variabel bulan data omzet
$bulanDataOmzet = [];

// Hanya proses grafik bulanan jika benar-benar diperlukan
// Jangan proses di sini, tapi nanti setelah data diambil dari database

// Buat array untuk bulan yang akan ditampilkan berdasarkan filter tanggal
$startMonth = !empty($tanggal_awal) ? date('Y-m', strtotime($tanggal_awal)) : null;
$endMonth = !empty($tanggal_akhir) ? date('Y-m', strtotime($tanggal_akhir)) : null;

// Jika tidak ada filter tanggal, gunakan bulan Januari sampai bulan ini
if (!$startMonth) {
    $startMonth = date('Y') . '-01';
}
if (!$endMonth) {
    $endMonth = date('Y-m');
}

$monthsToShow = [];
$start = new DateTime($startMonth . '-01');
$end = new DateTime($endMonth . '-01');
$end->modify('+1 month'); 

$interval = new DateInterval('P1M');
$period = new DatePeriod($start, $interval, $end);

foreach ($period as $dt) {
    $monthKey = $dt->format("Y-m");
    $monthsToShow[$monthKey] = $monthKey;
}

// Kosongkan variabel untuk grafik - akan diisi nanti
$labelsOmzet = [];
$targetOmzetData = [];
$realisasiOmzetData = [];

// Proses data untuk tabel dan grafik
$no_bulanan = 1; // Gunakan variabel no yang berbeda agar tidak bentrok
$totalTargetBulanan = 0;
$totalRealisasiBulanan = 0;
$totalGapBulanan = 0;

foreach ($monthsToShow as $monthKey):
    $monthParts = explode('-', $monthKey);
    if (count($monthParts) !== 2) continue;
    
    $tahun = $monthParts[0];
    $bulanAngka = (int)$monthParts[1];
    $monthName = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
    
    $target = isset($bulanDataOmzet[$monthKey]) ? $bulanDataOmzet[$monthKey]['target'] : 0;
    $realisasi = isset($bulanDataOmzet[$monthKey]) ? $bulanDataOmzet[$monthKey]['realisasi'] : 0;
    $gap = $realisasi - $target;

    $labelsOmzet[] = $monthName . ' ' . $tahun;
    $targetOmzetData[] = $target / 1000000;
    $realisasiOmzetData[] = $realisasi / 1000000;

    $totalTargetBulanan += $target;
    $totalRealisasiBulanan += $realisasi;
    $totalGapBulanan += $gap;
endforeach;
/////////////////////////////////////////////////////

// Fungsi untuk memformat angka dalam jutaan
function formatInJutaan($number) {
    return number_format($number / 1000000, 2, ',', '.');
}

// Fungsi untuk memformat angka penuh
function formatFullNumber($number) {
    return number_format($number, 0, ',', '.');
}

// Query untuk mengambil data dari tabel data_report dengan filter tanggal
$sql = "SELECT * FROM data_report WHERE 1=1";
$params = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $params[] = $tanggal_awal;
    $params[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sql .= " AND DATE(tanggal) >= ?";
    $params[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sql .= " AND DATE(tanggal) <= ?";
    $params[] = $tanggal_akhir;
}

$sql .= " ORDER BY tanggal, nama_entitas, urutan";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dataReport = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database: " . $e->getMessage());
}

if (empty($dataReport)) {
    $dateFilterInfo = "";
    if (!empty($tanggal_awal) || !empty($tanggal_akhir)) {
        $dateFilterInfo = " untuk periode " . ($tanggal_awal ?? '-') . " hingga " . ($tanggal_akhir ?? '-');
    }
    echo '<div class="alert alert-warning mt-3">Tidak ada data laporan untuk periode yang dipilih' . $dateFilterInfo . '</div>';
    $dataReport = [];
}


// Proses data untuk analisis
$processedData = [];

// Kelompokkan data berdasarkan entitas dan akun
$entitasData = [];
$tanggalData = [];
$akunData = [];
$akunUrutan = []; // Untuk menyimpan urutan per akun

// KHUSUS UNTUK DATA OMZET
$entitasDataOmzet = []; // Data khusus untuk Omzet
$tanggalDataOmzet = []; // Data omzet per tanggal
$bulanDataOmzet = []; // Data omzet per bulan
$mingguDataOmzet = []; // Data omzet per minggu

// KHUSUS UNTUK DATA HPP
$entitasDataHPP = []; // Data khusus untuk HPP
$tanggalDataHPP = []; // Data HPP per tanggal
$bulanDataHPP = []; // Data HPP per bulan

// KHUSUS UNTUK DATA BIAYA
$tanggalDataBiaya = []; // Data biaya per tanggal
$jenisBiayaData = []; // Data biaya per jenis akun
$entitasBiayaData = []; // Data biaya per entitas
$jenisBiayaEntitasData = []; // Data biaya per jenis per entitas

// Query untuk mengambil data biaya (grup_akun = 'BIAYA')
$sqlBiaya = "SELECT data_report.nama_akun, data_report.nilai_target, data_report.nilai_realisasi, data_report.tanggal,data_report.urutan,data_report.nama_entitas FROM data_report left join akun on (data_report.urutan=akun.urutan) WHERE akun.group_akun = 'BIAYA'";
$paramsBiaya = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiaya .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $paramsBiaya[] = $tanggal_awal;
    $paramsBiaya[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiaya .= " AND DATE(tanggal) >= ?";
    $paramsBiaya[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiaya .= " AND DATE(tanggal) <= ?";
    $paramsBiaya[] = $tanggal_akhir;
}

$sqlBiaya .= " ORDER BY tanggal, nama_akun";

try {
    $stmtBiaya = $pdo->prepare($sqlBiaya);
    $stmtBiaya->execute($paramsBiaya);
    $dataBiaya = $stmtBiaya->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya: " . $e->getMessage());
}

// Proses data biaya
foreach ($dataBiaya as $row) {
    $tanggal = $row['tanggal'];
    $jenisAkun = $row['nama_akun'];
    $entitas = $row['nama_entitas'];
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Data per tanggal
    if (!isset($tanggalDataBiaya[$tanggal])) {
        $tanggalDataBiaya[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    
    $tanggalDataBiaya[$tanggal]['target'] += $target;
    $tanggalDataBiaya[$tanggal]['realisasi'] += $realisasi;
    
    // Data per jenis akun biaya
    if (!isset($jenisBiayaData[$jenisAkun])) {
        $jenisBiayaData[$jenisAkun] = [
            'target' => 0,
            'realisasi' => 0,
            'urutan' => (int)$row['urutan']
        ];
    }
    
    $jenisBiayaData[$jenisAkun]['target'] += $target;
    $jenisBiayaData[$jenisAkun]['realisasi'] += $realisasi;
    
    // Data per entitas biaya
    if (!isset($entitasBiayaData[$entitas])) {
        $entitasBiayaData[$entitas] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    
    $entitasBiayaData[$entitas]['target'] += $target;
    $entitasBiayaData[$entitas]['realisasi'] += $realisasi;
    
    // Data per jenis biaya per entitas
    if (!isset($jenisBiayaEntitasData[$jenisAkun])) {
        $jenisBiayaEntitasData[$jenisAkun] = [];
    }
    
    if (!isset($jenisBiayaEntitasData[$jenisAkun][$entitas])) {
        $jenisBiayaEntitasData[$jenisAkun][$entitas] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    
    $jenisBiayaEntitasData[$jenisAkun][$entitas]['target'] += $target;
    $jenisBiayaEntitasData[$jenisAkun][$entitas]['realisasi'] += $realisasi;
}

// Urutkan jenis biaya berdasarkan urutan
uasort($jenisBiayaData, function($a, $b) {
    return $a['urutan'] - $b['urutan'];
});

// Proses data utama (untuk semua data report)
foreach ($dataReport as $row) {
    $entitas = $row['nama_entitas'];
    $tanggal = $row['tanggal'];
    $akun = $row['nama_akun'];
    $urutan = (int)$row['urutan'];
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Simpan urutan untuk setiap akun (ambil yang pertama ditemukan)
    if (!isset($akunUrutan[$akun])) {
        $akunUrutan[$akun] = $urutan;
    }
    
    // KHUSUS UNTUK DATA OMZET - hanya proses data dengan nama_akun = 'Omzet'
    if ($akun === 'OMZET') {
        // Data per entitas
        if (!isset($entitasDataOmzet[$entitas])) {
            $entitasDataOmzet[$entitas] = [
                'total_target' => 0,
                'total_realisasi' => 0,
                'detail' => []
            ];
        }
        
        $entitasDataOmzet[$entitas]['total_target'] += $target;
        $entitasDataOmzet[$entitas]['total_realisasi'] += $realisasi;
        $entitasDataOmzet[$entitas]['detail'][] = [
            'tanggal' => $tanggal,
            'target' => $target,
            'realisasi' => $realisasi
        ];
        
        // Data per tanggal
        if (!isset($tanggalDataOmzet[$tanggal])) {
            $tanggalDataOmzet[$tanggal] = [
                'target' => 0,
                'realisasi' => 0
            ];
        }
        
        $tanggalDataOmzet[$tanggal]['target'] += $target;
        $tanggalDataOmzet[$tanggal]['realisasi'] += $realisasi;
        
        // Data per bulan
        $bulan = date('Y-m', strtotime($tanggal));
        if (!isset($bulanDataOmzet[$bulan])) {
            $bulanDataOmzet[$bulan] = [
                'target' => 0,
                'realisasi' => 0
            ];
        }
        
        $bulanDataOmzet[$bulan]['target'] += $target;
        $bulanDataOmzet[$bulan]['realisasi'] += $realisasi;
        
        // Data per minggu (tahun + minggu ke-)
        $tahun = date('Y', strtotime($tanggal));
        $minggu = date('W', strtotime($tanggal));
        $keyMinggu = $tahun . '-W' . str_pad($minggu, 2, '0', STR_PAD_LEFT);
        
        if (!isset($mingguDataOmzet[$keyMinggu])) {
            $mingguDataOmzet[$keyMinggu] = [
                'target' => 0,
                'realisasi' => 0,
                'label' => 'Minggu ' . $minggu . ' ' . $tahun
            ];
        }
        
        $mingguDataOmzet[$keyMinggu]['target'] += $target;
        $mingguDataOmzet[$keyMinggu]['realisasi'] += $realisasi;
    }
    
    // KHUSUS UNTUK DATA HPP - hanya proses data dengan nama_akun = 'HPP'
    if ($akun === 'HPP') {
        // Data per entitas
        if (!isset($entitasDataHPP[$entitas])) {
            $entitasDataHPP[$entitas] = [
                'total_target' => 0,
                'total_realisasi' => 0,
                'detail' => []
            ];
        }
        
        $entitasDataHPP[$entitas]['total_target'] += $target;
        $entitasDataHPP[$entitas]['total_realisasi'] += $realisasi;
        $entitasDataHPP[$entitas]['detail'][] = [
            'tanggal' => $tanggal,
            'target' => $target,
            'realisasi' => $realisasi
        ];
        
        // Data per tanggal
        if (!isset($tanggalDataHPP[$tanggal])) {
            $tanggalDataHPP[$tanggal] = [
                'target' => 0,
                'realisasi' => 0
            ];
        }
        
        $tanggalDataHPP[$tanggal]['target'] += $target;
        $tanggalDataHPP[$tanggal]['realisasi'] += $realisasi;
        
        // Data per bulan
        $bulan = date('Y-m', strtotime($tanggal));
        if (!isset($bulanDataHPP[$bulan])) {
            $bulanDataHPP[$bulan] = [
                'target' => 0,
                'realisasi' => 0
            ];
        }
        
        $bulanDataHPP[$bulan]['target'] += $target;
        $bulanDataHPP[$bulan]['realisasi'] += $realisasi;
    }
    
    // Kelompokkan berdasarkan entitas (untuk semua data)
    if (!isset($entitasData[$entitas])) {
        $entitasData[$entitas] = [];
    }
    
    if (!isset($entitasData[$entitas][$akun])) {
        $entitasData[$entitas][$akun] = [
            'total_target' => 0,
            'total_realisasi' => 0,
            'detail' => []
        ];
    }
    
    $entitasData[$entitas][$akun]['total_target'] += $target;
    $entitasData[$entitas][$akun]['total_realisasi'] += $realisasi;
    $entitasData[$entitas][$akun]['detail'][] = [
        'tanggal' => $tanggal,
        'target' => $target,
        'realisasi' => $realisasi
    ];
    
    // Kelompokkan berdasarkan tanggal
    if (!isset($tanggalData[$tanggal])) {
        $tanggalData[$tanggal] = [];
    }
    
    if (!isset($tanggalData[$tanggal][$entitas])) {
        $tanggalData[$tanggal][$entitas] = [];
    }
    
    $tanggalData[$tanggal][$entitas][$akun] = [
        'target' => $target,
        'realisasi' => $realisasi
    ];
    
    // Kelompokkan berdasarkan akun
    if (!isset($akunData[$akun])) {
        $akunData[$akun] = [
            'total_target' => 0,
            'total_realisasi' => 0,
            'entitas' => [],
            'urutan' => $urutan
        ];
    }
    
    $akunData[$akun]['total_target'] += $target;
    $akunData[$akun]['total_realisasi'] += $realisasi;
    
    if (!isset($akunData[$akun]['entitas'][$entitas])) {
        $akunData[$akun]['entitas'][$entitas] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    
    $akunData[$akun]['entitas'][$entitas]['target'] += $target;
    $akunData[$akun]['entitas'][$entitas]['realisasi'] += $realisasi;
}

// Urutkan data tanggal
ksort($tanggalDataOmzet);
ksort($tanggalDataHPP);
ksort($tanggalDataBiaya);

// Urutkan data bulan
ksort($bulanDataOmzet);
ksort($bulanDataHPP);

// Urutkan data minggu
ksort($mingguDataOmzet);

// Urutkan akunData berdasarkan urutan ascending
uasort($akunData, function($a, $b) {
    return $a['urutan'] - $b['urutan'];
});

// Hitung statistik untuk semua data
$totalTargetAll = 0;
$totalRealisasiAll = 0;
$totalEntitas = count($entitasData);
$totalHari = count($tanggalData);

foreach ($akunData as $akun) {
    $totalTargetAll += $akun['total_target'];
    $totalRealisasiAll += $akun['total_realisasi'];
}

$totalGapAll = $totalRealisasiAll - $totalTargetAll;
$totalPercentageAll = $totalTargetAll > 0 ? ($totalRealisasiAll / $totalTargetAll) * 100 : 0;

// Hitung statistik khusus untuk Omzet
$totalTargetOmzet = 0;
$totalRealisasiOmzet = 0;

foreach ($entitasDataOmzet as $entitasDataItem) {
    $totalTargetOmzet += $entitasDataItem['total_target'];
    $totalRealisasiOmzet += $entitasDataItem['total_realisasi'];
}

$totalGapOmzet = $totalRealisasiOmzet - $totalTargetOmzet;
$totalPercentageOmzet = $totalTargetOmzet > 0 ? ($totalRealisasiOmzet / $totalTargetOmzet) * 100 : 0;

// Hitung statistik khusus untuk HPP
$totalTargetHPP = 0;
$totalRealisasiHPP = 0;

foreach ($entitasDataHPP as $entitasDataItem) {
    $totalTargetHPP += $entitasDataItem['total_target'];
    $totalRealisasiHPP += $entitasDataItem['total_realisasi'];
}

$totalGapHPP = $totalRealisasiHPP - $totalTargetHPP;
$totalPercentageHPP = $totalTargetHPP > 0 ? ($totalRealisasiHPP / $totalTargetHPP) * 100 : 0;

// Hitung statistik khusus untuk Biaya
$totalTargetBiaya = 0;
$totalRealisasiBiaya = 0;

foreach ($tanggalDataBiaya as $data) {
    $totalTargetBiaya += $data['target'];
    $totalRealisasiBiaya += $data['realisasi'];
}

$totalGapBiaya = $totalRealisasiBiaya - $totalTargetBiaya;
$totalPercentageBiaya = $totalTargetBiaya > 0 ? ($totalRealisasiBiaya / $totalTargetBiaya) * 100 : 0;

// Hitung margin (Omzet - HPP - Biaya)
$totalMargin = $totalRealisasiOmzet - $totalRealisasiHPP - $totalRealisasiBiaya;
$totalTargetMargin = $totalTargetOmzet - $totalTargetHPP - $totalTargetBiaya;
$marginPercentage = $totalRealisasiOmzet > 0 ? ($totalMargin / $totalRealisasiOmzet) * 100 : 0;

// Hitung % Biaya terhadap Omzet
$biayaPercentage = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiaya / $totalRealisasiOmzet) * 100 : 0;

// ==================== DATA PENJUALAN PRODUK ====================
// Query untuk mengambil data penjualan produk
$sqlProduk = "SELECT 
                p.kd_produk,
                pr.nama_produk,
                SUM(p.jumlah) as total_jumlah,
                SUM(p.harga * p.jumlah) as total_nilai,
                COUNT(*) as total_transaksi
            FROM penjualan p
            LEFT JOIN produk pr ON p.kd_produk = pr.kd_produk
            WHERE 1=1";

$paramsProduk = [];

// Tambahkan filter tanggal jika ada (sama seperti filter utama)
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlProduk .= " AND DATE(p.tanggal) BETWEEN ? AND ?";
    $paramsProduk[] = $tanggal_awal;
    $paramsProduk[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlProduk .= " AND DATE(p.tanggal) >= ?";
    $paramsProduk[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlProduk .= " AND DATE(p.tanggal) <= ?";
    $paramsProduk[] = $tanggal_akhir;
}

$sqlProduk .= " GROUP BY p.kd_produk, pr.nama_produk 
                ORDER BY total_nilai DESC 
                LIMIT 15"; // Ambil 15 produk teratas

try {
    $stmtProduk = $pdo->prepare($sqlProduk);
    $stmtProduk->execute($paramsProduk);
    $dataPenjualan = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database produk: " . $e->getMessage());
}

// Proses data produk untuk chart
$produkLabels = [];
$produkJumlah = [];
$produkNilai = [];
$produkDetails = [];

foreach ($dataPenjualan as $row) {
    $produkLabels[] = $row['nama_produk'] . ' (' . $row['kd_produk'] . ')';
    $produkJumlah[] = (int)$row['total_jumlah'];
    $produkNilai[] = (float)$row['total_nilai'];
    
    $produkDetails[$row['kd_produk']] = [
        'nama' => $row['nama_produk'],
        'jumlah' => (int)$row['total_jumlah'],
        'nilai' => (float)$row['total_nilai'],
        'transaksi' => (int)$row['total_transaksi']
    ];
}

// Hitung total untuk produk
$totalProdukJumlah = array_sum($produkJumlah);
$totalProdukNilai = array_sum($produkNilai);
$totalJenisProduk = count($dataPenjualan);

////////////////////////////////////////////

// Query khusus untuk data gaji/HR
        $sqlGaji = "SELECT 
                        dr.nama_entitas,
                        dr.nama_akun,
                        dr.nilai_target,
                        dr.nilai_realisasi,
                        dr.tanggal,
                        dr.urutan,
                        a.group_akun
                    FROM data_report dr
                    LEFT JOIN akun a ON dr.urutan = a.urutan
                    WHERE (a.group_akun = 'BIAYA' AND 
                           (LOWER(dr.nama_akun) LIKE '%gaji%' OR 
                            LOWER(dr.nama_akun) LIKE '%salary%' OR 
                            LOWER(dr.nama_akun) LIKE '%hr%' OR 
                            LOWER(dr.nama_akun) LIKE '%human resource%' OR 
                            LOWER(dr.nama_akun) LIKE '%tunjangan%' OR 
                            LOWER(dr.nama_akun) LIKE '%bonus%' OR 
                            LOWER(dr.nama_akun) LIKE '%insentif%' OR 
                            LOWER(dr.nama_akun) LIKE '%upah%' OR 
                            LOWER(dr.nama_akun) LIKE '%payroll%'))
                           OR dr.nama_akun = 'GAJI' 
                           OR dr.nama_akun = 'GAJI KARYAWAN' 
                           OR dr.nama_akun = 'BEBAN GAJI'";
        
        $paramsGaji = [];
        
        // Tambahkan filter tanggal jika ada
        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $sqlGaji .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
            $paramsGaji[] = $tanggal_awal;
            $paramsGaji[] = $tanggal_akhir;
        } elseif (!empty($tanggal_awal)) {
            $sqlGaji .= " AND DATE(dr.tanggal) >= ?";
            $paramsGaji[] = $tanggal_awal;
        } elseif (!empty($tanggal_akhir)) {
            $sqlGaji .= " AND DATE(dr.tanggal) <= ?";
            $paramsGaji[] = $tanggal_akhir;
        }
        
        $sqlGaji .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";
        
        try {
            $stmtGaji = $pdo->prepare($sqlGaji);
            $stmtGaji->execute($paramsGaji);
            $dataGaji = $stmtGaji->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error accessing database gaji: " . $e->getMessage());
        }
        
        // Proses data gaji/HR
        $gajiData = [];
        $totalTargetGaji = 0;
        $totalRealisasiGaji = 0;
        $gajiPerEntitas = [];
        $gajiPerJenis = [];
        $gajiPerTanggal = [];
        
        foreach ($dataGaji as $row) {
            $entitas = $row['nama_entitas'];
            $jenis = $row['nama_akun'];
            $tanggal = $row['tanggal'];
            $target = (float)$row['nilai_target'];
            $realisasi = (float)$row['nilai_realisasi'];
            
            // Total keseluruhan
            $totalTargetGaji += $target;
            $totalRealisasiGaji += $realisasi;
            
            // Per entitas
            if (!isset($gajiPerEntitas[$entitas])) {
                $gajiPerEntitas[$entitas] = [
                    'target' => 0,
                    'realisasi' => 0,
                    'gap' => 0,
                    'detail' => []
                ];
            }
            $gajiPerEntitas[$entitas]['target'] += $target;
            $gajiPerEntitas[$entitas]['realisasi'] += $realisasi;
            $gajiPerEntitas[$entitas]['gap'] = $gajiPerEntitas[$entitas]['realisasi'] - $gajiPerEntitas[$entitas]['target'];
            
            // Per jenis
            if (!isset($gajiPerJenis[$jenis])) {
                $gajiPerJenis[$jenis] = [
                    'target' => 0,
                    'realisasi' => 0,
                    'gap' => 0
                ];
            }
            $gajiPerJenis[$jenis]['target'] += $target;
            $gajiPerJenis[$jenis]['realisasi'] += $realisasi;
            $gajiPerJenis[$jenis]['gap'] = $gajiPerJenis[$jenis]['realisasi'] - $gajiPerJenis[$jenis]['target'];
            
            // Per tanggal
            if (!isset($gajiPerTanggal[$tanggal])) {
                $gajiPerTanggal[$tanggal] = [
                    'target' => 0,
                    'realisasi' => 0
                ];
            }
            $gajiPerTanggal[$tanggal]['target'] += $target;
            $gajiPerTanggal[$tanggal]['realisasi'] += $realisasi;
        }
        
        // Hitung statistik
        $totalGapGaji = $totalRealisasiGaji - $totalTargetGaji;
        $percentageGaji = $totalTargetGaji > 0 ? ($totalRealisasiGaji / $totalTargetGaji) * 100 : 0;
        
        // Hitung persentase terhadap total biaya
        $percentageOfTotalBiaya = $totalRealisasiBiaya > 0 ? ($totalRealisasiGaji / $totalRealisasiBiaya) * 100 : 0;
        
        // Urutkan data
        arsort($gajiPerEntitas); // Urutkan berdasarkan realisasi tertinggi
        ksort($gajiPerTanggal); // Urutkan tanggal

///////////////////////////////
// ==================== DATA GAJI PER BULAN ====================
$gajiPerBulan = [];
$bulanGajiLabels = [];

foreach ($dataGaji as $row) {
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    if (!isset($gajiPerBulan[$bulan])) {
        $gajiPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
        $bulanGajiLabels[] = $bulan;
    }
    
    $gajiPerBulan[$bulan]['target'] += $target;
    $gajiPerBulan[$bulan]['realisasi'] += $realisasi;
    $gajiPerBulan[$bulan]['gap'] = $gajiPerBulan[$bulan]['realisasi'] - $gajiPerBulan[$bulan]['target'];
}

// Urutkan berdasarkan bulan
ksort($gajiPerBulan);

// Hitung statistik per bulan
$totalTargetGajiBulanan = 0;
$totalRealisasiGajiBulanan = 0;
$totalGapGajiBulanan = 0;
$jumlahBulan = count($gajiPerBulan);

foreach ($gajiPerBulan as $bulanData) {
    $totalTargetGajiBulanan += $bulanData['target'];
    $totalRealisasiGajiBulanan += $bulanData['realisasi'];
    $totalGapGajiBulanan += $bulanData['gap'];
}

$rataTargetGajiBulanan = $jumlahBulan > 0 ? $totalTargetGajiBulanan / $jumlahBulan : 0;
$rataRealisasiGajiBulanan = $jumlahBulan > 0 ? $totalRealisasiGajiBulanan / $jumlahBulan : 0;
$rataGapGajiBulanan = $jumlahBulan > 0 ? $totalGapGajiBulanan / $jumlahBulan : 0;
$rataPersentaseGajiBulanan = $totalTargetGajiBulanan > 0 ? ($totalRealisasiGajiBulanan / $totalTargetGajiBulanan) * 100 : 0;


// Query khusus untuk data biaya operasional (BUKAN gaji/HR)
$sqlBiayaOps = "SELECT 
                    dr.nama_entitas,
                    dr.nama_akun,
                    dr.nilai_target,
                    dr.nilai_realisasi,
                    dr.tanggal,
                    dr.urutan,
                    a.group_akun
                FROM data_report dr
                LEFT JOIN akun a ON dr.urutan = a.urutan
                WHERE a.group_akun = 'BIAYA' 
                    AND NOT (
                        LOWER(dr.nama_akun) LIKE '%operasional%' OR 
                        LOWER(dr.nama_akun) LIKE '%opration%' OR
                        dr.nama_akun = 'OPERASIONAL' OR 
                        dr.nama_akun = 'BIAYA OPERASIONAL' OR 
                        dr.nama_akun = 'BEBAN OPERASIONAL'
                    )";

$paramsBiayaOps = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaOps .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaOps[] = $tanggal_awal;
    $paramsBiayaOps[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaOps .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaOps[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaOps .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaOps[] = $tanggal_akhir;
}

$sqlBiayaOps .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtBiayaOps = $pdo->prepare($sqlBiayaOps);
    $stmtBiayaOps->execute($paramsBiayaOps);
    $dataBiayaOps = $stmtBiayaOps->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya operasional: " . $e->getMessage());
}

// Proses data biaya operasional
$biayaOpsData = [];
$totalTargetBiayaOps = 0;
$totalRealisasiBiayaOps = 0;
$biayaOpsPerEntitas = [];
$biayaOpsPerJenis = [];
$biayaOpsPerTanggal = [];
$biayaOpsPerBulan = [];

foreach ($dataBiayaOps as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetBiayaOps += $target;
    $totalRealisasiBiayaOps += $realisasi;
    
    // Per entitas
    if (!isset($biayaOpsPerEntitas[$entitas])) {
        $biayaOpsPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $biayaOpsPerEntitas[$entitas]['target'] += $target;
    $biayaOpsPerEntitas[$entitas]['realisasi'] += $realisasi;
    $biayaOpsPerEntitas[$entitas]['gap'] = $biayaOpsPerEntitas[$entitas]['realisasi'] - $biayaOpsPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($biayaOpsPerJenis[$jenis])) {
        $biayaOpsPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaOpsPerJenis[$jenis]['target'] += $target;
    $biayaOpsPerJenis[$jenis]['realisasi'] += $realisasi;
    $biayaOpsPerJenis[$jenis]['gap'] = $biayaOpsPerJenis[$jenis]['realisasi'] - $biayaOpsPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($biayaOpsPerTanggal[$tanggal])) {
        $biayaOpsPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $biayaOpsPerTanggal[$tanggal]['target'] += $target;
    $biayaOpsPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($biayaOpsPerBulan[$bulan])) {
        $biayaOpsPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaOpsPerBulan[$bulan]['target'] += $target;
    $biayaOpsPerBulan[$bulan]['realisasi'] += $realisasi;
    $biayaOpsPerBulan[$bulan]['gap'] = $biayaOpsPerBulan[$bulan]['realisasi'] - $biayaOpsPerBulan[$bulan]['target'];
}

// Hitung statistik
$totalGapBiayaOps = $totalRealisasiBiayaOps - $totalTargetBiayaOps;
$percentageBiayaOps = $totalTargetBiayaOps > 0 ? ($totalRealisasiBiayaOps / $totalTargetBiayaOps) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiayaOps = $totalRealisasiBiaya > 0 ? ($totalRealisasiBiayaOps / $totalRealisasiBiaya) * 100 : 0;

// Hitung persentase terhadap omzet
$percentageOfOmzetBiayaOps = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiayaOps / $totalRealisasiOmzet) * 100 : 0;

// Urutkan data
arsort($biayaOpsPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($biayaOpsPerTanggal); // Urutkan tanggal
ksort($biayaOpsPerBulan); // Urutkan bulan

// Hitung statistik per bulan
$totalTargetBiayaOpsBulanan = 0;
$totalRealisasiBiayaOpsBulanan = 0;
$totalGapBiayaOpsBulanan = 0;
$jumlahBulanOps = count($biayaOpsPerBulan);

foreach ($biayaOpsPerBulan as $bulanData) {
    $totalTargetBiayaOpsBulanan += $bulanData['target'];
    $totalRealisasiBiayaOpsBulanan += $bulanData['realisasi'];
    $totalGapBiayaOpsBulanan += $bulanData['gap'];
}

$rataTargetBiayaOpsBulanan = $jumlahBulanOps > 0 ? $totalTargetBiayaOpsBulanan / $jumlahBulanOps : 0;
$rataRealisasiBiayaOpsBulanan = $jumlahBulanOps > 0 ? $totalRealisasiBiayaOpsBulanan / $jumlahBulanOps : 0;
$rataGapBiayaOpsBulanan = $jumlahBulanOps > 0 ? $totalGapBiayaOpsBulanan / $jumlahBulanOps : 0;
$rataPersentaseBiayaOpsBulanan = $totalTargetBiayaOpsBulanan > 0 ? ($totalRealisasiBiayaOpsBulanan / $totalTargetBiayaOpsBulanan) * 100 : 0;


// Query khusus untuk data biaya pemasaran
$sqlBiayaMarketing = "SELECT 
                        dr.nama_entitas,
                        dr.nama_akun,
                        dr.nilai_target,
                        dr.nilai_realisasi,
                        dr.tanggal,
                        dr.urutan,
                        a.group_akun
                    FROM data_report dr
                    LEFT JOIN akun a ON dr.urutan = a.urutan
                    WHERE a.group_akun = 'BIAYA' 
                        AND (
                            LOWER(dr.nama_akun) LIKE '%pemasaran%' OR 
                            LOWER(dr.nama_akun) LIKE '%marketing%' OR 
                            LOWER(dr.nama_akun) LIKE '%iklan%' OR 
                            LOWER(dr.nama_akun) LIKE '%promosi%' OR 
                            LOWER(dr.nama_akun) LIKE '%advertisement%' OR 
                            LOWER(dr.nama_akun) LIKE '%advertise%' OR 
                            LOWER(dr.nama_akun) LIKE '%sponsor%' OR 
                            LOWER(dr.nama_akun) LIKE '%media%' OR 
                            LOWER(dr.nama_akun) LIKE '%sosmed%' OR 
                            LOWER(dr.nama_akun) LIKE '%social media%' OR 
                            LOWER(dr.nama_akun) LIKE '%digital marketing%' OR 
                            LOWER(dr.nama_akun) LIKE '%branding%' OR 
                            LOWER(dr.nama_akun) LIKE '%event%' OR 
                            LOWER(dr.nama_akun) LIKE '%exhibition%' OR 
                            LOWER(dr.nama_akun) LIKE '%campaig%' OR 
                            dr.nama_akun = 'BIAYA PEMASARAN' OR 
                            dr.nama_akun = 'BEBAN PEMASARAN' OR 
                            dr.nama_akun = 'BIAYA MARKETING' OR
                            dr.nama_akun = 'BEBAN MARKETING' OR  
                            dr.nama_akun = 'BIAYA PROMOSI' OR 
                            dr.nama_akun = 'BEBAN PROMOSI' OR
                            dr.nama_akun = 'BEBAN IKLAN' OR
                            dr.nama_akun = 'BIAYA IKLAN'
                        )";

$paramsBiayaMarketing = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaMarketing .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaMarketing[] = $tanggal_awal;
    $paramsBiayaMarketing[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaMarketing .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaMarketing[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaMarketing .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaMarketing[] = $tanggal_akhir;
}

$sqlBiayaMarketing .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtBiayaMarketing = $pdo->prepare($sqlBiayaMarketing);
    $stmtBiayaMarketing->execute($paramsBiayaMarketing);
    $dataBiayaMarketing = $stmtBiayaMarketing->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya pemasaran: " . $e->getMessage());
}

// Proses data biaya pemasaran
$biayaMarketingData = [];
$totalTargetBiayaMarketing = 0;
$totalRealisasiBiayaMarketing = 0;
$biayaMarketingPerEntitas = [];
$biayaMarketingPerJenis = [];
$biayaMarketingPerTanggal = [];
$biayaMarketingPerBulan = [];

foreach ($dataBiayaMarketing as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetBiayaMarketing += $target;
    $totalRealisasiBiayaMarketing += $realisasi;
    
    // Per entitas
    if (!isset($biayaMarketingPerEntitas[$entitas])) {
        $biayaMarketingPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $biayaMarketingPerEntitas[$entitas]['target'] += $target;
    $biayaMarketingPerEntitas[$entitas]['realisasi'] += $realisasi;
    $biayaMarketingPerEntitas[$entitas]['gap'] = $biayaMarketingPerEntitas[$entitas]['realisasi'] - $biayaMarketingPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($biayaMarketingPerJenis[$jenis])) {
        $biayaMarketingPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaMarketingPerJenis[$jenis]['target'] += $target;
    $biayaMarketingPerJenis[$jenis]['realisasi'] += $realisasi;
    $biayaMarketingPerJenis[$jenis]['gap'] = $biayaMarketingPerJenis[$jenis]['realisasi'] - $biayaMarketingPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($biayaMarketingPerTanggal[$tanggal])) {
        $biayaMarketingPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $biayaMarketingPerTanggal[$tanggal]['target'] += $target;
    $biayaMarketingPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($biayaMarketingPerBulan[$bulan])) {
        $biayaMarketingPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaMarketingPerBulan[$bulan]['target'] += $target;
    $biayaMarketingPerBulan[$bulan]['realisasi'] += $realisasi;
    $biayaMarketingPerBulan[$bulan]['gap'] = $biayaMarketingPerBulan[$bulan]['realisasi'] - $biayaMarketingPerBulan[$bulan]['target'];
}

// Hitung statistik
$totalGapBiayaMarketing = $totalRealisasiBiayaMarketing - $totalTargetBiayaMarketing;
$percentageBiayaMarketing = $totalTargetBiayaMarketing > 0 ? ($totalRealisasiBiayaMarketing / $totalTargetBiayaMarketing) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiayaMarketing = $totalRealisasiBiaya > 0 ? ($totalRealisasiBiayaMarketing / $totalRealisasiBiaya) * 100 : 0;

// Hitung persentase terhadap omzet
$percentageOfOmzetBiayaMarketing = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiayaMarketing / $totalRealisasiOmzet) * 100 : 0;

// Hitung ROAS (Return on Ad Spend) jika ada data omzet
$roasMarketing = $totalRealisasiBiayaMarketing > 0 ? ($totalRealisasiOmzet / $totalRealisasiBiayaMarketing) : 0;

// Urutkan data
arsort($biayaMarketingPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($biayaMarketingPerTanggal); // Urutkan tanggal
ksort($biayaMarketingPerBulan); // Urutkan bulan

// Hitung statistik per bulan
$totalTargetBiayaMarketingBulanan = 0;
$totalRealisasiBiayaMarketingBulanan = 0;
$totalGapBiayaMarketingBulanan = 0;
$jumlahBulanMarketing = count($biayaMarketingPerBulan);

foreach ($biayaMarketingPerBulan as $bulanData) {
    $totalTargetBiayaMarketingBulanan += $bulanData['target'];
    $totalRealisasiBiayaMarketingBulanan += $bulanData['realisasi'];
    $totalGapBiayaMarketingBulanan += $bulanData['gap'];
}

$rataTargetBiayaMarketingBulanan = $jumlahBulanMarketing > 0 ? $totalTargetBiayaMarketingBulanan / $jumlahBulanMarketing : 0;
$rataRealisasiBiayaMarketingBulanan = $jumlahBulanMarketing > 0 ? $totalRealisasiBiayaMarketingBulanan / $jumlahBulanMarketing : 0;
$rataGapBiayaMarketingBulanan = $jumlahBulanMarketing > 0 ? $totalGapBiayaMarketingBulanan / $jumlahBulanMarketing : 0;
$rataPersentaseBiayaMarketingBulanan = $totalTargetBiayaMarketingBulanan > 0 ? ($totalRealisasiBiayaMarketingBulanan / $totalTargetBiayaMarketingBulanan) * 100 : 0;

// Hitung efisiensi pemasaran per entitas (omzet per biaya pemasaran)
$efisiensiMarketingPerEntitas = [];
foreach ($biayaMarketingPerEntitas as $entitas => $data) {
    $omzetEntitas = isset($entitasDataOmzet[$entitas]) ? $entitasDataOmzet[$entitas]['total_realisasi'] : 0;
    $biayaMarketingEntitas = $data['realisasi'];
    $efisiensi = $biayaMarketingEntitas > 0 ? ($omzetEntitas / $biayaMarketingEntitas) : 0;
    $efisiensiMarketingPerEntitas[$entitas] = $efisiensi;
}

// Query untuk biaya lain-lain (semua biaya yang belum termasuk dalam kategori sebelumnya)
$sqlBiayaLain = "SELECT 
                    dr.nama_entitas,
                    dr.nama_akun,
                    dr.nilai_target,
                    dr.nilai_realisasi,
                    dr.tanggal,
                    dr.urutan,
                    a.group_akun
                FROM data_report dr
                LEFT JOIN akun a ON dr.urutan = a.urutan
                WHERE a.group_akun = 'BIAYA' 
                    AND (
                        -- Bukan biaya pemasaran
                        LOWER(dr.nama_akun) LIKE '%lain-lain%' OR 
                        LOWER(dr.nama_akun) LIKE '%biaya lain-lain%' OR 
                        LOWER(dr.nama_akun) LIKE '%biaya lain%' OR 
                        LOWER(dr.nama_akun) LIKE '%beban lain-lain%' OR 
                        LOWER(dr.nama_akun) LIKE '%beban lain%'
                    )";

$paramsBiayaLain = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaLain .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaLain[] = $tanggal_awal;
    $paramsBiayaLain[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaLain .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaLain[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaLain .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaLain[] = $tanggal_akhir;
}

$sqlBiayaLain .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtBiayaLain = $pdo->prepare($sqlBiayaLain);
    $stmtBiayaLain->execute($paramsBiayaLain);
    $dataBiayaLain = $stmtBiayaLain->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya lain-lain: " . $e->getMessage());
}

// Proses data biaya lain-lain
$biayaLainData = [];
$totalTargetBiayaLain = 0;
$totalRealisasiBiayaLain = 0;
$biayaLainPerEntitas = [];
$biayaLainPerJenis = [];
$biayaLainPerTanggal = [];
$biayaLainPerBulan = [];

foreach ($dataBiayaLain as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetBiayaLain += $target;
    $totalRealisasiBiayaLain += $realisasi;
    
    // Per entitas
    if (!isset($biayaLainPerEntitas[$entitas])) {
        $biayaLainPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $biayaLainPerEntitas[$entitas]['target'] += $target;
    $biayaLainPerEntitas[$entitas]['realisasi'] += $realisasi;
    $biayaLainPerEntitas[$entitas]['gap'] = $biayaLainPerEntitas[$entitas]['realisasi'] - $biayaLainPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($biayaLainPerJenis[$jenis])) {
        $biayaLainPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaLainPerJenis[$jenis]['target'] += $target;
    $biayaLainPerJenis[$jenis]['realisasi'] += $realisasi;
    $biayaLainPerJenis[$jenis]['gap'] = $biayaLainPerJenis[$jenis]['realisasi'] - $biayaLainPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($biayaLainPerTanggal[$tanggal])) {
        $biayaLainPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $biayaLainPerTanggal[$tanggal]['target'] += $target;
    $biayaLainPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($biayaLainPerBulan[$bulan])) {
        $biayaLainPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaLainPerBulan[$bulan]['target'] += $target;
    $biayaLainPerBulan[$bulan]['realisasi'] += $realisasi;
    $biayaLainPerBulan[$bulan]['gap'] = $biayaLainPerBulan[$bulan]['realisasi'] - $biayaLainPerBulan[$bulan]['target'];
}

// Hitung statistik
$totalGapBiayaLain = $totalRealisasiBiayaLain - $totalTargetBiayaLain;
$percentageBiayaLain = $totalTargetBiayaLain > 0 ? ($totalRealisasiBiayaLain / $totalTargetBiayaLain) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiayaLain = $totalRealisasiBiaya > 0 ? ($totalRealisasiBiayaLain / $totalRealisasiBiaya) * 100 : 0;

// Hitung persentase terhadap omzet
$percentageOfOmzetBiayaLain = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiayaLain / $totalRealisasiOmzet) * 100 : 0;

// Hitung persentase terhadap margin
$percentageOfMarginBiayaLain = $totalMargin > 0 ? ($totalRealisasiBiayaLain / $totalMargin) * 100 : 0;

// Urutkan data
arsort($biayaLainPerEntitas); // Urutkan berdasarkan realisasi tertinggi
uasort($biayaLainPerJenis, function($a, $b) {
    return $b['realisasi'] <=> $a['realisasi'];
});
ksort($biayaLainPerTanggal); // Urutkan tanggal
ksort($biayaLainPerBulan); // Urutkan bulan

// Hitung statistik per bulan
$totalTargetBiayaLainBulanan = 0;
$totalRealisasiBiayaLainBulanan = 0;
$totalGapBiayaLainBulanan = 0;
$jumlahBulanLain = count($biayaLainPerBulan);

foreach ($biayaLainPerBulan as $bulanData) {
    $totalTargetBiayaLainBulanan += $bulanData['target'];
    $totalRealisasiBiayaLainBulanan += $bulanData['realisasi'];
    $totalGapBiayaLainBulanan += $bulanData['gap'];
}

$rataTargetBiayaLainBulanan = $jumlahBulanLain > 0 ? $totalTargetBiayaLainBulanan / $jumlahBulanLain : 0;
$rataRealisasiBiayaLainBulanan = $jumlahBulanLain > 0 ? $totalRealisasiBiayaLainBulanan / $jumlahBulanLain : 0;
$rataGapBiayaLainBulanan = $jumlahBulanLain > 0 ? $totalGapBiayaLainBulanan / $jumlahBulanLain : 0;
$rataPersentaseBiayaLainBulanan = $totalTargetBiayaLainBulanan > 0 ? ($totalRealisasiBiayaLainBulanan / $totalTargetBiayaLainBulanan) * 100 : 0;

// Hitung persentase rata-rata bulanan
$totalPersenBiayaLainBulanan = 0;
$totalPersenOmzetLainBulanan = 0;
$totalHariBulananLain = 0;

foreach ($biayaLainPerBulan as $bulanKey => $dataBulan) {
    $bulanAngka = (int)substr($bulanKey, 5, 2);
    $tahun = (int)substr($bulanKey, 0, 4);
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulanAngka, $tahun);
    
    // Hitung persentase dari total biaya bulanan
    $biayaBulanan = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['realisasi'] : 0;
    $persenBiaya = $biayaBulanan > 0 ? ($dataBulan['realisasi'] / $biayaBulanan) * 100 : 0;
    
    // Hitung persentase dari omzet bulanan
    $omzetBulanan = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['realisasi'] : 0;
    $persenOmzet = $omzetBulanan > 0 ? ($dataBulan['realisasi'] / $omzetBulanan) * 100 : 0;
    
    $totalPersenBiayaLainBulanan += $persenBiaya;
    $totalPersenOmzetLainBulanan += $persenOmzet;
    $totalHariBulananLain += $daysInMonth;
}

$rataPersenBiayaLain = $jumlahBulanLain > 0 ? $totalPersenBiayaLainBulanan / $jumlahBulanLain : 0;
$rataPersenOmzetLain = $jumlahBulanLain > 0 ? $totalPersenOmzetLainBulanan / $jumlahBulanLain : 0;

// Identifikasi jenis biaya yang paling signifikan
$top5BiayaLain = array_slice($biayaLainPerJenis, 0, 5, true);

// ==================== DATA NPM ====================
// Query khusus untuk data NPM dari tabel data_report
$sqlNPM = "SELECT 
                dr.nama_entitas,
                dr.nama_akun,
                dr.nilai_target,
                dr.nilai_realisasi,
                dr.tanggal,
                dr.urutan
            FROM data_report dr
            WHERE dr.nama_akun = 'Npm'";

$paramsNPM = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlNPM .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsNPM[] = $tanggal_awal;
    $paramsNPM[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlNPM .= " AND DATE(dr.tanggal) >= ?";
    $paramsNPM[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlNPM .= " AND DATE(dr.tanggal) <= ?";
    $paramsNPM[] = $tanggal_akhir;
}

$sqlNPM .= " ORDER BY dr.nama_entitas, dr.tanggal";

try {
    $stmtNPM = $pdo->prepare($sqlNPM);
    $stmtNPM->execute($paramsNPM);
    $dataNPM = $stmtNPM->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database NPM: " . $e->getMessage());
}

// Proses data NPM
$npmData = [];
$totalTargetNPM = 0;
$totalRealisasiNPM = 0;
$npmPerEntitas = [];
$npmPerTanggal = [];
$npmPerBulan = [];

foreach ($dataNPM as $row) {
    $entitas = $row['nama_entitas'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetNPM += $target;
    $totalRealisasiNPM += $realisasi;
    
    // Per entitas
    if (!isset($npmPerEntitas[$entitas])) {
        $npmPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $npmPerEntitas[$entitas]['target'] += $target;
    $npmPerEntitas[$entitas]['realisasi'] += $realisasi;
    $npmPerEntitas[$entitas]['gap'] = $npmPerEntitas[$entitas]['realisasi'] - $npmPerEntitas[$entitas]['target'];
    
    // Per tanggal
    if (!isset($npmPerTanggal[$tanggal])) {
        $npmPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $npmPerTanggal[$tanggal]['target'] += $target;
    $npmPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($npmPerBulan[$bulan])) {
        $npmPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $npmPerBulan[$bulan]['target'] += $target;
    $npmPerBulan[$bulan]['realisasi'] += $realisasi;
    $npmPerBulan[$bulan]['gap'] = $npmPerBulan[$bulan]['realisasi'] - $npmPerBulan[$bulan]['target'];
}

// Hitung statistik NPM
$totalGapNPM = $totalRealisasiNPM - $totalTargetNPM;
$percentageNPM = $totalTargetNPM > 0 ? ($totalRealisasiNPM / $totalTargetNPM) * 100 : 0;

// Hitung persentase NPM terhadap total omzet
$percentageOfOmzetNPM = $totalRealisasiOmzet > 0 ? ($totalRealisasiNPM / $totalRealisasiOmzet) * 100 : 0;

// Hitung rata-rata NPM per bulan
$totalTargetNPMBulanan = 0;
$totalRealisasiNPMBulanan = 0;
$totalGapNPMBulanan = 0;
$totalOmzetBulananNPM = 0;
$jumlahBulanNPM = count($npmPerBulan);

foreach ($npmPerBulan as $bulanKey => $dataBulan) {
    $totalTargetNPMBulanan += $dataBulan['target'];
    $totalRealisasiNPMBulanan += $dataBulan['realisasi'];
    $totalGapNPMBulanan += $dataBulan['gap'];
    
    // Hitung juga total omzet untuk bulan-bulan yang ada data NPM
    $omzetBulanan = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['realisasi'] : 0;
    $totalOmzetBulananNPM += $omzetBulanan;
}

$rataTargetNPMBulanan = $jumlahBulanNPM > 0 ? $totalTargetNPMBulanan / $jumlahBulanNPM : 0;
$rataRealisasiNPMBulanan = $jumlahBulanNPM > 0 ? $totalRealisasiNPMBulanan / $jumlahBulanNPM : 0;
$rataGapNPMBulanan = $jumlahBulanNPM > 0 ? $totalGapNPMBulanan / $jumlahBulanNPM : 0;
$rataPersentaseNPMBulanan = $totalTargetNPMBulanan > 0 ? ($totalRealisasiNPMBulanan / $totalTargetNPMBulanan) * 100 : 0;

// Urutkan data
arsort($npmPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($npmPerTanggal); // Urutkan tanggal
ksort($npmPerBulan); // Urutkan bulan

// Hitung profitabilitas per entitas (NPM/Omzet)
$profitabilitasPerEntitas = [];
foreach ($npmPerEntitas as $entitas => $data) {
    $omzetEntitas = isset($entitasDataOmzet[$entitas]) ? $entitasDataOmzet[$entitas]['total_realisasi'] : 0;
    $npmEntitas = $data['realisasi'];
    $profitabilitas = $omzetEntitas > 0 ? ($npmEntitas / $omzetEntitas) * 100 : 0;
    $profitabilitasPerEntitas[$entitas] = $profitabilitas;
}

//////////////////////////////////////////
// ==================== QUERY OMZET BERSIH PER HARI ====================
// Query untuk mengambil data Omzet Bersih per hari
$sqlOmzetBersihHarianBiaya = "SELECT 
    DATE(tanggal) as tanggal_date,
    SUM(nilai_target) as total_target,
    SUM(nilai_realisasi) as total_realisasi
FROM data_report 
WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
    OR LOWER(nama_akun) LIKE '%omzetbersih%'
    OR nama_akun = 'Omzet Bersih')";

$paramsOmzetBersihHarianBiaya = [];

// Tambahkan filter tanggal jika ada (sama dengan filter utama)
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlOmzetBersihHarianBiaya .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $paramsOmzetBersihHarianBiaya[] = $tanggal_awal;
    $paramsOmzetBersihHarianBiaya[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlOmzetBersihHarianBiaya .= " AND DATE(tanggal) >= ?";
    $paramsOmzetBersihHarianBiaya[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlOmzetBersihHarianBiaya .= " AND DATE(tanggal) <= ?";
    $paramsOmzetBersihHarianBiaya[] = $tanggal_akhir;
}

$sqlOmzetBersihHarianBiaya .= " GROUP BY DATE(tanggal) ORDER BY tanggal_date";

try {
    $stmtOmzetBersihHarianBiaya = $pdo->prepare($sqlOmzetBersihHarianBiaya);
    $stmtOmzetBersihHarianBiaya->execute($paramsOmzetBersihHarianBiaya);
    $dataOmzetBersihHarianBiaya = $stmtOmzetBersihHarianBiaya->fetchAll(PDO::FETCH_ASSOC);
    
    // Konversi ke array asosiatif dengan tanggal sebagai key
    $omzetBersihPerHariBiaya = [];
    foreach ($dataOmzetBersihHarianBiaya as $row) {
        $tanggal = $row['tanggal_date'];
        $omzetBersihPerHariBiaya[$tanggal] = [
            'target' => (float)$row['total_target'],
            'realisasi' => (float)$row['total_realisasi']
        ];
    }
    
    // Fallback jika data kosong - gunakan data omzet biasa
    if (empty($omzetBersihPerHariBiaya)) {
        foreach ($tanggalDataOmzet as $tanggal => $data) {
            $includeRow = true;
            if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
                $includeRow = false;
            }
            if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
                $includeRow = false;
            }
            
            if ($includeRow) {
                $omzetBersihPerHariBiaya[$tanggal] = $data;
            }
        }
    }
    
} catch (PDOException $e) {
    // Fallback jika query gagal
    $omzetBersihPerHariBiaya = [];
    foreach ($tanggalDataOmzet as $tanggal => $data) {
        $includeRow = true;
        if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
            $includeRow = false;
        }
        if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
            $includeRow = false;
        }
        
        if ($includeRow) {
            $omzetBersihPerHariBiaya[$tanggal] = $data;
        }
    }
}

// Filter $tanggalDataBiaya berdasarkan tanggal filter
$tanggalDataBiayaFiltered = [];
foreach ($tanggalDataBiaya as $tanggal => $data) {
    $includeRow = true;
    if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
        $includeRow = false;
    }
    if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
        $includeRow = false;
    }
    
    if ($includeRow) {
        $tanggalDataBiayaFiltered[$tanggal] = $data;
    }
}


///////////////////////////////////////////////////////
// ==================== QUERY OMZET BERSIH PER PERIODE ====================
// Query untuk mengambil total Omzet Bersih untuk periode yang difilter
$sqlTotalOmzetBersih = "SELECT 
    SUM(nilai_target) as total_target,
    SUM(nilai_realisasi) as total_realisasi
FROM data_report 
WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
    OR LOWER(nama_akun) LIKE '%omzetbersih%'
    OR nama_akun = 'Omzet Bersih')";

$paramsTotalOmzetBersih = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlTotalOmzetBersih .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $paramsTotalOmzetBersih[] = $tanggal_awal;
    $paramsTotalOmzetBersih[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlTotalOmzetBersih .= " AND DATE(tanggal) >= ?";
    $paramsTotalOmzetBersih[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlTotalOmzetBersih .= " AND DATE(tanggal) <= ?";
    $paramsTotalOmzetBersih[] = $tanggal_akhir;
}

try {
    $stmtTotalOmzetBersih = $pdo->prepare($sqlTotalOmzetBersih);
    $stmtTotalOmzetBersih->execute($paramsTotalOmzetBersih);
    $dataTotalOmzetBersih = $stmtTotalOmzetBersih->fetch(PDO::FETCH_ASSOC);
    
    $totalTargetOmzetBersih = $dataTotalOmzetBersih ? (float)$dataTotalOmzetBersih['total_target'] : 0;
    $totalRealisasiOmzetBersih = $dataTotalOmzetBersih ? (float)$dataTotalOmzetBersih['total_realisasi'] : 0;
    
    // Fallback jika data kosong - gunakan total omzet biasa
    if ($totalRealisasiOmzetBersih == 0) {
        $totalTargetOmzetBersih = $totalTargetOmzet;
        $totalRealisasiOmzetBersih = $totalRealisasiOmzet;
    }
    
} catch (PDOException $e) {
    // Fallback jika query gagal
    $totalTargetOmzetBersih = $totalTargetOmzet;
    $totalRealisasiOmzetBersih = $totalRealisasiOmzet;
}


////////////////////////////////////////////////////////
// Query khusus untuk data gaji/HR - DIUBAH
$sqlGaji = "SELECT 
                dr.nama_entitas,
                dr.nama_akun,
                dr.nilai_target,
                dr.nilai_realisasi,
                dr.tanggal,
                dr.urutan,
                a.group_akun
            FROM data_report dr
            LEFT JOIN akun a ON dr.urutan = a.urutan
            WHERE a.group_akun = 'BIAYA' 
                AND (
                    LOWER(dr.nama_akun) LIKE '%gaji%' 
                    OR LOWER(dr.nama_akun) LIKE '%hr%' 
                    OR LOWER(dr.nama_akun) LIKE '%human resource%' 
                    OR LOWER(dr.nama_akun) LIKE '%tunjangan%' 
                    OR LOWER(dr.nama_akun) LIKE '%bonus%' 
                    OR LOWER(dr.nama_akun) LIKE '%insentif%' 
                    OR LOWER(dr.nama_akun) LIKE '%upah%' 
                    OR LOWER(dr.nama_akun) LIKE '%payroll%'
                    OR dr.nama_akun = 'GAJI' 
                    OR dr.nama_akun = 'GAJI KARYAWAN' 
                    OR dr.nama_akun = 'BEBAN GAJI'
                    OR dr.nama_akun = 'BIAYA GAJI'
                    OR dr.nama_akun = 'BIAYA HR'
                    OR dr.nama_akun = 'BEBAN HR'
                )";

$paramsGaji = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlGaji .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsGaji[] = $tanggal_awal;
    $paramsGaji[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlGaji .= " AND DATE(dr.tanggal) >= ?";
    $paramsGaji[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlGaji .= " AND DATE(dr.tanggal) <= ?";
    $paramsGaji[] = $tanggal_akhir;
}

$sqlGaji .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtGaji = $pdo->prepare($sqlGaji);
    $stmtGaji->execute($paramsGaji);
    $dataGaji = $stmtGaji->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database gaji: " . $e->getMessage());
}

// Proses data gaji/HR
$gajiData = [];
$totalTargetGaji = 0;
$totalRealisasiGaji = 0;
$gajiPerEntitas = [];
$gajiPerJenis = [];
$gajiPerTanggal = [];

foreach ($dataGaji as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetGaji += $target;
    $totalRealisasiGaji += $realisasi;
    
    // Per entitas
    if (!isset($gajiPerEntitas[$entitas])) {
        $gajiPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $gajiPerEntitas[$entitas]['target'] += $target;
    $gajiPerEntitas[$entitas]['realisasi'] += $realisasi;
    $gajiPerEntitas[$entitas]['gap'] = $gajiPerEntitas[$entitas]['realisasi'] - $gajiPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($gajiPerJenis[$jenis])) {
        $gajiPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $gajiPerJenis[$jenis]['target'] += $target;
    $gajiPerJenis[$jenis]['realisasi'] += $realisasi;
    $gajiPerJenis[$jenis]['gap'] = $gajiPerJenis[$jenis]['realisasi'] - $gajiPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($gajiPerTanggal[$tanggal])) {
        $gajiPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $gajiPerTanggal[$tanggal]['target'] += $target;
    $gajiPerTanggal[$tanggal]['realisasi'] += $realisasi;
}

// Hitung statistik
$totalGapGaji = $totalRealisasiGaji - $totalTargetGaji;
$percentageGaji = $totalTargetGaji > 0 ? ($totalRealisasiGaji / $totalTargetGaji) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiaya = $totalRealisasiBiaya > 0 ? ($totalRealisasiGaji / $totalRealisasiBiaya) * 100 : 0;

// Urutkan data
arsort($gajiPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($gajiPerTanggal); // Urutkan tanggal

// ==================== DATA GAJI PER BULAN ====================
$gajiPerBulan = [];
$bulanGajiLabels = [];

foreach ($dataGaji as $row) {
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    if (!isset($gajiPerBulan[$bulan])) {
        $gajiPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
        $bulanGajiLabels[] = $bulan;
    }
    
    $gajiPerBulan[$bulan]['target'] += $target;
    $gajiPerBulan[$bulan]['realisasi'] += $realisasi;
    $gajiPerBulan[$bulan]['gap'] = $gajiPerBulan[$bulan]['realisasi'] - $gajiPerBulan[$bulan]['target'];
}

// Urutkan berdasarkan bulan
ksort($gajiPerBulan);

// Hitung statistik per bulan
$totalTargetGajiBulanan = 0;
$totalRealisasiGajiBulanan = 0;
$totalGapGajiBulanan = 0;
$jumlahBulan = count($gajiPerBulan);

foreach ($gajiPerBulan as $bulanData) {
    $totalTargetGajiBulanan += $bulanData['target'];
    $totalRealisasiGajiBulanan += $bulanData['realisasi'];
    $totalGapGajiBulanan += $bulanData['gap'];
}

$rataTargetGajiBulanan = $jumlahBulan > 0 ? $totalTargetGajiBulanan / $jumlahBulan : 0;
$rataRealisasiGajiBulanan = $jumlahBulan > 0 ? $totalRealisasiGajiBulanan / $jumlahBulan : 0;
$rataGapGajiBulanan = $jumlahBulan > 0 ? $totalGapGajiBulanan / $jumlahBulan : 0;
$rataPersentaseGajiBulanan = $totalTargetGajiBulanan > 0 ? ($totalRealisasiGajiBulanan / $totalTargetGajiBulanan) * 100 : 0;


///////////////////////////////////////////////////
// Query khusus untuk data biaya operasional 
// Biaya Operasional = Semua biaya KECUALI gaji/hr DAN pemasaran
$sqlBiayaOps = "SELECT 
                    dr.nama_entitas,
                    dr.nama_akun,
                    dr.nilai_target,
                    dr.nilai_realisasi,
                    dr.tanggal,
                    dr.urutan,
                    a.group_akun
                FROM data_report dr
                LEFT JOIN akun a ON dr.urutan = a.urutan
                WHERE a.group_akun = 'BIAYA' 
                    AND (
                        -- Kecualikan biaya gaji/hr
                        LOWER(dr.nama_akun) LIKE '%operasional%' 
                        OR LOWER(dr.nama_akun) LIKE '%operational%'
                        OR dr.nama_akun = 'BIAYA OPERASIONAL'
                        OR dr.nama_akun = 'BIAYA OPERASI'
                        OR dr.nama_akun = 'BEBAN OPERASI'
                        OR dr.nama_akun = 'BEBAN OPERASIONAL'
                    )";

$paramsBiayaOps = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaOps .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaOps[] = $tanggal_awal;
    $paramsBiayaOps[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaOps .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaOps[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaOps .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaOps[] = $tanggal_akhir;
}

$sqlBiayaOps .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtBiayaOps = $pdo->prepare($sqlBiayaOps);
    $stmtBiayaOps->execute($paramsBiayaOps);
    $dataBiayaOps = $stmtBiayaOps->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya operasional: " . $e->getMessage());
}

// Proses data biaya operasional
$biayaOpsData = [];
$totalTargetBiayaOps = 0;
$totalRealisasiBiayaOps = 0;
$biayaOpsPerEntitas = [];
$biayaOpsPerJenis = [];
$biayaOpsPerTanggal = [];
$biayaOpsPerBulan = [];

foreach ($dataBiayaOps as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetBiayaOps += $target;
    $totalRealisasiBiayaOps += $realisasi;
    
    // Per entitas
    if (!isset($biayaOpsPerEntitas[$entitas])) {
        $biayaOpsPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $biayaOpsPerEntitas[$entitas]['target'] += $target;
    $biayaOpsPerEntitas[$entitas]['realisasi'] += $realisasi;
    $biayaOpsPerEntitas[$entitas]['gap'] = $biayaOpsPerEntitas[$entitas]['realisasi'] - $biayaOpsPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($biayaOpsPerJenis[$jenis])) {
        $biayaOpsPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaOpsPerJenis[$jenis]['target'] += $target;
    $biayaOpsPerJenis[$jenis]['realisasi'] += $realisasi;
    $biayaOpsPerJenis[$jenis]['gap'] = $biayaOpsPerJenis[$jenis]['realisasi'] - $biayaOpsPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($biayaOpsPerTanggal[$tanggal])) {
        $biayaOpsPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $biayaOpsPerTanggal[$tanggal]['target'] += $target;
    $biayaOpsPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($biayaOpsPerBulan[$bulan])) {
        $biayaOpsPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaOpsPerBulan[$bulan]['target'] += $target;
    $biayaOpsPerBulan[$bulan]['realisasi'] += $realisasi;
    $biayaOpsPerBulan[$bulan]['gap'] = $biayaOpsPerBulan[$bulan]['realisasi'] - $biayaOpsPerBulan[$bulan]['target'];
}

// Hitung statistik
$totalGapBiayaOps = $totalRealisasiBiayaOps - $totalTargetBiayaOps;
$percentageBiayaOps = $totalTargetBiayaOps > 0 ? ($totalRealisasiBiayaOps / $totalTargetBiayaOps) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiayaOps = $totalRealisasiBiaya > 0 ? ($totalRealisasiBiayaOps / $totalRealisasiBiaya) * 100 : 0;

// Hitung persentase terhadap omzet
$percentageOfOmzetBiayaOps = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiayaOps / $totalRealisasiOmzet) * 100 : 0;

// Urutkan data
arsort($biayaOpsPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($biayaOpsPerTanggal); // Urutkan tanggal
ksort($biayaOpsPerBulan); // Urutkan bulan

// Hitung statistik per bulan
$totalTargetBiayaOpsBulanan = 0;
$totalRealisasiBiayaOpsBulanan = 0;
$totalGapBiayaOpsBulanan = 0;
$jumlahBulanOps = count($biayaOpsPerBulan);

foreach ($biayaOpsPerBulan as $bulanData) {
    $totalTargetBiayaOpsBulanan += $bulanData['target'];
    $totalRealisasiBiayaOpsBulanan += $bulanData['realisasi'];
    $totalGapBiayaOpsBulanan += $bulanData['gap'];
}

$rataTargetBiayaOpsBulanan = $jumlahBulanOps > 0 ? $totalTargetBiayaOpsBulanan / $jumlahBulanOps : 0;
$rataRealisasiBiayaOpsBulanan = $jumlahBulanOps > 0 ? $totalRealisasiBiayaOpsBulanan / $jumlahBulanOps : 0;
$rataGapBiayaOpsBulanan = $jumlahBulanOps > 0 ? $totalGapBiayaOpsBulanan / $jumlahBulanOps : 0;
$rataPersentaseBiayaOpsBulanan = $totalTargetBiayaOpsBulanan > 0 ? ($totalRealisasiBiayaOpsBulanan / $totalTargetBiayaOpsBulanan) * 100 : 0;


// Query untuk mengambil data Biaya Lain-lain per bulan dengan grouping yang benar
$sqlBiayaLainBulanan = "SELECT 
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    SUM(nilai_target) as total_target,
    SUM(nilai_realisasi) as total_realisasi
FROM data_report dr
LEFT JOIN akun a ON dr.urutan = a.urutan
WHERE a.group_akun = 'BIAYA' 
    AND (
        LOWER(dr.nama_akun) LIKE '%lain-lain%' 
        OR LOWER(dr.nama_akun) LIKE '%biaya lain-lain%' 
        OR LOWER(dr.nama_akun) LIKE '%biaya lain%' 
        OR LOWER(dr.nama_akun) LIKE '%beban lain-lain%' 
        OR LOWER(dr.nama_akun) LIKE '%beban lain%'
    )";

$paramsBiayaLainBulanan = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaLainBulanan .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $paramsBiayaLainBulanan[] = $tanggal_awal;
    $paramsBiayaLainBulanan[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaLainBulanan .= " AND DATE(tanggal) >= ?";
    $paramsBiayaLainBulanan[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaLainBulanan .= " AND DATE(tanggal) <= ?";
    $paramsBiayaLainBulanan[] = $tanggal_akhir;
}

$sqlBiayaLainBulanan .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";

try {
    $stmtBiayaLainBulanan = $pdo->prepare($sqlBiayaLainBulanan);
    $stmtBiayaLainBulanan->execute($paramsBiayaLainBulanan);
    $dataBiayaLainBulanan = $stmtBiayaLainBulanan->fetchAll(PDO::FETCH_ASSOC);
    
    // Konversi ke array asosiatif dengan grouping yang benar
    $biayaLainPerBulanGrouped = [];
    foreach ($dataBiayaLainBulanan as $row) {
        $bulan = $row['bulan'];
        $biayaLainPerBulanGrouped[$bulan] = [
            'target' => (float)$row['total_target'],
            'realisasi' => (float)$row['total_realisasi']
        ];
    }
    
} catch (PDOException $e) {
    die("Error accessing database biaya lain-lain bulanan: " . $e->getMessage());
}


// ==================== QUERY BIAYA GA (GENERAL AFFAIRS) ====================
$sqlBiayaGA = "SELECT 
                    dr.nama_entitas,
                    dr.nama_akun,
                    dr.nilai_target,
                    dr.nilai_realisasi,
                    dr.tanggal,
                    dr.urutan,
                    a.group_akun
                FROM data_report dr
                LEFT JOIN akun a ON dr.urutan = a.urutan
                WHERE a.group_akun = 'BIAYA' 
                    AND (
                        LOWER(dr.nama_akun) LIKE '%general affair%' OR 
                        LOWER(dr.nama_akun) LIKE '%administrasi%' OR 
                        LOWER(dr.nama_akun) LIKE '%admin%' OR 
                        LOWER(dr.nama_akun) LIKE '%kantor%' OR 
                        LOWER(dr.nama_akun) LIKE '%office%' OR 
                        LOWER(dr.nama_akun) LIKE '%perlengkapan%' OR 
                        LOWER(dr.nama_akun) LIKE '%supplies%' OR 
                        LOWER(dr.nama_akun) LIKE '%atk%' OR 
                        LOWER(dr.nama_akun) LIKE '%alat tulis%' OR 
                        LOWER(dr.nama_akun) LIKE '%stationery%' OR 
                        LOWER(dr.nama_akun) LIKE '%utilitas%' OR 
                        LOWER(dr.nama_akun) LIKE '%utility%' OR 
                        LOWER(dr.nama_akun) LIKE '%listrik%' OR 
                        LOWER(dr.nama_akun) LIKE '%electricity%' OR 
                        LOWER(dr.nama_akun) LIKE '%air%' OR 
                        LOWER(dr.nama_akun) LIKE '%water%' OR 
                        LOWER(dr.nama_akun) LIKE '%telepon%' OR 
                        LOWER(dr.nama_akun) LIKE '%telephone%' OR 
                        LOWER(dr.nama_akun) LIKE '%internet%' OR 
                        LOWER(dr.nama_akun) LIKE '%konsumsi%' OR 
                        LOWER(dr.nama_akun) LIKE '%consumable%' OR 
                        LOWER(dr.nama_akun) LIKE '%kebersihan%' OR 
                        LOWER(dr.nama_akun) LIKE '%cleaning%' OR 
                        LOWER(dr.nama_akun) LIKE '%keamanan%' OR 
                        LOWER(dr.nama_akun) LIKE '%security%' OR 
                        LOWER(dr.nama_akun) LIKE '%perawatan%' OR 
                        LOWER(dr.nama_akun) LIKE '%maintenance%' OR 
                        dr.nama_akun = 'BIAYA GA' OR 
                        dr.nama_akun = 'BEBAN GA' OR 
                        dr.nama_akun = 'BIAYA ADMINISTRASI' OR 
                        dr.nama_akun = 'BEBAN ADMINISTRASI' OR
                        dr.nama_akun = 'BIAYA KANTOR' OR
                        dr.nama_akun = 'BEBAN KANTOR' OR
                        dr.nama_akun = 'BIAYA PERLENGKAPAN' OR
                        dr.nama_akun = 'BEBAN PERLENGKAPAN'
                    )";

$paramsBiayaGA = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaGA .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaGA[] = $tanggal_awal;
    $paramsBiayaGA[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaGA .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaGA[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaGA .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaGA[] = $tanggal_akhir;
}

$sqlBiayaGA .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtBiayaGA = $pdo->prepare($sqlBiayaGA);
    $stmtBiayaGA->execute($paramsBiayaGA);
    $dataBiayaGA = $stmtBiayaGA->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya GA: " . $e->getMessage());
}

// Proses data biaya GA
$biayaGAData = [];
$totalTargetBiayaGA = 0;
$totalRealisasiBiayaGA = 0;
$biayaGAPerEntitas = [];
$biayaGAPerJenis = [];
$biayaGAPerTanggal = [];
$biayaGAPerBulan = [];

foreach ($dataBiayaGA as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetBiayaGA += $target;
    $totalRealisasiBiayaGA += $realisasi;
    
    // Per entitas
    if (!isset($biayaGAPerEntitas[$entitas])) {
        $biayaGAPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $biayaGAPerEntitas[$entitas]['target'] += $target;
    $biayaGAPerEntitas[$entitas]['realisasi'] += $realisasi;
    $biayaGAPerEntitas[$entitas]['gap'] = $biayaGAPerEntitas[$entitas]['realisasi'] - $biayaGAPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($biayaGAPerJenis[$jenis])) {
        $biayaGAPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaGAPerJenis[$jenis]['target'] += $target;
    $biayaGAPerJenis[$jenis]['realisasi'] += $realisasi;
    $biayaGAPerJenis[$jenis]['gap'] = $biayaGAPerJenis[$jenis]['realisasi'] - $biayaGAPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($biayaGAPerTanggal[$tanggal])) {
        $biayaGAPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $biayaGAPerTanggal[$tanggal]['target'] += $target;
    $biayaGAPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($biayaGAPerBulan[$bulan])) {
        $biayaGAPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaGAPerBulan[$bulan]['target'] += $target;
    $biayaGAPerBulan[$bulan]['realisasi'] += $realisasi;
    $biayaGAPerBulan[$bulan]['gap'] = $biayaGAPerBulan[$bulan]['realisasi'] - $biayaGAPerBulan[$bulan]['target'];
}

// Hitung statistik
$totalGapBiayaGA = $totalRealisasiBiayaGA - $totalTargetBiayaGA;
$percentageBiayaGA = $totalTargetBiayaGA > 0 ? ($totalRealisasiBiayaGA / $totalTargetBiayaGA) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiayaGA = $totalRealisasiBiaya > 0 ? ($totalRealisasiBiayaGA / $totalRealisasiBiaya) * 100 : 0;

// Hitung persentase terhadap omzet
$percentageOfOmzetBiayaGA = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiayaGA / $totalRealisasiOmzet) * 100 : 0;

// Hitung efisiensi GA per entitas
$efisiensiGAPerEntitas = [];
foreach ($biayaGAPerEntitas as $entitas => $data) {
    $omzetEntitas = isset($entitasDataOmzet[$entitas]) ? $entitasDataOmzet[$entitas]['total_realisasi'] : 0;
    $biayaGAEntitas = $data['realisasi'];
    $efisiensi = $biayaGAEntitas > 0 ? ($omzetEntitas / $biayaGAEntitas) : 0;
    $efisiensiGAPerEntitas[$entitas] = $efisiensi;
}

// Urutkan data
arsort($biayaGAPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($biayaGAPerTanggal); // Urutkan tanggal
ksort($biayaGAPerBulan); // Urutkan bulan

// Hitung statistik per bulan
$totalTargetBiayaGABulanan = 0;
$totalRealisasiBiayaGABulanan = 0;
$totalGapBiayaGABulanan = 0;
$jumlahBulanGA = count($biayaGAPerBulan);

foreach ($biayaGAPerBulan as $bulanData) {
    $totalTargetBiayaGABulanan += $bulanData['target'];
    $totalRealisasiBiayaGABulanan += $bulanData['realisasi'];
    $totalGapBiayaGABulanan += $bulanData['gap'];
}

$rataTargetBiayaGABulanan = $jumlahBulanGA > 0 ? $totalTargetBiayaGABulanan / $jumlahBulanGA : 0;
$rataRealisasiBiayaGABulanan = $jumlahBulanGA > 0 ? $totalRealisasiBiayaGABulanan / $jumlahBulanGA : 0;
$rataGapBiayaGABulanan = $jumlahBulanGA > 0 ? $totalGapBiayaGABulanan / $jumlahBulanGA : 0;
$rataPersentaseBiayaGABulanan = $totalTargetBiayaGABulanan > 0 ? ($totalRealisasiBiayaGABulanan / $totalTargetBiayaGABulanan) * 100 : 0;

// Query khusus untuk data per bulan (optional)
$sqlBiayaGABulanan = "SELECT 
    DATE_FORMAT(dr.tanggal, '%Y-%m') as bulan,
    SUM(dr.nilai_target) as total_target,
    SUM(dr.nilai_realisasi) as total_realisasi
FROM data_report dr
LEFT JOIN akun a ON dr.urutan = a.urutan
WHERE a.group_akun = 'BIAYA' 
    AND (
        LOWER(dr.nama_akun) LIKE '%general affair%' OR 
        LOWER(dr.nama_akun) LIKE '%administrasi%' OR 
        LOWER(dr.nama_akun) LIKE '%admin%' OR 
        LOWER(dr.nama_akun) LIKE '%kantor%' OR 
        LOWER(dr.nama_akun) LIKE '%office%' OR 
        LOWER(dr.nama_akun) LIKE '%perlengkapan%' OR 
        LOWER(dr.nama_akun) LIKE '%supplies%' OR 
        LOWER(dr.nama_akun) LIKE '%Biaya GA%' OR 
        dr.nama_akun = 'BIAYA GA' OR 
        dr.nama_akun = 'BEBAN GA' OR 
        dr.nama_akun = 'BIAYA ADMINISTRASI' OR 
        dr.nama_akun = 'BEBAN ADMINISTRASI'
    )";

$paramsBiayaGABulanan = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaGABulanan .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaGABulanan[] = $tanggal_awal;
    $paramsBiayaGABulanan[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaGABulanan .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaGABulanan[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaGABulanan .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaGABulanan[] = $tanggal_akhir;
}

$sqlBiayaGABulanan .= " GROUP BY DATE_FORMAT(dr.tanggal, '%Y-%m') ORDER BY bulan";

try {
    $stmtBiayaGABulanan = $pdo->prepare($sqlBiayaGABulanan);
    $stmtBiayaGABulanan->execute($paramsBiayaGABulanan);
    $dataBiayaGABulanan = $stmtBiayaGABulanan->fetchAll(PDO::FETCH_ASSOC);
    
    // Override data per bulan dengan hasil query
    $biayaGAPerBulan = [];
    foreach ($dataBiayaGABulanan as $row) {
        $bulan = $row['bulan'];
        $biayaGAPerBulan[$bulan] = [
            'target' => (float)$row['total_target'],
            'realisasi' => (float)$row['total_realisasi'],
            'gap' => (float)$row['total_realisasi'] - (float)$row['total_target']
        ];
    }
    
    // Update statistik bulanan
    $totalTargetBiayaGABulanan = 0;
    $totalRealisasiBiayaGABulanan = 0;
    $totalGapBiayaGABulanan = 0;
    $jumlahBulanGA = count($biayaGAPerBulan);
    
    foreach ($biayaGAPerBulan as $bulanData) {
        $totalTargetBiayaGABulanan += $bulanData['target'];
        $totalRealisasiBiayaGABulanan += $bulanData['realisasi'];
        $totalGapBiayaGABulanan += $bulanData['gap'];
    }
    
    $rataTargetBiayaGABulanan = $jumlahBulanGA > 0 ? $totalTargetBiayaGABulanan / $jumlahBulanGA : 0;
    $rataRealisasiBiayaGABulanan = $jumlahBulanGA > 0 ? $totalRealisasiBiayaGABulanan / $jumlahBulanGA : 0;
    $rataGapBiayaGABulanan = $jumlahBulanGA > 0 ? $totalGapBiayaGABulanan / $jumlahBulanGA : 0;
    $rataPersentaseBiayaGABulanan = $totalTargetBiayaGABulanan > 0 ? ($totalRealisasiBiayaGABulanan / $totalTargetBiayaGABulanan) * 100 : 0;
    
} catch (PDOException $e) {
    // Jika query gagal, tetap gunakan data yang sudah diproses sebelumnya
}


// ==================== QUERY BIAYA ZISWAF (ZAKAT, INFAQ, SEDEKAH, WAKAF) ====================
$sqlBiayaZISWAF = "SELECT 
                    dr.nama_entitas,
                    dr.nama_akun,
                    dr.nilai_target,
                    dr.nilai_realisasi,
                    dr.tanggal,
                    dr.urutan,
                    a.group_akun
                FROM data_report dr
                LEFT JOIN akun a ON dr.urutan = a.urutan
                WHERE a.group_akun = 'BIAYA' 
                    AND (
                        LOWER(dr.nama_akun) LIKE '%ziswaf%' OR 
                        LOWER(dr.nama_akun) LIKE '%zakat%' OR 
                        LOWER(dr.nama_akun) LIKE '%infaq%' OR 
                        LOWER(dr.nama_akun) LIKE '%sedekah%' OR 
                        LOWER(dr.nama_akun) LIKE '%wakaf%' OR 
                        LOWER(dr.nama_akun) LIKE '%sodaqoh%' OR 
                        LOWER(dr.nama_akun) LIKE '%shodaqoh%' OR 
                        LOWER(dr.nama_akun) LIKE '%donasi%' OR 
                        LOWER(dr.nama_akun) LIKE '%dana sosial%' OR 
                        LOWER(dr.nama_akun) LIKE '%dana kemanusiaan%' OR 
                        LOWER(dr.nama_akun) LIKE '%dana umat%' OR 
                        dr.nama_akun = 'BIAYA ZISWAF' OR 
                        dr.nama_akun = 'BEBAN ZISWAF' OR 
                        dr.nama_akun = 'BIAYA ZAKAT' OR 
                        dr.nama_akun = 'BEBAN ZAKAT' OR
                        dr.nama_akun = 'BIAYA INFAQ' OR
                        dr.nama_akun = 'BEBAN INFAQ' OR
                        dr.nama_akun = 'BIAYA SEDEKAH' OR
                        dr.nama_akun = 'BEBAN SEDEKAH' OR
                        dr.nama_akun = 'BIAYA WAKAF' OR
                        dr.nama_akun = 'BEBAN WAKAF'
                    )";

$paramsBiayaZISWAF = [];

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sqlBiayaZISWAF .= " AND DATE(dr.tanggal) BETWEEN ? AND ?";
    $paramsBiayaZISWAF[] = $tanggal_awal;
    $paramsBiayaZISWAF[] = $tanggal_akhir;
} elseif (!empty($tanggal_awal)) {
    $sqlBiayaZISWAF .= " AND DATE(dr.tanggal) >= ?";
    $paramsBiayaZISWAF[] = $tanggal_awal;
} elseif (!empty($tanggal_akhir)) {
    $sqlBiayaZISWAF .= " AND DATE(dr.tanggal) <= ?";
    $paramsBiayaZISWAF[] = $tanggal_akhir;
}

$sqlBiayaZISWAF .= " ORDER BY dr.nama_entitas, dr.nama_akun, dr.tanggal";

try {
    $stmtBiayaZISWAF = $pdo->prepare($sqlBiayaZISWAF);
    $stmtBiayaZISWAF->execute($paramsBiayaZISWAF);
    $dataBiayaZISWAF = $stmtBiayaZISWAF->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database biaya ZISWAF: " . $e->getMessage());
}

// Proses data biaya ZISWAF
$biayaZISWAFData = [];
$totalTargetBiayaZISWAF = 0;
$totalRealisasiBiayaZISWAF = 0;
$biayaZISWAFPerEntitas = [];
$biayaZISWAFPerJenis = [];
$biayaZISWAFPerTanggal = [];
$biayaZISWAFPerBulan = [];

foreach ($dataBiayaZISWAF as $row) {
    $entitas = $row['nama_entitas'];
    $jenis = $row['nama_akun'];
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    // Total keseluruhan
    $totalTargetBiayaZISWAF += $target;
    $totalRealisasiBiayaZISWAF += $realisasi;
    
    // Per entitas
    if (!isset($biayaZISWAFPerEntitas[$entitas])) {
        $biayaZISWAFPerEntitas[$entitas] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0,
            'detail' => []
        ];
    }
    $biayaZISWAFPerEntitas[$entitas]['target'] += $target;
    $biayaZISWAFPerEntitas[$entitas]['realisasi'] += $realisasi;
    $biayaZISWAFPerEntitas[$entitas]['gap'] = $biayaZISWAFPerEntitas[$entitas]['realisasi'] - $biayaZISWAFPerEntitas[$entitas]['target'];
    
    // Per jenis
    if (!isset($biayaZISWAFPerJenis[$jenis])) {
        $biayaZISWAFPerJenis[$jenis] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaZISWAFPerJenis[$jenis]['target'] += $target;
    $biayaZISWAFPerJenis[$jenis]['realisasi'] += $realisasi;
    $biayaZISWAFPerJenis[$jenis]['gap'] = $biayaZISWAFPerJenis[$jenis]['realisasi'] - $biayaZISWAFPerJenis[$jenis]['target'];
    
    // Per tanggal
    if (!isset($biayaZISWAFPerTanggal[$tanggal])) {
        $biayaZISWAFPerTanggal[$tanggal] = [
            'target' => 0,
            'realisasi' => 0
        ];
    }
    $biayaZISWAFPerTanggal[$tanggal]['target'] += $target;
    $biayaZISWAFPerTanggal[$tanggal]['realisasi'] += $realisasi;
    
    // Per bulan
    if (!isset($biayaZISWAFPerBulan[$bulan])) {
        $biayaZISWAFPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
    }
    $biayaZISWAFPerBulan[$bulan]['target'] += $target;
    $biayaZISWAFPerBulan[$bulan]['realisasi'] += $realisasi;
    $biayaZISWAFPerBulan[$bulan]['gap'] = $biayaZISWAFPerBulan[$bulan]['realisasi'] - $biayaZISWAFPerBulan[$bulan]['target'];
}

// Hitung statistik
$totalGapBiayaZISWAF = $totalRealisasiBiayaZISWAF - $totalTargetBiayaZISWAF;
$percentageBiayaZISWAF = $totalTargetBiayaZISWAF > 0 ? ($totalRealisasiBiayaZISWAF / $totalTargetBiayaZISWAF) * 100 : 0;

// Hitung persentase terhadap total biaya
$percentageOfTotalBiayaZISWAF = $totalRealisasiBiaya > 0 ? ($totalRealisasiBiayaZISWAF / $totalRealisasiBiaya) * 100 : 0;

// Hitung persentase terhadap omzet
$percentageOfOmzetBiayaZISWAF = $totalRealisasiOmzet > 0 ? ($totalRealisasiBiayaZISWAF / $totalRealisasiOmzet) * 100 : 0;

// Hitung persentase terhadap margin
$percentageOfMarginBiayaZISWAF = $totalMargin > 0 ? ($totalRealisasiBiayaZISWAF / $totalMargin) * 100 : 0;

// Urutkan data
arsort($biayaZISWAFPerEntitas); // Urutkan berdasarkan realisasi tertinggi
ksort($biayaZISWAFPerTanggal); // Urutkan tanggal
ksort($biayaZISWAFPerBulan); // Urutkan bulan

// Hitung statistik per bulan
$totalTargetBiayaZISWAFBulanan = 0;
$totalRealisasiBiayaZISWAFBulanan = 0;
$totalGapBiayaZISWAFBulanan = 0;
$jumlahBulanZISWAF = count($biayaZISWAFPerBulan);

foreach ($biayaZISWAFPerBulan as $bulanData) {
    $totalTargetBiayaZISWAFBulanan += $bulanData['target'];
    $totalRealisasiBiayaZISWAFBulanan += $bulanData['realisasi'];
    $totalGapBiayaZISWAFBulanan += $bulanData['gap'];
}

$rataTargetBiayaZISWAFBulanan = $jumlahBulanZISWAF > 0 ? $totalTargetBiayaZISWAFBulanan / $jumlahBulanZISWAF : 0;
$rataRealisasiBiayaZISWAFBulanan = $jumlahBulanZISWAF > 0 ? $totalRealisasiBiayaZISWAFBulanan / $jumlahBulanZISWAF : 0;
$rataGapBiayaZISWAFBulanan = $jumlahBulanZISWAF > 0 ? $totalGapBiayaZISWAFBulanan / $jumlahBulanZISWAF : 0;
$rataPersentaseBiayaZISWAFBulanan = $totalTargetBiayaZISWAFBulanan > 0 ? ($totalRealisasiBiayaZISWAFBulanan / $totalTargetBiayaZISWAFBulanan) * 100 : 0;


// ==================== DATA ZISWAF PER BULAN ====================
// Data sudah diambil dari $dataBiayaZISWAF sebelumnya
$ziswafPerBulan = [];
$bulanZISWAFLabels = [];

foreach ($dataBiayaZISWAF as $row) {
    $tanggal = $row['tanggal'];
    $bulan = date('Y-m', strtotime($tanggal));
    $target = (float)$row['nilai_target'];
    $realisasi = (float)$row['nilai_realisasi'];
    
    if (!isset($ziswafPerBulan[$bulan])) {
        $ziswafPerBulan[$bulan] = [
            'target' => 0,
            'realisasi' => 0,
            'gap' => 0
        ];
        $bulanZISWAFLabels[] = $bulan;
    }
    
    $ziswafPerBulan[$bulan]['target'] += $target;
    $ziswafPerBulan[$bulan]['realisasi'] += $realisasi;
    $ziswafPerBulan[$bulan]['gap'] = $ziswafPerBulan[$bulan]['realisasi'] - $ziswafPerBulan[$bulan]['target'];
}

// Urutkan berdasarkan bulan
ksort($ziswafPerBulan);

// Hitung statistik per bulan
$totalTargetZISWAFBulanan = 0;
$totalRealisasiZISWAFBulanan = 0;
$totalGapZISWAFBulanan = 0;
$jumlahBulanZISWAF = count($ziswafPerBulan);

foreach ($ziswafPerBulan as $bulanData) {
    $totalTargetZISWAFBulanan += $bulanData['target'];
    $totalRealisasiZISWAFBulanan += $bulanData['realisasi'];
    $totalGapZISWAFBulanan += $bulanData['gap'];
}

$rataTargetZISWAFBulanan = $jumlahBulanZISWAF > 0 ? $totalTargetZISWAFBulanan / $jumlahBulanZISWAF : 0;
$rataRealisasiZISWAFBulanan = $jumlahBulanZISWAF > 0 ? $totalRealisasiZISWAFBulanan / $jumlahBulanZISWAF : 0;
$rataGapZISWAFBulanan = $jumlahBulanZISWAF > 0 ? $totalGapZISWAFBulanan / $jumlahBulanZISWAF : 0;
$rataPersentaseZISWAFBulanan = $totalTargetZISWAFBulanan > 0 ? ($totalRealisasiZISWAFBulanan / $totalTargetZISWAFBulanan) * 100 : 0;


// ==================== DATA HASIL ANALISIS ====================
// Query untuk mengambil semua data dari tabel hasil_analisis berdasarkan kd_laporan
$sqlHasilAnalisis = "SELECT title, analisis FROM hasil_analisis WHERE kd_laporan = ?";
$paramsHasilAnalisis = [$kd_laporan];

try {
    $stmtHasilAnalisis = $pdo->prepare($sqlHasilAnalisis);
    $stmtHasilAnalisis->execute($paramsHasilAnalisis);
    $dataHasilAnalisis = $stmtHasilAnalisis->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error accessing database hasil_analisis: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - <?php echo htmlspecialchars($kd_laporan); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
   /* =================================================
   NEW LAYOUT STYLES (SIDEBAR & TOP NAV)
   ================================================= */
    /* ==================== STYLE UNTUK BADGE STATUS ==================== */
    .badge.badge-success {
        background-color: #28a745 !important;
        color: white !important;
    }

    .badge.badge-primary {
        background-color: #007bff !important;
        color: white !important;
    }

    .badge.badge-warning {
        background-color: #ffc107 !important;
        color: #212529 !important; /* Teks hitam untuk background kuning */
    }

    .badge.badge-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .badge.badge-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }

    .badge.badge-info {
        background-color: #17a2b8 !important;
        color: white !important;
    }

    .badge.badge-dark {
        background-color: #343a40 !important;
        color: white !important;
    }

    /* Untuk badge yang sudah ada di sistem */
    .badge-selesai {
        background-color: #28a745 !important;
        color: white !important;
    }

    .badge-belum-selesai {
        background-color: #dc3545 !important;
        color: white !important;
    }

    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4cc9f0;
        --light-bg: #f8f9fa;
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 100px;
        --transition-speed: 0.3s;
        --top-nav-height: 70px;
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        min-width: auto !important;
    }

    /* Top Navigation Bar */
    #top-nav {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        right: 0;
        height: var(--top-nav-height);
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all var(--transition-speed) ease;
    }

    #top-nav.expanded {
        left: var(--sidebar-collapsed-width);
    }

    .logo-title-section {
        display: flex;
        align-items: center;
        gap: 15px;
        flex: 1;
        min-width: 0;
    }

    .system-title {
        color: white;
        font-weight: 400;
        font-size: 1.3rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-right: 10px;
    }

    .user-section {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-shrink: 0;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar i {
        font-size: 1.2rem;
        color: white;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .user-role {
        font-size: 0.75rem;
        opacity: 0.9;
    }

    .user-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-actions a {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.85rem;
    }

    .user-actions a:hover {
        color: white;
    }

    .login-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.85rem;
    }

    .login-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }

    /* Hamburger Menu Button */
    #mobileMenuToggle {
        display: none;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 6px;
        width: 40px;
        height: 40px;
        color: white;
        font-size: 1.2rem;
        margin-right: 10px;
        flex-shrink: 0;
    }

    /* Sidebar Styles */
    #sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1001;
        transition: all var(--transition-speed) ease;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }

    #sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar-header {
        padding: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        flex-shrink: 0;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: var(--top-nav-height);
        min-height: var(--top-nav-height);
        width: 100%;
    }

    /* Logo di Sidebar - Kotak Putih dengan Height Sama Top Nav */
    .sidebar-logo-container {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all var(--transition-speed) ease;
        position: relative;
        background: white;
        margin: 0;
        padding: 0;
    }

    .sidebar-logo-full {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: all var(--transition-speed) ease;
        padding: 15px;
        box-sizing: border-box;
    }

    .sidebar-logo-small {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: all var(--transition-speed) ease;
        display: none;
        padding: 10px;
        box-sizing: border-box;
    }

    .sidebar-logo-placeholder {
        width: 100%;
        height: 100%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        font-weight: bold;
        font-size: 1rem;
        text-align: center;
        padding: 15px;
        box-sizing: border-box;
    }

    .sidebar-logo-placeholder-small {
        width: 100%;
        height: 100%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        font-weight: bold;
        font-size: 0.8rem;
        text-align: center;
        padding: 10px;
        box-sizing: border-box;
        display: none;
    }

    /* Ketika sidebar collapsed */
    #sidebar.collapsed .sidebar-logo-full {
        display: none;
    }

    #sidebar.collapsed .sidebar-logo-small {
        display: block;
    }

    #sidebar.collapsed .sidebar-logo-placeholder {
        display: none;
    }

    #sidebar.collapsed .sidebar-logo-placeholder-small {
        display: flex;
    }

    /* Tombol Toggle - Dipindahkan ke bawah logo */
    .toggle-section {
        position: absolute;
        bottom: -45px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1002;
        display: flex;
        justify-content: center;
        width: 100%;
    }

    .toggle-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 6px;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .toggle-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .sidebar-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        margin-top: 60px;
    }

    .sidebar-menu {
        list-style: none;
        margin: 0;
        padding: 0 15px;
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }

    .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
    }

    .sidebar-menu li {
        position: relative;
    }

    .sidebar-menu a {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        white-space: nowrap;
        overflow: hidden;
        border-left: 3px solid transparent;
        font-size: 0.85rem;
        min-height: 40px;
        border-radius: 6px;
        margin: 2px 0;
    }

    .sidebar-menu a:hover, .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border-left-color: var(--accent-color);
    }

    .sidebar-menu i {
        margin-right: 12px;
        font-size: 1rem;
        min-width: 20px;
        text-align: center;
        flex-shrink: 0;
    }

    #sidebar.collapsed .sidebar-menu span {
        opacity: 0;
        width: 0;
        height: 0;
        overflow: hidden;
    }

    #sidebar.collapsed .sidebar-menu i {
        margin-right: 0;
    }

    #sidebar.collapsed .sidebar-menu a {
        justify-content: center;
        padding: 10px 5px;
    }

    .menu-section {
        padding: 8px 15px;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin: 10px 0 5px 0;
        white-space: nowrap;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 4px;
    }

    #sidebar.collapsed .menu-section {
        opacity: 0;
        height: 0;
        padding: 0;
        margin: 0;
        border: none;
    }

    .sidebar-footer {
        padding: 15px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Tooltip untuk menu yang diminimalkan */
    .menu-tooltip {
        position: relative;
    }

    #sidebar.collapsed .menu-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
        margin-left: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        pointer-events: none;
    }

    #sidebar.collapsed .menu-tooltip:hover::after {
        opacity: 1;
        visibility: visible;
        transform: translateY(-50%) translateX(5px);
    }

    #sidebar.collapsed .menu-tooltip::before {
        content: '';
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 6px solid transparent;
        border-right-color: rgba(0, 0, 0, 0.9);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1001;
        margin-left: 3px;
        pointer-events: none;
    }

    #sidebar.collapsed .menu-tooltip:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateY(-50%) translateX(0);
    }

    /* Main Content Styles untuk Dashboard */
    #content {
        margin-left: var(--sidebar-width);
        margin-top: var(--top-nav-height);
        padding: 20px;
        transition: all var(--transition-speed) ease;
        min-height: calc(100vh - var(--top-nav-height));
        background-color: var(--light-bg);
    }

    #content.expanded {
        margin-left: var(--sidebar-collapsed-width);
    }

    /* Content Styling */
    .content-header {
        margin-bottom: 2rem;
    }

    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn {
        border-radius: 6px;
    }

    /* =================================================
    ADAPTASI DASHBOARD FINAL STYLES
    ================================================= */

    /* Override container-fluid untuk dashboard */
    #content .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    /* Override card styles untuk dashboard */
    #content .card {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 0 20px 0 !important;
    }

    #content .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
        color: white !important;
    }

    #content .card-title {
        color: white !important;
        font-weight: 600 !important;
    }

    /* Tabel styles */
    #content table {
        font-size: 12px !important;
    }

    #content table th {
        background-color: #f8f9fc !important;
        color: var(--primary-color) !important;
        font-weight: 600 !important;
    }

    /* Chart container */
    #content .chart-container-fixed {
        height: 300px !important;
        margin: 20px 0 !important;
    }

    /* Print styles tetap dipertahankan */
    @media print {
        #top-nav, #sidebar, .no-print {
            display: none !important;
        }
        
        #content {
            margin-left: 0 !important;
            margin-top: 0 !important;
            padding: 0 !important;
        }
        
        body {
            background: white !important;
        }
    }

    /* Mobile Responsive */
    @media (max-width: 991px) {
        #sidebar {
            width: var(--sidebar-width);
            transform: translateX(-100%);
            z-index: 1050;
            transition: transform var(--transition-speed) ease;
        }
        
        #sidebar.mobile-open {
            transform: translateX(0);
        }
        
        #top-nav {
            left: 0;
            z-index: 1060;
            padding: 0 15px;
        }
        
        #top-nav.expanded {
            left: 0;
        }
        
        #content {
            margin-left: 0;
            margin-top: var(--top-nav-height);
        }
        
        #content.expanded {
            margin-left: 0;
        }
        
        .sidebar-header {
            height: var(--top-nav-height);
            min-height: var(--top-nav-height);
        }
        
        .sidebar-logo-full {
            padding: 12px;
        }
        
        .sidebar-logo-small {
            padding: 8px;
        }
        
        .toggle-section {
            bottom: -40px;
        }
        
        .sidebar-content {
            margin-top: 50px;
        }
        
        /* Perbaikan layout top navigation untuk mobile */
        .logo-title-section {
            flex: 1;
            justify-content: flex-start;
            min-width: 0;
        }
        
        .system-title {
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-right: 0;
        }
        
        .user-details {
            display: none;
        }
        
        .user-actions a span {
            display: none;
        }
        
        .login-btn span {
            display: none;
        }
        
        /* Hamburger Menu */
        #mobileMenuToggle {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        /* Nonaktifkan tooltip di mobile */
        .menu-tooltip::after,
        .menu-tooltip::before {
            display: none !important;
        }
        
        /* Overlay untuk menutup sidebar */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1049;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    }

    /* Responsivitas tambahan untuk layar yang lebih kecil */
    @media (max-width: 576px) {
        #top-nav {
            padding: 0 10px;
        }
        
        .system-title {
            font-size: 1rem;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
        }
        
        .user-avatar i {
            font-size: 1rem;
        }
        
        .user-actions a, .login-btn {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
        
        #mobileMenuToggle {
            width: 35px;
            height: 35px;
            font-size: 1rem;
            margin-right: 10px;
        }
        
        #content {
            padding: 10px !important;
        }
    }

    /* ==================== DASHBOARD CARD STYLES (SAFE VERSION) ==================== */
/* Menggunakan prefix yang unik untuk menghindari konflik */
.db-card {
    border-radius: 6px;
    border: 1px solid #e3e6f0;
    background: #fff;
    margin-bottom: 15px;
    transition: all 0.2s ease;
    overflow: hidden;
    position: relative;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08);
}

.db-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.db-card .db-card-body {
    padding: 12px 15px;
}

.db-card .db-card-icon {
    position: absolute;
    right: 15px;
    top: 12px;
    color: #dddfeb;
    font-size: 20px;
    opacity: 0.7;
}

.db-card .db-card-title {
    font-size: 10px;
    font-weight: 600;
    color: #6e707e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    line-height: 1.2;
}

.db-card .db-card-value {
    font-size: 16px;
    font-weight: 700;
    color: #5a5c69;
    margin-bottom: 4px;
    line-height: 1.2;
}

.db-card .db-card-subtitle {
    font-size: 9px;
    color: #858796;
    margin-bottom: 0;
    line-height: 1.3;
}

.db-card .db-card-badge {
    display: inline-block;
    padding: 2px 6px;
    font-size: 8px;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 10px;
    margin-top: 4px;
}

/* Border kiri dengan warna yang tidak kontras */
.db-card-primary {
    border-left: 3px solid #4e73df;
}

.db-card-success {
    border-left: 3px solid #1cc88a;
}

.db-card-info {
    border-left: 3px solid #36b9cc;
}

.db-card-warning {
    border-left: 3px solid #f6c23e;
}

.db-card-danger {
    border-left: 3px solid #e74a3b;
}

.db-card-secondary {
    border-left: 3px solid #858796;
}

.db-card-dark {
    border-left: 3px solid #5a5c69;
}

/* Untuk print - tetap sederhana */
@media print {
    .db-card {
        break-inside: avoid;
        page-break-inside: avoid;
        border: 0.3pt solid #dee2e6;
        box-shadow: none !important;
        margin-bottom: 4mm;
    }
    
    .db-card .db-card-value {
        font-size: 10pt;
        color: #000 !important;
    }
    
    .db-card .db-card-title {
        font-size: 8pt;
        color: #000 !important;
    }
    
    .db-card .db-card-subtitle {
        font-size: 7pt;
    }
    
    .db-card .db-card-icon {
        color: #ccc !important;
    }
}

/* Responsif untuk mobile */
@media (max-width: 768px) {
    .db-card {
        margin-bottom: 10px;
    }
    
    .db-card .db-card-value {
        font-size: 14px;
    }
    
    .db-card .db-card-icon {
        font-size: 18px;
    }
}
    </style>
</head>
<body>
<div class="a4-preview">
    <div class="container-fluid py-4" id="a4-container">

        <!-- ==================== DASHBOARD SUMMARY CARDS ==================== -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="db-card db-card-primary">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h6 class="db-card-title">Target Omzet Sampai Hari Ini</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalTargetOmzet); ?>
                        </div>
                        <p class="db-card-subtitle">Total target omzet periode ini</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="db-card db-card-success">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h6 class="db-card-title">Realisasi Omzet Sampai Hari Ini</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalRealisasiOmzet); ?>
                        </div>
                        <p class="db-card-subtitle">Total realisasi omzet periode ini</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="db-card db-card-<?php echo $totalGapOmzet >= 0 ? 'success' : 'danger'; ?>">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h6 class="db-card-title">Gap Omzet (Realisasi - Target)</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalGapOmzet); ?>
                        </div>
                        <p class="db-card-subtitle">
                            <span class="db-card-badge badge <?php echo $totalGapOmzet >= 0 ? 'badge-selesai' : 'badge-belum-selesai'; ?>">
                                <?php echo $totalGapOmzet >= 0 ? 'LEBIH TINGGI' : 'LEBIH RENDAH'; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="db-card db-card-info">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h6 class="db-card-title">% Pencapaian Omzet</h6>
                        <div class="db-card-value">
                            <?php echo number_format($totalPercentageOmzet, 1); ?>%
                        </div>
                        <p class="db-card-subtitle">
                            <span class="db-card-badge badge <?php 
                                if ($totalPercentageOmzet >= 100) echo 'badge-selesai';
                                elseif ($totalPercentageOmzet >= 80) echo 'badge bg-warning text-dark';
                                else echo 'badge-belum-selesai';
                            ?>">
                                <?php 
                                if ($totalPercentageOmzet >= 100) echo 'ACHIEVED';
                                elseif ($totalPercentageOmzet >= 80) echo 'HAMPIR ACHIEVE';
                                else echo 'BELUM ACHIEVE';
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== CARD TAMBAHAN: OMZET BERSIH ==================== -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="db-card db-card-info">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-filter"></i>
                        </div>
                        <h6 class="db-card-title">Omzet Bersih</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalRealisasiOmzetBersih); ?>
                        </div>
                        <p class="db-card-subtitle">Target: Rp <?php echo formatFullNumber($totalTargetOmzetBersih); ?></p>
                        <p class="db-card-subtitle">
                            <span class="db-card-badge badge <?php echo $totalRealisasiOmzetBersih >= $totalTargetOmzetBersih ? 'badge-selesai' : 'badge-belum-selesai'; ?>">
                                <?php 
                                if ($totalTargetOmzetBersih > 0) {
                                    $percentageOmzetBersih = ($totalRealisasiOmzetBersih / $totalTargetOmzetBersih) * 100;
                                    echo number_format($percentageOmzetBersih, 1) . '%';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="db-card db-card-warning">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h6 class="db-card-title">Rasio HPP/Omzet</h6>
                        <div class="db-card-value">
                            <?php 
                            if ($totalRealisasiOmzetBersih > 0) {
                                $rasioHPP = ($totalRealisasiHPP / $totalRealisasiOmzetBersih) * 100;
                                echo number_format($rasioHPP, 1) . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </div>
                        <p class="db-card-subtitle">
                            HPP: Rp <?php echo formatFullNumber($totalRealisasiHPP); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="db-card db-card-secondary">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h6 class="db-card-title">Rasio Biaya/Omzet</h6>
                        <div class="db-card-value">
                            <?php 
                            if ($totalRealisasiOmzetBersih > 0) {
                                $rasioBiaya = ($totalRealisasiBiaya / $totalRealisasiOmzetBersih) * 100;
                                echo number_format($rasioBiaya, 1) . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </div>
                        <p class="db-card-subtitle">
                            Biaya: Rp <?php echo formatFullNumber($totalRealisasiBiaya); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SECOND ROW CARDS ==================== -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="db-card db-card-warning">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h6 class="db-card-title">Total Omzet</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalRealisasiOmzet); ?>
                        </div>
                        <p class="db-card-subtitle">Target: Rp <?php echo formatFullNumber($totalTargetOmzet); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="db-card db-card-danger">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h6 class="db-card-title">Total HPP</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalRealisasiHPP); ?>
                        </div>
                        <p class="db-card-subtitle">Target: Rp <?php echo formatFullNumber($totalTargetHPP); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="db-card db-card-secondary">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h6 class="db-card-title">Total Biaya</h6>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($totalRealisasiBiaya); ?>
                        </div>
                        <p class="db-card-subtitle">Target: Rp <?php echo formatFullNumber($totalTargetBiaya); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="db-card db-card-dark">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h6 class="db-card-title">Margin Bersih</h6>
                        <?php
                        // PERHITUNGAN DIPERBAIKI: Margin Bersih = Omzet Bersih - HPP - Biaya
                        // Gunakan $totalRealisasiOmzetBersih (dari query khusus) atau fallback ke $totalRealisasiOmzet
                        $marginBersihRealisasi = $totalRealisasiOmzetBersih - $totalRealisasiHPP - $totalRealisasiBiaya;
                        $marginBersihTarget = $totalTargetOmzetBersih - $totalTargetHPP - $totalTargetBiaya;
                        ?>
                        <div class="db-card-value">
                            Rp <?php echo formatFullNumber($marginBersihRealisasi); ?>
                        </div>
                        <p class="db-card-subtitle">Target: Rp <?php echo formatFullNumber($marginBersihTarget); ?></p>
                        <p class="db-card-subtitle">
                            <span class="db-card-badge badge <?php echo $marginBersihRealisasi >= $marginBersihTarget ? 'badge-selesai' : 'badge-belum-selesai'; ?>">
                                <?php 
                                if ($marginBersihTarget > 0) {
                                    $percentageMargin = ($marginBersihRealisasi / $marginBersihTarget) * 100;
                                    echo number_format($percentageMargin, 1) . '%';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== THIRD ROW CARDS ==================== -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="db-card db-card-primary">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h6 class="db-card-title">Jumlah Entitas</h6>
                        <div class="db-card-value">
                            <?php echo $totalEntitas; ?> Entitas
                        </div>
                        <p class="db-card-subtitle">
                            <?php 
                            $entitasNames = array_keys($entitasData);
                            if (count($entitasNames) <= 2) {
                                echo implode(', ', $entitasNames);
                            } else {
                                echo $entitasNames[0] . ', ' . $entitasNames[1] . '...';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="db-card db-card-info">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h6 class="db-card-title">Periode Analisis</h6>
                        <div class="db-card-value">
                            <?php echo $totalHari; ?> Hari
                        </div>
                        <p class="db-card-subtitle">
                            <?php 
                            if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                echo date('d M Y', strtotime($tanggal_awal)) . ' s/d ' . date('d M Y', strtotime($tanggal_akhir));
                            } elseif (!empty($tanggal_awal)) {
                                echo 'Dari ' . date('d M Y', strtotime($tanggal_awal));
                            } elseif (!empty($tanggal_akhir)) {
                                echo 'Sampai ' . date('d M Y', strtotime($tanggal_akhir));
                            } else {
                                echo 'Semua Periode';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="db-card db-card-success">
                    <div class="db-card-body">
                        <div class="db-card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h6 class="db-card-title">Produk Terjual</h6>
                        <div class="db-card-value">
                            <?php echo $totalProdukJumlah; ?> Unit
                        </div>
                        <p class="db-card-subtitle">
                            Nilai: Rp <?php echo formatFullNumber($totalProdukNilai); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Detail per Jenis Akun -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Goal vs Reality Laporan Keuangan</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Akun</th>
                                        <th>Target</th>
                                        <th>% Target</th>
                                        <th>Realisasi</th>
                                        <th>% Realisasi</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    
                                    // Cari nilai Target Omzet Bersih dari data akun
                                    $targetOmzetBersih = 0;
                                    $realisasiOmzetBersih = 0;
                                    
                                    foreach ($akunData as $akunNama => $data) {
                                        $akunNamaNormalized = strtoupper(trim($akunNama));
                                        // Cari akun "Omzet Bersih"
                                        if (strpos($akunNamaNormalized, 'OMZET BERSIH') !== false || 
                                            strpos($akunNamaNormalized, 'OMZETBERSIH') !== false ||
                                            $akunNamaNormalized === 'OMZET BERSIH') {
                                            $targetOmzetBersih = $data['total_target'];
                                            $realisasiOmzetBersih = $data['total_realisasi'];
                                            break;
                                        }
                                    }
                                    
                                    // Jika tidak ditemukan "Omzet Bersih", coba cari di data_report
                                    if ($realisasiOmzetBersih == 0) {
                                        $sqlOmzetBersih = "SELECT SUM(nilai_realisasi) as total_realisasi 
                                                        FROM data_report 
                                                        WHERE LOWER(nama_akun) LIKE '%omzet bersih%' 
                                                        OR LOWER(nama_akun) LIKE '%omzetbersih%'";
                                        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                            $sqlOmzetBersih .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                            $paramsOmzetBersih = [$tanggal_awal, $tanggal_akhir];
                                        }
                                        
                                        try {
                                            $stmtOmzetBersih = $pdo->prepare($sqlOmzetBersih);
                                            if (isset($paramsOmzetBersih)) {
                                                $stmtOmzetBersih->execute($paramsOmzetBersih);
                                            } else {
                                                $stmtOmzetBersih->execute();
                                            }
                                            $dataOmzetBersih = $stmtOmzetBersih->fetch(PDO::FETCH_ASSOC);
                                            
                                            if ($dataOmzetBersih) {
                                                $realisasiOmzetBersih = (float)$dataOmzetBersih['total_realisasi'];
                                            }
                                        } catch (PDOException $e) {
                                            // Tidak perlu ditampilkan error
                                        }
                                    }
                                    
                                    // Jika masih tidak ditemukan, gunakan total realisasi omzet dari data yang sudah diproses
                                    if ($realisasiOmzetBersih == 0) {
                                        // Cari akun "Omzet" (bukan Omzet Bersih) untuk mendapatkan realisasi
                                        foreach ($akunData as $akunNama => $data) {
                                            $akunNamaNormalized = strtoupper(trim($akunNama));
                                            if ($akunNamaNormalized === 'OMZET') {
                                                $realisasiOmzetBersih = $data['total_realisasi'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Hitung ulang total realisasi dari semua data akun
                                    $totalRealisasiAll = 0;
                                    foreach ($akunData as $akunNama => $data) {
                                        $totalRealisasiAll += $data['total_realisasi'];
                                    }
                                    
                                    foreach ($akunData as $akunNama => $data): 
                                        $gapAkun = $data['total_realisasi'] - $data['total_target'];
                                        $percentageAkun = $data['total_target'] > 0 ? ($data['total_realisasi'] / $data['total_target']) * 100 : 0;
                                        
                                        // Hitung % Target sesuai dengan rumus yang benar:
                                        $percentageTarget = 0;
                                        $denominatorName = '';
                                        
                                        // Hitung % Realisasi
                                        $percentageRealisasi = 0;
                                        $denominatorNameRealisasi = '';
                                        
                                        // Normalisasi nama akun untuk perbandingan case-insensitive
                                        $akunNamaNormalized = strtoupper(trim($akunNama));

                                        // Cek apakah ini akun "Omzet Bersih"
                                        $isOmzetBersih = (strpos($akunNamaNormalized, 'OMZET BERSIH') !== false || 
                                                        strpos($akunNamaNormalized, 'OMZETBERSIH') !== false);

                                        // Cek apakah ini akun yang mengandung kata "Omzet" (lebih fleksibel)
                                        $isOmzet = (strpos($akunNamaNormalized, 'OMZET') !== false);

                                        // Cek apakah ini akun khusus (Omzet, Omzet Bersih, NPM/EBITDA, ZISWAF)
                                        $isAkunKhusus = false;
                                        $kategoriAkun = '';

                                        if ($isOmzet || $isOmzetBersih) {  // Semua akun yang mengandung "OMZET"
                                            $isAkunKhusus = true;
                                            $kategoriAkun = 'PENDAPATAN';
                                        } 
                                        elseif ($akunNamaNormalized === 'PENDAPATAN LAIN') {
                                            $isAkunKhusus = true;
                                            $kategoriAkun = 'PENDAPATAN';
                                        } 
                                        elseif (strpos($akunNamaNormalized, 'NPM') !== false || 
                                                strpos($akunNamaNormalized, 'EBITDA') !== false ||
                                                strpos($akunNamaNormalized, 'PROFIT') !== false ||
                                                strpos($akunNamaNormalized, 'LABA') !== false) {
                                            $isAkunKhusus = true;
                                            $kategoriAkun = 'PROFIT';
                                        } 
                                        elseif (strpos($akunNamaNormalized, 'ZISWAF') !== false || 
                                                strpos($akunNamaNormalized, 'ZAKAT') !== false ||
                                                strpos($akunNamaNormalized, 'INFAQ') !== false ||
                                                strpos($akunNamaNormalized, 'SEDEKAH') !== false ||
                                                strpos($akunNamaNormalized, 'WAKAF') !== false) {
                                            $isAkunKhusus = true;
                                            $kategoriAkun = 'ZISWAF';
                                        } 
                                        else {
                                            $isAkunKhusus = false;
                                            $kategoriAkun = 'BIAYA/LAINNYA';
                                        }
                                        
                                        // ==================== PERHITUNGAN % TARGET ====================
                                        // 1. Jika akun adalah 'Omzet' (bukan Omzet Bersih)
                                        if (in_array($akunNamaNormalized, ['OMZET', 'Omzet Reguler', 'Omzet Deadstock']) && !$isOmzetBersih) {
                                            // Omzet = 100% (Target Omzet / Target Omzet)
                                            $percentageTarget = 100;
                                            $denominatorName = 'OMZET';
                                        }
                                        // 2. Jika akun adalah 'Omzet Bersih'
                                        elseif ($isOmzetBersih) {
                                            // Omzet Bersih = 100% (Target Omzet Bersih / Target Omzet Bersih)
                                            $percentageTarget = 100;
                                            $denominatorName = 'Omzet Bersih';
                                        }
                                        // 3. Jika akun adalah 'HPP'
                                        elseif ($akunNamaNormalized === 'HPP') {
                                            // HPP = Target HPP / Target Omzet Bersih
                                            if ($targetOmzetBersih > 0) {
                                                $percentageTarget = ($data['total_target'] / $targetOmzetBersih) * 100;
                                            }
                                            $denominatorName = 'Omzet Bersih';
                                        }
                                        // 4. Jika akun adalah 'NPM', 'EBITDA', dll (termasuk ZISWAF)
                                        elseif (in_array($akunNamaNormalized, ['NPM', 'EBITDA','NET PROFIT MARGIN', 'NET PROFIT', 'PROFIT MARGIN']) ||
                                            strpos($akunNamaNormalized, 'ZISWAF') !== false) {
                                            // NPM/EBITDA/ZISWAF = Target / Target Omzet Bersih
                                            if ($targetOmzetBersih > 0) {
                                                $percentageTarget = ($data['total_target'] / $targetOmzetBersih) * 100;
                                            }
                                            $denominatorName = 'Omzet Bersih';
                                        }
                                        // 5. Untuk semua akun lainnya -> % terhadap Omzet Bersih
                                        else {
                                            // Target Akun / Target Omzet Bersih
                                            if ($targetOmzetBersih > 0) {
                                                $percentageTarget = ($data['total_target'] / $targetOmzetBersih) * 100;
                                            }
                                            $denominatorName = 'Omzet Bersih';
                                        }
                                        
                                        // ==================== PERHITUNGAN % REALISASI ====================
                                        // 1. Jika akun adalah 'Omzet' (bukan Omzet Bersih)
                                        //(in_array($akunNamaNormalized, ['NPM', 'EBITDA','NET PROFIT MARGIN', 'NET PROFIT', 'PROFIT MARGIN'])
                                        if (in_array($akunNamaNormalized, ['OMZET', 'Omzet Reguler', 'Omzet Deadstock']) && !$isOmzetBersih) {
                                            // Omzet = Realisasi / Target (x 100%)
                                            if ($data['total_target'] > 0) {
                                                $percentageRealisasi = ($data['total_realisasi'] / $data['total_target']) * 100;
                                            } else {
                                                $percentageRealisasi = 0;
                                            }
                                            $denominatorNameRealisasi = 'Target';
                                        }
                                        // 2. Jika akun adalah 'Omzet Bersih'
                                        elseif ($isOmzetBersih) {
                                            // Omzet Bersih = Realisasi / Target (x 100%)
                                            if ($data['total_target'] > 0) {
                                                $percentageRealisasi = ($data['total_realisasi'] / $data['total_target']) * 100;
                                            } else {
                                                $percentageRealisasi = 0;
                                            }
                                            $denominatorNameRealisasi = 'Target';
                                        }
                                        // 3. Jika akun adalah 'HPP'
                                        elseif ($akunNamaNormalized === 'HPP') {
                                            // HPP = Realisasi HPP / Realisasi Omzet Bersih
                                            if ($realisasiOmzetBersih > 0) {
                                                $percentageRealisasi = ($data['total_realisasi'] / $realisasiOmzetBersih) * 100;
                                            } else {
                                                $percentageRealisasi = 0;
                                            }
                                            $denominatorNameRealisasi = 'Omzet Bersih (Realisasi)';
                                        }
                                        // 4. Jika akun adalah 'NPM', 'EBITDA', dll
                                        elseif (in_array($akunNamaNormalized, ['NPM', 'EBITDA','NET PROFIT MARGIN', 'NET PROFIT', 'PROFIT MARGIN'])) {
                                            // NPM/EBITDA = Realisasi / Target (x 100%)
                                            if ($data['total_target'] > 0) {
                                                $percentageRealisasi = ($data['total_realisasi'] / $data['total_target']) * 100;
                                            } else {
                                                $percentageRealisasi = 0;
                                            }
                                            $denominatorNameRealisasi = 'Target';
                                        }
                                        // 5. Jika akun adalah 'ZISWAF' atau sejenisnya
                                        elseif (strpos($akunNamaNormalized, 'ZISWAF') !== false || 
                                            strpos($akunNamaNormalized, 'ZAKAT') !== false ||
                                            strpos($akunNamaNormalized, 'INFAQ') !== false ||
                                            strpos($akunNamaNormalized, 'SEDEKAH') !== false ||
                                            strpos($akunNamaNormalized, 'WAKAF') !== false) {
                                            // ZISWAF = Realisasi / Target (x 100%)
                                            if ($data['total_target'] > 0) {
                                                $percentageRealisasi = ($data['total_realisasi'] / $data['total_target']) * 100;
                                            } else {
                                                $percentageRealisasi = 0;
                                            }
                                            $denominatorNameRealisasi = 'Target';
                                        }
                                        // 6. Untuk semua akun lainnya -> % terhadap Omzet Bersih
                                        else {
                                            // Realisasi Akun / Realisasi Omzet Bersih
                                            if ($realisasiOmzetBersih > 0) {
                                                $percentageRealisasi = ($data['total_realisasi'] / $realisasiOmzetBersih) * 100;
                                            } else {
                                                $percentageRealisasi = 0;
                                            }
                                            $denominatorNameRealisasi = 'Omzet Bersih (Realisasi)';
                                        }
                                        
                                        // ==================== LOGIKA STATUS ====================
                                        $statusText = '';
                                        $statusClass = '';
                                        $statusBadgeClass = '';
                                        
                                        // RULE 1: Untuk akun khusus (Omzet, Omzet Bersih, NPM/EBITDA, ZISWAF)
                                        if ($isAkunKhusus) {
                                            if ($data['total_target'] <= $data['total_realisasi']) {
                                                $statusText = 'ACHIEVE';
                                                $statusClass = 'text-success';
                                                $statusBadgeClass = 'badge-selesai';
                                            } else {
                                                $statusText = 'NOT ACHIEVE';
                                                $statusClass = 'text-danger';
                                                $statusBadgeClass = 'badge-belum-selesai';
                                            }
                                        }
                                        // RULE 2: Untuk akun lainnya (Biaya dan akun non-khusus)
                                        else {
                                            if ($percentageTarget <= $percentageRealisasi) {
                                                $statusText = 'OVER BUDGET';
                                                $statusClass = 'text-danger';
                                                $statusBadgeClass = 'badge-belum-selesai';
                                            } else {
                                                $statusText = 'EFISIEN';
                                                $statusClass = 'text-success';
                                                $statusBadgeClass = 'badge-selesai';
                                            }
                                        }
                                        
                                        // Tampilkan dalam format yang sesuai
                                        $percentageTargetFormatted = number_format($percentageTarget, 2) . '%';
                                        $percentageRealisasiFormatted = number_format($percentageRealisasi, 2) . '%';
                                        
                                        // Warna untuk % Target
                                        $percentageTargetClass = '';
                                        if ($percentageTarget <= 10) {
                                            $percentageTargetClass = 'text-success'; // Hijau untuk persentase kecil
                                        } elseif ($percentageTarget <= 25) {
                                            $percentageTargetClass = 'text-warning'; // Kuning untuk persentase sedang
                                        } else {
                                            $percentageTargetClass = 'text-danger'; // Merah untuk persentase besar
                                        }
                                        
                                        // Warna untuk % Realisasi
                                        $percentageRealisasiClass = '';
                                        // Untuk akun khusus, warna berdasarkan pencapaian target
                                        if ($isAkunKhusus) {
                                            if ($percentageRealisasi >= 100) {
                                                $percentageRealisasiClass = 'text-success'; // Hijau jika mencapai/melampaui target
                                            } elseif ($percentageRealisasi >= 80) {
                                                $percentageRealisasiClass = 'text-warning'; // Kuning jika 80-99%
                                            } else {
                                                $percentageRealisasiClass = 'text-danger'; // Merah jika < 80%
                                            }
                                        } else {
                                            // Untuk akun biaya/lainnya, warna berdasarkan efisiensi
                                            if ($percentageRealisasi <= 10) {
                                                $percentageRealisasiClass = 'text-success'; // Hijau untuk persentase kecil
                                            } elseif ($percentageRealisasi <= 25) {
                                                $percentageRealisasiClass = 'text-warning'; // Kuning untuk persentase sedang
                                            } else {
                                                $percentageRealisasiClass = 'text-danger'; // Merah untuk persentase besar
                                            }
                                        }
                                        
                                        // Untuk Omzet dan Omzet Bersih di kolom % Target, selalu warna hijau karena 100%
                                        if ($akunNamaNormalized === 'OMZET' || $isOmzetBersih) {
                                            $percentageTargetClass = 'text-success';
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($akunNama); ?></strong>
                                            </td>
                                            <td class="text-end"><?php echo formatFullNumber($data['total_target']); ?></td>
                                            <td class="text-end <?php echo $percentageTargetClass; ?>">
                                                <strong><?php echo $percentageTargetFormatted; ?></strong>
                                            </td>
                                            <td class="text-end"><?php echo formatFullNumber($data['total_realisasi']); ?></td>
                                            <td class="text-end <?php echo $percentageRealisasiClass; ?>">
                                                <strong><?php echo $percentageRealisasiFormatted; ?></strong>
                                            </td>
                                            <td class="text-end <?php echo $gapAkun >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatFullNumber($gapAkun); ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($percentageAkun, 1); ?>%</td>
                                            <td class="<?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>                            
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Goal vs Reality Laporan Keuangan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- GRAFIK-GRAFIK OMZET -->
        
        <!-- 1. Grafik Tren Target dan Realisasi Omzet per Hari -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Target dan Realisasi Omzet per Hari</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed" style="height: 250px !important;">
                            <canvas id="chartTrenOmzetHarian"></canvas>
                        </div>
                        <!-- Tabel Data Harian Omzet -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Target Omzet</th>
                                        <th>Realisasi Omzet</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalTargetHarian = 0;
                                    $totalRealisasiHarian = 0;
                                    $totalGapHarian = 0;
                                    
                                    // Urutkan tanggal
                                    ksort($tanggalDataOmzet);
                                    
                                    foreach ($tanggalDataOmzet as $tanggal => $data):
                                        $target = $data['target'];
                                        $realisasi = $data['realisasi'];
                                        $gap = $realisasi - $target;
                                        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                        
                                        // Hitung rata-rata per hari
                                        $targetHarian = $target;
                                        $realisasiHarian = $realisasi;
                                        
                                        $totalTargetHarian += $target;
                                        $totalRealisasiHarian += $realisasi;
                                        $totalGapHarian += $gap;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo date('d M Y', strtotime($tanggal)); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                        <td class="text-end <?php echo $gap >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gap); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                        <td>
                                            <?php echo $percentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>                                       
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetHarian); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiHarian); ?></strong></td>
                                        <td class="text-end <?php echo $totalGapHarian >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapHarian); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageHarian = $totalTargetHarian > 0 ? ($totalRealisasiHarian / $totalTargetHarian) * 100 : 0;
                                                echo number_format($totalPercentageHarian, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php echo $totalPercentageHarian >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Target dan Realisasi Omzet Per Hari')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Grafik Tren Realisasi Omzet per Hari per Entitas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Realisasi Omzet Per Hari Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartRealisasiOmzetPerEntitas"></canvas>
                        </div>
                        <!-- Tabel Data Realisasi Omzet per Hari per Entitas -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-condensed">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Total Realisasi</th>
                                        <?php
                                        // Ambil semua entitas yang ada data omzetnya
                                        $entitasList = array_keys($entitasDataOmzet);
                                        sort($entitasList); // Urutkan berdasarkan nama entitas
                                        
                                        foreach ($entitasList as $entitas):
                                        ?>
                                        <th class="text-center"><?php echo htmlspecialchars($entitas); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalRealisasiHarian = 0;
                                    
                                    // Inisialisasi array untuk menyimpan total per entitas
                                    $totalPerEntitas = [];
                                    foreach ($entitasList as $entitas) {
                                        $totalPerEntitas[$entitas] = 0;
                                    }
                                    
                                    // Urutkan tanggal
                                    ksort($tanggalDataOmzet);
                                    
                                    foreach ($tanggalDataOmzet as $tanggal => $dataTotal):
                                        $realisasiTotal = $dataTotal['realisasi'];
                                        
                                        $totalRealisasiHarian += $realisasiTotal;
                                        
                                        // Inisialisasi dengan 0 untuk semua entitas
                                        $realisasiPerEntitas = [];
                                        foreach ($entitasList as $entitas) {
                                            $realisasiPerEntitas[$entitas] = 0;
                                        }
                                        
                                        // Isi data realisasi per entitas untuk tanggal ini
                                        foreach ($entitasDataOmzet as $entitas => $dataEntitas) {
                                            foreach ($dataEntitas['detail'] as $detail) {
                                                if ($detail['tanggal'] == $tanggal) {
                                                    $realisasiPerEntitas[$entitas] = $detail['realisasi'];
                                                    
                                                    // Akumulasi total per entitas
                                                    $totalPerEntitas[$entitas] += $detail['realisasi'];
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo date('d M Y', strtotime($tanggal)); ?></strong></td>
                                        
                                        <!-- Kolom Total Realisasi -->
                                        <td class="text-end text-primary">
                                            <strong><?php echo formatFullNumber($realisasiTotal); ?></strong>
                                        </td>
                                        
                                        <!-- Kolom untuk setiap entitas -->
                                        <?php foreach ($entitasList as $entitas): 
                                            $realisasiEntitas = $realisasiPerEntitas[$entitas];
                                        ?>
                                        <td class="text-end">
                                            <?php if ($realisasiEntitas > 0): ?>
                                                <?php echo formatFullNumber($realisasiEntitas); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                                        
                                        <!-- Total Realisasi -->
                                        <td class="text-end">
                                            <strong class="text-primary"><?php echo formatFullNumber($totalRealisasiHarian); ?></strong>
                                        </td>
                                        
                                        <!-- Total per Entitas -->
                                        <?php foreach ($entitasList as $entitas): 
                                            $totalEntitas = $totalPerEntitas[$entitas];
                                        ?>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalEntitas); ?></strong>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    
                                    <!-- Baris Rata-rata Harian -->
                                    <tr class="table-secondary">
                                        <td colspan="2" class="text-center"><strong>RATA HARIAN</strong></td>
                                        
                                        <!-- Rata-rata Total Harian -->
                                        <td class="text-end">
                                            <strong class="text-primary">
                                                <?php 
                                                $rataRealisasiHarian = count($tanggalDataOmzet) > 0 ? $totalRealisasiHarian / count($tanggalDataOmzet) : 0;
                                                echo formatFullNumber($rataRealisasiHarian);
                                                ?>
                                            </strong>
                                        </td>
                                        
                                        <!-- Rata-rata per Entitas -->
                                        <?php foreach ($entitasList as $entitas): 
                                            $rataEntitas = count($tanggalDataOmzet) > 0 ? $totalPerEntitas[$entitas] / count($tanggalDataOmzet) : 0;
                                        ?>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($rataEntitas); ?></strong>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Realisasi Omzet per Hari Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Grafik Batang Target dan Realisasi Omzet Per Bulan -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Target dan Realisasi Omzet Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                        <?php if (!empty($bulanDataOmzet)): ?>
                            <div class="chart-container-fixed">
                                <canvas id="chartOmzetBulanan"></canvas>
                            </div>
                            <!-- Tabel Data Bulanan -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Bulan</th>
                                            <th>Target Omzet</th>
                                            <th>Realisasi Omzet</th>
                                            <th>Gap</th>
                                            <th>% Pencapaian</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Gunakan $monthsToShow yang sudah dibuat berdasarkan filter
                                        $no = 1;
                                        $totalTargetBulanan = 0;
                                        $totalRealisasiBulanan = 0;
                                        $totalGapBulanan = 0;
                                        
                                        // Siapkan data untuk grafik
                                        $labelsOmzet = [];
                                        $targetOmzetData = [];
                                        $realisasiOmzetData = [];
                                        
                                        foreach ($monthsToShow as $monthKey):
                                            $monthParts = explode('-', $monthKey);
                                            if (count($monthParts) !== 2) continue;
                                            
                                            $tahun = $monthParts[0];
                                            $bulanAngka = (int)$monthParts[1];
                                            
                                            // Dapatkan data dari $bulanDataOmzet
                                            $target = isset($bulanDataOmzet[$monthKey]) ? $bulanDataOmzet[$monthKey]['target'] : 0;
                                            $realisasi = isset($bulanDataOmzet[$monthKey]) ? $bulanDataOmzet[$monthKey]['realisasi'] : 0;
                                            $gap = $realisasi - $target;
                                            $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                            
                                            $monthName = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                            
                                            // Simpan untuk grafik
                                            $labelsOmzet[] = $monthName . ' ' . $tahun;
                                            $targetOmzetData[] = $target / 1000000; // Dalam jutaan
                                            $realisasiOmzetData[] = $realisasi / 1000000;
                                            
                                            $totalTargetBulanan += $target;
                                            $totalRealisasiBulanan += $realisasi;
                                            $totalGapBulanan += $gap;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($monthName . ' ' . $tahun); ?></strong></td>
                                            <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                            <td class="text-end <?php echo $gap >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatFullNumber($gap); ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                            <td>
                                                <?php echo $percentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBulanan); ?></strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBulanan); ?></strong></td>
                                            <td class="text-end <?php echo $totalGapBulanan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo formatFullNumber($totalGapBulanan); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <strong>
                                                    <?php 
                                                    $totalPercentageBulanan = $totalTargetBulanan > 0 ? ($totalRealisasiBulanan / $totalTargetBulanan) * 100 : 0;
                                                    echo number_format($totalPercentageBulanan, 1); ?>%
                                                </strong>
                                            </td>
                                            <td>
                                                <?php echo $totalPercentageBulanan >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>                              
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada data omzet untuk periode yang dipilih.
                            </div>
                        <?php endif; ?>
                        
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Target dan Realisasi Omzet Per Bulan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Grafik Tren Omzet Per Tahun -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Omzet Per Tahun</h5>
                    </div>
                    <div class="card-body p-2">
                        <?php
                        // Proses data omzet per tahun dari data yang sudah ada
                        $tahunDataOmzet = [];
                        
                        foreach ($dataReport as $row) {
                            if ($row['nama_akun'] === 'OMZET') {
                                $tahun = date('Y', strtotime($row['tanggal']));
                                $target = (float)$row['nilai_target'];
                                $realisasi = (float)$row['nilai_realisasi'];
                                
                                if (!isset($tahunDataOmzet[$tahun])) {
                                    $tahunDataOmzet[$tahun] = [
                                        'target' => 0,
                                        'realisasi' => 0
                                    ];
                                }
                                
                                $tahunDataOmzet[$tahun]['target'] += $target;
                                $tahunDataOmzet[$tahun]['realisasi'] += $realisasi;
                            }
                        }
                        
                        // Urutkan berdasarkan tahun
                        ksort($tahunDataOmzet);
                        
                        // Jika tidak ada data tahunan, coba ambil dari data bulanan
                        if (empty($tahunDataOmzet) && !empty($bulanDataOmzet)) {
                            foreach ($bulanDataOmzet as $monthKey => $data) {
                                $tahun = substr($monthKey, 0, 4);
                                if (!isset($tahunDataOmzet[$tahun])) {
                                    $tahunDataOmzet[$tahun] = [
                                        'target' => 0,
                                        'realisasi' => 0
                                    ];
                                }
                                $tahunDataOmzet[$tahun]['target'] += $data['target'];
                                $tahunDataOmzet[$tahun]['realisasi'] += $data['realisasi'];
                            }
                            ksort($tahunDataOmzet);
                        }
                        ?>
                        
                        <div class="chart-container-fixed">
                            <canvas id="chartOmzetTahunan"></canvas>
                        </div>
                        
                        <!-- Tabel Data Tahunan -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tahun</th>
                                        <th>Target Omzet</th>
                                        <th>Realisasi Omzet</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalTargetTahunan = 0;
                                    $totalRealisasiTahunan = 0;
                                    $totalGapTahunan = 0;
                                    $totalRataBulanan = 0;
                                    $totalRataHarian = 0;
                                    $tahunCount = count($tahunDataOmzet);
                                    
                                    foreach ($tahunDataOmzet as $tahun => $data):
                                        $target = $data['target'];
                                        $realisasi = $data['realisasi'];
                                        $gap = $realisasi - $target;
                                        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                        
                                        // Hitung rata-rata bulanan (asumsi 12 bulan)
                                        $rataBulanan = $realisasi / 12;
                                        
                                        // Hitung rata-rata harian (asumsi 365 hari, kecuali tahun kabisat)
                                        $daysInYear = (($tahun % 4 == 0 && $tahun % 100 != 0) || $tahun % 400 == 0) ? 366 : 365;
                                        $rataHarian = $realisasi / $daysInYear;
                                        
                                        $totalTargetTahunan += $target;
                                        $totalRealisasiTahunan += $realisasi;
                                        $totalGapTahunan += $gap;
                                        $totalRataBulanan += $rataBulanan;
                                        $totalRataHarian += $rataHarian;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($tahun); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                        <td class="text-end <?php echo $gap >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gap); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                        <td>
                                            <?php echo $percentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($tahunDataOmzet)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada data tahunan yang tersedia</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($tahunDataOmzet)): ?>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($tahunCount > 0 ? $totalTargetTahunan / $tahunCount : 0); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($tahunCount > 0 ? $totalRealisasiTahunan / $tahunCount : 0); ?></strong></td>
                                        <td class="text-end <?php echo $totalGapTahunan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($tahunCount > 0 ? $totalGapTahunan / $tahunCount : 0); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPercentage = $totalTargetTahunan > 0 ? ($totalRealisasiTahunan / $totalTargetTahunan) * 100 : 0;
                                                echo number_format($rataPercentage, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php echo $rataPercentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Omzet Per Tahun')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
                
        <!-- 4. Grafik Batang Target dan Realisasi Omzet Per Entitas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Target dan Realisasi Omzet Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartOmzetEntitas"></canvas>
                        </div>
                        <!-- <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Entitas</th>
                                        <th>Target Omzet</th>
                                        <th>% dari Total Target</th>
                                        <th>Realisasi Omzet</th>
                                        <th>% dari Total Realisasi</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalGapEntitasOmzet = 0;
                                    $totalTargetEntitasOmzet = 0;
                                    $totalRealisasiEntitasOmzet = 0;
                                    $totalRataHarianEntitas = 0;
                                    $totalHariEntitas = 0;
                                    
                                    // Urutkan entitas berdasarkan realisasi tertinggi
                                    uasort($entitasDataOmzet, function($a, $b) {
                                        return $b['total_realisasi'] <=> $a['total_realisasi'];
                                    });
                                    
                                    foreach ($entitasDataOmzet as $entitas => $data):
                                        $targetEntitas = $data['total_target'];
                                        $realisasiEntitas = $data['total_realisasi'];
                                        $gapEntitas = $realisasiEntitas - $targetEntitas;
                                        $percentageEntitas = $targetEntitas > 0 ? ($realisasiEntitas / $targetEntitas) * 100 : 0;
                                        $persenTargetTotal = $totalTargetOmzet > 0 ? ($targetEntitas / $totalTargetOmzet) * 100 : 0;
                                        $persenRealisasiTotal = $totalRealisasiOmzet > 0 ? ($realisasiEntitas / $totalRealisasiOmzet) * 100 : 0;
                                        
                                        // Hitung rata-rata harian untuk entitas ini
                                        $jumlahHariEntitas = count(array_unique(array_column($data['detail'], 'tanggal')));
                                        $rataHarianEntitas = $jumlahHariEntitas > 0 ? $realisasiEntitas / $jumlahHariEntitas : 0;
                                        
                                        // Hitung trend (hari dengan realisasi tertinggi)
                                        $detailHarian = [];
                                        foreach ($data['detail'] as $detail) {
                                            $tanggal = $detail['tanggal'];
                                            if (!isset($detailHarian[$tanggal])) {
                                                $detailHarian[$tanggal] = 0;
                                            }
                                            $detailHarian[$tanggal] += $detail['realisasi'];
                                        }
                                        
                                        if (!empty($detailHarian)) {
                                            $hariTertinggi = array_search(max($detailHarian), $detailHarian);
                                            $nilaiTertinggi = max($detailHarian);
                                            $hariTerendah = array_search(min($detailHarian), $detailHarian);
                                            $nilaiTerendah = min($detailHarian);
                                        }
                                        
                                        $totalGapEntitasOmzet += $gapEntitas;
                                        $totalTargetEntitasOmzet += $targetEntitas;
                                        $totalRealisasiEntitasOmzet += $realisasiEntitas;
                                        $totalRataHarianEntitas += $rataHarianEntitas;
                                        $totalHariEntitas += $jumlahHariEntitas;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetEntitas); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="ms-2"><?php echo number_format($persenTargetTotal, 1); ?>%</span>
                                            </div>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiEntitas); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="ms-2"><?php echo number_format($persenRealisasiTotal, 1); ?>%</span>
                                            </div>
                                        </td>
                                        <td class="text-end <?php echo $gapEntitas >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($gapEntitas); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="ms-2"><?php echo number_format($percentageEntitas, 1); ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $percentageEntitas >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetEntitasOmzet); ?></strong></td>
                                        <td><strong>100%</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiEntitasOmzet); ?></strong></td>
                                        <td><strong>100%</strong></td>
                                        <td class="text-end <?php echo $totalGapEntitasOmzet >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapEntitasOmzet); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                    <?php 
                                                    $totalPercentageEntitasOmzet = $totalTargetEntitasOmzet > 0 ? 
                                                        ($totalRealisasiEntitasOmzet / $totalTargetEntitasOmzet) * 100 : 0;
                                                    $progressColor = $totalPercentageEntitasOmzet >= 100 ? 'bg-success' : 
                                                        ($totalPercentageEntitasOmzet >= 80 ? 'bg-warning' : 'bg-danger');
                                                    ?>
                                                <span class="ms-2"><strong><?php echo number_format($totalPercentageEntitasOmzet, 1); ?>%</strong></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $totalPercentageEntitasOmzet >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div> -->
                        <div class="table-responsive">
                            <h5 class="mb-3">Tabel Omzet Per Entitas</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Target</th>
                                        <th colspan="2" class="text-center">Realisasi</th>
                                        <th rowspan="2" class="align-middle text-center">Gap Omzet</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian</th>
                                        <th rowspan="2" class="align-middle text-center">Keterangan</th>
                                    </tr>
                                    <tr style="background-color: #A78BFA; color: white;">
                                        <th class="text-center">Omzet</th>
                                        <th class="text-center">% dari Total</th>
                                        <th class="text-center">Omzet</th>
                                        <th class="text-center">% dari Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalGapEntitasOmzet = 0;
                                    $totalTargetEntitasOmzet = 0;
                                    $totalRealisasiEntitasOmzet = 0;
                                    
                                    // Urutkan entitas berdasarkan realisasi tertinggi
                                    uasort($entitasDataOmzet, function($a, $b) {
                                        return $b['total_realisasi'] <=> $a['total_realisasi'];
                                    });
                                    
                                    foreach ($entitasDataOmzet as $entitas => $data):
                                        $targetEntitas = $data['total_target'];
                                        $realisasiEntitas = $data['total_realisasi'];
                                        $gapEntitas = $realisasiEntitas - $targetEntitas;
                                        $percentageEntitas = $targetEntitas > 0 ? ($realisasiEntitas / $targetEntitas) * 100 : 0;
                                        $persenTargetTotal = $totalTargetOmzet > 0 ? ($targetEntitas / $totalTargetOmzet) * 100 : 0;
                                        $persenRealisasiTotal = $totalRealisasiOmzet > 0 ? ($realisasiEntitas / $totalRealisasiOmzet) * 100 : 0;
                                        
                                        // Tentukan warna untuk % Pencapaian
                                        $warnaPencapaian = '';
                                        if ($percentageEntitas >= 100) {
                                            $warnaPencapaian = 'text-success';
                                        } elseif ($percentageEntitas >= 80) {
                                            $warnaPencapaian = 'text-warning';
                                        } else {
                                            $warnaPencapaian = 'text-danger';
                                        }
                                        
                                        // Tentukan warna untuk % dari Total
                                        $warnaPersenTotal = '';
                                        if ($persenRealisasiTotal >= $persenTargetTotal) {
                                            $warnaPersenTotal = 'text-success';
                                        } else {
                                            $warnaPersenTotal = 'text-danger';
                                        }
                                        
                                        // Tentukan Status
                                        $status = '';
                                        $statusClass = '';
                                        if ($percentageEntitas >= 100) {
                                            $status = 'ACHIEVE';
                                            $statusClass = 'badge-selesai';
                                        } else {
                                            $status = 'TIDAK ACHIEVE';
                                            $statusClass = 'badge-belum-selesai';
                                        }
                                        
                                        $totalGapEntitasOmzet += $gapEntitas;
                                        $totalTargetEntitasOmzet += $targetEntitas;
                                        $totalRealisasiEntitasOmzet += $realisasiEntitas;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Target -->
                                        <td class="text-end"><?php echo formatFullNumber($targetEntitas); ?></td>
                                        <td class="text-end"><?php echo number_format($persenTargetTotal, 1); ?>%</td>
                                        
                                        <!-- Realisasi -->
                                        <td class="text-end"><?php echo formatFullNumber($realisasiEntitas); ?></td>
                                        <td class="text-end <?php echo $warnaPersenTotal; ?>">
                                            <strong><?php echo number_format($persenRealisasiTotal, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap Omzet -->
                                        <td class="text-end <?php echo $gapEntitas >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($gapEntitas); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian -->
                                        <td class="text-end <?php echo $warnaPencapaian; ?>">
                                            <strong><?php echo number_format($percentageEntitas, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($entitasDataOmzet)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data omzet untuk periode yang dipilih!</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($entitasDataOmzet)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Target Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetEntitasOmzet); ?></strong></td>
                                        <td class="text-end"><strong>100%</strong></td>
                                        
                                        <!-- Realisasi Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiEntitasOmzet); ?></strong></td>
                                        <td class="text-end"><strong>100%</strong></td>
                                        
                                        <!-- Gap Omzet Total -->
                                        <td class="text-end <?php echo $totalGapEntitasOmzet >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapEntitasOmzet); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Total -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageEntitasOmzet = $totalTargetEntitasOmzet > 0 ? 
                                                    ($totalRealisasiEntitasOmzet / $totalTargetEntitasOmzet) * 100 : 0;
                                                $warnaPencapaianTotal = '';
                                                if ($totalPercentageEntitasOmzet >= 100) {
                                                    $warnaPencapaianTotal = 'text-success';
                                                } elseif ($totalPercentageEntitasOmzet >= 80) {
                                                    $warnaPencapaianTotal = 'text-warning';
                                                } else {
                                                    $warnaPencapaianTotal = 'text-danger';
                                                }
                                                ?>
                                                <span class="<?php echo $warnaPencapaianTotal; ?>"><?php echo number_format($totalPercentageEntitasOmzet, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <strong>
                                                <?php 
                                                $statusTotal = $totalPercentageEntitasOmzet >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                                                echo $statusTotal;
                                                ?>
                                            </strong>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Target dan Realisasi Omzet Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Grafik Pie Omzet per Entitas -->
        <!-- <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Distribusi Realisasi Omzet Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container">
                            <canvas id="chartPieOmzetEntitas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- 6. Grafik Batang & Line - Trend Harian -->
        <!-- <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">6. Trend Harian - Target vs Realisasi (Batang & Line)</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container">
                            <canvas id="chartTrendHarianBatangLine"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- 7. Grafik Batang & Line - Trend Mingguan -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Trend Omztet Mingguan - Target vs Realisasi</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartTrendMingguanBatangLine"></canvas>
                        </div>
                        <!-- Tabel Data Trend Mingguan -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Periode Minggu</th>
                                        <th>Target Omzet</th>
                                        <th>Realisasi Omzet</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalTargetMingguan = 0;
                                    $totalRealisasiMingguan = 0;
                                    $totalGapMingguan = 0;
                                    $totalMinggu = count($mingguDataOmzet);
                                    
                                    // Urutkan data mingguan berdasarkan kunci
                                    ksort($mingguDataOmzet);
                                    
                                    foreach ($mingguDataOmzet as $mingguKey => $data):
                                        $target = $data['target'];
                                        $realisasi = $data['realisasi'];
                                        $gap = $realisasi - $target;
                                        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                        $label = $data['label'];
                                        
                                        $totalTargetMingguan += $target;
                                        $totalRealisasiMingguan += $realisasi;
                                        $totalGapMingguan += $gap;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($label); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                        <td class="text-end <?php echo $gap >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gap); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                        <td>
                                            <?php echo $percentage >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($mingguDataOmzet)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data mingguan yang tersedia</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($mingguDataOmzet)): ?>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetMingguan); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiMingguan); ?></strong></td>
                                        <td class="text-end <?php echo $totalGapMingguan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapMingguan); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageMingguan = $totalTargetMingguan > 0 ? ($totalRealisasiMingguan / $totalTargetMingguan) * 100 : 0;
                                                echo number_format($totalPercentageMingguan, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php echo $totalPercentageMingguan >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Baris Rata-rata per Minggu -->
                                    <tr class="table-secondary">
                                        <td colspan="2" class="text-center"><strong>RATA-RATA PER MINGGU</strong></td>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalMinggu > 0 ? $totalTargetMingguan / $totalMinggu : 0); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalMinggu > 0 ? $totalRealisasiMingguan / $totalMinggu : 0); ?></strong>
                                        </td>
                                        <td class="text-end <?php echo ($totalMinggu > 0 ? $totalGapMingguan / $totalMinggu : 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalMinggu > 0 ? $totalGapMingguan / $totalMinggu : 0); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo number_format($totalPercentageMingguan, 1); ?>%</strong>
                                        </td>
                                        <td>
                                            <?php echo $totalPercentageMingguan >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Trend Omztet Mingguan - Target vs Realisasi')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>                    
                </div>
            </div>
        </div>

        <!-- Card Utama untuk Grafik Penjualan Produk -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Grafik Penjualan Produk</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik 2 Kolom FIX: Penjualan Produk -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Distribusi Penjualan berdasarkan Jumlah (Donut) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Distribusi Penjualan Berdasarkan Jumlah Unit</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProdukJumlahDonut"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1">
                                                <strong>Total Unit:</strong> <?php echo number_format($totalProdukJumlah, 0, ',', '.'); ?> unit
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Produk Terlaris:</strong> 
                                                <?php 
                                                if (!empty($dataPenjualan)) {
                                                    $terlaris = $dataPenjualan[0];
                                                    echo htmlspecialchars($terlaris['nama_produk']) . ' (' . number_format($terlaris['total_jumlah'], 0, ',', '.') . ' unit)';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Distribusi Penjualan berdasarkan Nilai (Pie) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Distribusi Penjualan Berdasarkan Nilai</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProdukNilaiPie"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1">
                                                <strong>Total Nilai:</strong> Rp <?php echo number_format($totalProdukNilai, 0, ',', '.'); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Produk Bernilai Tertinggi:</strong> 
                                                <?php 
                                                if (!empty($dataPenjualan)) {
                                                    $bernilaiTinggi = $dataPenjualan[0];
                                                    echo htmlspecialchars($bernilaiTinggi['nama_produk']) . ' (Rp ' . number_format($bernilaiTinggi['total_nilai'], 0, ',', '.') . ')';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabel Detail Penjualan Produk -->
                        <div class="mt-4">
                            <h5 class="mb-3">Detail Penjualan per Produk (Top 15)</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Produk</th>
                                            <th>Nama Produk</th>
                                            <th>Jumlah Terjual</th>
                                            <th>% dari Total</th>
                                            <th>Nilai Penjualan</th>
                                            <th>% dari Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        foreach ($dataPenjualan as $row): 
                                            $persenJumlah = $totalProdukJumlah > 0 ? ($row['total_jumlah'] / $totalProdukJumlah) * 100 : 0;
                                            $persenNilai = $totalProdukNilai > 0 ? ($row['total_nilai'] / $totalProdukNilai) * 100 : 0;
                                            $rataHarga = $row['total_jumlah'] > 0 ? $row['total_nilai'] / $row['total_jumlah'] : 0;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['kd_produk']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                                            <td class="text-end"><?php echo number_format($row['total_jumlah'], 0, ',', '.'); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2"><?php echo number_format($persenJumlah, 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-end">Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2"><?php echo number_format($persenNilai, 1); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <td colspan="3" class="text-center"><strong>TOTAL</strong></td>
                                            <td class="text-end"><strong><?php echo number_format($totalProdukJumlah, 0, ',', '.'); ?></strong></td>
                                            <td><strong>100%</strong></td>
                                            <td class="text-end"><strong>Rp <?php echo number_format($totalProdukNilai, 0, ',', '.'); ?></strong></td>
                                            <td><strong>100%</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                            <?php if (!empty($dataHasilAnalisis)): ?>
                                <?php foreach ($dataHasilAnalisis as $row): ?>
                                    <?php if(strtolower($row['title']) == strtolower('Grafik Penjualan Produk')): ?>
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                            </div>
                                            <div class="card-body p-2">
                                            <?php 
                                                $cleanedContent = nl2br($row['analisis']);
                                                $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                                $cleanedContent = str_replace('\n', '', $cleanedContent);
                                                $cleanedContent = str_replace('"', '', $cleanedContent);
                                                echo $cleanedContent; 
                                                ?>
                                            </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Utama untuk Distribusi Penjualan per Entitas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Distribusi Penjualan Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik 2 Kolom FIX: Distribusi Penjualan per Entitas -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Distribusi Nilai Penjualan per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Distribusi Nilai Penjualan Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartDistribusiNilaiEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <?php
                                            // Hitung total nilai penjualan per entitas
                                            $totalNilaiPerEntitas = [];
                                            $totalJumlahPerEntitas = [];
                                            $topEntitasByValue = '';
                                            $topEntitasByVolume = '';
                                            $maxValue = 0;
                                            $maxVolume = 0;
                                            
                                            foreach ($dataReport as $row) {
                                                if ($row['nama_akun'] === 'OMZET') {
                                                    $entitas = $row['nama_entitas'];
                                                    $nilai = (float)$row['nilai_realisasi'];
                                                    
                                                    if (!isset($totalNilaiPerEntitas[$entitas])) {
                                                        $totalNilaiPerEntitas[$entitas] = 0;
                                                        $totalJumlahPerEntitas[$entitas] = 0;
                                                    }
                                                    $totalNilaiPerEntitas[$entitas] += $nilai;
                                                    
                                                    // Untuk jumlah, kita asumsikan 1 transaksi per baris data
                                                    $totalJumlahPerEntitas[$entitas] += 1;
                                                    
                                                    // Cari entitas dengan nilai tertinggi
                                                    if ($totalNilaiPerEntitas[$entitas] > $maxValue) {
                                                        $maxValue = $totalNilaiPerEntitas[$entitas];
                                                        $topEntitasByValue = $entitas;
                                                    }
                                                    
                                                    // Cari entitas dengan volume tertinggi
                                                    if ($totalJumlahPerEntitas[$entitas] > $maxVolume) {
                                                        $maxVolume = $totalJumlahPerEntitas[$entitas];
                                                        $topEntitasByVolume = $entitas;
                                                    }
                                                }
                                            }
                                            
                                            // Urutkan berdasarkan nilai tertinggi
                                            arsort($totalNilaiPerEntitas);
                                            $totalNilaiSemuaEntitas = array_sum($totalNilaiPerEntitas);
                                            $persenTopValue = $totalNilaiSemuaEntitas > 0 ? ($maxValue / $totalNilaiSemuaEntitas) * 100 : 0;
                                            ?>
                                            <p class="text-muted mb-1">
                                                <strong>Total Entitas:</strong> <?php echo count($totalNilaiPerEntitas); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Entitas Terbesar (Nilai):</strong> 
                                                <?php echo htmlspecialchars($topEntitasByValue); ?> 
                                                (<?php echo formatFullNumber($maxValue); ?> - <?php echo number_format($persenTopValue, 1); ?>%)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Distribusi Jumlah Penjualan per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Distribusi Jumlah Penjualan Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartDistribusiJumlahEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <?php
                                            // Urutkan berdasarkan jumlah tertinggi
                                            arsort($totalJumlahPerEntitas);
                                            $totalJumlahSemuaEntitas = array_sum($totalJumlahPerEntitas);
                                            $persenTopVolume = $totalJumlahSemuaEntitas > 0 ? ($maxVolume / $totalJumlahSemuaEntitas) * 100 : 0;
                                            ?>
                                            <p class="text-muted mb-1">
                                                <strong>Total Transaksi:</strong> <?php echo number_format($totalJumlahSemuaEntitas, 0, ',', '.'); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Entitas Teraktif (Volume):</strong> 
                                                <?php echo htmlspecialchars($topEntitasByVolume); ?> 
                                                (<?php echo number_format($maxVolume, 0, ',', '.'); ?> transaksi - <?php echo number_format($persenTopVolume, 1); ?>%)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabel Detail Distribusi Penjualan per Entitas -->
                        <div class="mt-4">
                            <h5 class="mb-3">Detail Distribusi Penjualan Per Entitas</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Entitas</th>
                                            <th>Nilai Penjualan</th>
                                            <th>% dari Total Nilai</th>
                                            <th>Jumlah Transaksi</th>
                                            <th>% dari Total Transaksi</th>
                                            <th>Rata-rata Nilai per Transaksi</th>
                                            <th>Target Omzet</th>
                                            <th>% Pencapaian</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        $totalTransaksiSemua = 0;
                                        $totalNilaiSemua = 0;
                                        $totalTargetSemua = 0;
                                        $totalRealisasiSemua = 0;
                                        
                                        // Gabungkan data nilai, jumlah, dan target
                                        $dataEntitasGabungan = [];
                                        
                                        foreach ($totalNilaiPerEntitas as $entitas => $nilai) {
                                            $dataEntitasGabungan[$entitas] = [
                                                'nilai' => $nilai,
                                                'jumlah' => isset($totalJumlahPerEntitas[$entitas]) ? $totalJumlahPerEntitas[$entitas] : 0,
                                                'target' => isset($entitasDataOmzet[$entitas]['total_target']) ? $entitasDataOmzet[$entitas]['total_target'] : 0,
                                                'realisasi' => isset($entitasDataOmzet[$entitas]['total_realisasi']) ? $entitasDataOmzet[$entitas]['total_realisasi'] : 0
                                            ];
                                            
                                            $totalTransaksiSemua += $dataEntitasGabungan[$entitas]['jumlah'];
                                            $totalNilaiSemua += $nilai;
                                            $totalTargetSemua += $dataEntitasGabungan[$entitas]['target'];
                                            $totalRealisasiSemua += $dataEntitasGabungan[$entitas]['realisasi'];
                                        }
                                        
                                        // Urutkan berdasarkan nilai tertinggi
                                        uasort($dataEntitasGabungan, function($a, $b) {
                                            return $b['nilai'] <=> $a['nilai'];
                                        });
                                        
                                        foreach ($dataEntitasGabungan as $entitas => $data):
                                            $persenNilai = $totalNilaiSemua > 0 ? ($data['nilai'] / $totalNilaiSemua) * 100 : 0;
                                            $persenTransaksi = $totalTransaksiSemua > 0 ? ($data['jumlah'] / $totalTransaksiSemua) * 100 : 0;
                                            $rataTransaksi = $data['jumlah'] > 0 ? $data['nilai'] / $data['jumlah'] : 0;
                                            $persenPencapaian = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                            <td class="text-end"><?php echo formatFullNumber($data['nilai']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2"><?php echo number_format($persenNilai, 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-end"><?php echo number_format($data['jumlah'], 0, ',', '.'); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2"><?php echo number_format($persenTransaksi, 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-end"><?php echo formatFullNumber($rataTransaksi); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($data['target']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2"><?php echo number_format($persenPencapaian, 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $persenPencapaian >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalNilaiSemua); ?></strong></td>
                                            <td><strong>100%</strong></td>
                                            <td class="text-end"><strong><?php echo number_format($totalTransaksiSemua, 0, ',', '.'); ?></strong></td>
                                            <td><strong>100%</strong></td>
                                            <td class="text-end">
                                                <strong>
                                                    <?php 
                                                    $rataTransaksiTotal = $totalTransaksiSemua > 0 ? $totalNilaiSemua / $totalTransaksiSemua : 0;
                                                    echo formatFullNumber($rataTransaksiTotal);
                                                    ?>
                                                </strong>
                                            </td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalTargetSemua); ?></strong></td>
                                            <td>
                                                <?php 
                                                $totalPersenPencapaian = $totalTargetSemua > 0 ? ($totalRealisasiSemua / $totalTargetSemua) * 100 : 0;
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2"><strong><?php echo number_format($totalPersenPencapaian, 1); ?>%</strong></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $totalPersenPencapaian >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                            <?php if (!empty($dataHasilAnalisis)): ?>
                                <?php foreach ($dataHasilAnalisis as $row): ?>
                                    <?php if(strtolower($row['title']) == strtolower('Distribusi Penjualan Per Entitas')): ?>
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                            </div>
                                            <div class="card-body p-2">
                                            <?php 
                                                $cleanedContent = nl2br($row['analisis']);
                                                $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                                $cleanedContent = str_replace('\n', '', $cleanedContent);
                                                $cleanedContent = str_replace('"', '', $cleanedContent);
                                                echo $cleanedContent; 
                                                ?>
                                            </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ANALISIS HPP ==================== -->
        <!-- 10. Grafik Tren Target dan Realisasi HPP per Hari -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Target dan Realisasi HPP Per Hari</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartTrenHPPHarian"></canvas>
                        </div>
                        <?php
                        // [Kode query Omzet Bersih tetap sama seperti sebelumnya]
                        // Query khusus untuk mengambil data Omzet Bersih per hari
                        $sqlOmzetBersihHarian = "SELECT 
                            DATE(tanggal) as tanggal_date,
                            SUM(nilai_target) as total_target,
                            SUM(nilai_realisasi) as total_realisasi
                        FROM data_report 
                        WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                            OR LOWER(nama_akun) LIKE '%omzetbersih%'
                            OR nama_akun = 'Omzet Bersih')";
                        
                        $paramsOmzetBersihHarian = [];
                        
                        // Tambahkan filter tanggal jika ada
                        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                            $sqlOmzetBersihHarian .= " AND DATE(tanggal) BETWEEN ? AND ?";
                            $paramsOmzetBersihHarian[] = $tanggal_awal;
                            $paramsOmzetBersihHarian[] = $tanggal_akhir;
                        } elseif (!empty($tanggal_awal)) {
                            $sqlOmzetBersihHarian .= " AND DATE(tanggal) >= ?";
                            $paramsOmzetBersihHarian[] = $tanggal_awal;
                        } elseif (!empty($tanggal_akhir)) {
                            $sqlOmzetBersihHarian .= " AND DATE(tanggal) <= ?";
                            $paramsOmzetBersihHarian[] = $tanggal_akhir;
                        }
                        
                        $sqlOmzetBersihHarian .= " GROUP BY DATE(tanggal) ORDER BY tanggal_date";
                        
                        try {
                            $stmtOmzetBersihHarian = $pdo->prepare($sqlOmzetBersihHarian);
                            $stmtOmzetBersihHarian->execute($paramsOmzetBersihHarian);
                            $dataOmzetBersihHarian = $stmtOmzetBersihHarian->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Konversi ke array asosiatif dengan tanggal sebagai key
                            $omzetBersihPerHari = [];
                            foreach ($dataOmzetBersihHarian as $row) {
                                $tanggal = $row['tanggal_date'];
                                $omzetBersihPerHari[$tanggal] = [
                                    'target' => (float)$row['total_target'],
                                    'realisasi' => (float)$row['total_realisasi']
                                ];
                            }
                            
                            // Fallback jika data kosong
                            if (empty($omzetBersihPerHari)) {
                                // Gunakan data dari $tanggalDataOmzet dengan filter
                                foreach ($tanggalDataOmzet as $tanggal => $data) {
                                    $includeRow = true;
                                    if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
                                        $includeRow = false;
                                    }
                                    if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
                                        $includeRow = false;
                                    }
                                    
                                    if ($includeRow) {
                                        $omzetBersihPerHari[$tanggal] = $data;
                                    }
                                }
                            }
                            
                        } catch (PDOException $e) {
                            // Fallback jika query gagal
                            $omzetBersihPerHari = [];
                            foreach ($tanggalDataOmzet as $tanggal => $data) {
                                $includeRow = true;
                                if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
                                    $includeRow = false;
                                }
                                if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
                                    $includeRow = false;
                                }
                                
                                if ($includeRow) {
                                    $omzetBersihPerHari[$tanggal] = $data;
                                }
                            }
                        }
                        
                        // Filter $tanggalDataHPP berdasarkan tanggal
                        $tanggalDataHPPFiltered = [];
                        if (isset($tanggalDataHPP) && !empty($tanggalDataHPP)) {
                            foreach ($tanggalDataHPP as $tanggal => $data) {
                                $includeRow = true;
                                if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
                                    $includeRow = false;
                                }
                                if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
                                    $includeRow = false;
                                }
                                
                                if ($includeRow) {
                                    $tanggalDataHPPFiltered[$tanggal] = $data;
                                }
                            }
                        }
                        ?>                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target HPP</th>
                                        <th>% Target</th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi HPP</th>
                                        <th>% Realisasi</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihHarian = 0;
                                    $totalTargetHarianHPP = 0;
                                    $totalRealisasiOmzetBersihHarian = 0;
                                    $totalRealisasiHarianHPP = 0;
                                    $totalGapHarianHPP = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalHari = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Gabungkan semua tanggal yang ada
                                    $allDates = array_unique(array_merge(
                                        array_keys($tanggalDataHPPFiltered),
                                        array_keys($omzetBersihPerHari)
                                    ));
                                    sort($allDates);
                                    
                                    foreach ($allDates as $tanggal):
                                        // Filter tanggal
                                        $includeRow = true;
                                        if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
                                            $includeRow = false;
                                        }
                                        if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
                                            $includeRow = false;
                                        }
                                        
                                        if (!$includeRow) continue;
                                        
                                        // Data HPP
                                        $targetHPPHarian = isset($tanggalDataHPPFiltered[$tanggal]) ? $tanggalDataHPPFiltered[$tanggal]['target'] : 0;
                                        $realisasiHPPHarian = isset($tanggalDataHPPFiltered[$tanggal]) ? $tanggalDataHPPFiltered[$tanggal]['realisasi'] : 0;
                                        $gapHPPHarian = $realisasiHPPHarian - $targetHPPHarian;
                                        
                                        // Data Omzet Bersih
                                        $targetOmzetBersihHarian = isset($omzetBersihPerHari[$tanggal]) ? $omzetBersihPerHari[$tanggal]['target'] : 0;
                                        $realisasiOmzetBersihHarian = isset($omzetBersihPerHari[$tanggal]) ? $omzetBersihPerHari[$tanggal]['realisasi'] : 0;
                                        
                                        // Hitung %_Target (Target HPP / Target Omzet Bersih)
                                        $persenTargetHPP = $targetOmzetBersihHarian > 0 ? ($targetHPPHarian / $targetOmzetBersihHarian) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi HPP / Realisasi Omzet Bersih) - PERUBAHAN BARU
                                        $persenRealisasiHPP = $realisasiOmzetBersihHarian > 0 ? ($realisasiHPPHarian / $realisasiOmzetBersihHarian) * 100 : 0;
                                        
                                        // Hitung % Pencapaian HPP (Realisasi HPP / Target HPP)
                                        $percentageHarian = $targetHPPHarian > 0 ? ($realisasiHPPHarian / $targetHPPHarian) * 100 : 0;
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi - PERUBAHAN BARU
                                        $status = '';
                                        $statusClass = '';
                                        if ($persenTargetHPP <= $persenRealisasiHPP) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihHarian += $targetOmzetBersihHarian;
                                        $totalTargetHarianHPP += $targetHPPHarian;
                                        $totalRealisasiOmzetBersihHarian += $realisasiOmzetBersihHarian;
                                        $totalRealisasiHarianHPP += $realisasiHPPHarian;
                                        $totalGapHarianHPP += $gapHPPHarian;
                                        $totalPersenTargetSum += $persenTargetHPP;
                                        $totalPersenRealisasiSum += $persenRealisasiHPP;
                                        $totalHari++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo date('d M Y', strtotime($tanggal)); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihHarian); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetHPPHarian); ?></td>
                                        <td class="text-end <?php echo $persenTargetHPP <= 70 ? 'text-success' : ($persenTargetHPP <= 85 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenTargetHPP, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihHarian); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiHPPHarian); ?></td>
                                        <td class="text-end <?php echo $persenRealisasiHPP <= 70 ? 'text-success' : ($persenRealisasiHPP <= 85 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenRealisasiHPP, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $gapHPPHarian >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gapHPPHarian); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentageHarian, 1); ?>%</td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allDates)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            Tidak ada data untuk periode yang dipilih
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allDates)): ?>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihHarian); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetHarianHPP); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalHari > 0 ? $totalPersenTargetSum / $totalHari : 0;
                                                echo number_format($rataRataPersenTarget, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihHarian); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiHarianHPP); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalHari > 0 ? $totalPersenRealisasiSum / $totalHari : 0;
                                                echo number_format($rataRataPersenRealisasi, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapHarianHPP >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapHarianHPP); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageHarianHPP = $totalTargetHarianHPP > 0 ? ($totalRealisasiHarianHPP / $totalTargetHarianHPP) * 100 : 0;
                                                echo number_format($totalPercentageHarianHPP, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            if ($rataRataPersenTarget <= $rataRataPersenRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Target dan Realisasi HPP Per Hari')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 11. Grafik Batang Target dan Realisasi HPP per Entitas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Target dan Realisasi HPP Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartHPPEntitas"></canvas>
                        </div>
                        <?php
                        // Query untuk mengambil data Omzet Bersih per entitas
                        $sqlOmzetBersihEntitas = "SELECT 
                            nama_entitas,
                            SUM(nilai_target) as total_target,
                            SUM(nilai_realisasi) as total_realisasi
                        FROM data_report 
                        WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                            OR LOWER(nama_akun) LIKE '%omzetbersih%'
                            OR nama_akun = 'Omzet Bersih')";
                        
                        $paramsOmzetBersihEntitas = [];
                        
                        // Tambahkan filter tanggal jika ada
                        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                            $sqlOmzetBersihEntitas .= " AND DATE(tanggal) BETWEEN ? AND ?";
                            $paramsOmzetBersihEntitas[] = $tanggal_awal;
                            $paramsOmzetBersihEntitas[] = $tanggal_akhir;
                        } elseif (!empty($tanggal_awal)) {
                            $sqlOmzetBersihEntitas .= " AND DATE(tanggal) >= ?";
                            $paramsOmzetBersihEntitas[] = $tanggal_awal;
                        } elseif (!empty($tanggal_akhir)) {
                            $sqlOmzetBersihEntitas .= " AND DATE(tanggal) <= ?";
                            $paramsOmzetBersihEntitas[] = $tanggal_akhir;
                        }
                        
                        $sqlOmzetBersihEntitas .= " GROUP BY nama_entitas ORDER BY nama_entitas";
                        
                        try {
                            $stmtOmzetBersihEntitas = $pdo->prepare($sqlOmzetBersihEntitas);
                            $stmtOmzetBersihEntitas->execute($paramsOmzetBersihEntitas);
                            $dataOmzetBersihEntitas = $stmtOmzetBersihEntitas->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Konversi ke array asosiatif dengan entitas sebagai key
                            $omzetBersihPerEntitas = [];
                            foreach ($dataOmzetBersihEntitas as $row) {
                                $entitas = $row['nama_entitas'];
                                $omzetBersihPerEntitas[$entitas] = [
                                    'target' => (float)$row['total_target'],
                                    'realisasi' => (float)$row['total_realisasi']
                                ];
                            }
                            
                            // Fallback jika data kosong
                            if (empty($omzetBersihPerEntitas)) {
                                // Gunakan data Omzet biasa dari $entitasDataOmzet
                                foreach ($entitasDataOmzet as $entitas => $data) {
                                    $omzetBersihPerEntitas[$entitas] = [
                                        'target' => $data['total_target'],
                                        'realisasi' => $data['total_realisasi']
                                    ];
                                }
                            }
                            
                        } catch (PDOException $e) {
                            // Fallback jika query gagal
                            $omzetBersihPerEntitas = [];
                            foreach ($entitasDataOmzet as $entitas => $data) {
                                $omzetBersihPerEntitas[$entitas] = [
                                    'target' => $data['total_target'],
                                    'realisasi' => $data['total_realisasi']
                                ];
                            }
                        }
                        ?>
                        
                        <!-- <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Entitas</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target HPP</th>
                                        <th>% Target</th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi HPP</th>
                                        <th>% Realisasi</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihEntitas = 0;
                                    $totalTargetEntitasHPP = 0;
                                    $totalRealisasiOmzetBersihEntitas = 0;
                                    $totalRealisasiEntitasHPP = 0;
                                    $totalGapEntitasHPP = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalMarginEntitas = 0;
                                    $totalEntitas = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Gabungkan semua entitas yang ada (baik dari HPP maupun Omzet Bersih)
                                    $allEntitas = array_unique(array_merge(
                                        array_keys($entitasDataHPP),
                                        array_keys($omzetBersihPerEntitas)
                                    ));
                                    
                                    // Urutkan berdasarkan nama entitas
                                    sort($allEntitas);
                                    
                                    foreach ($allEntitas as $entitas):
                                        // Data HPP per entitas
                                        $targetHPPerEntitas = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_target'] : 0;
                                        $realisasiHPPerEntitas = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_realisasi'] : 0;
                                        $gapHPPerEntitas = $realisasiHPPerEntitas - $targetHPPerEntitas;
                                        
                                        // Data Omzet Bersih per entitas
                                        $targetOmzetBersihEntitas = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['target'] : 0;
                                        $realisasiOmzetBersihEntitas = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        // Hitung %_Target (Target HPP / Target Omzet Bersih)
                                        $persenTargetHPP = $targetOmzetBersihEntitas > 0 ? ($targetHPPerEntitas / $targetOmzetBersihEntitas) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi HPP / Realisasi Omzet Bersih)
                                        $persenRealisasiHPP = $realisasiOmzetBersihEntitas > 0 ? ($realisasiHPPerEntitas / $realisasiOmzetBersihEntitas) * 100 : 0;
                                        
                                        // Hitung % Pencapaian HPP (Realisasi HPP / Target HPP)
                                        $percentageEntitas = $targetHPPerEntitas > 0 ? ($realisasiHPPerEntitas / $targetHPPerEntitas) * 100 : 0;
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                        $status = '';
                                        $statusClass = '';
                                        if ($persenTargetHPP <= $persenRealisasiHPP) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Hitung margin (Omzet Bersih - HPP)
                                        $marginEntitas = $realisasiOmzetBersihEntitas - $realisasiHPPerEntitas;
                                        
                                        // Hitung % margin (Margin / Omzet Bersih)
                                        $persenMargin = $realisasiOmzetBersihEntitas > 0 ? ($marginEntitas / $realisasiOmzetBersihEntitas) * 100 : 0;
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihEntitas += $targetOmzetBersihEntitas;
                                        $totalTargetEntitasHPP += $targetHPPerEntitas;
                                        $totalRealisasiOmzetBersihEntitas += $realisasiOmzetBersihEntitas;
                                        $totalRealisasiEntitasHPP += $realisasiHPPerEntitas;
                                        $totalGapEntitasHPP += $gapHPPerEntitas;
                                        $totalPersenTargetSum += $persenTargetHPP;
                                        $totalPersenRealisasiSum += $persenRealisasiHPP;
                                        $totalMarginEntitas += $marginEntitas;
                                        $totalEntitas++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihEntitas); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetHPPerEntitas); ?></td>
                                        <td class="text-end <?php echo $persenTargetHPP <= 70 ? 'text-success' : ($persenTargetHPP <= 85 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenTargetHPP, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihEntitas); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiHPPerEntitas); ?></td>
                                        <td class="text-end <?php echo $persenRealisasiHPP <= 70 ? 'text-success' : ($persenRealisasiHPP <= 85 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenRealisasiHPP, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $gapHPPerEntitas >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gapHPPerEntitas); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentageEntitas, 1); ?>%</td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allEntitas)): ?>
                                    <tr>
                                        <td colspan="13" class="text-center">Tidak ada data untuk periode yang dipilih</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allEntitas)): ?>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihEntitas); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetEntitasHPP); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalEntitas > 0 ? $totalPersenTargetSum / $totalEntitas : 0;
                                                echo number_format($rataRataPersenTarget, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihEntitas); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiEntitasHPP); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalEntitas > 0 ? $totalPersenRealisasiSum / $totalEntitas : 0;
                                                echo number_format($rataRataPersenRealisasi, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapEntitasHPP >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapEntitasHPP); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageEntitasHPP = $totalTargetEntitasHPP > 0 ? ($totalRealisasiEntitasHPP / $totalTargetEntitasHPP) * 100 : 0;
                                                echo number_format($totalPercentageEntitasHPP, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            if ($rataRataPersenTarget <= $rataRataPersenRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-belum-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div> -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">HPP</th>
                                        <th colspan="2" class="text-center">% HPP/Omzet</th>
                                        <th rowspan="2" class="align-middle text-center">Gap HPP</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian HPP</th>
                                        <th rowspan="2" class="align-middle text-center">Keterangan</th>
                                    </tr>
                                    <tr style="background-color: #A78BFA; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihEntitas = 0;
                                    $totalTargetEntitasHPP = 0;
                                    $totalRealisasiOmzetBersihEntitas = 0;
                                    $totalRealisasiEntitasHPP = 0;
                                    $totalGapEntitasHPP = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalEntitas = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Gabungkan semua entitas yang ada (baik dari HPP maupun Omzet Bersih)
                                    $allEntitas = array_unique(array_merge(
                                        array_keys($entitasDataHPP),
                                        array_keys($omzetBersihPerEntitas)
                                    ));
                                    
                                    // Urutkan berdasarkan nama entitas
                                    sort($allEntitas);
                                    
                                    foreach ($allEntitas as $entitas):
                                        // Data HPP per entitas
                                        $targetHPPerEntitas = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_target'] : 0;
                                        $realisasiHPPerEntitas = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_realisasi'] : 0;
                                        $gapHPPerEntitas = $realisasiHPPerEntitas - $targetHPPerEntitas;
                                        
                                        // Data Omzet Bersih per entitas
                                        $targetOmzetBersihEntitas = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['target'] : 0;
                                        $realisasiOmzetBersihEntitas = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        // Hitung %_Target (Target HPP / Target Omzet Bersih)
                                        $persenTargetHPP = $targetOmzetBersihEntitas > 0 ? ($targetHPPerEntitas / $targetOmzetBersihEntitas) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi HPP / Realisasi Omzet Bersih)
                                        $persenRealisasiHPP = $realisasiOmzetBersihEntitas > 0 ? ($realisasiHPPerEntitas / $realisasiOmzetBersihEntitas) * 100 : 0;
                                        
                                        // Hitung % Pencapaian HPP (Realisasi HPP / Target HPP)
                                        $percentageEntitas = $targetHPPerEntitas > 0 ? ($realisasiHPPerEntitas / $targetHPPerEntitas) * 100 : 0;
                                        
                                        // Tentukan warna untuk % HPP/Omzet (Target)
                                        $warnaPersenTarget = $persenTargetHPP <= 70 ? 'text-success' : 
                                                        ($persenTargetHPP <= 85 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % HPP/Omzet (Realisasi)
                                        $warnaPersenRealisasi = $persenRealisasiHPP <= 70 ? 'text-success' : 
                                                            ($persenRealisasiHPP <= 85 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Pencapaian HPP
                                        $warnaPencapaianHPP = '';
                                        if ($percentageEntitas <= 100) {
                                            $warnaPencapaianHPP = 'text-success';
                                        } elseif ($percentageEntitas <= 120) {
                                            $warnaPencapaianHPP = 'text-warning';
                                        } else {
                                            $warnaPencapaianHPP = 'text-danger';
                                        }
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                        $status = '';
                                        $statusClass = '';
                                        if ($persenTargetHPP <= $persenRealisasiHPP) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalOverBudget++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalEfisien++;
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihEntitas += $targetOmzetBersihEntitas;
                                        $totalTargetEntitasHPP += $targetHPPerEntitas;
                                        $totalRealisasiOmzetBersihEntitas += $realisasiOmzetBersihEntitas;
                                        $totalRealisasiEntitasHPP += $realisasiHPPerEntitas;
                                        $totalGapEntitasHPP += $gapHPPerEntitas;
                                        $totalPersenTargetSum += $persenTargetHPP;
                                        $totalPersenRealisasiSum += $persenRealisasiHPP;
                                        $totalEntitas++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihEntitas); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihEntitas); ?></td>
                                        
                                        <!-- HPP -->
                                        <td class="text-end"><?php echo formatFullNumber($targetHPPerEntitas); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiHPPerEntitas); ?></td>
                                        
                                        <!-- % HPP/Omzet -->
                                        <td class="text-end <?php echo $warnaPersenTarget; ?>">
                                            <strong><?php echo number_format($persenTargetHPP, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaPersenRealisasi; ?>">
                                            <strong><?php echo number_format($persenRealisasiHPP, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap HPP -->
                                        <td class="text-end <?php echo $gapHPPerEntitas >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gapHPPerEntitas); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian HPP -->
                                        <td class="text-end <?php echo $warnaPencapaianHPP; ?>">
                                            <strong><?php echo number_format($percentageEntitas, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allEntitas)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data HPP untuk periode yang dipilih!</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allEntitas)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihEntitas); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihEntitas); ?></strong></td>
                                        
                                        <!-- HPP Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetEntitasHPP); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiEntitasHPP); ?></strong></td>
                                        
                                        <!-- % HPP/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalEntitas > 0 ? $totalPersenTargetSum / $totalEntitas : 0;
                                                echo number_format($rataRataPersenTarget, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalEntitas > 0 ? $totalPersenRealisasiSum / $totalEntitas : 0;
                                                echo number_format($rataRataPersenRealisasi, 1); ?>%
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap HPP Total -->
                                        <td class="text-end <?php echo $totalGapEntitasHPP >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapEntitasHPP); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian HPP Total -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageEntitasHPP = $totalTargetEntitasHPP > 0 ? ($totalRealisasiEntitasHPP / $totalTargetEntitasHPP) * 100 : 0;
                                                $warnaPencapaianTotal = '';
                                                if ($totalPercentageEntitasHPP <= 100) {
                                                    $warnaPencapaianTotal = 'text-success';
                                                } elseif ($totalPercentageEntitasHPP <= 120) {
                                                    $warnaPencapaianTotal = 'text-warning';
                                                } else {
                                                    $warnaPencapaianTotal = 'text-danger';
                                                }
                                                ?>
                                                <span class="<?php echo $warnaPencapaianTotal; ?>"><?php echo number_format($totalPercentageEntitasHPP, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            if ($rataRataPersenTarget <= $rataRataPersenRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-belum-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Target dan Realisasi HPP Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ANALISIS BIAYA ==================== -->
        <!-- 12. Grafik Tren Target dan Realisasi Biaya per Hari -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Target dan Realisasi Biaya Per Hari</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartTrenBiayaHarian"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target Biaya</th>
                                        <th>% Target</th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi Biaya</th>
                                        <th>% Realisasi</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihHarian = 0;
                                    $totalTargetHarianBiaya = 0;
                                    $totalRealisasiOmzetBersihHarian = 0;
                                    $totalRealisasiHarianBiaya = 0;
                                    $totalGapHarianBiaya = 0;
                                    $totalMarginHarian = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalHari = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Gabungkan semua tanggal yang ada
                                    $allDatesBiaya = array_unique(array_merge(
                                        array_keys($tanggalDataBiayaFiltered),
                                        array_keys($omzetBersihPerHariBiaya)
                                    ));
                                    sort($allDatesBiaya);
                                    
                                    foreach ($allDatesBiaya as $tanggal):
                                        // Filter tanggal
                                        $includeRow = true;
                                        if (!empty($tanggal_awal) && strtotime($tanggal) < strtotime($tanggal_awal)) {
                                            $includeRow = false;
                                        }
                                        if (!empty($tanggal_akhir) && strtotime($tanggal) > strtotime($tanggal_akhir)) {
                                            $includeRow = false;
                                        }
                                        
                                        if (!$includeRow) continue;
                                        
                                        // Data Biaya per hari
                                        $targetHarianBiaya = isset($tanggalDataBiayaFiltered[$tanggal]) ? $tanggalDataBiayaFiltered[$tanggal]['target'] : 0;
                                        $realisasiHarianBiaya = isset($tanggalDataBiayaFiltered[$tanggal]) ? $tanggalDataBiayaFiltered[$tanggal]['realisasi'] : 0;
                                        $gapHarianBiaya = $realisasiHarianBiaya - $targetHarianBiaya;
                                        
                                        // Data Omzet Bersih per hari
                                        $targetOmzetBersihHarian = isset($omzetBersihPerHariBiaya[$tanggal]) ? $omzetBersihPerHariBiaya[$tanggal]['target'] : 0;
                                        $realisasiOmzetBersihHarian = isset($omzetBersihPerHariBiaya[$tanggal]) ? $omzetBersihPerHariBiaya[$tanggal]['realisasi'] : 0;
                                        
                                        // Hitung %_Target (Target Biaya / Target Omzet Bersih)
                                        $persenTargetBiaya = $targetOmzetBersihHarian > 0 ? ($targetHarianBiaya / $targetOmzetBersihHarian) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi Biaya / Realisasi Omzet Bersih)
                                        $persenRealisasiBiaya = $realisasiOmzetBersihHarian > 0 ? ($realisasiHarianBiaya / $realisasiOmzetBersihHarian) * 100 : 0;
                                        
                                        // Hitung % Pencapaian Biaya (Realisasi Biaya / Target Biaya)
                                        $percentageHarian = $targetHarianBiaya > 0 ? ($realisasiHarianBiaya / $targetHarianBiaya) * 100 : 0;
                                        
                                        // Hitung margin setelah biaya
                                        $marginSetelahBiaya = $realisasiOmzetBersihHarian - $realisasiHarianBiaya;
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                        $status = '';
                                        $statusClass = '';
                                        if ($persenTargetBiaya <= $persenRealisasiBiaya) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihHarian += $targetOmzetBersihHarian;
                                        $totalTargetHarianBiaya += $targetHarianBiaya;
                                        $totalRealisasiOmzetBersihHarian += $realisasiOmzetBersihHarian;
                                        $totalRealisasiHarianBiaya += $realisasiHarianBiaya;
                                        $totalGapHarianBiaya += $gapHarianBiaya;
                                        $totalMarginHarian += $marginSetelahBiaya;
                                        $totalPersenTargetSum += $persenTargetBiaya;
                                        $totalPersenRealisasiSum += $persenRealisasiBiaya;
                                        $totalHari++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo date('d M Y', strtotime($tanggal)); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihHarian); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetHarianBiaya); ?></td>
                                        <td class="text-end <?php echo $persenTargetBiaya <= 20 ? 'text-success' : ($persenTargetBiaya <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenTargetBiaya, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihHarian); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiHarianBiaya); ?></td>
                                        <td class="text-end <?php echo $persenRealisasiBiaya <= 20 ? 'text-success' : ($persenRealisasiBiaya <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenRealisasiBiaya, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $gapHarianBiaya >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gapHarianBiaya); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentageHarian, 1); ?>%</td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allDatesBiaya)): ?>
                                    <tr>
                                        <td colspan="12" class="text-center">
                                            Tidak ada data untuk periode yang dipilih
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allDatesBiaya)): ?>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihHarian); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetHarianBiaya); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalHari > 0 ? $totalPersenTargetSum / $totalHari : 0;
                                                echo number_format($rataRataPersenTarget, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihHarian); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiHarianBiaya); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalHari > 0 ? $totalPersenRealisasiSum / $totalHari : 0;
                                                echo number_format($rataRataPersenRealisasi, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapHarianBiaya >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapHarianBiaya); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageHarianBiaya = $totalTargetHarianBiaya > 0 ? ($totalRealisasiHarianBiaya / $totalTargetHarianBiaya) * 100 : 0;
                                                echo number_format($totalPercentageHarianBiaya, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            if ($rataRataPersenTarget <= $rataRataPersenRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Target dan Realisasi Biaya Per Hari')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 13. Grafik Batang Target dan Realisasi Berdasarkan Jenis Biaya -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Target dan Realisasi Berdasarkan Jenis Biaya</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik Batang - Jenis Biaya -->
                        <div class="chart-container-fixed">
                            <canvas id="chartJenisBiaya"></canvas>
                        </div>
                        
                        <!-- Dua Grafik Pie dalam 2 Kolom Tetap -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Proporsi Biaya per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi Biaya Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiBiayaEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1">
                                                <strong>Total Entitas:</strong> <?php echo count($entitasBiayaData); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Entitas dengan Biaya Tertinggi:</strong> 
                                                <?php 
                                                if (!empty($entitasBiayaData)) {
                                                    arsort($entitasBiayaData);
                                                    $entitasKeys = array_keys($entitasBiayaData);
                                                    $entitasTertinggi = $entitasKeys[0];
                                                    $biayaTertinggi = $entitasBiayaData[$entitasTertinggi]['realisasi'];
                                                    echo htmlspecialchars($entitasTertinggi) . ' (' . formatFullNumber($biayaTertinggi) . ')';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kolom 2: Proporsi Biaya berdasarkan Jenis -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi Biaya Berdasarkan Jenis</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiJenisBiaya"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1">
                                                <strong>Jenis Biaya:</strong> <?php echo count($jenisBiayaData); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Jenis Biaya Tertinggi:</strong> 
                                                <?php 
                                                if (!empty($jenisBiayaData)) {
                                                    $jenisTertinggi = '';
                                                    $nilaiTertinggi = 0;
                                                    foreach ($jenisBiayaData as $jenis => $data) {
                                                        if ($data['realisasi'] > $nilaiTertinggi) {
                                                            $nilaiTertinggi = $data['realisasi'];
                                                            $jenisTertinggi = $jenis;
                                                        }
                                                    }
                                                    echo htmlspecialchars($jenisTertinggi) . ' (' . formatFullNumber($nilaiTertinggi) . ')';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabel Data Jenis Biaya (Tabel Baru) -->
                        <!-- <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jenis Biaya</th>
                                    <th>Target Omzet Bersih</th>
                                    <th>Target Biaya</th>
                                    <th>% Target</th>
                                    <th>Realisasi Omzet Bersih</th>
                                    <th>Realisasi Biaya</th>
                                    <th>% Realisasi</th>
                                    <th>Gap</th>
                                    <th>% Pencapaian</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $totalTargetOmzetBersihJenis = 0;
                                $totalTargetJenisBiaya = 0;
                                $totalRealisasiOmzetBersihJenis = 0;
                                $totalRealisasiJenisBiaya = 0;
                                $totalGapJenisBiaya = 0;
                                $totalPersenTargetSum = 0;
                                $totalPersenRealisasiSum = 0;
                                $totalJenis = 0;
                                $totalEfisien = 0;
                                $totalOverBudget = 0;
                                
                                foreach ($jenisBiayaData as $jenis => $data):
                                    $target = $data['target'];
                                    $realisasi = $data['realisasi'];
                                    $gap = $realisasi - $target;
                                    
                                    $targetOmzetBersihJenis = $totalTargetOmzetBersih;
                                    $realisasiOmzetBersihJenis = $totalRealisasiOmzetBersih;
                                    
                                    $persenTarget = $targetOmzetBersihJenis > 0 ? ($target / $targetOmzetBersihJenis) * 100 : 0;
                                    $persenRealisasi = $realisasiOmzetBersihJenis > 0 ? ($realisasi / $realisasiOmzetBersihJenis) * 100 : 0;
                                    $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                    
                                    $status = '';
                                    $statusClass = '';
                                    if ($persenTarget <= $persenRealisasi) {
                                        $status = 'OVER BUDGET';
                                        $statusClass = 'badge-belum-selesai';
                                        $totalEfisien++;
                                    } else {
                                        $status = 'EFISIEN';
                                        $statusClass = 'badge-selesai';
                                        $totalOverBudget++;
                                    }
                                    
                                    $totalTargetOmzetBersihJenis += $targetOmzetBersihJenis;
                                    $totalTargetJenisBiaya += $target;
                                    $totalRealisasiOmzetBersihJenis += $realisasiOmzetBersihJenis;
                                    $totalRealisasiJenisBiaya += $realisasi;
                                    $totalGapJenisBiaya += $gap;
                                    $totalPersenTargetSum += $persenTarget;
                                    $totalPersenRealisasiSum += $persenRealisasi;
                                    $totalJenis++;
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($jenis); ?></strong></td>
                                    <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihJenis); ?></td>
                                    <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                    <td class="text-end <?php echo $persenTarget <= 20 ? 'text-success' : ($persenTarget <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                        <strong><?php echo number_format($persenTarget, 1); ?>%</strong>
                                    </td>
                                    <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihJenis); ?></td>
                                    <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                    <td class="text-end <?php echo $persenRealisasi <= 20 ? 'text-success' : ($persenRealisasi <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                        <strong><?php echo number_format($persenRealisasi, 1); ?>%</strong>
                                    </td>
                                    <td class="text-end <?php echo $gap >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatFullNumber($gap); ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                    <td>
                                        <?php echo $status; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($jenisBiayaData)): ?>
                                <tr>
                                    <td colspan="12" class="text-center">Tidak ada data jenis biaya</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($jenisBiayaData)): ?>
                            <tfoot>
                                <tr class="table-primary">
                                    <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalJenis > 0 ? $totalTargetOmzetBersihJenis / $totalJenis : 0); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalTargetJenisBiaya); ?></strong></td>
                                    <td class="text-end">
                                        <strong>
                                            <?php 
                                            $rataRataPersenTarget = $totalJenis > 0 ? $totalPersenTargetSum / $totalJenis : 0;
                                            echo number_format($rataRataPersenTarget, 1); ?>%
                                        </strong>
                                    </td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalJenis > 0 ? $totalRealisasiOmzetBersihJenis / $totalJenis : 0); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiJenisBiaya); ?></strong></td>
                                    <td class="text-end">
                                        <strong>
                                            <?php 
                                            $rataRataPersenRealisasi = $totalJenis > 0 ? $totalPersenRealisasiSum / $totalJenis : 0;
                                            echo number_format($rataRataPersenRealisasi, 1); ?>%
                                        </strong>
                                    </td>
                                    <td class="text-end <?php echo $totalGapJenisBiaya >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatFullNumber($totalGapJenisBiaya); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            <?php 
                                            $totalPercentageJenisBiaya = $totalTargetJenisBiaya > 0 ? ($totalRealisasiJenisBiaya / $totalTargetJenisBiaya) * 100 : 0;
                                            echo number_format($totalPercentageJenisBiaya, 1); ?>%
                                        </strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusTotal = '';
                                        $statusClassTotal = '';
                                        if ($rataRataPersenTarget <= $rataRataPersenRealisasi) {
                                            $statusTotal = 'OVER BUDGET';
                                            $statusClassTotal = 'badge-belum-selesai';
                                        } else {
                                            $statusTotal = 'EFISIEN';
                                            $statusClassTotal = 'badge-selesai';
                                        }
                                        ?>
                                        <?php echo $statusTotal; ?>
                                    </td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                        </div> -->
                        <div class="table-responsive">
                            <h5 class="mb-3">Tabel Jenis Biaya Per Entitas</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Jenis Biaya</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">Biaya</th>
                                        <th colspan="2" class="text-center">% Biaya/Omzet</th>
                                        <th rowspan="2" class="align-middle text-center">Gap Biaya</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian</th>
                                        <th rowspan="2" class="align-middle text-center">Keterangan</th>
                                    </tr>
                                    <tr style="background-color: #A78BFA; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihJenis = 0;
                                    $totalTargetJenisBiaya = 0;
                                    $totalRealisasiOmzetBersihJenis = 0;
                                    $totalRealisasiJenisBiaya = 0;
                                    $totalGapJenisBiaya = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalJenis = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    foreach ($jenisBiayaData as $jenis => $data):
                                        $target = $data['target'];
                                        $realisasi = $data['realisasi'];
                                        $gap = $realisasi - $target;
                                        
                                        $targetOmzetBersihJenis = $totalTargetOmzetBersih;
                                        $realisasiOmzetBersihJenis = $totalRealisasiOmzetBersih;
                                        
                                        $persenTarget = $targetOmzetBersihJenis > 0 ? ($target / $targetOmzetBersihJenis) * 100 : 0;
                                        $persenRealisasi = $realisasiOmzetBersihJenis > 0 ? ($realisasi / $realisasiOmzetBersihJenis) * 100 : 0;
                                        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                        
                                        // Tentukan warna untuk % Biaya/Omzet (Target)
                                        $warnaPersenTarget = $persenTarget <= 20 ? 'text-success' : 
                                                        ($persenTarget <= 30 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Biaya/Omzet (Realisasi)
                                        $warnaPersenRealisasi = $persenRealisasi <= 20 ? 'text-success' : 
                                                            ($persenRealisasi <= 30 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Pencapaian
                                        $warnaPencapaian = '';
                                        if ($percentage <= 100) {
                                            $warnaPencapaian = 'text-success';
                                        } elseif ($percentage <= 120) {
                                            $warnaPencapaian = 'text-warning';
                                        } else {
                                            $warnaPencapaian = 'text-danger';
                                        }
                                        
                                        // Tentukan Status Efisiensi
                                        $status = '';
                                        $statusClass = '';
                                        if ($persenTarget <= $persenRealisasi) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalOverBudget++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalEfisien++;
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihJenis += $targetOmzetBersihJenis;
                                        $totalTargetJenisBiaya += $target;
                                        $totalRealisasiOmzetBersihJenis += $realisasiOmzetBersihJenis;
                                        $totalRealisasiJenisBiaya += $realisasi;
                                        $totalGapJenisBiaya += $gap;
                                        $totalPersenTargetSum += $persenTarget;
                                        $totalPersenRealisasiSum += $persenRealisasi;
                                        $totalJenis++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($jenis); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihJenis); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihJenis); ?></td>
                                        
                                        <!-- Biaya -->
                                        <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                        
                                        <!-- % Biaya/Omzet -->
                                        <td class="text-end <?php echo $warnaPersenTarget; ?>">
                                            <strong><?php echo number_format($persenTarget, 1); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaPersenRealisasi; ?>">
                                            <strong><?php echo number_format($persenRealisasi, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap Biaya -->
                                        <td class="text-end <?php echo $gap >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gap); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian -->
                                        <td class="text-end <?php echo $warnaPencapaian; ?>">
                                            <strong><?php echo number_format($percentage, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($jenisBiayaData)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data jenis biaya untuk periode yang dipilih!</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($jenisBiayaData)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataTargetOmzet = $totalJenis > 0 ? $totalTargetOmzetBersihJenis / $totalJenis : 0;
                                                echo formatFullNumber($rataTargetOmzet); 
                                                ?>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRealisasiOmzet = $totalJenis > 0 ? $totalRealisasiOmzetBersihJenis / $totalJenis : 0;
                                                echo formatFullNumber($rataRealisasiOmzet); 
                                                ?>
                                            </strong>
                                        </td>
                                        
                                        <!-- Biaya Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetJenisBiaya); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiJenisBiaya); ?></strong></td>
                                        
                                        <!-- % Biaya/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalJenis > 0 ? $totalPersenTargetSum / $totalJenis : 0;
                                                echo number_format($rataRataPersenTarget, 1); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalJenis > 0 ? $totalPersenRealisasiSum / $totalJenis : 0;
                                                echo number_format($rataRataPersenRealisasi, 1); ?>%
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap Biaya Total -->
                                        <td class="text-end <?php echo $totalGapJenisBiaya >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapJenisBiaya); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Total -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageJenisBiaya = $totalTargetJenisBiaya > 0 ? ($totalRealisasiJenisBiaya / $totalTargetJenisBiaya) * 100 : 0;
                                                $warnaPencapaianTotal = '';
                                                if ($totalPercentageJenisBiaya <= 100) {
                                                    $warnaPencapaianTotal = 'text-success';
                                                } elseif ($totalPercentageJenisBiaya <= 120) {
                                                    $warnaPencapaianTotal = 'text-warning';
                                                } else {
                                                    $warnaPencapaianTotal = 'text-danger';
                                                }
                                                ?>
                                                <span class="<?php echo $warnaPencapaianTotal; ?>"><?php echo number_format($totalPercentageJenisBiaya, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            if ($rataRataPersenTarget <= $rataRataPersenRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Target dan Realisasi Berdasarkan Jenis Biaya')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ANALISIS GAJI/HR PER ENTITAS ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Analisis Beban HR Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Container untuk dua grafik berdampingan dengan layout 2 kolom tetap -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Grafik 16: Beban Gaji/HR per Entitas (Target vs Realisasi) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Beban HR Per Entitas - Target vs Realisasi</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartGajiEntitas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grafik 17: Proporsi Biaya Gaji/HR per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi Biaya HR Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiGajiEntitas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Entitas</th>
                                    <th>Target Omzet Bersih</th>
                                    <th>Target Gaji/HR</th>
                                    <th>% Target</th>
                                    <th>Realisasi Omzet Bersih</th>
                                    <th>Realisasi Gaji/HR</th>
                                    <th>% Realisasi</th>
                                    <th>Gap Gaji</th>
                                    <th>% Pencapaian Gaji</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $totalTargetOmzetBersih = 0;
                                $totalTargetGaji = 0;
                                $totalRealisasiOmzetBersih = 0;
                                $totalRealisasiGaji = 0;
                                $totalGapGaji = 0;
                                $totalPersenGajiTargetSum = 0;
                                $totalPersenGajiRealisasiSum = 0;
                                $totalEntitas = 0;
                                $totalEfisien = 0;
                                $totalOverBudget = 0;
                                
                                // Gabungkan semua entitas yang ada data dari omzet bersih dan gaji
                                $allEntitasGaji = array_unique(array_merge(
                                    array_keys($gajiPerEntitas),
                                    array_keys($omzetBersihPerEntitas)
                                ));
                                
                                // Jika tidak ada data sama sekali, coba ambil dari $entitasDataOmzet
                                if (empty($allEntitasGaji) && !empty($entitasDataOmzet)) {
                                    $allEntitasGaji = array_keys($entitasDataOmzet);
                                }
                                
                                // Urutkan berdasarkan nama entitas
                                sort($allEntitasGaji);
                                
                                foreach ($allEntitasGaji as $entitas):
                                    // Data Gaji per entitas - jika tidak ada, set ke 0
                                    $targetGaji = isset($gajiPerEntitas[$entitas]) ? $gajiPerEntitas[$entitas]['target'] : 0;
                                    $realisasiGaji = isset($gajiPerEntitas[$entitas]) ? $gajiPerEntitas[$entitas]['realisasi'] : 0;
                                    $gapGaji = $realisasiGaji - $targetGaji;
                                    
                                    // Data Omzet Bersih per entitas
                                    $targetOmzetBersih = 0;
                                    $realisasiOmzetBersih = 0;
                                    
                                    // Coba dari omzetBersihPerEntitas
                                    if (isset($omzetBersihPerEntitas[$entitas])) {
                                        $targetOmzetBersih = $omzetBersihPerEntitas[$entitas]['target'];
                                        $realisasiOmzetBersih = $omzetBersihPerEntitas[$entitas]['realisasi'];
                                    } 
                                    // Fallback: coba dari entitasDataOmzet (omzet biasa)
                                    elseif (isset($entitasDataOmzet[$entitas])) {
                                        $targetOmzetBersih = $entitasDataOmzet[$entitas]['total_target'];
                                        $realisasiOmzetBersih = $entitasDataOmzet[$entitas]['total_realisasi'];
                                    }
                                    
                                    // Hitung % Gaji/Omzet (Target)
                                    $persenGajiTarget = $targetOmzetBersih > 0 ? ($targetGaji / $targetOmzetBersih) * 100 : 0;
                                    
                                    // Hitung % Gaji/Omzet (Realisasi)
                                    $persenGajiRealisasi = $realisasiOmzetBersih > 0 ? ($realisasiGaji / $realisasiOmzetBersih) * 100 : 0;
                                    
                                    // Hitung % Pencapaian Gaji
                                    $persenPencapaianGaji = $targetGaji > 0 ? ($realisasiGaji / $targetGaji) * 100 : 0;
                                    
                                    // Tentukan Status Efisiensi
                                    $status = '';
                                    $statusClass = '';
                                    
                                    if ($persenGajiTarget <= $persenGajiRealisasi) {
                                        $status = 'OVER BUDGET';
                                        $statusClass = 'badge-belum-selesai';
                                        $totalEfisien++;
                                    } else {
                                        $status = 'EFISIEN';
                                        $statusClass = 'badge-selesai';
                                        $totalOverBudget++;
                                    }
                                    
                                    // Akumulasi total
                                    $totalTargetOmzetBersih += $targetOmzetBersih;
                                    $totalTargetGaji += $targetGaji;
                                    $totalRealisasiOmzetBersih += $realisasiOmzetBersih;
                                    $totalRealisasiGaji += $realisasiGaji;
                                    $totalGapGaji += $gapGaji;
                                    $totalPersenGajiTargetSum += $persenGajiTarget;
                                    $totalPersenGajiRealisasiSum += $persenGajiRealisasi;
                                    $totalEntitas++;
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($entitas); ?></td>
                                    <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                    <td class="text-end"><?php echo formatFullNumber($targetGaji); ?></td>
                                    <td class="text-end <?php echo $persenGajiTarget <= 20 ? 'text-success' : ($persenGajiTarget <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo number_format($persenGajiTarget, 2); ?>%
                                    </td>
                                    <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                    <td class="text-end"><?php echo formatFullNumber($realisasiGaji); ?></td>
                                    <td class="text-end <?php echo $persenGajiRealisasi <= 20 ? 'text-success' : ($persenGajiRealisasi <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo number_format($persenGajiRealisasi, 2); ?>%
                                    </td>
                                    <td class="text-end <?php echo $gapGaji >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatFullNumber($gapGaji); ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="ms-2"><?php echo number_format($persenPencapaianGaji, 1); ?>%</span>
                                    </td>
                                    <td>
                                        <?php echo $status; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($allEntitasGaji)): ?>
                                <tr>
                                    <td colspan="11" class="text-center">Tidak ada data untuk periode yang dipilih</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersih); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalTargetGaji); ?></strong></td>
                                    <td class="text-end">
                                        <strong>
                                            <?php 
                                            $rataPersenGajiTarget = $totalEntitas > 0 ? $totalPersenGajiTargetSum / $totalEntitas : 0;
                                            echo number_format($rataPersenGajiTarget, 2); ?>%
                                        </strong>
                                    </td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersih); ?></strong></td>
                                    <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiGaji); ?></strong></td>
                                    <td class="text-end">
                                        <strong>
                                            <?php 
                                            $rataPersenGajiRealisasi = $totalEntitas > 0 ? $totalPersenGajiRealisasiSum / $totalEntitas : 0;
                                            echo number_format($rataPersenGajiRealisasi, 2); ?>%
                                        </strong>
                                    </td>
                                    <td class="text-end <?php echo $totalGapGaji >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatFullNumber($totalGapGaji); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end">
                                                <?php 
                                                $totalPersenPencapaianGaji = $totalTargetGaji > 0 ? ($totalRealisasiGaji / $totalTargetGaji) * 100 : 0;
                                                $warnaProgressTotal = $totalPersenPencapaianGaji >= 100 ? 'bg-success' : 
                                                    ($totalPersenPencapaianGaji >= 80 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                            <span class="ms-2"><strong><?php echo number_format($totalPersenPencapaianGaji, 1); ?>%</strong></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        // Tentukan status total berdasarkan perbandingan rata-rata
                                        $statusTotal = '';
                                        $statusClassTotal = '';
                                        if ($rataPersenGajiTarget <= $rataPersenGajiRealisasi) {
                                            $statusTotal = 'OVER BUDGET';
                                            $statusClassTotal = 'badge-belum-selesai';
                                        } else {
                                            $statusTotal = 'EFISIEN';
                                            $statusClassTotal = 'badge-selesai';
                                        }
                                        ?>
                                        <?php echo $statusTotal; ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        </div> -->
                        <div class="table-responsive">
                            <h5 class="mb-3">Tabel Biaya Gaji/HR Per Entitas</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">Gaji/HR</th>
                                        <th colspan="2" class="text-center">% Gaji/Omzet</th>
                                        <th rowspan="2" class="align-middle text-center">Gap Gaji</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian Gaji</th>
                                        <th rowspan="2" class="align-middle text-center">Keterangan</th>
                                    </tr>
                                    <tr style="background-color: #A78BFA; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalTargetOmzetBersih = 0;
                                    $totalTargetGaji = 0;
                                    $totalRealisasiOmzetBersih = 0;
                                    $totalRealisasiGaji = 0;
                                    $totalGapGaji = 0;
                                    $totalPersenGajiTargetSum = 0;
                                    $totalPersenGajiRealisasiSum = 0;
                                    $totalEntitas = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Gabungkan semua entitas yang ada data dari omzet bersih dan gaji
                                    $allEntitasGaji = array_unique(array_merge(
                                        array_keys($gajiPerEntitas),
                                        array_keys($omzetBersihPerEntitas)
                                    ));
                                    
                                    // Jika tidak ada data sama sekali, coba ambil dari $entitasDataOmzet
                                    if (empty($allEntitasGaji) && !empty($entitasDataOmzet)) {
                                        $allEntitasGaji = array_keys($entitasDataOmzet);
                                    }
                                    
                                    // Urutkan berdasarkan nama entitas
                                    sort($allEntitasGaji);
                                    
                                    foreach ($allEntitasGaji as $entitas):
                                        // Data Gaji per entitas - jika tidak ada, set ke 0
                                        $targetGaji = isset($gajiPerEntitas[$entitas]) ? $gajiPerEntitas[$entitas]['target'] : 0;
                                        $realisasiGaji = isset($gajiPerEntitas[$entitas]) ? $gajiPerEntitas[$entitas]['realisasi'] : 0;
                                        $gapGaji = $realisasiGaji - $targetGaji;
                                        
                                        // Data Omzet Bersih per entitas
                                        $targetOmzetBersih = 0;
                                        $realisasiOmzetBersih = 0;
                                        
                                        // Coba dari omzetBersihPerEntitas
                                        if (isset($omzetBersihPerEntitas[$entitas])) {
                                            $targetOmzetBersih = $omzetBersihPerEntitas[$entitas]['target'];
                                            $realisasiOmzetBersih = $omzetBersihPerEntitas[$entitas]['realisasi'];
                                        } 
                                        // Fallback: coba dari entitasDataOmzet (omzet biasa)
                                        elseif (isset($entitasDataOmzet[$entitas])) {
                                            $targetOmzetBersih = $entitasDataOmzet[$entitas]['total_target'];
                                            $realisasiOmzetBersih = $entitasDataOmzet[$entitas]['total_realisasi'];
                                        }
                                        
                                        // Hitung % Gaji/Omzet (Target)
                                        $persenGajiTarget = $targetOmzetBersih > 0 ? ($targetGaji / $targetOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Gaji/Omzet (Realisasi)
                                        $persenGajiRealisasi = $realisasiOmzetBersih > 0 ? ($realisasiGaji / $realisasiOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Pencapaian Gaji
                                        $persenPencapaianGaji = $targetGaji > 0 ? ($realisasiGaji / $targetGaji) * 100 : 0;
                                        
                                        // Tentukan warna untuk % Gaji/Omzet (Target)
                                        $warnaPersenTarget = $persenGajiTarget <= 20 ? 'text-success' : 
                                                        ($persenGajiTarget <= 30 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Gaji/Omzet (Realisasi)
                                        $warnaPersenRealisasi = $persenGajiRealisasi <= 20 ? 'text-success' : 
                                                            ($persenGajiRealisasi <= 30 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Pencapaian Gaji
                                        $warnaPencapaianGaji = '';
                                        if ($persenPencapaianGaji <= 100) {
                                            $warnaPencapaianGaji = 'text-success';
                                        } elseif ($persenPencapaianGaji <= 120) {
                                            $warnaPencapaianGaji = 'text-warning';
                                        } else {
                                            $warnaPencapaianGaji = 'text-danger';
                                        }
                                        
                                        // Tentukan Status Efisiensi
                                        $status = '';
                                        $statusClass = '';
                                        
                                        if ($persenGajiTarget <= $persenGajiRealisasi) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersih += $targetOmzetBersih;
                                        $totalTargetGaji += $targetGaji;
                                        $totalRealisasiOmzetBersih += $realisasiOmzetBersih;
                                        $totalRealisasiGaji += $realisasiGaji;
                                        $totalGapGaji += $gapGaji;
                                        $totalPersenGajiTargetSum += $persenGajiTarget;
                                        $totalPersenGajiRealisasiSum += $persenGajiRealisasi;
                                        $totalEntitas++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                        
                                        <!-- Gaji/HR -->
                                        <td class="text-end"><?php echo formatFullNumber($targetGaji); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiGaji); ?></td>
                                        
                                        <!-- % Gaji/Omzet -->
                                        <td class="text-end <?php echo $warnaPersenTarget; ?>">
                                            <strong><?php echo number_format($persenGajiTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaPersenRealisasi; ?>">
                                            <strong><?php echo number_format($persenGajiRealisasi, 2); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap Gaji -->
                                        <td class="text-end <?php echo $gapGaji >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gapGaji); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Gaji -->
                                        <td class="text-end <?php echo $warnaPencapaianGaji; ?>">
                                            <strong><?php echo number_format($persenPencapaianGaji, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allEntitasGaji)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data Gaji/HR untuk periode yang dipilih!</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allEntitasGaji)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersih); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersih); ?></strong></td>
                                        
                                        <!-- Gaji/HR Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetGaji); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiGaji); ?></strong></td>
                                        
                                        <!-- % Gaji/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenGajiTarget = $totalEntitas > 0 ? $totalPersenGajiTargetSum / $totalEntitas : 0;
                                                echo number_format($rataPersenGajiTarget, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenGajiRealisasi = $totalEntitas > 0 ? $totalPersenGajiRealisasiSum / $totalEntitas : 0;
                                                echo number_format($rataPersenGajiRealisasi, 2); ?>%
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap Gaji Total -->
                                        <td class="text-end <?php echo $totalGapGaji >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapGaji); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Gaji Total -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPersenPencapaianGaji = $totalTargetGaji > 0 ? ($totalRealisasiGaji / $totalTargetGaji) * 100 : 0;
                                                $warnaPencapaianTotal = '';
                                                if ($totalPersenPencapaianGaji <= 100) {
                                                    $warnaPencapaianTotal = 'text-success';
                                                } elseif ($totalPersenPencapaianGaji <= 120) {
                                                    $warnaPencapaianTotal = 'text-warning';
                                                } else {
                                                    $warnaPencapaianTotal = 'text-danger';
                                                }
                                                ?>
                                                <span class="<?php echo $warnaPencapaianTotal; ?>"><?php echo number_format($totalPersenPencapaianGaji, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            if ($rataPersenGajiTarget <= $rataPersenGajiRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Analisis Beban HR Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

 
        <!-- ==================== GRAFIK TREN BEBAN GAJI PER BULAN ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Beban HR Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-fixed">
                            <canvas id="chartTrenGajiBulanan"></canvas>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <?php
                        // Query untuk mengambil data omzet bersih per bulan dengan filter
                        $sqlOmzetBersihBulanan = "SELECT 
                            DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                            SUM(nilai_target) as total_target,
                            SUM(nilai_realisasi) as total_realisasi
                        FROM data_report 
                        WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                            OR LOWER(nama_akun) LIKE '%omzetbersih%'
                            OR nama_akun = 'Omzet Bersih')";
                        
                        $paramsOmzetBersihBulanan = [];
                        
                        // Tambahkan filter tanggal jika ada
                        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                            $sqlOmzetBersihBulanan .= " AND DATE(tanggal) BETWEEN ? AND ?";
                            $paramsOmzetBersihBulanan[] = $tanggal_awal;
                            $paramsOmzetBersihBulanan[] = $tanggal_akhir;
                        } elseif (!empty($tanggal_awal)) {
                            $sqlOmzetBersihBulanan .= " AND DATE(tanggal) >= ?";
                            $paramsOmzetBersihBulanan[] = $tanggal_awal;
                        } elseif (!empty($tanggal_akhir)) {
                            $sqlOmzetBersihBulanan .= " AND DATE(tanggal) <= ?";
                            $paramsOmzetBersihBulanan[] = $tanggal_akhir;
                        }
                        
                        $sqlOmzetBersihBulanan .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                        
                        try {
                            $stmtOmzetBersihBulanan = $pdo->prepare($sqlOmzetBersihBulanan);
                            $stmtOmzetBersihBulanan->execute($paramsOmzetBersihBulanan);
                            $dataOmzetBersihBulanan = $stmtOmzetBersihBulanan->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Konversi ke array asosiatif
                            $omzetBersihPerBulanFiltered = [];
                            foreach ($dataOmzetBersihBulanan as $row) {
                                $bulan = $row['bulan'];
                                $omzetBersihPerBulanFiltered[$bulan] = [
                                    'target' => (float)$row['total_target'],
                                    'realisasi' => (float)$row['total_realisasi']
                                ];
                            }
                            
                            // Fallback jika data kosong - gunakan data omzet biasa dengan filter
                            if (empty($omzetBersihPerBulanFiltered)) {
                                foreach ($bulanDataOmzet as $bulanKey => $data) {
                                    // Cek apakah bulan ini masuk dalam filter tanggal
                                    $includeBulan = true;
                                    $tahunBulan = (int)substr($bulanKey, 0, 4);
                                    $bulanAngka = (int)substr($bulanKey, 5, 2);
                                    
                                    // Buat tanggal pertama dan terakhir di bulan ini
                                    $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                    $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                    
                                    if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                        $includeBulan = false;
                                    }
                                    if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                        $includeBulan = false;
                                    }
                                    
                                    if ($includeBulan) {
                                        $omzetBersihPerBulanFiltered[$bulanKey] = $data;
                                    }
                                }
                            }
                            
                        } catch (PDOException $e) {
                            // Fallback jika query gagal
                            $omzetBersihPerBulanFiltered = [];
                            foreach ($bulanDataOmzet as $bulanKey => $data) {
                                // Cek filter tanggal untuk bulan ini
                                $includeBulan = true;
                                $tahunBulan = (int)substr($bulanKey, 0, 4);
                                $bulanAngka = (int)substr($bulanKey, 5, 2);
                                
                                // Buat tanggal pertama dan terakhir di bulan ini
                                $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                
                                if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                    $includeBulan = false;
                                }
                                if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                    $includeBulan = false;
                                }
                                
                                if ($includeBulan) {
                                    $omzetBersihPerBulanFiltered[$bulanKey] = $data;
                                }
                            }
                        }
                        
                        // Filter $gajiPerBulan berdasarkan tanggal (bulan yang ada dalam filter)
                        $gajiPerBulanFiltered = [];
                        foreach ($gajiPerBulan as $bulanKey => $data) {
                            // Cek apakah bulan ini masuk dalam filter tanggal
                            $includeBulan = true;
                            $tahunBulan = (int)substr($bulanKey, 0, 4);
                            $bulanAngka = (int)substr($bulanKey, 5, 2);
                            
                            // Buat tanggal pertama dan terakhir di bulan ini
                            $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                            $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                            
                            if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                $includeBulan = false;
                            }
                            if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                $includeBulan = false;
                            }
                            
                            if ($includeBulan) {
                                $gajiPerBulanFiltered[$bulanKey] = $data;
                            }
                        }
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #F59E0B; color: white;">
                                        <th>No</th>
                                        <th>Bulan</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target Gaji/HR</th>
                                        <th>% Target</th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi Gaji/HR</th>
                                        <th>% Realisasi</th>
                                        <th>Gap Gaji</th>
                                        <th>% Pencapaian Gaji</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihBulanan = 0;
                                    $totalTargetBulananGaji = 0;
                                    $totalRealisasiOmzetBersihBulanan = 0;
                                    $totalRealisasiBulananGaji = 0;
                                    $totalGapBulananGaji = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalBulan = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Urutkan bulan
                                    ksort($gajiPerBulanFiltered);
                                    
                                    foreach ($gajiPerBulanFiltered as $bulanKey => $dataBulan):
                                        // Data Gaji per bulan
                                        $targetBulan = $dataBulan['target'];
                                        $realisasiBulan = $dataBulan['realisasi'];
                                        $gapBulan = $dataBulan['gap'];
                                        
                                        // Data Omzet Bersih per bulan (dengan filter)
                                        $targetOmzetBersihBulanan = isset($omzetBersihPerBulanFiltered[$bulanKey]) ? $omzetBersihPerBulanFiltered[$bulanKey]['target'] : 0;
                                        $realisasiOmzetBersihBulanan = isset($omzetBersihPerBulanFiltered[$bulanKey]) ? $omzetBersihPerBulanFiltered[$bulanKey]['realisasi'] : 0;
                                        
                                        // Hitung %_Target (Target Gaji / Target Omzet Bersih)
                                        $persenTarget = $targetOmzetBersihBulanan > 0 ? ($targetBulan / $targetOmzetBersihBulanan) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi Gaji / Realisasi Omzet Bersih)
                                        $persenRealisasi = $realisasiOmzetBersihBulanan > 0 ? ($realisasiBulan / $realisasiOmzetBersihBulanan) * 100 : 0;
                                        
                                        // Hitung % Pencapaian Gaji (Realisasi Gaji / Target Gaji)
                                        $percentageBulan = $targetBulan > 0 ? ($realisasiBulan / $targetBulan) * 100 : 0;
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                        $status = '';
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        // ATURAN BARU: 
                                        // Jika % Realisasi <= % Target → EFISIEN (biaya gaji lebih kecil dari yang dianggarkan relatif terhadap omzet)
                                        // Jika % Realisasi > % Target → OVER BUDGET (biaya gaji lebih besar dari yang dianggarkan)
                                        if ($persenRealisasi <= $persenTarget) {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $statusText = 'Biaya gaji efisien terhadap omzet';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $statusText = 'Biaya gaji melebihi budget relatif terhadap omzet';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Format nama bulan
                                        $tahun = substr($bulanKey, 0, 4);
                                        $bulanAngka = (int)substr($bulanKey, 5, 2);
                                        $namaBulan = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihBulanan += $targetOmzetBersihBulanan;
                                        $totalTargetBulananGaji += $targetBulan;
                                        $totalRealisasiOmzetBersihBulanan += $realisasiOmzetBersihBulanan;
                                        $totalRealisasiBulananGaji += $realisasiBulan;
                                        $totalGapBulananGaji += $gapBulan;
                                        $totalPersenTargetSum += $persenTarget;
                                        $totalPersenRealisasiSum += $persenRealisasi;
                                        $totalBulan++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($namaBulan . ' ' . $tahun); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihBulanan); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetBulan); ?></td>
                                        <td class="text-end <?php echo $persenTarget <= 20 ? 'text-success' : ($persenTarget <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihBulanan); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiBulan); ?></td>
                                        <td class="text-end <?php echo $persenRealisasi <= 20 ? 'text-success' : ($persenRealisasi <= 30 ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo number_format($persenRealisasi, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $gapBulan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gapBulan); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo number_format($percentageBulan, 1); ?>%
                                        </td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if (!empty($gajiPerBulanFiltered)): ?>
                                <tfoot style="background-color: #FFFBEB;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihBulanan); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBulananGaji); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalBulan > 0 ? $totalPersenTargetSum / $totalBulan : 0;
                                                echo number_format($rataRataPersenTarget, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihBulanan); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBulananGaji); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalBulan > 0 ? $totalPersenRealisasiSum / $totalBulan : 0;
                                                echo number_format($rataRataPersenRealisasi, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapBulananGaji >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapBulananGaji); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageBulanan = $totalTargetBulananGaji > 0 ? ($totalRealisasiBulananGaji / $totalTargetBulananGaji) * 100 : 0;
                                                echo number_format($totalPercentageBulanan, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            $statusTextTotal = '';
                                            
                                            if ($rataRataPersenRealisasi <= $rataRataPersenTarget) {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                                $statusTextTotal = 'Rata-rata biaya gaji efisien terhadap omzet';
                                            } else {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                                $statusTextTotal = 'Rata-rata biaya gaji melebihi budget relatif terhadap omzet';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Beban HR Per Bulan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ANALISIS BIAYA OPERASIONAL ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tabel Biaya Operasional Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Container untuk dua grafik berdampingan dengan layout 2 kolom tetap -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Grafik 19: Biaya Operasional per Entitas (Target vs Realisasi) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Biaya Operasional Per Entitas - Target vs Realisasi</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartBiayaOpsEntitas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grafik 20: Proporsi Biaya Operasional per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi Biaya Operasional Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiBiayaOpsEntitas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Tabel Analisis Biaya Operasional per Entitas -->
                        <div class="table-responsive">
                            <h5 class="mb-3">Analisis Biaya Operasional Per Entitas</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">Biaya Operasional</th>
                                        <th colspan="2" class="text-center">% Operasional/Omzet</th>
                                        <th rowspan="2" class="align-middle text-center">Gap Biaya Operasional</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian Ops</th>
                                        <th rowspan="2" class="align-middle text-center">Keterangan</th>
                                    </tr>
                                    <tr style="background-color: #A78BFA; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalTargetOmzetBersihOps = 0;
                                    $totalTargetOps = 0;
                                    $totalRealisasiOmzetBersihOps = 0;
                                    $totalRealisasiOps = 0;
                                    $totalGapOps = 0;
                                    $totalPersenOpsTargetSum = 0;
                                    $totalPersenOpsRealisasiSum = 0;
                                    $totalEntitasOps = 0;
                                    $totalEfisienOps = 0;
                                    $totalOverBudgetOps = 0;
                                    
                                    // Gabungkan semua entitas yang ada data dari omzet bersih dan biaya operasional
                                    $allEntitasOps = array_unique(array_merge(
                                        array_keys($biayaOpsPerEntitas),
                                        array_keys($omzetBersihPerEntitas)
                                    ));
                                    
                                    // Jika tidak ada data sama sekali, coba ambil dari $entitasDataOmzet
                                    if (empty($allEntitasOps) && !empty($entitasDataOmzet)) {
                                        $allEntitasOps = array_keys($entitasDataOmzet);
                                    }
                                    
                                    // Urutkan berdasarkan nama entitas
                                    sort($allEntitasOps);
                                    
                                    foreach ($allEntitasOps as $entitas):
                                        // Data Biaya Operasional per entitas - jika tidak ada, set ke 0
                                        $targetOps = isset($biayaOpsPerEntitas[$entitas]) ? $biayaOpsPerEntitas[$entitas]['target'] : 0;
                                        $realisasiOps = isset($biayaOpsPerEntitas[$entitas]) ? $biayaOpsPerEntitas[$entitas]['realisasi'] : 0;
                                        $gapOps = $realisasiOps - $targetOps;
                                        
                                        // Data Omzet Bersih per entitas
                                        $targetOmzetBersih = 0;
                                        $realisasiOmzetBersih = 0;
                                        
                                        // Coba dari omzetBersihPerEntitas
                                        if (isset($omzetBersihPerEntitas[$entitas])) {
                                            $targetOmzetBersih = $omzetBersihPerEntitas[$entitas]['target'];
                                            $realisasiOmzetBersih = $omzetBersihPerEntitas[$entitas]['realisasi'];
                                        } 
                                        // Fallback: coba dari entitasDataOmzet (omzet biasa)
                                        elseif (isset($entitasDataOmzet[$entitas])) {
                                            $targetOmzetBersih = $entitasDataOmzet[$entitas]['total_target'];
                                            $realisasiOmzetBersih = $entitasDataOmzet[$entitas]['total_realisasi'];
                                        }
                                        
                                        // Hitung % Operasional/Omzet (Target)
                                        $persenOpsTarget = $targetOmzetBersih > 0 ? ($targetOps / $targetOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Operasional/Omzet (Realisasi)
                                        $persenOpsRealisasi = $realisasiOmzetBersih > 0 ? ($realisasiOps / $realisasiOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Pencapaian Operasional
                                        $persenPencapaianOps = $targetOps > 0 ? ($realisasiOps / $targetOps) * 100 : 0;
                                        
                                        // Tentukan warna untuk % Target
                                        $warnaPersenTarget = $persenOpsTarget <= 10 ? 'text-success' : 
                                                        ($persenOpsTarget <= 20 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Realisasi
                                        $warnaPersenRealisasi = $persenOpsRealisasi <= 10 ? 'text-success' : 
                                                            ($persenOpsRealisasi <= 20 ? 'text-warning' : 'text-danger');
                                        
                                        // Tentukan warna untuk % Pencapaian Ops
                                        $warnaPencapaianOps = '';
                                        if ($persenPencapaianOps <= 100) {
                                            $warnaPencapaianOps = 'text-success';
                                        } elseif ($persenPencapaianOps <= 120) {
                                            $warnaPencapaianOps = 'text-warning';
                                        } else {
                                            $warnaPencapaianOps = 'text-danger';
                                        }
                                        
                                        // Tentukan Status Efisiensi
                                        $status = '';
                                        $statusClass = '';
                                        
                                        if ($persenOpsTarget <= $persenOpsRealisasi) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $totalEfisienOps++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $totalOverBudgetOps++;
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihOps += $targetOmzetBersih;
                                        $totalTargetOps += $targetOps;
                                        $totalRealisasiOmzetBersihOps += $realisasiOmzetBersih;
                                        $totalRealisasiOps += $realisasiOps;
                                        $totalGapOps += $gapOps;
                                        $totalPersenOpsTargetSum += $persenOpsTarget;
                                        $totalPersenOpsRealisasiSum += $persenOpsRealisasi;
                                        $totalEntitasOps++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                        
                                        <!-- Biaya Operasional -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOps); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOps); ?></td>
                                        
                                        <!-- % Operasional/Omzet -->
                                        <td class="text-end <?php echo $warnaPersenTarget; ?>">
                                            <strong><?php echo number_format($persenOpsTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaPersenRealisasi; ?>">
                                            <strong><?php echo number_format($persenOpsRealisasi, 2); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap Biaya Operasional -->
                                        <td class="text-end <?php echo $gapOps >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gapOps); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Ops -->
                                        <td class="text-end <?php echo $warnaPencapaianOps; ?>">
                                            <strong><?php echo number_format($persenPencapaianOps, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allEntitasOps)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data biaya operasional untuk periode yang dipilih!</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allEntitasOps)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihOps); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihOps); ?></strong></td>
                                        
                                        <!-- Biaya Operasional Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOps); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOps); ?></strong></td>
                                        
                                        <!-- % Operasional/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenOpsTarget = $totalEntitasOps > 0 ? $totalPersenOpsTargetSum / $totalEntitasOps : 0;
                                                echo number_format($rataPersenOpsTarget, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenOpsRealisasi = $totalEntitasOps > 0 ? $totalPersenOpsRealisasiSum / $totalEntitasOps : 0;
                                                echo number_format($rataPersenOpsRealisasi, 2); ?>%
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap Biaya Operasional Total -->
                                        <td class="text-end <?php echo $totalGapOps >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapOps); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Ops Total -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPersenPencapaianOps = $totalTargetOps > 0 ? ($totalRealisasiOps / $totalTargetOps) * 100 : 0;
                                                $warnaPencapaianTotal = '';
                                                if ($totalPersenPencapaianOps <= 100) {
                                                    $warnaPencapaianTotal = 'text-success';
                                                } elseif ($totalPersenPencapaianOps <= 120) {
                                                    $warnaPencapaianTotal = 'text-warning';
                                                } else {
                                                    $warnaPencapaianTotal = 'text-danger';
                                                }
                                                ?>
                                                <span class="<?php echo $warnaPencapaianTotal; ?>"><?php echo number_format($totalPersenPencapaianOps, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            
                                            if ($rataPersenOpsTarget <= $rataPersenOpsRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Analisis Biaya Operasional Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== GRAFIK TREN BIAYA OPERASIONAL PER BULAN ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Biaya Operasional Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                            <div class="chart-container-fixed">
                                <canvas id="chartTrenBiayaOpsBulanan"></canvas>
                            </div>
                            <div class="card-body p-2">
                            <?php
                            // Query untuk mengambil data omzet bersih per bulan dengan filter
                            $sqlOmzetBersihBulananOps = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                                SUM(nilai_target) as total_target,
                                SUM(nilai_realisasi) as total_realisasi
                            FROM data_report 
                            WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                                OR LOWER(nama_akun) LIKE '%omzetbersih%'
                                OR nama_akun = 'Omzet Bersih')";
                            
                            $paramsOmzetBersihBulananOps = [];
                            
                            // Tambahkan filter tanggal jika ada
                            if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                $sqlOmzetBersihBulananOps .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                $paramsOmzetBersihBulananOps[] = $tanggal_awal;
                                $paramsOmzetBersihBulananOps[] = $tanggal_akhir;
                            } elseif (!empty($tanggal_awal)) {
                                $sqlOmzetBersihBulananOps .= " AND DATE(tanggal) >= ?";
                                $paramsOmzetBersihBulananOps[] = $tanggal_awal;
                            } elseif (!empty($tanggal_akhir)) {
                                $sqlOmzetBersihBulananOps .= " AND DATE(tanggal) <= ?";
                                $paramsOmzetBersihBulananOps[] = $tanggal_akhir;
                            }
                            
                            $sqlOmzetBersihBulananOps .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                            
                            try {
                                $stmtOmzetBersihBulananOps = $pdo->prepare($sqlOmzetBersihBulananOps);
                                $stmtOmzetBersihBulananOps->execute($paramsOmzetBersihBulananOps);
                                $dataOmzetBersihBulananOps = $stmtOmzetBersihBulananOps->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Konversi ke array asosiatif
                                $omzetBersihPerBulanOpsFiltered = [];
                                foreach ($dataOmzetBersihBulananOps as $row) {
                                    $bulan = $row['bulan'];
                                    $omzetBersihPerBulanOpsFiltered[$bulan] = [
                                        'target' => (float)$row['total_target'],
                                        'realisasi' => (float)$row['total_realisasi']
                                    ];
                                }
                                
                                // Fallback jika data kosong - gunakan data omzet biasa dengan filter
                                if (empty($omzetBersihPerBulanOpsFiltered)) {
                                    foreach ($bulanDataOmzet as $bulanKey => $data) {
                                        // Cek apakah bulan ini masuk dalam filter tanggal
                                        $includeBulan = true;
                                        $tahunBulan = (int)substr($bulanKey, 0, 4);
                                        $bulanAngka = (int)substr($bulanKey, 5, 2);
                                        
                                        // Buat tanggal pertama dan terakhir di bulan ini
                                        $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                        $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                        
                                        if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                            $includeBulan = false;
                                        }
                                        if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                            $includeBulan = false;
                                        }
                                        
                                        if ($includeBulan) {
                                            $omzetBersihPerBulanOpsFiltered[$bulanKey] = $data;
                                        }
                                    }
                                }
                                
                            } catch (PDOException $e) {
                                // Fallback jika query gagal
                                $omzetBersihPerBulanOpsFiltered = [];
                                foreach ($bulanDataOmzet as $bulanKey => $data) {
                                    // Cek filter tanggal untuk bulan ini
                                    $includeBulan = true;
                                    $tahunBulan = (int)substr($bulanKey, 0, 4);
                                    $bulanAngka = (int)substr($bulanKey, 5, 2);
                                    
                                    // Buat tanggal pertama dan terakhir di bulan ini
                                    $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                    $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                    
                                    if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                        $includeBulan = false;
                                    }
                                    if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                        $includeBulan = false;
                                    }
                                    
                                    if ($includeBulan) {
                                        $omzetBersihPerBulanOpsFiltered[$bulanKey] = $data;
                                    }
                                }
                            }
                            
                            // Filter $biayaOpsPerBulan berdasarkan tanggal (bulan yang ada dalam filter)
                            $biayaOpsPerBulanFiltered = [];
                            foreach ($biayaOpsPerBulan as $bulanKey => $data) {
                                // Cek apakah bulan ini masuk dalam filter tanggal
                                $includeBulan = true;
                                $tahunBulan = (int)substr($bulanKey, 0, 4);
                                $bulanAngka = (int)substr($bulanKey, 5, 2);
                                
                                // Buat tanggal pertama dan terakhir di bulan ini
                                $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                
                                if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                    $includeBulan = false;
                                }
                                if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                    $includeBulan = false;
                                }
                                
                                if ($includeBulan) {
                                    $biayaOpsPerBulanFiltered[$bulanKey] = $data;
                                }
                            }
                            ?>
                            
                            <?php if (count($dataBiayaOps) == 0): ?>
                            <div class="alert alert-warning mb-4">
                                <strong>Data tidak ditemukan!</strong><br>
                                Tidak ada data biaya operasional untuk periode yang dipilih.<br>
                                <strong>Definisi Biaya Operasional:</strong> Semua biaya yang BUKAN gaji/hr DAN BUKAN pemasaran.
                            </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Bulan</th>
                                            <th>Target Omzet Bersih</th>
                                            <th>Target Biaya Ops</th>
                                            <th>% Target</th>
                                            <th>Realisasi Omzet Bersih</th>
                                            <th>Realisasi Biaya Ops</th>
                                            <th>% Realisasi</th>
                                            <th>Gap Biaya Ops</th>
                                            <th>% Pencapaian Biaya Ops</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        $totalTargetOmzetBersihBulananOps = 0;
                                        $totalTargetBulananOps = 0;
                                        $totalRealisasiOmzetBersihBulananOps = 0;
                                        $totalRealisasiBulananOps = 0;
                                        $totalGapBulananOps = 0;
                                        $totalPersenTargetSum = 0;
                                        $totalPersenRealisasiSum = 0;
                                        $totalBulanOps = 0;
                                        $totalEfisienOps = 0;
                                        $totalOverBudgetOps = 0;
                                        
                                        // Urutkan bulan
                                        ksort($biayaOpsPerBulanFiltered);
                                        
                                        foreach ($biayaOpsPerBulanFiltered as $bulanKey => $dataBulan):
                                            // Data Biaya Operasional per bulan
                                            $targetBulan = $dataBulan['target'];
                                            $realisasiBulan = $dataBulan['realisasi'];
                                            $gapBulan = $dataBulan['gap'];
                                            
                                            // Data Omzet Bersih per bulan (dengan filter)
                                            $targetOmzetBersihBulanan = isset($omzetBersihPerBulanOpsFiltered[$bulanKey]) ? $omzetBersihPerBulanOpsFiltered[$bulanKey]['target'] : 0;
                                            $realisasiOmzetBersihBulanan = isset($omzetBersihPerBulanOpsFiltered[$bulanKey]) ? $omzetBersihPerBulanOpsFiltered[$bulanKey]['realisasi'] : 0;
                                            
                                            // Hitung %_Target (Target Biaya Ops / Target Omzet Bersih)
                                            $persenTarget = $targetOmzetBersihBulanan > 0 ? ($targetBulan / $targetOmzetBersihBulanan) * 100 : 0;
                                            
                                            // Hitung %_Realisasi (Realisasi Biaya Ops / Realisasi Omzet Bersih)
                                            $persenRealisasi = $realisasiOmzetBersihBulanan > 0 ? ($realisasiBulan / $realisasiOmzetBersihBulanan) * 100 : 0;
                                            
                                            // Hitung % Pencapaian Biaya Ops (Realisasi Biaya Ops / Target Biaya Ops)
                                            $percentageBulan = $targetBulan > 0 ? ($realisasiBulan / $targetBulan) * 100 : 0;
                                            
                                            // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                            $status = '';
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            // ATURAN: 
                                            // Jika % Realisasi <= % Target → EFISIEN (biaya ops lebih kecil dari yang dianggarkan relatif terhadap omzet)
                                            // Jika % Realisasi > % Target → OVER BUDGET (biaya ops lebih besar dari yang dianggarkan)
                                            if ($persenRealisasi <= $persenTarget) {
                                                $status = 'EFISIEN';
                                                $statusClass = 'badge-selesai';
                                                $statusText = 'Biaya operasional efisien terhadap omzet';
                                                $totalEfisienOps++;
                                            } else {
                                                $status = 'OVER BUDGET';
                                                $statusClass = 'badge-belum-selesai';
                                                $statusText = 'Biaya operasional melebihi budget relatif terhadap omzet';
                                                $totalOverBudgetOps++;
                                            }
                                            
                                            // Format nama bulan
                                            $tahun = substr($bulanKey, 0, 4);
                                            $bulanAngka = (int)substr($bulanKey, 5, 2);
                                            $namaBulan = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                            
                                            // Hitung rata-rata harian
                                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulanAngka, $tahun);
                                            $rataHarian = $daysInMonth > 0 ? $realisasiBulan / $daysInMonth : 0;
                                            
                                            // Akumulasi total
                                            $totalTargetOmzetBersihBulananOps += $targetOmzetBersihBulanan;
                                            $totalTargetBulananOps += $targetBulan;
                                            $totalRealisasiOmzetBersihBulananOps += $realisasiOmzetBersihBulanan;
                                            $totalRealisasiBulananOps += $realisasiBulan;
                                            $totalGapBulananOps += $gapBulan;
                                            $totalPersenTargetSum += $persenTarget;
                                            $totalPersenRealisasiSum += $persenRealisasi;
                                            $totalBulanOps++;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($namaBulan . ' ' . $tahun); ?></strong></td>
                                            <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihBulanan); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($targetBulan); ?></td>
                                            <td class="text-end <?php echo $persenTarget <= 15 ? 'text-success' : ($persenTarget <= 25 ? 'text-warning' : 'text-danger'); ?>">
                                                <strong><?php echo number_format($persenTarget, 2); ?>%</strong>
                                            </td>
                                            <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihBulanan); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($realisasiBulan); ?></td>
                                            <td class="text-end <?php echo $persenRealisasi <= 15 ? 'text-success' : ($persenRealisasi <= 25 ? 'text-warning' : 'text-danger'); ?>">
                                                <strong><?php echo number_format($persenRealisasi, 2); ?>%</strong>
                                            </td>
                                            <td class="text-end <?php echo $gapBulan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatFullNumber($gapBulan); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($percentageBulan, 1); ?>%
                                            </td>
                                            <td>
                                                <?php echo $status; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($biayaOpsPerBulanFiltered)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center">
                                                <div class="alert alert-warning">
                                                    <strong>Tidak ada data biaya operasional untuk periode yang dipilih!</strong><br>
                                                    <small>Data biaya operasional mencakup semua biaya yang BUKAN gaji/hr dan BUKAN pemasaran.</small>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($biayaOpsPerBulanFiltered)): ?>
                                    <tfoot style="background-color: #E0F2FE;">
                                        <tr class="table-primary">
                                            <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihBulananOps); ?></strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBulananOps); ?></strong></td>
                                            <td class="text-end">
                                                <strong>
                                                    <?php 
                                                    $rataRataPersenTarget = $totalBulanOps > 0 ? $totalPersenTargetSum / $totalBulanOps : 0;
                                                    echo number_format($rataRataPersenTarget, 2); ?>%
                                                </strong>
                                            </td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihBulananOps); ?></strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBulananOps); ?></strong></td>
                                            <td class="text-end">
                                                <strong>
                                                    <?php 
                                                    $rataRataPersenRealisasi = $totalBulanOps > 0 ? $totalPersenRealisasiSum / $totalBulanOps : 0;
                                                    echo number_format($rataRataPersenRealisasi, 2); ?>%
                                                </strong>
                                            </td>
                                            <td class="text-end <?php echo $totalGapBulananOps >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo formatFullNumber($totalGapBulananOps); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <strong>
                                                    <?php 
                                                    $totalPercentageBulanan = $totalTargetBulananOps > 0 ? ($totalRealisasiBulananOps / $totalTargetBulananOps) * 100 : 0;
                                                    echo number_format($totalPercentageBulanan, 1); ?>%
                                                </strong>
                                            </td>
                                            <td>
                                                <?php 
                                                // Tentukan status total berdasarkan perbandingan rata-rata
                                                $statusTotal = '';
                                                $statusClassTotal = '';
                                                $statusTextTotal = '';
                                                
                                                if ($rataRataPersenRealisasi <= $rataRataPersenTarget) {
                                                    $statusTotal = 'EFISIEN';
                                                    $statusClassTotal = 'badge-selesai';
                                                    $statusTextTotal = 'Rata-rata biaya operasional efisien terhadap omzet';
                                                } else {
                                                    $statusTotal = 'OVER BUDGET';
                                                    $statusClassTotal = 'badge-belum-selesai';
                                                    $statusTextTotal = 'Rata-rata biaya operasional melebihi budget relatif terhadap omzet';
                                                }
                                                ?>
                                                <?php echo $statusTotal; ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                            <?php if (!empty($dataHasilAnalisis)): ?>
                                <?php foreach ($dataHasilAnalisis as $row): ?>
                                    <?php if(strtolower($row['title']) == strtolower('Tren Biaya Operasional Per Bulan')): ?>
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                            </div>
                                            <div class="card-body p-2">
                                            <?php 
                                                $cleanedContent = nl2br($row['analisis']);
                                                $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                                $cleanedContent = str_replace('\n', '', $cleanedContent);
                                                $cleanedContent = str_replace('"', '', $cleanedContent);
                                                echo $cleanedContent; 
                                                ?>
                                            </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

 

        <!-- ==================== CARD BIAYA PEMASARAN KOMPLIT ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Biaya Pemasaran Per Entitas
                        </h5>
                    </div>
                    <div class="card-body p-2">
                        
                        <!-- GRAFIK 2 KOLOM FIX: Proporsi Biaya Pemasaran -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Grafik Batang Biaya Pemasaran per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100" style="border: 1px solid #8B5CF6;">
                                    <div class="card-header py-2" style="background-color: #8B5CF6; color: white;">
                                        <h6 class="card-title mb-0">Biaya Pemasaran Per Entitas - Target vs Realisasi</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartBiayaMarketingEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1 small">
                                                <strong>Total Entitas:</strong> <?php echo count($biayaMarketingPerEntitas); ?>
                                            </p>
                                            <p class="text-muted mb-0 small">
                                                <strong>Entitas dengan Biaya Tertinggi:</strong> 
                                                <?php 
                                                if (!empty($biayaMarketingPerEntitas)) {
                                                    $entitasKeys = array_keys($biayaMarketingPerEntitas);
                                                    $entitasTertinggi = $entitasKeys[0];
                                                    $bebanTertinggi = $biayaMarketingPerEntitas[$entitasTertinggi]['realisasi'];
                                                    echo htmlspecialchars($entitasTertinggi) . ' (' . formatFullNumber($bebanTertinggi) . ')';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Grafik Pie untuk Proporsi Biaya Pemasaran per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100" style="border: 1px solid #8B5CF6;">
                                    <div class="card-header py-2" style="background-color: #8B5CF6; color: white;">
                                        <h6 class="card-title mb-0">Proporsi Biaya Pemasaran Per Entitas</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiBiayaMarketingEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1 small">
                                                <strong>Total Biaya Pemasaran:</strong> <?php echo formatFullNumber($totalRealisasiBiayaMarketing); ?>
                                            </p>
                                            <p class="text-muted mb-0 small">
                                                <strong>% dari Total Biaya:</strong> <?php echo number_format($percentageOfTotalBiayaMarketing, 1); ?>%
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- TABEL UTAMA: Biaya Pemasaran per Entitas -->
                        <h6 class="mb-3 mt-4" style="color: #6D28D9;"><strong>Tabel Detail Biaya Pemasaran Per Entitas</strong></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">Biaya Pemasaran</th>
                                        <th colspan="2" class="text-center">% Marketing/Omzet</th>
                                        <th rowspan="2" class="align-middle text-center">Gap Marketing</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian</th>
                                        <th rowspan="2" class="align-middle text-center">Katerangan</th>
                                    </tr>
                                    <tr style="background-color: #A78BFA; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersih = 0;
                                    $totalRealisasiOmzetBersih = 0;
                                    $totalTargetMarketing = 0;
                                    $totalRealisasiMarketing = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalROASSum = 0;
                                    $totalGapMarketing = 0;
                                    $totalPersenPencapaianSum = 0;
                                    $totalEntitas = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Urutkan berdasarkan realisasi marketing tertinggi
                                    uasort($biayaMarketingPerEntitas, function($a, $b) {
                                        return $b['realisasi'] <=> $a['realisasi'];
                                    });
                                    
                                    foreach ($biayaMarketingPerEntitas as $entitas => $data):
                                        // Data Biaya Pemasaran
                                        $targetMarketing = $data['target'];
                                        $realisasiMarketing = $data['realisasi'];
                                        $gapMarketing = $data['gap'];
                                        
                                        // Data Omzet Bersih untuk entitas ini
                                        $targetOmzetBersih = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['target'] : 0;
                                        $realisasiOmzetBersih = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        // Jika tidak ada data omzet bersih, gunakan data omzet biasa
                                        if ($realisasiOmzetBersih == 0 && isset($entitasDataOmzet[$entitas])) {
                                            $targetOmzetBersih = $entitasDataOmzet[$entitas]['total_target'];
                                            $realisasiOmzetBersih = $entitasDataOmzet[$entitas]['total_realisasi'];
                                        }
                                        
                                        // Hitung % Marketing/Omzet (Target)
                                        $persenTarget = $targetOmzetBersih > 0 ? ($targetMarketing / $targetOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Marketing/Omzet (Realisasi)
                                        $persenRealisasi = $realisasiOmzetBersih > 0 ? ($realisasiMarketing / $realisasiOmzetBersih) * 100 : 0;
                                        
                                        // Hitung ROAS (Return on Ad Spend)
                                        $roas = $realisasiMarketing > 0 ? ($realisasiOmzetBersih / $realisasiMarketing) : 0;
                                        
                                        // Hitung % Pencapaian Marketing
                                        $persenPencapaian = $targetMarketing > 0 ? ($realisasiMarketing / $targetMarketing) * 100 : 0;
                                        
                                        // Tentukan Status Efisiensi
                                        $status = '';
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        // ATURAN: Jika % Realisasi <= % Target → EFISIEN, jika tidak → OVER BUDGET
                                        if ($persenRealisasi <= $persenTarget) {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $statusText = 'Biaya pemasaran efisien terhadap omzet';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $statusText = 'Biaya pemasaran melebihi budget relatif terhadap omzet';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Warna untuk % Target dan % Realisasi
                                        $warnaPersenTarget = $persenTarget <= 10 ? 'text-success' : ($persenTarget <= 20 ? 'text-warning' : 'text-danger');
                                        $warnaPersenRealisasi = $persenRealisasi <= 10 ? 'text-success' : ($persenRealisasi <= 20 ? 'text-warning' : 'text-danger');
                                        
                                        // Warna untuk ROAS
                                        $warnaROAS = '';
                                        if ($roas >= 5) {
                                            $warnaROAS = 'text-success';
                                        } elseif ($roas >= 3) {
                                            $warnaROAS = 'text-warning';
                                        } else {
                                            $warnaROAS = 'text-danger';
                                        }
                                        
                                        // Warna untuk % Pencapaian
                                        $warnaPencapaian = '';
                                        if ($persenPencapaian <= 100) {
                                            $warnaPencapaian = 'text-success';
                                        } elseif ($persenPencapaian <= 120) {
                                            $warnaPencapaian = 'text-warning';
                                        } else {
                                            $warnaPencapaian = 'text-danger';
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersih += $targetOmzetBersih;
                                        $totalRealisasiOmzetBersih += $realisasiOmzetBersih;
                                        $totalTargetMarketing += $targetMarketing;
                                        $totalRealisasiMarketing += $realisasiMarketing;
                                        $totalPersenTargetSum += $persenTarget;
                                        $totalPersenRealisasiSum += $persenRealisasi;
                                        $totalROASSum += $roas;
                                        $totalGapMarketing += $gapMarketing;
                                        $totalPersenPencapaianSum += $persenPencapaian;
                                        $totalEntitas++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                        
                                        <!-- Biaya Pemasaran -->
                                        <td class="text-end"><?php echo formatFullNumber($targetMarketing); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiMarketing); ?></td>
                                        
                                        <!-- % Marketing/Omzet -->
                                        <td class="text-end <?php echo $warnaPersenTarget; ?>">
                                            <strong><?php echo number_format($persenTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaPersenRealisasi; ?>">
                                            <strong><?php echo number_format($persenRealisasi, 2); ?>%</strong>
                                        </td>
                                                                            
                                        <!-- Gap Marketing -->
                                        <td class="text-end <?php echo $gapMarketing >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gapMarketing); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian -->
                                        <td class="text-end <?php echo $warnaPencapaian; ?>">
                                            <strong><?php echo number_format($persenPencapaian, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($biayaMarketingPerEntitas)): ?>
                                    <tr>
                                        <td colspan="13" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data biaya pemasaran untuk periode yang dipilih!</strong><br>
                                                <small class="small">Data biaya pemasaran mencakup: pemasaran, marketing, iklan, promosi, dan sejenisnya.</small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($biayaMarketingPerEntitas)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersih); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersih); ?></strong></td>
                                        
                                        <!-- Biaya Pemasaran Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetMarketing); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiMarketing); ?></strong></td>
                                        
                                        <!-- % Marketing/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenTarget = $totalEntitas > 0 ? $totalPersenTargetSum / $totalEntitas : 0;
                                                echo number_format($rataPersenTarget, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenRealisasi = $totalEntitas > 0 ? $totalPersenRealisasiSum / $totalEntitas : 0;
                                                echo number_format($rataPersenRealisasi, 2); ?>%
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap Marketing Total -->
                                        <td class="text-end <?php echo $totalGapMarketing >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapMarketing); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenPencapaian = $totalEntitas > 0 ? $totalPersenPencapaianSum / $totalEntitas : 0;
                                                $warnaPencapaianTotal = $rataPersenPencapaian <= 100 ? 'text-success' : 
                                                    ($rataPersenPencapaian <= 120 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaPencapaianTotal; ?>"><?php echo number_format($rataPersenPencapaian, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            $statusTextTotal = '';
                                            
                                            if ($rataPersenRealisasi <= $rataPersenTarget) {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                                $statusTextTotal = 'Rata-rata biaya pemasaran efisien terhadap omzet';
                                            } else {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                                $statusTextTotal = 'Rata-rata biaya pemasaran melebihi budget relatif terhadap omzet';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Biaya Pemasaran Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>                       
                    </div>
                </div>
            </div>
        </div>


        <!-- ==================== GRAFIK TREN BIAYA PEMASARAN PER BULAN ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Biaya Pemasaran Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik Tren Biaya Pemasaran per Bulan -->
                        <div class="chart-container-fixed">
                            <canvas id="chartTrenBiayaMarketingBulanan"></canvas>
                        </div>
                        
                        <?php
                        // Query untuk mengambil data omzet bersih per bulan dengan filter
                        $sqlOmzetBersihBulananMarketing = "SELECT 
                            DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                            SUM(nilai_target) as total_target,
                            SUM(nilai_realisasi) as total_realisasi
                        FROM data_report 
                        WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                            OR LOWER(nama_akun) LIKE '%omzetbersih%'
                            OR nama_akun = 'Omzet Bersih')";
                        
                        $paramsOmzetBersihBulananMarketing = [];
                        
                        // Tambahkan filter tanggal jika ada
                        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                            $sqlOmzetBersihBulananMarketing .= " AND DATE(tanggal) BETWEEN ? AND ?";
                            $paramsOmzetBersihBulananMarketing[] = $tanggal_awal;
                            $paramsOmzetBersihBulananMarketing[] = $tanggal_akhir;
                        } elseif (!empty($tanggal_awal)) {
                            $sqlOmzetBersihBulananMarketing .= " AND DATE(tanggal) >= ?";
                            $paramsOmzetBersihBulananMarketing[] = $tanggal_awal;
                        } elseif (!empty($tanggal_akhir)) {
                            $sqlOmzetBersihBulananMarketing .= " AND DATE(tanggal) <= ?";
                            $paramsOmzetBersihBulananMarketing[] = $tanggal_akhir;
                        }
                        
                        $sqlOmzetBersihBulananMarketing .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                        
                        try {
                            $stmtOmzetBersihBulananMarketing = $pdo->prepare($sqlOmzetBersihBulananMarketing);
                            $stmtOmzetBersihBulananMarketing->execute($paramsOmzetBersihBulananMarketing);
                            $dataOmzetBersihBulananMarketing = $stmtOmzetBersihBulananMarketing->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Konversi ke array asosiatif
                            $omzetBersihPerBulanMarketingFiltered = [];
                            foreach ($dataOmzetBersihBulananMarketing as $row) {
                                $bulan = $row['bulan'];
                                $omzetBersihPerBulanMarketingFiltered[$bulan] = [
                                    'target' => (float)$row['total_target'],
                                    'realisasi' => (float)$row['total_realisasi']
                                ];
                            }
                            
                            // Fallback jika data kosong - gunakan data omzet biasa dengan filter
                            if (empty($omzetBersihPerBulanMarketingFiltered)) {
                                foreach ($bulanDataOmzet as $bulanKey => $data) {
                                    // Cek apakah bulan ini masuk dalam filter tanggal
                                    $includeBulan = true;
                                    $tahunBulan = (int)substr($bulanKey, 0, 4);
                                    $bulanAngka = (int)substr($bulanKey, 5, 2);
                                    
                                    // Buat tanggal pertama dan terakhir di bulan ini
                                    $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                    $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                    
                                    if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                        $includeBulan = false;
                                    }
                                    if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                        $includeBulan = false;
                                    }
                                    
                                    if ($includeBulan) {
                                        $omzetBersihPerBulanMarketingFiltered[$bulanKey] = $data;
                                    }
                                }
                            }
                            
                        } catch (PDOException $e) {
                            // Fallback jika query gagal
                            $omzetBersihPerBulanMarketingFiltered = [];
                            foreach ($bulanDataOmzet as $bulanKey => $data) {
                                // Cek filter tanggal untuk bulan ini
                                $includeBulan = true;
                                $tahunBulan = (int)substr($bulanKey, 0, 4);
                                $bulanAngka = (int)substr($bulanKey, 5, 2);
                                
                                // Buat tanggal pertama dan terakhir di bulan ini
                                $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                                $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                                
                                if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                    $includeBulan = false;
                                }
                                if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                    $includeBulan = false;
                                }
                                
                                if ($includeBulan) {
                                    $omzetBersihPerBulanMarketingFiltered[$bulanKey] = $data;
                                }
                            }
                        }
                        
                        // Filter $biayaMarketingPerBulan berdasarkan tanggal (bulan yang ada dalam filter)
                        $biayaMarketingPerBulanFiltered = [];
                        foreach ($biayaMarketingPerBulan as $bulanKey => $data) {
                            // Cek apakah bulan ini masuk dalam filter tanggal
                            $includeBulan = true;
                            $tahunBulan = (int)substr($bulanKey, 0, 4);
                            $bulanAngka = (int)substr($bulanKey, 5, 2);
                            
                            // Buat tanggal pertama dan terakhir di bulan ini
                            $tanggalPertamaBulan = date('Y-m-01', strtotime($bulanKey . '-01'));
                            $tanggalTerakhirBulan = date('Y-m-t', strtotime($bulanKey . '-01'));
                            
                            if (!empty($tanggal_awal) && strtotime($tanggalTerakhirBulan) < strtotime($tanggal_awal)) {
                                $includeBulan = false;
                            }
                            if (!empty($tanggal_akhir) && strtotime($tanggalPertamaBulan) > strtotime($tanggal_akhir)) {
                                $includeBulan = false;
                            }
                            
                            if ($includeBulan) {
                                $biayaMarketingPerBulanFiltered[$bulanKey] = $data;
                            }
                        }
                        ?>
                        
                        <?php if (count($dataBiayaMarketing) == 0): ?>
                        <div class="alert alert-warning mb-4">
                            <strong>Data tidak ditemukan!</strong><br>
                            Tidak ada data biaya pemasaran untuk periode yang dipilih.<br>
                            <strong>Definisi Biaya Pemasaran:</strong> Semua biaya terkait pemasaran, marketing, iklan, promosi, dan sejenisnya.
                        </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #8B5CF6; color: white;">
                                        <th>No</th>
                                        <th>Bulan</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target Marketing</th>
                                        <th>% Mkt/Omzet (Target)</th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi Marketing</th>
                                        <th>% Mkt/Omzet (Realisasi)</th>
                                        <th>Gap Marketing</th>
                                        <th>% Pencapaian Marketing</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihBulanan = 0;
                                    $totalTargetBulanan = 0;
                                    $totalRealisasiOmzetBersihBulanan = 0;
                                    $totalRealisasiBulanan = 0;
                                    $totalGapBulanan = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalROASSum = 0;
                                    $totalBulan = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Urutkan bulan
                                    ksort($biayaMarketingPerBulanFiltered);
                                    
                                    foreach ($biayaMarketingPerBulanFiltered as $bulanKey => $dataBulan):
                                        // Data Biaya Pemasaran per bulan
                                        $targetBulan = $dataBulan['target'];
                                        $realisasiBulan = $dataBulan['realisasi'];
                                        $gapBulan = $dataBulan['gap'];
                                        
                                        // Data Omzet Bersih per bulan (dengan filter)
                                        $targetOmzetBersihBulanan = isset($omzetBersihPerBulanMarketingFiltered[$bulanKey]) ? $omzetBersihPerBulanMarketingFiltered[$bulanKey]['target'] : 0;
                                        $realisasiOmzetBersihBulanan = isset($omzetBersihPerBulanMarketingFiltered[$bulanKey]) ? $omzetBersihPerBulanMarketingFiltered[$bulanKey]['realisasi'] : 0;
                                        
                                        // Hitung %_Target (Target Marketing / Target Omzet Bersih)
                                        $persenTarget = $targetOmzetBersihBulanan > 0 ? ($targetBulan / $targetOmzetBersihBulanan) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi Marketing / Realisasi Omzet Bersih)
                                        $persenRealisasi = $realisasiOmzetBersihBulanan > 0 ? ($realisasiBulan / $realisasiOmzetBersihBulanan) * 100 : 0;
                                        
                                        // Hitung ROAS (Return on Ad Spend)
                                        $roas = $realisasiBulan > 0 ? ($realisasiOmzetBersihBulanan / $realisasiBulan) : 0;
                                        
                                        // Hitung % Pencapaian Marketing (Realisasi Marketing / Target Marketing)
                                        $percentageBulan = $targetBulan > 0 ? ($realisasiBulan / $targetBulan) * 100 : 0;
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                        $status = '';
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        // ATURAN: 
                                        // Jika % Realisasi <= % Target → EFISIEN (biaya marketing lebih kecil dari yang dianggarkan relatif terhadap omzet)
                                        // Jika % Realisasi > % Target → OVER BUDGET (biaya marketing lebih besar dari yang dianggarkan)
                                        if ($persenRealisasi <= $persenTarget) {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $statusText = 'Biaya pemasaran efisien terhadap omzet';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $statusText = 'Biaya pemasaran melebihi budget relatif terhadap omzet';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Format nama bulan
                                        $tahun = substr($bulanKey, 0, 4);
                                        $bulanAngka = (int)substr($bulanKey, 5, 2);
                                        $namaBulan = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                        
                                        // Hitung rata-rata harian
                                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulanAngka, $tahun);
                                        $rataHarian = $daysInMonth > 0 ? $realisasiBulan / $daysInMonth : 0;
                                        
                                        // Warna untuk % Target dan % Realisasi
                                        $warnaPersenTarget = $persenTarget <= 10 ? 'text-success' : ($persenTarget <= 20 ? 'text-warning' : 'text-danger');
                                        $warnaPersenRealisasi = $persenRealisasi <= 10 ? 'text-success' : ($persenRealisasi <= 20 ? 'text-warning' : 'text-danger');
                                        
                                        // Warna untuk ROAS
                                        $warnaROAS = '';
                                        if ($roas >= 5) {
                                            $warnaROAS = 'text-success';
                                        } elseif ($roas >= 3) {
                                            $warnaROAS = 'text-warning';
                                        } else {
                                            $warnaROAS = 'text-danger';
                                        }
                                        
                                        // Warna untuk % Pencapaian
                                        $warnaPencapaian = '';
                                        if ($percentageBulan <= 100) {
                                            $warnaPencapaian = 'text-success';
                                        } elseif ($percentageBulan <= 120) {
                                            $warnaPencapaian = 'text-warning';
                                        } else {
                                            $warnaPencapaian = 'text-danger';
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihBulanan += $targetOmzetBersihBulanan;
                                        $totalTargetBulanan += $targetBulan;
                                        $totalRealisasiOmzetBersihBulanan += $realisasiOmzetBersihBulanan;
                                        $totalRealisasiBulanan += $realisasiBulan;
                                        $totalGapBulanan += $gapBulan;
                                        $totalPersenTargetSum += $persenTarget;
                                        $totalPersenRealisasiSum += $persenRealisasi;
                                        $totalROASSum += $roas;
                                        $totalBulan++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($namaBulan . ' ' . $tahun); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihBulanan); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetBulan); ?></td>
                                        <td class="text-end <?php echo $warnaPersenTarget; ?>">
                                            <strong><?php echo number_format($persenTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihBulanan); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiBulan); ?></td>
                                        <td class="text-end <?php echo $warnaPersenRealisasi; ?>">
                                            <strong><?php echo number_format($persenRealisasi, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $gapBulan >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo formatFullNumber($gapBulan); ?>
                                        </td>
                                        <td class="text-end <?php echo $warnaPencapaian; ?>">
                                            <?php echo number_format($percentageBulan, 1); ?>%
                                        </td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($biayaMarketingPerBulanFiltered)): ?>
                                    <tr>
                                        <td colspan="12" class="text-center">
                                            <div class="alert alert-warning">
                                                <strong>Tidak ada data biaya pemasaran untuk periode yang dipilih!</strong><br>
                                                <small>Data biaya pemasaran mencakup semua biaya terkait pemasaran, marketing, iklan, promosi, dan sejenisnya.</small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($biayaMarketingPerBulanFiltered)): ?>
                                <tfoot style="background-color: #EDE9FE;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihBulanan); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBulanan); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenTarget = $totalBulan > 0 ? $totalPersenTargetSum / $totalBulan : 0;
                                                $warnaRataTarget = $rataRataPersenTarget <= 10 ? 'text-success' : ($rataRataPersenTarget <= 20 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataTarget; ?>"><?php echo number_format($rataRataPersenTarget, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihBulanan); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBulanan); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataRataPersenRealisasi = $totalBulan > 0 ? $totalPersenRealisasiSum / $totalBulan : 0;
                                                $warnaRataRealisasi = $rataRataPersenRealisasi <= 10 ? 'text-success' : ($rataRataPersenRealisasi <= 20 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataRealisasi; ?>"><?php echo number_format($rataRataPersenRealisasi, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapBulanan >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapBulanan); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageBulanan = $totalTargetBulanan > 0 ? ($totalRealisasiBulanan / $totalTargetBulanan) * 100 : 0;
                                                $warnaRataPencapaian = $totalPercentageBulanan <= 100 ? 'text-success' : 
                                                    ($totalPercentageBulanan <= 120 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataPencapaian; ?>"><?php echo number_format($totalPercentageBulanan, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            $statusTextTotal = '';
                                            
                                            if ($rataRataPersenRealisasi <= $rataRataPersenTarget) {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                                $statusTextTotal = 'Rata-rata biaya pemasaran efisien terhadap omzet';
                                            } else {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                                $statusTextTotal = 'Rata-rata biaya pemasaran melebihi budget relatif terhadap omzet';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Biaya Pemasaran Per Bulan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?> 
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== BIAYA GA (GENERAL AFFAIRS) ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Analisis Biaya General Affairs Per Entias</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik 2 Kolom: Biaya GA per Entitas -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Biaya GA per Entitas (Target vs Realisasi) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Biaya GA Per Entitas - Target vs Realisasi</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartBiayaGAEntitas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Proporsi Biaya GA per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi Biaya GA Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiBiayaGA"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- B. Tabel Detail Biaya GA per Entitas -->
                        <h6 class="mb-3 mt-4" style="color: #059669;"><strong>Tabel Detail Biaya GA Per Entitas</strong></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #10B981; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">Biaya GA</th>
                                        <th colspan="2" class="text-center">% GA/Omzet</th>
                                        <th colspan="2" class="text-center">% GA/Margin</th>
                                        <th rowspan="2" class="align-middle text-center">Gap</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian</th>
                                        <th rowspan="2" class="align-middle text-center">Keterangan</th>
                                    </tr>
                                    <tr style="background-color: #34D399; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihGA = 0;
                                    $totalRealisasiOmzetBersihGA = 0;
                                    $totalTargetBiayaGA = 0;
                                    $totalRealisasiBiayaGA = 0;
                                    $totalPersenOmzetTargetSumGA = 0;
                                    $totalPersenOmzetRealisasiSumGA = 0;
                                    $totalPersenMarginTargetSumGA = 0;
                                    $totalPersenMarginRealisasiSumGA = 0;
                                    $totalGapGA = 0;
                                    $totalPersenPencapaianSumGA = 0;
                                    $totalEntitasGA = 0;
                                    $totalEfisienGA = 0;
                                    $totalOverBudgetGA = 0;
                                    
                                    // Urutkan berdasarkan realisasi biaya GA tertinggi
                                    uasort($biayaGAPerEntitas, function($a, $b) {
                                        return $b['realisasi'] <=> $a['realisasi'];
                                    });
                                    
                                    foreach ($biayaGAPerEntitas as $entitas => $data):
                                        // Data Biaya GA
                                        $targetBiayaGA = $data['target'];
                                        $realisasiBiayaGA = $data['realisasi'];
                                        $gapGA = $realisasiBiayaGA - $targetBiayaGA;
                                        
                                        // Data Omzet Bersih untuk entitas ini
                                        $targetOmzetBersih = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['target'] : 0;
                                        $realisasiOmzetBersih = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        // Jika tidak ada data omzet bersih, gunakan data omzet biasa
                                        if ($realisasiOmzetBersih == 0 && isset($entitasDataOmzet[$entitas])) {
                                            $targetOmzetBersih = $entitasDataOmzet[$entitas]['total_target'];
                                            $realisasiOmzetBersih = $entitasDataOmzet[$entitas]['total_realisasi'];
                                        }
                                        
                                        // Hitung margin untuk entitas ini (Omzet Bersih - HPP - Biaya Operasional - Biaya Pemasaran - Biaya Lain-lain - Biaya GA)
                                        // Tapi untuk menghitung % GA/Margin, kita gunakan margin TANPA Biaya GA
                                        $realisasiHPP = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_realisasi'] : 0;
                                        $realisasiBiayaOps = isset($biayaOpsPerEntitas[$entitas]) ? $biayaOpsPerEntitas[$entitas]['realisasi'] : 0;
                                        $realisasiBiayaMarketing = isset($biayaMarketingPerEntitas[$entitas]) ? $biayaMarketingPerEntitas[$entitas]['realisasi'] : 0;
                                        $realisasiBiayaLain = isset($biayaLainPerEntitas[$entitas]) ? $biayaLainPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        // Margin TANPA Biaya GA (untuk menghitung % GA/Margin)
                                        $marginTanpaGA = $realisasiOmzetBersih - $realisasiHPP - $realisasiBiayaOps - $realisasiBiayaMarketing - $realisasiBiayaLain;
                                        
                                        // Margin DENGAN Biaya GA (untuk konteks umum)
                                        $marginDenganGA = $marginTanpaGA - $realisasiBiayaGA;
                                        
                                        // Target margin tanpa GA
                                        $targetHPP = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_target'] : 0;
                                        $targetBiayaOps = isset($biayaOpsPerEntitas[$entitas]) ? $biayaOpsPerEntitas[$entitas]['target'] : 0;
                                        $targetBiayaMarketing = isset($biayaMarketingPerEntitas[$entitas]) ? $biayaMarketingPerEntitas[$entitas]['target'] : 0;
                                        $targetBiayaLain = isset($biayaLainPerEntitas[$entitas]) ? $biayaLainPerEntitas[$entitas]['target'] : 0;
                                        
                                        $targetMarginTanpaGA = $targetOmzetBersih - $targetHPP - $targetBiayaOps - $targetBiayaMarketing - $targetBiayaLain;
                                        
                                        // Hitung % Biaya GA/Omzet (Target)
                                        $persenOmzetTargetGA = $targetOmzetBersih > 0 ? ($targetBiayaGA / $targetOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Biaya GA/Omzet (Realisasi)
                                        $persenOmzetRealisasiGA = $realisasiOmzetBersih > 0 ? ($realisasiBiayaGA / $realisasiOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Biaya GA/Margin (Target) - margin tanpa GA
                                        $persenMarginTargetGA = $targetMarginTanpaGA > 0 ? ($targetBiayaGA / $targetMarginTanpaGA) * 100 : 0;
                                        
                                        // Hitung % Biaya GA/Margin (Realisasi) - margin tanpa GA
                                        $persenMarginRealisasiGA = $marginTanpaGA > 0 ? ($realisasiBiayaGA / $marginTanpaGA) * 100 : 0;
                                        
                                        // Hitung % Pencapaian Biaya GA
                                        $persenPencapaianGA = $targetBiayaGA > 0 ? ($realisasiBiayaGA / $targetBiayaGA) * 100 : 0;
                                        
                                        // Tentukan Status Efisiensi (berdasarkan % Omzet)
                                        $statusGA = '';
                                        $statusClassGA = '';
                                        $statusTextGA = '';
                                        
                                        // ATURAN: Jika % Realisasi <= % Target → EFISIEN, jika tidak → OVER BUDGET
                                        if ($persenOmzetRealisasiGA <= $persenOmzetTargetGA) {
                                            $statusGA = 'EFISIEN';
                                            $statusClassGA = 'badge-selesai';
                                            $statusTextGA = 'Biaya GA efisien terhadap omzet';
                                            $totalEfisienGA++;
                                        } else {
                                            $statusGA = 'OVER BUDGET';
                                            $statusClassGA = 'badge-belum-selesai';
                                            $statusTextGA = 'Biaya GA melebihi budget relatif terhadap omzet';
                                            $totalOverBudgetGA++;
                                        }
                                        
                                        // Warna untuk % Omzet (Target dan Realisasi)
                                        // Untuk GA, ambang batas bisa berbeda dari biaya lain-lain
                                        // Misal: ≤3% hijau, ≤6% kuning, >6% merah
                                        $warnaOmzetTargetGA = $persenOmzetTargetGA <= 3 ? 'text-success' : ($persenOmzetTargetGA <= 6 ? 'text-warning' : 'text-danger');
                                        $warnaOmzetRealisasiGA = $persenOmzetRealisasiGA <= 3 ? 'text-success' : ($persenOmzetRealisasiGA <= 6 ? 'text-warning' : 'text-danger');
                                        
                                        // Warna untuk % Margin (Target dan Realisasi)
                                        // Untuk GA, ambang batas: ≤15% hijau, ≤30% kuning, >30% merah
                                        $warnaMarginTargetGA = $persenMarginTargetGA <= 15 ? 'text-success' : ($persenMarginTargetGA <= 30 ? 'text-warning' : 'text-danger');
                                        $warnaMarginRealisasiGA = $persenMarginRealisasiGA <= 15 ? 'text-success' : ($persenMarginRealisasiGA <= 30 ? 'text-warning' : 'text-danger');
                                        
                                        // Warna untuk % Pencapaian
                                        $warnaPencapaianGA = '';
                                        if ($persenPencapaianGA <= 100) {
                                            $warnaPencapaianGA = 'text-success';
                                        } elseif ($persenPencapaianGA <= 120) {
                                            $warnaPencapaianGA = 'text-warning';
                                        } else {
                                            $warnaPencapaianGA = 'text-danger';
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihGA += $targetOmzetBersih;
                                        $totalRealisasiOmzetBersihGA += $realisasiOmzetBersih;
                                        $totalTargetBiayaGA += $targetBiayaGA;
                                        $totalRealisasiBiayaGA += $realisasiBiayaGA;
                                        $totalPersenOmzetTargetSumGA += $persenOmzetTargetGA;
                                        $totalPersenOmzetRealisasiSumGA += $persenOmzetRealisasiGA;
                                        $totalPersenMarginTargetSumGA += $persenMarginTargetGA;
                                        $totalPersenMarginRealisasiSumGA += $persenMarginRealisasiGA;
                                        $totalGapGA += $gapGA;
                                        $totalPersenPencapaianSumGA += $persenPencapaianGA;
                                        $totalEntitasGA++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                        
                                        <!-- Biaya GA -->
                                        <td class="text-end"><?php echo formatFullNumber($targetBiayaGA); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiBiayaGA); ?></td>
                                        
                                        <!-- % GA/Omzet -->
                                        <td class="text-end <?php echo $warnaOmzetTargetGA; ?>">
                                            <strong><?php echo number_format($persenOmzetTargetGA, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaOmzetRealisasiGA; ?>">
                                            <strong><?php echo number_format($persenOmzetRealisasiGA, 2); ?>%</strong>
                                        </td>
                                        
                                        <!-- % GA/Margin -->
                                        <td class="text-end <?php echo $warnaMarginTargetGA; ?>">
                                            <strong><?php echo number_format($persenMarginTargetGA, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaMarginRealisasiGA; ?>">
                                            <strong><?php echo number_format($persenMarginRealisasiGA, 2); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap -->
                                        <td class="text-end <?php echo $gapGA >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gapGA); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian -->
                                        <td class="text-end <?php echo $warnaPencapaianGA; ?>">
                                            <strong><?php echo number_format($persenPencapaianGA, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $statusGA; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($biayaGAPerEntitas)): ?>
                                    <tr>
                                        <td colspan="15" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data biaya GA (General Affairs) untuk periode yang dipilih!</strong><br>
                                                <small class="small">Data biaya GA mencakup: administrasi, perlengkapan kantor, utilitas, dan biaya operasional umum lainnya.</small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($biayaGAPerEntitas)): ?>
                                <tfoot style="background-color: #D1FAE5;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersihGA); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersihGA); ?></strong></td>
                                        
                                        <!-- Biaya GA Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBiayaGA); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBiayaGA); ?></strong></td>
                                        
                                        <!-- % GA/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenOmzetTargetGA = $totalEntitasGA > 0 ? $totalPersenOmzetTargetSumGA / $totalEntitasGA : 0;
                                                $warnaRataOmzetTargetGA = $rataPersenOmzetTargetGA <= 3 ? 'text-success' : ($rataPersenOmzetTargetGA <= 6 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataOmzetTargetGA; ?>"><?php echo number_format($rataPersenOmzetTargetGA, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenOmzetRealisasiGA = $totalEntitasGA > 0 ? $totalPersenOmzetRealisasiSumGA / $totalEntitasGA : 0;
                                                $warnaRataOmzetRealisasiGA = $rataPersenOmzetRealisasiGA <= 3 ? 'text-success' : ($rataPersenOmzetRealisasiGA <= 6 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataOmzetRealisasiGA; ?>"><?php echo number_format($rataPersenOmzetRealisasiGA, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- % GA/Margin Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenMarginTargetGA = $totalEntitasGA > 0 ? $totalPersenMarginTargetSumGA / $totalEntitasGA : 0;
                                                $warnaRataMarginTargetGA = $rataPersenMarginTargetGA <= 15 ? 'text-success' : ($rataPersenMarginTargetGA <= 30 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataMarginTargetGA; ?>"><?php echo number_format($rataPersenMarginTargetGA, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenMarginRealisasiGA = $totalEntitasGA > 0 ? $totalPersenMarginRealisasiSumGA / $totalEntitasGA : 0;
                                                $warnaRataMarginRealisasiGA = $rataPersenMarginRealisasiGA <= 15 ? 'text-success' : ($rataPersenMarginRealisasiGA <= 30 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataMarginRealisasiGA; ?>"><?php echo number_format($rataPersenMarginRealisasiGA, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap Total -->
                                        <td class="text-end <?php echo $totalGapGA >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapGA); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenPencapaianGA = $totalEntitasGA > 0 ? $totalPersenPencapaianSumGA / $totalEntitasGA : 0;
                                                $warnaRataPencapaianGA = $rataPersenPencapaianGA <= 100 ? 'text-success' : 
                                                    ($rataPersenPencapaianGA <= 120 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataPencapaianGA; ?>"><?php echo number_format($rataPersenPencapaianGA, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotalGA = '';
                                            $statusClassTotalGA = '';
                                            $statusTextTotalGA = '';
                                            
                                            if ($rataPersenOmzetRealisasiGA <= $rataPersenOmzetTargetGA) {
                                                $statusTotalGA = 'EFISIEN';
                                                $statusClassTotalGA = 'badge-selesai';
                                                $statusTextTotalGA = 'Rata-rata biaya GA efisien terhadap omzet';
                                            } else {
                                                $statusTotalGA = 'OVER BUDGET';
                                                $statusClassTotalGA = 'badge-belum-selesai';
                                                $statusTextTotalGA = 'Rata-rata biaya GA melebihi budget relatif terhadap omzet';
                                            }
                                            ?>
                                            <?php echo $statusTotalGA; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Analisis Biaya General Affairs Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?> 
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TREN BIAYA GA PER BULAN ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Biaya General Affairs Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container">
                            <canvas id="chartTrenBiayaGABulanan"></canvas>
                        </div>
                        
                        <!-- Tabel Data Biaya GA per Bulan -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Bulan</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target GA</th>
                                        <th>% Target GA/Omzet Bersih</th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi GA</th>
                                        <th>% Realisasi GA/Omzet Bersih</th>
                                        <th>Gap GA</th>
                                        <th>% Pencapaian Realisasi/Target</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $totalTargetOmzetBersih = 0;
                                    $totalTargetGA = 0;
                                    $totalRealisasiOmzetBersih = 0;
                                    $totalRealisasiGA = 0;
                                    $totalGapGA = 0;
                                    $totalPersenTargetGA = 0;
                                    $totalPersenRealisasiGA = 0;
                                    $totalPersenPencapaian = 0;
                                    $countBulan = 0;
                                    
                                    // Urutkan berdasarkan bulan
                                    ksort($biayaGAPerBulan);
                                    
                                    foreach ($biayaGAPerBulan as $bulanKey => $dataBulan):
                                        $targetGA = isset($dataBulan['target']) ? $dataBulan['target'] : 0;
                                        $realisasiGA = isset($dataBulan['realisasi']) ? $dataBulan['realisasi'] : 0;
                                        $gapGA = $realisasiGA - $targetGA;
                                        $percentageGA = $targetGA > 0 ? ($realisasiGA / $targetGA) * 100 : 0;
                                        
                                        // Ambil data omzet bersih untuk bulan ini
                                        $targetOmzetBersih = 0;
                                        $realisasiOmzetBersih = 0;
                                        
                                        // Cari data omzet bersih dari berbagai sumber
                                        if (isset($omzetBersihPerHariBiaya)) {
                                            foreach ($omzetBersihPerHariBiaya as $tanggal => $data) {
                                                if (date('Y-m', strtotime($tanggal)) == $bulanKey) {
                                                    $targetOmzetBersih += $data['target'];
                                                    $realisasiOmzetBersih += $data['realisasi'];
                                                }
                                            }
                                        }
                                        
                                        // Jika tidak ada data dari $omzetBersihPerHariBiaya, coba dari $bulanDataOmzet
                                        if ($targetOmzetBersih == 0 && $realisasiOmzetBersih == 0) {
                                            $targetOmzetBersih = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['target'] : 0;
                                            $realisasiOmzetBersih = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['realisasi'] : 0;
                                        }
                                        
                                        // Hitung persentase
                                        $persenTargetGA = $targetOmzetBersih > 0 ? ($targetGA / $targetOmzetBersih) * 100 : 0;
                                        $persenRealisasiGA = $realisasiOmzetBersih > 0 ? ($realisasiGA / $realisasiOmzetBersih) * 100 : 0;
                                        
                                        // Status
                                        if ($percentageGA <= 100) {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                        } else {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersih += $targetOmzetBersih;
                                        $totalTargetGA += $targetGA;
                                        $totalRealisasiOmzetBersih += $realisasiOmzetBersih;
                                        $totalRealisasiGA += $realisasiGA;
                                        $totalGapGA += $gapGA;
                                        $totalPersenTargetGA += $persenTargetGA;
                                        $totalPersenRealisasiGA += $persenRealisasiGA;
                                        $totalPersenPencapaian += $percentageGA;
                                        $countBulan++;
                                        
                                        $bulanAngka = (int)substr($bulanKey, 5, 2);
                                        $tahun = substr($bulanKey, 0, 4);
                                        $namaBulan = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($namaBulan . ' ' . $tahun); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetGA); ?></td>
                                        <td class="text-end"><?php echo number_format($persenTargetGA, 2); ?>%</td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiGA); ?></td>
                                        <td class="text-end"><?php echo number_format($persenRealisasiGA, 2); ?>%</td>
                                        <td class="text-end <?php echo $gapGA >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo formatFullNumber($gapGA); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentageGA, 1); ?>%</td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($biayaGAPerBulan)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">Tidak ada data biaya GA untuk periode yang dipilih</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersih); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetGA); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenTargetGA = $countBulan > 0 ? $totalPersenTargetGA / $countBulan : 0;
                                                echo number_format($rataPersenTargetGA, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersih); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiGA); ?></strong></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenRealisasiGA = $countBulan > 0 ? $totalPersenRealisasiGA / $countBulan : 0;
                                                echo number_format($rataPersenRealisasiGA, 2); ?>%
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapGA >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapGA); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenPencapaian = $countBulan > 0 ? $totalPersenPencapaian / $countBulan : 0;
                                                echo number_format($rataPersenPencapaian, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $statusTotal = $rataPersenPencapaian <= 100 ? 'EFISIEN' : 'OVER BUDGET';
                                            $statusTotalClass = $rataPersenPencapaian <= 100 ? 'badge-selesai' : 'badge-belum-selesai';
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Biaya General Affairs Per Bulan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?> 
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ANALISIS BIAYA ZISWAF ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Analisis Biaya ZISWAF (Zakat, Infaq, Sedekah, Wakaf)</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik 2 Kolom FIX: Analisis ZISWAF -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Biaya ZISWAF per Entitas (Target vs Realisasi) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Biaya ZISWAF per Entitas (Target vs Realisasi)</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartZISWAFPerEntitasTargetRealisasi"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1">
                                                <strong>Total Entitas:</strong> <?php echo count($biayaZISWAFPerEntitas); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Rata-rata Realisasi Per Entitas:</strong> 
                                                <?php 
                                                $rataRealisasiPerEntitas = count($biayaZISWAFPerEntitas) > 0 ? 
                                                    $totalRealisasiBiayaZISWAF / count($biayaZISWAFPerEntitas) : 0;
                                                echo 'Rp ' . number_format($rataRealisasiPerEntitas, 0, ',', '.');
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Proporsi Biaya ZISWAF per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi Biaya ZISWAF Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiZISWAFPerEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1">
                                                <strong>Total Entitas Aktif:</strong> 
                                                <?php 
                                                $entitasAktif = 0;
                                                foreach ($biayaZISWAFPerEntitas as $data) {
                                                    if ($data['realisasi'] > 0) {
                                                        $entitasAktif++;
                                                    }
                                                }
                                                echo $entitasAktif . ' dari ' . count($biayaZISWAFPerEntitas);
                                                ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Entitas Dominan:</strong> 
                                                <?php 
                                                if (!empty($biayaZISWAFPerEntitas)) {
                                                    arsort($biayaZISWAFPerEntitas);
                                                    $entitasDominan = array_key_first($biayaZISWAFPerEntitas);
                                                    $nilaiDominan = $biayaZISWAFPerEntitas[$entitasDominan]['realisasi'];
                                                    $persenDominan = $totalRealisasiBiayaZISWAF > 0 ? 
                                                        ($nilaiDominan / $totalRealisasiBiayaZISWAF) * 100 : 0;
                                                    echo htmlspecialchars($entitasDominan) . ' (' . number_format($persenDominan, 1) . '%)';
                                                } else {
                                                    echo 'Tidak ada data';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Detail ZISWAF per Entitas -->
                        <div class="mt-4">
                            <h5 class="mb-3">Tabel Biaya ZISWAF Per Entitas</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Entitas</th>
                                            <th>Target ZISWAF</th>
                                            <th>Realisasi ZISWAF</th>
                                            <th>Gap</th>
                                            <th>% Pencapaian</th>
                                            <th>% dari Total ZISWAF</th>
                                            <th>% dari Total Biaya</th>
                                            <th>% dari Omzet</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        // Urutkan berdasarkan realisasi tertinggi
                                        uasort($biayaZISWAFPerEntitas, function($a, $b) {
                                            return $b['realisasi'] <=> $a['realisasi'];
                                        });
                                        
                                        foreach ($biayaZISWAFPerEntitas as $entitas => $data): 
                                            $persenTotalZISWAF = $totalRealisasiBiayaZISWAF > 0 ? ($data['realisasi'] / $totalRealisasiBiayaZISWAF) * 100 : 0;
                                            $persenTotalBiaya = $totalRealisasiBiaya > 0 ? ($data['realisasi'] / $totalRealisasiBiaya) * 100 : 0;
                                            $persenOmzet = $totalRealisasiOmzet > 0 ? ($data['realisasi'] / $totalRealisasiOmzet) * 100 : 0;
                                            $persenPencapaian = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
                                            
                                            // Tentukan status
                                            $statusClass = '';
                                            $statusText = '';
                                            if ($data['target'] == 0) {
                                                $statusClass = 'bg-info';
                                                $statusText = 'TIDAK ADA TARGET';
                                            } elseif ($persenPencapaian >= 100) {
                                                $statusClass = 'bg-success';
                                                $statusText = 'ACHIEVE';
                                            } elseif ($persenPencapaian >= 80) {
                                                $statusClass = 'bg-warning';
                                                $statusText = 'MENDEKATI';
                                            } else {
                                                $statusClass = 'bg-danger';
                                                $statusText = 'TIDAK ACHIEVE';
                                            }
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                            <td class="text-end"><?php echo formatFullNumber($data['target']); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($data['realisasi']); ?></td>
                                            <td class="text-end <?php echo $data['gap'] >= 0 ? 'text-danger' : 'text-success'; ?>">
                                                <strong><?php echo formatFullNumber($data['gap']); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($persenPencapaian, 1); ?>%
                                            </td>
                                            <td class="text-end"><?php echo number_format($persenTotalZISWAF, 2); ?>%</td>
                                            <td class="text-end"><?php echo number_format($persenTotalBiaya, 2); ?>%</td>
                                            <td class="text-end"><?php echo number_format($persenOmzet, 2); ?>%</td>
                                            <td>
                                                <?php echo $statusText; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBiayaZISWAF); ?></strong></td>
                                            <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBiayaZISWAF); ?></strong></td>
                                            <td class="text-end <?php echo $totalGapBiayaZISWAF >= 0 ? 'text-danger' : 'text-success'; ?>">
                                                <strong><?php echo formatFullNumber($totalGapBiayaZISWAF); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo number_format($percentageBiayaZISWAF, 1); ?>%</strong>
                                            </td>
                                            <td class="text-end"><strong>100%</strong></td>
                                            <td class="text-end"><strong><?php echo number_format($percentageOfTotalBiayaZISWAF, 2); ?>%</strong></td>
                                            <td class="text-end"><strong><?php echo number_format($percentageOfOmzetBiayaZISWAF, 2); ?>%</strong></td>
                                            <td>
                                                 <?php echo $percentageBiayaZISWAF >= 100 ? 'ACHIEVE' : ($percentageBiayaZISWAF >= 80 ? 'MENDEKATI' : 'BELUM ACHIEVE'); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                            <?php if (!empty($dataHasilAnalisis)): ?>
                                <?php foreach ($dataHasilAnalisis as $row): ?>
                                    <?php if(strtolower($row['title']) == strtolower('Analisis Biaya ZISWAF Per Entitas')): ?>
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                            </div>
                                            <div class="card-body p-2">
                                            <?php 
                                                $cleanedContent = nl2br($row['analisis']);
                                                $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                                $cleanedContent = str_replace('\n', '', $cleanedContent);
                                                $cleanedContent = str_replace('"', '', $cleanedContent);
                                                echo $cleanedContent; 
                                                ?>
                                            </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?> 
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik 28: Tren Biaya ZISWAF per Bulan -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Biaya ZISWAF Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">

                        <!-- Grafik Tren ZISWAF per Bulan -->
                        <div class="chart-container-medium">
                            <canvas id="chartTrenZISWAFBulanan"></canvas>
                        </div>
                        
                        <!-- Tabel Data ZISWAF per Bulan -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Bulan</th>
                                        <th>Target ZISWAF</th>
                                        <th>Realisasi ZISWAF</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>% dari Total ZISWAF</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($ziswafPerBulan as $bulan => $data):
                                        $percentage = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
                                        $percentageOfTotal = $totalRealisasiBiayaZISWAF > 0 ? ($data['realisasi'] / $totalRealisasiBiayaZISWAF) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo date('F Y', strtotime($bulan . '-01')); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($data['target']); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($data['realisasi']); ?></td>
                                        <td class="text-end <?php echo $data['gap'] >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo formatFullNumber($data['gap']); ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                        <td class="text-end"><?php echo number_format($percentageOfTotal, 2); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetZISWAFBulanan); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiZISWAFBulanan); ?></strong></td>
                                        <td class="text-end <?php echo $totalGapZISWAFBulanan >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapZISWAFBulanan); ?></strong>
                                        </td>
                                        <td class="text-end"><strong><?php echo number_format($rataPersentaseZISWAFBulanan, 1); ?>%</strong></td>
                                        <td class="text-end"><strong>100%</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Biaya ZISWAF Per Bulan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?> 
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== CARD BIAYA LAIN-LAIN KOMPLIT ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Biaya Lain-lain Per Entitas
                        </h5>
                    </div>
                    <div class="card-body p-2">                        
                        <!-- GRAFIK 2 KOLOM FIX: Proporsi Biaya Lain-lain -->
                        <!-- <h6 class="mb-3" style="color: #059669;"><strong>Grafik Analisis Biaya Lain-lain</strong></h6> -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: Grafik Batang Biaya Lain-lain per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100" style="border: 1px solid #10B981;">
                                    <div class="card-header py-2" style="background-color: #10B981; color: white;">
                                        <h6 class="card-title mb-0">Biaya Lain-lain Per Entitas - Target vs Realisasi</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartBiayaLainEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1 small">
                                                <strong>Total Entitas:</strong> <?php echo count($biayaLainPerEntitas); ?>
                                            </p>
                                            <p class="text-muted mb-0 small">
                                                <strong>Entitas dengan Biaya Tertinggi:</strong> 
                                                <?php 
                                                if (!empty($biayaLainPerEntitas)) {
                                                    $entitasKeys = array_keys($biayaLainPerEntitas);
                                                    $entitasTertinggi = $entitasKeys[0];
                                                    $bebanTertinggi = $biayaLainPerEntitas[$entitasTertinggi]['realisasi'];
                                                    echo htmlspecialchars($entitasTertinggi) . ' (' . formatFullNumber($bebanTertinggi) . ')';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Grafik Pie untuk Proporsi Biaya Lain-lain per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100" style="border: 1px solid #10B981;">
                                    <div class="card-header py-2" style="background-color: #10B981; color: white;">
                                        <h6 class="card-title mb-0">Proporsi Biaya Lain-lain Per Entitas</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiBiayaLainEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-muted mb-1 small">
                                                <strong>Total Biaya Lain-lain:</strong> <?php echo formatFullNumber($totalRealisasiBiayaLain); ?>
                                            </p>
                                            <p class="text-muted mb-0 small">
                                                <strong>% dari Total Biaya:</strong> <?php echo number_format($percentageOfTotalBiayaLain, 1); ?>%
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- TABEL UTAMA: Biaya Lain-lain per Entitas -->
                        <h6 class="mb-3 mt-4" style="color: #059669;"><strong>Tabel Biaya Lain-lain Per Entitas</strong></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #10B981; color: white;">
                                        <th rowspan="2" class="align-middle text-center">No</th>
                                        <th rowspan="2" class="align-middle">Entitas</th>
                                        <th colspan="2" class="text-center">Omzet Bersih</th>
                                        <th colspan="2" class="text-center">Biaya Lain-lain</th>
                                        <th colspan="2" class="text-center">% Lain-lain/Omzet</th>
                                        <th colspan="2" class="text-center">% Lain-lain/Margin</th>
                                        <th rowspan="2" class="align-middle text-center">Gap</th>
                                        <th rowspan="2" class="align-middle text-center">% Pencapaian</th>
                                        <th rowspan="2" class="align-middle text-center">Status Efisiensi</th>
                                    </tr>
                                    <tr style="background-color: #34D399; color: white;">
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersih = 0;
                                    $totalRealisasiOmzetBersih = 0;
                                    $totalTargetBiayaLain = 0;
                                    $totalRealisasiBiayaLain = 0;
                                    $totalPersenOmzetTargetSum = 0;
                                    $totalPersenOmzetRealisasiSum = 0;
                                    $totalPersenMarginTargetSum = 0;
                                    $totalPersenMarginRealisasiSum = 0;
                                    $totalGap = 0;
                                    $totalPersenPencapaianSum = 0;
                                    $totalEntitas = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Urutkan berdasarkan realisasi biaya lain-lain tertinggi
                                    uasort($biayaLainPerEntitas, function($a, $b) {
                                        return $b['realisasi'] <=> $a['realisasi'];
                                    });
                                    
                                    foreach ($biayaLainPerEntitas as $entitas => $data):
                                        // Data Biaya Lain-lain
                                        $targetBiayaLain = $data['target'];
                                        $realisasiBiayaLain = $data['realisasi'];
                                        $gap = $data['gap'];
                                        
                                        // Data Omzet Bersih untuk entitas ini
                                        $targetOmzetBersih = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['target'] : 0;
                                        $realisasiOmzetBersih = isset($omzetBersihPerEntitas[$entitas]) ? $omzetBersihPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        // Jika tidak ada data omzet bersih, gunakan data omzet biasa
                                        if ($realisasiOmzetBersih == 0 && isset($entitasDataOmzet[$entitas])) {
                                            $targetOmzetBersih = $entitasDataOmzet[$entitas]['total_target'];
                                            $realisasiOmzetBersih = $entitasDataOmzet[$entitas]['total_realisasi'];
                                        }
                                        
                                        // Hitung margin untuk entitas ini (Omzet Bersih - HPP - Biaya Operasional - Biaya Pemasaran - Biaya Lain-lain)
                                        $realisasiHPP = isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_realisasi'] : 0;
                                        $realisasiBiayaOps = isset($biayaOpsPerEntitas[$entitas]) ? $biayaOpsPerEntitas[$entitas]['realisasi'] : 0;
                                        $realisasiBiayaMarketing = isset($biayaMarketingPerEntitas[$entitas]) ? $biayaMarketingPerEntitas[$entitas]['realisasi'] : 0;
                                        
                                        $marginEntitas = $realisasiOmzetBersih - $realisasiHPP - $realisasiBiayaOps - $realisasiBiayaMarketing - $realisasiBiayaLain;
                                        $targetMarginEntitas = $targetOmzetBersih - 
                                            (isset($entitasDataHPP[$entitas]) ? $entitasDataHPP[$entitas]['total_target'] : 0) -
                                            (isset($biayaOpsPerEntitas[$entitas]) ? $biayaOpsPerEntitas[$entitas]['target'] : 0) -
                                            (isset($biayaMarketingPerEntitas[$entitas]) ? $biayaMarketingPerEntitas[$entitas]['target'] : 0) -
                                            $targetBiayaLain;
                                        
                                        // Hitung % Biaya Lain-lain/Omzet (Target)
                                        $persenOmzetTarget = $targetOmzetBersih > 0 ? ($targetBiayaLain / $targetOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Biaya Lain-lain/Omzet (Realisasi)
                                        $persenOmzetRealisasi = $realisasiOmzetBersih > 0 ? ($realisasiBiayaLain / $realisasiOmzetBersih) * 100 : 0;
                                        
                                        // Hitung % Biaya Lain-lain/Margin (Target)
                                        $persenMarginTarget = $targetMarginEntitas > 0 ? ($targetBiayaLain / $targetMarginEntitas) * 100 : 0;
                                        
                                        // Hitung % Biaya Lain-lain/Margin (Realisasi)
                                        $persenMarginRealisasi = $marginEntitas > 0 ? ($realisasiBiayaLain / $marginEntitas) * 100 : 0;
                                        
                                        // Hitung % Pencapaian Biaya Lain-lain
                                        $persenPencapaian = $targetBiayaLain > 0 ? ($realisasiBiayaLain / $targetBiayaLain) * 100 : 0;
                                        
                                        // Tentukan Status Efisiensi (berdasarkan % Omzet)
                                        $status = '';
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        // ATURAN: Jika % Realisasi <= % Target → EFISIEN, jika tidak → OVER BUDGET
                                        if ($persenOmzetRealisasi <= $persenOmzetTarget) {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $statusText = 'Biaya lain-lain efisien terhadap omzet';
                                            $totalEfisien++;
                                        } else {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $statusText = 'Biaya lain-lain melebihi budget relatif terhadap omzet';
                                            $totalOverBudget++;
                                        }
                                        
                                        // Warna untuk % Omzet (Target dan Realisasi)
                                        $warnaOmzetTarget = $persenOmzetTarget <= 5 ? 'text-success' : ($persenOmzetTarget <= 10 ? 'text-warning' : 'text-danger');
                                        $warnaOmzetRealisasi = $persenOmzetRealisasi <= 5 ? 'text-success' : ($persenOmzetRealisasi <= 10 ? 'text-warning' : 'text-danger');
                                        
                                        // Warna untuk % Margin (Target dan Realisasi)
                                        $warnaMarginTarget = $persenMarginTarget <= 20 ? 'text-success' : ($persenMarginTarget <= 40 ? 'text-warning' : 'text-danger');
                                        $warnaMarginRealisasi = $persenMarginRealisasi <= 20 ? 'text-success' : ($persenMarginRealisasi <= 40 ? 'text-warning' : 'text-danger');
                                        
                                        // Warna untuk % Pencapaian
                                        $warnaPencapaian = '';
                                        if ($persenPencapaian <= 100) {
                                            $warnaPencapaian = 'text-success';
                                        } elseif ($persenPencapaian <= 120) {
                                            $warnaPencapaian = 'text-warning';
                                        } else {
                                            $warnaPencapaian = 'text-danger';
                                        }
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersih += $targetOmzetBersih;
                                        $totalRealisasiOmzetBersih += $realisasiOmzetBersih;
                                        $totalTargetBiayaLain += $targetBiayaLain;
                                        $totalRealisasiBiayaLain += $realisasiBiayaLain;
                                        $totalPersenOmzetTargetSum += $persenOmzetTarget;
                                        $totalPersenOmzetRealisasiSum += $persenOmzetRealisasi;
                                        $totalPersenMarginTargetSum += $persenMarginTarget;
                                        $totalPersenMarginRealisasiSum += $persenMarginRealisasi;
                                        $totalGap += $gap;
                                        $totalPersenPencapaianSum += $persenPencapaian;
                                        $totalEntitas++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        
                                        <!-- Omzet Bersih -->
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersih); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersih); ?></td>
                                        
                                        <!-- Biaya Lain-lain -->
                                        <td class="text-end"><?php echo formatFullNumber($targetBiayaLain); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiBiayaLain); ?></td>
                                        
                                        <!-- % Lain-lain/Omzet -->
                                        <td class="text-end <?php echo $warnaOmzetTarget; ?>">
                                            <strong><?php echo number_format($persenOmzetTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaOmzetRealisasi; ?>">
                                            <strong><?php echo number_format($persenOmzetRealisasi, 2); ?>%</strong>
                                        </td>
                                        
                                        <!-- % Lain-lain/Margin -->
                                        <td class="text-end <?php echo $warnaMarginTarget; ?>">
                                            <strong><?php echo number_format($persenMarginTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $warnaMarginRealisasi; ?>">
                                            <strong><?php echo number_format($persenMarginRealisasi, 2); ?>%</strong>
                                        </td>
                                        
                                        <!-- Gap -->
                                        <td class="text-end <?php echo $gap >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($gap); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian -->
                                        <td class="text-end <?php echo $warnaPencapaian; ?>">
                                            <strong><?php echo number_format($persenPencapaian, 1); ?>%</strong>
                                        </td>
                                        
                                        <!-- Status Efisiensi -->
                                        <td class="text-center">
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($biayaLainPerEntitas)): ?>
                                    <tr>
                                        <td colspan="15" class="text-center">
                                            <div class="alert alert-warning py-2">
                                                <strong>Tidak ada data biaya lain-lain untuk periode yang dipilih!</strong><br>
                                                <small class="small">Data biaya lain-lain mencakup: biaya lain-lain, biaya tak terduga, dan pengeluaran non-rutin lainnya.</small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($biayaLainPerEntitas)): ?>
                                <tfoot style="background-color: #D1FAE5;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        
                                        <!-- Omzet Bersih Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetOmzetBersih); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiOmzetBersih); ?></strong></td>
                                        
                                        <!-- Biaya Lain-lain Total -->
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetBiayaLain); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiBiayaLain); ?></strong></td>
                                        
                                        <!-- % Lain-lain/Omzet Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenOmzetTarget = $totalEntitas > 0 ? $totalPersenOmzetTargetSum / $totalEntitas : 0;
                                                $warnaRataOmzetTarget = $rataPersenOmzetTarget <= 5 ? 'text-success' : ($rataPersenOmzetTarget <= 10 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataOmzetTarget; ?>"><?php echo number_format($rataPersenOmzetTarget, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenOmzetRealisasi = $totalEntitas > 0 ? $totalPersenOmzetRealisasiSum / $totalEntitas : 0;
                                                $warnaRataOmzetRealisasi = $rataPersenOmzetRealisasi <= 5 ? 'text-success' : ($rataPersenOmzetRealisasi <= 10 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataOmzetRealisasi; ?>"><?php echo number_format($rataPersenOmzetRealisasi, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- % Lain-lain/Margin Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenMarginTarget = $totalEntitas > 0 ? $totalPersenMarginTargetSum / $totalEntitas : 0;
                                                $warnaRataMarginTarget = $rataPersenMarginTarget <= 20 ? 'text-success' : ($rataPersenMarginTarget <= 40 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataMarginTarget; ?>"><?php echo number_format($rataPersenMarginTarget, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenMarginRealisasi = $totalEntitas > 0 ? $totalPersenMarginRealisasiSum / $totalEntitas : 0;
                                                $warnaRataMarginRealisasi = $rataPersenMarginRealisasi <= 20 ? 'text-success' : ($rataPersenMarginRealisasi <= 40 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataMarginRealisasi; ?>"><?php echo number_format($rataPersenMarginRealisasi, 2); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Gap Total -->
                                        <td class="text-end <?php echo $totalGap >= 0 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo formatFullNumber($totalGap); ?></strong>
                                        </td>
                                        
                                        <!-- % Pencapaian Rata-rata -->
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenPencapaian = $totalEntitas > 0 ? $totalPersenPencapaianSum / $totalEntitas : 0;
                                                $warnaRataPencapaian = $rataPersenPencapaian <= 100 ? 'text-success' : 
                                                    ($rataPersenPencapaian <= 120 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $warnaRataPencapaian; ?>"><?php echo number_format($rataPersenPencapaian, 1); ?>%</span>
                                            </strong>
                                        </td>
                                        
                                        <!-- Status Total -->
                                        <td class="text-center">
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            $statusTextTotal = '';
                                            
                                            if ($rataPersenOmzetRealisasi <= $rataPersenOmzetTarget) {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                                $statusTextTotal = 'Rata-rata biaya lain-lain efisien terhadap omzet';
                                            } else {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                                $statusTextTotal = 'Rata-rata biaya lain-lain melebihi budget relatif terhadap omzet';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div> 
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Biaya Lain-lain Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>                        
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== GRAFIK TREN BIAYA LAIN-LAIN PER BULAN ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header" style="background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%); color: white;">
                        <h5 class="card-title mb-0">Tren Biaya Lain-lain Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container-medium">
                            <canvas id="chartTrenBiayaLainBulanan"></canvas>
                        </div>
                        <?php
                        // Query untuk mengambil data Omzet Bersih per bulan
                        $sqlOmzetBersihBulananLain = "SELECT 
                            DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                            SUM(nilai_target) as total_target,
                            SUM(nilai_realisasi) as total_realisasi
                        FROM data_report 
                        WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                            OR LOWER(nama_akun) LIKE '%omzetbersih%'
                            OR nama_akun = 'Omzet Bersih')";
                        
                        $paramsOmzetBersihBulananLain = [];
                        
                        // Tambahkan filter tanggal jika ada
                        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                            $sqlOmzetBersihBulananLain .= " AND DATE(tanggal) BETWEEN ? AND ?";
                            $paramsOmzetBersihBulananLain[] = $tanggal_awal;
                            $paramsOmzetBersihBulananLain[] = $tanggal_akhir;
                        } elseif (!empty($tanggal_awal)) {
                            $sqlOmzetBersihBulananLain .= " AND DATE(tanggal) >= ?";
                            $paramsOmzetBersihBulananLain[] = $tanggal_awal;
                        } elseif (!empty($tanggal_akhir)) {
                            $sqlOmzetBersihBulananLain .= " AND DATE(tanggal) <= ?";
                            $paramsOmzetBersihBulananLain[] = $tanggal_akhir;
                        }
                        
                        $sqlOmzetBersihBulananLain .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                        
                        try {
                            $stmtOmzetBersihBulananLain = $pdo->prepare($sqlOmzetBersihBulananLain);
                            $stmtOmzetBersihBulananLain->execute($paramsOmzetBersihBulananLain);
                            $dataOmzetBersihBulananLain = $stmtOmzetBersihBulananLain->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Konversi ke array asosiatif
                            $omzetBersihPerBulanLain = [];
                            foreach ($dataOmzetBersihBulananLain as $row) {
                                $bulan = $row['bulan'];
                                $omzetBersihPerBulanLain[$bulan] = [
                                    'target' => (float)$row['total_target'],
                                    'realisasi' => (float)$row['total_realisasi']
                                ];
                            }
                            
                            // Fallback jika data kosong - gunakan data omzet biasa
                            if (empty($omzetBersihPerBulanLain)) {
                                foreach ($bulanDataOmzet as $bulanKey => $data) {
                                    $omzetBersihPerBulanLain[$bulanKey] = $data;
                                }
                            }
                            
                        } catch (PDOException $e) {
                            // Fallback jika query gagal
                            $omzetBersihPerBulanLain = [];
                            foreach ($bulanDataOmzet as $bulanKey => $data) {
                                $omzetBersihPerBulanLain[$bulanKey] = $data;
                            }
                        }
                        
                        ?>                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background-color: #6B7280; color: white;">
                                        <th>No</th>
                                        <th>Bulan</th>
                                        <th>Target Omzet Bersih</th>
                                        <th>Target Lain-lain</th>
                                        <th>%_Target<br><small>Lain-lain/Omzet Bersih</small></th>
                                        <th>Realisasi Omzet Bersih</th>
                                        <th>Realisasi Lain-lain</th>
                                        <th>%_Realisasi<br><small>Lain-lain/Omzet Bersih</small></th>
                                        <th>Gap Lain-lain</th>
                                        <th>% Pencapaian<br><small>Realisasi/Target</small></th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetOmzetBersihBulananLain = 0;
                                    $totalTargetBulananLain = 0;
                                    $totalRealisasiOmzetBersihBulananLain = 0;
                                    $totalRealisasiBulananLain = 0;
                                    $totalGapBulananLain = 0;
                                    $totalPersenTargetSum = 0;
                                    $totalPersenRealisasiSum = 0;
                                    $totalPersenBiayaLainBulanan = 0;
                                    $totalPersenOmzetLainBulanan = 0;
                                    $totalPersenMarginLainBulanan = 0;
                                    $totalHariBulananLain = 0;
                                    $totalBulanLain = 0;
                                    $totalEfisien = 0;
                                    $totalOverBudget = 0;
                                    
                                    // Gabungkan semua bulan yang ada data
                                    $allMonthsLain = array_unique(array_merge(
                                        array_keys($omzetBersihPerBulanLain),
                                        array_keys($biayaLainPerBulanGrouped)
                                    ));
                                    
                                    // Urutkan bulan
                                    sort($allMonthsLain);
                                    
                                    foreach ($allMonthsLain as $bulanKey):
                                        // Data Omzet Bersih per bulan
                                        $targetOmzetBersihBulanan = isset($omzetBersihPerBulanLain[$bulanKey]) ? 
                                            $omzetBersihPerBulanLain[$bulanKey]['target'] : 0;
                                        $realisasiOmzetBersihBulanan = isset($omzetBersihPerBulanLain[$bulanKey]) ? 
                                            $omzetBersihPerBulanLain[$bulanKey]['realisasi'] : 0;
                                        
                                        // Data Biaya Lain-lain per bulan dengan grouping yang benar
                                        $targetBulan = isset($biayaLainPerBulanGrouped[$bulanKey]) ? 
                                            $biayaLainPerBulanGrouped[$bulanKey]['target'] : 0;
                                        $realisasiBulan = isset($biayaLainPerBulanGrouped[$bulanKey]) ? 
                                            $biayaLainPerBulanGrouped[$bulanKey]['realisasi'] : 0;
                                        
                                        // Data Omzet biasa per bulan (untuk perhitungan persentase)
                                        $omzetBulanan = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['realisasi'] : 0;
                                        $totalBiayaBulanan = isset($tanggalDataBiayaFiltered) ? 
                                            array_sum(array_column($tanggalDataBiayaFiltered, 'realisasi')) : 0;
                                        
                                        // Hitung gap
                                        $gapBulan = $realisasiBulan - $targetBulan;
                                        
                                        // Hitung %_Target (Target Lain-lain / Target Omzet Bersih)
                                        $persenTarget = $targetOmzetBersihBulanan > 0 ? 
                                            ($targetBulan / $targetOmzetBersihBulanan) * 100 : 0;
                                        
                                        // Hitung %_Realisasi (Realisasi Lain-lain / Realisasi Omzet Bersih)
                                        $persenRealisasi = $realisasiOmzetBersihBulanan > 0 ? 
                                            ($realisasiBulan / $realisasiOmzetBersihBulanan) * 100 : 0;
                                        
                                        // Hitung % Pencapaian (Realisasi Lain-lain / Target Lain-lain)
                                        $percentageBulan = $targetBulan > 0 ? 
                                            ($realisasiBulan / $targetBulan) * 100 : 0;
                                        
                                        // Format nama bulan
                                        $tahun = substr($bulanKey, 0, 4);
                                        $bulanAngka = (int)substr($bulanKey, 5, 2);
                                        $namaBulan = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                        
                                        // Hitung jumlah hari dalam bulan
                                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulanAngka, $tahun);
                                        $rataHarian = $daysInMonth > 0 ? $realisasiBulan / $daysInMonth : 0;
                                        
                                        // Hitung persentase dari total biaya bulanan
                                        $persenBiaya = $totalBiayaBulanan > 0 ? ($realisasiBulan / $totalBiayaBulanan) * 100 : 0;
                                        
                                        // Hitung persentase dari omzet bulanan
                                        $persenOmzet = $omzetBulanan > 0 ? ($realisasiBulan / $omzetBulanan) * 100 : 0;
                                        
                                        // Hitung margin bulanan
                                        $hppBulanan = isset($bulanDataHPP[$bulanKey]) ? $bulanDataHPP[$bulanKey]['realisasi'] : 0;
                                        $marginBulanan = $omzetBulanan - $hppBulanan;
                                        $persenMargin = $marginBulanan > 0 ? ($realisasiBulan / $marginBulanan) * 100 : 0;
                                        
                                        // Tentukan Status berdasarkan perbandingan %_Target dan %_Realisasi
                                        $status = '';
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        if ($persenTarget <= $persenRealisasi) {
                                            $status = 'OVER BUDGET';
                                            $statusClass = 'badge-belum-selesai';
                                            $statusText = 'Biaya lain-lain melebihi budget relatif terhadap omzet bersih';
                                            $totalOverBudget++;
                                        } else {
                                            $status = 'EFISIEN';
                                            $statusClass = 'badge-selesai';
                                            $statusText = 'Biaya lain-lain efisien terhadap omzet bersih';
                                            $totalEfisien++;
                                        }
                                        
                                        // Tentukan warna untuk persentase
                                        $persenTargetColor = $persenTarget <= 5 ? 'text-success' : 
                                                            ($persenTarget <= 10 ? 'text-warning' : 'text-danger');
                                        $persenRealisasiColor = $persenRealisasi <= 5 ? 'text-success' : 
                                                            ($persenRealisasi <= 10 ? 'text-warning' : 'text-danger');
                                        $persenMarginColor = $persenMargin <= 20 ? 'text-success' : 
                                                        ($persenMargin <= 40 ? 'text-warning' : 'text-danger');
                                        
                                        // Akumulasi total
                                        $totalTargetOmzetBersihBulananLain += $targetOmzetBersihBulanan;
                                        $totalTargetBulananLain += $targetBulan;
                                        $totalRealisasiOmzetBersihBulananLain += $realisasiOmzetBersihBulanan;
                                        $totalRealisasiBulananLain += $realisasiBulan;
                                        $totalGapBulananLain += $gapBulan;
                                        $totalPersenTargetSum += $persenTarget;
                                        $totalPersenRealisasiSum += $persenRealisasi;
                                        $totalPersenBiayaLainBulanan += $persenBiaya;
                                        $totalPersenOmzetLainBulanan += $persenOmzet;
                                        $totalPersenMarginLainBulanan += $persenMargin;
                                        $totalHariBulananLain += $daysInMonth;
                                        $totalBulanLain++;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($namaBulan . ' ' . $tahun); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihBulanan); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($targetBulan); ?></td>
                                        <td class="text-end <?php echo $persenTargetColor; ?>">
                                            <strong><?php echo number_format($persenTarget, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihBulanan); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasiBulan); ?></td>
                                        <td class="text-end <?php echo $persenRealisasiColor; ?>">
                                            <strong><?php echo number_format($persenRealisasi, 2); ?>%</strong>
                                        </td>
                                        <td class="text-end <?php echo $gapBulan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gapBulan); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo number_format($percentageBulan, 1); ?>%
                                        </td>
                                        <td>
                                            <?php echo $status; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($allMonthsLain)): ?>
                                    <tr>
                                        <td colspan="15" class="text-center">
                                            <div class="alert alert-warning">
                                                <strong>Tidak ada data biaya lain-lain untuk periode yang dipilih!</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($allMonthsLain)): ?>
                                <tfoot style="background-color: #f3f4f6;">
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalTargetOmzetBersihBulananLain); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalTargetBulananLain); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenTarget = $totalBulanLain > 0 ? $totalPersenTargetSum / $totalBulanLain : 0;
                                                $rataPersenTargetColor = $rataPersenTarget <= 5 ? 'text-success' : 
                                                                        ($rataPersenTarget <= 10 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $rataPersenTargetColor; ?>">
                                                    <?php echo number_format($rataPersenTarget, 2); ?>%
                                                </span>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalRealisasiOmzetBersihBulananLain); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo formatFullNumber($totalRealisasiBulananLain); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $rataPersenRealisasi = $totalBulanLain > 0 ? $totalPersenRealisasiSum / $totalBulanLain : 0;
                                                $rataPersenRealisasiColor = $rataPersenRealisasi <= 5 ? 'text-success' : 
                                                                        ($rataPersenRealisasi <= 10 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $rataPersenRealisasiColor; ?>">
                                                    <?php echo number_format($rataPersenRealisasi, 2); ?>%
                                                </span>
                                            </strong>
                                        </td>
                                        <td class="text-end <?php echo $totalGapBulananLain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapBulananLain); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $totalPercentageLain = $totalTargetBulananLain > 0 ? 
                                                    ($totalRealisasiBulananLain / $totalTargetBulananLain) * 100 : 0;
                                                ?>
                                                <?php echo number_format($totalPercentageLain, 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Tentukan status total berdasarkan perbandingan rata-rata
                                            $statusTotal = '';
                                            $statusClassTotal = '';
                                            $statusTextTotal = '';
                                            
                                            if ($rataPersenTarget <= $rataPersenRealisasi) {
                                                $statusTotal = 'OVER BUDGET';
                                                $statusClassTotal = 'badge-belum-selesai';
                                                $statusTextTotal = 'Rata-rata biaya lain-lain melebihi budget';
                                            } else {
                                                $statusTotal = 'EFISIEN';
                                                $statusClassTotal = 'badge-selesai';
                                                $statusTextTotal = 'Rata-rata biaya lain-lain efisien';
                                            }
                                            ?>
                                            <?php echo $statusTotal; ?>
                                        </td>                                
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Tren Biaya Lain-lain Per Bulan')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ANALISIS NPM ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Analisis Net Profit Margin Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <!-- Grafik 2 Kolom FIX: Analisis NPM -->
                        <div class="chart-fixed-columns mb-4">
                            <!-- Kolom 1: NPM per Entitas (Target vs Realisasi) -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">NPM Per Entitas - Target vs Realisasi</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartNPMEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <?php
                                            // Hitung statistik untuk NPM
                                            $totalTargetNPMEntitas = 0;
                                            $totalRealisasiNPMEntitas = 0;
                                            $rataTargetNPMEntitas = 0;
                                            $rataRealisasiNPMEntitas = 0;
                                            $jumlahEntitasNPM = count($npmPerEntitas);
                                            
                                            if ($jumlahEntitasNPM > 0) {
                                                foreach ($npmPerEntitas as $data) {
                                                    $totalTargetNPMEntitas += $data['target'];
                                                    $totalRealisasiNPMEntitas += $data['realisasi'];
                                                }
                                                $rataTargetNPMEntitas = $totalTargetNPMEntitas / $jumlahEntitasNPM;
                                                $rataRealisasiNPMEntitas = $totalRealisasiNPMEntitas / $jumlahEntitasNPM;
                                            }
                                            ?>
                                            <p class="text-muted mb-1">
                                                <strong>Total Entitas:</strong> <?php echo $jumlahEntitasNPM; ?>
                                            </p>
                                            <p class="text-muted mb-1">
                                                <strong>Rata Target:</strong> <?php echo formatFullNumber($rataTargetNPMEntitas); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Rata Realisasi:</strong> <?php echo formatFullNumber($rataRealisasiNPMEntitas); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2: Proporsi NPM per Entitas -->
                            <div class="chart-fixed-col">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Proporsi NPM Per Entitas</h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="chart-container-fixed">
                                            <canvas id="chartProporsiNPMEntitas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <?php
                                            // Hitung proporsi
                                            $entitasTerbesarNPM = '';
                                            $nilaiTerbesarNPM = 0;
                                            $persenTerbesarNPM = 0;
                                            
                                            if (!empty($npmPerEntitas)) {
                                                // Cari entitas dengan NPM terbesar
                                                foreach ($npmPerEntitas as $entitas => $data) {
                                                    if ($data['realisasi'] > $nilaiTerbesarNPM) {
                                                        $nilaiTerbesarNPM = $data['realisasi'];
                                                        $entitasTerbesarNPM = $entitas;
                                                    }
                                                }
                                                
                                                // Hitung persentase
                                                if ($totalRealisasiNPMEntitas > 0) {
                                                    $persenTerbesarNPM = ($nilaiTerbesarNPM / $totalRealisasiNPMEntitas) * 100;
                                                }
                                            }
                                            ?>
                                            <p class="text-muted mb-1">
                                                <strong>Total NPM:</strong> <?php echo formatFullNumber($totalRealisasiNPMEntitas); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Entitas Terbesar:</strong> 
                                                <?php 
                                                if (!empty($entitasTerbesarNPM)) {
                                                    echo htmlspecialchars($entitasTerbesarNPM) . ' (' . formatFullNumber($nilaiTerbesarNPM) . ' - ' . number_format($persenTerbesarNPM, 1) . '%)';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabel Detail NPM per Entitas -->
                        <h5 class="mb-3">Tabel NPM Per Entitas</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Entitas</th>
                                        <th>Target NPM</th>
                                        <th>Realisasi NPM</th>
                                        <th>Gap</th>
                                        <th>% Pencapaian</th>
                                        <th>% terhadap Total NPM</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetNPMAll = 0;
                                    $totalRealisasiNPMAll = 0;
                                    $totalGapNPMAll = 0;
                                    
                                    // Urutkan berdasarkan realisasi tertinggi
                                    uasort($npmPerEntitas, function($a, $b) {
                                        return $b['realisasi'] <=> $a['realisasi'];
                                    });
                                    
                                    foreach ($npmPerEntitas as $entitas => $data):
                                        $target = $data['target'];
                                        $realisasi = $data['realisasi'];
                                        $gap = $data['gap'];
                                        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                                        $persenTotal = $totalRealisasiNPMEntitas > 0 ? ($realisasi / $totalRealisasiNPMEntitas) * 100 : 0;
                                        
                                        $totalTargetNPMAll += $target;
                                        $totalRealisasiNPMAll += $realisasi;
                                        $totalGapNPMAll += $gap;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entitas); ?></strong></td>
                                        <td class="text-end"><?php echo formatFullNumber($target); ?></td>
                                        <td class="text-end"><?php echo formatFullNumber($realisasi); ?></td>
                                        <td class="text-end <?php echo $gap >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatFullNumber($gap); ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="<?php echo $percentage >= 100 ? 'text-success' : ($percentage >= 80 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="ms-2"><?php echo number_format($persenTotal, 1); ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $percentage >= 100 ? 'ACHIEVE' : 'NOT ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetNPMAll); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiNPMAll); ?></strong></td>
                                        <td class="text-end <?php echo $totalGapNPMAll >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapNPMAll); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <?php 
                                            $totalPercentageNPMAll = $totalTargetNPMAll > 0 ? ($totalRealisasiNPMAll / $totalTargetNPMAll) * 100 : 0;
                                            ?>
                                            <strong>
                                                <span class="<?php echo $totalPercentageNPMAll >= 100 ? 'text-success' : ($totalPercentageNPMAll >= 80 ? 'text-warning' : 'text-danger'); ?>">
                                                    <?php echo number_format($totalPercentageNPMAll, 1); ?>%
                                                </span>
                                            </strong>
                                        </td>
                                        <td><strong>100%</strong></td>
                                        <td>
                                            <?php echo $totalPercentageNPMAll >= 100 ? 'ACHIEVE' : 'NOT ACHIEVE'; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Analisis Net Profit Margin Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== GRAFIK TREN NPM PER BULAN ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tren Net Profit Margin Per Bulan</h5>
                    </div>
                    <div class="card-body p-2">
                        <?php
                            // Inisialisasi array untuk bulan dalam rentang
                            $monthsInRange = [];
                            
                            // Generate semua bulan dalam rentang tanggal
                            if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                $start = new DateTime($tanggal_awal);
                                $end = new DateTime($tanggal_akhir);
                                $end->modify('last day of this month');
                                
                                $interval = DateInterval::createFromDateString('1 month');
                                $period = new DatePeriod($start, $interval, $end);
                                
                                foreach ($period as $dt) {
                                    $monthsInRange[] = $dt->format("Y-m");
                                }
                            }
                            
                            // Query untuk data NPM per bulan
                            $sqlNPMChart = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                                SUM(nilai_target) as total_target,
                                SUM(nilai_realisasi) as total_realisasi
                            FROM data_report 
                            WHERE (LOWER(nama_akun) LIKE '%npm%' 
                                OR nama_akun = 'NPM' 
                                OR nama_akun = 'NET PROFIT MARGIN' 
                                OR nama_akun = 'NET PROFIT' 
                                OR nama_akun = 'PROFIT MARGIN')";
                            
                            $paramsNPMChart = [];
                            
                            // Filter tanggal
                            if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                $sqlNPMChart .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                $paramsNPMChart[] = $tanggal_awal;
                                $paramsNPMChart[] = $tanggal_akhir;
                            } elseif (!empty($tanggal_awal)) {
                                $sqlNPMChart .= " AND DATE(tanggal) >= ?";
                                $paramsNPMChart[] = $tanggal_awal;
                            } elseif (!empty($tanggal_akhir)) {
                                $sqlNPMChart .= " AND DATE(tanggal) <= ?";
                                $paramsNPMChart[] = $tanggal_akhir;
                            }
                            
                            $sqlNPMChart .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                            
                            try {
                                $stmtNPMChart = $pdo->prepare($sqlNPMChart);
                                $stmtNPMChart->execute($paramsNPMChart);
                                $dataNPMChart = $stmtNPMChart->fetchAll(PDO::FETCH_ASSOC);
                                
                                $npmChartData = [];
                                foreach ($dataNPMChart as $row) {
                                    $bulan = $row['bulan'];
                                    if (empty($monthsInRange) || in_array($bulan, $monthsInRange)) {
                                        $npmChartData[$bulan] = [
                                            'target' => (float)$row['total_target'],
                                            'realisasi' => (float)$row['total_realisasi']
                                        ];
                                    }
                                }
                            } catch (PDOException $e) {
                                $npmChartData = [];
                            }
                            
                            // Query untuk Omzet Bersih per bulan
                            $sqlOmzetBersihChart = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                                SUM(nilai_realisasi) as total_realisasi
                            FROM data_report 
                            WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                                OR LOWER(nama_akun) LIKE '%omzetbersih%'
                                OR nama_akun = 'Omzet Bersih')";
                            
                            $paramsOmzetBersihChart = [];
                            
                            if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                $sqlOmzetBersihChart .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                $paramsOmzetBersihChart[] = $tanggal_awal;
                                $paramsOmzetBersihChart[] = $tanggal_akhir;
                            } elseif (!empty($tanggal_awal)) {
                                $sqlOmzetBersihChart .= " AND DATE(tanggal) >= ?";
                                $paramsOmzetBersihChart[] = $tanggal_awal;
                            } elseif (!empty($tanggal_akhir)) {
                                $sqlOmzetBersihChart .= " AND DATE(tanggal) <= ?";
                                $paramsOmzetBersihChart[] = $tanggal_akhir;
                            }
                            
                            $sqlOmzetBersihChart .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                            
                            try {
                                $stmtOmzetBersihChart = $pdo->prepare($sqlOmzetBersihChart);
                                $stmtOmzetBersihChart->execute($paramsOmzetBersihChart);
                                $dataOmzetBersihChart = $stmtOmzetBersihChart->fetchAll(PDO::FETCH_ASSOC);
                                
                                $omzetBersihChartData = [];
                                foreach ($dataOmzetBersihChart as $row) {
                                    $bulan = $row['bulan'];
                                    if (empty($monthsInRange) || in_array($bulan, $monthsInRange)) {
                                        $omzetBersihChartData[$bulan] = [
                                            'realisasi' => (float)$row['total_realisasi']
                                        ];
                                    }
                                }
                            } catch (PDOException $e) {
                                $omzetBersihChartData = [];
                            }
                            
                            // Siapkan data untuk chart
                            $chartLabelsNPM = [];
                            $chartTargetDataNPM = [];
                            $chartRealisasiDataNPM = [];
                            $chartPersentaseDataNPM = [];
                            
                            $monthsForChart = !empty($monthsInRange) ? $monthsInRange : 
                                array_unique(array_merge(array_keys($npmChartData), array_keys($omzetBersihChartData)));
                            
                            sort($monthsForChart);
                            
                            foreach ($monthsForChart as $bulanKey) {
                                $tahun = substr($bulanKey, 0, 4);
                                $bulanAngka = (int)substr($bulanKey, 5, 2);
                                $chartLabelsNPM[] = date('M Y', strtotime($bulanKey . '-01'));
                                
                                $targetNPM = isset($npmChartData[$bulanKey]) ? $npmChartData[$bulanKey]['target'] : 0;
                                $realisasiNPM = isset($npmChartData[$bulanKey]) ? $npmChartData[$bulanKey]['realisasi'] : 0;
                                $chartTargetDataNPM[] = $targetNPM;
                                $chartRealisasiDataNPM[] = $realisasiNPM;
                                
                                $omzetBersih = isset($omzetBersihChartData[$bulanKey]) ? 
                                    $omzetBersihChartData[$bulanKey]['realisasi'] : 0;
                                $persentase = $omzetBersih > 0 ? ($realisasiNPM / $omzetBersih) * 100 : 0;
                                $chartPersentaseDataNPM[] = $persentase;
                            }
                            
                            // Simpan data ke array untuk digunakan nanti di JavaScript
                            $chartDataNPM = [
                                'labels' => $chartLabelsNPM,
                                'target' => $chartTargetDataNPM,
                                'realisasi' => $chartRealisasiDataNPM,
                                'persentase' => $chartPersentaseDataNPM
                            ];
                            ?>
                            
                            <div class="chart-container-medium">
                                <canvas id="chartTrenNPMBulanan"></canvas>
                            </div>
                            <div class="card-body p-2">
                            <?php
                            // Query untuk Omzet Bersih per bulan
                            $sqlOmzetBersihBulananNPM = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                                SUM(nilai_target) as total_target,
                                SUM(nilai_realisasi) as total_realisasi
                            FROM data_report 
                            WHERE (LOWER(nama_akun) LIKE '%omzet bersih%' 
                                OR LOWER(nama_akun) LIKE '%omzetbersih%'
                                OR nama_akun = 'Omzet Bersih')";
                            
                            $paramsOmzetBersihBulananNPM = [];
                            
                            if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                $sqlOmzetBersihBulananNPM .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                $paramsOmzetBersihBulananNPM[] = $tanggal_awal;
                                $paramsOmzetBersihBulananNPM[] = $tanggal_akhir;
                            } elseif (!empty($tanggal_awal)) {
                                $sqlOmzetBersihBulananNPM .= " AND DATE(tanggal) >= ?";
                                $paramsOmzetBersihBulananNPM[] = $tanggal_awal;
                            } elseif (!empty($tanggal_akhir)) {
                                $sqlOmzetBersihBulananNPM .= " AND DATE(tanggal) <= ?";
                                $paramsOmzetBersihBulananNPM[] = $tanggal_akhir;
                            }
                            
                            $sqlOmzetBersihBulananNPM .= " GROUP BY DATE_FORMAT(tanggal, '%Y-%m') ORDER BY bulan";
                            
                            try {
                                $stmtOmzetBersihBulananNPM = $pdo->prepare($sqlOmzetBersihBulananNPM);
                                $stmtOmzetBersihBulananNPM->execute($paramsOmzetBersihBulananNPM);
                                $dataOmzetBersihBulananNPM = $stmtOmzetBersihBulananNPM->fetchAll(PDO::FETCH_ASSOC);
                                
                                $omzetBersihPerBulanNPM = [];
                                foreach ($dataOmzetBersihBulananNPM as $row) {
                                    $bulan = $row['bulan'];
                                    $omzetBersihPerBulanNPM[$bulan] = [
                                        'target' => (float)$row['total_target'],
                                        'realisasi' => (float)$row['total_realisasi']
                                    ];
                                }
                            } catch (PDOException $e) {
                                $omzetBersihPerBulanNPM = [];
                            }
                            
                            // GUNAKAN DATA NPM YANG SUDAH ADA ($npmPerBulan) - JANGAN QUERY ULANG!
                            // Data NPM sudah benar di $npmPerBulan dari kode awal
                            
                            // Gabungkan bulan yang ada data
                            $allMonthsNPM = array_unique(array_merge(
                                array_keys($npmPerBulan),
                                array_keys($omzetBersihPerBulanNPM)
                            ));
                            
                            if (!empty($monthsInRange)) {
                                $allMonthsNPM = array_intersect($allMonthsNPM, $monthsInRange);
                            }
                            
                            sort($allMonthsNPM);
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr style="background-color: #10B981; color: white;">
                                            <th>No</th>
                                            <th>Bulan</th>
                                            <th>Target Omzet Bersih</th>
                                            <th>Target NPM</th>
                                            <th>% NPM/Omzet (Target)</th>
                                            <th>Realisasi Omzet Bersih</th>
                                            <th>Realisasi NPM</th>
                                            <th>% NPM/Omzet (Realisasi)</th>
                                            <th>Gap NPM</th>
                                            <th>% Pencapaian NPM</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        $totalTargetOmzetBersihBulananNPM = 0;
                                        $totalTargetBulananNPM = 0;
                                        $totalRealisasiOmzetBersihBulananNPM = 0;
                                        $totalRealisasiBulananNPM = 0;
                                        $totalGapBulananNPM = 0;
                                        $totalPersenTargetSum = 0;
                                        $totalPersenRealisasiSum = 0;
                                        $totalBulanNPM = 0;
                                        
                                        if (empty($allMonthsNPM)): ?>
                                            <tr>
                                                <td colspan="12" class="text-center">
                                                    <div class="alert alert-info">
                                                        <strong>Tidak ada data NPM untuk periode yang dipilih!</strong>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else:
                                            foreach ($allMonthsNPM as $bulanKey):
                                                $targetOmzetBersihBulanan = isset($omzetBersihPerBulanNPM[$bulanKey]) ? 
                                                    $omzetBersihPerBulanNPM[$bulanKey]['target'] : 0;
                                                $realisasiOmzetBersihBulanan = isset($omzetBersihPerBulanNPM[$bulanKey]) ? 
                                                    $omzetBersihPerBulanNPM[$bulanKey]['realisasi'] : 0;
                                                
                                                // AMBIL DATA NPM DARI $npmPerBulan (YANG SUDAH BENAR)
                                                $targetBulanNPM = isset($npmPerBulan[$bulanKey]) ? 
                                                    $npmPerBulan[$bulanKey]['target'] : 0;
                                                $realisasiBulanNPM = isset($npmPerBulan[$bulanKey]) ? 
                                                    $npmPerBulan[$bulanKey]['realisasi'] : 0;
                                                
                                                $gapBulanNPM = $realisasiBulanNPM - $targetBulanNPM;
                                                $persenTarget = $targetOmzetBersihBulanan > 0 ? 
                                                    ($targetBulanNPM / $targetOmzetBersihBulanan) * 100 : 0;
                                                $persenRealisasi = $realisasiOmzetBersihBulanan > 0 ? 
                                                    ($realisasiBulanNPM / $realisasiOmzetBersihBulanan) * 100 : 0;
                                                $percentageBulanNPM = $targetBulanNPM > 0 ? 
                                                    ($realisasiBulanNPM / $targetBulanNPM) * 100 : 0;
                                                
                                                $tahun = substr($bulanKey, 0, 4);
                                                $bulanAngka = (int)substr($bulanKey, 5, 2);
                                                $namaBulan = date('F', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                                                
                                                // Tentukan status pencapaian
                                                $statusPencapaian = $percentageBulanNPM >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE';
                                                $statusPencapaianClass = $percentageBulanNPM >= 100 ? 'badge-selesai' : 'badge-belum-selesai';
                                                
                                                // Tentukan profitabilitas
                                                if ($persenRealisasi >= 20) {
                                                    $statusProfit = 'EXCELLENT';
                                                    $statusProfitClass = 'badge-success';
                                                } elseif ($persenRealisasi >= 15) {
                                                    $statusProfit = 'VERY GOOD';
                                                    $statusProfitClass = 'badge-primary';
                                                } elseif ($persenRealisasi >= 10) {
                                                    $statusProfit = 'GOOD';
                                                    $statusProfitClass = 'badge-info';
                                                } elseif ($persenRealisasi >= 5) {
                                                    $statusProfit = 'AVERAGE';
                                                    $statusProfitClass = 'badge-warning';
                                                } elseif ($persenRealisasi >= 0) {
                                                    $statusProfit = 'POOR';
                                                    $statusProfitClass = 'badge-danger';
                                                } else {
                                                    $statusProfit = 'LOSS';
                                                    $statusProfitClass = 'badge-dark';
                                                }
                                                
                                                // Warna untuk persentase
                                                $persenTargetColor = $persenTarget >= 20 ? 'text-success' : 
                                                                    ($persenTarget >= 10 ? 'text-warning' : 'text-danger');
                                                $persenRealisasiColor = $persenRealisasi >= 20 ? 'text-success' : 
                                                                        ($persenRealisasi >= 10 ? 'text-warning' : 'text-danger');
                                                
                                                $totalTargetOmzetBersihBulananNPM += $targetOmzetBersihBulanan;
                                                $totalTargetBulananNPM += $targetBulanNPM;
                                                $totalRealisasiOmzetBersihBulananNPM += $realisasiOmzetBersihBulanan;
                                                $totalRealisasiBulananNPM += $realisasiBulanNPM;
                                                $totalGapBulananNPM += $gapBulanNPM;
                                                $totalPersenTargetSum += $persenTarget;
                                                $totalPersenRealisasiSum += $persenRealisasi;
                                                $totalBulanNPM++;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($namaBulan . ' ' . $tahun); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($targetOmzetBersihBulanan); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($targetBulanNPM); ?></td>
                                            <td class="text-end <?php echo $persenTargetColor; ?>">
                                                <?php echo number_format($persenTarget, 2); ?>%
                                            </td>
                                            <td class="text-end"><?php echo formatFullNumber($realisasiOmzetBersihBulanan); ?></td>
                                            <td class="text-end"><?php echo formatFullNumber($realisasiBulanNPM); ?></td>
                                            <td class="text-end <?php echo $persenRealisasiColor; ?>">
                                                <?php echo number_format($persenRealisasi, 2); ?>%
                                            </td>
                                            <td class="text-end <?php echo $gapBulanNPM >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatFullNumber($gapBulanNPM); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($percentageBulanNPM, 1); ?>%
                                            </td>
                                            <td class="text-center">
                                                <?php echo $statusPencapaian; ?>
                                            </td>
                                        </tr>
                                        <?php 
                                            endforeach;
                                        endif; ?>
                                    </tbody>
                                    <?php if (!empty($allMonthsNPM)): ?>
                                    <tfoot style="background-color: #d1fae5;">
                                        <tr class="table-primary">
                                            <td colspan="2" class="text-center"><strong>TOTAL / RATA-RATA</strong></td>
                                            <td class="text-end">
                                                <strong><?php echo formatFullNumber($totalTargetOmzetBersihBulananNPM); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo formatFullNumber($totalTargetBulananNPM); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                $rataPersenTarget = $totalBulanNPM > 0 ? $totalPersenTargetSum / $totalBulanNPM : 0;
                                                $rataPersenTargetColor = $rataPersenTarget >= 20 ? 'text-success' : 
                                                                        ($rataPersenTarget >= 10 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <strong class="<?php echo $rataPersenTargetColor; ?>">
                                                    <?php echo number_format($rataPersenTarget, 2); ?>%
                                                </strong>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo formatFullNumber($totalRealisasiOmzetBersihBulananNPM); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo formatFullNumber($totalRealisasiBulananNPM); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                $rataPersenRealisasi = $totalBulanNPM > 0 ? $totalPersenRealisasiSum / $totalBulanNPM : 0;
                                                $rataPersenRealisasiColor = $rataPersenRealisasi >= 20 ? 'text-success' : 
                                                                            ($rataPersenRealisasi >= 10 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <strong class="<?php echo $rataPersenRealisasiColor; ?>">
                                                    <?php echo number_format($rataPersenRealisasi, 2); ?>%
                                                </strong>
                                            </td>
                                            <td class="text-end <?php echo $totalGapBulananNPM >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo formatFullNumber($totalGapBulananNPM); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                $totalPercentageNPM = $totalTargetBulananNPM > 0 ? 
                                                    ($totalRealisasiBulananNPM / $totalTargetBulananNPM) * 100 : 0;
                                                ?>
                                                <strong><?php echo number_format($totalPercentageNPM, 1); ?>%</strong>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                $totalPercentageNPM = $totalTargetBulananNPM > 0 ? 
                                                    ($totalRealisasiBulananNPM / $totalTargetBulananNPM) * 100 : 0;
                                                ?>
                                                <?php echo $totalPercentageNPM >= 100 ? 'ACHIVE' : 'TIDAK ACHIVE'; ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>                            
                            <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                            <?php if (!empty($dataHasilAnalisis)): ?>
                                <?php foreach ($dataHasilAnalisis as $row): ?>
                                    <?php if(strtolower($row['title']) == strtolower('Tren Net Profit Margin Per Bulan')): ?>
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                            </div>
                                            <div class="card-body p-2">
                                            <?php 
                                                $cleanedContent = nl2br($row['analisis']);
                                                $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                                $cleanedContent = str_replace('\n', '', $cleanedContent);
                                                $cleanedContent = str_replace('"', '', $cleanedContent);
                                                echo $cleanedContent; 
                                                ?>
                                            </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>      
        
        <!-- ==================== GOAL VS REALITY LAPORAN KEUANGAN PER ENTITAS (1 CARD PER ENTITAS) ==================== -->
        <!-- Tabel Goal vs Reality Laporan Keuangan Per Entitas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Goal vs Reality Laporan Keuangan Per Entitas</h5>
                    </div>
                    <div class="card-body p-2">
                        <?php
                        // Loop melalui setiap entitas
                        foreach ($entitasData as $entitas => $akunDataEntitas):
                            // Hitung total target dan realisasi Omzet Bersih untuk entitas ini
                            $targetOmzetBersihEntitas = 0;
                            $realisasiOmzetBersihEntitas = 0;
                            
                            // Cari akun "Omzet Bersih" dalam entitas ini
                            foreach ($akunDataEntitas as $akunNama => $dataAkun) {
                                $akunNamaNormalized = strtoupper(trim($akunNama));
                                if (strpos($akunNamaNormalized, 'OMZET BERSIH') !== false || 
                                    strpos($akunNamaNormalized, 'OMZETBERSIH') !== false ||
                                    $akunNamaNormalized === 'OMZET BERSIH') {
                                    $targetOmzetBersihEntitas = $dataAkun['total_target'];
                                    $realisasiOmzetBersihEntitas = $dataAkun['total_realisasi'];
                                    break;
                                }
                            }
                            
                            // Jika tidak ditemukan "Omzet Bersih", cari "Omzet"
                            if ($realisasiOmzetBersihEntitas == 0) {
                                foreach ($akunDataEntitas as $akunNama => $dataAkun) {
                                    $akunNamaNormalized = strtoupper(trim($akunNama));
                                    if ($akunNamaNormalized === 'OMZET') {
                                        $realisasiOmzetBersihEntitas = $dataAkun['total_realisasi'];
                                        break;
                                    }
                                }
                            }
                            
                            // Siapkan array untuk urutan dan sorting
                            $akunDataWithUrutan = [];
                            
                            // Ambil urutan langsung dari data_report untuk setiap akun
                            foreach ($akunDataEntitas as $akunNama => $dataAkun) {
                                // Cari urutan untuk akun ini dari array $akunUrutan yang sudah dibuat di awal
                                $urutan = isset($akunUrutan[$akunNama]) ? $akunUrutan[$akunNama] : 9999;
                                
                                $akunDataWithUrutan[] = [
                                    'nama' => $akunNama,
                                    'data' => $dataAkun,
                                    'urutan' => $urutan
                                ];
                            }
                            
                            // Urutkan berdasarkan urutan (ascending)
                            usort($akunDataWithUrutan, function($a, $b) {
                                if ($a['urutan'] == $b['urutan']) {
                                    return strcmp($a['nama'], $b['nama']);
                                }
                                return $a['urutan'] - $b['urutan'];
                            });
                        ?>
                        
                        <!-- Card untuk setiap entitas -->
                        <h5><?php echo htmlspecialchars($entitas); ?></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;">No</th>
                                        <th style="width: 150px;">Nama Akun</th>
                                        <th style="width: 100px; text-align: right;">Target</th>
                                        <th style="width: 80px; text-align: right;">% Target</th>
                                        <th style="width: 100px; text-align: right;">Realisasi</th>
                                        <th style="width: 80px; text-align: right;">% Realisasi</th>
                                        <th style="width: 100px; text-align: right;">Gap</th>
                                        <th style="width: 80px; text-align: right;">% Pencapaian</th>
                                        <th style="width: 120px; text-align: center;">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $totalTargetEntitas = 0;
                                    $totalRealisasiEntitas = 0;
                                    $totalGapEntitas = 0;
                                    
                                    foreach ($akunDataWithUrutan as $akunItem): 
                                        $akunNama = $akunItem['nama'];
                                        $data = $akunItem['data'];
                                        $urutanAkunValue = $akunItem['urutan'];
                                        
                                        $gapAkun = $data['total_realisasi'] - $data['total_target'];
                                        $percentageAkun = $data['total_target'] > 0 ? ($data['total_realisasi'] / $data['total_target']) * 100 : 0;
                                        
                                        // Normalisasi nama akun untuk perbandingan case-insensitive
                                        $akunNamaNormalized = strtoupper(trim($akunNama));
                                        
                                        // Cek apakah ini akun "Omzet Bersih"
                                        $isOmzetBersih = (strpos($akunNamaNormalized, 'OMZET BERSIH') !== false || 
                                                        strpos($akunNamaNormalized, 'OMZETBERSIH') !== false);
                                        
                                        // ==================== PERHITUNGAN % TARGET ====================
                                        // 1. Jika akun adalah 'Omzet' atau 'Omzet Bersih' maka % Target = 100%
                                        if ($akunNamaNormalized === 'OMZET' || $isOmzetBersih) {
                                            $percentageTarget = 100;
                                        } 
                                        // 2. Untuk akun lainnya: (target akun / target omzet bersih) × 100%
                                        else {
                                            $percentageTarget = $targetOmzetBersihEntitas > 0 ? ($data['total_target'] / $targetOmzetBersihEntitas) * 100 : 0;
                                        }
                                        
                                        // ==================== PERHITUNGAN % REALISASI ====================
                                        // 1. Jika akun adalah 'Omzet' maka % Realisasi = (realisasi omzet / target omzet) × 100%
                                        if ($akunNamaNormalized === 'OMZET') {
                                            $percentageRealisasi = $data['total_target'] > 0 ? ($data['total_realisasi'] / $data['total_target']) * 100 : 0;
                                        }
                                        // 2. Jika akun adalah 'Omzet Bersih' maka % Realisasi = (realisasi omzet bersih / target omzet bersih) × 100%
                                        elseif ($isOmzetBersih) {
                                            $percentageRealisasi = $data['total_target'] > 0 ? ($data['total_realisasi'] / $data['total_target']) * 100 : 0;
                                        }
                                        // 3. Untuk akun lainnya: (realisasi akun / realisasi omzet bersih) × 100%
                                        else {
                                            $percentageRealisasi = $realisasiOmzetBersihEntitas > 0 ? ($data['total_realisasi'] / $realisasiOmzetBersihEntitas) * 100 : 0;
                                        }
                                        
                                        // ==================== LOGIKA KETERANGAN ====================
                                        $statusText = '';
                                        $statusClass = '';
                                        
                                        // 1. Untuk akun 'Omzet', 'Omzet Bersih', 'Ziswaf', dan 'NPM'

                                        $akunNamaNormalized = strtoupper(trim($akunNama));

                                        // Cek apakah ini akun "Omzet Bersih"
                                        $isOmzetBersih = (strpos($akunNamaNormalized, 'OMZET BERSIH') !== false || 
                                                        strpos($akunNamaNormalized, 'OMZETBERSIH') !== false);

                                        // Cek apakah ini akun yang mengandung kata "Omzet" (lebih fleksibel)
                                        $isOmzet = (strpos($akunNamaNormalized, 'OMZET') !== false);

                                        if ($isOmzet ||
                                            $isOmzetBersih ||
                                            strpos($akunNamaNormalized, 'ZISWAF') !== false ||
                                            strpos($akunNamaNormalized, 'ZAKAT') !== false ||
                                            strpos($akunNamaNormalized, 'INFAQ') !== false ||
                                            strpos($akunNamaNormalized, 'SEDEKAH') !== false ||
                                            strpos($akunNamaNormalized, 'WAKAF') !== false ||
                                            strpos($akunNamaNormalized, 'NPM') !== false ||
                                            strpos($akunNamaNormalized, 'EBITDA') !== false ||
                                            strpos($akunNamaNormalized, 'NET PROFIT') !== false) {
                                            
                                            // Jika realisasi ≥ target → ACHIEVE, selain itu TIDAK ACHIEVE
                                            if ($data['total_realisasi'] >= $data['total_target']) {
                                                $statusText = 'ACHIEVE';
                                                $statusClass = 'text-success';
                                            } else {
                                                $statusText = 'TIDAK ACHIEVE';
                                                $statusClass = 'text-danger';
                                            }
                                        } 
                                        // 2. Untuk akun lainnya
                                        else {
                                            // Jika % Target ≤ % Realisasi → OVER BUDGET, selain itu EFISIEN
                                            if ($percentageTarget <= $percentageRealisasi) {
                                                $statusText = 'OVER BUDGET';
                                                $statusClass = 'text-danger';
                                            } else {
                                                $statusText = 'EFISIEN';
                                                $statusClass = 'text-success';
                                            }
                                        }
                                        
                                        // Format persentase
                                        $percentageTargetFormatted = number_format($percentageTarget, 2) . '%';
                                        $percentageRealisasiFormatted = number_format($percentageRealisasi, 2) . '%';
                                        
                                        $totalTargetEntitas += $data['total_target'];
                                        $totalRealisasiEntitas += $data['total_realisasi'];
                                        $totalGapEntitas += $gapAkun;
                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($akunNama); ?></strong></td>
                                            <td class="text-end"><?php echo formatFullNumber($data['total_target']); ?></td>
                                            <td class="text-end">
                                                <strong><?php echo $percentageTargetFormatted; ?></strong>
                                            </td>
                                            <td class="text-end"><?php echo formatFullNumber($data['total_realisasi']); ?></td>
                                            <td class="text-end">
                                                <strong><?php echo $percentageRealisasiFormatted; ?></strong>
                                            </td>
                                            <td class="text-end <?php echo $gapAkun >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatFullNumber($gapAkun); ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($percentageAkun, 1); ?>%</td>
                                            <td class="text-center <?php echo $statusClass; ?>">
                                                <strong><?php echo $statusText; ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalTargetEntitas); ?></strong></td>
                                        <td class="text-end"><strong>
                                            <?php 
                                            $percentageTotalTarget = $targetOmzetBersihEntitas > 0 ? ($totalTargetEntitas / $targetOmzetBersihEntitas) * 100 : 0;
                                            echo number_format($percentageTotalTarget, 2) . '%';
                                            ?>
                                        </strong></td>
                                        <td class="text-end"><strong><?php echo formatFullNumber($totalRealisasiEntitas); ?></strong></td>
                                        <td class="text-end"><strong>
                                            <?php 
                                            $percentageTotalRealisasi = $realisasiOmzetBersihEntitas > 0 ? ($totalRealisasiEntitas / $realisasiOmzetBersihEntitas) * 100 : 0;
                                            echo number_format($percentageTotalRealisasi, 2) . '%';
                                            ?>
                                        </strong></td>
                                        <td class="text-end <?php echo $totalGapEntitas >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo formatFullNumber($totalGapEntitas); ?></strong>
                                        </td>
                                        <td class="text-end"><strong>
                                            <?php 
                                            $percentageTotalPencapaian = $totalTargetEntitas > 0 ? ($totalRealisasiEntitas / $totalTargetEntitas) * 100 : 0;
                                            echo number_format($percentageTotalPencapaian, 1) . '%';
                                            ?>
                                        </strong></td>
                                        <td class="text-center <?php echo $percentageTotalPencapaian >= 100 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo $percentageTotalPencapaian >= 100 ? 'ACHIEVE' : 'TIDAK ACHIEVE'; ?></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- TEMPATKAN SETELAH TABEL GOAL VS REALITY -->
                        <?php if (!empty($dataHasilAnalisis)): ?>
                            <?php foreach ($dataHasilAnalisis as $row): ?>
                                <?php if(strtolower($row['title']) == strtolower('Goal vs Reality Laporan Keuangan Per Entitas')): ?>
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Hasil Analisis: <?php echo htmlspecialchars($row['title']); ?></h5>
                                        </div>
                                        <div class="card-body p-2">
                                        <?php 
                                            $cleanedContent = nl2br($row['analisis']);
                                            $cleanedContent = str_replace('\/', '/', $cleanedContent);
                                            $cleanedContent = str_replace('\n', '', $cleanedContent);
                                            $cleanedContent = str_replace('"', '', $cleanedContent);
                                            echo $cleanedContent; 
                                            ?>
                                        </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== DATA TUGAS, KOMPLAIN, DAN ISSUE ==================== -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card tabel-lebar-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Tugas, Komplain, dan Issue</h5>
                    </div>
                    <div class="card-body p-2">
                        <ul class="nav nav-tabs" id="tugasTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tugas-tab" data-bs-toggle="tab" data-bs-target="#tugas-content" type="button" role="tab">
                                    Tugas
                                    <?php
                                    // Query untuk menghitung total tugas
                                    $sqlCountTugas = "SELECT COUNT(*) as total FROM tugas WHERE 1=1";
                                    $paramsCountTugas = [];
                                    
                                    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                        $sqlCountTugas .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                        $paramsCountTugas[] = $tanggal_awal;
                                        $paramsCountTugas[] = $tanggal_akhir;
                                    } elseif (!empty($tanggal_awal)) {
                                        $sqlCountTugas .= " AND DATE(tanggal) >= ?";
                                        $paramsCountTugas[] = $tanggal_awal;
                                    } elseif (!empty($tanggal_akhir)) {
                                        $sqlCountTugas .= " AND DATE(tanggal) <= ?";
                                        $paramsCountTugas[] = $tanggal_akhir;
                                    }
                                    
                                    try {
                                        $stmtCountTugas = $pdo->prepare($sqlCountTugas);
                                        $stmtCountTugas->execute($paramsCountTugas);
                                        $countTugas = $stmtCountTugas->fetch(PDO::FETCH_ASSOC);
                                        $totalTugas = $countTugas ? $countTugas['total'] : 0;
                                    } catch (PDOException $e) {
                                        $totalTugas = 0;
                                    }
                                    ?>
                                    <span class="badge bg-primary"><?php echo $totalTugas; ?></span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="komplain-tab" data-bs-toggle="tab" data-bs-target="#komplain-content" type="button" role="tab">
                                    Komplain
                                    <?php
                                    // Query untuk menghitung total komplain
                                    $sqlCountKomplain = "SELECT COUNT(*) as total FROM komplain WHERE 1=1";
                                    $paramsCountKomplain = [];
                                    
                                    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                        $sqlCountKomplain .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                        $paramsCountKomplain[] = $tanggal_awal;
                                        $paramsCountKomplain[] = $tanggal_akhir;
                                    } elseif (!empty($tanggal_awal)) {
                                        $sqlCountKomplain .= " AND DATE(tanggal) >= ?";
                                        $paramsCountKomplain[] = $tanggal_awal;
                                    } elseif (!empty($tanggal_akhir)) {
                                        $sqlCountKomplain .= " AND DATE(tanggal) <= ?";
                                        $paramsCountKomplain[] = $tanggal_akhir;
                                    }
                                    
                                    try {
                                        $stmtCountKomplain = $pdo->prepare($sqlCountKomplain);
                                        $stmtCountKomplain->execute($paramsCountKomplain);
                                        $countKomplain = $stmtCountKomplain->fetch(PDO::FETCH_ASSOC);
                                        $totalKomplain = $countKomplain ? $countKomplain['total'] : 0;
                                    } catch (PDOException $e) {
                                        $totalKomplain = 0;
                                    }
                                    ?>
                                    <span class="badge bg-warning"><?php echo $totalKomplain; ?></span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="issue-tab" data-bs-toggle="tab" data-bs-target="#issue-content" type="button" role="tab">
                                    Issue
                                    <?php
                                    // Query untuk menghitung total issue
                                    $sqlCountIssue = "SELECT COUNT(*) as total FROM issue WHERE 1=1";
                                    $paramsCountIssue = [];
                                    
                                    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                        $sqlCountIssue .= " AND DATE(tanggal) BETWEEN ? AND ?";
                                        $paramsCountIssue[] = $tanggal_awal;
                                        $paramsCountIssue[] = $tanggal_akhir;
                                    } elseif (!empty($tanggal_awal)) {
                                        $sqlCountIssue .= " AND DATE(tanggal) >= ?";
                                        $paramsCountIssue[] = $tanggal_awal;
                                    } elseif (!empty($tanggal_akhir)) {
                                        $sqlCountIssue .= " AND DATE(tanggal) <= ?";
                                        $paramsCountIssue[] = $tanggal_akhir;
                                    }
                                    
                                    try {
                                        $stmtCountIssue = $pdo->prepare($sqlCountIssue);
                                        $stmtCountIssue->execute($paramsCountIssue);
                                        $countIssue = $stmtCountIssue->fetch(PDO::FETCH_ASSOC);
                                        $totalIssue = $countIssue ? $countIssue['total'] : 0;
                                    } catch (PDOException $e) {
                                        $totalIssue = 0;
                                    }
                                    ?>
                                    <span class="badge bg-danger"><?php echo $totalIssue; ?></span>
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="tugasTabContent">
                            <!-- Tab Tugas -->
                            <div class="tab-pane fade show active" id="tugas-content" role="tabpanel">
                                <?php
                                // Query untuk mengambil data tugas
                                $sqlTugas = "SELECT 
                                                t.kd_tugas,
                                                t.tanggal,
                                                t.uraian,
                                                t.bulan,
                                                t.tahun,
                                                t.status,
                                                u.name as nama_user
                                            FROM tugas t
                                            LEFT JOIN users u ON t.kd_user = u.id
                                            WHERE 1=1";
                                
                                $paramsTugas = [];
                                
                                if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                    $sqlTugas .= " AND DATE(t.tanggal) BETWEEN ? AND ?";
                                    $paramsTugas[] = $tanggal_awal;
                                    $paramsTugas[] = $tanggal_akhir;
                                } elseif (!empty($tanggal_awal)) {
                                    $sqlTugas .= " AND DATE(t.tanggal) >= ?";
                                    $paramsTugas[] = $tanggal_awal;
                                } elseif (!empty($tanggal_akhir)) {
                                    $sqlTugas .= " AND DATE(t.tanggal) <= ?";
                                    $paramsTugas[] = $tanggal_akhir;
                                }
                                
                                $sqlTugas .= " ORDER BY t.tanggal DESC, t.kd_tugas DESC";
                                
                                try {
                                    $stmtTugas = $pdo->prepare($sqlTugas);
                                    $stmtTugas->execute($paramsTugas);
                                    $dataTugas = $stmtTugas->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $dataTugas = [];
                                }
                                ?>
                                
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>User</th>
                                                <th>Uraian Tugas</th>
                                                <th>Bulan</th>
                                                <th>Tahun</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($dataTugas)): ?>
                                                <?php $noTugas = 1; ?>
                                                <?php foreach ($dataTugas as $tugas): ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $noTugas++; ?></td>
                                                        <td><?php echo date('d M Y', strtotime($tugas['tanggal'])); ?></td>
                                                        <td><?php echo htmlspecialchars($tugas['nama_user'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($tugas['uraian']); ?></td>
                                                        <td><?php echo htmlspecialchars($tugas['bulan']); ?></td>
                                                        <td><?php echo htmlspecialchars($tugas['tahun']); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusTugas = $tugas['status'];
                                                            $badgeClass = '';
                                                            $statusText = '';
                                                            
                                                            if ($statusTugas == 0) {
                                                                $badgeClass = 'badge-secondary';
                                                                $statusText = 'Menunggu';
                                                            } elseif ($statusTugas == 1) {
                                                                $badgeClass = 'badge-primary';
                                                                $statusText = 'Dikerjakan';
                                                            } elseif ($statusTugas == 2) {
                                                                $badgeClass = 'badge-warning';
                                                                $statusText = 'Review';
                                                            } elseif ($statusTugas == 3) {
                                                                $badgeClass = 'badge-success';
                                                                $statusText = 'Selesai';
                                                            } else {
                                                                $badgeClass = 'badge-danger';
                                                                $statusText = 'Ditolak';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Tidak ada data tugas untuk periode ini</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Tab Komplain -->
                            <div class="tab-pane fade" id="komplain-content" role="tabpanel">
                                <?php
                                // Query untuk mengambil data komplain
                                $sqlKomplain = "SELECT 
                                                    k.kd_komplain,
                                                    k.tanggal,
                                                    k.uraian,
                                                    k.kategori,
                                                    k.prioritas,
                                                    k.bulan,
                                                    k.tahun,
                                                    k.status,
                                                    k.sumber,
                                                    u.name as nama_user
                                                FROM komplain k
                                                LEFT JOIN users u ON k.kd_user = u.id
                                                WHERE 1=1";
                                
                                $paramsKomplain = [];
                                
                                if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                    $sqlKomplain .= " AND DATE(k.tanggal) BETWEEN ? AND ?";
                                    $paramsKomplain[] = $tanggal_awal;
                                    $paramsKomplain[] = $tanggal_akhir;
                                } elseif (!empty($tanggal_awal)) {
                                    $sqlKomplain .= " AND DATE(k.tanggal) >= ?";
                                    $paramsKomplain[] = $tanggal_awal;
                                } elseif (!empty($tanggal_akhir)) {
                                    $sqlKomplain .= " AND DATE(k.tanggal) <= ?";
                                    $paramsKomplain[] = $tanggal_akhir;
                                }
                                
                                $sqlKomplain .= " ORDER BY k.tanggal DESC, k.kd_komplain DESC";
                                
                                try {
                                    $stmtKomplain = $pdo->prepare($sqlKomplain);
                                    $stmtKomplain->execute($paramsKomplain);
                                    $dataKomplain = $stmtKomplain->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $dataKomplain = [];
                                }
                                ?>
                                
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>User</th>
                                                <th>Uraian Komplain</th>
                                                <th>Kategori</th>
                                                <th>Prioritas</th>
                                                <th>Bulan</th>
                                                <th>Tahun</th>
                                                <th>Sumber</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($dataKomplain)): ?>
                                                <?php $noKomplain = 1; ?>
                                                <?php foreach ($dataKomplain as $komplain): ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $noKomplain++; ?></td>
                                                        <td><?php echo date('d M Y', strtotime($komplain['tanggal'])); ?></td>
                                                        <td><?php echo htmlspecialchars($komplain['nama_user'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($komplain['uraian']); ?></td>
                                                        <td><?php echo htmlspecialchars($komplain['kategori']); ?></td>
                                                        <td>
                                                            <?php
                                                            $prioritas = $komplain['prioritas'];
                                                            $badgePrioritas = '';
                                                            
                                                            if ($prioritas == 'Rendah') {
                                                                $badgePrioritas = 'badge-success';
                                                            } elseif ($prioritas == 'Sedang') {
                                                                $badgePrioritas = 'badge-warning';
                                                            } else {
                                                                $badgePrioritas = 'badge-danger';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badgePrioritas; ?>"><?php echo $prioritas; ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($komplain['bulan']); ?></td>
                                                        <td><?php echo htmlspecialchars($komplain['tahun']); ?></td>
                                                        <td><?php echo htmlspecialchars($komplain['sumber']); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusKomplain = $komplain['status'];
                                                            $badgeClassKomplain = '';
                                                            $statusTextKomplain = '';
                                                            
                                                            if ($statusKomplain == 0) {
                                                                $badgeClassKomplain = 'badge-secondary';
                                                                $statusTextKomplain = 'Baru';
                                                            } elseif ($statusKomplain == 1) {
                                                                $badgeClassKomplain = 'badge-primary';
                                                                $statusTextKomplain = 'Diproses';
                                                            } elseif ($statusKomplain == 2) {
                                                                $badgeClassKomplain = 'badge-success';
                                                                $statusTextKomplain = 'Selesai';
                                                            } else {
                                                                $badgeClassKomplain = 'badge-danger';
                                                                $statusTextKomplain = 'Ditolak';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badgeClassKomplain; ?>"><?php echo $statusTextKomplain; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">Tidak ada data komplain untuk periode ini</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Tab Issue -->
                            <div class="tab-pane fade" id="issue-content" role="tabpanel">
                                <?php
                                // Query untuk mengambil data issue
                                $sqlIssue = "SELECT 
                                                i.kd_issue,
                                                i.tanggal,
                                                i.uraian,
                                                i.bulan,
                                                i.tahun,
                                                i.status,
                                                u.name as nama_user
                                            FROM issue i
                                            LEFT JOIN users u ON i.kd_user = u.id
                                            WHERE 1=1";
                                
                                $paramsIssue = [];
                                
                                if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
                                    $sqlIssue .= " AND DATE(i.tanggal) BETWEEN ? AND ?";
                                    $paramsIssue[] = $tanggal_awal;
                                    $paramsIssue[] = $tanggal_akhir;
                                } elseif (!empty($tanggal_awal)) {
                                    $sqlIssue .= " AND DATE(i.tanggal) >= ?";
                                    $paramsIssue[] = $tanggal_awal;
                                } elseif (!empty($tanggal_akhir)) {
                                    $sqlIssue .= " AND DATE(i.tanggal) <= ?";
                                    $paramsIssue[] = $tanggal_akhir;
                                }
                                
                                $sqlIssue .= " ORDER BY i.tanggal DESC, i.kd_issue DESC";
                                
                                try {
                                    $stmtIssue = $pdo->prepare($sqlIssue);
                                    $stmtIssue->execute($paramsIssue);
                                    $dataIssue = $stmtIssue->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $dataIssue = [];
                                }
                                ?>
                                
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>User</th>
                                                <th>Uraian Issue</th>
                                                <th>Bulan</th>
                                                <th>Tahun</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($dataIssue)): ?>
                                                <?php $noIssue = 1; ?>
                                                <?php foreach ($dataIssue as $issue): ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $noIssue++; ?></td>
                                                        <td><?php echo date('d M Y', strtotime($issue['tanggal'])); ?></td>
                                                        <td><?php echo htmlspecialchars($issue['nama_user'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($issue['uraian']); ?></td>
                                                        <td><?php echo htmlspecialchars($issue['bulan']); ?></td>
                                                        <td><?php echo htmlspecialchars($issue['tahun']); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusIssue = $issue['status'];
                                                            $badgeClassIssue = '';
                                                            $statusTextIssue = '';
                                                            
                                                            if ($statusIssue == 0) {
                                                                $badgeClassIssue = 'badge-secondary';
                                                                $statusTextIssue = 'Open';
                                                            } elseif ($statusIssue == 1) {
                                                                $badgeClassIssue = 'badge-primary';
                                                                $statusTextIssue = 'In Progress';
                                                            } elseif ($statusIssue == 2) {
                                                                $badgeClassIssue = 'badge-success';
                                                                $statusTextIssue = 'Resolved';
                                                            } elseif ($statusIssue == 3) {
                                                                $badgeClassIssue = 'badge-danger';
                                                                $statusTextIssue = 'Closed';
                                                            } else {
                                                                $badgeClassIssue = 'badge-dark';
                                                                $statusTextIssue = 'Unknown';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badgeClassIssue; ?>"><?php echo $statusTextIssue; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Tidak ada data issue untuk periode ini</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <p class="text-muted">Laporan ini dihasilkan secara otomatis pada <?php echo date('d F Y H:i:s'); ?></p>
            </div>
        </div>
    </div>
</div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ==================== GRAFIK BIAYA ZISWAF ====================

        // ==================== GRAFIK 28: TREN ZISWAF PER BULAN ====================

        // Grafik Tren ZISWAF per Bulan
        const ctxTrenZISWAFBulanan = document.getElementById('chartTrenZISWAFBulanan');
        if (ctxTrenZISWAFBulanan) {
            const bulanLabelsZISWAF = [<?php 
                $labels = [];
                foreach ($ziswafPerBulan as $bulan => $data) {
                    $labels[] = date('M Y', strtotime($bulan . '-01'));
                }
                echo '"' . implode('","', $labels) . '"';
            ?>];
            
            const targetZISWAF = [<?php echo implode(',', array_column($ziswafPerBulan, 'target')); ?>];
            const realisasiZISWAF = [<?php echo implode(',', array_column($ziswafPerBulan, 'realisasi')); ?>];
            const gapZISWAF = [<?php echo implode(',', array_column($ziswafPerBulan, 'gap')); ?>];
            
            new Chart(ctxTrenZISWAFBulanan, {
                type: 'bar',
                data: {
                    labels: bulanLabelsZISWAF,
                    datasets: [
                        {
                            label: 'Target ZISWAF',
                            data: targetZISWAF,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Realisasi ZISWAF',
                            data: realisasiZISWAF,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    
                                    // Tambahkan info gap untuk realisasi
                                    if (context.datasetIndex === 1) { // Realisasi dataset
                                        const index = context.dataIndex;
                                        const target = targetZISWAF[index];
                                        const realisasi = realisasiZISWAF[index];
                                        const gap = realisasi - target;
                                        const percentage = target > 0 ? (realisasi / target) * 100 : 0;
                                        
                                        return [
                                            label,
                                            'Gap: ' + (gap >= 0 ? '+' : '') + 'Rp ' + Math.abs(gap).toLocaleString('id-ID'),
                                            'Pencapaian: ' + percentage.toFixed(1) + '%'
                                        ];
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        const ctxZISWAFPerEntitasTargetRealisasi = document.getElementById('chartZISWAFPerEntitasTargetRealisasi');
        
        if (ctxZISWAFPerEntitasTargetRealisasi) {
            // Siapkan data untuk chart (maksimal 10 entitas teratas berdasarkan realisasi)
            <?php
            // Urutkan berdasarkan realisasi tertinggi
            uasort($biayaZISWAFPerEntitas, function($a, $b) {
                return $b['realisasi'] <=> $a['realisasi'];
            });
            $topEntitas = array_slice($biayaZISWAFPerEntitas, 0, 10, true);

            $entitasLabels = [];
            $targetData = [];
            $realisasiData = [];
            $gapData = [];

            foreach ($topEntitas as $entitas => $data) {
                $entitasLabels[] = $entitas;
                $targetData[] = $data['target'];
                $realisasiData[] = $data['realisasi'];
                $gapData[] = $data['gap'];
            }
            ?>

            new Chart(ctxZISWAFPerEntitasTargetRealisasi, {
                type: 'bar',
                data: {
                    labels: [<?php echo '"' . implode('","', $entitasLabels) . '"'; ?>],
                    datasets: [
                        {
                            label: 'Target ZISWAF',
                            data: [<?php echo implode(',', $targetData); ?>],
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            order: 2
                        },
                        {
                            label: 'Realisasi ZISWAF',
                            data: [<?php echo implode(',', $realisasiData); ?>],
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                },
                                font: {
                                    size: 11
                                }
                            },
                            title: {
                                display: true,
                                text: 'Nilai (Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Grafik Proporsi Biaya ZISWAF per Entitas
        const ctxProporsiZISWAFPerEntitas = document.getElementById('chartProporsiZISWAFPerEntitas');
        
        if (ctxProporsiZISWAFPerEntitas) {
            <?php
            // Siapkan data untuk pie chart (maksimal 8 entitas teratas, sisanya digabung sebagai "Lainnya")
            $pieEntitasLabels = [];
            $pieEntitasData = [];
            $pieEntitasColors = [];

            // Warna untuk chart
            $colorPalette = [
                'rgba(255, 99, 132, 0.7)',   // Merah
                'rgba(54, 162, 235, 0.7)',   // Biru
                'rgba(255, 206, 86, 0.7)',   // Kuning
                'rgba(75, 192, 192, 0.7)',   // Hijau
                'rgba(153, 102, 255, 0.7)',  // Ungu
                'rgba(255, 159, 64, 0.7)',   // Oranye
                'rgba(201, 203, 207, 0.7)',  // Abu-abu
                'rgba(255, 99, 71, 0.7)',    // Tomat
                'rgba(60, 179, 113, 0.7)',   // Hijau medium
                'rgba(106, 90, 205, 0.7)'    // Biru medium
            ];

            $counter = 0;
            $totalLainnya = 0;

            foreach ($biayaZISWAFPerEntitas as $entitas => $data) {
                if ($counter < 8 && $data['realisasi'] > 0) {
                    $pieEntitasLabels[] = $entitas;
                    $pieEntitasData[] = $data['realisasi'];
                    $pieEntitasColors[] = $colorPalette[$counter % count($colorPalette)];
                    $counter++;
                } else {
                    $totalLainnya += $data['realisasi'];
                }
            }

            // Tambahkan "Lainnya" jika ada
            if ($totalLainnya > 0) {
                $pieEntitasLabels[] = 'Lainnya';
                $pieEntitasData[] = $totalLainnya;
                $pieEntitasColors[] = 'rgba(169, 169, 169, 0.7)'; // Abu-abu gelap
            }
            ?>

            new Chart(ctxProporsiZISWAFPerEntitas, {
                type: 'pie',
                data: {
                    labels: [<?php echo '"' . implode('","', $pieEntitasLabels) . '"'; ?>],
                    datasets: [{
                        data: [<?php echo implode(',', $pieEntitasData); ?>],
                        backgroundColor: [<?php echo '"' . implode('","', $pieEntitasColors) . '"'; ?>],
                        borderColor: 'rgba(255, 255, 255, 0.8)',
                        borderWidth: 2,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 11
                                },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = <?php echo $totalRealisasiBiayaZISWAF; ?>;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                    
                                    return [
                                        label,
                                        'Nilai: Rp ' + value.toLocaleString('id-ID'),
                                        'Proporsi: ' + percentage + '%'
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Grafik Tren Biaya GA per Bulan
        <?php
        // Siapkan data untuk chart
        $bulanLabelsGA = [];
        $targetDataGA = [];
        $realisasiDataGA = [];

        // Urutkan berdasarkan bulan
        ksort($biayaGAPerBulan);

        foreach ($biayaGAPerBulan as $bulanKey => $data) {
            $bulanAngka = (int)substr($bulanKey, 5, 2);
            $tahun = substr($bulanKey, 0, 4);
            $bulanLabelsGA[] = date('M Y', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
            
            $targetDataGA[] = isset($data['target']) ? $data['target'] : 0;
            $realisasiDataGA[] = isset($data['realisasi']) ? $data['realisasi'] : 0;
        }
        ?>

        var ctxBiayaGABulanan = document.getElementById('chartTrenBiayaGABulanan').getContext('2d');
        var chartTrenBiayaGABulanan = new Chart(ctxBiayaGABulanan, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($bulanLabelsGA); ?>,
                datasets: [
                    {
                        label: 'Target Biaya GA',
                        data: <?php echo json_encode($targetDataGA); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Realisasi Biaya GA',
                        data: <?php echo json_encode($realisasiDataGA); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Gap (Realisasi - Target)',
                        data: <?php
                            $gapDataGA = [];
                            for ($i = 0; $i < count($targetDataGA); $i++) {
                                $gapDataGA[] = $realisasiDataGA[$i] - $targetDataGA[$i];
                            }
                            echo json_encode($gapDataGA);
                        ?>,
                        type: 'line',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Tren Biaya GA (General Affairs) per Bulan',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Bulan'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Target & Realisasi (Rp)'
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Gap (Rp)'
                        },
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // ==================== GRAFIK BIAYA GA ====================

        // Grafik Biaya GA per Entitas (Target vs Realisasi)
        if (document.getElementById('chartBiayaGAEntitas')) {
            const ctx = document.getElementById('chartBiayaGAEntitas').getContext('2d');
            
            // Siapkan data dari PHP
            const labels = <?php 
                $labels = array_keys($biayaGAPerEntitas);
                // Urutkan berdasarkan realisasi tertinggi
                uasort($biayaGAPerEntitas, function($a, $b) {
                    return $b['realisasi'] <=> $a['realisasi'];
                });
                echo json_encode(array_keys($biayaGAPerEntitas)); 
            ?>;
            const targetData = <?php 
                $targets = [];
                foreach ($biayaGAPerEntitas as $entitas => $data) {
                    $targets[] = $data['target'];
                }
                echo json_encode($targets);
            ?>;
            const realisasiData = <?php 
                $realisasis = [];
                foreach ($biayaGAPerEntitas as $entitas => $data) {
                    $realisasis[] = $data['realisasi'];
                }
                echo json_encode($realisasis);
            ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Target',
                            data: targetData,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Realisasi',
                            data: realisasiData,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + ' Jt';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value / 1000).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' K';
                                    }
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        // Grafik Proporsi Biaya GA per Entitas (Pie Chart)
        if (document.getElementById('chartProporsiBiayaGA')) {
            const ctx = document.getElementById('chartProporsiBiayaGA').getContext('2d');
            
            // Siapkan data dari PHP
            const labels = <?php 
                $labels = array_keys($biayaGAPerEntitas);
                echo json_encode($labels);
            ?>;
            const data = <?php 
                $realisasis = [];
                foreach ($biayaGAPerEntitas as $entitas => $data) {
                    $realisasis[] = $data['realisasi'];
                }
                echo json_encode($realisasis);
            ?>;
            
            // Warna untuk pie chart
            const colors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#6f42c1', '#fd7e14', '#20c9a6', '#5a5c69'
            ];
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 11
                                },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        //  // Tambahkan tombol print jika belum ada
        // if (!document.querySelector('.custom-print-btn')) {
        //     const printBtn = document.createElement('button');
        //     printBtn.className = 'custom-print-btn btn btn-success no-print';
        //     printBtn.style.position = 'fixed';
        //     printBtn.style.top = '20px';
        //     printBtn.style.right = '20px';
        //     printBtn.style.zIndex = '9999';
        //     printBtn.innerHTML = '🖨️ Cetak Laporan';
        //     printBtn.onclick = printWithOptimization;
        //     document.body.appendChild(printBtn);
        // }
        // Helper function untuk format number
        function formatFullNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        function formatCurrency(num) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
        }

        
        ////////////////////////////////////////////= GRAFIK NPM ==================== -->
        <?php
        // Siapkan data untuk chart NPM dengan nama variabel berbeda
        $npmChartEntitasLabels = array_keys($npmPerEntitas);
        $npmChartTargetValues = array_column($npmPerEntitas, 'target');
        $npmChartRealisasiValues = array_column($npmPerEntitas, 'realisasi');
        $npmChartProporsiValues = array_column($npmPerEntitas, 'realisasi');

        // Siapkan data untuk chart NPM per bulan
        $npmChartBulanLabels = [];
        $npmChartBulanTarget = [];
        $npmChartBulanRealisasi = [];
        $npmChartBulanPersentase = [];

        foreach ($npmPerBulan as $bulanKey => $data) {
            $tahun = substr($bulanKey, 0, 4);
            $bulanAngka = (int)substr($bulanKey, 5, 2);
            $namaBulan = date('M', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
            
            $npmChartBulanLabels[] = $namaBulan . ' ' . $tahun;
            $npmChartBulanTarget[] = $data['target'];
            $npmChartBulanRealisasi[] = $data['realisasi'];
            $npmChartBulanPersentase[] = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
        }

        // Tambahkan data omzet bulanan untuk perbandingan
        $omzetChartBulananNPM = [];
        foreach ($npmChartBulanLabels as $key => $label) {
            $bulanKey = array_keys($npmPerBulan)[$key];
            $omzetChartBulananNPM[$key] = isset($bulanDataOmzet[$bulanKey]) ? $bulanDataOmzet[$bulanKey]['realisasi'] : 0;
        }

        // Hitung persentase NPM terhadap omzet per bulan
        $npmChartPersenOmzet = [];
        foreach ($npmChartBulanRealisasi as $key => $npm) {
            $omzet = $omzetChartBulananNPM[$key] ?? 0;
            $npmChartPersenOmzet[$key] = $omzet > 0 ? ($npm / $omzet) * 100 : 0;
        }

        // Batasi jumlah entitas yang ditampilkan
        $maxNPMChartDisplay = 8;
        if (count($npmChartEntitasLabels) > $maxNPMChartDisplay) {
            // Ambil top N berdasarkan realisasi
            $topIndices = array_keys($npmChartRealisasiValues);
            array_splice($topIndices, $maxNPMChartDisplay);
            
            $npmChartEntitasLabels = array_slice($npmChartEntitasLabels, 0, $maxNPMChartDisplay);
            $npmChartTargetValues = array_slice($npmChartTargetValues, 0, $maxNPMChartDisplay);
            $npmChartRealisasiValues = array_slice($npmChartRealisasiValues, 0, $maxNPMChartDisplay);
            $npmChartProporsiValues = array_slice($npmChartProporsiValues, 0, $maxNPMChartDisplay);
            
            // Hitung nilai "Lainnya"
            $targetLainnyaNPM = array_sum(array_slice($npmChartTargetValues, $maxNPMChartDisplay));
            $realisasiLainnyaNPM = array_sum(array_slice($npmChartRealisasiValues, $maxNPMChartDisplay));
            
            if ($realisasiLainnyaNPM > 0) {
                $npmChartEntitasLabels[] = 'Lainnya';
                $npmChartTargetValues[] = $targetLainnyaNPM;
                $npmChartRealisasiValues[] = $realisasiLainnyaNPM;
                $npmChartProporsiValues[] = $realisasiLainnyaNPM;
            }
        }
        ?>

        // Data dari PHP untuk chart NPM - variabel dengan prefix 'npmChart'
        const npmChartEntitasLabels = <?php echo json_encode($npmChartEntitasLabels); ?>;
        const npmChartTargetValues = <?php echo json_encode($npmChartTargetValues); ?>;
        const npmChartRealisasiValues = <?php echo json_encode($npmChartRealisasiValues); ?>;
        const npmChartProporsiValues = <?php echo json_encode($npmChartProporsiValues); ?>;
        const npmChartBulanLabels = <?php echo json_encode($npmChartBulanLabels); ?>;
        const npmChartBulanTarget = <?php echo json_encode($npmChartBulanTarget); ?>;
        const npmChartBulanRealisasi = <?php echo json_encode($npmChartBulanRealisasi); ?>;
        const npmChartBulanPersentase = <?php echo json_encode($npmChartBulanPersentase); ?>;
        const omzetChartBulananNPM = <?php echo json_encode(array_values($omzetChartBulananNPM)); ?>;
        const npmChartPersenOmzet = <?php echo json_encode($npmChartPersenOmzet); ?>;

        // Convert to millions for chart
        const npmChartTargetValuesJuta = npmChartTargetValues.map(val => val / 1000000);
        const npmChartRealisasiValuesJuta = npmChartRealisasiValues.map(val => val / 1000000);
        const npmChartProporsiValuesJuta = npmChartProporsiValues.map(val => val / 1000000);
        const npmChartBulanTargetJuta = npmChartBulanTarget.map(val => val / 1000000);
        const npmChartBulanRealisasiJuta = npmChartBulanRealisasi.map(val => val / 1000000);
        const omzetChartBulananNPMJuta = omzetChartBulananNPM.map(val => val / 1000000);

        // Warna untuk chart NPM
        const backgroundColorsNPMChart = [
            '#10B981', '#059669', '#047857', '#065F46', // Green tones
            '#0EA5E9', '#0284C7', '#0369A1', '#075985', // Blue tones
            '#8B5CF6', '#7C3AED', '#6D28D9', '#5B21B6', // Purple tones
            '#F59E0B', '#D97706', '#B45309', '#92400E'  // Yellow/Orange tones
        ];

        // 29. Grafik Batang NPM per Entitas
        const chartNPMEntitas = document.getElementById('chartNPMEntitas');
        if (chartNPMEntitas && npmChartEntitasLabels.length > 0) {
            new Chart(chartNPMEntitas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: npmChartEntitasLabels,
                    datasets: [
                        {
                            label: 'Target NPM (Juta)',
                            data: npmChartTargetValuesJuta,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)', // Green 500
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi NPM (Juta)',
                            data: npmChartRealisasiValuesJuta,
                            backgroundColor: 'rgba(5, 150, 105, 0.7)', // Green 600
                            borderColor: 'rgba(5, 150, 105, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai NPM (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1) + ' Jt';
                                },
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                drawBorder: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Entitas',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                    
                                    const datasetIndex = context.datasetIndex;
                                    const dataIndex = context.dataIndex;
                                    if (datasetIndex === 0) { // Target
                                        const nilaiAsli = npmChartTargetValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    } else if (datasetIndex === 1) { // Realisasi
                                        const nilaiAsli = npmChartRealisasiValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    }
                                    
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const target = npmChartTargetValues[dataIndex];
                                    const realisasi = npmChartRealisasiValues[dataIndex];
                                    const gap = realisasi - target;
                                    const percentage = target > 0 ? (realisasi / target) * 100 : 0;
                                    
                                    // Hitung profitabilitas
                                    const entitas = npmChartEntitasLabels[dataIndex];
                                    const omzetEntitasData = <?php echo json_encode($entitasDataOmzet); ?>;
                                    const omzetEntitas = omzetEntitasData[entitas] ? omzetEntitasData[entitas]['total_realisasi'] : 0;
                                    const profitabilitas = omzetEntitas > 0 ? (realisasi / omzetEntitas) * 100 : 0;
                                    
                                    if (target > 0) {
                                        const gapText = gap >= 0 ? 
                                            'PROFIT LEBIH: Rp ' + formatFullNumber(gap) : 
                                            'PROFIT KURANG: Rp ' + formatFullNumber(Math.abs(gap));
                                        
                                        const info = [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText,
                                            'Pencapaian: ' + percentage.toFixed(1) + '%'
                                        ];
                                        
                                        if (omzetEntitas > 0) {
                                            info.push('Profitabilitas: ' + profitabilitas.toFixed(1) + '%');
                                            info.push('Omzet Entitas: Rp ' + formatFullNumber(omzetEntitas));
                                        }
                                        
                                        return info;
                                    }
                                    return null;
                                },
                                title: function(tooltipItems) {
                                    return 'Entitas: ' + npmChartEntitasLabels[tooltipItems[0].dataIndex];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 5
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        } else if (chartNPMEntitas) {
            chartNPMEntitas.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data NPM untuk ditampilkan</div>';
        }

        // 30. Grafik Pie untuk Proporsi NPM per Entitas
        const chartProporsiNPMEntitas = document.getElementById('chartProporsiNPMEntitas');
        if (chartProporsiNPMEntitas && npmChartEntitasLabels.length > 0) {
            new Chart(chartProporsiNPMEntitas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: npmChartEntitasLabels,
                    datasets: [{
                        data: npmChartProporsiValuesJuta,
                        backgroundColor: backgroundColorsNPMChart.slice(0, npmChartEntitasLabels.length),
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 20,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    const nilaiRupiah = (value * 1000000).toLocaleString('id-ID');
                                    
                                    const entitasIndex = context.dataIndex;
                                    const target = npmChartTargetValues[entitasIndex] || 0;
                                    const realisasi = npmChartProporsiValues[entitasIndex] || 0;
                                    const gap = realisasi - target;
                                    
                                    // Hitung profitabilitas
                                    const entitas = npmChartEntitasLabels[entitasIndex];
                                    const omzetEntitasData = <?php echo json_encode($entitasDataOmzet); ?>;
                                    const omzetEntitas = omzetEntitasData[entitas] ? omzetEntitasData[entitas]['total_realisasi'] : 0;
                                    const profitabilitas = omzetEntitas > 0 ? (realisasi / omzetEntitas) * 100 : 0;
                                    
                                    const info = [
                                        `${label}`,
                                        `Nilai: Rp ${nilaiRupiah}`,
                                        `Persentase: ${percentage}%`,
                                        `Target: Rp ${formatFullNumber(target)}`,
                                        `Realisasi: Rp ${formatFullNumber(realisasi)}`,
                                        `Gap: Rp ${formatFullNumber(gap)}`,
                                        `Pencapaian: ${target > 0 ? ((realisasi / target) * 100).toFixed(1) : '0.0'}%`
                                    ];
                                    
                                    if (omzetEntitas > 0) {
                                        info.push(`Profitabilitas: ${profitabilitas.toFixed(1)}%`);
                                    }
                                    
                                    return info;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Proporsi Net Profit Margin per Entitas',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    }
                }
            });
        } else if (chartProporsiNPMEntitas) {
            chartProporsiNPMEntitas.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data proporsi NPM untuk ditampilkan</div>';
        }

        // 31. Grafik Tren NPM per Bulan
        const chartTrenNPMBulanan = document.getElementById('chartTrenNPMBulanan');
        if (chartTrenNPMBulanan && npmChartBulanLabels.length > 0) {
            new Chart(chartTrenNPMBulanan.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: npmChartBulanLabels,
                    datasets: [
                        {
                            label: 'Target NPM (Juta)',
                            data: npmChartBulanTargetJuta,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)', // Green 500
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi NPM (Juta)',
                            data: npmChartBulanRealisasiJuta,
                            backgroundColor: 'rgba(5, 150, 105, 0.7)', // Green 600
                            borderColor: 'rgba(5, 150, 105, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '% Pencapaian',
                            data: npmChartBulanPersentase,
                            type: 'line',
                            borderColor: 'rgba(239, 68, 68, 1)', // Red 500
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            order: 1,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai NPM (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + ' Jt';
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '% Pencapaian',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + '%';
                                },
                                max: Math.max(150, Math.max(...npmChartBulanPersentase) * 1.1) || 150,
                                min: 0
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bulan',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    
                                    if (context.dataset.yAxisID === 'y') {
                                        label += context.parsed.y.toFixed(2) + ' Juta';
                                    } else if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(1) + '%';
                                    }
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const target = npmChartBulanTarget[index];
                                    const realisasi = npmChartBulanRealisasi[index];
                                    const omzet = omzetChartBulananNPM[index] || 0;
                                    const persenOmzet = npmChartPersenOmzet[index] || 0;
                                    
                                    if (target > 0) {
                                        const gap = realisasi - target;
                                        const gapFormatted = formatFullNumber(Math.abs(gap));
                                        const gapText = gap >= 0 ? 
                                            'Profit lebih: Rp ' + gapFormatted : 
                                            'Profit kurang: Rp ' + gapFormatted;
                                        
                                        const info = [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText
                                        ];
                                        
                                        if (omzet > 0) {
                                            info.push('Omzet Bulanan: Rp ' + formatFullNumber(omzet));
                                            info.push('% NPM/Omzet: ' + persenOmzet.toFixed(1) + '%');
                                            
                                            // Tambahkan interpretasi profitabilitas
                                            let interpretasi = '';
                                            if (persenOmzet >= 20) {
                                                interpretasi = 'Excellent';
                                            } else if (persenOmzet >= 15) {
                                                interpretasi = 'Very Good';
                                            } else if (persenOmzet >= 10) {
                                                interpretasi = 'Good';
                                            } else if (persenOmzet >= 5) {
                                                interpretasi = 'Average';
                                            } else if (persenOmzet >= 0) {
                                                interpretasi = 'Poor';
                                            } else {
                                                interpretasi = 'Loss';
                                            }
                                            info.push('Profitabilitas: ' + interpretasi);
                                        }
                                        
                                        return info;
                                    }
                                    return null;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        } else if (chartTrenNPMBulanan) {
            chartTrenNPMBulanan.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data tren NPM untuk ditampilkan</div>';
        }
        
        ///////////////////////////////////////////////
        // ==================== GRAFIK BIAYA LAIN-LAIN ====================
        <?php
        // HANYA deklarasikan variabel di sini SEKALI
        if (!isset($biayaLainLabelsProcessed)) {
            $biayaLainLabelsProcessed = true;
            
            // Siapkan data untuk chart biaya lain-lain - versi yang diperbaiki
            $biayaLainChartLabels = !empty($biayaLainPerEntitas) ? array_keys($biayaLainPerEntitas) : [];
            $biayaLainChartTarget = !empty($biayaLainPerEntitas) ? array_column($biayaLainPerEntitas, 'target') : [];
            $biayaLainChartRealisasi = !empty($biayaLainPerEntitas) ? array_column($biayaLainPerEntitas, 'realisasi') : [];
            $biayaLainChartProporsi = !empty($biayaLainPerEntitas) ? array_column($biayaLainPerEntitas, 'realisasi') : [];

            // Siapkan data untuk chart biaya lain-lain per bulan
            $biayaLainChartBulanLabels = [];
            $biayaLainChartBulanTarget = [];
            $biayaLainChartBulanRealisasi = [];
            $biayaLainChartBulanPersentase = [];

            if (!empty($biayaLainPerBulan)) {
                foreach ($biayaLainPerBulan as $bulanKey => $data) {
                    $tahun = substr($bulanKey, 0, 4);
                    $bulanAngka = (int)substr($bulanKey, 5, 2);
                    $namaBulan = date('M', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
                    
                    $biayaLainChartBulanLabels[] = $namaBulan . ' ' . $tahun;
                    $biayaLainChartBulanTarget[] = $data['target'];
                    $biayaLainChartBulanRealisasi[] = $data['realisasi'];
                    $biayaLainChartBulanPersentase[] = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
                }
            }

            // Batasi jumlah entitas yang ditampilkan
            $maxBiayaLainDisplay = 8;
            if (count($biayaLainChartLabels) > $maxBiayaLainDisplay) {
                $topIndices = array_keys($biayaLainChartRealisasi);
                array_splice($topIndices, $maxBiayaLainDisplay);
                
                $biayaLainChartLabels = array_slice($biayaLainChartLabels, 0, $maxBiayaLainDisplay);
                $biayaLainChartTarget = array_slice($biayaLainChartTarget, 0, $maxBiayaLainDisplay);
                $biayaLainChartRealisasi = array_slice($biayaLainChartRealisasi, 0, $maxBiayaLainDisplay);
                $biayaLainChartProporsi = array_slice($biayaLainChartProporsi, 0, $maxBiayaLainDisplay);
                
                $targetLainnya = array_sum(array_slice($biayaLainChartTarget, $maxBiayaLainDisplay));
                $realisasiLainnya = array_sum(array_slice($biayaLainChartRealisasi, $maxBiayaLainDisplay));
                
                if ($realisasiLainnya > 0) {
                    $biayaLainChartLabels[] = 'Lainnya';
                    $biayaLainChartTarget[] = $targetLainnya;
                    $biayaLainChartRealisasi[] = $realisasiLainnya;
                    $biayaLainChartProporsi[] = $realisasiLainnya;
                }
            }
        }
        ?>

        // Data dari PHP untuk chart biaya lain-lain
        const biayaLainChartEntitasLabels = <?php echo json_encode($biayaLainChartLabels ?? []); ?>;
        const biayaLainChartTargetValues = <?php echo json_encode($biayaLainChartTarget ?? []); ?>;
        const biayaLainChartRealisasiValues = <?php echo json_encode($biayaLainChartRealisasi ?? []); ?>;
        const biayaLainChartProporsiValues = <?php echo json_encode($biayaLainChartProporsi ?? []); ?>;
        const biayaLainChartBulanLabels = <?php echo json_encode($biayaLainChartBulanLabels ?? []); ?>;
        const biayaLainChartBulanTarget = <?php echo json_encode($biayaLainChartBulanTarget ?? []); ?>;
        const biayaLainChartBulanRealisasi = <?php echo json_encode($biayaLainChartBulanRealisasi ?? []); ?>;
        const biayaLainChartBulanPersentase = <?php echo json_encode($biayaLainChartBulanPersentase ?? []); ?>;

        // Convert to millions for chart
        const biayaLainChartTargetValuesJuta = biayaLainChartTargetValues.map(val => val / 1000000);
        const biayaLainChartRealisasiValuesJuta = biayaLainChartRealisasiValues.map(val => val / 1000000);
        const biayaLainChartProporsiValuesJuta = biayaLainChartProporsiValues.map(val => val / 1000000);
        const biayaLainChartBulanTargetJuta = biayaLainChartBulanTarget.map(val => val / 1000000);
        const biayaLainChartBulanRealisasiJuta = biayaLainChartBulanRealisasi.map(val => val / 1000000);

        // WARNA STANDAR YANG KONSISTEN DENGAN GRAFIK LAIN
        // Mengikuti pola warna dari grafik marketing dan operasional
        const backgroundColorsBiayaLainChart = [
            '#6B7280', '#4B5563', '#374151', '#1F2937', // Gray tones
            '#8B5CF6', '#7C3AED', '#6D28D9', '#5B21B6', // Purple tones
            '#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF', // Blue tones
            '#10B981', '#059669', '#047857', '#065F46'  // Green tones
        ];

        // 26. Grafik Batang Biaya Lain-lain per Entitas
        const ctx26 = document.getElementById('chartBiayaLainEntitas');
        if (ctx26 && biayaLainChartEntitasLabels.length > 0) {
            new Chart(ctx26.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: biayaLainChartEntitasLabels,
                    datasets: [
                        {
                            label: 'Target Lain-lain (Juta)',
                            data: biayaLainChartTargetValuesJuta,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)', // Blue 500 - sama dengan marketing
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Lain-lain (Juta)',
                            data: biayaLainChartRealisasiValuesJuta,
                            backgroundColor: 'rgba(139, 92, 246, 0.7)', // Purple 500 - konsisten
                            borderColor: 'rgba(139, 92, 246, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Biaya Lain-lain (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1) + ' Jt';
                                },
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                drawBorder: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Entitas',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                    
                                    const datasetIndex = context.datasetIndex;
                                    const dataIndex = context.dataIndex;
                                    if (datasetIndex === 0) { // Target
                                        const nilaiAsli = biayaLainChartTargetValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    } else if (datasetIndex === 1) { // Realisasi
                                        const nilaiAsli = biayaLainChartRealisasiValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    }
                                    
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const target = biayaLainChartTargetValues[dataIndex];
                                    const realisasi = biayaLainChartRealisasiValues[dataIndex];
                                    const gap = realisasi - target;
                                    const percentage = target > 0 ? (realisasi / target) * 100 : 0;
                                    
                                    if (target > 0) {
                                        const gapText = gap >= 0 ? 
                                            'OVER: Rp ' + formatFullNumber(gap) : 
                                            'UNDER: Rp ' + formatFullNumber(Math.abs(gap));
                                        
                                        return [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText,
                                            'Pencapaian: ' + percentage.toFixed(1) + '%'
                                        ];
                                    }
                                    return null;
                                },
                                title: function(tooltipItems) {
                                    return 'Entitas: ' + biayaLainChartEntitasLabels[tooltipItems[0].dataIndex];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 5
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        } else if (ctx26) {
            ctx26.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data biaya lain-lain untuk ditampilkan</div>';
        }

        // 27. Grafik Pie untuk Proporsi Biaya Lain-lain per Entitas
        const ctx27 = document.getElementById('chartProporsiBiayaLainEntitas');
        if (ctx27 && biayaLainChartEntitasLabels.length > 0) {
            new Chart(ctx27.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: biayaLainChartEntitasLabels,
                    datasets: [{
                        data: biayaLainChartProporsiValuesJuta,
                        backgroundColor: backgroundColorsBiayaLainChart.slice(0, biayaLainChartEntitasLabels.length),
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 20,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    const nilaiRupiah = (value * 1000000).toLocaleString('id-ID');
                                    
                                    const entitasIndex = context.dataIndex;
                                    const target = biayaLainChartTargetValues[entitasIndex] || 0;
                                    const realisasi = biayaLainChartProporsiValues[entitasIndex] || 0;
                                    const gap = realisasi - target;
                                    
                                    return [
                                        `${label}`,
                                        `Nilai: Rp ${nilaiRupiah}`,
                                        `Persentase: ${percentage}%`,
                                        `Target: Rp ${formatFullNumber(target)}`,
                                        `Realisasi: Rp ${formatFullNumber(realisasi)}`,
                                        `Gap: Rp ${formatFullNumber(gap)}`,
                                        `Pencapaian: ${target > 0 ? ((realisasi / target) * 100).toFixed(1) : '0.0'}%`
                                    ];
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Proporsi Biaya Lain-lain per Entitas',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    }
                }
            });
        } else if (ctx27) {
            ctx27.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data proporsi biaya lain-lain untuk ditampilkan</div>';
        }

        // 28. Grafik Tren Biaya Lain-lain per Bulan
        const ctx28 = document.getElementById('chartTrenBiayaLainBulanan');
        if (ctx28 && biayaLainChartBulanLabels.length > 0) {
            new Chart(ctx28.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: biayaLainChartBulanLabels,
                    datasets: [
                        {
                            label: 'Target Lain-lain (Juta)',
                            data: biayaLainChartBulanTargetJuta,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)', // Blue 500
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Lain-lain (Juta)',
                            data: biayaLainChartBulanRealisasiJuta,
                            backgroundColor: 'rgba(139, 92, 246, 0.7)', // Purple 500
                            borderColor: 'rgba(139, 92, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '% Pencapaian',
                            data: biayaLainChartBulanPersentase,
                            type: 'line',
                            borderColor: 'rgba(239, 68, 68, 1)', // Red 500 - konsisten dengan grafik lain
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            order: 1,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Biaya Lain-lain (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + ' Jt';
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '% Pencapaian',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + '%';
                                },
                                max: Math.max(150, Math.max(...biayaLainChartBulanPersentase) * 1.1) || 150,
                                min: 0
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bulan',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    
                                    if (context.dataset.yAxisID === 'y') {
                                        label += context.parsed.y.toFixed(2) + ' Juta';
                                    } else if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(1) + '%';
                                    }
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const target = biayaLainChartBulanTarget[index];
                                    const realisasi = biayaLainChartBulanRealisasi[index];
                                    
                                    if (target > 0) {
                                        const gap = realisasi - target;
                                        const gapFormatted = formatFullNumber(Math.abs(gap));
                                        const gapText = gap >= 0 ? 
                                            'Lebih tinggi: Rp ' + gapFormatted : 
                                            'Lebih rendah: Rp ' + gapFormatted;
                                        
                                        const omzetBulanan = <?php echo json_encode(array_values($bulanDataOmzet)); ?>;
                                        const omzet = index < omzetBulanan.length ? omzetBulanan[index]['realisasi'] : 0;
                                        const persenOmzet = omzet > 0 ? (realisasi / omzet) * 100 : 0;
                                        
                                        const marginBulanan = omzet - (<?php echo json_encode(array_values($bulanDataHPP)); ?>[index]?.realisasi || 0);
                                        const persenMargin = marginBulanan > 0 ? (realisasi / marginBulanan) * 100 : 0;
                                        
                                        const info = [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText
                                        ];
                                        
                                        if (omzet > 0) {
                                            info.push('% terhadap Omzet: ' + persenOmzet.toFixed(1) + '%');
                                        }
                                        
                                        if (marginBulanan > 0) {
                                            info.push('% terhadap Margin: ' + persenMargin.toFixed(1) + '%');
                                        }
                                        
                                        return info;
                                    }
                                    return null;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        } else if (ctx28) {
            ctx28.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data tren biaya lain-lain untuk ditampilkan</div>';
        }

        // ==================== GRAFIK BIAYA PEMASARAN ====================
        <?php
        // Siapkan data untuk chart biaya pemasaran
        $biayaMarketingEntitasLabels = array_keys($biayaMarketingPerEntitas);
        $biayaMarketingTargetValues = array_column($biayaMarketingPerEntitas, 'target');
        $biayaMarketingRealisasiValues = array_column($biayaMarketingPerEntitas, 'realisasi');
        $biayaMarketingProporsiValues = array_column($biayaMarketingPerEntitas, 'realisasi');

        // Siapkan data untuk chart biaya pemasaran per bulan
        $biayaMarketingBulanLabels = [];
        $biayaMarketingBulanTarget = [];
        $biayaMarketingBulanRealisasi = [];
        $biayaMarketingBulanPersentase = [];

        foreach ($biayaMarketingPerBulan as $bulanKey => $data) {
            $tahun = substr($bulanKey, 0, 4);
            $bulanAngka = (int)substr($bulanKey, 5, 2);
            $namaBulan = date('M', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
            
            $biayaMarketingBulanLabels[] = $namaBulan . ' ' . $tahun;
            $biayaMarketingBulanTarget[] = $data['target'];
            $biayaMarketingBulanRealisasi[] = $data['realisasi'];
            $biayaMarketingBulanPersentase[] = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
        }

        // Tambahkan data efisiensi (ROAS) jika ada
        $biayaMarketingEfisiensiValues = [];
        foreach ($biayaMarketingEntitasLabels as $entitas) {
            $efisiensi = isset($efisiensiMarketingPerEntitas[$entitas]) ? $efisiensiMarketingPerEntitas[$entitas] : 0;
            $biayaMarketingEfisiensiValues[] = $efisiensi;
        }

        // Batasi jumlah entitas yang ditampilkan
        $maxBiayaMarketingDisplay = 8;
        if (count($biayaMarketingEntitasLabels) > $maxBiayaMarketingDisplay) {
            // Ambil top N berdasarkan realisasi
            $topIndices = array_keys($biayaMarketingRealisasiValues);
            array_splice($topIndices, $maxBiayaMarketingDisplay);
            
            $biayaMarketingEntitasLabels = array_slice($biayaMarketingEntitasLabels, 0, $maxBiayaMarketingDisplay);
            $biayaMarketingTargetValues = array_slice($biayaMarketingTargetValues, 0, $maxBiayaMarketingDisplay);
            $biayaMarketingRealisasiValues = array_slice($biayaMarketingRealisasiValues, 0, $maxBiayaMarketingDisplay);
            $biayaMarketingProporsiValues = array_slice($biayaMarketingProporsiValues, 0, $maxBiayaMarketingDisplay);
            $biayaMarketingEfisiensiValues = array_slice($biayaMarketingEfisiensiValues, 0, $maxBiayaMarketingDisplay);
            
            // Hitung nilai "Lainnya"
            $targetLainnya = array_sum(array_slice($biayaMarketingTargetValues, $maxBiayaMarketingDisplay));
            $realisasiLainnya = array_sum(array_slice($biayaMarketingRealisasiValues, $maxBiayaMarketingDisplay));
            
            if ($realisasiLainnya > 0) {
                $biayaMarketingEntitasLabels[] = 'Lainnya';
                $biayaMarketingTargetValues[] = $targetLainnya;
                $biayaMarketingRealisasiValues[] = $realisasiLainnya;
                $biayaMarketingProporsiValues[] = $realisasiLainnya;
                $biayaMarketingEfisiensiValues[] = 0; // ROAS untuk Lainnya dianggap 0
            }
        }
        ?>

        // Data dari PHP untuk chart biaya pemasaran
        const biayaMarketingEntitasLabels = <?php echo json_encode($biayaMarketingEntitasLabels); ?>;
        const biayaMarketingTargetValues = <?php echo json_encode($biayaMarketingTargetValues); ?>;
        const biayaMarketingRealisasiValues = <?php echo json_encode($biayaMarketingRealisasiValues); ?>;
        const biayaMarketingProporsiValues = <?php echo json_encode($biayaMarketingProporsiValues); ?>;
        const biayaMarketingEfisiensiValues = <?php echo json_encode($biayaMarketingEfisiensiValues); ?>;
        const biayaMarketingBulanLabels = <?php echo json_encode($biayaMarketingBulanLabels); ?>;
        const biayaMarketingBulanTarget = <?php echo json_encode($biayaMarketingBulanTarget); ?>;
        const biayaMarketingBulanRealisasi = <?php echo json_encode($biayaMarketingBulanRealisasi); ?>;
        const biayaMarketingBulanPersentase = <?php echo json_encode($biayaMarketingBulanPersentase); ?>;

        // Convert to millions for chart
        const biayaMarketingTargetValuesJuta = biayaMarketingTargetValues.map(val => val / 1000000);
        const biayaMarketingRealisasiValuesJuta = biayaMarketingRealisasiValues.map(val => val / 1000000);
        const biayaMarketingProporsiValuesJuta = biayaMarketingProporsiValues.map(val => val / 1000000);
        const biayaMarketingBulanTargetJuta = biayaMarketingBulanTarget.map(val => val / 1000000);
        const biayaMarketingBulanRealisasiJuta = biayaMarketingBulanRealisasi.map(val => val / 1000000);

        // Warna untuk chart biaya pemasaran
        const backgroundColorsBiayaMarketing = [
            '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899',
            '#F59E0B', '#EF4444', '#10B981', '#06B6D4',
            '#84CC16', '#F97316', '#8B5CF6', '#06B6D4',
            '#3B82F6', '#EC4899', '#14B8A6', '#84CC16'
        ];

        // 22. Grafik Batang Biaya Pemasaran per Entitas
        const ctx22 = document.getElementById('chartBiayaMarketingEntitas');
        if (ctx22) {
            new Chart(ctx22.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: biayaMarketingEntitasLabels,
                    datasets: [
                        {
                            label: 'Target Pemasaran (Juta)',
                            data: biayaMarketingTargetValuesJuta,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Pemasaran (Juta)',
                            data: biayaMarketingRealisasiValuesJuta,
                            backgroundColor: 'rgba(139, 92, 246, 0.7)',
                            borderColor: 'rgba(139, 92, 246, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Biaya Pemasaran (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1) + ' Jt';
                                },
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                drawBorder: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Entitas',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                    
                                    const datasetIndex = context.datasetIndex;
                                    const dataIndex = context.dataIndex;
                                    if (datasetIndex === 0) { // Target
                                        const nilaiAsli = biayaMarketingTargetValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    } else if (datasetIndex === 1) { // Realisasi
                                        const nilaiAsli = biayaMarketingRealisasiValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    }
                                    
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const target = biayaMarketingTargetValues[dataIndex];
                                    const realisasi = biayaMarketingRealisasiValues[dataIndex];
                                    const gap = realisasi - target;
                                    const percentage = target > 0 ? (realisasi / target) * 100 : 0;
                                    const efisiensi = biayaMarketingEfisiensiValues[dataIndex];
                                    
                                    if (target > 0) {
                                        const gapText = gap >= 0 ? 
                                            'OVER: Rp ' + formatFullNumber(gap) : 
                                            'UNDER: Rp ' + formatFullNumber(Math.abs(gap));
                                        
                                        const info = [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText,
                                            'Pencapaian: ' + percentage.toFixed(1) + '%'
                                        ];
                                        
                                        if (efisiensi > 0) {
                                            info.push('ROAS: ' + efisiensi.toFixed(2) + 'x');
                                            info.push('(Setiap Rp 1 menghasilkan Rp ' + efisiensi.toFixed(2) + ')');
                                        }
                                        
                                        return info;
                                    }
                                    return null;
                                },
                                title: function(tooltipItems) {
                                    return 'Entitas: ' + biayaMarketingEntitasLabels[tooltipItems[0].dataIndex];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 5
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        // 23. Grafik Pie untuk Proporsi Biaya Pemasaran per Entitas
        const ctx23 = document.getElementById('chartProporsiBiayaMarketingEntitas');
        if (ctx23) {
            new Chart(ctx23.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: biayaMarketingEntitasLabels,
                    datasets: [{
                        data: biayaMarketingProporsiValuesJuta,
                        backgroundColor: backgroundColorsBiayaMarketing.slice(0, biayaMarketingEntitasLabels.length),
                        borderColor: backgroundColorsBiayaMarketing.slice(0, biayaMarketingEntitasLabels.length).map(color => color),
                        borderWidth: 1,
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    const nilaiRupiah = (value * 1000000).toLocaleString('id-ID');
                                    
                                    const entitasIndex = context.dataIndex;
                                    const target = biayaMarketingTargetValues[entitasIndex] || 0;
                                    const realisasi = biayaMarketingProporsiValues[entitasIndex] || 0;
                                    const gap = realisasi - target;
                                    const efisiensi = biayaMarketingEfisiensiValues[entitasIndex] || 0;
                                    
                                    const info = [
                                        `${label}`,
                                        `Nilai: Rp ${nilaiRupiah}`,
                                        `Persentase: ${percentage}%`,
                                        `Target: Rp ${formatFullNumber(target)}`,
                                        `Realisasi: Rp ${formatFullNumber(realisasi)}`,
                                        `Gap: Rp ${formatFullNumber(gap)}`,
                                        `Pencapaian: ${target > 0 ? ((realisasi / target) * 100).toFixed(1) : '0.0'}%`
                                    ];
                                    
                                    if (efisiensi > 0) {
                                        info.push(`ROAS: ${efisiensi.toFixed(2)}x`);
                                        info.push(`Efisiensi: ${(efisiensi >= 5 ? 'Excellent' : (efisiensi >= 3 ? 'Good' : (efisiensi >= 1 ? 'Average' : 'Poor')))}`);
                                    }
                                    
                                    return info;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Proporsi Biaya Pemasaran per Entitas',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    }
                }
            });
        }

        // 24. Grafik Tren Biaya Pemasaran per Bulan
        const ctx24 = document.getElementById('chartTrenBiayaMarketingBulanan');
        if (ctx24) {
            new Chart(ctx24.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: biayaMarketingBulanLabels,
                    datasets: [
                        {
                            label: 'Target Pemasaran (Juta)',
                            data: biayaMarketingBulanTargetJuta,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Pemasaran (Juta)',
                            data: biayaMarketingBulanRealisasiJuta,
                            backgroundColor: 'rgba(139, 92, 246, 0.7)',
                            borderColor: 'rgba(139, 92, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '% Pencapaian',
                            data: biayaMarketingBulanPersentase,
                            type: 'line',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            order: 1,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Biaya Pemasaran (Juta Rupiah)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + ' Jt';
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '% Pencapaian'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + '%';
                                },
                                max: Math.max(150, Math.max(...biayaMarketingBulanPersentase) * 1.1) || 150,
                                min: 0
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bulan'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    
                                    if (context.dataset.yAxisID === 'y') {
                                        label += context.parsed.y.toFixed(2) + ' Juta';
                                    } else if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(1) + '%';
                                    }
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const target = biayaMarketingBulanTarget[index];
                                    const realisasi = biayaMarketingBulanRealisasi[index];
                                    
                                    if (target > 0) {
                                        const gap = realisasi - target;
                                        const gapFormatted = formatFullNumber(Math.abs(gap));
                                        const gapText = gap >= 0 ? 
                                            'Lebih tinggi: Rp ' + gapFormatted : 
                                            'Lebih rendah: Rp ' + gapFormatted;
                                        
                                        // Dapatkan omzet untuk bulan ini
                                        const omzetBulanan = <?php echo json_encode(array_values($bulanDataOmzet)); ?>;
                                        const omzet = index < omzetBulanan.length ? omzetBulanan[index]['realisasi'] : 0;
                                        const roas = realisasi > 0 ? (omzet / realisasi) : 0;
                                        
                                        const info = [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText
                                        ];
                                        
                                        if (realisasi > 0 && omzet > 0) {
                                            info.push('ROAS Bulanan: ' + roas.toFixed(2) + 'x');
                                            info.push('(Omzet: Rp ' + formatFullNumber(omzet) + ')');
                                        }
                                        
                                        return info;
                                    }
                                    return null;
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }


        // ==================== GRAFIK BIAYA OPERASIONAL ====================
        <?php
        // Siapkan data untuk chart biaya operasional
        $biayaOpsEntitasLabels = array_keys($biayaOpsPerEntitas);
        $biayaOpsTargetValues = array_column($biayaOpsPerEntitas, 'target');
        $biayaOpsRealisasiValues = array_column($biayaOpsPerEntitas, 'realisasi');
        $biayaOpsProporsiValues = array_column($biayaOpsPerEntitas, 'realisasi');

        // Siapkan data untuk chart biaya operasional per bulan
        $biayaOpsBulanLabels = [];
        $biayaOpsBulanTarget = [];
        $biayaOpsBulanRealisasi = [];
        $biayaOpsBulanPersentase = [];

        foreach ($biayaOpsPerBulan as $bulanKey => $data) {
            $tahun = substr($bulanKey, 0, 4);
            $bulanAngka = (int)substr($bulanKey, 5, 2);
            $namaBulan = date('M', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
            
            $biayaOpsBulanLabels[] = $namaBulan . ' ' . $tahun;
            $biayaOpsBulanTarget[] = $data['target'];
            $biayaOpsBulanRealisasi[] = $data['realisasi'];
            $biayaOpsBulanPersentase[] = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
        }

        // Batasi jumlah entitas yang ditampilkan
        $maxBiayaOpsDisplay = 8;
        if (count($biayaOpsEntitasLabels) > $maxBiayaOpsDisplay) {
            // Ambil top N berdasarkan realisasi
            $topIndices = array_keys($biayaOpsRealisasiValues);
            array_splice($topIndices, $maxBiayaOpsDisplay);
            
            $biayaOpsEntitasLabels = array_slice($biayaOpsEntitasLabels, 0, $maxBiayaOpsDisplay);
            $biayaOpsTargetValues = array_slice($biayaOpsTargetValues, 0, $maxBiayaOpsDisplay);
            $biayaOpsRealisasiValues = array_slice($biayaOpsRealisasiValues, 0, $maxBiayaOpsDisplay);
            $biayaOpsProporsiValues = array_slice($biayaOpsProporsiValues, 0, $maxBiayaOpsDisplay);
            
            // Hitung nilai "Lainnya"
            $targetLainnya = array_sum(array_slice($biayaOpsTargetValues, $maxBiayaOpsDisplay));
            $realisasiLainnya = array_sum(array_slice($biayaOpsRealisasiValues, $maxBiayaOpsDisplay));
            
            if ($realisasiLainnya > 0) {
                $biayaOpsEntitasLabels[] = 'Lainnya';
                $biayaOpsTargetValues[] = $targetLainnya;
                $biayaOpsRealisasiValues[] = $realisasiLainnya;
                $biayaOpsProporsiValues[] = $realisasiLainnya;
            }
        }
        ?>

        // Data dari PHP untuk chart biaya operasional
        const biayaOpsEntitasLabels = <?php echo json_encode($biayaOpsEntitasLabels); ?>;
        const biayaOpsTargetValues = <?php echo json_encode($biayaOpsTargetValues); ?>;
        const biayaOpsRealisasiValues = <?php echo json_encode($biayaOpsRealisasiValues); ?>;
        const biayaOpsProporsiValues = <?php echo json_encode($biayaOpsProporsiValues); ?>;
        const biayaOpsBulanLabels = <?php echo json_encode($biayaOpsBulanLabels); ?>;
        const biayaOpsBulanTarget = <?php echo json_encode($biayaOpsBulanTarget); ?>;
        const biayaOpsBulanRealisasi = <?php echo json_encode($biayaOpsBulanRealisasi); ?>;
        const biayaOpsBulanPersentase = <?php echo json_encode($biayaOpsBulanPersentase); ?>;

        // Convert to millions for chart
        const biayaOpsTargetValuesJuta = biayaOpsTargetValues.map(val => val / 1000000);
        const biayaOpsRealisasiValuesJuta = biayaOpsRealisasiValues.map(val => val / 1000000);
        const biayaOpsProporsiValuesJuta = biayaOpsProporsiValues.map(val => val / 1000000);
        const biayaOpsBulanTargetJuta = biayaOpsBulanTarget.map(val => val / 1000000);
        const biayaOpsBulanRealisasiJuta = biayaOpsBulanRealisasi.map(val => val / 1000000);

        // Warna untuk chart biaya operasional
        const backgroundColorsBiayaOps = [
            '#4BC0C0', '#9966FF', '#FF9F40', '#8B5CF6', 
            '#10B981', '#F59E0B', '#EF4444', '#3B82F6',
            '#EC4899', '#14B8A6', '#84CC16', '#F97316',
            '#8B5CF6', '#06B6D4', '#3B82F6', '#EC4899'
        ];

        // 19. Grafik Batang Biaya Operasional per Entitas
        const ctx19 = document.getElementById('chartBiayaOpsEntitas');
        if (ctx19) {
            new Chart(ctx19.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: biayaOpsEntitasLabels,
                    datasets: [
                        {
                            label: 'Target Biaya Ops (Juta)',
                            data: biayaOpsTargetValuesJuta,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Biaya Ops (Juta)',
                            data: biayaOpsRealisasiValuesJuta,
                            backgroundColor: 'rgba(153, 102, 255, 0.7)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Biaya Operasional (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1) + ' Jt';
                                },
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                drawBorder: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Entitas',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                    
                                    const datasetIndex = context.datasetIndex;
                                    const dataIndex = context.dataIndex;
                                    if (datasetIndex === 0) { // Target
                                        const nilaiAsli = biayaOpsTargetValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    } else if (datasetIndex === 1) { // Realisasi
                                        const nilaiAsli = biayaOpsRealisasiValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    }
                                    
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const target = biayaOpsTargetValues[dataIndex];
                                    const realisasi = biayaOpsRealisasiValues[dataIndex];
                                    const gap = realisasi - target;
                                    const percentage = target > 0 ? (realisasi / target) * 100 : 0;
                                    
                                    if (target > 0) {
                                        const gapText = gap >= 0 ? 
                                            'OVER: Rp ' + formatFullNumber(gap) : 
                                            'UNDER: Rp ' + formatFullNumber(Math.abs(gap));
                                        
                                        return [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText,
                                            'Pencapaian: ' + percentage.toFixed(1) + '%'
                                        ];
                                    }
                                    return null;
                                },
                                title: function(tooltipItems) {
                                    return 'Entitas: ' + biayaOpsEntitasLabels[tooltipItems[0].dataIndex];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 5
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        // 20. Grafik Pie untuk Proporsi Biaya Operasional per Entitas
        const ctx20 = document.getElementById('chartProporsiBiayaOpsEntitas');
        if (ctx20) {
            new Chart(ctx20.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: biayaOpsEntitasLabels,
                    datasets: [{
                        data: biayaOpsProporsiValuesJuta,
                        backgroundColor: backgroundColorsBiayaOps.slice(0, biayaOpsEntitasLabels.length),
                        borderColor: backgroundColorsBiayaOps.slice(0, biayaOpsEntitasLabels.length).map(color => color),
                        borderWidth: 1,
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    const nilaiRupiah = (value * 1000000).toLocaleString('id-ID');
                                    
                                    const entitasIndex = context.dataIndex;
                                    const target = biayaOpsTargetValues[entitasIndex] || 0;
                                    const realisasi = biayaOpsProporsiValues[entitasIndex] || 0;
                                    const gap = realisasi - target;
                                    
                                    return [
                                        `${label}`,
                                        `Nilai: Rp ${nilaiRupiah}`,
                                        `Persentase: ${percentage}%`,
                                        `Target: Rp ${formatFullNumber(target)}`,
                                        `Realisasi: Rp ${formatFullNumber(realisasi)}`,
                                        `Gap: Rp ${formatFullNumber(gap)}`,
                                        `Pencapaian: ${target > 0 ? ((realisasi / target) * 100).toFixed(1) : '0.0'}%`
                                    ];
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Proporsi Biaya Operasional per Entitas',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    }
                }
            });
        }

        // 21. Grafik Tren Biaya Operasional per Bulan
        const ctx21 = document.getElementById('chartTrenBiayaOpsBulanan');
        if (ctx21) {
            new Chart(ctx21.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: biayaOpsBulanLabels,
                    datasets: [
                        {
                            label: 'Target Biaya Ops (Juta)',
                            data: biayaOpsBulanTargetJuta,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Biaya Ops (Juta)',
                            data: biayaOpsBulanRealisasiJuta,
                            backgroundColor: 'rgba(153, 102, 255, 0.7)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '% Pencapaian',
                            data: biayaOpsBulanPersentase,
                            type: 'line',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgba(255, 159, 64, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            order: 1,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Biaya Operasional (Juta Rupiah)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + ' Jt';
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '% Pencapaian'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + '%';
                                },
                                max: Math.max(150, Math.max(...biayaOpsBulanPersentase) * 1.1) || 150,
                                min: 0
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bulan'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    
                                    if (context.dataset.yAxisID === 'y') {
                                        label += context.parsed.y.toFixed(2) + ' Juta';
                                    } else if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(1) + '%';
                                    }
                                    return label;
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }

        <?php
                // Siapkan data untuk chart gaji per bulan
        $bulanGajiLabelsChart = [];
        $bulanGajiTargetChart = [];
        $bulanGajiRealisasiChart = [];
        $bulanGajiPersentaseChart = [];

        foreach ($gajiPerBulan as $bulanKey => $data) {
            $tahun = substr($bulanKey, 0, 4);
            $bulanAngka = (int)substr($bulanKey, 5, 2);
            $namaBulan = date('M', mktime(0, 0, 0, $bulanAngka, 1, $tahun));
            
            $bulanGajiLabelsChart[] = $namaBulan . ' ' . $tahun;
            $bulanGajiTargetChart[] = $data['target'];
            $bulanGajiRealisasiChart[] = $data['realisasi'];
            $bulanGajiPersentaseChart[] = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
        }
        ?>

        // Data untuk chart gaji per bulan
        const bulanGajiLabels = <?php echo json_encode($bulanGajiLabelsChart); ?>;
        const bulanGajiTarget = <?php echo json_encode($bulanGajiTargetChart); ?>;
        const bulanGajiRealisasi = <?php echo json_encode($bulanGajiRealisasiChart); ?>;
        const bulanGajiPersentase = <?php echo json_encode($bulanGajiPersentaseChart); ?>;

        // 18. Grafik Tren Beban Gaji per Bulan
        const ctx18 = document.getElementById('chartTrenGajiBulanan');
        if (ctx18) {
            // Convert to millions for chart
            const bulanGajiTargetJuta = bulanGajiTarget.map(val => val / 1000000);
            const bulanGajiRealisasiJuta = bulanGajiRealisasi.map(val => val / 1000000);
            
            new Chart(ctx18.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: bulanGajiLabels,
                    datasets: [
                        {
                            label: 'Target Gaji (Juta)',
                            data: bulanGajiTargetJuta,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Gaji (Juta)',
                            data: bulanGajiRealisasiJuta,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                            order: 2,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: '% Pencapaian',
                            data: bulanGajiPersentase,
                            type: 'line',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgba(255, 159, 64, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            order: 1,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Gaji (Juta Rupiah)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + ' Jt';
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '% Pencapaian'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0) + '%';
                                },
                                max: Math.max(150, Math.max(...bulanGajiPersentase) * 1.1) || 150,
                                min: 0
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bulan'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    
                                    if (context.dataset.yAxisID === 'y') {
                                        label += context.parsed.y.toFixed(2) + ' Juta';
                                    } else if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(1) + '%';
                                    }
                                    return label;
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }

        // ==================== GRAFIK GAJI/HR ====================
        <?php
        // Siapkan data untuk chart gaji
        $gajiEntitasLabels = array_keys($gajiPerEntitas);
        $gajiTargetValues = array_column($gajiPerEntitas, 'target');
        $gajiRealisasiValues = array_column($gajiPerEntitas, 'realisasi');
        $gajiGapValues = array_column($gajiPerEntitas, 'gap');
        
        // Data untuk chart proporsi gaji per entitas
        $proporsiGajiValues = array_column($gajiPerEntitas, 'realisasi');
        
        // Data untuk chart jenis gaji
        $jenisGajiLabels = array_keys($gajiPerJenis);
        $jenisGajiValues = array_column($gajiPerJenis, 'realisasi');
        
        // Data untuk chart tren harian
        $trenGajiDates = array_keys($gajiPerTanggal);
        $trenGajiTarget = array_column($gajiPerTanggal, 'target');
        $trenGajiRealisasi = array_column($gajiPerTanggal, 'realisasi');
        
        // Batasi jumlah entitas yang ditampilkan
        $maxGajiDisplay = 8;
        if (count($gajiEntitasLabels) > $maxGajiDisplay) {
            // Ambil top N berdasarkan realisasi
            $topIndices = array_keys($gajiRealisasiValues);
            array_splice($topIndices, $maxGajiDisplay);
            
            $gajiEntitasLabels = array_slice($gajiEntitasLabels, 0, $maxGajiDisplay);
            $gajiTargetValues = array_slice($gajiTargetValues, 0, $maxGajiDisplay);
            $gajiRealisasiValues = array_slice($gajiRealisasiValues, 0, $maxGajiDisplay);
            $gajiGapValues = array_slice($gajiGapValues, 0, $maxGajiDisplay);
            $proporsiGajiValues = array_slice($proporsiGajiValues, 0, $maxGajiDisplay);
            
            // Hitung nilai "Lainnya"
            $targetLainnya = array_sum(array_slice($gajiTargetValues, $maxGajiDisplay));
            $realisasiLainnya = array_sum(array_slice($gajiRealisasiValues, $maxGajiDisplay));
            
            if ($realisasiLainnya > 0) {
                $gajiEntitasLabels[] = 'Lainnya';
                $gajiTargetValues[] = $targetLainnya;
                $gajiRealisasiValues[] = $realisasiLainnya;
                $gajiGapValues[] = $realisasiLainnya - $targetLainnya;
                $proporsiGajiValues[] = $realisasiLainnya;
            }
        }
        ?>
        
        // Data dari PHP untuk chart gaji
        const gajiEntitasLabels = <?php echo json_encode($gajiEntitasLabels); ?>;
        const gajiTargetValues = <?php echo json_encode($gajiTargetValues); ?>;
        const gajiRealisasiValues = <?php echo json_encode($gajiRealisasiValues); ?>;
        const proporsiGajiValues = <?php echo json_encode($proporsiGajiValues); ?>;
        const jenisGajiLabels = <?php echo json_encode($jenisGajiLabels); ?>;
        const jenisGajiValues = <?php echo json_encode($jenisGajiValues); ?>;
        const trenGajiDates = <?php echo json_encode($trenGajiDates); ?>;
        const trenGajiTarget = <?php echo json_encode($trenGajiTarget); ?>;
        const trenGajiRealisasi = <?php echo json_encode($trenGajiRealisasi); ?>;
        
        // Warna untuk chart gaji
        const backgroundColorsGaji = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444',
            '#3B82F6', '#EC4899', '#14B8A6', '#84CC16', '#F97316'
        ];

        // Convert to millions for chart
        const gajiTargetValuesJuta = gajiTargetValues.map(val => val / 1000000);
        const gajiRealisasiValuesJuta = gajiRealisasiValues.map(val => val / 1000000);
        const proporsiGajiValuesJuta = proporsiGajiValues.map(val => val / 1000000);
        const jenisGajiValuesJuta = jenisGajiValues.map(val => val / 1000000);
        const trenGajiTargetJuta = trenGajiTarget.map(val => val / 1000000);
        const trenGajiRealisasiJuta = trenGajiRealisasi.map(val => val / 1000000);

        // 16. Grafik Batang Beban Gaji per Entitas (Target vs Realisasi)
        const ctx16 = document.getElementById('chartGajiEntitas');
        if (ctx16) {
            new Chart(ctx16.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: gajiEntitasLabels,
                    datasets: [
                        {
                            label: 'Target Gaji (Juta)',
                            data: gajiTargetValuesJuta,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Realisasi Gaji (Juta)',
                            data: gajiRealisasiValuesJuta,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nilai Gaji (Juta Rupiah)',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1) + ' Jt';
                                },
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                drawBorder: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Entitas',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                    
                                    const datasetIndex = context.datasetIndex;
                                    const dataIndex = context.dataIndex;
                                    if (datasetIndex === 0) { // Target
                                        const nilaiAsli = gajiTargetValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    } else if (datasetIndex === 1) { // Realisasi
                                        const nilaiAsli = gajiRealisasiValues[dataIndex];
                                        label += ' (Rp ' + formatFullNumber(nilaiAsli) + ')';
                                    }
                                    
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const dataIndex = context.dataIndex;
                                    const target = gajiTargetValues[dataIndex];
                                    const realisasi = gajiRealisasiValues[dataIndex];
                                    const gap = realisasi - target;
                                    const percentage = target > 0 ? (realisasi / target) * 100 : 0;
                                    
                                    if (target > 0) {
                                        const gapText = gap >= 0 ? 
                                            'OVER: Rp ' + formatFullNumber(gap) : 
                                            'UNDER: Rp ' + formatFullNumber(Math.abs(gap));
                                        
                                        return [
                                            'Gap: Rp ' + formatFullNumber(gap),
                                            gapText,
                                            'Pencapaian: ' + percentage.toFixed(1) + '%'
                                        ];
                                    }
                                    return null;
                                },
                                title: function(tooltipItems) {
                                    return 'Entitas: ' + gajiEntitasLabels[tooltipItems[0].dataIndex];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 5
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        // 17. Grafik Pie untuk Proporsi Biaya Gaji per Entitas
        const ctx17 = document.getElementById('chartProporsiGajiEntitas');
        if (ctx17) {
            new Chart(ctx17.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: gajiEntitasLabels,
                    datasets: [{
                        data: proporsiGajiValuesJuta,
                        backgroundColor: backgroundColorsGaji.slice(0, gajiEntitasLabels.length),
                        borderColor: backgroundColorsGaji.slice(0, gajiEntitasLabels.length).map(color => color),
                        borderWidth: 1,
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                    const nilaiRupiah = (value * 1000000).toLocaleString('id-ID');
                                    
                                    const entitasIndex = context.dataIndex;
                                    const target = gajiTargetValues[entitasIndex] || 0;
                                    const realisasi = proporsiGajiValues[entitasIndex] || 0;
                                    const gap = realisasi - target;
                                    
                                    return [
                                        `${label}`,
                                        `Nilai: Rp ${nilaiRupiah}`,
                                        `Persentase: ${percentage}%`,
                                        `Target: Rp ${formatFullNumber(target)}`,
                                        `Realisasi: Rp ${formatFullNumber(realisasi)}`,
                                        `Gap: Rp ${formatFullNumber(gap)}`,
                                        `Pencapaian: ${target > 0 ? ((realisasi / target) * 100).toFixed(1) : '0.0'}%`
                                    ];
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Proporsi Beban Gaji per Entitas',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    }
                }
            });
        }

        // ==================== GRAFIK OMZET ====================
        // 1. Grafik Tren Target dan Realisasi Omzet per Hari
        const ctx1 = document.getElementById('chartTrenOmzetHarian').getContext('2d');
        const tanggalLabelsOmzet = <?php echo json_encode(array_keys($tanggalDataOmzet)); ?>;
        const targetHarianOmzet = <?php echo json_encode(array_column($tanggalDataOmzet, 'target')); ?>;
        const realisasiHarianOmzet = <?php echo json_encode(array_column($tanggalDataOmzet, 'realisasi')); ?>;
        
        // Convert to millions for chart
        const targetHarianOmzetJuta = targetHarianOmzet.map(val => val / 1000000);
        const realisasiHarianOmzetJuta = realisasiHarianOmzet.map(val => val / 1000000);
        
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: tanggalLabelsOmzet,
                datasets: [
                    {
                        label: 'Target Omzet Harian (Juta)',
                        data: targetHarianOmzetJuta,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Realisasi Omzet Harian (Juta)',
                        data: realisasiHarianOmzetJuta,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai Omzet (Juta Rupiah)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // 2. Grafik Tren Realisasi Omzet per Hari per Entitas
        const ctx2 = document.getElementById('chartRealisasiOmzetPerEntitas').getContext('2d');
        
        // Data untuk grafik ke-2
        <?php
        // Proses data realisasi omzet per entitas per hari
        $realisasiPerEntitasPerHari = [];
        $allTanggal = array_unique(array_keys($tanggalDataOmzet));
        sort($allTanggal);
        
        foreach ($dataReport as $row) {
            if ($row['nama_akun'] === 'OMZET') {
                $tanggal = $row['tanggal'];
                $entitas = $row['nama_entitas'];
                $realisasi = (float)$row['nilai_realisasi'];
                
                if (!isset($realisasiPerEntitasPerHari[$entitas])) {
                    $realisasiPerEntitasPerHari[$entitas] = [];
                }
                
                if (!isset($realisasiPerEntitasPerHari[$entitas][$tanggal])) {
                    $realisasiPerEntitasPerHari[$entitas][$tanggal] = 0;
                }
                $realisasiPerEntitasPerHari[$entitas][$tanggal] += $realisasi;
            }
        }
        
        // Siapkan datasets untuk chart - ambil maksimal 8 entitas teratas
        $topEntitas = array_keys($entitasDataOmzet);
        $maxEntitas = min(8, count($topEntitas));
        
        $datasetsJS = [];
        $colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)'
        ];
        
        $colorIndex = 0;
        for ($i = 0; $i < $maxEntitas; $i++) {
            $entitas = $topEntitas[$i];
            $dataArray = [];
            foreach ($allTanggal as $tanggal) {
                $value = isset($realisasiPerEntitasPerHari[$entitas][$tanggal]) 
                    ? $realisasiPerEntitasPerHari[$entitas][$tanggal] / 1000000 
                    : 0;
                $dataArray[] = $value;
            }
            
            $datasetsJS[] = [
                'label' => $entitas,
                'data' => $dataArray,
                'borderColor' => $colors[$colorIndex],
                'backgroundColor' => str_replace('0.8', '0.2', $colors[$colorIndex]),
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.3
            ];
            $colorIndex++;
        }
        ?>
        
        const tanggalLabels = <?php echo json_encode($allTanggal); ?>;
        const datasetsRealisasiPerEntitas = <?php echo json_encode($datasetsJS); ?>;
        
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: tanggalLabels,
                datasets: datasetsRealisasiPerEntitas
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Realisasi Omzet (Juta Rupiah)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' Jt';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        },
                        ticks: {
                            maxTicksLimit: 15
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // 3. Grafik Batang Target dan Realisasi Omzet Per Bulan (Januari - Bulan Saat Ini)
        var ctx = document.getElementById('chartOmzetBulanan').getContext('2d');
        var chartOmzetBulanan = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labelsOmzet); ?>,
                datasets: [{
                    label: 'Target Omzet (Jutaan)',
                    data: <?php echo json_encode($targetOmzetData); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Realisasi Omzet (Jutaan)',
                    data: <?php echo json_encode($realisasiOmzetData); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai (dalam Jutaan)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Target vs Realisasi Omzet Per Bulan'
                    }
                }
            }
        });

        // 4. Grafik Batang Target dan Realisasi Omzet Per Entitas
        const ctx4 = document.getElementById('chartOmzetEntitas').getContext('2d');
        const entitasLabelsOmzet = <?php echo json_encode(array_keys($entitasDataOmzet)); ?>;
        const targetEntitasOmzet = <?php echo json_encode(array_column($entitasDataOmzet, 'total_target')); ?>;
        const realisasiEntitasOmzet = <?php echo json_encode(array_column($entitasDataOmzet, 'total_realisasi')); ?>;
        
        // Convert to millions for chart
        const targetEntitasOmzetJuta = targetEntitasOmzet.map(val => val / 1000000);
        const realisasiEntitasOmzetJuta = realisasiEntitasOmzet.map(val => val / 1000000);
        
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: entitasLabelsOmzet,
                datasets: [
                    {
                        label: 'Target Omzet per Entitas (Juta)',
                        data: targetEntitasOmzetJuta,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi Omzet per Entitas (Juta)',
                        data: realisasiEntitasOmzetJuta,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai Omzet (Juta Rupiah)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Entitas'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // 5. Grafik Pie Omzet per Entitas
        // const ctx5 = document.getElementById('chartPieOmzetEntitas').getContext('2d');
        // const realisasiPieOmzet = <?php echo json_encode(array_column($entitasDataOmzet, 'total_realisasi')); ?>;
        
        // new Chart(ctx5, {
        //     type: 'pie',
        //     data: {
        //         labels: entitasLabelsOmzet,
        //         datasets: [{
        //             data: realisasiPieOmzet,
        //             backgroundColor: [
        //                 '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        //                 '#9966FF', '#FF9F40', '#8B5CF6', '#10B981',
        //                 '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'
        //             ],
        //             borderWidth: 1
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         maintainAspectRatio: false,
        //         plugins: {
        //             legend: {
        //                 position: 'right',
        //             },
        //             tooltip: {
        //                 callbacks: {
        //                     label: function(context) {
        //                         const label = context.label || '';
        //                         const value = context.parsed;
        //                         const total = context.dataset.data.reduce((a, b) => a + b, 0);
        //                         const percentage = ((value / total) * 100).toFixed(1);
        //                         return `${label}: ${formatFullNumber(value)} (${percentage}%)`;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // });

        // // 6. Grafik Batang & Line - Trend Harian
        // const ctx6 = document.getElementById('chartTrendHarianBatangLine').getContext('2d');
        
        // new Chart(ctx6, {
        //     type: 'bar',
        //     data: {
        //         labels: tanggalLabelsOmzet,
        //         datasets: [
        //             {
        //                 label: 'Target Harian (Juta)',
        //                 data: targetHarianOmzetJuta,
        //                 backgroundColor: 'rgba(54, 162, 235, 0.5)',
        //                 borderColor: 'rgba(54, 162, 235, 1)',
        //                 borderWidth: 1,
        //                 type: 'bar',
        //                 order: 2
        //             },
        //             {
        //                 label: 'Realisasi Harian (Juta)',
        //                 data: realisasiHarianOmzetJuta,
        //                 borderColor: 'rgba(255, 99, 132, 1)',
        //                 backgroundColor: 'rgba(255, 99, 132, 0.2)',
        //                 borderWidth: 3,
        //                 type: 'line',
        //                 fill: false,
        //                 tension: 0.3,
        //                 order: 1
        //             }
        //         ]
        //     },
        //     options: {
        //         responsive: true,
        //         maintainAspectRatio: false,
        //         scales: {
        //             y: {
        //                 beginAtZero: true,
        //                 title: {
        //                     display: true,
        //                     text: 'Nilai Omzet (Juta Rupiah)'
        //                 },
        //                 ticks: {
        //                     callback: function(value) {
        //                         return value.toFixed(0) + ' Jt';
        //                     }
        //                 }
        //             },
        //             x: {
        //                 title: {
        //                     display: true,
        //                     text: 'Tanggal'
        //                 },
        //                 ticks: {
        //                     maxTicksLimit: 10
        //                 }
        //             }
        //         },
        //         plugins: {
        //             tooltip: {
        //                 callbacks: {
        //                     label: function(context) {
        //                         let label = context.dataset.label || '';
        //                         if (label) {
        //                             label += ': ';
        //                         }
        //                         label += context.parsed.y.toFixed(2) + ' Juta';
        //                         return label;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // });

        // 7. Grafik Batang & Line - Trend Mingguan
        const ctx7 = document.getElementById('chartTrendMingguanBatangLine').getContext('2d');
        
        // Data untuk grafik mingguan
        <?php
        $mingguLabels = [];
        $targetMingguan = [];
        $realisasiMingguan = [];
        
        foreach ($mingguDataOmzet as $key => $data) {
            $mingguLabels[] = $data['label'];
            $targetMingguan[] = $data['target'];
            $realisasiMingguan[] = $data['realisasi'];
        }
        ?>
        
        const mingguLabels = <?php echo json_encode($mingguLabels); ?>;
        const targetMingguanOmzet = <?php echo json_encode($targetMingguan); ?>;
        const realisasiMingguanOmzet = <?php echo json_encode($realisasiMingguan); ?>;
        
        // Convert to millions for chart
        const targetMingguanOmzetJuta = targetMingguanOmzet.map(val => val / 1000000);
        const realisasiMingguanOmzetJuta = realisasiMingguanOmzet.map(val => val / 1000000);
        
        new Chart(ctx7, {
            type: 'bar',
            data: {
                labels: mingguLabels,
                datasets: [
                    {
                        label: 'Target Mingguan (Juta)',
                        data: targetMingguanOmzetJuta,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        type: 'bar',
                        order: 2
                    },
                    {
                        label: 'Realisasi Mingguan (Juta)',
                        data: realisasiMingguanOmzetJuta,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderWidth: 3,
                        type: 'line',
                        fill: false,
                        tension: 0.3,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai Omzet (Juta Rupiah)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' Jt';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Minggu'
                        },
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // ==================== GRAFIK PRODUK ====================
        // Data dari PHP untuk produk
        const produkLabels = <?php echo json_encode($produkLabels); ?>;
        const produkJumlah = <?php echo json_encode($produkJumlah); ?>;
        const produkNilai = <?php echo json_encode($produkNilai); ?>;
        const totalProdukJumlah = <?php echo $totalProdukJumlah; ?>;
        const totalProdukNilai = <?php echo $totalProdukNilai; ?>;

        // 8. Grafik Donut - Distribusi Penjualan Berdasarkan Jumlah
        const ctx8 = document.getElementById('chartProdukJumlahDonut').getContext('2d');
        
        // Warna untuk chart donut jumlah
        const backgroundColorsDonut = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#8B5CF6', '#10B981',
            '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4',
            '#3B82F6', '#EC4899', '#14B8A6'
        ];
        
        new Chart(ctx8, {
            type: 'doughnut',
            data: {
                labels: produkLabels,
                datasets: [{
                    data: produkJumlah,
                    backgroundColor: backgroundColorsDonut,
                    borderColor: backgroundColorsDonut.map(color => color),
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const percentage = ((value / totalProdukJumlah) * 100).toFixed(1);
                                return `${label}: ${formatFullNumber(value)} unit (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // 9. Grafik Pie - Distribusi Penjualan Berdasarkan Nilai
        const ctx9 = document.getElementById('chartProdukNilaiPie').getContext('2d');
        
        new Chart(ctx9, {
            type: 'pie',
            data: {
                labels: produkLabels,
                datasets: [{
                    data: produkNilai,
                    backgroundColor: backgroundColorsDonut,
                    borderColor: backgroundColorsDonut.map(color => color),
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const percentage = ((value / totalProdukNilai) * 100).toFixed(1);
                                return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // ==================== GRAFIK HPP ====================
        // 10. Grafik Tren Target dan Realisasi HPP per Hari
        const ctx10 = document.getElementById('chartTrenHPPHarian').getContext('2d');
        
        // Data untuk grafik HPP
        const tanggalLabelsHPP = <?php echo json_encode(array_keys($tanggalDataHPP)); ?>;
        const targetHarianHPP = <?php echo json_encode(array_column($tanggalDataHPP, 'target')); ?>;
        const realisasiHarianHPP = <?php echo json_encode(array_column($tanggalDataHPP, 'realisasi')); ?>;
        
        // Convert to millions for chart
        const targetHarianHPPJuta = targetHarianHPP.map(val => val / 1000000);
        const realisasiHarianHPPJuta = realisasiHarianHPP.map(val => val / 1000000);
        
        new Chart(ctx10, {
            type: 'line',
            data: {
                labels: tanggalLabelsHPP,
                datasets: [
                    {
                        label: 'Target HPP Harian (Juta)',
                        data: targetHarianHPPJuta,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Realisasi HPP Harian (Juta)',
                        data: realisasiHarianHPPJuta,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai HPP (Juta Rupiah)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' Jt';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        },
                        ticks: {
                            maxTicksLimit: 15
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // 11. Grafik Batang Target dan Realisasi HPP per Entitas
        const ctx11 = document.getElementById('chartHPPEntitas').getContext('2d');
        
        const entitasLabelsHPP = <?php echo json_encode(array_keys($entitasDataHPP)); ?>;
        const targetEntitasHPP = <?php echo json_encode(array_column($entitasDataHPP, 'total_target')); ?>;
        const realisasiEntitasHPP = <?php echo json_encode(array_column($entitasDataHPP, 'total_realisasi')); ?>;
        
        // Convert to millions for chart
        const targetEntitasHPPJuta = targetEntitasHPP.map(val => val / 1000000);
        const realisasiEntitasHPPJuta = realisasiEntitasHPP.map(val => val / 1000000);
        
        new Chart(ctx11, {
            type: 'bar',
            data: {
                labels: entitasLabelsHPP,
                datasets: [
                    {
                        label: 'Target HPP per Entitas (Juta)',
                        data: targetEntitasHPPJuta,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi HPP per Entitas (Juta)',
                        data: realisasiEntitasHPPJuta,
                        backgroundColor: 'rgba(255, 159, 64, 0.7)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai HPP (Juta Rupiah)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' Jt';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Entitas'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // ==================== GRAFIK BIAYA ====================
        // Data dari PHP untuk biaya
        const tanggalLabelsBiaya = <?php echo json_encode(array_keys($tanggalDataBiaya)); ?>;
        const targetHarianBiaya = <?php echo json_encode(array_column($tanggalDataBiaya, 'target')); ?>;
        const realisasiHarianBiaya = <?php echo json_encode(array_column($tanggalDataBiaya, 'realisasi')); ?>;
        const jenisBiayaLabels = <?php echo json_encode(array_keys($jenisBiayaData)); ?>;
        const targetJenisBiaya = <?php echo json_encode(array_column($jenisBiayaData, 'target')); ?>;
        const realisasiJenisBiaya = <?php echo json_encode(array_column($jenisBiayaData, 'realisasi')); ?>;
        const entitasBiayaLabels = <?php echo json_encode(array_keys($entitasBiayaData)); ?>;
        const realisasiEntitasBiaya = <?php echo json_encode(array_column($entitasBiayaData, 'realisasi')); ?>;
        
        // Convert to millions for chart
        const targetHarianBiayaJuta = targetHarianBiaya.map(val => val / 1000000);
        const realisasiHarianBiayaJuta = realisasiHarianBiaya.map(val => val / 1000000);
        const targetJenisBiayaJuta = targetJenisBiaya.map(val => val / 1000000);
        const realisasiJenisBiayaJuta = realisasiJenisBiaya.map(val => val / 1000000);
        const realisasiEntitasBiayaJuta = realisasiEntitasBiaya.map(val => val / 1000000);

        // 12. Grafik Tren Target dan Realisasi Biaya per Hari
        const ctx12 = document.getElementById('chartTrenBiayaHarian').getContext('2d');
        
        new Chart(ctx12, {
            type: 'line',
            data: {
                labels: tanggalLabelsBiaya,
                datasets: [
                    {
                        label: 'Target Biaya Harian (Juta)',
                        data: targetHarianBiayaJuta,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Realisasi Biaya Harian (Juta)',
                        data: realisasiHarianBiayaJuta,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai Biaya (Juta Rupiah)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' Jt';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        },
                        ticks: {
                            maxTicksLimit: 15
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // 13. Grafik Batang Target dan Realisasi Berdasarkan Jenis Biaya
        const ctx13 = document.getElementById('chartJenisBiaya').getContext('2d');
        
        new Chart(ctx13, {
            type: 'bar',
            data: {
                labels: jenisBiayaLabels,
                datasets: [
                    {
                        label: 'Target Jenis Biaya (Juta)',
                        data: targetJenisBiayaJuta,
                        backgroundColor: 'rgba(255, 159, 64, 0.7)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi Jenis Biaya (Juta)',
                        data: realisasiJenisBiayaJuta,
                        backgroundColor: 'rgba(153, 102, 255, 0.7)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai Biaya (Juta Rupiah)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' Jt';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // 14. Grafik Pie - Proporsi Biaya per Entitas
        const ctx14 = document.getElementById('chartProporsiBiayaEntitas').getContext('2d');
        
        // Warna untuk chart pie biaya
        const backgroundColorsBiaya = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#8B5CF6', '#10B981',
            '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4',
            '#3B82F6', '#EC4899', '#14B8A6'
        ];
        
        new Chart(ctx14, {
            type: 'pie',
            data: {
                labels: entitasBiayaLabels,
                datasets: [{
                    data: realisasiEntitasBiaya,
                    backgroundColor: backgroundColorsBiaya.slice(0, entitasBiayaLabels.length),
                    borderColor: backgroundColorsBiaya.slice(0, entitasBiayaLabels.length).map(color => color),
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // 15. Grafik Pie - Proporsi Biaya berdasarkan Jenis
        const ctx15 = document.getElementById('chartProporsiJenisBiaya').getContext('2d');
        
        new Chart(ctx15, {
            type: 'pie',
            data: {
                labels: jenisBiayaLabels,
                datasets: [{
                    data: realisasiJenisBiaya,
                    backgroundColor: backgroundColorsBiaya.slice(0, jenisBiayaLabels.length),
                    borderColor: backgroundColorsBiaya.slice(0, jenisBiayaLabels.length).map(color => color),
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });

    // 4. Grafik Tren Omzet Per Tahun
    const ctx4 = document.getElementById('chartOmzetTahunan').getContext('2d');

    // Data untuk grafik tahunan (dari PHP)
    <?php
    $chartTahunLabels = [];
    $chartTargetTahunan = [];
    $chartRealisasiTahunan = [];
    $chartPersentaseTahunan = [];

    foreach ($tahunDataOmzet as $tahun => $data) {
        $chartTahunLabels[] = $tahun;
        $chartTargetTahunan[] = $data['target'];
        $chartRealisasiTahunan[] = $data['realisasi'];
        $chartPersentaseTahunan[] = $data['target'] > 0 ? ($data['realisasi'] / $data['target']) * 100 : 0;
    }
    ?>

    const tahunLabelsChart = <?php echo json_encode($chartTahunLabels); ?>;
    const targetTahunanOmzetChart = <?php echo json_encode($chartTargetTahunan); ?>;
    const realisasiTahunanOmzetChart = <?php echo json_encode($chartRealisasiTahunan); ?>;
    const persentaseTahunanChart = <?php echo json_encode($chartPersentaseTahunan); ?>;

    // Jika ada data, buat chart
    if (tahunLabelsChart.length > 0 && targetTahunanOmzetChart.length > 0) {
        // Convert to millions for chart
        const targetTahunanOmzetJuta = targetTahunanOmzetChart.map(val => val / 1000000);
        const realisasiTahunanOmzetJuta = realisasiTahunanOmzetChart.map(val => val / 1000000);

        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: tahunLabelsChart,
                datasets: [
                    {
                        label: 'Target Omzet (Juta)',
                        data: targetTahunanOmzetJuta,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y',
                        order: 2,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Realisasi Omzet (Juta)',
                        data: realisasiTahunanOmzetJuta,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        yAxisID: 'y',
                        order: 2,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    },
                    {
                        label: '% Pencapaian',
                        data: persentaseTahunanChart,
                        type: 'line',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y1',
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: 'rgba(255, 159, 64, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        order: 1,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai Omzet (Juta Rupiah)',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' Jt';
                            },
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            drawBorder: true,
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: '% Pencapaian',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + '%';
                            },
                            font: {
                                size: 11
                            },
                            max: Math.max(150, Math.max(...persentaseTahunanChart) * 1.1) || 150,
                            min: 0
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tahun',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                
                                if (context.dataset.yAxisID === 'y') {
                                    // Untuk bar chart (target dan realisasi)
                                    label += context.parsed.y.toFixed(2) + ' Juta';
                                    
                                    // Tambahkan nilai asli dalam tooltip
                                    const datasetIndex = context.datasetIndex;
                                    if (datasetIndex === 0) { // Target
                                        label += ' (Rp ' + formatFullNumber(targetTahunanOmzetChart[context.dataIndex]) + ')';
                                    } else if (datasetIndex === 1) { // Realisasi
                                        label += ' (Rp ' + formatFullNumber(realisasiTahunanOmzetChart[context.dataIndex]) + ')';
                                    }
                                } else if (context.dataset.yAxisID === 'y1') {
                                    // Untuk line chart (persentase)
                                    label += context.parsed.y.toFixed(1) + '%';
                                }
                                return label;
                            },
                            afterLabel: function(context) {
                                // Tambahkan info tambahan setelah label
                                const index = context.dataIndex;
                                const tahun = tahunLabelsChart[index];
                                const target = targetTahunanOmzetChart[index];
                                const realisasi = realisasiTahunanOmzetChart[index];
                                
                                if (target > 0) {
                                    const gap = realisasi - target;
                                    const gapFormatted = formatFullNumber(Math.abs(gap));
                                    const gapText = gap >= 0 ? 
                                        'Lebih tinggi: Rp ' + gapFormatted : 
                                        'Lebih rendah: Rp ' + gapFormatted;
                                    
                                    // Hitung rata-rata bulanan dan harian
                                    const rataBulanan = realisasi / 12;
                                    const daysInYear = ((tahun % 4 == 0 && tahun % 100 != 0) || tahun % 400 == 0) ? 366 : 365;
                                    const rataHarian = realisasi / daysInYear;
                                    
                                    return [
                                        'Gap: Rp ' + formatFullNumber(gap),
                                        gapText,
                                        'Rata-rata Bulanan: Rp ' + formatFullNumber(rataBulanan),
                                        'Rata-rata Harian: Rp ' + formatFullNumber(rataHarian)
                                    ];
                                }
                                return null;
                            },
                            title: function(tooltipItems) {
                                // Tampilkan tahun
                                const index = tooltipItems[0].dataIndex;
                                return 'Tahun ' + tahunLabelsChart[index];
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#fff',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        boxPadding: 5
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                elements: {
                    line: {
                        tension: 0.3
                    },
                    point: {
                        hoverRadius: 10,
                        hoverBorderWidth: 2
                    }
                }
            }
        });
    } else {
        // Tampilkan pesan jika tidak ada data
        ctx4.canvas.parentNode.innerHTML = '<div class="alert alert-info text-center">Tidak ada data omzet tahunan yang tersedia</div>';
    }

            // ==================== GRAFIK DISTRIBUSI PENJUALAN PER ENTITAS ====================
        // Data untuk distribusi penjualan per entitas
        <?php
        // Persiapan data untuk chart
        $entitasLabelsChart = array_keys($totalNilaiPerEntitas);
        $nilaiPerEntitasChart = array_values($totalNilaiPerEntitas);
        $jumlahPerEntitasChart = array_values($totalJumlahPerEntitas);
        
        // Batasi jumlah entitas yang ditampilkan (misal 10 teratas)
        $maxDisplay = 10;
        if (count($entitasLabelsChart) > $maxDisplay) {
            // Ambil top N berdasarkan nilai
            $topIndices = array_keys($nilaiPerEntitasChart);
            array_splice($topIndices, $maxDisplay);
            
            $entitasLabelsChart = array_slice($entitasLabelsChart, 0, $maxDisplay);
            $nilaiPerEntitasChart = array_slice($nilaiPerEntitasChart, 0, $maxDisplay);
            $jumlahPerEntitasChart = array_slice($jumlahPerEntitasChart, 0, $maxDisplay);
            
            // Hitung nilai "Lainnya"
            $nilaiLainnya = array_sum(array_slice($nilaiPerEntitasChart, $maxDisplay));
            $jumlahLainnya = array_sum(array_slice($jumlahPerEntitasChart, $maxDisplay));
            
            if ($nilaiLainnya > 0) {
                $entitasLabelsChart[] = 'Lainnya';
                $nilaiPerEntitasChart[] = $nilaiLainnya;
                $jumlahPerEntitasChart[] = $jumlahLainnya;
            }
        }
        ?>
        
        const entitasLabelsDistribusi = <?php echo json_encode($entitasLabelsChart); ?>;
        const nilaiPerEntitasChart = <?php echo json_encode($nilaiPerEntitasChart); ?>;
        const jumlahPerEntitasChart = <?php echo json_encode($jumlahPerEntitasChart); ?>;
        
        // Warna untuk chart
        const backgroundColorsEntitas = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444',
            '#3B82F6', '#EC4899', '#14B8A6', '#84CC16', '#F97316',
            '#8B5CF6', '#06B6D4', '#3B82F6', '#EC4899', '#14B8A6'
        ];

        // 10. Grafik Distribusi Nilai Penjualan per Entitas
        const ctx10 = document.getElementById('chartDistribusiNilaiEntitas').getContext('2d');
        
        // Convert to millions for chart
        const nilaiPerEntitasJuta = nilaiPerEntitasChart.map(val => val / 1000000);
        
        new Chart(ctx10, {
            type: 'pie',
            data: {
                labels: entitasLabelsDistribusi,
                datasets: [{
                    data: nilaiPerEntitasJuta,
                    backgroundColor: backgroundColorsEntitas.slice(0, entitasLabelsDistribusi.length),
                    borderColor: backgroundColorsEntitas.slice(0, entitasLabelsDistribusi.length).map(color => color),
                    borderWidth: 1,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 10
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                        const valueInRupiah = (value * 1000000).toLocaleString('id-ID');
                                        
                                        return {
                                            text: `${label}: ${value.toFixed(1)} Jt (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor[i],
                                            lineWidth: data.datasets[0].borderWidth,
                                            hidden: false,
                                            index: i,
                                            // Custom data untuk tooltip
                                            value: valueInRupiah,
                                            percentage: percentage
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                const nilaiRupiah = (value * 1000000).toLocaleString('id-ID');
                                return [
                                    `${label}`,
                                    `Nilai: Rp ${nilaiRupiah}`,
                                    `Persentase: ${percentage}%`,
                                    `Dalam Juta: ${value.toFixed(2)} Jt`
                                ];
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Nilai Penjualan per Entitas',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    }
                }
            }
        });

        // 11. Grafik Distribusi Jumlah Penjualan per Entitas
        const ctx11 = document.getElementById('chartDistribusiJumlahEntitas').getContext('2d');
        
        new Chart(ctx11, {
            type: 'doughnut',
            data: {
                labels: entitasLabelsDistribusi,
                datasets: [{
                    data: jumlahPerEntitasChart,
                    backgroundColor: backgroundColorsEntitas.slice(0, entitasLabelsDistribusi.length),
                    borderColor: backgroundColorsEntitas.slice(0, entitasLabelsDistribusi.length).map(color => color),
                    borderWidth: 1,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 10
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                        
                                        return {
                                            text: `${label}: ${value.toLocaleString('id-ID')} (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor[i],
                                            lineWidth: data.datasets[0].borderWidth,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (value / total * 100).toFixed(1) : 0;
                                return [
                                    `${label}`,
                                    `Jumlah: ${value.toLocaleString('id-ID')} transaksi`,
                                    `Persentase: ${percentage}%`,
                                    `Rata-rata per transaksi: ${(totalNilaiSemua > 0 ? (context.dataset.data.reduce((a, b) => a + b, 0) / context.dataset.data.length).toFixed(0) : 0).toLocaleString('id-ID')}`
                                ];
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Volume Penjualan per Entitas',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    }
                }
            }
        });

        // Tambahkan chart perbandingan Nilai vs Jumlah per Entitas
        const ctxPerbandingan = document.createElement('canvas');
        document.getElementById('chartDistribusiNilaiEntitas').parentNode.appendChild(ctxPerbandingan);
        
        
        const ctxPerbandinganBar = document.getElementById('chartPerbandinganEntitas').getContext('2d');
        
        // Buat data untuk chart bar perbandingan
        const nilaiNormalized = nilaiPerEntitasJuta.map(val => val);
        const jumlahNormalized = jumlahPerEntitasChart.map(val => val);
        
        // Normalize jumlah untuk skala yang lebih baik
        const maxNilai = Math.max(...nilaiNormalized);
        const maxJumlah = Math.max(...jumlahNormalized);
        const jumlahScaled = jumlahNormalized.map(val => (val / maxJumlah) * maxNilai);
        
        new Chart(ctxPerbandinganBar, {
            type: 'bar',
            data: {
                labels: entitasLabelsDistribusi,
                datasets: [
                    {
                        label: 'Nilai Penjualan (Juta)',
                        data: nilaiNormalized,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Jumlah Transaksi (Scaled)',
                        data: jumlahScaled,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai (Juta Rupiah) / Jumlah (Scaled)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1);
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Nilai')) {
                                    label += ': ' + context.parsed.y.toFixed(2) + ' Juta';
                                } else if (label.includes('Jumlah')) {
                                    const index = context.dataIndex;
                                    const jumlahAsli = jumlahPerEntitasChart[index];
                                    label += ': ' + jumlahAsli.toLocaleString('id-ID') + ' transaksi (scaled: ' + context.parsed.y.toFixed(1) + ')';
                                }
                                return label;
                            },
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const nilai = nilaiPerEntitasChart[index];
                                const jumlah = jumlahPerEntitasChart[index];
                                
                                if (jumlah > 0) {
                                    const rataRata = nilai / jumlah;
                                    return [
                                        'Rata-rata per transaksi: Rp ' + formatFullNumber(rataRata),
                                        'Efisiensi: ' + (nilai / jumlah).toFixed(0)
                                    ];
                                }
                                return null;
                            }
                        }
                    }
                }
            }
        });

        // ==================== JAVASCRIPT UNTUK GRAFIK BIAYA GA ====================

        // Fungsi untuk print laporan
    function printReport() {
        window.print();
    }

    // Fungsi untuk export PDF (placeholder)
    function exportToPDF() {
        alert('Fitur ekspor PDF akan segera tersedia.');
    }

    // Fungsi untuk optimasi sebelum print
    function optimizeForPrint() {
    // Tambahkan class no-print ke elemen yang tidak perlu
    const noPrintElements = document.querySelectorAll('button, .btn, form, .no-print');
    noPrintElements.forEach(el => {
        if (!el.classList.contains('no-print')) {
        el.classList.add('no-print');
        }
    });
    
    // Tambahkan class keep-together ke card dan container utama
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('keep-together');
    });
    
    // Pastikan tabel header diulang
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const thead = table.querySelector('thead');
        if (thead) {
        thead.style.display = 'table-header-group';
        }
    });
    
    // Resize chart untuk print
    setTimeout(() => {
        if (typeof Chart !== 'undefined') {
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.resize();
        });
        }
    }, 100);
    
    return true;
    }

    // Event sebelum print
    window.addEventListener('beforeprint', function() {
    console.log('Mempersiapkan untuk cetak...');
    optimizeForPrint();
    
    // Optional: tambahkan watermark
    const watermark = document.createElement('div');
    watermark.className = 'watermark';
    watermark.textContent = 'SALINAN RESMI';
    document.body.appendChild(watermark);
    });

    // Event setelah print
    window.addEventListener('afterprint', function() {
    console.log('Selesai cetak, mengembalikan tampilan...');
    
    // Hapus watermark
    const watermark = document.querySelector('.watermark');
    if (watermark) {
        watermark.remove();
    }
    
    // Hapus class no-print yang ditambahkan
    const noPrintElements = document.querySelectorAll('.no-print');
    noPrintElements.forEach(el => {
        // Hanya hapus jika kita yang tambahkan
        if (el.tagName === 'BUTTON' || el.classList.contains('btn') || el.tagName === 'FORM') {
        el.classList.remove('no-print');
        }
    });
    });

    // Manual print dengan optimasi
    function printWithOptimization() {
    optimizeForPrint();
    
    // Tunggu sebentar untuk memastikan optimasi selesai
    setTimeout(() => {
        window.print();
    }, 500);
    }

    // Keyboard shortcut Ctrl+P
    document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault(); // Mencegah dialog print default
        printWithOptimization();
    }
    });  
    
    </script>
</body>
</html>